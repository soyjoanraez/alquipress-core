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
    private static $write_counter = 0;

    public static function init()
    {
        // SEGURIDAD: Mover log fuera del document root (CRITICAL #1)
        // Si no es posible, usar directorio protegido
        $log_dir = dirname(ABSPATH) . '/alquipress-logs';

        // Fallback: Si no se puede crear fuera de ABSPATH, usar wp-content con .htaccess
        if (!is_dir($log_dir) && !wp_mkdir_p($log_dir)) {
            $log_dir = WP_CONTENT_DIR . '/alquipress-logs';
            wp_mkdir_p($log_dir);
            self::protect_log_directory($log_dir);
        }

        self::$log_file = $log_dir . '/audit.log';
        add_action('wp_ajax_alquipress_log_iban_access', [__CLASS__, 'log_iban_access']);
    }

    /**
     * Proteger directorio de logs con .htaccess (fallback si no está fuera de document root)
     */
    private static function protect_log_directory($log_dir)
    {
        $htaccess_file = $log_dir . '/.htaccess';

        if (!file_exists($htaccess_file)) {
            $content = "# Bloquear acceso a archivos de log\n";
            $content .= "Order deny,allow\n";
            $content .= "Deny from all\n";
            $content .= "<FilesMatch \"\\.(log|bak)$\">\n";
            $content .= "    Deny from all\n";
            $content .= "</FilesMatch>\n";

            file_put_contents($htaccess_file, $content);
        }
    }

    /**
     * Registrar acceso a IBAN via AJAX
     */
    public static function log_iban_access()
    {
        // Rate limiting: 10 accesos por minuto (prevenir spam)
        Alquipress_Rate_Limiter::check_and_exit('log_iban_access', 10, 60);

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
            esc_html(get_the_title($owner_id)), // Sanitizar título
            alquipress_get_client_ip() // Usar función global mejorada
        );

        // Escribir al log
        self::write_log($log_entry);

        wp_send_json_success(['logged' => true]);
    }

    /**
     * Escribir entrada al archivo de log
     * Optimizado: Solo verifica filesize cada 50 escrituras (MEDIUM #6)
     */
    private static function write_log($entry)
    {
        // Crear directorio si no existe
        $log_dir = dirname(self::$log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Escribir al archivo
        $result = error_log($entry, 3, self::$log_file);

        if ($result === false) {
            error_log('ALQUIPRESS Audit: Failed to write to log file');
            return;
        }

        // Incrementar contador
        self::$write_counter++;

        // Verificar tamaño solo cada 50 escrituras (optimización)
        if (self::$write_counter % 50 === 0 && file_exists(self::$log_file)) {
            $filesize = filesize(self::$log_file);
            if ($filesize !== false && $filesize > 5 * 1024 * 1024) {
                self::rotate_log();
            }
        }
    }

    /**
     * Rotar archivo de log
     * Mejorado con error handling y optimización (MEDIUM #5, #7)
     */
    private static function rotate_log()
    {
        $backup_file = self::$log_file . '.' . date('Y-m-d-His') . '.bak';

        // Verificar resultado de rename (MEDIUM #5)
        if (!rename(self::$log_file, $backup_file)) {
            error_log('ALQUIPRESS Audit: Failed to rotate log file');
            return;
        }

        // Resetear contador después de rotar
        self::$write_counter = 0;

        // Mantener solo los últimos 5 archivos de backup (optimizado)
        $backups = glob(self::$log_file . '.*.bak');

        if ($backups === false || count($backups) <= 5) {
            return; // No hay backups o no exceden el límite
        }

        // Optimización: usar array_multisort en lugar de usort con filemtime
        $mtimes = array_map('filemtime', $backups);
        array_multisort($mtimes, SORT_ASC, $backups);

        // Eliminar los más antiguos (mantener últimos 5)
        $to_delete = array_slice($backups, 0, -5);
        foreach ($to_delete as $old_backup) {
            if (file_exists($old_backup) && is_writable($old_backup)) {
                unlink($old_backup);
            }
        }
    }

    /**
     * Obtener últimos logs (solo para administradores)
     * Optimizado con SplFileObject para prevenir memory exhaustion (HIGH #1)
     */
    public static function get_recent_logs($limit = 50)
    {
        if (!current_user_can('manage_options')) {
            return [];
        }

        if (!file_exists(self::$log_file)) {
            return [];
        }

        try {
            // Usar SplFileObject para lectura eficiente (HIGH #1)
            $file = new SplFileObject(self::$log_file, 'r');
            $file->seek(PHP_INT_MAX); // Ir al final
            $total_lines = $file->key() + 1;

            // Calcular desde qué línea leer
            $start_line = max(0, $total_lines - $limit);

            $lines = [];
            $file->seek($start_line);

            while (!$file->eof() && count($lines) < $limit) {
                $line = $file->current();
                if (!empty(trim($line))) {
                    $lines[] = $line;
                }
                $file->next();
            }

            return $lines;

        } catch (Exception $e) {
            error_log('ALQUIPRESS Audit: Error reading log file - ' . $e->getMessage());
            return [];
        }
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
        <div class="ap-wrap">
            <div class="ap-page-header">
                <h1>
                    <span class="dashicons dashicons-shield-alt"></span>
                    Auditoría de Accesos a Datos Sensibles
                </h1>
                <p>Registro de todos los accesos a información confidencial de propietarios.</p>
            </div>

            <div class="ap-card">
                <h2>Últimos 100 accesos registrados</h2>

                <?php if (empty($logs)): ?>
                    <p class="ap-text-muted">No hay registros de auditoría todavía.</p>
                <?php else: ?>
                    <div class="ap-code-block">
                        <?php foreach (array_reverse($logs) as $log): ?>
                            <div class="ap-code-block__line">
                                <?php echo esc_html($log); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="ap-mt-5">
                    <strong>Archivo de log:</strong> <code><?php echo esc_html(self::$log_file); ?></code>
                </p>
            </div>

            <div class="ap-card ap-card--info">
                <h2>
                    <span class="dashicons dashicons-info"></span>
                    Información del Sistema
                </h2>
                <ul>
                    <li>Se registran todos los accesos a datos sensibles (IBAN, cuentas bancarias)</li>
                    <li>Los logs incluyen: fecha, usuario, acción, propietario afectado e IP</li>
                    <li>Los archivos de log se rotan automáticamente cuando superan 5MB</li>
                    <li>Se mantienen los últimos 5 archivos de backup</li>
                    <li><strong>Seguridad:</strong> Los logs están almacenados fuera del document root o protegidos con .htaccess</li>
                </ul>
            </div>
        </div>
        <?php
    }
}

// Inicializar
Alquipress_Audit_Logger::init();
add_action('admin_menu', ['Alquipress_Audit_Logger', 'add_audit_page'], 30);
