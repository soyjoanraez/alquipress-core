<?php
/**
 * Gestión de pre-autorizaciones de Stripe para fianzas
 *
 * @package ALQUIPRESS\PaymentManager
 */

namespace ALQUIPRESS\PaymentManager\Payment;

defined('ABSPATH') || exit;

/**
 * Clase StripePreauth
 * 
 * Gestiona las pre-autorizaciones de tarjetas para retener fianzas
 * utilizando Stripe Payment Intents con capture_method=manual
 */
class StripePreauth {

    /**
     * Instancia de Stripe API
     */
    private $stripe_client = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_stripe_client();
        $this->init_hooks();
    }

    /**
     * Inicializar cliente de Stripe
     */
    private function init_stripe_client() {
        if (!class_exists('\Stripe\StripeClient')) {
            return;
        }

        $stripe_settings = get_option('woocommerce_stripe_settings', []);
        $secret_key = $stripe_settings['testmode'] === 'yes' 
            ? ($stripe_settings['test_secret_key'] ?? '')
            : ($stripe_settings['secret_key'] ?? '');

        if (empty($secret_key)) {
            error_log('APM: API key de Stripe no configurada');
            return;
        }

        try {
            $this->stripe_client = new \Stripe\StripeClient($secret_key);
        } catch (\Exception $e) {
            error_log('APM: Error al inicializar Stripe client: ' . $e->getMessage());
        }
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Capturar fianza cuando se marca el pedido como completado
        add_action('woocommerce_order_status_completed', [$this, 'handle_order_completed'], 10, 2);
        
        // Liberar fianza cuando se cancela el pedido
        add_action('woocommerce_order_status_cancelled', [$this, 'handle_order_cancelled'], 10, 2);
        
        // AJAX para capturar fianza manualmente
        add_action('wp_ajax_apm_capture_security', [$this, 'ajax_capture_security']);
        
        // AJAX para liberar fianza manualmente
        add_action('wp_ajax_apm_release_security', [$this, 'ajax_release_security']);
    }

    /**
     * Crear pre-autorización de fianza
     *
     * @param int $order_id ID del pedido
     * @param float $amount Importe de la fianza
     * @param string $payment_method_id ID del método de pago de Stripe
     * @return array|\WP_Error
     */
    public function create_preauthorization($order_id, $amount, $payment_method_id) {
        if (!$this->stripe_client) {
            return new \WP_Error('stripe_not_configured', 'Stripe no está configurado');
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return new \WP_Error('invalid_order', 'Pedido no encontrado');
        }

        try {
            // Obtener o crear cliente de Stripe
            $customer_id = $this->get_or_create_stripe_customer($order);
            if (is_wp_error($customer_id)) {
                return $customer_id;
            }

            // Crear Payment Intent con capture_method=manual
            $intent = $this->stripe_client->paymentIntents->create([
                'amount' => $amount * 100, // Convertir a centavos
                'currency' => strtolower($order->get_currency()),
                'customer' => $customer_id,
                'payment_method' => $payment_method_id,
                'capture_method' => 'manual',
                'confirmation_method' => 'manual',
                'description' => sprintf(
                    'Fianza reserva #%s - %s',
                    $order->get_order_number(),
                    get_bloginfo('name')
                ),
                'metadata' => [
                    'order_id' => $order_id,
                    'order_number' => $order->get_order_number(),
                    'type' => 'security_deposit'
                ]
            ]);

            // Guardar ID del intent en el pedido
            $order->update_meta_data('_apm_security_preauth_id', $intent->id);
            $order->update_meta_data('_apm_security_preauth_amount', $amount);
            $order->save();

            return [
                'success' => true,
                'intent_id' => $intent->id,
                'client_secret' => $intent->client_secret,
                'amount' => $amount
            ];

        } catch (\Exception $e) {
            error_log("APM: Error al crear pre-autorización: " . $e->getMessage());
            return new \WP_Error('stripe_error', 'Error al crear pre-autorización: ' . $e->getMessage());
        }
    }

    /**
     * Capturar pre-autorización (cobrar la fianza)
     *
     * @param int $order_id ID del pedido
     * @param string|null $reason Motivo de la captura
     * @return array|\WP_Error
     */
    public function capture_preauthorization($order_id, $reason = null) {
        if (!$this->stripe_client) {
            return new \WP_Error('stripe_not_configured', 'Stripe no está configurado');
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return new \WP_Error('invalid_order', 'Pedido no encontrado');
        }

        $preauth_id = $order->get_meta('_apm_security_preauth_id');
        if (!$preauth_id) {
            return new \WP_Error('no_preauth', 'No hay pre-autorización para este pedido');
        }

        try {
            // Verificar estado del Payment Intent
            $intent = $this->stripe_client->paymentIntents->retrieve($preauth_id);
            
            if ($intent->status !== 'requires_capture') {
                return new \WP_Error('invalid_status', 
                    sprintf('La pre-autorización no puede ser capturada. Estado actual: %s', $intent->status)
                );
            }

            // Capturar el pago
            $capture = $this->stripe_client->paymentIntents->capture($preauth_id, [
                'statement_descriptor' => sprintf('Fianza #%s', $order->get_order_number())
            ]);

            // Actualizar estado del pedido
            $order->update_meta_data('_apm_security_captured', 'yes');
            $order->update_meta_data('_apm_security_captured_date', current_time('mysql'));
            $order->update_meta_data('_apm_security_capture_reason', $reason);
            
            // Añadir nota al pedido
            $note = sprintf(
                'Fianza de %s capturada correctamente (Stripe Intent: %s)',
                wc_price($intent->amount / 100),
                $preauth_id
            );
            if ($reason) {
                $note .= sprintf('. Motivo: %s', $reason);
            }
            $order->add_order_note($note);

            $order->save();

            // Disparar acción para otros procesos
            do_action('apm_security_deposit_captured', $order, $capture);

            return [
                'success' => true,
                'capture_id' => $capture->id,
                'amount' => $capture->amount / 100
            ];

        } catch (\Exception $e) {
            error_log("APM: Error al capturar pre-autorización: " . $e->getMessage());
            return new \WP_Error('stripe_error', 'Error al capturar pre-autorización: ' . $e->getMessage());
        }
    }

    /**
     * Liberar pre-autorización (devolver la retención)
     *
     * @param int $order_id ID del pedido
     * @param string|null $reason Motivo de la liberación
     * @return array|\WP_Error
     */
    public function release_preauthorization($order_id, $reason = null) {
        if (!$this->stripe_client) {
            return new \WP_Error('stripe_not_configured', 'Stripe no está configurado');
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return new \WP_Error('invalid_order', 'Pedido no encontrado');
        }

        $preauth_id = $order->get_meta('_apm_security_preauth_id');
        if (!$preauth_id) {
            return new \WP_Error('no_preauth', 'No hay pre-autorización para este pedido');
        }

        try {
            // Cancelar el Payment Intent (esto libera la retención)
            $intent = $this->stripe_client->paymentIntents->cancel($preauth_id);

            // Actualizar estado del pedido
            $order->update_meta_data('_apm_security_released', 'yes');
            $order->update_meta_data('_apm_security_released_date', current_time('mysql'));
            $order->update_meta_data('_apm_security_release_reason', $reason);

            // Añadir nota al pedido
            $note = sprintf(
                'Fianza de %s liberada correctamente (Stripe Intent: %s)',
                wc_price($order->get_meta('_apm_security_preauth_amount')),
                $preauth_id
            );
            if ($reason) {
                $note .= sprintf('. Motivo: %s', $reason);
            }
            $order->add_order_note($note);

            $order->save();

            // Disparar acción para otros procesos
            do_action('apm_security_deposit_released', $order, $intent);

            return [
                'success' => true,
                'intent_id' => $intent->id,
                'status' => $intent->status
            ];

        } catch (\Exception $e) {
            error_log("APM: Error al liberar pre-autorización: " . $e->getMessage());
            return new \WP_Error('stripe_error', 'Error al liberar pre-autorización: ' . $e->getMessage());
        }
    }

    /**
     * Obtener o crear cliente de Stripe
     *
     * @param \WC_Order $order
     * @return string|\WP_Error
     */
    private function get_or_create_stripe_customer($order) {
        // Verificar si ya tiene un cliente de Stripe guardado
        $customer_id = $order->get_meta('_stripe_customer_id');
        if (!empty($customer_id)) {
            return $customer_id;
        }

        try {
            // Crear nuevo cliente
            $customer = $this->stripe_client->customers->create([
                'email' => $order->get_billing_email(),
                'name' => trim($order->get_formatted_billing_full_name()),
                'address' => [
                    'line1' => $order->get_billing_address_1(),
                    'line2' => $order->get_billing_address_2(),
                    'city' => $order->get_billing_city(),
                    'state' => $order->get_billing_state(),
                    'postal_code' => $order->get_billing_postcode(),
                    'country' => $order->get_billing_country(),
                ],
                'metadata' => [
                    'order_id' => $order->get_id(),
                    'order_number' => $order->get_order_number()
                ]
            ]);

            // Guardar ID del cliente
            $order->update_meta_data('_stripe_customer_id', $customer->id);
            $order->save();

            return $customer->id;

        } catch (\Exception $e) {
            error_log("APM: Error al crear cliente Stripe: " . $e->getMessage());
            return new \WP_Error('stripe_error', 'Error al crear cliente: ' . $e->getMessage());
        }
    }

    /**
     * Manejar completado de pedido (capturar fianza automáticamente)
     *
     * @param int $order_id
     * @param \WC_Order $order
     */
    public function handle_order_completed($order_id, $order) {
        $preauth_id = $order->get_meta('_apm_security_preauth_id');
        $auto_capture = get_option('apm_auto_capture_security', 'yes');

        if ($preauth_id && $auto_capture === 'yes') {
            $this->capture_preauthorization($order_id, 'Captura automática al completar pedido');
        }
    }

    /**
     * Manejar cancelación de pedido (liberar fianza automáticamente)
     *
     * @param int $order_id
     * @param \WC_Order $order
     */
    public function handle_order_cancelled($order_id, $order) {
        $preauth_id = $order->get_meta('_apm_security_preauth_id');
        $auto_release = get_option('apm_auto_release_security', 'yes');

        if ($preauth_id && $auto_release === 'yes') {
            $this->release_preauthorization($order_id, 'Liberación automática al cancelar pedido');
        }
    }

    /**
     * AJAX: Capturar fianza manualmente
     */
    public function ajax_capture_security() {
        check_ajax_referer('apm_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Sin permisos');
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        $reason = sanitize_text_field($_POST['reason'] ?? 'Captura manual por administrador');

        if (!$order_id) {
            wp_send_json_error('ID de pedido inválido');
        }

        $result = $this->capture_preauthorization($order_id, $reason);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success('Fianza capturada correctamente');
    }

    /**
     * AJAX: Liberar fianza manualmente
     */
    public function ajax_release_security() {
        check_ajax_referer('apm_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Sin permisos');
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        $reason = sanitize_text_field($_POST['reason'] ?? 'Liberación manual por administrador');

        if (!$order_id) {
            wp_send_json_error('ID de pedido inválido');
        }

        $result = $this->release_preauthorization($order_id, $reason);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success('Fianza liberada correctamente');
    }

    /**
     * Obtener estado de la pre-autorización
     *
     * @param int $order_id
     * @return array|null
     */
    public function get_preauth_status($order_id) {
        if (!$this->stripe_client) {
            return null;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return null;
        }

        $preauth_id = $order->get_meta('_apm_security_preauth_id');
        if (!$preauth_id) {
            return null;
        }

        try {
            $intent = $this->stripe_client->paymentIntents->retrieve($preauth_id);
            
            return [
                'intent_id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount / 100,
                'currency' => $intent->currency,
                'created' => $intent->created,
                'captured' => $order->get_meta('_apm_security_captured') === 'yes',
                'released' => $order->get_meta('_apm_security_released') === 'yes',
                'capture_date' => $order->get_meta('_apm_security_captured_date'),
                'release_date' => $order->get_meta('_apm_security_released_date'),
            ];
        } catch (\Exception $e) {
            error_log("APM: Error al obtener estado de pre-autorización: " . $e->getMessage());
            return null;
        }
    }
}