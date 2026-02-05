<?php
/**
 * Clase de Datos para Dashboard Widgets
 * Contiene toda la lógica de obtención de datos para el dashboard
 * 
 * @package Alquipress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Dashboard_Data
{
    /**
     * Obtener IDs de reservas con check-in en una fecha específica
     * 
     * Consulta la base de datos para encontrar todas las órdenes que tienen
     * una fecha de check-in igual a la fecha proporcionada.
     * 
     * @param string $date Fecha en formato Y-m-d (ej: "2026-02-05")
     * @return array Array de IDs de órdenes (post_id)
     * @since 1.0.0
     * @example
     * $checkins = Alquipress_Dashboard_Data::get_bookings_by_checkin_date('2026-02-05');
     * // Retorna: [123, 456, 789] (IDs de órdenes con check-in ese día)
     */
    public static function get_bookings_by_checkin_date($date)
    {
        global $wpdb;

        $order_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_booking_checkin_date'
            AND meta_value = %s",
            $date
        ));

        return $order_ids;
    }

    /**
     * Obtener IDs de reservas con check-out en una fecha específica
     * 
     * Consulta la base de datos para encontrar todas las órdenes que tienen
     * una fecha de check-out igual a la fecha proporcionada.
     * 
     * @param string $date Fecha en formato Y-m-d (ej: "2026-02-05")
     * @return array Array de IDs de órdenes (post_id)
     * @since 1.0.0
     * @example
     * $checkouts = Alquipress_Dashboard_Data::get_bookings_by_checkout_date('2026-02-05');
     * // Retorna: [234, 567] (IDs de órdenes con check-out ese día)
     */
    public static function get_bookings_by_checkout_date($date)
    {
        global $wpdb;

        $order_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_booking_checkout_date'
            AND meta_value = %s",
            $date
        ));

        return $order_ids;
    }

    /**
     * Obtener ingresos totales entre dos fechas
     * 
     * Calcula la suma de ingresos de pedidos en estados pagados/completados.
     * Prioriza el valor real de la reserva (_apm_booking_total) si existe,
     * de lo contrario usa el total del pedido de WooCommerce (_order_total).
     * Esto soluciona el problema de pagos escalonados donde WC solo ve el depósito.
     * 
     * @param string $start_date Fecha de inicio en formato Y-m-d (ej: "2026-02-01")
     * @param string $end_date Fecha de fin en formato Y-m-d (ej: "2026-02-28")
     * @return float Total de ingresos en euros (0.00 si no hay ingresos)
     * @since 1.0.0
     * @example
     * $revenue = Alquipress_Dashboard_Data::get_revenue_between_dates('2026-02-01', '2026-02-28');
     * // Retorna: 12500.50 (total de ingresos del mes)
     */
    public static function get_revenue_between_dates($start_date, $end_date)
    {
        global $wpdb;

        // Optimización: Usar el valor real de la reserva (Alquipress) si existe,
        // de lo contrario usar el total del pedido de WooCommerce.
        // Esto soluciona el problema de los pagos escalonados donde WC solo ve el depósito.
        
        $sql = "
            SELECT SUM(COALESCE(CAST(pm_real.meta_value AS DECIMAL(12,2)), CAST(pm_wc.meta_value AS DECIMAL(12,2))))
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_wc ON p.ID = pm_wc.post_id AND pm_wc.meta_key = '_order_total'
            LEFT JOIN {$wpdb->postmeta} pm_real ON p.ID = pm_real.post_id AND pm_real.meta_key = '_apm_booking_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-deposito-ok', 'wc-in-progress')
            AND p.post_date >= %s
            AND p.post_date <= %s
        ";

        $end_date_full = $end_date . ' 23:59:59';
        $total = $wpdb->get_var($wpdb->prepare($sql, $start_date, $end_date_full));

        return (float) $total;
    }

    /**
     * Obtener ingresos agrupados por estado de pedido entre dos fechas
     * 
     * Calcula los ingresos totales para cada estado de pedido (completed, processing, etc.)
     * dentro del rango de fechas especificado. Solo incluye estados con ingresos > 0.
     * 
     * @param string $start_date Fecha de inicio en formato Y-m-d (ej: "2026-02-01")
     * @param string $end_date Fecha de fin en formato Y-m-d (ej: "2026-02-28")
     * @return array Array asociativo con estructura ['estado' => total_float]
     * @since 1.0.0
     * @example
     * $revenue_by_status = Alquipress_Dashboard_Data::get_revenue_by_status('2026-02-01', '2026-02-28');
     * // Retorna: ['completed' => 10000.00, 'processing' => 2500.50]
     */
    public static function get_revenue_by_status($start_date, $end_date)
    {
        global $wpdb;

        $statuses = ['completed', 'processing', 'deposito-ok', 'in-progress'];
        $revenue_by_status = [];
        $end_date_full = $end_date . ' 23:59:59';

        foreach ($statuses as $status) {
            $status_key = 'wc-' . $status;
            
            $sql = "
                SELECT SUM(COALESCE(CAST(pm_real.meta_value AS DECIMAL(12,2)), CAST(pm_wc.meta_value AS DECIMAL(12,2))))
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm_wc ON p.ID = pm_wc.post_id AND pm_wc.meta_key = '_order_total'
                LEFT JOIN {$wpdb->postmeta} pm_real ON p.ID = pm_real.post_id AND pm_real.meta_key = '_apm_booking_total'
                WHERE p.post_type = 'shop_order'
                AND p.post_status = %s
                AND p.post_date >= %s
                AND p.post_date <= %s
            ";

            $total = $wpdb->get_var($wpdb->prepare($sql, $status_key, $start_date, $end_date_full));

            if ($total > 0) {
                $revenue_by_status[$status] = (float) $total;
            }
        }

        return $revenue_by_status;
    }

    /**
     * Contar propiedades ocupadas en una fecha específica
     * 
     * Determina cuántas propiedades tienen reservas activas en la fecha proporcionada.
     * Una propiedad está ocupada si tiene una reserva donde checkin <= fecha <= checkout.
     * Los resultados se cachean para mejorar el rendimiento.
     * 
     * @param string $date Fecha en formato Y-m-d (ej: "2026-02-05")
     * @return int Número de propiedades ocupadas (0 si ninguna)
     * @since 1.0.0
     * @example
     * $occupied = Alquipress_Dashboard_Data::get_occupied_properties_count('2026-02-05');
     * // Retorna: 15 (15 propiedades tienen reservas activas ese día)
     */
    public static function get_occupied_properties_count($date)
    {
        global $wpdb;

        $cache_key = class_exists('Alquipress_Config') 
            ? Alquipress_Config::get_cache_key_with_date('occupied_count', $date)
            : 'alquipress_occupied_count_' . $date;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return (int) $cached;
        }

        // Buscar reservas activas en esta fecha
        $occupied_properties = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT pm1.post_id, pm2.meta_value as product_id
            FROM {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            INNER JOIN {$wpdb->postmeta} pm3 ON pm1.post_id = pm3.post_id
            WHERE pm1.meta_key = '_booking_checkin_date'
            AND pm2.meta_key = '_booking_product_id'
            AND pm3.meta_key = '_booking_checkout_date'
            AND pm1.meta_value <= %s
            AND pm3.meta_value >= %s",
            $date,
            $date
        ));

        $count = count($occupied_properties);
        $cache_duration = class_exists('Alquipress_Config') 
            ? Alquipress_Config::get_cache_duration('properties')
            : HOUR_IN_SECONDS;
        set_transient($cache_key, $count, $cache_duration);

        return $count;
    }

    /**
     * Contar propietarios sin IBAN configurado
     * 
     * Busca propietarios publicados que no tienen el campo IBAN configurado
     * (campo vacío o no existe). Utiliza WP_Query optimizado con meta_query.
     * 
     * @return int Número de propietarios sin IBAN (0 si todos tienen IBAN)
     * @since 1.0.0
     * @example
     * $missing_iban = Alquipress_Dashboard_Data::get_owners_without_iban();
     * // Retorna: 3 (3 propietarios necesitan configurar su IBAN)
     */
    public static function get_owners_without_iban()
    {
        // Optimización: Usar meta_query en lugar de iterar PHP
        $query = new WP_Query([
            'post_type' => 'propietario',
            'post_status' => 'publish',
            'posts_per_page' => 1, // Solo necesitamos saber si hay, pero para contar necesitamos count
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'owner_iban',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => 'owner_iban',
                    'value' => '',
                    'compare' => '='
                ]
            ]
        ]);

        return $query->found_posts;
    }

    /**
     * Contar pedidos por estado
     * 
     * Cuenta el número total de pedidos en un estado específico.
     * Compatible con HPOS (High-Performance Order Storage) y el sistema legacy de posts.
     * 
     * @param string $status Estado del pedido sin prefijo 'wc-' (ej: "completed", "pending")
     * @return int Número de pedidos en ese estado (0 si no hay pedidos)
     * @since 1.0.0
     * @example
     * $count = Alquipress_Dashboard_Data::count_orders_by_status('completed');
     * // Retorna: 42 (42 pedidos completados)
     */
    public static function count_orders_by_status($status)
    {
        global $wpdb;

        $status_key = 'wc-' . $status;

        // Para HPOS (High-Performance Order Storage)
        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') &&
            \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders WHERE status = %s",
                $status_key
            ));
        } else {
            // Legacy: posts table
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status = %s",
                $status_key
            ));
        }

        return (int) $count;
    }

    /**
     * Contar reservas activas (pedidos en estados de reserva)
     * 
     * Cuenta las reservas que están activas hoy, es decir, pedidos en estados
     * de reserva (processing, deposito-ok, in-progress, completed) que tienen
     * una fecha de check-in <= hoy <= fecha de check-out.
     * 
     * @return int Número de reservas activas hoy (0 si no hay reservas activas)
     * @since 1.0.0
     * @example
     * $active = Alquipress_Dashboard_Data::get_active_bookings_count();
     * // Retorna: 8 (8 reservas están activas hoy)
     */
    public static function get_active_bookings_count()
    {
        global $wpdb;
        $today = current_time('Y-m-d');

        // Estados que consideramos activos
        $statuses = ['wc-completed', 'wc-processing', 'wc-deposito-ok', 'wc-in-progress'];
        $status_string = "'" . implode("','", $statuses) . "'";

        // Query optimizada: Busca pedidos que tengan checkin <= hoy <= checkout
        $sql = "
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ($status_string)
            AND pm1.meta_key = '_booking_checkin_date' AND pm1.meta_value <= %s
            AND pm2.meta_key = '_booking_checkout_date' AND pm2.meta_value >= %s
        ";

        return (int) $wpdb->get_var($wpdb->prepare($sql, $today, $today));
    }

    /**
     * Obtener reservas recientes para la tabla del dashboard
     * 
     * @param int $limit Número máximo de reservas
     * @return array Array de reservas con datos formateados
     */
    public static function get_recent_bookings($limit = 5)
    {
        if (class_exists('Alquipress_Config')) {
            $limit = $limit ?: Alquipress_Config::get_default_limit('dashboard');
        }

        try {
            $orders = wc_get_orders([
                'limit' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
                'return' => 'objects',
                'meta_query' => [
                    [
                        'key' => '_booking_checkin_date',
                        'compare' => 'EXISTS'
                    ]
                ]
            ]);

            $bookings = [];
            foreach ($orders as $order) {
                $order_id = $order->get_id();
                $product_id = (int) $order->get_meta('_booking_product_id');
                $checkin = $order->get_meta('_booking_checkin_date');
                $checkout = $order->get_meta('_booking_checkout_date');

                if (!$product_id) {
                    foreach ($order->get_items() as $item) {
                        $product = $item->get_product();
                        if ($product) {
                            $product_id = $product->get_id();
                            break;
                        }
                    }
                }

                $prop_name = Alquipress_Property_Helper::get_order_property_name($order);
                $prop_location = $product_id ? Alquipress_Property_Helper::get_product_location($product_id) : '';

                $bookings[] = [
                    'order_id' => $order_id,
                    'prop_name' => $prop_name,
                    'prop_location' => $prop_location,
                    'checkin' => $checkin,
                    'checkout' => $checkout,
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                ];
            }

            return $bookings;
        } catch (Exception $e) {
            if (class_exists('Alquipress_Logger')) {
                Alquipress_Logger::error(
                    'Error obteniendo reservas recientes',
                    Alquipress_Logger::CONTEXT_QUERY,
                    ['limit' => $limit, 'exception' => $e->getMessage()]
                );
            }
            return [];
        }
    }

    /**
     * Obtener actividad reciente (pagos, check-ins, check-outs, nuevas reservas)
     * 
     * @param int $limit Número máximo de actividades
     * @return array Array de actividades formateadas
     */
    public static function get_recent_activity($limit = 5)
    {
        if (class_exists('Alquipress_Config')) {
            $limit = $limit ?: Alquipress_Config::get_default_limit('dashboard');
        }

        $cache_key = class_exists('Alquipress_Config') 
            ? Alquipress_Config::get_cache_key('dashboard_activity_' . $limit)
            : 'alquipress_dashboard_activity_' . $limit;
        $cached_activity = get_transient($cache_key);

        if ($cached_activity !== false) {
            return $cached_activity;
        }

        $activities = [];
        $today = current_time('Y-m-d');

        try {
            // 1. Pagos recibidos (Pedidos completados)
            $completed = wc_get_orders([
                'status' => 'completed',
                'limit' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
            foreach ($completed as $order) {
                $date = $order->get_date_created();
                if (!$date) continue;

                $guest = $order->get_billing_first_name() ?: ($order->get_billing_company() ?: __('Cliente', 'alquipress'));
                
                $activities[] = [
                    'ts' => $date->getTimestamp(),
                    'type' => 'payment',
                    'title' => __('Pago recibido', 'alquipress'),
                    'sub' => $guest . ' - ' . wc_price($order->get_total()),
                    'icon_class' => 'icon-success',
                ];
            }

            // 2. Check-ins y Check-outs (Optimizado: cargar todas las órdenes en una query)
            $movements = [
                'checkin' => array_slice(self::get_bookings_by_checkin_date($today), 0, $limit),
                'checkout' => array_slice(self::get_bookings_by_checkout_date($today), 0, $limit)
            ];

            // Recopilar todos los IDs de órdenes necesarios
            $all_order_ids = array_merge($movements['checkin'], $movements['checkout']);
            
            // Cargar todas las órdenes en una sola query si hay IDs
            $orders_map = [];
            if (!empty($all_order_ids)) {
                $orders = wc_get_orders([
                    'post__in' => array_unique($all_order_ids),
                    'limit' => count($all_order_ids),
                    'return' => 'objects'
                ]);
                
                // Crear mapa para acceso O(1)
                foreach ($orders as $order) {
                    $orders_map[$order->get_id()] = $order;
                }
            }

            // Procesar check-ins y check-outs usando el mapa
            foreach ($movements as $type => $order_ids) {
                foreach ($order_ids as $order_id) {
                    if (!isset($orders_map[$order_id])) {
                        continue;
                    }
                    
                    $order = $orders_map[$order_id];
                    $prop = Alquipress_Property_Helper::get_order_property_name($order);
                    $activities[] = [
                        'ts' => strtotime($today . ($type === 'checkin' ? ' 12:00:00' : ' 11:00:00')),
                        'type' => $type,
                        'title' => $type === 'checkin' ? __('Check-in hoy', 'alquipress') : __('Check-out hoy', 'alquipress'),
                        'sub' => $prop,
                        'icon_class' => 'icon-info',
                    ];
                }
            }

            // 3. Nuevas reservas (Filtrado por meta_query para ser más selectivo)
            $recent_orders = wc_get_orders([
                'status' => ['pending', 'processing', 'deposito-ok', 'in-progress'],
                'limit' => $limit,
                'meta_query' => [
                    ['key' => '_booking_checkin_date', 'compare' => 'EXISTS']
                ]
            ]);
            foreach ($recent_orders as $order) {
                $date = $order->get_date_created();
                if (!$date) continue;

                $prop = Alquipress_Property_Helper::get_order_property_name($order);
                $activities[] = [
                    'ts' => $date->getTimestamp(),
                    'type' => 'booking',
                    'title' => __('Nueva reserva', 'alquipress'),
                    'sub' => $prop,
                    'icon_class' => 'icon-warning',
                ];
            }

            // Ordenar y limitar
            usort($activities, function ($a, $b) {
                return $b['ts'] - $a['ts'];
            });

            $activities = array_slice($activities, 0, $limit);

            // Procesar tiempo relativo
            $current_ts = current_time('timestamp');
            foreach ($activities as &$a) {
                $a['time_ago'] = sprintf(
                    _x('hace %s', 'time ago', 'alquipress'),
                    human_time_diff($a['ts'], $current_ts)
                );
                unset($a['ts']);
            }

            $cache_duration = class_exists('Alquipress_Config') 
                ? Alquipress_Config::get_cache_duration('dashboard')
                : 5 * MINUTE_IN_SECONDS;
            set_transient($cache_key, $activities, $cache_duration);

            return $activities;
        } catch (Exception $e) {
            if (class_exists('Alquipress_Logger')) {
                Alquipress_Logger::error(
                    'Error obteniendo actividad reciente',
                    Alquipress_Logger::CONTEXT_QUERY,
                    ['limit' => $limit, 'exception' => $e->getMessage()]
                );
            }
            return [];
        }
    }

    /**
     * Obtener badge de estado para una reserva
     * 
     * @param string $order_status Estado de la orden
     * @param string $checkin_date Fecha de check-in (Y-m-d)
     * @return array [label, class] para el badge
     */
    public static function get_booking_status_badge($order_status, $checkin_date = '')
    {
        $today = current_time('Y-m-d');
        if ($checkin_date === $today && in_array($order_status, ['processing', 'deposito-ok', 'in-progress'], true)) {
            return ['Check-in', 'status-checkin'];
        }
        if (in_array($order_status, ['completed', 'deposito-ok'], true)) {
            return ['Confirmada', 'status-completed'];
        }
        if (in_array($order_status, ['pending'], true)) {
            return ['Pendiente', 'status-pending'];
        }
        return ['En curso', 'status-active'];
    }
}
