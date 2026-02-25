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

        // Compatibilidad con motor propio de depósitos (apm_payment_failed)
        add_action('apm_payment_failed', [__CLASS__, 'handle_apm_payment_failed'], 10, 3);
    }

    /**
     * Forzar guardado de tarjeta en Stripe cuando el pedido tiene depósito
     * (WC Deposits legacy o motor propio APM).
     */
    public static function force_stripe_save_source($force, $order) {
        if (!is_object($order)) {
            return $force;
        }
        $order_id = is_numeric($order) ? (int) $order : (int) $order->get_id();

        // Motor propio APM
        if ($order_id) {
            $wc_order = is_object($order) && method_exists($order, 'get_meta') ? $order : wc_get_order($order_id);
            if ($wc_order && $wc_order->get_meta('_apm_is_staged_payment') === 'yes') {
                return true;
            }
        }

        // WC Deposits (legacy)
        if ($order_id && class_exists('WC_Deposits_Order_Manager')) {
            if (WC_Deposits_Order_Manager::has_deposit($order_id)) {
                return true;
            }
        }
        return $force;
    }

    /**
     * Aviso legal en el resumen del pedido: el saldo se cobrará automáticamente.
     * Cubre tanto WC Deposits (legacy) como el motor propio APM.
     */
    public static function add_deposit_legal_notice($total_rows, $order, $tax_display) {
        $order_id = is_numeric($order) ? (int) $order : (int) $order->get_id();
        if (!$order_id) {
            return $total_rows;
        }

        $wc_order     = is_object($order) && method_exists($order, 'get_meta') ? $order : wc_get_order($order_id);
        $is_apm       = $wc_order && $wc_order->get_meta('_apm_is_staged_payment') === 'yes';
        $is_wc_deposit = class_exists('WC_Deposits_Order_Manager') && WC_Deposits_Order_Manager::has_deposit($order_id);

        if (!$is_apm && !$is_wc_deposit) {
            return $total_rows;
        }

        if ($is_apm && $wc_order) {
            $days_before  = (int) ($wc_order->get_meta('_apm_days_before') ?: 7);
            $balance      = (float) $wc_order->get_meta('_apm_balance_amount');
            $aviso        = sprintf(
                /* translators: 1: importe saldo, 2: días antes del check-in */
                __('El saldo restante (%1$s) se cargará automáticamente en su tarjeta %2$d días antes del check-in.', 'alquipress'),
                function_exists('wc_price') ? strip_tags(wc_price($balance)) : number_format_i18n($balance, 2),
                $days_before
            );
        } else {
            $aviso = __('El saldo restante (60%) se cargará automáticamente en su tarjeta 7 días antes del check-in.', 'alquipress');
        }

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
     * Notificar al admin cuando falla el cobro automático del balance (segundo cobro, WC Deposits legacy).
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
        $checkin_label   = self::get_checkin_label_for_order($parent_order_id);

        $admin_email = get_option('admin_email');
        $subject     = '⚠️ ALQUIPRESS: ' . __('Cobro automático FALLIDO', 'alquipress') . ' — #' . $parent_order_id;
        $message     = sprintf(
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
     * Notificar al admin cuando falla un cobro automático del motor propio APM.
     *
     * @param \WC_Order $order   Pedido WooCommerce.
     * @param object    $payment Fila de apm_payment_schedule.
     * @param string    $error   Mensaje de error de la pasarela.
     */
    public static function handle_apm_payment_failed($order, $payment, string $error) {
        if (!$order instanceof WC_Order) {
            return;
        }

        $order_id      = $order->get_id();
        $checkin_label = self::get_checkin_label_for_order($order_id);
        $amount        = isset($payment->amount) ? (float) $payment->amount : 0.0;
        $amount_label  = function_exists('wc_price') ? strip_tags(wc_price($amount)) : number_format_i18n($amount, 2);

        $admin_email = get_option('admin_email');
        $subject     = '⚠️ ALQUIPRESS: ' . __('Cobro automático FALLIDO (APM)', 'alquipress') . ' — #' . $order->get_order_number();
        $message     = sprintf(
            __("El cobro automático del saldo ha fallado (Motor APM).\n\n", 'alquipress') .
            __("Pedido: #%s\n", 'alquipress') .
            __("Cliente: %s\n", 'alquipress') .
            __("Importe: %s\n", 'alquipress') .
            __("Error: %s\n\n", 'alquipress') .
            __("Acción requerida: Revisar el pedido y contactar al cliente.\n\n", 'alquipress') .
            __("Ver pedido: %s", 'alquipress'),
            $order->get_order_number(),
            $order->get_billing_email(),
            $amount_label,
            $error,
            admin_url('post.php?post=' . $order_id . '&action=edit')
        );
        if ($checkin_label) {
            $message = sprintf(__("Check-in: %s\n\n", 'alquipress'), $checkin_label) . $message;
        }
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Obtener etiqueta de fecha de check-in para un pedido.
     * Prioriza el motor propio Ap_Booking; fallback a WC Bookings para pedidos legacy.
     */
    private static function get_checkin_label_for_order($order_id) {
        $order_id = (int) $order_id;
        if (!$order_id) {
            return '';
        }

        // Motor propio: leer ap_checkin de los items del pedido
        $wc_order = wc_get_order($order_id);
        if ($wc_order) {
            foreach ($wc_order->get_items() as $item) {
                if (!method_exists($item, 'get_meta')) {
                    continue;
                }
                $checkin = $item->get_meta('ap_checkin');
                if ($checkin) {
                    $ts = strtotime($checkin);
                    return $ts ? date_i18n(get_option('date_format'), $ts) : $checkin;
                }
            }
        }

        // WC Bookings (legacy)
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

