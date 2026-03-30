# Security Smoke Tests

Fecha: 2026-03-03

## 1) Quick Actions AJAX handler

Objetivo: validar que el endpoint registrado existe y responde con error controlado.

1. Inicia sesión en admin.
2. Lanza una petición a `admin-ajax.php` con `action=alquipress_quick_status_change`.
3. Verifica:
   - Sin nonce: respuesta de seguridad de WordPress.
   - Con nonce válido pero usuario sin capacidad: `403`.
   - Con nonce y permisos: `success=true` al actualizar estado válido.

## 2) REST pública de booking

Objetivo: impedir acceso anónimo a productos no publicados.

1. Crea un producto `draft`.
2. Llama anónimamente a:
   - `/wp-json/ap-bookings/v1/calendar?product_id=<draft_id>&from=2026-03-10&to=2026-03-17`
   - `/wp-json/ap-bookings/v1/price?product_id=<draft_id>&checkin=2026-03-10&checkout=2026-03-17&guests=2`
3. Esperado: denegado por `permission_callback`.
4. Repite con producto `publish`: permitido.

## 3) SSRF hardening (Kyero/iCal)

Objetivo: bloquear URLs internas/locales.

1. En Kyero Sync, intenta guardar URL como:
   - `http://127.0.0.1/feed.xml`
   - `http://localhost/feed.xml`
2. Esperado: mensaje de URL no permitida por seguridad.
3. En metabox iCal, añade feed con URL local similar.
4. Guarda y verifica que no se persiste en `_alquipress_ical_feeds`.
5. Ejecuta sync manual con URL insegura ya persistida (si existía legacy):
   - Esperado: estado `error` y no bloquea fechas.

## 4) Ajustes de módulos (authz)

Objetivo: evitar cambios por usuarios sin privilegios.

1. Con usuario sin `manage_options`, envía POST a `admin.php?page=alquipress-settings`.
2. Esperado: `wp_die` 403.
3. Con admin, POST con nonce válido: cambios aplicados.

## 5) Caché diaria

Objetivo: confirmar que no se vacía caché global.

1. Ejecuta `do_action('alquipress_clear_daily_cache')`.
2. Verifica que:
   - Se borra caché/transients de Alquipress.
   - No se ejecuta `wp_cache_flush()` global.

## 6) Leaflet local

Objetivo: eliminar dependencia CDN en admin.

1. Abre página de propiedades (`alquipress-properties`).
2. En red del navegador, valida que Leaflet carga desde:
   - `.../wp-content/plugins/alquipress-core/includes/assets/vendor/leaflet/leaflet.css`
   - `.../wp-content/plugins/alquipress-core/includes/assets/vendor/leaflet/leaflet.js`
3. Verifica que no hay solicitudes a `unpkg.com`.
