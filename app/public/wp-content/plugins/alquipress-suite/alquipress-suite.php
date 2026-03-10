<?php
/**
 * Plugin Name: ALQUIPRESS Performance & Security Suite
 * Description: Optimización avanzada de rendimiento, seguridad y búsqueda para la plataforma ALQUIPRESS.
 * Version: 1.0.0
 * Author: Antigravity AI
 * Text Domain: alquipress-suite
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definición de constantes
define('ALQ_SUITE_VERSION', '1.0.0');
define('ALQ_SUITE_PATH', plugin_dir_path(__FILE__));
define('ALQ_SUITE_URL', plugin_dir_url(__FILE__));
define('ALQ_SUITE_BASENAME', plugin_basename(__FILE__));

/**
 * Autoload de clases (PSR-4 básico manual para evitar dependencias externas iniciales)
 */
spl_autoload_register(function ($class) {
    $prefix = 'Alquipress\\Suite\\';
    $base_dir = ALQ_SUITE_PATH . 'includes/';

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
 * Inicialización de la Suite
 */
function alq_suite_init()
{
    // Verificar dependencias
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>' . esc_html__('ALQUIPRESS Suite requiere que WooCommerce esté instalado y activo.', 'alquipress-suite') . '</p></div>';
        });
        return;
    }

    // Instanciar el Core Manager
    \Alquipress\Suite\Core\Manager::instance();
}
add_action('plugins_loaded', 'alq_suite_init');

/**
 * Hook de activación
 */
register_activation_hook(__FILE__, ['Alquipress\\Suite\\Core\\Activator', 'activate']);
