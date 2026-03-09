<?php
/**
 * MailPoet Integration: suscripción por zona/preferencias y baja.
 */

if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . '/class-email-flows.php';

class Alquipress_MailPoet_Integration
{
    public function __construct()
    {
        add_action('woocommerce_order_status_changed', [$this, 'subscribe_on_booking'], 10, 4);
        add_action('init', [$this, 'register_unsubscribe_endpoint']);
        add_action('template_redirect', [$this, 'handle_unsubscribe']);
    }

    /**
     * Suscribir a listas MailPoet al confirmar reserva: clientes-compradores + zona + preferencias.
     */
    public function subscribe_on_booking($order_id, $old_status, $new_status, $order = null)
    {
        $active_statuses = ['processing', 'deposito-ok', 'completed'];
        if (!in_array($new_status, $active_statuses, true)) {
            return;
        }
        if (get_post_meta($order_id, '_alquipress_mailpoet_subscribed', true)) {
            return;
        }
        if (!class_exists('\MailPoet\API\API')) {
            return;
        }
        if (!$order || !is_object($order)) {
            $order = wc_get_order($order_id);
        }
        if (!$order) {
            return;
        }

        $list_slugs = ['clientes-compradores'];
        $product_id = (int) $order->get_meta('_booking_product_id');
        if (!$product_id && $order->get_items()) {
            foreach ($order->get_items() as $item) {
                $product = is_object($item) && method_exists($item, 'get_product') ? $item->get_product() : null;
                if ($product) {
                    $product_id = $product->get_id();
                    break;
                }
            }
        }
        if ($product_id) {
            $terms = get_the_terms($product_id, 'poblacion');
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $slug = 'zona-' . $term->slug;
                    if (in_array($slug, ['zona-denia', 'zona-javea', 'zona-calpe'], true)) {
                        $list_slugs[] = $slug;
                    }
                }
            }
        }
        $customer_id = $order->get_customer_id();
        if ($customer_id) {
            $prefs = get_user_meta($customer_id, 'guest_preferences', true);
            if (is_array($prefs)) {
                if (in_array('mascotas', $prefs, true)) {
                    $list_slugs[] = 'mascotas';
                }
                if (in_array('familia', $prefs, true)) {
                    $list_slugs[] = 'familias';
                }
            }
        }

        $list_ids = $this->get_list_ids_by_slugs($list_slugs);
        if (empty($list_ids)) {
            return;
        }

        try {
            \MailPoet\API\API::MP('v1')->addSubscriber([
                'email' => $order->get_billing_email(),
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'status' => 'subscribed',
            ], $list_ids, ['send_confirmation_email' => false]);
            update_post_meta($order_id, '_alquipress_mailpoet_subscribed', '1');
        } catch (\Exception $e) {
            if (function_exists('error_log')) {
                error_log('ALQUIPRESS MailPoet subscribe: ' . $e->getMessage());
            }
        }
    }

    private function get_list_ids_by_slugs(array $slugs)
    {
        if (!class_exists('\MailPoet\API\API')) {
            return [];
        }
        try {
            $lists = \MailPoet\API\API::MP('v1')->getLists();
            $ids = [];
            foreach ($lists as $list) {
                $name_slug = sanitize_title($list['name']);
                if (in_array($name_slug, $slugs, true)) {
                    $ids[] = (int) $list['id'];
                }
            }
            return array_unique($ids);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function register_unsubscribe_endpoint()
    {
        add_rewrite_rule('^baja-newsletter/?$', 'index.php?alquipress_baja_newsletter=1', 'top');
        add_filter('query_vars', function ($vars) {
            $vars[] = 'alquipress_baja_newsletter';
            return $vars;
        });
    }

    public function handle_unsubscribe()
    {
        $is_baja = (int) get_query_var('alquipress_baja_newsletter', 0) === 1;
        if (!$is_baja) {
            $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            if (strpos($uri, '/baja-newsletter') === false) {
                return;
            }
        }
        $email = isset($_GET['email']) ? sanitize_email(wp_unslash($_GET['email'])) : '';
        if (!$email) {
            wp_die(esc_html__('Email no válido.', 'alquipress'), '', ['response' => 400]);
        }
        if (class_exists('\MailPoet\API\API')) {
            try {
                $subscriber = \MailPoet\API\API::MP('v1')->getSubscriber($email);
                if (!empty($subscriber['id'])) {
                    $api = \MailPoet\API\API::MP('v1');
                if (method_exists($api, 'unsubscribe')) {
                    $api->unsubscribe($subscriber['id']);
                }
                }
            } catch (\Exception $e) {
                // ignore
            }
        }
        $user = get_user_by('email', $email);
        if ($user) {
            update_user_meta($user->ID, 'newsletter_opt_out', true);
        }
        wp_die(
            '<h2>' . esc_html__('Baja confirmada', 'alquipress') . '</h2><p>' . esc_html__('Ya no recibirás más emails de nuestra lista.', 'alquipress') . ' <a href="' . esc_url(home_url('/')) . '">' . esc_html__('Volver a la web', 'alquipress') . '</a></p>',
            esc_html__('Baja de newsletter', 'alquipress'),
            ['response' => 200]
        );
    }
}

new Alquipress_MailPoet_Integration();
Alquipress_Email_Flows::init();
