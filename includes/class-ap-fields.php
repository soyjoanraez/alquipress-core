<?php
/**
 * Capa de compatibilidad de campos (Ap_Fields)
 *
 * Proporciona acceso a metadatos de WordPress de forma agnóstica respecto a ACF.
 * Si ACF está activo, las funciones globales get_field()/update_field() las provee ACF.
 * Si ACF NO está activo, este archivo las define como wrappers de WP core meta.
 *
 * Contextos soportados en $object_id:
 *   - int / numeric string → postmeta  (get_post_meta)
 *   - "user_X"             → usermeta  (get_user_meta)
 *   - "term_X"             → termmeta  (get_term_meta)
 *   - "option"             → options   (get_option)
 *
 * Campos con tratamiento especial:
 *   - propietario_asignado  → ACF relationship max:1, devuelve int (no array)
 *   - owner_properties      → ACF relationship múltiple, devuelve array de int
 *   - distribucion_habitaciones / guest_documents → repeaters, ya serializados por WP
 *   - guest_preferences     → checkbox ACF, devuelve array
 *   - coordenadas_gps       → google_map, devuelve array ['lat','lng','address']
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ap_Fields
{
    /**
     * Lista de campos que son relationships de un solo elemento (devuelven int, no array).
     */
    private static array $single_relationships = [
        'propietario_asignado',
    ];

    /**
     * Lista de campos que son arrays (relationships múltiples, checkboxes, repeaters).
     * Para estos, get_post_meta/get_user_meta se llama con $single=true y el valor
     * ya es un array serializado. Se retorna como array (nunca string).
     */
    private static array $array_fields = [
        'owner_properties',
        'guest_preferences',
        'guest_documents',
        'distribucion_habitaciones',
        'coordenadas_gps',
        'galeria_fotos',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // API pública
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Obtener el valor de un campo.
     *
     * @param string     $field     Nombre del campo (meta_key).
     * @param int|string $object_id ID del objeto. Formatos: int, "user_X", "term_X", "option".
     * @return mixed
     */
    public static function get(string $field, $object_id)
    {
        $context = self::parse_context($object_id);

        switch ($context['type']) {
            case 'user':
                $value = get_user_meta($context['id'], $field, true);
                break;

            case 'term':
                $value = get_term_meta($context['id'], $field, true);
                break;

            case 'option':
                $value = get_option($field);
                break;

            default: // post
                $value = get_post_meta($context['id'], $field, true);
                break;
        }

        return self::normalize_value($field, $value);
    }

    /**
     * Guardar el valor de un campo.
     *
     * @param string     $field     Nombre del campo (meta_key).
     * @param int|string $object_id ID del objeto.
     * @param mixed      $value     Valor a guardar.
     */
    public static function set(string $field, $object_id, $value): void
    {
        $context = self::parse_context($object_id);

        // Para relationships single, envolver en array al guardar (compatibilidad ACF)
        if (in_array($field, self::$single_relationships, true) && !is_array($value)) {
            $value = [$value];
        }

        switch ($context['type']) {
            case 'user':
                update_user_meta($context['id'], $field, $value);
                break;

            case 'term':
                update_term_meta($context['id'], $field, $value);
                break;

            case 'option':
                update_option($field, $value);
                break;

            default: // post
                update_post_meta($context['id'], $field, $value);
                break;
        }
    }

    /**
     * Eliminar un campo.
     *
     * @param string     $field     Nombre del campo.
     * @param int|string $object_id ID del objeto.
     */
    public static function delete(string $field, $object_id): void
    {
        $context = self::parse_context($object_id);

        switch ($context['type']) {
            case 'user':
                delete_user_meta($context['id'], $field);
                break;

            case 'term':
                delete_term_meta($context['id'], $field);
                break;

            case 'option':
                delete_option($field);
                break;

            default:
                delete_post_meta($context['id'], $field);
                break;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Parsear $object_id y determinar el tipo de contexto.
     *
     * @return array{type: string, id: int}
     */
    private static function parse_context($object_id): array
    {
        if ($object_id === 'option' || $object_id === 'options') {
            return ['type' => 'option', 'id' => 0];
        }

        if (is_string($object_id) && strncmp($object_id, 'user_', 5) === 0) {
            return ['type' => 'user', 'id' => (int) substr($object_id, 5)];
        }

        if (is_string($object_id) && strncmp($object_id, 'term_', 5) === 0) {
            return ['type' => 'term', 'id' => (int) substr($object_id, 5)];
        }

        // Soportar "caracteristicas_X" (prefijo de taxonomía usado en el código)
        if (is_string($object_id) && preg_match('/^[a-z_]+_(\d+)$/', $object_id, $m)) {
            // Intentar detectar si es un término por su prefijo
            $id = (int) $m[1];
            if ($id > 0 && strncmp($object_id, 'post_', 5) !== 0) {
                // Asumir término si no parece post
                return ['type' => 'term', 'id' => $id];
            }
        }

        return ['type' => 'post', 'id' => (int) $object_id];
    }

    /**
     * Normalizar el valor según el tipo de campo.
     *
     * @param string $field Nombre del campo.
     * @param mixed  $value Valor crudo de la base de datos.
     * @return mixed
     */
    private static function normalize_value(string $field, $value)
    {
        // Desenvolver relationships de un solo elemento (ACF guarda ['42'], devolvemos 42)
        if (in_array($field, self::$single_relationships, true)) {
            if (is_array($value) && count($value) === 1) {
                return (int) reset($value);
            }
            if (is_array($value) && empty($value)) {
                return 0;
            }
            return $value ? (int) $value : 0;
        }

        // Campos que deben ser siempre arrays
        if (in_array($field, self::$array_fields, true)) {
            if (empty($value)) {
                return [];
            }
            // Si llegó como string serializado de PHP (legacy ACF)
            if (is_string($value)) {
                $unserialized = maybe_unserialize($value);
                if (is_array($unserialized)) {
                    return $unserialized;
                }
                // Intentar JSON (nuestro nuevo formato para guest_documents)
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            return is_array($value) ? $value : [];
        }

        return $value;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Funciones de compatibilidad global cuando ACF no está activo
// ──────────────────────────────────────────────────────────────────────────────

if (!function_exists('get_field')) {
    /**
     * Obtener un campo de ACF o, si ACF no está activo, del meta de WP.
     *
     * @param string     $field_name  Nombre del campo.
     * @param int|string $post_id     ID del objeto (post, user_X, term_X, option).
     * @param bool       $format_value Ignorado (compatibilidad de firma).
     * @return mixed
     */
    function get_field($field_name, $post_id = false, $format_value = true)
    {
        $object_id = $post_id ?: (int) get_the_ID();
        return Ap_Fields::get((string) $field_name, $object_id);
    }
}

if (!function_exists('update_field')) {
    /**
     * Guardar un campo de ACF o, si ACF no está activo, en el meta de WP.
     *
     * @param string     $field_name Nombre del campo.
     * @param mixed      $value      Valor a guardar.
     * @param int|string $post_id    ID del objeto.
     * @return bool
     */
    function update_field($field_name, $value, $post_id = false)
    {
        $object_id = $post_id ?: (int) get_the_ID();
        Ap_Fields::set((string) $field_name, $object_id, $value);
        return true;
    }
}

if (!function_exists('delete_field')) {
    /**
     * Eliminar un campo de ACF o, si ACF no está activo, del meta de WP.
     *
     * @param string     $field_name Nombre del campo.
     * @param int|string $post_id    ID del objeto.
     * @return bool
     */
    function delete_field($field_name, $post_id = false)
    {
        $object_id = $post_id ?: (int) get_the_ID();
        Ap_Fields::delete((string) $field_name, $object_id);
        return true;
    }
}

if (!function_exists('have_rows')) {
    /**
     * Compatibilidad básica de have_rows() para repeaters sin ACF.
     * Inicializa la iteración del repeater y devuelve true si hay filas.
     *
     * @param string     $field_name Nombre del campo repeater.
     * @param int|string $post_id    ID del objeto.
     * @return bool
     */
    function have_rows($field_name, $post_id = false)
    {
        $object_id = $post_id ?: (int) get_the_ID();
        $rows = Ap_Fields::get((string) $field_name, $object_id);
        if (!is_array($rows) || empty($rows)) {
            return false;
        }
        // Almacenar en global para iteración posterior (patrón mínimo)
        $GLOBALS['_ap_fields_loop'][$field_name] = $rows;
        $GLOBALS['_ap_fields_loop'][$field_name . '_index'] = 0;
        return true;
    }
}

if (!function_exists('the_row')) {
    /**
     * Avanzar al siguiente sub-campo del repeater.
     *
     * @param string|null $field_name Nombre del campo (opcional, usa el último).
     * @return array|false
     */
    function the_row($field_name = null)
    {
        if (!$field_name) {
            // Obtener el primer loop activo
            foreach ($GLOBALS['_ap_fields_loop'] ?? [] as $k => $v) {
                if (substr($k, -6) !== '_index' && is_array($v)) {
                    $field_name = $k;
                    break;
                }
            }
        }
        if (!$field_name) {
            return false;
        }
        $rows  = $GLOBALS['_ap_fields_loop'][$field_name] ?? [];
        $index = $GLOBALS['_ap_fields_loop'][$field_name . '_index'] ?? 0;
        if (!isset($rows[$index])) {
            return false;
        }
        $GLOBALS['_ap_fields_loop'][$field_name . '_current'] = $rows[$index];
        $GLOBALS['_ap_fields_loop'][$field_name . '_index']++;
        return $rows[$index];
    }
}

if (!function_exists('get_sub_field')) {
    /**
     * Obtener un sub-campo del repeater activo.
     *
     * @param string $sub_field_name Nombre del sub-campo.
     * @param bool   $format         Ignorado.
     * @return mixed
     */
    function get_sub_field($sub_field_name, $format = true)
    {
        foreach ($GLOBALS['_ap_fields_loop'] ?? [] as $k => $v) {
            if (substr($k, -8) === '_current' && is_array($v)) {
                return $v[$sub_field_name] ?? null;
            }
        }
        return null;
    }
}

if (!function_exists('acf_add_local_field_group')) {
    /** Stub vacío para cuando se llame a acf_add_local_field_group sin ACF. */
    function acf_add_local_field_group(array $field_group): void {}
}

if (!function_exists('acf_is_local_field_group')) {
    /** Stub vacío. */
    function acf_is_local_field_group(string $key): bool { return false; }
}
