<?php
/**
 * Módulo: Taxonomías Personalizadas
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Taxonomies
{

    public function __construct()
    {
        add_action('init', [$this, 'register_taxonomies']);
        add_action('init', [$this, 'populate_caracteristicas'], 99);
        add_action('init', [$this, 'populate_marina_alta'], 99);
        add_action('init', [$this, 'populate_tipo_vivienda'], 99);

        // Validar y normalizar coordenadas GPS al guardar (hook nativo, sin ACF)
        add_action('save_post_product', [$this, 'validate_coordenadas_gps'], 25);

        // Campos nativos para términos de "caracteristicas" (icono_clase)
        add_action('caracteristicas_edit_form_fields',  [$this, 'render_icono_clase_field'], 10, 1);
        add_action('caracteristicas_add_form_fields',   [$this, 'render_icono_clase_field_add'], 10, 1);
        add_action('edited_caracteristicas',            [$this, 'save_icono_clase_field'], 10, 2);
        add_action('create_caracteristicas',            [$this, 'save_icono_clase_field'], 10, 2);

        // Cargar meta box nativo de productos y save handler
        require_once dirname(__FILE__) . '/class-product-fields.php';
        new Alquipress_Product_Fields();
    }

    /**
     * Renderizar campo icono_clase en edición de término "caracteristicas".
     */
    public function render_icono_clase_field(\WP_Term $term): void
    {
        $icon = (string) get_term_meta($term->term_id, 'icono_clase', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="icono_clase"><?php esc_html_e('Clase de icono (CSS)', 'alquipress'); ?></label></th>
            <td>
                <?php wp_nonce_field('alquipress_term_meta_save', 'alquipress_term_meta_nonce'); ?>
                <input type="text" id="icono_clase" name="icono_clase" value="<?php echo esc_attr($icon); ?>" style="width:220px" placeholder="dashicons-star-filled" />
                <p class="description"><?php esc_html_e('Clase CSS del icono (ej: dashicons-star-filled, fa-home).', 'alquipress'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Renderizar campo icono_clase al añadir nuevo término.
     */
    public function render_icono_clase_field_add(string $taxonomy): void
    {
        ?>
        <div class="form-field">
            <?php wp_nonce_field('alquipress_term_meta_save', 'alquipress_term_meta_nonce'); ?>
            <label for="icono_clase"><?php esc_html_e('Clase de icono (CSS)', 'alquipress'); ?></label>
            <input type="text" id="icono_clase" name="icono_clase" value="" placeholder="dashicons-star-filled" />
            <p><?php esc_html_e('Clase CSS del icono (ej: dashicons-star-filled, fa-home).', 'alquipress'); ?></p>
        </div>
        <?php
    }

    /**
     * Guardar campo icono_clase al guardar/crear un término.
     */
    public function save_icono_clase_field(int $term_id, int $tt_id): void
    {
        if (!isset($_POST['icono_clase'])) {
            return;
        }
        if (isset($_POST['alquipress_term_meta_nonce']) &&
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alquipress_term_meta_nonce'])), 'alquipress_term_meta_save')) {
            return;
        }
        $icon = sanitize_text_field(wp_unslash($_POST['icono_clase']));
        update_term_meta($term_id, 'icono_clase', $icon);
    }

    public function register_taxonomies()
    {
        // Población (Jerárquica)
        register_taxonomy('poblacion', 'product', [
            'label' => 'Población',
            'labels' => [
                'name' => 'Poblaciones',
                'singular_name' => 'Población',
                'search_items' => 'Buscar Poblaciones',
                'all_items' => 'Todas las Poblaciones',
                'parent_item' => 'Población Padre',
                'parent_item_colon' => 'Población Padre:',
                'edit_item' => 'Editar Población',
                'update_item' => 'Actualizar Población',
                'add_new_item' => 'Añadir Nueva Población',
                'new_item_name' => 'Nombre de Nueva Población',
                'menu_name' => 'Población',
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'poblacion'],
            'show_in_rest' => true,
        ]);

        // Zona (No jerárquica)
        register_taxonomy('zona', 'product', [
            'label' => 'Zona / Barrio',
            'labels' => [
                'name' => 'Zonas',
                'singular_name' => 'Zona',
                'search_items' => 'Buscar Zonas',
                'all_items' => 'Todas las Zonas',
                'edit_item' => 'Editar Zona',
                'update_item' => 'Actualizar Zona',
                'add_new_item' => 'Añadir Nueva Zona',
                'new_item_name' => 'Nombre de Nueva Zona',
                'menu_name' => 'Zonas',
            ],
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'zona'],
            'show_in_rest' => true,
        ]);

        // Características (No jerárquica)
        register_taxonomy('caracteristicas', 'product', [
            'label' => 'Características',
            'labels' => [
                'name' => 'Características',
                'singular_name' => 'Característica',
                'search_items' => 'Buscar Características',
                'all_items' => 'Todas las Características',
                'edit_item' => 'Editar Característica',
                'update_item' => 'Actualizar Característica',
                'add_new_item' => 'Añadir Nueva Característica',
                'new_item_name' => 'Nombre de Nueva Característica',
                'menu_name' => 'Características',
            ],
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'caracteristicas'],
            'show_in_rest' => true,
        ]);
    }

    public function populate_caracteristicas()
    {
        // Solo ejecutar una vez
        if (get_option('alquipress_caracteristicas_populated')) {
            return;
        }

        $caracteristicas = [
            // Cocina
            'Cocina Equipada',
            'Lavavajillas',
            'Horno',
            'Microondas',
            'Cafetera Nespresso',
            'Tostadora',
            'Nevera Combi',
            // Clima
            'Aire Acondicionado',
            'Calefacción',
            'Chimenea',
            // Tech
            'WiFi Fibra',
            'Smart TV',
            'TV Satélite',
            // Exterior
            'Piscina Privada',
            'Piscina Comunitaria',
            'Barbacoa',
            'Jardín',
            'Terraza',
            'Vistas al Mar',
            'Primera Línea',
            // Servicios
            'Parking Privado',
            'Ascensor',
            'Admite Mascotas',
            'Lavadora',
            'Secadora',
            'Cuna de viaje',
            'Plancha'
        ];

        foreach ($caracteristicas as $item) {
            if (!term_exists($item, 'caracteristicas')) {
                wp_insert_term($item, 'caracteristicas');
            }
        }

        update_option('alquipress_caracteristicas_populated', true);
    }

    public function populate_marina_alta()
    {
        // Solo ejecutar una vez
        if (get_option('alquipress_marina_alta_populated')) {
            return;
        }

        // Crear Alicante como provincia padre
        $alicante_id = null;
        if (!term_exists('Alicante', 'poblacion')) {
            $result = wp_insert_term('Alicante', 'poblacion');
            if (!is_wp_error($result)) {
                $alicante_id = $result['term_id'];
            }
        } else {
            $term = get_term_by('name', 'Alicante', 'poblacion');
            $alicante_id = $term ? $term->term_id : null;
        }

        // Poblaciones de la Marina Alta
        if ($alicante_id) {
            $poblaciones = [
                'Dénia',
                'Jávea (Xàbia)',
                'Calpe (Calp)',
                'Altea',
                'Benissa',
                'Teulada-Moraira',
                'Benidorm',
                'Pedreguer',
                'Gata de Gorgos',
                'Beniarbeig',
                'Els Poblets',
                'Ondara',
                'Pego',
                'Vergel',
                'El Verger',
                'Jesús Pobre'
            ];

            foreach ($poblaciones as $poblacion) {
                if (!term_exists($poblacion, 'poblacion')) {
                    wp_insert_term($poblacion, 'poblacion', ['parent' => $alicante_id]);
                }
            }
        }

        // Zonas/Barrios
        $zonas = [
            'Centro',
            'Playa',
            'Puerto',
            'Casco Antiguo',
            'Residencial',
            'Montaña',
            'Las Rotas',
            'Les Marines',
            'La Marineta Casiana',
            'Les Deveses',
            'Arenal',
            'Puerto de Jávea',
            'Cabo de la Nao',
            'Granadella',
            'Levante',
            'La Fossa',
            'Peñón de Ifach',
            'Altea Hills',
            'Pueblo de Altea',
            'Cala Moraira',
            'El Portet',
            'Benissa Costa',
            'Golf',
            'Vista Mar',
            'Primera Línea'
        ];

        foreach ($zonas as $zona) {
            if (!term_exists($zona, 'zona')) {
                wp_insert_term($zona, 'zona');
            }
        }

        update_option('alquipress_marina_alta_populated', true);
    }

    public function populate_tipo_vivienda()
    {
        $types = apply_filters('alquipress_tipo_vivienda_list', [
            'Villa',
            'Apartamento',
            'Ático',
            'Casa de Pueblo',
            'Bungalow',
            'Chalet',
            'Casa',
            'Piso',
            'Estudio',
            'Dúplex',
            'Tríplex',
            'Loft',
            'Casa adosada',
            'Pareado',
            'Casa rural',
            'Casa rústica',
            'Masía',
            'Cortijo',
            'Casona',
            'Finca rústica',
            'Planta baja'
        ]);

        $parent_name = apply_filters('alquipress_tipo_vivienda_parent', false);
        $parent_id = 0;

        if ($parent_name) {
            $parent_slug = sanitize_title($parent_name);
            $existing_parent = term_exists($parent_slug, 'product_cat');
            if (!$existing_parent) {
                $result = wp_insert_term($parent_name, 'product_cat', ['slug' => $parent_slug]);
                if (!is_wp_error($result)) {
                    $parent_id = (int) $result['term_id'];
                }
            } else {
                $parent_id = is_array($existing_parent) ? (int) $existing_parent['term_id'] : (int) $existing_parent;
            }
        }

        $hash = md5(wp_json_encode([$types, $parent_name]));
        $stored_hash = get_option('alquipress_tipo_vivienda_populated_hash');
        if ($stored_hash === $hash) {
            return;
        }

        foreach ((array) $types as $type) {
            $type = trim((string) $type);
            if ($type === '') {
                continue;
            }

            if (!term_exists($type, 'product_cat')) {
                $args = [];
                if ($parent_id) {
                    $args['parent'] = $parent_id;
                }
                wp_insert_term($type, 'product_cat', $args);
            }
        }

        update_option('alquipress_tipo_vivienda_populated_hash', $hash);
    }

    /**
     * Valida y normaliza el campo de coordenadas GPS al guardar.
     * Se engancha a save_post_product (priority 25, después del save handler de Alquipress_Product_Fields).
     *
     * @param int $post_id ID del producto.
     */
    public function validate_coordenadas_gps(int $post_id): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Obtener el valor actual del campo (ya guardado por Alquipress_Product_Fields)
        $coordenadas = get_post_meta($post_id, 'coordenadas_gps', true);
        if (!is_array($coordenadas)) {
            $coordenadas = [];
        }

        // Si no hay coordenadas, no hacer nada
        if (empty($coordenadas)) {
            return;
        }

        // Normalizar el formato del campo
        // ACF google_map puede devolver array con 'lat', 'lng', 'address'
        // Asegurarse de que tenga el formato correcto
        $normalized = [];

        // Si es un array asociativo con lat/lng
        if (is_array($coordenadas)) {
            // Formato estándar de ACF google_map
            if (isset($coordenadas['lat']) && isset($coordenadas['lng'])) {
                $normalized = [
                    'lat' => (float) $coordenadas['lat'],
                    'lng' => (float) $coordenadas['lng'],
                ];
                
                // Preservar address si existe
                if (isset($coordenadas['address'])) {
                    $normalized['address'] = sanitize_text_field($coordenadas['address']);
                }
            }
            // Formato alternativo (latitud/longitud como claves diferentes)
            elseif (isset($coordenadas['latitude']) && isset($coordenadas['longitude'])) {
                $normalized = [
                    'lat' => (float) $coordenadas['latitude'],
                    'lng' => (float) $coordenadas['longitude'],
                ];
                
                if (isset($coordenadas['address'])) {
                    $normalized['address'] = sanitize_text_field($coordenadas['address']);
                }
            }
        }

        // Si se normalizó correctamente y es diferente al original, actualizar
        if (!empty($normalized) && $normalized !== $coordenadas) {
            update_post_meta($post_id, 'coordenadas_gps', $normalized);
        }
    }
}

new Alquipress_Taxonomies();

// Cargar gestión de iconos FontAwesome
require_once dirname(__FILE__) . '/icon-selector.php';
