<?php
/**
 * Módulo: Dashboard Widgets CRM
 * Widgets informativos para el dashboard de WordPress
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Dashboard_Widgets
{

    public function __construct()
    {
        add_action('wp_dashboard_setup', [$this, 'add_widgets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
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
        $today = date('Y-m-d');

        // Obtener datos
        $checkins_today = $this->get_bookings_by_checkin_date($today);
        $checkouts_today = $this->get_bookings_by_checkout_date($today);

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
                <div class="revenue-period"><?php echo date_i18n('F Y'); ?></div>

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
        $today = date('Y-m-d');
        $occupied_today = $this->get_occupied_properties_count($today);

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
        $checkins_tomorrow = $this->get_bookings_by_checkin_date($tomorrow);
        if (!empty($checkins_tomorrow)) {
            $alerts[] = [
                'type' => 'info',
                'icon' => '📅',
                'message' => count($checkins_tomorrow) . ' check-in(s) programado(s) para mañana'
            ];
        }

        // Alerta: Pedidos pendientes de pago
        $pending_orders = wc_get_orders([
            'status' => 'pending',
            'limit' => -1,
        ]);
        if (!empty($pending_orders)) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'message' => count($pending_orders) . ' pedido(s) pendiente(s) de pago'
            ];
        }

        // Alerta: Pedidos en revisión de salida
        $review_orders = wc_get_orders([
            'status' => 'checkout-review',
            'limit' => -1,
        ]);
        if (!empty($review_orders)) {
            $alerts[] = [
                'type' => 'info',
                'icon' => '🔍',
                'message' => count($review_orders) . ' propiedad(es) en revisión de salida'
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

    private function get_bookings_by_checkin_date($date)
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

    private function get_bookings_by_checkout_date($date)
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

    private function get_order_property_name($order)
    {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                return $product->get_name();
            }
        }
        return '-';
    }

    private function get_revenue_between_dates($start_date, $end_date)
    {
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'processing', 'deposito-ok', 'in-progress'],
            'date_created' => $start_date . '...' . $end_date,
        ]);

        $total = 0;
        foreach ($orders as $order) {
            $total += $order->get_total();
        }

        return $total;
    }

    private function get_revenue_by_status($start_date, $end_date)
    {
        $statuses = ['completed', 'processing', 'deposito-ok', 'in-progress'];
        $revenue_by_status = [];

        foreach ($statuses as $status) {
            $orders = wc_get_orders([
                'limit' => -1,
                'status' => $status,
                'date_created' => $start_date . '...' . $end_date,
            ]);

            $total = 0;
            foreach ($orders as $order) {
                $total += $order->get_total();
            }

            if ($total > 0) {
                $revenue_by_status[$status] = $total;
            }
        }

        return $revenue_by_status;
    }

    private function get_occupied_properties_count($date)
    {
        global $wpdb;

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

        return count($occupied_properties);
    }

    private function get_owners_without_iban()
    {
        $owners = get_posts([
            'post_type' => 'propietario',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        $count = 0;
        foreach ($owners as $owner_id) {
            $iban = get_field('owner_iban', $owner_id);
            if (empty($iban)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Cargar estilos CSS
     */
    public function enqueue_assets($hook)
    {
        // Solo cargar en el dashboard
        if ($hook === 'index.php') {
            wp_enqueue_style(
                'alquipress-dashboard-widgets',
                ALQUIPRESS_URL . 'includes/modules/dashboard-widgets/assets/dashboard-widgets.css',
                [],
                ALQUIPRESS_VERSION
            );
        }
    }
}

new Alquipress_Dashboard_Widgets();
