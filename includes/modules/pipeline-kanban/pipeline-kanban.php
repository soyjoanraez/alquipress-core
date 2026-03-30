<?php
/**
 * Módulo: Pipeline Kanban
 * Vista tipo tablero para gestionar reservas visualmente integrada en el Dashboard.
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Pipeline_Kanban
{

    public function __construct()
    {
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_section_assets']);

        add_action('wp_ajax_alquipress_update_order_status', [$this, 'ajax_update_order_status']);
    }

    public function maybe_render_section($page)
    {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
        if ($page === 'alquipress-pipeline' && $tab !== 'cobros') {
            $this->render_pipeline_page();
        }
    }

    public function enqueue_section_assets($page)
    {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
        if ($page !== 'alquipress-pipeline' || $tab === 'cobros') {
            return;
        }
        
        // Cargar SortableJS (local, sin CDN)
        wp_enqueue_script('sortable-js', ALQUIPRESS_URL . 'includes/admin/assets/sortable.min.js', [], '1.15.0', true);
        
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
        
        // Cargar script del pipeline
        wp_enqueue_script(
            'alquipress-pipeline-kanban',
            ALQUIPRESS_URL . 'includes/modules/pipeline-kanban/assets/pipeline-kanban.js',
            ['jquery', 'sortable-js', 'alquipress-toast-notifications'],
            ALQUIPRESS_VERSION,
            true
        );
        
        // Localizar script con datos AJAX
        wp_localize_script('alquipress-pipeline-kanban', 'alquipressPipeline', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alquipress-pipeline-nonce')
        ]);
        
        // Cargar estilos
        wp_enqueue_style(
            'alquipress-pipeline-kanban',
            ALQUIPRESS_URL . 'includes/modules/pipeline-kanban/assets/pipeline-kanban.css',
            [],
            ALQUIPRESS_VERSION
        );
    }

    /**
     * Renderizar la página del pipeline (Diseño Unificado Pencil)
     */
    public function render_pipeline_page()
    {
        // Definir los estados del pipeline con colores modernos
        $pipeline_stages = [
            'pending' => ['label' => 'Pendiente Pago', 'color' => '#94a3b8', 'icon' => '⚪'],
            'processing' => ['label' => 'Procesando', 'color' => '#3b82f6', 'icon' => '🔵'],
            'deposit-paid' => ['label' => 'Depósito Pagado', 'color' => '#f39c12', 'icon' => '💰'],
            'deposito-ok' => ['label' => 'Depósito OK', 'color' => '#10b981', 'icon' => '🟢'],
            'fully-paid' => ['label' => 'Totalmente Pagado', 'color' => '#27ae60', 'icon' => '✅'],
            'balance-pending' => ['label' => 'Saldo Pendiente', 'color' => '#e67e22', 'icon' => '⏳'],
            'pending-checkin' => ['label' => 'Pre-Check-in', 'color' => '#f59e0b', 'icon' => '🟡'],
            'in-progress' => ['label' => 'En Curso', 'color' => '#059669', 'icon' => '🏠'],
            'checkout-review' => ['label' => 'Revisión', 'color' => '#f97316', 'icon' => '🔍'],
            'security-held' => ['label' => 'Fianza Retenida', 'color' => '#9b59b6', 'icon' => '🔒'],
            'completed' => ['label' => 'Finalizado', 'color' => '#059669', 'icon' => '✅']
        ];

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap alquipress-dashboard-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('pipeline'); ?>
                <main class="ap-owners-main">
                    <header class="ap-header">
                        <div class="ap-header-left">
                            <h1 class="ap-header-title"><?php esc_html_e('Pipeline', 'alquipress'); ?></h1>
                            <div class="ap-pipeline-tabs" style="display:flex;gap:4px;margin-top:8px;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-pipeline')); ?>" class="ap-tab ap-tab-active" style="padding:6px 14px;border-radius:8px;font-size:14px;font-weight:500;text-decoration:none;background:rgba(44,153,226,0.1);color:#2c99e2;">
                                    <?php esc_html_e('Reservas', 'alquipress'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-pipeline&tab=cobros')); ?>" class="ap-tab" style="padding:6px 14px;border-radius:8px;font-size:14px;font-weight:500;text-decoration:none;color:#507a95;">
                                    <?php esc_html_e('Cobros', 'alquipress'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="ap-header-right">
                            <div class="ap-pipeline-filters-row">
                                <select id="filter-date" class="ap-select-small">
                                    <option value="all">Todas las fechas</option>
                                    <option value="today">Hoy</option>
                                    <option value="week" selected>Esta semana</option>
                                    <option value="month">Este mes</option>
                                </select>
                                <button type="button" class="ap-reports-refresh" id="refresh-pipeline">
                                    <span class="dashicons dashicons-update"></span>
                                </button>
                            </div>
                        </div>
                    </header>

                    <div class="ap-pipeline-container">
                        <div class="alquipress-pipeline-board">
                            <?php foreach ($pipeline_stages as $status => $config): ?>
                                <?php
                                $orders = $this->get_orders_by_status($status);
                                $count = count($orders);
                                $column_total = 0;
                                foreach ($orders as $o) {
                                    $column_total += (float) (method_exists($o, 'get_meta') && $o->get_meta('_apm_booking_total') ? $o->get_meta('_apm_booking_total') : $o->get_total());
                                }
                                ?>

                                <div class="pipeline-column" data-status="<?php echo esc_attr($status); ?>">
                                    <div class="column-header" style="border-bottom: 2px solid <?php echo $config['color']; ?>">
                                        <div class="column-title">
                                            <span class="column-label"><?php echo esc_html($config['label']); ?></span>
                                            <span class="column-count"><?php echo $count; ?></span>
                                        </div>
                                        <?php if ($column_total > 0): ?>
                                        <div class="column-total"><?php echo wc_price($column_total); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="column-cards" id="cards-<?php echo esc_attr($status); ?>">
                                        <?php if (!empty($orders)): ?>
                                            <?php foreach ($orders as $order): ?>
                                                <?php $this->render_order_card($order); ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="empty-column-msg">
                                                <span>Sin reservas</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <style>
            .ap-pipeline-container { margin-top: 24px; overflow-x: auto; padding-bottom: 20px; }
            .alquipress-pipeline-board { display: flex; gap: 16px; min-width: min-content; }
            .pipeline-column { background: #f1f5f9; border-radius: 12px; width: 280px; min-width: 280px; display: flex; flex-direction: column; max-height: calc(100vh - 250px); }
            .column-header { padding: 16px; background: #f8fafc; border-radius: 12px 12px 0 0; }
            .column-title { display: flex; justify-content: space-between; align-items: center; }
            .column-label { font-weight: 700; color: #1e293b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
            .column-count { background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
            .column-total { margin-top: 6px; font-size: 12px; font-weight: 600; color: #059669; }
            .column-cards { padding: 12px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; min-height: 100px; }
            .empty-column-msg { text-align: center; padding: 20px; color: #94a3b8; font-size: 12px; font-style: italic; }
            
            /* Estilo de Tarjeta Unificado con Dashboard */
            .order-card { background: #ffffff; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); cursor: pointer; transition: transform 0.1s, box-shadow 0.1s; position: relative; }
            .order-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-color: #cbd5e0; }
            .card-header { display: flex; justify-content: space-between; margin-bottom: 8px; align-items: flex-start; }
            .order-number { font-weight: 700; color: #3b82f6; font-size: 12px; }
            .order-total { font-weight: 600; color: #1e293b; font-size: 12px; }
            .card-customer { font-size: 13px; color: #334155; margin-bottom: 4px; display: block; font-weight: 600; }
            .card-property { font-size: 11px; color: #64748b; margin-bottom: 8px; }
            .card-dates { background: #f8fafc; border-radius: 6px; padding: 8px; font-size: 10px; color: #475569; }
            .date-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
            .urgent { border-left: 4px solid #ef4444 !important; }
            .ap-pipeline-filters-row { display: flex; gap: 8px; align-items: center; }
            .ap-select-small { padding: 4px 8px !important; font-size: 12px !important; height: auto !important; border-radius: 6px !important; border: 1px solid #e2e8f0 !important; }
            .ap-reports-refresh { background: #fff; border: 1px solid #e2e8f0; padding: 6px; border-radius: 6px; cursor: pointer; color: #64748b; }
            .ap-reports-refresh:hover { background: #f8fafc; color: #1e293b; }
        </style>

        <script>
            jQuery(document).ready(function ($) {
                // Actualizar al cambiar filtros
                $('#filter-date, #refresh-pipeline').on('change click', function () {
                    location.reload();
                });

                // Abrir pedido al hacer click
                $('.order-card').on('click', function (e) {
                    if (!$(e.target).is('a, button')) {
                        const orderUrl = $(this).data('order-url');
                        if (orderUrl) window.open(orderUrl, '_blank');
                    }
                });

                // Drag & Drop se inicializa desde pipeline-kanban.js
                // El código inline se eliminó para evitar duplicación y usar la implementación completa del archivo JS externo
            });
        </script>
        <?php
    }

    /**
     * Renderizar tarjeta de pedido
     */
    private function render_order_card($order)
    {
        $order_id = $order->get_id();
        $edit_url = admin_url('post.php?post=' . $order_id . '&action=edit');
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $property_name = $this->get_order_property_name($order);
        $checkin = $order->get_meta('_booking_checkin_date');
        $checkout = $order->get_meta('_booking_checkout_date');
        $total = $order->get_total();

        $is_urgent = false;
        if ($checkin) {
            $days = floor((strtotime($checkin) - time()) / (60 * 60 * 24));
            $is_urgent = $days >= 0 && $days <= 3;
        }

        ?>
        <div class="order-card <?php echo $is_urgent ? 'urgent' : ''; ?>" data-order-id="<?php echo $order_id; ?>"
            data-order-url="<?php echo esc_url($edit_url); ?>">
            <div class="card-header">
                <span class="order-number">#<?php echo $order_id; ?></span>
                <span class="order-total"><?php echo wc_price($total); ?></span>
            </div>
            <div class="card-customer"><?php echo esc_html($customer_name); ?></div>
            <div class="card-property">🏠 <?php echo esc_html($property_name); ?></div>
            <?php if ($checkin && $checkout): ?>
                <div class="card-dates">
                    <div class="date-row"><span>Entrada:</span> <span><?php echo wp_date('d/m/Y', strtotime($checkin)); ?></span></div>
                    <div class="date-row"><span>Salida:</span> <span><?php echo wp_date('d/m/Y', strtotime($checkout)); ?></span></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function get_orders_by_status($status)
    {
        // Estados estándar de WooCommerce no necesitan prefijo
        $standard_statuses = ['pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed'];
        
        // Si no es un estado estándar, agregar prefijo wc- para búsqueda
        if (!in_array($status, $standard_statuses)) {
            $status = 'wc-' . $status;
        }
        
        // meta_query compatible con HPOS (WooCommerce abstrae internamente desde 8.2+)
        return wc_get_orders([
            'limit' => 20,
            'status' => $status,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [['key' => '_booking_checkin_date', 'compare' => 'EXISTS']]
        ]);
    }

    private function get_order_property_name($order)
    {
        return Alquipress_Property_Helper::get_order_property_name($order);
    }

    public function ajax_update_order_status()
    {
        check_ajax_referer('alquipress-pipeline-nonce', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error([
                'message' => __('No tienes permisos para editar pedidos', 'alquipress')
            ]);
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (!$order_id) {
            wp_send_json_error([
                'message' => __('ID de pedido inválido', 'alquipress')
            ]);
        }
        
        if (empty($new_status)) {
            wp_send_json_error([
                'message' => __('Estado no especificado', 'alquipress')
            ]);
        }
        
        // Normalizar: remover prefijo wc- si existe (update_status no lo necesita)
        $new_status = str_replace('wc-', '', $new_status);
        
        // Validar que el estado existe en los estados registrados
        $valid_statuses = [
            // Estados estándar de WooCommerce
            'pending', 
            'processing', 
            'completed', 
            'cancelled', 
            'refunded',
            'on-hold',
            'failed',
            // Estados personalizados del booking pipeline
            'deposito-ok', 
            'pending-checkin', 
            'in-progress', 
            'checkout-review',
            'deposit-refunded',
            // Estados personalizados del payment manager
            'deposit-paid',
            'fully-paid',
            'balance-pending',
            'security-held',
            'payment-failed'
        ];
        
        if (!in_array($new_status, $valid_statuses, true)) {
            wp_send_json_error([
                'message' => sprintf(__('Estado no válido: %s', 'alquipress'), esc_html($new_status))
            ]);
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error([
                'message' => __('Pedido no encontrado', 'alquipress')
            ]);
        }
        
        // Obtener estado actual antes de actualizar para logging
        $old_status = $order->get_status();
        
        // Verificar que el estado esté registrado en WooCommerce
        $all_statuses = wc_get_order_statuses();
        $status_with_prefix = 'wc-' . $new_status;
        if (!isset($all_statuses[$status_with_prefix]) && !isset($all_statuses[$new_status])) {
            wp_send_json_error([
                'message' => sprintf(__('El estado "%s" no está registrado en WooCommerce', 'alquipress'), esc_html($new_status))
            ]);
            return;
        }
        
        try {
            // Usar update_status con manual=true para permitir cambios incluso en pedidos "no editables"
            $result = $order->update_status($new_status, __('Movido en Pipeline CRM', 'alquipress'), true);
            
            // Recargar el pedido para obtener el estado actualizado
            $order = wc_get_order($order_id);
            $updated_status = $order->get_status();
            
            // Logging para debug
            if (class_exists('Alquipress_Logger')) {
                Alquipress_Logger::debug(
                    'Estado de pedido actualizado en Pipeline',
                    Alquipress_Logger::CONTEXT_AJAX,
                    [
                        'order_id' => $order_id,
                        'old_status' => $old_status,
                        'new_status' => $new_status,
                        'updated_status' => $updated_status,
                        'update_result' => $result,
                        'status_match' => ($updated_status === $new_status)
                    ]
                );
            }
            
            if ($result && $updated_status === $new_status) {
                wp_send_json_success([
                    'message' => __('Estado actualizado correctamente', 'alquipress'),
                    'old_status' => $old_status,
                    'new_status' => $updated_status
                ]);
            } else {
                wp_send_json_error([
                    'message' => sprintf(__('No se pudo actualizar el estado. Estado actual: %s, Estado esperado: %s', 'alquipress'), esc_html($updated_status), esc_html($new_status))
                ]);
            }
        } catch (Exception $e) {
            if (class_exists('Alquipress_Logger')) {
                Alquipress_Logger::error(
                    'Error al actualizar estado de pedido en Pipeline',
                    Alquipress_Logger::CONTEXT_AJAX,
                    [
                        'order_id' => $order_id,
                        'old_status' => $old_status,
                        'new_status' => $new_status,
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]
                );
            }
            wp_send_json_error([
                'message' => sprintf(__('Error al actualizar el estado del pedido: %s', 'alquipress'), $e->getMessage())
            ]);
        }
    }
}

new Alquipress_Pipeline_Kanban();