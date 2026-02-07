# 🧪 ALQUIPRESS CORE - Plan de Testing y Verificación

**Versión:** 1.0.0
**Fecha:** 2026-01-24
**Branch:** claude/code-review-tYL7q
**Commits:** 3 (Fase 1, 2 y 3)

---

## 📋 ÍNDICE

1. [Requisitos Previos](#requisitos-previos)
2. [Fase 1: Testing de Correcciones Críticas](#fase-1-testing-de-correcciones-críticas)
3. [Fase 2: Testing de Correcciones Alta Prioridad](#fase-2-testing-de-correcciones-alta-prioridad)
4. [Fase 3: Testing de Correcciones Prioridad Media](#fase-3-testing-de-correcciones-prioridad-media)
5. [Tests de Integración](#tests-de-integración)
6. [Tests de Performance](#tests-de-performance)
7. [Tests de Seguridad](#tests-de-seguridad)
8. [Checklist Final](#checklist-final)

---

## ✅ REQUISITOS PREVIOS

### Entorno de Testing

- [ ] WordPress 6.0 o superior instalado
- [ ] WooCommerce activo y configurado
- [ ] Advanced Custom Fields PRO activo
- [ ] Tema Astra instalado (para widgets)
- [ ] Al menos 3 productos (inmuebles) creados
- [ ] Al menos 5 usuarios clientes creados
- [ ] Al menos 3 pedidos de prueba creados
- [ ] Al menos 2 propietarios creados

### Plugins Requeridos

```
✓ WooCommerce
✓ Advanced Custom Fields PRO
✓ ALQUIPRESS Core (este plugin)
```

### Preparación del Entorno

```bash
# 1. Activar modo debug en wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# 2. Verificar permisos de archivos
chmod 755 wp-content/uploads/
chmod 644 wp-content/debug.log

# 3. Tener acceso a logs
tail -f wp-content/debug.log
```

---

## 🔴 FASE 1: TESTING DE CORRECCIONES CRÍTICAS

### TEST 1.1: Validación de Términos en Filtros de Taxonomía

**Objetivo:** Verificar que solo se acepten términos válidos en filtros

**Pasos:**

1. Ir a la tienda (frontend)
2. Inspeccionar URL y agregar manualmente parámetro malicioso:
   ```
   ?caracteristicas=invalid-term,malicious-code
   ```
3. Recargar la página

**Resultado Esperado:**
- ✅ La página carga sin errores
- ✅ No se aplica ningún filtro (términos inválidos ignorados)
- ✅ No aparece error visible al usuario
- ✅ Productos se muestran normalmente sin filtrar

**Cómo Verificar:**
- Inspeccionar query SQL en debug.log
- Verificar que solo términos válidos aparezcan en tax_query

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

### TEST 1.2: Eliminación de Código Duplicado en Module Manager

**Objetivo:** Verificar que el guardado de módulos funciona correctamente

**Pasos:**

1. Ir a **ALQUIPRESS** → **ALQUIPRESS** (página de settings)
2. Activar/desactivar varios módulos
3. Hacer clic en **Guardar Cambios**
4. Verificar mensaje de éxito
5. Recargar la página
6. Verificar que los cambios persisten

**Resultado Esperado:**
- ✅ Mensaje "✓ Módulos actualizados correctamente" aparece
- ✅ Los cambios se guardan correctamente
- ✅ Al recargar, los módulos mantienen su estado
- ✅ Solo aparece UN mensaje de éxito (no duplicado)

**Cómo Verificar:**
- Inspeccionar la base de datos:
  ```sql
  SELECT * FROM wp_options WHERE option_name = 'alquipress_modules';
  ```

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

### TEST 1.3: Validación SQL con REGEXP

**Objetivo:** Verificar que queries SQL validen tipos antes de CAST

**Pasos:**

1. Ir a **ALQUIPRESS** → **📊 Informes**
2. Seleccionar pestaña **"👥 Clientes"**
3. Seleccionar año actual
4. Verificar que "Top 5 Clientes" se muestra correctamente

**Resultado Esperado:**
- ✅ La tabla de clientes carga sin errores
- ✅ Los datos son correctos y coinciden con pedidos reales
- ✅ No hay errores SQL en debug.log

**Cómo Verificar:**
- Revisar debug.log para verificar ausencia de errores SQL
- Verificar que el query incluye validaciones REGEXP:
  ```sql
  AND pm_customer.meta_value REGEXP '^[0-9]+$'
  AND pm_total.meta_value REGEXP '^[0-9]+\.?[0-9]*$'
  ```

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

### TEST 1.4: Validación y Sanitización en AJAX de Reportes

**Objetivo:** Verificar que el endpoint AJAX valida correctamente inputs

**Pasos:**

1. Abrir DevTools → Console
2. Ejecutar este código malicioso:
   ```javascript
   jQuery.post(ajaxurl, {
       action: 'alquipress_get_report_data',
       nonce: 'invalid-nonce',
       report_type: '<script>alert("xss")</script>',
       year: 9999
   }, function(response) {
       console.log(response);
   });
   ```

**Resultado Esperado:**
- ✅ Respuesta de error (nonce inválido)
- ✅ No se ejecuta código malicioso
- ✅ Error registrado en debug.log

**Cómo Verificar:**
- Verificar respuesta en Console: debe ser error 400 o 403
- Revisar debug.log para entrada de error

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

## 🟠 FASE 2: TESTING DE CORRECCIONES ALTA PRIORIDAD

### TEST 2.1: Sistema de Auditoría Server-Side para IBAN

**Objetivo:** Verificar que los accesos a IBAN se registran en servidor

**Pasos:**

1. Ir a **Propietarios** → **Editar** (cualquier propietario)
2. Buscar el campo **"Datos Bancarios - IBAN"**
3. Verificar que el IBAN está enmascarado: `ES12 •••• •••• •••• •••• 3456`
4. Hacer clic en **"👁️ Mostrar IBAN"**
5. Verificar que el IBAN se muestra completo
6. Hacer clic en **"🔒 Ocultar IBAN"**
7. Ir a **ALQUIPRESS** → **🔒 Auditoría**
8. Verificar el registro

**Resultado Esperado:**
- ✅ IBAN está enmascarado por defecto
- ✅ Botón toggle funciona correctamente
- ✅ Al mostrar IBAN, se envía petición AJAX
- ✅ Entrada aparece en página de Auditoría
- ✅ Entrada contiene: fecha, usuario, acción, propietario e IP
- ✅ NO aparece console.log en DevTools

**Formato del log esperado:**
```
[2026-01-24 10:30:45] Usuario: admin (ID: 1) | Acción: reveal_iban | Propietario ID: 123 (Juan Pérez) | IP: 192.168.1.1
```

**Cómo Verificar:**
- Inspeccionar Network tab en DevTools (debe aparecer petición POST a admin-ajax.php)
- Verificar archivo físico: `wp-content/alquipress-audit.log`
  ```bash
  tail -20 wp-content/alquipress-audit.log
  ```

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

### TEST 2.2: Corrección de sanitize_url en Kyero

**Objetivo:** Verificar que la validación de URL funciona correctamente

**Pasos:**

1. Ir a **ALQUIPRESS** → **Kyero Sync**
2. **Test 1 - URL Inválida:**
   - Ingresar: `htp://malformed-url`
   - Hacer clic en **"💾 Guardar Configuración"**
   - Verificar mensaje de error

3. **Test 2 - URL Válida:**
   - Ingresar: `https://example.com/kyero-feed.xml`
   - Hacer clic en **"💾 Guardar Configuración"**
   - Verificar mensaje de éxito

4. **Test 3 - Campo Vacío:**
   - Dejar campo vacío
   - Hacer clic en **"💾 Guardar Configuración"**
   - Verificar que se acepta (campo opcional)

**Resultado Esperado:**

Test 1:
- ✅ Mensaje: "❌ La URL proporcionada no es válida"
- ✅ URL NO se guarda en base de datos

Test 2:
- ✅ Mensaje: "✅ Configuración guardada"
- ✅ URL se guarda correctamente

Test 3:
- ✅ Mensaje: "✅ Configuración guardada"
- ✅ Campo se guarda como vacío

**Cómo Verificar:**
```sql
SELECT option_value FROM wp_options WHERE option_name = 'kyero_import_url';
```

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

### TEST 2.3: Validación de Nonce con Error Handling

**Objetivo:** Verificar que errores de nonce se registran correctamente

**Pasos:**

1. Abrir debug.log en tiempo real:
   ```bash
   tail -f wp-content/debug.log
   ```

2. Editar cualquier **Producto** (inmueble)
3. En DevTools, modificar el nonce del formulario Kyero:
   ```javascript
   document.querySelector('input[name="alquipress_kyero_export_nonce"]').value = 'invalid';
   ```
4. Marcar/desmarcar checkbox "📤 Exportar esta propiedad a Kyero"
5. Guardar el producto

**Resultado Esperado:**
- ✅ El cambio NO se aplica (por nonce inválido)
- ✅ En debug.log aparece:
  ```
  ALQUIPRESS Kyero: Nonce inválido en save_post_product (Post ID: XXX)
  ```

**Cómo Verificar:**
- Revisar debug.log inmediatamente después de guardar
- Verificar que el término "exportar" NO se agregó al producto

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

### TEST 2.4: Optimización de Limpieza de Caché

**Objetivo:** Verificar que solo se limpian transients específicos

**Pasos:**

1. Activar monitoreo de debug.log:
   ```bash
   tail -f wp-content/debug.log | grep ALQUIPRESS
   ```

2. **Test 1 - Actualizar Pedido:**
   - Ir a **WooCommerce** → **Pedidos**
   - Editar cualquier pedido
   - Cambiar estado o cualquier dato
   - Guardar

3. **Test 2 - Actualizar Producto:**
   - Ir a **Productos**
   - Editar cualquier inmueble
   - Cambiar precio
   - Guardar

**Resultado Esperado:**

Test 1 (Pedido):
- ✅ Solo se eliminan transients del año del pedido
- ✅ NO aparece DELETE masivo en debug.log
- ✅ Transients específicos eliminados:
  - `alquipress_monthly_revenue_[año]`
  - `alquipress_top_clients_[año]_5`
  - `alquipress_top_properties_[año]_5`

Test 2 (Producto):
- ✅ Solo se elimina: `alquipress_top_properties_[año_actual]_5`
- ✅ NO se eliminan todos los transients

**Cómo Verificar:**
- Revisar debug.log: NO debe aparecer query DELETE masivo
- Verificar que otros transients no relacionados persisten:
  ```sql
  SELECT option_name FROM wp_options WHERE option_name LIKE '_transient_alquipress_%';
  ```

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

## 🟡 FASE 3: TESTING DE CORRECCIONES PRIORIDAD MEDIA

### TEST 3.1: Validación de Dependencias (ACF y WooCommerce)

**Objetivo:** Verificar que el plugin detecta dependencias faltantes

**Pasos:**

1. **Test 1 - Desactivar ACF:**
   - Desactivar plugin "Advanced Custom Fields PRO"
   - Ir a página de **Plugins**
   - Verificar mensaje de error

2. **Test 2 - Desactivar WooCommerce:**
   - Reactivar ACF
   - Desactivar "WooCommerce"
   - Verificar mensaje de error

3. **Test 3 - Reactivar todo:**
   - Reactivar ambos plugins
   - Verificar que mensajes desaparecen

**Resultado Esperado:**

Test 1:
- ✅ Mensaje en admin: "ALQUIPRESS: Requiere que **Advanced Custom Fields PRO** esté instalado y activado."
- ✅ Plugin no se carga (verificar que módulos no se activan)

Test 2:
- ✅ Mensaje en admin: "ALQUIPRESS: Requiere que **WooCommerce** esté instalado y activado."

Test 3:
- ✅ Mensajes desaparecen
- ✅ Plugin funciona normalmente

**Cómo Verificar:**
- Verificar que módulos CRM Guests/Owners no se cargan sin ACF
- Revisar debug.log:
  ```
  [ALQUIPRESS] CRM Guests: ACF no está disponible, módulo no cargado
  ```

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

### TEST 3.2: Error Handling en Operaciones de Archivo (Kyero)

**Objetivo:** Verificar que errores de archivo se manejan correctamente

**Pasos:**

1. **Test 1 - Directorio no escribible:**
   ```bash
   # Cambiar permisos de uploads
   chmod 000 wp-content/uploads/
   ```
   - Ir a **ALQUIPRESS** → **Kyero Sync**
   - Hacer clic en **"🚀 Generar Feed Ahora"**
   - Verificar comportamiento

2. **Test 2 - Restaurar permisos:**
   ```bash
   chmod 755 wp-content/uploads/
   ```
   - Volver a hacer clic en **"🚀 Generar Feed Ahora"**
   - Verificar éxito

3. **Test 3 - Acceso al feed:**
   - Ir a `https://tu-sitio.com/kyero-feed.xml`
   - Verificar que se muestra XML

**Resultado Esperado:**

Test 1:
- ✅ NO aparece error fatal de PHP
- ✅ Mensaje de error amigable
- ✅ En debug.log:
  ```
  ALQUIPRESS Kyero: Directorio no escribible - /path/to/uploads
  ```

Test 2:
- ✅ Mensaje: "✅ Feed exportado: [URL]"
- ✅ Archivo se crea en `wp-content/uploads/kyero-feed.xml`

Test 3:
- ✅ XML válido se muestra
- ✅ Content-Type: application/xml
- ✅ Si hay error de lectura, se muestra:
  ```xml
  <?xml version="1.0"?><error>Failed to read feed</error>
  ```

**Cómo Verificar:**
```bash
# Verificar archivo creado
ls -la wp-content/uploads/kyero-feed.xml

# Verificar contenido
head -20 wp-content/uploads/kyero-feed.xml
```

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

### TEST 3.3: Rate Limiting en Endpoints AJAX

**Objetivo:** Verificar que el rate limiting previene spam

**Pasos:**

1. **Test 1 - Endpoint de Reportes:**
   - Ir a **ALQUIPRESS** → **📊 Informes**
   - Abrir DevTools → Console
   - Ejecutar script de spam (30+ requests):
   ```javascript
   for(let i = 0; i < 35; i++) {
       jQuery.post(ajaxurl, {
           action: 'alquipress_get_report_data',
           nonce: document.querySelector('[name="alquipress_reports_nonce"]').value,
           report_type: 'overview',
           year: 2024
       }, function(response) {
           console.log('Request #' + i, response);
       });
   }
   ```

2. **Test 2 - Endpoint de Auditoría IBAN:**
   - Similar pero con límite de 10 requests

**Resultado Esperado:**

Test 1 (Reportes):
- ✅ Primeras 30 requests: respuesta exitosa
- ✅ Requests 31-35: Error 429 (Too Many Requests)
- ✅ Mensaje: "Demasiadas peticiones. Por favor, espera un momento antes de intentar de nuevo."
- ✅ En debug.log:
  ```
  ALQUIPRESS Rate Limit: User 1 (IP: ...) exceeded limit for action get_report_data (31/30)
  ```

Test 2 (IBAN):
- ✅ Límite de 10 requests/min
- ✅ Request 11: Error 429

**Cómo Verificar:**
- Ver respuestas en Console de DevTools
- Verificar código de respuesta HTTP en Network tab
- Revisar debug.log para entradas de rate limit

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

### TEST 3.4: CSS/JS Externalizados en Frontend Filters

**Objetivo:** Verificar que los archivos externos se cargan correctamente

**Pasos:**

1. Ir a la **Tienda** (frontend)
2. Abrir DevTools → Network tab
3. Filtrar por "alquipress"
4. Recargar página

**Resultado Esperado:**
- ✅ Se carga: `frontend-filters.css`
- ✅ Se carga: `frontend-filters.js`
- ✅ Estado: 200 OK para ambos
- ✅ En Sources tab, abrir archivos y verificar contenido legible
- ✅ NO hay inline CSS/JS en el HTML relacionado con filtros

**Verificar en el sidebar/widget de filtros:**
- ✅ Estilos se aplican correctamente
- ✅ Checkboxes tienen espaciado correcto
- ✅ Al marcar checkbox, se recarga página con filtro aplicado

**Cómo Verificar:**
```bash
# Verificar que archivos existen
ls -la includes/assets/css/frontend-filters.css
ls -la includes/assets/js/frontend-filters.js

# Verificar contenido
cat includes/assets/css/frontend-filters.css
```

**Estado:** [ ] PASS / [ ] FAIL

**Notas:**
```
[Anotar aquí cualquier observación]
```

---

## 🔗 TESTS DE INTEGRACIÓN

### TEST INT-1: Flujo Completo de Auditoría

**Objetivo:** Verificar el flujo completo desde acceso hasta visualización

**Pasos:**

1. Crear un propietario nuevo con IBAN
2. Acceder al IBAN 3 veces (mostrar/ocultar)
3. Verificar registros en página de Auditoría
4. Verificar archivo físico de log
5. Hacer que el archivo supere 5MB (simular):
   ```bash
   # Crear archivo grande para test
   for i in {1..10000}; do
       echo "[2026-01-24] Test entry $i" >> wp-content/alquipress-audit.log
   done
   ```
6. Acceder al IBAN una vez más
7. Verificar que se creó archivo de backup

**Resultado Esperado:**
- ✅ Todas las 3 entradas aparecen en orden cronológico
- ✅ Archivo se rota al superar 5MB
- ✅ Se crea `alquipress-audit.log.2026-01-24-HHMMSS.bak`
- ✅ Se mantienen solo últimos 5 backups

**Estado:** [ ] PASS / [ ] FAIL

---

### TEST INT-2: Flujo Completo de Kyero Export/Import

**Objetivo:** Verificar integración completa de Kyero

**Pasos:**

1. **Exportación:**
   - Crear 3 inmuebles
   - Marcar 2 como "Exportar a Kyero"
   - Generar feed
   - Descargar XML y verificar contenido

2. **Importación:**
   - Configurar URL de importación (puede ser del mismo sitio)
   - Ejecutar importación manual
   - Verificar productos importados

**Resultado Esperado:**
- ✅ Feed XML contiene solo 2 propiedades marcadas
- ✅ XML es válido (validar en https://www.kyero.com/xml-validator)
- ✅ Importación crea nuevos productos o actualiza existentes
- ✅ Contador muestra: "X nuevas, Y actualizadas, 0 errores"

**Estado:** [ ] PASS / [ ] FAIL

---

### TEST INT-3: Dashboard y Reportes Completos

**Objetivo:** Verificar que todos los reportes funcionan

**Pasos:**

1. Ir a **ALQUIPRESS** → **📊 Informes**
2. Probar cada pestaña:
   - 💰 Ingresos (gráfica mensual)
   - 📊 Ocupación (por mes y comparación)
   - 👥 Clientes (top 5 y distribución por rating)
   - 🏠 Propiedades (top 5 y comparación)

**Resultado Esperado:**
- ✅ Todas las gráficas cargan sin errores
- ✅ Datos coinciden con la realidad
- ✅ Cambiar año funciona correctamente
- ✅ Botón "🔄 Actualizar Informes" funciona
- ✅ Rate limiting activo (max 30 req/min)

**Estado:** [ ] PASS / [ ] FAIL

---

## ⚡ TESTS DE PERFORMANCE

### TEST PERF-1: Limpieza Selectiva de Caché

**Objetivo:** Medir mejora de performance en limpieza de caché

**Pasos:**

1. Crear 20 transients de prueba:
   ```php
   for($i = 1; $i <= 20; $i++) {
       set_transient('alquipress_test_'.$i, 'data', DAY_IN_SECONDS);
   }
   ```

2. Editar un pedido y medir tiempo de guardado

3. Verificar cuántos transients se eliminaron

**Resultado Esperado:**
- ✅ Solo 3-6 transients eliminados (selectivos)
- ✅ Otros 14+ transients siguen existiendo
- ✅ Guardado de pedido < 1 segundo

**Métrica Objetivo:** < 10 transients eliminados por operación

**Estado:** [ ] PASS / [ ] FAIL

---

### TEST PERF-2: Carga de Assets en Frontend

**Objetivo:** Verificar que assets solo se cargan donde se necesitan

**Pasos:**

1. **Página Shop:**
   - Ir a la tienda
   - DevTools → Network
   - Verificar archivos cargados

2. **Página Producto Individual:**
   - Ir a un inmueble
   - Verificar archivos cargados

3. **Página Home:**
   - Ir a homepage
   - Verificar archivos cargados

**Resultado Esperado:**

Shop/Categorías:
- ✅ `frontend-filters.css` se carga
- ✅ `frontend-filters.js` se carga

Producto Individual:
- ✅ Assets de filtros NO se cargan (innecesarios)

Homepage:
- ✅ Assets de filtros NO se cargan

**Métrica Objetivo:** Assets solo en páginas necesarias

**Estado:** [ ] PASS / [ ] FAIL

---

## 🔒 TESTS DE SEGURIDAD

### TEST SEC-1: Inyección SQL en Reportes

**Objetivo:** Intentar inyección SQL en año de reporte

**Pasos:**

1. Abrir DevTools → Console
2. Intentar inyección:
   ```javascript
   jQuery.post(ajaxurl, {
       action: 'alquipress_get_report_data',
       nonce: document.querySelector('[name="alquipress_reports_nonce"]').value,
       report_type: 'overview',
       year: "2024' OR 1=1 --"
   });
   ```

**Resultado Esperado:**
- ✅ Error 400: "Año inválido"
- ✅ Query SQL NO se ejecuta
- ✅ No se expone información de base de datos

**Estado:** [ ] PASS / [ ] FAIL

---

### TEST SEC-2: XSS en Filtros de Taxonomía

**Objetivo:** Intentar inyección XSS en filtros

**Pasos:**

1. Ir a tienda
2. Modificar URL manualmente:
   ```
   ?caracteristicas=<script>alert('XSS')</script>
   ```
3. Recargar página

**Resultado Esperado:**
- ✅ NO se ejecuta JavaScript
- ✅ Término inválido es ignorado
- ✅ Página funciona normalmente

**Estado:** [ ] PASS / [ ] FAIL

---

### TEST SEC-3: CSRF en Guardado de Módulos

**Objetivo:** Intentar CSRF en configuración

**Pasos:**

1. Crear archivo HTML malicioso:
   ```html
   <form method="POST" action="http://tu-sitio.com/wp-admin/admin.php?page=alquipress-settings">
       <input name="alquipress_save_modules" value="1">
       <input name="modules[malicious-module]" value="1">
   </form>
   <script>document.forms[0].submit();</script>
   ```

2. Abrir en navegador (estando logueado en WordPress)

**Resultado Esperado:**
- ✅ Formulario es rechazado por falta de nonce
- ✅ Configuración NO se modifica
- ✅ Mensaje de error de validación

**Estado:** [ ] PASS / [ ] FAIL

---

### TEST SEC-4: Rate Limiting Bypass

**Objetivo:** Intentar bypass del rate limiting

**Pasos:**

1. Ejecutar 35 requests desde diferentes IPs/usuarios
2. Intentar modificar transients manualmente

**Resultado Esperado:**
- ✅ Rate limit se aplica por usuario E IP
- ✅ No es posible hacer bypass fácilmente
- ✅ Logs registran intentos de exceso

**Estado:** [ ] PASS / [ ] FAIL

---

## 📊 CHECKLIST FINAL

### Funcionalidad General

- [ ] Plugin activa correctamente
- [ ] Plugin desactiva sin errores
- [ ] Flush de rewrite rules funciona
- [ ] Menú ALQUIPRESS aparece en posición correcta
- [ ] Todas las subpáginas son accesibles

### Módulos Core

- [ ] CRM Guests funciona (sin ACF muestra warning)
- [ ] CRM Owners funciona
- [ ] Pipeline de reservas carga
- [ ] Informes y analíticas funcionan
- [ ] Kyero export/import funciona
- [ ] SEO Master funciona

### Seguridad

- [ ] Todos los inputs están sanitizados
- [ ] Todos los outputs están escapados
- [ ] Nonces validados en todos los formularios
- [ ] AJAX endpoints tienen verificación de capabilities
- [ ] Rate limiting activo en endpoints sensibles
- [ ] Logging de eventos de seguridad funciona

### Performance

- [ ] Caché selectivo funciona (no limpieza masiva)
- [ ] Transients se usan correctamente
- [ ] Assets se cargan solo donde se necesitan
- [ ] Queries SQL están optimizadas
- [ ] No hay N+1 queries evidentes

### Logs y Debugging

- [ ] WP_DEBUG funciona correctamente
- [ ] Logs se escriben en debug.log
- [ ] Auditoría de IBAN registra accesos
- [ ] Error handling registra problemas
- [ ] No hay warnings o notices en producción

### Compatibilidad

- [ ] Compatible con WordPress 6.0+
- [ ] Compatible con WooCommerce actual
- [ ] Compatible con ACF PRO
- [ ] Compatible con tema Astra
- [ ] No conflictos con otros plugins comunes

---

## 📋 REPORTE DE BUGS (Si los hay)

### Bug #1
- **Descripción:**
- **Pasos para reproducir:**
- **Resultado esperado:**
- **Resultado actual:**
- **Severidad:** [ ] Crítica [ ] Alta [ ] Media [ ] Baja
- **Archivo afectado:**

### Bug #2
- **Descripción:**
- **Pasos para reproducir:**
- **Resultado esperado:**
- **Resultado actual:**
- **Severidad:** [ ] Crítica [ ] Alta [ ] Media [ ] Baja
- **Archivo afectado:**

---

## ✅ APROBACIÓN FINAL

### Criterios de Aceptación

Para considerar el plugin listo para producción:

- [ ] Todos los tests CRÍTICOS (Fase 1) pasan: 4/4
- [ ] Todos los tests ALTA PRIORIDAD (Fase 2) pasan: 4/4
- [ ] Al menos 80% tests MEDIA PRIORIDAD (Fase 3) pasan: 4/5
- [ ] No hay bugs de severidad crítica
- [ ] No hay warnings de seguridad
- [ ] Performance es aceptable (< 2s carga de páginas)

### Firma de Aprobación

**Tester:** ________________________

**Fecha:** ________________________

**Resultado:** [ ] APROBADO [ ] RECHAZADO [ ] APROBADO CON OBSERVACIONES

**Notas finales:**
```




```

---

## 📚 RECURSOS ADICIONALES

### Comandos Útiles

```bash
# Ver logs en tiempo real
tail -f wp-content/debug.log

# Buscar errores específicos
grep "ALQUIPRESS" wp-content/debug.log

# Ver todos los transients de ALQUIPRESS
wp option list --search="alquipress_*"

# Limpiar todos los transients
wp transient delete --all

# Verificar permisos
find . -type f -exec ls -la {} \; | grep alquipress

# Ver tamaño de archivos de log
du -h wp-content/*.log
```

### Queries SQL Útiles

```sql
-- Ver todos los módulos activos
SELECT option_value FROM wp_options WHERE option_name = 'alquipress_modules';

-- Ver todos los transients de ALQUIPRESS
SELECT * FROM wp_options WHERE option_name LIKE '_transient_alquipress_%';

-- Contar propietarios
SELECT COUNT(*) FROM wp_posts WHERE post_type = 'propietario';

-- Ver productos con export a Kyero
SELECT p.ID, p.post_title
FROM wp_posts p
INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
INNER JOIN wp_terms t ON tt.term_id = t.term_id
WHERE p.post_type = 'product'
AND tt.taxonomy = 'kyero_export'
AND t.slug = 'exportar';
```

---

**Fin del documento de testing**

**Versión:** 1.0
**Última actualización:** 2026-01-24
