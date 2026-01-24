<?php
/**
 * Sistema de Auditoría para Datos Sensibles
 * Registra accesos a información confidencial de propietarios
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Audit_Logger
{
    private static $log_file;

    public static function init()
    {
        self::$log_file = WP_CONTENT_DIR . '/alquipress-audit.log';
        add_action('wp_ajax_alquipress_log_iban_access', [__CLASS__, 'log_iban_access']);
    }

    /**
     * Registrar acceso a IBAN via AJAX
     */
    public static function log_iban_access()
    {
        check_ajax_referer('alquipress_iban_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('No autorizado', 403);
        }

        $owner_id = isset($_POST['owner_id']) ? absint($_POST['owner_id']) : 0;
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'view';

        if (!$owner_id) {
            wp_send_json_error('ID de propietario inválido', 400);
        }

        // Verificar que el post existe y es un propietario
        $post = get_post($owner_id);
        if (!$post || $post->post_type !== 'propietario') {
            wp_send_json_error('Propietario no encontrado', 404);
        }

        $log_entry = sprintf(
            "[%s] Usuario: %s (ID: %d) | Acción: %s | Propietario ID: %d (%s) | IP: %s\n",
            current_time('mysql'),
            wp_get_current_user()->user_login,
            get_current_user_id(),
            $action,
            $owner_id,
            get_the_title($owner_id),
            self::get_client_ip()
        );

        // Escribir al log
        self::write_log($log_entry);

        wp_send_json_success(['logged' => true]);
    }

    /**
     * Escribir entrada al archivo de log
     */
    private static function write_log($entry)
    {
        // Crear directorio si no existe
        $log_dir = dirname(self::$log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Escribir al archivo
        error_log($entry, 3, self::$log_file);

        // Rotar log si es muy grande (> 5MB)
        if (file_exists(self::$log_file) && filesize(self::$log_file) > 5 * 1024 * 1024) {
            self::rotate_log();
        }
    }

    /**
     * Rotar archivo de log
     */
    private static function rotate_log()
    {
        $backup_file = self::$log_file . '.' . date('Y-m-d-His') . '.bak';
        rename(self::$log_file, $backup_file);

        // Mantener solo los últimos 5 archivos de backup
        $backups = glob(self::$log_file . '.*.bak');
        if (count($backups) > 5) {
            usort($backups, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            // Eliminar los más antiguos
            foreach (array_slice($backups, 0, -5) as $old_backup) {
                unlink($old_backup);
            }
        }
    }

    /**
     * Obtener IP del cliente
     */
    private static function get_client_ip()
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }

    /**
     * Obtener últimos logs (solo para administradores)
     */
    public static function get_recent_logs($limit = 50)
    {
        if (!current_user_can('manage_options')) {
            return [];
        }

        if (!file_exists(self::$log_file)) {
            return [];
        }

        $lines = file(self::$log_file);
        return array_slice($lines, -$limit);
    }

    /**
     * Agregar página de auditoría en admin
     */
    public static function add_audit_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        add_submenu_page(
            'alquipress-settings',
            'Auditoría de Accesos',
            '🔒 Auditoría',
            'manage_options',
            'alquipress-audit',
            [__CLASS__, 'render_audit_page']
        );
    }

    /**
     * Renderizar página de auditoría
     */
    public static function render_audit_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para acceder a esta página.');
        }

        $logs = self::get_recent_logs(100);
        ?>
        <div class="wrap">
            <h1>🔒 Auditoría de Accesos a Datos Sensibles</h1>

            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2>Últimos 100 accesos registrados</h2>

                <?php if (empty($logs)): ?>
                    <p style="color: #666;">No hay registros de auditoría todavía.</p>
                <?php else: ?>
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 600px; overflow-y: auto;">
                        <?php foreach (array_reverse($logs) as $log): ?>
                            <div style="padding: 5px 0; border-bottom: 1px solid #e0e0e0;">
                                <?php echo esc_html($log); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p style="margin-top: 20px;">
                    <strong>Archivo de log:</strong> <code><?php echo esc_html(self::$log_file); ?></code>
                </p>
            </div>

            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2>ℹ️ Información</h2>
                <ul>
                    <li>Se registran todos los accesos a datos sensibles (IBAN, cuentas bancarias)</li>
                    <li>Los logs incluyen: fecha, usuario, acción, propietario afectado e IP</li>
                    <li>Los archivos de log se rotan automáticamente cuando superan 5MB</li>
                    <li>Se mantienen los últimos 5 archivos de backup</li>
                </ul>
            </div>
        </div>

        <style>
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .card h2 {
                margin-top: 0;
            }
        </style>
        <?php
    }
}

// Inicializar
Alquipress_Audit_Logger::init();
add_action('admin_menu', ['Alquipress_Audit_Logger', 'add_audit_page'], 30);
