<?php
/**
 * Módulo: Dashboard de reservas Ap_Booking
 * Próximas entradas/salidas y tabla de reservas activas desde Ap_Booking_Store.
 * Sustituye al módulo wc-bookings-dashboard.
 *
 * @package Alquipress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Ap_Bookings_Dashboard
{
    public function __construct()
    {
        add_action('alquipress_render_section', [$this, 'maybe_render'], 15);
    }

    public function maybe_render($page)
    {
        if ($page !== 'alquipress-bookings') {
            return;
        }
        $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : '';
        if ($view !== 'ap-dashboard') {
            return;
        }
        add_filter('alquipress_bookings_skip_resumen', '__return_true');
        $this->render();
    }

    private function render()
    {
        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        $base        = admin_url('admin.php?page=alquipress-bookings');
        $checkins    = $this->get_upcoming('checkin', 14);
        $checkouts   = $this->get_upcoming('checkout', 14);
        $active      = $this->get_active_now();
        ?>
        <div class="wrap alquipress-bookings-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('bookings'); ?>
                <main class="ap-owners-main">
                    <header class="ap-header">
                        <div class="ap-header-left">
                            <h1 class="ap-header-title"><?php esc_html_e('Dashboard de reservas', 'alquipress'); ?></h1>
                            <p class="ap-header-subtitle"><?php esc_html_e('Próximas entradas, salidas y reservas activas', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-header-right">
                            <a href="<?php echo esc_url($base); ?>" class="ap-bookings-new-btn">
                                <span class="dashicons dashicons-arrow-left-alt"></span>
                                <?php esc_html_e('Volver al resumen', 'alquipress'); ?>
                            </a>
                        </div>
                    </header>

                    <!-- Reservas activas ahora -->
                    <section class="ap-bookings-section">
                        <h2 class="ap-bookings-section-title">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Reservas activas hoy', 'alquipress'); ?>
                            <span class="ap-count-badge"><?php echo count($active); ?></span>
                        </h2>
                        <?php $this->render_booking_table($active); ?>
                    </section>

                    <div class="ap-bookings-two-col">
                        <!-- Próximos check-ins -->
                        <section class="ap-bookings-section">
                            <h2 class="ap-bookings-section-title">
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                                <?php esc_html_e('Próximos check-ins (14 días)', 'alquipress'); ?>
                            </h2>
                            <?php $this->render_booking_table($checkins); ?>
                        </section>

                        <!-- Próximos check-outs -->
                        <section class="ap-bookings-section">
                            <h2 class="ap-bookings-section-title">
                                <span class="dashicons dashicons-exit"></span>
                                <?php esc_html_e('Próximos check-outs (14 días)', 'alquipress'); ?>
                            </h2>
                            <?php $this->render_booking_table($checkouts); ?>
                        </section>
                    </div>
                </main>
            </div>
        </div>
        <style>
        .ap-bookings-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px; }
        .ap-bookings-section { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .ap-bookings-section-title { font-size: 15px; font-weight: 600; margin: 0 0 16px; display: flex; align-items: center; gap: 6px; }
        .ap-count-badge { background: #0f766e; color: #fff; border-radius: 20px; padding: 2px 10px; font-size: 12px; }
        .ap-db-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ap-db-table th { text-align: left; font-weight: 600; padding: 6px 10px; border-bottom: 2px solid #e5e7eb; color: #6b7280; font-size: 11px; text-transform: uppercase; }
        .ap-db-table td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .ap-db-table tr:last-child td { border-bottom: 0; }
        .ap-db-table a { color: #0f766e; text-decoration: none; }
        .ap-db-table a:hover { text-decoration: underline; }
        .ap-db-empty { color: #9ca3af; font-style: italic; padding: 12px 10px; }
        @media (max-width: 900px) { .ap-bookings-two-col { grid-template-columns: 1fr; } }
        </style>
        <?php
    }

    private function render_booking_table(array $bookings)
    {
        if (empty($bookings)) {
            echo '<p class="ap-db-empty">' . esc_html__('Sin reservas en este período.', 'alquipress') . '</p>';
            return;
        }
        echo '<table class="ap-db-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Propiedad', 'alquipress') . '</th>';
        echo '<th>' . esc_html__('Huésped', 'alquipress') . '</th>';
        echo '<th>' . esc_html__('Check-in', 'alquipress') . '</th>';
        echo '<th>' . esc_html__('Check-out', 'alquipress') . '</th>';
        echo '<th>' . esc_html__('Noches', 'alquipress') . '</th>';
        echo '<th>' . esc_html__('Total', 'alquipress') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($bookings as $row) {
            $nights = (int) $row['nights'];
            $edit_url = $row['edit_url'] ?? '';
            echo '<tr>';
            echo '<td>' . esc_html($row['prop']) . '</td>';
            echo '<td>' . ($edit_url ? '<a href="' . esc_url($edit_url) . '">' . esc_html($row['guest']) . '</a>' : esc_html($row['guest'])) . '</td>';
            echo '<td>' . esc_html(date_i18n('j M', strtotime($row['checkin']))) . '</td>';
            echo '<td>' . esc_html(date_i18n('j M', strtotime($row['checkout']))) . '</td>';
            echo '<td>' . $nights . '</td>';
            echo '<td>' . (function_exists('wc_price') ? wc_price($row['total']) : number_format_i18n($row['total'], 2) . ' €') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    private function get_upcoming(string $field, int $days): array
    {
        global $wpdb;
        $table  = $wpdb->prefix . 'ap_booking';
        $today  = gmdate('Y-m-d');
        $limit  = gmdate('Y-m-d', strtotime('+' . $days . ' days'));
        $rows   = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE {$field} BETWEEN %s AND %s AND status IN ('held','confirmed') ORDER BY {$field} ASC",
                $today, $limit
            ),
            ARRAY_A
        );
        return $this->hydrate_rows($rows);
    }

    private function get_active_now(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';
        $today = gmdate('Y-m-d');
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE checkin <= %s AND checkout >= %s AND status IN ('held','confirmed') ORDER BY checkin ASC",
                $today, $today
            ),
            ARRAY_A
        );
        return $this->hydrate_rows($rows);
    }

    private function hydrate_rows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $order    = ($row['order_id'] > 0 && function_exists('wc_get_order')) ? wc_get_order((int) $row['order_id']) : null;
            $guest    = $order
                ? (trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) ?: $order->get_billing_company() ?: '-')
                : '-';
            $nights   = $row['checkin'] && $row['checkout']
                ? (int) ((strtotime($row['checkout']) - strtotime($row['checkin'])) / DAY_IN_SECONDS)
                : 0;
            $out[] = [
                'prop'     => get_the_title((int) $row['product_id']) ?: '-',
                'guest'    => $guest,
                'checkin'  => $row['checkin'],
                'checkout' => $row['checkout'],
                'nights'   => $nights,
                'total'    => (float) $row['total'],
                'edit_url' => $order ? $order->get_edit_order_url() : '',
            ];
        }
        return $out;
    }
}

new Alquipress_Ap_Bookings_Dashboard();
