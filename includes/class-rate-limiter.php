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

        // Crear clave única por usuario/IP + acción
        $transient_key = 'alquipress_rl_' . md5($user_id . '_' . $ip . '_' . $action);

        // Obtener contador actual
        $requests = get_transient($transient_key);

        if ($requests === false) {
            // Primera petición en esta ventana de tiempo
            set_transient($transient_key, 1, $time_window);
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
            return false;
        }

        // Incrementar contador
        set_transient($transient_key, $requests + 1, $time_window);
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
