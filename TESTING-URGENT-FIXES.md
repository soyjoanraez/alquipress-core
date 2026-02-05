# 🧪 Plan de Testing - Correcciones Urgentes de Seguridad

**Versión**: 1.0
**Fecha**: 2026-02-05
**Alcance**: Testing de correcciones CRITICAL + HIGH identificadas en SECURITY-AUDIT.md
**Estado**: Pendiente de ejecución

---

## 📋 Índice

1. [Pre-requisitos](#pre-requisitos)
2. [Corrección CRITICAL #1: Protección de Logs](#test-1-protección-de-logs)
3. [Corrección HIGH #1: Optimización Lectura de Logs](#test-2-optimización-lectura-logs)
4. [Corrección HIGH #2: Race Condition Rate Limiter](#test-3-race-condition-rate-limiter)
5. [Corrección HIGH #3: Sanitización de Input](#test-4-sanitización-input)
6. [Optimizaciones MEDIUM](#test-5-optimizaciones-medium)
7. [Tests de Integración](#test-6-integración)
8. [Checklist Final](#checklist-final)

---

## Pre-requisitos

### Entorno de Testing
- [ ] WordPress 5.8+ instalado
- [ ] PHP 7.4+ con extensiones SPL
- [ ] Acceso SSH al servidor
- [ ] Permisos para crear directorios fuera de ABSPATH
- [ ] WooCommerce activo
- [ ] Advanced Custom Fields PRO activo
- [ ] Plugin ALQUIPRESS Core actualizado con los últimos commits

### Herramientas Necesarias
```bash
# Verificar versión de PHP
php -v

# Verificar extensiones SPL (necesarias para SplFileObject)
php -m | grep -i spl

# Verificar permisos
ls -la /home/user/
```

### Backup
```bash
# IMPORTANTE: Hacer backup antes de testear
cd /home/user/alquipress-core
git stash
cp -r ../alquipress-core ../alquipress-core-backup-$(date +%Y%m%d-%H%M%S)
```

---

## TEST #1: Protección de Logs (CRITICAL #1)

### 🎯 Objetivo
Verificar que los archivos de log NO son accesibles vía web y están correctamente protegidos.

### Test 1.1: Ubicación del Archivo de Log

**Pasos:**
```bash
# 1. Verificar que se intentó crear el directorio fuera de ABSPATH
cd /home/user/
ls -la | grep alquipress-logs

# 2. Si existe, verificar permisos
ls -la alquipress-logs/
```

**Resultado Esperado:**
```
drwxr-xr-x  2 www-data www-data 4096 Feb  5 10:00 alquipress-logs
```

**Checklist:**
- [ ] Directorio `/home/user/alquipress-logs/` existe
- [ ] Directorio tiene permisos correctos (755 o 750)
- [ ] Archivo `audit.log` está dentro del directorio
- [ ] Directorio está FUERA de `/home/user/alquipress-core/`

### Test 1.2: Fallback a wp-content (si Test 1.1 falla)

**Pasos:**
```bash
# Si no se pudo crear fuera de ABSPATH, verificar fallback
cd /home/user/alquipress-core/wp-content/
ls -la | grep alquipress-logs

# Verificar existencia de .htaccess protector
cat alquipress-logs/.htaccess
```

**Resultado Esperado:**
```apache
# Bloquear acceso a archivos de log
Order deny,allow
Deny from all
<FilesMatch "\.(log|bak)$">
    Deny from all
</FilesMatch>
```

**Checklist:**
- [ ] Directorio `wp-content/alquipress-logs/` existe
- [ ] Archivo `.htaccess` existe dentro del directorio
- [ ] `.htaccess` contiene reglas de bloqueo
- [ ] Archivo `audit.log` está dentro del directorio

### Test 1.3: Verificar Acceso Web Bloqueado

**Pasos:**
```bash
# 1. Obtener URL del sitio WordPress
echo "URL del sitio: $(wp option get siteurl)"

# 2. Crear archivo de log de prueba
echo "[TEST] Esta es una prueba de seguridad" > /home/user/alquipress-core/wp-content/alquipress-logs/audit.log
```

**Abrir en navegador:**
- Si log en `/home/user/alquipress-logs/`: NO debería ser accesible (directorio fuera de web root)
- Si log en `wp-content/alquipress-logs/`: Probar `https://tu-sitio.com/wp-content/alquipress-logs/audit.log`

**Resultado Esperado:**
```
HTTP 403 Forbidden
O
HTTP 404 Not Found (si está fuera de document root)
```

**Checklist:**
- [ ] URL del log retorna 403 o 404 (NO 200)
- [ ] No se puede descargar el archivo vía navegador
- [ ] No se puede listar el contenido del directorio
- [ ] Archivos `.bak` tampoco son accesibles

### Test 1.4: Test Funcional de Escritura

**Pasos:**
```bash
# Verificar que el plugin puede escribir logs correctamente
tail -f /ruta/al/log/audit.log &

# En WordPress Admin:
# 1. Ir a un propietario
# 2. Revelar IBAN (hacer clic en "Mostrar")
# 3. Verificar que se escribió al log
```

**Resultado Esperado:**
```
[2026-02-05 10:15:23] Usuario: admin (ID: 1) | Acción: reveal_iban | Propietario ID: 123 (Juan Pérez) | IP: 192.168.1.100
```

**Checklist:**
- [ ] El log se escribe correctamente en la nueva ubicación
- [ ] Los timestamps son correctos
- [ ] Los datos incluyen: fecha, usuario, acción, propietario, IP
- [ ] No hay errores en PHP error log

---

## TEST #2: Optimización Lectura de Logs (HIGH #1)

### 🎯 Objetivo
Verificar que la lectura de logs usa SplFileObject y NO causa memory exhaustion con archivos grandes.

### Test 2.1: Crear Log Grande de Prueba

**Pasos:**
```bash
# Crear log de prueba de 100MB (mucho más grande que el límite de memoria típico de PHP)
LOG_FILE="/ruta/al/log/audit.log"

# Generar 1 millón de líneas
for i in {1..1000000}; do
    echo "[2026-02-05 10:00:00] Usuario: test$i (ID: $i) | Acción: test | Propietario ID: $i (Test) | IP: 192.168.1.$((i % 255))" >> $LOG_FILE
done

# Verificar tamaño
ls -lh $LOG_FILE
```

**Resultado Esperado:**
```
-rw-r--r-- 1 www-data www-data 150M Feb  5 10:30 audit.log
```

### Test 2.2: Verificar Lectura Eficiente

**Pasos:**
```bash
# Verificar límite de memoria de PHP
php -r "echo 'Memory Limit: ' . ini_get('memory_limit') . PHP_EOL;"

# Crear script de prueba
cat > /tmp/test_log_reading.php << 'EOF'
<?php
define('ABSPATH', '/home/user/alquipress-core/');
require_once ABSPATH . 'wp-load.php';

echo "Memory antes: " . memory_get_usage(true) / 1024 / 1024 . " MB\n";

// Intentar leer logs
$logs = Alquipress_Audit_Logger::get_recent_logs(100);

echo "Memory después: " . memory_get_usage(true) / 1024 / 1024 . " MB\n";
echo "Líneas leídas: " . count($logs) . "\n";
echo "Primeras 3 líneas:\n";
echo implode("", array_slice($logs, 0, 3));
EOF

php /tmp/test_log_reading.php
```

**Resultado Esperado:**
```
Memory antes: 2.5 MB
Memory después: 3.2 MB
Líneas leídas: 100
Primeras 3 líneas:
[2026-02-05 10:00:00] Usuario: test999998 ...
[2026-02-05 10:00:00] Usuario: test999999 ...
[2026-02-05 10:00:00] Usuario: test1000000 ...
```

**Checklist:**
- [ ] La lectura NO incrementa dramáticamente la memoria
- [ ] Se leen correctamente las últimas N líneas
- [ ] NO se genera error de memory exhaustion
- [ ] El proceso tarda menos de 2 segundos

### Test 2.3: Verificar Visor de Admin

**Pasos:**
1. Ir a WP Admin → ALQUIPRESS → 🔒 Auditoría
2. Verificar que la página carga correctamente
3. Verificar que muestra las últimas 100 entradas

**Resultado Esperado:**
- La página carga sin errores
- Se muestran las últimas 100 entradas en orden inverso (más recientes primero)
- No hay timeout ni error de memoria

**Checklist:**
- [ ] Página de auditoría carga en < 5 segundos
- [ ] Se muestran 100 líneas correctamente
- [ ] Las líneas están en orden correcto (más reciente primero)
- [ ] No hay errores PHP en error_log

---

## TEST #3: Race Condition Rate Limiter (HIGH #2)

### 🎯 Objetivo
Verificar que el locking mechanism previene bypass del rate limiting mediante requests concurrentes.

### Test 3.1: Test con Apache Bench (Requests Concurrentes)

**Pasos:**
```bash
# Instalar Apache Bench si no está instalado
sudo apt-get install apache2-utils

# Obtener nonce válido (hacer esto desde WordPress admin console en navegador)
# 1. Inspeccionar elemento en página de reportes
# 2. Buscar 'alquipress_reports'
# 3. Copiar el valor del nonce

NONCE="abc123def456"  # Reemplazar con nonce real
SITE_URL="https://tu-sitio.com"

# Crear archivo con POST data
cat > /tmp/post_data.txt << EOF
action=alquipress_get_report_data&report_type=overview&year=2026&nonce=$NONCE
EOF

# Lanzar 100 requests concurrentes (límite es 30/min)
ab -n 100 -c 10 -p /tmp/post_data.txt -T 'application/x-www-form-urlencoded' \
   -C "wordpress_logged_in_cookie=TU_COOKIE_AQUI" \
   "$SITE_URL/wp-admin/admin-ajax.php"
```

**Resultado Esperado:**
```
Complete requests:      100
Failed requests:        70  (deben fallar porque exceden el límite de 30)
Non-2xx responses:      70  (deben ser HTTP 429)
```

**Checklist:**
- [ ] Solo ~30 requests retornan HTTP 200
- [ ] ~70 requests retornan HTTP 429 (Too Many Requests)
- [ ] En error_log aparecen mensajes de rate limit excedido
- [ ] NO aparecen mensajes de "Could not acquire lock" (máximo 1-2)

### Test 3.2: Verificar Locking Mechanism

**Pasos:**
```bash
# Verificar que los locks se están creando y liberando correctamente
wp transient list | grep 'alquipress_rl_.*_lock'

# Ejecutar request y verificar inmediatamente
curl -X POST "$SITE_URL/wp-admin/admin-ajax.php" \
  -d "action=alquipress_get_report_data&report_type=overview&year=2026&nonce=$NONCE" \
  -b "wordpress_logged_in_cookie=TU_COOKIE" &

# Verificar locks activos (debe hacerse rápidamente)
sleep 0.1 && wp transient list | grep '_lock'
```

**Resultado Esperado:**
- Los locks aparecen brevemente y desaparecen después de 1-2 segundos
- NO hay locks "colgados" que persistan más de 3 segundos

**Checklist:**
- [ ] Los locks se crean correctamente
- [ ] Los locks se liberan en < 2 segundos
- [ ] NO hay locks permanentes en la base de datos
- [ ] El sistema funciona bajo carga concurrente

### Test 3.3: Test de Sanitización de Action

**Pasos:**
```bash
# Intentar inyección en parámetro action
curl -X POST "$SITE_URL/wp-admin/admin-ajax.php" \
  -d "action=get_report_data<script>alert(1)</script>&report_type=overview&year=2026&nonce=$NONCE" \
  -b "wordpress_logged_in_cookie=TU_COOKIE"

# Verificar logs
tail -20 /var/log/apache2/error.log | grep 'ALQUIPRESS Rate Limit'
```

**Resultado Esperado:**
- El action se sanitiza correctamente (se eliminan caracteres no permitidos)
- NO se ejecuta ningún script
- El rate limiter funciona con el action sanitizado

**Checklist:**
- [ ] Los caracteres especiales se eliminan del action
- [ ] No hay vulnerabilidad XSS
- [ ] El rate limiting funciona correctamente

---

## TEST #4: Sanitización de Input (HIGH #3)

### 🎯 Objetivo
Verificar que `$_GET['post_type']` está correctamente sanitizado en helpers.php.

### Test 4.1: Test de Inyección XSS

**Pasos:**
```bash
# Test 1: Intentar inyección de script
SITE_URL="https://tu-sitio.com"
curl -v "$SITE_URL/wp-admin/edit.php?post_type=product<script>alert(1)</script>"

# Test 2: Intentar SQL injection
curl -v "$SITE_URL/wp-admin/edit.php?post_type=product' OR '1'='1"

# Test 3: Caracteres especiales
curl -v "$SITE_URL/wp-admin/edit.php?post_type=product%20%3C%3E%22%27"
```

**Resultado Esperado:**
- Todos los caracteres especiales se eliminan
- Solo quedan caracteres alfanuméricos, guiones y guiones bajos
- No se ejecuta ningún código malicioso

**Checklist:**
- [ ] Scripts se eliminan de `$_GET['post_type']`
- [ ] Comillas y caracteres SQL se eliminan
- [ ] Solo pasan valores válidos (alfanuméricos + `-` + `_`)

### Test 4.2: Verificar Función alquipress_is_editing_post_type()

**Pasos:**
```bash
# Crear script de test
cat > /tmp/test_sanitize.php << 'EOF'
<?php
define('ABSPATH', '/home/user/alquipress-core/');
require_once ABSPATH . 'wp-load.php';
require_once ABSPATH . 'wp-content/plugins/alquipress-core/includes/helpers.php';

// Simular diferentes valores de post_type
$_GET['post_type'] = "product<script>alert(1)</script>";
echo "Test 1: " . (alquipress_is_editing_post_type('product') ? 'FAIL (matched)' : 'PASS (no match)') . "\n";

$_GET['post_type'] = "product";
echo "Test 2: " . (alquipress_is_editing_post_type('product') ? 'PASS (matched)' : 'FAIL (no match)') . "\n";

$_GET['post_type'] = "product' OR '1'='1";
echo "Test 3: " . (alquipress_is_editing_post_type('product') ? 'FAIL (matched)' : 'PASS (no match)') . "\n";

$_GET['post_type'] = "propietario";
echo "Test 4: " . (alquipress_is_editing_post_type('propietario') ? 'PASS (matched)' : 'FAIL (no match)') . "\n";
EOF

php /tmp/test_sanitize.php
```

**Resultado Esperado:**
```
Test 1: PASS (no match)
Test 2: PASS (matched)
Test 3: PASS (no match)
Test 4: PASS (matched)
```

**Checklist:**
- [ ] Test 1: PASS (script bloqueado)
- [ ] Test 2: PASS (valor válido acepta)
- [ ] Test 3: PASS (SQL injection bloqueado)
- [ ] Test 4: PASS (valores válidos funcionan)

---

## TEST #5: Optimizaciones MEDIUM

### Test 5.1: Detección de IP Mejorada (MEDIUM #1)

**Pasos:**
```bash
# Test con múltiples IPs en X-Forwarded-For
cat > /tmp/test_ip.php << 'EOF'
<?php
define('ABSPATH', '/home/user/alquipress-core/');
require_once ABSPATH . 'wp-load.php';
require_once ABSPATH . 'wp-content/plugins/alquipress-core/includes/helpers.php';

// Test 1: X-Forwarded-For con múltiples IPs
$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 198.51.100.1, 192.0.2.1';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
echo "Test 1 (múltiples IPs): " . alquipress_get_client_ip() . "\n";
echo "Esperado: 203.0.113.1\n\n";

// Test 2: IP inválida
$_SERVER['HTTP_X_FORWARDED_FOR'] = 'invalid_ip';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
echo "Test 2 (IP inválida): " . alquipress_get_client_ip() . "\n";
echo "Esperado: 0.0.0.0 (fallback)\n\n";

// Test 3: IP válida simple
$_SERVER['HTTP_CLIENT_IP'] = '203.0.113.50';
echo "Test 3 (IP válida): " . alquipress_get_client_ip() . "\n";
echo "Esperado: 203.0.113.50\n";
EOF

php /tmp/test_ip.php
```

**Resultado Esperado:**
```
Test 1 (múltiples IPs): 203.0.113.1
Esperado: 203.0.113.1

Test 2 (IP inválida): 0.0.0.0
Esperado: 0.0.0.0 (fallback)

Test 3 (IP válida): 203.0.113.50
Esperado: 203.0.113.50
```

**Checklist:**
- [ ] Test 1: Se toma la primera IP de la lista
- [ ] Test 2: IPs inválidas retornan 0.0.0.0
- [ ] Test 3: IPs válidas pasan correctamente
- [ ] IPv6 también funciona (opcional)

### Test 5.2: Performance - Verificación Filesize (MEDIUM #6)

**Pasos:**
```bash
# Verificar que filesize() solo se llama cada 50 escrituras
# Habilitar debug temporal
cat >> /home/user/alquipress-core/wp-content/plugins/alquipress-core/includes/modules/crm-owners/audit-logger.php << 'EOF'
// Debug temporal
if (self::$write_counter % 50 === 0) {
    error_log('ALQUIPRESS DEBUG: Checking filesize at write #' . self::$write_counter);
}
EOF

# Generar 100 accesos IBAN
for i in {1..100}; do
    # Simular acceso IBAN via AJAX
    echo "Generando acceso #$i"
done

# Verificar logs
grep 'ALQUIPRESS DEBUG: Checking filesize' /var/log/apache2/error.log | wc -l
```

**Resultado Esperado:**
```
2  (solo en escritura #50 y #100, no en cada escritura)
```

**Checklist:**
- [ ] filesize() solo se llama cada 50 escrituras
- [ ] NO se llama en cada escritura individual
- [ ] El contador se resetea correctamente después de rotar

### Test 5.3: Optimización Rotación de Logs (MEDIUM #7)

**Pasos:**
```bash
# Crear 10 archivos de backup para forzar limpieza
LOG_FILE="/ruta/al/log/audit.log"
for i in {1..10}; do
    sleep 1
    touch "${LOG_FILE}.2026-02-05-10$(printf %02d $i)00.bak"
done

# Forzar rotación (crear log > 5MB)
dd if=/dev/zero of="$LOG_FILE" bs=1M count=6

# Trigger rotación accediendo a IBAN
# Luego verificar backups
ls -lt /ruta/al/log/*.bak | head -6
```

**Resultado Esperado:**
```
Solo deben quedar 5 backups (los más recientes)
```

**Checklist:**
- [ ] Solo quedan 5 archivos .bak
- [ ] Los archivos más antiguos se eliminaron
- [ ] Los archivos más recientes se mantienen
- [ ] rename() retorna true y no hay errores

---

## TEST #6: Integración

### Test 6.1: Flow Completo IBAN

**Escenario**: Usuario admin accede a IBAN de propietario

**Pasos:**
1. Login como admin
2. Ir a Propietarios → Editar Propietario
3. Hacer clic en "Mostrar IBAN"
4. Verificar que:
   - El IBAN se revela
   - Se registra en audit.log
   - El rate limiter funciona
   - La IP se detecta correctamente

**Checklist:**
- [ ] IBAN se muestra correctamente
- [ ] Se registra en audit.log en ubicación segura
- [ ] Log contiene: timestamp, usuario, acción, propietario, IP
- [ ] IP detectada es correcta (no 0.0.0.0)
- [ ] Si se hace clic 11 veces en 1 minuto, la 11ª falla (rate limit 10/min)

### Test 6.2: Flow Completo Reportes

**Escenario**: Usuario admin solicita múltiples reportes

**Pasos:**
1. Login como admin
2. Ir a ALQUIPRESS → Reportes Avanzados
3. Solicitar 35 reportes en 1 minuto (exceder límite de 30)
4. Verificar respuestas

**Checklist:**
- [ ] Primeros 30 reportes retornan HTTP 200
- [ ] Reportes 31-35 retornan HTTP 429
- [ ] Mensaje de error es claro: "Demasiadas peticiones..."
- [ ] Después de 1 minuto, se puede solicitar de nuevo

### Test 6.3: Visor de Auditoría

**Pasos:**
1. Generar 150 accesos IBAN
2. Ir a WP Admin → ALQUIPRESS → 🔒 Auditoría
3. Verificar visualización

**Checklist:**
- [ ] Se muestran últimos 100 accesos
- [ ] Están ordenados por fecha (más reciente primero)
- [ ] Todos los campos son legibles
- [ ] Títulos con caracteres especiales se muestran correctamente (esc_html)
- [ ] La página carga en < 5 segundos

---

## CHECKLIST FINAL

### Pre-Producción

#### Seguridad (CRITICAL)
- [ ] ✅ Logs NO accesibles vía web (HTTP 403/404)
- [ ] ✅ Directorio de logs fuera de document root O protegido con .htaccess
- [ ] ✅ `.htaccess` bloquea archivos .log y .bak
- [ ] ✅ Permisos de directorio correctos (750/755)

#### Performance (HIGH)
- [ ] ✅ Lectura de logs con SplFileObject (NO file())
- [ ] ✅ NO hay memory exhaustion con logs grandes (>100MB)
- [ ] ✅ Visor de auditoría carga en < 5 segundos
- [ ] ✅ filesize() solo se llama cada 50 escrituras

#### Rate Limiting (HIGH)
- [ ] ✅ Locking mechanism funciona correctamente
- [ ] ✅ Requests concurrentes NO bypassean el límite
- [ ] ✅ Locks se liberan correctamente (< 2 segundos)
- [ ] ✅ NO hay locks "colgados" en base de datos

#### Validación de Input (HIGH)
- [ ] ✅ `$_GET['post_type']` sanitizado con sanitize_key()
- [ ] ✅ Scripts bloqueados en post_type
- [ ] ✅ SQL injection bloqueado en post_type
- [ ] ✅ Parameter `action` sanitizado en rate limiter

#### Detección de IP (MEDIUM)
- [ ] ✅ X-Forwarded-For con múltiples IPs maneja correctamente
- [ ] ✅ IPs inválidas retornan 0.0.0.0 (fallback)
- [ ] ✅ filter_var(FILTER_VALIDATE_IP) funciona
- [ ] ✅ No hay código duplicado (usa función global)

#### Rotación de Logs (MEDIUM)
- [ ] ✅ Rotación funciona cuando log > 5MB
- [ ] ✅ Solo se mantienen 5 backups
- [ ] ✅ array_multisort optimiza sorting
- [ ] ✅ rename() y unlink() con error handling

### Funcionalidad
- [ ] ✅ Plugin activa sin errores
- [ ] ✅ No hay errores PHP en error_log
- [ ] ✅ Acceso IBAN funciona correctamente
- [ ] ✅ Reportes funcionan correctamente
- [ ] ✅ Rate limiting NO afecta uso normal

### Documentación
- [ ] ✅ SECURITY-AUDIT.md refleja estado actual
- [ ] ✅ README actualizado con nueva ubicación de logs
- [ ] ✅ Comentarios de código claros
- [ ] ✅ PHPDoc presente en funciones modificadas

---

## 🚨 Criterios de Aprobación

### ✅ APROBADO para producción si:
- Todos los tests CRITICAL pasan ✅
- Todos los tests HIGH pasan ✅
- Al menos 80% de tests MEDIUM pasan ✅
- No hay errores PHP fatales ✅
- No hay degradación de performance ✅

### ❌ RECHAZADO si:
- Logs accesibles vía web ❌
- Memory exhaustion con logs grandes ❌
- Rate limiting puede ser bypasseado ❌
- XSS/Injection funciona en inputs ❌
- Errores PHP fatales ❌

---

## 📝 Plantilla de Reporte de Bugs

```markdown
### Bug #[número]

**Prioridad**: [CRITICAL/HIGH/MEDIUM/LOW]
**Test Fallado**: [Número de test]
**Descripción**: [Descripción breve del problema]

**Pasos para Reproducir**:
1. [Paso 1]
2. [Paso 2]
3. [Paso 3]

**Resultado Actual**:
[Qué está pasando]

**Resultado Esperado**:
[Qué debería pasar]

**Logs/Screenshots**:
```
[Pegar logs relevantes]
```

**Entorno**:
- PHP: [versión]
- WordPress: [versión]
- Plugin: [versión/commit]
```

---

## 📊 Registro de Ejecución

**Tester**: __________________
**Fecha Inicio**: __________________
**Fecha Fin**: __________________
**Resultado**: [ ] APROBADO  [ ] RECHAZADO  [ ] PENDIENTE

### Resumen de Resultados

| Test | Descripción | Estado | Notas |
|------|-------------|--------|-------|
| TEST #1 | Protección de Logs | ⬜ | |
| TEST #2 | Optimización Lectura | ⬜ | |
| TEST #3 | Race Condition | ⬜ | |
| TEST #4 | Sanitización Input | ⬜ | |
| TEST #5 | Optimizaciones MEDIUM | ⬜ | |
| TEST #6 | Integración | ⬜ | |

**Leyenda**: ✅ Pasó | ❌ Falló | ⬜ Pendiente | ⏸️ Bloqueado

---

## 🎓 Notas para el Tester

### Comandos Útiles

```bash
# Limpiar todos los transients (resetear rate limiter)
wp transient delete --all

# Ver logs de WordPress en tiempo real
tail -f /var/log/apache2/error.log

# Ver logs de auditoría
tail -f /ruta/al/log/audit.log

# Verificar uso de memoria PHP
php -r "echo ini_get('memory_limit');"

# Limpiar caché de WordPress
wp cache flush

# Verificar permisos
namei -l /ruta/al/log/audit.log
```

### Troubleshooting

**Problema**: No se puede crear directorio fuera de ABSPATH
**Solución**: Verificar permisos del usuario web (www-data) y usar fallback a wp-content

**Problema**: SplFileObject lanza excepción
**Solución**: Verificar que PHP tiene extensión SPL habilitada (php -m | grep SPL)

**Problema**: Locks se quedan "colgados"
**Solución**: Los locks expiran automáticamente en 2 segundos. Si persiste, limpiar transients.

**Problema**: Rate limiter muy agresivo
**Solución**: Los límites son configurables en el código. Para testing, aumentar temporalmente.

---

## ✅ Firma de Aprobación

**Desarrollador**: __________________
**Firma**: __________________
**Fecha**: __________________

**QA/Tester**: __________________
**Firma**: __________________
**Fecha**: __________________

**Product Owner**: __________________
**Firma**: __________________
**Fecha**: __________________

---

**Fin del documento**
