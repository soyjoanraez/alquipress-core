<?php
/**
 * Panel de Finanzas Avanzado integrado en el Dashboard.
 */
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$table_schedule = $wpdb->prefix . 'apm_payment_schedule';
$table_security = $wpdb->prefix . 'apm_security_deposits';
$tables_exist = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_schedule)) === $table_schedule)
    && ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_security)) === $table_security);

if (!$tables_exist) {
    require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
    ?>
    <div class="wrap alquipress-dashboard-page ap-has-sidebar">
        <div class="ap-owners-layout">
            <?php alquipress_render_sidebar('finances'); ?>
            <main class="ap-owners-main">
                <header class="ap-header">
                    <h1 class="ap-header-title"><?php esc_html_e('Gestión Financiera', 'alquipress'); ?></h1>
                    <p class="ap-header-subtitle"><?php esc_html_e('Control de ingresos, saldos programados y fianzas', 'alquipress'); ?></p>
                </header>
                <div class="notice notice-warning inline" style="margin: 20px 0; padding: 16px;">
                    <p><strong><?php esc_html_e('Módulo APM no inicializado', 'alquipress'); ?></strong></p>
                    <p><?php esc_html_e('Las tablas de pagos programados y fianzas no existen. Activa o configura el módulo Alquipress Payment Manager para usar esta sección.', 'alquipress'); ?></p>
                    <p><a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-settings')); ?>" class="button"><?php esc_html_e('Ir a Ajustes', 'alquipress'); ?></a></p>
                </div>
            </main>
        </div>
    </div>
    <?php
    return;
}

// 1. Obtener KPIs Financieros
$today = current_time('Y-m-d');
$month_start = date('Y-m-01 00:00:00');
$month_end = date('Y-m-t 23:59:59');

// Ingresos Brutos (Real de Alquipress)
$sql_revenue = "
    SELECT SUM(COALESCE(CAST(pm_real.meta_value AS DECIMAL(12,2)), CAST(pm_wc.meta_value AS DECIMAL(12,2))))
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm_wc ON p.ID = pm_wc.post_id AND pm_wc.meta_key = '_order_total'
    LEFT JOIN {$wpdb->postmeta} pm_real ON p.ID = pm_real.post_id AND pm_real.meta_key = '_apm_booking_total'
    WHERE p.post_type = 'shop_order'
    AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-deposito-ok', 'wc-in-progress')
    AND p.post_date >= %s AND p.post_date <= %s
";
$total_revenue = (float) $wpdb->get_var($wpdb->prepare($sql_revenue, $month_start, $month_end));

// Saldos Pendientes de cobro (desde la tabla de pagos programados)
$pending_balances = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(amount) FROM {$table_schedule} WHERE status = 'pending' AND scheduled_date >= %s",
    $today . ' 00:00:00'
));

// Fianzas Retenidas actualmente
$active_security = (float) $wpdb->get_var(
    "SELECT SUM(amount) FROM {$table_security} WHERE status = 'held'"
);

// 2. Obtener Próximos Cobros (Próximos 10)
$upcoming_payments = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, p.post_title as order_title 
     FROM {$table_schedule} s
     INNER JOIN {$wpdb->posts} p ON s.order_id = p.ID
     WHERE s.status = 'pending' 
     ORDER BY s.scheduled_date ASC 
     LIMIT 10"
));

require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
?>
<div class="wrap alquipress-dashboard-page ap-has-sidebar">
    <div class="ap-owners-layout">
        <?php alquipress_render_sidebar('finances'); ?>
        <main class="ap-owners-main">
            <header class="ap-header">
                <div class="ap-header-left">
                    <h1 class="ap-header-title"><?php esc_html_e('Gestión Financiera', 'alquipress'); ?></h1>
                    <p class="ap-header-subtitle"><?php esc_html_e('Control de ingresos, saldos programados y fianzas', 'alquipress'); ?></p>
                </div>
                <div class="ap-header-right">
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order')); ?>" class="ap-reports-refresh">
                        <span class="dashicons dashicons-list-view"></span> Ver Pedidos
                    </a>
                </div>
            </header>

            <!-- KPIs Financieros -->
            <div class="ap-metrics-row">
                <div class="ap-metric-card">
                    <span class="ap-metric-label"><?php esc_html_e('Ingresos Brutos (Mes)', 'alquipress'); ?></span>
                    <div class="ap-metric-value-row">
                        <span class="ap-metric-value" style="color: #059669;"><?php echo wc_price($total_revenue); ?></span>
                    </div>
                </div>
                <div class="ap-metric-card">
                    <span class="ap-metric-label"><?php esc_html_e('Saldos Pendientes', 'alquipress'); ?></span>
                    <div class="ap-metric-value-row">
                        <span class="ap-metric-value" style="color: #3b82f6;"><?php echo wc_price($pending_balances); ?></span>
                        <span class="ap-metric-change"><?php echo count($upcoming_payments); ?> cobros</span>
                    </div>
                </div>
                <div class="ap-metric-card">
                    <span class="ap-metric-label"><?php esc_html_e('Fianzas Retenidas', 'alquipress'); ?></span>
                    <div class="ap-metric-value-row">
                        <span class="ap-metric-value" style="color: #8b5cf6;"><?php echo wc_price($active_security); ?></span>
                    </div>
                </div>
            </div>

            <div class="ap-content-row">
                <!-- Columna Izquierda: Próximos Cobros -->
                <div class="ap-content-left" style="flex: 2;">
                    <section class="ap-recent-bookings">
                        <div class="ap-recent-bookings-header">
                            <h2 class="ap-recent-bookings-title"><?php esc_html_e('Próximos Cobros Automáticos', 'alquipress'); ?></h2>
                        </div>
                        <table class="ap-bookings-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Pedido', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Fecha Programada', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Importe', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Estado', 'alquipress'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($upcoming_payments)) : ?>
                                    <?php foreach ($upcoming_payments as $payment) : ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo $payment->order_id; ?></strong>
                                                <div style="font-size: 11px; color: #64748b;">Saldo restante</div>
                                            </td>
                                            <td><?php echo wp_date('d/m/Y', strtotime($payment->scheduled_date)); ?></td>
                                            <td><strong><?php echo wc_price($payment->amount); ?></strong></td>
                                            <td><span class="ap-booking-status status-processing">Pendiente</span></td>
                                            <td style="text-align: right;">
                                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $payment->order_id . '&action=edit')); ?>" class="button button-small">Gestionar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                            No hay cobros programados próximamente.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </section>
                </div>

                <!-- Columna Derecha: Accesos Rápidos y Ayuda -->
                <div class="ap-content-right" style="flex: 1;">
                    <div class="ap-recent-activity">
                        <div class="ap-recent-activity-header">
                            <h2 class="ap-recent-activity-title"><?php esc_html_e('Herramientas', 'alquipress'); ?></h2>
                        </div>
                        <div style="padding: 20px; display: flex; flex-direction: column; gap: 12px;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=stripe')); ?>" class="button button-secondary" style="width: 100%; text-align: center;">
                                ⚙️ Configuración Stripe
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-reports&tab=orders&report_res=month')); ?>" class="button button-secondary" style="width: 100%; text-align: center;">
                                📊 Informes WooCommerce
                            </a>
                            <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; font-size: 12px; color: #475569; border: 1px solid #e2e8f0;">
                                <strong>Nota sobre Cobros:</strong> El sistema Alquipress Payment Manager intenta realizar el cobro del segundo pago automáticamente en la tarjeta guardada del cliente el día programado. Si el cobro falla, recibirás una alerta en el Dashboard.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>