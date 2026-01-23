<?php
/**
 * Módulo: Alquipress Tester
 * Genera datos de prueba para el sistema.
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Tester
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_tester_menu']);
        add_action('admin_init', [$this, 'handle_generation']);
    }

    public function add_tester_menu()
    {
        add_submenu_page(
            'alquipress-settings',
            'Generador de Pruebas',
            'Generador de Pruebas',
            'manage_options',
            'alquipress-tester',
            [$this, 'render_tester_page']
        );
    }

    public function handle_generation()
    {
        if (isset($_POST['alquipress_generate_data']) && check_admin_referer('alquipress_tester_nonce')) {
            $type = $_POST['generation_type'] ?? 'all';
            $count = intval($_POST['generation_count'] ?? 10);

            switch ($type) {
                case 'owners':
                    $this->seed_owners($count);
                    break;
                case 'products':
                    $this->seed_products($count);
                    break;
                case 'guests':
                    $this->seed_guests($count);
                    break;
                case 'all':
                    $this->seed_owners(5);
                    $this->seed_products(10);
                    $this->seed_guests(10);
                    break;
            }

            add_settings_error(
                'alquipress_tester_messages',
                'alquipress_tester_message',
                '✓ Datos generados correctamente.',
                'success'
            );
        }
    }

    private function seed_owners($count)
    {
        $names = ['Juan Pérez', 'María García', 'Carlos Rodríguez', 'Ana Martínez', 'Luis López', 'Elena Sánchez', 'Pedro Gómez', 'Laura Fernández'];

        for ($i = 0; $i < $count; $i++) {
            $name = $names[array_rand($names)] . ' ' . ($i + 1);
            $post_id = wp_insert_post([
                'post_type' => 'propietario',
                'post_title' => $name,
                'post_status' => 'publish',
            ]);

            if ($post_id) {
                update_field('owner_phone', '+34 600 ' . rand(100000, 999999), $post_id);
                update_field('owner_email_management', strtolower(str_replace(' ', '.', $name)) . '@example.com', $post_id);
                update_field('owner_commission_rate', rand(10, 25), $post_id);
                update_field('owner_iban', 'ES' . rand(10, 99) . ' 2100 ' . rand(1000, 9999) . ' ' . rand(10, 99) . ' ' . rand(1000000000, 9999999999), $post_id);
            }
        }
    }

    private function seed_products($count)
    {
        $poblaciones = ['Denia', 'Jávea', 'Calpe', 'Moraira', 'Altea'];
        $zonas = ['Centro', 'Playa', 'Montaña', 'Puerto', 'Casco Antiguo'];
        $styles = ['Villa', 'Apartamento', 'Ático', 'Casa de Pueblo', 'Bungalow'];

        // Get caracteristicas terms
        $caracteristicas = get_terms(['taxonomy' => 'caracteristicas', 'hide_empty' => false]);
        $caracteristicas_ids = wp_list_pluck($caracteristicas, 'term_id');

        for ($i = 0; $i < $count; $i++) {
            $poblacion = $poblaciones[array_rand($poblaciones)];
            $zona = $zonas[array_rand($zonas)];
            $style = $styles[array_rand($styles)];
            $title = $style . ' ' . $zona . ' en ' . $poblacion . ' #' . ($i + 1);

            $post_id = wp_insert_post([
                'post_type' => 'product',
                'post_title' => $title,
                'post_status' => 'publish',
            ]);

            if ($post_id) {
                // Set taxonomies
                wp_set_object_terms($post_id, $poblacion, 'poblacion');
                wp_set_object_terms($post_id, $zona, 'zona');

                if (!empty($caracteristicas_ids)) {
                    $random_features = (array) array_rand(array_flip($caracteristicas_ids), rand(3, 8));
                    wp_set_object_terms($post_id, $random_features, 'caracteristicas');
                }

                // ACF Fields
                update_field('licencia_turistica', 'VT-' . rand(10000, 99999) . '-A', $post_id);
                update_field('referencia_interna', 'ALQ-' . rand(100, 999), $post_id);
                update_field('superficie_m2', rand(50, 250), $post_id);
                update_field('distancia_playa', rand(100, 2000), $post_id);
                update_field('distancia_centro', rand(100, 3000), $post_id);
                update_field('hora_checkin', '16:00', $post_id);
                update_field('hora_checkout', '10:00', $post_id);
                update_field('fianza_texto', '300€ mediante retención', $post_id);

                // Habitaciones (Repeater)
                $habs = [];
                for ($j = 1; $j <= rand(1, 4); $j++) {
                    $habs[] = [
                        'nombre_hab' => 'Habitación ' . $j,
                        'tipo_cama' => 'matrimonio',
                        'bano_en_suite' => rand(0, 1)
                    ];
                }
                update_field('distribucion_habitaciones', $habs, $post_id);

                // WooCommerce specific
                update_post_meta($post_id, '_price', rand(80, 500));
                update_post_meta($post_id, '_regular_price', rand(80, 500));
                wp_set_object_terms($post_id, 'simple', 'product_type');
            }
        }
    }

    private function seed_guests($count)
    {
        $first_names = ['Antonio', 'Jose', 'Manuel', 'Francisco', 'David', 'Juan', 'Jose Antonio', 'Javier', 'Jose Luis', 'Daniel', 'Maria Carmen', 'Maria', 'Carmen', 'Ana Maria', 'Josefa', 'Maria Pilar', 'Isabel', 'Laura', 'Maria Dolores', 'Maria Teresa'];
        $last_names = ['Garcia', 'Rodriguez', 'Gonzalez', 'Fernandez', 'Lopez', 'Martinez', 'Sanchez', 'Perez', 'Gomez', 'Martin'];

        for ($i = 0; $i < $count; $i++) {
            $fname = $first_names[array_rand($first_names)];
            $lname = $last_names[array_rand($last_names)];
            $username = strtolower(substr($fname, 0, 1) . $lname . rand(10, 99));
            $email = $username . '@example.com';

            $user_id = wp_create_user($username, 'password123', $email);

            if (!is_wp_error($user_id)) {
                wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $fname,
                    'last_name' => $lname,
                    'display_name' => $fname . ' ' . $lname,
                    'role' => 'customer'
                ]);

                $statuses = ['standard', 'vip', 'blacklist'];
                update_field('guest_status', $statuses[array_rand($statuses)], 'user_' . $user_id);
                update_field('guest_rating', rand(1, 5), 'user_' . $user_id);
            }
        }
    }

    public function render_tester_page()
    {
        ?>
        <div class="wrap"
            style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-top: 20px;">
            <h1><span class="dashicons dashicons-rest-api"></span> Generador de Datos de Prueba</h1>
            <p>Usa esta herramienta para poblar el sistema con datos ficticios y probar las funcionalidades.</p>

            <hr>

            <?php settings_errors('alquipress_tester_messages'); ?>

            <form method="post" action="">
                <?php wp_nonce_field('alquipress_tester_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="generation_type">¿Qué quieres generar?</label></th>
                        <td>
                            <select name="generation_type" id="generation_type" style="min-width: 200px;">
                                <option value="all">Todo (Propietarios, Productos y Clientes)</option>
                                <option value="owners">Solo Propietarios</option>
                                <option value="products">Solo Productos (Alojamientos)</option>
                                <option value="guests">Solo Clientes (Huéspedes)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="generation_count">Cantidad</label></th>
                        <td>
                            <input type="number" name="generation_count" id="generation_count" value="10" min="1" max="100">
                            <p class="description">Número de elementos a generar por cada tipo seleccionado.</p>
                        </td>
                    </tr>
                </table>

                <div
                    style="margin-top: 20px; padding: 20px; background: #fff8e5; border-left: 4px solid #ffb900; color: #856404;">
                    <p><strong>Aviso:</strong> Esta acción creará múltiples registros en la base de datos. Se recomienda usar
                        solo en entornos de desarrollo.</p>
                </div>

                <p class="submit">
                    <input type="submit" name="alquipress_generate_data" class="button button-primary button-large"
                        value="Generar Datos Ahora">
                </p>
            </form>
        </div>
        <?php
    }
}

new Alquipress_Tester();
