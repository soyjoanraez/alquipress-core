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
        if ($page !== 'alquipress-bookings') {
            return;
        }
        if (apply_filters('alquipress_bookings_skip_resumen', false)) {
            return;
        }
        $this->render_page();
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
     * IDs de pedido con check-in en la fecha dada (desde wp_ap_booking).
     */
    private function get_bookings_by_checkin_date(string $date): array
    {
        if (!$this->has_ap_booking_table()) {
            return [];
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';
        return $wpdb->get_col(
            $wpdb->prepare("SELECT order_id FROM {$table} WHERE checkin = %s AND status IN ('held','confirmed') AND order_id > 0", $date)
        ) ?: [];
    }

    /**
     * IDs de pedido con check-out en la fecha dada (desde wp_ap_booking).
     */
    private function get_bookings_by_checkout_date(string $date): array
    {
        if (!$this->has_ap_booking_table()) {
            return [];
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';
        return $wpdb->get_col(
            $wpdb->prepare("SELECT order_id FROM {$table} WHERE checkout = %s AND status IN ('held','confirmed') AND order_id > 0", $date)
        ) ?: [];
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

        // KPIs desde wp_ap_booking (si la tabla existe)
        $revenue = 0;
        if ($this->has_ap_booking_table()) {
            global $wpdb;
            $ap_table = $wpdb->prefix . 'ap_booking';
            $active = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$ap_table} WHERE checkin <= %s AND checkout >= %s AND status IN ('held','confirmed')", $today, $today)
            );

            // Ingresos del mes: suma totales de pedidos vinculados a reservas Ap_Booking creadas este mes
            $order_ids_month = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT order_id FROM {$ap_table} WHERE DATE(created_at) BETWEEN %s AND %s AND order_id > 0 AND status IN ('held','confirmed')",
                    $month_start, $month_end
                )
            );
            foreach ((array) $order_ids_month as $oid) {
                $order = function_exists('wc_get_order') ? wc_get_order((int) $oid) : null;
                if ($order) {
                    $real_total = $order->get_meta('_apm_booking_total');
                    $revenue += (float) ($real_total !== '' && is_numeric($real_total) ? $real_total : $order->get_total());
                }
            }
        }

        $active_today = count($this->get_bookings_by_checkin_date($today));
        $active_yesterday = count($this->get_bookings_by_checkin_date(date('Y-m-d', strtotime('-1 day'))));
        $badge_today = $active_today - $active_yesterday;
        $badge_text = $badge_today > 0 ? '↑ +' . $badge_today . ' ' . __('hoy', 'alquipress') : ($badge_today < 0 ? $badge_today . ' ' . __('hoy', 'alquipress') : '');

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
        if (!$this->has_ap_booking_table()) {
            return [];
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE status IN ('held','confirmed') ORDER BY created_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        $bookings = [];
        foreach ((array) $rows as $row) {
            $booking    = class_exists('Ap_Booking') ? Ap_Booking::from_row($row) : null;
            $order      = ($booking && $booking->order_id && function_exists('wc_get_order'))
                ? wc_get_order($booking->order_id)
                : null;
            $checkin    = $row['checkin'] ?? '';
            $checkout   = $row['checkout'] ?? '';
            $guest      = $order
                ? (trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) ?: $order->get_billing_company() ?: '-')
                : '-';
            $prop_name  = get_the_title((int) ($row['product_id'] ?? 0)) ?: '-';
            $amount     = $order ? (float) $order->get_total() : (float) ($row['total'] ?? 0);
            $status     = $order ? $order->get_status() : '';
            list($status_label, $status_class) = $this->get_booking_status_badge($status, $checkin);
            $edit_url   = $order ? $order->get_edit_order_url() : '';
            $bookings[] = [
                'order_id'     => $row['order_id'] ?? 0,
                'date'         => $checkin ? date_i18n('j M Y', strtotime($checkin)) : '',
                'guest'        => $guest,
                'prop_name'    => $prop_name,
                'amount'       => $amount,
                'status_label' => $status_label,
                'status_class' => $status_class,
                'edit_url'     => $edit_url,
            ];
        }
        return $bookings;
    }

    /**
     * Comprobar si la tabla wp_ap_booking existe antes de lanzar queries.
     */
    private function has_ap_booking_table(): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        return $found === $table;
    }

    private function get_booking_tabs()
    {
        $base = admin_url('admin.php?page=alquipress-bookings');
        return [
            'resumen'  => ['label' => __('Resumen', 'alquipress'), 'icon' => 'dashicons-calendar-alt', 'url' => $base],
            'pipeline' => ['label' => __('Pipeline', 'alquipress'), 'icon' => 'dashicons-editor-table', 'url' => admin_url('admin.php?page=alquipress-pipeline')],
        ];
    }

    private function render_booking_tabs($current)
    {
        $tabs = $this->get_booking_tabs();
        ?>
        <nav class="ap-bookings-tabs-nav" role="tablist">
            <?php foreach ($tabs as $key => $tab) : ?>
                <a href="<?php echo esc_url($tab['url']); ?>" class="ap-bookings-tab <?php echo $key === $current ? 'is-active' : ''; ?>" role="tab">
                    <?php if (!empty($tab['icon'])) : ?><span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span><?php endif; ?>
                    <?php echo esc_html($tab['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    private function render_header_actions()
    {
        ?>
        <div class="ap-bookings-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-ses-export')); ?>" class="ap-bookings-new-btn ap-bookings-btn-ses" style="background:#0f766e;"><span class="dashicons dashicons-media-spreadsheet"></span> <?php esc_html_e('SES XML', 'alquipress'); ?></a>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=shop_order')); ?>" class="ap-bookings-new-btn"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nueva reserva', 'alquipress'); ?></a>
        </div>
        <?php
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
                    <?php $this->render_header_actions(); ?>
                </div>
            </header>

            <?php $this->render_booking_tabs('resumen'); ?>

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
                                        <th class="ap-bookings-th-action"><?php esc_html_e('Ver', 'alquipress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent)) : ?>
                                        <tr><td colspan="6" class="ap-bookings-empty"><?php esc_html_e('No hay reservas recientes.', 'alquipress'); ?></td></tr>
                                    <?php else : ?>
                                        <?php foreach ($recent as $b) : ?>
                                            <tr>
                                                <td><?php echo esc_html($b['date']); ?></td>
                                                <td><a href="<?php echo esc_url($b['edit_url']); ?>"><?php echo esc_html($b['guest']); ?></a></td>
                                                <td><?php echo esc_html($b['prop_name']); ?></td>
                                                <td class="ap-bookings-td-amount"><?php echo function_exists('wc_price') ? wc_price($b['amount']) : number_format_i18n($b['amount'], 2); ?></td>
                                                <td class="ap-bookings-td-status"><span class="ap-bookings-badge ap-bookings-badge-<?php echo esc_attr($b['status_class']); ?>"><?php echo esc_html($b['status_label']); ?></span></td>
                                                <td class="ap-bookings-td-action"><a href="<?php echo esc_url($b['edit_url']); ?>" class="ap-bookings-view-btn"><?php esc_html_e('View', 'alquipress'); ?></a></td>
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
