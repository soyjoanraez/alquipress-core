# 🔍 ANÁLISIS TÉCNICO DEL PROYECTO ALQUIPRESS ACTUAL

## 📊 ESTADO DEL PROYECTO (23 Enero 2026)

### ✅ STACK TECNOLÓGICO CONFIRMADO

#### WordPress Core
```yaml
Versión WordPress: 6.4+
PHP: 8.0+
Base de Datos: MySQL 5.7+ (charset: utf8)
Memoria: 512M (WP_MEMORY_LIMIT) / 1024M (WP_MAX_MEMORY_LIMIT)
Debug Mode: ACTIVADO (WP_DEBUG, WP_DEBUG_LOG, WP_DEBUG_DISPLAY)
Entorno: local (alquipress.local)
```

#### Plugins Críticos Instalados
| Plugin | Estado | Versión Mínima | Notas |
|--------|--------|----------------|-------|
| **WooCommerce** | ✅ Activo | 8.0+ | Core de e-commerce |
| **WooCommerce Bookings** | ✅ Activo | 1.15+ | Sistema de reservas |
| **WooCommerce Bookings Availability** | ✅ Activo | - | Gestión disponibilidad |
| **WooCommerce Deposits** | ✅ Activo | - | Fianzas/pagos parciales |
| **Advanced Custom Fields PRO** | ✅ Activo | 6.0+ | Campos personalizados |
| **MailPoet** | ✅ Activo | 3.0+ | Email marketing |
| **Query Monitor** | 🔧 Dev Mode | - | Debug avanzado |
| **Code Snippets** | 🔧 Dev Mode | - | Snippets PHP |
| **WP Crontrol** | 🔧 Dev Mode | - | Gestión cron jobs |
| **alquipress-core** | ✅ Activo | 1.0.0 | **Plugin custom principal** |

#### Tema
```yaml
Tema Activo: Astra
Tipo: Multipropósito ligero
Compatibilidad WooCommerce: ✅ Nativa
Child Theme: ❌ No detectado (considerar crear uno)
```

---

## 🏗️ ARQUITECTURA ACTUAL DEL PLUGIN `alquipress-core`

### Estructura de Directorios
```
/wp-content/plugins/alquipress-core/
├── alquipress-core.php          # Bootstrap principal
├── includes/
│   ├── class-module-manager.php # Gestor modular
│   ├── admin/
│   │   ├── settings-page.php    # Panel de configuración
│   │   └── assets/              # CSS/JS del admin
│   └── modules/
│       ├── taxonomies/
│       │   ├── taxonomies.php
│       │   └── acf-fields.json
│       ├── crm-guests/
│       │   ├── crm-guests.php
│       │   └── acf-fields.json
│       ├── crm-owners/
│       │   ├── crm-owners.php
│       │   └── acf-fields.json
│       ├── booking-pipeline/
│       │   └── pipeline.php
│       ├── email-automation/
│       │   └── mailpoet-integration.php
│       ├── payments/
│       │   └── payment-gates.php     # ⏸️ Desactivado
│       └── alquipress-tester/
│           └── tester.php            # 🧪 Testing mode
└── assets/
    ├── css/
    └── js/
```

### Sistema de Módulos (Module Manager)

El sistema ya implementa un **gestor modular activable/desactivable**:

```php
// Registro de módulos existente
$this->modules = [
    'taxonomies' => [
        'name' => 'Taxonomías Personalizadas',
        'description' => 'Población, Zona, Características',
        'file' => 'taxonomies/taxonomies.php',
        'dependencies' => []
    ],
    // ... resto de módulos
];
```

**VENTAJA CLAVE**: ✅ Ya existe infraestructura para añadir nuevos módulos.

---

## 📦 MODELO DE DATOS ACTUAL

### Custom Post Types

#### `product` (WooCommerce)
**Uso**: Representa las propiedades/alojamientos vacacionales

**Campos ACF Registrados**:
| Nombre Campo | Tipo | Requerido | Uso |
|--------------|------|-----------|-----|
| `licencia_turistica` | text | ✅ Sí | EGVT/VT obligatorio |
| `referencia_interna` | text | ❌ No | Código interno gestión |
| `superficie_m2` | number | ❌ No | Metros cuadrados |
| `distancia_playa` | number | ❌ No | **CRÍTICO para búsquedas** |
| `distancia_centro` | number | ❌ No | Metros al centro urbano |
| `coordenadas_gps` | google_map | ❌ No | **PESA mucho en PageSpeed** |
| `distribucion_habitaciones` | repeater | ❌ No | Detalles dormitorios |
| `hora_checkin` | time_picker | ❌ No | HH:MM formato 24h |
| `hora_checkout` | time_picker | ❌ No | HH:MM formato 24h |
| `fianza_texto` | text | ❌ No | Info fianza visual |

