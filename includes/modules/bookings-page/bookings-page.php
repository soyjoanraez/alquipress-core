<?php
/**
 * Módulo: Página Reservas / Booking Dashboard (diseño Pencil)
 * Dashboard de reservas: KPIs, requieren atención, reservas recientes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Bookings_Page
{
    public function __construct()
    {
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_section_assets']);
    }

    public function maybe_render_section($page)
    {
        if ($page === 'alquipress-bookings') {
            $this->render_page();
        }
    }

    public function enqueue_section_assets($page)
    {
        if ($page !== 'alquipress-bookings') {
            return;
        }
        wp_enqueue_style(
            'alquipress-bookings-page',
            ALQUIPRESS_URL . 'includes/modules/bookings-page/assets/bookings-page.css',
            [],
            ALQUIPRESS_VERSION
        );
    }

    /**
     * IDs de pedidos con check-in en la fecha dada. Compatible con HPOS y almacenamiento legacy (postmeta).
     */
    private function get_bookings_by_checkin_date($date)
    {
        if (!function_exists('wc_get_orders')) {
            return [];
        }
        $orders = wc_get_orders([
            'limit' => -1,
            'return' => 'ids',
            'meta_query' => [
                ['key' => '_booking_checkin_date', 'value' => $date, 'compare' => '='],
            ],
        ]);
        return is_array($orders) ? $orders : [];
    }

    /**
     * IDs de pedidos con check-out en la fecha dada. Compatible con HPOS y almacenamiento legacy (postmeta).
     */
    private function get_bookings_by_checkout_date($date)
    {
        if (!function_exists('wc_get_orders')) {
            return [];
        }
        $orders = wc_get_orders([
            'limit' => -1,
            'return' => 'ids',
            'meta_query' => [
                ['key' => '_booking_checkout_date', 'value' => $date, 'compare' => '='],
            ],
        ]);
        return is_array($orders) ? $orders : [];
    }

    private function get_order_property_name($order)
    {
        return Alquipress_Property_Helper::get_order_property_name($order);
    }

    private function get_product_location($product_id)
    {
        return Alquipress_Property_Helper::get_product_location($product_id);
    }

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

    private function get_kpis()
    {
        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $week_end = date('Y-m-d', strtotime('sunday this week'));
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-t');

        $active = 0;
        $checkins_week = 0;
        $checkouts_week = 0;
        for ($d = strtotime($week_start); $d <= strtotime($week_end); $d += 86400) {
            $checkins_week += count($this->get_bookings_by_checkin_date(date('Y-m-d', $d)));
            $checkouts_week += count($this->get_bookings_by_checkout_date(date('Y-m-d', $d)));
        }

        $orders = function_exists('wc_get_orders') ? wc_get_orders([
            'status' => ['processing', 'deposito-ok', 'in-progress', 'completed'],
            'limit' => -1,
            'return' => 'objects',
        ]) : [];
        $revenue = 0;
        foreach ($orders as $order) {
            $checkin = $order->get_meta('_booking_checkin_date');
            $checkout = $order->get_meta('_booking_checkout_date');
            if ($checkin && $checkout && $checkin <= $today && $checkout >= $today) {
                $active++;
            }
            $created = $order->get_date_created();
            if ($created && $month_start <= $created->format('Y-m-d') && $created->format('Y-m-d') <= $month_end) {
                $revenue += (float) $order->get_total();
            }
        }

        $active_today = count($this->get_bookings_by_checkin_date($today));
        $active_yesterday = count($this->get_bookings_by_checkin_date(date('Y-m-d', strtotime('-1 day'))));
        $badge_today = $active_today - $active_yesterday;
        $badge_text = $badge_today > 0 ? '↑ +' . $badge_today . ' ' . __('today', 'alquipress') : ($badge_today < 0 ? $badge_today . ' ' . __('today', 'alquipress') : '');

        return [
            'active_bookings' => $active,
            'active_badge' => $badge_text,
            'checkins_this_week' => $checkins_week,
            'revenue_this_month' => $revenue,
            'checkouts_this_week' => $checkouts_week,
        ];
    }

    private function get_requires_attention()
    {
        $alerts = [];
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        if (function_exists('wc_get_orders')) {
            $pending = wc_get_orders(['status' => 'pending', 'limit' => 10, 'return' => 'objects']);
            foreach ($pending as $order) {
                $total = (float) $order->get_total();
                $prop = $this->get_order_property_name($order);
                $guest = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) ?: $order->get_billing_company();
                $alerts[] = [
                    'title' => ($guest ?: __('Guest', 'alquipress')) . ' - ' . __('Pago pendiente', 'alquipress'),
                    'subtitle' => wc_price($total) . ' ' . sprintf(__('para %s', 'alquipress'), $prop),
                    'url' => $order->get_edit_order_url(),
                    'btn' => __('Cobrar', 'alquipress'),
                ];
            }

            $checkin_tomorrow = $this->get_bookings_by_checkin_date($tomorrow);
            foreach (array_slice($checkin_tomorrow, 0, 5) as $order_id) {
                $order = wc_get_order($order_id);
                if (!$order) {
                    continue;
                }
                $prop = $this->get_order_property_name($order);
                $guest = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) ?: $order->get_billing_company();
                $alerts[] = [
                    'title' => ($guest ?: __('Guest', 'alquipress')) . ' - ' . __('Check-in mañana', 'alquipress'),
                    'subtitle' => $prop,
                    'url' => $order->get_edit_order_url(),
                    'btn' => __('Ver reserva', 'alquipress'),
                ];
            }
        }
        return array_slice($alerts, 0, 5);
    }

    private function get_recent_bookings($limit = 8)
    {
        if (!function_exists('wc_get_orders')) {
            return [];
        }
        $orders = wc_get_orders([
            'limit' => $limit * 3,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
        ]);
        $bookings = [];
        foreach ($orders as $order) {
            if (!$order->get_meta('_booking_checkin_date')) {
                continue;
            }
            $product_id = (int) $order->get_meta('_booking_product_id');
            if (!$product_id) {
                foreach ($order->get_items() as $item) {
                    $product = is_object($item) && method_exists($item, 'get_product') ? $item->get_product() : null;
                    if ($product) {
                        $product_id = $product->get_id();
                        break;
                    }
                }
            }
            $checkin = $order->get_meta('_booking_checkin_date');
            $checkout = $order->get_meta('_booking_checkout_date');
            $guest = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) ?: $order->get_billing_company() ?: '-';
            $dates = ($checkin && $checkout) ? date_i18n('j M', strtotime($checkin)) . ' - ' . date_i18n('j M', strtotime($checkout)) : '';
            list($status_label, $status_class) = $this->get_booking_status_badge($order->get_status(), $checkin);
            $bookings[] = [
                'order_id' => $order->get_id(),
                'date' => $order->get_date_created() ? $order->get_date_created()->format('j M Y') : '',
                'guest' => $guest,
                'prop_name' => $this->get_order_property_name($order),
                'amount' => $order->get_total(),
                'status_label' => $status_label,
                'status_class' => $status_class,
                'edit_url' => $order->get_edit_order_url(),
            ];
            if (count($bookings) >= $limit) {
                break;
            }
        }
        return $bookings;
    }

    public function render_page()
    {
        $kpis = $this->get_kpis();
        $attention = $this->get_requires_attention();
        $recent = $this->get_recent_bookings(8);
        $orders_url = admin_url('edit.php?post_type=shop_order');
        $new_order_url = admin_url('post-new.php?post_type=shop_order');
        $pipeline_url = admin_url('admin.php?page=alquipress-pipeline');
        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap alquipress-bookings-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('bookings'); ?>
                <main class="ap-owners-main">
            <header class="ap-header">
                <div class="ap-header-left">
                    <h1 class="ap-header-title"><?php esc_html_e('Reservas', 'alquipress'); ?></h1>
                    <p class="ap-header-subtitle"><?php esc_html_e('Gestión detallada y KPIs de reservas activas', 'alquipress'); ?></p>
                </div>
                <div class="ap-header-right">
                    <div class="ap-bookings-view-toggle">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-bookings')); ?>" class="ap-bookings-view-btn ap-bookings-view-active"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Resumen', 'alquipress'); ?></a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-pipeline')); ?>" class="ap-bookings-view-btn"><span class="dashicons dashicons-editor-table"></span> <?php esc_html_e('Pipeline', 'alquipress'); ?></a>
                    </div>
                    <a href="<?php echo esc_url($new_order_url); ?>" class="ap-bookings-new-btn"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nueva reserva', 'alquipress'); ?></a>
                </div>
            </header>

            <div class="ap-bookings-kpi-row">
                <div class="ap-bookings-kpi-card ap-bookings-kpi-active">
                    <div class="ap-bookings-kpi-head">
                        <span class="ap-bookings-kpi-icon dashicons dashicons-yes-alt"></span>
                        <?php if (!empty($kpis['active_badge'])) : ?>
                            <span class="ap-bookings-kpi-badge"><?php echo esc_html($kpis['active_badge']); ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="ap-bookings-kpi-value"><?php echo (int) $kpis['active_bookings']; ?></span>
                    <span class="ap-bookings-kpi-label"><?php esc_html_e('Reservas activas', 'alquipress'); ?></span>
                </div>
                <div class="ap-bookings-kpi-card ap-bookings-kpi-checkins">
                    <span class="ap-bookings-kpi-icon dashicons dashicons-arrow-right-alt"></span>
                    <span class="ap-bookings-kpi-value"><?php echo (int) $kpis['checkins_this_week']; ?></span>
                    <span class="ap-bookings-kpi-label"><?php esc_html_e('Check-ins esta semana', 'alquipress'); ?></span>
                </div>
                <div class="ap-bookings-kpi-card ap-bookings-kpi-revenue">
                    <span class="ap-bookings-kpi-icon dashicons dashicons-money-alt"></span>
                    <span class="ap-bookings-kpi-value"><?php echo function_exists('wc_price') ? wc_price($kpis['revenue_this_month']) : number_format_i18n($kpis['revenue_this_month'], 2) . ' €'; ?></span>
                    <span class="ap-bookings-kpi-label"><?php esc_html_e('Ingresos este mes', 'alquipress'); ?></span>
                </div>
                <div class="ap-bookings-kpi-card ap-bookings-kpi-checkouts">
                    <span class="ap-bookings-kpi-icon dashicons dashicons-exit"></span>
                    <span class="ap-bookings-kpi-value"><?php echo (int) $kpis['checkouts_this_week']; ?></span>
                    <span class="ap-bookings-kpi-label"><?php esc_html_e('Check-outs esta semana', 'alquipress'); ?></span>
                </div>
            </div>

            <div class="ap-bookings-content-row">
                <div class="ap-bookings-attention-col">
                    <div class="ap-bookings-requires-attention">
                        <div class="ap-bookings-alert-header">
                            <span class="dashicons dashicons-warning"></span>
                            <h3 class="ap-bookings-alert-title"><?php esc_html_e('Requieren atención', 'alquipress'); ?></h3>
                            <span class="ap-bookings-alert-count"><?php echo count($attention); ?></span>
                        </div>
                        <div class="ap-bookings-alert-items">
                            <?php if (empty($attention)) : ?>
                                <p class="ap-bookings-alert-empty"><?php esc_html_e('No hay alertas pendientes.', 'alquipress'); ?></p>
                            <?php else : ?>
                                <?php foreach ($attention as $item) : ?>
                                    <div class="ap-bookings-alert-item">
                                        <div class="ap-bookings-alert-item-left">
                                            <span class="ap-bookings-alert-dot"></span>
                                            <div class="ap-bookings-alert-item-content">
                                                <span class="ap-bookings-alert-item-title"><?php echo esc_html($item['title']); ?></span>
                                                <span class="ap-bookings-alert-item-sub"><?php echo wp_kses_post($item['subtitle']); ?></span>
                                            </div>
                                        </div>
                                        <a href="<?php echo esc_url($item['url']); ?>" class="ap-bookings-alert-btn"><?php echo esc_html($item['btn']); ?></a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="ap-bookings-recent-col">
                    <div class="ap-bookings-recent-card">
                        <div class="ap-bookings-recent-header">
                            <h3 class="ap-bookings-recent-title"><?php esc_html_e('Reservas recientes', 'alquipress'); ?></h3>
                            <a href="<?php echo esc_url($orders_url); ?>" class="ap-bookings-recent-viewall"><?php esc_html_e('Ver todas', 'alquipress'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span></a>
                        </div>
                        <div class="ap-bookings-table-wrap">
                            <table class="ap-bookings-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('FECHA', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('HUÉSPED', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('PROPIEDAD', 'alquipress'); ?></th>
                                        <th class="ap-bookings-th-amount"><?php esc_html_e('IMPORTE', 'alquipress'); ?></th>
                                        <th class="ap-bookings-th-status"><?php esc_html_e('ESTADO', 'alquipress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent)) : ?>
                                        <tr><td colspan="5" class="ap-bookings-empty"><?php esc_html_e('No hay reservas recientes.', 'alquipress'); ?></td></tr>
                                    <?php else : ?>
                                        <?php foreach ($recent as $b) : ?>
                                            <tr>
                                                <td><?php echo esc_html($b['date']); ?></td>
                                                <td><a href="<?php echo esc_url($b['edit_url']); ?>"><?php echo esc_html($b['guest']); ?></a></td>
                                                <td><?php echo esc_html($b['prop_name']); ?></td>
                                                <td class="ap-bookings-td-amount"><?php echo function_exists('wc_price') ? wc_price($b['amount']) : number_format_i18n($b['amount'], 2); ?></td>
                                                <td class="ap-bookings-td-status"><span class="ap-bookings-badge ap-bookings-badge-<?php echo esc_attr($b['status_class']); ?>"><?php echo esc_html($b['status_label']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
                </main>
            </div>
        </div>
        <?php
    }
}

new Alquipress_Bookings_Page();
