<?php
/**
 * Template para Widget de Reserva Flotante
 */

if (!defined('ABSPATH')) {
    exit;
}

// Solo mostrar en single product
if (!is_singular('product')) {
    return;
}

global $product;
$product_id = $product->get_id();

if (!$product || !$product->is_type('booking')) {
    return;
}

$sticky = isset($attributes['sticky']) ? $attributes['sticky'] : true;
$show_cleaning_fee = isset($attributes['showCleaningFee']) ? $attributes['showCleaningFee'] : true;
$show_laundry_fee = isset($attributes['showLaundryFee']) ? $attributes['showLaundryFee'] : true;

// Obtener precio base
$base_price = floatval($product->get_price());
$cleaning_fee = floatval(get_post_meta($product_id, '_cleaning_fee', true)) ?: 0;
$laundry_fee = floatval(get_post_meta($product_id, '_laundry_fee', true)) ?: 0;
$deposit_percentage = floatval(get_post_meta($product_id, '_deposit_percentage', true)) ?: 40;

// Obtener fechas de URL si existen
$checkin = isset($_GET['checkin']) ? sanitize_text_field($_GET['checkin']) : '';
$checkout = isset($_GET['checkout']) ? sanitize_text_field($_GET['checkout']) : '';
$guests = isset($_GET['huespedes']) ? absint($_GET['huespedes']) : 1;

$block_id = 'alq-booking-widget-' . uniqid();
$widget_class = 'alq-booking-widget';
if ($sticky) {
    $widget_class .= ' alq-booking-widget--sticky';
}
?>

<div class="<?php echo esc_attr($widget_class); ?>" id="<?php echo esc_attr($block_id); ?>" data-product-id="<?php echo esc_attr($product_id); ?>">
    <div class="alq-booking-widget-header">
        <div class="alq-booking-widget-price">
            <?php echo wp_kses_post($product->get_price_html()); ?>
            <span class="alq-booking-widget-price-label"><?php esc_html_e('/noche', 'alquipress-theme'); ?></span>
        </div>
        <?php
        $rating = $product->get_average_rating();
        if ($rating > 0):
        ?>
            <div class="alq-booking-widget-rating">
                <?php
                echo str_repeat('⭐', round($rating));
                echo ' <span>(' . esc_html($product->get_rating_count()) . ')</span>';
                ?>
            </div>
        <?php endif; ?>
    </div>
    
    <form class="alq-booking-widget-form" method="post" action="<?php echo esc_url(wc_get_cart_url()); ?>">
        <?php wp_nonce_field('woocommerce-add_to_cart'); ?>
        <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product_id); ?>">
        
        <div class="alq-booking-widget-field">
            <label for="<?php echo esc_attr($block_id); ?>-checkin">
                <?php esc_html_e('📅 Check-in', 'alquipress-theme'); ?>
            </label>
            <input 
                type="date" 
                id="<?php echo esc_attr($block_id); ?>-checkin"
                name="checkin" 
                class="alq-booking-widget-input"
                value="<?php echo esc_attr($checkin); ?>"
                min="<?php echo esc_attr(date('Y-m-d', strtotime('today'))); ?>"
                required
            >
            <span class="alq-booking-widget-time">15:00</span>
        </div>
        
        <div class="alq-booking-widget-field">
            <label for="<?php echo esc_attr($block_id); ?>-checkout">
                <?php esc_html_e('📅 Check-out', 'alquipress-theme'); ?>
            </label>
            <input 
                type="date" 
                id="<?php echo esc_attr($block_id); ?>-checkout"
                name="checkout" 
                class="alq-booking-widget-input"
                value="<?php echo esc_attr($checkout); ?>"
                min="<?php echo esc_attr(date('Y-m-d', strtotime('tomorrow'))); ?>"
                required
            >
            <span class="alq-booking-widget-time">11:00</span>
        </div>
        
        <div class="alq-booking-widget-field">
            <label for="<?php echo esc_attr($block_id); ?>-guests">
                <?php esc_html_e('👥 Huéspedes', 'alquipress-theme'); ?>
            </label>
            <select 
                id="<?php echo esc_attr($block_id); ?>-guests"
                name="guests" 
                class="alq-booking-widget-select"
                required
            >
                <?php for ($i = 1; $i <= 20; $i++): ?>
                    <option value="<?php echo esc_attr($i); ?>" <?php selected($guests, $i); ?>>
                        <?php 
                        if ($i === 1) {
                            esc_html_e('1 huésped', 'alquipress-theme');
                        } else {
                            printf(esc_html__('%d huéspedes', 'alquipress-theme'), $i);
                        }
                        ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="alq-booking-widget-summary" id="<?php echo esc_attr($block_id); ?>-summary">
            <div class="alq-booking-widget-summary-row">
                <span><?php esc_html_e('Subtotal (0 noches):', 'alquipress-theme'); ?></span>
                <span class="alq-booking-widget-summary-subtotal">0€</span>
            </div>
            <?php if ($show_cleaning_fee && $cleaning_fee > 0): ?>
                <div class="alq-booking-widget-summary-row">
                    <span><?php esc_html_e('Limpieza:', 'alquipress-theme'); ?></span>
                    <span><?php echo esc_html(wc_price($cleaning_fee)); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($show_laundry_fee && $laundry_fee > 0): ?>
                <div class="alq-booking-widget-summary-row">
                    <span><?php esc_html_e('Lavandería:', 'alquipress-theme'); ?></span>
                    <span><?php echo esc_html(wc_price($laundry_fee)); ?></span>
                </div>
            <?php endif; ?>
            <div class="alq-booking-widget-summary-divider"></div>
            <div class="alq-booking-widget-summary-row alq-booking-widget-summary-total">
                <span><?php esc_html_e('Total:', 'alquipress-theme'); ?></span>
                <span class="alq-booking-widget-summary-total-amount">0€</span>
            </div>
            <div class="alq-booking-widget-summary-row alq-booking-widget-summary-deposit">
                <span><?php printf(esc_html__('💰 Depósito (%d%%):', 'alquipress-theme'), $deposit_percentage); ?></span>
                <span class="alq-booking-widget-summary-deposit-amount">0€</span>
            </div>
            <div class="alq-booking-widget-summary-row alq-booking-widget-summary-balance">
                <span><?php esc_html_e('📅 Resto (7 días antes)', 'alquipress-theme'); ?></span>
                <span class="alq-booking-widget-summary-balance-amount">0€</span>
            </div>
        </div>
        
        <button type="submit" class="alq-booking-widget-submit button button-primary">
            <?php esc_html_e('🔒 Reservar Ahora', 'alquipress-theme'); ?>
        </button>
        
        <div class="alq-booking-widget-features">
            <span>✓ <?php esc_html_e('Cancelación gratuita', 'alquipress-theme'); ?></span>
            <span>✓ <?php esc_html_e('Confirmación instantánea', 'alquipress-theme'); ?></span>
        </div>
    </form>
</div>
