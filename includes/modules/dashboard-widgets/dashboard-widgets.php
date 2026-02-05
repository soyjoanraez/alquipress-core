<?php
/**
 * Módulo: Dashboard Widgets CRM
 * Widgets informativos para el dashboard de WordPress
 */

if (!defined('ABSPATH'))
    exit;

// Cargar clase de datos
require_once __DIR__ . '/class-dashboard-data.php';

class Alquipress_Dashboard_Widgets
{

    public function __construct()
    {
        $GLOBALS['alquipress_dashboard_widgets'] = $this;
        add_action('wp_dashboard_setup', [$this, 'add_widgets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_section_assets']);
        add_action('admin_head', [$this, 'ensure_standards_mode_on_panel'], 1);

        // Limpiar caché cuando cambien datos críticos
        add_action('woocommerce_order_status_changed', [$this, 'clear_dashboard_cache']);
        add_action('save_post_product', [$this, 'clear_dashboard_cache']);
        add_action('delete_post', [$this, 'clear_dashboard_cache']);
    }

    /**
     * Limpiar todos los transients de caché del dashboard
     */
    public function clear_dashboard_cache()
    {
        global $wpdb;
        // Limpia todos los transients que empiecen por alquipress o ap_ (que usamos para propiedades)
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_alquipress_%' OR option_name LIKE '_transient_timeout_alquipress_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ap_%' OR option_name LIKE '_transient_timeout_ap_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_alq_%' OR option_name LIKE '_transient_timeout_alq_%'");
        
        // También limpiar caché de objetos si existe
        wp_cache_flush();
    }

    /**
     * En la página Panel, asegurar meta X-UA-Compatible (IE=edge) en el head del admin.
     * Nota: el aviso "jQuery is not compatible with Quirks Mode" suele deberse a salida antes del DOCTYPE
     * por otro plugin o el tema; esta meta ayuda en edge cases con IE.
     */
    public function ensure_standards_mode_on_panel()
    {
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if ($page !== 'alquipress-dashboard') {
            return;
        }
        echo '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";
    }

    /**
     * Registrar widgets del dashboard
     */
    public function add_widgets()
    {
        // Widget: Movimientos de Hoy
        wp_add_dashboard_widget(
            'alquipress_todays_movements',
            '📅 Movimientos de Hoy',
            [$this, 'render_todays_movements']
        );

        // Widget: Ingresos del Mes
        wp_add_dashboard_widget(
            'alquipress_monthly_revenue',
            '💰 Ingresos del Mes',
            [$this, 'render_monthly_revenue']
        );

        // Widget: Estado de Propiedades
        wp_add_dashboard_widget(
            'alquipress_property_status',
            '🏠 Estado de Propiedades',
            [$this, 'render_property_status']
        );

        // Widget: Alertas y Pendientes
        wp_add_dashboard_widget(
            'alquipress_alerts',
            '⚠️ Alertas',
            [$this, 'render_alerts']
        );
    }

    /**
     * Widget: Movimientos de Hoy (Check-ins y Check-outs)
     */
    public function render_todays_movements()
    {
        $today = current_time('Y-m-d');
        $cache_key = 'alquipress_widget_movements_' . $today;
        $cached_html = get_transient($cache_key);

        if ($cached_html !== false) {
            echo $cached_html;
            return;
        }

        ob_start();
        // Obtener datos
        $checkins_today = Alquipress_Dashboard_Data::get_bookings_by_checkin_date($today);
        $checkouts_today = Alquipress_Dashboard_Data::get_bookings_by_checkout_date($today);

        ?>
        <div class="alquipress-dashboard-container">
            <div class="alquipress-movements-grid">
                <div class="movement-card checkin-card">
                    <div class="movement-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
                    <div class="movement-data">
                        <div class="movement-number"><?php echo count($checkins_today); ?></div>
                        <div class="movement-label">Check-ins Hoy</div>
                    </div>
                </div>

                <div class="movement-card checkout-card">
                    <div class="movement-icon"><span class="dashicons dashicons-exit"></span></div>
                    <div class="movement-data">
                        <div class="movement-number"><?php echo count($checkouts_today); ?></div>
                        <div class="movement-label">Check-outs Hoy</div>
                    </div>
                </div>
            </div>

            <?php if (!empty($checkins_today) || !empty($checkouts_today)): ?>
                <table class="widefat alquipress-dashboard-table">
                    <thead>
                        <tr>
                            <th>Huésped</th>
                            <th>Propiedad</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($checkins_today as $order_id) {
                            $this->render_movement_row($order_id, 'checkin');
                        }
                        foreach ($checkouts_today as $order_id) {
                            $this->render_movement_row($order_id, 'checkout');
                        }
                        ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alquipress-empty-state">
                    <span class="dashicons dashicons-calendar"></span>
                    <p>No hay movimientos programados para hoy.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();
        set_transient($cache_key, $html, 10 * MINUTE_IN_SECONDS);
        echo $html;
    }

