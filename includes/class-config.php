<?php
/**
 * Clase de Configuración Centralizada para Alquipress
 * Centraliza constantes, límites por defecto y métodos helper
 * 
 * @package Alquipress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Config
{
    /**
     * Prefijo para todas las claves de caché
     * 
     * @var string
     * @since 1.0.0
     */
    const CACHE_PREFIX = 'alquipress_';

    /**
     * Duración por defecto del caché (en segundos)
     * 
     * @var int Equivale a 1 hora (HOUR_IN_SECONDS)
     * @since 1.0.0
     */
    const CACHE_DURATION_DEFAULT = HOUR_IN_SECONDS;

    /**
     * Duración del caché para dashboard (5 minutos)
     * 
     * @var int Equivale a 5 minutos
     * @since 1.0.0
     */
    const CACHE_DURATION_DASHBOARD = 5 * MINUTE_IN_SECONDS;

    /**
     * Duración del caché para informes (1 hora)
     * 
     * @var int Equivale a 1 hora
     * @since 1.0.0
     */
    const CACHE_DURATION_REPORTS = HOUR_IN_SECONDS;

    /**
     * Duración del caché para propiedades (30 minutos)
     * 
     * @var int Equivale a 30 minutos
     * @since 1.0.0
     */
    const CACHE_DURATION_PROPERTIES = 30 * MINUTE_IN_SECONDS;

    /**
     * Límite por defecto para listados generales
     * 
     * @var int
     * @since 1.0.0
     */
    const DEFAULT_LIMIT = 5;

    /**
     * Límite por defecto para dashboard widgets
     * 
     * @var int
     * @since 1.0.0
     */
    const DEFAULT_DASHBOARD_LIMIT = 5;

    /**
     * Límite por defecto para listados completos
     * 
     * @var int
     * @since 1.0.0
     */
    const DEFAULT_LIST_LIMIT = 50;

    /**
     * Grupo de caché para informes
     * 
     * @var string
     * @since 1.0.0
     */
    const CACHE_GROUP_REPORTS = 'reports';
    
    /**
     * Grupo de caché para dashboard
     * 
     * @var string
     * @since 1.0.0
     */
    const CACHE_GROUP_DASHBOARD = 'dashboard';
    
    /**
     * Grupo de caché para propiedades
     * 
     * @var string
     * @since 1.0.0
     */
    const CACHE_GROUP_PROPERTIES = 'properties';
    
    /**
     * Grupo de caché para órdenes
     * 
     * @var string
     * @since 1.0.0
     */
    const CACHE_GROUP_ORDERS = 'orders';

    /**
     * Generar clave de caché con prefijo
     * 
     * Prefija la clave proporcionada con CACHE_PREFIX para evitar conflictos
     * con otras claves de caché en WordPress.
     * 
     * @param string $key Clave base sin prefijo
     * @return string Clave completa con prefijo 'alquipress_'
     * @since 1.0.0
     * @example
     * $key = Alquipress_Config::get_cache_key('recent_orders');
     * // Retorna: "alquipress_recent_orders"
     */
    public static function get_cache_key($key)
    {
        return self::CACHE_PREFIX . $key;
    }

    /**
     * Generar clave de caché con sufijo de fecha
     * 
     * Útil para crear claves de caché que varían por fecha, como datos diarios
     * del dashboard o informes por fecha específica.
     * 
     * @param string $key Clave base sin prefijo
     * @param string|null $date Fecha en formato Y-m-d, o null para usar la fecha actual
     * @return string Clave completa con prefijo y fecha (ej: "alquipress_reports_2026-02-05")
     * @since 1.0.0
     * @example
     * $key = Alquipress_Config::get_cache_key_with_date('daily_stats', '2026-02-05');
     * // Retorna: "alquipress_daily_stats_2026-02-05"
     * 
     * $key = Alquipress_Config::get_cache_key_with_date('daily_stats');
     * // Retorna: "alquipress_daily_stats_2026-02-05" (fecha actual)
     */
    public static function get_cache_key_with_date($key, $date = null)
    {
        if ($date === null) {
            $date = current_time('Y-m-d');
        }
        return self::CACHE_PREFIX . $key . '_' . $date;
    }

    /**
     * Obtener duración de caché por grupo
     * 
     * Retorna la duración configurada para un grupo específico de caché.
     * Si el grupo no existe, retorna la duración por defecto.
     * 
     * @param string $group Grupo de caché ('default', 'dashboard', 'reports', 'properties')
     * @return int Duración en segundos
     * @since 1.0.0
     * @example
     * $duration = Alquipress_Config::get_cache_duration('dashboard');
     * // Retorna: 300 (5 minutos)
     * 
     * $duration = Alquipress_Config::get_cache_duration('invalid');
     * // Retorna: 3600 (duración por defecto: 1 hora)
     */
    public static function get_cache_duration($group = 'default')
    {
        $durations = [
            'default' => self::CACHE_DURATION_DEFAULT,
            'dashboard' => self::CACHE_DURATION_DASHBOARD,
            'reports' => self::CACHE_DURATION_REPORTS,
            'properties' => self::CACHE_DURATION_PROPERTIES,
        ];

        return isset($durations[$group]) ? $durations[$group] : self::CACHE_DURATION_DEFAULT;
    }

    /**
     * Obtener límite por defecto según contexto
     * 
     * Retorna el límite de registros recomendado para un contexto específico.
     * Útil para mantener consistencia en listados y widgets.
     * 
     * @param string $context Contexto: 'dashboard' (5), 'list' (50), 'default' (5)
     * @return int Límite de registros
     * @since 1.0.0
     * @example
     * $limit = Alquipress_Config::get_default_limit('dashboard');
     * // Retorna: 5
     * 
     * $limit = Alquipress_Config::get_default_limit('list');
     * // Retorna: 50
     */
    public static function get_default_limit($context = 'default')
    {
        $limits = [
            'default' => self::DEFAULT_LIMIT,
            'dashboard' => self::DEFAULT_DASHBOARD_LIMIT,
            'list' => self::DEFAULT_LIST_LIMIT,
        ];

        return isset($limits[$context]) ? $limits[$context] : self::DEFAULT_LIMIT;
    }

    /**
     * Validar que un grupo de caché es válido
     * 
     * Verifica si el grupo proporcionado está en la lista de grupos válidos
     * definidos como constantes de clase.
     * 
     * @param string $group Grupo a validar
     * @return bool True si el grupo es válido, false en caso contrario
     * @since 1.0.0
     * @example
     * if (Alquipress_Config::is_valid_cache_group('reports')) {
     *     // El grupo es válido
     * }
     * 
     * if (!Alquipress_Config::is_valid_cache_group('invalid')) {
     *     // El grupo no es válido
     * }
     */
    public static function is_valid_cache_group($group)
    {
        $valid_groups = [
            self::CACHE_GROUP_REPORTS,
            self::CACHE_GROUP_DASHBOARD,
            self::CACHE_GROUP_PROPERTIES,
            self::CACHE_GROUP_ORDERS,
        ];

        return in_array($group, $valid_groups, true);
    }
}
