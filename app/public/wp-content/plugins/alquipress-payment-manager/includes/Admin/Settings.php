<?php
/**
 * Panel de configuración global en WooCommerce
 *
 * @package ALQUIPRESS\PaymentManager\Admin
 */

namespace ALQUIPRESS\PaymentManager\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings
 * Añade una sección de configuración en WooCommerce > Ajustes > Productos
 */
class Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_filter('woocommerce_get_sections_products', [$this, 'add_section']);
        add_filter('woocommerce_get_settings_products', [$this, 'add_settings'], 10, 2);
    }

    /**
     * Añadir sección en WooCommerce > Ajustes > Productos
     *
     * @param array $sections Secciones existentes
     * @return array
     */
    public function add_section($sections) {
        $sections['alquipress_payments'] = __('ALQUIPRESS Payments', 'apm');
        return $sections;
    }

    /**
     * Añadir campos de configuración
     *
     * @param array  $settings Ajustes existentes
     * @param string $section  Sección actual
     * @return array
     */
    public function add_settings($settings, $section) {
        if ($section !== 'alquipress_payments') {
            return $settings;
        }

        $custom_settings = [];

        // Título principal
        $custom_settings[] = [
            'title' => __('Configuración de Pagos Escalonados', 'apm'),
            'desc'  => __('Configura cómo se gestionan los depósitos, pagos programados y fianzas para las reservas.', 'apm'),
            'type'  => 'title',
            'id'    => 'apm_payment_settings',
        ];

        // Depósito inicial
        $custom_settings[] = [
            'title'             => __('Depósito Inicial (%)', 'apm'),
            'desc'              => __('Porcentaje del total que se cobra en el checkout como depósito inicial.', 'apm'),
            'id'                => 'apm_default_deposit_percent',
            'type'              => 'number',
            'default'           => '40',
            'css'               => 'width: 80px;',
            'custom_attributes' => [
                'min'  => '1',
                'max'  => '100',
                'step' => '1',
            ],
            'desc_tip'          => true,
        ];

        // Días antes del check-in para el segundo pago
        $custom_settings[] = [
            'title'             => __('Días antes para 2º pago', 'apm'),
            'desc'              => __('Número de días antes del check-in para cobrar automáticamente el saldo restante.', 'apm'),
            'id'                => 'apm_default_days_before',
            'type'              => 'number',
            'default'           => '7',
            'css'               => 'width: 80px;',
            'custom_attributes' => [
                'min'  => '1',
                'max'  => '60',
                'step' => '1',
            ],
            'desc_tip'          => true,
        ];

        // Fianza por defecto
        $custom_settings[] = [
            'title'             => __('Fianza por defecto (€)', 'apm'),
            'desc'              => __('Importe de la fianza que se retiene (pre-autorización) en la tarjeta del cliente.', 'apm'),
            'id'                => 'apm_default_security_deposit',
            'type'              => 'number',
            'default'           => '300',
            'css'               => 'width: 100px;',
            'custom_attributes' => [
                'min'  => '0',
                'step' => '50',
            ],
            'desc_tip'          => true,
        ];

        $custom_settings[] = [
            'type' => 'sectionend',
            'id'   => 'apm_payment_settings',
        ];

        // Sección: Métodos de pago
        $custom_settings[] = [
            'title' => __('Métodos de Pago Permitidos', 'apm'),
            'desc'  => __('Define qué métodos de pago están disponibles para cada tipo de cobro.', 'apm'),
            'type'  => 'title',
            'id'    => 'apm_payment_methods_settings',
        ];

        // Métodos para el segundo pago
        $custom_settings[] = [
            'title'    => __('Métodos para 2º Pago', 'apm'),
            'desc'     => __('Métodos de pago disponibles para el saldo restante.', 'apm'),
            'id'       => 'apm_second_payment_methods',
            'type'     => 'multiselect',
            'class'    => 'wc-enhanced-select',
            'css'      => 'width: 400px;',
            'options'  => [
                'stripe'   => __('Tarjeta (Cobro automático)', 'apm'),
                'cash'     => __('Efectivo (En check-in)', 'apm'),
                'transfer' => __('Transferencia bancaria', 'apm'),
            ],
            'default'  => ['stripe'],
            'desc_tip' => true,
        ];

        // Métodos para la fianza
        $custom_settings[] = [
            'title'    => __('Métodos para Fianza', 'apm'),
            'desc'     => __('Métodos disponibles para la retención de fianza.', 'apm'),
            'id'       => 'apm_security_methods',
            'type'     => 'multiselect',
            'class'    => 'wc-enhanced-select',
            'css'      => 'width: 400px;',
            'options'  => [
                'stripe_hold' => __('Retención en tarjeta (Pre-autorización)', 'apm'),
                'cash'        => __('Efectivo (En check-in)', 'apm'),
            ],
            'default'  => ['stripe_hold'],
            'desc_tip' => true,
        ];

        $custom_settings[] = [
            'type' => 'sectionend',
            'id'   => 'apm_payment_methods_settings',
        ];

        // Sección: Comportamiento
        $custom_settings[] = [
            'title' => __('Comportamiento', 'apm'),
            'type'  => 'title',
            'id'    => 'apm_behavior_settings',
        ];

        // Permitir pago completo
        $custom_settings[] = [
            'title'   => __('Permitir pago completo', 'apm'),
            'desc'    => __('Mostrar opción para que el cliente pague el total en el checkout (sin pagos escalonados).', 'apm'),
            'id'      => 'apm_allow_full_payment',
            'type'    => 'checkbox',
            'default' => 'yes',
        ];

        // Mostrar desglose en checkout
        $custom_settings[] = [
            'title'   => __('Mostrar desglose en checkout', 'apm'),
            'desc'    => __('Mostrar tabla con el desglose de pagos (depósito, saldo, fianza) antes de confirmar.', 'apm'),
            'id'      => 'apm_show_breakdown',
            'type'    => 'checkbox',
            'default' => 'yes',
        ];

        // Días mínimos de antelación
        $custom_settings[] = [
            'title'             => __('Antelación mínima (días)', 'apm'),
            'desc'              => __('Si la reserva es para menos de estos días, se cobra el 100% inmediatamente (sin pagos escalonados).', 'apm'),
            'id'                => 'apm_min_days_for_deposit',
            'type'              => 'number',
            'default'           => '14',
            'css'               => 'width: 80px;',
            'custom_attributes' => [
                'min'  => '0',
                'step' => '1',
            ],
            'desc_tip'          => true,
        ];

        $custom_settings[] = [
            'type' => 'sectionend',
            'id'   => 'apm_behavior_settings',
        ];

        // Sección: Notificaciones
        $custom_settings[] = [
            'title' => __('Notificaciones', 'apm'),
            'type'  => 'title',
            'id'    => 'apm_notification_settings',
        ];

        // Email de recordatorio
        $custom_settings[] = [
            'title'             => __('Recordatorio antes del 2º pago (días)', 'apm'),
            'desc'              => __('Días de antelación para enviar email recordando el próximo cobro. 0 = no enviar.', 'apm'),
            'id'                => 'apm_reminder_days',
            'type'              => 'number',
            'default'           => '3',
            'css'               => 'width: 80px;',
            'custom_attributes' => [
                'min'  => '0',
                'step' => '1',
            ],
            'desc_tip'          => true,
        ];

        // Notificar al admin en fallo de pago
        $custom_settings[] = [
            'title'   => __('Notificar fallo de pago', 'apm'),
            'desc'    => __('Enviar email al administrador cuando falle un cobro automático.', 'apm'),
            'id'      => 'apm_notify_admin_on_failure',
            'type'    => 'checkbox',
            'default' => 'yes',
        ];

        $custom_settings[] = [
            'type' => 'sectionend',
            'id'   => 'apm_notification_settings',
        ];

        // Sección: Stripe
        $custom_settings[] = [
            'title' => __('Configuración Stripe', 'apm'),
            'desc'  => sprintf(
                __('Para usar las funciones de pre-autorización y cobros automáticos, necesitas tener %s instalado y configurado.', 'apm'),
                '<a href="https://woocommerce.com/products/stripe/" target="_blank">WooCommerce Stripe Payment Gateway</a>'
            ),
            'type'  => 'title',
            'id'    => 'apm_stripe_settings',
        ];

        // Indicador de estado de Stripe
        $stripe_status = $this->get_stripe_status();
        $custom_settings[] = [
            'title' => __('Estado de Stripe', 'apm'),
            'type'  => 'info',
            'text'  => $stripe_status['html'],
            'id'    => 'apm_stripe_status_info',
        ];

        // Días para expirar pre-autorización
        $custom_settings[] = [
            'title'             => __('Expiración de fianza (días)', 'apm'),
            'desc'              => __('Días máximos que se mantiene la pre-autorización de fianza. Stripe permite hasta 7 días.', 'apm'),
            'id'                => 'apm_security_hold_days',
            'type'              => 'number',
            'default'           => '7',
            'css'               => 'width: 80px;',
            'custom_attributes' => [
                'min'  => '1',
                'max'  => '7',
                'step' => '1',
            ],
            'desc_tip'          => true,
        ];

        $custom_settings[] = [
            'type' => 'sectionend',
            'id'   => 'apm_stripe_settings',
        ];

        return $custom_settings;
    }

    /**
     * Verificar estado de Stripe
     *
     * @return array
     */
    private function get_stripe_status() {
        $stripe_enabled = false;
        $stripe_testmode = false;

        // Verificar si el plugin de Stripe está activo
        if (class_exists('WC_Stripe') || class_exists('WC_Gateway_Stripe')) {
            $stripe_settings = get_option('woocommerce_stripe_settings', []);
            $stripe_enabled = isset($stripe_settings['enabled']) && $stripe_settings['enabled'] === 'yes';
            $stripe_testmode = isset($stripe_settings['testmode']) && $stripe_settings['testmode'] === 'yes';
        }

        if (!$stripe_enabled) {
            return [
                'ok'   => false,
                'html' => '<span style="color: #dc3232;">❌ ' . __('Stripe no está configurado o habilitado.', 'apm') . '</span>',
            ];
        }

        $mode_text = $stripe_testmode
            ? '<span style="color: #f39c12;">⚠️ ' . __('Modo TEST', 'apm') . '</span>'
            : '<span style="color: #27ae60;">✅ ' . __('Modo LIVE', 'apm') . '</span>';

        return [
            'ok'   => true,
            'html' => '<span style="color: #27ae60;">✅ ' . __('Stripe configurado correctamente', 'apm') . '</span> - ' . $mode_text,
        ];
    }
}

/**
 * Campo personalizado tipo "info" para mostrar texto informativo
 */
add_action('woocommerce_admin_field_info', function($value) {
    ?>
    <tr valign="top">
        <th scope="row" class="titledesc">
            <label><?php echo esc_html($value['title']); ?></label>
        </th>
        <td class="forminp forminp-info">
            <?php echo wp_kses_post($value['text']); ?>
        </td>
    </tr>
    <?php
});
