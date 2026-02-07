# 🎨 ALQUIPRESS Admin Design System

**Versión**: 1.0.0
**Última actualización**: 2026-02-07

Sistema de diseño unificado para todas las páginas de administración de ALQUIPRESS Core.

---

## 📋 Índice

1. [Introducción](#introducción)
2. [Instalación](#instalación)
3. [Variables CSS](#variables-css)
4. [Componentes](#componentes)
5. [Utilidades](#utilidades)
6. [Ejemplos Completos](#ejemplos-completos)
7. [Buenas Prácticas](#buenas-prácticas)
8. [Migración](#migración)

---

## Introducción

El ALQUIPRESS Design System es un conjunto de estilos, componentes y patrones reutilizables diseñados para:

✅ **Consistencia visual** - Mismos colores, espaciado y tipografía en todo el plugin
✅ **Desarrollo rápido** - Componentes listos para usar
✅ **Mantenibilidad** - Cambios centralizados, sin estilos inline
✅ **Responsive** - Diseñado mobile-first
✅ **Accesibilidad** - Cumple estándares WCAG 2.1

---

## Instalación

### Carga Automática

El Design System se carga automáticamente en todas las páginas de ALQUIPRESS gracias al `Module Manager`:

```php
// includes/class-module-manager.php
wp_enqueue_style(
    'alquipress-design-system',
    ALQUIPRESS_URL . 'includes/assets/css/admin-design-system.css',
    [],
    ALQUIPRESS_VERSION
);
```

### Carga Manual (Opcional)

Si necesitas cargar el Design System en una página custom:

```php
function my_custom_admin_page_styles() {
    wp_enqueue_style(
        'alquipress-design-system',
        ALQUIPRESS_URL . 'includes/assets/css/admin-design-system.css',
        [],
        ALQUIPRESS_VERSION
    );
}
add_action('admin_enqueue_scripts', 'my_custom_admin_page_styles');
```

---

## Variables CSS

### Colores

```css
/* Primarios */
--ap-primary: #2271b1;
--ap-primary-hover: #135e96;
--ap-primary-light: #f0f6fb;

/* Estados */
--ap-success: #00a32a;
--ap-success-light: #e7f6ed;

--ap-warning: #f0b849;
--ap-warning-light: #fffbeb;

--ap-error: #dc3232;
--ap-error-light: #fcf0f1;

--ap-info: #0ea5e9;
--ap-info-light: #f0f9ff;

/* Grises */
--ap-gray-900: #2c3338;  /* Títulos */
--ap-gray-700: #50575e;  /* Texto */
--ap-gray-600: #646970;  /* Texto secundario */
--ap-gray-300: #c3c4c7;  /* Borders */
--ap-gray-100: #f0f0f1;  /* Backgrounds */
```

### Espaciado

Sistema basado en 4px:

```css
--ap-space-1: 4px;
--ap-space-2: 8px;
--ap-space-3: 12px;
--ap-space-4: 16px;
--ap-space-5: 20px;
--ap-space-6: 24px;
--ap-space-8: 30px;
--ap-space-10: 40px;
```

### Otros

```css
/* Border Radius */
--ap-radius-sm: 4px;
--ap-radius-md: 8px;
--ap-radius-lg: 12px;
--ap-radius-full: 9999px;

/* Sombras */
--ap-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
--ap-shadow-md: 0 2px 8px rgba(0, 0, 0, 0.05);
--ap-shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.08);

/* Tipografía */
--ap-font-size-sm: 13px;
--ap-font-size-md: 14px;
--ap-font-size-lg: 16px;
--ap-font-size-xl: 20px;
--ap-font-size-2xl: 24px;
--ap-font-size-3xl: 32px;
```

---

## Componentes

### 1. Layout

#### Contenedor Principal

```html
<div class="ap-wrap">
    <!-- Tu contenido aquí -->
</div>
```

**Variantes:**
- `.ap-wrap--narrow` - Máx. 900px
- `.ap-wrap--wide` - Máx. 1600px

#### Page Header

```html
<div class="ap-page-header">
    <h1>
        <span class="dashicons dashicons-admin-multisite"></span>
        Título de la Página
    </h1>
    <p>Descripción breve de la funcionalidad.</p>
</div>
```

#### Divider

```html
<hr class="ap-divider">
```

---

### 2. Cards

#### Card Básica

```html
<div class="ap-card">
    <h2>Título de la Card</h2>
    <p>Contenido de la card.</p>
</div>
```

#### Card con Variantes

```html
<!-- Card con borde azul -->
<div class="ap-card ap-card--primary">
    <h2>Card Primary</h2>
</div>

<!-- Card con borde verde -->
<div class="ap-card ap-card--success">
    <h2>Card Success</h2>
</div>

<!-- Card con borde amarillo -->
<div class="ap-card ap-card--warning">
    <h2>Card Warning</h2>
</div>

<!-- Card con borde rojo -->
<div class="ap-card ap-card--error">
    <h2>Card Error</h2>
</div>

<!-- Card con borde celeste -->
<div class="ap-card ap-card--info">
    <h2>Card Info</h2>
</div>
```

#### Stat Cards Grid

```html
<div class="ap-stats-grid">
    <div class="ap-stat-card">
        <span class="ap-stat-card__label">WordPress</span>
        <strong class="ap-stat-card__value">6.4.2</strong>
    </div>

    <div class="ap-stat-card ap-stat-card--success">
        <span class="ap-stat-card__label">WooCommerce</span>
        <strong class="ap-stat-card__value">8.5.1</strong>
    </div>

    <div class="ap-stat-card ap-stat-card--warning">
        <span class="ap-stat-card__label">ACF PRO</span>
        <strong class="ap-stat-card__value">✗ Requerido</strong>
    </div>
</div>
```

---

### 3. Buttons

#### Botones Básicos

```html
<!-- Primary -->
<button class="ap-button ap-button--primary">
    Guardar Cambios
</button>

<!-- Success -->
<button class="ap-button ap-button--success">
    <span class="dashicons dashicons-yes-alt"></span> Confirmar
</button>

<!-- Secondary -->
<button class="ap-button ap-button--secondary">
    Cancelar
</button>

<!-- Large -->
<button class="ap-button ap-button--primary ap-button--large">
    Botón Grande
</button>

<!-- Small -->
<button class="ap-button ap-button--secondary ap-button--small">
    Botón Pequeño
</button>
```

---

### 4. Badges

```html
<span class="ap-badge ap-badge--success">
    <span class="dashicons dashicons-yes-alt"></span> Activo
</span>

<span class="ap-badge ap-badge--warning">
    <span class="dashicons dashicons-warning"></span> Pendiente
</span>

<span class="ap-badge ap-badge--error">
    <span class="dashicons dashicons-dismiss"></span> Error
</span>

<span class="ap-badge ap-badge--info">
    <span class="dashicons dashicons-info"></span> Info
</span>

<span class="ap-badge ap-badge--inactive">
    <span class="dashicons dashicons-dismiss"></span> Inactivo
</span>
```

---

### 5. Forms

#### Tabla de Formulario

```html
<table class="ap-form-table">
    <tr>
        <th scope="row">Nombre del Campo</th>
        <td>
            <input type="text" name="field_name" class="regular-text">
            <p class="description">Texto de ayuda para el campo.</p>
        </td>
    </tr>
    <tr>
        <th scope="row">Opción Checkbox</th>
        <td>
            <label>
                <input type="checkbox" name="option" value="1">
                Habilitar esta opción
            </label>
        </td>
    </tr>
</table>
```

#### Submit Area

```html
<div class="ap-submit-area">
    <button type="submit" class="ap-button ap-button--primary ap-button--large">
        Guardar Configuración
    </button>
</div>
```

#### Toggle Switch

```html
<label class="ap-switch">
    <input type="checkbox" name="enabled" value="1">
    <span class="ap-switch__slider"></span>
</label>
```

---

### 6. Tables

```html
<table class="ap-table">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Juan Pérez</td>
            <td>juan@example.com</td>
            <td>
                <span class="ap-badge ap-badge--success">Activo</span>
            </td>
        </tr>
        <tr>
            <td>María García</td>
            <td>maria@example.com</td>
            <td>
                <span class="ap-badge ap-badge--inactive">Inactivo</span>
            </td>
        </tr>
    </tbody>
</table>
```

---

### 7. Notices

```html
<div class="ap-notice ap-notice--success">
    <span class="dashicons dashicons-yes-alt"></span>
    <p>Operación completada correctamente.</p>
</div>

<div class="ap-notice ap-notice--warning">
    <span class="dashicons dashicons-warning"></span>
    <p>Advertencia: Revisa los datos antes de continuar.</p>
</div>

<div class="ap-notice ap-notice--error">
    <span class="dashicons dashicons-dismiss"></span>
    <p>Error: No se pudo completar la operación.</p>
</div>

<div class="ap-notice ap-notice--info">
    <span class="dashicons dashicons-info"></span>
    <p>Información: Recuerda guardar los cambios.</p>
</div>
```

---

### 8. Code Blocks

```html
<div class="ap-code-block">
    <div class="ap-code-block__line">
        [2026-02-07 10:15:23] Usuario: admin (ID: 1) | Acción: view
    </div>
    <div class="ap-code-block__line">
        [2026-02-07 10:16:45] Usuario: editor (ID: 2) | Acción: edit
    </div>
</div>

<!-- Inline code -->
<p>URL del feed: <code>https://ejemplo.com/kyero-feed.xml</code></p>
```

---

## Utilidades

### Spacing

```html
<!-- Margin Top -->
<div class="ap-mt-0">Sin margin-top</div>
<div class="ap-mt-3">Margin-top: 12px</div>
<div class="ap-mt-5">Margin-top: 20px</div>
<div class="ap-mt-10">Margin-top: 40px</div>

<!-- Margin Bottom -->
<div class="ap-mb-0">Sin margin-bottom</div>
<div class="ap-mb-3">Margin-bottom: 12px</div>
<div class="ap-mb-5">Margin-bottom: 20px</div>
<div class="ap-mb-10">Margin-bottom: 40px</div>
```

### Text

```html
<!-- Alineación -->
<p class="ap-text-center">Texto centrado</p>
<p class="ap-text-right">Texto a la derecha</p>

<!-- Tamaño -->
<span class="ap-text-sm">Texto pequeño</span>
<span class="ap-text-md">Texto mediano</span>
<span class="ap-text-lg">Texto grande</span>

<!-- Peso -->
<strong class="ap-text-bold">Texto en negrita</strong>
<span class="ap-text-semibold">Texto semi-negrita</span>

<!-- Color -->
<span class="ap-text-primary">Texto azul</span>
<span class="ap-text-success">Texto verde</span>
<span class="ap-text-warning">Texto amarillo</span>
<span class="ap-text-error">Texto rojo</span>
<span class="ap-text-muted">Texto gris</span>
```

### Flex

```html
<div class="ap-flex ap-items-center ap-gap-3">
    <span class="dashicons dashicons-admin-home"></span>
    <span>Icono con texto</span>
</div>

<div class="ap-flex ap-justify-between">
    <div>Izquierda</div>
    <div>Derecha</div>
</div>
```

### Grid

```html
<div class="ap-grid ap-grid-cols-2 ap-gap-4">
    <div>Columna 1</div>
    <div>Columna 2</div>
</div>

<div class="ap-grid ap-grid-cols-3 ap-gap-5">
    <div>Col 1</div>
    <div>Col 2</div>
    <div>Col 3</div>
</div>
```

---

## Ejemplos Completos

### Página de Configuración

```html
<div class="ap-wrap ap-wrap--narrow">
    <div class="ap-page-header">
        <h1>
            <span class="dashicons dashicons-admin-settings"></span>
            Configuración del Módulo
        </h1>
        <p>Configura las opciones de tu módulo.</p>
    </div>

    <div class="ap-card">
        <h2>Opciones Generales</h2>

        <form method="post">
            <?php wp_nonce_field('mi_modulo_settings'); ?>

            <table class="ap-form-table">
                <tr>
                    <th scope="row">Habilitar Módulo</th>
                    <td>
                        <label class="ap-switch">
                            <input type="checkbox" name="enabled" value="1">
                            <span class="ap-switch__slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">API Key</th>
                    <td>
                        <input type="text" name="api_key" class="regular-text">
                        <p class="description">Introduce tu clave API.</p>
                    </td>
                </tr>
            </table>

            <div class="ap-submit-area">
                <button type="submit" class="ap-button ap-button--primary ap-button--large">
                    <span class="dashicons dashicons-saved"></span> Guardar Configuración
                </button>
            </div>
        </form>
    </div>

    <div class="ap-card ap-card--info">
        <h2>
            <span class="dashicons dashicons-info"></span>
            Información
        </h2>
        <p>Esta es una card informativa con borde celeste.</p>
    </div>
</div>
```

### Dashboard con Stats

```html
<div class="ap-wrap">
    <div class="ap-page-header">
        <h1>
            <span class="dashicons dashicons-dashboard"></span>
            Dashboard
        </h1>
        <p>Vista general del sistema.</p>
    </div>

    <div class="ap-stats-grid">
        <div class="ap-stat-card">
            <span class="ap-stat-card__label">Total Usuarios</span>
            <strong class="ap-stat-card__value">1,250</strong>
        </div>

        <div class="ap-stat-card ap-stat-card--success">
            <span class="ap-stat-card__label">Ventas del Mes</span>
            <strong class="ap-stat-card__value">€45,320</strong>
        </div>

        <div class="ap-stat-card ap-stat-card--warning">
            <span class="ap-stat-card__label">Pendientes</span>
            <strong class="ap-stat-card__value">23</strong>
        </div>

        <div class="ap-stat-card ap-stat-card--error">
            <span class="ap-stat-card__label">Errores</span>
            <strong class="ap-stat-card__value">3</strong>
        </div>
    </div>

    <div class="ap-card">
        <h2>Últimas Transacciones</h2>

        <table class="ap-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Monto</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>#1234</td>
                    <td>Juan Pérez</td>
                    <td>€150.00</td>
                    <td>
                        <span class="ap-badge ap-badge--success">Completado</span>
                    </td>
                </tr>
                <tr>
                    <td>#1235</td>
                    <td>María García</td>
                    <td>€320.50</td>
                    <td>
                        <span class="ap-badge ap-badge--warning">Pendiente</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

---

## Buenas Prácticas

### ✅ Hacer

```html
<!-- Usar clases del design system -->
<div class="ap-card">
    <h2>Título</h2>
    <p class="ap-text-muted">Descripción</p>
</div>

<!-- Usar variables CSS para valores personalizados -->
<style>
.mi-componente {
    color: var(--ap-primary);
    padding: var(--ap-space-4);
    border-radius: var(--ap-radius-md);
}
</style>

<!-- Usar dashicons con clases del design system -->
<button class="ap-button ap-button--primary">
    <span class="dashicons dashicons-saved"></span> Guardar
</button>
```

### ❌ Evitar

```html
<!-- NO usar estilos inline -->
<div style="background: #fff; padding: 20px;">
    Contenido
</div>

<!-- NO reinventar componentes existentes -->
<div style="background: #f0f6fb; border-left: 4px solid #2271b1;">
    <!-- Usa .ap-card .ap-card--primary en su lugar -->
</div>

<!-- NO usar colores hardcodeados -->
<style>
.mi-elemento {
    color: #2271b1; /* ❌ Usa var(--ap-primary) */
}
</style>

<!-- NO mezclar emojis con dashicons -->
<button>
    🚀 Generar <!-- ❌ Usa dashicons -->
</button>
```

---

## Migración

### Migrar Código Existente

**Antes:**
```html
<div class="wrap" style="background: #fff; padding: 20px; border-radius: 8px;">
    <h1 style="color: #2271b1;">Título</h1>
    <div style="background: #f0f6fb; padding: 15px;">
        <p style="color: #666;">Contenido</p>
    </div>
</div>
```

**Después:**
```html
<div class="ap-wrap">
    <div class="ap-page-header">
        <h1>
            <span class="dashicons dashicons-admin-generic"></span>
            Título
        </h1>
    </div>
    <div class="ap-card ap-card--primary">
        <p class="ap-text-muted">Contenido</p>
    </div>
</div>
```

### Checklist de Migración

- [ ] Eliminar TODOS los estilos inline (`style="..."`)
- [ ] Eliminar bloques `<style>` embebidos
- [ ] Reemplazar con clases `.ap-*`
- [ ] Usar dashicons en lugar de emojis (donde aplique)
- [ ] Verificar responsive en móvil
- [ ] Testing visual

---

## Soporte

**Archivo CSS**: `includes/assets/css/admin-design-system.css`
**Versión**: 1.0.0
**Última actualización**: 2026-02-07

Para reportar bugs o solicitar nuevos componentes, contacta al equipo de desarrollo.

---

## Changelog

### v1.0.0 (2026-02-07)
- ✨ Release inicial del Design System
- 📦 12 componentes base
- 🎨 40+ utility classes
- 📱 Responsive design
- ♿ Accesibilidad mejorada
