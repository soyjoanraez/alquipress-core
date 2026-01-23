<?php
/**
 * Módulo: Pipeline Kanban
 * Vista tipo tablero para gestionar reservas visualmente
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Pipeline_Kanban
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu_page'], 25);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Añadir página al menú de WordPress
     */
    public function add_menu_page()
    {
        add_submenu_page(
            'alquipress-settings',
            'Pipeline de Reservas',
            '📊 Pipeline',
            'edit_shop_orders',
            'alquipress-pipeline',
            [$this, 'render_pipeline_page']
        );
    }

    /**
     * Renderizar la página del pipeline
     */
    public function render_pipeline_page()
    {
        // Definir los estados del pipeline
        $pipeline_stages = [
            'pending' => [
                'label' => 'Pendiente Pago',
                'color' => '#999',
                'icon' => '⚪'
            ],
            'processing' => [
                'label' => 'Procesando',
                'color' => '#007cba',
                'icon' => '🔵'
            ],
            'deposito-ok' => [
                'label' => 'Depósito Recibido',
                'color' => '#46b450',
                'icon' => '🟢'
            ],
            'pending-checkin' => [
                'label' => 'Pendiente Check-in',
                'color' => '#ffb900',
                'icon' => '🟡'
            ],
            'in-progress' => [
                'label' => 'Estancia en Curso',
                'color' => '#00a32a',
                'icon' => '🟢'
            ],
            'checkout-review' => [
                'label' => 'Revisión Salida',
                'color' => '#f0b849',
                'icon' => '🟠'
            ],
            'completed' => [
                'label' => 'Completado',
                'color' => '#00a32a',
                'icon' => '✅'
            ]
        ];

        ?>
        <div class="wrap alquipress-pipeline-wrap">
            <h1 class="wp-heading-inline">📊 Pipeline de Reservas</h1>
            <p class="subtitle">Gestión visual de reservas por estado</p>

            <div class="alquipress-pipeline-filters">
                <div class="filter-group">
                    <label for="filter-date">Filtrar por fecha:</label>
                    <select id="filter-date">
                        <option value="all">Todas las fechas</option>
                        <option value="today">Hoy</option>
                        <option value="week" selected>Esta semana</option>
                        <option value="month">Este mes</option>
                        <option value="next-month">Próximo mes</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter-property">Propiedad:</label>
                    <select id="filter-property">
                        <option value="all">Todas las propiedades</option>
                        <?php
                        $properties = get_posts([
                            'post_type' => 'product',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ]);

                        foreach ($properties as $property) {
                            echo '<option value="' . $property->ID . '">' . esc_html($property->post_title) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <button type="button" class="button button-primary" id="refresh-pipeline">
                    🔄 Actualizar
                </button>
            </div>

            <div class="alquipress-pipeline-board">
                <?php foreach ($pipeline_stages as $status => $config): ?>
                    <?php
                    $orders = $this->get_orders_by_status($status);
                    $count = count($orders);
                    ?>

                    <div class="pipeline-column" data-status="<?php echo esc_attr($status); ?>">
                        <div class="column-header" style="border-top: 4px solid <?php echo $config['color']; ?>">
                            <div class="column-title">
                                <span class="column-icon"><?php echo $config['icon']; ?></span>
                                <span class="column-label"><?php echo esc_html($config['label']); ?></span>
                            </div>
                            <div class="column-count"><?php echo $count; ?></div>
                        </div>

                        <div class="column-cards">
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                    <?php $this->render_order_card($order); ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-column">
                                    <span style="color: #999; font-size: 12px;">Sin reservas</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // Actualizar pipeline al cambiar filtros
                $('#filter-date, #filter-property, #refresh-pipeline').on('change click', function() {
                    location.reload();
                });

                // Hacer click en tarjeta abre el pedido
                $('.order-card').on('click', function(e) {
                    if (!$(e.target).is('a')) {
                        const orderUrl = $(this).data('order-url');
                        if (orderUrl) {
                            window.open(orderUrl, '_blank');
                        }
                    }
                });
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

        // Obtener datos del pedido
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $property_name = $this->get_order_property_name($order);
        $checkin = $order->get_meta('_booking_checkin_date');
        $checkout = $order->get_meta('_booking_checkout_date');
        $total = $order->get_total();

        // Calcular noches
        $nights = 0;
        if ($checkin && $checkout) {
            $diff = strtotime($checkout) - strtotime($checkin);
            $nights = floor($diff / (60 * 60 * 24));
        }

        // Determinar si es urgente (check-in en menos de 3 días)
        $is_urgent = false;
        if ($checkin) {
            $days_until_checkin = floor((strtotime($checkin) - time()) / (60 * 60 * 24));
            $is_urgent = $days_until_checkin >= 0 && $days_until_checkin <= 3;
        }

        ?>
        <div class="order-card <?php echo $is_urgent ? 'urgent' : ''; ?>" data-order-id="<?php echo $order_id; ?>"
            data-order-url="<?php echo esc_url($edit_url); ?>">

            <?php if ($is_urgent): ?>
                <div class="urgent-badge">⚡ Urgente</div>
            <?php endif; ?>

            <div class="card-header">
                <div class="order-number">
                    <a href="<?php echo esc_url($edit_url); ?>" target="_blank"
                        style="text-decoration: none; color: inherit; font-weight: 700;">
                        #<?php echo $order_id; ?>
                    </a>
                </div>
                <div class="order-total"><?php echo wc_price($total); ?></div>
            </div>

            <div class="card-customer">
                <strong>👤 <?php echo esc_html($customer_name); ?></strong>
            </div>

            <div class="card-property">
                🏠 <?php echo esc_html($property_name); ?>
            </div>

            <?php if ($checkin && $checkout): ?>
                <div class="card-dates">
                    <div class="date-row">
                        <span class="date-label">↓ Check-in:</span>
                        <span class="date-value"><?php echo date_i18n('d/m/Y', strtotime($checkin)); ?></span>
                    </div>
                    <div class="date-row">
                        <span class="date-label">↑ Check-out:</span>
                        <span class="date-value"><?php echo date_i18n('d/m/Y', strtotime($checkout)); ?></span>
                    </div>
                    <?php if ($nights > 0): ?>
                        <div class="card-nights">
                            <?php echo $nights; ?> <?php echo $nights === 1 ? 'noche' : 'noches'; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="card-footer">
                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small" target="_blank">
                    Ver Detalles
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener pedidos por estado
     */
    private function get_orders_by_status($status)
    {
        $args = [
            'limit' => -1,
            'status' => $status,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        return wc_get_orders($args);
    }

    /**
     * Obtener nombre de la propiedad del pedido
     */
    private function get_order_property_name($order)
    {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                return $product->get_name();
            }
        }
        return '-';
    }

    /**
     * Cargar assets CSS y JS
     */
    public function enqueue_assets($hook)
    {
        // Solo cargar en la página del pipeline
        if ($hook !== 'alquipress_page_alquipress-pipeline') {
            return;
        }

        wp_enqueue_style(
            'alquipress-pipeline-kanban',
            ALQUIPRESS_URL . 'includes/modules/pipeline-kanban/assets/pipeline-kanban.css',
            [],
            ALQUIPRESS_VERSION
        );

        wp_enqueue_script(
            'alquipress-pipeline-kanban',
            ALQUIPRESS_URL . 'includes/modules/pipeline-kanban/assets/pipeline-kanban.js',
            ['jquery'],
            ALQUIPRESS_VERSION,
            true
        );
    }
}

new Alquipress_Pipeline_Kanban();
