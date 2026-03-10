# 📋 INFORMACIÓN NECESARIA PARA EL PLUGIN DE OPTIMIZACIÓN ALQUIPRESS

## ✅ LO QUE YA SÉ (Análisis del Proyecto Actual)

### Infraestructura Existente:
- **Plugin Base**: `alquipress-core` v1.0.0 con arquitectura modular
- **WordPress**: 6.4+ (con debugging activado)
- **PHP**: 8.0+
- **Base de Datos**: MySQL con charset utf8
- **WooCommerce**: Instalado ✓
- **WooCommerce Bookings**: Instalado ✓
- **WooCommerce Deposits**: Instalado ✓
- **Advanced Custom Fields PRO**: Instalado ✓
- **MailPoet**: Instalado ✓
- **Tema Activo**: Astra (tema popular compatible con WooCommerce)
- **Entorno**: Local (Local by Flywheel)

### Módulos Activos en alquipress-core:
1. ✅ **taxonomies** - Población, Zona, Características
2. ✅ **crm-guests** - Gestión de huéspedes con preferencias
3. ✅ **crm-owners** - Gestión de propietarios con datos financieros
4. ✅ **booking-pipeline** - Estados personalizados de pedidos
5. ✅ **email-automation** - Integración con MailPoet
6. ⏸️ **payments** - Stripe + Redsys (Desactivado)
7. 🧪 **alquipress-tester** - Generador de datos de prueba (Desactivado)

### Campos Personalizados en Productos (Propiedades):
- `licencia_turistica` (Obligatorio)
- `referencia_interna`
- `superficie_m2`
- `distancia_playa` (metros)
- `distancia_centro` (metros)
- `coordenadas_gps` (Google Maps)
- `distribucion_habitaciones` (Repeater con tipo_cama, baño_suite)
- `hora_checkin` / `hora_checkout`
- `fianza_texto`

### Taxonomías Personalizadas:
- `poblacion` (Localización principal)
- `zona` (Subzona dentro de población)
- `caracteristicas` (WiFi, Piscina, Mascotas, etc.)

---

## ❓ INFORMACIÓN CRÍTICA QUE NECESITO

### 🎯 1. ESTRATEGIA DE IMPLEMENTACIÓN

#### 1.1 ¿Arquitectura del Plugin?
**OPCIÓN A**: Plugin Independiente Nuevo
```
/wp-content/plugins/
├── alquipress-core/          (Ya existe)
└── alquipress-performance/   (NUEVO)
    ├── modules/
    │   ├── wpo/
    │   ├── security/
    │   ├── smart-search/
    │   └── ...
```
**Ventajas**:
- Separación de responsabilidades
- Puede venderse/distribuirse independientemente
- No afecta funcionalidad core

**Desventajas**:
- Otro plugin que mantener
- Posible dependencia compleja

---

**OPCIÓN B**: Extensión del Plugin Existente
```
/wp-content/plugins/alquipress-core/
└── includes/modules/
    ├── performance-wpo/       (NUEVO)
    ├── performance-security/  (NUEVO)
    ├── performance-search/    (NUEVO)
    └── ...
```
**Ventajas**:
- Todo en un solo ecosistema
- Reutiliza Module Manager existente
- Más fácil coordinación entre módulos

**Desventajas**:
- Plugin core más pesado
- Menos modular para distribución

---

**❓ PREGUNTA CLAVE**: ¿Prefieres **OPCIÓN A** (plugin separado) o **OPCIÓN B** (integrar en alquipress-core)?

---

### 🗂️ 2. CONTEXTO DE PRODUCTO (PROPIEDADES)

#### 2.1 Volumen de Datos Esperado
**❓ NECESITO SABER**:
- ¿Cuántas propiedades totales gestionarás inicialmente? (10 / 50 / 100+ )
- ¿Cuántas imágenes por propiedad de media? (5 / 10 / 20+)
- ¿Cuántas reservas simultáneas gestionando en temporada alta? (10 / 50 / 200+)
- ¿Cuántos huéspedes/propietarios únicos en BBDD? (50 / 200 / 1000+)

**POR QUÉ ES IMPORTANTE**:
- Si gestionas <30 propiedades → Optimizaciones simples bastan
- Si gestionas 100+ propiedades con 20 imágenes c/u → Necesitas CDN + cache agresivo

---

#### 2.2 Tipos de Búsqueda Frecuentes
**❓ ESCENARIOS REALES**:
Marca los tipos de búsquedas que tus clientes hacen **MÁS del 50% del tiempo**:

