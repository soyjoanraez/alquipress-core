<?php
/**
 * Módulo: Campañas de email
 * - Email masivo a clientes con reservas completadas
 * - Listas Mailpoet para fin de año
 * - Emails de aniversario (mismas fechas de estancia)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Email_Campaigns
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('admin_init', [$this, 'handle_bulk_send']);
        add_action('admin_init', [$this, 'handle_mailpoet_sync']);
        add_action('alquipress_daily_cron', [$this, 'send_same_dates_reminders']);
        add_action('init', [$this, 'ensure_cron_scheduled']);
    }

    public function ensure_cron_scheduled()
    {
        if (!wp_next_scheduled('alquipress_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'alquipress_daily_cron');
        }
    }

    public function maybe_render_section($page)
    {
        if ($page === 'alquipress-email-campaigns') {
            $this->render_page();
        }
    }

    /**
     * Clientes con al menos una reserva completada
     */
    private function get_past_customers_emails()
    {
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => 'completed',
            'return' => 'objects',
        ]);
        $emails = [];
        foreach ($orders as $order) {
            $email = $order->get_billing_email();
            if ($email && is_email($email)) {
                $emails[$email] = true;
            }
        }
        return array_keys($emails);
    }

    /**
     * Enviar email masivo
     */
    public function handle_bulk_send()
    {
        if (!isset($_POST['alquipress_bulk_email']) || !current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('alquipress_bulk_email');

        $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
        $body = isset($_POST['body']) ? wp_kses_post(wp_unslash($_POST['body'])) : '';

        if (!$subject || !$body) {
            add_settings_error('alquipress_campaigns', 'missing', __('Asunto y cuerpo son obligatorios.', 'alquipress'), 'error');
            return;
        }

        $emails = $this->get_past_customers_emails();
        $sent = 0;
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        foreach ($emails as $email) {
            if (wp_mail($email, $subject, $body, $headers)) {
                $sent++;
            }
        }

        add_settings_error(
            'alquipress_campaigns',
            'sent',
            sprintf(__('Se enviaron %d de %d emails correctamente.', 'alquipress'), $sent, count($emails)),
            'success'
        );
    }

    /**
     * Sincronizar listas Mailpoet para fin de año
     */
    public function handle_mailpoet_sync()
    {
        if (!isset($_POST['alquipress_mailpoet_sync']) || !current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('alquipress_mailpoet_sync');

        $list_name = isset($_POST['list_name']) ? sanitize_text_field(wp_unslash($_POST['list_name'])) : 'Clientes año actual';
        $year = isset($_POST['year']) ? absint($_POST['year']) : (int) date('Y');

        $list_id = $this->ensure_mailpoet_list($list_name);
        if (!$list_id) {
            add_settings_error('alquipress_campaigns', 'mailpoet', __('MailPoet no está activo o no se pudo crear la lista.', 'alquipress'), 'error');
            return;
        }

        $emails = $this->get_customers_with_orders_in_year($year);
        $synced = $this->sync_emails_to_mailpoet_list($list_id, $emails);

        add_settings_error(
            'alquipress_campaigns',
            'synced',
            sprintf(__('Sincronizados %d clientes a la lista "%s".', 'alquipress'), $synced, $list_name),
            'success'
        );
    }

    private function get_customers_with_orders_in_year($year)
    {
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => 'completed',
            'date_created' => $year . '-01-01...' . $year . '-12-31',
            'return' => 'objects',
        ]);
        $result = [];
        foreach ($orders as $order) {
            $email = $order->get_billing_email();
            if ($email && is_email($email)) {
                $result[$email] = [
                    'first_name' => $order->get_billing_first_name(),
                    'last_name' => $order->get_billing_last_name(),
                ];
            }
        }
        return $result;
    }

    private function ensure_mailpoet_list($name)
    {
        if (!class_exists('\MailPoet\API\API')) {
            return false;
        }
        try {
            $api = \MailPoet\API\API::MP('v1');
            $lists = $api->getLists();
            foreach ($lists as $list) {
                if ($list['name'] === $name) {
                    return $list['id'];
                }
            }
            $list = $api->addList(['name' => $name]);
            return $list['id'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function sync_emails_to_mailpoet_list($list_id, $customers)
    {
        if (!class_exists('\MailPoet\API\API')) {
            return 0;
        }
        $synced = 0;
        try {
            $api = \MailPoet\API\API::MP('v1');
            foreach ($customers as $email => $data) {
                try {
                    $api->addSubscriber([
                        'email' => $email,
                        'first_name' => $data['first_name'] ?? '',
                        'last_name' => $data['last_name'] ?? '',
                    ], [$list_id]);
                    $synced++;
                } catch (\Exception $e) {
                    // Subscriber may already exist
                    $synced++;
                }
            }
        } catch (\Exception $e) {
            return 0;
        }
        return $synced;
    }

    /**
     * Cron: buscar clientes con reservas en fechas próximas (misma semana del año) y enviar email
     */
    public function send_same_dates_reminders()
    {
        $orders = wc_get_orders([
            'limit' => 500,
            'status' => 'completed',
            'return' => 'objects',
        ]);

        $next_4_weeks = [];
        for ($i = 0; $i < 28; $i++) {
            $d = strtotime("+$i days");
            $next_4_weeks[date('m-d', $d)] = true;
        }

        $sent = [];
        foreach ($orders as $order) {
            $checkin = $order->get_meta('_booking_checkin_date');
            $checkout = $order->get_meta('_booking_checkout_date');
            $product_id = (int) $order->get_meta('_booking_product_id');
            if (!$checkin || !$product_id) {
                continue;
            }

            $checkin_md = date('m-d', strtotime($checkin));
            if (!isset($next_4_weeks[$checkin_md])) {
                continue;
            }

            $email = $order->get_billing_email();
            if (!$email || isset($sent[$email])) {
                continue;
            }

            $prop_name = get_the_title($product_id);
            $subject = sprintf(__('¿Te gustaría repetir? %s disponible', 'alquipress'), $prop_name);
            $body = sprintf(
                "<p>Hola %s,</p><p>Hace un año estuviste en <strong>%s</strong>. ¿Te gustaría reservar de nuevo para las mismas fechas? ¡Consulta disponibilidad en nuestra web!</p><p>Un saludo,<br>El equipo</p>",
                esc_html($order->get_billing_first_name()),
                esc_html($prop_name)
            );

            if (wp_mail($email, $subject, $body, ['Content-Type: text/html; charset=UTF-8'])) {
                $sent[$email] = true;
            }
        }
    }

    public function render_page()
    {
        $past_count = count($this->get_past_customers_emails());
        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        settings_errors('alquipress_campaigns');
        ?>
        <div class="wrap alquipress-email-campaigns ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('communications'); ?>
                <main class="ap-owners-main">
                    <header class="ap-header">
                        <h1 class="ap-header-title"><?php esc_html_e('Campañas de email', 'alquipress'); ?></h1>
                        <p class="ap-header-subtitle"><?php esc_html_e('Emails masivos, listas Mailpoet y recordatorios por fechas', 'alquipress'); ?></p>
                    </header>

                    <div class="ap-campaigns-section" style="margin-bottom: 32px;">
                        <h2><?php esc_html_e('Email a clientes pasados', 'alquipress'); ?></h2>
                        <p><?php printf(esc_html__('Enviar un email a los %d clientes con al menos una reserva completada.', 'alquipress'), $past_count); ?></p>
                        <form method="post" style="max-width: 600px; margin-top: 16px;">
                            <?php wp_nonce_field('alquipress_bulk_email'); ?>
                            <input type="hidden" name="alquipress_bulk_email" value="1">
                            <p>
                                <label for="subject"><?php esc_html_e('Asunto', 'alquipress'); ?></label><br>
                                <input type="text" name="subject" id="subject" required style="width: 100%; padding: 8px;">
                            </p>
                            <p>
                                <label for="body"><?php esc_html_e('Cuerpo (HTML)', 'alquipress'); ?></label><br>
                                <textarea name="body" id="body" rows="8" required style="width: 100%; padding: 8px;"></textarea>
                            </p>
                            <p><button type="submit" class="button button-primary"><?php esc_html_e('Enviar', 'alquipress'); ?></button></p>
                        </form>
                    </div>

                    <div class="ap-campaigns-section" style="margin-bottom: 32px;">
                        <h2><?php esc_html_e('Listas Mailpoet para fin de año', 'alquipress'); ?></h2>
                        <p><?php esc_html_e('Crear o sincronizar listas con clientes del año para campañas de fin de año.', 'alquipress'); ?></p>
                        <form method="post" style="max-width: 400px; margin-top: 16px;">
                            <?php wp_nonce_field('alquipress_mailpoet_sync'); ?>
                            <input type="hidden" name="alquipress_mailpoet_sync" value="1">
                            <p>
                                <label for="list_name"><?php esc_html_e('Nombre de lista', 'alquipress'); ?></label><br>
                                <input type="text" name="list_name" id="list_name" value="Clientes año actual" style="width: 100%; padding: 8px;">
                            </p>
                            <p>
                                <label for="year"><?php esc_html_e('Año', 'alquipress'); ?></label><br>
                                <select name="year" id="year">
                                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--) : ?>
                                    <option value="<?php echo (int) $y; ?>"><?php echo (int) $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </p>
                            <p><button type="submit" class="button button-primary"><?php esc_html_e('Sincronizar lista', 'alquipress'); ?></button></p>
                        </form>
                        <p style="font-size: 13px; color: #64748b;"><?php esc_html_e('Listas sugeridas: "Clientes año actual", "Clientes recurrentes" (varios años).', 'alquipress'); ?></p>
                    </div>

                    <div class="ap-campaigns-section">
                        <h2><?php esc_html_e('Emails de mismas fechas (aniversario)', 'alquipress'); ?></h2>
                        <p><?php esc_html_e('Un cron diario envía automáticamente emails a clientes cuya reserva anterior fue en fechas próximas (misma semana del año), invitándoles a repetir.', 'alquipress'); ?></p>
                        <p style="font-size: 13px; color: #64748b;"><?php esc_html_e('Acción programada: alquipress_daily_cron', 'alquipress'); ?></p>
                    </div>
                </main>
            </div>
        </div>
        <?php
    }
}

Alquipress_Email_Campaigns::get_instance();
