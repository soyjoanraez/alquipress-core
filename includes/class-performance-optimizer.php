<?php
/**
 * Optimizador de Rendimiento para ALQUIPRESS
 * Caché, queries optimizadas y mejoras de performance
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Performance_Optimizer
{

    private static $instance = null;

    /**
     * Singleton
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        // Limpiar caché cuando se actualicen datos relevantes
        add_action('save_post', [$this, 'clear_reports_cache']);
        add_action('woocommerce_order_status_changed', [$this, 'clear_reports_cache']);
        add_action('profile_update', [$this, 'clear_preferences_cache']);
        
        // Invalidación específica por tipo de dato
        add_action('save_post_product', [$this, 'clear_properties_cache'], 10, 2);
        add_action('save_post_shop_order', [$this, 'clear_orders_cache'], 10, 2);
        add_action('woocommerce_order_status_changed', [$this, 'clear_dashboard_cache']);

        // Optimizar carga de scripts
        add_action('admin_enqueue_scripts', [$this, 'optimize_script_loading'], 1);
    }

    // ========== Caché de Informes ==========

    /**
     * Obtener estadísticas de preferencias (con caché)
     */
    public static function get_cached_preferences_stats()
    {
        $cache_key = 'alquipress_preferences_stats';
        $stats = get_transient($cache_key);

        if (false === $stats) {
            $stats = self::calculate_preferences_stats();
            set_transient($cache_key, $stats, HOUR_IN_SECONDS);
        }

        return $stats;
    }

    /**
     * Calcular estadísticas de preferencias
     */
    private static function calculate_preferences_stats()
    {
        $preferences = [
            'piscina' => 0,
            'wifi' => 0,
            'parking' => 0,
            'mascotas' => 0,
            'aire_acondicionado' => 0,
            'cocina' => 0,
            'terraza' => 0,
            'vistas_mar' => 0,
            'cerca_playa' => 0,
            'zona_tranquila' => 0
        ];

        $users = get_users(['role' => 'customer', 'fields' => ['ID']]);
        $total_users = count($users);

        if ($total_users === 0) {
            return $preferences;
        }

        foreach ($users as $user) {
            $user_prefs = get_user_meta($user->ID, 'guest_preferences', true);
            if (is_array($user_prefs)) {
                foreach ($user_prefs as $pref) {
                    if (isset($preferences[$pref])) {
                        $preferences[$pref]++;
                    }
                }
            }
        }

        // Convertir a porcentajes
        foreach ($preferences as $key => $count) {
            $preferences[$key] = [
                'count' => $count,
                'percentage' => ($count / $total_users) * 100
            ];
        }

        return $preferences;
    }

    /**
     * Obtener top clientes (con caché)
     */
    public static function get_cached_top_clients($year, $limit = 5)
    {
        $cache_key = 'alquipress_top_clients_' . $year . '_' . $limit;
        $clients = get_transient($cache_key);

        if (false === $clients) {
            $clients = self::calculate_top_clients($year, $limit);
            set_transient($cache_key, $clients, HOUR_IN_SECONDS * 6); // 6 horas
        }

        return $clients;
    }

    /**
     * Calcular top clientes
     */
    private static function calculate_top_clients($year, $limit)
    {
        global $wpdb;

        $start_date = $year . '-01-01 00:00:00';
        $end_date = $year . '-12-31 23:59:59';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                pm_customer.meta_value as customer_id,
                COUNT(DISTINCT p.ID) as order_count,
                SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_spent,
                MAX(p.post_date) as last_order_date
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = '_customer_user'
            INNER JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-in-progress', 'wc-checkout-review')
            AND p.post_date >= %s
            AND p.post_date <= %s
            AND pm_customer.meta_value REGEXP '^[0-9]+$'
            AND CAST(pm_customer.meta_value AS UNSIGNED) > 0
            AND pm_total.meta_value REGEXP '^[0-9]+\.?[0-9]*$'
            GROUP BY customer_id
            ORDER BY total_spent DESC
            LIMIT %d",
            $start_date,
            $end_date,
            $limit
        ));

        $clients = [];
        foreach ($results as $row) {
            $user = get_userdata($row->customer_id);
            $clients[] = [
                'id' => $row->customer_id,
                'name' => $user ? $user->display_name : 'Usuario #' . $row->customer_id,
                'email' => $user ? $user->user_email : '',
                'order_count' => intval($row->order_count),
                'total_spent' => floatval($row->total_spent),
                'last_order_date' => $row->last_order_date
            ];
        }

        return $clients;
    }

    /**
     * Obtener top propiedades (con caché)
     */
    public static function get_cached_top_properties($year, $limit = 5)
    {
        $cache_key = 'alquipress_top_properties_' . $year . '_' . $limit;
        $properties = get_transient($cache_key);

        if (false === $properties) {
            $properties = self::calculate_top_properties($year, $limit);
            set_transient($cache_key, $properties, HOUR_IN_SECONDS * 6); // 6 horas
        }

        return $properties;
    }

    /**
     * Calcular top propiedades
     */
    private static function calculate_top_properties($year, $limit)
    {
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'in-progress', 'checkout-review'],
            'date_created' => $year . '-01-01...' . $year . '-12-31',
        ]);

        $properties_data = [];
        $days_in_year = ($year == date('Y')) ? date('z') : 365;

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if (!$product)
                    continue;

                $product_id = $product->get_id();

                if (!isset($properties_data[$product_id])) {
                    $properties_data[$product_id] = [
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'total_revenue' => 0,
                        'total_bookings' => 0,
                        'total_nights' => 0
                    ];
                }

                $properties_data[$product_id]['total_revenue'] += $order->get_total();
                $properties_data[$product_id]['total_bookings']++;

                $checkin = $order->get_meta('_booking_checkin_date');
                $checkout = $order->get_meta('_booking_checkout_date');

                if ($checkin && $checkout) {
                    $diff = strtotime($checkout) - strtotime($checkin);
                    $nights = floor($diff / (60 * 60 * 24));
                    $properties_data[$product_id]['total_nights'] += $nights;
                }
            }
        }

        // Calcular tasa de ocupación
        foreach ($properties_data as $id => &$data) {
            $data['occupancy_rate'] = ($data['total_nights'] / $days_in_year) * 100;
        }

        // Ordenar por ingresos
        usort($properties_data, function ($a, $b) {
            return $b['total_revenue'] - $a['total_revenue'];
        });

        return array_slice($properties_data, 0, $limit);
    }

    /**
     * Obtener ingresos mensuales (con caché)
     */
    public static function get_cached_monthly_revenue($year)
    {
        $cache_key = 'alquipress_monthly_revenue_' . $year;
        $revenue = get_transient($cache_key);

        if (false === $revenue) {
            $revenue = self::calculate_monthly_revenue($year);
            set_transient($cache_key, $revenue, DAY_IN_SECONDS); // 1 día
        }

        return $revenue;
    }

    /**
     * Calcular ingresos mensuales
     */
    private static function calculate_monthly_revenue($year)
    {
        global $wpdb;

        $start_date = $year . '-01-01 00:00:00';
        $end_date = $year . '-12-31 23:59:59';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                MONTH(p.post_date) as month,
                SUM(CAST(pm.meta_value AS DECIMAL(10,2))) as total
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-in-progress', 'wc-checkout-review')
            AND p.post_date >= %s
            AND p.post_date <= %s
            AND pm.meta_value REGEXP '^[0-9]+\.?[0-9]*$'
            GROUP BY MONTH(p.post_date)
            ORDER BY month ASC",
            $start_date,
            $end_date
        ));

        $monthly_data = array_fill(1, 12, 0);

        foreach ($results as $row) {
            $monthly_data[intval($row->month)] = floatval($row->total);
        }

        return $monthly_data;
    }

    /**
     * Limpiar caché de informes (optimizado)
     */
    public function clear_reports_cache($post_id = null)
    {
<<<<<<< HEAD
        // Limpiar todos los transients relacionados con informes
        self::clear_cache_group('reports');
    }

    /**
     * Limpiar todo el caché de Alquipress
     * 
     * @return int Número de transients eliminados
     */
    public static function clear_all_alquipress_cache()
    {
=======
        // Solo limpiar caché si es relevante
        if (!$post_id) {
            return;
        }

        $post_type = get_post_type($post_id);
        $current_year = date('Y');

        // Si es un pedido, limpiar solo reportes relacionados
        if ($post_type === 'shop_order') {
            $order = wc_get_order($post_id);

            if ($order) {
                $order_year = date('Y', strtotime($order->get_date_created()));

                // Limpiar transients específicos del año del pedido
                delete_transient('alquipress_monthly_revenue_' . $order_year);
                delete_transient('alquipress_top_clients_' . $order_year . '_5');
                delete_transient('alquipress_top_properties_' . $order_year . '_5');

                // Si es del año actual, limpiar también ese año
                if ($order_year != $current_year) {
                    delete_transient('alquipress_monthly_revenue_' . $current_year);
                    delete_transient('alquipress_top_clients_' . $current_year . '_5');
                    delete_transient('alquipress_top_properties_' . $current_year . '_5');
                }
            }
        }

        // Si es un producto (propiedad), limpiar reportes de propiedades
        if ($post_type === 'product') {
            delete_transient('alquipress_top_properties_' . $current_year . '_5');
        }
    }

    /**
     * Limpiar TODO el caché (solo llamar manualmente)
     */
    public function clear_all_cache()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

