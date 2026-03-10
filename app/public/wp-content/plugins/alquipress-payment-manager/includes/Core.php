<?php
/**
 * Clase principal del plugin
 *
 * @package ALQUIPRESS\PaymentManager
 */

namespace ALQUIPRESS\PaymentManager;

defined('ABSPATH') || exit;

/**
 * Core class - Singleton
 */
class Core {

    /**
     * Instancia única
     *
     * @var Core
     */
    private static $instance = null;

    /**
     * Componentes cargados
     *
     * @var array
     */
    private $components = [];

    /**
     * Obtener instancia única
     *
     * @return Core
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->load_components();
        $this->init_hooks();
    }

    /**
     * Cargar componentes del plugin
     */
    private function load_components() {
        // Admin
        if (is_admin()) {
            $this->components['settings'] = new Admin\Settings();
            $this->components['product_metabox'] = new Admin\ProductMetabox();
        }

        // Frontend
        if (!is_admin() || wp_doing_ajax()) {
            $this->components['checkout_modifier'] = new Frontend\CheckoutModifier();
        }

        // Payment handlers (siempre activos)
        $this->components['deposit_handler'] = new Payment\DepositHandler();
        // Stripe Preauth solo está disponible si está configurado
        if (class_exists('ALQUIPRESS\PaymentManager\Payment\StripePreauth')) {
            $this->components['stripe_preauth'] = new Payment\StripePreauth();
        }

        // Orders
        $this->components['status_manager'] = new Orders\StatusManager();
        $this->components['scheduled_payments'] = new Orders\ScheduledPayments();
    }

    /**
     * Inicializar hooks generales
     */
    private function init_hooks() {
        // Encolar assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_apm_get_payment_breakdown', [$this, 'ajax_get_payment_breakdown']);
        add_action('wp_ajax_nopriv_apm_get_payment_breakdown', [$this, 'ajax_get_payment_breakdown']);
    }

    /**
     * Encolar assets del frontend
     */
    public function enqueue_frontend_assets() {
        if (!is_checkout() && !is_cart()) {
            return;
        }

        wp_enqueue_style(
            'apm-frontend',
            APM_URL . 'assets/css/frontend.css',
            [],
            APM_VERSION
        );

        wp_enqueue_script(
            'apm-frontend',
            APM_URL . 'assets/js/frontend.js',
            ['jquery'],
            APM_VERSION,
            true
        );

        // Localize script with translations (safe to call after init)
        if (did_action('init')) {
            wp_localize_script('apm-frontend', 'apm_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('apm_nonce'),
                'i18n' => [
                    'pay_full_label' => __('Prefiero pagar el importe total ahora', 'apm'),
                    'deposit_label' => __('Pago hoy (Depósito)', 'apm'),
                    'balance_label' => __('Segundo pago', 'apm'),
                    'security_label' => __('Fianza (Retención)', 'apm'),
                ]
            ]);
        } else {
            // Defer localization until after init
            add_action('init', function() {
                wp_localize_script('apm-frontend', 'apm_vars', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('apm_nonce'),
                    'i18n' => [
                        'pay_full_label' => __('Prefiero pagar el importe total ahora', 'apm'),
                        'deposit_label' => __('Pago hoy (Depósito)', 'apm'),
                        'balance_label' => __('Segundo pago', 'apm'),
                        'security_label' => __('Fianza (Retención)', 'apm'),
                    ]
                ]);
            });
        }
    }

    /**
     * Encolar assets del admin
     */
    public function enqueue_admin_assets($hook) {
        global $post;

        // Solo en páginas relevantes
        $allowed_hooks = ['post.php', 'post-new.php', 'woocommerce_page_wc-settings'];
        if (!in_array($hook, $allowed_hooks)) {
            return;
        }

        // Solo para productos o settings de WooCommerce
        if (in_array($hook, ['post.php', 'post-new.php']) && (!$post || $post->post_type !== 'product')) {
            return;
        }

        wp_enqueue_style(
            'apm-admin',
            APM_URL . 'assets/css/admin.css',
            [],
            APM_VERSION
        );

        wp_enqueue_script(
            'apm-admin',
            APM_URL . 'assets/js/admin.js',
            ['jquery'],
            APM_VERSION,
            true
        );
    }

    /**
     * AJAX: Obtener desglose de pagos
     */
    public function ajax_get_payment_breakdown() {
        check_ajax_referer('apm_nonce', 'nonce');

        $breakdown = $this->components['checkout_modifier']->calculate_cart_breakdown();

        if ($breakdown) {
            wp_send_json_success($breakdown);
        } else {
            wp_send_json_error('No se pudo calcular el desglose');
        }
    }

    /**
     * Obtener componente
     *
     * @param string $name Nombre del componente
     * @return mixed|null
     */
    public function get_component($name) {
        return $this->components[$name] ?? null;
    }

    /**
     * Obtener configuración de un producto
     *
     * @param int $product_id ID del producto
     * @return array
     */
    public static function get_product_config($product_id) {
        // Verificar si tiene configuración personalizada
        if (get_post_meta($product_id, '_apm_override_global', true) === 'yes') {
            return [
                'deposit_percent' => (float) get_post_meta($product_id, '_apm_deposit_percent', true) ?: 40,
                'days_before' => (int) get_post_meta($product_id, '_apm_days_before', true) ?: 7,
                'security_deposit' => (float) get_post_meta($product_id, '_apm_security_deposit', true) ?: 300,
                'second_payment_methods' => get_post_meta($product_id, '_apm_second_payment_methods', true) ?: ['stripe'],
                'security_methods' => get_post_meta($product_id, '_apm_security_methods', true) ?: ['stripe_hold'],
            ];
        }

        // Usar configuración global
        return [
            'deposit_percent' => (float) get_option('apm_default_deposit_percent', 40),
            'days_before' => (int) get_option('apm_default_days_before', 7),
            'security_deposit' => (float) get_option('apm_default_security_deposit', 300),
            'second_payment_methods' => get_option('apm_second_payment_methods', ['stripe']),
            'security_methods' => get_option('apm_security_methods', ['stripe_hold']),
        ];
    }
}
