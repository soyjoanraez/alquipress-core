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

    public function enqueue_assets()
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        global $product;
        if (!$product || !is_a($product, 'WC_Product_Booking')) {
            return;
        }

        wp_enqueue_style(
            'alquipress-booking-day-prices',
            ALQUIPRESS_URL . 'includes/modules/booking-calendar-prices/assets/booking-day-prices.css',
            [],
            ALQUIPRESS_VERSION
        );

        wp_enqueue_script(
            'alquipress-booking-day-prices',
            ALQUIPRESS_URL . 'includes/modules/booking-calendar-prices/assets/booking-day-prices.js',
            ['jquery', 'wc-bookings-booking-form', 'wp-hooks'],
            ALQUIPRESS_VERSION,
            true
        );

        wp_localize_script(
            'alquipress-booking-day-prices',
            'alquipressBookingDayPrices',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('alquipress_booking_day_prices'),
            ]
        );
    }

    public function ajax_day_prices()
    {
        check_ajax_referer('alquipress_booking_day_prices', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $year = isset($_POST['year']) ? absint($_POST['year']) : 0;
        $month = isset($_POST['month']) ? absint($_POST['month']) : 0;

        // Validar rangos: año razonable (2020-2100), mes (1-12), product_id positivo
        if (!$product_id || $year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
            wp_send_json_error([
                'message' => __('Parámetros inválidos', 'alquipress')
            ]);
            return;
        }

        // Verificar que el producto existe y es accesible (para usuarios autenticados)
        if (is_user_logged_in() && !current_user_can('edit_products')) {
            // Para usuarios autenticados sin permisos, verificar que el producto existe y está publicado
            $product = wc_get_product($product_id);
            if (!$product || $product->get_status() !== 'publish') {
                wp_send_json_error([
                    'message' => __('Producto no encontrado o no disponible', 'alquipress')
                ]);
                return;
            }
        }

        if (!function_exists('get_wc_product_booking')) {
            wp_send_json_success(['prices' => []]);
        }

        $product = get_wc_product_booking($product_id);
        if (!$product || !is_a($product, 'WC_Product_Booking')) {
            wp_send_json_success(['prices' => []]);
        }

        $duration_unit = $product->get_duration_unit();
        if (!in_array($duration_unit, ['day', 'night'], true)) {
            wp_send_json_success(['prices' => []]);
        }

        $cache_key = 'alq_booking_prices_' . $product_id . '_' . $year . '_' . $month;
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            wp_send_json_success(['prices' => $cached]);
        }

        $timezone = wp_timezone();
        try {
            $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), $timezone);
        } catch (Exception $e) {
            wp_send_json_success(['prices' => []]);
        }

        $end = $start->modify('first day of next month');

        $resource_ids = [];
        if ($product->has_resources() && $product->is_resource_assignment_type('customer')) {
            $resource_ids = $product->get_resource_ids();
        }

        if (empty($resource_ids)) {
            $resource_ids = [0];
        }

        $prices = [];
        for ($date = $start; $date < $end; $date = $date->modify('+1 day')) {
            $price = $this->get_day_price($product, $date, $resource_ids);
            if ($price === null) {
                continue;
            }
            $prices[$date->format('Y-m-d')] = wp_strip_all_tags(wc_price($price));
        }

        set_transient($cache_key, $prices, HOUR_IN_SECONDS);

        wp_send_json_success(['prices' => $prices]);
    }

    private function get_day_price($product, DateTimeImmutable $date, array $resource_ids)
    {
        if ($product->is_duration_type('customer')) {
            $duration = max(1, (int) $product->get_min_duration());
            $divide_by = $duration;
        } else {
            $duration = max(1, (int) $product->get_duration());
            $divide_by = $duration;
        }

        $min_cost = null;
        foreach ($resource_ids as $resource_id) {
            $data = [
                '_start_date' => $date->getTimestamp(),
                '_date' => $date->format('Y-m-d'),
                'date' => $date->format('Y-m-d'),
                '_time' => '',
                'time' => '',
                '_duration' => $duration,
                '_resource_id' => (int) $resource_id,
            ];

            $cost = WC_Bookings_Cost_Calculation::calculate_booking_cost($data, $product);
            if (is_wp_error($cost)) {
                continue;
            }
            $cost = (float) $cost;
            if ($divide_by > 1) {
                $cost = $cost / $divide_by;
            }
            if ($min_cost === null || $cost < $min_cost) {
                $min_cost = $cost;
            }
        }

        return $min_cost;
    }
}

new Alquipress_Booking_Calendar_Prices();
