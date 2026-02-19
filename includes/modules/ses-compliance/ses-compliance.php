<?php
/**
 * Módulo: Cumplimiento SES Hospedajes
 * Metadatos operativos por reserva/pedido + exportación XML por lotes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_SES_Compliance
{
    const META_STATUS = '_alq_ses_status';
    const META_PAYMENT_TYPE = '_alq_ses_payment_type';
    const META_GUEST_ROLE = '_alq_ses_guest_role';
    const META_SENT_AT = '_alq_ses_sent_at';
    const META_SUBMISSION_REF = '_alq_ses_submission_ref';
    const META_NOTES = '_alq_ses_notes';
    const META_GUEST_COUNT = '_alq_guest_count';
    const META_FORM_TOKEN = '_alq_ses_form_token';
    const META_FORM_COMPLETED_AT = '_alq_ses_form_completed_at';
    const META_TRAVELERS = '_alq_ses_travelers';
    const META_DATA_CONSENT_AT = '_alq_ses_data_consent_at';
    const META_FORM_LINK_SENT_AT = '_alq_ses_form_link_sent_at';
    const META_FORM_LINK_SENT_TO = '_alq_ses_form_link_sent_to';
    const META_FIRST_PAYMENT_LINK_SENT_AT = '_alq_ses_first_payment_link_sent_at';
    const META_REMINDER_4D_SENT_AT = '_alq_ses_reminder_4d_sent_at';
    const CRON_4D_REMINDER_HOOK = 'alquipress_ses_send_4d_reminders';
    const NONCE_ORDER_META = 'alquipress_ses_order_meta';
    const NONCE_EXPORT = 'alquipress_ses_export';
    const NONCE_SEND_LINK = 'alquipress_ses_send_link';
    const NONCE_GUEST_FORM = 'alquipress_ses_guest_form';

    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'register_order_metabox']);
        add_action('woocommerce_process_shop_order_meta', [$this, 'save_order_meta'], 10, 1);

        add_action('admin_menu', [$this, 'add_export_page'], 20);
        add_action('admin_post_alquipress_ses_export_xml', [$this, 'handle_export_xml']);
        add_action('admin_post_alquipress_ses_send_form_link', [$this, 'handle_send_form_link']);
        add_action('template_redirect', [$this, 'maybe_render_guest_form']);
        add_action('woocommerce_order_status_changed', [$this, 'auto_send_form_link_on_first_payment'], 20, 4);
        add_action('init', [$this, 'ensure_4d_reminder_cron']);
        add_action(self::CRON_4D_REMINDER_HOOK, [$this, 'send_4d_checkin_reminders']);

        add_action('woocommerce_before_order_notes', [$this, 'render_checkout_guest_count_field']);
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_guest_count_field']);
        add_action('woocommerce_checkout_create_order', [$this, 'save_checkout_guest_count_field'], 10, 2);
    }

    /**
     * Registrar pantalla de exportación XML.
     */
    public function add_export_page()
    {
        add_submenu_page(
            'alquipress-settings',
            __('SES Hospedajes XML', 'alquipress'),
            __('SES Hospedajes', 'alquipress'),
            'edit_shop_orders',
            'alquipress-ses-export',
            [$this, 'render_export_page']
        );
    }

    /**
     * Registrar metabox en edición de pedido.
     */
    public function register_order_metabox()
    {
        $screens = ['shop_order', 'woocommerce_page_wc-orders'];
        foreach ($screens as $screen) {
            add_meta_box(
                'alquipress_ses_compliance',
                '🛂 SES Hospedajes',
                [$this, 'render_order_metabox'],
                $screen,
                'side',
                'default'
            );
        }
    }

    /**
     * Renderizar metabox SES.
     *
     * @param WP_Post|mixed $post Objeto post en modo clásico.
     */
    public function render_order_metabox($post)
    {
        $order = $this->resolve_order_from_screen($post);
        if (!$order) {
            echo '<p>' . esc_html__('No se pudo cargar el pedido.', 'alquipress') . '</p>';
            return;
        }

        $status = (string) $order->get_meta(self::META_STATUS);
        $payment_type = (string) $order->get_meta(self::META_PAYMENT_TYPE);
        $guest_role = (string) $order->get_meta(self::META_GUEST_ROLE);
        $sent_at = (string) $order->get_meta(self::META_SENT_AT);
        $submission_ref = (string) $order->get_meta(self::META_SUBMISSION_REF);
        $notes = (string) $order->get_meta(self::META_NOTES);
        $guest_count = (int) $order->get_meta(self::META_GUEST_COUNT);
        if ($guest_count < 1) {
            $guest_count = $this->get_required_guest_count($order);
        }
        $form_url = $this->get_guest_form_url($order);
        $link_notice = isset($_GET['ses_link_notice']) ? sanitize_key(wp_unslash($_GET['ses_link_notice'])) : '';

        if ($status === '') {
            $status = 'pending';
        }
        if ($payment_type === '') {
            $payment_type = alquipress_ses_guess_payment_type_from_order($order);
        }
        if ($guest_role === '') {
            $guest_role = 'VI';
        }

        $sent_at_input = '';
        if ($sent_at !== '') {
            $sent_ts = strtotime($sent_at);
            if ($sent_ts) {
                $sent_at_input = wp_date('Y-m-d\TH:i', $sent_ts);
            }
        }

        $status_options = [
            'pending' => 'Pendiente',
            'xml_generated' => 'XML generado',
            'sent' => 'Enviado',
            'accepted' => 'Aceptado',
            'rejected' => 'Rechazado',
        ];
        $payment_options = alquipress_ses_payment_type_choices();
        $role_options = alquipress_ses_role_choices();

        wp_nonce_field(self::NONCE_ORDER_META, 'alquipress_ses_order_nonce');
        ?>
        <p>
            <label for="<?php echo esc_attr(self::META_STATUS); ?>"><strong><?php esc_html_e('Estado SES', 'alquipress'); ?></strong></label><br>
            <select name="<?php echo esc_attr(self::META_STATUS); ?>" id="<?php echo esc_attr(self::META_STATUS); ?>" style="width:100%;">
                <?php foreach ($status_options as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($status, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr(self::META_PAYMENT_TYPE); ?>"><strong><?php esc_html_e('Tipo pago (SES)', 'alquipress'); ?></strong></label><br>
            <select name="<?php echo esc_attr(self::META_PAYMENT_TYPE); ?>" id="<?php echo esc_attr(self::META_PAYMENT_TYPE); ?>" style="width:100%;">
                <?php foreach ($payment_options as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($payment_type, $key); ?>>
                        <?php echo esc_html($key . ' · ' . $label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr(self::META_GUEST_ROLE); ?>"><strong><?php esc_html_e('Rol huésped', 'alquipress'); ?></strong></label><br>
            <select name="<?php echo esc_attr(self::META_GUEST_ROLE); ?>" id="<?php echo esc_attr(self::META_GUEST_ROLE); ?>" style="width:100%;">
                <?php foreach ($role_options as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($guest_role, $key); ?>>
                        <?php echo esc_html($key . ' · ' . $label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr(self::META_SENT_AT); ?>"><strong><?php esc_html_e('Fecha/hora envío', 'alquipress'); ?></strong></label><br>
            <input
                type="datetime-local"
                name="<?php echo esc_attr(self::META_SENT_AT); ?>"
                id="<?php echo esc_attr(self::META_SENT_AT); ?>"
                value="<?php echo esc_attr($sent_at_input); ?>"
                style="width:100%;"
            >
        </p>

        <p>
            <label for="<?php echo esc_attr(self::META_SUBMISSION_REF); ?>"><strong><?php esc_html_e('Referencia envío', 'alquipress'); ?></strong></label><br>
            <input
                type="text"
                name="<?php echo esc_attr(self::META_SUBMISSION_REF); ?>"
                id="<?php echo esc_attr(self::META_SUBMISSION_REF); ?>"
                value="<?php echo esc_attr($submission_ref); ?>"
                placeholder="SES-2026-0001"
                style="width:100%;"
            >
        </p>

        <p>
            <label for="<?php echo esc_attr(self::META_NOTES); ?>"><strong><?php esc_html_e('Notas SES', 'alquipress'); ?></strong></label><br>
            <textarea
                name="<?php echo esc_attr(self::META_NOTES); ?>"
                id="<?php echo esc_attr(self::META_NOTES); ?>"
                rows="3"
                style="width:100%;"
                placeholder="<?php esc_attr_e('Ej: rechazado por fecha expedición inválida', 'alquipress'); ?>"
            ><?php echo esc_textarea($notes); ?></textarea>
        </p>

        <p>
            <label for="<?php echo esc_attr(self::META_GUEST_COUNT); ?>"><strong><?php esc_html_e('Nº huéspedes esperados', 'alquipress'); ?></strong></label><br>
            <input
                type="number"
                min="1"
                max="30"
                name="<?php echo esc_attr(self::META_GUEST_COUNT); ?>"
                id="<?php echo esc_attr(self::META_GUEST_COUNT); ?>"
                value="<?php echo (int) $guest_count; ?>"
                style="width:100%;"
            >
        </p>

        <p>
            <label><strong><?php esc_html_e('Formulario huésped', 'alquipress'); ?></strong></label><br>
            <input type="text" readonly value="<?php echo esc_attr($form_url); ?>" style="width:100%;font-size:11px;">
            <small style="display:block;color:#6b7280;margin-top:4px;"><?php esc_html_e('Enlace seguro para que el huésped complete sus datos.', 'alquipress'); ?></small>
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0 0 12px;">
            <?php wp_nonce_field(self::NONCE_SEND_LINK, 'alquipress_ses_send_link_nonce'); ?>
            <input type="hidden" name="action" value="alquipress_ses_send_form_link">
            <input type="hidden" name="order_id" value="<?php echo (int) $order->get_id(); ?>">
            <button type="submit" class="button button-secondary" style="width:100%;">
                <?php esc_html_e('Enviar enlace por email', 'alquipress'); ?>
            </button>
        </form>

        <?php if ($link_notice === 'sent'): ?>
            <p style="margin:0 0 12px;color:#166534;font-size:12px;"><?php esc_html_e('Enlace enviado correctamente.', 'alquipress'); ?></p>
        <?php elseif ($link_notice === 'error'): ?>
            <p style="margin:0 0 12px;color:#991b1b;font-size:12px;"><?php esc_html_e('No se pudo enviar el email al huésped.', 'alquipress'); ?></p>
        <?php endif; ?>

        <p style="margin-top:10px;">
            <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=alquipress-ses-export')); ?>">
                <?php esc_html_e('Exportar pendientes XML', 'alquipress'); ?>
            </a>
        </p>
        <?php
    }

    /**
     * Guardar metadatos SES del pedido.
     *
     * @param int $order_id ID del pedido.
     */
    public function save_order_meta($order_id)
    {
        if (!isset($_POST['alquipress_ses_order_nonce'])) {
            return;
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alquipress_ses_order_nonce'])), self::NONCE_ORDER_META)) {
            return;
        }
        if (!current_user_can('edit_shop_orders')) {
            return;
        }

        $order = wc_get_order((int) $order_id);
        if (!$order) {
            return;
        }

        $status = isset($_POST[self::META_STATUS]) ? sanitize_key(wp_unslash($_POST[self::META_STATUS])) : 'pending';
        $allowed_statuses = ['pending', 'xml_generated', 'sent', 'accepted', 'rejected'];
        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'pending';
        }

        $payment_type = isset($_POST[self::META_PAYMENT_TYPE]) ? sanitize_text_field(wp_unslash($_POST[self::META_PAYMENT_TYPE])) : '';
        if ($payment_type === '') {
            $payment_type = alquipress_ses_guess_payment_type_from_order($order);
        }
        $payment_type = alquipress_ses_normalize_payment_type($payment_type);

        $guest_role = isset($_POST[self::META_GUEST_ROLE]) ? sanitize_text_field(wp_unslash($_POST[self::META_GUEST_ROLE])) : 'VI';
        $guest_role = alquipress_ses_normalize_role($guest_role);

        $sent_at = isset($_POST[self::META_SENT_AT]) ? sanitize_text_field(wp_unslash($_POST[self::META_SENT_AT])) : '';
        $sent_at = $this->normalize_datetime_local($sent_at);

        $submission_ref = isset($_POST[self::META_SUBMISSION_REF]) ? sanitize_text_field(wp_unslash($_POST[self::META_SUBMISSION_REF])) : '';
        $notes = isset($_POST[self::META_NOTES]) ? sanitize_textarea_field(wp_unslash($_POST[self::META_NOTES])) : '';
        $guest_count = isset($_POST[self::META_GUEST_COUNT]) ? absint(wp_unslash($_POST[self::META_GUEST_COUNT])) : 0;
        if ($guest_count < 1) {
            $guest_count = 1;
        }

        $order->update_meta_data(self::META_STATUS, $status);
        $order->update_meta_data(self::META_PAYMENT_TYPE, $payment_type);
        $order->update_meta_data(self::META_GUEST_ROLE, $guest_role);
        $order->update_meta_data(self::META_SUBMISSION_REF, $submission_ref);
        $order->update_meta_data(self::META_NOTES, $notes);
        $order->update_meta_data(self::META_GUEST_COUNT, $guest_count);

        if ($sent_at !== '') {
            $order->update_meta_data(self::META_SENT_AT, $sent_at);
        } else {
            $order->delete_meta_data(self::META_SENT_AT);
        }

        $order->save();
    }

    /**
     * Campo checkout: número de huéspedes esperados.
     */
    public function render_checkout_guest_count_field($checkout)
    {
        if (!function_exists('woocommerce_form_field')) {
            return;
        }

        $default = 1;
        if (function_exists('WC') && WC()->cart) {
            $default = max(1, (int) WC()->cart->get_cart_contents_count());
        }

        echo '<div id="alquipress-ses-checkout-guest-count"><h3>' . esc_html__('Datos de viajeros', 'alquipress') . '</h3>';
        $value = $checkout->get_value('alq_guest_count');
        if ($value === '') {
            $value = $default;
        }

        woocommerce_form_field('alq_guest_count', [
            'type' => 'number',
            'class' => ['form-row-wide'],
            'label' => __('Número total de huéspedes', 'alquipress'),
            'required' => true,
            'custom_attributes' => [
                'min' => 1,
                'max' => 30,
            ],
            'description' => __('Usaremos este número para preparar el formulario de viajeros.', 'alquipress'),
        ], $value);
        echo '</div>';
    }

    /**
     * Validar campo de huéspedes en checkout.
     */
    public function validate_checkout_guest_count_field()
    {
        if (!isset($_POST['alq_guest_count'])) {
            return;
        }

        $count = absint(wp_unslash($_POST['alq_guest_count']));
        if ($count < 1 && function_exists('wc_add_notice')) {
            wc_add_notice(__('Indica un número válido de huéspedes.', 'alquipress'), 'error');
        }
    }

    /**
     * Guardar nº de huéspedes en pedido al crear checkout.
     *
     * @param WC_Order $order Pedido.
     * @param array    $data Datos checkout.
     */
    public function save_checkout_guest_count_field($order, $data)
    {
        $count = isset($_POST['alq_guest_count']) ? absint(wp_unslash($_POST['alq_guest_count'])) : 0;
        if ($count < 1) {
            $count = 1;
        }

        $order->update_meta_data(self::META_GUEST_COUNT, $count);
    }

    /**
     * Enviar por email al huésped el enlace seguro del formulario SES.
     */
    public function handle_send_form_link()
    {
        if (!current_user_can('edit_shop_orders')) {
            wp_die(esc_html__('Permisos insuficientes.', 'alquipress'));
        }
        if (!isset($_POST['alquipress_ses_send_link_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alquipress_ses_send_link_nonce'])), self::NONCE_SEND_LINK)) {
            wp_die(esc_html__('Solicitud inválida.', 'alquipress'));
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $order = $order_id > 0 ? wc_get_order($order_id) : false;
        if (!$order) {
            wp_die(esc_html__('Pedido no encontrado.', 'alquipress'));
        }

        $sent = $this->send_guest_form_link_email($order, true, 'manual');
        $notice = $sent ? 'sent' : 'error';

        $back_url = add_query_arg('ses_link_notice', $notice, $this->get_order_return_admin_url((int) $order_id));
        wp_safe_redirect($back_url);
        exit;
    }

    /**
     * Auto-envío al producirse el primer pago.
     *
     * @param int            $order_id ID pedido.
     * @param string         $from Estado anterior.
     * @param string         $to Estado nuevo.
     * @param WC_Order|mixed $order Pedido.
     */
    public function auto_send_form_link_on_first_payment($order_id, $from, $to, $order)
    {
        if (!$this->is_paid_like_status((string) $to)) {
            return;
        }

        if (!is_object($order) || !method_exists($order, 'get_id')) {
            $order = wc_get_order((int) $order_id);
        }
        if (!$order) {
            return;
        }

        $already_marked = (string) $order->get_meta(self::META_FIRST_PAYMENT_LINK_SENT_AT);
        if ($already_marked !== '') {
            return;
        }

        $sent = $this->send_guest_form_link_email($order, false, 'auto_first_payment', false);
        if ($sent || (string) $order->get_meta(self::META_FORM_LINK_SENT_AT) !== '') {
            $order->update_meta_data(self::META_FIRST_PAYMENT_LINK_SENT_AT, wp_date('Y-m-d H:i:s'));
            $order->save();
        }
    }

    /**
     * Programar cron para recordatorios 4 días antes.
     */
    public function ensure_4d_reminder_cron()
    {
        if (!wp_next_scheduled(self::CRON_4D_REMINDER_HOOK)) {
            wp_schedule_event(time() + 300, 'hourly', self::CRON_4D_REMINDER_HOOK);
        }
    }

    /**
     * Enviar recordatorio automático 4 días antes del check-in.
     */
    public function send_4d_checkin_reminders()
    {
        if (!function_exists('wc_get_orders')) {
            return;
        }

        $target_date = wp_date('Y-m-d', strtotime('+4 days', current_time('timestamp')));
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['processing', 'deposito-ok', 'pending-checkin', 'in-progress', 'completed'],
            'return' => 'objects',
            'meta_query' => [
                [
                    'key' => '_booking_checkin_date',
                    'value' => $target_date,
                    'compare' => '=',
                ],
            ],
        ]);

        foreach ($orders as $order) {
            if (!$order) {
                continue;
            }

            if ((string) $order->get_meta(self::META_REMINDER_4D_SENT_AT) !== '') {
                continue;
            }

            $sent = $this->send_guest_form_link_email($order, false, 'auto_reminder_4d', true);
            if ($sent) {
                $order->update_meta_data(self::META_REMINDER_4D_SENT_AT, wp_date('Y-m-d H:i:s'));
                $order->save();
            }
        }
    }

    /**
     * Renderizar formulario público para captura de viajeros.
     */
    public function maybe_render_guest_form()
    {
        if (!isset($_GET['alquipress_ses_guest_form'])) {
            return;
        }

        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
        $order = $order_id > 0 ? wc_get_order($order_id) : false;
        $required_count = $order ? $this->get_required_guest_count($order) : 1;

        if (!$order || $token === '') {
            status_header(404);
            $this->render_guest_form_shell(__('Enlace inválido o caducado.', 'alquipress'), [], [], false, 1);
            exit;
        }

        $stored_token = (string) $order->get_meta(self::META_FORM_TOKEN);
        if ($stored_token === '' || !hash_equals($stored_token, $token)) {
            status_header(403);
            $this->render_guest_form_shell(__('No hemos podido validar el enlace.', 'alquipress'), [], [], false, $required_count);
            exit;
        }

        $errors = [];
        $success = '';
        $travelers = $this->get_prefill_travelers($order);

        if (strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST') {
            $nonce = isset($_POST['alquipress_ses_guest_form_nonce']) ? sanitize_text_field(wp_unslash($_POST['alquipress_ses_guest_form_nonce'])) : '';
            if (!wp_verify_nonce($nonce, self::NONCE_GUEST_FORM . '_' . $order_id)) {
                $errors[] = __('No se pudo validar el formulario. Recarga la página e inténtalo de nuevo.', 'alquipress');
            } else {
                $raw_travelers = isset($_POST['travelers']) && is_array($_POST['travelers']) ? wp_unslash($_POST['travelers']) : [];
                $consent = isset($_POST['ses_data_consent']) && $_POST['ses_data_consent'] === '1';

                $travelers = $this->sanitize_travelers_payload($raw_travelers);
                $validation = $this->validate_travelers_payload($travelers, true, $required_count);
                $errors = $validation['errors'];
                $travelers = $validation['travelers'];

                if (!$consent) {
                    $errors[] = __('Debes aceptar el tratamiento de datos para completar el envío.', 'alquipress');
                }

                if (empty($errors)) {
                    $order->update_meta_data(self::META_TRAVELERS, $travelers);
                    $order->update_meta_data(self::META_FORM_COMPLETED_AT, wp_date('Y-m-d H:i:s'));
                    $order->update_meta_data(self::META_DATA_CONSENT_AT, wp_date('Y-m-d H:i:s'));

                    $current_status = (string) $order->get_meta(self::META_STATUS);
                    if ($current_status === '' || $current_status === 'rejected') {
                        $order->update_meta_data(self::META_STATUS, 'pending');
                    }

                    $order->add_order_note(sprintf('Formulario SES completado por huésped (%d viajeros).', count($travelers)));
                    $order->save();

                    $this->sync_primary_traveler_to_profile($order, $travelers[0]);
                    $success = __('Gracias. Hemos recibido correctamente los datos de los viajeros.', 'alquipress');
                }
            }
        }

        $this->render_guest_form_shell($success, $errors, $travelers, true, $required_count);
        exit;
    }

    /**
     * Render HTML del formulario público.
     *
     * @param string $success Mensaje éxito.
     * @param array  $errors Errores.
     * @param array  $travelers Viajeros.
     * @param bool   $show_form Mostrar formulario.
     */
    private function render_guest_form_shell($success, $errors, $travelers, $show_form, $required_count)
    {
        nocache_headers();
        ?>
        <!doctype html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php esc_html_e('Datos de viajeros', 'alquipress'); ?></title>
            <style>
                body{margin:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#0f172a}
                .wrap{max-width:980px;margin:28px auto;padding:0 16px}
                .card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:16px}
                .title{font-size:26px;margin:0 0 8px}
                .subtitle{margin:0;color:#475569}
                .msg-ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px;border-radius:10px;margin:14px 0}
                .msg-err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px;border-radius:10px;margin:14px 0}
                .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
                .field{display:flex;flex-direction:column;gap:6px}
                .field label{font-size:13px;font-weight:600}
                .field input,.field select{height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}
                .traveler{border:1px solid #dbeafe;border-radius:10px;padding:14px;margin-top:14px;background:#f8fbff}
                .traveler-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
                .traveler-title{font-size:15px;font-weight:700}
                .btn{border:0;border-radius:8px;padding:11px 14px;font-weight:700;cursor:pointer}
                .btn-primary{background:#0f766e;color:#fff}
                .btn-secondary{background:#e2e8f0;color:#0f172a}
                .btn-danger{background:#fee2e2;color:#991b1b}
                .actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
                .consent{margin:14px 0;font-size:13px;color:#334155}
                .muted{color:#64748b;font-size:12px}
            </style>
        </head>
        <body>
            <div class="wrap">
                <div class="card">
                    <h1 class="title"><?php esc_html_e('Datos de viajeros para check-in', 'alquipress'); ?></h1>
                    <p class="subtitle"><?php esc_html_e('Completa un bloque por cada huésped. Los datos se usan únicamente para la comunicación legal obligatoria.', 'alquipress'); ?></p>
                    <p class="subtitle" style="margin-top:6px;"><strong><?php echo esc_html(sprintf(__('Esta reserva tiene %d huésped(es).', 'alquipress'), (int) $required_count)); ?></strong></p>

                    <?php if ($success !== ''): ?>
                        <div class="msg-ok"><?php echo esc_html($success); ?></div>
                    <?php endif; ?>

                    <?php foreach ($errors as $error): ?>
                        <div class="msg-err"><?php echo esc_html($error); ?></div>
                    <?php endforeach; ?>

                    <?php if ($show_form): ?>
                        <form method="post">
                            <?php
                            $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
                            wp_nonce_field(self::NONCE_GUEST_FORM . '_' . $order_id, 'alquipress_ses_guest_form_nonce');
                            ?>
                            <div id="ses-travelers-list">
                                <?php foreach ($travelers as $index => $traveler): ?>
                                    <?php $this->render_traveler_fields_row((int) $index, $traveler); ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="actions" style="margin-top:12px;">
                                <button type="button" id="ses-add-traveler" class="btn btn-secondary"><?php esc_html_e('Añadir viajero', 'alquipress'); ?></button>
                                <span class="muted"><?php esc_html_e('Incluye también menores de edad.', 'alquipress'); ?></span>
                            </div>

                            <label class="consent">
                                <input type="checkbox" name="ses_data_consent" value="1" required>
                                <?php esc_html_e('Declaro que los datos son correctos y autorizo su tratamiento para cumplir la normativa de hospedaje.', 'alquipress'); ?>
                            </label>

                            <div class="actions">
                                <button type="submit" class="btn btn-primary"><?php esc_html_e('Enviar datos de viajeros', 'alquipress'); ?></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <template id="ses-traveler-template">
                <?php $this->render_traveler_fields_row(9999, $this->get_empty_traveler_template()); ?>
            </template>

            <script>
                (function(){
                    const list = document.getElementById('ses-travelers-list');
                    const btnAdd = document.getElementById('ses-add-traveler');
                    const tpl = document.getElementById('ses-traveler-template');
                    const requiredCount = <?php echo (int) $required_count; ?>;
                    if(!list || !btnAdd || !tpl){ return; }

                    function reindex(){
                        const rows = list.querySelectorAll('.traveler');
                        rows.forEach((row, idx) => {
                            const number = idx + 1;
                            const title = row.querySelector('.traveler-title');
                            if(title){ title.textContent = 'Viajero ' + number; }
                            row.querySelectorAll('[data-name]').forEach((el) => {
                                const nameKey = el.getAttribute('data-name');
                                el.name = 'travelers[' + idx + '][' + nameKey + ']';
                                if (el.id) {
                                    el.id = 'traveler-' + idx + '-' + nameKey;
                                }
                            });
                        });
                    }

                    btnAdd.addEventListener('click', function(){
                        const fragment = tpl.content.cloneNode(true);
                        list.appendChild(fragment);
                        reindex();
                    });

                    list.addEventListener('click', function(e){
                        const btn = e.target.closest('.ses-remove-traveler');
                        if(!btn){ return; }
                        if (list.querySelectorAll('.traveler').length <= requiredCount) {
                            return;
                        }
                        const row = btn.closest('.traveler');
                        if(!row){ return; }
                        row.remove();
                        reindex();
                    });

                    reindex();
                })();
            </script>
        </body>
        </html>
        <?php
    }

    /**
     * Renderizar campos de una fila de viajero.
     *
     * @param int   $index Índice.
     * @param array $traveler Datos.
     */
    private function render_traveler_fields_row($index, $traveler)
    {
        $doc_choices = alquipress_ses_document_type_choices();
        ?>
        <div class="traveler">
            <div class="traveler-head">
                <span class="traveler-title"><?php echo esc_html(sprintf(__('Viajero %d', 'alquipress'), (int) $index + 1)); ?></span>
                <button type="button" class="btn btn-danger ses-remove-traveler"><?php esc_html_e('Eliminar', 'alquipress'); ?></button>
            </div>
            <div class="grid">
                <div class="field">
                    <label><?php esc_html_e('Nombre', 'alquipress'); ?></label>
                    <input name="travelers[<?php echo (int) $index; ?>][first_name]" data-name="first_name" type="text" value="<?php echo esc_attr($traveler['first_name']); ?>" required>
                </div>
                <div class="field">
                    <label><?php esc_html_e('Primer apellido', 'alquipress'); ?></label>
                    <input name="travelers[<?php echo (int) $index; ?>][last_name]" data-name="last_name" type="text" value="<?php echo esc_attr($traveler['last_name']); ?>" required>
                </div>
                <div class="field">
                    <label><?php esc_html_e('Sexo', 'alquipress'); ?></label>
                    <select name="travelers[<?php echo (int) $index; ?>][sex]" data-name="sex" required>
                        <option value=""><?php esc_html_e('Seleccionar', 'alquipress'); ?></option>
                        <option value="M" <?php selected($traveler['sex'], 'M'); ?>>M</option>
                        <option value="F" <?php selected($traveler['sex'], 'F'); ?>>F</option>
                        <option value="X" <?php selected($traveler['sex'], 'X'); ?>>X</option>
                    </select>
                </div>
                <div class="field">
                    <label><?php esc_html_e('Fecha nacimiento', 'alquipress'); ?></label>
                    <input name="travelers[<?php echo (int) $index; ?>][birth_date]" data-name="birth_date" type="date" value="<?php echo esc_attr($traveler['birth_date']); ?>" required>
                </div>
                <div class="field">
                    <label><?php esc_html_e('Nacionalidad (ISO3)', 'alquipress'); ?></label>
                    <input name="travelers[<?php echo (int) $index; ?>][nationality]" data-name="nationality" type="text" maxlength="3" placeholder="ESP" value="<?php echo esc_attr($traveler['nationality']); ?>" required>
                </div>
                <div class="field">
                    <label><?php esc_html_e('Tipo documento', 'alquipress'); ?></label>
                    <select name="travelers[<?php echo (int) $index; ?>][doc_type]" data-name="doc_type" required>
                        <?php foreach ($doc_choices as $code => $label): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($traveler['doc_type'], $code); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label><?php esc_html_e('Número documento', 'alquipress'); ?></label>
                    <input name="travelers[<?php echo (int) $index; ?>][doc_number]" data-name="doc_number" type="text" value="<?php echo esc_attr($traveler['doc_number']); ?>" required>
                </div>
                <div class="field">
                    <label><?php esc_html_e('Fecha expedición', 'alquipress'); ?></label>
                    <input name="travelers[<?php echo (int) $index; ?>][issue_date]" data-name="issue_date" type="date" value="<?php echo esc_attr($traveler['issue_date']); ?>" required>
                </div>
                <div class="field">
                    <label><?php esc_html_e('País expedición (ISO3)', 'alquipress'); ?></label>
                    <input name="travelers[<?php echo (int) $index; ?>][issue_country]" data-name="issue_country" type="text" maxlength="3" placeholder="ESP" value="<?php echo esc_attr($traveler['issue_country']); ?>" required>
                </div>
                <div class="field">
                    <label><?php esc_html_e('Fecha caducidad (opcional)', 'alquipress'); ?></label>
                    <input name="travelers[<?php echo (int) $index; ?>][expiry_date]" data-name="expiry_date" type="date" value="<?php echo esc_attr($traveler['expiry_date']); ?>">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Pantalla de exportación XML SES.
     */
    public function render_export_page()
    {
        if (!current_user_can('edit_shop_orders')) {
            wp_die(esc_html__('Permisos insuficientes.', 'alquipress'));
        }

        $rows = $this->get_pending_rows(250);
        $valid_count = 0;
        $invalid_count = 0;
        foreach ($rows as $row) {
            if (empty($row['errors'])) {
                $valid_count++;
            } else {
                $invalid_count++;
            }
        }

        $notice = isset($_GET['ses_notice']) ? sanitize_key(wp_unslash($_GET['ses_notice'])) : '';
        $skipped = isset($_GET['skipped']) ? absint($_GET['skipped']) : 0;

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('bookings'); ?>
                <main class="ap-owners-main">
                    <header class="ap-clients-header">
                        <div class="ap-clients-header-left">
                            <h1 class="ap-clients-title"><?php esc_html_e('SES Hospedajes · Exportación XML', 'alquipress'); ?></h1>
                            <p class="ap-clients-subtitle"><?php esc_html_e('Genera XML con pedidos en estado SES pendiente', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-clients-header-right">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-bookings')); ?>" class="ap-clients-btn"><?php esc_html_e('Volver a Reservas', 'alquipress'); ?></a>
                        </div>
                    </header>

                    <?php if ($notice === 'empty'): ?>
                        <div class="notice notice-warning"><p><?php esc_html_e('No hay pedidos válidos para exportar con el criterio actual.', 'alquipress'); ?></p></div>
                    <?php elseif ($notice === 'error'): ?>
                        <div class="notice notice-error"><p><?php esc_html_e('No se pudo generar el XML SES.', 'alquipress'); ?></p></div>
                    <?php elseif ($notice === 'ok'): ?>
                        <div class="notice notice-success"><p>
                            <?php
                            if ($skipped > 0) {
                                printf(esc_html__('%d pedidos se han omitido por validación.', 'alquipress'), (int) $skipped);
                            } else {
                                esc_html_e('Exportación realizada correctamente.', 'alquipress');
                            }
                            ?>
                        </p></div>
                    <?php endif; ?>

                    <div class="ap-clients-metrics-row">
                        <div class="ap-clients-metric-card">
                            <span class="ap-clients-metric-label"><?php esc_html_e('Pendientes SES', 'alquipress'); ?></span>
                            <span class="ap-clients-metric-value"><?php echo (int) count($rows); ?></span>
                        </div>
                        <div class="ap-clients-metric-card ap-clients-metric-info">
                            <span class="ap-clients-metric-label"><?php esc_html_e('Válidos para XML', 'alquipress'); ?></span>
                            <span class="ap-clients-metric-value"><?php echo (int) $valid_count; ?></span>
                        </div>
                        <div class="ap-clients-metric-card ap-clients-metric-warning">
                            <span class="ap-clients-metric-label"><?php esc_html_e('Con incidencias', 'alquipress'); ?></span>
                            <span class="ap-clients-metric-value"><?php echo (int) $invalid_count; ?></span>
                        </div>
                    </div>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin: 0 0 16px;">
                        <?php wp_nonce_field(self::NONCE_EXPORT, 'alquipress_ses_export_nonce'); ?>
                        <input type="hidden" name="action" value="alquipress_ses_export_xml">
                        <?php foreach ($rows as $row): ?>
                            <input type="hidden" name="order_ids[]" value="<?php echo (int) $row['order_id']; ?>">
                        <?php endforeach; ?>

                        <label style="display:inline-flex;align-items:center;gap:8px;margin-right:16px;">
                            <input type="checkbox" name="only_valid" value="1" checked>
                            <span><?php esc_html_e('Exportar solo pedidos válidos (recomendado)', 'alquipress'); ?></span>
                        </label>

                        <label style="display:inline-flex;align-items:center;gap:8px;margin-right:16px;">
                            <input type="checkbox" name="mark_xml_generated" value="1" checked>
                            <span><?php esc_html_e('Marcar pedidos exportados como XML generado', 'alquipress'); ?></span>
                        </label>

                        <button type="submit" class="button button-primary" <?php disabled(count($rows) === 0); ?>>
                            <?php esc_html_e('Descargar XML SES de pendientes', 'alquipress'); ?>
                        </button>
                    </form>

                    <div class="ap-clients-table-wrap">
                        <table class="ap-clients-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Pedido', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Cliente', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Estancia', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Pago SES', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Rol', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Huéspedes', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Estado datos', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Incidencias', 'alquipress'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="8" class="ap-clients-empty"><?php esc_html_e('No hay pedidos con estado SES pendiente.', 'alquipress'); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo esc_url($row['order_edit_url']); ?>" target="_blank">
                                                    <strong>#<?php echo (int) $row['order_id']; ?></strong>
                                                </a>
                                            </td>
                                            <td>
                                                <strong><?php echo esc_html($row['guest_name']); ?></strong><br>
                                                <span class="ap-clients-email"><?php echo esc_html($row['guest_email']); ?></span>
                                            </td>
                                            <td>
                                                <?php echo esc_html($row['checkin']); ?> → <?php echo esc_html($row['checkout']); ?><br>
                                                <span class="ap-clients-property"><?php echo esc_html($row['property_name']); ?></span>
                                            </td>
                                            <td><?php echo esc_html($row['payment_type']); ?></td>
                                            <td><?php echo esc_html($row['guest_role']); ?></td>
                                            <td><?php echo (int) $row['travelers_count']; ?> / <?php echo (int) $row['required_guest_count']; ?></td>
                                            <td>
                                                <?php if (empty($row['errors'])): ?>
                                                    <span class="ap-clients-doc-badge ap-clients-doc-ok"><?php esc_html_e('Válido', 'alquipress'); ?></span>
                                                <?php else: ?>
                                                    <span class="ap-clients-doc-badge ap-clients-doc-expired"><?php esc_html_e('Revisar', 'alquipress'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (empty($row['errors'])): ?>
                                                    <span style="color:#16a34a;">—</span>
                                                <?php else: ?>
                                                    <div style="font-size:12px;line-height:1.4;">
                                                        <?php echo esc_html(implode(' | ', $row['errors'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </main>
            </div>
        </div>
        <?php
    }

    /**
     * Handler: generar y descargar XML SES de pedidos pendientes.
     */
    public function handle_export_xml()
    {
        if (!current_user_can('edit_shop_orders')) {
            wp_die(esc_html__('Permisos insuficientes.', 'alquipress'));
        }
        if (!isset($_POST['alquipress_ses_export_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alquipress_ses_export_nonce'])), self::NONCE_EXPORT)) {
            wp_die(esc_html__('Solicitud inválida.', 'alquipress'));
        }

        $order_ids = isset($_POST['order_ids']) && is_array($_POST['order_ids']) ? array_map('absint', wp_unslash($_POST['order_ids'])) : [];
        $order_ids = array_values(array_filter(array_unique($order_ids)));

        if (empty($order_ids)) {
            wp_safe_redirect(add_query_arg(['page' => 'alquipress-ses-export', 'ses_notice' => 'empty'], admin_url('admin.php')));
            exit;
        }

        $only_valid = isset($_POST['only_valid']) && $_POST['only_valid'] === '1';
        $mark_xml_generated = isset($_POST['mark_xml_generated']) && $_POST['mark_xml_generated'] === '1';

        $records = [];
        $included_orders = [];
        $skipped = 0;

        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }

            $errors = [];
            $record = $this->build_order_record($order, $errors);
            if ($only_valid && !empty($errors)) {
                $skipped++;
                continue;
            }

            $records[] = $record;
            $included_orders[] = $order;
        }

        if (empty($records)) {
            wp_safe_redirect(add_query_arg(['page' => 'alquipress-ses-export', 'ses_notice' => 'empty'], admin_url('admin.php')));
            exit;
        }

        $batch_ref = 'SESXML-' . wp_date('Ymd-His');
        $xml = $this->build_xml($records, $batch_ref);
        if ($xml === '') {
            wp_safe_redirect(add_query_arg(['page' => 'alquipress-ses-export', 'ses_notice' => 'error'], admin_url('admin.php')));
            exit;
        }

        if ($mark_xml_generated) {
            foreach ($included_orders as $order) {
                $status = (string) $order->get_meta(self::META_STATUS);
                if ($status === '' || $status === 'pending' || $status === 'rejected') {
                    $order->update_meta_data(self::META_STATUS, 'xml_generated');
                }
                if ((string) $order->get_meta(self::META_SUBMISSION_REF) === '') {
                    $order->update_meta_data(self::META_SUBMISSION_REF, $batch_ref);
                }
                $order->add_order_note(sprintf('XML SES generado: %s', $batch_ref));
                $order->save();
            }
        }

        // Limpiar cualquier buffer previo para no corromper el XML descargable.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        nocache_headers();
        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ses-hospedajes-pendientes-' . gmdate('Ymd-His') . '.xml"');
        header('X-Skipped-Orders: ' . (int) $skipped);
        echo $xml;
        exit;
    }

    /**
     * Obtener pedidos pendientes SES con validaciones.
     *
     * @param int $limit Límite de pedidos.
     * @return array
     */
    private function get_pending_rows($limit = 200)
    {
        $orders = wc_get_orders([
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
        ]);

        $rows = [];
        foreach ($orders as $order) {
            $status = (string) $order->get_meta(self::META_STATUS);
            if ($status !== '' && $status !== 'pending') {
                continue;
            }

            $order_status = (string) $order->get_status();
            if (in_array($order_status, ['cancelled', 'refunded', 'failed'], true)) {
                continue;
            }

            $errors = [];
            $record = $this->build_order_record($order, $errors);

            $rows[] = [
                'order_id' => $order->get_id(),
                'order_edit_url' => $this->get_order_edit_url($order->get_id()),
                'guest_name' => trim($record['traveler']['first_name'] . ' ' . $record['traveler']['last_name']),
                'guest_email' => $record['traveler']['email'],
                'checkin' => $record['stay']['checkin'],
                'checkout' => $record['stay']['checkout'],
                'property_name' => $record['stay']['property_name'],
                'payment_type' => $record['payment_type'],
                'guest_role' => $record['guest_role'],
                'required_guest_count' => (int) $record['required_guest_count'],
                'travelers_count' => is_array($record['travelers']) ? count($record['travelers']) : 0,
                'errors' => $errors,
            ];
        }

        return $rows;
    }

    /**
     * Construir estructura de datos de una orden para XML SES.
     *
     * @param WC_Order $order Pedido.
     * @param array    $errors Errores de validación (output).
     * @return array
     */
    private function build_order_record($order, &$errors)
    {
        $errors = [];
        $order_id = (int) $order->get_id();
        $user_id = (int) $order->get_customer_id();

        $checkin = $this->normalize_date((string) $order->get_meta('_booking_checkin_date'));
        $checkout = $this->normalize_date((string) $order->get_meta('_booking_checkout_date'));
        if ($checkin === '') {
            $errors[] = 'Falta fecha check-in';
        }
        if ($checkout === '') {
            $errors[] = 'Falta fecha check-out';
        }
        if ($checkin !== '' && $checkout !== '' && $checkin > $checkout) {
            $errors[] = 'Check-out anterior a check-in';
        }

        $property_name = Alquipress_Property_Helper::get_order_property_name($order);
        $property_id = $this->get_order_property_id($order);

        $nationality_raw = $this->get_user_acf_or_meta($user_id, 'guest_nationality', 'guest_nationality');
        if ($nationality_raw === '') {
            $nationality_raw = (string) $order->get_billing_country();
        }
        $nationality = $this->normalize_country_to_iso3($nationality_raw);
        if ($nationality === '') {
            $errors[] = 'Falta nacionalidad ISO3';
        }

        $payment_type = (string) $order->get_meta(self::META_PAYMENT_TYPE);
        if ($payment_type === '') {
            $payment_type = alquipress_ses_guess_payment_type_from_order($order);
        }
        $payment_type = alquipress_ses_normalize_payment_type($payment_type);

        $guest_role = alquipress_ses_normalize_role((string) $order->get_meta(self::META_GUEST_ROLE));
        if ($guest_role === '') {
            $guest_role = 'VI';
        }

        $required_count = $this->get_required_guest_count($order);
        $travelers = $this->get_order_travelers($order, $user_id, $nationality);
        $validation = $this->validate_travelers_payload($travelers, true, $required_count);
        $travelers = $validation['travelers'];
        foreach ($validation['errors'] as $traveler_error) {
            $errors[] = $traveler_error;
        }
        if (empty($travelers)) {
            $errors[] = 'Sin viajeros en la reserva';
        }
        $primary_traveler = !empty($travelers) ? $travelers[0] : $this->get_empty_traveler_template();

        return [
            'order_id' => $order_id,
            'order_date' => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
            'payment_type' => $payment_type,
            'guest_role' => $guest_role,
            'required_guest_count' => $required_count,
            'ses_status' => ((string) $order->get_meta(self::META_STATUS)) ?: 'pending',
            'submission_ref' => (string) $order->get_meta(self::META_SUBMISSION_REF),
            'stay' => [
                'property_id' => $property_id,
                'property_name' => $property_name,
                'checkin' => $checkin,
                'checkout' => $checkout,
                'total' => (string) $order->get_total(),
            ],
            'travelers' => $travelers,
            'traveler' => [
                'first_name' => $primary_traveler['first_name'],
                'last_name' => $primary_traveler['last_name'],
                'second_last_name' => '',
                'sex' => $primary_traveler['sex'],
                'birth_date' => $primary_traveler['birth_date'],
                'nationality' => $primary_traveler['nationality'],
                'email' => (string) $order->get_billing_email(),
                'phone' => (string) $order->get_billing_phone(),
                'documents' => [
                    [
                        'type' => $primary_traveler['doc_type'],
                        'number' => $primary_traveler['doc_number'],
                        'issue_date' => $primary_traveler['issue_date'],
                        'issue_country' => $primary_traveler['issue_country'],
                        'expiry_date' => $primary_traveler['expiry_date'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Crear XML del lote.
     *
     * @param array  $records Registros.
     * @param string $batch_ref Referencia de lote.
     * @return string
     */
    private function build_xml($records, $batch_ref)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('comunicaciones');
        $root->appendChild($dom->createElement('lote_referencia', $batch_ref));
        $root->appendChild($dom->createElement('generado_en', wp_date('Y-m-d H:i:s')));
        $root->appendChild($dom->createElement('origen', 'alquipress'));
        $root->appendChild($dom->createElement('total_registros', (string) count($records)));

        foreach ($records as $record) {
            $item = $dom->createElement('comunicacion');

            $item->appendChild($dom->createElement('pedido_id', (string) $record['order_id']));
            $item->appendChild($dom->createElement('estado_ses', (string) $record['ses_status']));
            $item->appendChild($dom->createElement('tipo_pago', (string) $record['payment_type']));
            $item->appendChild($dom->createElement('rol_persona', (string) $record['guest_role']));

            $stay = $dom->createElement('estancia');
            $stay->appendChild($dom->createElement('inmueble_id', (string) $record['stay']['property_id']));
            $stay->appendChild($dom->createElement('inmueble_nombre', (string) $record['stay']['property_name']));
            $stay->appendChild($dom->createElement('fecha_entrada', (string) $record['stay']['checkin']));
            $stay->appendChild($dom->createElement('fecha_salida', (string) $record['stay']['checkout']));
            $stay->appendChild($dom->createElement('importe_total', (string) $record['stay']['total']));
            $item->appendChild($stay);

            $traveler = $dom->createElement('viajero_principal');
            $traveler->appendChild($dom->createElement('nombre', (string) $record['traveler']['first_name']));
            $traveler->appendChild($dom->createElement('apellido1', (string) $record['traveler']['last_name']));
            $traveler->appendChild($dom->createElement('apellido2', (string) $record['traveler']['second_last_name']));
            $traveler->appendChild($dom->createElement('sexo', (string) $record['traveler']['sex']));
            $traveler->appendChild($dom->createElement('fecha_nacimiento', (string) $record['traveler']['birth_date']));
            $traveler->appendChild($dom->createElement('nacionalidad', (string) $record['traveler']['nationality']));
            $traveler->appendChild($dom->createElement('email', (string) $record['traveler']['email']));
            $traveler->appendChild($dom->createElement('telefono', (string) $record['traveler']['phone']));

            $docsNode = $dom->createElement('documentos');
            foreach ($record['traveler']['documents'] as $doc) {
                $docNode = $dom->createElement('documento');
                $docNode->appendChild($dom->createElement('tipo', (string) $doc['type']));
                $docNode->appendChild($dom->createElement('numero', (string) $doc['number']));
                $docNode->appendChild($dom->createElement('fecha_expedicion', (string) $doc['issue_date']));
                $docNode->appendChild($dom->createElement('pais_expedicion', (string) $doc['issue_country']));
                $docNode->appendChild($dom->createElement('fecha_caducidad', (string) $doc['expiry_date']));
                $docsNode->appendChild($docNode);
            }
            $traveler->appendChild($docsNode);
            $item->appendChild($traveler);

            $travelersNode = $dom->createElement('viajeros');
            foreach ($record['travelers'] as $travelerRow) {
                $travelerItem = $dom->createElement('viajero');
                $travelerItem->appendChild($dom->createElement('nombre', (string) $travelerRow['first_name']));
                $travelerItem->appendChild($dom->createElement('apellido1', (string) $travelerRow['last_name']));
                $travelerItem->appendChild($dom->createElement('sexo', (string) $travelerRow['sex']));
                $travelerItem->appendChild($dom->createElement('fecha_nacimiento', (string) $travelerRow['birth_date']));
                $travelerItem->appendChild($dom->createElement('nacionalidad', (string) $travelerRow['nationality']));
                $travelerItem->appendChild($dom->createElement('tipo_documento', (string) $travelerRow['doc_type']));
                $travelerItem->appendChild($dom->createElement('numero_documento', (string) $travelerRow['doc_number']));
                $travelerItem->appendChild($dom->createElement('fecha_expedicion', (string) $travelerRow['issue_date']));
                $travelerItem->appendChild($dom->createElement('pais_expedicion', (string) $travelerRow['issue_country']));
                $travelerItem->appendChild($dom->createElement('fecha_caducidad', (string) $travelerRow['expiry_date']));
                $travelersNode->appendChild($travelerItem);
            }
            $item->appendChild($travelersNode);

            $root->appendChild($item);
        }

        $dom->appendChild($root);
        return $dom->saveXML();
    }

    /**
     * Obtener documentación del huésped.
     *
     * @param int    $user_id Usuario.
     * @param string $fallback_country País fallback ISO3.
     * @return array
     */
    private function get_guest_documents($user_id, $fallback_country = '')
    {
        if ($user_id <= 0) {
            return [];
        }

        $rows = get_field('guest_documents', 'user_' . $user_id);
        if (!is_array($rows)) {
            return [];
        }

        $documents = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = isset($row['tipo_doc']) ? alquipress_ses_normalize_document_type((string) $row['tipo_doc']) : 'OTRO';
            $number = isset($row['numero_doc']) ? strtoupper(trim((string) $row['numero_doc'])) : '';
            $number = preg_replace('/\s+/', '', $number);
            $issue_date = isset($row['fecha_expedicion']) ? $this->normalize_date((string) $row['fecha_expedicion']) : '';
            $issue_country_raw = isset($row['pais_expedicion']) ? (string) $row['pais_expedicion'] : $fallback_country;
            $issue_country = $this->normalize_country_to_iso3($issue_country_raw);
            $expiry_date = isset($row['fecha_vencimiento']) ? $this->normalize_date((string) $row['fecha_vencimiento']) : '';

            $documents[] = [
                'type' => $type,
                'number' => $number,
                'issue_date' => $issue_date,
                'issue_country' => $issue_country,
                'expiry_date' => $expiry_date,
            ];
        }

        return $documents;
    }

    /**
     * Enviar email con enlace del formulario SES.
     *
     * @param WC_Order $order Pedido.
     * @param bool     $force Forzar envío (manual).
     * @param string   $source Origen del envío.
     * @param bool     $allow_repeat Permitir reenvío automático aunque ya se enviase antes.
     * @return bool
     */
    private function send_guest_form_link_email($order, $force = false, $source = 'manual', $allow_repeat = false)
    {
        if (!$force && !$this->can_send_guest_form_link_email($order, $allow_repeat)) {
            return false;
        }

        $to = sanitize_email((string) $order->get_billing_email());
        if ($to === '') {
            return false;
        }

        $form_url = $this->get_guest_form_url($order);
        $required_count = $this->get_required_guest_count($order);
        $subject = sprintf(__('Completa los datos de viajeros de tu reserva #%d', 'alquipress'), (int) $order->get_id());

        $message = [];
        $message[] = sprintf(__('Hola %s,', 'alquipress'), trim((string) $order->get_billing_first_name()) ?: __('cliente', 'alquipress'));
        $message[] = '';
        $message[] = __('Para finalizar la gestión de tu reserva, necesitamos los datos de todos los viajeros.', 'alquipress');
        $message[] = sprintf(__('Número de huéspedes esperados: %d', 'alquipress'), (int) $required_count);
        $message[] = __('Completa el formulario seguro aquí:', 'alquipress');
        $message[] = $form_url;
        $message[] = '';
        $message[] = __('Gracias.', 'alquipress');

        $sent = wp_mail($to, $subject, implode("\n", $message));
        if (!$sent) {
            return false;
        }

        $order->update_meta_data(self::META_FORM_LINK_SENT_AT, wp_date('Y-m-d H:i:s'));
        $order->update_meta_data(self::META_FORM_LINK_SENT_TO, $to);

        if ($source === 'manual') {
            $order->add_order_note(sprintf('Enlace SES enviado manualmente a %s', $to));
        } elseif ($source === 'auto_first_payment') {
            $order->add_order_note(sprintf('Enlace SES enviado automáticamente (primer pago) a %s', $to));
        } elseif ($source === 'auto_reminder_4d') {
            $order->add_order_note(sprintf('Recordatorio SES enviado automáticamente (4 días antes) a %s', $to));
        } else {
            $order->add_order_note(sprintf('Enlace SES enviado automáticamente (estado) a %s', $to));
        }

        $order->save();
        return true;
    }

    /**
     * Determinar si corresponde auto-enviar enlace SES.
     *
     * @param WC_Order $order Pedido.
     * @param bool     $allow_repeat Permitir reenvío automático.
     * @return bool
     */
    private function can_send_guest_form_link_email($order, $allow_repeat = false)
    {
        $status = (string) $order->get_status();
        if (in_array($status, ['cancelled', 'failed', 'refunded'], true)) {
            return false;
        }

        $completed_at = (string) $order->get_meta(self::META_FORM_COMPLETED_AT);
        if ($completed_at !== '') {
            return false;
        }

        $already_sent = (string) $order->get_meta(self::META_FORM_LINK_SENT_AT);
        if (!$allow_repeat && $already_sent !== '') {
            return false;
        }

        return true;
    }

    /**
     * Determina si un estado implica que ya hubo al menos un pago.
     *
     * @param string $status Estado WooCommerce sin prefijo wc-.
     * @return bool
     */
    private function is_paid_like_status($status)
    {
        $status = (string) $status;
        $paid_statuses = ['processing', 'deposito-ok', 'pending-checkin', 'in-progress', 'completed'];
        return in_array($status, $paid_statuses, true);
    }

    /**
     * Obtener URL segura del formulario de viajeros.
     *
     * @param WC_Order $order Pedido.
     * @return string
     */
    private function get_guest_form_url($order)
    {
        $token = $this->get_or_create_form_token($order);
        return add_query_arg([
            'alquipress_ses_guest_form' => '1',
            'order_id' => (int) $order->get_id(),
            'token' => $token,
        ], home_url('/'));
    }

    /**
     * Obtener o crear token de formulario.
     *
     * @param WC_Order $order Pedido.
     * @return string
     */
    private function get_or_create_form_token($order)
    {
        $token = (string) $order->get_meta(self::META_FORM_TOKEN);
        if ($token !== '') {
            return $token;
        }

        try {
            $token = bin2hex(random_bytes(24));
        } catch (Exception $e) {
            $token = wp_generate_password(48, false, false);
        }

        $order->update_meta_data(self::META_FORM_TOKEN, $token);
        $order->save();

        return $token;
    }

    /**
     * URL retorno a la edición del pedido.
     *
     * @param int $order_id Pedido.
     * @return string
     */
    private function get_order_return_admin_url($order_id)
    {
        return $this->get_order_edit_url($order_id);
    }

    /**
     * Obtener nº de huéspedes esperados para una reserva.
     *
     * @param WC_Order $order Pedido.
     * @return int
     */
    private function get_required_guest_count($order)
    {
        $count = (int) $order->get_meta(self::META_GUEST_COUNT);
        if ($count > 0) {
            return $count;
        }

        $detected = 0;
        foreach ($order->get_items() as $item) {
            $candidate_keys = [
                '_booking_persons',
                '_persons',
                'Persons',
                'persons',
                'Guests',
                'guests',
                'Adults',
                'Children',
            ];
            foreach ($candidate_keys as $key) {
                $raw = $item->get_meta($key, true);
                $num = $this->extract_people_count($raw);
                if ($num > $detected) {
                    $detected = $num;
                }
            }

            foreach ($item->get_meta_data() as $meta) {
                $k = is_object($meta) && isset($meta->key) ? (string) $meta->key : '';
                if ($k === '') {
                    continue;
                }
                if (stripos($k, 'person') !== false || stripos($k, 'guest') !== false || stripos($k, 'adult') !== false || stripos($k, 'child') !== false) {
                    $num = $this->extract_people_count(is_object($meta) && isset($meta->value) ? $meta->value : '');
                    if ($num > $detected) {
                        $detected = $num;
                    }
                }
            }
        }

        if ($detected > 0) {
            return $detected;
        }

        $adult = absint($order->get_meta('_booking_adults'));
        $child = absint($order->get_meta('_booking_children'));
        if (($adult + $child) > 0) {
            return ($adult + $child);
        }

        return 1;
    }

    /**
     * Extraer número de personas desde un valor de metadato mixto.
     *
     * @param mixed $raw Valor.
     * @return int
     */
    private function extract_people_count($raw)
    {
        if (is_numeric($raw)) {
            return max(0, (int) $raw);
        }

        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') {
                return 0;
            }

            if (is_numeric($raw)) {
                return max(0, (int) $raw);
            }

            $maybe_unserialized = maybe_unserialize($raw);
            if ($maybe_unserialized !== $raw) {
                return $this->extract_people_count($maybe_unserialized);
            }

            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->extract_people_count($decoded);
            }
        }

        if (is_array($raw)) {
            $sum = 0;
            foreach ($raw as $v) {
                $sum += $this->extract_people_count($v);
            }
            return $sum;
        }

        if (is_object($raw)) {
            return $this->extract_people_count((array) $raw);
        }

        return 0;
    }

    /**
     * Viajero vacío para plantillas.
     *
     * @return array
     */
    private function get_empty_traveler_template()
    {
        return [
            'first_name' => '',
            'last_name' => '',
            'sex' => '',
            'birth_date' => '',
            'nationality' => '',
            'doc_type' => 'NIF',
            'doc_number' => '',
            'issue_date' => '',
            'issue_country' => '',
            'expiry_date' => '',
        ];
    }

    /**
     * Obtener viajeros pre-cargados para formulario.
     *
     * @param WC_Order $order Pedido.
     * @return array
     */
    private function get_prefill_travelers($order)
    {
        $required_count = $this->get_required_guest_count($order);
        $travelers = $this->get_order_travelers($order, (int) $order->get_customer_id(), $this->normalize_country_to_iso3((string) $order->get_billing_country()));
        if (empty($travelers)) {
            $fallback = $this->get_empty_traveler_template();
            $fallback['first_name'] = trim((string) $order->get_billing_first_name());
            $fallback['last_name'] = trim((string) $order->get_billing_last_name());
            $fallback['nationality'] = $this->normalize_country_to_iso3((string) $order->get_billing_country());
            $travelers = [$fallback];
        }
        while (count($travelers) < $required_count) {
            $travelers[] = $this->get_empty_traveler_template();
        }
        return $travelers;
    }

    /**
     * Obtener viajeros desde meta de pedido o fallback CRM.
     *
     * @param WC_Order $order Pedido.
     * @param int      $user_id Usuario.
     * @param string   $fallback_country País fallback.
     * @return array
     */
    private function get_order_travelers($order, $user_id, $fallback_country = '')
    {
        $from_order = $order->get_meta(self::META_TRAVELERS);
        if (is_array($from_order) && !empty($from_order)) {
            $validation = $this->validate_travelers_payload($this->sanitize_travelers_payload($from_order), false);
            if (!empty($validation['travelers'])) {
                return $validation['travelers'];
            }
        }

        $travelers = [];
        $first_name = trim((string) $order->get_billing_first_name());
        $last_name = trim((string) $order->get_billing_last_name());
        if ($first_name === '' && $user_id > 0) {
            $first_name = trim((string) get_user_meta($user_id, 'first_name', true));
        }
        if ($last_name === '' && $user_id > 0) {
            $last_name = trim((string) get_user_meta($user_id, 'last_name', true));
        }
        $birth_date = $this->normalize_date($this->get_user_meta_with_fallback($user_id, ['guest_birth_date', 'guest_birthdate', 'billing_birthdate', 'billing_birth_date']));
        $sex = $this->normalize_sex($this->get_user_meta_with_fallback($user_id, ['guest_sex', 'guest_gender', 'billing_gender', 'gender']));
        $nationality_raw = $this->get_user_acf_or_meta($user_id, 'guest_nationality', 'guest_nationality');
        if ($nationality_raw === '') {
            $nationality_raw = $fallback_country;
        }
        $nationality = $this->normalize_country_to_iso3($nationality_raw);

        $documents = $this->get_guest_documents($user_id, $nationality);
        if (!empty($documents)) {
            foreach ($documents as $doc) {
                $travelers[] = [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'sex' => $sex,
                    'birth_date' => $birth_date,
                    'nationality' => $nationality,
                    'doc_type' => $doc['type'],
                    'doc_number' => $doc['number'],
                    'issue_date' => $doc['issue_date'],
                    'issue_country' => $doc['issue_country'],
                    'expiry_date' => $doc['expiry_date'],
                ];
            }
        }

        return $travelers;
    }

    /**
     * Sanitizar payload de viajeros.
     *
     * @param array $rows Payload.
     * @return array
     */
    private function sanitize_travelers_payload($rows)
    {
        $sanitized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = $this->get_empty_traveler_template();
            $entry['first_name'] = sanitize_text_field($row['first_name'] ?? '');
            $entry['last_name'] = sanitize_text_field($row['last_name'] ?? '');
            $entry['sex'] = sanitize_text_field($row['sex'] ?? '');
            $entry['birth_date'] = sanitize_text_field($row['birth_date'] ?? '');
            $entry['nationality'] = sanitize_text_field($row['nationality'] ?? '');
            $entry['doc_type'] = sanitize_text_field($row['doc_type'] ?? 'NIF');
            $entry['doc_number'] = sanitize_text_field($row['doc_number'] ?? '');
            $entry['issue_date'] = sanitize_text_field($row['issue_date'] ?? '');
            $entry['issue_country'] = sanitize_text_field($row['issue_country'] ?? '');
            $entry['expiry_date'] = sanitize_text_field($row['expiry_date'] ?? '');

            $is_empty = true;
            foreach ($entry as $val) {
                if (trim((string) $val) !== '') {
                    $is_empty = false;
                    break;
                }
            }
            if (!$is_empty) {
                $sanitized[] = $entry;
            }
        }
        return $sanitized;
    }

    /**
     * Validar y normalizar viajeros.
     *
     * @param array $travelers Viajeros.
     * @param bool  $strict Requiere campos obligatorios.
     * @param int   $required_count Nº mínimo de viajeros esperados.
     * @return array
     */
    private function validate_travelers_payload($travelers, $strict = true, $required_count = 1)
    {
        $errors = [];
        $normalized = [];
        $today = wp_date('Y-m-d');

        foreach ($travelers as $i => $traveler) {
            $t = $this->get_empty_traveler_template();
            foreach ($t as $key => $_) {
                $t[$key] = isset($traveler[$key]) ? trim((string) $traveler[$key]) : '';
            }

            $t['sex'] = $this->normalize_sex($t['sex']);
            $t['birth_date'] = $this->normalize_date($t['birth_date']);
            $t['nationality'] = $this->normalize_country_to_iso3($t['nationality']);
            $t['doc_type'] = alquipress_ses_normalize_document_type($t['doc_type']);
            $t['doc_number'] = strtoupper(preg_replace('/\s+/', '', $t['doc_number']));
            $t['issue_date'] = $this->normalize_date($t['issue_date']);
            $t['issue_country'] = $this->normalize_country_to_iso3($t['issue_country']);
            $t['expiry_date'] = $this->normalize_date($t['expiry_date']);

            $label = sprintf('Viajero %d', $i + 1);
            if ($strict) {
                if ($t['first_name'] === '') {
                    $errors[] = $label . ': falta nombre';
                }
                if ($t['last_name'] === '') {
                    $errors[] = $label . ': falta primer apellido';
                }
                if ($t['sex'] === '') {
                    $errors[] = $label . ': falta sexo';
                }
                if ($t['birth_date'] === '') {
                    $errors[] = $label . ': falta fecha nacimiento';
                }
                if ($t['nationality'] === '') {
                    $errors[] = $label . ': nacionalidad inválida';
                }
                if ($t['doc_number'] === '') {
                    $errors[] = $label . ': falta número documento';
                }
                if ($t['issue_date'] === '') {
                    $errors[] = $label . ': falta fecha expedición';
                }
                if ($t['issue_country'] === '') {
                    $errors[] = $label . ': país expedición inválido';
                }
                if ($t['issue_date'] !== '' && $t['issue_date'] > $today) {
                    $errors[] = $label . ': fecha expedición futura';
                }
                if ($t['birth_date'] !== '' && $t['birth_date'] > $today) {
                    $errors[] = $label . ': fecha nacimiento futura';
                }
                if ($t['doc_type'] === 'NIF' && $t['issue_country'] !== 'ESP') {
                    $errors[] = $label . ': NIF requiere país expedición ESP';
                }
            }

            $normalized[] = $t;
        }

        $required_count = max(1, (int) $required_count);
        if ($strict && empty($normalized)) {
            $errors[] = 'Debes añadir al menos un viajero';
        }
        if ($strict && count($normalized) < $required_count) {
            $errors[] = sprintf('Faltan viajeros: se esperan %d y hay %d', $required_count, count($normalized));
        }

        return ['travelers' => $normalized, 'errors' => $errors];
    }

    /**
     * Sincronizar viajero principal al perfil CRM del usuario.
     *
     * @param WC_Order $order Pedido.
     * @param array    $traveler Viajero principal.
     */
    private function sync_primary_traveler_to_profile($order, $traveler)
    {
        $user_id = (int) $order->get_customer_id();
        if ($user_id <= 0) {
            return;
        }

        update_user_meta($user_id, 'first_name', $traveler['first_name']);
        update_user_meta($user_id, 'last_name', $traveler['last_name']);
        update_user_meta($user_id, 'guest_sex', $traveler['sex']);
        update_user_meta($user_id, 'guest_birth_date', $traveler['birth_date']);
        update_user_meta($user_id, 'guest_nationality', $traveler['nationality']);

        if (function_exists('update_field')) {
            update_field('guest_sex', $traveler['sex'], 'user_' . $user_id);
            update_field('guest_birth_date', $traveler['birth_date'], 'user_' . $user_id);
            update_field('guest_nationality', $traveler['nationality'], 'user_' . $user_id);

            $documents = [[
                'tipo_doc' => $traveler['doc_type'],
                'numero_doc' => $traveler['doc_number'],
                'fecha_expedicion' => $traveler['issue_date'],
                'pais_expedicion' => $traveler['issue_country'],
                'fecha_vencimiento' => $traveler['expiry_date'],
                'nombre_doc' => '',
                'archivo_doc' => '',
            ]];
            update_field('guest_documents', $documents, 'user_' . $user_id);
        }
    }

    /**
     * Resolver objeto WC_Order desde pantalla.
     *
     * @param mixed $post Contexto callback metabox.
     * @return WC_Order|false
     */
    private function resolve_order_from_screen($post)
    {
        if (is_object($post) && isset($post->ID)) {
            $order = wc_get_order((int) $post->ID);
            if ($order) {
                return $order;
            }
        }

        $order_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($order_id > 0) {
            return wc_get_order($order_id);
        }

        return false;
    }

    /**
     * URL edición pedido, compatible clásico/HPOS.
     *
     * @param int $order_id ID pedido.
     * @return string
     */
    private function get_order_edit_url($order_id)
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            if (\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
                return admin_url('admin.php?page=wc-orders&action=edit&id=' . (int) $order_id);
            }
        }

        return admin_url('post.php?post=' . (int) $order_id . '&action=edit');
    }

    /**
     * Obtener primer product_id de la orden.
     *
     * @param WC_Order $order Pedido.
     * @return int
     */
    private function get_order_property_id($order)
    {
        foreach ($order->get_items() as $item) {
            $product = is_object($item) && method_exists($item, 'get_product') ? $item->get_product() : null;
            if ($product) {
                return (int) $product->get_id();
            }
        }
        return 0;
    }

    /**
     * Fallback de metadatos de usuario.
     *
     * @param int   $user_id Usuario.
     * @param array $keys Claves.
     * @return string
     */
    private function get_user_meta_with_fallback($user_id, $keys)
    {
        if ($user_id <= 0) {
            return '';
        }

        foreach ($keys as $key) {
            $value = trim((string) get_user_meta($user_id, $key, true));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Intentar obtener valor ACF y fallback a user meta.
     *
     * @param int    $user_id Usuario.
     * @param string $acf_field Campo ACF.
     * @param string $meta_key Meta fallback.
     * @return string
     */
    private function get_user_acf_or_meta($user_id, $acf_field, $meta_key)
    {
        $value = '';
        if ($user_id > 0 && function_exists('get_field')) {
            $value = trim((string) get_field($acf_field, 'user_' . $user_id));
        }

        if ($value === '' && $user_id > 0) {
            $value = trim((string) get_user_meta($user_id, $meta_key, true));
        }

        return $value;
    }

    /**
     * Normalizar sexo a M/F/X.
     *
     * @param string $value Entrada.
     * @return string
     */
    private function normalize_sex($value)
    {
        $value = strtoupper(trim((string) $value));
        $map = [
            'M' => 'M',
            'H' => 'M',
            'MALE' => 'M',
            'HOMBRE' => 'M',
            'F' => 'F',
            'FEMALE' => 'F',
            'MUJER' => 'F',
            'X' => 'X',
            'O' => 'X',
            'OTRO' => 'X',
            'OTHER' => 'X',
        ];

        return isset($map[$value]) ? $map[$value] : '';
    }

    /**
     * Normalizar fecha a YYYY-MM-DD.
     *
     * @param string $value Fecha.
     * @return string
     */
    private function normalize_date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (alquipress_is_iso_date($value)) {
            return $value;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
            $iso = $m[3] . '-' . $m[2] . '-' . $m[1];
            return alquipress_is_iso_date($iso) ? $iso : '';
        }

        $ts = strtotime($value);
        if (!$ts) {
            return '';
        }

        return wp_date('Y-m-d', $ts);
    }

    /**
     * Normalizar país a ISO3.
     *
     * @param string $value País en texto, ISO2 o ISO3.
     * @return string
     */
    private function normalize_country_to_iso3($value)
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return '';
        }

        $map = [
            'ES' => 'ESP',
            'ESP' => 'ESP',
            'ESPAÑA' => 'ESP',
            'SPAIN' => 'ESP',
            'FR' => 'FRA',
            'FRA' => 'FRA',
            'FRANCE' => 'FRA',
            'FRANCIA' => 'FRA',
            'DE' => 'DEU',
            'DEU' => 'DEU',
            'GERMANY' => 'DEU',
            'ALEMANIA' => 'DEU',
            'IT' => 'ITA',
            'ITA' => 'ITA',
            'ITALY' => 'ITA',
            'ITALIA' => 'ITA',
            'PT' => 'PRT',
            'PRT' => 'PRT',
            'PORTUGAL' => 'PRT',
            'GB' => 'GBR',
            'UK' => 'GBR',
            'GBR' => 'GBR',
            'UNITED KINGDOM' => 'GBR',
            'US' => 'USA',
            'USA' => 'USA',
            'UNITED STATES' => 'USA',
            'EEUU' => 'USA',
            'NL' => 'NLD',
            'NLD' => 'NLD',
            'BELGIUM' => 'BEL',
            'BE' => 'BEL',
            'BEL' => 'BEL',
            'CH' => 'CHE',
            'CHE' => 'CHE',
            'SWITZERLAND' => 'CHE',
            'SUIZA' => 'CHE',
            'IE' => 'IRL',
            'IRL' => 'IRL',
            'IRLANDA' => 'IRL',
            'DK' => 'DNK',
            'DNK' => 'DNK',
            'SE' => 'SWE',
            'SWE' => 'SWE',
            'NO' => 'NOR',
            'NOR' => 'NOR',
            'FI' => 'FIN',
            'FIN' => 'FIN',
            'AT' => 'AUT',
            'AUT' => 'AUT',
        ];

        if (isset($map[$value])) {
            return $map[$value];
        }

        if (preg_match('/^[A-Z]{3}$/', $value)) {
            return $value;
        }

        return '';
    }

    /**
     * Normalizar datetime-local (YYYY-MM-DDTHH:MM) a datetime SQL.
     *
     * @param string $value Valor entrada.
     * @return string
     */
    private function normalize_datetime_local($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            return '';
        }

        $date = str_replace('T', ' ', $value) . ':00';
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return '';
        }

        return wp_date('Y-m-d H:i:s', $timestamp);
    }
}

new Alquipress_SES_Compliance();
