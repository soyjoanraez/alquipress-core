<?php
/**
 * Módulo: SEO Master & Renombrado
 * 
 * Implementa la arquitectura de URLs personalizada, Schema VacationRental,
 * optimizaciones de WPO y tracking de GTM.
 */

if (!defined('ABSPATH'))
    exit;

// Verificar que ACF esté disponible (opcional para algunas funciones)
if (!function_exists('get_field')) {
    alquipress_log('SEO Master: ACF no está disponible, algunas funciones limitadas');
}

class Alquipress_SEO_Master
{

    public function __construct()
    {
        // 1. URLs y Renombrado
        add_filter('woocommerce_register_post_type_product', [$this, 'customize_product_cpt']);
        add_action('init', [$this, 'custom_tax_rewrites'], 10);
        add_action('admin_menu', [$this, 'rename_admin_menu_labels'], 999);
        add_filter('gettext', [$this, 'rename_woocommerce_strings'], 20, 3);

        // 2. Schema.org
        add_filter('wpseo_schema_graph_pieces', [$this, 'schema_vacation_rental'], 11, 2);

        // 3. WPO & Imágenes
        add_filter('wp_lazy_loading_enabled', '__return_true');
        add_filter('wp_get_attachment_image_attributes', [$this, 'auto_alt_text'], 10, 3);

        // 4. Tracking GTM
        add_action('wp_footer', [$this, 'track_search_events']);

        // 5. Breadcrumbs
        add_action('astra_entry_before', [$this, 'render_seo_breadcrumbs']);

        // 6. Canonical Dinámico
        add_action('wp_head', [$this, 'custom_canonical'], 1);

        // 7. Integración Meta Datos ACF
        add_filter('wpseo_title', [$this, 'custom_seo_title']);
        add_filter('wpseo_metadesc', [$this, 'custom_seo_description']);
    }

    /**
     * Personaliza el post type 'product' (Inmuebles + URL /alquiler-vacacional/)
     */
    public function customize_product_cpt($args)
    {
        $labels = [
            'name' => 'Inmuebles',
            'singular_name' => 'Inmueble',
            'menu_name' => 'Inmuebles',
            'add_new' => 'Añadir Inmueble',
            'add_new_item' => 'Añadir Nuevo Inmueble',
            'edit_item' => 'Editar Inmueble',
            'all_items' => 'Todos los Inmuebles',
        ];

        $args['labels'] = array_merge($args['labels'], $labels);
        $args['rewrite']['slug'] = 'alquiler-vacacional';
        $args['has_archive'] = 'alquiler-vacacional';

        return $args;
    }

    /**
     * Reglas de reescritura para taxonomías con el prefijo /alquiler-vacacional/
     */
    public function custom_tax_rewrites()
    {
        // Población
        add_rewrite_rule(
            'alquiler-vacacional/poblacion/([^/]+)/?$',
            'index.php?poblacion=$matches[1]',
            'top'
        );

        // Zona/Barrio
        add_rewrite_rule(
            'alquiler-vacacional/zona/([^/]+)/?$',
            'index.php?zona=$matches[1]',
            'top'
        );

        // Características
        add_rewrite_rule(
            'alquiler-vacacional/caracteristica/([^/]+)/?$',
            'index.php?caracteristicas=$matches[1]',
            'top'
        );
    }

    /**
     * Schema.org: Cambiar Product a VacationRental
     */
    public function schema_vacation_rental($pieces, $context)
    {
        if (!is_product())
            return $pieces;

        global $product;
        $post_id = $product->get_id();

        foreach ($pieces as &$piece) {
            if (isset($piece['@type']) && $piece['@type'] === 'Product') {
                $piece['@type'] = 'VacationRental';

                // Datos de tiempo
                $piece['checkinTime'] = get_field('hora_checkin', $post_id) ?: '15:00';
                $piece['checkoutTime'] = get_field('hora_checkout', $post_id) ?: '11:00';

                // GPS
                $coords = get_field('coordenadas_gps', $post_id);
                if ($coords) {
                    $piece['geo'] = [
                        '@type' => 'GeoCoordinates',
                        'latitude' => $coords['lat'],
                        'longitude' => $coords['lng']
                    ];
                }

                // Dirección
                $poblacion = wp_get_post_terms($post_id, 'poblacion', ['fields' => 'names']);
                if (!empty($poblacion)) {
                    $piece['address'] = [
                        '@type' => 'PostalAddress',
                        'addressLocality' => $poblacion[0],
                        'addressCountry' => 'ES'
                    ];
                }

                // Habitaciones y Superficie
                $habitaciones = get_field('distribucion_habitaciones', $post_id);
                if ($habitaciones)
                    $piece['numberOfRooms'] = count($habitaciones);

                $superficie = get_field('superficie_m2', $post_id);
                if ($superficie) {
                    $piece['floorSize'] = [
                        '@type' => 'QuantitativeValue',
                        'value' => $superficie,
                        'unitCode' => 'MTK'
                    ];
                }

                // Amenities
                $caract = wp_get_post_terms($post_id, 'caracteristicas', ['fields' => 'names']);
                if (!empty($caract)) {
                    $piece['amenityFeature'] = array_map(function ($name) {
                        return ['@type' => 'LocationFeatureSpecification', 'name' => $name];
                    }, $caract);
                }
            }
        }
        return $pieces;
    }

