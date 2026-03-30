<?php
/**
 * Módulo: Columnas Personalizadas en Pedidos WooCommerce
 * Añade columnas: Propiedad, Fechas, Propietario, Semáforo Visual
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Order_Columns
{

    public function __construct()
    {
        // Columnas para el listado clásico de pedidos
        add_filter('manage_edit-shop_order_columns', [$this, 'add_custom_columns'], 20);
        add_action('manage_shop_order_posts_custom_column', [$this, 'populate_custom_columns'], 20, 2);

        // Compatibilidad con HPOS (High-Performance Order Storage)
        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_custom_columns'], 20);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'populate_custom_columns_hpos'], 20, 2);

        // Cargar estilos
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Añadir columnas personalizadas
     */
    public function add_custom_columns($columns)
    {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            // Insertar después de 'order_number'
            if ($key === 'order_number') {
                $new_columns['property'] = '🏠 Propiedad';
                $new_columns['check_dates'] = '📅 Fechas';
                $new_columns['owner'] = '👤 Propietario';
                $new_columns['booking_status'] = '🚦 Estado';
                $new_columns['ses_status'] = '🛂 SES';
            }
        }

        return $new_columns;
    }

    /**
     * Poblar columnas (sistema clásico)
     */
    public function populate_custom_columns($column, $post_id)
    {
        $order = wc_get_order($post_id);
        if (!$order)
            return;

        switch ($column) {
            case 'property':
                $this->render_property_column($order);
                break;
            case 'check_dates':
                $this->render_dates_column($order);
                break;
            case 'owner':
                $this->render_owner_column($order);
                break;
            case 'booking_status':
                $this->render_status_semaphore($order);
                break;
            case 'ses_status':
                $this->render_ses_status($order);
                break;
        }
    }

    /**
     * Poblar columnas (HPOS)
     */
    public function populate_custom_columns_hpos($column, $order)
    {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }

        if (!$order)
            return;

        switch ($column) {
            case 'property':
                $this->render_property_column($order);
                break;
            case 'check_dates':
                $this->render_dates_column($order);
                break;
            case 'owner':
                $this->render_owner_column($order);
                break;
            case 'booking_status':
                $this->render_status_semaphore($order);
                break;
            case 'ses_status':
                $this->render_ses_status($order);
                break;
        }
    }

    /**
     * Renderizar columna de Propiedad
     */
    private function render_property_column($order)
    {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $property_id = $product->get_id();
                $edit_url = get_edit_post_link($property_id);

                echo '<a href="' . esc_url($edit_url) . '" target="_blank" style="font-weight: 600;">';
                echo esc_html($product->get_name());
                echo '</a>';

                // Mostrar referencia interna si existe
                $ref = get_field('referencia_interna', $property_id);
                if ($ref) {
                    echo '<br><small style="color: #666;">Ref: ' . esc_html($ref) . '</small>';
                }

                break; // Solo mostrar la primera propiedad
            }
        }
    }

    /**
     * Renderizar columna de Fechas Check-in/Check-out
     */
    private function render_dates_column($order)
    {
        $checkin = $order->get_meta('_booking_checkin_date');
        $checkout = $order->get_meta('_booking_checkout_date');

        if ($checkin && $checkout) {
            // Check-in (verde)
            echo '<div style="margin-bottom: 4px;">';
            echo '<strong style="color: #2ea2cc;">↓ ' . date_i18n('d/m/Y', strtotime($checkin)) . '</strong>';
            echo '</div>';

            // Check-out (naranja)
            echo '<div style="margin-bottom: 4px;">';
            echo '<strong style="color: #d63638;">↑ ' . date_i18n('d/m/Y', strtotime($checkout)) . '</strong>';
            echo '</div>';

            // Calcular noches
            $diff = strtotime($checkout) - strtotime($checkin);
            $nights = floor($diff / (60 * 60 * 24));

            if ($nights > 0) {
                echo '<small style="color: #666;">' . $nights . ' ' . ($nights === 1 ? 'noche' : 'noches') . '</small>';
            }
        } else {
            echo '<span style="color: #999;">-</span>';
        }
    }

    /**
     * Renderizar columna de Propietario
     */
    private function render_owner_column($order)
    {
        // Obtener la propiedad del pedido
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                // Buscar el propietario asignado a esta propiedad
                $owner_id = get_field('propietario_asignado', $product->get_id());

                // El campo puede devolver array o valor único
                if (is_array($owner_id) && !empty($owner_id)) {
                    $owner_id = $owner_id[0];
                }

                if ($owner_id) {
                    $edit_url = get_edit_post_link($owner_id);
                    echo '<a href="' . esc_url($edit_url) . '" target="_blank" style="font-weight: 500;">';
                    echo esc_html(get_the_title($owner_id));
                    echo '</a>';
                } else {
                    echo '<span style="color: #999;">Sin propietario</span>';
                }

                break;
            }
        }
    }

    /**
     * Renderizar semáforo visual de estado
     */
    private function render_status_semaphore($order)
    {
        $status = $order->get_status();

        // Mapeo de estados con colores e iconos
        $semaphore_map = [
            'pending' => ['color' => '#999', 'icon' => '⚪', 'text' => 'Pendiente'],
            'processing' => ['color' => '#007cba', 'icon' => '🔵', 'text' => 'Procesando'],
            'deposito-ok' => ['color' => '#46b450', 'icon' => '🟢', 'text' => 'Depósito OK'],
            'pending-checkin' => ['color' => '#ffb900', 'icon' => '🟡', 'text' => 'Pre Check-in'],
            'in-progress' => ['color' => '#00a32a', 'icon' => '🟢', 'text' => 'En Curso'],
            'checkout-review' => ['color' => '#f0b849', 'icon' => '🟠', 'text' => 'Revisión'],
            'deposit-refunded' => ['color' => '#00a32a', 'icon' => '✅', 'text' => 'Fianza Dev.'],
            'completed' => ['color' => '#00a32a', 'icon' => '✅', 'text' => 'Completado'],
            'cancelled' => ['color' => '#dc3232', 'icon' => '🔴', 'text' => 'Cancelado'],
            'refunded' => ['color' => '#dc3232', 'icon' => '↩️', 'text' => 'Reembolsado'],
            'failed' => ['color' => '#dc3232', 'icon' => '❌', 'text' => 'Fallido'],
        ];

        $config = $semaphore_map[$status] ?? ['color' => '#999', 'icon' => '⚪', 'text' => ucfirst($status)];

        echo '<div style="text-align: center;">';
        echo '<span style="font-size: 20px; display: block; margin-bottom: 4px;">' . $config['icon'] . '</span>';
        echo '<small style="color: ' . $config['color'] . '; font-weight: 600; display: block;">';
        echo esc_html($config['text']);
        echo '</small>';
        echo '</div>';
    }

    /**
     * Renderizar estado de comunicación SES.
     */
    private function render_ses_status($order)
    {
        $status = (string) $order->get_meta('_alq_ses_status');
        if ($status === '') {
            $status = 'pending';
        }

        $map = [
            'pending' => ['label' => 'Pendiente', 'color' => '#6b7280', 'bg' => '#f3f4f6'],
            'xml_generated' => ['label' => 'XML', 'color' => '#1d4ed8', 'bg' => '#dbeafe'],
            'sent' => ['label' => 'Enviado', 'color' => '#0369a1', 'bg' => '#e0f2fe'],
            'accepted' => ['label' => 'Aceptado', 'color' => '#166534', 'bg' => '#dcfce7'],
            'rejected' => ['label' => 'Rechazado', 'color' => '#991b1b', 'bg' => '#fee2e2'],
        ];
        $item = isset($map[$status]) ? $map[$status] : $map['pending'];

        echo '<span style="display:inline-block;padding:4px 8px;border-radius:999px;font-size:11px;font-weight:700;'
            . 'color:' . esc_attr($item['color']) . ';background:' . esc_attr($item['bg']) . ';">'
            . esc_html($item['label']) . '</span>';
    }

    /**
     * Cargar assets CSS
     */
    public function enqueue_assets($hook)
    {
        // Solo cargar en la página de pedidos
        if ($hook === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') {
            wp_enqueue_style(
                'alquipress-order-columns',
                ALQUIPRESS_URL . 'includes/modules/order-columns/assets/order-columns.css',
                [],
                ALQUIPRESS_VERSION
            );
        }

        // Para HPOS
        if ($hook === 'woocommerce_page_wc-orders') {
            wp_enqueue_style(
                'alquipress-order-columns',
                ALQUIPRESS_URL . 'includes/modules/order-columns/assets/order-columns.css',
                [],
                ALQUIPRESS_VERSION
            );
        }
    }
}

new Alquipress_Order_Columns();
