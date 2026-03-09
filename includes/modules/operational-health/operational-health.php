<?php
/**
 * Módulo: Panel de Salud Operativa
 * Dashboard centralizado con alertas accionables sobre problemas operativos críticos
 * 
 * @package Alquipress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar clase de alertas
require_once __DIR__ . '/class-health-alerts.php';

class Alquipress_Operational_Health
{
    public function __construct()
    {
        // Hook para renderizar widget en dashboard
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_section_assets']);
        
        // Agregar contador en admin bar
        add_action('admin_bar_menu', [$this, 'add_admin_bar_counter'], 100);
        
        // AJAX para obtener detalles de alertas
        add_action('wp_ajax_alquipress_get_alert_details', [$this, 'ajax_get_alert_details']);
    }
    
    /**
     * Renderizar sección si es la página correcta
     */
    public function maybe_render_section($page)
    {
        if ($page === 'alquipress-dashboard') {
            $this->render_health_widget();
        }
    }
    
    /**
     * Encolar assets del módulo
     */
    public function enqueue_section_assets($page)
    {
        if ($page !== 'alquipress-dashboard') {
            return;
        }
        
        wp_enqueue_style(
            'alquipress-operational-health',
            ALQUIPRESS_URL . 'includes/modules/operational-health/assets/operational-health.css',
            [],
            ALQUIPRESS_VERSION
        );
        
        wp_enqueue_script(
            'alquipress-operational-health',
            ALQUIPRESS_URL . 'includes/modules/operational-health/assets/operational-health.js',
            ['jquery'],
            ALQUIPRESS_VERSION,
            true
        );
        
        wp_localize_script('alquipress-operational-health', 'alquipressHealth', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alquipress-health-nonce')
        ]);
    }
    
    /**
     * Renderizar widget de salud operativa en dashboard
     */
    public function render_health_widget()
    {
        $alerts = Alquipress_Health_Alerts::get_all_alerts();
        $critical_count = Alquipress_Health_Alerts::count_critical_alerts();
        
        // Agrupar por prioridad
        $alerts_by_priority = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => []
        ];
        
        foreach ($alerts as $alert) {
            $alerts_by_priority[$alert['priority']][] = $alert;
        }
        
        ?>
        <div class="alquipress-health-widget">
            <div class="health-widget-header">
                <h2 class="health-widget-title">
                    <span class="health-icon">🏥</span>
                    Salud Operativa
                    <?php if ($critical_count > 0): ?>
                        <span class="health-badge health-badge-critical"><?php echo esc_html($critical_count); ?></span>
                    <?php endif; ?>
                </h2>
                <div class="health-filters">
                    <button class="health-filter-btn active" data-priority="all">Todas</button>
                    <button class="health-filter-btn" data-priority="critical">Críticas</button>
                    <button class="health-filter-btn" data-priority="high">Altas</button>
                    <button class="health-filter-btn" data-priority="medium">Medias</button>
                </div>
            </div>
            
            <?php if (empty($alerts)): ?>
                <div class="health-empty-state">
                    <p>✅ Todo está en orden. No hay alertas pendientes.</p>
                </div>
            <?php else: ?>
                <div class="health-alerts-container">
                    <?php foreach ($alerts_by_priority as $priority => $priority_alerts): ?>
                        <?php if (empty($priority_alerts)) continue; ?>
                        <?php foreach ($priority_alerts as $alert): ?>
                            <div class="health-alert-card health-priority-<?php echo esc_attr($alert['priority']); ?>" data-alert-type="<?php echo esc_attr($alert['type']); ?>">
                                <div class="health-alert-header">
                                    <div class="health-alert-icon"><?php echo esc_html($alert['icon']); ?></div>
                                    <div class="health-alert-info">
                                        <h3 class="health-alert-title"><?php echo esc_html($alert['title']); ?></h3>
                                        <span class="health-alert-count"><?php echo esc_html($alert['count']); ?> <?php echo esc_html($alert['count'] === 1 ? 'elemento' : 'elementos'); ?></span>
                                    </div>
                                    <div class="health-alert-priority">
                                        <span class="health-priority-badge health-priority-<?php echo esc_attr($alert['priority']); ?>">
                                            <?php
                                            $priority_labels = [
                                                'critical' => 'Crítico',
                                                'high' => 'Alto',
                                                'medium' => 'Medio',
                                                'low' => 'Bajo'
                                            ];
                                            echo esc_html($priority_labels[$alert['priority']] ?? $alert['priority']);
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($alert['items']) && count($alert['items']) <= 5): ?>
                                    <div class="health-alert-items">
                                        <ul>
                                            <?php foreach (array_slice($alert['items'], 0, 5) as $item): ?>
                                                <li>
                                                    <?php if (isset($item['order_id'])): ?>
                                                        <a href="<?php echo esc_url($item['order_url']); ?>" target="_blank">
                                                            Pedido #<?php echo esc_html($item['order_number'] ?? $item['order_id']); ?>
                                                        </a>
                                                        <?php if (isset($item['customer_name'])): ?>
                                                            - <?php echo esc_html($item['customer_name']); ?>
                                                        <?php endif; ?>
                                                        <?php if (isset($item['days_pending'])): ?>
                                                            <span class="health-item-meta">(<?php echo esc_html($item['days_pending']); ?> días pendiente)</span>
                                                        <?php endif; ?>
                                                        <?php if (isset($item['days_until_checkin'])): ?>
                                                            <span class="health-item-meta">(Check-in en <?php echo esc_html($item['days_until_checkin']); ?> días)</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="health-alert-actions">
                                    <a href="<?php echo esc_url($alert['action_url']); ?>" class="health-action-btn">
                                        <?php echo esc_html($alert['action_text']); ?>
                                    </a>
                                    <?php if (!empty($alert['items']) && count($alert['items']) > 5): ?>
                                        <button class="health-action-btn health-view-all" data-alert-type="<?php echo esc_attr($alert['type']); ?>">
                                            Ver todos (<?php echo esc_html(count($alert['items'])); ?>)
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Agregar contador en admin bar
     */
    public function add_admin_bar_counter($wp_admin_bar)
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $critical_count = Alquipress_Health_Alerts::count_critical_alerts();
        
        if ($critical_count > 0) {
            $wp_admin_bar->add_node([
                'id' => 'alquipress_health_alerts',
                'title' => '<span class="ab-icon">🏥</span> <span class="ab-label">' . $critical_count . ' Alertas</span>',
                'href' => admin_url('admin.php?page=alquipress-dashboard#health-widget'),
                'meta' => [
                    'class' => 'alquipress-health-counter'
                ]
            ]);
        }
    }
    
    /**
     * AJAX: Obtener detalles de una alerta
     */
    public function ajax_get_alert_details()
    {
        check_ajax_referer('alquipress-health-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permisos insuficientes', 'alquipress')
            ]);
            return;
        }
        
        $alert_type = isset($_POST['alert_type']) ? sanitize_text_field($_POST['alert_type']) : '';
        
        if (empty($alert_type)) {
            wp_send_json_error([
                'message' => __('Tipo de alerta no especificado', 'alquipress')
            ]);
            return;
        }
        
        $alerts = Alquipress_Health_Alerts::get_alerts_by_type($alert_type);
        
        if (empty($alerts)) {
            wp_send_json_error([
                'message' => __('No se encontraron alertas', 'alquipress')
            ]);
            return;
        }
        
        $alert = reset($alerts);
        
        wp_send_json_success([
            'alert' => $alert,
            'items' => $alert['items'] ?? []
        ]);
    }
}

new Alquipress_Operational_Health();
