<?php
/**
 * CTA para propietarios – enlace al panel o formulario de contacto
 */
if (!defined('ABSPATH')) {
    exit;
}
$title = isset($attributes['title']) ? $attributes['title'] : __('¿Tienes una propiedad?', 'alquipress-child');
$subtitle = isset($attributes['subtitle']) ? $attributes['subtitle'] : __('Únete a nuestra red y empieza a generar ingresos con tu alojamiento.', 'alquipress-child');
$button_text = isset($attributes['buttonText']) ? $attributes['buttonText'] : __('Publicar mi propiedad', 'alquipress-child');
$button_url = isset($attributes['buttonUrl']) && $attributes['buttonUrl'] !== '' ? esc_url($attributes['buttonUrl']) : '';
if (empty($button_url)) {
    $button_url = home_url('/contacto/');
    $page_mi_area = get_page_by_path('mi-area');
    if ($page_mi_area) {
        $button_url = get_permalink($page_mi_area);
    }
}
?>
<div class="ap-child-owner-cta">
    <div class="ap-child-owner-cta-inner">
        <h2 class="ap-child-owner-cta-title"><?php echo esc_html($title); ?></h2>
        <p class="ap-child-owner-cta-subtitle"><?php echo esc_html($subtitle); ?></p>
        <ul class="ap-child-owner-cta-benefits">
            <li><?php esc_html_e('Gestión profesional de reservas', 'alquipress-child'); ?></li>
            <li><?php esc_html_e('Pago seguro y liquidaciones claras', 'alquipress-child'); ?></li>
            <li><?php esc_html_e('Panel para ver la ocupación de tu casa', 'alquipress-child'); ?></li>
        </ul>
        <a href="<?php echo esc_url($button_url); ?>" class="ap-child-owner-cta-button button"><?php echo esc_html($button_text); ?></a>
    </div>
</div>