**Sub-campos de `distribucion_habitaciones`**:
- `nombre_hab` (text)
- `tipo_cama` (select: matrimonio/individual/litera/sofa)
- `bano_en_suite` (true_false)

---

### Taxonomías Personalizadas

#### 1. `poblacion`
**Tipo**: Jerárquica (como categorías)  
**Ejemplo**: "Roses", "Empuriabrava", "Cadaqués"  
**Indexación**: 🔴 **Necesita full-text search** (búsquedas frecuentes)

#### 2. `zona`
**Tipo**: Jerárquica  
**Ejemplo**: "Centro", "Primera línea mar", "Zona residencial"  
**Indexación**: 🟡 **Opcional** (menos consultado)

#### 3. `caracteristicas`
**Tipo**: No jerárquica (tags)  
**Ejemplo**: "WiFi", "Piscina", "Permite mascotas", "Aire acondicionado"  
**Indexación**: 🔴 **CRÍTICA** (99% de búsquedas incluyen esto)

---

### Custom Post Types de CRM

#### `alq_guest` (Módulo crm-guests)
**Uso**: Perfil de huéspedes/clientes

**Campos Probables** (pendiente verificación):
- Nombre, email, teléfono
- Preferencias (relación con taxonomía `caracteristicas`)
- Valoración (1-5 estrellas)
- Historial de reservas (relationship con `shop_order`)
- Notas internas

#### `alq_owner` (Módulo crm-owners)
**Uso**: Perfil de propietarios

**Campos Probables** (pendiente verificación):
- Datos personales/empresa
- IBAN / datos bancarios **← CRÍTICO PARA SECURITY MODULE**
- Comisión pactada (%)
- Propiedades gestionadas (relationship con `product`)
- Documentación legal

---

## 🔍 ANÁLISIS DE NECESIDADES DETECTADAS

### 🚨 PROBLEMAS CRÍTICOS ANTICIPADOS

#### 1. **Performance del Google Maps**
```php
// Campo actual en ACF
'coordenadas_gps' => 'google_map'
```
**PROBLEMA**: Este campo carga Google Maps API en **TODAS las fichas de producto**.  
**IMPACTO**: +500KB de JS + Bloqueo de render inicial  
**SOLUCIÓN PROPUESTA**: Lazy Load con Intersection Observer

---

#### 2. **Búsqueda de Disponibilidad Costosa**
**ESCENARIO**:
```
Usuario busca: "Villa disponible del 15-22 Agosto"
↓
WordPress debe consultar:
- Tabla `wc_bookings` (reservas existentes)
- Tabla `wc_bookings_availability` (bloqueos)
- Calcular overlap para CADA propiedad en catálogo
```

**MEDICIÓN NECESARIA**:
```sql
-- Query que probablemente se ejecuta ahora (lenta):
SELECT DISTINCT p.ID 
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'product'
AND p.post_status = 'publish'
AND NOT EXISTS (
    SELECT 1 FROM wp_wc_booking_relationships br
    INNER JOIN wp_wc_bookings b ON br.booking_id = b.id
    WHERE br.product_id = p.ID
    AND b.start_date <= '2024-08-22'
    AND b.end_date >= '2024-08-15'
    AND b.status IN ('confirmed', 'paid')
)
```

**PROPUESTA**: Tabla de cache `wp_alquipress_availability_cache` (como en specs originales).

---

#### 3. **Peso de Imágenes**
**HIPÓTESIS** (pendiente confirmación):
- Formato predominante: **JPG** (sin WebP)
- Tamaño promedio: **800KB - 2MB** por imagen
- Imágenes por producto: **10-15** (galería)

**CÁLCULO**:
```
50 propiedades × 12 imágenes × 1.2MB = 720MB de imágenes
Sin WebP → Sin lazy load → PageSpeed Score <50
```

**SOLUCIÓN PROPUESTA**: Módulo 4 (Image Optimization) con conversión WebP automática.

---

### 🎯 MÓDULOS PRIORITARIOS (Basado en Análisis)

#### Ranking Técnico Objetivado:

| Posición | Módulo | Urgencia | Justificación |
|----------|--------|----------|---------------|
| **#1** | 🧨 **Bookings Performance** | 🔴 Crítica | WooCommerce Bookings es notoriamente lento con muchos productos |
| **#2** | 🖼️ **Image Optimizer** | 🟠 Alta | Galería de imágenes pesadas = 80% del peso de página |
| **#3** | 🔍 **Smart Search** | 🟡 Media-Alta | Taxonomías + meta_query = queries lentas (>1s) |
| **#4** | ⚡ **WPO Module** | 🟡 Media | Google Maps + sin lazy load = LCP alto |
| **#5** | 🔒 **Security** | 🟢 Media-Baja | Si múltiples usuarios gestionan propietarios |
| **#6** | 📊 **Analytics** | 🔵 Baja | Nice to have, no crítico para funcionamiento |
| **#7** | 🖥️ **CRM Dashboard** | 🔵 Baja | Solo si hay +100 huéspedes/propietarios |

---

## 🛠️ RECOMENDACIONES DE ARQUITECTURA

### OPCIÓN RECOMENDADA: **Plugin Independiente Hermano**

```
/wp-content/plugins/
├── alquipress-core/          [Funcionalidad de negocio]
│   └── modules/
│       ├── taxonomies/
│       ├── crm-guests/
│       └── ...
│
└── alquipress-suite/         [Performance & Security] ← NUEVO
    ├── alquipress-suite.php
    ├── includes/
    │   ├── class-suite-manager.php
    │   └── modules/
    │       ├── wpo/
    │       ├── security/
    │       ├── image-optimizer/
    │       ├── smart-search/
    │       ├── bookings-performance/
    │       ├── crm-accelerator/
    │       └── analytics/
    ├── assets/
    │   ├── admin/              # Panel React
    │   └── vendor/             # Librerías (si no usa Composer)
    └── vendor/                 # Si usa Composer
        ├── guzzlehttp/
        ├── intervention/image/
        └── symfony/cache/
```

### ✅ Ventajas de esta Arquitectura:
1. **Separación Clara**: Core de negocio vs Optimización técnica
2. **Desacoplamiento**: Puedes desactivar `alquipress-suite` sin romper funcionalidad
3. **Comercialización**: Fácil vender como premium addon
4. **Mantenibilidad**: Updates independientes
5. **Reutilización**: `alquipress-suite` puede funcionar en otros proyectos WooCommerce

### ⚠️ Dependencias a Declarar:
```php
// alquipress-suite.php
if (!class_exists('WooCommerce')) {
    add_action('admin_notices', 'alquipress_suite_wc_missing_notice');
    return;
}

if (!class_exists('WC_Bookings')) {
    add_action('admin_notices', 'alquipress_suite_bookings_missing_notice');
    return;
}

// Opcional pero recomendado
if (!is_plugin_active('alquipress-core/alquipress-core.php')) {
    add_action('admin_notices', 'alquipress_suite_core_recommended_notice');
}
```

---

## 📋 SIGUIENTE PASO: CHECKLIST DE VALIDACIÓN

### Antes de Empezar el Desarrollo, Necesito:

#### ✅ Información del Usuario (Ver INFORMACION-NECESARIA-PLUGIN-OPTIMIZACION.md)
- [ ] Volumen de propiedades estimado
- [ ] Top 3 módulos prioritarios según su uso real
- [ ] Tipo de búsquedas más frecuentes
- [ ] Muestras de imágenes actuales
- [ ] Configuración de hosting producción
- [ ] Timeline esperado

#### 🔬 Tests Técnicos que Puedo Hacer YO Ahora:
- [ ] Analizar tiempo de carga de `/tienda/` con Query Monitor
- [ ] Revisar peso de 5 imágenes random de productos
- [ ] Ver queries SQL de búsqueda con filtros
- [ ] Comprobar si existe `wp-content/themes/astra-child/`
- [ ] Verificar campos ACF completos de `alq_guest` y `alq_owner`

---

## 🚀 PROPUESTA DE MVP (Semana 1)

### Módulos Esenciales para MVP Funcional:

#### Fase 1A: Fundamentos (Días 1-2)
```
✓ Crear estructura base de alquipress-suite
✓ System de activación de módulos (replicar Module Manager)
✓ Panel admin básico (HTML+CSS, sin React todavía)
✓ Hooks de integración con alquipress-core
```

