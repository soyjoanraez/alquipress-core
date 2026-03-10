<?php
/**
 * Pattern: Página de resultados de búsqueda
 */
if (!defined('ABSPATH')) {
    exit;
}
register_block_pattern(
    'alquipress-child/search-results',
    [
        'title' => __('Resultados de búsqueda', 'alquipress-child'),
        'description' => _x('Buscador en la parte superior y grid de propiedades debajo. Ideal como página de resultados.', 'Block pattern description', 'alquipress-child'),
        'content' => '<!-- wp:alquipress-child/property-search /-->
<!-- wp:alquipress-child/property-grid {"columns":3,"postsPerPage":12,"showPagination":true} /-->',
        'categories' => ['alquipress'],
        'keywords' => ['resultados', 'búsqueda', 'grid'],
    ]
);
