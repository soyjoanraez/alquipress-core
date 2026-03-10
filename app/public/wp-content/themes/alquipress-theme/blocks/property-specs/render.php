<?php
/**
 * Template para Ficha Técnica de Propiedad
 */

if (!defined('ABSPATH')) {
    exit;
}

// Solo en single product
if (!is_singular('product')) {
    return;
}

global $product;
$product_id = $product->get_id();

// Obtener campos ACF
$superficie = get_field('superficie_m2', $product_id);
$habitaciones = get_field('numero_habitaciones', $product_id);
$banos = get_field('numero_banos', $product_id);
$capacidad = get_field('capacidad_maxima', $product_id);
$distancia_playa = get_field('distancia_playa', $product_id);
$distancia_centro = get_field('distancia_centro', $product_id);
$licencia = get_field('licencia_turistica', $product_id);

// Obtener características (taxonomía)
$caracteristicas = get_the_terms($product_id, 'caracteristicas');

$has_specs = $superficie || $habitaciones || $banos || $capacidad || $distancia_playa || $distancia_centro || $licencia;

if (!$has_specs && empty($caracteristicas)) {
    return;
}
?>

<div class="alq-property-specs">
    <?php if ($has_specs): ?>
        <div class="alq-property-specs-section">
            <h3 class="alq-property-specs-title"><?php esc_html_e('Características', 'alquipress-theme'); ?></h3>
            <div class="alq-property-specs-grid">
                <?php if ($superficie): ?>
                    <div class="alq-property-spec-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <path d="M3 9h18"></path>
                            <path d="M9 21V9"></path>
                        </svg>
                        <div>
                            <span class="alq-property-spec-label"><?php esc_html_e('Superficie', 'alquipress-theme'); ?></span>
                            <span class="alq-property-spec-value"><?php echo esc_html($superficie); ?> m²</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($habitaciones): ?>
                    <div class="alq-property-spec-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"></path>
                            <path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"></path>
                            <path d="M12 4v6"></path>
                            <path d="M2 18h20"></path>
                        </svg>
                        <div>
                            <span class="alq-property-spec-label"><?php esc_html_e('Dormitorios', 'alquipress-theme'); ?></span>
                            <span class="alq-property-spec-value"><?php echo esc_html($habitaciones); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($banos): ?>
                    <div class="alq-property-spec-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1 .5C3.5 4.5 2 6 2 7.5 2 9 3.5 10.5 5 11.5"></path>
                            <path d="m6 8 2 2"></path>
                            <path d="m4 14 2 2"></path>
                            <path d="m2 20 2 2"></path>
                            <path d="M22 20l-2 2"></path>
                            <path d="M22 14l-2 2"></path>
                            <path d="M22 8l-2 2"></path>
                            <path d="M22 2l-2 2"></path>
                        </svg>
                        <div>
                            <span class="alq-property-spec-label"><?php esc_html_e('Baños', 'alquipress-theme'); ?></span>
                            <span class="alq-property-spec-value"><?php echo esc_html($banos); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($capacidad): ?>
                    <div class="alq-property-spec-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <div>
                            <span class="alq-property-spec-label"><?php esc_html_e('Capacidad', 'alquipress-theme'); ?></span>
                            <span class="alq-property-spec-value"><?php printf(esc_html(_n('%d persona', '%d personas', $capacidad, 'alquipress-theme')), $capacidad); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($distancia_playa): ?>
                    <div class="alq-property-spec-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <div>
                            <span class="alq-property-spec-label"><?php esc_html_e('Distancia a la playa', 'alquipress-theme'); ?></span>
                            <span class="alq-property-spec-value"><?php echo esc_html($distancia_playa); ?> m</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($distancia_centro): ?>
                    <div class="alq-property-spec-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <div>
                            <span class="alq-property-spec-label"><?php esc_html_e('Distancia al centro', 'alquipress-theme'); ?></span>
                            <span class="alq-property-spec-value"><?php echo esc_html($distancia_centro); ?> m</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($licencia): ?>
                    <div class="alq-property-spec-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <div>
                            <span class="alq-property-spec-label"><?php esc_html_e('Licencia', 'alquipress-theme'); ?></span>
                            <span class="alq-property-spec-value"><?php echo esc_html($licencia); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($caracteristicas) && !is_wp_error($caracteristicas)): ?>
        <div class="alq-property-specs-section">
            <h3 class="alq-property-specs-title"><?php esc_html_e('✨ Comodidades', 'alquipress-theme'); ?></h3>
            <div class="alq-property-specs-amenities">
                <?php foreach ($caracteristicas as $term): 
                    $icon = get_field('icono_clase', 'caracteristicas_' . $term->term_id);
                    $icon_class = $icon ?: 'fa-circle';
                ?>
                    <div class="alq-property-spec-amenity">
                        <?php if (strpos($icon_class, 'fa-') === 0): ?>
                            <i class="<?php echo esc_attr($icon_class); ?>"></i>
                        <?php else: ?>
                            <span class="alq-property-spec-amenity-icon"><?php echo esc_html($icon_class); ?></span>
                        <?php endif; ?>
                        <span><?php echo esc_html($term->name); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
