<?php
/**
 * KPIs de propiedad: habitaciones, baños, plazas, superficie, precio/noche, limpieza
 */
if (!defined('ABSPATH')) {
    exit;
}
if (!is_singular('product')) {
    echo '<p class="ap-child-kpis-placeholder">' . esc_html__('Bloque para ficha de propiedad.', 'alquipress-child') . '</p>';
    return;
}
global $product;
$product_id = $product->get_id();
$meta = function_exists('alquipress_child_get_property_meta') ? alquipress_child_get_property_meta($product_id) : [];
if (empty($meta)) {
    return;
}
$items = [];
if (!empty($meta['habitaciones'])) {
    $items[] = ['label' => _n('Habitación', 'Habitaciones', $meta['habitaciones'], 'alquipress-child'), 'value' => $meta['habitaciones']];
}
if (!empty($meta['banos'])) {
    $items[] = ['label' => _n('Baño', 'Baños', $meta['banos'], 'alquipress-child'), 'value' => $meta['banos']];
}
if (!empty($meta['plazas'])) {
    $items[] = ['label' => _n('Plaza', 'Plazas', $meta['plazas'], 'alquipress-child'), 'value' => $meta['plazas']];
}
if (!empty($meta['superficie'])) {
    $items[] = ['label' => __('Superficie', 'alquipress-child'), 'value' => $meta['superficie'] . ' m²'];
}
if ($meta['price'] !== '' && $meta['price'] !== null) {
    $items[] = ['label' => __('Precio/noche', 'alquipress-child'), 'value' => $meta['price_html'] ?: wc_price($meta['price']), 'html' => true];
}
if (!empty($meta['cleaning_fee'])) {
    $items[] = ['label' => __('Limpieza', 'alquipress-child'), 'value' => function_exists('wc_price') ? wc_price($meta['cleaning_fee']) : $meta['cleaning_fee'] . ' €', 'html' => true];
}
if (empty($items)) {
    return;
}
?>
<div class="ap-child-property-kpis">
    <?php foreach ($items as $item) : ?>
        <div class="ap-child-property-kpis-item">
            <span class="ap-child-property-kpis-label"><?php echo esc_html($item['label']); ?></span>
            <span class="ap-child-property-kpis-value"><?php echo !empty($item['html']) ? wp_kses_post($item['value']) : esc_html($item['value']); ?></span>
        </div>
    <?php endforeach; ?>
</div>
