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
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        // Obtener check-ins de hoy
        $checkins_today = $this->get_bookings_by_checkin_date($today);

        // Obtener check-outs de hoy
        $checkouts_today = $this->get_bookings_by_checkout_date($today);

        echo '<div class="alquipress-movements-grid">';

        // Check-ins
        echo '<div class="movement-card checkin-card">';
        echo '<div class="movement-number">' . count($checkins_today) . '</div>';
        echo '<div class="movement-label">Check-ins Hoy</div>';
        echo '</div>';

        // Check-outs
        echo '<div class="movement-card checkout-card">';
        echo '<div class="movement-number">' . count($checkouts_today) . '</div>';
        echo '<div class="movement-label">Check-outs Hoy</div>';
        echo '</div>';

        echo '</div>';

        // Listado detallado
        if (!empty($checkins_today) || !empty($checkouts_today)) {
            echo '<table class="widefat alquipress-movements-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Huésped</th>';
            echo '<th>Propiedad</th>';
            echo '<th>Tipo</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            // Check-ins
            foreach ($checkins_today as $order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                    $property_name = $this->get_order_property_name($order);

                    echo '<tr>';
                    echo '<td><strong>' . esc_html($customer_name) . '</strong></td>';
                    echo '<td>' . esc_html($property_name) . '</td>';
                    echo '<td><span class="badge-checkin">✅ Check-in</span></td>';
                    echo '</tr>';
                }
            }

            // Check-outs
            foreach ($checkouts_today as $order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                    $property_name = $this->get_order_property_name($order);

                    echo '<tr>';
                    echo '<td><strong>' . esc_html($customer_name) . '</strong></td>';
                    echo '<td>' . esc_html($property_name) . '</td>';
                    echo '<td><span class="badge-checkout">🚪 Check-out</span></td>';
                    echo '</tr>';
                }
            }

            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p style="text-align: center; color: #666; padding: 20px 0;">No hay movimientos programados para hoy.</p>';
        }
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

        // Ingresos del mes actual
        $current_revenue = $this->get_revenue_between_dates($current_month_start, $current_month_end);

        // Ingresos del mes pasado
        $last_revenue = $this->get_revenue_between_dates($last_month_start, $last_month_end);

        // Calcular cambio porcentual
        $change_percentage = 0;
        if ($last_revenue > 0) {
            $change_percentage = (($current_revenue - $last_revenue) / $last_revenue) * 100;
        }

        echo '<div class="revenue-card">';
        echo '<div class="revenue-amount">' . wc_price($current_revenue) . '</div>';
        echo '<div class="revenue-period">' . date_i18n('F Y') . '</div>';
        echo '</div>';

        echo '<div class="revenue-comparison">';
        if ($change_percentage > 0) {
            echo '<span class="revenue-up">📈 +' . number_format($change_percentage, 1) . '%</span>';
        } elseif ($change_percentage < 0) {
            echo '<span class="revenue-down">📉 ' . number_format($change_percentage, 1) . '%</span>';
        } else {
            echo '<span class="revenue-neutral">➡️ Sin cambios</span>';
        }
        echo ' respecto al mes anterior';
        echo '</div>';

        // Desglose por estado
        $revenue_by_status = $this->get_revenue_by_status($current_month_start, $current_month_end);

        if (!empty($revenue_by_status)) {
            echo '<div class="revenue-breakdown">';
            echo '<h4 style="margin: 15px 0 10px; font-size: 13px; color: #666;">Desglose por Estado:</h4>';
            echo '<ul class="revenue-list">';

            foreach ($revenue_by_status as $status => $amount) {
                $status_labels = [
                    'completed' => 'Completado',
                    'processing' => 'Procesando',
                    'deposito-ok' => 'Depósito OK',
                    'in-progress' => 'En Curso',
                ];

                $label = $status_labels[$status] ?? ucfirst($status);

                echo '<li>';
                echo '<span class="status-label">' . esc_html($label) . '</span>';
                echo '<span class="status-amount">' . wc_price($amount) . '</span>';
                echo '</li>';
            }

            echo '</ul>';
            echo '</div>';
        }
    }

    /**
     * Widget: Estado de Propiedades
     */
    public function render_property_status()
    {
        // Total de propiedades (productos publicados)
        $total_properties = wp_count_posts('product')->publish;

        // Propiedades ocupadas hoy
        $today = date('Y-m-d');
        $occupied_today = $this->get_occupied_properties_count($today);

        // Calcular tasa de ocupación
        $occupancy_rate = 0;
        if ($total_properties > 0) {
            $occupancy_rate = ($occupied_today / $total_properties) * 100;
        }

        echo '<div class="property-status-grid">';

        // Total de propiedades
        echo '<div class="property-stat">';
        echo '<div class="stat-number">' . $total_properties . '</div>';
        echo '<div class="stat-label">Total Propiedades</div>';
        echo '</div>';

        // Propiedades ocupadas
        echo '<div class="property-stat occupied">';
        echo '<div class="stat-number">' . $occupied_today . '</div>';
        echo '<div class="stat-label">Ocupadas Hoy</div>';
        echo '</div>';

        // Propiedades disponibles
        $available_today = $total_properties - $occupied_today;
        echo '<div class="property-stat available">';
        echo '<div class="stat-number">' . $available_today . '</div>';
        echo '<div class="stat-label">Disponibles</div>';
        echo '</div>';

        echo '</div>';

        // Barra de ocupación
        echo '<div class="occupancy-bar-container">';
        echo '<div class="occupancy-bar" style="width: ' . round($occupancy_rate) . '%"></div>';
        echo '</div>';
        echo '<div class="occupancy-label">';
        echo 'Tasa de ocupación: <strong>' . number_format($occupancy_rate, 1) . '%</strong>';
        echo '</div>';
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
        if (!empty($alerts)) {
            echo '<ul class="alquipress-alerts-list">';
            foreach ($alerts as $alert) {
                $class = 'alert-' . $alert['type'];
                echo '<li class="' . esc_attr($class) . '">';
                echo '<span class="alert-icon">' . $alert['icon'] . '</span>';
                echo '<span class="alert-message">' . esc_html($alert['message']) . '</span>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p style="text-align: center; color: #46b450; padding: 20px 0; font-weight: 600;">';
            echo '✅ No hay alertas pendientes';
            echo '</p>';
        }
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
