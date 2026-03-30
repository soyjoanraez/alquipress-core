<?php
/**
 * Flujos de email automáticos: T-7, T-2, reseña, recuperador, cumpleaños, alerta interna.
 *
 * @package Alquipress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Email_Flows {

    const CRON_DAILY = 'alquipress_email_flows_daily';
    const CRON_MONTHLY = 'alquipress_email_flows_monthly';

    public static function init() {
        add_action('init', [__CLASS__, 'schedule_crons']);
        add_action(self::CRON_DAILY, [__CLASS__, 'run_daily_flows']);
        add_action(self::CRON_MONTHLY, [__CLASS__, 'run_monthly_recovery']);
        add_action('woocommerce_order_status_checkout-review', [__CLASS__, 'schedule_review_request'], 10, 1);
        add_action('alquipress_send_review_request', [__CLASS__, 'send_review_request'], 10, 1);
    }

    public static function schedule_crons() {
        if (wp_next_scheduled(self::CRON_DAILY)) {
            return;
        }
        wp_schedule_event(strtotime('today 09:00:00'), 'daily', self::CRON_DAILY);
        if (!wp_next_scheduled(self::CRON_MONTHLY)) {
            wp_schedule_event(strtotime('first day of next month 02:00:00'), 'monthly', self::CRON_MONTHLY);
        }
    }

    public static function run_daily_flows() {
        self::send_arrival_guide_t7();
        self::send_reminder_t2();
        self::send_birthday_emails();
        self::alert_team_pending_checkins();
    }

    /**
     * Guía de llegada: reservas con check-in en 7 días.
     */
    private static function send_arrival_guide_t7() {
        $target_ts = strtotime('+7 days');
        $bookings  = self::get_bookings_checkin_on_date($target_ts);
        foreach ($bookings as $booking) {
            $meta_key = '_alquipress_arrival_guide_sent_' . $booking->id;
            if (get_post_meta($booking->order_id, $meta_key, true)) {
                continue;
            }
            $order = $booking->order_id ? wc_get_order($booking->order_id) : null;
            if (!$order) {
                continue;
            }
            $subject = sprintf(
                __('Tu guía de llegada — %s te espera en 7 días', 'alquipress'),
                get_the_title($booking->product_id)
            );
            $body = sprintf(
                __("Hola %s,\n\nTu check-in es el %s.\n\nRecibirás el código de acceso y las instrucciones 2 días antes.\n\nSi tienes dudas, responde a este email.\n\nSaludos,", 'alquipress'),
                $order->get_billing_first_name(),
                date_i18n('j \d\e F', strtotime($booking->checkin))
            );
            if (function_exists('alquipress_send_custom_email')) {
                alquipress_send_custom_email($order->get_billing_email(), $subject, nl2br(esc_html($body)));
            } else {
                wp_mail($order->get_billing_email(), $subject, $body);
            }
            update_post_meta($booking->order_id, $meta_key, '1');
        }
    }

    /**
     * Recordatorio T-2: código de llaves (reservas con check-in en 2 días).
     */
    private static function send_reminder_t2() {
        $target_ts = strtotime('+2 days');
        $bookings  = self::get_bookings_checkin_on_date($target_ts);
        foreach ($bookings as $booking) {
            $meta_key = '_alquipress_reminder_t2_sent_' . $booking->id;
            if (get_post_meta($booking->order_id, $meta_key, true)) {
                continue;
            }
            $order = $booking->order_id ? wc_get_order($booking->order_id) : null;
            if (!$order) {
                continue;
            }
            $code = get_field('codigo_caja_llaves', $booking->product_id)
                ?: __('[Ver instrucciones en el email de llegada]', 'alquipress');
            $subject = sprintf(
                __('Tu código de acceso — Llegada el %s', 'alquipress'),
                date_i18n('j \d\e F', strtotime($booking->checkin))
            );
            $body = sprintf(
                __("Hola %s,\n\nRecuerda: llegas el %s.\n\nCódigo de la caja de llaves: %s\n\nSaludos,", 'alquipress'),
                $order->get_billing_first_name(),
                date_i18n('j \d\e F', strtotime($booking->checkin)),
                $code
            );
            if (function_exists('alquipress_send_custom_email')) {
                alquipress_send_custom_email($order->get_billing_email(), $subject, nl2br(esc_html($body)));
            } else {
                wp_mail($order->get_billing_email(), $subject, $body);
            }
            update_post_meta($booking->order_id, $meta_key, '1');
        }
    }

    /**
     * Devuelve Ap_Booking[] cuyo checkin coincide con la fecha del timestamp dado.
     */
    private static function get_bookings_checkin_on_date(int $target_ts): array
    {
        if (!class_exists('Ap_Booking_Store')) {
            return [];
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';
        $date  = gmdate('Y-m-d', $target_ts);
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE checkin = %s AND status IN ('held','confirmed') ORDER BY id",
                $date
            ),
            ARRAY_A
        );
        $out = [];
        foreach ((array) $rows as $row) {
            $out[] = Ap_Booking::from_row($row);
        }
        return $out;
    }

    public static function schedule_review_request($order_id) {
        if (get_post_meta($order_id, '_alquipress_review_request_scheduled', true)) {
            return;
        }
        if (function_exists('wp_schedule_single_event')) {
            wp_schedule_single_event(time() + DAY_IN_SECONDS, 'alquipress_send_review_request', [$order_id]);
            update_post_meta($order_id, '_alquipress_review_request_scheduled', '1');
        }
    }

    public static function send_review_request($order_id) {
        if (get_post_meta($order_id, '_alquipress_review_request_sent', true)) {
            return;
        }
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
        $subject = sprintf(__('¿Cómo fue tu estancia en %s?', 'alquipress'), $product_name ?: __('tu alojamiento', 'alquipress'));
        $body = sprintf(
            __("Hola %s,\n\nEsperamos que hayas disfrutado de tu estancia.\n\nSi tienes un momento, tu opinión nos ayuda mucho.\n\n¡Gracias!", 'alquipress'),
            $order->get_billing_first_name()
        );
        if (function_exists('alquipress_send_custom_email')) {
            alquipress_send_custom_email($order->get_billing_email(), $subject, nl2br(esc_html($body)));
        } else {
            wp_mail($order->get_billing_email(), $subject, $body);
        }
        update_post_meta($order_id, '_alquipress_review_request_sent', '1');
    }

    /**
     * Recuperador: pedidos completados hace ~11 meses.
     */
    public static function run_monthly_recovery() {
        $date_min = date('Y-m-d', strtotime('-11 months -15 days'));
        $date_max = date('Y-m-d', strtotime('-10 months +15 days'));
        $orders = wc_get_orders([
            'limit' => 50,
            'status' => ['wc-completed'],
            'date_after' => $date_min . ' 00:00:00',
            'date_before' => $date_max . ' 23:59:59',
            'return' => 'ids',
        ]);
        foreach ($orders as $order_id) {
            if (get_post_meta($order_id, '_alquipress_recovery_sent', true)) {
                continue;
            }
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }
            $customer_id = $order->get_customer_id();
            if ($customer_id) {
                $status = get_user_meta($customer_id, 'guest_status', true);
                if ($status === 'blacklist') {
                    continue;
                }
            }
            $product_name = '';
            foreach ($order->get_items() as $item) {
                if (is_object($item) && method_exists($item, 'get_name')) {
                    $product_name = $item->get_name();
                    break;
                }
            }
            $subject = sprintf(__('Hace un año estuviste con nosotros... ¿vuelves?', 'alquipress'));
            $body = sprintf(
                __("Hola %s,\n\nEl año pasado disfrutaste de %s.\n\n¿Te gustaría repetir? Estamos a tu disposición.\n\nSaludos,", 'alquipress'),
                $order->get_billing_first_name(),
                $product_name ?: __('uno de nuestros alojamientos', 'alquipress')
            );
            if (function_exists('alquipress_send_custom_email')) {
                alquipress_send_custom_email($order->get_billing_email(), $subject, nl2br(esc_html($body)));
            } else {
                wp_mail($order->get_billing_email(), $subject, $body);
            }
            update_post_meta($order_id, '_alquipress_recovery_sent', '1');
        }
    }

    /**
     * Cumpleaños: usuarios con guest_dob = hoy.
     */
    private static function send_birthday_emails() {
        $today_md = date('m-d');
        $users = get_users(['meta_key' => 'guest_dob', 'number' => 200]);
        foreach ($users as $user) {
            $dob = get_user_meta($user->ID, 'guest_dob', true);
            if (!$dob || date('m-d', strtotime($dob)) !== $today_md) {
                continue;
            }
            $sent = get_user_meta($user->ID, '_alquipress_birthday_sent_year', true);
            if ((int) $sent === (int) date('Y')) {
                continue;
            }
            $subject = sprintf(__('¡Feliz cumpleaños, %s!', 'alquipress'), $user->first_name ?: $user->display_name);
            $body = sprintf(__('¡Te deseamos un feliz día!', 'alquipress'));
            if (function_exists('alquipress_send_custom_email')) {
                alquipress_send_custom_email($user->user_email, $subject, nl2br(esc_html($body)));
            } else {
                wp_mail($user->user_email, $subject, $body);
            }
            update_user_meta($user->ID, '_alquipress_birthday_sent_year', date('Y'));
        }
    }

    /**
     * Alerta interna: check-ins mañana con checklist incompleta.
     */
    private static function alert_team_pending_checkins() {
        $tomorrow_start = strtotime('tomorrow 00:00:00');
        $tomorrow_end   = strtotime('tomorrow 23:59:59');
        $bookings = self::get_bookings_between($tomorrow_start, $tomorrow_end);
        $pending  = [];
        foreach ($bookings as $booking) {
            $order_id = $booking->order_id;
            if (!$order_id) {
                continue;
            }
            $ses      = get_post_meta($order_id, '_alq_ses_status', true);
            $cleaning = get_post_meta($order_id, '_alquipress_cleaning_scheduled', true);
            $keys     = get_post_meta($order_id, '_alquipress_keys_delivered', true);
            if (in_array($ses, ['sent', 'accepted', 'xml_generated'], true) && $cleaning && $keys) {
                continue;
            }
            $pending[] = [
                'property' => get_the_title($booking->product_id),
                'order_id' => $order_id,
            ];
        }
        if (empty($pending)) {
            return;
        }
        $lines = [__('Check-ins mañana con tareas pendientes:', 'alquipress'), ''];
        foreach ($pending as $p) {
            $lines[] = $p['property'] . ' (#' . $p['order_id'] . ')';
        }
        $lines[] = '';
        $lines[] = admin_url('admin.php?page=alquipress-pipeline');
        wp_mail(
            get_option('admin_email'),
            '⚠️ ALQUIPRESS — ' . count($pending) . ' ' . __('check-ins mañana con tareas pendientes', 'alquipress'),
            implode("\n", $lines)
        );
    }

    /**
     * Devuelve Ap_Booking[] cuyo checkin cae dentro del rango de timestamps dado.
     */
    private static function get_bookings_between(int $start_ts, int $end_ts): array
    {
        if (!class_exists('Ap_Booking_Store')) {
            return [];
        }
        global $wpdb;
        $table      = $wpdb->prefix . 'ap_booking';
        $date_from  = gmdate('Y-m-d', $start_ts);
        $date_to    = gmdate('Y-m-d', $end_ts);
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE checkin BETWEEN %s AND %s AND status IN ('held','confirmed') ORDER BY checkin",
                $date_from,
                $date_to
            ),
            ARRAY_A
        );
        $out = [];
        foreach ((array) $rows as $row) {
            $out[] = Ap_Booking::from_row($row);
        }
        return $out;
    }
}
