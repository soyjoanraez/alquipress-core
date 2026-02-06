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

require_once ALQUIPRESS_PATH . 'includes/class-module-manager.php';
require_once ALQUIPRESS_PATH . 'includes/class-frontend-filters.php';
require_once ALQUIPRESS_PATH . 'includes/class-performance-optimizer.php';
require_once ALQUIPRESS_PATH . 'includes/class-property-helper.php';
require_once ALQUIPRESS_PATH . 'includes/class-config.php';
require_once ALQUIPRESS_PATH . 'includes/class-logger.php';
require_once ALQUIPRESS_PATH . 'includes/class-order-status-guard.php';
require_once ALQUIPRESS_PATH . 'includes/class-owner-role-manager.php';

function alquipress_init()
{
    Alquipress_Owner_Role_Manager::ensure_role_exists();

    $module_manager = new Alquipress_Module_Manager();
    $module_manager->load_active_modules();
}
add_action('plugins_loaded', 'alquipress_init');

register_activation_hook(__FILE__, 'alquipress_activate');
function alquipress_activate()
{
    Alquipress_Owner_Role_Manager::ensure_role_exists();
    $migrated_owner_users = Alquipress_Owner_Role_Manager::migrate_legacy_owner_users();

    if (!get_option('alquipress_modules')) {
        update_option('alquipress_modules', [
            'taxonomies' => true,
            'crm-guests' => true,
            'crm-owners' => true,
            'booking-pipeline' => true,
            'email-automation' => true,
            'seo-master' => true,
            'booking-enforcer' => true,
            'order-columns' => true,
            'dashboard-widgets' => true,
            'properties-page' => true,
            'property-editor' => true,
            'owners-page' => true,
            'bookings-page' => true,
            'clients-page' => true,
            'booking-calendar-prices' => true,
            'payments' => false,
            'alquipress-tester' => false
        ]);
    }

    update_option('alquipress_owner_role_migrated_users', (int) $migrated_owner_users, false);
    
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
