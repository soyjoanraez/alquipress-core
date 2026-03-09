# Hooks y Filtros de Alquipress Core

Este documento describe todos los hooks (acciones) y filtros disponibles en Alquipress Core para desarrolladores que quieran extender o modificar el comportamiento del plugin.

---

## Acciones (Actions)

### `alquipress_enqueue_section_assets`

**Descripción:** Se dispara antes de cargar los assets (CSS/JS) de una sección específica del dashboard.

**Parámetros:**
- `$page` (string) - ID de la página/sección (ej: 'alquipress-dashboard', 'alquipress-pipeline')

**Cuándo se dispara:** Cuando se está a punto de cargar los assets de una sección del dashboard.

**Ejemplo de uso:**
```php
add_action('alquipress_enqueue_section_assets', function($page) {
    if ($page === 'alquipress-dashboard') {
        wp_enqueue_style('mi-estilo-custom', 'url/to/style.css');
        wp_enqueue_script('mi-script-custom', 'url/to/script.js', ['jquery'], '1.0.0', true);
    }
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_render_section`

**Descripción:** Se dispara para renderizar el contenido de una sección específica del dashboard.

**Parámetros:**
- `$page` (string) - ID de la página/sección a renderizar

**Cuándo se dispara:** Cuando se está renderizando una página del dashboard de Alquipress.

**Ejemplo de uso:**
```php
add_action('alquipress_render_section', function($page) {
    if ($page === 'alquipress-dashboard') {
        echo '<div class="mi-widget-personalizado">Contenido personalizado</div>';
    }
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_owner_revenue_cache_event`

**Descripción:** Se dispara cuando ocurre un evento relacionado con el caché de ingresos de propietarios.

**Parámetros:** Ninguno

**Cuándo se dispara:** Cuando se invalida o actualiza el caché de ingresos de propietarios.

**Ejemplo de uso:**
```php
add_action('alquipress_owner_revenue_cache_event', function() {
    // Limpiar caché relacionado cuando se actualizan ingresos
    wp_cache_flush();
});
```

**Versión:** Disponible desde 1.0.0

---

## Filtros (Filters)

### `alquipress_tipo_vivienda_list`

**Descripción:** Filtra la lista de tipos de vivienda disponibles en el sistema.

**Parámetros:**
- `$types` (array) - Array asociativo de tipos de vivienda ['key' => 'Label']

**Retorna:** Array modificado de tipos de vivienda

**Ejemplo de uso:**
```php
add_filter('alquipress_tipo_vivienda_list', function($types) {
    // Agregar nuevo tipo de vivienda
    $types['casa_rural'] = 'Casa Rural';
    
    // Modificar etiqueta existente
    $types['apartamento'] = 'Apartamento Turístico';
    
    return $types;
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_tipo_vivienda_parent`

**Descripción:** Filtra el parent/tipo padre de un tipo de vivienda específico.

**Parámetros:**
- `$parent` (string) - Tipo padre actual
- `$tipo` (string) - Tipo de vivienda actual

**Retorna:** String con el tipo padre modificado

**Ejemplo de uso:**
```php
add_filter('alquipress_tipo_vivienda_parent', function($parent, $tipo) {
    if ($tipo === 'casa_rural') {
        return 'casa';
    }
    return $parent;
}, 10, 2);
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_owner_revenue_cache_ttl`

**Descripción:** Filtra el TTL (Time To Live) del caché de ingresos de propietarios.

**Parámetros:**
- `$ttl` (int) - TTL actual en segundos

**Retorna:** Int con el TTL modificado (en segundos)

**Ejemplo de uso:**
```php
add_filter('alquipress_owner_revenue_cache_ttl', function($ttl) {
    // Aumentar TTL a 2 horas para desarrollo
    if (defined('WP_DEBUG') && WP_DEBUG) {
        return 2 * HOUR_IN_SECONDS;
    }
    return $ttl;
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_owner_revenue_invalidation_statuses`

**Descripción:** Filtra los estados de pedido que invalidan el caché de ingresos de propietarios.

**Parámetros:**
- `$statuses` (array) - Array de estados que invalidan el caché

**Retorna:** Array modificado de estados

**Ejemplo de uso:**
```php
add_filter('alquipress_owner_revenue_invalidation_statuses', function($statuses) {
    // Agregar estado personalizado que también invalida el caché
    $statuses[] = 'wc-fully-paid';
    return $statuses;
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_owner_revenue_use_object_cache`

**Descripción:** Filtra si se debe usar object cache (Redis/Memcached) para el caché de ingresos.

**Parámetros:**
- `$use_cache` (bool) - Valor actual (true/false)

**Retorna:** Bool modificado

**Ejemplo de uso:**
```php
add_filter('alquipress_owner_revenue_use_object_cache', function($use_cache) {
    // Forzar uso de object cache si está disponible
    if (wp_using_ext_object_cache()) {
        return true;
    }
    return $use_cache;
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_owner_revenue_cache_log`

**Descripción:** Filtra si se debe habilitar el logging del caché de ingresos.

