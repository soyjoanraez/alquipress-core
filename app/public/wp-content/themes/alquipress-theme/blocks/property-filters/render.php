<?php
/**
 * Template para renderizar el bloque Filtros de Propiedades
 */

if (!defined('ABSPATH')) {
    exit;
}

$show_location = isset($attributes['showLocation']) ? $attributes['showLocation'] : true;
$show_price = isset($attributes['showPrice']) ? $attributes['showPrice'] : true;
$show_rooms = isset($attributes['showRooms']) ? $attributes['showRooms'] : true;
$show_characteristics = isset($attributes['showCharacteristics']) ? $attributes['showCharacteristics'] : true;
$use_ajax = isset($attributes['useAjax']) ? $attributes['useAjax'] : true;

// Obtener valores actuales de URL
$current_poblacion = isset($_GET['poblacion']) ? sanitize_text_field($_GET['poblacion']) : '';
$current_precio_min = isset($_GET['precio_min']) ? absint($_GET['precio_min']) : 0;
$current_precio_max = isset($_GET['precio_max']) ? absint($_GET['precio_max']) : 0;
$current_habitaciones = isset($_GET['habitaciones_min']) ? absint($_GET['habitaciones_min']) : 0;
$current_banos = isset($_GET['banos_min']) ? absint($_GET['banos_min']) : 0;
$current_caracteristicas = isset($_GET['caracteristicas']) ? array_map('absint', (array) $_GET['caracteristicas']) : [];

// Obtener datos para filtros
$poblaciones = get_terms(['taxonomy' => 'poblacion', 'hide_empty' => true]);
$zonas = get_terms(['taxonomy' => 'zona', 'hide_empty' => true]);
$characteristics = alquipress_get_characteristics();

// Obtener rango de precios
global $wpdb;
$price_range = $wpdb->get_row("
    SELECT MIN(CAST(meta_value AS UNSIGNED)) as min_price, 
           MAX(CAST(meta_value AS UNSIGNED)) as max_price 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = '_price' 
    AND meta_value != ''
");

$min_price = $price_range->min_price ?? 0;
$max_price = $price_range->max_price ?? 1000;

$block_id = 'alq-property-filters-' . uniqid();
?>

<div class="alq-property-filters" id="<?php echo esc_attr($block_id); ?>" data-ajax="<?php echo $use_ajax ? 'true' : 'false'; ?>">
    <form class="alq-property-filters-form" method="get" action="<?php echo esc_url(get_post_type_archive_link('product')); ?>">
        <?php if ($show_location && !empty($poblaciones)): ?>
            <div class="alq-property-filters-group">
                <h4 class="alq-property-filters-title"><?php esc_html_e('📍 Ubicación', 'alquipress-theme'); ?></h4>
                <div class="alq-property-filters-checkboxes">
                    <?php foreach ($poblaciones as $term): ?>
                        <label class="alq-property-filters-checkbox">
                            <input 
                                type="checkbox" 
                                name="poblacion[]" 
                                value="<?php echo esc_attr($term->slug); ?>"
                                <?php checked(in_array($term->slug, (array) $current_poblacion)); ?>
                            >
                            <span><?php echo esc_html($term->name); ?> <span class="alq-property-filters-count">(<?php echo $term->count; ?>)</span></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($show_price): ?>
            <div class="alq-property-filters-group">
                <h4 class="alq-property-filters-title"><?php esc_html_e('💰 Precio/noche', 'alquipress-theme'); ?></h4>
                <div class="alq-property-filters-range">
                    <input 
                        type="range" 
                        name="precio_min" 
                        min="<?php echo esc_attr($min_price); ?>" 
                        max="<?php echo esc_attr($max_price); ?>" 
                        value="<?php echo esc_attr($current_precio_min ?: $min_price); ?>"
                        class="alq-property-filters-range-input"
                        data-label="min"
                    >
                    <input 
                        type="range" 
                        name="precio_max" 
                        min="<?php echo esc_attr($min_price); ?>" 
                        max="<?php echo esc_attr($max_price); ?>" 
                        value="<?php echo esc_attr($current_precio_max ?: $max_price); ?>"
                        class="alq-property-filters-range-input"
                        data-label="max"
                    >
                    <div class="alq-property-filters-range-values">
                        <span class="alq-property-filters-range-min"><?php echo esc_html($current_precio_min ?: $min_price); ?>€</span>
                        <span> - </span>
                        <span class="alq-property-filters-range-max"><?php echo esc_html($current_precio_max ?: $max_price); ?>€</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($show_rooms): ?>
            <div class="alq-property-filters-group">
                <h4 class="alq-property-filters-title"><?php esc_html_e('🛏️ Dormitorios', 'alquipress-theme'); ?></h4>
                <div class="alq-property-filters-radio">
                    <label>
                        <input type="radio" name="habitaciones_min" value="0" <?php checked($current_habitaciones, 0); ?>>
                        <?php esc_html_e('Cualquiera', 'alquipress-theme'); ?>
                    </label>
                    <label>
                        <input type="radio" name="habitaciones_min" value="2" <?php checked($current_habitaciones, 2); ?>>
                        2+
                    </label>
                    <label>
                        <input type="radio" name="habitaciones_min" value="3" <?php checked($current_habitaciones, 3); ?>>
                        3+
                    </label>
                    <label>
                        <input type="radio" name="habitaciones_min" value="4" <?php checked($current_habitaciones, 4); ?>>
                        4+
                    </label>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($show_characteristics && !empty($characteristics)): ?>
            <div class="alq-property-filters-group">
                <h4 class="alq-property-filters-title"><?php esc_html_e('✨ Características', 'alquipress-theme'); ?></h4>
                <div class="alq-property-filters-checkboxes">
                    <?php foreach ($characteristics as $char): ?>
                        <label class="alq-property-filters-checkbox alq-property-filters-checkbox-icon">
                            <input 
                                type="checkbox" 
                                name="caracteristicas[]" 
                                value="<?php echo esc_attr($char['id']); ?>"
                                <?php checked(in_array($char['id'], $current_caracteristicas)); ?>
                            >
                            <i class="<?php echo esc_attr($char['icon']); ?>"></i>
                            <span><?php echo esc_html($char['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <button type="submit" class="alq-property-filters-submit button button-primary">
            <?php esc_html_e('Aplicar Filtros', 'alquipress-theme'); ?>
        </button>
        
        <?php if (!empty($_GET)): ?>
            <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="alq-property-filters-clear">
                <?php esc_html_e('Limpiar', 'alquipress-theme'); ?>
            </a>
        <?php endif; ?>
    </form>
</div>
