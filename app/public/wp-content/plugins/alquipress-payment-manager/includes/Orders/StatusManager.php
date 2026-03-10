<?php
/**
 * Gestor de estados de pedido personalizados
 *
 * @package ALQUIPRESS\PaymentManager\Orders
 */

namespace ALQUIPRESS\PaymentManager\Orders;

defined('ABSPATH') || exit;

/**
 * Class StatusManager
 * Registra y gestiona estados de pedido personalizados
 */
class StatusManager {

    /**
     * Estados personalizados
     *
     * @var array
     */
    private $custom_statuses = [];

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'register_statuses']);
        add_filter('wc_order_statuses', [$this, 'add_statuses_to_list']);
        add_filter('woocommerce_reports_order_statuses', [$this, 'add_to_reports']);

        // Colores en el admin
        add_action('admin_head', [$this, 'add_status_colors']);

        // Columna personalizada en listado de pedidos
        add_filter('manage_edit-shop_order_columns', [$this, 'add_payment_status_column']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'render_payment_status_column'], 10, 2);

        // Compatibilidad con HPOS
        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_payment_status_column']);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'render_payment_status_column_hpos'], 10, 2);
    }

    /**
     * Obtener estados (inicialización perezosa)
     */
    public function get_statuses() {
        if (empty($this->custom_statuses)) {
            $this->define_statuses();
        }
        return $this->custom_statuses;
    }

    /**
     * Definir estados personalizados
     */
    private function define_statuses() {
        $this->custom_statuses = [
            'wc-deposit-paid' => [
                'label'       => __('Depósito Pagado', 'apm'),
                'label_count' => _n_noop(
                    'Depósito Pagado <span class="count">(%s)</span>',
                    'Depósito Pagado <span class="count">(%s)</span>',
                    'apm'
                ),
                'color'       => '#f39c12',
                'background'  => '#fef5e7',
            ],
            'wc-balance-pending' => [
                'label'       => __('Saldo Pendiente', 'apm'),
                'label_count' => _n_noop(
                    'Saldo Pendiente <span class="count">(%s)</span>',
                    'Saldo Pendiente <span class="count">(%s)</span>',
                    'apm'
                ),
                'color'       => '#e67e22',
                'background'  => '#fdebd0',
            ],
            'wc-fully-paid' => [
                'label'       => __('Totalmente Pagado', 'apm'),
                'label_count' => _n_noop(
                    'Totalmente Pagado <span class="count">(%s)</span>',
                    'Totalmente Pagado <span class="count">(%s)</span>',
                    'apm'
                ),
                'color'       => '#27ae60',
                'background'  => '#e8f6ef',
            ],
            'wc-security-held' => [
                'label'       => __('Fianza Retenida', 'apm'),
                'label_count' => _n_noop(
                    'Fianza Retenida <span class="count">(%s)</span>',
                    'Fianza Retenida <span class="count">(%s)</span>',
                    'apm'
                ),
                'color'       => '#9b59b6',
                'background'  => '#f4ecf7',
            ],
            'wc-payment-failed' => [
                'label'       => __('Pago Fallido', 'apm'),
                'label_count' => _n_noop(
                    'Pago Fallido <span class="count">(%s)</span>',
                    'Pago Fallido <span class="count">(%s)</span>',
                    'apm'
                ),
                'color'       => '#e74c3c',
                'background'  => '#fdedec',
            ],
        ];
    }

    /**
     * Registrar estados en WordPress
     */
    public function register_statuses() {
        foreach ($this->get_statuses() as $status => $args) {
            register_post_status($status, [
                'label'                     => $args['label'],
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => $args['label_count'],
            ]);
        }
    }

    /**
     * Añadir estados a la lista de WooCommerce
     *
     * @param array $statuses Estados existentes
     * @return array
     */
    public function add_statuses_to_list($statuses) {
        $new_statuses = [];

        foreach ($statuses as $key => $label) {
            $new_statuses[$key] = $label;

            // Insertar después de "processing"
            if ($key === 'wc-processing') {
                foreach ($this->get_statuses() as $status => $args) {
                    $new_statuses[$status] = $args['label'];
                }
            }
        }

        return $new_statuses;
    }

    /**
     * Incluir en reportes
     *
     * @param array $statuses Estados para reportes
     * @return array
     */
    public function add_to_reports($statuses) {
        return array_merge($statuses, ['deposit-paid', 'balance-pending', 'fully-paid', 'security-held']);
    }

    /**
     * Añadir colores CSS para los estados
     */
    public function add_status_colors() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, ['edit-shop_order', 'woocommerce_page_wc-orders'])) {
            return;
        }

        echo '<style>';
        foreach ($this->get_statuses() as $status => $args) {
            $status_slug = str_replace('wc-', '', $status);
            echo ".order-status.status-{$status_slug} {
                background: {$args['background']};
                color: {$args['color']};
            }";
        }
        echo '</style>';
    }

    /**
     * Añadir columna de estado de pago
     *
     * @param array $columns Columnas existentes
     * @return array
     */
    public function add_payment_status_column($columns) {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'order_total') {
                $new_columns['apm_payment_status'] = __('Estado Pago', 'apm');
            }
        }

        return $new_columns;
    }

    /**
     * Renderizar columna de estado de pago (legacy)
     *
     * @param string $column  Nombre de la columna
     * @param int    $post_id ID del post
     */
    public function render_payment_status_column($column, $post_id) {
        if ($column !== 'apm_payment_status') {
            return;
        }

        $order = wc_get_order($post_id);
        $this->render_payment_status_badge($order);
    }

    /**
     * Renderizar columna de estado de pago (HPOS)
     *
     * @param string    $column Nombre de la columna
     * @param \WC_Order $order  Pedido
     */
    public function render_payment_status_column_hpos($column, $order) {
        if ($column !== 'apm_payment_status') {
            return;
        }

        $this->render_payment_status_badge($order);
    }

    /**
     * Renderizar badge de estado de pago
     *
     * @param \WC_Order|null $order Pedido
     */
    private function render_payment_status_badge($order) {
        if (!$order) {
            echo '—';
            return;
        }

        $is_staged = $order->get_meta('_apm_is_staged_payment');

        if ($is_staged !== 'yes') {
            echo '<span style="color: #7f8c8d;">—</span>';
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';

        $pending = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE order_id = %d AND status = 'pending'",
            $order->get_id()
        ));

        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE order_id = %d AND status = 'completed'",
            $order->get_id()
        ));

        $failed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE order_id = %d AND status = 'failed'",
            $order->get_id()
        ));

        if ($failed > 0) {
            echo '<span style="color: #e74c3c; font-weight: bold;">❌ ' . __('Pago fallido', 'apm') . '</span>';
        } elseif ($pending > 0) {
            echo '<span style="color: #f39c12;">⏱ ' . sprintf(_n('%d pago pendiente', '%d pagos pendientes', $pending, 'apm'), $pending) . '</span>';
        } else {
            echo '<span style="color: #27ae60;">✓ ' . __('Completo', 'apm') . '</span>';
        }
    }

    /**
     * Obtener estados personalizados
     *
     * @return array
     */
    public function get_custom_statuses() {
        return $this->get_statuses();
    }
}