    /**
     * Renderiza una fila de movimiento
     */
    private function render_movement_row($order_id, $type)
    {
        $order = wc_get_order($order_id);
        if (!$order)
            return;

        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $property_name = $this->get_order_property_name($order);
        $badge_class = ($type === 'checkin') ? 'badge-checkin' : 'badge-checkout';
        $badge_label = ($type === 'checkin') ? '✅ Check-in' : '🚪 Check-out';

        echo '<tr>';
        echo '<td><strong>' . esc_html($customer_name) . '</strong></td>';
        echo '<td>' . esc_html($property_name) . '</td>';
        echo '<td><span class="' . esc_attr($badge_class) . '">' . esc_html($badge_label) . '</span></td>';
        echo '</tr>';
    }

    /**
     * Widget: Ingresos del Mes
     */
    public function render_monthly_revenue()
    {
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');
        $last_month_start = date('Y-m-01', strtotime('-1 month'));
        $last_month_end = date('Y-m-t', strtotime('-1 month'));

        $current_revenue = $this->get_revenue_between_dates($current_month_start, $current_month_end);
        $last_revenue = $this->get_revenue_between_dates($last_month_start, $last_month_end);

        $change_percentage = 0;
        if ($last_revenue > 0) {
            $change_percentage = (($current_revenue - $last_revenue) / $last_revenue) * 100;
        }

        ?>
        <div class="alquipress-dashboard-container">
            <div class="revenue-hero-card">
                <div class="revenue-amount"><?php echo wc_price($current_revenue); ?></div>
                <div class="revenue-period"><?php echo wp_date('F Y'); ?></div>

                <div class="revenue-trend <?php echo ($change_percentage >= 0) ? 'trend-up' : 'trend-down'; ?>">
                    <span
                        class="dashicons <?php echo ($change_percentage >= 0) ? 'dashicons-trending-up' : 'dashicons-trending-down'; ?>"></span>
                    <?php echo number_format(abs($change_percentage), 1); ?>% vs mes pasado
                </div>
            </div>

            <?php
            $revenue_by_status = $this->get_revenue_by_status($current_month_start, $current_month_end);
            if (!empty($revenue_by_status)): ?>
                <div class="revenue-breakdown">
                    <h4>Desglose por Estado</h4>
                    <ul class="revenue-list">
                        <?php foreach ($revenue_by_status as $status => $amount):
                            $status_labels = [
                                'completed' => 'Completado',
                                'processing' => 'Procesando',
                                'deposito-ok' => 'Depósito OK',
                                'in-progress' => 'En Curso',
                            ];
                            $label = $status_labels[$status] ?? ucfirst($status);
                            ?>
                            <li>
                                <span class="status-dot status-<?php echo esc_attr($status); ?>"></span>
                                <span class="status-label"><?php echo esc_html($label); ?></span>
                                <span class="status-amount"><?php echo wc_price($amount); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Widget: Estado de Propiedades
     */
    public function render_property_status()
    {
        $total_properties = wp_count_posts('product')->publish;
        $today = current_time('Y-m-d');
        $occupied_today = Alquipress_Dashboard_Data::get_occupied_properties_count($today);

        $occupancy_rate = 0;
        if ($total_properties > 0) {
            $occupancy_rate = ($occupied_today / $total_properties) * 100;
        }

        ?>
        <div class="alquipress-dashboard-container">
            <div class="property-status-summary">
                <div class="stat-main">
                    <div class="stat-value"><?php echo round($occupancy_rate); ?>%</div>
                    <div class="stat-label">Ocupación Hoy</div>
                </div>
                <div class="stat-details">
                    <div class="detail-item">
                        <span class="dot occupied"></span>
                        <span class="label">Ocupadas:</span>
                        <span class="value"><?php echo $occupied_today; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="dot available"></span>
                        <span class="label">Disponibles:</span>
                        <span class="value"><?php echo ($total_properties - $occupied_today); ?></span>
                    </div>
                </div>
            </div>

            <div class="occupancy-progress-wrapper">
                <div class="occupancy-progress-bar">
                    <div class="progress-fill" style="width: <?php echo $occupancy_rate; ?>%"></div>
                </div>
            </div>

            <p class="stat-footer">Total de propiedades: <strong><?php echo $total_properties; ?></strong></p>
        </div>
        <?php
    }

    /**
     * Widget: Alertas
     */
    public function render_alerts()
    {
        $alerts = [];

        // Alerta: Check-ins mañana
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $checkins_tomorrow = Alquipress_Dashboard_Data::get_bookings_by_checkin_date($tomorrow);
        if (!empty($checkins_tomorrow)) {
            $alerts[] = [
                'type' => 'info',
                'icon' => '📅',
                'message' => count($checkins_tomorrow) . ' check-in(s) programado(s) para mañana'
            ];
        }

        // Alerta: Pedidos pendientes de pago (conteo optimizado)
        $pending_count = $this->count_orders_by_status('pending');
        if ($pending_count >= 5) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'message' => $pending_count . ' pedido(s) pendiente(s) de pago'
            ];
        }

