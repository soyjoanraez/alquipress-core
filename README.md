# ALQUIPRESS Core Plugin

## Descripción
El núcleo funcional de la plataforma Alquipress. Gestiona toda la lógica de negocio, tipos de datos, CRMs y optimizaciones.

## Módulos Activos

### 1. 🏨 Taxonomías Personalizadas
- **Ubicación:** `includes/modules/taxonomies/`
- **Función:** Gestiona Poblaciones, Zonas y Características.
- **Datos:** Incluye carga automática de datos de la Marina Alta (Dénia, Jávea, etc.).

### 2. 👥 CRM de Huéspedes
- **Ubicación:** `includes/modules/crm-guests/`
- **Función:** Extiende los usuarios de WordPress con preferencias, historial y valoraciones.

### 3. 🔑 CRM de Propietarios
- **Ubicación:** `includes/modules/crm-owners/`
- **Función:** CPT 'Propietario' con gestión financiera.
- **Reporting:** Cálculo automático de ingresos y comisiones (`owner-revenue.php`).
- **Seguridad:** Auditoría de cambios en datos bancarios (vía Suite).

### 4. 📅 Pipeline de Reservas
- **Ubicación:** `includes/modules/booking-pipeline/`
- **Función:** Estados de pedido personalizados (Pendiente Check-in, En curso, Revisión, Fianza devuelta).

### 5. 📧 Automatización Email
- **Ubicación:** `includes/modules/email-automation/`
- **Función:** Integración con MailPoet para flujos automáticos.

### 6. 🚀 SEO Master
- **Ubicación:** `includes/modules/seo-optimization/`
- **Función:** 
    - Arquitectura de URLs (`/alquiler-vacacional/`).
    - Schema `VacationRental`.
    - WPO (Lazy Load, WebP).
    - Renombrado global "Productos" -> "Inmuebles".

### 7. 🔒 Booking Enforcer
- **Ubicación:** `includes/modules/booking-enforcer/`
- **Función:** Fuerza que todos los inmuebles sean:
    - Tipo: `booking` (Reservable).
    - Virtuales: `yes`.
    - No descargables.

## Instalación y Configuración
El plugin verifica sus dependencias y estructura de datos automáticamente al activarse (`alquipress_activate`).

## Mantenimiento
- Para regenerar taxonomías: `Alquipress_Taxonomies::populate_marina_alta()`
- Para limpiar campos ACF duplicados: Usar `acf_add_local_field_group` (ya implementado).

---
*Desarrollado por Antigravity AI para Alquipress.*
