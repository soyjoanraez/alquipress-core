<?php
/**
 * Módulo: Campos de precios por propiedad
 * Limpieza, lavandería y comisión por propiedad.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Property_Pricing_Fields
{
    public function __construct()
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_pricing_fields']);
        add_action('woocommerce_process_product_meta', [$this, 'save_pricing_fields']);
    }

    public function add_pricing_fields()
    {
        global $post;
        $product_id = $post->ID;

        echo '<div class="options_group">';
        woocommerce_wp_text_input([
            'id'                => '_cleaning_fee',
            'label'             => __('Precio limpieza (€)', 'alquipress'),
            'description'       => __('Coste por limpieza de la propiedad por reserva.', 'alquipress'),
            'desc_tip'          => true,
            'type'              => 'number',
            'value'             => get_post_meta($product_id, '_cleaning_fee', true),
            'custom_attributes' => ['min' => '0', 'step' => '0.01'],
        ]);
        woocommerce_wp_text_input([
            'id'                => '_laundry_fee',
            'label'             => __('Precio lavandería (€)', 'alquipress'),
            'description'       => __('Coste de lavandería por reserva (sábanas, toallas, etc.).', 'alquipress'),
            'desc_tip'          => true,
            'type'              => 'number',
            'value'             => get_post_meta($product_id, '_laundry_fee', true),
            'custom_attributes' => ['min' => '0', 'step' => '0.01'],
        ]);
        woocommerce_wp_text_input([
            'id'                => 'property_commission_rate',
            'label'             => __('% Comisión agencia', 'alquipress'),
            'description'       => __('Porcentaje que se lleva la agencia por reserva. Deja vacío para usar el % del propietario.', 'alquipress'),
            'desc_tip'          => true,
            'type'              => 'number',
            'value'             => get_post_meta($product_id, 'property_commission_rate', true),
            'custom_attributes' => ['min' => '0', 'max' => '100', 'step' => '0.5'],
        ]);
        echo '</div>';
    }

    public function save_pricing_fields($post_id)
    {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $cleaning = isset($_POST['_cleaning_fee']) ? floatval($_POST['_cleaning_fee']) : 0;
        $laundry = isset($_POST['_laundry_fee']) ? floatval($_POST['_laundry_fee']) : 0;
        $commission = isset($_POST['property_commission_rate']) ? sanitize_text_field(wp_unslash($_POST['property_commission_rate'])) : '';

        update_post_meta($post_id, '_cleaning_fee', $cleaning);
        update_post_meta($post_id, '_laundry_fee', $laundry);
        if ($commission !== '') {
            update_post_meta($post_id, 'property_commission_rate', floatval($commission));
        } else {
            delete_post_meta($post_id, 'property_commission_rate');
        }
    }
}

new Alquipress_Property_Pricing_Fields();
