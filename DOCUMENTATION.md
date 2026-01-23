# ALQUIPRESS Core - Documentación Técnica

**Versión:** 1.0.0
**Fecha:** 2026-01-23
**WordPress:** 6.0+
**PHP:** 8.0+
**Dependencias:** WooCommerce, ACF Pro

---

## Índice

1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Módulos Implementados](#módulos-implementados)
3. [Estructura de Archivos](#estructura-de-archivos)
4. [Base de Datos](#base-de-datos)
5. [Hooks y Filtros](#hooks-y-filtros)
6. [APIs y Endpoints](#apis-y-endpoints)
7. [Optimización y Caché](#optimización-y-caché)
8. [Guía de Desarrollo](#guía-de-desarrollo)

---

## Arquitectura del Sistema

### Patrón de Diseño

ALQUIPRESS utiliza una arquitectura **modular** basada en:

- **Module Manager:** Sistema centralizado de gestión de módulos
- **Singleton Pattern:** Para el Performance Optimizer
- **Dependency Injection:** Módulos con dependencias declaradas
- **Hook System:** Integración nativa con WordPress

### Flujo de Inicialización

```
plugins_loaded
    └─> alquipress_init()
        ├─> Module Manager instantiation
        ├─> load_active_modules()
        │   └─> require_once module files
        └─> Performance Optimizer init
```

### Componentes Principales

1. **Module Manager** (`class-module-manager.php`)
   - Registro de módulos
   - Activación/desactivación
   - Gestión de dependencias
   - Interfaz de configuración

2. **Performance Optimizer** (`class-performance-optimizer.php`)
   - Sistema de caché con transients
   - Optimización de queries
   - Carga condicional de assets
   - Cron jobs para limpieza

3. **Frontend Filters** (`class-frontend-filters.php`)
   - Modificaciones de plantillas
   - Filtros de contenido
   - Customizaciones de WooCommerce

---

## Módulos Implementados

### Fase 1: Fundamentos Técnicos

#### 1. Taxonomías (`taxonomies`)

**Archivo:** `includes/modules/taxonomies/taxonomies.php`

**Taxonomías registradas:**

| Taxonomía | Tipo | Post Types | Términos |
|-----------|------|------------|----------|
| `poblacion` | Jerárquica | `product` | 33 (Marina Alta) |
| `zona` | Plana | `product` | 4 |
| `caracteristicas` | Plana | `product` | 27 |

**Auto-población:**

```php
// Marina Alta con todos sus municipios
register_taxonomy('poblacion', ['product'], [
    'hierarchical' => true,
    'show_admin_column' => true
]);

// Poblar términos al activar
private function populate_poblacion() {
    $comarca = wp_insert_term('Marina Alta', 'poblacion');
    foreach ($municipalities as $municipality) {
        wp_insert_term($municipality, 'poblacion', [
            'parent' => $comarca['term_id']
        ]);
    }
}
```

**Características con íconos:**

```php
// 27 características con FontAwesome
'piscina' => 'fa-swimming-pool',
'wifi' => 'fa-wifi',
'parking' => 'fa-car'
// ... etc
```

**Selector de íconos:** `taxonomies/icon-selector.php`

---

#### 2. CRM Propietarios (`crm-owners`)

**Archivo:** `includes/modules/crm-owners/crm-owners.php`

**CPT:** `propietario`

**Campos ACF:**
- `owner_name` (text)
- `owner_surname` (text)
- `owner_email` (email)
- `owner_phone` (text)
- `owner_iban` (text) - con máscara JS
- `owner_commission` (number) - porcentaje

**Columnas personalizadas:**
```php
add_filter('manage_propietario_posts_columns', [$this, 'add_custom_columns']);
// Columns: email, phone, IBAN (ofuscado)
```

**Cálculo de ingresos:** `crm-owners/owner-revenue.php`

```php
// Calcular ingresos del propietario
function calculate_owner_revenue($owner_id, $year) {
    // Get all properties of owner
    // Get all completed orders
    // Apply commission
    // Return net revenue
}
```

---

#### 3. CRM Huéspedes (`crm-guests`)

**Archivo:** `includes/modules/crm-guests/crm-guests.php`

**User Meta Fields:**
- `guest_rating` (number 1-5)
- `guest_preferences` (array)
- `guest_notes` (textarea)

**Preferencias disponibles:**
```php
$preferences = [
    'piscina', 'wifi', 'parking', 'mascotas',
    'aire_acondicionado', 'cocina', 'terraza',
    'vistas_mar', 'cerca_playa', 'zona_tranquila'
];
```

**Columnas en Users:**
```php
add_filter('manage_users_columns', [$this, 'add_user_columns']);
// Columns: Rating (stars), Total Spent, Last Booking
```

---

#### 4. Pipeline de Reservas (`booking-pipeline`)

**Archivo:** `includes/modules/booking-pipeline/pipeline.php`

**Estados personalizados:**

```php
function register_order_statuses() {
    register_post_status('wc-deposito-ok', [
        'label' => 'Depósito OK',
        'public' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Depósito OK <span class="count">(%s)</span>', ...)
    ]);
    // ... otros estados
}
```

| Estado | Slug | Color | Descripción |
|--------|------|-------|-------------|
| Depósito OK | `deposito-ok` | Azul | Depósito recibido |
| Pendiente Check-in | `pending-checkin` | Amarillo | Esperando entrada |
| En Curso | `in-progress` | Verde | Estancia activa |
| Revisión Salida | `checkout-review` | Naranja | Pendiente revisión |
| Depósito Devuelto | `deposit-refunded` | Gris | Depósito retornado |

**Workflow recomendado:**
```
pending → deposito-ok → pending-checkin → in-progress → checkout-review → completed
```

---

### Fase 2: Personalización UI Backend

#### 5. Dashboard Widgets (`dashboard-widgets`)

**Archivo:** `includes/modules/dashboard-widgets/dashboard-widgets.php`

**Widgets implementados:**

1. **Movimientos de Hoy**
   ```php
   function widget_movements_today() {
       $checkins = $this->get_checkins_today();
       $checkouts = $this->get_checkouts_today();
       // Render widget
   }
   ```

2. **Ingresos del Mes**
   ```php
   function widget_revenue_month() {
       $current_month = $this->get_revenue_between_dates(
           date('Y-m-01'), date('Y-m-t')
       );
       $last_month = $this->get_revenue_between_dates(...);
       $percentage_change = (($current - $last) / $last) * 100;
   }
   ```

3. **Estado de Propiedades**
   ```php
   function widget_property_status() {
       $total_properties = wp_count_posts('product')->publish;
       $occupied = $this->get_occupied_properties_count();
       $occupancy_rate = ($occupied / $total) * 100;
   }
   ```

4. **Alertas**
   - Pedidos pendientes
   - Propietarios sin IBAN
   - Propiedades en revisión

---

#### 6. Pipeline Kanban (`pipeline-kanban`)

**Archivo:** `includes/modules/pipeline-kanban/pipeline-kanban.php`

**Página:** `admin.php?page=alquipress-pipeline`

**Estructura:**

```html
<div class="kanban-board">
    <div class="kanban-columns">
        <!-- 7 columnas, una por estado -->
        <div class="kanban-column" data-status="pending">
            <h3>Pendiente <span class="count">5</span></h3>
            <div class="kanban-cards">
                <!-- Tarjetas de pedidos -->
            </div>
        </div>
    </div>
</div>
```

**Funcionalidades:**
- Búsqueda en tiempo real (JS)
- Filtros por fecha y propiedad
- Badge "URGENTE" si check-in < 3 días
- Smooth scroll horizontal
- Responsive

**JavaScript:** `pipeline-kanban.js`

```javascript
// Búsqueda
$('#search-orders').on('input', function() {
    const query = $(this).val().toLowerCase();
    $('.kanban-card').each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.includes(query));
    });
});
```

---

#### 7. Order Columns (`order-columns`)

**Archivo:** `includes/modules/order-columns/order-columns.php`

**Columnas añadidas:**

```php
add_filter('manage_shop_order_posts_columns', [$this, 'add_order_columns']);

function add_order_columns($columns) {
    $new_columns = [
        'property' => 'Propiedad',
        'booking_dates' => 'Fechas',
        'property_owner' => 'Propietario',
        'status_indicator' => 'Semáforo'
    ];
    return array_merge($columns, $new_columns);
}
```

**Compatibilidad HPOS:**
```php
// Detectar si HPOS está activo
if (wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()) {
    add_filter('manage_woocommerce_page_wc-orders_columns', ...);
} else {
    add_filter('manage_shop_order_posts_columns', ...);
}
```

---

#### 8. Perfil de Huésped (`guest-profile`)

**Archivo:** `includes/modules/guest-profile/guest-profile.php`

**Página:** `user-edit.php?user_id=X&action=view_profile`

**Secciones:**

1. **Header Card**
   - Avatar grande
   - Nombre completo
   - Email y teléfono
   - Rating con estrellas

2. **Stats Grid** (4 tarjetas)
   - Total gastado
   - Total reservas
   - Última reserva
   - Estado (activo/inactivo)

3. **Preferencias**
   - Grid de iconos con tooltips

4. **Notas Privadas**
   - Read-only WYSIWYG content

5. **Historial de Reservas**
   - Tabla con todas las reservas
   - Ordenado por fecha DESC

**CSS:** `guest-profile.css` con gradientes y animaciones

---

#### 9. Editor de Huésped (`guest-editor`)

**Archivo:** `includes/modules/guest-editor/guest-editor.php`

**Página:** `user-edit.php?user_id=X&action=edit_guest`

**Formulario mejorado:**

```html
<form method="post" class="guest-editor-form">
    <div class="form-grid">
        <!-- Columna izquierda: Datos básicos -->
        <div class="form-section">
            <h3>Datos del Huésped</h3>
            <input name="first_name" value="<?php echo $first_name; ?>">
            <input name="last_name" ...>
            <input name="user_email" ...>
        </div>

        <!-- Columna derecha: Valoración y preferencias -->
        <div class="form-section">
            <h3>Valoración</h3>
            <input type="range" id="guest_rating" min="1" max="5">
            <div id="rating-stars">⭐⭐⭐⭐⭐</div>

            <h3>Preferencias</h3>
            <div class="preferences-checkboxes">
                <!-- Visual checkbox cards -->
            </div>
        </div>
    </div>

    <!-- Editor de notas -->
    <?php wp_editor($notes, 'guest_notes'); ?>

    <button type="submit" class="button button-primary">Guardar Cambios</button>
</form>
```

**JavaScript en tiempo real:**
```javascript
$('#guest_rating').on('input', function() {
    const rating = $(this).val();
    updateStars(rating); // Actualizar preview
});
```

---

#### 10. UI Enhancements (`ui-enhancements`)

**Archivo:** `includes/modules/ui-enhancements/ui-enhancements.php`

**Mejoras globales:**

```php
function add_inline_styles() {
    if (get_current_screen()->id === 'propietario') {
        echo '<style>
            .acf-tab-group .acf-tab-button[data-key="datos-financieros"] {
                background: #fffbeb;
                border-left: 4px solid #f0b849;
            }
        </style>';
    }
}
```

**Páginas afectadas:**
- `post.php?post_type=propietario` - Estilos para propietarios
- `user-edit.php` - Estilos para huéspedes
- ACF field groups - Personalización global

---

### Fase 3: Funcionalidades Avanzadas

#### 11. Preferencias Avanzadas (`advanced-preferences`)

**Archivo:** `includes/modules/advanced-preferences/advanced-preferences.php`

**Widget en Dashboard:**

```php
function dashboard_widget() {
    $stats = Alquipress_Performance_Optimizer::get_cached_preferences_stats();
    $top_5 = array_slice($stats, 0, 5);

    foreach ($top_5 as $key => $data) {
        echo '<div class="preference-stat">';
        echo '<span>' . $labels[$key] . '</span>';
        echo '<div class="progress-bar">';
        echo '<div class="progress-fill" style="width: ' . $data['percentage'] . '%"></div>';
        echo '</div>';
        echo '<span class="percentage">' . round($data['percentage'], 1) . '%</span>';
        echo '</div>';
    }
}
```

**Modal AJAX:**
```javascript
$('#view-full-analysis').on('click', function(e) {
    e.preventDefault();
    $.ajax({
        url: ajaxurl,
        data: {
            action: 'alquipress_get_preferences_analysis'
        },
        success: function(response) {
            showModal(response.data.html);
        }
    });
});
```

**Shortcode:**
```php
add_shortcode('guest_preferences', [$this, 'render_shortcode']);

// Uso:
// [guest_preferences style="icons"]
// [guest_preferences style="badges"]
// [guest_preferences style="list"]
```

---

#### 12. Acciones Rápidas (`quick-actions`)

**Archivo:** `includes/modules/quick-actions/quick-actions.php`

**Admin Bar Menu:**

```php
function add_admin_bar_menu($wp_admin_bar) {
    $wp_admin_bar->add_node([
        'id' => 'alquipress_quick',
        'title' => '⚡ ALQUIPRESS',
        'href' => admin_url('admin.php?page=alquipress-settings')
    ]);

    // Submenús
    $wp_admin_bar->add_node([
        'id' => 'alquipress_pipeline',
        'parent' => 'alquipress_quick',
        'title' => '📊 Pipeline de Reservas',
        'href' => admin_url('admin.php?page=alquipress-pipeline')
    ]);
    // ... más items
}
```

**Atajos de Teclado:**

| Atajo | Acción |
|-------|--------|
| `Ctrl+K` | Buscar |
| `Ctrl+P` | Ir a Pipeline |
| `Ctrl+H` | Ir a Dashboard |
| `Shift` (mantener) | Mostrar hints |

**Vista Rápida Modal:**

```javascript
$('.alq-quick-view').on('click', function(e) {
    e.preventDefault();
    const orderId = $(this).data('order-id');

    $.ajax({
        url: ajaxurl,
        data: {
            action: 'alquipress_quick_view',
            order_id: orderId
        },
        success: function(response) {
            $('#alq-quick-view-modal').html(response.data.html).fadeIn();
        }
    });
});
```

---

#### 13. Notificaciones CRM (`crm-notifications`)

**Archivo:** `includes/modules/crm-notifications/crm-notifications.php`

**Sistema de notificaciones:**

```php
function get_active_notifications() {
    $notifications = [];
    $dismissed = get_user_meta(get_current_user_id(), 'alquipress_dismissed_notifications', true) ?: [];

    // Check-ins hoy
    $checkins_today = $this->get_checkins_today();
    if (!empty($checkins_today) && !in_array('checkins_today_' . date('Y-m-d'), $dismissed)) {
        $notifications[] = [
            'id' => 'checkins_today_' . date('Y-m-d'),
            'type' => 'info',
            'title' => 'Check-ins Programados Hoy',
            'message' => 'Hay ' . count($checkins_today) . ' check-in(s) programado(s) para hoy.',
            'action_url' => admin_url('admin.php?page=alquipress-pipeline'),
            'action_text' => 'Ver Pipeline',
            'dismissible' => true
        ];
    }

    // ... más notificaciones
    return $notifications;
}
```

**Tipos de notificaciones:**

| ID | Tipo | Condición | Persistencia |
|----|------|-----------|--------------|
| `checkins_today_{date}` | info | Check-ins hoy | Diaria |
| `checkouts_today_{date}` | warning | Check-outs hoy | Diaria |
| `checkins_tomorrow_{date}` | info | Check-ins mañana | Diaria |
| `pending_payments` | warning | ≥5 pedidos pendientes | Hasta descartar |
| `owners_no_iban` | error | Propietarios sin IBAN | Hasta descartar |
| `checkout_reviews` | warning | Propiedades en revisión | Hasta descartar |

**Descarte AJAX:**

```javascript
$('.notice-dismiss').on('click', function() {
    $.ajax({
        url: ajaxurl,
        data: {
            action: 'alquipress_dismiss_notification',
            notification_id: notificationId,
            nonce: nonce
        }
    });
});
```

**Badge contador:**

```php
function add_notification_badge() {
    global $menu;
    $count = count($this->get_active_notifications());

    foreach ($menu as $key => $item) {
        if ($item[2] === 'alquipress-settings') {
            $menu[$key][0] .= ' <span class="awaiting-mod count-' . $count . '"><span class="plugin-count">' . $count . '</span></span>';
        }
    }
}
```

---

### Fase 4: Informes y Dashboards

#### 14. Informes y Analíticas (`advanced-reports`)

**Archivo:** `includes/modules/advanced-reports/advanced-reports.php`

**Página:** `admin.php?page=alquipress-reports`

**Arquitectura:**

```
Reports Page
├─> Stats Overview (4 cards)
├─> Filters (year selector)
└─> Tabs
    ├─> Revenue Tab
    │   ├─> Monthly Revenue (Line Chart)
    │   └─> Season Revenue (Doughnut Chart)
    ├─> Occupancy Tab
    │   ├─> Monthly Occupancy (Bar Chart)
    │   └─> Comparison (Pie Chart)
    ├─> Clients Tab
    │   ├─> Top 5 Table
    │   └─> Rating Distribution (Bar Chart)
    └─> Properties Tab
        ├─> Top 5 Table
        └─> Revenue Comparison (Horizontal Bar Chart)
```

**AJAX Endpoints:**

| Endpoint | Datos Retornados |
|----------|------------------|
| `alquipress_get_report_data&report_type=overview` | Stats rápidas del año |
| `revenue_monthly` | Array de ingresos por mes |
| `revenue_season` | Ingresos por temporada |
| `occupancy_monthly` | % ocupación por mes |
| `occupancy_comparison` | Noches reservadas vs disponibles |
| `top_clients` | Top 5 clientes con datos |
| `clients_rating` | Distribución de ratings |
| `top_properties` | Top 5 propiedades |
| `properties_comparison` | Top 10 comparativa |

**Chart.js 4.4.0:**

```javascript
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Ene', 'Feb', ...],
        datasets: [{
            label: 'Ingresos (€)',
            data: [1200, 1500, ...],
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Ingresos: ' + context.parsed.y + ' €';
                    }
                }
            }
        }
    }
});
```

**Cálculos Automáticos:**

```php
// Ingresos mensuales
private function get_revenue_monthly($year) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT MONTH(p.post_date) as month, SUM(pm.meta_value) as total
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE pm.meta_key = '_order_total'
        AND p.post_status IN ('wc-completed', 'wc-in-progress')
        AND YEAR(p.post_date) = %d
        GROUP BY MONTH(p.post_date)",
        $year
    ));
}

// Tasa de ocupación
private function calculate_occupancy_rate($year) {
    $total_properties = wp_count_posts('product')->publish;
    $days_in_year = ($year == date('Y')) ? date('z') : 365;
    $available_nights = $total_properties * $days_in_year;

    // Contar noches reservadas
    $booked_nights = $this->count_booked_nights($year);

    return ($booked_nights / $available_nights) * 100;
}
```

---

## Estructura de Archivos

```
alquipress-core/
├── alquipress-core.php                 # Plugin principal
├── TESTING.md                          # Plan de testing
├── DOCUMENTATION.md                    # Este archivo
├── README.md                           # Readme del plugin
├── includes/
│   ├── class-module-manager.php        # Gestor de módulos
│   ├── class-frontend-filters.php      # Filtros frontend
│   ├── class-performance-optimizer.php # Optimizador
│   ├── admin/
│   │   └── settings-page.php           # Página de configuración
│   └── modules/
│       ├── taxonomies/
│       │   ├── taxonomies.php
│       │   └── icon-selector.php
│       ├── crm-owners/
│       │   ├── crm-owners.php
│       │   ├── owner-revenue.php
│       │   └── assets/
│       │       ├── iban-mask.js
│       │       └── owner-styles.css
│       ├── crm-guests/
│       │   ├── crm-guests.php
│       │   └── assets/
│       ├── booking-pipeline/
│       │   └── pipeline.php
│       ├── dashboard-widgets/
│       │   ├── dashboard-widgets.php
│       │   └── assets/
│       │       └── dashboard-widgets.css
│       ├── pipeline-kanban/
│       │   ├── pipeline-kanban.php
│       │   └── assets/
│       │       ├── pipeline-kanban.css
│       │       └── pipeline-kanban.js
│       ├── guest-profile/
│       │   ├── guest-profile.php
│       │   └── assets/
│       │       └── guest-profile.css
│       ├── guest-editor/
│       │   ├── guest-editor.php
│       │   └── assets/
│       │       └── guest-editor.css
│       ├── ui-enhancements/
│       │   ├── ui-enhancements.php
│       │   └── assets/
│       │       └── ui-enhancements.css
│       ├── advanced-preferences/
│       │   ├── advanced-preferences.php
│       │   └── assets/
│       │       └── advanced-preferences.css
│       ├── quick-actions/
│       │   ├── quick-actions.php
│       │   └── assets/
│       │       └── quick-actions.css
│       ├── crm-notifications/
│       │   ├── crm-notifications.php
│       │   └── assets/
│       │       └── crm-notifications.css
│       └── advanced-reports/
│           ├── advanced-reports.php
│           └── assets/
│               ├── advanced-reports.css
│               └── advanced-reports.js
└── acf-json/                           # ACF field exports
    ├── group_propietarios.json
    └── group_guests.json
```

---

## Base de Datos

### Tablas de WordPress

**wp_posts:**
- CPT `propietario` (propietarios)
- CPT `shop_order` (pedidos WooCommerce)
- CPT `product` (propiedades)

**wp_postmeta:**
```sql
-- Datos de propietarios (ACF)
meta_key: owner_name, owner_surname, owner_email, owner_phone, owner_iban, owner_commission

-- Datos de reservas
meta_key: _booking_checkin_date, _booking_checkout_date, _booking_property_id

-- Relación propiedad-propietario
meta_key: property_owner (post_id de propietario)
```

**wp_usermeta:**
```sql
-- Datos de huéspedes
meta_key: guest_rating (INT 1-5)
meta_key: guest_preferences (serialized array)
meta_key: guest_notes (longtext)
meta_key: alquipress_dismissed_notifications (serialized array)
```

**wp_terms & wp_term_taxonomy:**
```sql
-- Taxonomía: poblacion
SELECT * FROM wp_terms WHERE term_id IN (
    SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'poblacion'
);

-- Taxonomía: caracteristicas con meta de íconos
SELECT * FROM wp_termmeta WHERE meta_key = 'icon_class';
```

**wp_options:**
```sql
-- Configuración de módulos activos
option_name: alquipress_modules
option_value: {"taxonomies":true,"crm-guests":true,...}

-- Transients de caché
option_name: _transient_alquipress_top_clients_2026_5
option_name: _transient_timeout_alquipress_top_clients_2026_5
```

### Queries Importantes

**Top clientes del año:**
```sql
SELECT
    pm_customer.meta_value as customer_id,
    COUNT(DISTINCT p.ID) as order_count,
    SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_spent
FROM wp_posts p
INNER JOIN wp_postmeta pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = '_customer_user'
INNER JOIN wp_postmeta pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
WHERE p.post_type = 'shop_order'
AND p.post_status IN ('wc-completed', 'wc-in-progress')
AND YEAR(p.post_date) = 2026
GROUP BY customer_id
ORDER BY total_spent DESC
LIMIT 5;
```

**Check-ins de hoy:**
```sql
SELECT post_id
FROM wp_postmeta
WHERE meta_key = '_booking_checkin_date'
AND meta_value = CURDATE();
```

---

## Hooks y Filtros

### Hooks de WordPress

**Acciones:**
```php
// Inicialización
add_action('plugins_loaded', 'alquipress_init');

// Admin
add_action('admin_menu', 'add_settings_page');
add_action('admin_notices', 'show_admin_notices');
add_action('admin_enqueue_scripts', 'enqueue_assets');
add_action('admin_bar_menu', 'add_admin_bar_menu', 100);

// Dashboard
add_action('wp_dashboard_setup', 'add_dashboard_widgets');

// User/Post
add_action('profile_update', 'clear_preferences_cache');
add_action('save_post', 'clear_reports_cache');
add_action('woocommerce_order_status_changed', 'clear_reports_cache');

// Cron
add_action('alquipress_clear_daily_cache', 'clear_daily_cache');
```

**Filtros:**
```php
// Columnas
add_filter('manage_propietario_posts_columns', 'add_custom_columns');
add_filter('manage_shop_order_posts_columns', 'add_order_columns');
add_filter('manage_users_columns', 'add_user_columns');

// WooCommerce
add_filter('wc_order_statuses', 'add_custom_order_statuses');
add_filter('woocommerce_reports_order_statuses', 'add_custom_statuses_to_reports');
```

### Hooks Personalizados

**Crear hooks propios:**
```php
// En module
do_action('alquipress_before_save_guest_data', $user_id, $data);
// Guardar datos
do_action('alquipress_after_save_guest_data', $user_id);

// Para desarrolladores externos
add_action('alquipress_after_save_guest_data', function($user_id) {
    // Custom logic
});
```

---

## APIs y Endpoints

### AJAX Endpoints

**Registro:**
```php
add_action('wp_ajax_alquipress_quick_view', [$this, 'ajax_quick_view']);
add_action('wp_ajax_alquipress_get_report_data', [$this, 'ajax_get_report_data']);
add_action('wp_ajax_alquipress_dismiss_notification', [$this, 'ajax_dismiss_notification']);
add_action('wp_ajax_alquipress_get_preferences_analysis', [$this, 'ajax_get_preferences_analysis']);
```

**Ejemplo de implementación:**
```php
public function ajax_quick_view() {
    // Verificar nonce
    check_ajax_referer('alquipress_quick_actions', 'nonce');

    // Verificar permisos
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
    }

    // Obtener datos
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Pedido no encontrado']);
    }

    // Renderizar HTML
    ob_start();
    include 'templates/quick-view.php';
    $html = ob_get_clean();

    // Retornar
    wp_send_json_success(['html' => $html]);
}
```

**Llamada desde JavaScript:**
```javascript
$.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'alquipress_quick_view',
        order_id: orderId,
        nonce: alquipressData.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data.html);
        }
    }
});
```

---

## Optimización y Caché

### Sistema de Caché

**Transients API:**

```php
// Guardar en caché por 1 hora
set_transient('alquipress_top_clients_2026', $data, HOUR_IN_SECONDS);

// Recuperar
$data = get_transient('alquipress_top_clients_2026');
if (false === $data) {
    $data = calculate_expensive_data();
    set_transient('alquipress_top_clients_2026', $data, HOUR_IN_SECONDS);
}
```

**Object Cache (wp_cache):**

```php
// Caché de 1 hora
$count = wp_cache_get('alquipress_checkins_today_2026-01-23');
if (false === $count) {
    $count = $wpdb->get_var("SELECT COUNT(*) ...");
    wp_cache_set('alquipress_checkins_today_2026-01-23', $count, '', HOUR_IN_SECONDS);
}
```

### Estrategia de Invalidación

**Automática:**
```php
// Al actualizar pedido
add_action('woocommerce_order_status_changed', function($order_id) {
    Alquipress_Performance_Optimizer::get_instance()->clear_reports_cache();
});

// Al actualizar usuario
add_action('profile_update', function($user_id) {
    delete_transient('alquipress_preferences_stats');
});
```

**Manual:**
```php
// Botón de limpiar caché en configuración
if (isset($_POST['clear_cache'])) {
    Alquipress_Performance_Optimizer::clear_reports_cache();
    add_settings_error('alquipress', 'cache_cleared', 'Caché limpiada', 'success');
}
```

**Cron Diario:**
```php
wp_schedule_event(time(), 'daily', 'alquipress_clear_daily_cache');
add_action('alquipress_clear_daily_cache', function() {
    // Limpiar transients antiguos
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_alquipress_%'");
});
```

### Optimización de Queries

**Evitar N+1:**
```php
// MAL
foreach ($orders as $order) {
    $customer = get_userdata($order->get_customer_id()); // Query por iteración
}

// BIEN
$customer_ids = array_unique(array_map(function($order) {
    return $order->get_customer_id();
}, $orders));
$customers = get_users(['include' => $customer_ids]); // 1 solo query
```

**Índices recomendados:**
```sql
-- Mejorar búsqueda de bookings por fecha
CREATE INDEX idx_booking_checkin ON wp_postmeta(meta_key, meta_value)
WHERE meta_key = '_booking_checkin_date';

CREATE INDEX idx_booking_checkout ON wp_postmeta(meta_key, meta_value)
WHERE meta_key = '_booking_checkout_date';
```

---

## Guía de Desarrollo

### Crear un Nuevo Módulo

**1. Crear archivo del módulo:**
```
includes/modules/mi-modulo/mi-modulo.php
```

**2. Estructura básica:**
```php
<?php
/**
 * Módulo: Mi Módulo
 * Descripción breve
 */

if (!defined('ABSPATH')) exit;

class Alquipress_Mi_Modulo
{
    public function __construct()
    {
        // Hooks
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_admin_page()
    {
        add_submenu_page(
            'alquipress-settings',
            'Mi Módulo',
            'Mi Módulo',
            'manage_options',
            'alquipress-mi-modulo',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1>Mi Módulo</h1>
            <!-- Contenido -->
        </div>
        <?php
    }

    public function enqueue_assets($hook)
    {
        if ($hook !== 'alquipress_page_alquipress-mi-modulo') {
            return;
        }

        wp_enqueue_style(
            'alquipress-mi-modulo',
            ALQUIPRESS_URL . 'includes/modules/mi-modulo/assets/styles.css',
            [],
            ALQUIPRESS_VERSION
        );
    }
}

new Alquipress_Mi_Modulo();
```

**3. Registrar en Module Manager:**
```php
// includes/class-module-manager.php
'mi-modulo' => [
    'name' => 'Mi Módulo',
    'description' => 'Descripción del módulo',
    'file' => 'mi-modulo/mi-modulo.php',
    'dependencies' => [] // O ['otro-modulo']
]
```

**4. Activar módulo:**
```bash
wp option update alquipress_modules '{"mi-modulo":true,...}' --format=json
```

### Buenas Prácticas

**Seguridad:**
```php
// SIEMPRE validar nonces
check_ajax_referer('alquipress_action', 'nonce');

// SIEMPRE verificar permisos
if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Sin permisos']);
}

// SIEMPRE sanitizar input
$user_input = sanitize_text_field($_POST['input']);
$email = sanitize_email($_POST['email']);

// SIEMPRE escapar output
echo esc_html($variable);
echo esc_url($url);
echo esc_attr($attribute);
```

**Performance:**
```php
// Usar caché para datos costosos
$data = get_transient('mi_cache_key');
if (false === $data) {
    $data = expensive_calculation();
    set_transient('mi_cache_key', $data, HOUR_IN_SECONDS);
}

// Cargar assets solo donde se necesiten
function enqueue_assets($hook) {
    if ($hook !== 'mi_pagina') return;
    wp_enqueue_script(...);
}
```

**Código limpio:**
```php
// Nombres descriptivos
function get_top_clients_by_revenue($year, $limit = 5) { }

// Comentarios útiles
// Calcular tasa de ocupación: (noches reservadas / noches disponibles) * 100

// DRY - Don't Repeat Yourself
private function calculate_percentage($part, $total) {
    return $total > 0 ? ($part / $total) * 100 : 0;
}
```

### Testing

**Crear tests unitarios:**
```php
class Test_Mi_Modulo extends WP_UnitTestCase
{
    public function test_calculate_revenue()
    {
        $module = new Alquipress_Mi_Modulo();
        $revenue = $module->calculate_revenue(2026);

        $this->assertIsNumeric($revenue);
        $this->assertGreaterThanOrEqual(0, $revenue);
    }
}
```

**Comandos WP-CLI:**
```bash
# Activar módulo
wp option update alquipress_modules '{"mi-modulo":true}' --format=json

# Ver módulos activos
wp option get alquipress_modules --format=json

# Limpiar caché
wp transient delete --all
```

---

## Recursos Adicionales

**Documentación WordPress:**
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Database API](https://developer.wordpress.org/apis/database/)
- [Transients API](https://developer.wordpress.org/apis/transients/)

**Documentación WooCommerce:**
- [WooCommerce Docs](https://woocommerce.com/documentation/)
- [Custom Order Statuses](https://woocommerce.com/document/managing-orders/)

**Documentación ACF:**
- [ACF Documentation](https://www.advancedcustomfields.com/resources/)

**Chart.js:**
- [Chart.js Docs](https://www.chartjs.org/docs/latest/)

---

**Fin de la documentación técnica**

Para soporte o consultas, contactar al equipo de desarrollo.