- [ ] "Casas con piscina en [Población]"
- [ ] "Cerca de la playa (<500m)"
- [ ] "Permiten mascotas"
- [ ] "Para 8 personas"
- [ ] "Rango de precio €X - €Y"
- [ ] "Disponibles del [fecha] al [fecha]" (LA MÁS COSTOSA)

**POR QUÉ ES IMPORTANTE**:
Determina qué campos indexar en `wp_alquipress_search_index` y si necesitas cache de disponibilidad.

---

### 🖼️ 3. GESTIÓN DE IMÁGENES

#### 3.1 Hosting de Imágenes Actual
**❓ PREGUNTA**:
- ¿Subes imágenes directamente a WordPress? ✅ Sí / ❌ No
- ¿Usas algún CDN actualmente? (Cloudflare, BunnyCDN, AWS CloudFront...)
  - [ ] No uso ninguno
  - [ ] Cloudflare (gratis con cache automático)
  - [ ] Cloudflare R2 / AWS S3 (pago)
  - [ ] Otro: ___________

**POR QUÉ ES IMPORTANTE**:
- Si NO usas CDN → El módulo puede configurar subdominios locales (assets.alquipress.com)
- Si SÍ usas CDN externo → Implementar push automático a R2/S3

---

#### 3.2 Tamaño Actual de Imágenes
**❓ NECESITO SABER**:
Sube 3-5 imágenes típicas de propiedades a este hilo para analizar:
1. **Peso promedio** (¿Son de 200KB, 1MB, 5MB?)
2. **Dimensiones** (¿4000x3000, 1920x1080?)
3. **Formato** (¿JPG, PNG, HEIC desde móvil?)

**ACCIÓN**: Necesito rutas de ejemplo como:
```
/wp-content/uploads/2024/01/villa-sol-exterior.jpg
/wp-content/uploads/2024/01/villa-sol-piscina.jpg
```

---

### 🔒 4. REQUISITOS DE SEGURIDAD

#### 4.1 Contexto de Usuarios
**❓ PREGUNTA**:
¿Quiénes tienen acceso al backend?
- [ ] Solo TÚ (administrador único)
- [ ] Tú + 1-2 gestores internos
- [ ] Tú + Propietarios (cada propietario ve solo SUS propiedades)
- [ ] Tú + Propietarios + Proveedores externos (limpieza, mantenimiento)

**POR QUÉ ES IMPORTANTE**:
Determina si necesitas:
- 2FA obligatorio (si hay múltiples usuarios externos)
- Audit Log financiero (si los propietarios pueden cambiar IBANs)
- Roles personalizados con permisos limitados

---

#### 4.2 Historial de Incidentes
**❓ SINCERAMENTE**:
- ¿Has tenido alguna vez intentos de login sospechosos? (logs de Query Monitor)
- ¿Has bloqueado alguna IP manualmente?
- ¿Usas Wordfence / Sucuri / iThemes Security actualmente?

**RESPUESTA**:
- Sí, he tenido: _______________
- No, nunca he tenido problemas
- Uso plugin de seguridad: _______________

---

### 📊 5. ANALYTICS Y TRACKING

#### 5.1 ¿Qué Métricas TE Importan?
**❓ PRIORIZA** (1 = Más importante, 5 = Menos importante):

- [ ] Top búsquedas sin resultados (para crear contenido)
- [ ] % de abandono en checkout (usuario añade propiedad pero no paga)
- [ ] Tiempo medio en ficha de producto
- [ ] Filtros más usados (ej: "92% filtran por piscina")
- [ ] Propiedades más vistas pero menos reservadas (precio mal ajustado?)

**SELECCIONA TUS TOP 3**:
1. _______________________
2. _______________________
3. _______________________

---

#### 5.2 GTM/GA4 Existente
**❓ PREGUNTA**:
- ¿Ya tienes Google Tag Manager instalado? ✅ Sí / ❌ No
- ¿GTM4WP (plugin GTM for WordPress)? ✅ Sí / ❌ No
- ¿Google Analytics 4 configurado? ✅ Sí / ❌ No

**SI SÍ**: Dame el ID de contenedor GTM (GTM-XXXXXX) para verificar eventos actuales.

---

### 🗓️ 6. BOOKINGS & CALENDARIO

#### 6.1 Sistema de Sincronización iCal
**❓ PREGUNTA CRUCIAL**:
- ¿Sincronizas calendarios externos? (Airbnb, Booking.com...)
  - [ ] No sincronizo nada
  - [ ] Sí, de 1-5 fuentes externas por propiedad
  - [ ] Sí, de 10+ fuentes (muchas plataformas)

**POR QUÉ ES IMPORTANTE**:
Si sincronizas múltiples calendarios → Necesitas cache de disponibilidad agresivo porque recalcular disponibilidad en tiempo real es **MUY lento**.

