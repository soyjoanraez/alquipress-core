<?php
/**
 * Clase Helper para Propiedades
 * Centraliza métodos comunes para trabajar con propiedades (productos) y órdenes
 * 
 * @package Alquipress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Property_Helper
{
    /**
     * Cache interno para evitar queries repetidas
     * 
     * @var array
     */
    private static $cache = [];

    /**
     * Obtener el nombre de la propiedad desde una orden
     * 
     * Extrae el nombre del primer producto asociado a la orden.
     * Utiliza cache interno para evitar queries repetidas.
     * 
     * @param WC_Order|int $order Objeto WC_Order o ID de orden
     * @return string Nombre de la propiedad o '-' si no se encuentra
     * @since 1.0.0
     * @example
     * $order = wc_get_order(123);
     * $name = Alquipress_Property_Helper::get_order_property_name($order);
     * // Retorna: "Apartamento Centro Denia"
     */
    public static function get_order_property_name($order)
    {
        if (!is_object($order) || !method_exists($order, 'get_items')) {
            return '-';
        }

        $cache_key = 'property_name_order_' . $order->get_id();
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        foreach ($order->get_items() as $item) {
            $product = is_object($item) && method_exists($item, 'get_product') 
                ? $item->get_product() 
                : null;
            
            if ($product) {
                $name = $product->get_name();
                self::$cache[$cache_key] = $name;
                return $name;
            }
        }

        self::$cache[$cache_key] = '-';
        return '-';
    }

    /**
     * Obtener la ubicación (población) de una propiedad
     * 
     * Obtiene los términos de la taxonomía 'poblacion' asociados al producto.
     * Si hay múltiples poblaciones, las retorna separadas por coma.
     * 
     * @param int $product_id ID del producto/propiedad
     * @return string Nombres de las poblaciones separadas por coma, o string vacío si no hay ubicación
     * @since 1.0.0
     * @example
     * $location = Alquipress_Property_Helper::get_product_location(456);
     * // Retorna: "Denia, Javea" o "" si no tiene ubicación
     */
    public static function get_product_location($product_id)
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return '';
        }

        $cache_key = 'location_product_' . $product_id;
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        $terms = get_the_terms($product_id, 'poblacion');
        if (is_array($terms) && !empty($terms)) {
            $names = wp_list_pluck($terms, 'name');
            $location = implode(', ', $names);
            self::$cache[$cache_key] = $location;
            return $location;
        }

        self::$cache[$cache_key] = '';
        return '';
    }

    /**
     * Obtener el número de habitaciones (camas) de una propiedad
     * 
     * Cuenta el número de filas en el campo ACF 'distribucion_habitaciones'.
     * Cada fila representa una habitación.
     * 
     * @param int $product_id ID del producto/propiedad
     * @return int|null Número de habitaciones o null si no está definido
     * @since 1.0.0
     * @example
     * $beds = Alquipress_Property_Helper::get_product_beds(456);
     * // Retorna: 3 o null si no tiene habitaciones definidas
     */
    public static function get_product_beds($product_id)
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return null;
        }

        $cache_key = 'beds_product_' . $product_id;
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        $rows = get_field('distribucion_habitaciones', $product_id);
        if (is_array($rows)) {
            $count = count($rows);
            self::$cache[$cache_key] = $count;
            return $count;
        }

        self::$cache[$cache_key] = null;
        return null;
    }

    /**
     * Obtener el número de baños de una propiedad
     * 
     * Obtiene el valor del campo ACF 'numero_banos' y lo valida como número positivo.
     * 
     * @param int $product_id ID del producto/propiedad
     * @return int|null Número de baños o null si no está definido o es inválido
     * @since 1.0.0
     * @example
     * $baths = Alquipress_Property_Helper::get_product_baths(456);
     * // Retorna: 2 o null si no tiene baños definidos
     */
    public static function get_product_baths($product_id)
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return null;
        }

        $cache_key = 'baths_product_' . $product_id;
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        $n = get_field('numero_banos', $product_id);
        if (is_numeric($n) && (int) $n > 0) {
            $count = (int) $n;
            self::$cache[$cache_key] = $count;
            return $count;
        }

        self::$cache[$cache_key] = null;
        return null;
    }

    /**
     * Obtener el número de huéspedes de una propiedad
     * 
     * Intenta obtener el número de huéspedes desde múltiples fuentes:
     * 1. Campo ACF 'plazas'
     * 2. Campo ACF 'capacidad' (fallback)
     * 3. WooCommerce Bookings get_max_persons() (último fallback)
     * 
     * @param int $product_id ID del producto/propiedad
     * @return int|null Número de huéspedes o null si no está definido en ninguna fuente
     * @since 1.0.0
     * @example
     * $guests = Alquipress_Property_Helper::get_product_guests(456);
     * // Retorna: 6 o null si no tiene capacidad definida
     */
    public static function get_product_guests($product_id)
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return null;
        }

        $cache_key = 'guests_product_' . $product_id;
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        // Intentar obtener desde ACF campo 'plazas'
        $n = get_field('plazas', $product_id);
        if (is_numeric($n) && (int) $n > 0) {
            $count = (int) $n;
            self::$cache[$cache_key] = $count;
            return $count;
        }

        // Fallback: intentar desde ACF campo 'capacidad'
        $n = get_field('capacidad', $product_id);
        if (is_numeric($n) && (int) $n > 0) {
            $count = (int) $n;
            self::$cache[$cache_key] = $count;
            return $count;
        }

        // Último fallback: WooCommerce Bookings
        if (function_exists('wc_get_product')) {
            $product = wc_get_product($product_id);
            if ($product && method_exists($product, 'get_max_persons')) {
                $guests = $product->get_max_persons();
                if ($guests > 0) {
                    self::$cache[$cache_key] = $guests;
                    return $guests;
                }
            }
        }

        self::$cache[$cache_key] = null;
        return null;
    }

    /**
     * Limpiar el cache interno
     * 
     * Útil después de actualizar propiedades u órdenes para forzar la recarga de datos.
     * Si se especifica un product_id, solo elimina las entradas relacionadas con ese producto.
     * Si se pasa null, limpia todo el cache.
     * 
     * @param int|null $product_id ID del producto específico, o null para limpiar todo el cache
     * @return void
     * @since 1.0.0
     * @example
     * // Limpiar cache de un producto específico
     * Alquipress_Property_Helper::clear_cache(456);
     * 
     * // Limpiar todo el cache
     * Alquipress_Property_Helper::clear_cache();
     */
    public static function clear_cache($product_id = null)
    {
        if ($product_id !== null) {
            $product_id = (int) $product_id;
            // Eliminar todas las entradas relacionadas con este producto
            foreach (self::$cache as $key => $value) {
                if (strpos($key, '_product_' . $product_id) !== false || 
                    strpos($key, '_order_') !== false) {
                    unset(self::$cache[$key]);
                }
            }
        } else {
            self::$cache = [];
        }
    }
}
