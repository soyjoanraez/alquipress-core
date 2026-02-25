<?php
/**
 * Meta box Control Operativo y automatizaciones por cambio de estado del pipeline.
 *
 * @package Alquipress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Pipeline_Control {

    const NONCE = 'alquipress_pipeline_control_nonce';
    const META_CLEANING = '_alquipress_cleaning_scheduled';
    const META_KEYS = '_alquipress_keys_delivered';
    const META_PROPERTY_REVIEWED = '_alquipress_property_reviewed';
    const META_DEPOSIT_PROCESSED = '_alquipress_deposit_processed';
    const META_CHECKIN_REAL = '_alquipress_checkin_real';
    const META_CHECKOUT_REAL = '_alquipress_checkout_real';
    const META_CYCLE_COMPLETED = '_alquipress_cycle_completed';

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'add_metabox']);
        add_action('woocommerce_process_shop_order_meta', [__CLASS__, 'save_metabox'], 20, 1);
        add_action('woocommerce_update_order', [__CLASS__, 'save_metabox'], 20, 1);
        add_action('woocommerce_order_status_pending-checkin', [__CLASS__, 'on_pending_checkin'], 10, 1);
        add_action('woocommerce_order_status_in-progress', [__CLASS__, 'on_in_progress'], 10, 1);
        add_action('woocommerce_order_status_checkout-review', [__CLASS__, 'on_checkout_review'], 10, 1);
        add_action('woocommerce_order_status_deposit-refunded', [__CLASS__, 'on_deposit_refunded'], 10, 1);
        add_action('alquipress_send_wellbeing_email', [__CLASS__, 'send_wellbeing_email'], 10, 1);
    }

    /**
     * Email de cortesía T+24h tras check-in (¿todo bien?).
     */
    public static function send_wellbeing_email($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $product_name = '';
        foreach ($order->get_items() as $item) {
            if (is_object($item) && method_exists($item, 'get_name')) {
                $product_name = $item->get_name();
                break;
            }
        }
        $subject = sprintf('☀️ %s — %s', __('¿Todo bien en', 'alquipress'), $product_name ?: __('tu alojamiento', 'alquipress'));
        $message = sprintf(
            __("Hola %s,\n\nEsperamos que estéis disfrutando de vuestra estancia.\n¿Hay algo que podamos mejorar o necesitáis algo?\n\nEstamos a vuestra disposición.\n\nSaludos,", 'alquipress'),
            $order->get_billing_first_name()
        );
        wp_mail($order->get_billing_email(), $subject, $message);
    }

    public static function add_metabox() {
        $screens = ['shop_order', 'woocommerce_page_wc-orders'];
        foreach ($screens as $screen) {
            add_meta_box(
                'alquipress_pipeline_control',
                __('ALQUIPRESS — Control Operativo', 'alquipress'),
                [__CLASS__, 'render_metabox'],
                $screen,
                'side',
                'high'
            );
        }
    }

    public static function render_metabox($post_or_order) {
        $order = self::resolve_order($post_or_order);
        if (!$order) {
            echo '<p>' . esc_html__('No se pudo cargar el pedido.', 'alquipress') . '</p>';
            return;
        }
        $order_id = $order->get_id();
        wp_nonce_field('alquipress_pipeline_control_save', self::NONCE);

        $ses_status = (string) $order->get_meta('_alq_ses_status');
        $ses_ok = in_array($ses_status, ['sent', 'accepted', 'xml_generated'], true);
        $cleaning = (string) $order->get_meta(self::META_CLEANING);
        $keys = (string) $order->get_meta(self::META_KEYS);
        $reviewed = (string) $order->get_meta(self::META_PROPERTY_REVIEWED);
        $deposit = (string) $order->get_meta(self::META_DEPOSIT_PROCESSED);

        $labels = [
            'ses' => __('Parte SES enviado', 'alquipress'),
            'cleaning' => __('Limpieza programada', 'alquipress'),
            'keys' => __('Llaves entregadas', 'alquipress'),
            'reviewed' => __('Propiedad revisada (salida)', 'alquipress'),
            'deposit' => __('Fianza procesada', 'alquipress'),
        ];
        ?>
        <div class="alquipress-pipeline-control">
            <p><strong><?php esc_html_e('Estado SES:', 'alquipress'); ?></strong>
                <span class="ses-badge ses-<?php echo esc_attr($ses_status ?: 'pending'); ?>"><?php echo esc_html($ses_status ?: 'pendiente'); ?></span>
            </p>
            <hr style="margin:10px 0;">
            <p><strong><?php esc_html_e('Checklist operativa', 'alquipress'); ?></strong></p>
            <label class="alquipress-checklist-item" style="display:block;margin:6px 0;">
                <input type="checkbox" disabled <?php checked($ses_ok); ?>>
                <?php echo esc_html($labels['ses']); ?>
            </label>
            <label class="alquipress-checklist-item" style="display:block;margin:6px 0;">
                <input type="checkbox" name="alquipress_control_cleaning" value="1" <?php checked($cleaning, '1'); ?>>
                <?php echo esc_html($labels['cleaning']); ?>
            </label>
            <label class="alquipress-checklist-item" style="display:block;margin:6px 0;">
                <input type="checkbox" name="alquipress_control_keys" value="1" <?php checked($keys, '1'); ?>>
                <?php echo esc_html($labels['keys']); ?>
            </label>
            <label class="alquipress-checklist-item" style="display:block;margin:6px 0;">
                <input type="checkbox" name="alquipress_control_reviewed" value="1" <?php checked($reviewed, '1'); ?>>
                <?php echo esc_html($labels['reviewed']); ?>
            </label>
            <label class="alquipress-checklist-item" style="display:block;margin:6px 0;">
                <input type="checkbox" name="alquipress_control_deposit" value="1" <?php checked($deposit, '1'); ?>>
                <?php echo esc_html($labels['deposit']); ?>
            </label>
        </div>
        <?php
    }

    private static function resolve_order($post_or_order) {
        if ($post_or_order instanceof WC_Order) {
            return $post_or_order;
        }
        if (is_object($post_or_order) && isset($post_or_order->ID)) {
            return wc_get_order($post_or_order->ID);
        }
        if (is_numeric($post_or_order)) {
            return wc_get_order((int) $post_or_order);
        }
        return null;
    }

    public static function save_metabox($order_id) {
        if (!isset($_POST[self::NONCE]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE])), 'alquipress_pipeline_control_save')) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $order->update_meta_data(self::META_CLEANING, isset($_POST['alquipress_control_cleaning']) ? '1' : '0');
        $order->update_meta_data(self::META_KEYS, isset($_POST['alquipress_control_keys']) ? '1' : '0');
        $order->update_meta_data(self::META_PROPERTY_REVIEWED, isset($_POST['alquipress_control_reviewed']) ? '1' : '0');
        $order->update_meta_data(self::META_DEPOSIT_PROCESSED, isset($_POST['alquipress_control_deposit']) ? '1' : '0');
        $order->save_meta_data();
    }

    public static function on_pending_checkin($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $cleaning_email = get_option('alquipress_cleaning_email', get_option('admin_email'));
        $body = self::get_cleaning_email_body($order);
        wp_mail(
            $cleaning_email,
            sprintf('ALQUIPRESS — %s: Reserva #%s', __('Preparar propiedad', 'alquipress'), $order_id),
            $body
        );
        $ses_status = (string) get_post_meta($order_id, '_alq_ses_status', true);
        if (!in_array($ses_status, ['sent', 'accepted', 'xml_generated'], true)) {
            wp_mail(
                get_option('admin_email'),
                '⚠️ ALQUIPRESS: ' . __('SES pendiente', 'alquipress') . ' — Reserva #' . $order_id,
                __('El parte de viajeros SES no está enviado. Check-in próximo.', 'alquipress')
            );
        }
    }

    public static function on_in_progress($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $order->update_meta_data(self::META_CHECKIN_REAL, current_time('mysql'));
        $order->save_meta_data();
        if (function_exists('wp_schedule_single_event')) {
            wp_schedule_single_event(
                time() + DAY_IN_SECONDS,
                'alquipress_send_wellbeing_email',
                [$order_id]
            );
        }
    }

    public static function on_checkout_review($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $order->update_meta_data(self::META_CHECKOUT_REAL, current_time('mysql'));
        $order->save_meta_data();
        $cleaning_email = get_option('alquipress_cleaning_email', get_option('admin_email'));
        wp_mail(
            $cleaning_email,
            sprintf('ALQUIPRESS — %s: Reserva #%s', __('Revisión de salida', 'alquipress'), $order_id),
            __('El huésped ha salido. Revisar el estado de la propiedad y confirmar si se devuelve la fianza.', 'alquipress')
        );
    }

    public static function on_deposit_refunded($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $order->update_meta_data(self::META_CYCLE_COMPLETED, current_time('mysql'));
        $order->save_meta_data();
        $order->add_order_note(__('Ciclo completo. Fianza devuelta. Reserva archivada.', 'alquipress'));
    }

    private static function get_cleaning_email_body($order) {
        $order_id = $order->get_id();
        $lines    = [
            sprintf(__('Reserva #%s', 'alquipress'), $order_id),
            sprintf(__('Cliente: %s', 'alquipress'), $order->get_formatted_billing_full_name()),
            sprintf(__('Email: %s', 'alquipress'), $order->get_billing_email()),
            '',
        ];

        // Leer checkin/checkout desde Ap_Booking (campo order_id en wp_ap_booking)
        $ap_booking = self::get_ap_booking_for_order($order_id);
        if ($ap_booking) {
            $fmt = get_option('date_format');
            $lines[] = sprintf(__('Check-in: %s', 'alquipress'), date_i18n($fmt, strtotime($ap_booking->checkin)));
            $lines[] = sprintf(__('Check-out: %s', 'alquipress'), date_i18n($fmt, strtotime($ap_booking->checkout)));
        } else {
            // Fallback: leer metadatos guardados en el pedido por el booking widget
            $checkin  = get_post_meta($order_id, 'ap_checkin', true);
            $checkout = get_post_meta($order_id, 'ap_checkout', true);
            if ($checkin) {
                $fmt = get_option('date_format');
                $lines[] = sprintf(__('Check-in: %s', 'alquipress'), date_i18n($fmt, strtotime($checkin)));
                $lines[] = sprintf(__('Check-out: %s', 'alquipress'), date_i18n($fmt, strtotime($checkout)));
            }
        }

        $lines[] = '';
        $lines[] = admin_url('post.php?post=' . $order_id . '&action=edit');
        return implode("\n", $lines);
    }

    /**
     * Obtener la primera Ap_Booking asociada a un pedido.
     */
    private static function get_ap_booking_for_order(int $order_id): ?Ap_Booking
    {
        if (!class_exists('Ap_Booking')) {
            return null;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';
        $row   = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %d LIMIT 1", $order_id),
            ARRAY_A
        );
        return $row ? Ap_Booking::from_row($row) : null;
    }
}