>>>>>>> main
        global $wpdb;

        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_alquipress_%'
            OR option_name LIKE '_transient_timeout_alquipress_%'"
        );

<<<<<<< HEAD
        // Limpiar también el caché de Property Helper
        if (class_exists('Alquipress_Property_Helper')) {
            Alquipress_Property_Helper::clear_cache();
        }

        return (int) $deleted;
    }

    /**
     * Limpiar caché por grupo
     * 
     * @param string $group Grupo de caché: 'reports', 'dashboard', 'properties', 'orders'
     * @return int Número de transients eliminados
     */
    public static function clear_cache_group($group)
    {
        global $wpdb;

        // Validar grupo usando Alquipress_Config si está disponible
        if (class_exists('Alquipress_Config') && !Alquipress_Config::is_valid_cache_group($group)) {
            return 0;
        }

        $patterns = [
            'reports' => [
                '_transient_alquipress_reports_%',
                '_transient_alquipress_preferences_stats',
                '_transient_alquipress_monthly_revenue_%',
            ],
            'dashboard' => [
                '_transient_alquipress_dashboard_%',
                '_transient_alquipress_widget_%',
                '_transient_alquipress_activity_%',
            ],
            'properties' => [
                '_transient_alquipress_occupied_count_%',
                '_transient_alquipress_property_%',
            ],
            'orders' => [
                '_transient_alquipress_order_%',
                '_transient_alquipress_bookings_%',
            ],
        ];

        if (!isset($patterns[$group])) {
            return 0;
        }

        $deleted = 0;
        foreach ($patterns[$group] as $pattern) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options}
                    WHERE option_name LIKE %s
                    OR option_name LIKE %s",
                    $pattern,
                    str_replace('_transient_', '_transient_timeout_', $pattern)
                )
            );
            $deleted += (int) $result;
        }
