<?php
/**
 * Plugin Name: ALQUIPRESS Payment Manager
 * Plugin URI: https://alquipress.com
 * Description: Sistema de pagos escalonados y fianza para alquiler vacacional. Gestiona depósitos, pagos programados y pre-autorizaciones de fianza con Stripe.
 * Version: 1.0.0
 * Author: ALQUIPRESS
 * Author URI: https://alquipress.com
 * Text Domain: apm
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * Requires Plugins: woocommerce
 *
 * @package ALQUIPRESS\PaymentManager
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Workaround para el bug de WP 6.7.0 con PHP 8.1+
 * Evita avisos de parámetros nulos en wp_normalize_path durante la carga JIT de traducciones.
 * Se coloca al principio para capturar llamadas de otros plugins que se carguen después.
 */
add_filter('override_load_textdomain', function($override, $domain, $mofile) {
    if (null === $mofile || empty($mofile)) {
        return true; // Bloquear carga si el path es nulo o vacío para evitar el Deprecated
    }
    return $override;
}, 1, 3);

// Constantes del plugin
define('APM_VERSION', '1.0.0');
define('APM_PATH', plugin_dir_path(__FILE__));
define('APM_URL', plugin_dir_url(__FILE__));
define('APM_BASENAME', plugin_basename(__FILE__));

// Autoload de clases
spl_autoload_register(function ($class) {
    $prefix = 'ALQUIPRESS\\PaymentManager\\';
    $base_dir = APM_PATH . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Verificar dependencias antes de inicializar
 */
function apm_check_dependencies() {
    $missing = [];

    if (!class_exists('WooCommerce')) {
        $missing[] = 'WooCommerce';
    }

    return $missing;
}

/**
 * Mostrar aviso de dependencias faltantes
 */
function apm_missing_dependencies_notice() {
    $missing = apm_check_dependencies();
    if (empty($missing)) {
        return;
    }

    $message = sprintf(
        __('ALQUIPRESS Payment Manager requiere los siguientes plugins: %s', 'apm'),
        '<strong>' . implode(', ', $missing) . '</strong>'
    );

    echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
}

/**
 * Cargar traducciones del plugin
 */
function apm_load_textdomain() {
    $path = (string) dirname(APM_BASENAME) . '/languages';
    load_plugin_textdomain('apm', false, $path);
}
add_action('init', 'apm_load_textdomain');

/**
 * Inicialización del plugin
 */
function apm_init() {
    // Verificar dependencias
    $missing = apm_check_dependencies();
    if (!empty($missing)) {
        add_action('admin_notices', 'apm_missing_dependencies_notice');
        return;
    }

    // Inicializar el core del plugin
    \ALQUIPRESS\PaymentManager\Core::instance();
}
add_action('plugins_loaded', 'apm_init', 20);

/**
 * Activación del plugin
 */
function apm_activate() {
    // Crear tablas de base de datos
    if (class_exists('ALQUIPRESS\\PaymentManager\\Database\\Schema')) {
        \ALQUIPRESS\PaymentManager\Database\Schema::create_tables();
    }

    // Programar cron para pagos automáticos
    if (!wp_next_scheduled('apm_process_scheduled_payments')) {
        wp_schedule_event(time(), 'hourly', 'apm_process_scheduled_payments');
    }

    // Limpiar caché de rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'apm_activate');

/**
 * Desactivación del plugin
 */
function apm_deactivate() {
    // Limpiar cron
    wp_clear_scheduled_hook('apm_process_scheduled_payments');

    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'apm_deactivate');

/**
 * Declarar compatibilidad con HPOS (High-Performance Order Storage)
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
