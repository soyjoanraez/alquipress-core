<?php
namespace Alquipress\Suite\Core;

if (!defined('ABSPATH'))
    exit;

class Activator
{

    public static function activate()
    {
        // Inicializar opciones si no existen
        if (false === get_option('alq_suite_active_modules')) {
            update_option('alq_suite_active_modules', [
                'wpo' => true,
                'image_optimizer' => true,
                'security' => false
            ]);
        }

        // Crear tablas si es necesario (el módulo de seguridad podría necesitarlas)
        self::create_tables();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    private static function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de logs de seguridad / Auditoría si se requiere
        $table_name = $wpdb->prefix . 'alquipress_security_log';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED,
            action VARCHAR(100),
            entity_type VARCHAR(50),
            entity_id BIGINT,
            old_value TEXT,
            new_value TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_action (user_id, action),
            INDEX idx_entity (entity_type, entity_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