---

#### 6.2 Carga del Archivo de Propiedades
**❓ TEST PRÁCTICO**:
1. Abre `http://alquipress.local/tienda/` (o la URL del shop)
2. Con Query Monitor activado, mira el tiempo de carga
3. ¿Cuánto tarda? _______ms

**VALORES DE REFERENCIA**:
- <500ms → Excelente (tal vez no necesites cache)
- 500-2000ms → Mejorable (cache estratégico)
- >2000ms → CRÍTICO (cache obligatorio)

---

### 🎨 7. FRONTEND Y TEMA

#### 7.1 Plantillas Personalizadas
**❓ PREGUNTA**:
¿Usas **templates personalizados** para WooCommerce o todo es por defecto de Astra?

**BUSCA ARCHIVOS COMO**:
```
/wp-content/themes/astra-child/woocommerce/
├── archive-product.php
├── single-product/
└── ...
```

**SI EXISTEN**: Necesito las rutas para analizar qué hooks usar.

---

#### 7.2 Plantilla de Ficha de Producto
**❓ ACCIÓN REQUERIDA**:
1. Abre una ficha de producto (ej: `http://alquipress.local/producto/villa-ejemplo/`)
2. Captura de pantalla o describe los elementos que SE CARGAN AL INICIO:
   - [ ] Slider de galería (¿lightbox?)
   - [ ] Mapa de Google Maps (esto PESA mucho)
   - [ ] Calendario de disponibilidad (WooCommerce Bookings widget)
   - [ ] Formulario de contacto (Contact Form 7, WPForms...)

**POR QUÉ ES IMPORTANTE**:
Determina qué hacer lazy load (mapas/calendarios son los peores para performance).

---

### 💾 8. BASE DE DATOS Y HOSTING

#### 8.1 Entorno de Producción
**❓ PREGUNTA**:
Este proyecto, cuando esté en producción:
- [ ] Estará en shared hosting (Hostinger, SiteGround...)
- [ ] VPS managed (Cloudways, Kinsta, WP Engine...)
- [ ] VPS propio (DigitalOcean, Linode con gestión manual)

**RECURSOS DEL SERVIDOR**:
- RAM disponible: _______ GB
- PHP max_execution_time: _______ segundos

**COMANDO PARA SABERLO** (si ya tienes hosting):
```bash
php -i | grep max_execution_time
```

---

#### 8.2 Tamaño Actual de la Base de Datos
**❓ NECESITO SABER**:
En Local, abre phpMyAdmin y dime:
1. Tamaño total de la base de datos `local`: _______ MB
2. Tabla más grande (probablemente `wp_posts` o `wp_postmeta`): _______ MB

**POR QUÉ ES IMPORTANTE**:
Si la BBDD ya es grande (>500MB), el módulo **Database Optimizer** debe ser conservador para no romper nada.

---

### 🚀 9. PRIORIDADES Y ROADMAP

#### 9.1 ¿Qué Módulo Necesitas YA?
**❓ ORDENA POR URGENCIA** (arrastra y pon número):

1. [ ] **WPO Module** (Lazy Load, WebP) → Tengo PageSpeed Score <60
2. [ ] **Security Module** (Firewall, 2FA) → He tenido incidentes o múltiples usuarios
3. [ ] **Smart Search** (Indexación avanzada) → Nadie encuentra las propiedades que busca
4. [ ] **Image Optimizer** (CDN, WebP) → Pago mucho en ancho de banda
5. [ ] **Bookings Performance** (Cache disponibilidad) → El archivo carga lentísimo
6. [ ] **CRM Dashboard** (Vistas optimizadas) → El pipeline tarda 5+ segundos
7. [ ] **Analytics Module** (DataLayer) → Necesito métricas para tomar decisiones

**SELECCIONA TUS TOP 3 MÁS URGENTES**:
1. ___________________________
2. ___________________________
3. ___________________________

---

#### 9.2 Timeline Realista
**❓ PREGUNTA**:
- ¿Cuándo necesitas esto funcionando? (Fecha límite real)
  - [ ] En 1 semana (MVP básico)
  - [ ] En 1 mes (versión completa)
  - [ ] Sin prisa, vamos paso a paso

**RESPUESTA**: _______________

---

### 🧪 10. TESTING Y DATOS DE PRUEBA

#### 10.1 ¿Tienes Productos de Prueba?
**❓ ACCIÓN**:
- [ ] Sí, tengo 5+ productos ficticios con imágenes
- [ ] No, solo tengo 1-2 productos reales
- [ ] Necesito que generes productos de prueba con el módulo `alquipress-tester`

