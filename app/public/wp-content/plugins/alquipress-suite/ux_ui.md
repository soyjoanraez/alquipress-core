# 📋 Plan de Tareas UX/UI - CRM ALQUIPRESS

Basándome en la documentación del proyecto y las referencias visuales, aquí está el roadmap completo para construir el CRM.

---

## 🎯 **FASE 1: FUNDAMENTOS TÉCNICOS**
*Duración estimada: 3-4 días*

### **Tarea 1.1: Preparación del Entorno**
**Objetivo:** Dejar WordPress listo para customización avanzada del backend

- [ ] Instalar **Admin Columns** (Free) o **Admin Columns Pro**
- [ ] Instalar **Code Snippets** para gestionar código sin tocar functions.php
- [ ] Instalar **Advanced Custom Fields PRO** (ya deberías tenerlo)
- [ ] Crear Child Theme de Astra (si no existe)
  ```bash
  /wp-content/themes/astra-child/
  ├── style.css
  └── functions.php
  ```

**Entregable:** Screenshot del dashboard con plugins activados

---

### **Tarea 1.2: Importar Estructura de Datos ACF**
**Objetivo:** Cargar todos los campos personalizados del CRM

**Archivos a importar (en este orden):**

1. **Propiedades** → `Propiedades_json` (document #5)
2. **Clientes/Huéspedes** → `Clientes_json` (document #6)
3. **Propietarios** → `Propietarios_json` (document #7)
4. **Iconos de Taxonomías** → `acf-iconos-taxonomia.json` (document #10)

**Proceso:**
```
ACF > Tools > Import Field Groups > 
  [Seleccionar JSON] > Import
```

**Validación:**
- [ ] En "Productos" aparecen nuevos campos (Licencia, Superficie, etc.)
- [ ] En "Usuarios" aparecen campos CRM (Estado, Rating, Notas)
- [ ] En CPT "Propietarios" aparecen 3 pestañas (Contacto, Financiero, Docs)

**Entregable:** Captura de pantalla de cada grupo de campos

---

### **Tarea 1.3: Registrar CPT y Taxonomías**
**Objetivo:** Crear los Custom Post Types necesarios

**Archivo:** `PHP_CPT` (document #4)

```php
// Code Snippets > Add New
// Title: "ALQUIPRESS - CPT y Taxonomías"
// Code: [Pegar el contenido del document #4]
// Run: Everywhere
```

**Validación:**
- [ ] Aparece "Propietarios" en el menú lateral del admin
- [ ] En Productos > Taxonomías hay: "Población", "Zona", "Características"

**Entregable:** Screenshot del menú admin con "Propietarios" visible

---

### **Tarea 1.4: Importar Características con Iconos**
**Objetivo:** Popular la taxonomía "Características" con datos + iconos

**Archivo:** Script del document #10 (sección "PASO 2")

```php
// Code Snippets > Add New
// Title: "Importar Características + Iconos"
// Code: [Pegar script de "importar_caracteristicas_con_iconos"]
// Run: Only run once
// Activar > Recargar web > Desactivar
```

**Validación:**
- [ ] Ir a `Productos > Características`
- [ ] Debe haber ~30 términos (WiFi, Piscina Privada, etc.)
- [ ] Al editar cada término, en el campo ACF "Clase del Icono" debe aparecer código FontAwesome

**Entregable:** Screenshot del listado de características

---

## 🎨 **FASE 2: PERSONALIZACIÓN VISUAL DEL BACKEND**
*Duración estimada: 5-6 días*

### **Tarea 2.1: Customizar Listado de Pedidos (Orders)**
**Objetivo:** Convertir el listado de WooCommerce en una vista tipo CRM

**Referencia visual:** Imagen #1 (Pipeline Kanban)

#### **Sub-tarea A: Añadir Columnas Personalizadas**

**Plugin necesario:** Admin Columns Pro (o código custom)

**Columnas a añadir:**

| Columna Original | Mantener | Nueva Columna | Datos a Mostrar |
|-----------------|----------|---------------|-----------------|
| Orden # | ✅ | - | - |
| Estado | ❌ | **Semáforo Visual** | 🟢 Pagado / 🟡 Pendiente / 🔴 Atrasado |
| Cliente | ✅ | - | - |
| - | ➕ | **Check-in / Check-out** | Fechas del Booking |
| - | ➕ | **Propiedad** | Nombre de la casa |
| Total | ✅ | - | - |

**Código base (Code Snippets):**
```php
// Añadir columna "Propiedad" en pedidos
add_filter('manage_shop_order_posts_columns', 'agregar_columna_propiedad');
function agregar_columna_propiedad($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'order_status') {
            $new_columns['propiedad'] = __('Propiedad', 'alquipress');
            $new_columns['fechas_estancia'] = __('Estancia', 'alquipress');
        }
    }
    return $new_columns;
}

add_action('manage_shop_order_posts_custom_column', 'mostrar_columna_propiedad', 10, 2);
function mostrar_columna_propiedad($column, $post_id) {
    if ($column === 'propiedad') {
        $order = wc_get_order($post_id);
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            echo '<strong>' . $product->get_name() . '</strong>';
            break; // Solo mostrar el primero
        }
    }
    
    if ($column === 'fechas_estancia') {
        $order = wc_get_order($post_id);
        // Extraer fechas de WooCommerce Bookings
        // (requiere lógica adicional según cómo Bookings guarde los datos)
        echo '<span style="color: #666;">Oct 12 - Oct 18</span>';
    }
}
```

**Entregable:** Screenshot del listado con las nuevas columnas

---

#### **Sub-tarea B: Crear Estados Personalizados de Pedido**

**Referencia:** Document #1 (Estados: Pago Recibido, Pendiente Check-in, etc.)

**Código (Code Snippets):**
```php
// Registrar nuevos estados de pedido
add_action('init', 'registrar_estados_crm');
function registrar_estados_crm() {
    register_post_status('wc-pago-recibido', array(
        'label'                     => 'Pago Depósito Recibido',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Pago Recibido <span class="count">(%s)</span>',
            'Pago Recibido <span class="count">(%s)</span>'
        ),
    ));
    
    register_post_status('wc-pendiente-checkin', array(
        'label'                     => 'Pendiente Check-in',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Pendiente Check-in <span class="count">(%s)</span>',
            'Pendiente Check-in <span class="count">(%s)</span>'
        ),
    ));
    
    // Repetir para: wc-estancia-curso, wc-revision-salida, wc-fianza-devuelta
}

// Añadir estados al dropdown de WooCommerce
add_filter('wc_order_statuses', 'agregar_estados_al_dropdown');
function agregar_estados_al_dropdown($order_statuses) {
    $order_statuses['wc-pago-recibido'] = 'Pago Recibido';
    $order_statuses['wc-pendiente-checkin'] = 'Pendiente Check-in';
    $order_statuses['wc-estancia-curso'] = 'Estancia en Curso';
    $order_statuses['wc-revision-salida'] = 'Revisión Salida';
    $order_statuses['wc-fianza-devuelta'] = 'Fianza Devuelta';
    return $order_statuses;
}
```

**Entregable:** Screenshot del dropdown de estados con los nuevos valores

---

### **Tarea 2.2: Diseñar Vista "Pipeline" (Kanban)**
**Objetivo:** Crear la vista visual estilo Trello/Monday

**Referencia:** Imagen #1 completa

**⚠️ Decisión Técnica:**

Tienes 2 caminos:

**Opción A: Plugin (Recomendada para MVP rápido)**
- Plugin: **Kanban for WordPress** (WooCommerce Orders Add-on)
- Coste: ~$50
- Tiempo: 1 día de configuración
- Pro: Drag & drop nativo, responsive
- Contra: Dependencia externa

**Opción B: Código Custom**
- Crear página admin custom usando REST API de WooCommerce
- Librería JS: SortableJS + TailwindCSS
- Tiempo: 4-5 días de desarrollo
- Pro: Control total, sin licencias
- Contra: Mantenimiento a largo plazo

**Mi recomendación:** Opción A para Phase 1, migrar a B si el negocio escala

**Entregable (Opción A):** 
- Screenshot del Kanban configurado con las 5 columnas
- Video de 30seg moviendo una tarjeta entre estados

**Entregable (Opción B):**
- Archivo `crm-kanban.php` funcional
- Screenshot del resultado

---

### **Tarea 2.3: Perfil de Huésped (Vista Detallada)**
**Objetivo:** Crear la página de edición/visualización de cliente

**Referencia:** Imagen #2 (Juan Pérez Martínez)

#### **Sub-tarea A: Customizar Página de Edición de Usuario**

**Ubicación:** `Usuarios > Editar > [Nombre del usuario]`

**Elementos a añadir (usando ACF):**

Ya tienes los campos del `Clientes_json`, ahora hay que **organizarlos visualmente**.

**CSS Custom (Code Snippets):**
```php
add_action('admin_head', 'estilos_crm_usuario');
function estilos_crm_usuario() {
    ?>
    <style>
        /* Contenedor principal del perfil */
        .acf-field-group-crm-cliente {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        /* Badge VIP */
        .acf-field[data-name="guest_status"] select option[value="vip"] {
            background: #fbbf24;
            color: #fff;
            font-weight: bold;
        }
        
        /* Estrellas de rating */
        .acf-field[data-name="guest_rating"] .acf-button-group label {
            font-size: 20px;
        }
        
        /* Sección de preferencias */
        .acf-field[data-name="guest_preferences"] .acf-checkbox-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
    </style>
    <?php
}
```

**Maquetación con ACF Tabs:**
```json
// Editar el grupo "CRM - Ficha del Huésped"
// Añadir campos tipo "Tab" para organizar:

[
  {
    "type": "tab",
    "label": "📊 Estado y Valoración"
  },
  { "field": "guest_status" },
  { "field": "guest_rating" },
  
  {
    "type": "tab",
    "label": "🎯 Preferencias"
  },
  { "field": "guest_preferences" },
  
  {
    "type": "tab",
    "label": "📝 Notas Privadas"
  },
  { "field": "guest_internal_notes" },
  
  {
    "type": "tab",
    "label": "📂 Documentación"
  },
  { "field": "guest_documents" }
]
```

**Entregable:**
- Screenshot de la página de edición con tabs
- Video de 20seg navegando entre pestañas

---

#### **Sub-tarea B: Crear Vista "Read-Only" para Staff**

**Objetivo:** Página informativa (no editable) para ver datos del cliente rápidamente

**Opción 1:** Usar plugin **Frontend Admin by DynamiApps** (permite crear frontend para ACF)

**Opción 2:** Crear página PHP custom en el admin

**Código base (Opción 2):**
```php
// Archivo: /wp-content/themes/astra-child/admin/guest-profile.php

add_action('admin_menu', 'registrar_pagina_perfil_huesped');
function registrar_pagina_perfil_huesped() {
    add_users_page(
        'Perfil del Huésped',
        'Ver Perfil',
        'read',
        'guest-profile',
        'render_guest_profile_page'
    );
}

function render_guest_profile_page() {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if (!$user_id) {
        echo '<p>Usuario no encontrado</p>';
        return;
    }
    
    $user = get_userdata($user_id);
    $status = get_field('guest_status', 'user_' . $user_id);
    $rating = get_field('guest_rating', 'user_' . $user_id);
    $prefs = get_field('guest_preferences', 'user_' . $user_id);
    
    ?>
    <div class="wrap" style="max-width: 1200px;">
        <h1><?php echo esc_html($user->display_name); ?></h1>
        
        <!-- Header Card -->
        <div style="background: white; padding: 30px; border-radius: 8px; margin: 20px 0; display: flex; align-items: center; gap: 30px;">
            <?php echo get_avatar($user_id, 80, '', '', array('class' => 'rounded-full')); ?>
            
            <div>
                <h2 style="margin: 0;"><?php echo $user->display_name; ?></h2>
                <p style="color: #666; margin: 5px 0;">
                    <span class="dashicons dashicons-location"></span> Madrid, Spain
                </p>
                <div style="margin-top: 10px;">
                    <?php 
                    // Mostrar estrellas según rating
                    for ($i = 1; $i <= 5; $i++) {
                        $fill = $i <= intval($rating) ? 'gold' : '#ddd';
                        echo '<span class="dashicons dashicons-star-filled" style="color: ' . $fill . ';"></span>';
                    }
                    ?>
                </div>
            </div>
            
            <?php if ($status === 'vip'): ?>
                <span style="background: #fbbf24; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin-left: auto;">VIP</span>
            <?php endif; ?>
        </div>
        
        <!-- Contact Grid -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
            <div style="background: #eff6ff; padding: 20px; border-radius: 8px;">
                <span class="dashicons dashicons-email-alt" style="color: #3b82f6; font-size: 24px;"></span>
                <p style="margin: 10px 0 0; color: #666; font-size: 12px;">EMAIL</p>
                <p style="margin: 5px 0; font-weight: 600;"><?php echo $user->user_email; ?></p>
            </div>
            
            <!-- Repetir para Phone, Nationality, Total Spend -->
        </div>
        
        <!-- Preferences -->
        <div style="background: white; padding: 30px; border-radius: 8px; margin: 20px 0;">
            <h3><span class="dashicons dashicons-admin-generic"></span> Preferencias</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 20px;">
                <?php foreach ($prefs as $pref): ?>
                    <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f9fafb; border-radius: 6px;">
                        <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                        <span><?php echo ucfirst($pref); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Booking History Table -->
        <div style="background: white; padding: 30px; border-radius: 8px;">
            <h3>Historial de Reservas</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Propiedad</th>
                        <th>Fechas</th>
                        <th>Noches</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query de pedidos del usuario
                    $orders = wc_get_orders(array(
                        'customer_id' => $user_id,
                        'limit' => 10,
                    ));
                    
                    foreach ($orders as $order) {
                        // Renderizar fila
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
```

**Entregable:**
- Screenshot de la página completa
- Comparativa lado a lado con la Imagen #2

---

### **Tarea 2.4: Perfil de Propietario (Financiero)**
**Objetivo:** Crear la interfaz de gestión de propietarios

**Referencia:** Imagen #3 (Maria Gonzalez - Financial)

#### **Sub-tarea A: Diseño de Pestañas en CPT Propietarios**

Ya tienes los campos del `Propietarios_json`. Ahora toca mejorar la UX.

**Mejoras visuales con CSS:**
```php
add_action('admin_head', 'estilos_crm_propietario');
function estilos_crm_propietario() {
    global $post_type;
    if ($post_type !== 'propietario') return;
    ?>
    <style>
        /* Warning Banner para sección financiera */
        .acf-tab-wrap .acf-tab-group li[data-key="field_owner_tab_finance"] {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        
        /* IBAN Field - Estilo de seguridad */
        .acf-field[data-name="owner_iban"] {
            background: #fffbeb;
            border: 2px solid #fbbf24;
            padding: 20px;
            border-radius: 8px;
            position: relative;
        }
        
        .acf-field[data-name="owner_iban"]::before {
            content: "🔒 Dato Sensible - Acceso Registrado";
            display: block;
            background: #fbbf24;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 12px;
        }
        
        /* Botón de "View" para IBAN */
        .acf-field[data-name="owner_iban"] input[type="text"] {
            -webkit-text-security: disc;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
    </style>
    <?php
}
```

#### **Sub-tarea B: Crear Botón "Ver IBAN Completo"**

**Objetivo:** El IBAN debe estar oculto por defecto y solo mostrarse al hacer clic

**JavaScript (Code Snippets):**
```php
add_action('admin_footer', 'script_toggle_iban');
function script_toggle_iban() {
    global $post_type;
    if ($post_type !== 'propietario') return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Añadir botón "Ver" al campo IBAN
        var ibanField = $('.acf-field[data-name="owner_iban"] input');
        var realValue = ibanField.val();
        var maskedValue = realValue.replace(/(?<=.{4}).(?=.{4})/g, '*');
        
        ibanField.val(maskedValue);
        ibanField.prop('readonly', true);
        
        var viewBtn = $('<button type="button" class="button" style="margin-left: 10px;">👁️ Ver IBAN</button>');
        ibanField.after(viewBtn);
        
        viewBtn.on('click', function() {
            if (ibanField.val() === maskedValue) {
                ibanField.val(realValue);
                $(this).text('🔒 Ocultar');
                
                // Log de auditoría (opcional)
                console.log('[AUDIT] IBAN revelado por:', '<?php echo wp_get_current_user()->user_login; ?>', new Date());
            } else {
                ibanField.val(maskedValue);
                $(this).text('👁️ Ver IBAN');
            }
        });
    });
    </script>
    <?php
}
```

**Entregable:**
- Screenshot del campo IBAN enmascarado
- Video mostrando el toggle "Ver/Ocultar"

---

### **Tarea 2.5: Formulario de Edición de Huésped**
**Objetivo:** Crear la interfaz limpia de edición tipo Imagen #4

**Referencia:** Imagen #4 (Edit Guest: John Doe)

**Estrategia:** Usar **ACF Frontend Form** para crear una página de edición más amigable

**Código:**
```php
// Crear página en el admin: "Editar Huésped"
add_action('admin_menu', 'agregar_pagina_editar_huesped');
function agregar_pagina_editar_huesped() {
    add_submenu_page(
        'users.php',
        'Editar Huésped',
        'Editar Huésped',
        'edit_users',
        'editar-huesped',
        'render_editar_huesped_page'
    );
}

function render_editar_huesped_page() {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if (!$user_id) {
        echo '<p>Selecciona un usuario</p>';
        return;
    }
    
    echo '<div class="wrap" style="max-width: 1000px; margin: 40px auto;">';
    echo '<h1>Edit Guest: ' . get_userdata($user_id)->display_name . '</h1>';
    echo '<p style="color: #666;">Manage profile details, preferences, and internal notes.</p>';
    
    // Renderizar formulario ACF
    acf_form(array(
        'post_id' => 'user_' . $user_id,
        'field_groups' => array('group_crm_cliente'),
        'form' => true,
        'return' => admin_url('users.php'),
        'submit_value' => 'Save Changes',
        'updated_message' => 'Huésped actualizado correctamente',
    ));
    
    echo '</div>';
}
```

**Entregable:**
- Screenshot del formulario renderizado
- Comparativa con Imagen #4

---

## 🔧 **FASE 3: FUNCIONALIDADES AVANZADAS**
*Duración estimada: 4-5 días*

### **Tarea 3.1: Sistema de Preferencias con Iconos**
**Objetivo:** Crear los toggle buttons visuales de preferencias

**Referencia:** Imagen #4 (sección "Guest Preferences")

**Plugin recomendado:** **ACF Button Group** ya incluido en ACF Pro

**Configuración del campo:**
```json
{
  "key": "field_guest_preferences_visual",
  "label": "Preferencias Visuales",
  "name": "guest_preferences_visual",
  "type": "checkbox",
  "choices": {
    "mascotas": "🐾 Pets Allowed",
    "nofumador": "🚭 Non-Smoking",
    "familia": "👨‍👩‍👧 Family Friendly",
    "accesibilidad": "♿ Accessibility",
    "nomada": "📡 Digital Nomad"
  },
  "layout": "horizontal"
}
```

**CSS para estilo de cards:**
```css
.acf-field[data-name="guest_preferences_visual"] .acf-checkbox-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.acf-field[data-name="guest_preferences_visual"] .acf-checkbox-list label {
    background: #f3f4f6;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 600;
}

.acf-field[data-name="guest_preferences_visual"] .acf-checkbox-list input:checked + label {
    background: #dbeafe;
    border-color: #3b82f6;
    color: #1d4ed8;
}
```

**Entregable:** Screenshot del campo con estilo aplicado

---

### **Tarea 3.2: Sistema de Rating con Estrellas**
**Objetivo:** Implementar el sistema visual de valoración

**Referencia:** Imagen #4 (sección "Internal Rating")

**Campo ACF:**
```json
{
  "key": "field_guest_rating_stars",
  "label": "Valoración Interna",
  "name": "guest_rating",
  "type": "range",
  "min": 0,
  "max": 5,
  "step": 0.5
}
```

**JavaScript para convertir a estrellas:**
```javascript
jQuery(document).ready(function($) {
    var ratingField = $('[data-name="guest_rating"] input[type="range"]');
    var ratingValue = ratingField.val();
    
    // Crear contenedor de estrellas
    var starsHTML = '<div class="star-rating" style="margin-top: 10px;">';
    for (var i = 1; i <= 5; i++) {
        var fillClass = i <= Math.floor(ratingValue) ? 'filled' : 'empty';
        starsHTML += '<span class="star ' + fillClass + '" data-value="' + i + '">⭐</span>';
    }
    starsHTML += '<span style="margin-left: 15px; font-weight: bold;">' + ratingValue + ' / 5.0</span>';
    starsHTML += '</div>';
    
    ratingField.after(starsHTML);
    
    // Click en estrella actualiza el campo
    $('.star-rating .star').on('click', function() {
        var value = $(this).data('value');
        ratingField.val(value).trigger('change');
        
        $('.star-rating .star').each(function(index) {
            $(this).toggleClass('filled', index < value);
        });
        
        $('.star-rating span:last').text(value + ' / 5.0');
    });
});
```

**CSS:**
```css
.star-rating .star {
    font-size: 32px;
    cursor: pointer;
    transition: transform 0.1s;
}

.star-rating .star:hover {
    transform: scale(1.2);
}

.star-rating .star.empty {
    filter: grayscale(100%);
    opacity: 0.3;
}
```

**Entregable:** Video de 15seg interactuando con las estrellas

---

### **Tarea 3.3: Notas Privadas con Markdown**
**Objetivo:** Mejorar el campo de notas con formato básico

**Campo ACF:**
```json
{
  "key": "field_guest_notes_private",
  "label": "Notas Privadas",
  "name": "guest_internal_notes",
  "type": "wysiwyg",
  "toolbar": "basic",
  "media_upload": 0
}
```

**Advertencia de privacidad (debajo del campo):**
```php
add_filter('acf/prepare_field/name=guest_internal_notes', 'advertencia_notas_privadas');
function advertencia_notas_privadas($field) {
    $field['instructions'] = '🔒 Estas notas son CONFIDENCIALES y solo visibles para administradores. No se comparten con el huésped.';
    return $field;
}
```

**Entregable:** Screenshot del campo con mensaje de advertencia

---

## 📊 **FASE 4: REPORTES Y DASHBOARDS**
*Duración estimada: 3-4 días*

### **Tarea 4.1: Dashboard Principal del Admin**
**Objetivo:** Crear widgets informativos en `wp-admin`

**Widgets a crear:**
1. **Reservas Hoy** (Check-ins y Check-outs)
2. **Ingresos del Mes**
3. **Ocupación Global** (% de casas ocupadas)
4. **Alertas** (Pagos pendientes, check-ins mañana)

**Código base:**
```php
add_action('wp_dashboard_setup', 'agregar_widgets_crm');
function agregar_widgets_crm() {
    wp_add_dashboard_widget(
        'crm_reservas_hoy',
        '📅 Movimientos de Hoy',
        'render_widget_reservas_hoy'
    );
    
    wp_add_dashboard_widget(
        'crm_ingresos_mes',
        '💰 Ingresos del Mes',
        'render_widget_ingresos'
    );
}

function render_widget_reservas_hoy() {
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';
    
    // Check-ins de hoy
    echo '<div style="background: #dbeafe; padding: 20px; border-radius: 8px; text-align: center;">';
    echo '<h3 style="margin: 0; font-size: 36px; color: #1d4ed8;">3</h3>';
    echo '<p style="margin: 10px 0 0; color: #1e40af;">Check-ins Hoy</p>';
    echo '</div>';
    
    // Check-outs de hoy
    echo '<div style="background: #fef3c7; padding: 20px; border-radius: 8px; text-align: center;">';
    echo '<h3 style="margin: 0; font-size: 36px; color: #b45309;">2</h3>';
    echo '<p style="margin: 10px 0 0; color: #92400e;">Check-outs Hoy</p>';
    echo '</div>';
    
    echo '</div>';
    
    // Lista detallada
    echo '<table class="widefat" style="margin-top: 20px;">';
    echo '<thead><tr><th>Huésped</th><th>Propiedad</th><th>Tipo</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>Juan Pérez</td><td>Villa Sol</td><td><span style="color: green;">✅ Check-in</span></td></tr>';
    echo '<tr><td>María López</td><td>Casa Azul</td><td><span style="color: orange;">🚪 Check-out</span></td></tr>';
    echo '</tbody>';
    echo '</table>';
}

function render_widget_ingresos() {
    // Calcular ingresos del mes actual
    $fecha_inicio = date('Y-m-01');
    $fecha_fin = date('Y-m-t');
    
    $args = array(
        'limit' => -1,
        'status' => array('completed', 'processing'),
        'date_created' => $fecha_inicio . '...' . $fecha_fin,
    );
    
    $orders = wc_get_orders($args);
    $total = 0;
    
    foreach ($orders as $order) {
        $total += $order->get_total();
    }
    
    echo '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 8px; color: white; text-align: center;">';
    echo '<h3 style="margin: 0; font-size: 48px; font-weight: 900;">' . wc_price($total) . '</h3>';
    echo '<p style="margin: 10px 0 0; opacity: 0.9;">Ingresos de ' . date_i18n('F Y') . '</p>';
    echo '</div>';
    
    // Gráfico de barras simple (puedes usar Chart.js aquí)
    echo '<div style="margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 6px;">';
    echo '<p style="margin: 0; color: #666; font-size: 13px;">📊 Comparado con el mes anterior: <strong style="color: #10b981;">+15%</strong></p>';
    echo '</div>';
}
```

**Entregable:** Screenshot del dashboard con widgets activos

---

### **Tarea 4.2: Página de Reportes**
**Objetivo:** Crear página custom para ver estadísticas avanzadas

**Menú:** `ALQUIPRESS > Reportes`

**Métricas a mostrar:**
- Tasa de ocupación por propiedad
- Top 5 clientes (por gasto)
- Ingresos por temporada (Alta/Media/Baja)
- Propiedades más rentables

**Librería recomendada:** Chart.js (incluir vía CDN)

**Estructura HTML:**
```php
function render_reportes_page() {
    ?>
    <div class="wrap" style="max-width: 1400px;">
        <h1>📊 Reportes y Análisis</h1>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0;">
            <!-- KPI Cards -->
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3>Tasa de Ocupación</h3>
                <p style="font-size: 42px; font-weight: bold; color: #3b82f6; margin: 10px 0;">78%</p>
                <p style="color: #666;">Promedio anual</p>
            </div>
            
            <!-- Repetir para otros KPIs -->
        </div>
        
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>Ingresos por Mes</h2>
            <canvas id="chartIngresos" width="400" height="100"></canvas>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('chartIngresos').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [{
                label: 'Ingresos (€)',
                data: [12000, 15000, 18000, 22000, 28000, 35000, 42000, 40000, 30000, 25000, 20000, 18000],
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
    <?php
}
```

**Entregable:** Screenshot de la página de reportes con gráfico

---

## 🚀 **FASE 5: TESTING Y REFINAMIENTO**
*Duración estimada: 2-3 días*

### **Tarea 5.1: Testing de Flujos**

**Checklist de pruebas:**

- [ ] **Crear nuevo huésped** → Rellenar todos los campos ACF → Guardar → Verificar que se guardó
- [ ] **Editar propietario** → Cambiar comisión → Ver IBAN → Subir contrato PDF
- [ ] **Cambiar estado de pedido** → De "Pago Recibido" a "Pendiente Check-in" → Verificar en Pipeline
- [ ] **Mover tarjeta en Kanban** → Drag & drop → Verificar que cambia el estado del pedido
- [ ] **Ver perfil de huésped** → Comprobar que muestra historial de reservas
- [ ] **Dashboard** → Verificar que los widgets muestran datos reales

**Entregable:** Documento con capturas de cada prueba ✅

---

### **Tarea 5.2: Optimización de Rendimiento**

**Acciones:**
```php
// Desactivar revisiones en CPT Propietarios (para no saturar BD)
add_filter('wp_revisions_to_keep', function($num, $post) {
    if ($post->post_type === 'propietario') {
        return 0;
    }
    return $num;
}, 10, 2);

// Lazy load de avatars en listados
add_filter('get_avatar', function($avatar) {
    return str_replace('<img', '<img loading="lazy"', $avatar);
});
```

**Entregable:** Informe de GTmetrix/PageSpeed del wp-admin

---

### **Tarea 5.3: Documentación Interna**

**Crear página en el admin:** `ALQUIPRESS > Ayuda`

**Contenido:**
- Guía rápida: Cómo crear un huésped
- Guía rápida: Cómo gestionar una reserva
- Glosario de estados
- FAQ

**Formato:** Usar **ACF Flexible Content** para crear secciones tipo Accordion

**Entregable:** Screenshot de la página de ayuda

---

## 📦 **ENTREGABLES FINALES**

### **Checklist de Cierre:**

- [ ] Todos los campos ACF importados y funcionales
- [ ] Estados personalizados de pedido creados
- [ ] Vista Pipeline/Kanban operativa
- [ ] Perfiles de Huésped y Propietario diseñados
- [ ] Dashboard con widgets informativos
- [ ] Página de reportes con Chart.js
- [ ] Testing completado
- [ ] Documentación interna publicada

### **Paquete de entrega:**

```
📁 /alquipress-crm-build/
├── 📄 README.md (Instrucciones de instalación)
├── 📁 /code-snippets/ (Todos los snippets exportados)
├── 📁 /acf-json/ (Todos los grupos de campos)
├── 📁 /screenshots/ (Capturas de cada sección)
├── 📁 /videos/ (Demos de 30seg de cada funcionalidad)
└── 📄 TESTING-REPORT.md (Resultados de las pruebas)
```

---

## ⏱️ **TIMELINE ESTIMADO**

| Fase | Duración | Dependencias |
|------|----------|--------------|
| Fase 1 | 3-4 días | - |
| Fase 2 | 5-6 días | Fase 1 completa |
| Fase 3 | 4-5 días | Fase 2 completa |
| Fase 4 | 3-4 días | Fase 3 completa |
| Fase 5 | 2-3 días | Todas las anteriores |
| **TOTAL** | **17-22 días laborables** | ≈ 4-5 semanas |

---

## 💡 **RECOMENDACIONES FINALES**

1. **Prioriza Fase 1 y 2** → Son las que dan valor inmediato
2. **Testing continuo** → No dejes todo el testing para el final
3. **Backup antes de cada fase** → Usa UpdraftPlus
4. **Staging environment** → Trabaja en copia, publica cuando esté perfecto

---

¿Quieres que empiece a desarrollarte el código de alguna tarea específica, o prefieres que prioricemos las tareas por impacto? 🚀