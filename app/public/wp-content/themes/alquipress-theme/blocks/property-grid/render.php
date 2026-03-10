<?php
/**
 * Template para renderizar el bloque Grid de Propiedades
 * 
 * @var array $attributes Atributos del bloque
 * @var string $content Contenido del bloque
 * @var WP_Block $block Instancia del bloque
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener filtros de URL
$filters = [
    'poblacion' => isset($_GET['poblacion']) ? sanitize_text_field($_GET['poblacion']) : '',
    'zona' => isset($_GET['zona']) ? sanitize_text_field($_GET['zona']) : '',
    'caracteristicas' => isset($_GET['caracteristicas']) ? array_map('absint', (array) $_GET['caracteristicas']) : [],
    'precio_min' => isset($_GET['precio_min']) ? absint($_GET['precio_min']) : 0,
    'precio_max' => isset($_GET['precio_max']) ? absint($_GET['precio_max']) : 0,
    'habitaciones_min' => isset($_GET['habitaciones_min']) ? absint($_GET['habitaciones_min']) : 0,
    'banos_min' => isset($_GET['banos_min']) ? absint($_GET['banos_min']) : 0,
    'search' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
    'orderby' => isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : ($attributes['orderBy'] ?? 'date'),
    'order' => isset($_GET['order']) ? strtoupper(sanitize_key($_GET['order'])) : ($attributes['order'] ?? 'DESC'),
    'per_page' => isset($attributes['postsPerPage']) ? absint($attributes['postsPerPage']) : 12,
];

// Aplicar ordenación desde atributos si no hay en URL
if (empty($_GET['orderby'])) {
    $filters['orderby'] = $attributes['orderBy'] ?? 'date';
}
if (empty($_GET['order'])) {
    $filters['order'] = $attributes['order'] ?? 'DESC';
}

// Obtener página actual
$paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;

// Query de propiedades
$query = alquipress_get_filtered_properties($filters);
$query->set('paged', $paged);

// Atributos del bloque
$columns = isset($attributes['columns']) ? absint($attributes['columns']) : 3;
$layout = isset($attributes['layout']) ? $attributes['layout'] : 'grid';
$show_pagination = isset($attributes['showPagination']) ? $attributes['showPagination'] : true;
$use_ajax = isset($attributes['useAjaxPagination']) ? $attributes['useAjaxPagination'] : false;

$block_id = 'alq-property-grid-' . uniqid();
$grid_class = 'alq-property-grid';
$grid_class .= ' alq-property-grid--' . esc_attr($layout);
$grid_class .= ' alq-property-grid--cols-' . $columns;
?>

<div class="<?php echo esc_attr($grid_class); ?>" id="<?php echo esc_attr($block_id); ?>" data-ajax="<?php echo $use_ajax ? 'true' : 'false'; ?>">
    <?php if ($query->have_posts()): ?>
        <div class="alq-property-grid-items">
            <?php while ($query->have_posts()): $query->the_post(); ?>
                <?php
                $property_data = alquipress_get_property_data(get_the_ID());
                if ($property_data) {
                    // Incluir template de tarjeta
                    include get_template_directory() . '/blocks/property-card/render.php';
                }
                ?>
            <?php endwhile; ?>
        </div>
        
        <?php if ($show_pagination && $query->max_num_pages > 1): ?>
            <nav class="alq-property-grid-pagination" aria-label="<?php esc_attr_e('Paginación de propiedades', 'alquipress-theme'); ?>">
                <?php
                echo paginate_links([
                    'total' => $query->max_num_pages,
                    'current' => $paged,
                    'prev_text' => '← ' . __('Anterior', 'alquipress-theme'),
                    'next_text' => __('Siguiente', 'alquipress-theme') . ' →',
                    'type' => 'list',
                ]);
                ?>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alq-property-grid-empty">
            <p><?php esc_html_e('No se encontraron propiedades que coincidan con los filtros.', 'alquipress-theme'); ?></p>
            <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="button">
                <?php esc_html_e('Ver todas las propiedades', 'alquipress-theme'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
wp_reset_postdata();
?>
