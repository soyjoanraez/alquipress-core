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
        if (isset($_POST['alquipress_delete_fake_data']) && check_admin_referer('alquipress_tester_nonce')) {
            $aggressive = !empty($_POST['delete_aggressive']);
            $counts = $this->delete_fake_data($aggressive);
            $message = sprintf(
                '✓ Datos fake eliminados: %d propietarios, %d productos, %d clientes, %d reservas, %d pedidos.',
                (int) $counts['owners'],
                (int) $counts['products'],
                (int) $counts['users'],
                (int) $counts['bookings'],
                (int) $counts['orders']
            );
            add_settings_error(
                'alquipress_tester_messages',
                'alquipress_tester_message',
                $message,
                'success'
            );
            return;
        }

        if (isset($_POST['alquipress_generate_data']) && check_admin_referer('alquipress_tester_nonce')) {
            $type = isset($_POST['generation_type']) ? sanitize_text_field($_POST['generation_type']) : 'all';
            $count = isset($_POST['generation_count']) ? absint($_POST['generation_count']) : 10;

            $this->run_generation($type, $count);

            add_settings_error(
                'alquipress_tester_messages',
                'alquipress_tester_message',
                '✓ Datos generados correctamente.',
                'success'
            );
        }
    }

    public function run_generation($type, $count)
    {
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
            case 'bookings':
                $this->seed_bookings_with_payments($count);
                break;
            case 'payments':
                $this->seed_orders($count);
                break;
            case 'all':
                $this->seed_owners(5);
                $this->seed_products(10);
                $this->seed_guests(10);
                $this->seed_bookings_with_payments(12);
                break;
        }
    }

    public function run_deletion($aggressive = false)
    {
        return $this->delete_fake_data($aggressive);
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
                $this->mark_fake_post($post_id);
                update_field('owner_phone', '+34 600 ' . rand(100000, 999999), $post_id);
                update_field('owner_email_management', strtolower(str_replace(' ', '.', $name)) . '@example.com', $post_id);
                update_field('owner_commission_rate', rand(10, 25), $post_id);
                update_field('owner_iban', 'ES' . rand(10, 99) . ' 2100 ' . rand(1000, 9999) . ' ' . rand(10, 99) . ' ' . rand(1000000000, 9999999999), $post_id);
            }
        }
    }

    private function get_owner_ids()
    {
        $owners = get_posts([
            'post_type' => 'propietario',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids',
        ]);
        return is_array($owners) ? $owners : [];
    }

    private function attach_property_to_owner($owner_id, $property_id)
    {
        if (!$owner_id || !$property_id) {
            return;
        }
        $current = function_exists('get_field') ? get_field('owner_properties', $owner_id) : get_post_meta($owner_id, 'owner_properties', true);
        $current = is_array($current) ? $current : [];
        if (!in_array($property_id, $current, true)) {
            $current[] = $property_id;
        }
        if (function_exists('update_field')) {
            update_field('owner_properties', $current, $owner_id);
        } else {
            update_post_meta($owner_id, 'owner_properties', $current);
        }
        $this->set_property_owner($property_id, $owner_id);
    }

    private function set_property_owner($property_id, $owner_id)
    {
        if (!$owner_id || !$property_id) {
            return;
        }
        if (function_exists('get_field') && function_exists('update_field')) {
            $current = get_field('propietario_asignado', $property_id);
            if (is_array($current)) {
                if (!in_array($owner_id, $current, true)) {
                    $current[] = $owner_id;
                }
                $current = array_values(array_unique(array_map('intval', $current)));
                update_field('propietario_asignado', $current, $property_id);
            } else {
                update_field('propietario_asignado', (int) $owner_id, $property_id);
            }
        } else {
            update_post_meta($property_id, 'propietario_asignado', (int) $owner_id);
        }
    }

    private function ensure_owners($min = 5)
    {
        $owners = $this->get_owner_ids();
        if (count($owners) < $min) {
            $this->seed_owners($min - count($owners));
            $owners = $this->get_owner_ids();
        }
        return $owners;
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
                $this->mark_fake_post($post_id);
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

                $owners = $this->ensure_owners(3);
                if (!empty($owners)) {
                    $owner_id = $owners[array_rand($owners)];
                    $this->attach_property_to_owner($owner_id, $post_id);
                }
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
                $this->mark_fake_user($user_id);
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

    private function get_customer_ids()
    {
        $users = get_users([
            'role' => 'customer',
            'fields' => 'ID',
            'number' => -1,
        ]);
        return is_array($users) ? array_map('intval', $users) : [];
    }

    private function ensure_guests($min = 5)
    {
        $customers = $this->get_customer_ids();
        if (count($customers) < $min) {
            $this->seed_guests($min - count($customers));
            $customers = $this->get_customer_ids();
        }
        return $customers;
    }

    private function create_booking_product($name, $price)
    {
        if (!class_exists('WC_Product_Booking')) {
            return 0;
        }

        $product = new WC_Product_Booking();
        $product->set_name($name);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_regular_price($price);
        $product->set_price($price);
        $product->set_cost(0);
        $product->set_block_cost($price);
        $product->set_duration_type('fixed');
        $product->set_duration_unit('day');
        $product->set_duration(1);
        $product->set_min_date_unit('day');
        $product->set_min_date_value(0);
        $product->set_max_date_unit('month');
        $product->set_max_date_value(12);
        $product->set_calendar_display_mode('always_visible');
        $product->set_virtual(true);
        $product->update_meta_data('_alquipress_fake', 1);
        $product_id = $product->save();

        return $product_id;
    }

    private function get_booking_product_ids($min = 5)
    {
        $product_ids = [];
        $query = new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => ['booking'],
                ],
            ],
        ]);
        if ($query->have_posts()) {
            $product_ids = $query->posts;
        }

        if (count($product_ids) < $min) {
            $needed = $min - count($product_ids);
            $poblaciones = ['Denia', 'Jávea', 'Calpe', 'Moraira', 'Altea'];
            $styles = ['Villa', 'Apartamento', 'Ático', 'Casa de Pueblo', 'Bungalow'];
            $owners = $this->ensure_owners(3);
            for ($i = 0; $i < $needed; $i++) {
                $title = $styles[array_rand($styles)] . ' Booking ' . ($i + 1) . ' - ' . $poblaciones[array_rand($poblaciones)];
                $price = rand(80, 320);
                $product_id = $this->create_booking_product($title, $price);
                if ($product_id) {
                    $product_ids[] = $product_id;
                    if (!empty($owners)) {
                        $owner_id = $owners[array_rand($owners)];
                        $this->attach_property_to_owner($owner_id, $product_id);
                    }
                }
            }
        }

        return $product_ids;
    }

    private function get_first_resource_id($product)
    {
        if (!method_exists($product, 'has_resources') || !$product->has_resources()) {
            return null;
        }
        $resource_ids = $product->get_resource_ids();
        if (empty($resource_ids) || !is_array($resource_ids)) {
            return null;
        }
        return (int) $resource_ids[0];
    }

    private function calculate_booking_cost_for_day($product, $start_timestamp, $duration_days, $resource_id = null)
    {
        if (!class_exists('WC_Bookings_Cost_Calculation')) {
            return 0;
        }

        $persons = [];
        if (method_exists($product, 'has_persons') && $product->has_persons()) {
            if (method_exists($product, 'has_person_types') && $product->has_person_types()) {
                $person_types = $product->get_person_types();
                $min_total = max(1, (int) $product->get_min_persons());
                $total = 0;
                foreach ($person_types as $person_type) {
                    $min = method_exists($person_type, 'get_min') ? (int) $person_type->get_min() : 0;
                    $count = max(0, $min);
                    $persons[$person_type->get_id()] = $count;
                    $total += $count;
                }
                if ($total < $min_total && !empty($persons)) {
                    $first_key = array_key_first($persons);
                    $persons[$first_key] += ($min_total - $total);
                } elseif ($total < $min_total) {
                    $persons[0] = $min_total;
                }
            } else {
                $persons[0] = max(1, (int) $product->get_min_persons());
            }
        }
        $qty = !empty($persons) ? max(1, array_sum($persons)) : 1;

        $data = [
            '_start_date' => $start_timestamp,
            '_end_date' => strtotime('+' . $duration_days . ' days', $start_timestamp),
            '_duration' => $duration_days,
            '_persons' => $persons,
            '_qty' => $qty,
            '_date' => date('Y-m-d', $start_timestamp),
            'date' => date('Y-m-d', $start_timestamp),
            '_time' => '',
            'time' => '',
        ];
        if ($resource_id) {
            $data['_resource_id'] = (int) $resource_id;
        }

        $cost = WC_Bookings_Cost_Calculation::calculate_booking_cost($data, $product);
        if (is_wp_error($cost)) {
            return 0;
        }
        return (float) $cost;
    }

    private function create_booking_for_order($product, $order_id, $order_item_id, $customer_id, $start_timestamp, $duration_days, $status, $resource_id = null)
    {
        if (!class_exists('WC_Booking')) {
            return 0;
        }

        $end_timestamp = strtotime('+' . $duration_days . ' days', $start_timestamp);
        $cost = $this->calculate_booking_cost_for_day($product, $start_timestamp, $duration_days, $resource_id);
        $booking = new WC_Booking([
            'product_id' => $product->get_id(),
            'start_date' => $start_timestamp,
            'end_date' => $end_timestamp,
            'all_day' => true,
            'cost' => $cost,
            'order_id' => $order_id,
            'order_item_id' => $order_item_id,
            'customer_id' => $customer_id,
            'status' => $status,
        ]);
        if ($resource_id) {
            $booking->set_resource_id((int) $resource_id);
        }
        $booking->update_meta_data('_alquipress_fake', 1);
        $booking->set_local_timezone(wc_booking_get_timezone_string());
        $booking->save();
        return $booking->get_id();
    }

    private function seed_orders($count)
    {
        if (!function_exists('wc_create_order')) {
            return;
        }

        $customers = $this->ensure_guests(5);
        $products = $this->get_booking_product_ids(3);
        if (empty($products)) {
            return;
        }

        $statuses = ['completed', 'completed', 'in-progress', 'checkout-review', 'processing', 'deposito-ok', 'pending-checkin', 'pending', 'on-hold'];
        $now = current_time('timestamp');
        $year = (int) date('Y', $now);
        $year_start = strtotime($year . '-01-01 00:00:00');
        $year_end = strtotime($year . '-12-31 23:59:59');
        for ($i = 0; $i < $count; $i++) {
            $product_id = $products[array_rand($products)];
            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }
            $customer_id = $customers[array_rand($customers)];
            $duration = rand(2, 7);
            $start_timestamp = ($i % 3 === 0)
                ? strtotime('midnight', $now) + (rand(-30, 60) * DAY_IN_SECONDS)
                : rand($year_start, $year_end);
            $start_timestamp = strtotime('midnight', max($year_start, min($start_timestamp, $year_end)));
            $end_timestamp = strtotime('+' . $duration . ' days', $start_timestamp);
            $order = wc_create_order(['customer_id' => $customer_id]);
            $order_item_id = $order->add_product($product, 1);
            $this->set_order_customer_details($order, $customer_id);
            $this->set_order_booking_dates($order, $start_timestamp, $end_timestamp);
            $order->set_payment_method('bacs');
            $order->set_payment_method_title('Transferencia bancaria');
            $order->update_meta_data('_alquipress_fake', 1);
            $order->calculate_totals();
            $order->update_meta_data('_apm_booking_total', $order->get_total());
            $order_date = max($year_start, $start_timestamp - (rand(1, 45) * DAY_IN_SECONDS));
            $order->set_date_created($order_date);
            $order->set_status($statuses[array_rand($statuses)]);
            $order->save();
        }
    }

    private function seed_bookings_with_payments($count)
    {
        if (!function_exists('wc_create_order') || !class_exists('WC_Product_Booking')) {
            return;
        }

        $customers = $this->ensure_guests(8);
        $products = $this->get_booking_product_ids(5);
        if (empty($products)) {
            return;
        }

        $order_statuses = ['completed', 'completed', 'in-progress', 'checkout-review', 'processing', 'deposito-ok', 'pending-checkin', 'pending', 'on-hold', 'cancelled'];
        $booking_status_map = [
            'completed' => 'confirmed',
            'processing' => 'confirmed',
            'deposito-ok' => 'confirmed',
            'pending' => 'unpaid',
            'on-hold' => 'pending-confirmation',
            'pending-checkin' => 'confirmed',
            'in-progress' => 'confirmed',
            'checkout-review' => 'confirmed',
            'cancelled' => 'cancelled',
            'failed' => 'cancelled',
        ];
        $now = current_time('timestamp');
        $year = (int) date('Y', $now);
        $year_start = strtotime($year . '-01-01 00:00:00');
        $year_end = strtotime($year . '-12-31 23:59:59');

        for ($i = 0; $i < $count; $i++) {
            $product_id = $products[array_rand($products)];
            $product = get_wc_product_booking($product_id);
            if (!$product || !is_a($product, 'WC_Product_Booking')) {
                continue;
            }

            $customer_id = $customers[array_rand($customers)];
            $duration = $product->is_duration_type('customer') ? rand(1, max(1, (int) $product->get_max_duration())) : max(1, (int) $product->get_duration());
            $start_timestamp = ($i % 3 === 0)
                ? strtotime('midnight', $now) + (rand(-30, 60) * DAY_IN_SECONDS)
                : rand($year_start, $year_end);
            $start_timestamp = strtotime('midnight', max($year_start, min($start_timestamp, $year_end)));
            $end_timestamp = strtotime('+' . $duration . ' days', $start_timestamp);

            $order_status = $order_statuses[array_rand($order_statuses)];
            $booking_status = $booking_status_map[$order_status] ?? 'unpaid';

            $order = wc_create_order(['customer_id' => $customer_id]);
            $order_item_id = $order->add_product($product, 1);
            $this->set_order_customer_details($order, $customer_id);
            $this->set_order_booking_dates($order, $start_timestamp, $end_timestamp);
            $order->set_payment_method('bacs');
            $order->set_payment_method_title('Transferencia bancaria');
            $order->update_meta_data('_alquipress_fake', 1);
            $order->calculate_totals();
            $order->update_meta_data('_apm_booking_total', $order->get_total());
            $order_date = max($year_start, $start_timestamp - (rand(1, 60) * DAY_IN_SECONDS));
            $order->set_date_created($order_date);
            $order->set_status($order_status);
            $order->save();

            $resource_id = $this->get_first_resource_id($product);
            $this->create_booking_for_order($product, $order->get_id(), $order_item_id, $customer_id, $start_timestamp, $duration, $booking_status, $resource_id);
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
                                <option value="all">Todo (Propietarios, Productos, Clientes, Reservas y Pagos)</option>
                                <option value="owners">Solo Propietarios</option>
                                <option value="products">Solo Productos (Alojamientos)</option>
                                <option value="guests">Solo Clientes (Huéspedes)</option>
                                <option value="bookings">Reservas + Pagos</option>
                                <option value="payments">Solo Pagos (Pedidos)</option>
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

            <hr>

            <form method="post" action="">
                <?php wp_nonce_field('alquipress_tester_nonce'); ?>

                <h2 style="margin-top: 10px;">Borrar datos fake</h2>
                <p>Elimina datos de prueba generados por este módulo.</p>

                <label style="display: inline-flex; align-items: center; gap: 8px; margin: 6px 0 12px;">
                    <input type="checkbox" name="delete_aggressive" value="1">
                    <span>Borrado agresivo (incluye registros sin marca pero con emails @example.com)</span>
                </label>

                <p class="submit">
                    <input type="submit" name="alquipress_delete_fake_data" class="button button-secondary button-large"
                        onclick="return confirm('¿Seguro que quieres borrar los datos fake? Esta acción no se puede deshacer.');"
                        value="Borrar Datos Fake">
                </p>
            </form>
        </div>
        <?php
    }

    private function mark_fake_post($post_id)
    {
        if ($post_id) {
            update_post_meta($post_id, '_alquipress_fake', 1);
        }
    }

    private function mark_fake_user($user_id)
    {
        if ($user_id) {
            update_user_meta($user_id, '_alquipress_fake', 1);
        }
    }

    private function set_order_customer_details($order, $customer_id)
    {
        $user = $customer_id ? get_userdata($customer_id) : null;
        if (!$user) {
            return;
        }
        $first = get_user_meta($customer_id, 'first_name', true);
        $last = get_user_meta($customer_id, 'last_name', true);
        $order->set_billing_first_name($first ?: $user->display_name);
        $order->set_billing_last_name($last ?: '');
        $order->set_billing_email($user->user_email);
    }

    private function set_order_booking_dates($order, $start_timestamp, $end_timestamp)
    {
        $order->update_meta_data('_booking_checkin_date', date('Y-m-d', $start_timestamp));
        $order->update_meta_data('_booking_checkout_date', date('Y-m-d', $end_timestamp));
        $order->update_meta_data('_booking_start', $start_timestamp);
        $order->update_meta_data('_booking_end', $end_timestamp);
    }

    private function get_posts_by_meta($post_type, $meta_key, $meta_value)
    {
        return get_posts([
            'post_type' => $post_type,
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => $meta_key,
                    'value' => $meta_value,
                    'compare' => '=',
                ],
            ],
        ]);
    }

    private function get_booking_ids_by_product_ids($product_ids)
    {
        $product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
        if (empty($product_ids)) {
            return [];
        }
        if (class_exists('WC_Booking_Data_Store')) {
            return WC_Booking_Data_Store::get_booking_ids_by([
                'object_id' => $product_ids,
                'object_type' => 'product',
                'limit' => -1,
            ]);
        }
        $booking_ids = [];
        $chunks = array_chunk($product_ids, 50);
        foreach ($chunks as $chunk) {
            $ids = get_posts([
                'post_type' => 'wc_booking',
                'post_status' => 'any',
                'numberposts' => -1,
                'fields' => 'ids',
                'meta_query' => [
                    [
                        'key' => '_booking_product_id',
                        'value' => $chunk,
                        'compare' => 'IN',
                    ],
                ],
            ]);
            if (is_array($ids)) {
                $booking_ids = array_merge($booking_ids, $ids);
            }
        }
        return $booking_ids;
    }

    private function delete_fake_data($aggressive = false)
    {
        $counts = [
            'owners' => 0,
            'products' => 0,
            'users' => 0,
            'bookings' => 0,
            'orders' => 0,
        ];

        $order_ids = [];
        if (function_exists('wc_get_orders')) {
            $order_ids = wc_get_orders([
                'limit' => -1,
                'return' => 'ids',
                'type' => 'shop_order',
                'meta_key' => '_alquipress_fake',
                'meta_value' => 1,
            ]);
        } else {
            $order_ids = $this->get_posts_by_meta('shop_order', '_alquipress_fake', 1);
        }

        $product_ids = $this->get_posts_by_meta('product', '_alquipress_fake', 1);
        if ($aggressive) {
            $legacy_products = get_posts([
                'post_type' => 'product',
                'post_status' => 'any',
                'numberposts' => -1,
                'fields' => 'ids',
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'referencia_interna',
                        'value' => 'ALQ-',
                        'compare' => 'LIKE',
                    ],
                    [
                        'key' => 'licencia_turistica',
                        'value' => 'VT-',
                        'compare' => 'LIKE',
                    ],
                ],
            ]);
            $product_ids = array_merge($product_ids, $legacy_products);
        }

        $booking_ids = $this->get_posts_by_meta('wc_booking', '_alquipress_fake', 1);
        $booking_ids = array_merge($booking_ids, $this->get_booking_ids_by_product_ids($product_ids));
        if (!empty($order_ids)) {
            $booking_from_orders = [];
            $chunks = array_chunk(array_values(array_unique($order_ids)), 50);
            foreach ($chunks as $chunk) {
                $ids = get_posts([
                    'post_type' => 'wc_booking',
                    'post_status' => 'any',
                    'numberposts' => -1,
                    'fields' => 'ids',
                    'meta_query' => [
                        [
                            'key' => '_booking_order_id',
                            'value' => $chunk,
                            'compare' => 'IN',
                        ],
                    ],
                ]);
                if (is_array($ids)) {
                    $booking_from_orders = array_merge($booking_from_orders, $ids);
                }
            }
            $booking_ids = array_merge($booking_ids, $booking_from_orders);
        }
        foreach (array_unique($booking_ids) as $booking_id) {
            if (wp_delete_post($booking_id, true)) {
                $counts['bookings']++;
            }
        }

        foreach (array_unique($order_ids) as $order_id) {
            if (wp_delete_post($order_id, true)) {
                $counts['orders']++;
            }
        }

        foreach (array_unique($product_ids) as $product_id) {
            if (wp_delete_post($product_id, true)) {
                $counts['products']++;
            }
        }

        $owner_ids = $this->get_posts_by_meta('propietario', '_alquipress_fake', 1);
        if ($aggressive) {
            $legacy_owners = get_posts([
                'post_type' => 'propietario',
                'post_status' => 'any',
                'numberposts' => -1,
                'fields' => 'ids',
                'meta_query' => [
                    [
                        'key' => 'owner_email_management',
                        'value' => '@example.com',
                        'compare' => 'LIKE',
                    ],
                ],
            ]);
            $owner_ids = array_merge($owner_ids, $legacy_owners);
        }
        foreach (array_unique($owner_ids) as $owner_id) {
            if (wp_delete_post($owner_id, true)) {
                $counts['owners']++;
            }
        }

        $user_ids = [];
        $users = get_users([
            'meta_key' => '_alquipress_fake',
            'meta_value' => 1,
            'fields' => 'ID',
            'number' => -1,
        ]);
        if (is_array($users)) {
            $user_ids = array_map('intval', $users);
        }
        if ($aggressive) {
            $legacy_users = get_users([
                'search' => '*@example.com',
                'search_columns' => ['user_email'],
                'fields' => 'ID',
                'number' => -1,
            ]);
            if (is_array($legacy_users)) {
                $user_ids = array_merge($user_ids, array_map('intval', $legacy_users));
            }
        }
        foreach (array_unique($user_ids) as $user_id) {
            if (wp_delete_user($user_id)) {
                $counts['users']++;
            }
        }

        return $counts;
    }
}

new Alquipress_Tester();
