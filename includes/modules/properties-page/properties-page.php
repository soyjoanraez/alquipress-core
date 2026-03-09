<?php
/**
 * Módulo: Página Propiedades (diseño Pencil)
 * Listado de propiedades con header, toolbar, grid de tarjetas
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Properties_Page
{
    public function __construct()
    {
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_section_assets']);
        add_action('wp_ajax_alquipress_create_property', [$this, 'ajax_create_property']);
    }

    public function maybe_render_section($page)
    {
        if ($page === 'alquipress-properties') {
            $this->render_page();
        }
    }

    public function enqueue_section_assets($page)
    {
        if ($page !== 'alquipress-properties') {
            return;
        }
        wp_enqueue_style(
            'alquipress-properties-page',
            ALQUIPRESS_URL . 'includes/modules/properties-page/assets/properties-page.css',
            [],
            ALQUIPRESS_VERSION
        );
        wp_enqueue_style(
            'leaflet-css',
            ALQUIPRESS_URL . 'includes/assets/vendor/leaflet/leaflet.css',
            [],
            '1.9.4'
        );
        wp_enqueue_script(
            'leaflet-js',
            ALQUIPRESS_URL . 'includes/assets/vendor/leaflet/leaflet.js',
            [],
            '1.9.4',
            true
        );
        wp_enqueue_script(
            'alquipress-add-property-modal',
            ALQUIPRESS_URL . 'includes/modules/properties-page/assets/add-property-modal.js',
            ['jquery'],
            ALQUIPRESS_VERSION,
            true
        );
        wp_localize_script('alquipress-add-property-modal', 'alquipressAddProperty', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alquipress_create_property'),
            'i18n' => [
                'title' => __('Añadir propiedad', 'alquipress'),
                'desc' => __('Introduce el nombre o título de la propiedad.', 'alquipress'),
                'create' => __('Crear', 'alquipress'),
                'cancel' => __('Cancelar', 'alquipress'),
                'placeholder' => __('Nombre de la propiedad', 'alquipress'),
                'error' => __('Error al crear la propiedad.', 'alquipress'),
                'required' => __('El nombre es obligatorio.', 'alquipress'),
            ],
        ]);
    }

    public function ajax_create_property()
    {
        check_ajax_referer('alquipress_create_property', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $title = trim($title);
        if ($title === '') {
            wp_send_json_error(['message' => 'El nombre es obligatorio']);
        }
        $post_id = wp_insert_post([
            'post_type' => 'product',
            'post_title' => $title,
            'post_status' => 'draft',
        ]);
        if (is_wp_error($post_id) || !$post_id) {
            wp_send_json_error(['message' => 'Error al crear']);
        }
        $edit_url = admin_url('admin.php?page=alquipress-edit-property&post_id=' . $post_id);
        wp_send_json_success(['edit_url' => $edit_url]);
    }

    /**
     * Obtener ubicación del producto (taxonomía poblacion)
     */
    private function get_product_location($product_id)
    {
        return Alquipress_Property_Helper::get_product_location($product_id);
    }

    /**
     * Contar habitaciones desde ACF distribucion_habitaciones
     */
    private function get_product_beds($product_id)
    {
        return Alquipress_Property_Helper::get_product_beds($product_id);
    }

    /**
     * Número de baños (ACF numero_banos)
     */
    private function get_product_baths($product_id)
    {
        return Alquipress_Property_Helper::get_product_baths($product_id);
    }

    /**
     * Capacidad / plazas (ACF plazas o capacidad). Si no existe, null.
     */
    private function get_product_guests($product_id)
    {
        return Alquipress_Property_Helper::get_product_guests($product_id);
    }

    /**
     * Iconos Lucide (Pencil: bed-double, bath, users) como SVG inline 14x14
     */
    private function icon_bed_double()
    {
        return '<svg class="ap-props-icon ap-props-icon-bed" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/><path d="M12 4v6"/><path d="M2 18h20"/></svg>';
    }

    private function icon_bath()
    {
        return '<svg class="ap-props-icon ap-props-icon-bath" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1 .5C3.5 4.5 2 6 2 7.5 2 9 3.5 10.5 5 11.5"/><path d="m6 8 2 2"/><path d="m4 14 2 2"/><path d="m2 20 2 2"/><path d="M22 20l-2 2"/><path d="M22 14l-2 2"/><path d="M22 8l-2 2"/><path d="M22 2l-2 2"/></svg>';
    }

    private function icon_users()
    {
        return '<svg class="ap-props-icon ap-props-icon-users" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
    }

    /**
     * Ocupación reciente (porcentaje) - Altamente optimizado
     */
    private function get_product_occupancy_text($product_id)
    {
        $cache_key = 'ap_prop_occ_pct_' . $product_id;
        $pct = get_transient($cache_key);

        if ($pct === false) {
            global $wpdb;
            $today = current_time('Y-m-d');
            $start = date('Y-m-d', strtotime('-30 days'));
            $end = date('Y-m-d', strtotime('+30 days'));
            
            $statuses = ['wc-completed', 'wc-processing', 'wc-deposito-ok', 'wc-in-progress'];
            $status_string = "'" . implode("','", $statuses) . "'";

            // Query optimizada: Suma de días ocupados en el rango de 60 días
            $sql = "
                SELECT pm1.meta_value as checkin, pm2.meta_value as checkout
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm_prod ON p.ID = pm_prod.post_id AND pm_prod.meta_key = '_booking_product_id'
                INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_booking_checkin_date'
                INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_booking_checkout_date'
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN ($status_string)
                AND pm_prod.meta_value = %d
                AND pm1.meta_value <= %s
                AND pm2.meta_value >= %s
            ";

            $results = $wpdb->get_results($wpdb->prepare($sql, $product_id, $end, $start));
            
            $nights_occupied = 0;
            foreach ($results as $row) {
                $c_in = max(strtotime($start), strtotime($row->checkin));
                $c_out = min(strtotime($end), strtotime($row->checkout));
                $diff = ($c_out - $c_in) / 86400;
                if ($diff > 0) $nights_occupied += $diff;
            }

            $days_range = 60;
            $pct = round(($nights_occupied / $days_range) * 100);
            $pct = min(100, max(0, $pct));
            
            set_transient($cache_key, $pct, 15 * MINUTE_IN_SECONDS);
        }

        return $pct . '% ' . __('ocupación', 'alquipress');
    }

    public function render_page()
    {
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $status_filter = isset($_GET['status']) ? sanitize_key((string) $_GET['status']) : 'all';
        if ($status_filter === '' || !in_array($status_filter, ['all', 'active', 'maintenance'], true)) {
            $status_filter = 'all';
        }

        $price_min = isset($_GET['precio_min']) ? absint($_GET['precio_min']) : 0;
        $price_max = isset($_GET['precio_max']) ? absint($_GET['precio_max']) : 0;
        $poblacion = isset($_GET['poblacion']) ? sanitize_text_field(wp_unslash($_GET['poblacion'])) : '';
        $zona = isset($_GET['zona']) ? sanitize_text_field(wp_unslash($_GET['zona'])) : '';
        $caracteristicas = isset($_GET['caracteristicas']) ? array_map('sanitize_text_field', (array) $_GET['caracteristicas']) : [];
        $habitaciones_min = isset($_GET['habitaciones_min']) ? absint($_GET['habitaciones_min']) : 0;
        $banos_min = isset($_GET['banos_min']) ? absint($_GET['banos_min']) : 0;
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $per_page = 24;

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $habitaciones_min > 0 ? 500 : $per_page,
            'paged' => $habitaciones_min > 0 ? 1 : $paged,
            'order' => 'DESC',
        ];

        // Lógica de ordenación
        switch ($orderby) {
            case 'price_asc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
                $args['order'] = 'ASC';
                break;
            case 'price_desc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
                $args['order'] = 'DESC';
                break;
            case 'capacity_desc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = 'plazas'; // Usamos el campo principal de capacidad
                $args['order'] = 'DESC';
                break;
            case 'baths_desc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = 'numero_banos';
                $args['order'] = 'DESC';
                break;
            case 'rating_desc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_wc_average_rating';
                $args['order'] = 'DESC';
                break;
            case 'title':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }

        if ($status_filter === 'all') {
            $args['post_status'] = ['publish', 'draft'];
        } elseif ($status_filter === 'maintenance') {
            $args['post_status'] = 'draft';
        } else {
            $args['post_status'] = 'publish';
        }
        if ($search !== '') {
            $args['s'] = $search;
        }

        $meta_query = [];
        if ($price_min > 0 || $price_max > 0) {
            $price_query = ['relation' => 'AND'];
            if ($price_min > 0) {
                $price_query[] = ['key' => '_price', 'value' => $price_min, 'compare' => '>=', 'type' => 'NUMERIC'];
            }
            if ($price_max > 0) {
                $price_query[] = ['key' => '_price', 'value' => $price_max, 'compare' => '<=', 'type' => 'NUMERIC'];
            }
            $meta_query[] = $price_query;
        }
        if ($banos_min > 0) {
            $meta_query[] = [
                'key' => 'numero_banos',
                'value' => $banos_min,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        $tax_query = [];
        if ($poblacion !== '') {
            $tax_query[] = ['taxonomy' => 'poblacion', 'field' => 'slug', 'terms' => $poblacion];
        }
        if ($zona !== '') {
            $tax_query[] = ['taxonomy' => 'zona', 'field' => 'slug', 'terms' => $zona];
        }
        if (!empty($caracteristicas)) {
            $tax_query[] = ['taxonomy' => 'caracteristicas', 'field' => 'slug', 'terms' => $caracteristicas];
        }
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($args);
        $products = $query->posts;

        // Ordenación manual por Habitaciones (porque es un repetidor ACF)
        if ($orderby === 'beds_desc') {
            usort($products, function($a, $b) {
                $beds_a = (int) $this->get_product_beds($a->ID);
                $beds_b = (int) $this->get_product_beds($b->ID);
                return $beds_b <=> $beds_a;
            });
        }

        if ($habitaciones_min > 0) {
            $products = array_filter($products, function ($post) use ($habitaciones_min) {
                $beds = $this->get_product_beds($post->ID);
                return $beds !== null && $beds >= $habitaciones_min;
            });
            $products = array_values($products);
            $total_count = count($products);
            $products = array_slice($products, ($paged - 1) * $per_page, $per_page);
        } else {
            $total_count = $query->found_posts;
        }
        $showing_count = count($products);
        $total_pages = $total_count > 0 ? (int) ceil($total_count / $per_page) : 1;

        $count_all = wp_count_posts('product');
        $count_active = isset($count_all->publish) ? (int) $count_all->publish : 0;
        $count_maintenance = isset($count_all->draft) ? (int) $count_all->draft : 0;
        $count_all_num = $count_active + $count_maintenance;

        $add_url = admin_url('post-new.php?post_type=product');
        $base_url = (string) admin_url('admin.php?page=alquipress-properties');
        $terms_poblacion = get_terms(['taxonomy' => 'poblacion', 'hide_empty' => true]);
        $terms_zona = get_terms(['taxonomy' => 'zona', 'hide_empty' => true]);
        $terms_caracteristicas = get_terms(['taxonomy' => 'caracteristicas', 'hide_empty' => true]);
        if (!is_array($terms_poblacion)) {
            $terms_poblacion = [];
        }
        if (!is_array($terms_zona)) {
            $terms_zona = [];
        }
        if (!is_array($terms_caracteristicas)) {
            $terms_caracteristicas = [];
        }
        $map_properties = [];
        foreach ($products as $p) {
            $coords = function_exists('get_field') ? get_field('coordenadas_gps', $p->ID) : null;
            if (is_array($coords) && !empty($coords['lat']) && !empty($coords['lng'])) {
                $map_properties[] = [
                    'id' => $p->ID,
                    'title' => $p->post_title,
                    'title_escaped' => esc_html($p->post_title),
                    'lat' => (float) $coords['lat'],
                    'lng' => (float) $coords['lng'],
                    'url' => esc_url(admin_url('admin.php?page=alquipress-edit-property&post_id=' . $p->ID)),
                ];
            }
        }
        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap alquipress-properties-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('properties'); ?>
                <main class="ap-owners-main">
            <header class="ap-props-header">
                <div class="ap-props-header-left">
                    <h1 class="ap-props-title"><?php esc_html_e('Propiedades', 'alquipress'); ?></h1>
                    <p class="ap-props-subtitle"><?php esc_html_e('Gestiona tu cartera de alquileres vacacionales', 'alquipress'); ?></p>
                </div>
                <div class="ap-props-header-right">
                    <form action="<?php echo esc_url($base_url); ?>" method="get" class="ap-props-search-form">
                        <input type="hidden" name="page" value="alquipress-properties">
                        <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
                        <span class="ap-props-search-icon dashicons dashicons-search"></span>
                        <input type="search" name="s" class="ap-props-search-input" placeholder="<?php esc_attr_e('Buscar propiedades...', 'alquipress'); ?>" value="<?php echo esc_attr($search); ?>">
                    </form>
                    <button type="button" class="ap-props-filter-btn ap-props-filter-toggle" id="ap-props-filter-toggle" aria-expanded="false" aria-controls="ap-props-filter-panel">
                        <span class="dashicons dashicons-filter"></span>
                        <span><?php esc_html_e('Filtrar', 'alquipress'); ?></span>
                    </button>
                    <button type="button" class="ap-props-add-btn ap-add-property-trigger">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <span><?php esc_html_e('Añadir propiedad', 'alquipress'); ?></span>
                    </button>
                </div>
            </header>

            <?php
            $has_active_filters = $price_min > 0 || $price_max > 0 || $poblacion !== '' || $zona !== '' || $habitaciones_min > 0 || $banos_min > 0 || !empty($caracteristicas) || $status_filter !== 'all' || $search !== '';
            ?>
            <div class="ap-props-filter-panel" id="ap-props-filter-panel" role="region" aria-label="<?php esc_attr_e('Filtros', 'alquipress'); ?>" <?php echo $has_active_filters ? '' : 'hidden'; ?>>
                <form action="<?php echo esc_url($base_url); ?>" method="get" class="ap-props-filter-form">
                    <input type="hidden" name="page" value="alquipress-properties">
                    <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
                    <?php if ($search !== '') : ?>
                        <input type="hidden" name="s" value="<?php echo esc_attr($search); ?>">
                    <?php endif; ?>
                    <div class="ap-props-filter-grid">
                        <div class="ap-props-filter-field">
                            <label for="ap-filter-precio-min"><?php esc_html_e('Precio mín. (€/noche)', 'alquipress'); ?></label>
                            <input type="number" id="ap-filter-precio-min" name="precio_min" min="0" step="1" value="<?php echo $price_min > 0 ? (int) $price_min : ''; ?>" placeholder="—">
                        </div>
                        <div class="ap-props-filter-field">
                            <label for="ap-filter-precio-max"><?php esc_html_e('Precio máx. (€/noche)', 'alquipress'); ?></label>
                            <input type="number" id="ap-filter-precio-max" name="precio_max" min="0" step="1" value="<?php echo $price_max > 0 ? (int) $price_max : ''; ?>" placeholder="—">
                        </div>
                        <div class="ap-props-filter-field">
                            <label for="ap-filter-poblacion"><?php esc_html_e('Población', 'alquipress'); ?></label>
                            <select id="ap-filter-poblacion" name="poblacion">
                                <option value=""><?php esc_html_e('Todas', 'alquipress'); ?></option>
                                <?php foreach ($terms_poblacion as $term) : ?>
                                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($poblacion, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="ap-props-filter-field">
                            <label for="ap-filter-zona"><?php esc_html_e('Zona', 'alquipress'); ?></label>
                            <select id="ap-filter-zona" name="zona">
                                <option value=""><?php esc_html_e('Todas', 'alquipress'); ?></option>
                                <?php foreach ($terms_zona as $term) : ?>
                                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($zona, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="ap-props-filter-field">
                            <label for="ap-filter-habitaciones"><?php esc_html_e('Habitaciones mín.', 'alquipress'); ?></label>
                            <input type="number" id="ap-filter-habitaciones" name="habitaciones_min" min="0" step="1" value="<?php echo $habitaciones_min > 0 ? (int) $habitaciones_min : ''; ?>" placeholder="—">
                        </div>
                        <div class="ap-props-filter-field">
                            <label for="ap-filter-banos"><?php esc_html_e('Baños mín.', 'alquipress'); ?></label>
                            <input type="number" id="ap-filter-banos" name="banos_min" min="0" step="1" value="<?php echo $banos_min > 0 ? (int) $banos_min : ''; ?>" placeholder="—">
                        </div>
                        <?php if (!empty($terms_caracteristicas)) : ?>
                        <div class="ap-props-filter-field ap-props-filter-field-full">
                            <span class="ap-props-filter-label"><?php esc_html_e('Características', 'alquipress'); ?></span>
                            <div class="ap-props-filter-checkboxes">
                                <?php foreach ($terms_caracteristicas as $term) : ?>
                                    <label class="ap-props-filter-checkbox">
                                        <input type="checkbox" name="caracteristicas[]" value="<?php echo esc_attr($term->slug); ?>" <?php echo in_array($term->slug, $caracteristicas, true) ? ' checked' : ''; ?>>
                                        <span><?php echo esc_html($term->name); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ap-props-filter-actions">
                        <button type="submit" class="ap-props-filter-submit button button-primary"><?php esc_html_e('Aplicar filtros', 'alquipress'); ?></button>
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'alquipress-properties', 'status' => $status_filter], admin_url('admin.php'))); ?>" class="ap-props-filter-reset button"><?php esc_html_e('Limpiar', 'alquipress'); ?></a>
                    </div>
                </form>
            </div>

            <div class="ap-props-toolbar">
                <div class="ap-props-toolbar-left">
                    <div class="ap-props-view-toggle">
                        <button type="button" class="ap-props-view-btn ap-props-view-grid active" aria-pressed="true" data-view="grid">
                            <span class="dashicons dashicons-grid-view"></span>
                        </button>
                        <button type="button" class="ap-props-view-btn ap-props-view-list" aria-pressed="false" data-view="list">
                            <span class="dashicons dashicons-list-view"></span>
                        </button>
                        <?php if (!empty($map_properties)): ?>
                        <button type="button" class="ap-props-view-btn ap-props-view-map" aria-pressed="false" data-view="map" title="<?php esc_attr_e('Vista mapa', 'alquipress'); ?>">
                            <span class="dashicons dashicons-location-alt"></span>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ap-props-sort">
                        <select id="ap-props-orderby" class="ap-select-small">
                            <option value="date" <?php selected($orderby, 'date'); ?>><?php esc_html_e('Más recientes', 'alquipress'); ?></option>
                            <option value="price_asc" <?php selected($orderby, 'price_asc'); ?>><?php esc_html_e('Precio: Menor a Mayor', 'alquipress'); ?></option>
                            <option value="price_desc" <?php selected($orderby, 'price_desc'); ?>><?php esc_html_e('Precio: Mayor a Menor', 'alquipress'); ?></option>
                            <option value="capacity_desc" <?php selected($orderby, 'capacity_desc'); ?>><?php esc_html_e('Mayor Capacidad', 'alquipress'); ?></option>
                            <option value="beds_desc" <?php selected($orderby, 'beds_desc'); ?>><?php esc_html_e('Más Habitaciones', 'alquipress'); ?></option>
                            <option value="baths_desc" <?php selected($orderby, 'baths_desc'); ?>><?php esc_html_e('Más Baños', 'alquipress'); ?></option>
                            <option value="rating_desc" <?php selected($orderby, 'rating_desc'); ?>><?php esc_html_e('Mejor valorados', 'alquipress'); ?></option>
                            <option value="title" <?php selected($orderby, 'title'); ?>><?php esc_html_e('Nombre (A-Z)', 'alquipress'); ?></option>
                        </select>
                    </div>

                    <p class="ap-props-showing">
                        <?php
                        printf(
                            /* translators: 1: number shown, 2: total */
                            esc_html__('Mostrando %1$s de %2$s propiedades', 'alquipress'),
                            (int) $showing_count,
                            (int) $total_count
                        );
                        ?>
                    </p>
                </div>
                <div class="ap-props-filter-tabs">
                    <a href="<?php echo esc_url($base_url); ?>" class="ap-props-pill <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                        <span class="ap-props-pill-dot ap-props-pill-dot-muted"></span>
                        <span><?php esc_html_e('Todas', 'alquipress'); ?>: <?php echo (int) $count_all_num; ?></span>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('status', 'active', $base_url)); ?>" class="ap-props-pill ap-props-pill-active <?php echo $status_filter === 'active' ? 'active' : ''; ?>">
                        <span class="ap-props-pill-dot ap-props-pill-dot-success"></span>
                        <span><?php esc_html_e('Activas', 'alquipress'); ?>: <?php echo (int) $count_active; ?></span>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('status', 'maintenance', $base_url)); ?>" class="ap-props-pill ap-props-pill-maint <?php echo $status_filter === 'maintenance' ? 'active' : ''; ?>">
                        <span class="ap-props-pill-dot ap-props-pill-dot-warning"></span>
                        <span><?php esc_html_e('Mantenimiento', 'alquipress'); ?>: <?php echo (int) $count_maintenance; ?></span>
                    </a>
                </div>
            </div>

            <div class="ap-props-grid" data-view="grid">
                <?php if (empty($products)): ?>
                    <div class="ap-props-empty">
                        <span class="dashicons dashicons-building"></span>
                        <p><?php esc_html_e('No hay propiedades que coincidan con el filtro.', 'alquipress'); ?></p>
                        <?php if ($has_active_filters): ?>
                            <a href="<?php echo esc_url($base_url); ?>" class="button"><?php esc_html_e('Limpiar filtros', 'alquipress'); ?></a>
                            <button type="button" class="button button-primary ap-add-property-trigger" style="margin-left:8px;"><?php esc_html_e('Añadir propiedad', 'alquipress'); ?></button>
                        <?php else: ?>
                            <button type="button" class="button button-primary ap-add-property-trigger"><?php esc_html_e('Añadir propiedad', 'alquipress'); ?></button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $post): setup_postdata($post);
                        $product = wc_get_product($post->ID);
                        $edit_url = admin_url('admin.php?page=alquipress-edit-property&post_id=' . (int) $post->ID);
                        $thumb_url = get_the_post_thumbnail_url($post->ID, [400, 300]);
                        $location = $this->get_product_location($post->ID);
                        $beds = $this->get_product_beds($post->ID);
                        $baths = $this->get_product_baths($post->ID);
                        $guests = $this->get_product_guests($post->ID);
                        $price = $product ? $product->get_price() : '';
                        $price_html = $product && $price !== '' ? wc_price($price) : '—';
                        $occupancy = $this->get_product_occupancy_text($post->ID);
                        $is_draft = $post->post_status === 'draft';
                        $rating_value = null;
                        $rating_count = 0;
                        if ($product && method_exists($product, 'get_average_rating')) {
                            $avg = $product->get_average_rating();
                            if (is_numeric($avg) && (float) $avg > 0) {
                                $rating_value = (float) $avg;
                            }
                            if (method_exists($product, 'get_review_count')) {
                                $rating_count = (int) $product->get_review_count();
                            }
                        }
                        ?>
                        <article class="ap-props-card">
                            <div class="ap-props-card-image" style="<?php echo $thumb_url ? 'background-image:url(' . esc_url($thumb_url) . ')' : 'background:#E0EDF8'; ?>">
                                <span class="ap-props-card-badge <?php echo $is_draft ? 'status-draft' : 'status-active'; ?>">
                                    <?php echo $is_draft ? esc_html__('Borrador', 'alquipress') : esc_html__('Activa', 'alquipress'); ?>
                                </span>
                                <a href="<?php echo esc_url($edit_url); ?>" class="ap-props-card-menu" title="<?php esc_attr_e('Editar', 'alquipress'); ?>" aria-label="<?php esc_attr_e('Opciones', 'alquipress'); ?>">
                                    <svg class="ap-props-card-menu-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <circle cx="12" cy="6" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="18" r="2"/>
                                    </svg>
                                </a>
                            </div>
                            <div class="ap-props-card-content">
                                <h3 class="ap-props-card-title">
                                    <a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($post->post_title); ?></a>
                                </h3>
                                <?php if ($location): ?>
                                    <p class="ap-props-card-location">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($location); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="ap-props-card-specs">
                                    <?php if ($beds !== null): ?>
                                        <span class="ap-props-spec" title="<?php esc_attr_e('Habitaciones', 'alquipress'); ?>"><?php echo $this->icon_bed_double(); ?> <span class="ap-props-spec-num"><?php echo (int) $beds; ?></span></span>
                                    <?php endif; ?>
                                    <?php if ($baths !== null): ?>
                                        <span class="ap-props-spec" title="<?php esc_attr_e('Baños', 'alquipress'); ?>"><?php echo $this->icon_bath(); ?> <span class="ap-props-spec-num"><?php echo (int) $baths; ?></span></span>
                                    <?php endif; ?>
                                    <?php if ($guests !== null): ?>
                                        <span class="ap-props-spec" title="<?php esc_attr_e('Personas', 'alquipress'); ?>"><?php echo $this->icon_users(); ?> <span class="ap-props-spec-num"><?php echo (int) $guests; ?></span></span>
                                    <?php endif; ?>
                                    <?php if ($rating_value !== null || $rating_count > 0): ?>
                                        <span class="ap-props-spec ap-props-spec-rating" title="<?php esc_attr_e('Valoración de clientes', 'alquipress'); ?>">
                                            <span class="dashicons dashicons-star-filled"></span>
                                            <span class="ap-props-rating-value"><?php echo $rating_value !== null ? number_format_i18n($rating_value, 1) : '—'; ?></span>
                                            <?php if ($rating_count > 0): ?>
                                                <span class="ap-props-rating-count">(<?php echo (int) $rating_count; ?> <?php echo (int) $rating_count === 1 ? esc_html__('valoración', 'alquipress') : esc_html__('valoraciones', 'alquipress'); ?>)</span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="ap-props-spec ap-props-spec-price"><span class="dashicons dashicons-money-alt"></span> <?php echo wp_kses_post($price_html); ?></span>
                                </div>
                                <div class="ap-props-card-footer">
                                    <span class="ap-props-card-price"><?php echo wp_kses_post($price_html); ?> <span class="ap-props-card-unit">/<?php esc_html_e('noche', 'alquipress'); ?></span></span>
                                    <?php if ($occupancy): ?>
                                        <span class="ap-props-card-occ"><?php echo $this->icon_users(); ?> <?php echo esc_html($occupancy); ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($edit_url); ?>" class="ap-props-card-view"><?php esc_html_e('Ver', 'alquipress'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; wp_reset_postdata(); ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($map_properties)): ?>
            <div class="ap-props-map-wrap" data-view="map" style="display:none;">
                <div id="ap-props-map" style="height: 500px; border-radius: 12px; border: 1px solid #e2e8f0;"></div>
                <p class="ap-props-map-note"><?php echo count($map_properties); ?> <?php esc_html_e('propiedades con ubicación en mapa', 'alquipress'); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($total_pages > 1) : ?>
                <?php
                $paginate_args = ['page' => 'alquipress-properties', 'status' => $status_filter];
                if ($search) $paginate_args['s'] = $search;
                if ($price_min > 0) $paginate_args['precio_min'] = $price_min;
                if ($price_max > 0) $paginate_args['precio_max'] = $price_max;
                if ($poblacion) $paginate_args['poblacion'] = $poblacion;
                if ($zona) $paginate_args['zona'] = $zona;
                if ($habitaciones_min > 0) $paginate_args['habitaciones_min'] = $habitaciones_min;
                if ($banos_min > 0) $paginate_args['banos_min'] = $banos_min;
                if ($orderby !== 'date') $paginate_args['orderby'] = $orderby;
                if (!empty($caracteristicas)) $paginate_args['caracteristicas'] = $caracteristicas;
                $paginate_base = add_query_arg($paginate_args, admin_url('admin.php'));
                $paginate_base = str_replace('%#%', '###PAGE###', $paginate_base);
                ?>
                <nav class="ap-props-pagination" aria-label="<?php esc_attr_e('Paginación', 'alquipress'); ?>">
                    <?php
                    echo paginate_links([
                        'base' => esc_url(str_replace('###PAGE###', '%#%', $paginate_base)),
                        'format' => '&paged=%#%',
                        'current' => max(1, $paged),
                        'total' => $total_pages,
                        'prev_text' => '&larr; ' . __('Anterior', 'alquipress'),
                        'next_text' => __('Siguiente', 'alquipress') . ' &rarr;',
                        'type' => 'list',
                    ]);
                    ?>
                </nav>
            <?php endif; ?>
                </main>
            </div>
        </div>
        <?php
        ?>
        <script>
        (function() {
            var mapProps = <?php echo wp_json_encode(!empty($map_properties) ? $map_properties : []); ?>;
            var grid = document.querySelector('.ap-props-grid');
            var mapWrap = document.querySelector('.ap-props-map-wrap');
            var pagination = document.querySelector('.ap-props-pagination');
            var btns = document.querySelectorAll('.ap-props-view-btn[data-view]');
            var apMap = null;
            function initMap() {
                if (!mapWrap || !mapProps.length || typeof L === 'undefined') return;
                var mapEl = document.getElementById('ap-props-map');
                if (!mapEl || apMap) return;
                apMap = L.map('ap-props-map').setView([mapProps[0].lat, mapProps[0].lng], 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(apMap);
                mapProps.forEach(function(p) {
                    var popupHtml = '<a href="' + p.url + '">' + (p.title_escaped || p.title || '') + '</a>';
                    L.marker([p.lat, p.lng]).addTo(apMap).bindPopup(popupHtml);
                });
                if (mapProps.length > 1) {
                    var bounds = L.latLngBounds(mapProps.map(function(m){ return [m.lat, m.lng]; }));
                    apMap.fitBounds(bounds, { padding: [30, 30] });
                }
            }
            if (grid && btns.length) {
                btns.forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var view = this.getAttribute('data-view');
                        grid.setAttribute('data-view', view);
                        if (grid.style) grid.style.display = (view === 'map') ? 'none' : '';
                        if (mapWrap) mapWrap.style.display = (view === 'map') ? '' : 'none';
                        if (pagination) pagination.style.display = (view === 'map') ? 'none' : '';
                        btns.forEach(function(b) { b.classList.remove('active'); b.setAttribute('aria-pressed', 'false'); });
                        this.classList.add('active'); this.setAttribute('aria-pressed', 'true');
                        if (view === 'map') initMap();
                    });
                });
            }
            var filterToggle = document.getElementById('ap-props-filter-toggle');
            var filterPanel = document.getElementById('ap-props-filter-panel');
            if (filterToggle && filterPanel) {
                filterToggle.setAttribute('aria-expanded', filterPanel.getAttribute('hidden') === null ? 'true' : 'false');
                filterToggle.addEventListener('click', function() {
                    var open = filterPanel.getAttribute('hidden') === null;
                    if (open) {
                        filterPanel.setAttribute('hidden', '');
                        filterToggle.setAttribute('aria-expanded', 'false');
                    } else {
                        filterPanel.removeAttribute('hidden');
                        filterToggle.setAttribute('aria-expanded', 'true');
                    }
                });
            }
        })();

        // Script para la ordenación
        var orderbyEl = document.getElementById('ap-props-orderby');
        if (orderbyEl) {
            orderbyEl.addEventListener('change', function() {
            var url = new URL(window.location);
            url.searchParams.set('orderby', this.value);
            window.location = url.href;
        });
        }
        </script>
        
        <style>
            .ap-props-sort { margin-left: 15px; display: flex; align-items: center; }
            .ap-select-small { border: 1px solid #e2e8f0 !important; border-radius: 6px !important; padding: 4px 24px 4px 8px !important; font-size: 13px !important; background-color: #fff !important; cursor: pointer; }
            .ap-props-showing { margin-left: 20px; color: #64748b; font-size: 13px; }
        </style>
        <?php
    }
}

new Alquipress_Properties_Page();
