<?php
/**
 * Módulo: Owner Revenue Reporting
 * 
 * Calcula y muestra los ingresos totales de cada propietario
 * basándose en los pedidos completados de sus propiedades.
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Owner_Revenue
{

    public function __construct()
    {
        // Añadir metabox en la página de edición de propietario
        add_action('add_meta_boxes', [$this, 'add_revenue_metabox']);

        // Añadir columna de ingresos en el listado de propietarios
        add_filter('manage_propietario_posts_columns', [$this, 'add_revenue_column'], 20);
        add_action('manage_propietario_posts_custom_column', [$this, 'populate_revenue_column'], 20, 2);

        // Shortcode para mostrar panel de propietario
        add_shortcode('owner_dashboard', [$this, 'render_owner_dashboard']);
    }

    /**
     * Calcula los ingresos totales de un propietario
     */
    public function calculate_owner_revenue($owner_id, $start_date = null, $end_date = null)
    {
        // Obtener las propiedades del propietario
        $properties = get_field('owner_properties', $owner_id);

        if (empty($properties)) {
            return [
                'total' => 0,
                'count' => 0,
                'properties' => []
            ];
        }

        $total_revenue = 0;
        $total_bookings = 0;
        $property_breakdown = [];

        foreach ($properties as $property_id) {
            // Buscar pedidos completados de esta propiedad
            $args = [
                'post_type' => 'shop_order',
                'posts_per_page' => -1,
                'post_status' => ['wc-completed', 'wc-processing'],
                'meta_query' => [
                    [
                        'key' => '_order_key',
                        'compare' => 'EXISTS'
                    ]
                ]
            ];

            if ($start_date) {
                $args['date_query'][] = [
                    'after' => $start_date,
                    'inclusive' => true
                ];
            }

            if ($end_date) {
                $args['date_query'][] = [
                    'before' => $end_date,
                    'inclusive' => true
                ];
            }

            $orders = get_posts($args);
            $property_revenue = 0;
            $property_bookings = 0;

            foreach ($orders as $order_post) {
                $order = wc_get_order($order_post->ID);

                // Verificar si el pedido contiene este producto
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if ($product && $product->get_id() == $property_id) {
                        $property_revenue += $order->get_total();
                        $property_bookings++;
                        $total_bookings++;
                        break;
                    }
                }
            }

            $total_revenue += $property_revenue;

            $property_breakdown[$property_id] = [
                'name' => get_the_title($property_id),
                'revenue' => $property_revenue,
                'bookings' => $property_bookings
            ];
        }

        // Aplicar comisión
        $commission_rate = get_field('owner_commission_rate', $owner_id);
        $commission = 0;
        $net_revenue = $total_revenue;

        if ($commission_rate) {
            $commission = ($total_revenue * $commission_rate) / 100;
            $net_revenue = $total_revenue - $commission;
        }

        return [
            'total' => $total_revenue,
            'commission' => $commission,
            'net' => $net_revenue,
            'count' => $total_bookings,
            'properties' => $property_breakdown
        ];
    }

    /**
     * Añadir metabox de ingresos
     */
    public function add_revenue_metabox()
    {
        add_meta_box(
            'owner_revenue_metabox',
            '💰 Resumen de Ingresos',
            [$this, 'render_revenue_metabox'],
            'propietario',
            'side',
            'high'
        );
    }

    /**
     * Renderizar metabox de ingresos
     */
    public function render_revenue_metabox($post)
    {
        $stats = $this->calculate_owner_revenue($post->ID);
        $commission_rate = get_field('owner_commission_rate', $post->ID);

        ?>
        <div style="padding: 10px 0;">
            <div style="margin-bottom: 15px; padding: 10px; background: #f0f6fb; border-radius: 4px;">
                <strong style="display: block; font-size: 11px; color: #666; margin-bottom: 5px;">INGRESOS BRUTOS</strong>
                <span style="font-size: 24px; font-weight: bold; color: #2c99e2;">
                    <?php echo wc_price($stats['total']); ?>
                </span>
            </div>

            <?php if ($commission_rate): ?>
                <div style="margin-bottom: 15px; padding: 10px; background: #fff3cd; border-radius: 4px;">
                    <strong style="display: block; font-size: 11px; color: #666; margin-bottom: 5px;">COMISIÓN (
                        <?php echo $commission_rate; ?>%)
                    </strong>
                    <span style="font-size: 18px; font-weight: bold; color: #ff9800;">-
                        <?php echo wc_price($stats['commission']); ?>
                    </span>
                </div>

                <div style="margin-bottom: 15px; padding: 10px; background: #d4edda; border-radius: 4px;">
                    <strong style="display: block; font-size: 11px; color: #666; margin-bottom: 5px;">NETO A PAGAR</strong>
                    <span style="font-size: 24px; font-weight: bold; color: #28a745;">
                        <?php echo wc_price($stats['net']); ?>
                    </span>
                </div>
            <?php endif; ?>

            <div style="text-align: center; padding: 10px; border-top: 1px solid #ddd; margin-top: 15px;">
                <p style="margin: 0; font-size: 13px; color: #666;">
                    <strong>
                        <?php echo $stats['count']; ?>
                    </strong> reservas completadas
                </p>
            </div>

            <?php if (!empty($stats['properties'])): ?>
                <div style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                    <strong style="display: block; margin-bottom: 10px; font-size: 12px;">Desglose por Propiedad:</strong>
                    <?php foreach ($stats['properties'] as $prop_data): ?>
                        <div style="margin-bottom: 8px; padding: 8px; background: #f9f9f9; border-radius: 3px; font-size: 11px;">
                            <div><strong>
                                    <?php echo $prop_data['name']; ?>
                                </strong></div>
                            <div style="color: #666;">
                                <?php echo wc_price($prop_data['revenue']); ?>
                                •
                                <?php echo $prop_data['bookings']; ?> reserva(s)
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Añadir columna de ingresos en listado
     */
    public function add_revenue_column($columns)
    {
        // Insertar antes de la fecha
        $new_columns = [];
        foreach ($columns as $key => $value) {
            if ($key === 'date') {
                $new_columns['revenue'] = '💰 Ingresos';
            }
            $new_columns[$key] = $value;
        }
        return $new_columns;
    }

    /**
     * Poblar columna de ingresos
     */
    public function populate_revenue_column($column, $post_id)
    {
        if ($column === 'revenue') {
            $stats = $this->calculate_owner_revenue($post_id);
            echo '<strong style="color: #2c99e2;">' . wc_price($stats['total']) . '</strong><br>';
            echo '<small style="color: #666;">' . $stats['count'] . ' reservas</small>';
        }
    }

    /**
     * Shortcode para dashboard del propietario
     */
    public function render_owner_dashboard($atts)
    {
        // Este shortcode se puede usar si creas un área de propietarios en el frontend
        $atts = shortcode_atts(['owner_id' => get_current_user_id()], $atts);

        // Lógica futura para mostrar dashboard completo
        return '<p>Dashboard de propietario en desarrollo...</p>';
    }
}

new Alquipress_Owner_Revenue();
