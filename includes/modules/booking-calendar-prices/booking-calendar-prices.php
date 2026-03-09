<?php
/**
 * Módulo: Precios por día en calendario de reservas
 * Muestra el coste por día debajo de cada fecha del datepicker de WooCommerce Bookings.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Booking_Calendar_Prices
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_alquipress_booking_day_prices', [$this, 'ajax_day_prices']);
        add_action('wp_ajax_nopriv_alquipress_booking_day_prices', [$this, 'ajax_day_prices']);
    }

    /**
     * Los assets del calendario de precios están integrados en el widget Ap_Booking_Widget.
     * Este módulo solo mantiene el endpoint AJAX para compatibilidad con el widget.
     */
    public function enqueue_assets()
    {
        // El widget Ap_Booking_Widget gestiona sus propios assets; nada que encolar aquí.
    }

    public function ajax_day_prices()
    {
        check_ajax_referer('alquipress_booking_day_prices', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $year       = isset($_POST['year'])       ? absint($_POST['year'])       : 0;
        $month      = isset($_POST['month'])      ? absint($_POST['month'])      : 0;

        if (!$product_id || $year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
            wp_send_json_error(['message' => __('Parámetros inválidos', 'alquipress')]);
            return;
        }

        if (!class_exists('Ap_Booking_Pricing_Service')) {
            wp_send_json_success(['prices' => []]);
            return;
        }

        $cache_key     = 'alq_ap_prices_' . $product_id . '_' . $year . '_' . $month;
        $cached        = get_transient($cache_key);
        if (is_array($cached)) {
            wp_send_json_success(['prices' => $cached]);
            return;
        }

        $days_in_month = (int) gmdate('t', mktime(0, 0, 0, $month, 1, $year));
        $prices        = [];
        $cur_ts        = mktime(0, 0, 0, $month, 1, $year);
        $end_ts        = mktime(0, 0, 0, $month, $days_in_month + 1, $year);
        while ($cur_ts < $end_ts) {
            $date  = gmdate('Y-m-d', $cur_ts);
            $price = Ap_Booking_Pricing_Service::get_day_price($product_id, $cur_ts, 2);
            if (is_array($price) && isset($price['price']) && (float) $price['price'] > 0 && function_exists('wc_price')) {
                $prices[$date] = wp_strip_all_tags(wc_price((float) $price['price']));
            }
            $cur_ts += DAY_IN_SECONDS;
        }

        set_transient($cache_key, $prices, HOUR_IN_SECONDS);
        wp_send_json_success(['prices' => $prices]);
    }
}

new Alquipress_Booking_Calendar_Prices();
