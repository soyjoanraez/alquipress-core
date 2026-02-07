<?php
/**
 * Alquipress Frontend Filters & Widgets
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Frontend_Filters
{

    public function __construct()
    {
        add_action('widgets_init', [$this, 'register_widgets']);
        add_action('pre_get_posts', [$this, 'apply_taxonomy_filters']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_widgets()
    {
        register_widget('Alquipress_Taxonomy_Filter_Widget');
    }

    public function enqueue_assets()
    {
        if (!is_shop() && !is_product_taxonomy())
            return;

        // Cargar CSS desde archivo externo
        wp_enqueue_style(
            'alquipress-frontend-filters',
            ALQUIPRESS_URL . 'includes/assets/css/frontend-filters.css',
            [],
            ALQUIPRESS_VERSION
        );

        // Cargar JS desde archivo externo
        wp_enqueue_script(
            'alquipress-frontend-filters',
            ALQUIPRESS_URL . 'includes/assets/js/frontend-filters.js',
            ['jquery'],
            ALQUIPRESS_VERSION,
            true
        );
    }

    public function apply_taxonomy_filters($query)
    {
        if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_taxonomy())) {
            $taxonomies = ['poblacion', 'zona', 'caracteristicas'];
            $tax_query = $query->get('tax_query') ?: [];

            foreach ($taxonomies as $tax) {
                if (isset($_GET[$tax]) && !empty($_GET[$tax])) {
                    $raw = wp_unslash($_GET[$tax]);
                    $terms = array_filter(array_unique(array_map('sanitize_title', explode(',', $raw))));

                    // Validar que los términos existen en la taxonomía
                    $valid_terms = [];
                    foreach ($terms as $term_slug) {
                        $term = get_term_by('slug', $term_slug, $tax);
                        if ($term && !is_wp_error($term)) {
                            $valid_terms[] = $term_slug;
                        }
                    }

                    if (!empty($valid_terms)) {
                        $tax_query[] = [
                            'taxonomy' => $tax,
                            'field' => 'slug',
                            'terms' => $valid_terms,
                            'operator' => ($tax === 'caracteristicas') ? 'AND' : 'IN',
                        ];
                    }
                }
            }

            if (!empty($tax_query)) {
                $query->set('tax_query', $tax_query);
            }
        }
    }
}

class Alquipress_Taxonomy_Filter_Widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'alquipress_tax_filter',
            'Alquipress: Filtro Taxonomía',
            ['description' => 'Filtro premium para Población, Zona o Características']
        );
    }

    public function widget($args, $instance)
    {
        $taxonomy = !empty($instance['taxonomy']) ? $instance['taxonomy'] : 'caracteristicas';
        $title = !empty($instance['title']) ? $instance['title'] : 'Filtrar';

        echo $args['before_widget'];
        if ($title) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ]);

        if (!empty($terms)) {
            if (isset($_GET[$taxonomy])) {
                $raw = wp_unslash($_GET[$taxonomy]);
                $selected = array_filter(array_unique(array_map('sanitize_title', explode(',', $raw))));
            } else {
                $selected = [];
            }
            echo '<div class="alquipress-filter-group" data-taxonomy="' . esc_attr($taxonomy) . '">';
            echo '<ul class="alquipress-filter-list">';
            foreach ($terms as $term) {
                $checked = in_array($term->slug, $selected) ? 'checked' : '';
                echo '<li>';
                echo '<label>';
                echo '<input type="checkbox" value="' . esc_attr($term->slug) . '" ' . $checked . '> ';
                echo esc_html($term->name) . ' <small>(' . $term->count . ')</small>';
                echo '</label>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : 'Características';
        $taxonomy = !empty($instance['taxonomy']) ? $instance['taxonomy'] : 'caracteristicas';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Título:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('taxonomy'); ?>">Taxonomía:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('taxonomy'); ?>"
                name="<?php echo $this->get_field_name('taxonomy'); ?>">
                <option value="poblacion" <?php selected($taxonomy, 'poblacion'); ?>>Población</option>
                <option value="zona" <?php selected($taxonomy, 'zona'); ?>>Zona</option>
                <option value="caracteristicas" <?php selected($taxonomy, 'caracteristicas'); ?>>Características</option>
            </select>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['taxonomy'] = (!empty($new_instance['taxonomy'])) ? strip_tags($new_instance['taxonomy']) : 'caracteristicas';
        return $instance;
    }
}

new Alquipress_Frontend_Filters();
