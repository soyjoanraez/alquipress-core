# ALQUIPRESS - Plan de Testing y Validación

**Versión:** 1.0
**Fecha:** 2026-01-23
**Estado:** Testing Fase 5

---

## Índice
1. [Módulos a Validar](#módulos-a-validar)
2. [Testing por Fase](#testing-por-fase)
3. [Workflows Críticos](#workflows-críticos)
4. [Checklist de Funcionalidades](#checklist-de-funcionalidades)
5. [Testing de Rendimiento](#testing-de-rendimiento)
6. [Casos de Prueba](#casos-de-prueba)

---

## Módulos a Validar

### Fase 1: Fundamentos Técnicos ✅

#### 1. Taxonomías Personalizadas (`taxonomies`)
**Archivo:** `includes/modules/taxonomies/taxonomies.php`

- [ ] **Población (Jerárquica)**
  - [ ] Verificar que Marina Alta aparece con todos sus municipios
  - [ ] Comprobar jerarquía: Comarca → Municipio
  - [ ] Validar 33 términos creados automáticamente

- [ ] **Zona (No Jerárquica)**
  - [ ] Verificar creación de zonas (Centro, Playa, Montaña, Rural)
  - [ ] Asignar zona a propiedad

- [ ] **Características (No Jerárquica)**
  - [ ] Verificar 27 características creadas
  - [ ] Comprobar íconos FontAwesome asignados
  - [ ] Validar columna de íconos en admin (taxonomies/icon-selector.php)

**Comandos de verificación:**
```bash
wp term list poblacion --format=count  # Debe devolver 33
wp term list zona --format=count       # Debe devolver 4
wp term list caracteristicas --format=count  # Debe devolver 27
```

---

#### 2. CRM de Propietarios (`crm-owners`)
**Archivo:** `includes/modules/crm-owners/crm-owners.php`

- [ ] **CPT Propietario**
  - [ ] Crear nuevo propietario
  - [ ] Verificar campos ACF: nombre, apellidos, email, teléfono
  - [ ] Validar pestaña "Datos Financieros"
  - [ ] Comprobar campo IBAN con máscara automática
  - [ ] Validar campo "Comisión (%)"

- [ ] **Columnas Personalizadas**
  - [ ] Email visible en listado
  - [ ] Teléfono visible en listado
  - [ ] IBAN visible (ofuscado: ES** **** **** **** ****)

- [ ] **Cálculo de Ingresos** (owner-revenue.php)
  - [ ] Asignar propietario a propiedad
  - [ ] Crear pedido completado
  - [ ] Verificar cálculo de ingresos del propietario
  - [ ] Validar aplicación de comisión

**Test Manual:**
1. Crear propietario: "Juan Pérez"
2. IBAN: ES9121000418450200051332
3. Comisión: 15%
4. Asignar propiedad
5. Crear pedido de 1000€
6. Verificar ingresos: 850€ (1000 - 15%)

---

#### 3. CRM de Huéspedes (`crm-guests`)
**Archivo:** `includes/modules/crm-guests/crm-guests.php`

- [ ] **User Meta Fields**
  - [ ] Crear cliente (usuario)
  - [ ] Editar usuario y verificar campos:
    - [ ] Valoración (1-5 estrellas)
    - [ ] Preferencias (checkboxes múltiples)
    - [ ] Notas privadas (WYSIWYG)

- [ ] **Columnas en Listado**
  - [ ] Rating con estrellas visuales
  - [ ] Total gastado calculado
  - [ ] Última reserva

**Test Manual:**
1. Crear usuario: "María García"
2. Asignar valoración: 5 estrellas
3. Marcar preferencias: Piscina, WiFi, Mascotas
4. Crear pedido completado de 500€
5. Verificar en listado: ⭐⭐⭐⭐⭐ | 500,00 €

---

#### 4. Pipeline de Reservas (`booking-pipeline`)
**Archivo:** `includes/modules/booking-pipeline/pipeline.php`

- [ ] **Estados Personalizados**
  - [ ] `wc-deposito-ok` (Depósito OK)
  - [ ] `wc-pending-checkin` (Pendiente Check-in)
  - [ ] `wc-in-progress` (En Curso)
  - [ ] `wc-checkout-review` (Revisión Salida)
  - [ ] `wc-deposit-refunded` (Depósito Devuelto)

- [ ] **Transiciones de Estado**
  - [ ] Pedido pending → deposito-ok
  - [ ] deposito-ok → pending-checkin
  - [ ] pending-checkin → in-progress (día check-in)
  - [ ] in-progress → checkout-review (día check-out)
  - [ ] checkout-review → completed

**Test de Workflow Completo:**
1. Crear pedido manual
2. Cambiar estado a "Depósito OK"
3. Asignar fechas: check-in hoy, check-out +3 días
4. Cambiar a "Pendiente Check-in"
5. Cambiar a "En Curso"
6. Cambiar a "Revisión Salida"
7. Finalizar: "Completado"

---

### Fase 2: Personalización UI Backend ✅

#### 5. Dashboard Widgets (`dashboard-widgets`)
**Archivo:** `includes/modules/dashboard-widgets/dashboard-widgets.php`

- [ ] **Widget: Movimientos de Hoy**
  - [ ] Mostrar check-ins hoy
  - [ ] Mostrar check-outs hoy
  - [ ] Verificar enlaces a Pipeline

- [ ] **Widget: Ingresos del Mes**
  - [ ] Calcular ingresos mes actual
  - [ ] Comparar con mes anterior
  - [ ] Mostrar porcentaje de cambio

- [ ] **Widget: Estado de Propiedades**
  - [ ] Calcular tasa de ocupación hoy
  - [ ] Mostrar propiedades ocupadas/total

- [ ] **Widget: Alertas**
  - [ ] Mostrar pedidos pendientes
  - [ ] Mostrar propietarios sin IBAN
  - [ ] Mostrar propiedades en revisión

**Validación:**
```bash
# Crear datos de prueba
wp post create --post_type=shop_order --post_status=wc-completed --post_title="Test Order"
```

---

#### 6. Pipeline Kanban (`pipeline-kanban`)
**Archivo:** `includes/modules/pipeline-kanban/pipeline-kanban.php`

- [ ] **Vista Tablero**
  - [ ] Acceder a ALQUIPRESS → Pipeline Kanban
  - [ ] Verificar 7 columnas de estados
  - [ ] Comprobar tarjetas de pedidos

- [ ] **Funcionalidades**
  - [ ] Buscar por cliente/propiedad
  - [ ] Filtrar por rango de fechas
  - [ ] Filtrar por propiedad específica
  - [ ] Ver badge "URGENTE" en check-ins < 3 días
  - [ ] Smooth scroll horizontal

- [ ] **Tarjetas de Pedido**
  - [ ] Mostrar nombre cliente
  - [ ] Mostrar propiedad
  - [ ] Mostrar fechas check-in/check-out
  - [ ] Mostrar total del pedido
  - [ ] Enlace a editar pedido

**Test Visual:**
1. Crear 5 pedidos en diferentes estados
2. Verificar distribución en columnas
3. Buscar por nombre de cliente
4. Aplicar filtro de fechas

---

#### 7. Order Columns (`order-columns`)
**Archivo:** `includes/modules/order-columns/order-columns.php`

- [ ] **Columnas Personalizadas en Pedidos**
  - [ ] Propiedad (nombre producto)
  - [ ] Fechas (check-in → check-out)
  - [ ] Propietario (vinculado)
  - [ ] Semáforo (estado visual)

- [ ] **Compatibilidad HPOS**
  - [ ] Funciona con HPOS activado
  - [ ] Funciona con HPOS desactivado

---

#### 8. Perfil de Huésped (`guest-profile`)
**Archivo:** `includes/modules/guest-profile/guest-profile.php`

- [ ] **Vista de Perfil**
  - [ ] Acceder desde Usuarios → Ver Perfil CRM
  - [ ] Verificar header con avatar y datos
  - [ ] Verificar tarjetas de estadísticas (4)
  - [ ] Ver historial de reservas
  - [ ] Ver preferencias del huésped
  - [ ] Ver notas privadas

- [ ] **Cálculos**
  - [ ] Total gastado correcto
  - [ ] Total reservas correcto
  - [ ] Última reserva correcta
  - [ ] Rating visible

---

#### 9. Editor de Huésped (`guest-editor`)
**Archivo:** `includes/modules/guest-editor/guest-editor.php`

- [ ] **Formulario Mejorado**
  - [ ] Acceder desde Usuarios → Editar Huésped
  - [ ] Layout 2 columnas
  - [ ] Estrellas de rating interactivas (preview en tiempo real)
  - [ ] Checkboxes visuales para preferencias
  - [ ] Editor WYSIWYG para notas

- [ ] **Guardado**
  - [ ] Guardar cambios
  - [ ] Verificar datos persistidos
  - [ ] Validación de nonce

---

#### 10. UI Enhancements (`ui-enhancements`)
**Archivo:** `includes/modules/ui-enhancements/ui-enhancements.php`

- [ ] **Estilos Globales**
  - [ ] Pestaña "Datos Financieros" con warning
  - [ ] Campo IBAN resaltado
  - [ ] Badges de estado coloreados
  - [ ] Grid de preferencias visual

- [ ] **Páginas Afectadas**
  - [ ] Edición de propietario
  - [ ] Edición de usuario/huésped

---

### Fase 3: Funcionalidades Avanzadas ✅

#### 11. Preferencias Avanzadas (`advanced-preferences`)
**Archivo:** `includes/modules/advanced-preferences/advanced-preferences.php`

- [ ] **Widget en Dashboard**
  - [ ] Ver top 5 preferencias
  - [ ] Barras de progreso correctas
  - [ ] Botón "Ver Análisis Completo"

- [ ] **Modal de Análisis**
  - [ ] Abrir modal AJAX
  - [ ] Ver todas las preferencias con estadísticas
  - [ ] Porcentajes correctos

- [ ] **Columna en Usuarios**
  - [ ] Íconos de preferencias visibles
  - [ ] Tooltip al hover

- [ ] **Shortcode** `[guest_preferences]`
  - [ ] Insertar en página
  - [ ] Estilo: icons
  - [ ] Estilo: badges
  - [ ] Estilo: list

**Test:**
```
[guest_preferences style="icons"]
[guest_preferences style="badges"]
[guest_preferences style="list"]
```

---

#### 12. Acciones Rápidas (`quick-actions`)
**Archivo:** `includes/modules/quick-actions/quick-actions.php`

- [ ] **Admin Bar Menu**
  - [ ] Ver menú "⚡ ALQUIPRESS"
  - [ ] Pipeline de Reservas
  - [ ] Nueva Reserva
  - [ ] Ver Pedidos
  - [ ] Propietarios
  - [ ] Huéspedes
  - [ ] Propiedades
  - [ ] Check-ins Hoy (contador)
  - [ ] Check-outs Hoy (contador)

- [ ] **Atajos de Teclado**
  - [ ] `Ctrl+K` → Búsqueda
  - [ ] `Ctrl+P` → Pipeline
  - [ ] `Ctrl+H` → Dashboard
  - [ ] `Shift` → Mostrar tooltip de atajos

- [ ] **Vista Rápida de Pedidos**
  - [ ] Botón "👁️ Vista Rápida" en listado
  - [ ] Abrir modal con datos del pedido
  - [ ] Ver cliente, propiedad, fechas, pagos
  - [ ] Enlace "Editar Pedido Completo"

**Test de Atajos:**
1. Presionar `Shift` → Ver tooltip
2. Presionar `Ctrl+P` → Ir a Pipeline
3. Presionar `Ctrl+H` → Ir a Dashboard
4. En listado de pedidos → Click en "Vista Rápida"

---

#### 13. Notificaciones CRM (`crm-notifications`)
**Archivo:** `includes/modules/crm-notifications/crm-notifications.php`

- [ ] **Notificaciones Automáticas**
  - [ ] Check-ins hoy (con pedido de hoy)
  - [ ] Check-outs hoy (con pedido de hoy)
  - [ ] Check-ins mañana (recordatorio)
  - [ ] Pedidos pendientes ≥5
  - [ ] Propietarios sin IBAN
  - [ ] Propiedades en revisión de salida

- [ ] **Interacción**
  - [ ] Descartar notificación (X)
  - [ ] Persistencia (no volver a mostrar)
  - [ ] Badge contador en menú ALQUIPRESS
  - [ ] Botón de acción funcional

- [ ] **Diseño**
  - [ ] Íconos emoji correctos
  - [ ] Colores según tipo
  - [ ] Animación de entrada

**Test de Notificaciones:**
1. Crear pedido con check-in hoy
2. Recargar dashboard → Ver notificación
3. Descartar → No volver a aparecer
4. Verificar badge en menú

---

### Fase 4: Informes y Dashboards ✅

#### 14. Informes y Analíticas (`advanced-reports`)
**Archivo:** `includes/modules/advanced-reports/advanced-reports.php`

- [ ] **Acceso**
  - [ ] ALQUIPRESS → 📊 Informes

- [ ] **Estadísticas Rápidas**
  - [ ] Ingresos del año
  - [ ] Total reservas del año
  - [ ] Valor medio por reserva
  - [ ] Tasa de ocupación

- [ ] **Tab Ingresos**
  - [ ] Gráfico línea: Ingresos mensuales
  - [ ] Gráfico dona: Ingresos por temporada

- [ ] **Tab Ocupación**
  - [ ] Gráfico barras: Ocupación mensual %
  - [ ] Gráfico pie: Noches reservadas vs disponibles

- [ ] **Tab Clientes**
  - [ ] Tabla Top 5 clientes (medallas 🥇🥈🥉)
  - [ ] Gráfico barras: Distribución por rating

- [ ] **Tab Propiedades**
  - [ ] Tabla Top 5 propiedades
  - [ ] Gráfico horizontal: Comparativa ingresos

- [ ] **Funcionalidades**
  - [ ] Selector de año
  - [ ] Botón "Actualizar Informes"
  - [ ] Todos los gráficos Chart.js renderizados
  - [ ] Responsive en móvil

**Test Completo:**
1. Crear 10 pedidos completados del año actual
2. Asignar a diferentes clientes
3. Asignar a diferentes propiedades
4. Ir a Informes
5. Verificar todos los gráficos cargan
6. Cambiar año → Actualizar
7. Navegar entre tabs

---

## Workflows Críticos

### Workflow 1: Ciclo Completo de Reserva

**Objetivo:** Simular reserva desde creación hasta finalización

1. [ ] **Creación de Reserva**
   - Crear pedido manual en WooCommerce
   - Asignar cliente existente
   - Añadir producto (propiedad)
   - Asignar fechas: check-in y check-out

2. [ ] **Depósito Recibido**
   - Cambiar estado a "Depósito OK"
   - Verificar aparece en Pipeline Kanban

3. [ ] **Preparación Check-in**
   - Cambiar a "Pendiente Check-in"
   - Verificar notificación 1 día antes
   - Verificar aparece en "Check-ins Mañana"

4. [ ] **Check-in Realizado**
   - El día del check-in, cambiar a "En Curso"
   - Verificar notificación "Check-ins Hoy"
   - Verificar aparece en admin bar contador

5. [ ] **Check-out y Revisión**
   - El día del check-out, cambiar a "Revisión Salida"
   - Verificar notificación "Check-outs Hoy"

6. [ ] **Finalización**
   - Revisar propiedad
   - Cambiar a "Completado"
   - Verificar aparece en historial del huésped
   - Verificar suma en "Total Gastado"
   - Verificar aparece en informes

---

### Workflow 2: Gestión de Huésped

**Objetivo:** Crear y gestionar perfil completo de huésped

1. [ ] **Creación**
   - Crear usuario rol "Customer"
   - Asignar email y datos básicos

2. [ ] **Edición de Perfil**
   - Ir a Usuarios → Editar Huésped
   - Asignar valoración: 4 estrellas
   - Marcar preferencias: Piscina, WiFi, Parking
   - Añadir nota privada: "Cliente VIP, requiere late check-out"
   - Guardar

3. [ ] **Visualización**
   - Ir a Usuarios → Ver Perfil CRM
   - Verificar datos correctos
   - Verificar estadísticas

4. [ ] **Crear Reserva**
   - Crear pedido para este cliente
   - Completar pedido

5. [ ] **Verificar Actualización**
   - Volver a Ver Perfil CRM
   - Verificar "Total Gastado" actualizado
   - Verificar "Última Reserva" actualizada
   - Verificar reserva en historial

---

### Workflow 3: Informes Mensuales

**Objetivo:** Generar informe completo del mes

1. [ ] **Preparación**
   - Asegurar hay pedidos del mes actual
   - Ir a ALQUIPRESS → Informes

2. [ ] **Análisis de Ingresos**
   - Verificar ingresos del mes en stats
   - Ver gráfico de ingresos mensuales
   - Identificar temporada actual

3. [ ] **Análisis de Ocupación**
   - Ver tasa de ocupación del mes
   - Ver comparativa noches reservadas/disponibles

4. [ ] **Top Performers**
   - Identificar top 5 clientes
   - Identificar top 5 propiedades
   - Exportar datos (captura de pantalla)

---

## Checklist de Funcionalidades

### Taxonomías
- [x] Población (33 términos Marina Alta)
- [x] Zona (4 términos)
- [x] Características (27 términos con íconos)

### CPTs
- [x] Propietarios con ACF
- [x] Datos financieros (IBAN, comisión)

### User Meta
- [x] Rating de huéspedes
- [x] Preferencias (10 tipos)
- [x] Notas privadas

### Estados de Pedidos
- [x] 5 estados personalizados de pipeline

### Páginas de Admin
- [x] Pipeline Kanban
- [x] Perfil de Huésped
- [x] Editor de Huésped
- [x] Informes y Analíticas

### Widgets
- [x] Dashboard Widgets (4)
- [x] Widget de Preferencias Avanzadas

### Notificaciones
- [x] 6 tipos de alertas automáticas

### Atajos
- [x] Admin bar personalizado
- [x] Keyboard shortcuts (3)

### Informes
- [x] 9 visualizaciones con Chart.js

---

## Testing de Rendimiento

### Queries de Base de Datos

**Verificar optimización de queries:**

```php
// Activar query monitor
define('SAVEQUERIES', true);

// Verificar queries en:
// - Dashboard
// - Pipeline Kanban
// - Informes
// - Listado de pedidos
```

**Límites aceptables:**
- Dashboard: < 50 queries
- Pipeline Kanban: < 100 queries
- Informes: < 150 queries (por AJAX)
- Listado pedidos: < 30 queries

---

### Carga de Assets

**Verificar que CSS/JS solo se cargan donde se necesitan:**

```bash
# Inspeccionar página
wp-admin/index.php (Dashboard)
  → dashboard-widgets.css ✓
  → advanced-preferences.css ✓

wp-admin/admin.php?page=alquipress-pipeline
  → pipeline-kanban.css ✓
  → pipeline-kanban.js ✓

wp-admin/admin.php?page=alquipress-reports
  → advanced-reports.css ✓
  → advanced-reports.js ✓
  → chart.js (CDN) ✓
```

---

### Caché

**Implementar transients para datos pesados:**

```php
// Ejemplo: Cachear top clientes por 1 hora
$top_clients = get_transient('alquipress_top_clients_' . $year);
if (false === $top_clients) {
    $top_clients = $this->calculate_top_clients($year);
    set_transient('alquipress_top_clients_' . $year, $top_clients, HOUR_IN_SECONDS);
}
```

**Áreas a cachear:**
- [ ] Top clientes (1 hora)
- [ ] Top propiedades (1 hora)
- [ ] Estadísticas de preferencias (1 hora)
- [ ] Ingresos mensuales (1 día)

---

## Casos de Prueba

### Test Case 1: Crear Propietario Completo

**Precondiciones:** Plugin activado

**Pasos:**
1. Ir a Propietarios → Añadir nuevo
2. Título: "Antonio Martínez López"
3. ACF Campos:
   - Nombre: Antonio
   - Apellidos: Martínez López
   - Email: antonio@example.com
   - Teléfono: 666555444
4. Datos Financieros:
   - IBAN: ES6621000418401234567891
   - Comisión: 20%
5. Publicar

**Resultado Esperado:**
- CPT creado exitosamente
- IBAN visible con máscara en listado: ES** **** **** **** ****91
- Email y teléfono visibles en columnas

---

### Test Case 2: Pipeline Completo de Reserva

**Precondiciones:**
- Cliente creado
- Propiedad publicada

**Pasos:**
1. Crear pedido manual
2. Asignar cliente
3. Añadir producto (propiedad)
4. Añadir fechas personalizadas:
   - Check-in: mañana
   - Check-out: +3 días
5. Estado: Depósito OK
6. Al día siguiente:
   - Cambiar a "Pendiente Check-in"
   - Verificar notificación "Check-ins Hoy"
7. Cambiar a "En Curso"
8. 3 días después:
   - Verificar notificación "Check-outs Hoy"
   - Cambiar a "Revisión Salida"
9. Cambiar a "Completado"

**Resultado Esperado:**
- Notificaciones aparecen en momentos correctos
- Estados cambian sin errores
- Pedido aparece en Pipeline Kanban en columna correcta
- Al completar, suma en estadísticas del cliente

---

### Test Case 3: Generación de Informe Anual

**Precondiciones:**
- Mínimo 5 pedidos completados en el año
- Múltiples clientes y propiedades

**Pasos:**
1. Ir a ALQUIPRESS → Informes
2. Seleccionar año actual
3. Hacer clic en "Actualizar Informes"
4. Navegar por todos los tabs
5. Verificar cada gráfico

**Resultado Esperado:**
- Todos los gráficos renderizan sin errores
- Datos coherentes entre tabs
- Top 5 tablas pobladas
- Sin errores en consola JavaScript

---

## Checklist Final de Producción

### Pre-Deploy

- [ ] Todos los módulos testeados individualmente
- [ ] Workflows críticos validados
- [ ] Rendimiento verificado (< 2s carga Dashboard)
- [ ] Sin errores PHP en logs
- [ ] Sin errores JavaScript en consola
- [ ] Responsive verificado en móvil/tablet
- [ ] Compatibilidad navegadores (Chrome, Firefox, Safari)

### Deploy

- [ ] Backup completo de base de datos
- [ ] Backup de archivos del plugin
- [ ] Migrar código a producción
- [ ] Activar plugin en producción
- [ ] Activar todos los módulos necesarios
- [ ] Verificar ACF fields cargados
- [ ] Verificar taxonomías creadas

### Post-Deploy

- [ ] Test rápido de funcionalidades críticas
- [ ] Verificar Dashboard carga correctamente
- [ ] Crear pedido de prueba
- [ ] Verificar notificaciones funcionan
- [ ] Verificar informes cargan
- [ ] Monitorear logs primeras 24h

---

## Contacto y Soporte

**Desarrollador:** Claude Code
**Fecha de Implementación:** 2026-01-23
**Versión del Plugin:** 1.0.0

Para reportar bugs o solicitar mejoras, contactar al equipo de desarrollo.
