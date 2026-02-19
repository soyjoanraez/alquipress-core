<?php
/**
 * Módulo: Comunicación (Email)
 * Envío y registro de emails + bandeja de entrada por IMAP
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Communications
{
    const OPTION_KEY = 'alquipress_comm_settings';
    const POST_TYPE = 'alquipress_comm';
    const CRON_HOOK = 'alquipress_comm_fetch_inbox';

    public function __construct()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_section_assets']);

        add_action('admin_post_alquipress_comm_save_settings', [$this, 'handle_save_settings']);
        add_action('admin_post_alquipress_send_email', [$this, 'handle_send_email']);
        add_action('admin_post_alquipress_comm_fetch_now', [$this, 'handle_fetch_now']);
        
        // AJAX para funcionalidades adicionales
        add_action('wp_ajax_alquipress_comm_resend_email', [$this, 'ajax_resend_email']);
        add_action('wp_ajax_alquipress_comm_get_email_content', [$this, 'ajax_get_email_content']);
        add_action('wp_ajax_alquipress_comm_export_csv', [$this, 'ajax_export_csv']);
        add_action('wp_ajax_alquipress_comm_get_autocomplete', [$this, 'ajax_get_autocomplete']);

        add_action(self::CRON_HOOK, [$this, 'fetch_inbox']);
        add_filter('cron_schedules', [$this, 'register_cron_schedule']);
        add_action('admin_init', [$this, 'maybe_schedule_fetch']);

        add_action('phpmailer_init', [$this, 'configure_phpmailer']);
        add_filter('wp_mail_from', [$this, 'filter_mail_from']);
        add_filter('wp_mail_from_name', [$this, 'filter_mail_from_name']);
    }

    public function register_post_type()
    {
        register_post_type(self::POST_TYPE, [
            'label' => __('Comunicaciones', 'alquipress'),
            'public' => false,
            'show_ui' => false,
            'supports' => ['title', 'editor', 'author'],
        ]);
    }

    public function maybe_render_section($page)
    {
        if ($page !== 'alquipress-comunicacion') {
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('Lo siento, no tienes permisos para acceder a esta página.', 'alquipress'));
        }
        
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'manage';

        if ($tab === 'manage') {
            $this->render_page();
        } else {
            $this->render_inbox_page();
        }
    }

    public function enqueue_section_assets($page)
    {
        if ($page !== 'alquipress-comunicacion') {
            return;
        }
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'manage';

        if ($tab === 'manage') {
            wp_enqueue_style(
                'alquipress-communications',
                ALQUIPRESS_URL . 'includes/modules/communications/assets/communications.css',
                [],
                ALQUIPRESS_VERSION
            );

            wp_enqueue_script(
                'alquipress-communications',
                ALQUIPRESS_URL . 'includes/modules/communications/assets/communications.js',
                ['jquery'],
                ALQUIPRESS_VERSION,
                true
            );

            wp_localize_script('alquipress-communications', 'alquipressComm', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('alquipress_comm_nonce')
            ]);
        } else {
            wp_enqueue_style(
                'alquipress-inbox',
                ALQUIPRESS_URL . 'includes/modules/communications/assets/inbox.css',
                [],
                ALQUIPRESS_VERSION
            );

            wp_enqueue_script(
                'alquipress-inbox',
                ALQUIPRESS_URL . 'includes/modules/communications/assets/inbox.js',
                ['jquery'],
                ALQUIPRESS_VERSION,
                true
            );
        }
    }

    public function register_cron_schedule($schedules)
    {
        if (!isset($schedules['alquipress_15min'])) {
            $schedules['alquipress_15min'] = [
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display' => __('Cada 15 minutos', 'alquipress')
            ];
        }
        return $schedules;
    }

    public function maybe_schedule_fetch()
    {
        $settings = $this->get_settings();
        $enabled = !empty($settings['imap_enabled']);
        $has_creds = !empty($settings['imap_host']) && !empty($settings['imap_user']) && !empty($settings['imap_pass']);

        if ($enabled && $has_creds && !wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'alquipress_15min', self::CRON_HOOK);
        }

        if ((!$enabled || !$has_creds) && wp_next_scheduled(self::CRON_HOOK)) {
            $timestamp = wp_next_scheduled(self::CRON_HOOK);
            if ($timestamp) {
                wp_unschedule_event($timestamp, self::CRON_HOOK);
            }
        }
    }

    private function get_settings()
    {
        $defaults = [
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_secure' => 'tls',
            'smtp_from_email' => '',
            'smtp_from_name' => 'ALQUIPRESS',
            'imap_enabled' => 0,
            'imap_host' => '',
            'imap_port' => 993,
            'imap_user' => '',
            'imap_pass' => '',
            'imap_secure' => 'ssl',
            'imap_mailbox' => 'INBOX',
        ];
        $stored = get_option(self::OPTION_KEY, []);
        if (!is_array($stored)) {
            $stored = [];
        }
        return array_merge($defaults, $stored);
    }

    public function configure_phpmailer($phpmailer)
    {
        $settings = $this->get_settings();
        if (empty($settings['smtp_host']) || empty($settings['smtp_user']) || empty($settings['smtp_pass'])) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $settings['smtp_host'];
        $phpmailer->Port = (int) $settings['smtp_port'];
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $settings['smtp_user'];
        $phpmailer->Password = $settings['smtp_pass'];

        $secure = $settings['smtp_secure'];
        if (in_array($secure, ['ssl', 'tls'], true)) {
            $phpmailer->SMTPSecure = $secure;
        }
    }

    public function filter_mail_from($from)
    {
        $settings = $this->get_settings();
        return !empty($settings['smtp_from_email']) ? $settings['smtp_from_email'] : $from;
    }

    public function filter_mail_from_name($name)
    {
        $settings = $this->get_settings();
        return !empty($settings['smtp_from_name']) ? $settings['smtp_from_name'] : $name;
    }

    public function handle_save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para guardar estos ajustes.', 'alquipress'));
        }
        check_admin_referer('alquipress_comm_settings');

        $settings = [
            'smtp_host' => sanitize_text_field($_POST['smtp_host'] ?? ''),
            'smtp_port' => absint($_POST['smtp_port'] ?? 587),
            'smtp_user' => sanitize_text_field($_POST['smtp_user'] ?? ''),
            'smtp_pass' => sanitize_text_field($_POST['smtp_pass'] ?? ''),
            'smtp_secure' => sanitize_text_field($_POST['smtp_secure'] ?? 'tls'),
            'smtp_from_email' => sanitize_email($_POST['smtp_from_email'] ?? ''),
            'smtp_from_name' => sanitize_text_field($_POST['smtp_from_name'] ?? ''),
            'imap_enabled' => !empty($_POST['imap_enabled']) ? 1 : 0,
            'imap_host' => sanitize_text_field($_POST['imap_host'] ?? ''),
            'imap_port' => absint($_POST['imap_port'] ?? 993),
            'imap_user' => sanitize_text_field($_POST['imap_user'] ?? ''),
            'imap_pass' => sanitize_text_field($_POST['imap_pass'] ?? ''),
            'imap_secure' => sanitize_text_field($_POST['imap_secure'] ?? 'ssl'),
            'imap_mailbox' => sanitize_text_field($_POST['imap_mailbox'] ?? 'INBOX'),
        ];

        update_option(self::OPTION_KEY, $settings);
        $this->maybe_schedule_fetch();

        wp_safe_redirect(admin_url('admin.php?page=alquipress-comunicacion&saved=1'));
        exit;
    }

    public function handle_send_email()
    {
        if (!current_user_can('edit_posts')) {
            wp_die(__('No tienes permisos para enviar emails.', 'alquipress'));
        }
        check_admin_referer('alquipress_comm_send');

        $to = sanitize_email($_POST['to_email'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = wp_kses_post($_POST['message'] ?? '');
        $entity_type = sanitize_text_field($_POST['entity_type'] ?? '');
        $entity_id = absint($_POST['entity_id'] ?? 0);

        if (empty($to) || empty($subject) || empty($message)) {
            wp_safe_redirect(admin_url('admin.php?page=alquipress-comunicacion&error=missing'));
            exit;
        }

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $template = $this->render_email_template($subject, $message);
        $sent = wp_mail($to, $subject, $template, $headers);

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $subject,
            'post_content' => $message,
            'post_author' => get_current_user_id(),
        ]);

        if ($post_id) {
            update_post_meta($post_id, 'ap_comm_direction', 'outbound');
            update_post_meta($post_id, 'ap_comm_to', $to);
            update_post_meta($post_id, 'ap_comm_status', $sent ? 'sent' : 'error');
            update_post_meta($post_id, 'ap_comm_entity_type', $entity_type);
            update_post_meta($post_id, 'ap_comm_entity_id', $entity_id);
            if (!$sent) {
                update_post_meta($post_id, 'ap_comm_error', __('Error al enviar el email.', 'alquipress'));
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=alquipress-comunicacion&sent=' . ($sent ? '1' : '0')));
        exit;
    }

    private function render_email_template($subject, $message)
    {
        $safe_subject = esc_html($subject);
        $safe_message = wpautop(wp_kses_post($message));
        $brand = esc_html__('ALQUIPRESS', 'alquipress');
        $year = date('Y');

        // Plantilla simple y limpia coherente con el CRM
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Inter, -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f8fafb;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; border: 1px solid #e8eef3; overflow: hidden;">
                    <tr>
                        <td style="padding: 32px;">
                            <h1 style="margin: 0 0 20px 0; font-size: 24px; font-weight: 600; color: #0e161b; line-height: 1.3;">' . $safe_subject . '</h1>
                            <div style="font-size: 15px; line-height: 1.6; color: #334155;">' . $safe_message . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 32px; background-color: #f8fafb; border-top: 1px solid #e8eef3;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8; text-align: center;">' . $brand . ' · ' . $year . '</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    public function handle_fetch_now()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos.', 'alquipress'));
        }
        check_admin_referer('alquipress_comm_fetch');

        $this->fetch_inbox();
        update_option('alquipress_comm_last_sync', current_time('mysql'));
        wp_safe_redirect(admin_url('admin.php?page=alquipress-comunicacion&fetched=1'));
        exit;
    }

    public function ajax_resend_email()
    {
        check_ajax_referer('alquipress_comm_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'alquipress')]);
            return;
        }
        
        $email_id = isset($_POST['email_id']) ? absint($_POST['email_id']) : 0;
        if (!$email_id) {
            wp_send_json_error(['message' => __('ID de email no válido', 'alquipress')]);
            return;
        }
        
        $post = get_post($email_id);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            wp_send_json_error(['message' => __('Email no encontrado', 'alquipress')]);
            return;
        }
        
        $to = get_post_meta($email_id, 'ap_comm_to', true);
        $subject = $post->post_title;
        $message = $post->post_content;
        
        if (empty($to) || empty($subject) || empty($message)) {
            wp_send_json_error(['message' => __('Datos incompletos para reenviar', 'alquipress')]);
            return;
        }
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $template = $this->render_email_template($subject, $message);
        $sent = wp_mail($to, $subject, $template, $headers);
        
        if ($sent) {
            update_post_meta($email_id, 'ap_comm_status', 'sent');
            delete_post_meta($email_id, 'ap_comm_error');
            wp_send_json_success(['message' => __('Email reenviado correctamente', 'alquipress')]);
        } else {
            update_post_meta($email_id, 'ap_comm_status', 'error');
            update_post_meta($email_id, 'ap_comm_error', __('Error al reenviar el email', 'alquipress'));
            wp_send_json_error(['message' => __('Error al reenviar el email', 'alquipress')]);
        }
    }

    public function ajax_get_email_content()
    {
        check_ajax_referer('alquipress_comm_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'alquipress')]);
            return;
        }
        
        $email_id = isset($_POST['email_id']) ? absint($_POST['email_id']) : 0;
        if (!$email_id) {
            wp_send_json_error(['message' => __('ID de email no válido', 'alquipress')]);
            return;
        }
        
        $post = get_post($email_id);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            wp_send_json_error(['message' => __('Email no encontrado', 'alquipress')]);
            return;
        }
        
        $direction = get_post_meta($email_id, 'ap_comm_direction', true);
        $to = get_post_meta($email_id, 'ap_comm_to', true);
        $from = get_post_meta($email_id, 'ap_comm_from', true);
        $status = get_post_meta($email_id, 'ap_comm_status', true);
        
        wp_send_json_success([
            'subject' => $post->post_title,
            'content' => wp_kses_post($post->post_content),
            'direction' => $direction,
            'to' => $to,
            'from' => $from,
            'status' => $status,
            'date' => wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($post->post_date))
        ]);
    }

    public function ajax_export_csv()
    {
        // Verificar nonce
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'alquipress_comm_nonce')) {
            wp_die(__('Error de seguridad', 'alquipress'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes', 'alquipress'));
        }
        
        // Obtener filtros (pueden venir de GET o POST)
        $filters = [
            'direction' => isset($_REQUEST['filter_direction']) ? sanitize_text_field($_REQUEST['filter_direction']) : '',
            'status' => isset($_REQUEST['filter_status']) ? sanitize_text_field($_REQUEST['filter_status']) : '',
            'entity_type' => isset($_REQUEST['filter_entity_type']) ? sanitize_text_field($_REQUEST['filter_entity_type']) : '',
            'entity_id' => isset($_REQUEST['filter_entity_id']) ? absint($_REQUEST['filter_entity_id']) : 0,
            'owner_id' => isset($_REQUEST['filter_owner_id']) ? absint($_REQUEST['filter_owner_id']) : 0,
            'guest_id' => isset($_REQUEST['filter_guest_id']) ? absint($_REQUEST['filter_guest_id']) : 0,
            'date_from' => isset($_REQUEST['filter_date_from']) ? sanitize_text_field($_REQUEST['filter_date_from']) : '',
            'date_to' => isset($_REQUEST['filter_date_to']) ? sanitize_text_field($_REQUEST['filter_date_to']) : '',
        ];
        
        $items = $this->get_recent_communications(-1, $filters);
        
        // Enviar headers para descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=comunicaciones_' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            __('Fecha', 'alquipress'),
            __('Tipo', 'alquipress'),
            __('Asunto', 'alquipress'),
            __('Destinatario', 'alquipress'),
            __('Remitente', 'alquipress'),
            __('Estado', 'alquipress'),
            __('Entidad', 'alquipress'),
            __('ID Entidad', 'alquipress')
        ], ';');
        
        foreach ($items as $item) {
            $direction = get_post_meta($item->ID, 'ap_comm_direction', true);
            $status = get_post_meta($item->ID, 'ap_comm_status', true);
            $to = get_post_meta($item->ID, 'ap_comm_to', true);
            $from = get_post_meta($item->ID, 'ap_comm_from', true);
            $entity_type = get_post_meta($item->ID, 'ap_comm_entity_type', true);
            $entity_id = get_post_meta($item->ID, 'ap_comm_entity_id', true);
            
            fputcsv($output, [
                wp_date('Y-m-d H:i:s', strtotime($item->post_date)),
                $direction === 'inbound' ? __('Entrada', 'alquipress') : __('Salida', 'alquipress'),
                $item->post_title,
                $to ?: '',
                $from ?: '',
                $status ?: '',
                $entity_type ?: '',
                $entity_id ?: ''
            ], ';');
        }
        
        fclose($output);
        wp_die(); // Terminar correctamente sin output adicional
    }

    public function ajax_get_autocomplete()
    {
        check_ajax_referer('alquipress_comm_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'alquipress')]);
            return;
        }
        
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        if (strlen($term) < 2) {
            wp_send_json_success(['results' => []]);
            return;
        }
        
        $results = [];
        
        // Buscar propietarios
        $owners = get_posts([
            'post_type' => 'propietario',
            'post_status' => 'publish',
            'numberposts' => 10,
            's' => $term,
            'fields' => 'ids'
        ]);
        
        foreach ($owners as $owner_id) {
            $email = get_post_meta($owner_id, 'owner_email_management', true);
            $name = get_the_title($owner_id);
            if ($email) {
                $results[] = [
                    'id' => $email,
                    'text' => $name . ' (' . $email . ')',
                    'type' => 'propietario',
                    'entity_id' => $owner_id
                ];
            }
        }
        
        // Buscar usuarios/clientes
        $users = get_users([
            'number' => 10,
            'search' => '*' . $term . '*',
            'search_columns' => ['user_email', 'display_name', 'user_login']
        ]);
        
        foreach ($users as $user) {
            if ($user->user_email) {
                $results[] = [
                    'id' => $user->user_email,
                    'text' => ($user->display_name ?: $user->user_login) . ' (' . $user->user_email . ')',
                    'type' => 'cliente',
                    'entity_id' => $user->ID
                ];
            }
        }
        
        wp_send_json_success(['results' => $results]);
    }

    public function fetch_inbox()
    {
        $settings = $this->get_settings();
        if (empty($settings['imap_enabled']) || empty($settings['imap_host']) || empty($settings['imap_user']) || empty($settings['imap_pass'])) {
            return;
        }

        if (!function_exists('imap_open')) {
            return;
        }

        $mailbox = $this->build_imap_mailbox($settings);
        $inbox = @imap_open($mailbox, $settings['imap_user'], $settings['imap_pass']);
        if (!$inbox) {
            return;
        }

        $emails = imap_search($inbox, 'UNSEEN');
        if (!$emails) {
            imap_close($inbox);
            return;
        }

        foreach ($emails as $email_number) {
            $header = imap_headerinfo($inbox, $email_number);
            $subject = isset($header->subject) ? $this->decode_imap_header($header->subject) : __('Sin asunto', 'alquipress');
            $from_email = '';
            if (!empty($header->from[0]->mailbox) && !empty($header->from[0]->host)) {
                $from_email = $header->from[0]->mailbox . '@' . $header->from[0]->host;
            }

            $message_id = !empty($header->message_id) ? (string) $header->message_id : '';
            if ($message_id && $this->has_message_id($message_id)) {
                continue;
            }

            $body = $this->get_imap_body($inbox, $email_number);
            $post_id = wp_insert_post([
                'post_type' => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title' => $subject,
                'post_content' => wp_kses_post($body),
                'post_author' => 0,
            ]);

            if ($post_id) {
                update_post_meta($post_id, 'ap_comm_direction', 'inbound');
                update_post_meta($post_id, 'ap_comm_from', $from_email);
                update_post_meta($post_id, 'ap_comm_status', 'received');
                update_post_meta($post_id, 'ap_comm_message_id', $message_id);

                $entity = $this->match_entity_by_email($from_email);
                if ($entity) {
                    update_post_meta($post_id, 'ap_comm_entity_type', $entity['type']);
                    update_post_meta($post_id, 'ap_comm_entity_id', $entity['id']);
                }
            }

            imap_setflag_full($inbox, (string) $email_number, "\\Seen");
        }

        imap_close($inbox);
        update_option('alquipress_comm_last_sync', current_time('mysql'));
    }

    private function build_imap_mailbox($settings)
    {
        $secure = $settings['imap_secure'];
        $secure = in_array($secure, ['ssl', 'tls'], true) ? '/' . $secure : '';
        $mailbox = '{' . $settings['imap_host'] . ':' . (int) $settings['imap_port'] . '/imap' . $secure . '}' . $settings['imap_mailbox'];
        return $mailbox;
    }

    private function decode_imap_header($text)
    {
        $decoded = imap_mime_header_decode($text);
        if (!is_array($decoded)) {
            return $text;
        }
        $parts = [];
        foreach ($decoded as $part) {
            $parts[] = $part->text;
        }
        return trim(implode('', $parts));
    }

    private function get_imap_body($inbox, $email_number)
    {
        $structure = imap_fetchstructure($inbox, $email_number);
        if (!$structure) {
            return '';
        }

        if (!isset($structure->parts)) {
            $body = imap_body($inbox, $email_number);
            return $this->decode_imap_body($body, $structure->encoding);
        }

        foreach ($structure->parts as $index => $part) {
            $part_number = $index + 1;
            if ($part->subtype === 'HTML') {
                $body = imap_fetchbody($inbox, $email_number, (string) $part_number);
                return $this->decode_imap_body($body, $part->encoding);
            }
        }

        foreach ($structure->parts as $index => $part) {
            $part_number = $index + 1;
            if ($part->subtype === 'PLAIN') {
                $body = imap_fetchbody($inbox, $email_number, (string) $part_number);
                return nl2br(esc_html($this->decode_imap_body($body, $part->encoding)));
            }
        }

        return '';
    }

    private function decode_imap_body($body, $encoding)
    {
        if ($encoding === 3) {
            return base64_decode($body);
        }
        if ($encoding === 4) {
            return quoted_printable_decode($body);
        }
        return $body;
    }

    private function has_message_id($message_id)
    {
        $existing = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'any',
            'numberposts' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'ap_comm_message_id',
                    'value' => $message_id,
                    'compare' => '=',
                ],
            ],
        ]);
        return !empty($existing);
    }

    private function match_entity_by_email($email)
    {
        $email = sanitize_email($email);
        if ($email === '') {
            return null;
        }

        $owner = get_posts([
            'post_type' => 'propietario',
            'post_status' => 'publish',
            'numberposts' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'owner_email_management',
                    'value' => $email,
                    'compare' => '=',
                ],
            ],
        ]);
        if (!empty($owner)) {
            return ['type' => 'propietario', 'id' => (int) $owner[0]];
        }

        $user = get_user_by('email', $email);
        if ($user) {
            return ['type' => 'cliente', 'id' => (int) $user->ID];
        }

        return null;
    }

    private function get_imap_sync_status()
    {
        $settings = $this->get_settings();
        $enabled = !empty($settings['imap_enabled']);
        $has_creds = !empty($settings['imap_host']) && !empty($settings['imap_user']) && !empty($settings['imap_pass']);
        $active = $enabled && $has_creds && wp_next_scheduled(self::CRON_HOOK);
        
        $last_sync = get_option('alquipress_comm_last_sync', '');
        $last_sync_formatted = $last_sync ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync)) : '';
        
        return [
            'enabled' => $enabled,
            'active' => (bool) $active,
            'last_sync' => $last_sync_formatted,
            'next_sync' => $active ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), wp_next_scheduled(self::CRON_HOOK)) : ''
        ];
    }

    private function get_entity_url($entity_type, $entity_id)
    {
        switch ($entity_type) {
            case 'propietario':
                return admin_url('post.php?post=' . $entity_id . '&action=edit');
            case 'cliente':
                return admin_url('user-edit.php?user_id=' . $entity_id);
            case 'reserva':
                return admin_url('post.php?post=' . $entity_id . '&action=edit');
            default:
                return '#';
        }
    }

    private function get_metrics()
    {
        $today = date('Y-m-d') . ' 00:00:00';
        $today_end = date('Y-m-d') . ' 23:59:59';
        $week_start = date('Y-m-d', strtotime('monday this week')) . ' 00:00:00';
        $week_end = date('Y-m-d', strtotime('sunday this week')) . ' 23:59:59';
        $month_start = date('Y-m-01') . ' 00:00:00';
        $month_end = date('Y-m-t') . ' 23:59:59';
        $year = date('Y');
        $year_start = $year . '-01-01 00:00:00';
        $year_end = $year . '-12-31 23:59:59';

        // Enviados
        $sent_today = $this->count_by_direction('outbound', $today, $today_end);
        $sent_week = $this->count_by_direction('outbound', $week_start, $week_end);
        $sent_month = $this->count_by_direction('outbound', $month_start, $month_end);
        $sent_year = $this->count_by_direction('outbound', $year_start, $year_end);

        // Recibidos
        $incoming_today = $this->count_by_direction('inbound', $today, $today_end);
        $incoming_week = $this->count_by_direction('inbound', $week_start, $week_end);
        $incoming_month = $this->count_by_direction('inbound', $month_start, $month_end);
        $incoming_year = $this->count_by_direction('inbound', $year_start, $year_end);

        // Errores
        $errors_total = $this->count_by_status('error', $year_start, $year_end);
        
        // Tasa de éxito
        $total_sent = $sent_year;
        $success_rate = $total_sent > 0 ? round((($total_sent - $errors_total) / $total_sent) * 100, 1) : 100;

        return [
            'sent_today' => $sent_today,
            'sent_week' => $sent_week,
            'sent_month' => $sent_month,
            'sent_year' => $sent_year,
            'incoming_today' => $incoming_today,
            'incoming_week' => $incoming_week,
            'incoming_month' => $incoming_month,
            'incoming_year' => $incoming_year,
            'errors_total' => $errors_total,
            'success_rate' => $success_rate,
        ];
    }

    private function count_by_direction($direction, $start, $end)
    {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $start,
                    'before' => $end,
                    'inclusive' => true,
                ],
            ],
            'meta_query' => [
                [
                    'key' => 'ap_comm_direction',
                    'value' => $direction,
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
            'posts_per_page' => -1,
        ]);
        return (int) $query->found_posts;
    }

    private function count_by_status($status, $start, $end)
    {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $start,
                    'before' => $end,
                    'inclusive' => true,
                ],
            ],
            'meta_query' => [
                [
                    'key' => 'ap_comm_status',
                    'value' => $status,
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
            'posts_per_page' => -1,
        ]);
        return (int) $query->found_posts;
    }

    private function get_recent_communications($limit = 50, $filters = [])
    {
        $args = [
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [],
        ];

        // Filtro por dirección
        if (!empty($filters['direction'])) {
            $args['meta_query'][] = [
                'key' => 'ap_comm_direction',
                'value' => $filters['direction'],
                'compare' => '='
            ];
        }

        // Filtro por estado
        if (!empty($filters['status'])) {
            $args['meta_query'][] = [
                'key' => 'ap_comm_status',
                'value' => $filters['status'],
                'compare' => '='
            ];
        }

        $entity_filters = [];

        if (!empty($filters['entity_type']) || !empty($filters['entity_id'])) {
            $entity_group = ['relation' => 'AND'];
            if (!empty($filters['entity_type'])) {
                $entity_group[] = [
                    'key' => 'ap_comm_entity_type',
                    'value' => sanitize_text_field($filters['entity_type']),
                    'compare' => '='
                ];
            }
            if (!empty($filters['entity_id'])) {
                $entity_group[] = [
                    'key' => 'ap_comm_entity_id',
                    'value' => absint($filters['entity_id']),
                    'compare' => '='
                ];
            }
            if (count($entity_group) > 1) {
                $entity_filters[] = $entity_group;
            }
        }

        if (!empty($filters['owner_id'])) {
            $entity_filters[] = [
                'relation' => 'AND',
                [
                    'key' => 'ap_comm_entity_type',
                    'value' => 'propietario',
                    'compare' => '='
                ],
                [
                    'key' => 'ap_comm_entity_id',
                    'value' => absint($filters['owner_id']),
                    'compare' => '='
                ],
            ];
        }

        if (!empty($filters['guest_id'])) {
            $entity_filters[] = [
                'relation' => 'AND',
                [
                    'key' => 'ap_comm_entity_type',
                    'value' => 'cliente',
                    'compare' => '='
                ],
                [
                    'key' => 'ap_comm_entity_id',
                    'value' => absint($filters['guest_id']),
                    'compare' => '='
                ],
            ];
        }

        if (count($entity_filters) === 1) {
            $args['meta_query'][] = $entity_filters[0];
        } elseif (count($entity_filters) > 1) {
            $args['meta_query'][] = array_merge(['relation' => 'OR'], $entity_filters);
        }

        // Filtro por rango de fechas
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $args['date_query'] = [];
            if (!empty($filters['date_from'])) {
                $args['date_query']['after'] = sanitize_text_field($filters['date_from']) . ' 00:00:00';
                $args['date_query']['inclusive'] = true;
            }
            if (!empty($filters['date_to'])) {
                $args['date_query']['before'] = sanitize_text_field($filters['date_to']) . ' 23:59:59';
                $args['date_query']['inclusive'] = true;
            }
        }

        // Búsqueda por asunto o email
        if (!empty($filters['search'])) {
            $args['s'] = sanitize_text_field($filters['search']);
        }

        if (!empty($args['meta_query'])) {
            $args['meta_query']['relation'] = 'AND';
        }

        return get_posts($args);
    }

    /**
     * Renderizar página Inbox Omnicanal: layout 3 columnas con datos mock.
     */
    private function render_inbox_page()
    {
        $mock_conversations = [
            [
                'id' => 'conv_1',
                'guest' => 'María García',
                'channel' => 'whatsapp',
                'last_message' => __('¿A qué hora es el check-in?', 'alquipress'),
                'last_at' => __('Hace 5 min', 'alquipress'),
                'booking_id' => 123,
                'prop_name' => 'Villa Sol',
                'checkin_today' => true,
                'unread' => 1,
            ],
            [
                'id' => 'conv_2',
                'guest' => 'Juan Pérez',
                'channel' => 'airbnb',
                'last_message' => __('Confirmación de reserva recibida', 'alquipress'),
                'last_at' => __('Hace 2 h', 'alquipress'),
                'booking_id' => 124,
                'prop_name' => 'Apartamento Centro',
                'checkin_today' => false,
                'unread' => 0,
            ],
            [
                'id' => 'conv_3',
                'guest' => 'Ana Martínez',
                'channel' => 'booking',
                'last_message' => __('¿Hay parking disponible?', 'alquipress'),
                'last_at' => __('Ayer', 'alquipress'),
                'booking_id' => 125,
                'prop_name' => 'Casa Playa',
                'checkin_today' => false,
                'unread' => 0,
            ],
        ];

        $mock_messages = [
            ['role' => 'guest', 'content' => __('Hola, tengo una reserva para la Villa Sol la próxima semana. ¿A qué hora es el check-in?', 'alquipress'), 'time' => __('10:32', 'alquipress')],
            ['role' => 'staff', 'content' => __('Hola María, el check-in es a las 16:00. Te enviaré las coordenadas y el código de la caja de llaves el día anterior.', 'alquipress'), 'time' => __('10:45', 'alquipress')],
            ['role' => 'note', 'content' => __('Ojo: Este cliente ha pedido toallas extra, ya se las he dejado en el armario.', 'alquipress'), 'time' => __('10:50', 'alquipress')],
            ['role' => 'guest', 'content' => __('Perfecto, muchas gracias.', 'alquipress'), 'time' => __('11:02', 'alquipress')],
        ];

        $channel_icons = [
            'whatsapp' => '✓',
            'airbnb' => 'A',
            'booking' => 'B',
            'email' => '✉',
        ];

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap alquipress-inbox-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('communications'); ?>
                <main class="ap-owners-main" style="padding:0; display:flex; flex-direction:column;">
                    <header class="ap-header" style="padding:20px 32px; border-bottom:1px solid var(--ap-border);">
                        <div class="ap-header-left">
                            <h1 class="ap-header-title"><?php esc_html_e('Inbox', 'alquipress'); ?></h1>
                            <p class="ap-header-subtitle"><?php esc_html_e('Comunicación unificada con huéspedes y propietarios.', 'alquipress'); ?></p>
                        </div>
                    </header>

                    <div class="ap-comm-tabs" style="padding: 0 32px; border-bottom: 1px solid var(--ap-border); background: #fff;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-comunicacion&tab=inbox')); ?>" class="ap-comm-tab is-active" style="display:inline-block; padding: 16px 20px; text-decoration:none; color:var(--ap-primary); border-bottom: 2px solid var(--ap-primary); font-weight: 600;">
                            <?php esc_html_e('Omnicanal Inbox', 'alquipress'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-comunicacion&tab=manage')); ?>" class="ap-comm-tab" style="display:inline-block; padding: 16px 20px; text-decoration:none; color:var(--ap-text-secondary); border-bottom: 2px solid transparent; font-weight: 600;">
                            <?php esc_html_e('Histórico y Ajustes SMTP', 'alquipress'); ?>
                        </a>
                    </div>

                    <div class="ap-inbox">
                        <div class="ap-inbox-conversations">
                            <div class="ap-inbox-tabs">
                                <button type="button" class="ap-inbox-tab is-active"><?php esc_html_e('Pendientes', 'alquipress'); ?></button>
                                <button type="button" class="ap-inbox-tab"><?php esc_html_e('Míos', 'alquipress'); ?></button>
                                <button type="button" class="ap-inbox-tab"><?php esc_html_e('Archivados', 'alquipress'); ?></button>
                            </div>
                            <div class="ap-inbox-conversation-list">
                                <?php foreach ($mock_conversations as $i => $conv) : ?>
                                    <div class="ap-inbox-conv-item <?php echo $i === 0 ? 'is-active' : ''; ?> <?php echo $conv['checkin_today'] ? 'is-urgent' : ''; ?>" data-conv-id="<?php echo esc_attr($conv['id']); ?>">
                                        <div class="ap-inbox-conv-avatar-wrap">
                                            <div class="ap-inbox-conv-avatar"><?php echo esc_html(strtoupper(substr($conv['guest'], 0, 1))); ?></div>
                                            <span class="ap-inbox-conv-channel" title="<?php echo esc_attr(ucfirst($conv['channel'])); ?>"><?php echo esc_html($channel_icons[$conv['channel']] ?? '?'); ?></span>
                                        </div>
                                        <div class="ap-inbox-conv-body">
                                            <div class="ap-inbox-conv-header">
                                                <span class="ap-inbox-conv-guest"><?php echo esc_html($conv['guest']); ?></span>
                                                <?php if ($conv['unread'] > 0) : ?><span class="ap-inbox-conv-unread"><?php echo (int) $conv['unread']; ?></span><?php endif; ?>
                                            </div>
                                            <div class="ap-inbox-conv-preview"><?php echo esc_html($conv['last_message']); ?></div>
                                            <div class="ap-inbox-conv-time"><?php echo esc_html($conv['last_at']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="ap-inbox-thread">
                            <div class="ap-inbox-thread-empty" style="<?php echo count($mock_conversations) > 0 ? 'display:none;' : ''; ?>">
                                <span class="dashicons dashicons-email-alt"></span>
                                <p><?php esc_html_e('Selecciona una conversación', 'alquipress'); ?></p>
                            </div>
                            <div class="ap-inbox-messages" style="<?php echo count($mock_conversations) > 0 ? '' : 'display:none;'; ?>">
                                <?php foreach ($mock_messages as $msg) : ?>
                                    <div class="ap-inbox-msg ap-inbox-msg-role-<?php echo esc_attr($msg['role']); ?>">
                                        <div class="ap-inbox-msg-content"><?php echo esc_html($msg['content']); ?></div>
                                        <div class="ap-inbox-msg-time"><?php echo esc_html($msg['time']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="ap-inbox-toolbar">
                                <div class="ap-inbox-toolbar-actions">
                                    <button type="button" class="ap-inbox-auto-redact" disabled><?php esc_html_e('Auto-Redactar', 'alquipress'); ?></button>
                                </div>
                                <div class="ap-inbox-input-wrap">
                                    <button type="button" class="ap-inbox-ghost-toggle" title="<?php esc_attr_e('Modo Colaboración: nota interna (amarillo) o mensaje al cliente (azul)', 'alquipress'); ?>" aria-label="<?php esc_attr_e('Toggle modo colaboración', 'alquipress'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                    <textarea class="ap-inbox-input" rows="2" placeholder="<?php esc_attr_e('Escribe un mensaje...', 'alquipress'); ?>"></textarea>
                                    <button type="button" class="ap-inbox-send"><?php esc_html_e('Enviar', 'alquipress'); ?></button>
                                </div>
                            </div>
                        </div>

                        <div class="ap-inbox-context">
                            <?php if (count($mock_conversations) > 0) : $ctx = $mock_conversations[0]; ?>
                                <div class="ap-inbox-context-card">
                                    <div class="ap-inbox-context-photo"></div>
                                    <div class="ap-inbox-context-details">
                                        <div class="ap-inbox-context-prop"><?php echo esc_html($ctx['prop_name']); ?></div>
                                        <div class="ap-inbox-context-dates"><?php esc_html_e('15 Feb - 22 Feb 2026', 'alquipress'); ?></div>
                                        <span class="ap-inbox-context-status paid"><?php esc_html_e('Pagado', 'alquipress'); ?></span>
                                    </div>
                                </div>
                                <div class="ap-inbox-context-actions">
                                    <a href="#" class="ap-inbox-ctx-btn"><?php esc_html_e('Extender estancia', 'alquipress'); ?></a>
                                    <a href="#" class="ap-inbox-ctx-btn"><?php esc_html_e('Solicitar pago', 'alquipress'); ?></a>
                                    <a href="tel:" class="ap-inbox-ctx-btn"><span class="dashicons dashicons-phone" style="font-size:16px;width:16px;height:16px;"></span> <?php esc_html_e('Llamar ahora', 'alquipress'); ?></a>
                                </div>
                            <?php else : ?>
                                <div class="ap-inbox-context-empty"><?php esc_html_e('Selecciona una conversación para ver el contexto de la reserva.', 'alquipress'); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <?php
    }

    private function render_page()
    {
        $settings = $this->get_settings();
        $metrics = $this->get_metrics();
        $saved = isset($_GET['saved']);
        $sent = isset($_GET['sent']) ? sanitize_text_field($_GET['sent']) : '';
        $error = isset($_GET['error']);
        $fetched = isset($_GET['fetched']);

        // Obtener filtros del GET
        $filters = [
            'direction' => isset($_GET['filter_direction']) ? sanitize_text_field($_GET['filter_direction']) : '',
            'status' => isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '',
            'entity_type' => isset($_GET['filter_entity_type']) ? sanitize_text_field($_GET['filter_entity_type']) : '',
            'entity_id' => isset($_GET['filter_entity_id']) ? absint($_GET['filter_entity_id']) : 0,
            'owner_id' => isset($_GET['filter_owner_id']) ? absint($_GET['filter_owner_id']) : 0,
            'guest_id' => isset($_GET['filter_guest_id']) ? absint($_GET['filter_guest_id']) : 0,
            'date_from' => isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '',
            'date_to' => isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '',
            'search' => isset($_GET['filter_search']) ? sanitize_text_field($_GET['filter_search']) : '',
        ];

        // Obtener comunicaciones filtradas
        $items = $this->get_recent_communications(50, $filters);

        // Obtener listas para dropdowns
        $owners = get_posts([
            'post_type' => 'propietario',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids'
        ]);

        $users = get_users([
            'number' => -1,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ]);

        // Estado de sincronización IMAP
        $imap_status = $this->get_imap_sync_status();

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap alquipress-dashboard-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('communications'); ?>
                <main class="ap-owners-main">
                    <header class="ap-header">
                        <div class="ap-header-left">
                            <h1 class="ap-header-title"><?php esc_html_e('Comunicación', 'alquipress'); ?></h1>
                            <p class="ap-header-subtitle"><?php esc_html_e('Envía emails y registra todo el histórico de conversaciones.', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-header-right">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline;">
                                <?php wp_nonce_field('alquipress_comm_fetch'); ?>
                                <input type="hidden" name="action" value="alquipress_comm_fetch_now">
                                <button type="submit" class="button"><?php esc_html_e('Sincronizar bandeja', 'alquipress'); ?></button>
                            </form>
                        </div>
                    </header>

                    <?php $tab = 'manage'; ?>
                    <div class="ap-comm-tabs" style="padding: 0 32px; border-bottom: 1px solid var(--ap-border); background: #fff;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-comunicacion&tab=inbox')); ?>" class="ap-comm-tab" style="display:inline-block; padding: 16px 20px; text-decoration:none; color:var(--ap-text-secondary); border-bottom: 2px solid transparent; font-weight: 600;">
                            <?php esc_html_e('Omnicanal Inbox', 'alquipress'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-comunicacion&tab=manage')); ?>" class="ap-comm-tab is-active" style="display:inline-block; padding: 16px 20px; text-decoration:none; color:var(--ap-primary); border-bottom: 2px solid var(--ap-primary); font-weight: 600;">
                            <?php esc_html_e('Histórico y Ajustes SMTP', 'alquipress'); ?>
                        </a>
                    </div>

                    <?php if ($saved): ?>
                        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Ajustes guardados correctamente.', 'alquipress'); ?></p></div>
                    <?php endif; ?>
                    <?php if ($sent === '1'): ?>
                        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Email enviado correctamente.', 'alquipress'); ?></p></div>
                    <?php elseif ($sent === '0'): ?>
                        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('No se pudo enviar el email. Revisa el SMTP.', 'alquipress'); ?></p></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Faltan campos obligatorios en el envío.', 'alquipress'); ?></p></div>
                    <?php endif; ?>
                    <?php if ($fetched): ?>
                        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Bandeja sincronizada.', 'alquipress'); ?></p></div>
                    <?php endif; ?>

                    <section class="ap-owners-metrics-row ap-comm-metrics">
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Enviados hoy', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo esc_html($metrics['sent_today']); ?></span>
                                <span class="ap-metric-change ap-change-info"><?php echo esc_html($metrics['sent_month']); ?> este mes</span>
                            </div>
                        </div>
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Recibidos hoy', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo esc_html($metrics['incoming_today']); ?></span>
                                <span class="ap-metric-change ap-change-info"><?php echo esc_html($metrics['incoming_month']); ?> este mes</span>
                            </div>
                        </div>
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Tasa de éxito', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo esc_html($metrics['success_rate']); ?>%</span>
                                <span class="ap-metric-change ap-change-info"><?php echo esc_html($metrics['errors_total']); ?> errores</span>
                            </div>
                        </div>
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Total año', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo esc_html($metrics['sent_year'] + $metrics['incoming_year']); ?></span>
                                <span class="ap-metric-change ap-change-info"><?php echo esc_html($metrics['sent_year']); ?> enviados</span>
                            </div>
                        </div>
                    </section>

                    <?php if ($imap_status['enabled']): ?>
                        <div class="ap-comm-imap-status">
                            <span class="ap-comm-status-label"><?php esc_html_e('IMAP:', 'alquipress'); ?></span>
                            <span class="ap-comm-status-value <?php echo $imap_status['active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $imap_status['active'] ? esc_html__('Activo', 'alquipress') : esc_html__('Inactivo', 'alquipress'); ?>
                            </span>
                            <?php if ($imap_status['last_sync']): ?>
                                <span class="ap-comm-status-meta"><?php echo esc_html(sprintf(__('Última sincronización: %s', 'alquipress'), $imap_status['last_sync'])); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="ap-communications-grid">
                        <section class="ap-communications-card">
                            <h2><?php esc_html_e('Enviar email', 'alquipress'); ?></h2>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="ap-comm-send-form">
                                <?php wp_nonce_field('alquipress_comm_send'); ?>
                                <input type="hidden" name="action" value="alquipress_send_email">
                                
                                <div class="ap-comm-form-group">
                                    <label class="ap-comm-label"><?php esc_html_e('Enviar a', 'alquipress'); ?></label>
                                    <input type="email" name="to_email" id="to_email" class="ap-comm-input" placeholder="cliente@dominio.com" required autocomplete="off">
                                    <input type="hidden" name="entity_type" id="send-entity-type" value="">
                                    <input type="hidden" name="entity_id" id="send-entity-id" value="">
                                    <small class="ap-comm-hint"><?php esc_html_e('Escribe el email o nombre para buscar automáticamente', 'alquipress'); ?></small>
                                </div>

                                <div class="ap-comm-form-group">
                                    <label class="ap-comm-label"><?php esc_html_e('Asunto', 'alquipress'); ?></label>
                                    <input type="text" name="subject" class="ap-comm-input" required>
                                </div>

                                <div class="ap-comm-form-group">
                                    <label class="ap-comm-label"><?php esc_html_e('Mensaje', 'alquipress'); ?></label>
                                    <textarea name="message" rows="8" class="ap-comm-textarea" required></textarea>
                                </div>

                                <div class="ap-comm-form-group">
                                    <div class="ap-comm-inline">
                                        <div>
                                            <label class="ap-comm-label"><?php esc_html_e('Vincular con', 'alquipress'); ?></label>
                                            <select name="entity_type" id="send-entity-type-select" class="ap-comm-select">
                                                <option value=""><?php esc_html_e('Sin vincular', 'alquipress'); ?></option>
                                                <option value="propietario"><?php esc_html_e('Propietario', 'alquipress'); ?></option>
                                                <option value="cliente"><?php esc_html_e('Cliente', 'alquipress'); ?></option>
                                                <option value="reserva"><?php esc_html_e('Reserva', 'alquipress'); ?></option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="ap-comm-label"><?php esc_html_e('ID relacionado', 'alquipress'); ?></label>
                                            <input type="number" name="entity_id" id="send-entity-id-input" class="ap-comm-input" min="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="ap-comm-form-actions">
                                    <button type="button" id="preview-email" class="button"><?php esc_html_e('Vista previa', 'alquipress'); ?></button>
                                    <button type="submit" class="button button-primary"><?php esc_html_e('Enviar', 'alquipress'); ?></button>
                                </div>
                            </form>
                        </section>

                        <section class="ap-communications-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h2 style="margin: 0;"><?php esc_html_e('Histórico', 'alquipress'); ?></h2>
                                <button type="button" id="export-csv" class="button"><?php esc_html_e('Exportar CSV', 'alquipress'); ?></button>
                            </div>
                            
                            <!-- Filtros -->
                            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="ap-comm-filters">
                                <input type="hidden" name="page" value="alquipress-comunicacion">
                                <input type="hidden" name="tab" value="manage">
                                
                                <div class="ap-comm-filters-row">
                                    <div class="ap-comm-filter-group">
                                        <label><?php esc_html_e('Búsqueda:', 'alquipress'); ?></label>
                                        <input type="text" name="filter_search" class="ap-comm-input" placeholder="<?php esc_attr_e('Asunto o email...', 'alquipress'); ?>" value="<?php echo esc_attr($filters['search']); ?>">
                                    </div>
                                    
                                    <div class="ap-comm-filter-group">
                                        <label><?php esc_html_e('Tipo:', 'alquipress'); ?></label>
                                        <select name="filter_direction" class="ap-comm-select">
                                            <option value=""><?php esc_html_e('Todos', 'alquipress'); ?></option>
                                            <option value="outbound" <?php selected($filters['direction'], 'outbound'); ?>><?php esc_html_e('Salida', 'alquipress'); ?></option>
                                            <option value="inbound" <?php selected($filters['direction'], 'inbound'); ?>><?php esc_html_e('Entrada', 'alquipress'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="ap-comm-filter-group">
                                        <label><?php esc_html_e('Estado:', 'alquipress'); ?></label>
                                        <select name="filter_status" class="ap-comm-select">
                                            <option value=""><?php esc_html_e('Todos', 'alquipress'); ?></option>
                                            <option value="sent" <?php selected($filters['status'], 'sent'); ?>><?php esc_html_e('Enviado', 'alquipress'); ?></option>
                                            <option value="received" <?php selected($filters['status'], 'received'); ?>><?php esc_html_e('Recibido', 'alquipress'); ?></option>
                                            <option value="error" <?php selected($filters['status'], 'error'); ?>><?php esc_html_e('Error', 'alquipress'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="ap-comm-filter-group">
                                        <label><?php esc_html_e('Vincular con:', 'alquipress'); ?></label>
                                        <select name="filter_entity_type" class="ap-comm-select" id="filter-entity-type">
                                            <option value=""><?php esc_html_e('Todos', 'alquipress'); ?></option>
                                            <option value="propietario" <?php selected($filters['entity_type'], 'propietario'); ?>><?php esc_html_e('Propietario', 'alquipress'); ?></option>
                                            <option value="cliente" <?php selected($filters['entity_type'], 'cliente'); ?>><?php esc_html_e('Cliente', 'alquipress'); ?></option>
                                            <option value="reserva" <?php selected($filters['entity_type'], 'reserva'); ?>><?php esc_html_e('Reserva', 'alquipress'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="ap-comm-filter-group" id="filter-entity-id-wrapper" style="<?php echo empty($filters['entity_type']) ? 'display: none;' : ''; ?>">
                                        <label><?php esc_html_e('ID:', 'alquipress'); ?></label>
                                        <input type="number" name="filter_entity_id" class="ap-comm-input" value="<?php echo esc_attr($filters['entity_id']); ?>" min="0">
                                    </div>

                                    <div class="ap-comm-filter-group">
                                        <label><?php esc_html_e('Propietario:', 'alquipress'); ?></label>
                                        <select name="filter_owner_id" class="ap-comm-select">
                                            <option value=""><?php esc_html_e('Todos', 'alquipress'); ?></option>
                                            <?php foreach ($owners as $owner_id) : ?>
                                                <option value="<?php echo esc_attr($owner_id); ?>" <?php selected($filters['owner_id'], $owner_id); ?>>
                                                    <?php echo esc_html(get_the_title($owner_id)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="ap-comm-filter-group">
                                        <label><?php esc_html_e('Guest:', 'alquipress'); ?></label>
                                        <select name="filter_guest_id" class="ap-comm-select">
                                            <option value=""><?php esc_html_e('Todos', 'alquipress'); ?></option>
                                            <?php foreach ($users as $user) : ?>
                                                <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($filters['guest_id'], $user->ID); ?>>
                                                    <?php echo esc_html(($user->display_name ?: $user->user_login) . ' (' . $user->user_email . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="ap-comm-filter-group">
                                        <label><?php esc_html_e('Desde:', 'alquipress'); ?></label>
                                        <input type="date" name="filter_date_from" class="ap-comm-input" value="<?php echo esc_attr($filters['date_from']); ?>">
                                    </div>
                                    
                                    <div class="ap-comm-filter-group">
                                        <label><?php esc_html_e('Hasta:', 'alquipress'); ?></label>
                                        <input type="date" name="filter_date_to" class="ap-comm-input" value="<?php echo esc_attr($filters['date_to']); ?>">
                                    </div>
                                    
                                    <div class="ap-comm-filter-group">
                                        <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', 'alquipress'); ?></button>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-comunicacion&tab=manage')); ?>" class="button"><?php esc_html_e('Limpiar', 'alquipress'); ?></a>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="ap-comm-table">
                                <div class="ap-comm-table-head">
                                    <span><?php esc_html_e('Fecha', 'alquipress'); ?></span>
                                    <span><?php esc_html_e('Tipo', 'alquipress'); ?></span>
                                    <span><?php esc_html_e('Asunto', 'alquipress'); ?></span>
                                    <span><?php esc_html_e('Destino / Remitente', 'alquipress'); ?></span>
                                    <span><?php esc_html_e('Estado', 'alquipress'); ?></span>
                                    <span><?php esc_html_e('Acciones', 'alquipress'); ?></span>
                                </div>
                                <?php
                                if (empty($items)) :
                                ?>
                                    <div class="ap-comm-empty"><?php esc_html_e('No hay comunicaciones que coincidan con los filtros.', 'alquipress'); ?></div>
                                <?php else :
                                    foreach ($items as $item) :
                                        $direction = get_post_meta($item->ID, 'ap_comm_direction', true);
                                        $status = get_post_meta($item->ID, 'ap_comm_status', true);
                                        $to = get_post_meta($item->ID, 'ap_comm_to', true);
                                        $from = get_post_meta($item->ID, 'ap_comm_from', true);
                                        $entity_type = get_post_meta($item->ID, 'ap_comm_entity_type', true);
                                        $entity_id = get_post_meta($item->ID, 'ap_comm_entity_id', true);
                                        $label = $direction === 'inbound' ? __('Entrada', 'alquipress') : __('Salida', 'alquipress');
                                        $contact = $direction === 'inbound' ? $from : $to;
                                        ?>
                                        <div class="ap-comm-table-row">
                                            <span><?php echo esc_html(wp_date('d/m/Y H:i', strtotime($item->post_date))); ?></span>
                                            <span class="ap-comm-pill <?php echo $direction === 'inbound' ? 'is-in' : 'is-out'; ?>"><?php echo esc_html($label); ?></span>
                                            <span class="ap-comm-subject" title="<?php echo esc_attr($item->post_title); ?>">
                                                <a href="#" class="ap-comm-view-email" data-email-id="<?php echo esc_attr($item->ID); ?>"><?php echo esc_html($item->post_title); ?></a>
                                            </span>
                                            <span><?php echo esc_html($contact ?: '-'); ?></span>
                                            <span class="ap-comm-status <?php echo esc_attr($status); ?>"><?php echo esc_html($status ?: '—'); ?></span>
                                            <span class="ap-comm-actions">
                                                <?php if ($status === 'error' && $direction === 'outbound'): ?>
                                                    <button class="button button-small ap-comm-resend" data-email-id="<?php echo esc_attr($item->ID); ?>" title="<?php esc_attr_e('Reenviar', 'alquipress'); ?>">↻</button>
                                                <?php endif; ?>
                                                <?php if ($entity_type && $entity_id): ?>
                                                    <a href="<?php echo esc_url($this->get_entity_url($entity_type, $entity_id)); ?>" class="button button-small" target="_blank" title="<?php esc_attr_e('Ver ' . $entity_type, 'alquipress'); ?>">👁</a>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; endif; ?>
                            </div>
                        </section>
                    </div>

                    <section class="ap-communications-card ap-comm-settings">
                        <h2><?php esc_html_e('Ajustes SMTP / IMAP', 'alquipress'); ?></h2>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('alquipress_comm_settings'); ?>
                            <input type="hidden" name="action" value="alquipress_comm_save_settings">

                            <div class="ap-comm-settings-grid">
                                <div>
                                    <h3><?php esc_html_e('SMTP (salida)', 'alquipress'); ?></h3>
                                    <label class="ap-comm-label">Host</label>
                                    <input type="text" name="smtp_host" class="ap-comm-input" value="<?php echo esc_attr($settings['smtp_host']); ?>">
                                    <label class="ap-comm-label">Puerto</label>
                                    <input type="number" name="smtp_port" class="ap-comm-input" value="<?php echo esc_attr($settings['smtp_port']); ?>">
                                    <label class="ap-comm-label">Usuario</label>
                                    <input type="text" name="smtp_user" class="ap-comm-input" value="<?php echo esc_attr($settings['smtp_user']); ?>">
                                    <label class="ap-comm-label">Contraseña</label>
                                    <input type="password" name="smtp_pass" class="ap-comm-input" value="<?php echo esc_attr($settings['smtp_pass']); ?>">
                                    <label class="ap-comm-label">Seguridad</label>
                                    <select name="smtp_secure" class="ap-comm-select">
                                        <option value="" <?php selected($settings['smtp_secure'], ''); ?>><?php esc_html_e('Ninguna', 'alquipress'); ?></option>
                                        <option value="tls" <?php selected($settings['smtp_secure'], 'tls'); ?>>TLS</option>
                                        <option value="ssl" <?php selected($settings['smtp_secure'], 'ssl'); ?>>SSL</option>
                                    </select>
                                    <label class="ap-comm-label">From Email</label>
                                    <input type="email" name="smtp_from_email" class="ap-comm-input" value="<?php echo esc_attr($settings['smtp_from_email']); ?>">
                                    <label class="ap-comm-label">From Name</label>
                                    <input type="text" name="smtp_from_name" class="ap-comm-input" value="<?php echo esc_attr($settings['smtp_from_name']); ?>">
                                </div>
                                <div>
                                    <h3><?php esc_html_e('IMAP (entrada)', 'alquipress'); ?></h3>
                                    <label class="ap-comm-checkbox">
                                        <input type="checkbox" name="imap_enabled" value="1" <?php checked($settings['imap_enabled'], 1); ?>>
                                        <?php esc_html_e('Activar sincronización de entrada', 'alquipress'); ?>
                                    </label>
                                    <label class="ap-comm-label">Host</label>
                                    <input type="text" name="imap_host" class="ap-comm-input" value="<?php echo esc_attr($settings['imap_host']); ?>">
                                    <label class="ap-comm-label">Puerto</label>
                                    <input type="number" name="imap_port" class="ap-comm-input" value="<?php echo esc_attr($settings['imap_port']); ?>">
                                    <label class="ap-comm-label">Usuario</label>
                                    <input type="text" name="imap_user" class="ap-comm-input" value="<?php echo esc_attr($settings['imap_user']); ?>">
                                    <label class="ap-comm-label">Contraseña</label>
                                    <input type="password" name="imap_pass" class="ap-comm-input" value="<?php echo esc_attr($settings['imap_pass']); ?>">
                                    <label class="ap-comm-label">Seguridad</label>
                                    <select name="imap_secure" class="ap-comm-select">
                                        <option value="" <?php selected($settings['imap_secure'], ''); ?>><?php esc_html_e('Ninguna', 'alquipress'); ?></option>
                                        <option value="tls" <?php selected($settings['imap_secure'], 'tls'); ?>>TLS</option>
                                        <option value="ssl" <?php selected($settings['imap_secure'], 'ssl'); ?>>SSL</option>
                                    </select>
                                    <label class="ap-comm-label">Buzón</label>
                                    <input type="text" name="imap_mailbox" class="ap-comm-input" value="<?php echo esc_attr($settings['imap_mailbox']); ?>">
                                    <?php if (!function_exists('imap_open')) : ?>
                                        <p class="ap-comm-warning"><?php esc_html_e('IMAP no está disponible en el servidor. Habla con tu hosting para habilitar la extensión PHP imap.', 'alquipress'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <button type="submit" class="ap-comm-btn ap-comm-btn-primary"><?php esc_html_e('Guardar ajustes', 'alquipress'); ?></button>
                        </form>
                    </section>
                </main>
            </div>
        </div>
        <?php
    }
}

new Alquipress_Communications();
