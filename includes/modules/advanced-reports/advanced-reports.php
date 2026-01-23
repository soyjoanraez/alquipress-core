<?php
/**
 * Módulo: Informes y Analíticas Avanzadas
 * Reportes con Chart.js y análisis de negocio
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Advanced_Reports
{

    public function __construct()
    {
        // Añadir página de informes
        add_action('admin_menu', [$this, 'add_reports_page'], 25);

        // AJAX handlers
        add_action('wp_ajax_alquipress_get_report_data', [$this, 'ajax_get_report_data']);

        // Cargar assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Añadir página de informes al menú
     */
    public function add_reports_page()
    {
        add_submenu_page(
            'alquipress-settings',
            'Informes y Analíticas',
            '📊 Informes',
            'manage_options',
            'alquipress-reports',
            [$this, 'render_reports_page']
        );
    }

    /**
     * Renderizar página de informes
     */
    public function render_reports_page()
    {
        ?>
        <div class="wrap alquipress-reports-wrap">
            <h1>📊 Informes y Analíticas</h1>

            <!-- Filtros Generales -->
            <div class="reports-filters">
                <div class="filter-group">
                    <label for="report-year">Año:</label>
                    <select id="report-year">
                        <?php
                        $current_year = date('Y');
                        for ($year = $current_year; $year >= $current_year - 5; $year--) {
                            echo '<option value="' . $year . '">' . $year . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <button id="refresh-reports" class="button button-primary">🔄 Actualizar Informes</button>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3 id="stat-revenue-year">Cargando...</h3>
                        <p>Ingresos del Año</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content">
                        <h3 id="stat-bookings-year">Cargando...</h3>
                        <p>Reservas del Año</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3 id="stat-avg-booking">Cargando...</h3>
                        <p>Valor Medio por Reserva</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏠</div>
                    <div class="stat-content">
                        <h3 id="stat-occupancy-rate">Cargando...</h3>
                        <p>Tasa de Ocupación</p>
                    </div>
                </div>
            </div>

            <!-- Tabs de Informes -->
            <div class="reports-tabs">
                <button class="tab-button active" data-tab="revenue">💰 Ingresos</button>
                <button class="tab-button" data-tab="occupancy">📊 Ocupación</button>
                <button class="tab-button" data-tab="clients">👥 Clientes</button>
                <button class="tab-button" data-tab="properties">🏠 Propiedades</button>
            </div>

            <!-- Contenido de Tabs -->
            <div class="reports-content">

                <!-- Tab: Ingresos -->
                <div id="tab-revenue" class="tab-content active">
                    <div class="report-section">
                        <h2>Ingresos Mensuales</h2>
                        <canvas id="chart-revenue-monthly" style="max-height: 400px;"></canvas>
                    </div>

                    <div class="report-section">
                        <h2>Ingresos por Temporada</h2>
                        <canvas id="chart-revenue-season" style="max-height: 350px;"></canvas>
                    </div>
                </div>

                <!-- Tab: Ocupación -->
                <div id="tab-occupancy" class="tab-content">
                    <div class="report-section">
                        <h2>Tasa de Ocupación Mensual</h2>
                        <canvas id="chart-occupancy-monthly" style="max-height: 400px;"></canvas>
                    </div>

                    <div class="report-section">
                        <h2>Noches Reservadas vs Disponibles</h2>
                        <canvas id="chart-occupancy-comparison" style="max-height: 350px;"></canvas>
                    </div>
                </div>

                <!-- Tab: Clientes -->
                <div id="tab-clients" class="tab-content">
                    <div class="report-section">
                        <h2>Top 5 Clientes por Gasto Total</h2>
                        <table class="wp-list-table widefat fixed striped" id="table-top-clients">
                            <thead>
                                <tr>
                                    <th>Posición</th>
                                    <th>Cliente</th>
                                    <th>Email</th>
                                    <th>Total Reservas</th>
                                    <th>Gasto Total</th>
                                    <th>Última Reserva</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Cargando datos...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="report-section">
                        <h2>Distribución de Clientes por Valoración</h2>
                        <canvas id="chart-clients-rating" style="max-height: 300px;"></canvas>
                    </div>
                </div>

                <!-- Tab: Propiedades -->
                <div id="tab-properties" class="tab-content">
                    <div class="report-section">
                        <h2>Top 5 Propiedades Más Rentables</h2>
                        <table class="wp-list-table widefat fixed striped" id="table-top-properties">
                            <thead>
                                <tr>
                                    <th>Posición</th>
                                    <th>Propiedad</th>
                                    <th>Total Reservas</th>
                                    <th>Noches Reservadas</th>
                                    <th>Ingresos Totales</th>
                                    <th>Tasa Ocupación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Cargando datos...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="report-section">
                        <h2>Comparativa de Ingresos por Propiedad</h2>
                        <canvas id="chart-properties-comparison" style="max-height: 400px;"></canvas>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Obtener datos para informes
     */
    public function ajax_get_report_data()
    {
        check_ajax_referer('alquipress_reports', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

        $data = [];

        switch ($report_type) {
            case 'overview':
                $data = $this->get_overview_stats($year);
                break;
            case 'revenue_monthly':
                $data = $this->get_revenue_monthly($year);
                break;
            case 'revenue_season':
                $data = $this->get_revenue_by_season($year);
                break;
            case 'occupancy_monthly':
                $data = $this->get_occupancy_monthly($year);
                break;
            case 'occupancy_comparison':
                $data = $this->get_occupancy_comparison($year);
                break;
            case 'top_clients':
                $data = $this->get_top_clients($year);
                break;
            case 'clients_rating':
                $data = $this->get_clients_by_rating();
                break;
            case 'top_properties':
                $data = $this->get_top_properties($year);
                break;
            case 'properties_comparison':
                $data = $this->get_properties_revenue_comparison($year);
                break;
            default:
                wp_send_json_error(['message' => 'Tipo de reporte no válido']);
        }

        wp_send_json_success($data);
    }

    // ========== Métodos de Análisis de Datos ==========

    /**
     * Estadísticas generales del año
     */
    private function get_overview_stats($year)
    {
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'in-progress', 'checkout-review'],
            'date_created' => $year . '-01-01...' . $year . '-12-31',
        ]);

        $total_revenue = 0;
        $total_bookings = count($orders);
        $total_nights = 0;

        foreach ($orders as $order) {
            $total_revenue += $order->get_total();

            $checkin = $order->get_meta('_booking_checkin_date');
            $checkout = $order->get_meta('_booking_checkout_date');

            if ($checkin && $checkout) {
                $diff = strtotime($checkout) - strtotime($checkin);
                $total_nights += floor($diff / (60 * 60 * 24));
            }
        }

        $avg_booking = $total_bookings > 0 ? $total_revenue / $total_bookings : 0;

        // Calcular tasa de ocupación (simplificado)
        $total_properties = wp_count_posts('product')->publish;
        $days_in_year = ($year == date('Y')) ? date('z') : 365;
        $available_nights = $total_properties * $days_in_year;
        $occupancy_rate = $available_nights > 0 ? ($total_nights / $available_nights) * 100 : 0;

        return [
            'total_revenue' => wc_price($total_revenue),
            'total_bookings' => $total_bookings,
            'avg_booking' => wc_price($avg_booking),
            'occupancy_rate' => number_format($occupancy_rate, 1) . '%'
        ];
    }

    /**
     * Ingresos mensuales
     */
    private function get_revenue_monthly($year)
    {
        $monthly_data = array_fill(1, 12, 0);

        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'in-progress', 'checkout-review'],
            'date_created' => $year . '-01-01...' . $year . '-12-31',
        ]);

        foreach ($orders as $order) {
            $month = intval($order->get_date_created()->format('n'));
            $monthly_data[$month] += $order->get_total();
        }

        return [
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            'data' => array_values($monthly_data)
        ];
    }

    /**
     * Ingresos por temporada
     */
    private function get_revenue_by_season($year)
    {
        $seasons = [
            'Temporada Baja' => 0,      // Ene, Feb, Nov, Dic
            'Temporada Media' => 0,     // Mar, Abr, May, Oct
            'Temporada Alta' => 0,      // Jun, Jul, Ago, Sep
        ];

        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'in-progress', 'checkout-review'],
            'date_created' => $year . '-01-01...' . $year . '-12-31',
        ]);

        foreach ($orders as $order) {
            $month = intval($order->get_date_created()->format('n'));
            $total = $order->get_total();

            if (in_array($month, [1, 2, 11, 12])) {
                $seasons['Temporada Baja'] += $total;
            } elseif (in_array($month, [3, 4, 5, 10])) {
                $seasons['Temporada Media'] += $total;
            } else {
                $seasons['Temporada Alta'] += $total;
            }
        }

        return [
            'labels' => array_keys($seasons),
            'data' => array_values($seasons)
        ];
    }

    /**
     * Ocupación mensual
     */
    private function get_occupancy_monthly($year)
    {
        $monthly_occupancy = array_fill(1, 12, 0);
        $total_properties = wp_count_posts('product')->publish;

        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'in-progress', 'checkout-review'],
            'date_created' => $year . '-01-01...' . $year . '-12-31',
        ]);

        foreach ($orders as $order) {
            $checkin = $order->get_meta('_booking_checkin_date');
            $checkout = $order->get_meta('_booking_checkout_date');

            if ($checkin && $checkout) {
                $start = new DateTime($checkin);
                $end = new DateTime($checkout);

                while ($start < $end) {
                    if ($start->format('Y') == $year) {
                        $month = intval($start->format('n'));
                        $monthly_occupancy[$month]++;
                    }
                    $start->modify('+1 day');
                }
            }
        }

        // Convertir a porcentaje
        foreach ($monthly_occupancy as $month => $nights) {
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $available_nights = $total_properties * $days_in_month;
            $monthly_occupancy[$month] = $available_nights > 0 ? ($nights / $available_nights) * 100 : 0;
        }

        return [
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            'data' => array_values($monthly_occupancy)
        ];
    }

    /**
     * Comparación noches reservadas vs disponibles
     */
    private function get_occupancy_comparison($year)
    {
        $total_properties = wp_count_posts('product')->publish;
        $days_in_year = ($year == date('Y')) ? date('z') : 365;
        $available_nights = $total_properties * $days_in_year;

        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'in-progress', 'checkout-review'],
            'date_created' => $year . '-01-01...' . $year . '-12-31',
        ]);

        $booked_nights = 0;
        foreach ($orders as $order) {
            $checkin = $order->get_meta('_booking_checkin_date');
            $checkout = $order->get_meta('_booking_checkout_date');

            if ($checkin && $checkout) {
                $diff = strtotime($checkout) - strtotime($checkin);
                $booked_nights += floor($diff / (60 * 60 * 24));
            }
        }

        return [
            'labels' => ['Noches Reservadas', 'Noches Disponibles'],
            'data' => [$booked_nights, $available_nights - $booked_nights]
        ];
    }

    /**
     * Top 5 clientes
     */
    private function get_top_clients($year)
    {
        global $wpdb;

        $start_date = $year . '-01-01';
        $end_date = $year . '-12-31';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                p.ID as order_id,
                pm_customer.meta_value as customer_id,
                pm_total.meta_value as order_total,
                p.post_date
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = '_customer_user'
            LEFT JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-in-progress', 'wc-checkout-review')
            AND p.post_date >= %s
            AND p.post_date <= %s
            AND pm_customer.meta_value > 0",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));

        $clients_data = [];

        foreach ($results as $row) {
            $customer_id = intval($row->customer_id);

            if (!isset($clients_data[$customer_id])) {
                $user = get_userdata($customer_id);
                $clients_data[$customer_id] = [
                    'name' => $user ? $user->display_name : 'Usuario #' . $customer_id,
                    'email' => $user ? $user->user_email : '',
                    'total_spent' => 0,
                    'total_orders' => 0,
                    'last_order_date' => ''
                ];
            }

            $clients_data[$customer_id]['total_spent'] += floatval($row->order_total);
            $clients_data[$customer_id]['total_orders']++;
            $clients_data[$customer_id]['last_order_date'] = $row->post_date;
        }

        // Ordenar por gasto total
        usort($clients_data, function ($a, $b) {
            return $b['total_spent'] - $a['total_spent'];
        });

        // Top 5
        return array_slice($clients_data, 0, 5);
    }

    /**
     * Distribución de clientes por valoración
     */
    private function get_clients_by_rating()
    {
        $ratings = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $users = get_users(['role' => 'customer']);

        foreach ($users as $user) {
            $rating = intval(get_user_meta($user->ID, 'guest_rating', true));
            if ($rating >= 1 && $rating <= 5) {
                $ratings[$rating]++;
            }
        }

        return [
            'labels' => ['⭐ 1 Estrella', '⭐⭐ 2 Estrellas', '⭐⭐⭐ 3 Estrellas', '⭐⭐⭐⭐ 4 Estrellas', '⭐⭐⭐⭐⭐ 5 Estrellas'],
            'data' => array_values($ratings)
        ];
    }

    /**
     * Top 5 propiedades más rentables
     */
    private function get_top_properties($year)
    {
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'in-progress', 'checkout-review'],
            'date_created' => $year . '-01-01...' . $year . '-12-31',
        ]);

        $properties_data = [];

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if (!$product)
                    continue;

                $product_id = $product->get_id();

                if (!isset($properties_data[$product_id])) {
                    $properties_data[$product_id] = [
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
        $days_in_year = ($year == date('Y')) ? date('z') : 365;
        foreach ($properties_data as $id => &$data) {
            $data['occupancy_rate'] = ($data['total_nights'] / $days_in_year) * 100;
        }

        // Ordenar por ingresos
        usort($properties_data, function ($a, $b) {
            return $b['total_revenue'] - $a['total_revenue'];
        });

        return array_slice($properties_data, 0, 5);
    }

    /**
     * Comparativa de ingresos por propiedad (Top 10)
     */
    private function get_properties_revenue_comparison($year)
    {
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'in-progress', 'checkout-review'],
            'date_created' => $year . '-01-01...' . $year . '-12-31',
        ]);

        $properties_revenue = [];

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if (!$product)
                    continue;

                $product_id = $product->get_id();
                $product_name = $product->get_name();

                if (!isset($properties_revenue[$product_name])) {
                    $properties_revenue[$product_name] = 0;
                }

                $properties_revenue[$product_name] += $order->get_total();
            }
        }

        // Ordenar y tomar top 10
        arsort($properties_revenue);
        $properties_revenue = array_slice($properties_revenue, 0, 10, true);

        return [
            'labels' => array_keys($properties_revenue),
            'data' => array_values($properties_revenue)
        ];
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets($hook)
    {
        if ($hook !== 'alquipress_page_alquipress-reports') {
            return;
        }

        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        // Custom CSS
        wp_enqueue_style(
            'alquipress-advanced-reports',
            ALQUIPRESS_URL . 'includes/modules/advanced-reports/assets/advanced-reports.css',
            [],
            ALQUIPRESS_VERSION
        );

        // Custom JS
        wp_enqueue_script(
            'alquipress-advanced-reports',
            ALQUIPRESS_URL . 'includes/modules/advanced-reports/assets/advanced-reports.js',
            ['jquery', 'chartjs'],
            ALQUIPRESS_VERSION,
            true
        );

        wp_localize_script('alquipress-advanced-reports', 'alquipressReports', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alquipress_reports'),
            'currentYear' => date('Y')
        ]);
    }
}

new Alquipress_Advanced_Reports();
