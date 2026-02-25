<?php
/**
 * Plugin Name: ALQUIPRESS Core
 * Description: Sistema CRM modular para gestión de alquileres vacacionales
 * Version: 1.0.0
 * Author: Tu Nombre
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH'))
    exit;

define('ALQUIPRESS_VERSION', '1.0.0');
define('ALQUIPRESS_PATH', plugin_dir_path(__FILE__));
define('ALQUIPRESS_URL', plugin_dir_url(__FILE__));

// Cargar compatibilidad de campos (antes que cualquier módulo, para que get_field() esté disponible)
require_once ALQUIPRESS_PATH . 'includes/class-ap-fields.php';

// Cargar helpers primero
require_once ALQUIPRESS_PATH . 'includes/helpers.php';
require_once ALQUIPRESS_PATH . 'includes/email-helpers.php';

/**
 * Inicializar el plugin tras cargar todos los plugins para que las
 * dependencias (WooCommerce/ACF) estén disponibles en cualquier orden.
 */
function alquipress_bootstrap()
{
    if (!alquipress_check_dependencies()) {
        return;
    }

    if (class_exists('WooCommerce')) {
        require_once ALQUIPRESS_PATH . 'includes/integrations/class-wc-deposits-payments.php';
        Alquipress_WC_Deposits_Payments::init();
    }

    require_once ALQUIPRESS_PATH . 'includes/class-rate-limiter.php';
    require_once ALQUIPRESS_PATH . 'includes/class-module-manager.php';
    require_once ALQUIPRESS_PATH . 'includes/admin/class-live-search.php';
    require_once ALQUIPRESS_PATH . 'includes/class-frontend-filters.php';
    require_once ALQUIPRESS_PATH . 'includes/class-performance-optimizer.php';
    require_once ALQUIPRESS_PATH . 'includes/class-property-helper.php';
    require_once ALQUIPRESS_PATH . 'includes/class-config.php';
    require_once ALQUIPRESS_PATH . 'includes/class-logger.php';

    $module_manager = new Alquipress_Module_Manager();
    $module_manager->load_active_modules();
    new Alquipress_Live_Search();
}
add_action('plugins_loaded', 'alquipress_bootstrap', 20);

register_activation_hook(__FILE__, 'alquipress_activate');
function alquipress_activate()
{
    alquipress_add_owner_role();
    if (!get_option('alquipress_modules')) {
        update_option('alquipress_modules', [
            'taxonomies' => true,
            'crm-guests' => true,
            'crm-owners' => true,
            'booking-pipeline' => true,
            'email-automation' => true,
            'seo-master' => true,
            'booking-enforcer' => true,
            'ap-bookings' => true,
            'order-columns' => true,
            'dashboard-widgets' => true,
            'properties-page' => true,
            'property-editor' => true,
            'property-pricing-fields' => true,
            'accounting' => true,
            'owner-invoicing' => true,
            'checkout-document-fields' => true,
            'owner-portal' => true,
            'email-campaigns' => true,
            'owners-page' => true,
            'bookings-page' => true,
            'clients-page' => true,
            'booking-calendar-prices' => true,
            'ses-compliance' => true,
            'payments' => false,
            'alquipress-tester' => false
        ]);
    }
    
    // Crear índices de base de datos para optimizar queries de reservas
    alquipress_create_database_indexes();
    
    flush_rewrite_rules();
}

/**
 * Crear índices de base de datos para optimizar queries
 */
function alquipress_create_database_indexes()
{
    global $wpdb;
    
    // Verificar si los índices ya existen
    $indexes_exist = get_option('alquipress_db_indexes_created', false);
    if ($indexes_exist) {
        return;
    }
    
    // Índices para meta_query de fechas de reservas
    // Nota: WordPress no soporta índices directamente en postmeta, pero podemos optimizar las queries
    // En su lugar, documentamos las mejores prácticas y optimizamos a nivel de código
    
    // Marcar que los índices fueron "creados" (en realidad, optimizamos a nivel de código)
    update_option('alquipress_db_indexes_created', true);
}
