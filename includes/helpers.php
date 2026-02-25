<?php
/**
 * Helper Functions para ALQUIPRESS
 * Funciones auxiliares y validación de dependencias
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Verificar si ACF (el plugin externo) está activo.
 * Los campos siempre están disponibles a través del shim Ap_Fields,
 * por lo que este check solo es necesario para características exclusivas de ACF.
 *
 * @return bool True si el plugin ACF está instalado y activo.
 */
function alquipress_is_acf_active(): bool
{
    return class_exists('ACF');
}

/**
 * Verificar si los campos de Alquipress están disponibles (siempre true).
 *
 * @return bool
 */
function alquipress_has_field_support(): bool
{
    return true;
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

    // ACF ya no es una dependencia obligatoria (usamos el shim Ap_Fields)

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
 * Maneja correctamente proxies y múltiples IPs en X-Forwarded-For
 *
 * @return string IP sanitizada
 */
function alquipress_get_client_ip()
{
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // X-Forwarded-For puede contener múltiples IPs: "client, proxy1, proxy2"
        // Tomamos la primera (IP del cliente real)
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Validar que sea una IP válida
    $ip = sanitize_text_field($ip);
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0'; // IP por defecto si la validación falla
    }

    return $ip;
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

    if (isset($_GET['post_type']) && sanitize_key($_GET['post_type']) === $post_type) {
        return true;
    }

    return false;
}

/**
 * Opciones oficiales SES Hospedajes: tipo de documento.
 *
 * @return array
 */
function alquipress_ses_document_type_choices()
{
    return [
        'NIF' => 'DNI/NIF',
        'NIE' => 'NIE',
        'PAS' => 'Pasaporte',
        'OTRO' => 'Otro documento',
    ];
}

/**
 * Opciones oficiales SES Hospedajes: rol de persona.
 *
 * @return array
 */
function alquipress_ses_role_choices()
{
    return [
        'VI' => 'Viajero',
        'TI' => 'Titular contrato',
        'CP' => 'Conductor principal',
        'CS' => 'Conductor secundario',
    ];
}

/**
 * Opciones oficiales SES Hospedajes: tipo de pago.
 *
 * @return array
 */
function alquipress_ses_payment_type_choices()
{
    return [
        'DESTI' => 'Pago en destino',
        'TARJT' => 'Tarjeta',
        'PLATF' => 'Plataforma',
        'TRANS' => 'Transferencia',
        'EFECT' => 'Efectivo',
        'MOVIL' => 'Pago móvil',
        'TREG' => 'Tarjeta regalo',
        'OTRO' => 'Otro',
    ];
}

/**
 * Normalizar tipo de documento a códigos SES.
 *
 * @param string $value Valor libre o legacy.
 * @return string Código SES (NIF/NIE/PAS/OTRO).
 */
function alquipress_ses_normalize_document_type($value)
{
    $value = strtoupper(trim((string) $value));

    $legacy_map = [
        'DNI' => 'NIF',
        'NIF' => 'NIF',
        'NIE' => 'NIE',
        'PASAPORTE' => 'PAS',
        'PAS' => 'PAS',
        'OTRO' => 'OTRO',
    ];

    if (isset($legacy_map[$value])) {
        return $legacy_map[$value];
    }

    $choices = alquipress_ses_document_type_choices();
    return isset($choices[$value]) ? $value : 'OTRO';
}

/**
 * Obtener etiqueta humana a partir de código SES de documento.
 *
 * @param string $code Código SES.
 * @return string
 */
function alquipress_ses_get_document_label($code)
{
    $code = alquipress_ses_normalize_document_type($code);
    $choices = alquipress_ses_document_type_choices();

    return isset($choices[$code]) ? $choices[$code] : $choices['OTRO'];
}

/**
 * Normalizar rol de persona a código SES.
 *
 * @param string $value Valor libre.
 * @return string Código SES (por defecto VI).
 */
function alquipress_ses_normalize_role($value)
{
    $value = strtoupper(trim((string) $value));
    $choices = alquipress_ses_role_choices();

    return isset($choices[$value]) ? $value : 'VI';
}

/**
 * Normalizar tipo de pago a código SES.
 *
 * @param string $value Valor libre o legacy.
 * @return string Código SES.
 */
function alquipress_ses_normalize_payment_type($value)
{
    $value = strtoupper(trim((string) $value));

    $legacy_map = [
        'DESTINO' => 'DESTI',
        'DESTI' => 'DESTI',
        'TARJETA' => 'TARJT',
        'TARJT' => 'TARJT',
        'PLATAFORMA' => 'PLATF',
        'PLATF' => 'PLATF',
        'TRANSFERENCIA' => 'TRANS',
        'TRANS' => 'TRANS',
        'EFECTIVO' => 'EFECT',
        'EFECT' => 'EFECT',
        'MOVIL' => 'MOVIL',
        'TREG' => 'TREG',
        'OTRO' => 'OTRO',
    ];

    if (isset($legacy_map[$value])) {
        return $legacy_map[$value];
    }

    $choices = alquipress_ses_payment_type_choices();
    return isset($choices[$value]) ? $value : 'OTRO';
}

/**
 * Estimar tipo de pago SES a partir del método de pago del pedido.
 *
 * @param WC_Order|mixed $order Pedido WooCommerce.
 * @return string
 */
function alquipress_ses_guess_payment_type_from_order($order)
{
    if (!is_object($order) || !method_exists($order, 'get_payment_method')) {
        return 'OTRO';
    }

    $gateway = (string) $order->get_payment_method();
    $title = strtoupper((string) $order->get_payment_method_title());

    $platform_gateways = ['stripe', 'paypal', 'redsys', 'bacs', 'bookings_gateway'];
    if (in_array($gateway, $platform_gateways, true)) {
        return 'PLATF';
    }

    if ($gateway === 'cod') {
        return 'DESTI';
    }

    if (strpos($title, 'TRANSFER') !== false) {
        return 'TRANS';
    }

    if (strpos($title, 'TARJET') !== false || strpos($title, 'CARD') !== false) {
        return 'TARJT';
    }

    if (strpos($title, 'EFECT') !== false || strpos($title, 'CASH') !== false) {
        return 'EFECT';
    }

    return 'OTRO';
}

/**
 * Validar fecha ISO (YYYY-MM-DD).
 *
 * @param string $date Fecha.
 * @return bool
 */
function alquipress_is_iso_date($date)
{
    $date = trim((string) $date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    $parts = explode('-', $date);
    return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
}

/**
 * Registrar rol de WordPress para propietarios (portal frontend).
 * Solo lectura; redirección al panel tras login.
 */
function alquipress_add_owner_role()
{
    if (get_role('propietario_alquipress')) {
        return;
    }
    add_role(
        'propietario_alquipress',
        __('Propietario', 'alquipress'),
        ['read' => true]
    );
}
