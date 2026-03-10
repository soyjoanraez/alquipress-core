<?php
/**
 * Formulario de reserva – integrado con el motor propio (Ap_Booking) o,
 * en su defecto, como wrapper del widget original de WC Bookings.
 */
if (!defined('ABSPATH')) {
    exit;
}
if (!is_singular('product')) {
    echo '<p class="ap-child-booking-widget-placeholder">' . esc_html__('Este bloque se muestra en la ficha de una propiedad (producto reservable).', 'alquipress-child') . '</p>';
    return;
}
global $post, $product;
$product_id = $product && is_a($product, 'WC_Product') ? $product->get_id() : (int) get_the_ID();

$use_ap_engine = (bool) get_post_meta($product_id, 'ap_booking_enabled', true) && class_exists('Ap_Booking_Pricing_Service') && class_exists('Ap_Booking_Availability_Service');

if ($use_ap_engine) {
    $checkin = isset($_GET['checkin']) ? sanitize_text_field(wp_unslash($_GET['checkin'])) : '';
    $checkout = isset($_GET['checkout']) ? sanitize_text_field(wp_unslash($_GET['checkout'])) : '';
    $guests = isset($_GET['huespedes']) ? absint($_GET['huespedes']) : 1;
    $guests = $guests > 0 ? $guests : 1;

    $price_breakdown = null;
    if ($checkin && $checkout) {
        $price_breakdown = Ap_Booking_Pricing_Service::calculate_price($product_id, $checkin, $checkout, $guests);
    }

    $base_price = get_post_meta($product_id, 'ap_base_price', true);
    if ($base_price === '' && function_exists('wc_get_product')) {
        $p = wc_get_product($product_id);
        if ($p) {
            $base_price = $p->get_price();
        }
    }
    ?>
    <div class="ap-child-booking-widget-wrap ap-child-booking-widget-wrap--ap-engine">
        <div class="ap-child-booking-widget-header">
            <div class="ap-child-booking-widget-price">
                <?php
                if (function_exists('wc_price') && $base_price !== '') {
                    echo wp_kses_post(wc_price((float) $base_price));
                    echo ' <span class="ap-child-booking-widget-price-label">' . esc_html__('/noche', 'alquipress-child') . '</span>';
                } else {
                    echo esc_html__('Precio/noche no definido', 'alquipress-child');
                }
                ?>
            </div>
        </div>
        <form class="ap-child-booking-widget-form" method="post" action="<?php echo esc_url(wc_get_cart_url()); ?>">
            <?php if (function_exists('wp_nonce_field')) : ?>
                <?php wp_nonce_field('woocommerce-cart'); ?>
            <?php endif; ?>
            <input type="hidden" name="add-to-cart" value="<?php echo (int) $product_id; ?>" />

            <div class="ap-child-booking-widget-field">
                <label for="ap-checkin">
                    <?php esc_html_e('📅 Check-in', 'alquipress-child'); ?>
                </label>
                <input
                    type="date"
                    id="ap-checkin"
                    name="ap_checkin"
                    class="ap-child-booking-widget-input"
                    value="<?php echo esc_attr($checkin); ?>"
                    min="<?php echo esc_attr(date('Y-m-d')); ?>"
                    required
                />
            </div>

            <div class="ap-child-booking-widget-field">
                <label for="ap-checkout">
                    <?php esc_html_e('📅 Check-out', 'alquipress-child'); ?>
                </label>
                <input
                    type="date"
                    id="ap-checkout"
                    name="ap_checkout"
                    class="ap-child-booking-widget-input"
                    value="<?php echo esc_attr($checkout); ?>"
                    min="<?php echo esc_attr(date('Y-m-d', strtotime('+1 day'))); ?>"
                    required
                />
            </div>

            <div class="ap-child-booking-widget-field">
                <label for="ap-guests">
                    <?php esc_html_e('👥 Huéspedes', 'alquipress-child'); ?>
                </label>
                <select id="ap-guests" name="ap_guests" class="ap-child-booking-widget-select" required>
                    <?php for ($i = 1; $i <= 20; $i++) : ?>
                        <option value="<?php echo esc_attr($i); ?>" <?php selected($guests, $i); ?>>
                            <?php
                            if ($i === 1) {
                                esc_html_e('1 huésped', 'alquipress-child');
                            } else {
                                printf(esc_html__('%d huéspedes', 'alquipress-child'), $i);
                            }
                            ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="ap-child-booking-widget-summary">
                <?php if ($price_breakdown && $price_breakdown['nights'] > 0) : ?>
                    <div class="ap-child-booking-widget-summary-row">
                        <span><?php echo esc_html(sprintf(_n('%d noche', '%d noches', $price_breakdown['nights'], 'alquipress-child'), $price_breakdown['nights'])); ?></span>
                        <span>
                            <?php
                            $subtotal = (float) $price_breakdown['subtotal'];
                            echo function_exists('wc_price') ? wp_kses_post(wc_price($subtotal)) : esc_html(number_format_i18n($subtotal, 2));
                            ?>
                        </span>
                    </div>
                    <?php if ($price_breakdown['cleaning_fee'] > 0 || $price_breakdown['laundry_fee'] > 0) : ?>
                        <div class="ap-child-booking-widget-summary-row">
                            <span><?php esc_html_e('Tasas limpieza/lavandería', 'alquipress-child'); ?></span>
                            <span>
                                <?php
                                $extras = (float) $price_breakdown['cleaning_fee'] + (float) $price_breakdown['laundry_fee'];
                                echo function_exists('wc_price') ? wp_kses_post(wc_price($extras)) : esc_html(number_format_i18n($extras, 2));
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="ap-child-booking-widget-summary-row ap-child-booking-widget-summary-total">
                        <span><?php esc_html_e('Total estimado', 'alquipress-child'); ?></span>
                        <span>
                            <?php
                            $total = (float) $price_breakdown['total'];
                            echo function_exists('wc_price') ? wp_kses_post(wc_price($total)) : esc_html(number_format_i18n($total, 2));
                            ?>
                        </span>
                    </div>
                <?php else : ?>
                    <p class="ap-child-booking-widget-placeholder">
                        <?php esc_html_e('Selecciona fechas para ver el precio total.', 'alquipress-child'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <button type="submit" class="ap-child-booking-widget-submit button button-primary">
                <?php esc_html_e('Reservar ahora', 'alquipress-child'); ?>
            </button>
        </form>
    </div>
    <?php
    return;
}

// Fallback: usar widget original de WC Bookings cuando siga activo.
if (!$product || !$product->is_type('booking')) {
    echo '<p class="ap-child-booking-widget-placeholder">' . esc_html__('El producto actual no es reservable.', 'alquipress-child') . '</p>';
    return;
}
$attributes = isset($attributes) ? $attributes : [];
$attributes['sticky'] = isset($attributes['sticky']) ? $attributes['sticky'] : true;
$attributes['showCleaningFee'] = isset($attributes['showCleaningFee']) ? $attributes['showCleaningFee'] : true;
$attributes['showLaundryFee'] = isset($attributes['showLaundryFee']) ? $attributes['showLaundryFee'] : true;
?>
<div class="ap-child-booking-widget-wrap">
    <?php include get_template_directory() . '/blocks/booking-widget/render.php'; ?>
</div>
