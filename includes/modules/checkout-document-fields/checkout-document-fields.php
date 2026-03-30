<?php
/**
 * Módulo: Campos DNI/Pasaporte en checkout
 * Pide documentación de identidad antes de confirmar la reserva
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Checkout_Document_Fields
{
    const META_DOC_TYPE = '_guest_document_type';
    const META_DOC_NUMBER = '_guest_document_number';

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
        add_filter('woocommerce_checkout_fields', [$this, 'add_checkout_fields']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_checkout_fields'], 10, 2);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_order_admin']);
        add_filter('woocommerce_order_formatted_billing_address', [$this, 'add_document_to_formatted_address'], 10, 2);
    }

    public function add_checkout_fields($fields)
    {
        $fields['billing']['billing_document_type'] = [
            'type' => 'select',
            'label' => __('Tipo de documento', 'alquipress'),
            'required' => true,
            'options' => [
                '' => __('Seleccionar...', 'alquipress'),
                'dni' => __('DNI', 'alquipress'),
                'nie' => __('NIE', 'alquipress'),
                'passport' => __('Pasaporte', 'alquipress'),
            ],
            'priority' => 35,
        ];

        $fields['billing']['billing_document_number'] = [
            'type' => 'text',
            'label' => __('Número de documento', 'alquipress'),
            'required' => true,
            'placeholder' => __('Ej: 12345678A', 'alquipress'),
            'priority' => 36,
        ];

        return $fields;
    }

    public function save_checkout_fields($order_id, $data)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        if (!empty($_POST['billing_document_type'])) {
            $type = sanitize_text_field(wp_unslash($_POST['billing_document_type']));
            if (in_array($type, ['dni', 'nie', 'passport'], true)) {
                $order->update_meta_data(self::META_DOC_TYPE, $type);
            }
        }
        if (!empty($_POST['billing_document_number'])) {
            $order->update_meta_data(self::META_DOC_NUMBER, sanitize_text_field(wp_unslash($_POST['billing_document_number'])));
        }
        $order->save();
    }

    public function display_order_admin($order)
    {
        if (!$order) {
            return;
        }
        if (!is_a($order, 'WC_Order')) {
            $order = wc_get_order($order);
        }
        if (!$order) {
            return;
        }

        $type = $order->get_meta(self::META_DOC_TYPE);
        $number = $order->get_meta(self::META_DOC_NUMBER);
        if (!$type && !$number) {
            return;
        }

        $labels = ['dni' => 'DNI', 'nie' => 'NIE', 'passport' => 'Pasaporte'];
        $type_label = isset($labels[$type]) ? $labels[$type] : $type;
        ?>
        <p class="form-field form-field-wide">
            <strong><?php esc_html_e('Documento de identidad:', 'alquipress'); ?></strong><br>
            <?php echo esc_html($type_label . ($number ? ' ' . $number : '')); ?>
        </p>
        <?php
    }

    public function add_document_to_formatted_address($address, $order)
    {
        if (!$order || !is_a($order, 'WC_Order')) {
            return $address;
        }
        $type = $order->get_meta(self::META_DOC_TYPE);
        $number = $order->get_meta(self::META_DOC_NUMBER);
        if ($type || $number) {
            $labels = ['dni' => 'DNI', 'nie' => 'NIE', 'passport' => 'Pasaporte'];
            $type_label = isset($labels[$type]) ? $labels[$type] : $type;
            $address['document'] = $type_label . ($number ? ' ' . $number : '');
        }
        return $address;
    }

    /**
     * Obtener documento de un pedido (para uso en ficha de cliente)
     */
    public static function get_order_document($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return null;
        }
        $type = $order->get_meta(self::META_DOC_TYPE);
        $number = $order->get_meta(self::META_DOC_NUMBER);
        if (!$number) {
            return null;
        }
        $labels = ['dni' => 'DNI', 'nie' => 'NIE', 'passport' => 'Pasaporte'];
        $type_label = isset($labels[$type]) ? $labels[$type] : ($type ?: '');
        return trim($type_label . ' ' . $number);
    }
}

Alquipress_Checkout_Document_Fields::get_instance();