        // Alerta: Pedidos en revisión de salida (conteo optimizado)
        $review_count = $this->count_orders_by_status('checkout-review');
        if ($review_count > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => '🔍',
                'message' => $review_count . ' propiedad(es) en revisión de salida'
            ];
        }

        // Alerta: Propietarios sin IBAN
        $owners_without_iban = $this->get_owners_without_iban();
        if ($owners_without_iban > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => '💳',
                'message' => $owners_without_iban . ' propietario(s) sin IBAN registrado'
            ];
        }

        // Renderizar alertas
        ?>
        <div class="alquipress-dashboard-container">
            <?php if (!empty($alerts)): ?>
                <ul class="alquipress-alerts-list">
                    <?php foreach ($alerts as $alert):
                        $class = 'alert-' . $alert['type'];
                        ?>
                        <li class="<?php echo esc_attr($class); ?>">
                            <span class="alert-icon"><?php echo $alert['icon']; ?></span>
                            <span class="alert-message"><?php echo esc_html($alert['message']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="alquipress-empty-state">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p>No tienes alertas pendientes.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ========== Métodos auxiliares ==========
    // Nota: Los métodos get_bookings_by_checkin_date() y get_bookings_by_checkout_date()
    // han sido movidos a Alquipress_Dashboard_Data para evitar duplicación de código.

    private function get_order_property_name($order)
    {
        return Alquipress_Property_Helper::get_order_property_name($order);
    }

    private function get_revenue_between_dates($start_date, $end_date)
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

    private function get_revenue_by_status($start_date, $end_date)
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

    private function get_occupied_properties_count($date)
    {
        global $wpdb;

        $cache_key = 'alquipress_occupied_count_' . $date;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return (int) $cached;
        }

        // Buscar reservas activas en esta fecha filtrando por estados válidos.
        // Unimos con la tabla de posts para verificar que el pedido esté confirmado/pagado.
        $statuses = ['wc-completed', 'wc-processing', 'wc-deposito-ok', 'wc-in-progress'];
        $status_string = "'" . implode("','", $statuses) . "'";

        $occupied_properties = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT pm2.meta_value as product_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            INNER JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ($status_string)
            AND pm1.meta_key = '_booking_checkin_date' AND pm1.meta_value <= %s
            AND pm2.meta_key = '_booking_product_id'
            AND pm3.meta_key = '_booking_checkout_date' AND pm3.meta_value >= %s",
            $date,
            $date
        ));

        $count = count($occupied_properties);
        set_transient($cache_key, $count, 5 * MINUTE_IN_SECONDS); // Reducido a 5 min

        return $count;
    }

    private function get_owners_without_iban()
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
     * Contar pedidos por estado (optimizado con query directa)
     */
    private function count_orders_by_status($status)
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
     * Optimizado: SQL directo en lugar de cargar objetos.
     */
    private function get_active_bookings_count()
    {
        global $wpdb;
        $today = date('Y-m-d');

        // Estados que consideramos activos
        $statuses = ['wc-processing', 'wc-deposito-ok', 'wc-in-progress', 'wc-completed'];
        $status_string = "'" . implode("','", $statuses) . "'";

        // Query optimizada: Busca pedidos que tengan checkin <= hoy <= checkout
        // Usamos alias pm1 para checkin y pm2 para checkout
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
     * Obtener ubicación de un producto (taxonomía poblacion)
     */
    private function get_product_location($product_id)
    {
        return Alquipress_Property_Helper::get_product_location($product_id);
    }

    /**
     * Obtener reservas recientes para la tabla del dashboard
     * Optimizado: Filtrado por meta_query en la base de datos.
     */
    private function get_recent_bookings($limit = 5)
    {
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

            $prop_name = $this->get_order_property_name($order);
            $prop_location = $product_id ? $this->get_product_location($product_id) : '';
            $thumb_url = $product_id ? get_the_post_thumbnail_url($product_id, [40, 40]) : '';

            $guest = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            if (trim($guest) === '') {
                $guest = $order->get_billing_company() ?: '-';
            }

            $dates = '';
            if ($checkin && $checkout) {
                $dates = wp_date('j M', strtotime($checkin)) . ' - ' . wp_date('j M', strtotime($checkout));
            }

            $status = $order->get_status();
            list($status_label, $status_class) = $this->get_booking_status_badge($status, $checkin);

            $bookings[] = [
                'order_id' => $order_id,
                'prop_name' => $prop_name,
                'prop_location' => $prop_location,
                'prop_thumb_url' => $thumb_url,
                'guest' => $guest,
                'dates' => $dates,
                'status_label' => $status_label,
                'status_class' => $status_class,
            ];
        }

        return $bookings;
    }

    /**
     * Devuelve [ label, class ] para el badge de estado
     */
    private function get_booking_status_badge($order_status, $checkin_date = '')
    {
        $today = date('Y-m-d');
        if ($checkin_date === $today && in_array($order_status, ['processing', 'deposito-ok', 'in-progress'], true)) {
            return ['Check-in', 'status-checkin'];
        }
        $map = [
            'completed' => ['Confirmado', 'status-confirmed'],
            'processing' => ['Procesando', 'status-processing'],
            'pending' => ['Pendiente', 'status-pending'],
            'deposito-ok' => ['Depósito OK', 'status-confirmed'],
            'in-progress' => ['En curso', 'status-checkin'],
        ];
        return $map[$order_status] ?? [ucfirst($order_status), 'status-pending'];
    }

    /**
     * Obtener actividad reciente (pagos, check-in/out, nuevas reservas)
     * Optimizado: Implementación de Caché (Transient) y consultas selectivas.
     */
    private function get_recent_activity($limit = 5)
    {
        // Usar límite por defecto de Config si está disponible
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
            'checkin' => array_slice(Alquipress_Dashboard_Data::get_bookings_by_checkin_date($today), 0, $limit),
            'checkout' => array_slice(Alquipress_Dashboard_Data::get_bookings_by_checkout_date($today), 0, $limit)
        ];

        // Recopilar todos los IDs de órdenes necesarios
        $all_order_ids = array_merge($movements['checkin'], $movements['checkout']);
        
        // Cargar todas las órdenes en una sola query si hay IDs
        $orders_map = [];
        if (!empty($all_order_ids)) {
            try {
                $orders = wc_get_orders([
                    'post__in' => array_unique($all_order_ids),
                    'limit' => count($all_order_ids),
                    'return' => 'objects'
                ]);
                
                // Crear mapa para acceso O(1)
                foreach ($orders as $order) {
                    $orders_map[$order->get_id()] = $order;
                }
            } catch (Exception $e) {
                if (class_exists('Alquipress_Logger')) {
                    Alquipress_Logger::error(
                        'Error cargando órdenes para actividad reciente',
                        Alquipress_Logger::CONTEXT_QUERY,
                        [
                            'order_ids' => $all_order_ids,
                            'exception' => $e->getMessage()
                        ]
                    );
                }
            }
        }

        // Procesar check-ins y check-outs usando el mapa
        foreach ($movements as $type => $order_ids) {
            foreach ($order_ids as $order_id) {
                if (!isset($orders_map[$order_id])) {
                    continue;
                }
                
                $order = $orders_map[$order_id];
                $prop = $this->get_order_property_name($order);
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

            $prop = $this->get_order_property_name($order);
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

        set_transient($cache_key, $activities, 5 * MINUTE_IN_SECONDS);

        return $activities;
    }

    /**
     * Cargar estilos CSS (dashboard WP y página Panel ALQUIPRESS)
     */
    public function enqueue_assets($hook)
    {
        $is_wp_dashboard = ($hook === 'index.php');
        if ($is_wp_dashboard) {
            wp_enqueue_style(
                'alquipress-dashboard-widgets',
                ALQUIPRESS_URL . 'includes/modules/dashboard-widgets/assets/dashboard-widgets.css',
                [],
                ALQUIPRESS_VERSION
            );
        }
    }

    public function enqueue_section_assets($page)
    {
        $allowed_pages = ['alquipress-dashboard', 'alquipress-finanzas'];
        if (!in_array($page, $allowed_pages, true)) {
            return;
        }
        wp_enqueue_style(
            'alquipress-dashboard-widgets',
            ALQUIPRESS_URL . 'includes/modules/dashboard-widgets/assets/dashboard-widgets.css',
            [],
            ALQUIPRESS_VERSION
        );
    }

    /**
     * Renderizar la página completa del Dashboard del sistema (ALQUIPRESS → Dashboard) - Diseño Pencil
     */
    public function render_full_dashboard()
    {
        $today = date('Y-m-d');
        $checkins_today = Alquipress_Dashboard_Data::get_bookings_by_checkin_date($today);
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');
        $last_month_start = date('Y-m-01', strtotime('-1 month'));
        $last_month_end = date('Y-m-t', strtotime('-1 month'));
        $current_revenue = Alquipress_Dashboard_Data::get_revenue_between_dates($current_month_start, $current_month_end);
        $last_revenue = Alquipress_Dashboard_Data::get_revenue_between_dates($last_month_start, $last_month_end);
        $revenue_change_pct = ($last_revenue > 0) ? (($current_revenue - $last_revenue) / $last_revenue) * 100 : 0;
        $total_properties = (int) wp_count_posts('product')->publish;
        $occupied_today = Alquipress_Dashboard_Data::get_occupied_properties_count($today);
        $occupancy_rate = ($total_properties > 0) ? ($occupied_today / $total_properties) * 100 : 0;
        $active_bookings = Alquipress_Dashboard_Data::get_active_bookings_count();
        $recent_bookings = $this->get_recent_bookings(5);
        $recent_activity = $this->get_recent_activity(5);

        // Propiedades publicadas este mes (optimizado con found_posts)
        $props_query = new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'date_query' => [
                ['after' => $current_month_start, 'before' => $current_month_end . ' 23:59:59', 'inclusive' => true],
            ],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
        ]);
        $props_added_this_month = $props_query->found_posts;
        $last_month_15 = date('Y-m-d', strtotime('midnight', strtotime('first day of last month +14 days')));
        $occ_last_month = Alquipress_Dashboard_Data::get_occupied_properties_count($last_month_15);
        $occupancy_last = ($total_properties > 0) ? ($occ_last_month / $total_properties) * 100 : 0;
        $occupancy_change = round($occupancy_rate - $occupancy_last, 1);

        $orders_url = admin_url('edit.php?post_type=shop_order');
        $products_url = admin_url('edit.php?post_type=product');
        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap alquipress-dashboard-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('dashboard'); ?>
                <main class="ap-owners-main">
            <header class="ap-header">
                <div class="ap-header-left">
                    <h1 class="ap-header-title"><?php esc_html_e('Dashboard', 'alquipress'); ?></h1>
                    <p class="ap-header-subtitle"><?php esc_html_e('Bienvenido, aquí tienes el resumen de tus propiedades', 'alquipress'); ?></p>
                </div>
                <div class="ap-header-right">
                    <form action="<?php echo esc_url(admin_url('edit.php')); ?>" method="get" class="ap-search-form" role="search">
                        <input type="hidden" name="post_type" value="product">
                        <input type="search" name="s" class="ap-search-bar" placeholder="<?php esc_attr_e('Buscar propiedades...', 'alquipress'); ?>" aria-label="<?php esc_attr_e('Buscar propiedades', 'alquipress'); ?>" value="<?php echo isset($_GET['s']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['s']))) : ''; ?>">
                    </form>
                    <a href="<?php echo esc_url($products_url); ?>" class="ap-notif-btn" title="<?php esc_attr_e('Notificaciones', 'alquipress'); ?>"><span class="dashicons dashicons-bell"></span></a>
                </div>
            </header>

            <div class="ap-metrics-row">
                <div class="ap-metric-card">
                    <span class="ap-metric-label"><?php esc_html_e('Total propiedades', 'alquipress'); ?></span>
                    <div class="ap-metric-value-row">
                        <span class="ap-metric-value"><?php echo (int) $total_properties; ?></span>
                        <span class="ap-metric-change ap-change-success"><?php echo $props_added_this_month > 0 ? '+' . $props_added_this_month . ' ' . esc_html__('este mes', 'alquipress') : esc_html__('en catálogo', 'alquipress'); ?></span>
                    </div>
                </div>
                <div class="ap-metric-card">
                    <span class="ap-metric-label"><?php esc_html_e('Reservas activas', 'alquipress'); ?></span>
                    <div class="ap-metric-value-row">
                        <span class="ap-metric-value"><?php echo (int) $active_bookings; ?></span>
                        <span class="ap-metric-change ap-change-info"><?php echo count($checkins_today); ?> <?php esc_html_e('check-ins hoy', 'alquipress'); ?></span>
                    </div>
                </div>
                <div class="ap-metric-card">
                    <span class="ap-metric-label"><?php esc_html_e('Ingresos del mes', 'alquipress'); ?></span>
                    <div class="ap-metric-value-row">
                        <span class="ap-metric-value"><?php echo wc_price($current_revenue); ?></span>
                        <span class="ap-metric-change ap-change-success"><?php echo ($revenue_change_pct >= 0 ? '+' : '') . number_format_i18n($revenue_change_pct, 1); ?>%</span>
                    </div>
                </div>
                <div class="ap-metric-card">
                    <span class="ap-metric-label"><?php esc_html_e('Ocupación hoy', 'alquipress'); ?></span>
                    <div class="ap-metric-value-row">
                        <span class="ap-metric-value"><?php echo round($occupancy_rate); ?>%</span>
                        <span class="ap-metric-change ap-change-success"><?php echo ($occupancy_change >= 0 ? '+' : '') . number_format_i18n($occupancy_change, 1); ?>% <?php esc_html_e('vs mes pasado', 'alquipress'); ?></span>
                    </div>
                </div>
            </div>

            <?php
            // Renderizar widget de Salud Operativa si el módulo está activo
            do_action('alquipress_render_section', 'alquipress-dashboard');
            ?>

            <div class="ap-content-row">
                <div class="ap-content-left">
                    <section class="ap-recent-bookings">
                        <div class="ap-recent-bookings-header">
                            <h2 class="ap-recent-bookings-title"><?php esc_html_e('Reservas recientes', 'alquipress'); ?></h2>
                            <a href="<?php echo esc_url($orders_url); ?>" class="ap-recent-bookings-view-all"><?php esc_html_e('Ver todo', 'alquipress'); ?></a>
                        </div>
                        <?php if (!empty($recent_bookings)): ?>
                            <table class="ap-bookings-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Propiedad', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Huésped', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Fechas', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Estado', 'alquipress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_bookings as $row): ?>
                                        <tr>
                                            <td>
                                                <div class="ap-booking-prop">
                                                    <?php if (!empty($row['prop_thumb_url'])): ?>
                                                        <img src="<?php echo esc_url($row['prop_thumb_url']); ?>" alt="" class="ap-booking-prop-thumb" width="40" height="40">
                                                    <?php else: ?>
                                                        <div class="ap-booking-prop-placeholder" aria-hidden="true"></div>
                                                    <?php endif; ?>
                                                    <div class="ap-booking-prop-info">
                                                        <span class="ap-booking-prop-name"><?php echo esc_html($row['prop_name']); ?></span>
                                                        <?php if ($row['prop_location'] !== ''): ?>
                                                            <span class="ap-booking-prop-loc"><?php echo esc_html($row['prop_location']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="ap-booking-guest"><?php echo esc_html($row['guest']); ?></td>
                                            <td class="ap-booking-dates"><?php echo esc_html($row['dates']); ?></td>
                                            <td><span class="ap-booking-status <?php echo esc_attr($row['status_class']); ?>"><?php echo esc_html($row['status_label']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alquipress-empty-state">
                                <span class="dashicons dashicons-calendar"></span>
                                <p><?php esc_html_e('No hay reservas recientes.', 'alquipress'); ?></p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
                <div class="ap-content-right">
                    <section class="ap-recent-activity">
                        <div class="ap-recent-activity-header">
                            <h2 class="ap-recent-activity-title"><?php esc_html_e('Actividad reciente', 'alquipress'); ?></h2>
                            <a href="<?php echo esc_url($orders_url); ?>" class="ap-recent-activity-view-all"><?php esc_html_e('Ver todo', 'alquipress'); ?></a>
                        </div>
                        <?php if (!empty($recent_activity)): ?>
                            <ul class="ap-activity-list">
                                <?php foreach ($recent_activity as $act): ?>
                                    <li class="ap-activity-item">
                                        <span class="ap-activity-icon <?php echo esc_attr($act['icon_class']); ?>">
                                            <?php
                                            if ($act['icon_class'] === 'icon-success') {
                                                echo '<span class="dashicons dashicons-yes-alt"></span>';
                                            } elseif ($act['icon_class'] === 'icon-info') {
                                                echo '<span class="dashicons dashicons-unlock"></span>';
                                            } else {
                                                echo '<span class="dashicons dashicons-calendar-alt"></span>';
                                            }
                                            ?>
                                        </span>
                                        <div class="ap-activity-info">
                                            <span class="ap-activity-item-title"><?php echo esc_html($act['title']); ?></span>
                                            <span class="ap-activity-item-sub"><?php echo wp_kses_post($act['sub']); ?></span>
                                        </div>
                                        <span class="ap-activity-time"><?php echo esc_html($act['time_ago']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alquipress-empty-state">
                                <span class="dashicons dashicons-backup"></span>
                                <p><?php esc_html_e('No hay actividad reciente.', 'alquipress'); ?></p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
                </main>
            </div>
        </div>
        <?php
    }
}

new Alquipress_Dashboard_Widgets();
