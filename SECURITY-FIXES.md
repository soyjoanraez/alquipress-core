# 🔒 ALQUIPRESS CORE - Guía de Correcciones de Seguridad

**Fecha:** 2026-01-24
**Versión del Plugin:** 1.0.0
**Estado:** Revisión de seguridad completa

---

## 📌 ÍNDICE

1. [Correcciones Críticas](#correcciones-críticas)
2. [Correcciones de Alta Prioridad](#correcciones-de-alta-prioridad)
3. [Correcciones de Prioridad Media](#correcciones-de-prioridad-media)
4. [Mejoras de Calidad de Código](#mejoras-de-calidad-de-código)
5. [Plan de Implementación](#plan-de-implementación)

---

## 🚨 CORRECCIONES CRÍTICAS

### 1. XSS en Frontend Filters - Validación de Términos de Taxonomía

**Archivo:** `includes/class-frontend-filters.php`
**Líneas:** 69-71
**Severidad:** CRÍTICA
**CVE Potencial:** XSS vía Query String

#### Código Actual (INSEGURO):
```php
foreach ($taxonomies as $tax) {
    if (isset($_GET[$tax]) && !empty($_GET[$tax])) {
        $raw = wp_unslash($_GET[$tax]);
        $terms = array_filter(array_unique(array_map('sanitize_title', explode(',', $raw))));
        if (!empty($terms)) {
            $tax_query[] = [
                'taxonomy' => $tax,
                'field' => 'slug',
                'terms' => $terms,
                'operator' => ($tax === 'caracteristicas') ? 'AND' : 'IN',
            ];
        }
    }
}
```

#### Código Corregido (SEGURO):
```php
foreach ($taxonomies as $tax) {
    if (isset($_GET[$tax]) && !empty($_GET[$tax])) {
        $raw = wp_unslash($_GET[$tax]);
        $terms = array_filter(array_unique(array_map('sanitize_title', explode(',', $raw))));

        // ✅ VALIDAR que los términos existen en la taxonomía
        $valid_terms = [];
        foreach ($terms as $term_slug) {
            $term = get_term_by('slug', $term_slug, $tax);
            if ($term && !is_wp_error($term)) {
                $valid_terms[] = $term_slug;
            }
        }

        if (!empty($valid_terms)) {
            $tax_query[] = [
                'taxonomy' => $tax,
                'field' => 'slug',
                'terms' => $valid_terms,
                'operator' => ($tax === 'caracteristicas') ? 'AND' : 'IN',
            ];
        }
    }
}
```

**Explicación:**
Se valida que cada término slug exista realmente en la base de datos antes de incluirlo en la query. Esto previene inyección de términos maliciosos.

---

### 2. Código Duplicado en Module Manager

**Archivo:** `includes/class-module-manager.php`
**Líneas:** 167-183 y 189-203
**Severidad:** CRÍTICA
**Problema:** Lógica duplicada que puede causar inconsistencias

#### Código Actual (PROBLEMÁTICO):
```php
public function handle_form_submit()
{
    if (isset($_POST['alquipress_save_modules']) && check_admin_referer('alquipress_modules_nonce')) {
        $new_modules = [];
        foreach ($this->modules as $id => $module) {
            $new_modules[$id] = isset($_POST['modules'][$id]);
        }
        update_option('alquipress_modules', $new_modules);
        $this->active_modules = $new_modules;
        // ...
    }
}

public function render_settings_page()
{
    // ❌ DUPLICADO: Misma lógica
    if (isset($_POST['alquipress_save_modules'])) {
        check_admin_referer('alquipress_modules_nonce');
        $new_modules = [];
        foreach ($this->modules as $id => $module) {
            $new_modules[$id] = isset($_POST['modules'][$id]);
        }
        update_option('alquipress_modules', $new_modules);
        // ...
    }
}
```

#### Código Corregido (REFACTORIZADO):
```php
public function handle_form_submit()
{
    if (isset($_POST['alquipress_save_modules']) && check_admin_referer('alquipress_modules_nonce')) {
        $new_modules = $this->process_module_form();
        update_option('alquipress_modules', $new_modules);
        $this->active_modules = $new_modules;

        add_settings_error(
            'alquipress_messages',
            'alquipress_message',
            '✓ Módulos actualizados correctamente.',
            'success'
        );
    }
}

private function process_module_form()
{
    $new_modules = [];
    foreach ($this->modules as $id => $module) {
        $new_modules[$id] = isset($_POST['modules'][$id]);
    }
    return $new_modules;
}

public function render_settings_page()
{
    // ✅ Ya no procesa el formulario, solo renderiza
    settings_errors('alquipress_messages');
    require_once ALQUIPRESS_PATH . 'includes/admin/settings-page.php';
}
```

**Explicación:**
Se extrae la lógica común a un método privado `process_module_form()` para evitar duplicación y facilitar mantenimiento.

---

### 3. Validación de Tipos en SQL Query

**Archivo:** `includes/class-performance-optimizer.php`
**Líneas:** 128-148
**Severidad:** CRÍTICA
**Problema:** CAST de meta_value sin validación previa

#### Código Actual (RIESGOSO):
```php
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT
        pm_customer.meta_value as customer_id,
        COUNT(DISTINCT p.ID) as order_count,
        SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_spent,
        MAX(p.post_date) as last_order_date
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = '_customer_user'
    INNER JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
    WHERE p.post_type = 'shop_order'
    AND p.post_status IN ('wc-completed', 'wc-in-progress', 'wc-checkout-review')
    AND p.post_date >= %s
    AND p.post_date <= %s
    AND CAST(pm_customer.meta_value AS UNSIGNED) > 0
    GROUP BY customer_id
    ORDER BY total_spent DESC
    LIMIT %d",
    $start_date,
    $end_date,
    $limit
));
```

#### Código Corregido (SEGURO):
```php
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT
        pm_customer.meta_value as customer_id,
        COUNT(DISTINCT p.ID) as order_count,
        SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_spent,
        MAX(p.post_date) as last_order_date
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm_customer
        ON p.ID = pm_customer.post_id
        AND pm_customer.meta_key = '_customer_user'
    INNER JOIN {$wpdb->postmeta} pm_total
        ON p.ID = pm_total.post_id
        AND pm_total.meta_key = '_order_total'
    WHERE p.post_type = 'shop_order'
    AND p.post_status IN ('wc-completed', 'wc-in-progress', 'wc-checkout-review')
    AND p.post_date >= %s
    AND p.post_date <= %s
    AND pm_customer.meta_value REGEXP '^[0-9]+$'
    AND CAST(pm_customer.meta_value AS UNSIGNED) > 0
    AND pm_total.meta_value REGEXP '^[0-9]+\.?[0-9]*$'
    GROUP BY customer_id
    ORDER BY total_spent DESC
    LIMIT %d",
    $start_date,
    $end_date,
    $limit
));
```

**Explicación:**
Se agregan validaciones REGEXP antes del CAST para asegurar que los valores sean numéricos. Esto previene errores de conversión y posibles inyecciones SQL indirectas.

---

### 4. Falta de Verificación de Capacidades en AJAX

**Archivos:** Todos los módulos con AJAX handlers
**Severidad:** CRÍTICA
**Problema:** No se verifica permisos de usuario en endpoints AJAX

#### Ejemplo: Advanced Reports AJAX Handler

**Crear archivo:** `includes/modules/advanced-reports/ajax-handlers.php`

```php
<?php
/**
 * AJAX Handlers para Advanced Reports
 */

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_alquipress_get_report_data', 'alquipress_handle_report_ajax');

function alquipress_handle_report_ajax() {
    // ✅ 1. Verificar nonce
    check_ajax_referer('alquipress_reports_nonce', 'nonce');

    // ✅ 2. Verificar capacidades del usuario
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'No tienes permisos para acceder a estos datos.'
        ], 403);
    }

    // ✅ 3. Validar y sanitizar inputs
    $report_type = isset($_POST['report_type']) ? sanitize_key($_POST['report_type']) : '';
    $year = isset($_POST['year']) ? absint($_POST['year']) : date('Y');

    // Validar report_type
    $allowed_reports = [
        'overview',
        'revenue_monthly',
        'revenue_season',
        'occupancy_monthly',
        'occupancy_comparison',
        'top_clients',
        'clients_rating',
        'top_properties',
        'properties_comparison'
    ];

    if (!in_array($report_type, $allowed_reports)) {
        wp_send_json_error([
            'message' => 'Tipo de reporte inválido.'
        ], 400);
    }

    // Validar año
    if ($year < 2000 || $year > 2100) {
        wp_send_json_error([
            'message' => 'Año inválido.'
        ], 400);
    }

    // ✅ 4. Procesar la solicitud
    try {
        $data = alquipress_get_report_data($report_type, $year);
        wp_send_json_success($data);
    } catch (Exception $e) {
        error_log('ALQUIPRESS Reports Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'Error al generar el reporte.'
        ], 500);
    }
}

function alquipress_get_report_data($type, $year) {
    switch ($type) {
        case 'overview':
            return alquipress_get_overview_stats($year);
        case 'revenue_monthly':
            return Alquipress_Performance_Optimizer::get_cached_monthly_revenue($year);
        case 'top_clients':
            return Alquipress_Performance_Optimizer::get_cached_top_clients($year);
        case 'top_properties':
            return Alquipress_Performance_Optimizer::get_cached_top_properties($year);
        // ... otros casos
        default:
            throw new Exception('Tipo de reporte no implementado: ' . $type);
    }
}
```

**Agregar en:** `includes/modules/advanced-reports/advanced-reports.php`

```php
// Al final del archivo, agregar:
require_once __DIR__ . '/ajax-handlers.php';
```

---

## ⚠️ CORRECCIONES DE ALTA PRIORIDAD

### 5. Logging Server-Side para Acciones Sensibles

**Archivo:** `includes/modules/crm-owners/assets/iban-mask.js`
**Líneas:** 49
**Severidad:** ALTA
**Problema:** Logs de auditoría en consola del navegador (inseguro)

#### Código Actual (INSEGURO):
```javascript
// Log de auditoría en consola
console.log('[AUDIT] IBAN revelado - Usuario: ' + window.userLogin + ' - Fecha: ' + new Date().toISOString());
```

#### Solución:

**1. Crear endpoint AJAX para logging:**

**Nuevo archivo:** `includes/modules/crm-owners/audit-logger.php`

```php
<?php
/**
 * Sistema de Auditoría para Datos Sensibles
 */

if (!defined('ABSPATH')) exit;

class Alquipress_Audit_Logger
{
    private static $log_file;

    public static function init()
    {
        self::$log_file = WP_CONTENT_DIR . '/alquipress-audit.log';
        add_action('wp_ajax_alquipress_log_iban_access', [__CLASS__, 'log_iban_access']);
    }

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

        $log_entry = sprintf(
            "[%s] Usuario: %s (ID: %d) | Acción: %s | Propietario ID: %d | IP: %s\n",
            current_time('mysql'),
            wp_get_current_user()->user_login,
            get_current_user_id(),
            $action,
            $owner_id,
            self::get_client_ip()
        );

        // Escribir al log
        error_log($log_entry, 3, self::$log_file);

        wp_send_json_success();
    }

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
}

Alquipress_Audit_Logger::init();
```

**2. Actualizar JavaScript:**

**Archivo:** `includes/modules/crm-owners/assets/iban-mask.js`

```javascript
// Reemplazar el console.log con:
toggleBtn.on('click', function (e) {
    e.preventDefault();

    if (ibanInput.hasClass('iban-hidden')) {
        // Mostrar IBAN real
        ibanInput.removeClass('iban-hidden');
        maskedSpan.hide();
        toggleBtn.html('🔒 Ocultar IBAN');
        toggleBtn.addClass('iban-visible');

        // ✅ Log de auditoría SERVER-SIDE
        jQuery.post(ajaxurl, {
            action: 'alquipress_log_iban_access',
            nonce: ibanMaskData.nonce,
            owner_id: ibanMaskData.ownerId,
            action_type: 'reveal_iban'
        });

    } else {
        // Ocultar IBAN
        ibanInput.addClass('iban-hidden');
        maskedSpan.show();
        toggleBtn.html('👁️ Mostrar IBAN');
        toggleBtn.removeClass('iban-visible');
    }
});
```

**3. Actualizar localize_script:**

**Archivo:** `includes/modules/crm-owners/crm-owners.php`

```php
// Línea 46-48, reemplazar con:
wp_localize_script('alquipress-iban-mask', 'ibanMaskData', [
    'userLogin' => wp_get_current_user()->user_login,
    'ownerId' => $post->ID,
    'nonce' => wp_create_nonce('alquipress_iban_nonce')
]);
```

**4. Incluir audit logger:**

**Archivo:** `includes/modules/crm-owners/crm-owners.php`

```php
// Al final del archivo, agregar:
require_once dirname(__FILE__) . '/audit-logger.php';
```

---

### 6. Corrección de sanitize_url en Kyero Integration

**Archivo:** `includes/modules/kyero-integration/kyero-integration.php`
**Línea:** 169
**Severidad:** ALTA
**Problema:** Función inexistente `sanitize_url()`

#### Código Actual (INCORRECTO):
```php
update_option('kyero_import_url', sanitize_url($_POST['kyero_import_url']));
```

#### Código Corregido:
```php
// ✅ Usar esc_url_raw() que es la función correcta de WordPress
$import_url = isset($_POST['kyero_import_url']) ? esc_url_raw($_POST['kyero_import_url']) : '';

// Validar que sea una URL válida
if (!empty($import_url) && !filter_var($import_url, FILTER_VALIDATE_URL)) {
    add_settings_error(
        'kyero_messages',
        'invalid_url',
        'La URL proporcionada no es válida.',
        'error'
    );
    return;
}

update_option('kyero_import_url', $import_url);
```

---

### 7. Mejorar Validación de Nonce con Error Handling

**Archivo:** `includes/modules/kyero-integration/kyero-integration.php`
**Líneas:** 88-94
**Severidad:** ALTA
**Problema:** Fallo silencioso sin logging

#### Código Actual (SIN ERROR HANDLING):
```php
function alquipress_save_kyero_export($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (empty($_POST['alquipress_kyero_export_nonce']) || !wp_verify_nonce($_POST['alquipress_kyero_export_nonce'], 'alquipress_kyero_export')) {
        return; // ❌ Fallo silencioso
    }
    // ...
}
```

#### Código Corregido (CON ERROR HANDLING):
```php
function alquipress_save_kyero_export($post_id) {
    // Validaciones básicas
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        error_log('ALQUIPRESS: Usuario sin permisos intentó editar Kyero export (Post ID: ' . $post_id . ')');
        return;
    }

    // ✅ Validación de nonce con logging
    if (empty($_POST['alquipress_kyero_export_nonce'])) {
        error_log('ALQUIPRESS: Nonce faltante en save_post_product (Post ID: ' . $post_id . ')');
        return;
    }

    if (!wp_verify_nonce($_POST['alquipress_kyero_export_nonce'], 'alquipress_kyero_export')) {
        error_log('ALQUIPRESS: Nonce inválido en save_post_product (Post ID: ' . $post_id . ')');
        return;
    }

    // Procesar el formulario
    $export_term = get_term_by('slug', 'exportar', 'kyero_export');

    if (!$export_term) {
        error_log('ALQUIPRESS: Término "exportar" no encontrado en taxonomía kyero_export');
        return;
    }

    if (isset($_POST['kyero_export_checkbox']) && $_POST['kyero_export_checkbox'] == '1') {
        wp_set_post_terms($post_id, [$export_term->term_id], 'kyero_export', false);
    } else {
        wp_remove_object_terms($post_id, $export_term->term_id, 'kyero_export');
    }
}
```

---

### 8. Optimizar Cache Clearing

**Archivo:** `includes/class-performance-optimizer.php`
**Líneas:** 295-305
**Severidad:** ALTA
**Problema:** Limpieza demasiado agresiva de caché

#### Código Actual (INEFICIENTE):
```php
public function clear_reports_cache($post_id = null)
{
    // ❌ Limpiar TODOS los transients en cada actualización
    global $wpdb;

    $wpdb->query(
        "DELETE FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_alquipress_%'
        OR option_name LIKE '_transient_timeout_alquipress_%'"
    );
}
```

#### Código Corregido (OPTIMIZADO):
```php
public function clear_reports_cache($post_id = null)
{
    // ✅ Solo limpiar caché si es relevante

    // Si es un pedido, limpiar solo reportes relacionados
    if (get_post_type($post_id) === 'shop_order') {
        $current_year = date('Y');
        $order = wc_get_order($post_id);

        if ($order) {
            $order_year = date('Y', strtotime($order->get_date_created()));

            // Limpiar transients específicos del año del pedido
            delete_transient('alquipress_monthly_revenue_' . $order_year);
            delete_transient('alquipress_top_clients_' . $order_year . '_5');
            delete_transient('alquipress_top_properties_' . $order_year . '_5');

            // Si es del año actual, limpiar también ese año
            if ($order_year != $current_year) {
                delete_transient('alquipress_monthly_revenue_' . $current_year);
                delete_transient('alquipress_top_clients_' . $current_year . '_5');
                delete_transient('alquipress_top_properties_' . $current_year . '_5');
            }
        }
    }

    // Si es un producto (propiedad), limpiar reportes de propiedades
    if (get_post_type($post_id) === 'product') {
        $current_year = date('Y');
        delete_transient('alquipress_top_properties_' . $current_year . '_5');
    }
}

/**
 * Limpiar TODO el caché (solo llamar manualmente)
 */
public function clear_all_cache()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $deleted = $wpdb->query(
        "DELETE FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_alquipress_%'
        OR option_name LIKE '_transient_timeout_alquipress_%'"
    );

    error_log('ALQUIPRESS: Limpiados ' . $deleted . ' transients');

    return $deleted;
}
```

---

## 🔶 CORRECCIONES DE PRIORIDAD MEDIA

### 9. Internacionalización (i18n)

**Todos los archivos**
**Severidad:** MEDIA
**Problema:** Textos hardcodeados sin posibilidad de traducción

#### Ejemplo de Corrección:

**Antes:**
```php
echo '✓ Módulos actualizados correctamente.';
```

**Después:**
```php
echo esc_html__('✓ Módulos actualizados correctamente.', 'alquipress-core');
```

**Crear archivo:** `languages/alquipress-core.pot`

```php
// En el archivo principal alquipress-core.php, agregar:
function alquipress_load_textdomain() {
    load_plugin_textdomain(
        'alquipress-core',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('plugins_loaded', 'alquipress_load_textdomain');
```

**Ejemplos de strings a traducir:**

```php
// includes/class-module-manager.php
add_settings_error(
    'alquipress_messages',
    'alquipress_message',
    esc_html__('✓ Módulos actualizados correctamente.', 'alquipress-core'),
    'success'
);

// includes/modules/booking-pipeline/pipeline.php
'label' => __('Pago Depósito Recibido', 'alquipress-core'),

// includes/modules/crm-guests/crm-guests.php
'<span style="color: #666;">' . esc_html__('Estándar', 'alquipress-core') . '</span>',
'<span style="color: #F39C12; font-weight: bold;">' . esc_html__('⭐ VIP', 'alquipress-core') . '</span>',
```

---

### 10. Validación de Dependencias ACF

**Archivos:** Todos los módulos que usan ACF
**Severidad:** MEDIA
**Problema:** No se verifica si ACF está activo

#### Solución General:

**Crear helper global:**

**Nuevo archivo:** `includes/helpers.php`

```php
<?php
/**
 * Helper Functions para ALQUIPRESS
 */

if (!defined('ABSPATH')) exit;

/**
 * Verificar si ACF está activo
 */
function alquipress_is_acf_active() {
    return class_exists('ACF') || function_exists('get_field');
}

/**
 * Mostrar aviso de dependencia faltante
 */
function alquipress_missing_dependency_notice($plugin_name) {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                esc_html__('ALQUIPRESS requiere que %s esté instalado y activado.', 'alquipress-core'),
                '<strong>' . esc_html($plugin_name) . '</strong>'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Verificar dependencias críticas
 */
function alquipress_check_dependencies() {
    $missing = [];

    if (!alquipress_is_acf_active()) {
        $missing[] = 'Advanced Custom Fields PRO';
    }

    if (!class_exists('WooCommerce')) {
        $missing[] = 'WooCommerce';
    }

    if (!empty($missing)) {
        add_action('admin_notices', function() use ($missing) {
            foreach ($missing as $plugin) {
                alquipress_missing_dependency_notice($plugin);
            }
        });
        return false;
    }

    return true;
}
```

**Actualizar archivo principal:**

**Archivo:** `alquipress-core.php`

```php
<?php
// Después de las definiciones de constantes, agregar:

require_once ALQUIPRESS_PATH . 'includes/helpers.php';

// Verificar dependencias antes de inicializar
if (!alquipress_check_dependencies()) {
    return; // No cargar el plugin si faltan dependencias
}

function alquipress_init()
{
    $module_manager = new Alquipress_Module_Manager();
    $module_manager->load_active_modules();
}
add_action('plugins_loaded', 'alquipress_init');
```

**Aplicar en módulos individuales:**

**Ejemplo en:** `includes/modules/crm-guests/crm-guests.php`

```php
<?php
if (!defined('ABSPATH')) exit;

// ✅ Verificar ACF antes de continuar
if (!function_exists('get_field')) {
    error_log('ALQUIPRESS CRM Guests: ACF no está disponible');
    return;
}

class Alquipress_CRM_Guests
{
    // ... resto del código
}
```

---

### 11. Error Handling en File Operations

**Archivo:** `includes/modules/kyero-integration/kyero-integration.php`
**Línea:** 134
**Severidad:** MEDIA

#### Código Actual:
```php
if (file_exists($feed_file)) {
    readfile($feed_file);
}
```

#### Código Corregido:
```php
if (file_exists($feed_file)) {
    // ✅ Verificar permisos de lectura
    if (!is_readable($feed_file)) {
        error_log('ALQUIPRESS: Feed file exists but is not readable: ' . $feed_file);
        header('HTTP/1.1 500 Internal Server Error');
        echo '<?xml version="1.0" encoding="UTF-8"?><error>Feed file not accessible</error>';
        exit;
    }

    // ✅ Intentar leer con error handling
    $result = @readfile($feed_file);

    if ($result === false) {
        error_log('ALQUIPRESS: Failed to read feed file: ' . $feed_file);
        header('HTTP/1.1 500 Internal Server Error');
        echo '<?xml version="1.0" encoding="UTF-8"?><error>Failed to read feed</error>';
        exit;
    }
} else {
    // Generar feed nuevo
    $feed = new Alquipress_Kyero_Feed();
    echo $feed->generate();
}
```

**Mejorar también save_to_file():**

**Archivo:** `includes/modules/kyero-integration/class-kyero-feed.php`

```php
public function save_to_file() {
    $feed_file = alquipress_kyero_feed_file_path();
    $xml = $this->generate();

    // ✅ Verificar directorio
    $upload_dir = dirname($feed_file);
    if (!file_exists($upload_dir)) {
        if (!wp_mkdir_p($upload_dir)) {
            error_log('ALQUIPRESS: No se pudo crear directorio: ' . $upload_dir);
            return false;
        }
    }

    // ✅ Verificar permisos de escritura
    if (file_exists($feed_file) && !is_writable($feed_file)) {
        error_log('ALQUIPRESS: Feed file is not writable: ' . $feed_file);
        return false;
    }

    // ✅ Escribir con error handling
    $result = file_put_contents($feed_file, $xml);

    if ($result === false) {
        error_log('ALQUIPRESS: Failed to write feed file: ' . $feed_file);
        return false;
    }

    return home_url('/kyero-feed.xml');
}
```

---

### 12. Escapado Consistente en Admin UI

**Archivo:** `includes/admin/settings-page.php`
**Severidad:** MEDIA

#### Revisar todas las líneas de output:

```php
<!-- ANTES -->
<th style="width: 50px; padding: 15px;">Activo</th>

<!-- DESPUÉS -->
<th style="width: 50px; padding: 15px;"><?php echo esc_html__('Activo', 'alquipress-core'); ?></th>

<!-- ANTES -->
<input ... id="<?php echo $this->get_field_id('title'); ?>" ...>

<!-- DESPUÉS -->
<input ... id="<?php echo esc_attr($this->get_field_id('title')); ?>" ...>

<!-- ANTES -->
value="<?php echo esc_attr($title); ?>"

<!-- DESPUÉS (Ya está bien) -->
value="<?php echo esc_attr($title); ?>"
```

---

### 13. Externalizar Inline Styles y Scripts

**Archivos:** `class-frontend-filters.php`, `admin/settings-page.php`
**Severidad:** MEDIA

#### Solución para Frontend Filters:

**Crear archivo:** `includes/assets/css/frontend-filters.css`

```css
.alquipress-filter-group {
    margin-bottom: 30px;
}

.alquipress-filter-group h4 {
    margin-bottom: 10px;
    font-weight: 700;
    border-bottom: 2px solid #f0f0f1;
    padding-bottom: 5px;
}

.alquipress-filter-list {
    list-style: none;
    padding: 0;
}

.alquipress-filter-list li {
    margin-bottom: 5px;
}

.alquipress-filter-list label {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alquipress-filter-list input {
    margin: 0;
}
```

**Crear archivo:** `includes/assets/js/frontend-filters.js`

```javascript
jQuery(document).ready(function($) {
    $('.alquipress-filter-group input').on('change', function() {
        var url = new URL(window.location);
        var tax = $(this).closest('.alquipress-filter-group').data('taxonomy');
        var values = [];

        $(this).closest('.alquipress-filter-group').find('input:checked').each(function() {
            values.push($(this).val());
        });

        url.searchParams.delete(tax);
        if (values.length) {
            url.searchParams.append(tax, values.join(','));
        }

        window.location = url;
    });
});
```

**Actualizar:** `includes/class-frontend-filters.php`

```php
public function enqueue_assets()
{
    if (!is_shop() && !is_product_taxonomy())
        return;

    // ✅ Cargar CSS desde archivo
    wp_enqueue_style(
        'alquipress-frontend-filters',
        ALQUIPRESS_URL . 'includes/assets/css/frontend-filters.css',
        [],
        ALQUIPRESS_VERSION
    );

    // ✅ Cargar JS desde archivo
    wp_enqueue_script(
        'alquipress-frontend-filters',
        ALQUIPRESS_URL . 'includes/assets/js/frontend-filters.js',
        ['jquery'],
        ALQUIPRESS_VERSION,
        true
    );
}
```

**Crear directorio:** `includes/assets/css/` y `includes/assets/js/`

---

### 14. Rate Limiting en AJAX

**Nuevo archivo:** `includes/class-rate-limiter.php`

```php
<?php
/**
 * Sistema de Rate Limiting para peticiones AJAX
 */

if (!defined('ABSPATH')) exit;

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
        $ip = self::get_client_ip();

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
                'ALQUIPRESS Rate Limit: User %d (IP: %s) exceeded limit for action %s',
                $user_id,
                $ip,
                $action
            ));
            return false;
        }

        // Incrementar contador
        set_transient($transient_key, $requests + 1, $time_window);
        return true;
    }

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
}
```

**Aplicar en AJAX handlers:**

```php
function alquipress_handle_report_ajax() {
    // ✅ 1. Rate limiting
    if (!Alquipress_Rate_Limiter::check_limit('get_report_data', 30, 60)) {
        wp_send_json_error([
            'message' => 'Demasiadas peticiones. Por favor, espera un momento.'
        ], 429);
    }

    // 2. Verificar nonce
    check_ajax_referer('alquipress_reports_nonce', 'nonce');

    // ... resto del código
}
```

---

## 📝 MEJORAS DE CALIDAD DE CÓDIGO

### 15. Eliminar Debug Logs en Producción

**Crear archivo:** `includes/class-debug.php`

```php
<?php
/**
 * Sistema de Debug Condicional
 */

if (!defined('ABSPATH')) exit;

class Alquipress_Debug
{
    public static function log($message, $context = [])
    {
        // Solo log si WP_DEBUG está activo
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $formatted = '[ALQUIPRESS] ' . $message;

        if (!empty($context)) {
            $formatted .= ' | Context: ' . json_encode($context);
        }

        error_log($formatted);
    }

    public static function console_log($message, $data = null)
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $script = '<script>';
        $script .= 'console.log("[ALQUIPRESS] ' . esc_js($message) . '"';

        if ($data !== null) {
            $script .= ', ' . json_encode($data);
        }

        $script .= ');</script>';

        echo $script;
    }
}
```

**Usar en lugar de console.log directo:**

**Archivo:** `includes/modules/pipeline-kanban/assets/pipeline-kanban.js`

```javascript
// ANTES:
console.log('[ALQUIPRESS Pipeline] Inicializado correctamente');

// DESPUÉS: Usar solo en desarrollo, o eliminarlo
if (typeof alquipressDebug !== 'undefined' && alquipressDebug) {
    console.log('[ALQUIPRESS Pipeline] Inicializado correctamente');
}
```

**Pasar variable desde PHP:**

```php
wp_localize_script('alquipress-pipeline-kanban', 'alquipressDebug', [
    'enabled' => defined('WP_DEBUG') && WP_DEBUG
]);
```

---

### 16. Extraer Magic Numbers a Constantes

**Archivo:** `includes/modules/crm-owners/assets/iban-mask.js`

```javascript
// ANTES:
const first = ibanValue.substring(0, 4);
const last = ibanValue.substring(ibanValue.length - 4);

// DESPUÉS:
(function ($) {
    'use strict';

    // ✅ Configuración
    const CONFIG = {
        IBAN_MIN_LENGTH: 8,
        VISIBLE_START_CHARS: 4,
        VISIBLE_END_CHARS: 4,
        MASK_CHAR: '••••'
    };

    jQuery(document).ready(function ($) {
        const ibanField = $('[data-name="datos_bancarios_iban"]');

        if (ibanField.length) {
            const ibanInput = ibanField.find('input[type="text"]');
            const ibanValue = ibanInput.val();

            if (ibanValue && ibanValue.length > CONFIG.IBAN_MIN_LENGTH) {
                const first = ibanValue.substring(0, CONFIG.VISIBLE_START_CHARS);
                const last = ibanValue.substring(ibanValue.length - CONFIG.VISIBLE_END_CHARS);
                const maskedValue = `${first} ${CONFIG.MASK_CHAR} ${CONFIG.MASK_CHAR} ${CONFIG.MASK_CHAR} ${CONFIG.MASK_CHAR} ${last}`;

                // ... resto del código
            }
        }
    });
})(jQuery);
```

---

### 17. Agregar PHPDoc Completo

**Ejemplo de documentación completa:**

```php
<?php
/**
 * Clase para gestionar el sistema de módulos de ALQUIPRESS
 *
 * @package     AlquipressCore
 * @subpackage  Core
 * @since       1.0.0
 */

if (!defined('ABSPATH')) exit;

class Alquipress_Module_Manager
{
    /**
     * Lista de módulos disponibles
     *
     * @since 1.0.0
     * @var array
     */
    private $modules = [];

    /**
     * Lista de módulos activos
     *
     * @since 1.0.0
     * @var array
     */
    private $active_modules = [];

    /**
     * Constructor
     *
     * Inicializa el gestor de módulos y registra hooks
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->register_modules();
        $this->active_modules = get_option('alquipress_modules', []);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'handle_form_submit']);
    }

    /**
     * Registra todos los módulos disponibles
     *
     * Define la configuración, descripción y dependencias de cada módulo
     *
     * @since 1.0.0
     * @return void
     */
    private function register_modules()
    {
        // ... código
    }

    /**
     * Carga los módulos activos
     *
     * Itera sobre los módulos marcados como activos y carga sus archivos
     *
     * @since 1.0.0
     * @return void
     */
    public function load_active_modules()
    {
        // ... código
    }

    // ... resto de métodos con PHPDoc
}
```

---

### 18. Refactorizar Código Duplicado en Filtros

**Archivo:** `includes/class-frontend-filters.php`

**Extraer método reutilizable:**

```php
/**
 * Obtener términos seleccionados desde query string
 *
 * @param string $taxonomy Nombre de la taxonomía
 * @return array Lista de slugs válidos
 */
private function get_selected_terms($taxonomy)
{
    if (!isset($_GET[$taxonomy]) || empty($_GET[$taxonomy])) {
        return [];
    }

    $raw = wp_unslash($_GET[$taxonomy]);
    $term_slugs = array_filter(array_unique(array_map('sanitize_title', explode(',', $raw))));

    $selected = [];
    foreach ($term_slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);
        if ($term && !is_wp_error($term)) {
            $selected[] = $slug;
        }
    }

    return $selected;
}

/**
 * Renderizar widget con términos seleccionados
 */
public function widget($args, $instance)
{
    $taxonomy = !empty($instance['taxonomy']) ? $instance['taxonomy'] : 'caracteristicas';
    $title = !empty($instance['title']) ? $instance['title'] : 'Filtrar';

    echo $args['before_widget'];
    if ($title) {
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
    }

    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
    ]);

    if (!empty($terms)) {
        // ✅ Usar método reutilizable
        $selected = $this->get_selected_terms($taxonomy);

        echo '<div class="alquipress-filter-group" data-taxonomy="' . esc_attr($taxonomy) . '">';
        echo '<ul class="alquipress-filter-list">';

        foreach ($terms as $term) {
            $checked = in_array($term->slug, $selected) ? 'checked' : '';
            echo '<li>';
            echo '<label>';
            echo '<input type="checkbox" value="' . esc_attr($term->slug) . '" ' . $checked . '> ';
            echo esc_html($term->name) . ' <small>(' . $term->count . ')</small>';
            echo '</label>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';
    }

    echo $args['after_widget'];
}
```

---

## 📅 PLAN DE IMPLEMENTACIÓN

### Fase 1: Críticas (1-2 días)
- [ ] Validación de términos en Frontend Filters
- [ ] Refactorizar código duplicado en Module Manager
- [ ] Validar tipos en SQL queries
- [ ] Implementar AJAX handlers seguros

### Fase 2: Alta Prioridad (2-3 días)
- [ ] Sistema de logging server-side
- [ ] Corregir sanitize_url
- [ ] Mejorar validación de nonce
- [ ] Optimizar cache clearing

### Fase 3: Media Prioridad (3-5 días)
- [ ] Implementar i18n completo
- [ ] Validar dependencias ACF
- [ ] Mejorar error handling
- [ ] Escapado consistente
- [ ] Externalizar inline CSS/JS
- [ ] Implementar rate limiting

### Fase 4: Calidad de Código (2-3 días)
- [ ] Eliminar debug logs
- [ ] Extraer magic numbers
- [ ] Agregar PHPDoc
- [ ] Refactorizar código duplicado

---

## ✅ CHECKLIST DE VERIFICACIÓN

Antes de desplegar a producción:

- [ ] Todos los problemas críticos resueltos
- [ ] AJAX endpoints con verificación de capabilities
- [ ] Nonces validados en todos los formularios
- [ ] Input sanitizado y output escapado
- [ ] Queries SQL con prepared statements
- [ ] Dependencias verificadas
- [ ] Logging implementado
- [ ] Caché optimizado
- [ ] Tests manuales completados
- [ ] Documentación actualizada

---

## 📚 RECURSOS ADICIONALES

- [WordPress Security Handbook](https://developer.wordpress.org/apis/security/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [WooCommerce Extension Guidelines](https://woocommerce.com/document/create-a-plugin/)

---

**Última actualización:** 2026-01-24
**Autor:** Claude (Anthropic AI)
**Revisión:** Código ALQUIPRESS Core v1.0.0