    /**
     * ALT text dinámico
     */
    public function auto_alt_text($attr, $attachment, $size)
    {
        if (empty($attr['alt'])) {
            $post_id = get_the_ID();
            if (get_post_type($post_id) === 'product') {
                $poblacion = wp_get_post_terms($post_id, 'poblacion', ['fields' => 'names']);
                $attr['alt'] = get_the_title($post_id) . (!empty($poblacion) ? ' en ' . $poblacion[0] : '');
            }
        }
        return $attr;
    }

    /**
     * Tracking GTM: Búsquedas y Filtros
     */
    public function track_search_events()
    {
        if (is_search() || is_tax(['poblacion', 'zona', 'caracteristicas'])) {
            $term = get_search_query() ?: get_queried_object()->name;
            ?>
            <script>
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    'event': 'property_search',
                    'search_term': '<?php echo esc_js($term); ?>',
                    'search_type': '<?php echo is_search() ? 'keyword' : 'filter'; ?>'
                        });
            </script>
            <?php
        }
    }

    /**
     * Breadcrumbs con Schema para Inmuebles
     */
    public function render_seo_breadcrumbs()
    {
        if (!is_singular('product'))
            return;

        $post_id = get_the_ID();
        $poblacion = wp_get_post_terms($post_id, 'poblacion');

        echo '<nav aria-label="breadcrumb" style="margin-bottom: 20px;">';
        echo '<ol itemscope itemtype="https://schema.org/BreadcrumbList" style="list-style:none; padding:0; display:flex; gap:10px; font-size:0.9rem; color:#666;">';

        // Inicio
        $this->breadcrumb_item(1, 'Inicio', home_url());
        echo ' <span>/</span> ';

        // Población
        $pos = 2;
        if (!empty($poblacion)) {
            $this->breadcrumb_item($pos++, $poblacion[0]->name, get_term_link($poblacion[0]));
            echo ' <span>/</span> ';
        }

        // Actual
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo '<span itemprop="name">' . get_the_title() . '</span>';
        echo '<meta itemprop="position" content="' . $pos . '" />';
        echo '</li>';

        echo '</ol></nav>';
    }

    private function breadcrumb_item($pos, $name, $url)
    {
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo '<a itemprop="item" href="' . esc_url($url) . '"><span itemprop="name">' . esc_html($name) . '</span></a>';
        echo '<meta itemprop="position" content="' . $pos . '" />';
        echo '</li>';
    }

    /**
     * Canonical corregido para taxonomías
     */
    public function custom_canonical()
    {
        if (is_tax(['poblacion', 'zona', 'caracteristicas'])) {
            $term = get_queried_object();
            $canonical = get_term_link($term);
            if (!is_wp_error($canonical)) {
                echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
            }
        }
    }

    /**
     * Sobrescribir Título y Descripción SEO con campos ACF
     */
    public function custom_seo_title($title)
    {
        if (is_singular('product')) {
            $custom = get_field('seo_title_override');
            return $custom ?: $title;
        }
        return $title;
    }

    public function custom_seo_description($desc)
    {
        if (is_singular('product')) {
            $custom = get_field('seo_description_override');
            return $custom ?: $desc;
        }
        return $desc;
    }

    /**
     * Renombrado de Admin UI (Inmuebles)
     */
    public function rename_admin_menu_labels()
    {
        global $menu, $submenu;
        foreach ($menu as $key => $item) {
            if ($item[0] === 'Productos' || $item[0] === 'Inmuebles') {
                $menu[$key][0] = 'Inmuebles';
            }
        }
        if (isset($submenu['edit.php?post_type=product'])) {
            foreach ($submenu['edit.php?post_type=product'] as $key => $item) {
                if ($item[0] === 'Todos los productos')
                    $submenu['edit.php?post_type=product'][$key][0] = 'Todos los inmuebles';
                if ($item[0] === 'Añadir nuevo')
                    $submenu['edit.php?post_type=product'][$key][0] = 'Añadir inmueble';
            }
        }
    }

    public function rename_woocommerce_strings($translated, $text, $domain)
    {
        if ($domain === 'woocommerce') {
            switch ($text) {
                case 'Product':
                    return 'Inmueble';
                case 'Products':
                    return 'Inmuebles';
                case 'Related products':
                    return 'Inmuebles relacionados';
            }
        }
        return $translated;
    }
}

new Alquipress_SEO_Master();
