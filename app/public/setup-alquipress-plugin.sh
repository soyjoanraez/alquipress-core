#!/bin/bash
# Generar estructura del plugin ALQUIPRESS Core

echo "🔧 Generando plugin ALQUIPRESS Core..."

PLUGIN_DIR="wp-content/plugins/alquipress-core"

# Crear estructura de directorios
mkdir -p $PLUGIN_DIR/includes/{modules/{crm-guests,crm-owners,taxonomies,booking-pipeline,email-automation,payments},admin/assets}
mkdir -p $PLUGIN_DIR/assets/{css,js}

# Archivo principal del plugin
cat > $PLUGIN_DIR/alquipress-core.php << 'PHPEOF'
<?php
/**
 * Plugin Name: ALQUIPRESS Core
 * Description: Sistema CRM modular para gestión de alquileres vacacionales
 * Version: 1.0.0
 * Author: Tu Nombre
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) exit;

define('ALQUIPRESS_VERSION', '1.0.0');
define('ALQUIPRESS_PATH', plugin_dir_path(__FILE__));
define('ALQUIPRESS_URL', plugin_dir_url(__FILE__));

require_once ALQUIPRESS_PATH . 'includes/class-module-manager.php';

function alquipress_init() {
    $module_manager = new Alquipress_Module_Manager();
    $module_manager->load_active_modules();
}
add_action('plugins_loaded', 'alquipress_init');

register_activation_hook(__FILE__, 'alquipress_activate');
function alquipress_activate() {
    if (!get_option('alquipress_modules')) {
        update_option('alquipress_modules', [
            'taxonomies' => true,
            'crm-guests' => true,
            'crm-owners' => true,
            'booking-pipeline' => true,
            'email-automation' => true,
            'payments' => false
        ]);
    }
    flush_rewrite_rules();
}
PHPEOF

# Copiar los archivos PHP del plan anterior (Module Manager, etc.)
# ... (aquí irían los archivos que ya te mostré)

echo "✓ Estructura del plugin creada en: $PLUGIN_DIR"

# Activar el plugin
wp plugin activate alquipress-core

echo "✓ Plugin ALQUIPRESS Core activado"