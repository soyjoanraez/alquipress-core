<?php
/**
 * Sistema de Rate Limiting para peticiones AJAX
 * Previene abuso de endpoints AJAX limitando requests por usuario/IP
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Rate_Limiter
{
    /**
     * Verificar si el usuario ha excedido el límite de peticiones
     * Usa locking para prevenir race conditions
     *
     * @param string $action Nombre de la acción AJAX
     * @param int $max_requests Máximo de peticiones permitidas
     * @param int $time_window Ventana de tiempo en segundos
     * @return bool True si está dentro del límite, False si lo excedió
     */
    public static function check_limit($action, $max_requests = 60, $time_window = 60)
    {
        $user_id = get_current_user_id();
        $ip = alquipress_get_client_ip();

        // Sanitizar action para prevenir injection
        $action = sanitize_key($action);

        // Crear clave única por usuario/IP + acción
        $transient_key = 'alquipress_rl_' . md5($user_id . '_' . $ip . '_' . $action);
        $lock_key = $transient_key . '_lock';

        // Intentar adquirir lock (máximo 5 intentos con 100ms de espera)
        $lock_acquired = false;
        for ($i = 0; $i < 5; $i++) {
            if (add_transient($lock_key, '1', 2)) { // Lock por 2 segundos
                $lock_acquired = true;
                break;
            }
            usleep(100000); // Esperar 100ms antes de reintentar
        }

        if (!$lock_acquired) {
            // No se pudo adquirir lock, denegar por seguridad
            error_log('ALQUIPRESS Rate Limit: Could not acquire lock for ' . $action);
            return false;
        }

        // Obtener contador actual (dentro de la sección crítica)
        $requests = get_transient($transient_key);

        if ($requests === false) {
            // Primera petición en esta ventana de tiempo
            set_transient($transient_key, 1, $time_window);
            delete_transient($lock_key); // Liberar lock
            return true;
        }

        if ($requests >= $max_requests) {
            // Límite excedido
            error_log(sprintf(
                'ALQUIPRESS Rate Limit: User %d (IP: %s) exceeded limit for action %s (%d/%d)',
                $user_id,
                $ip,
                $action,
                $requests,
                $max_requests
            ));
            delete_transient($lock_key); // Liberar lock
            return false;
        }

        // Incrementar contador
        set_transient($transient_key, $requests + 1, $time_window);
        delete_transient($lock_key); // Liberar lock
        return true;
    }

    /**
     * Resetear límite para una acción específica
     *
     * @param string $action Nombre de la acción AJAX
     * @return bool True si se eliminó el transient
     */
    public static function reset_limit($action)
    {
        $user_id = get_current_user_id();
        $ip = alquipress_get_client_ip();
        $transient_key = 'alquipress_rl_' . md5($user_id . '_' . $ip . '_' . $action);

        return delete_transient($transient_key);
    }

    /**
     * Obtener contador actual de requests
     *
     * @param string $action Nombre de la acción AJAX
     * @return int Número de requests realizados en la ventana actual
     */
    public static function get_current_count($action)
    {
        $user_id = get_current_user_id();
        $ip = alquipress_get_client_ip();
        $transient_key = 'alquipress_rl_' . md5($user_id . '_' . $ip . '_' . $action);

        $count = get_transient($transient_key);
        return $count !== false ? intval($count) : 0;
    }

    /**
     * Verificar y enviar error si excede límite
     *
     * @param string $action Nombre de la acción AJAX
     * @param int $max_requests Máximo de peticiones
     * @param int $time_window Ventana de tiempo
     */
    public static function check_and_exit($action, $max_requests = 60, $time_window = 60)
    {
        if (!self::check_limit($action, $max_requests, $time_window)) {
            wp_send_json_error([
                'message' => 'Demasiadas peticiones. Por favor, espera un momento antes de intentar de nuevo.',
                'retry_after' => $time_window
            ], 429);
        }
    }
}
