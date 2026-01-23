<?php
/**
 * Gestión de Iconos FontAwesome para Taxonomía Características
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Feature_Icons
{

    private $icon_map = [
        // Cocina
        'Cocina Equipada' => 'fa-kitchen-set',
        'Lavavajillas' => 'fa-sink',
        'Horno' => 'fa-fire-burner',
        'Microondas' => 'fa-microwave',
        'Cafetera Nespresso' => 'fa-mug-hot',
        'Tostadora' => 'fa-bread-slice',
        'Nevera Combi' => 'fa-temperature-low',
        // Clima
        'Aire Acondicionado' => 'fa-fan',
        'Calefacción' => 'fa-temperature-arrow-up',
        'Chimenea' => 'fa-fire',
        // Tech
        'WiFi Fibra' => 'fa-wifi',
        'Smart TV' => 'fa-tv',
        'TV Satélite' => 'fa-satellite-dish',
        // Exterior
        'Piscina Privada' => 'fa-water-ladder',
        'Piscina Comunitaria' => 'fa-person-swimming',
        'Barbacoa' => 'fa-fire-flame-simple',
        'Jardín' => 'fa-tree',
        'Terraza' => 'fa-umbrella-beach',
        'Vistas al Mar' => 'fa-water',
        'Primera Línea' => 'fa-wave-square',
        // Servicios
        'Parking Privado' => 'fa-square-parking',
        'Ascensor' => 'fa-elevator',
        'Admite Mascotas' => 'fa-paw',
        'Lavadora' => 'fa-jug-detergent',
        'Secadora' => 'fa-heat',
        'Cuna de viaje' => 'fa-baby-carriage',
        'Plancha' => 'fa-iron'
    ];

    public function __construct()
    {
        // Formularios de taxonomía
        add_action('caracteristicas_add_form_fields', [$this, 'add_icon_field']);
        add_action('caracteristicas_edit_form_fields', [$this, 'edit_icon_field']);

        // Guardar campo
        add_action('created_caracteristicas', [$this, 'save_icon_field']);
        add_action('edited_caracteristicas', [$this, 'save_icon_field']);

        // Columna en admin
        add_filter('manage_edit-caracteristicas_columns', [$this, 'add_icon_column']);
        add_filter('manage_caracteristicas_custom_column', [$this, 'populate_icon_column'], 10, 3);

        // FontAwesome CDN
        add_action('admin_enqueue_scripts', [$this, 'enqueue_fontawesome']);

        // Auto-popular iconos
        add_action('init', [$this, 'populate_default_icons'], 100);
    }

    /**
     * Enqueue FontAwesome 6 CDN
     */
    public function enqueue_fontawesome()
    {
        $screen = get_current_screen();

        // Solo cargar en páginas de características
        if ($screen && (
            $screen->id === 'edit-caracteristicas' ||
            $screen->taxonomy === 'caracteristicas'
        )) {
            wp_enqueue_style(
                'fontawesome-6',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
                [],
                '6.5.1'
            );

            wp_enqueue_style(
                'alquipress-icons',
                ALQUIPRESS_URL . 'includes/modules/taxonomies/assets/icons.css',
                ['fontawesome-6'],
                ALQUIPRESS_VERSION
            );
        }
    }

    /**
     * Campo de icono en formulario de creación
     */
    public function add_icon_field()
    {
        ?>
        <div class="form-field term-icon-wrap">
            <label for="feature_icon">Icono FontAwesome</label>
            <input type="text" name="feature_icon" id="feature_icon" placeholder="fa-wifi" class="feature-icon-input" />
            <p class="description">
                Código del icono de <a href="https://fontawesome.com/search?o=r&m=free" target="_blank">FontAwesome 6 Free</a>
                (ej: <code>fa-wifi</code>, <code>fa-tv</code>, <code>fa-paw</code>)
            </p>
        </div>
        <?php
    }

    /**
     * Campo de icono en formulario de edición
     */
    public function edit_icon_field($term)
    {
        $icon = get_term_meta($term->term_id, 'feature_icon', true);
        ?>
        <tr class="form-field term-icon-wrap">
            <th scope="row">
                <label for="feature_icon">Icono FontAwesome</label>
            </th>
            <td>
                <input type="text" name="feature_icon" id="feature_icon" value="<?php echo esc_attr($icon); ?>"
                    placeholder="fa-wifi" class="regular-text feature-icon-input" />
                <p class="description">
                    Código del icono de <a href="https://fontawesome.com/search?o=r&m=free" target="_blank">FontAwesome 6
                        Free</a>
                    (ej: <code>fa-wifi</code>, <code>fa-tv</code>, <code>fa-paw</code>)
                </p>

                <?php if ($icon): ?>
                    <div style="margin-top: 15px; padding: 15px; background: #f0f0f1; border-radius: 4px; display: inline-block;">
                        <strong>Vista previa:</strong>
                        <i class="fa-solid <?php echo esc_attr($icon); ?>"
                            style="font-size: 32px; color: #2271b1; margin-left: 15px;"></i>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Guardar el campo de icono
     */
    public function save_icon_field($term_id)
    {
        if (isset($_POST['feature_icon'])) {
            $icon = sanitize_text_field($_POST['feature_icon']);

            // Limpiar: remover prefijos innecesarios
            $icon = str_replace(['fas ', 'fa-solid ', 'far ', 'fa-regular '], '', $icon);

            // Asegurar que empiece con fa-
            if (!empty($icon) && strpos($icon, 'fa-') !== 0) {
                $icon = 'fa-' . $icon;
            }

            update_term_meta($term_id, 'feature_icon', $icon);
        }
    }

    /**
     * Añadir columna de icono en listado
     */
    public function add_icon_column($columns)
    {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['icon'] = 'Icono';
        $new_columns['name'] = $columns['name'];

        // Añadir resto de columnas
        foreach ($columns as $key => $value) {
            if ($key !== 'cb' && $key !== 'name') {
                $new_columns[$key] = $value;
            }
        }

        return $new_columns;
    }

    /**
     * Poblar columna de icono
     */
    public function populate_icon_column($content, $column_name, $term_id)
    {
        if ($column_name === 'icon') {
            $icon = get_term_meta($term_id, 'feature_icon', true);
            if ($icon) {
                return '<i class="fa-solid ' . esc_attr($icon) . '" style="font-size: 22px; color: #2271b1;" title="' . esc_attr($icon) . '"></i>';
            }
            return '<span style="color: #999;" title="Sin icono">-</span>';
        }
        return $content;
    }

    /**
     * Auto-popular iconos por defecto
     */
    public function populate_default_icons()
    {
        // Solo ejecutar una vez
        if (get_option('alquipress_icons_populated')) {
            return;
        }

        $terms = get_terms([
            'taxonomy' => 'caracteristicas',
            'hide_empty' => false
        ]);

        if (is_wp_error($terms)) {
            return;
        }

        $updated = 0;
        foreach ($terms as $term) {
            if (isset($this->icon_map[$term->name])) {
                update_term_meta($term->term_id, 'feature_icon', $this->icon_map[$term->name]);
                $updated++;
            }
        }

        // Marcar como completado
        update_option('alquipress_icons_populated', true);
    }
}

new Alquipress_Feature_Icons();
