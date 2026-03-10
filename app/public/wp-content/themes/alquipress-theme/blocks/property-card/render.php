<?php
/**
 * Template para tarjeta de propiedad reutilizable
 * 
 * @var array $property_data Datos de la propiedad
 */

if (!defined('ABSPATH')) {
    exit;
}

// Si no se pasan datos, intentar obtenerlos del post actual
if (empty($property_data) && in_the_loop()) {
    $property_data = alquipress_get_property_data(get_the_ID());
}

if (empty($property_data)) {
    return;
}

$product_id = $property_data['id'];
$title = $property_data['title'];
$permalink = $property_data['permalink'];
$image = $property_data['image'];
$price_html = $property_data['price_html'];
$poblacion = $property_data['poblacion'];
$zona = $property_data['zona'];
$habitaciones = $property_data['habitaciones'];
$banos = $property_data['banos'];
$capacidad = $property_data['capacidad'];

// Construir ubicación
$ubicacion = $poblacion;
if (!empty($zona)) {
    $ubicacion .= ' - ' . $zona;
}
?>

<article class="alq-property-card" data-property-id="<?php echo esc_attr($product_id); ?>">
    <a href="<?php echo esc_url($permalink); ?>" class="alq-property-card-link">
        <div class="alq-property-card-image-wrapper">
            <?php if ($image): ?>
                <img 
                    src="<?php echo esc_url($image); ?>" 
                    alt="<?php echo esc_attr($title); ?>"
                    class="alq-property-card-image"
                    loading="lazy"
                >
            <?php else: ?>
                <div class="alq-property-card-image-placeholder">
                    <?php esc_html_e('Sin imagen', 'alquipress-theme'); ?>
                </div>
            <?php endif; ?>
            
            <button class="alq-property-card-wishlist" data-product-id="<?php echo esc_attr($product_id); ?>" aria-label="<?php esc_attr_e('Añadir a favoritos', 'alquipress-theme'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
            </button>
        </div>
        
        <div class="alq-property-card-content">
            <?php if (!empty($ubicacion)): ?>
                <div class="alq-property-card-location">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span><?php echo esc_html($ubicacion); ?></span>
                </div>
            <?php endif; ?>
            
            <h3 class="alq-property-card-title">
                <?php echo esc_html($title); ?>
            </h3>
            
            <div class="alq-property-card-features">
                <?php if ($habitaciones > 0): ?>
                    <span class="alq-property-card-feature">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"></path>
                            <path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"></path>
                            <path d="M12 4v6"></path>
                            <path d="M2 18h20"></path>
                        </svg>
                        <?php printf(esc_html(_n('%d dormitorio', '%d dormitorios', $habitaciones, 'alquipress-theme')), $habitaciones); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($banos > 0): ?>
                    <span class="alq-property-card-feature">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1 .5C3.5 4.5 2 6 2 7.5 2 9 3.5 10.5 5 11.5"></path>
                            <path d="m6 8 2 2"></path>
                            <path d="m4 14 2 2"></path>
                            <path d="m2 20 2 2"></path>
                            <path d="M22 20l-2 2"></path>
                            <path d="M22 14l-2 2"></path>
                            <path d="M22 8l-2 2"></path>
                            <path d="M22 2l-2 2"></path>
                        </svg>
                        <?php printf(esc_html(_n('%d baño', '%d baños', $banos, 'alquipress-theme')), $banos); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($capacidad > 0): ?>
                    <span class="alq-property-card-feature">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <?php printf(esc_html(_n('%d persona', '%d personas', $capacidad, 'alquipress-theme')), $capacidad); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="alq-property-card-footer">
                <div class="alq-property-card-price">
                    <?php echo wp_kses_post($price_html); ?>
                    <span class="alq-property-card-price-label"><?php esc_html_e('/noche', 'alquipress-theme'); ?></span>
                </div>
                <span class="alq-property-card-button">
                    <?php esc_html_e('Ver más', 'alquipress-theme'); ?> →
                </span>
            </div>
        </div>
    </a>
</article>
