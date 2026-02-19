<?php
/**
 * ALQUIPRESS — Integración WC Deposits + Stripe (Módulo 02)
 * Forzar tokenización, aviso legal en checkout y notificación de cobro fallido.
 *
 * @package Alquipress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_WC_Deposits_Payments {

    public static function init() {
        add_filter('wc_deposits_force_save_card', '__return_true');

        add_filter('woocommerce_stripe_force_save_source', [__CLASS__, 'force_stripe_save_source'], 10, 2);
        add_filter('woocommerce_get_order_item_totals', [__CLASS__, 'add_deposit_legal_notice'], 10, 3);
        add_action('woocommerce_payment_token_added_to_order', [__CLASS__, 'log_token_saved'], 10, 4);
        add_action('woocommerce_order_status_failed', [__CLASS__, 'notify_admin_balance_failed'], 10);
    }

    /**
     * Forzar guardado de tarjeta en Stripe cuando el pedido tiene depósito.
     */
    public static function force_stripe_save_source($force, $order) {
        if (!is_object($order)) {
            return $force;
        }
        $order_id = is_numeric($order) ? (int) $order : (int) $order->get_id();
        if ($order_id && class_exists('WC_Deposits_Order_Manager')) {
            if (WC_Deposits_Order_Manager::has_deposit($order_id)) {
                return true;
            }
        }
        return $force;
    }

    /**
     * Aviso legal en el resumen del pedido: el saldo se cobrará automáticamente.
     */
    public static function add_deposit_legal_notice($total_rows, $order, $tax_display) {
        if (!class_exists('WC_Deposits_Order_Manager')) {
            return $total_rows;
        }
        $order_id = is_numeric($order) ? (int) $order : (int) $order->get_id();
        if (!$order_id || !WC_Deposits_Order_Manager::has_deposit($order_id)) {
            return $total_rows;
        }
        $aviso = __('El saldo restante (60%) se cargará automáticamente en su tarjeta 7 días antes del check-in.', 'alquipress');
        $total_rows['alquipress_deposit_notice'] = [
            'label' => __('Aviso de pago automático:', 'alquipress'),
            'value' => $aviso,
        ];
        return $total_rows;
    }

    /**
     * Registrar en el pedido cuando se guarda el token para cobro automático.
     */
    public static function log_token_saved($order_id, $token_id, $token, $user_id) {
        $order = wc_get_order($order_id);
        if ($order && is_object($token)) {
            $order->add_order_note(
                sprintf(
                    __('Token de pago guardado para cobro automático. Token ID: %1$s | Gateway: %2$s', 'alquipress'),
                    $token->get_id(),
                    $token->get_gateway_id()
                )
            );
        }
    }

    /**
     * Notificar al admin cuando falla el cobro automático del balance (segundo cobro).
     */
    public static function notify_admin_balance_failed($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $is_balance = get_post_meta($order_id, '_wc_deposits_is_balance_order', true);
        if (!$is_balance) {
            return;
        }
        $parent_order_id = get_post_meta($order_id, '_wc_deposits_parent_order_id', true);
        $checkin_label = self::get_checkin_label_for_order($parent_order_id);

        $admin_email = get_option('admin_email');
        $subject = '⚠️ ALQUIPRESS: ' . __('Cobro automático FALLIDO', 'alquipress') . ' — #' . $parent_order_id;
        $message = sprintf(
            __("El cobro automático del saldo restante ha fallado.\n\n", 'alquipress') .
            __("Reserva padre: #%s\n", 'alquipress') .
            __("Pedido de balance: #%s\n", 'alquipress') .
            __("Importe: %s\n\n", 'alquipress') .
            __("Acción requerida: Contactar al cliente o cancelar la reserva.\n\n", 'alquipress') .
            __("Ver reserva: %s", 'alquipress'),
            $parent_order_id,
            $order_id,
            $order->get_formatted_order_total(),
            admin_url('post.php?post=' . (int) $parent_order_id . '&action=edit')
        );
        if ($checkin_label) {
            $message = sprintf(__("Check-in: %s\n\n", 'alquipress'), $checkin_label) . $message;
        }
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Obtener etiqueta de fecha de check-in para un pedido (desde WC Bookings si existe).
     */
    private static function get_checkin_label_for_order($order_id) {
        $order_id = (int) $order_id;
        if (!$order_id) {
            return '';
        }
        if (class_exists('WC_Booking_Data_Store') && method_exists('WC_Booking_Data_Store', 'get_booking_ids_from_order_id')) {
            $booking_ids = WC_Booking_Data_Store::get_booking_ids_from_order_id($order_id);
            if (!empty($booking_ids) && class_exists('WC_Booking')) {
                $booking = new WC_Booking($booking_ids[0]);
                if ($booking && method_exists($booking, 'get_start') && $booking->get_start()) {
                    return date_i18n(get_option('date_format'), $booking->get_start());
                }
            }
        }
        global $wpdb;
        $booking_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_booking_order_id' AND meta_value = %s LIMIT 1",
            (string) $order_id
        ));
        if ($booking_id) {
            $start = get_post_meta($booking_id, '_booking_start', true);
            if ($start) {
                return date_i18n(get_option('date_format'), strtotime($start));
            }
        }
        return '';
    }
}

