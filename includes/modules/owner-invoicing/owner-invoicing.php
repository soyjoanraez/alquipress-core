<?php
/**
 * Módulo: Facturación para propietarios
 * Genera facturas PDF/HTML con el desglose a pagar a cada propietario
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Owner_Invoicing
{
    const CPT = 'owner_invoice';

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'register_cpt']);
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('admin_init', [$this, 'handle_generate']);
        add_action('admin_init', [$this, 'handle_view_invoice']);
    }

    public function register_cpt()
    {
        register_post_type(self::CPT, [
            'labels' => [
                'name' => __('Facturas propietarios', 'alquipress'),
                'singular_name' => __('Factura propietario', 'alquipress'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'manage_options',
            'supports' => ['title'],
        ]);
    }

    public function handle_view_invoice()
    {
        if (!isset($_GET['alquipress_view_invoice']) || !isset($_GET['id']) || !current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('view_invoice_' . (int) $_GET['id']);
        $id = (int) $_GET['id'];
        $post = get_post($id);
        if (!$post || $post->post_type !== self::CPT) {
            wp_die(esc_html__('Factura no encontrada', 'alquipress'));
        }
        $html = $post->post_content;
        if ($html) {
            echo $html;
            exit;
        }
    }

    public function handle_generate()
    {
        if (!isset($_POST['alquipress_generate_invoice']) || !current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('alquipress_generate_invoice');

        $owner_id = isset($_POST['owner_id']) ? absint($_POST['owner_id']) : 0;
        $start = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : '';
        $end = isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : '';

        if (!$owner_id || !$start || !$end) {
            add_settings_error(
                'alquipress_invoicing',
                'missing_data',
                __('Selecciona propietario y rango de fechas.', 'alquipress'),
                'error'
            );
            return;
        }

        $revenue = $this->get_owner_revenue($owner_id, $start, $end);
        if ($revenue['total'] <= 0) {
            add_settings_error(
                'alquipress_invoicing',
                'no_data',
                __('No hay ingresos en el periodo seleccionado para este propietario.', 'alquipress'),
                'warning'
            );
            return;
        }

        $invoice_id = $this->create_invoice($owner_id, $start, $end, $revenue);
        if ($invoice_id) {
            $view_url = wp_nonce_url(
                add_query_arg(['alquipress_view_invoice' => 1, 'id' => $invoice_id], admin_url('admin.php')),
                'view_invoice_' . $invoice_id
            );
            wp_safe_redirect($view_url);
            exit;
        }
    }

    private function get_owner_revenue($owner_id, $start, $end)
    {
        if (class_exists('Alquipress_Owner_Revenue')) {
            return Alquipress_Owner_Revenue::get_instance()->calculate_owner_revenue($owner_id, $start, $end);
        }
        return ['total' => 0, 'commission' => 0, 'net' => 0, 'count' => 0, 'properties' => []];
    }

    private function create_invoice($owner_id, $start, $end, $revenue)
    {
        $owner = get_post($owner_id);
        $owner_name = $owner ? $owner->post_title : '';
        $html = $this->render_invoice_html($owner_id, $owner_name, $start, $end, $revenue);

        $title = sprintf(
            __('Factura %s - %s a %s', 'alquipress'),
            $owner_name,
            date_i18n('d/m/Y', strtotime($start)),
            date_i18n('d/m/Y', strtotime($end))
        );

        $post_id = wp_insert_post([
            'post_type' => self::CPT,
            'post_title' => $title,
            'post_content' => $html,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ], true);

        if (is_wp_error($post_id)) {
            return 0;
        }

        update_post_meta($post_id, '_owner_id', $owner_id);
        update_post_meta($post_id, '_start_date', $start);
        update_post_meta($post_id, '_end_date', $end);
        update_post_meta($post_id, '_total', $revenue['total']);
        update_post_meta($post_id, '_commission', $revenue['commission']);
        update_post_meta($post_id, '_net', $revenue['net']);
        update_post_meta($post_id, '_bookings_count', $revenue['count']);

        return $post_id;
    }

    private function render_invoice_html($owner_id, $owner_name, $start, $end, $revenue)
    {
        $site_name = get_bloginfo('name');
        $today = current_time(get_option('date_format'));
        $start_fmt = date_i18n(get_option('date_format'), strtotime($start));
        $end_fmt = date_i18n(get_option('date_format'), strtotime($end));
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_attr(sprintf(__('Factura %s', 'alquipress'), $owner_name)); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.5; color: #333; max-width: 800px; margin: 0 auto; padding: 40px; }
        @media print { body { padding: 20px; } }
        h1 { font-size: 24px; margin: 0 0 8px; }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #2c99e2; }
        .invoice-title { font-size: 28px; font-weight: 700; color: #2c99e2; }
        .invoice-meta { text-align: right; color: #666; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin: 24px 0; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        .amount { text-align: right; font-variant-numeric: tabular-nums; }
        .total-row { font-weight: 700; font-size: 18px; background: #f0fdf4; color: #166534; }
        .total-row td { border-bottom: none; padding-top: 20px; }
        .footer { margin-top: 48px; padding-top: 24px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #64748b; text-align: center; }
        @media print { .no-print { display: none !important; } }
        .print-btn { background: #2c99e2; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin: 20px 0; }
        .print-btn:hover { background: #2380c4; }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div>
            <div class="invoice-title"><?php echo esc_html($site_name); ?></div>
            <div style="margin-top: 4px; color: #64748b;"><?php esc_html_e('Factura de liquidación a propietario', 'alquipress'); ?></div>
        </div>
        <div class="invoice-meta">
            <div><?php echo esc_html($today); ?></div>
            <div><?php printf(esc_html__('Periodo: %s - %s', 'alquipress'), esc_html($start_fmt), esc_html($end_fmt)); ?></div>
        </div>
    </div>

    <h1><?php echo esc_html($owner_name); ?></h1>
    <p><?php printf(esc_html__('Resumen de ingresos y liquidación para el periodo indicado.', 'alquipress')); ?></p>

    <table>
        <thead>
            <tr>
                <th><?php esc_html_e('Concepto', 'alquipress'); ?></th>
                <th class="amount"><?php esc_html_e('Importe', 'alquipress'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php esc_html_e('Ingresos brutos', 'alquipress'); ?> (<?php echo (int) $revenue['count']; ?> <?php echo $revenue['count'] === 1 ? esc_html__('reserva', 'alquipress') : esc_html__('reservas', 'alquipress'); ?>)</td>
                <td class="amount"><?php echo wc_price($revenue['total']); ?></td>
            </tr>
            <tr>
                <td><?php esc_html_e('Comisión agencia', 'alquipress'); ?></td>
                <td class="amount">- <?php echo wc_price($revenue['commission']); ?></td>
            </tr>
            <?php if (!empty($revenue['properties'])) : ?>
            <tr>
                <td colspan="2" style="padding-top: 16px;"><strong><?php esc_html_e('Desglose por propiedad:', 'alquipress'); ?></strong></td>
            </tr>
            <?php foreach ($revenue['properties'] as $prop) : ?>
            <tr>
                <td style="padding-left: 24px;"><?php echo esc_html($prop['name']); ?> — <?php echo (int) $prop['bookings']; ?> <?php echo $prop['bookings'] === 1 ? esc_html__('reserva', 'alquipress') : esc_html__('reservas', 'alquipress'); ?></td>
                <td class="amount"><?php echo wc_price($prop['revenue']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            <tr class="total-row">
                <td><?php esc_html_e('Neto a pagar al propietario', 'alquipress'); ?></td>
                <td class="amount"><?php echo wc_price($revenue['net']); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p><?php echo esc_html($site_name); ?> — <?php esc_html_e('Documento generado automáticamente. Para imprimir o guardar como PDF, usa la función Imprimir de tu navegador.', 'alquipress'); ?></p>
    </div>

    <div class="no-print" style="margin-top: 24px;">
        <button type="button" class="print-btn" onclick="window.print();"><?php esc_html_e('Imprimir / Guardar PDF', 'alquipress'); ?></button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-owner-invoicing')); ?>" style="margin-left: 12px; color: #64748b;"><?php esc_html_e('Volver', 'alquipress'); ?></a>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    public function maybe_render_section($page)
    {
        if ($page === 'alquipress-owner-invoicing') {
            $this->render_page();
        }
    }

    public function render_page()
    {
        if (isset($_GET['alquipress_view_invoice'])) {
            return;
        }

        $owners = get_posts([
            'post_type' => 'propietario',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $start = date('Y-m-01');
        $end = date('Y-m-d');

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        settings_errors('alquipress_invoicing');
        ?>
        <div class="wrap alquipress-invoicing-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('finances'); ?>
                <main class="ap-owners-main">
                    <header class="ap-header">
                        <h1 class="ap-header-title"><?php esc_html_e('Facturación propietarios', 'alquipress'); ?></h1>
                        <p class="ap-header-subtitle"><?php esc_html_e('Generar facturas de liquidación por periodo', 'alquipress'); ?></p>
                    </header>

                    <form method="post" class="ap-invoicing-form" style="max-width: 480px; background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb;">
                        <?php wp_nonce_field('alquipress_generate_invoice'); ?>
                        <input type="hidden" name="alquipress_generate_invoice" value="1">

                        <p>
                            <label for="owner_id" style="display: block; margin-bottom: 6px; font-weight: 500;"><?php esc_html_e('Propietario', 'alquipress'); ?></label>
                            <select name="owner_id" id="owner_id" required style="width: 100%; padding: 8px 12px;">
                                <option value=""><?php esc_html_e('Seleccionar propietario', 'alquipress'); ?></option>
                                <?php foreach ($owners as $owner) : ?>
                                <option value="<?php echo (int) $owner->ID; ?>"><?php echo esc_html($owner->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>

                        <p>
                            <label for="start_date" style="display: block; margin-bottom: 6px; font-weight: 500;"><?php esc_html_e('Desde', 'alquipress'); ?></label>
                            <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start); ?>" required style="width: 100%; padding: 8px 12px;">
                        </p>

                        <p>
                            <label for="end_date" style="display: block; margin-bottom: 6px; font-weight: 500;"><?php esc_html_e('Hasta', 'alquipress'); ?></label>
                            <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end); ?>" required style="width: 100%; padding: 8px 12px;">
                        </p>

                        <p>
                            <button type="submit" class="button button-primary button-hero"><?php esc_html_e('Generar factura', 'alquipress'); ?></button>
                        </p>
                    </form>

                    <div style="margin-top: 32px;">
                        <h2 style="font-size: 18px; margin-bottom: 12px;"><?php esc_html_e('Facturas generadas', 'alquipress'); ?></h2>
                        <?php
                        $invoices = get_posts([
                            'post_type' => self::CPT,
                            'post_status' => 'any',
                            'posts_per_page' => 10,
                            'orderby' => 'date',
                            'order' => 'DESC',
                        ]);
                        ?>
                        <?php if (!empty($invoices)) : ?>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Factura', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Fecha', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Acciones', 'alquipress'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $inv) : ?>
                                <tr>
                                    <td><?php echo esc_html($inv->post_title); ?></td>
                                    <td><?php echo esc_html(get_the_date('', $inv)); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['alquipress_view_invoice' => 1, 'id' => $inv->ID], admin_url('admin.php')), 'view_invoice_' . $inv->ID)); ?>">
                                            <?php esc_html_e('Ver / Imprimir', 'alquipress'); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else : ?>
                        <p style="color: #64748b;"><?php esc_html_e('No hay facturas generadas aún.', 'alquipress'); ?></p>
                        <?php endif; ?>
                    </div>
                </main>
            </div>
        </div>
        <?php
    }
}

Alquipress_Owner_Invoicing::get_instance();
