<?php
/**
 * Widget de reserva frontend para propiedades con motor Ap_Booking.
 *
 * Renderiza el calendario de fechas + selector de huéspedes + breakdown de precio
 * en la ficha del producto (single product) cuando ap_booking_enabled = 1.
 *
 * El widget emite un formulario que añade el producto al carrito con los meta
 * ap_checkin, ap_checkout y ap_guests para que Ap_Booking_Store los procese.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ap_Booking_Widget
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('woocommerce_before_add_to_cart_button', [$this, 'render_widget']);
    }

    public function enqueue_assets(): void
    {
        if (!is_product()) {
            return;
        }

        global $product;
        if (!$product) {
            global $post;
            $product = wc_get_product($post->ID ?? 0);
        }

        if (!$product || !get_post_meta($product->get_id(), 'ap_booking_enabled', true)) {
            return;
        }

        wp_enqueue_style(
            'ap-booking-widget',
            ALQUIPRESS_URL . 'includes/modules/ap-bookings/assets/ap-booking-widget.css',
            [],
            ALQUIPRESS_VERSION
        );

        wp_enqueue_script(
            'ap-booking-widget',
            ALQUIPRESS_URL . 'includes/modules/ap-bookings/assets/ap-booking-widget.js',
            [],
            ALQUIPRESS_VERSION,
            true
        );

        $product_id = $product->get_id();

        wp_localize_script('ap-booking-widget', 'apBookingWidget', [
            'restBase'    => esc_url_raw(rest_url('ap-bookings/v1')),
            'nonce'       => wp_create_nonce('wp_rest'),
            'productId'   => (string) $product_id,
            'currency'    => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€',
            'addToCartUrl'=> esc_url(wc_get_cart_url()),
            'monthNames'  => [
                __('Enero', 'alquipress'), __('Febrero', 'alquipress'), __('Marzo', 'alquipress'),
                __('Abril', 'alquipress'), __('Mayo', 'alquipress'), __('Junio', 'alquipress'),
                __('Julio', 'alquipress'), __('Agosto', 'alquipress'), __('Septiembre', 'alquipress'),
                __('Octubre', 'alquipress'), __('Noviembre', 'alquipress'), __('Diciembre', 'alquipress'),
            ],
            'dayNames'    => ['L', 'M', 'X', 'J', 'V', 'S', 'D'],
            'i18n'        => [
                'selectCheckin'  => __('Selecciona la fecha de entrada.', 'alquipress'),
                'selectCheckout' => __('Ahora selecciona la fecha de salida.', 'alquipress'),
                'notAvailable'   => __('Las fechas seleccionadas no están disponibles.', 'alquipress'),
                'nightsLabel'    => __('noches', 'alquipress'),
                'bookNow'        => __('Reservar', 'alquipress'),
                'selectDates'    => __('Selecciona fechas', 'alquipress'),
            ],
        ]);
    }

    public function render_widget(): void
    {
        global $product;
        if (!$product) {
            return;
        }
        $product_id = $product->get_id();

        if (!get_post_meta($product_id, 'ap_booking_enabled', true)) {
            return;
        }

        // Ocultar el botón de "Añadir al carrito" nativo de WooCommerce mientras usamos el widget.
        add_filter('woocommerce_product_single_add_to_cart_text', fn() => '');
        add_filter('woocommerce_loop_add_to_cart_link', fn() => '');
        ?>
        <div id="ap-booking-widget-root" style="margin-bottom:1.5em"></div>
        <?php
    }
}

new Ap_Booking_Widget();
