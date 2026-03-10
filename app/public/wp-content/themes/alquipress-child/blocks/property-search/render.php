<?php
/**
 * Bloque Buscador de propiedades – render frontend
 */
if (!defined('ABSPATH')) {
    exit;
}
$title = isset($attributes['title']) ? $attributes['title'] : __('Encuentra tu alojamiento', 'alquipress-child');
$subtitle = isset($attributes['subtitle']) ? $attributes['subtitle'] : '';
$results_page = isset($attributes['resultsPage']) && $attributes['resultsPage'] !== ''
    ? esc_url($attributes['resultsPage'])
    : get_post_type_archive_link('product');
if (empty($results_page) && function_exists('wc_get_page_id')) {
    $shop_id = wc_get_page_id('shop');
    $results_page = $shop_id > 0 ? get_permalink($shop_id) : home_url('/');
}
if (empty($results_page)) {
    $results_page = home_url('/');
}
$block_id = 'ap-child-search-' . uniqid();
$current_checkin = isset($_GET['checkin']) ? sanitize_text_field(wp_unslash($_GET['checkin'])) : '';
$current_checkout = isset($_GET['checkout']) ? sanitize_text_field(wp_unslash($_GET['checkout'])) : '';
$current_guests = isset($_GET['huespedes']) ? absint($_GET['huespedes']) : 1;
$current_poblacion = isset($_GET['poblacion']) ? sanitize_text_field(wp_unslash($_GET['poblacion'])) : '';
?>
<div class="ap-child-property-search" id="<?php echo esc_attr($block_id); ?>">
    <div class="ap-child-property-search-inner">
        <?php if ($title) : ?>
            <h2 class="ap-child-property-search-title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>
        <?php if ($subtitle) : ?>
            <p class="ap-child-property-search-subtitle"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>
        <form class="ap-child-property-search-form" method="get" action="<?php echo esc_url($results_page); ?>">
            <div class="ap-child-property-search-fields">
                <div class="ap-child-property-search-field">
                    <label for="<?php echo esc_attr($block_id); ?>-location" class="screen-reader-text"><?php esc_html_e('Ubicación', 'alquipress-child'); ?></label>
                    <input type="text" id="<?php echo esc_attr($block_id); ?>-location" name="poblacion" class="ap-child-property-search-input" placeholder="<?php esc_attr_e('¿Dónde?', 'alquipress-child'); ?>" value="<?php echo esc_attr($current_poblacion); ?>">
                </div>
                <div class="ap-child-property-search-field">
                    <label for="<?php echo esc_attr($block_id); ?>-checkin" class="screen-reader-text"><?php esc_html_e('Entrada', 'alquipress-child'); ?></label>
                    <input type="date" id="<?php echo esc_attr($block_id); ?>-checkin" name="checkin" class="ap-child-property-search-input" min="<?php echo esc_attr(date('Y-m-d')); ?>" value="<?php echo esc_attr($current_checkin); ?>">
                    <span class="ap-child-property-search-label"><?php esc_html_e('Entrada', 'alquipress-child'); ?></span>
                </div>
                <div class="ap-child-property-search-field">
                    <label for="<?php echo esc_attr($block_id); ?>-checkout" class="screen-reader-text"><?php esc_html_e('Salida', 'alquipress-child'); ?></label>
                    <input type="date" id="<?php echo esc_attr($block_id); ?>-checkout" name="checkout" class="ap-child-property-search-input" min="<?php echo esc_attr(date('Y-m-d', strtotime('tomorrow'))); ?>" value="<?php echo esc_attr($current_checkout); ?>">
                    <span class="ap-child-property-search-label"><?php esc_html_e('Salida', 'alquipress-child'); ?></span>
                </div>
                <div class="ap-child-property-search-field">
                    <label for="<?php echo esc_attr($block_id); ?>-guests" class="screen-reader-text"><?php esc_html_e('Huéspedes', 'alquipress-child'); ?></label>
                    <select id="<?php echo esc_attr($block_id); ?>-guests" name="huespedes" class="ap-child-property-search-select">
                        <?php for ($i = 1; $i <= 20; $i++) : ?>
                            <option value="<?php echo esc_attr($i); ?>" <?php selected($current_guests, $i); ?>><?php echo $i === 1 ? esc_html__('1 huésped', 'alquipress-child') : sprintf(esc_html__('%d huéspedes', 'alquipress-child'), $i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="ap-child-property-search-submit"><?php esc_html_e('Buscar', 'alquipress-child'); ?></button>
            </div>
        </form>
    </div>
</div>