=======
        error_log('ALQUIPRESS: Limpiados ' . $deleted . ' transients');
>>>>>>> main

        return $deleted;
    }

    /**
     * Limpiar caché de preferencias
     */
    public function clear_preferences_cache($user_id)
    {
        delete_transient('alquipress_preferences_stats');
    }

    /**
     * Limpiar caché de propiedades cuando se actualiza una propiedad
     */
    public function clear_properties_cache($post_id, $post)
    {
        if ($post->post_type === 'product') {
            self::clear_cache_group('properties');
            // Limpiar también el caché del Property Helper
            if (class_exists('Alquipress_Property_Helper')) {
                Alquipress_Property_Helper::clear_cache($post_id);
            }
        }
    }

    /**
     * Limpiar caché de órdenes cuando se actualiza una orden
     */
    public function clear_orders_cache($post_id, $post)
    {
        if ($post->post_type === 'shop_order') {
            self::clear_cache_group('orders');
            self::clear_cache_group('dashboard');
        }
    }

    /**
     * Limpiar caché del dashboard cuando cambia el estado de una orden
     */
    public function clear_dashboard_cache()
    {
        self::clear_cache_group('dashboard');
    }

    // ========== Optimización de Scripts ==========

    /**
     * Optimizar carga de scripts según página
     */
    public function optimize_script_loading($hook)
    {
        // No cargar assets en páginas donde no se necesitan
        $allowed_pages = [
            'index.php',                          // Dashboard
            'edit.php',                           // Listados
            'post.php',                           // Edición
            'post-new.php',                       // Nuevo
            'user-edit.php',                      // Editar usuario
            'users.php',                          // Listado usuarios
            'toplevel_page_alquipress-settings',  // Settings
            'alquipress_page_alquipress-pipeline', // Pipeline
            'alquipress_page_alquipress-reports'  // Informes
        ];

        // Solo cargar jQuery UI si es necesario
        if (!in_array($hook, $allowed_pages)) {
            wp_dequeue_script('jquery-ui-core');
            wp_dequeue_script('jquery-ui-datepicker');
        }
    }

    // ========== Helpers Públicos ==========

    /**
     * Obtener contador de check-ins hoy (optimizado)
     */
    public static function get_checkins_today_count()
    {
        $cache_key = 'alquipress_checkins_today_' . date('Y-m-d');
        $count = wp_cache_get($cache_key);

        if (false === $count) {
            global $wpdb;
            $today = date('Y-m-d');

            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                WHERE meta_key = '_booking_checkin_date'
                AND meta_value = %s",
                $today
            ));

            wp_cache_set($cache_key, $count, '', HOUR_IN_SECONDS);
        }

        return intval($count);
    }

    /**
     * Obtener contador de check-outs hoy (optimizado)
     */
    public static function get_checkouts_today_count()
    {
        $cache_key = 'alquipress_checkouts_today_' . date('Y-m-d');
        $count = wp_cache_get($cache_key);

        if (false === $count) {
            global $wpdb;
            $today = date('Y-m-d');

            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                WHERE meta_key = '_booking_checkout_date'
                AND meta_value = %s",
                $today
            ));

            wp_cache_set($cache_key, $count, '', HOUR_IN_SECONDS);
        }

        return intval($count);
    }

    /**
     * Invalidar caché diario (cron)
     */
    public static function clear_daily_cache()
    {
        delete_transient('alquipress_preferences_stats');

        // Limpiar caché de WordPress
        wp_cache_flush();
    }

    /**
     * OPTIMIZACIÓN BD: Crear índices para búsquedas de reservas
     * Debe ejecutarse al activar el plugin o manualmente desde herramientas
     */
    public static function install_booking_indexes()
    {
        global $wpdb;

        $indices = [
            'idx_booking_checkin' => '_booking_checkin_date',
            'idx_booking_checkout' => '_booking_checkout_date',
            'idx_apm_booking_total' => '_apm_booking_total'
        ];

        foreach ($indices as $index_name => $meta_key) {
            // Comprobar si el índice existe (forma ligera)
            $exists = $wpdb->get_results("SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = '{$index_name}'");
            
            if (empty($exists)) {
                // Crear índice en meta_value especificando longitud para TEXT
                // NOTA: meta_value es LONGTEXT, requiere prefijo de longitud (ej. 20 chars es suficiente para fechas)
                $wpdb->query("CREATE INDEX {$index_name} ON {$wpdb->postmeta} (meta_key(30), meta_value(20))");
            }
        }
    }
}

// Inicializar
Alquipress_Performance_Optimizer::get_instance();

// Ejecutar indexación en admin init (una sola vez, verificando opción)
add_action('admin_init', function() {
    if (!get_option('alquipress_db_indexes_created')) {
        Alquipress_Performance_Optimizer::install_booking_indexes();
        update_option('alquipress_db_indexes_created', true);
    }
});

// Programar limpieza diaria de caché
if (!wp_next_scheduled('alquipress_clear_daily_cache')) {
    wp_schedule_event(time(), 'daily', 'alquipress_clear_daily_cache');
}
add_action('alquipress_clear_daily_cache', ['Alquipress_Performance_Optimizer', 'clear_daily_cache']);