**SI NECESITAS GENERACIÓN**:
¿Cuántos productos quieres que genere para testear?
- [ ] 10 propiedades básicas
- [ ] 50 propiedades con imágenes (vía Unsplash API)
- [ ] 100+ para test de carga

---

#### 10.2 Acceso a Staging/Local
**❓ PREGUNTA**:
Para validar el plugin, ¿necesito acceso remoto o trabajamos en tu Local?
- [ ] Trabajas tú en Local y me pasas feedback
- [ ] Usamos GitHub/Bitbucket para compartir código
- [ ] Otro: _______________

---

## 📦 11. DEPENDENCIAS EXTERNAS Y LIBRERÍAS

### 11.1 ¿Usas Composer en WordPress?
**❓ PREGUNTA**:
- [ ] Sí, ya tengo `composer.json` en la raíz del proyecto
- [ ] No, pero puedo instalarlo
- [ ] Prefiero evitar Composer (instalar librerías manualmente)

**POR QUÉ ES IMPORTANTE**:
El plugin requiere:
- `guzzlehttp/guzzle` (para CDN push)
- `intervention/image` (para WebP)
- `symfony/cache` (opcional si quieres Redis/Memcached)

**SI NO USAS COMPOSER**: Incluiré las librerías manualmente en `/vendor`.

---

### 11.2 ¿Redis/Memcached Disponible?
**❓ EN PRODUCCIÓN**:
- [ ] Tengo Redis disponible
- [ ] Tengo Memcached
- [ ] Solo puedo usar Object Cache nativo de WordPress
- [ ] No sé qué es esto (usaré Transients API)

---

## 🎯 12. LICENCIA Y DISTRIBUCIÓN

### 12.1 ¿Es para Uso Interno o Comercial?
**❓ PREGUNTA FINAL**:
- [ ] **Uso exclusivo** en mis proyectos de alquiler vacacional
- [ ] **Quiero venderlo** como producto comercial (CodeCanyon, web propia)
- [ ] **White label** para clientes de mi agencia
- [ ] Open Source (GPL) para la comunidad WordPress

**RESPUESTA**: _______________

**IMPLICACIONES**:
- Comercial → Necesitamos sistema de licencias (EDD, Freemius)
- Interno → Código más simple sin validación de licencias

---

## ✅ RESUMEN DE INFORMACIÓN RECOPILADA

Por favor, **rellena este checklist** y me lo devuelves:

### DECISIONES ARQUITECTÓNICAS:
- [ ] Arquitectura: ¿Plugin separado (A) o integrado (B)? → **______**
- [ ] Volumen de propiedades estimado: **______**
- [ ] Top 3 módulos prioritarios: **______ / ______ / ______**

### DATOS TÉCNICOS:
- [ ] CDN actual: **______** (o "ninguno")
- [ ] Peso/formato típico de imágenes: **______**
- [ ] Usuarios con acceso backend: **______**
- [ ] Sincronización iCal: ✅ Sí / ❌ No

### MÉTRICAS Y PERFORMANCE:
- [ ] Tiempo de carga archivo actual: **______ms**
- [ ] PageSpeed Score actual (mobile): **______**
- [ ] GTM ID (si existe): **GTM-______**

### HOSTING Y BBDD:
- [ ] Tipo de hosting producción: **______**
- [ ] Tamaño BBDD actual: **______MB**
- [ ] ¿Composer disponible? ✅ Sí / ❌ No
- [ ] ¿Redis/Memcached? **______**

### TIMELINE:
- [ ] Fecha límite: **______**
- [ ] Enfoque: ✅ MVP rápido / ⏰ Completo sin prisa

---

## 🚀 PRÓXIMOS PASOS

Una vez tengas esta info:

### PASO 1: Análisis (30 min)
- Revisas este documento
- Rellenas las respuestas críticas
- Me lo devuelves en el chat

### PASO 2: Especificaciones Refinadas (1h)
- Creo un documento técnico definitivo
- Con arquitectura exacta según tus respuestas
- Diseño de BBDD específico

### PASO 3: Desarrollo MVP (Semana 1)
- Implemento los 3 módulos top que elijas
- Con tests básicos en tu Local
- Feedback y ajustes

### PASO 4: Iteración (Semanas 2-3)
- Módulos restantes
- Optimización avanzada
- Documentación

---

## 📝 NOTAS ADICIONALES

Si hay algo más que creas relevante que no esté en este documento, añádelo aquí:

```
[Tu input adicional]

```

---

**Creado por**: Antigravity AI  
**Fecha**: 2026-01-23  
**Versión**: 1.0
