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
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_section_assets']);

        add_action('wp_ajax_alquipress_get_report_data', [$this, 'ajax_get_report_data']);
        add_action('wp_ajax_alquipress_export_reports_csv', [$this, 'ajax_export_reports_csv']);
        add_action('wp_ajax_alquipress_email_report', [$this, 'ajax_email_report']);
    }

    public function maybe_render_section($page)
    {
        if ($page === 'alquipress-reports') {
            $this->render_reports_page();
        }
    }

    /**
     * Renderizar página de informes (diseño Pencil: Reports Dashboard)
     */
    public function render_reports_page()
    {
        $current_year = date('Y');
        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap alquipress-reports-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('reports'); ?>
                <main class="ap-owners-main">
            <header class="ap-reports-header">
                <div class="ap-reports-header-left">
                    <h1 class="ap-reports-title"><?php esc_html_e('Informes', 'alquipress'); ?></h1>
                    <p class="ap-reports-subtitle"><?php esc_html_e('Ingresos, rendimiento de reservas y analíticas', 'alquipress'); ?></p>
                </div>
                <div class="ap-reports-header-right">
                    <div class="ap-reports-year-wrap">
                        <label for="report-year" class="screen-reader-text"><?php esc_html_e('Año', 'alquipress'); ?></label>
                        <select id="report-year">
                            <?php for ($y = $current_year; $y >= $current_year - 5; $y--) : ?>
                                <option value="<?php echo (int) $y; ?>"><?php echo (int) $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="button" id="refresh-reports" class="ap-reports-refresh"><?php esc_html_e('Actualizar', 'alquipress'); ?></button>
                </div>
            </header>

            <!-- Métricas (Pencil: Total Revenue, Total Bookings, Occupancy Rate, Avg Daily Rate) -->
            <div class="ap-reports-metrics-row">
                <div class="ap-reports-metric-card">
                    <span class="ap-reports-metric-label"><?php esc_html_e('Ingresos totales', 'alquipress'); ?></span>
                    <div class="ap-reports-metric-value-row">
                        <span class="ap-reports-metric-value" id="stat-revenue-year">—</span>
                        <span class="ap-reports-metric-change ap-reports-change-positive" id="stat-revenue-change">—</span>
                    </div>
                </div>
                <div class="ap-reports-metric-card">
                    <span class="ap-reports-metric-label"><?php esc_html_e('Reservas totales', 'alquipress'); ?></span>
                    <div class="ap-reports-metric-value-row">
                        <span class="ap-reports-metric-value" id="stat-bookings-year">—</span>
                        <span class="ap-reports-metric-change ap-reports-change-positive" id="stat-bookings-change">—</span>
                    </div>
                </div>
                <div class="ap-reports-metric-card">
                    <span class="ap-reports-metric-label"><?php esc_html_e('Tasa de ocupación', 'alquipress'); ?></span>
                    <div class="ap-reports-metric-value-row">
                        <span class="ap-reports-metric-value" id="stat-occupancy-rate">—</span>
                        <span class="ap-reports-metric-change ap-reports-change-positive" id="stat-occupancy-change">—</span>
                    </div>
                </div>
                <div class="ap-reports-metric-card">
                    <span class="ap-reports-metric-label"><?php esc_html_e('Precio medio diario', 'alquipress'); ?></span>
                    <div class="ap-reports-metric-value-row">
                        <span class="ap-reports-metric-value" id="stat-avg-daily-rate">—</span>
                        <span class="ap-reports-metric-change ap-reports-change-positive" id="stat-adr-change">—</span>
                    </div>
                </div>
            </div>

            <div class="ap-reports-content-row">
                <div class="ap-reports-left-col">
                    <!-- Revenue Breakdown (chart) -->
                    <div class="ap-reports-card ap-reports-card-chart">
                        <div class="ap-reports-card-head">
                            <h3 class="ap-reports-card-title"><?php esc_html_e('Desglose de ingresos', 'alquipress'); ?></h3>
                            <div class="ap-reports-chart-filters">
                                <button type="button" class="ap-reports-filter-pill active" data-view="property"><?php esc_html_e('Por propiedad', 'alquipress'); ?></button>
                                <button type="button" class="ap-reports-filter-pill" data-view="month"><?php esc_html_e('Por mes', 'alquipress'); ?></button>
                            </div>
                        </div>
                        <div class="ap-reports-chart-wrap">
                            <canvas id="chart-revenue-monthly" style="max-height: 320px;"></canvas>
                        </div>
                    </div>

                    <!-- Booking Performance (tabla) -->
                    <div class="ap-reports-card ap-reports-card-table">
                        <h3 class="ap-reports-card-title"><?php esc_html_e('Rendimiento por propiedad', 'alquipress'); ?></h3>
                        <div class="ap-reports-table-wrap">
                            <table class="ap-reports-perf-table" id="table-booking-performance">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Propiedad', 'alquipress'); ?></th>
                                        <th class="ap-reports-th-num"><?php esc_html_e('Reservas', 'alquipress'); ?></th>
                                        <th class="ap-reports-th-num"><?php esc_html_e('Ingresos', 'alquipress'); ?></th>
                                        <th class="ap-reports-th-num"><?php esc_html_e('Ocupación', 'alquipress'); ?></th>
                                        <th class="ap-reports-th-trend"><?php esc_html_e('Tendencia', 'alquipress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="5" class="ap-reports-loading"><?php esc_html_e('Cargando...', 'alquipress'); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="ap-reports-right-col">
                    <!-- Export Reports -->
                    <div class="ap-reports-card ap-reports-export-card">
                        <h3 class="ap-reports-card-title"><?php esc_html_e('Exportar informes', 'alquipress'); ?></h3>
                        <div class="ap-reports-export-buttons">
                            <button type="button" class="ap-reports-btn ap-reports-btn-primary" id="export-excel"><?php esc_html_e('Exportar a Excel', 'alquipress'); ?></button>
                            <button type="button" class="ap-reports-btn ap-reports-btn-outline" id="export-pdf"><?php esc_html_e('Exportar a PDF', 'alquipress'); ?></button>
                            <button type="button" class="ap-reports-btn ap-reports-btn-outline" id="email-report"><?php esc_html_e('Enviar por email', 'alquipress'); ?></button>
                        </div>
                    </div>

                    <!-- Report Filters -->
                    <div class="ap-reports-card ap-reports-filters-card">
                        <h3 class="ap-reports-card-title"><?php esc_html_e('Filtros del informe', 'alquipress'); ?></h3>
                        <dl class="ap-reports-filter-list">
                            <div class="ap-reports-filter-row">
                                <dt><?php esc_html_e('Período', 'alquipress'); ?></dt>
                                <dd id="filter-date-range"><?php echo esc_html(sprintf(__('Últimos 12 meses (%s)', 'alquipress'), $current_year)); ?></dd>
                            </div>
                            <div class="ap-reports-filter-row">
                                <dt><?php esc_html_e('Tipo de propiedad', 'alquipress'); ?></dt>
                                <dd><?php esc_html_e('Todas', 'alquipress'); ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Tabs de informes detallados -->
            <div class="ap-reports-tabs-wrap">
                <div class="reports-tabs">
                    <button class="tab-button active" data-tab="revenue"><?php esc_html_e('Ingresos', 'alquipress'); ?></button>
                    <button class="tab-button" data-tab="occupancy"><?php esc_html_e('Ocupación', 'alquipress'); ?></button>
                    <button class="tab-button" data-tab="clients"><?php esc_html_e('Clientes', 'alquipress'); ?></button>
                    <button class="tab-button" data-tab="properties"><?php esc_html_e('Propiedades', 'alquipress'); ?></button>
                </div>

                <div class="reports-content">
                    <div id="tab-revenue" class="tab-content active">
                        <div class="report-section">
                            <h2><?php esc_html_e('Ingresos Mensuales', 'alquipress'); ?></h2>
                            <canvas id="chart-revenue-monthly-tab" style="max-height: 400px;"></canvas>
                        </div>
                        <div class="report-section">
                            <h2><?php esc_html_e('Ingresos por Temporada', 'alquipress'); ?></h2>
                            <canvas id="chart-revenue-season" style="max-height: 350px;"></canvas>
                        </div>
                    </div>
                    <div id="tab-occupancy" class="tab-content">
                        <div class="report-section">
                            <h2><?php esc_html_e('Tasa de Ocupación Mensual', 'alquipress'); ?></h2>
                            <canvas id="chart-occupancy-monthly" style="max-height: 400px;"></canvas>
                        </div>
                        <div class="report-section">
                            <h2><?php esc_html_e('Noches Reservadas vs Disponibles', 'alquipress'); ?></h2>
                            <canvas id="chart-occupancy-comparison" style="max-height: 350px;"></canvas>
                        </div>
                    </div>
                    <div id="tab-clients" class="tab-content">
                        <div class="report-section">
                            <h2><?php esc_html_e('Top 5 Clientes por Gasto Total', 'alquipress'); ?></h2>
                            <table class="wp-list-table widefat fixed striped" id="table-top-clients">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Posición', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Cliente', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Email', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Total Reservas', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Gasto Total', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Última Reserva', 'alquipress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody><tr><td colspan="6"><?php esc_html_e('Cargando...', 'alquipress'); ?></td></tr></tbody>
                            </table>
                        </div>
                        <div class="report-section">
                            <h2><?php esc_html_e('Distribución por Valoración', 'alquipress'); ?></h2>
                            <canvas id="chart-clients-rating" style="max-height: 300px;"></canvas>
                        </div>
                    </div>
                    <div id="tab-properties" class="tab-content">
                        <div class="report-section">
                            <h2><?php esc_html_e('Top 5 Propiedades Más Rentables', 'alquipress'); ?></h2>
                            <table class="wp-list-table widefat fixed striped" id="table-top-properties">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Posición', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Propiedad', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Total Reservas', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Noches', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Ingresos', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Ocupación', 'alquipress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody><tr><td colspan="6"><?php esc_html_e('Cargando...', 'alquipress'); ?></td></tr></tbody>
                            </table>
                        </div>
                        <div class="report-section">
                            <h2><?php esc_html_e('Comparativa por Propiedad', 'alquipress'); ?></h2>
                            <canvas id="chart-properties-comparison" style="max-height: 400px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
                </main>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Obtener datos para informes
     */
    public function ajax_get_report_data()
    {
        // Rate limiting: 120 requests por minuto para soportar cargas completas del dashboard
        Alquipress_Rate_Limiter::check_and_exit('get_report_data', 120, 60);

        check_ajax_referer('alquipress_reports', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes'], 403);
        }

        $report_type = isset($_POST['report_type']) ? sanitize_key($_POST['report_type']) : '';
        $year = isset($_POST['year']) ? absint($_POST['year']) : date('Y');

        // Validar report_type
        $allowed_reports = [
            'overview',
            'overview_yoy',
            'revenue_monthly',
            'revenue_monthly_yoy',
            'revenue_season',
            'occupancy_monthly',
            'occupancy_comparison',
            'top_clients',
            'clients_rating',
            'top_properties',
            'properties_comparison'
        ];

        if (!in_array($report_type, $allowed_reports, true)) {
            wp_send_json_error(['message' => 'Tipo de reporte inválido'], 400);
        }

        // Validar año
        if ($year < 2000 || $year > 2100) {
            wp_send_json_error(['message' => 'Año inválido'], 400);
        }

        // Whitelist de tipos de reporte válidos
        $valid_report_types = [
            'overview',
            'overview_yoy',
            'revenue_monthly',
            'revenue_monthly_yoy',
            'revenue_season',
            'occupancy_monthly',
            'occupancy_comparison',
            'top_clients',
            'clients_rating',
            'top_properties',
            'properties_comparison'
        ];

        // Validar tipo de reporte
        if (empty($report_type) || !in_array($report_type, $valid_report_types, true)) {
            wp_send_json_error([
                'message' => __('Tipo de reporte no válido', 'alquipress')
            ]);
            return;
        }

        // Validar año (rango razonable: 2020-2100)
        if ($year < 2020 || $year > 2100) {
            wp_send_json_error([
                'message' => __('Año fuera del rango válido', 'alquipress')
            ]);
            return;
        }

        $data = [];

        try {
            switch ($report_type) {
            case 'overview':
                $raw = $this->get_overview_stats($year);
                $data = [
                    'total_revenue' => wc_price($raw['total_revenue']),
                    'total_bookings' => $raw['total_bookings'],
                    'avg_booking' => wc_price($raw['avg_booking']),
                    'occupancy_rate' => number_format($raw['occupancy_rate'], 1) . '%'
                ];
                break;
            case 'overview_yoy':
                $data = $this->get_overview_with_yoy($year);
                break;
            case 'revenue_monthly':
                $data = $this->get_revenue_monthly($year);
                break;
            case 'revenue_monthly_yoy':
                $curr = $this->get_revenue_monthly($year);
                $prev = $this->get_revenue_monthly($year - 1);
                $data = [
                    'labels' => $curr['labels'],
                    'data' => $curr['data'],
                    'data_prev' => $prev['data'],
                    'year' => $year,
                    'year_prev' => $year - 1,
                ];
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
                wp_send_json_error(['message' => 'Tipo de reporte no válido'], 400);
            }

            wp_send_json_success($data);
        } catch (Exception $e) {
            error_log('ALQUIPRESS Reports Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error al generar el reporte'], 500);
        }
    }

    /**
     * AJAX: Exportar informes a CSV (Excel)
     */
    public function ajax_export_reports_csv()
    {
        Alquipress_Rate_Limiter::check_and_exit('export_reports_csv', 5, 60);
        check_ajax_referer('alquipress_reports', 'nonce');
        if (!current_user_can('manage_options')) {
            status_header(403);
            exit;
        }
        $year = isset($_POST['year']) ? absint($_POST['year']) : date('Y');
        if ($year < 2020 || $year > 2100) {
            $year = date('Y');
        }

        try {
            $overview = $this->get_overview_with_yoy($year);
            $revenue_monthly = $this->get_revenue_monthly($year);
            $top_clients = $this->get_top_clients($year);
            $top_properties = $this->get_top_properties($year);
        } catch (Exception $e) {
            error_log('ALQUIPRESS Export Error: ' . $e->getMessage());
            status_header(500);
            exit;
        }

        $csv = $this->build_export_csv($year, $overview, $revenue_monthly, $top_clients, $top_properties);
        $filename = 'informes-alquipress-' . $year . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo "\xEF\xBB\xBF"; // BOM UTF-8
        echo $csv;
        exit;
    }

    /**
     * AJAX: Enviar informe resumido por email
     */
    public function ajax_email_report()
    {
        Alquipress_Rate_Limiter::check_and_exit('email_report', 5, 60);
        check_ajax_referer('alquipress_reports', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'alquipress')], 403);
        }

        $year = isset($_POST['year']) ? absint($_POST['year']) : date('Y');
        if ($year < 2020 || $year > 2100) {
            $year = date('Y');
        }

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Email no válido.', 'alquipress')], 400);
        }

        try {
            $overview = $this->get_overview_with_yoy($year);
            $revenue_monthly = $this->get_revenue_monthly($year);
            $top_clients = $this->get_top_clients($year);
            $top_properties = $this->get_top_properties($year);
        } catch (Exception $e) {
            error_log('ALQUIPRESS Email Report Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('No se pudo generar el informe.', 'alquipress')], 500);
        }

        $top_property = !empty($top_properties[0]) ? $top_properties[0] : null;
        $best_month_label = '';
        $best_month_value = 0;
        if (!empty($revenue_monthly['data']) && !empty($revenue_monthly['labels'])) {
            $best_index = array_keys($revenue_monthly['data'], max($revenue_monthly['data']))[0];
            $best_month_label = $revenue_monthly['labels'][$best_index] ?? '';
            $best_month_value = (float) ($revenue_monthly['data'][$best_index] ?? 0);
        }

        $subject = sprintf(__('Informe Alquipress %d', 'alquipress'), $year);
        $lines = [
            sprintf(__('Resumen anual %d', 'alquipress'), $year),
            '',
            sprintf(__('Ingresos totales: %s', 'alquipress'), wp_strip_all_tags((string) ($overview['total_revenue'] ?? '—'))),
            sprintf(__('Reservas totales: %s', 'alquipress'), (string) ($overview['total_bookings'] ?? '—')),
            sprintf(__('Tasa de ocupación: %s', 'alquipress'), (string) ($overview['occupancy_rate'] ?? '—')),
            sprintf(__('Precio medio diario: %s', 'alquipress'), wp_strip_all_tags((string) ($overview['avg_daily_rate'] ?? '—'))),
        ];

        if ($best_month_label !== '') {
            $lines[] = sprintf(__('Mejor mes: %1$s (%2$s €)', 'alquipress'), $best_month_label, number_format_i18n($best_month_value, 2));
        }

        if (!empty($top_property['name'])) {
            $lines[] = sprintf(
                __('Propiedad destacada: %1$s (%2$s €)', 'alquipress'),
                $top_property['name'],
                number_format_i18n((float) ($top_property['total_revenue'] ?? 0), 2)
            );
        }

        $lines[] = '';
        $lines[] = __('Se adjuntan dos archivos:', 'alquipress');
        $lines[] = __('1) CSV con el detalle de datos.', 'alquipress');
        $lines[] = __('2) HTML imprimible (si lo abres en navegador puedes guardarlo como PDF).', 'alquipress');

        $attachments = [];
        try {
            $attachments = $this->build_report_email_attachments($year, $overview, $revenue_monthly, $top_clients, $top_properties);
        } catch (Exception $e) {
            error_log('ALQUIPRESS Email Report Attachment Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('No se pudieron generar los adjuntos del informe.', 'alquipress')], 500);
        }

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        $sent = wp_mail($email, $subject, implode("\n", $lines), $headers, $attachments);
        $this->cleanup_temp_attachments($attachments);

        if (!$sent) {
            wp_send_json_error(['message' => __('No se pudo enviar el email.', 'alquipress')], 500);
        }

        wp_send_json_success([
            'message' => sprintf(__('Informe enviado a %s', 'alquipress'), $email),
        ]);
    }

    /**
     * Construir CSV para exportación
     */
    private function build_export_csv($year, $overview, $revenue_monthly, $top_clients, $top_properties)
    {
        $lines = [];
        $lines[] = __('Informes Alquipress', 'alquipress') . ' - ' . $year;
        $lines[] = '';
        $lines[] = __('Resumen', 'alquipress');
        $lines[] = __('Ingresos totales', 'alquipress') . ';' . ($overview['total_revenue_raw'] ?? 0);
        $lines[] = __('Reservas totales', 'alquipress') . ';' . ($overview['total_bookings'] ?? 0);
        $lines[] = __('Tasa ocupación', 'alquipress') . ';' . ($overview['occupancy_rate'] ?? '');
        $lines[] = __('Precio medio diario', 'alquipress') . ';' . ($overview['avg_daily_rate'] ?? '');
        $lines[] = '';
        $lines[] = __('Ingresos mensuales (€)', 'alquipress');
        $lines[] = __('Mes', 'alquipress') . ';' . __('Ingresos', 'alquipress');
        foreach ($revenue_monthly['labels'] as $i => $label) {
            $lines[] = $label . ';' . number_format($revenue_monthly['data'][$i] ?? 0, 2, ',', '');
        }
        $lines[] = '';
        $lines[] = __('Top clientes por gasto', 'alquipress');
        $lines[] = __('Cliente', 'alquipress') . ';' . __('Email', 'alquipress') . ';' . __('Reservas', 'alquipress') . ';' . __('Gasto total (€)', 'alquipress') . ';' . __('Última reserva', 'alquipress');
        foreach ($top_clients as $c) {
            $lines[] = $this->csv_escape($c['name']) . ';' . $this->csv_escape($c['email']) . ';' . ($c['total_orders'] ?? 0) . ';' . number_format($c['total_spent'] ?? 0, 2, ',', '') . ';' . ($c['last_order_date'] ?? '');
        }
        $lines[] = '';
        $lines[] = __('Top propiedades por ingresos', 'alquipress');
        $lines[] = __('Propiedad', 'alquipress') . ';' . __('Reservas', 'alquipress') . ';' . __('Noches', 'alquipress') . ';' . __('Ingresos (€)', 'alquipress') . ';' . __('Ocupación %', 'alquipress');
        foreach ($top_properties as $p) {
            $lines[] = $this->csv_escape($p['name']) . ';' . ($p['total_bookings'] ?? 0) . ';' . ($p['total_nights'] ?? 0) . ';' . number_format($p['total_revenue'] ?? 0, 2, ',', '') . ';' . number_format($p['occupancy_rate'] ?? 0, 1, ',', '');
        }
        return implode("\r\n", $lines);
    }

    /**
     * Crear adjuntos temporales del informe para envío por email.
     */
    private function build_report_email_attachments($year, $overview, $revenue_monthly, $top_clients, $top_properties)
    {
        $upload_dir = wp_upload_dir();
        if (!empty($upload_dir['error'])) {
            throw new Exception('Upload dir error: ' . $upload_dir['error']);
        }

        $base_dir = trailingslashit($upload_dir['basedir']) . 'alquipress-temp';
        if (!wp_mkdir_p($base_dir)) {
            throw new Exception('No se pudo crear el directorio temporal');
        }

        $token = gmdate('Ymd-His') . '-' . wp_generate_password(6, false, false);

        $csv_filename = 'informes-alquipress-' . $year . '-' . $token . '.csv';
        $csv_path = trailingslashit($base_dir) . $csv_filename;
        $csv_content = "\xEF\xBB\xBF" . $this->build_export_csv($year, $overview, $revenue_monthly, $top_clients, $top_properties);
        if (file_put_contents($csv_path, $csv_content) === false) {
            throw new Exception('No se pudo escribir el CSV temporal');
        }

        $html_filename = 'informe-alquipress-' . $year . '-' . $token . '.html';
        $html_path = trailingslashit($base_dir) . $html_filename;
        $html_content = $this->build_report_email_html($year, $overview, $revenue_monthly, $top_properties);
        if (file_put_contents($html_path, $html_content) === false) {
            @unlink($csv_path);
            throw new Exception('No se pudo escribir el HTML temporal');
        }

        return [$csv_path, $html_path];
    }

    /**
     * Plantilla HTML simple del informe (imprimible para guardar como PDF).
     */
    private function build_report_email_html($year, $overview, $revenue_monthly, $top_properties)
    {
        $top_property = !empty($top_properties[0]) ? $top_properties[0] : null;

        $best_month_label = '';
        $best_month_value = 0;
        if (!empty($revenue_monthly['data']) && !empty($revenue_monthly['labels'])) {
            $best_index = array_keys($revenue_monthly['data'], max($revenue_monthly['data']))[0];
            $best_month_label = $revenue_monthly['labels'][$best_index] ?? '';
            $best_month_value = (float) ($revenue_monthly['data'][$best_index] ?? 0);
        }

        $rows = '';
        if (!empty($revenue_monthly['labels']) && !empty($revenue_monthly['data'])) {
            foreach ($revenue_monthly['labels'] as $index => $month) {
                $value = (float) ($revenue_monthly['data'][$index] ?? 0);
                $rows .= '<tr><td>' . esc_html($month) . '</td><td style="text-align:right;">' . esc_html(number_format_i18n($value, 2)) . ' €</td></tr>';
            }
        }

        $top_property_line = '';
        if (!empty($top_property['name'])) {
            $top_property_line = '<li><strong>' . esc_html__('Propiedad destacada:', 'alquipress') . '</strong> ' . esc_html($top_property['name']) . ' (' . esc_html(number_format_i18n((float) ($top_property['total_revenue'] ?? 0), 2)) . ' €)</li>';
        }

        $best_month_line = '';
        if ($best_month_label !== '') {
            $best_month_line = '<li><strong>' . esc_html__('Mejor mes:', 'alquipress') . '</strong> ' . esc_html($best_month_label) . ' (' . esc_html(number_format_i18n($best_month_value, 2)) . ' €)</li>';
        }

        return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . esc_html(sprintf(__('Informe Alquipress %d', 'alquipress'), $year)) . '</title>
<style>
body{font-family:Inter,Arial,sans-serif;background:#f8fafb;color:#0f172a;margin:0;padding:24px;}
.wrap{max-width:860px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;}
h1{margin:0 0 8px;font-size:24px;}
p{margin:0 0 16px;color:#475569;}
.cards{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;}
.card{flex:1;min-width:180px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;}
.card .label{font-size:12px;color:#64748b;margin-bottom:6px;}
.card .value{font-size:20px;font-weight:700;}
table{width:100%;border-collapse:collapse;margin-top:12px;}
th,td{border-bottom:1px solid #e2e8f0;padding:8px;font-size:14px;}
th{text-align:left;background:#f8fafc;}
ul{margin:12px 0 0 18px;padding:0;}
</style>
</head>
<body>
<div class="wrap">
  <h1>' . esc_html(sprintf(__('Informe Alquipress %d', 'alquipress'), $year)) . '</h1>
  <p>' . esc_html__('Archivo preparado para impresión. Si lo abres en el navegador, usa Imprimir > Guardar como PDF.', 'alquipress') . '</p>
  <div class="cards">
    <div class="card"><div class="label">' . esc_html__('Ingresos totales', 'alquipress') . '</div><div class="value">' . wp_kses_post((string) ($overview['total_revenue'] ?? '—')) . '</div></div>
    <div class="card"><div class="label">' . esc_html__('Reservas totales', 'alquipress') . '</div><div class="value">' . esc_html((string) ($overview['total_bookings'] ?? '—')) . '</div></div>
    <div class="card"><div class="label">' . esc_html__('Tasa de ocupación', 'alquipress') . '</div><div class="value">' . esc_html((string) ($overview['occupancy_rate'] ?? '—')) . '</div></div>
  </div>
  <h2>' . esc_html__('Resumen destacado', 'alquipress') . '</h2>
  <ul>' . $best_month_line . $top_property_line . '</ul>
  <h2>' . esc_html__('Ingresos mensuales', 'alquipress') . '</h2>
  <table>
    <thead><tr><th>' . esc_html__('Mes', 'alquipress') . '</th><th style="text-align:right;">' . esc_html__('Ingresos', 'alquipress') . '</th></tr></thead>
    <tbody>' . $rows . '</tbody>
  </table>
</div>
</body>
</html>';
    }

    private function cleanup_temp_attachments($attachments)
    {
        if (!is_array($attachments)) {
            return;
        }
        foreach ($attachments as $file) {
            if (is_string($file) && file_exists($file)) {
                @unlink($file);
            }
        }
    }

    private function csv_escape($val)
    {
        $val = (string) $val;
        if (strpos($val, ';') !== false || strpos($val, '"') !== false || strpos($val, "\n") !== false) {
            return '"' . str_replace('"', '""', $val) . '"';
        }
        return $val;
    }

    // ========== Métodos de Análisis de Datos ==========

    /**
     * Estadísticas generales del año (Optimizado con SQL directo)
     */
    private function get_overview_stats($year)
    {
        global $wpdb;

        $start_date = $year . '-01-01 00:00:00';
        $end_date = $year . '-12-31 23:59:59';
        $status_string = "'wc-completed', 'wc-in-progress', 'wc-checkout-review'";

        // 1. Ingresos Totales (Usando la lógica de COALESCE para soportar pagos escalonados)
        $sql_revenue = "
            SELECT SUM(COALESCE(CAST(pm_real.meta_value AS DECIMAL(12,2)), CAST(pm_wc.meta_value AS DECIMAL(12,2))))
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_wc ON p.ID = pm_wc.post_id AND pm_wc.meta_key = '_order_total'
            LEFT JOIN {$wpdb->postmeta} pm_real ON p.ID = pm_real.post_id AND pm_real.meta_key = '_apm_booking_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ($status_string)
            AND p.post_date >= %s AND p.post_date <= %s
        ";
        $total_revenue = (float) $wpdb->get_var($wpdb->prepare($sql_revenue, $start_date, $end_date));

        // 2. Total Reservas
        $sql_count = "
            SELECT COUNT(ID) FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ($status_string)
            AND post_date >= %s AND post_date <= %s
        ";
        $total_bookings = (int) $wpdb->get_var($wpdb->prepare($sql_count, $start_date, $end_date));

        // 3. Total Noches (Este es más complejo de optimizar puramente en SQL sin lógica de negocio,
        // pero podemos optimizar la carga de datos para solo traer fechas)
        $sql_nights = "
            SELECT pm1.meta_value as checkin, pm2.meta_value as checkout
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_booking_checkin_date'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_booking_checkout_date'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ($status_string)
            AND p.post_date >= %s AND p.post_date <= %s
        ";
        $nights_data = $wpdb->get_results($wpdb->prepare($sql_nights, $start_date, $end_date));
        
        $total_nights = 0;
        foreach ($nights_data as $row) {
            $diff = strtotime($row->checkout) - strtotime($row->checkin);
            if ($diff > 0) {
                $total_nights += floor($diff / (60 * 60 * 24));
            }
        }

        $avg_booking = $total_bookings > 0 ? $total_revenue / $total_bookings : 0;

        // Calcular tasa de ocupación (simplificado)
        $total_properties = wp_count_posts('product')->publish;
        $days_in_year = ($year == date('Y')) ? (int)date('z') + 1 : (int)date('z', mktime(0,0,0,12,31,$year)) + 1;
        $available_nights = $total_properties * $days_in_year;
        $occupancy_rate = $available_nights > 0 ? ($total_nights / $available_nights) * 100 : 0;

        return [
            'total_revenue' => $total_revenue,
            'total_bookings' => $total_bookings,
            'avg_booking' => $avg_booking,
            'occupancy_rate' => $occupancy_rate,
            'total_nights' => $total_nights,
            'days_in_year' => $days_in_year,
        ];
    }

    /**
     * Overview con cambios YoY para el dashboard Pencil
     */
    private function get_overview_with_yoy($year)
    {
        $curr = $this->get_overview_stats($year);
        $prev = $this->get_overview_stats($year - 1);

        $rev_change = $prev['total_revenue'] > 0
            ? round((($curr['total_revenue'] - $prev['total_revenue']) / $prev['total_revenue']) * 100, 1)
            : 0;
        $book_change = $prev['total_bookings'] > 0
            ? round((($curr['total_bookings'] - $prev['total_bookings']) / $prev['total_bookings']) * 100, 1)
            : ($curr['total_bookings'] > 0 ? 100 : 0);
        $occ_change = $prev['occupancy_rate'] > 0
            ? round($curr['occupancy_rate'] - $prev['occupancy_rate'], 1)
            : 0;
        $avg_daily = $curr['total_nights'] > 0 ? $curr['total_revenue'] / $curr['total_nights'] : 0;
        $avg_daily_prev = $prev['total_nights'] > 0 ? $prev['total_revenue'] / $prev['total_nights'] : 0;
        $adr_change = $avg_daily_prev > 0 ? round((($avg_daily - $avg_daily_prev) / $avg_daily_prev) * 100, 1) : 0;

        return [
            'total_revenue' => wc_price($curr['total_revenue']),
            'total_revenue_raw' => $curr['total_revenue'],
            'revenue_change' => $rev_change,
            'total_bookings' => $curr['total_bookings'],
            'bookings_change' => $book_change,
            'occupancy_rate' => number_format($curr['occupancy_rate'], 1) . '%',
            'occupancy_change' => $occ_change,
            'avg_daily_rate' => wc_price($avg_daily),
            'avg_daily_rate_change' => $adr_change,
            'avg_booking' => wc_price($curr['avg_booking']),
        ];
    }

    /**
     * Ingresos mensuales (Optimizado con SQL directo)
     */
    private function get_revenue_monthly($year)
    {
        global $wpdb;
        $monthly_data = array_fill(1, 12, 0);

        $start_date = $year . '-01-01 00:00:00';
        $end_date = $year . '-12-31 23:59:59';
        $status_string = "'wc-completed', 'wc-in-progress', 'wc-checkout-review'";

        // Query agrupada por mes
        $sql = "
            SELECT 
                MONTH(p.post_date) as month,
                SUM(COALESCE(CAST(pm_real.meta_value AS DECIMAL(12,2)), CAST(pm_wc.meta_value AS DECIMAL(12,2)))) as total
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_wc ON p.ID = pm_wc.post_id AND pm_wc.meta_key = '_order_total'
            LEFT JOIN {$wpdb->postmeta} pm_real ON p.ID = pm_real.post_id AND pm_real.meta_key = '_apm_booking_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ($status_string)
            AND p.post_date >= %s AND p.post_date <= %s
            GROUP BY month
        ";

        $results = $wpdb->get_results($wpdb->prepare($sql, $start_date, $end_date));

        foreach ($results as $row) {
            $monthly_data[intval($row->month)] = (float) $row->total;
        }

        return [
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            'data' => array_values($monthly_data)
        ];
    }

    /**
     * Ingresos por temporada (Optimizado)
     */
    private function get_revenue_by_season($year)
    {
        $monthly_data = $this->get_revenue_monthly($year); // Reutilizamos la query optimizada mensual
        $values = $monthly_data['data']; // Array indexado 0-11 (Ene-Dic)

        // Mapeo de índices (0 = Enero)
        // Baja: Ene(0), Feb(1), Nov(10), Dic(11)
        // Media: Mar(2), Abr(3), May(4), Oct(9)
        // Alta: Jun(5), Jul(6), Ago(7), Sep(8)

        $seasons = [
            'Temporada Baja' => $values[0] + $values[1] + $values[10] + $values[11],
            'Temporada Media' => $values[2] + $values[3] + $values[4] + $values[9],
            'Temporada Alta' => $values[5] + $values[6] + $values[7] + $values[8],
        ];

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
            'labels' => ['1 Estrella', '2 Estrellas', '3 Estrellas', '4 Estrellas', '5 Estrellas'],
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

    public function enqueue_section_assets($page)
    {
        if ($page !== 'alquipress-reports') {
            return;
        }

        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        wp_enqueue_style(
            'alquipress-advanced-reports',
            ALQUIPRESS_URL . 'includes/modules/advanced-reports/assets/advanced-reports.css',
            [],
            ALQUIPRESS_VERSION
        );

        wp_enqueue_style(
            'alquipress-toast-notifications',
            ALQUIPRESS_URL . 'includes/admin/assets/toast-notifications.css',
            [],
            ALQUIPRESS_VERSION
        );
        wp_enqueue_script(
            'alquipress-toast-notifications',
            ALQUIPRESS_URL . 'includes/admin/assets/toast-notifications.js',
            ['jquery'],
            ALQUIPRESS_VERSION,
            true
        );

        wp_enqueue_script(
            'alquipress-advanced-reports',
            ALQUIPRESS_URL . 'includes/modules/advanced-reports/assets/advanced-reports.js',
            ['jquery', 'chartjs', 'alquipress-toast-notifications'],
            ALQUIPRESS_VERSION,
            true
        );

        wp_localize_script('alquipress-advanced-reports', 'alquipressReports', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alquipress_reports'),
            'currentYear' => date('Y'),
            'currentUserEmail' => wp_get_current_user() ? wp_get_current_user()->user_email : '',
            'i18n' => [
                'vsLastYear' => __('vs año ant.', 'alquipress'),
                'vsAvg' => __('vs media', 'alquipress'),
                'trend' => __('Tendencia', 'alquipress'),
                'errorConnection' => __('Error de conexión al cargar los datos.', 'alquipress'),
                'emailPrompt' => __('Introduce el email de destino para enviar el informe.', 'alquipress'),
                'emailDefaultInvalid' => __('Email no válido.', 'alquipress'),
                'emailSending' => __('Enviando informe por email...', 'alquipress'),
                'emailSent' => __('Informe enviado correctamente.', 'alquipress'),
                'refreshCooldown' => __('Espera unos segundos antes de volver a actualizar.', 'alquipress'),
            ]
        ]);
    }
}

new Alquipress_Advanced_Reports();
