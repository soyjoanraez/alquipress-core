<?php
/**
 * Template para Guía de la Casa
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

$show_checkin = isset($attributes['showCheckIn']) ? $attributes['showCheckIn'] : true;
$show_checkout = isset($attributes['showCheckOut']) ? $attributes['showCheckOut'] : true;
$show_rules = isset($attributes['showRules']) ? $attributes['showRules'] : true;
$show_amenities = isset($attributes['showAmenities']) ? $attributes['showAmenities'] : true;
$show_location = isset($attributes['showLocation']) ? $attributes['showLocation'] : true;

// Obtener campos ACF
$checkin_time = get_field('hora_checkin', $product_id) ?: '15:00';
$checkout_time = get_field('hora_checkout', $product_id) ?: '11:00';
$checkin_instructions = get_field('instrucciones_checkin', $product_id);
$checkout_instructions = get_field('instrucciones_checkout', $product_id);
$house_rules = get_field('normas_casa', $product_id);
$wifi_password = get_field('password_wifi', $product_id);
$emergency_contact = get_field('contacto_emergencia', $product_id);
$address = get_field('direccion_completa', $product_id);
$map_coordinates = get_field('coordenadas_mapa', $product_id);

// Obtener ubicación (taxonomía)
$poblacion = get_the_terms($product_id, 'poblacion');
$zona = get_the_terms($product_id, 'zona');
?>

<div class="alq-house-guide">
    <h2 class="alq-house-guide-title"><?php esc_html_e('Guía de la Casa', 'alquipress-theme'); ?></h2>
    
    <?php if ($show_checkin): ?>
        <div class="alq-house-guide-section">
            <h3 class="alq-house-guide-section-title">
                <span class="alq-house-guide-icon">🔑</span>
                <?php esc_html_e('Check-in', 'alquipress-theme'); ?>
            </h3>
            <div class="alq-house-guide-content">
                <div class="alq-house-guide-time">
                    <strong><?php esc_html_e('Hora:', 'alquipress-theme'); ?></strong> <?php echo esc_html($checkin_time); ?>
                </div>
                <?php if ($checkin_instructions): ?>
                    <div class="alq-house-guide-instructions">
                        <?php echo wp_kses_post($checkin_instructions); ?>
                    </div>
                <?php else: ?>
                    <p><?php esc_html_e('Recibirás las instrucciones de check-in por email antes de tu llegada.', 'alquipress-theme'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($show_checkout): ?>
        <div class="alq-house-guide-section">
            <h3 class="alq-house-guide-section-title">
                <span class="alq-house-guide-icon">🚪</span>
                <?php esc_html_e('Check-out', 'alquipress-theme'); ?>
            </h3>
            <div class="alq-house-guide-content">
                <div class="alq-house-guide-time">
                    <strong><?php esc_html_e('Hora:', 'alquipress-theme'); ?></strong> <?php echo esc_html($checkout_time); ?>
                </div>
                <?php if ($checkout_instructions): ?>
                    <div class="alq-house-guide-instructions">
                        <?php echo wp_kses_post($checkout_instructions); ?>
                    </div>
                <?php else: ?>
                    <p><?php esc_html_e('Por favor, deja la propiedad limpia y ordenada. Las llaves puedes dejarlas en el lugar indicado.', 'alquipress-theme'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($show_rules && $house_rules): ?>
        <div class="alq-house-guide-section">
            <h3 class="alq-house-guide-section-title">
                <span class="alq-house-guide-icon">📋</span>
                <?php esc_html_e('Normas de la Casa', 'alquipress-theme'); ?>
            </h3>
            <div class="alq-house-guide-content">
                <?php echo wp_kses_post($house_rules); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($show_amenities): ?>
        <div class="alq-house-guide-section">
            <h3 class="alq-house-guide-section-title">
                <span class="alq-house-guide-icon">📶</span>
                <?php esc_html_e('Información Útil', 'alquipress-theme'); ?>
            </h3>
            <div class="alq-house-guide-content">
                <?php if ($wifi_password): ?>
                    <div class="alq-house-guide-info-item">
                        <strong><?php esc_html_e('WiFi:', 'alquipress-theme'); ?></strong>
                        <code><?php echo esc_html($wifi_password); ?></code>
                    </div>
                <?php endif; ?>
                
                <?php if ($emergency_contact): ?>
                    <div class="alq-house-guide-info-item">
                        <strong><?php esc_html_e('Contacto de Emergencia:', 'alquipress-theme'); ?></strong>
                        <?php echo esc_html($emergency_contact); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($show_location && ($address || $poblacion || $zona)): ?>
        <div class="alq-house-guide-section">
            <h3 class="alq-house-guide-section-title">
                <span class="alq-house-guide-icon">📍</span>
                <?php esc_html_e('Ubicación', 'alquipress-theme'); ?>
            </h3>
            <div class="alq-house-guide-content">
                <?php if ($address): ?>
                    <div class="alq-house-guide-address">
                        <strong><?php esc_html_e('Dirección:', 'alquipress-theme'); ?></strong>
                        <p><?php echo esc_html($address); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($poblacion || $zona): ?>
                    <div class="alq-house-guide-location-terms">
                        <?php if ($poblacion && !is_wp_error($poblacion)): ?>
                            <span class="alq-house-guide-location-tag">
                                <?php echo esc_html($poblacion[0]->name); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($zona && !is_wp_error($zona)): ?>
                            <span class="alq-house-guide-location-tag">
                                <?php echo esc_html($zona[0]->name); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($map_coordinates): ?>
                    <div class="alq-house-guide-map">
                        <?php
                        // Aquí podrías integrar un mapa con Leaflet.js o Google Maps
                        // Por ahora solo mostramos las coordenadas
                        ?>
                        <p><small><?php esc_html_e('Coordenadas:', 'alquipress-theme'); ?> <?php echo esc_html($map_coordinates); ?></small></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
