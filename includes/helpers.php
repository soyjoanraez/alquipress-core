<?php
/**
 * Helper Functions para ALQUIPRESS
 * Funciones auxiliares y validación de dependencias
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Verificar si ACF está activo
 *
 * @return bool True si ACF está disponible
 */
function alquipress_is_acf_active()
{
    return class_exists('ACF') || function_exists('get_field');
}

/**
 * Verificar si WooCommerce está activo
 *
 * @return bool True si WooCommerce está disponible
 */
function alquipress_is_woocommerce_active()
{
    return class_exists('WooCommerce');
}

/**
 * Mostrar aviso de dependencia faltante
 *
 * @param string $plugin_name Nombre del plugin requerido
 */
function alquipress_missing_dependency_notice($plugin_name)
{
    ?>
    <div class="notice notice-error">
        <p>
            <strong>ALQUIPRESS:</strong>
            <?php
            printf(
                esc_html__('Requiere que %s esté instalado y activado.', 'alquipress-core'),
                '<strong>' . esc_html($plugin_name) . '</strong>'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Verificar dependencias críticas
 *
 * @return bool True si todas las dependencias están disponibles
 */
function alquipress_check_dependencies()
{
    $missing = [];

    if (!alquipress_is_acf_active()) {
        $missing[] = 'Advanced Custom Fields PRO';
    }

    if (!alquipress_is_woocommerce_active()) {
        $missing[] = 'WooCommerce';
    }

    if (!empty($missing)) {
        add_action('admin_notices', function () use ($missing) {
            foreach ($missing as $plugin) {
                alquipress_missing_dependency_notice($plugin);
            }
        });
        return false;
    }

    return true;
}

/**
 * Obtener nombre del usuario actual
 *
 * @return string Nombre del usuario o 'Guest'
 */
function alquipress_get_current_user_name()
{
    $user = wp_get_current_user();
    return $user->exists() ? $user->display_name : 'Guest';
}

/**
 * Sanitizar array de IDs
 *
 * @param array $ids Array de IDs a sanitizar
 * @return array Array de IDs enteros válidos
 */
function alquipress_sanitize_ids($ids)
{
    if (!is_array($ids)) {
        return [];
    }

    return array_filter(array_map('absint', $ids));
}

/**
 * Verificar si un módulo está activo
 *
 * @param string $module_id ID del módulo
 * @return bool True si el módulo está activo
 */
function alquipress_is_module_active($module_id)
{
    $active_modules = get_option('alquipress_modules', []);
    return isset($active_modules[$module_id]) && $active_modules[$module_id];
}

/**
 * Logging condicional según WP_DEBUG
 *
 * @param string $message Mensaje a registrar
 * @param array $context Contexto adicional
 */
function alquipress_log($message, $context = [])
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    $formatted = '[ALQUIPRESS] ' . $message;

    if (!empty($context)) {
        $formatted .= ' | Context: ' . wp_json_encode($context);
    }

    error_log($formatted);
}

/**
 * Obtener IP del cliente de forma segura
 *
 * @return string IP sanitizada
 */
function alquipress_get_client_ip()
{
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return sanitize_text_field($ip);
}

/**
 * Formatear precio con símbolo de moneda
 *
 * @param float $amount Cantidad
 * @return string Precio formateado
 */
function alquipress_format_price($amount)
{
    if (function_exists('wc_price')) {
        return wc_price($amount);
    }

    return number_format($amount, 2, ',', '.') . ' €';
}

/**
 * Verificar si estamos en una página de edición de un post type específico
 *
 * @param string $post_type Post type a verificar
 * @return bool True si estamos editando ese post type
 */
function alquipress_is_editing_post_type($post_type)
{
    global $pagenow, $post;

    if (!in_array($pagenow, ['post.php', 'post-new.php'])) {
        return false;
    }

    if ($post && $post->post_type === $post_type) {
        return true;
    }

    if (isset($_GET['post_type']) && $_GET['post_type'] === $post_type) {
        return true;
    }

    return false;
}
