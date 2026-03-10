<?php
/**
 * Metabox de configuración por producto
 *
 * @package ALQUIPRESS\PaymentManager\Admin
 */

namespace ALQUIPRESS\PaymentManager\Admin;

defined('ABSPATH') || exit;

/**
 * Class ProductMetabox
 * Añade campos de configuración de pagos en la edición de productos
 */
class ProductMetabox {

    /**
     * Constructor
     */
    public function __construct() {
        // Campos en la pestaña General del producto
        add_action('woocommerce_product_options_general_product_data', [$this, 'render_fields']);
        add_action('woocommerce_process_product_meta', [$this, 'save_fields']);

        // Tab personalizada (alternativa más visible)
        add_filter('woocommerce_product_data_tabs', [$this, 'add_product_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'render_tab_content']);
    }

    /**
     * Añadir pestaña personalizada en el editor de producto
     *
     * @param array $tabs Pestañas existentes
     * @return array
     */
    public function add_product_tab($tabs) {
        $tabs['apm_payments'] = [
            'label'    => __('Pagos ALQUIPRESS', 'apm'),
            'target'   => 'apm_payments_data',
            'class'    => ['show_if_booking'],
            'priority' => 65,
        ];
        return $tabs;
    }

    /**
     * Renderizar contenido de la pestaña
     */
    public function render_tab_content() {
        global $post;
        $product_id = $post->ID;

        // Obtener valores guardados
        $override = get_post_meta($product_id, '_apm_override_global', true);
        $deposit_percent = get_post_meta($product_id, '_apm_deposit_percent', true);
        $days_before = get_post_meta($product_id, '_apm_days_before', true);
        $security_deposit = get_post_meta($product_id, '_apm_security_deposit', true);
        $second_payment_methods = get_post_meta($product_id, '_apm_second_payment_methods', true);
        $security_methods = get_post_meta($product_id, '_apm_security_methods', true);

        // Valores por defecto globales
        $global_deposit = get_option('apm_default_deposit_percent', 40);
        $global_days = get_option('apm_default_days_before', 7);
        $global_security = get_option('apm_default_security_deposit', 300);
        ?>
        <div id="apm_payments_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <p class="form-field">
                    <span class="description" style="margin-left: 0;">
                        <?php printf(
                            __('Configuración global actual: %s%% depósito, %s días antes, %s€ fianza. %s', 'apm'),
                            '<strong>' . esc_html($global_deposit) . '</strong>',
                            '<strong>' . esc_html($global_days) . '</strong>',
                            '<strong>' . esc_html($global_security) . '</strong>',
                            '<a href="' . admin_url('admin.php?page=wc-settings&tab=products&section=alquipress_payments') . '">' . __('Cambiar', 'apm') . '</a>'
                        ); ?>
                    </span>
                </p>

                <?php
                woocommerce_wp_checkbox([
                    'id'          => '_apm_override_global',
                    'label'       => __('Personalizar pagos', 'apm'),
                    'description' => __('Usar configuración específica para este producto en lugar de la global.', 'apm'),
                    'value'       => $override,
                ]);
                ?>
            </div>

            <div class="options_group apm-custom-fields" style="<?php echo $override !== 'yes' ? 'display:none;' : ''; ?>">
                <?php
                woocommerce_wp_text_input([
                    'id'                => '_apm_deposit_percent',
                    'label'             => __('Depósito inicial (%)', 'apm'),
                    'description'       => __('Porcentaje a cobrar en el checkout.', 'apm'),
                    'desc_tip'          => true,
                    'type'              => 'number',
                    'value'             => $deposit_percent ?: $global_deposit,
                    'custom_attributes' => [
                        'min'  => '1',
                        'max'  => '100',
                        'step' => '1',
                    ],
                ]);

                woocommerce_wp_text_input([
                    'id'                => '_apm_days_before',
                    'label'             => __('Días antes 2º pago', 'apm'),
                    'description'       => __('Días antes del check-in para cobrar el saldo.', 'apm'),
                    'desc_tip'          => true,
                    'type'              => 'number',
                    'value'             => $days_before ?: $global_days,
                    'custom_attributes' => [
                        'min'  => '1',
                        'max'  => '60',
                        'step' => '1',
                    ],
                ]);

                woocommerce_wp_text_input([
                    'id'                => '_apm_security_deposit',
                    'label'             => __('Fianza (€)', 'apm'),
                    'description'       => __('Importe de la fianza a retener.', 'apm'),
                    'desc_tip'          => true,
                    'type'              => 'number',
                    'value'             => $security_deposit ?: $global_security,
                    'custom_attributes' => [
                        'min'  => '0',
                        'step' => '50',
                    ],
                ]);
                ?>
            </div>

            <div class="options_group apm-custom-fields" style="<?php echo $override !== 'yes' ? 'display:none;' : ''; ?>">
                <p class="form-field">
                    <label for="_apm_second_payment_methods"><?php _e('Métodos 2º pago', 'apm'); ?></label>
                    <select id="_apm_second_payment_methods" name="_apm_second_payment_methods[]" class="wc-enhanced-select" multiple="multiple" style="width: 50%;">
                        <?php
                        $methods = [
                            'stripe'   => __('Tarjeta (Automático)', 'apm'),
                            'cash'     => __('Efectivo', 'apm'),
                            'transfer' => __('Transferencia', 'apm'),
                        ];
                        $selected = is_array($second_payment_methods) ? $second_payment_methods : ['stripe'];
                        foreach ($methods as $value => $label) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($value),
                                in_array($value, $selected) ? 'selected' : '',
                                esc_html($label)
                            );
                        }
                        ?>
                    </select>
                </p>

                <p class="form-field">
                    <label for="_apm_security_methods"><?php _e('Métodos fianza', 'apm'); ?></label>
                    <select id="_apm_security_methods" name="_apm_security_methods[]" class="wc-enhanced-select" multiple="multiple" style="width: 50%;">
                        <?php
                        $methods = [
                            'stripe_hold' => __('Retención tarjeta', 'apm'),
                            'cash'        => __('Efectivo', 'apm'),
                        ];
                        $selected = is_array($security_methods) ? $security_methods : ['stripe_hold'];
                        foreach ($methods as $value => $label) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($value),
                                in_array($value, $selected) ? 'selected' : '',
                                esc_html($label)
                            );
                        }
                        ?>
                    </select>
                </p>
            </div>

            <div class="options_group apm-custom-fields" style="<?php echo $override !== 'yes' ? 'display:none;' : ''; ?>">
                <?php
                woocommerce_wp_checkbox([
                    'id'          => '_apm_disable_deposits',
                    'label'       => __('Desactivar pagos escalonados', 'apm'),
                    'description' => __('Este producto siempre se cobra al 100% en el checkout.', 'apm'),
                    'value'       => get_post_meta($product_id, '_apm_disable_deposits', true),
                ]);
                ?>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#_apm_override_global').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('.apm-custom-fields').slideDown();
                    } else {
                        $('.apm-custom-fields').slideUp();
                    }
                });

                // Inicializar enhanced select si está disponible
                if ($.fn.select2) {
                    $('#_apm_second_payment_methods, #_apm_security_methods').select2();
                }
            });
        </script>
        <?php
    }

    /**
     * Renderizar campos en la pestaña General (alternativa)
     * Mantenemos esto por si se prefiere tener los campos en General
     */
    public function render_fields() {
        // Los campos ahora están en la pestaña personalizada
        // Este método queda vacío pero lo mantenemos por compatibilidad
    }

    /**
     * Guardar campos del producto
     *
     * @param int $post_id ID del producto
     */
    public function save_fields($post_id) {
        // Verificar nonce y permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Checkbox de override
        $override = isset($_POST['_apm_override_global']) ? 'yes' : 'no';
        update_post_meta($post_id, '_apm_override_global', $override);

        // Solo guardar el resto si override está activo
        if ($override === 'yes') {
            // Campos numéricos
            $numeric_fields = [
                '_apm_deposit_percent',
                '_apm_days_before',
                '_apm_security_deposit',
            ];

            foreach ($numeric_fields as $field) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, $field, absint($_POST[$field]));
                }
            }

            // Campos de selección múltiple
            $multiselect_fields = [
                '_apm_second_payment_methods',
                '_apm_security_methods',
            ];

            foreach ($multiselect_fields as $field) {
                if (isset($_POST[$field]) && is_array($_POST[$field])) {
                    $sanitized = array_map('sanitize_text_field', $_POST[$field]);
                    update_post_meta($post_id, $field, $sanitized);
                } else {
                    delete_post_meta($post_id, $field);
                }
            }

            // Checkbox de desactivar depósitos
            $disable = isset($_POST['_apm_disable_deposits']) ? 'yes' : 'no';
            update_post_meta($post_id, '_apm_disable_deposits', $disable);
        }
    }
}
