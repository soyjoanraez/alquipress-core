<?php
/**
 * Pattern: Ficha de propiedad – KPIs, reserva, disponibilidad
 */
if (!defined('ABSPATH')) {
    exit;
}
register_block_pattern(
    'alquipress-child/single-property',
    [
        'title' => __('Ficha de propiedad', 'alquipress-child'),
        'description' => _x('KPIs, formulario de reserva y calendario de disponibilidad. Usar en plantilla de producto.', 'Block pattern description', 'alquipress-child'),
        'content' => '<!-- wp:alquipress-child/property-kpis /-->
<!-- wp:alquipress-child/booking-widget {"sticky":true,"showCleaningFee":true,"showLaundryFee":true} /-->
<!-- wp:alquipress-child/availability-calendar {"monthsToShow":6} /-->',
        'categories' => ['alquipress'],
        'keywords' => ['propiedad', 'reserva', 'disponibilidad', 'ficha'],
    ]
);
