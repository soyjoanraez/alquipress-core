<?php
/**
 * Esquema de base de datos
 *
 * @package ALQUIPRESS\PaymentManager\Database
 */

namespace ALQUIPRESS\PaymentManager\Database;

defined('ABSPATH') || exit;

/**
 * Class Schema
 * Crea y gestiona las tablas personalizadas del plugin
 */
class Schema {

    /**
     * Versión del esquema de BD
     */
    const DB_VERSION = '1.0.0';

    /**
     * Crear todas las tablas
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla principal de pagos programados
        $table_payments = $wpdb->prefix . 'apm_payment_schedule';
        $sql_payments = "CREATE TABLE IF NOT EXISTS {$table_payments} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            booking_id bigint(20) unsigned DEFAULT NULL,
            payment_type varchar(50) NOT NULL,
            amount decimal(12,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'EUR',
            scheduled_date datetime NOT NULL,
            paid_date datetime DEFAULT NULL,
            payment_method varchar(50) DEFAULT NULL,
            stripe_intent_id varchar(255) DEFAULT NULL,
            stripe_charge_id varchar(255) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int(11) NOT NULL DEFAULT 0,
            last_error text DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY booking_id (booking_id),
            KEY status (status),
            KEY scheduled_date (scheduled_date),
            KEY payment_type (payment_type)
        ) {$charset_collate};";

        // Tabla de fianzas
        $table_security = $wpdb->prefix . 'apm_security_deposits';
        $sql_security = "CREATE TABLE IF NOT EXISTS {$table_security} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            booking_id bigint(20) unsigned DEFAULT NULL,
            amount decimal(12,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'EUR',
            method varchar(50) NOT NULL,
            stripe_intent_id varchar(255) DEFAULT NULL,
            status varchar(30) NOT NULL DEFAULT 'pending',
            held_at datetime DEFAULT NULL,
            released_at datetime DEFAULT NULL,
            captured_at datetime DEFAULT NULL,
            captured_amount decimal(12,2) DEFAULT NULL,
            capture_reason text DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY booking_id (booking_id),
            KEY status (status),
            KEY stripe_intent_id (stripe_intent_id)
        ) {$charset_collate};";

        // Tabla de logs/auditoría
        $table_logs = $wpdb->prefix . 'apm_payment_logs';
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$table_logs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            payment_schedule_id bigint(20) unsigned DEFAULT NULL,
            security_deposit_id bigint(20) unsigned DEFAULT NULL,
            action varchar(100) NOT NULL,
            status varchar(30) NOT NULL,
            amount decimal(12,2) DEFAULT NULL,
            message text DEFAULT NULL,
            meta longtext DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY action (action),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql_payments);
        dbDelta($sql_security);
        dbDelta($sql_logs);

        // Guardar versión del esquema
        update_option('apm_db_version', self::DB_VERSION);
    }

    /**
     * Verificar si las tablas necesitan actualización
     *
     * @return bool
     */
    public static function needs_upgrade() {
        $current_version = get_option('apm_db_version', '0');
        return version_compare($current_version, self::DB_VERSION, '<');
    }

    /**
     * Eliminar todas las tablas (para desinstalación)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'apm_payment_schedule',
            $wpdb->prefix . 'apm_security_deposits',
            $wpdb->prefix . 'apm_payment_logs',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('apm_db_version');
    }

    /**
     * Obtener nombre de tabla
     *
     * @param string $table Nombre corto de la tabla
     * @return string Nombre completo con prefijo
     */
    public static function get_table_name($table) {
        global $wpdb;

        $tables = [
            'payments' => $wpdb->prefix . 'apm_payment_schedule',
            'security' => $wpdb->prefix . 'apm_security_deposits',
            'logs'     => $wpdb->prefix . 'apm_payment_logs',
        ];

        return $tables[$table] ?? '';
    }
}