**Parámetros:**
- `$enable_log` (bool) - Valor actual

**Retorna:** Bool modificado

**Ejemplo de uso:**
```php
add_filter('alquipress_owner_revenue_cache_log', function($enable_log) {
    // Habilitar logging solo en desarrollo
    return defined('WP_DEBUG') && WP_DEBUG;
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_owner_revenue_cache_log_file`

**Descripción:** Filtra la ruta del archivo de log del caché de ingresos.

**Parámetros:**
- `$log_file` (string) - Ruta actual del archivo de log

**Retorna:** String con la ruta modificada

**Ejemplo de uso:**
```php
add_filter('alquipress_owner_revenue_cache_log_file', function($log_file) {
    // Usar ubicación personalizada para logs
    return WP_CONTENT_DIR . '/logs/alquipress-revenue-cache.log';
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_owner_revenue_cache_log_max_bytes`

**Descripción:** Filtra el tamaño máximo del archivo de log antes de rotar.

**Parámetros:**
- `$max_bytes` (int) - Tamaño máximo actual en bytes

**Retorna:** Int con el tamaño máximo modificado

**Ejemplo de uso:**
```php
add_filter('alquipress_owner_revenue_cache_log_max_bytes', function($max_bytes) {
    // Aumentar tamaño máximo a 10MB
    return 10 * 1024 * 1024;
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_owner_revenue_cache_log_max_files`

**Descripción:** Filtra el número máximo de archivos de log a mantener antes de eliminar los más antiguos.

**Parámetros:**
- `$max_files` (int) - Número máximo actual

**Retorna:** Int con el número máximo modificado

**Ejemplo de uso:**
```php
add_filter('alquipress_owner_revenue_cache_log_max_files', function($max_files) {
    // Mantener más archivos de log
    return 20;
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_kyero_feed_ttl`

**Descripción:** Filtra el TTL del feed de Kyero.

**Parámetros:**
- `$ttl` (int) - TTL actual en segundos

**Retorna:** Int con el TTL modificado

**Ejemplo de uso:**
```php
add_filter('alquipress_kyero_feed_ttl', function($ttl) {
    // Reducir TTL para actualizaciones más frecuentes
    return 30 * MINUTE_IN_SECONDS;
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_kyero_sslverify`

**Descripción:** Filtra si se debe verificar el certificado SSL en las peticiones a Kyero.

**Parámetros:**
- `$verify` (bool) - Valor actual

**Retorna:** Bool modificado

**Ejemplo de uso:**
```php
add_filter('alquipress_kyero_sslverify', function($verify) {
    // Deshabilitar verificación SSL solo en desarrollo local
    if (strpos(home_url(), 'localhost') !== false) {
        return false;
    }
    return $verify;
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_kyero_price_freq`

**Descripción:** Filtra la frecuencia de actualización de precios en Kyero.

**Parámetros:**
- `$freq` (string) - Frecuencia actual (ej: 'daily', 'weekly')

**Retorna:** String con la frecuencia modificada

**Ejemplo de uso:**
```php
add_filter('alquipress_kyero_price_freq', function($freq) {
    // Actualizar precios cada hora
    return 'hourly';
});
```

**Versión:** Disponible desde 1.0.0

---

### `alquipress_kyero_agent`

**Descripción:** Filtra los datos del agente que se envían a Kyero.

**Parámetros:**
- `$agent_data` (array) - Array con datos del agente ['name', 'email', 'phone', etc.]

**Retorna:** Array modificado con datos del agente

**Ejemplo de uso:**
```php
add_filter('alquipress_kyero_agent', function($agent_data) {
    // Modificar datos del agente antes de enviar a Kyero
    $agent_data['name'] = 'Alquipress CRM';
    $agent_data['email'] = 'info@alquipress.com';
    return $agent_data;
});
```

**Versión:** Disponible desde 1.0.0

---

## Notas para Desarrolladores

### Prioridades de Hooks

Los hooks de Alquipress Core usan las siguientes prioridades por defecto:

- **10** - Acciones estándar
- **100** - Admin bar menu
- **999** - Acciones finales

Si necesitas ejecutar código antes o después de otros hooks, ajusta la prioridad en tu `add_action` o `add_filter`:

```php
// Ejecutar antes que otros hooks (prioridad baja)
add_action('alquipress_render_section', 'mi_funcion', 5);

// Ejecutar después de otros hooks (prioridad alta)
add_action('alquipress_render_section', 'mi_funcion', 999);
```

### Compatibilidad hacia atrás

Todos los hooks y filtros documentados aquí mantienen compatibilidad hacia atrás. Los cambios en parámetros o valores de retorno se documentarán en las notas de versión.

### Debugging

Para ver qué hooks están disponibles y cuándo se disparan, puedes usar:

```php
// Ver todos los hooks registrados
add_action('all', function($hook) {
    if (strpos($hook, 'alquipress_') === 0) {
        error_log("Hook disparado: $hook");
    }
});
```

---

## Changelog

### Versión 1.0.0
- Documentación inicial de todos los hooks y filtros disponibles

---

**Última actualización:** 2026-02-05
