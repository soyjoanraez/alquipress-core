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

        $inline_js = "
        jQuery(document).ready(function($) {
            $('.alquipress-filter-group input').on('change', function() {
                var url = new URL(window.location);
                var tax = $(this).closest('.alquipress-filter-group').data('taxonomy');
                var values = [];
                
                $(this).closest('.alquipress-filter-group').find('input:checked').each(function() {
                    values.push($(this).val());
                });
                
                url.searchParams.delete(tax);
                if (values.length) {
                    url.searchParams.append(tax, values.join(','));
                }
                
                window.location = url;
            });
        });";

        wp_add_inline_script('jquery', $inline_js);

        $inline_css = "
        .alquipress-filter-group { margin-bottom: 30px; }
        .alquipress-filter-group h4 { margin-bottom: 10px; font-weight: 700; border-bottom: 2px solid #f0f0f1; padding-bottom: 5px; }
        .alquipress-filter-list { list-style: none; padding: 0; }
        .alquipress-filter-list li { margin-bottom: 5px; }
        .alquipress-filter-list label { cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .alquipress-filter-list input { margin: 0; }
        ";
        wp_add_inline_style('astra-theme-css', $inline_css);
    }

    public function apply_taxonomy_filters($query)
    {
        if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_taxonomy())) {
            $taxonomies = ['poblacion', 'zona', 'caracteristicas'];
            $tax_query = $query->get('tax_query') ?: [];

            foreach ($taxonomies as $tax) {
                if (isset($_GET[$tax]) && !empty($_GET[$tax])) {
                    $terms = explode(',', $_GET[$tax]);
                    $tax_query[] = [
                        'taxonomy' => $tax,
                        'field' => 'slug',
                        'terms' => $terms,
                        'operator' => ($tax === 'caracteristicas') ? 'AND' : 'IN',
                    ];
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
            $selected = isset($_GET[$taxonomy]) ? explode(',', $_GET[$taxonomy]) : [];
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
