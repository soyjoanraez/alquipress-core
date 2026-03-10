<?php
/**
 * Grid de propiedades – lee GET (checkin, checkout, huespedes, poblacion) y filtra
 */
if (!defined('ABSPATH')) {
    exit;
}
$filters = [
    'poblacion' => isset($_GET['poblacion']) ? sanitize_text_field(wp_unslash($_GET['poblacion'])) : '',
    'zona' => isset($_GET['zona']) ? sanitize_text_field(wp_unslash($_GET['zona'])) : '',
    'precio_min' => isset($_GET['precio_min']) ? absint($_GET['precio_min']) : 0,
    'precio_max' => isset($_GET['precio_max']) ? absint($_GET['precio_max']) : 0,
    'habitaciones_min' => isset($_GET['habitaciones_min']) ? absint($_GET['habitaciones_min']) : 0,
    'banos_min' => isset($_GET['banos_min']) ? absint($_GET['banos_min']) : 0,
    'search' => isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '',
    'orderby' => isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : ($attributes['orderBy'] ?? 'date'),
    'order' => isset($_GET['order']) ? strtoupper(sanitize_key(wp_unslash($_GET['order']))) : ($attributes['order'] ?? 'DESC'),
    'per_page' => isset($attributes['postsPerPage']) ? absint($attributes['postsPerPage']) : 12,
];
$guests = isset($_GET['huespedes']) ? absint($_GET['huespedes']) : 0;
$paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;
$columns = isset($attributes['columns']) ? absint($attributes['columns']) : 3;
$show_pagination = !empty($attributes['showPagination']);

$args = [
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => $filters['per_page'],
    'paged' => $paged,
    'orderby' => $filters['orderby'],
    'order' => $filters['order'],
];
$tax_query = [];
if (!empty($filters['poblacion'])) {
    $tax_query[] = ['taxonomy' => 'poblacion', 'field' => 'slug', 'terms' => $filters['poblacion']];
}
if (!empty($filters['zona'])) {
    $tax_query[] = ['taxonomy' => 'zona', 'field' => 'slug', 'terms' => $filters['zona']];
}
if (!empty($tax_query)) {
    $args['tax_query'] = $tax_query;
}
$meta_query = [];
if (!empty($filters['precio_min'])) {
    $meta_query[] = ['key' => '_price', 'value' => $filters['precio_min'], 'compare' => '>=', 'type' => 'NUMERIC'];
}
if (!empty($filters['precio_max'])) {
    $meta_query[] = ['key' => '_price', 'value' => $filters['precio_max'], 'compare' => '<=', 'type' => 'NUMERIC'];
}
if (!empty($filters['habitaciones_min'])) {
    $meta_query[] = ['key' => 'numero_habitaciones', 'value' => $filters['habitaciones_min'], 'compare' => '>=', 'type' => 'NUMERIC'];
}
if (!empty($filters['banos_min'])) {
    $meta_query[] = ['key' => 'numero_banos', 'value' => $filters['banos_min'], 'compare' => '>=', 'type' => 'NUMERIC'];
}
if ($guests > 0) {
    $meta_query[] = ['key' => 'capacidad_maxima', 'value' => $guests, 'compare' => '>=', 'type' => 'NUMERIC'];
}
if (!empty($meta_query)) {
    $args['meta_query'] = $meta_query;
}
if (!empty($filters['search'])) {
    $args['s'] = $filters['search'];
}
if ($filters['orderby'] === 'price') {
    $args['meta_key'] = '_price';
    $args['orderby'] = 'meta_value_num';
}
$query = new WP_Query($args);
$block_id = 'ap-child-grid-' . uniqid();
?>
<div class="ap-child-property-grid ap-child-property-grid--cols-<?php echo esc_attr($columns); ?>" id="<?php echo esc_attr($block_id); ?>">
    <?php if ($query->have_posts()) : ?>
        <div class="ap-child-property-grid-items">
            <?php while ($query->have_posts()) : $query->the_post();
                $property_data = function_exists('alquipress_get_property_data') ? alquipress_get_property_data(get_the_ID()) : null;
                if ($property_data) {
                    include get_template_directory() . '/blocks/property-card/render.php';
                }
            endwhile; ?>
        </div>
        <?php if ($show_pagination && $query->max_num_pages > 1) : ?>
            <nav class="ap-child-property-grid-pagination" aria-label="<?php esc_attr_e('Paginación', 'alquipress-child'); ?>">
                <?php
                echo paginate_links([
                    'total' => $query->max_num_pages,
                    'current' => $paged,
                    'prev_text' => '&larr; ' . __('Anterior', 'alquipress-child'),
                    'next_text' => __('Siguiente', 'alquipress-child') . ' &rarr;',
                    'type' => 'list',
                ]);
                ?>
            </nav>
        <?php endif; ?>
    <?php else : ?>
        <div class="ap-child-property-grid-empty">
            <p><?php esc_html_e('No hay propiedades que coincidan con los filtros.', 'alquipress-child'); ?></p>
            <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="button"><?php esc_html_e('Ver todas', 'alquipress-child'); ?></a>
        </div>
    <?php endif; ?>
</div>
<?php wp_reset_postdata(); ?>