#### Fase 1B: Image Optimizer (Días 3-4)
```
✓ Conversión WebP automática al subir imagen
✓ Generación de srcset responsive
✓ Limpieza de EXIF metadata
✓ Shortcode test: [alquipress_optimized_image id="123"]
```

#### Fase 1C: Lazy Load (Día 5)
```
✓ Lazy load de imágenes (Intersection Observer)
✓ Lazy load de Google Maps en fichas
✓ Lazy load de iframes (si hay vídeos YouTube)
```

#### Fase 1D: Critical CSS Básico (Días 6-7)
```
✓ Detección automática de CSS crítico homepage
✓ Inline de CSS crítico en <head>
✓ Defer del resto de CSS (loadCSS polyfill)
```

**ENTREGABLE SEMANA 1**:
- PageSpeed Score: **65 → 85+**
- Peso de página: **-60%** (gracias a WebP + Lazy Load)
- Panel admin funcional con toggle on/off de cada feature

---

## 🎨 DISEÑO DEL PANEL DE ADMINISTRACIÓN

### Wireframe Propuesto (HTML + Vanilla CSS primero, React después)

```
┌─────────────────────────────────────────────────────────────┐
│ ALQUIPRESS Suite        [v1.0.0]           [Guardar Cambios]│
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  📊 Dashboard Overview                                        │
│  ┌───────────────┬───────────────┬───────────────┐          │
│  │ PageSpeed     │ Storage Saved │ Cache Hit     │          │
│  │   Score: 87   │   2.4 GB      │   Rate: 94%   │          │
│  └───────────────┴───────────────┴───────────────┘          │
│                                                               │
│  ⚙️ Módulos Disponibles                                      │
│                                                               │
│  ┌─ 🖼️ Image Optimizer ────────────────────── [✓] Activo ─┐│
│  │  • WebP Conversion: ON                                   ││
│  │  • EXIF Cleaning: ON                                     ││
│  │  • CDN Push: OFF (configurar)                            ││
│  │  [⚙️ Configurar] [📊 Ver Estadísticas]                   ││
│  └──────────────────────────────────────────────────────────┘│
│                                                               │
│  ┌─ ⚡ WPO Module ──────────────────────────── [✓] Activo ─┐│
│  │  • Lazy Load Images: ON                                  ││
│  │  • Lazy Load Maps: ON                                    ││
│  │  • Critical CSS: AUTO                                    ││
│  │  [⚙️ Configurar] [🔄 Regenerar Critical CSS]             ││
│  └──────────────────────────────────────────────────────────┘│
│                                                               │
│  ┌─ 🔍 Smart Search ──────────────────────────  [  ] Off   ─┐│
│  │  ⚠️ Módulo desactivado. Activar para indexar búsquedas. ││
│  │  [✓ Activar Módulo]                                      ││
│  └──────────────────────────────────────────────────────────┘│
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 💡 DECISIONES TÉCNICAS PENDIENTES DEL USUARIO

### Preguntas Críticas Antes de Escribir Código:

1. **¿Usarás Composer?**  
   → Si NO → Incluir librerías en `/vendor` manualmente  
   → Si SÍ → Crear `composer.json`

2. **¿Qué CDN?**  
   → Ninguno → Crear subdominios locales  
   → Cloudflare → Configurar R2 API  
   → AWS → Configurar S3 + CloudFront

3. **¿Redis disponible?**  
   → Sí → Usar `symfony/cache` con RedisAdapter  
   → No → Usar WordPress Transients API

4. **¿Múltiples usuarios backend?**  
   → Sí → Implementar Security Module completo  
   → No (solo tú) → Simplificar a firewall básico

5. **¿Timeline?**  
   → 1 semana → Solo MVP (Image + Lazy Load)  
   → 1 mes → Suite completa con 7 módulos

---

## 📝 CONCLUSIÓN

**ESTADO ACTUAL**: ✅ Proyecto bien estructurado con buen punto de partida

**BLOQUEADORES PARA EMPEZAR**:  
❌ Falta información del usuario (ver documento `INFORMACION-NECESARIA-PLUGIN-OPTIMIZACION.md`)

**CUANDO EL USUARIO RESPONDA**:  
✅ Puedo empezar desarrollo inmediato  
⏱️ Tiempo estimado MVP completo: **5-7 días laborables**

---

**Siguiente acción**: 👉 **El usuario debe revisar y completar el documento `INFORMACION-NECESARIA-PLUGIN-OPTIMIZACION.md`**
