<?php
/**
 * Template part para renderizar el bloque Hero con Buscador
 * 
 * @var array $attributes Atributos del bloque
 * @var string $content Contenido del bloque
 * @var WP_Block $block Instancia del bloque
 */

if (!defined('ABSPATH')) {
    exit;
}

$title = isset($attributes['title']) ? esc_html($attributes['title']) : __('Encuentra tu Refugio en la Costa Blanca', 'alquipress-theme');
$subtitle = isset($attributes['subtitle']) ? esc_html($attributes['subtitle']) : __('Más de 200 propiedades disponibles para tu estancia perfecta', 'alquipress-theme');
$background_type = isset($attributes['backgroundType']) ? $attributes['backgroundType'] : 'image';
$background_image = isset($attributes['backgroundImage']) && isset($attributes['backgroundImage']['url']) ? esc_url($attributes['backgroundImage']['url']) : '';
$background_video = isset($attributes['backgroundVideo']) ? esc_url($attributes['backgroundVideo']) : '';
$overlay_opacity = isset($attributes['overlayOpacity']) ? floatval($attributes['overlayOpacity']) : 0.4;
$results_page = isset($attributes['resultsPage']) ? esc_url($attributes['resultsPage']) : get_post_type_archive_link('product');

// Si no hay página de resultados configurada, usar archive de productos
if (empty($results_page)) {
    $results_page = get_post_type_archive_link('product');
}

$block_id = 'alq-hero-search-' . uniqid();
?>

<div class="alq-hero-search" id="<?php echo esc_attr($block_id); ?>" data-results-page="<?php echo esc_url($results_page); ?>">
    <div class="alq-hero-search-background">
        <?php if ($background_type === 'video' && !empty($background_video)): ?>
            <video class="alq-hero-search-video" autoplay muted loop playsinline>
                <source src="<?php echo esc_url($background_video); ?>" type="video/mp4">
            </video>
        <?php elseif (!empty($background_image)): ?>
            <img src="<?php echo esc_url($background_image); ?>" alt="" class="alq-hero-search-image" loading="eager">
        <?php endif; ?>
        <div class="alq-hero-search-overlay" style="opacity: <?php echo esc_attr($overlay_opacity); ?>"></div>
    </div>
    
    <div class="alq-hero-search-content">
        <div class="alq-hero-search-text">
            <h1 class="alq-hero-search-title"><?php echo $title; ?></h1>
            <?php if (!empty($subtitle)): ?>
                <p class="alq-hero-search-subtitle"><?php echo $subtitle; ?></p>
            <?php endif; ?>
        </div>
        
        <form class="alq-hero-search-form" method="get" action="<?php echo esc_url($results_page); ?>">
            <div class="alq-hero-search-fields">
                <div class="alq-hero-search-field">
                    <label for="<?php echo esc_attr($block_id); ?>-location" class="screen-reader-text">
                        <?php esc_html_e('Ubicación', 'alquipress-theme'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="<?php echo esc_attr($block_id); ?>-location"
                        name="poblacion" 
                        class="alq-hero-search-input" 
                        placeholder="<?php esc_attr_e('¿Dónde quieres alojarte?', 'alquipress-theme'); ?>"
                        autocomplete="off"
                        data-autocomplete="true"
                    >
                    <div class="alq-hero-search-autocomplete" id="<?php echo esc_attr($block_id); ?>-autocomplete"></div>
                </div>
                
                <div class="alq-hero-search-field">
                    <label for="<?php echo esc_attr($block_id); ?>-checkin" class="screen-reader-text">
                        <?php esc_html_e('Fecha de entrada', 'alquipress-theme'); ?>
                    </label>
                    <input 
                        type="date" 
                        id="<?php echo esc_attr($block_id); ?>-checkin"
                        name="checkin" 
                        class="alq-hero-search-input alq-hero-search-date"
                        min="<?php echo esc_attr(date('Y-m-d', strtotime('today'))); ?>"
                        data-availability-check="true"
                    >
                    <span class="alq-hero-search-label"><?php esc_html_e('Check-in', 'alquipress-theme'); ?></span>
                </div>
                
                <div class="alq-hero-search-field">
                    <label for="<?php echo esc_attr($block_id); ?>-checkout" class="screen-reader-text">
                        <?php esc_html_e('Fecha de salida', 'alquipress-theme'); ?>
                    </label>
                    <input 
                        type="date" 
                        id="<?php echo esc_attr($block_id); ?>-checkout"
                        name="checkout" 
                        class="alq-hero-search-input alq-hero-search-date"
                        min="<?php echo esc_attr(date('Y-m-d', strtotime('tomorrow'))); ?>"
                        data-availability-check="true"
                    >
                    <span class="alq-hero-search-label"><?php esc_html_e('Check-out', 'alquipress-theme'); ?></span>
                </div>
                
                <div class="alq-hero-search-field">
                    <label for="<?php echo esc_attr($block_id); ?>-guests" class="screen-reader-text">
                        <?php esc_html_e('Huéspedes', 'alquipress-theme'); ?>
                    </label>
                    <select 
                        id="<?php echo esc_attr($block_id); ?>-guests"
                        name="huespedes" 
                        class="alq-hero-search-select"
                    >
                        <?php for ($i = 1; $i <= 20; $i++): ?>
                            <option value="<?php echo esc_attr($i); ?>">
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
                
                <button type="submit" class="alq-hero-search-submit">
                    <span class="alq-hero-search-submit-icon">🔍</span>
                    <?php esc_html_e('Buscar', 'alquipress-theme'); ?>
                </button>
            </div>
        </form>
        
        <div class="alq-hero-search-features">
            <span class="alq-hero-search-feature">
                ✓ <?php esc_html_e('+200 propiedades', 'alquipress-theme'); ?>
            </span>
            <span class="alq-hero-search-feature">
                ✓ <?php esc_html_e('Pago seguro', 'alquipress-theme'); ?>
            </span>
            <span class="alq-hero-search-feature">
                ✓ <?php esc_html_e('Confirmación instantánea', 'alquipress-theme'); ?>
            </span>
        </div>
    </div>
</div>
