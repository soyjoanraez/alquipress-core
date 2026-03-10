<?php
/**
 * Pattern: Home de alquiler – hero, buscador, grid, CTA
 */
if (!defined('ABSPATH')) {
    exit;
}
register_block_pattern(
    'alquipress-child/home-rental',
    [
        'title' => __('Home Alquiler', 'alquipress-child'),
        'description' => _x('Página de inicio para web de alquiler: buscador, grid de propiedades y CTA propietarios.', 'Block pattern description', 'alquipress-child'),
        'content' => '<!-- wp:alquipress-child/property-search {"title":"Encuentra tu alojamiento","subtitle":"Busca por fechas, huéspedes y ubicación."} /-->
<!-- wp:alquipress-child/property-grid {"columns":3,"postsPerPage":12,"showPagination":true} /-->
<!-- wp:alquipress-child/owner-cta {"title":"¿Tienes una propiedad?","subtitle":"Únete a nuestra red y empieza a generar ingresos.","buttonText":"Publicar mi propiedad"} /-->',
        'categories' => ['alquipress'],
        'keywords' => ['home', 'alquiler', 'buscador', 'grid'],
    ]
);
