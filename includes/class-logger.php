<?php
/**
 * Sistema de Logging Centralizado para Alquipress
 * 
 * @package Alquipress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Logger
{
    /**
     * Niveles de log disponibles
     * 
     * - LEVEL_ERROR: Errores críticos que requieren atención inmediata
     * - LEVEL_WARNING: Advertencias que indican problemas potenciales
     * - LEVEL_INFO: Información general sobre el funcionamiento del sistema
     * - LEVEL_DEBUG: Información detallada solo visible cuando WP_DEBUG está activado
     * 
     * @var string
     * @since 1.0.0
     */
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';

    /**
     * Contextos de logging disponibles
     * 
     * - CONTEXT_AJAX: Operaciones AJAX y endpoints
     * - CONTEXT_QUERY: Consultas a base de datos
     * - CONTEXT_CACHE: Operaciones de caché
     * - CONTEXT_MODULE: Eventos de módulos
     * - CONTEXT_SECURITY: Eventos de seguridad
     * 
     * @var string
     * @since 1.0.0
     */
    const CONTEXT_AJAX = 'ajax';
    const CONTEXT_QUERY = 'query';
    const CONTEXT_CACHE = 'cache';
    const CONTEXT_MODULE = 'module';
    const CONTEXT_SECURITY = 'security';
    const CONTEXT_PAYMENT = 'payment';
    const CONTEXT_BOOKING = 'booking';
    const CONTEXT_EMAIL = 'email';
    const CONTEXT_ICAL = 'ical';

    /**
     * Log un mensaje con nivel y contexto específicos
     * 
     * Método principal para registrar eventos. Los mensajes se escriben en error_log
     * de WordPress. Los errores críticos también se guardan en una opción de WordPress
     * para revisión posterior.
     * 
     * Solo registra mensajes de debug si WP_DEBUG está activado. Los errores siempre
     * se registran independientemente de WP_DEBUG.
     * 
     * @param string $message Mensaje a registrar
     * @param string $level Nivel de log (use las constantes LEVEL_*)
     * @param string $context Contexto del log (use las constantes CONTEXT_*)
     * @param array $data Datos adicionales para incluir en el log (se serializan como JSON)
     * @return void
     * @since 1.0.0
     * @example
     * Alquipress_Logger::log('Pedido actualizado', self::LEVEL_INFO, self::CONTEXT_AJAX, [
     *     'order_id' => 123,
     *     'status' => 'completed'
     * ]);
     */
    public static function log($message, $level = self::LEVEL_INFO, $context = '', $data = [])
    {
        // Solo loggear si WP_DEBUG está activado o es un error crítico
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            if ($level !== self::LEVEL_ERROR) {
                return;
            }
        }

        $log_entry = [
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'context' => $context,
            'message' => $message,
            'data' => $data,
        ];

        // Formatear mensaje para error_log
        $formatted_message = sprintf(
            '[Alquipress] [%s] [%s] %s',
            strtoupper($level),
            $context ?: 'general',
            $message
        );

        if (!empty($data)) {
            $formatted_message .= ' | Data: ' . wp_json_encode($data);
        }

        // Escribir en error_log de WordPress
        error_log($formatted_message);

        // Si es un error crítico, también guardar en opción de WordPress para revisión
        if ($level === self::LEVEL_ERROR) {
            self::save_error_to_option($log_entry);
        }
    }

    /**
     * Log de error crítico
     * 
     * Método de conveniencia para registrar errores. Los errores siempre se registran
     * incluso si WP_DEBUG está desactivado, y se guardan en una opción de WordPress
     * para revisión posterior.
     * 
     * @param string $message Mensaje de error
     * @param string $context Contexto del error (use las constantes CONTEXT_*)
     * @param array $data Datos adicionales sobre el error
     * @return void
     * @since 1.0.0
     * @example
     * Alquipress_Logger::error('Error al actualizar estado', self::CONTEXT_AJAX, [
     *     'order_id' => 123,
     *     'exception' => $e->getMessage()
     * ]);
     */
    public static function error($message, $context = '', $data = [])
    {
        self::log($message, self::LEVEL_ERROR, $context, $data);
    }

    /**
     * Log de advertencia
     * 
     * Método de conveniencia para registrar advertencias sobre problemas potenciales
     * que no son críticos pero requieren atención.
     * 
     * @param string $message Mensaje de advertencia
     * @param string $context Contexto de la advertencia (use las constantes CONTEXT_*)
     * @param array $data Datos adicionales
     * @return void
     * @since 1.0.0
     * @example
     * Alquipress_Logger::warning('Cache miss en consulta frecuente', self::CONTEXT_CACHE, [
     *     'key' => 'recent_orders',
     *     'query_time' => 0.5
     * ]);
     */
    public static function warning($message, $context = '', $data = [])
    {
        self::log($message, self::LEVEL_WARNING, $context, $data);
    }

    /**
     * Log de información general
     * 
     * Método de conveniencia para registrar eventos informativos sobre el
     * funcionamiento normal del sistema.
     * 
     * @param string $message Mensaje informativo
     * @param string $context Contexto del evento (use las constantes CONTEXT_*)
     * @param array $data Datos adicionales
     * @return void
     * @since 1.0.0
     * @example
     * Alquipress_Logger::info('Módulo cargado correctamente', self::CONTEXT_MODULE, [
     *     'module' => 'pipeline-kanban'
     * ]);
     */
    public static function info($message, $context = '', $data = [])
    {
        self::log($message, self::LEVEL_INFO, $context, $data);
    }

    /**
     * Log de debug
     * 
     * Método de conveniencia para registrar información de debug detallada.
     * Solo se registra si WP_DEBUG está activado para evitar sobrecarga en producción.
     * 
     * @param string $message Mensaje de debug
     * @param string $context Contexto del debug (use las constantes CONTEXT_*)
     * @param array $data Datos adicionales para debugging
     * @return void
     * @since 1.0.0
     * @example
     * Alquipress_Logger::debug('Estado de pedido actualizado', self::CONTEXT_AJAX, [
     *     'order_id' => 123,
     *     'old_status' => 'pending',
     *     'new_status' => 'completed'
     * ]);
     */
    public static function debug($message, $context = '', $data = [])
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::log($message, self::LEVEL_DEBUG, $context, $data);
        }
    }

    /**
     * Guardar error crítico en opción de WordPress
     * 
     * @param array $log_entry Entrada de log
     */
    private static function save_error_to_option($log_entry)
    {
        $errors = get_option('alquipress_recent_errors', []);
        
        // Mantener solo los últimos 50 errores
        $errors[] = $log_entry;
        if (count($errors) > 50) {
            $errors = array_slice($errors, -50);
        }
        
        update_option('alquipress_recent_errors', $errors, false);
    }

    /**
     * Obtener errores recientes guardados
     * 
     * Retorna los últimos errores críticos guardados en la opción de WordPress.
     * Los errores se mantienen en orden cronológico (más recientes al final).
     * 
     * @param int $limit Número máximo de errores a retornar (por defecto: 10)
     * @return array Array de entradas de log con estructura: ['timestamp', 'level', 'context', 'message', 'data']
     * @since 1.0.0
     * @example
     * $errors = Alquipress_Logger::get_recent_errors(5);
     * foreach ($errors as $error) {
     *     echo $error['message'] . ' - ' . $error['timestamp'];
     * }
     */
    public static function get_recent_errors($limit = 10)
    {
        $errors = get_option('alquipress_recent_errors', []);
        return array_slice($errors, -$limit);
    }

    /**
     * Limpiar todos los errores guardados
     * 
     * Elimina la opción de WordPress que contiene los errores recientes.
     * Útil para limpiar logs antiguos o después de resolver problemas.
     * 
     * @return void
     * @since 1.0.0
     * @example
     * // Limpiar errores después de resolver un problema
     * Alquipress_Logger::clear_errors();
     */
    public static function clear_errors()
    {
        delete_option('alquipress_recent_errors');
    }

    /**
     * Ejecutar código con manejo de errores y logging automático
     * 
     * Envuelve la ejecución de un callback en un try-catch y registra automáticamente
     * cualquier excepción que ocurra. Si falla, retorna el valor por defecto especificado.
     * 
     * Útil para ejecutar código que podría fallar sin interrumpir el flujo principal.
     * 
     * @param callable $callback Función o método a ejecutar (puede ser closure, array, string)
     * @param string $context Contexto para el log en caso de error (use las constantes CONTEXT_*)
     * @param mixed $default_value Valor por defecto a retornar si la ejecución falla
     * @return mixed Resultado de la función o $default_value si ocurre una excepción
     * @since 1.0.0
     * @example
     * $result = Alquipress_Logger::safe_execute(
     *     function() {
     *         return wc_get_order(123)->get_status();
     *     },
     *     self::CONTEXT_QUERY,
     *     'unknown'
     * );
     * // Si falla, retorna 'unknown' y registra el error automáticamente
     */
    public static function safe_execute($callback, $context = '', $default_value = null)
    {
        try {
            return call_user_func($callback);
        } catch (Exception $e) {
            self::error(
                sprintf('Error ejecutando callback en contexto "%s": %s', $context, $e->getMessage()),
                $context,
                [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            );
            return $default_value;
        }
    }
}
