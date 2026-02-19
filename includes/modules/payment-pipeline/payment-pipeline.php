<?php
/**
 * Módulo: Pipeline de Cobros con Hitos
 * Sistema de seguimiento visual de pagos por hitos con recordatorios automáticos
 * 
 * @package Alquipress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar clases necesarias
require_once __DIR__ . '/class-payment-milestones.php';
require_once __DIR__ . '/class-payment-reminders.php';

class Alquipress_Payment_Pipeline
{
    public function __construct()
    {
        // Inicializar sistema de recordatorios
        new Alquipress_Payment_Reminders();
        
        // Hook para renderizar sección
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_section_assets']);
        
        // AJAX para obtener datos del pipeline
        add_action('wp_ajax_alquipress_get_payment_pipeline', [$this, 'ajax_get_payment_pipeline']);
        add_action('wp_ajax_alquipress_update_payment_status', [$this, 'ajax_update_payment_status']);
        
        // AJAX para recordatorios
        add_action('wp_ajax_alquipress_get_reminders_status', [$this, 'ajax_get_reminders_status']);
        add_action('wp_ajax_alquipress_send_reminder_manual', [$this, 'ajax_send_reminder_manual']);
        add_action('wp_ajax_alquipress_test_reminders_cron', [$this, 'ajax_test_reminders_cron']);
        add_action('wp_ajax_alquipress_activate_reminders_cron', [$this, 'ajax_activate_reminders_cron']);
        
        // Pipeline de Cobros integrado como pestaña en Pipeline (sin menú separado)
        add_action('admin_menu', [$this, 'add_reminders_submenu'], 21);
    }
    
    /**
     * Agregar submenú para gestión de recordatorios
     */
    public function add_reminders_submenu()
    {
        add_submenu_page(
            'alquipress-settings',
            __('Recordatorios de Pago', 'alquipress'),
            __('Recordatorios', 'alquipress'),
            'manage_options',
            'alquipress-payment-reminders',
            [$this, 'render_reminders_page']
        );
    }
    
    /**
     * Renderizar sección si es la página correcta.
     * Pipeline de Cobros accesible como pestaña en Pipeline (alquipress-pipeline?tab=cobros).
     */
    public function maybe_render_section($page)
    {
        $is_cobros_tab = ($page === 'alquipress-pipeline' && isset($_GET['tab']) && $_GET['tab'] === 'cobros');
        if ($page === 'alquipress-payment-pipeline' || $is_cobros_tab) {
            $this->render_pipeline_page();
        } elseif ($page === 'alquipress-dashboard') {
            $this->render_dashboard_widget();
        }
    }
    
    /**
     * Renderizar página de gestión de recordatorios
     */
    public function render_reminders_page()
    {
        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        
        // Verificar estado del cron
        $next_run = wp_next_scheduled('alquipress_payment_reminders_daily');
        $cron_status = $next_run ? 'active' : 'inactive';
        $next_run_formatted = $next_run ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_run) : __('No programado', 'alquipress');
        
        ?>
        <div class="wrap alquipress-dashboard-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('pipeline'); ?>
                <main class="ap-owners-main">
                    <header class="ap-header">
                        <div class="ap-header-left">
                            <h1 class="ap-header-title"><?php esc_html_e('Gestión de Recordatorios de Pago', 'alquipress'); ?></h1>
                            <p class="ap-header-subtitle"><?php esc_html_e('Gestiona y prueba el sistema de recordatorios automáticos', 'alquipress'); ?></p>
                        </div>
                    </header>
                    
                    <div class="reminders-status-section">
                        <div class="reminders-status-card">
                            <h2><?php esc_html_e('Estado del Sistema', 'alquipress'); ?></h2>
                            <div class="status-grid">
                                <div class="status-item">
                                    <span class="status-label"><?php esc_html_e('Cron Job:', 'alquipress'); ?></span>
                                    <span class="status-value status-<?php echo esc_attr($cron_status); ?>">
                                        <?php echo $cron_status === 'active' ? esc_html__('Activo', 'alquipress') : esc_html__('Inactivo', 'alquipress'); ?>
                                    </span>
                                </div>
                                <div class="status-item">
                                    <span class="status-label"><?php esc_html_e('Próxima ejecución:', 'alquipress'); ?></span>
                                    <span class="status-value"><?php echo esc_html($next_run_formatted); ?></span>
                                </div>
                            </div>
                            <?php if ($cron_status === 'inactive'): ?>
                                <button id="activate-cron" class="button button-primary"><?php esc_html_e('Activar Cron Job', 'alquipress'); ?></button>
                            <?php endif; ?>
                            <button id="test-cron" class="button"><?php esc_html_e('Ejecutar Ahora (Prueba)', 'alquipress'); ?></button>
                        </div>
                    </div>
                    
                    <div class="reminders-actions-section">
                        <h2><?php esc_html_e('Acciones', 'alquipress'); ?></h2>
                        <div class="reminders-actions-grid">
                            <div class="action-card">
                                <h3><?php esc_html_e('Ver Estado de Recordatorios', 'alquipress'); ?></h3>
                                <p><?php esc_html_e('Consulta qué recordatorios se han enviado y cuáles están pendientes', 'alquipress'); ?></p>
                                <button id="load-reminders-status" class="button button-primary"><?php esc_html_e('Cargar Estado', 'alquipress'); ?></button>
                            </div>
                            <div class="action-card">
                                <h3><?php esc_html_e('Enviar Recordatorio Manual', 'alquipress'); ?></h3>
                                <p><?php esc_html_e('Envía un recordatorio específico para probar el sistema', 'alquipress'); ?></p>
                                <div class="manual-reminder-form">
                                    <input type="number" id="manual-payment-id" placeholder="<?php esc_attr_e('ID del pago programado', 'alquipress'); ?>" class="regular-text">
                                    <select id="manual-reminder-type" class="regular-text">
                                        <option value="7"><?php esc_html_e('7 días antes', 'alquipress'); ?></option>
                                        <option value="3"><?php esc_html_e('3 días antes', 'alquipress'); ?></option>
                                        <option value="0"><?php esc_html_e('Día de vencimiento', 'alquipress'); ?></option>
                                        <option value="-3"><?php esc_html_e('3 días después (vencido)', 'alquipress'); ?></option>
                                    </select>
                                    <button id="send-manual-reminder" class="button button-primary"><?php esc_html_e('Enviar', 'alquipress'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="reminders-status-results" class="reminders-results-section" style="display: none;">
                        <h2><?php esc_html_e('Estado de Recordatorios', 'alquipress'); ?></h2>
                        <div id="reminders-status-content"></div>
                    </div>
                </main>
            </div>
        </div>
        
        <style>
        .reminders-status-section, .reminders-actions-section, .reminders-results-section {
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .reminders-status-card h2, .reminders-actions-section h2, .reminders-results-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 600;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .status-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .status-label {
            font-size: 13px;
            color: #646970;
        }
        .status-value {
            font-size: 16px;
            font-weight: 600;
        }
        .status-active {
            color: #00a32a;
        }
        .status-inactive {
            color: #dc3232;
        }
        .reminders-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .action-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
        }
        .action-card h3 {
            margin-top: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .action-card p {
            color: #646970;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .manual-reminder-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .manual-reminder-form input,
        .manual-reminder-form select {
            width: 100%;
        }
        #reminders-status-content {
            margin-top: 20px;
        }
        .reminder-item {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .reminder-item-info {
            flex: 1;
        }
        .reminder-item-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        .reminder-item-meta {
            font-size: 13px;
            color: #646970;
        }
        .reminder-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .reminder-badge.sent {
            background: #d1fae5;
            color: #065f46;
        }
        .reminder-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Activar cron job
            $('#activate-cron').on('click', function() {
                $.ajax({
                    url: alquipressPaymentPipeline.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'alquipress_activate_reminders_cron',
                        nonce: alquipressPaymentPipeline.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            if (typeof AlquipressToast !== 'undefined') {
                                AlquipressToast.error(response.data.message || 'Error al activar cron');
                            }
                        }
                    }
                });
            });
            
            // Probar cron job
            $('#test-cron').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).text('Ejecutando...');
                
                $.ajax({
                    url: alquipressPaymentPipeline.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'alquipress_test_reminders_cron',
                        nonce: alquipressPaymentPipeline.nonce
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('Ejecutar Ahora (Prueba)');
                        if (response.success) {
                            if (typeof AlquipressToast !== 'undefined') {
                                AlquipressToast.success('Cron ejecutado. ' + (response.data.sent || 0) + ' recordatorios enviados.');
                            }
                        } else {
                            if (typeof AlquipressToast !== 'undefined') {
                                AlquipressToast.error(response.data.message || 'Error al ejecutar cron');
                            }
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('Ejecutar Ahora (Prueba)');
                        if (typeof AlquipressToast !== 'undefined') {
                            AlquipressToast.error('Error de conexión');
                        }
                    }
                });
            });
            
            // Cargar estado de recordatorios
            $('#load-reminders-status').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).text('Cargando...');
                
                $.ajax({
                    url: alquipressPaymentPipeline.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'alquipress_get_reminders_status',
                        nonce: alquipressPaymentPipeline.nonce
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('Cargar Estado');
                        if (response.success) {
                            $('#reminders-status-results').show();
                            renderRemindersStatus(response.data);
                        } else {
                            if (typeof AlquipressToast !== 'undefined') {
                                AlquipressToast.error(response.data.message || 'Error al cargar estado');
                            }
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('Cargar Estado');
                        if (typeof AlquipressToast !== 'undefined') {
                            AlquipressToast.error('Error de conexión');
                        }
                    }
                });
            });
            
            // Enviar recordatorio manual
            $('#send-manual-reminder').on('click', function() {
                const paymentId = $('#manual-payment-id').val();
                const reminderType = $('#manual-reminder-type').val();
                
                if (!paymentId) {
                    if (typeof AlquipressToast !== 'undefined') {
                        AlquipressToast.error('Por favor, ingresa el ID del pago');
                    }
                    return;
                }
                
                const $btn = $(this);
                $btn.prop('disabled', true).text('Enviando...');
                
                $.ajax({
                    url: alquipressPaymentPipeline.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'alquipress_send_reminder_manual',
                        nonce: alquipressPaymentPipeline.nonce,
                        payment_id: paymentId,
                        reminder_type: reminderType
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('Enviar');
                        if (response.success) {
                            if (typeof AlquipressToast !== 'undefined') {
                                AlquipressToast.success('Recordatorio enviado correctamente');
                            }
                            $('#manual-payment-id').val('');
                        } else {
                            if (typeof AlquipressToast !== 'undefined') {
                                AlquipressToast.error(response.data.message || 'Error al enviar recordatorio');
                            }
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('Enviar');
                        if (typeof AlquipressToast !== 'undefined') {
                            AlquipressToast.error('Error de conexión');
                        }
                    }
                });
            });
            
            function renderRemindersStatus(data) {
                let html = '<div class="reminders-list">';
                
                if (data.reminders && data.reminders.length > 0) {
                    data.reminders.forEach(function(reminder) {
                        html += '<div class="reminder-item">';
                        html += '<div class="reminder-item-info">';
                        html += '<div class="reminder-item-title">Pedido #' + reminder.order_number + ' - ' + reminder.customer_name + '</div>';
                        html += '<div class="reminder-item-meta">';
                        html += 'Monto: ' + reminder.amount_formatted + ' | ';
                        html += 'Vence: ' + reminder.due_date + ' | ';
                        html += 'Tipo: ' + reminder.payment_type;
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="reminder-badges">';
                        reminder.reminders.forEach(function(r) {
                            html += '<span class="reminder-badge ' + (r.sent ? 'sent' : 'pending') + '">';
                            html += r.label + (r.sent ? ' (Enviado)' : ' (Pendiente)');
                            html += '</span> ';
                        });
                        html += '</div>';
                        html += '</div>';
                    });
                } else {
                    html += '<p>No hay pagos pendientes con recordatorios programados.</p>';
                }
                
                html += '</div>';
                $('#reminders-status-content').html(html);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Encolar assets del módulo
     */
    public function enqueue_section_assets($page)
    {
        $is_cobros_tab = ($page === 'alquipress-pipeline' && isset($_GET['tab']) && $_GET['tab'] === 'cobros');
        if ($page !== 'alquipress-payment-pipeline' && $page !== 'alquipress-dashboard' && $page !== 'alquipress-payment-reminders' && !$is_cobros_tab) {
            return;
        }

        // SortableJS local (evitar CDN)
        wp_enqueue_script('sortable-js', ALQUIPRESS_URL . 'includes/admin/assets/sortable.min.js', [], '1.15.0', true);
        
        wp_enqueue_style(
            'alquipress-payment-pipeline',
            ALQUIPRESS_URL . 'includes/modules/payment-pipeline/assets/payment-pipeline.css',
            [],
            ALQUIPRESS_VERSION
        );
        
        wp_enqueue_script(
            'alquipress-payment-pipeline',
            ALQUIPRESS_URL . 'includes/modules/payment-pipeline/assets/payment-pipeline.js',
            ['jquery', 'sortable-js'],
            ALQUIPRESS_VERSION,
            true
        );
        
        wp_localize_script('alquipress-payment-pipeline', 'alquipressPaymentPipeline', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alquipress-payment-pipeline-nonce'),
            'currency' => get_woocommerce_currency_symbol()
        ]);
        
        // Cargar sistema de toast notifications
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
        
        // Cargar assets también en página de recordatorios
        if ($page === 'alquipress-payment-reminders') {
            wp_enqueue_script(
                'alquipress-payment-pipeline',
                ALQUIPRESS_URL . 'includes/modules/payment-pipeline/assets/payment-pipeline.js',
                ['jquery'],
                ALQUIPRESS_VERSION,
                true
            );
        }
    }
    
    /**
     * Renderizar página completa del Pipeline de Cobros
     */
    public function render_pipeline_page()
    {
        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        $sidebar_page = 'pipeline';
        ?>
        <div class="wrap alquipress-dashboard-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar($sidebar_page); ?>
                <main class="ap-owners-main">
                    <header class="ap-header">
                        <div class="ap-header-left">
                            <h1 class="ap-header-title"><?php esc_html_e('Pipeline', 'alquipress'); ?></h1>
                            <div class="ap-pipeline-tabs" style="display:flex;gap:4px;margin-top:8px;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-pipeline')); ?>" class="ap-tab" style="padding:6px 14px;border-radius:8px;font-size:14px;font-weight:500;text-decoration:none;color:#507a95;">
                                    <?php esc_html_e('Reservas', 'alquipress'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-pipeline&tab=cobros')); ?>" class="ap-tab ap-tab-active" style="padding:6px 14px;border-radius:8px;font-size:14px;font-weight:500;text-decoration:none;background:rgba(44,153,226,0.1);color:#2c99e2;">
                                    <?php esc_html_e('Cobros', 'alquipress'); ?>
                                </a>
                            </div>
                        </div>
                    </header>
                    
                    <div class="payment-pipeline-filters">
                        <div class="filter-group">
                            <label><?php esc_html_e('Rango de fechas:', 'alquipress'); ?></label>
                            <input type="date" id="filter-date-from" class="filter-input">
                            <span><?php esc_html_e('hasta', 'alquipress'); ?></span>
                            <input type="date" id="filter-date-to" class="filter-input">
                            <button id="filter-apply" class="button button-primary"><?php esc_html_e('Aplicar', 'alquipress'); ?></button>
                            <button id="filter-reset" class="button"><?php esc_html_e('Limpiar', 'alquipress'); ?></button>
                        </div>
                    </div>
                    
                    <div id="payment-pipeline-kanban" class="payment-pipeline-kanban">
                        <div class="pipeline-loading">
                            <span class="spinner is-active"></span>
                            <p><?php esc_html_e('Cargando pipeline de cobros...', 'alquipress'); ?></p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderizar widget en dashboard
     */
    public function render_dashboard_widget()
    {
        $upcoming = Alquipress_Payment_Milestones::get_upcoming_due_dates(7);
        $overdue = Alquipress_Payment_Milestones::get_overdue_payments(3);
        
        ?>
        <div class="alquipress-payment-pipeline-widget">
            <div class="pipeline-widget-header">
                <h2 class="pipeline-widget-title">
                    <span class="pipeline-icon">💳</span>
                    Pipeline de Cobros
                    <?php if (count($overdue) > 0): ?>
                        <span class="pipeline-badge pipeline-badge-overdue"><?php echo esc_html(count($overdue)); ?></span>
                    <?php endif; ?>
                </h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-pipeline&tab=cobros')); ?>" class="pipeline-view-all">
                    <?php esc_html_e('Ver todo', 'alquipress'); ?>
                </a>
            </div>
            
            <?php if (empty($upcoming) && empty($overdue)): ?>
                <div class="pipeline-empty-state">
                    <p>✅ No hay pagos pendientes en los próximos 7 días.</p>
                </div>
            <?php else: ?>
                <div class="pipeline-widget-content">
                    <?php if (!empty($overdue)): ?>
                        <div class="pipeline-section pipeline-overdue">
                            <h3><?php esc_html_e('Pagos Vencidos', 'alquipress'); ?> (<?php echo esc_html(count($overdue)); ?>)</h3>
                            <ul class="pipeline-list">
                                <?php foreach (array_slice($overdue, 0, 5) as $payment): ?>
                                    <li>
                                        <a href="<?php echo esc_url($payment['order_url']); ?>" target="_blank">
                                            Pedido #<?php echo esc_html($payment['order_number']); ?>
                                        </a>
                                        - <?php echo esc_html($payment['customer_name']); ?>
                                        <span class="pipeline-amount"><?php echo wc_price($payment['amount']); ?></span>
                                        <span class="pipeline-meta">(<?php echo esc_html($payment['days_overdue']); ?> días vencido)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($upcoming)): ?>
                        <div class="pipeline-section pipeline-upcoming">
                            <h3><?php esc_html_e('Próximos Vencimientos', 'alquipress'); ?> (<?php echo esc_html(count($upcoming)); ?>)</h3>
                            <ul class="pipeline-list">
                                <?php foreach (array_slice($upcoming, 0, 5) as $payment): ?>
                                    <li>
                                        <a href="<?php echo esc_url($payment['order_url']); ?>" target="_blank">
                                            Pedido #<?php echo esc_html($payment['order_number']); ?>
                                        </a>
                                        - <?php echo esc_html($payment['customer_name']); ?>
                                        <span class="pipeline-amount"><?php echo wc_price($payment['amount']); ?></span>
                                        <span class="pipeline-meta">(<?php echo esc_html($payment['days_until_due']); ?> días)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * AJAX: Obtener datos del pipeline
     */
    public function ajax_get_payment_pipeline()
    {
        check_ajax_referer('alquipress-payment-pipeline-nonce', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error([
                'message' => __('Permisos insuficientes', 'alquipress')
            ]);
            return;
        }
        
        $filters = [
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'property_id' => isset($_POST['property_id']) ? absint($_POST['property_id']) : 0
        ];
        
        $payments = Alquipress_Payment_Milestones::get_payments_by_status($filters);
        
        wp_send_json_success([
            'payments' => $payments
        ]);
    }
    
    /**
     * AJAX: Actualizar estado de pago
     */
    public function ajax_update_payment_status()
    {
        check_ajax_referer('alquipress-payment-pipeline-nonce', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error([
                'message' => __('Permisos insuficientes', 'alquipress')
            ]);
            return;
        }
        
        $payment_id = isset($_POST['payment_id']) ? absint($_POST['payment_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
        
        if (empty($payment_id) || empty($new_status)) {
            wp_send_json_error([
                'message' => __('Parámetros inválidos', 'alquipress')
            ]);
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';
        
        // Validar que el pago existe
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $payment_id
        ), ARRAY_A);
        
        if (!$payment) {
            wp_send_json_error([
                'message' => __('Pago no encontrado', 'alquipress')
            ]);
            return;
        }
        
        // Actualizar estado
        $result = $wpdb->update(
            $table,
            [
                'status' => $new_status === 'paid' ? 'paid' : 'pending',
                'paid_date' => $new_status === 'paid' ? current_time('mysql') : null,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $payment_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result !== false) {
            // Log
            if (class_exists('Alquipress_Logger')) {
                Alquipress_Logger::info(
                    sprintf('Estado de pago actualizado: ID %d → %s', $payment_id, $new_status),
                    Alquipress_Logger::CONTEXT_PAYMENT,
                    [
                        'payment_id' => $payment_id,
                        'new_status' => $new_status,
                        'order_id' => $payment['order_id']
                    ]
                );
            }
            
            wp_send_json_success([
                'message' => __('Estado actualizado correctamente', 'alquipress')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Error al actualizar el estado', 'alquipress')
            ]);
        }
    }
    
    /**
     * AJAX: Obtener estado de recordatorios (Altamente Optimizado)
     */
    public function ajax_get_reminders_status()
    {
        check_ajax_referer('alquipress-payment-pipeline-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'alquipress')]);
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';
        
        // Obtener pagos pendientes
        $payments = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY scheduled_date ASC LIMIT 50",
            ARRAY_A
        );
        
        if (empty($payments)) {
            wp_send_json_success(['reminders' => []]);
            return;
        }

        // OPTIMIZACIÓN: Obtener todos los metadatos de recordatorios de una sola vez
        $order_ids = array_unique(wp_list_pluck($payments, 'order_id'));
        $order_ids_string = implode(',', array_map('intval', $order_ids));
        
        // Buscamos todas las claves de recordatorios para estos pedidos
        $meta_results = $wpdb->get_results(
            "SELECT post_id, meta_key FROM {$wpdb->postmeta} 
             WHERE post_id IN ($order_ids_string) 
             AND meta_key LIKE '_payment_reminder_%'"
        );
        
        // Mapear metadatos para acceso rápido O(1)
        $sent_map = [];
        foreach ($meta_results as $meta) {
            $sent_map[$meta->post_id][$meta->meta_key] = true;
        }
        
        $reminders = [];
        $today = current_time('timestamp');

        foreach ($payments as $payment) {
            $order_id = (int)$payment['order_id'];
            $order = wc_get_order($order_id);
            if (!$order) continue;
            
            $scheduled_date = strtotime($payment['scheduled_date']);
            $days_until_due = floor(($scheduled_date - $today) / DAY_IN_SECONDS);
            
            $keys = ['7d', '3d', 'due', 'overdue'];
            $reminder_statuses = [];
            
            foreach ($keys as $key) {
                $meta_key = '_payment_reminder_' . $payment['id'] . '_' . $key;
                $is_sent = isset($sent_map[$order_id][$meta_key]);
                
                $label = '';
                $should = false;
                switch($key) {
                    case '7d': $label = '7 días antes'; $should = $days_until_due <= 7 && $days_until_due > 3; break;
                    case '3d': $label = '3 días antes'; $should = $days_until_due <= 3 && $days_until_due > 0; break;
                    case 'due': $label = 'Vencimiento'; $should = $days_until_due === 0; break;
                    case 'overdue': $label = 'Vencido'; $should = $days_until_due < 0 && abs($days_until_due) >= 3; break;
                }

                $reminder_statuses[] = [
                    'key' => $key,
                    'label' => $label,
                    'sent' => $is_sent,
                    'should_send' => $should
                ];
            }
            
            $reminders[] = [
                'payment_id' => $payment['id'],
                'order_id' => $order_id,
                'order_number' => $order->get_order_number(),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'amount_formatted' => wc_price($payment['amount'], ['currency' => $payment['currency']]),
                'due_date' => wp_date(get_option('date_format'), $scheduled_date),
                'payment_type' => $payment['payment_type'] === 'deposit' ? 'Depósito' : 'Saldo',
                'reminders' => $reminder_statuses
            ];
        }
        
        wp_send_json_success(['reminders' => $reminders]);
    }
    
    /**
     * AJAX: Enviar recordatorio manual
     */
    public function ajax_send_reminder_manual()
    {
        check_ajax_referer('alquipress-payment-pipeline-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permisos insuficientes', 'alquipress')
            ]);
            return;
        }
        
        $payment_id = isset($_POST['payment_id']) ? absint($_POST['payment_id']) : 0;
        $reminder_type = isset($_POST['reminder_type']) ? intval($_POST['reminder_type']) : 0;
        
        if (empty($payment_id)) {
            wp_send_json_error([
                'message' => __('ID de pago no válido', 'alquipress')
            ]);
            return;
        }
        
        // Obtener instancia de reminders
        $reminders = new Alquipress_Payment_Reminders();
        $sent = $reminders->send_reminder($payment_id, $reminder_type);
        
        if ($sent) {
            wp_send_json_success([
                'message' => __('Recordatorio enviado correctamente', 'alquipress')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Error al enviar el recordatorio. Verifica que el pago existe y está pendiente.', 'alquipress')
            ]);
        }
    }
    
    /**
     * AJAX: Probar cron job de recordatorios
     */
    public function ajax_test_reminders_cron()
    {
        check_ajax_referer('alquipress-payment-pipeline-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'alquipress')]);
            return;
        }
        
        $reminders = new Alquipress_Payment_Reminders();
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';
        $today = current_time('Y-m-d');
        
        $pending_payments = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY scheduled_date ASC",
            ARRAY_A
        );
        
        if (empty($pending_payments)) {
            wp_send_json_success(['sent' => 0, 'message' => __('No hay pagos pendientes', 'alquipress')]);
            return;
        }

        // OPTIMIZACIÓN: Cargar metadatos de una vez
        $order_ids = array_unique(wp_list_pluck($pending_payments, 'order_id'));
        $order_ids_string = implode(',', array_map('intval', $order_ids));
        $meta_results = $wpdb->get_results("SELECT post_id, meta_key FROM {$wpdb->postmeta} WHERE post_id IN ($order_ids_string) AND meta_key LIKE '_payment_reminder_%'");
        
        $sent_map = [];
        foreach ($meta_results as $meta) {
            $sent_map[$meta->post_id][$meta->meta_key] = true;
        }

        $sent_count = 0;
        foreach ($pending_payments as $payment) {
            $order_id = (int)$payment['order_id'];
            $scheduled_date = date('Y-m-d', strtotime($payment['scheduled_date']));
            $days_until_due = floor((strtotime($scheduled_date) - strtotime($today)) / DAY_IN_SECONDS);
            $days_overdue = -$days_until_due;
            
            $check_reminder = function($key, $days_match) use ($payment, $order_id, $sent_map, $reminders, &$sent_count) {
                $meta_key = '_payment_reminder_' . $payment['id'] . '_' . $key;
                if ($days_match && !isset($sent_map[$order_id][$meta_key])) {
                    $reminder_days = ($key === 'due') ? 0 : (($key === 'overdue') ? -3 : (int)$key);
                    if ($reminders->send_reminder($payment['id'], $reminder_days)) {
                        $sent_count++;
                        return true;
                    }
                }
                return false;
            };

            $check_reminder('7d', $days_until_due === 7);
            $check_reminder('3d', $days_until_due === 3);
            $check_reminder('due', $days_until_due === 0);
            $check_reminder('overdue', $days_overdue === 3);
        }
        
        wp_send_json_success([
            'sent' => $sent_count,
            'message' => sprintf(__('Se enviaron %d recordatorios correctamente', 'alquipress'), $sent_count)
        ]);
    }
    
    /**
     * AJAX: Activar cron job de recordatorios
     */
    public function ajax_activate_reminders_cron()
    {
        check_ajax_referer('alquipress-payment-pipeline-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permisos insuficientes', 'alquipress')
            ]);
            return;
        }
        
        // Eliminar cron existente si existe
        $timestamp = wp_next_scheduled('alquipress_payment_reminders_daily');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'alquipress_payment_reminders_daily');
        }
        
        // Programar nuevo cron
        wp_schedule_event(time(), 'daily', 'alquipress_payment_reminders_daily');
        
        wp_send_json_success([
            'message' => __('Cron job activado correctamente', 'alquipress')
        ]);
    }
    
    /**
     * Verificar si un recordatorio fue enviado
     */
    private function was_reminder_sent($payment_schedule_id, $reminder_key)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT order_id FROM {$table} WHERE id = %d",
            $payment_schedule_id
        ), ARRAY_A);
        
        if (!$payment) {
            return false;
        }
        
        $order = wc_get_order($payment['order_id']);
        if (!$order) {
            return false;
        }
        
        $meta_key = '_payment_reminder_' . $payment_schedule_id . '_' . $reminder_key;
        return !empty($order->get_meta($meta_key));
    }
}

new Alquipress_Payment_Pipeline();
