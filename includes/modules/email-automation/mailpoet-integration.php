<?php
/**
 * Phase 6: MailPoet Integration Module
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_MailPoet_Integration
{

    public function __construct()
    {
        // Enlace entre WooCommerce y MailPoet suele ser nativo si se activa el plugin de suscripción en checkout de MailPoet.
        // Aquí añadiremos lógica personalizada si es necesario.
        add_action('woocommerce_order_status_processing', [$this, 'sync_guest_to_mailpoet'], 10, 1);
    }

    /**
     * Sincronizar huésped con MailPoet al confirmar pedido
     */
    public function sync_guest_to_mailpoet($order_id)
    {
        if (!class_exists('\MailPoet\API\API'))
            return;

        $order = wc_get_order($order_id);
        $email = $order->get_billing_email();
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();

        try {
            $mailpoet_api = \MailPoet\API\API::MP('v1');
            $list_id = $this->get_list_id('Clientes Compradores');

            if ($list_id) {
                $mailpoet_api->addSubscriber([
                    'email' => $email,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                ], [$list_id]);
            }
        } catch (\Exception $e) {
            // Log error if needed
            if (function_exists('aq_log')) {
                aq_log($e->getMessage(), 'MailPoet Sync Error');
            }
        }
    }

    private function get_list_id($name)
    {
        if (!class_exists('\MailPoet\API\API'))
            return false;

        try {
            $mailpoet_api = \MailPoet\API\API::MP('v1');
            $lists = $mailpoet_api->getLists();
            foreach ($lists as $list) {
                if ($list['name'] === $name) {
                    return $list['id'];
                }
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }
}

new Alquipress_MailPoet_Integration();
