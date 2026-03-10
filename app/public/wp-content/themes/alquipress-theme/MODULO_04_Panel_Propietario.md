# MÓDULO 04 — Panel del Propietario (Frontend)

> **Proyecto:** ALQUIPRESS  
> **Stack:** WordPress · WooCommerce Bookings · ACF Pro · CPT `propietario` · Shortcodes personalizados  
> **Objetivo:** Portal privado en el frontend donde cada propietario ve sus KPIs, calendario de ocupación, historial de liquidaciones y documentos — sin acceder al wp-admin  
> **URL de acceso:** `/propietarios/mi-panel/`

---

## 1. Concepto: La ansiedad del propietario

El propietario tiene una única preocupación recurrente: **"¿Me están alquilando la casa? ¿Cuánto voy a cobrar?"**. Cada vez que llama para preguntar es tiempo perdido para ambas partes. El panel resuelve esto con transparencia total y acceso 24/7 desde cualquier dispositivo.

```
Propietario entra en /propietarios/mi-panel/
        │
        ├── 📊 KPIs del mes (ingresos netos, ocupación, próxima entrada)
        ├── 📅 Calendario visual de su propiedad
        ├── 💶 Historial de liquidaciones mensuales
        └── 📁 Documentos (contrato, licencia, facturas Drive)
```

---

## 2. Arquitectura técnica

```
CPT propietario (backend)
  └── ACF: owner_wp_user → vincula con usuario WP
        │
        ▼
Página WordPress: /propietarios/mi-panel/
  └── Template personalizado: page-owner-dashboard.php
        └── Shortcode: [alquipress_owner_dashboard]
              ├── Detecta usuario logueado
              ├── Encuentra su CPT propietario vinculado
              ├── Lee sus propiedades (owner_properties → product IDs)
              ├── Consulta pedidos WooCommerce de esas propiedades
              └── Renderiza los 4 módulos del panel
```

---

## 3. Estructura del CPT `propietario` y sus campos ACF

### 3.1 Registro del CPT

```php
<?php
/**
 * ALQUIPRESS — Módulo 04: CPT Propietario
 * Archivo: /wp-content/plugins/alquipress-core/includes/cpt-propietario.php
 */
add_action('init', 'alquipress_register_cpt_propietario');

function alquipress_register_cpt_propietario() {
    register_post_type('propietario', [
        'public'       => false,   // No visible en frontend directamente
        'show_ui'      => true,    // Sí visible en wp-admin
        'label'        => 'Propietarios',
        'supports'     => ['title'],
        'menu_icon'    => 'dashicons-businessperson',
        'show_in_rest' => false,   // Datos sensibles: fuera del API REST
        'capabilities' => [
            // Solo admin y shop_manager pueden gestionarlos
            'edit_post'          => 'manage_woocommerce',
            'read_post'          => 'manage_woocommerce',
            'delete_post'        => 'manage_woocommerce',
            'edit_posts'         => 'manage_woocommerce',
            'edit_others_posts'  => 'manage_woocommerce',
            'publish_posts'      => 'manage_woocommerce',
            'read_private_posts' => 'manage_woocommerce',
        ],
    ]);
}
```

### 3.2 Campos ACF del propietario (resumen)

| Pestaña | Campo | Nombre interno | Tipo |
|---|---|---|---|
| **Contacto** | Usuario WP vinculado | `owner_wp_user` | User |
| **Contacto** | Teléfono | `owner_phone` | Text |
| **Contacto** | Email de gestión | `owner_email_management` | Email |
| **Contacto** | WhatsApp | `owner_whatsapp` | URL |
| **Financiero** | % Comisión | `owner_commission_rate` | Number |
| **Financiero** | IBAN | `owner_iban` | Text |
| **Financiero** | Titular cuenta | `owner_billing_name` | Text |
| **Financiero** | NIF/CIF | `owner_tax_id` | Text |
| **Propiedades** | Propiedades asignadas | `owner_properties` | Relationship → product |
| **Propiedades** | Contrato PDF | `owner_contract_pdf` | File |
| **Propiedades** | Vencimiento contrato | `owner_contract_expiry` | Date Picker |
| **Propiedades** | Info llaves | `owner_keys_info` | Textarea |
| **Documentos** | Carpeta Google Drive | `owner_drive_folder_url` | URL |

> ⚠️ Los campos financieros (IBAN, NIF) solo son visibles en wp-admin para roles `administrator` y `shop_manager`. Nunca se exponen en el frontend.

---

## 4. Rol de WordPress para propietarios

Los propietarios tienen su propio rol con permisos mínimos: pueden loguearse y ver su panel, nada más.

```php
/**
 * Registrar el rol "Propietario" en WordPress
 * Ejecutar una sola vez en la activación del plugin
 */
function alquipress_add_owner_role() {
    add_role(
        'propietario_alquipress',
        'Propietario',
        [
            'read' => true,  // Permiso mínimo para loguearse
        ]
    );
}
register_activation_hook(__FILE__, 'alquipress_add_owner_role');

/**
 * Redirigir al propietario al panel tras el login
 * (evitar que vean el wp-admin)
 */
add_filter('login_redirect', 'alquipress_owner_login_redirect', 10, 3);

function alquipress_owner_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && in_array('propietario_alquipress', $user->roles)) {
        return home_url('/propietarios/mi-panel/');
    }
    return $redirect_to;
}

/**
 * Bloquear acceso al wp-admin para propietarios
 */
add_action('admin_init', function() {
    if (
        defined('DOING_AJAX') && DOING_AJAX
        || current_user_can('manage_options')
        || current_user_can('manage_woocommerce')
    ) return;

    if (in_array('propietario_alquipress', wp_get_current_user()->roles)) {
        wp_redirect(home_url('/propietarios/mi-panel/'));
        exit;
    }
});
```

---

## 5. Shortcode principal: `[alquipress_owner_dashboard]`

```php
<?php
/**
 * Shortcode del panel del propietario
 * Uso en página: [alquipress_owner_dashboard]
 */
add_shortcode('alquipress_owner_dashboard', 'alquipress_render_owner_dashboard');

function alquipress_render_owner_dashboard() {
    // 1. Verificar que hay sesión iniciada
    if (!is_user_logged_in()) {
        return '<div class="owner-notice">' .
               '<p>Debes <a href="' . wp_login_url(get_permalink()) . '">iniciar sesión</a> para ver tu panel.</p>' .
               '</div>';
    }

    $user_id = get_current_user_id();

    // 2. Encontrar el CPT propietario vinculado a este usuario
    $owner_posts = get_posts([
        'post_type'  => 'propietario',
        'meta_key'   => 'owner_wp_user',
        'meta_value' => $user_id,
        'numberposts' => 1,
    ]);

    if (empty($owner_posts)) {
        return '<div class="owner-notice">' .
               '<p>No tienes propiedades asignadas. Contacta con la agencia.</p>' .
               '</div>';
    }

    $owner_id   = $owner_posts[0]->ID;
    $owner_name = get_the_title($owner_id);

    // 3. Obtener propiedades del propietario
    $properties = get_field('owner_properties', $owner_id);
    if (empty($properties)) {
        return '<div class="owner-notice"><p>No tienes propiedades asignadas aún.</p></div>';
    }

    $commission = (float) get_field('owner_commission_rate', $owner_id) ?: 20;
    $product_ids = wp_list_pluck($properties, 'ID');

    // 4. Calcular datos del mes actual
    $stats = alquipress_calculate_owner_stats($product_ids, $commission);

    // 5. Renderizar panel completo
    ob_start();
    ?>
    <div class="alquipress-owner-dashboard" data-owner-id="<?php echo $owner_id; ?>">

        <!-- Cabecera -->
        <div class="owner-header">
            <div class="owner-greeting">
                <h1>Bienvenido, <?php echo esc_html($owner_name); ?></h1>
                <p><?php echo date_i18n('F Y'); ?></p>
            </div>
            <a href="<?php echo wp_logout_url(home_url()); ?>"
               class="owner-logout-btn">Cerrar sesión</a>
        </div>

        <!-- KPIs -->
        <?php echo alquipress_render_owner_kpis($stats); ?>

        <!-- Tabs de navegación -->
        <div class="owner-tabs">
            <button class="tab-btn active" data-tab="calendar">📅 Calendario</button>
            <button class="tab-btn" data-tab="settlements">💶 Liquidaciones</button>
            <button class="tab-btn" data-tab="documents">📁 Documentos</button>
        </div>

        <!-- Tab: Calendario -->
        <div class="tab-content active" id="tab-calendar">
            <?php echo alquipress_render_owner_calendar($product_ids, $owner_id); ?>
        </div>

        <!-- Tab: Liquidaciones -->
        <div class="tab-content" id="tab-settlements">
            <?php echo alquipress_render_owner_settlements($product_ids, $commission); ?>
        </div>

        <!-- Tab: Documentos -->
        <div class="tab-content" id="tab-documents">
            <?php echo alquipress_render_owner_documents($owner_id); ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
}
```

---

## 6. Módulo KPIs: "El Pulso del Mes"

```php
function alquipress_calculate_owner_stats(array $product_ids, float $commission): array {
    $now        = current_time('timestamp');
    $month_start = strtotime('first day of this month midnight', $now);
    $month_end   = strtotime('last day of this month 23:59:59', $now);

    // Pedidos del mes para estas propiedades
    $orders = wc_get_orders([
        'status'     => ['wc-completed', 'wc-in-progress', 'wc-deposit-received', 'wc-pending-checkin'],
        'date_after' => date('Y-m-d', $month_start),
        'date_before'=> date('Y-m-d', $month_end),
        'limit'      => -1,
    ]);

    $gross_income  = 0;
    $booked_nights = 0;
    $next_checkin  = null;

    foreach ($orders as $order) {
        $order_product_id = alquipress_get_product_id_from_order($order->get_id());

        if (!in_array($order_product_id, $product_ids)) continue;

        $booking    = alquipress_get_booking_from_order($order->get_id());
        if (!$booking) continue;

        $checkin_ts  = strtotime(get_post_meta($booking->ID, '_booking_start', true));
        $checkout_ts = strtotime(get_post_meta($booking->ID, '_booking_end', true));
        $nights      = max(1, ($checkout_ts - $checkin_ts) / DAY_IN_SECONDS);

        $gross_income  += $order->get_total();
        $booked_nights += $nights;

        // Próximo check-in (futuro más cercano)
        if ($checkin_ts > $now) {
            if (!$next_checkin || $checkin_ts < $next_checkin['timestamp']) {
                $next_checkin = [
                    'timestamp' => $checkin_ts,
                    'label'     => date_i18n('j \d\e F', $checkin_ts),
                    'guest'     => $order->get_formatted_billing_full_name(),
                ];
            }
        }
    }

    $commission_amount = $gross_income * ($commission / 100);
    $net_income        = $gross_income - $commission_amount;

    // Días del mes para calcular % ocupación
    $days_in_month     = (int) date('t');
    $occupancy_pct     = $days_in_month > 0
                         ? round(($booked_nights / $days_in_month) * 100)
                         : 0;

    return compact('gross_income', 'net_income', 'commission_amount',
                   'booked_nights', 'occupancy_pct', 'next_checkin');
}

function alquipress_render_owner_kpis(array $stats): string {
    ob_start();
    ?>
    <div class="owner-kpis">
        <div class="kpi-card kpi-income">
            <span class="kpi-icon">💶</span>
            <div class="kpi-data">
                <strong><?php echo wc_price($stats['net_income']); ?></strong>
                <span>Ingresos netos este mes</span>
                <small>Bruto: <?php echo wc_price($stats['gross_income']); ?>
                    · Comisión: <?php echo wc_price($stats['commission_amount']); ?></small>
            </div>
        </div>

        <div class="kpi-card kpi-occupancy">
            <span class="kpi-icon">📊</span>
            <div class="kpi-data">
                <strong><?php echo $stats['occupancy_pct']; ?>%</strong>
                <span>Ocupación este mes</span>
                <small><?php echo $stats['booked_nights']; ?> noches reservadas</small>
            </div>
        </div>

        <div class="kpi-card kpi-checkin">
            <span class="kpi-icon">🔑</span>
            <div class="kpi-data">
                <?php if ($stats['next_checkin']): ?>
                    <strong><?php echo esc_html($stats['next_checkin']['label']); ?></strong>
                    <span>Próxima entrada</span>
                    <small><?php echo esc_html($stats['next_checkin']['guest']); ?></small>
                <?php else: ?>
                    <strong>Sin reservas</strong>
                    <span>No hay entradas próximas</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
```

---

## 7. Módulo Calendario visual de ocupación

```php
function alquipress_render_owner_calendar(array $product_ids, int $owner_id): string {
    // Obtener reservas de los próximos 3 meses
    $bookings_data = [];

    foreach ($product_ids as $product_id) {
        $bookings = WC_Bookings_Controller::get_bookings_for_objects([$product_id], [
            'start_date' => strtotime('first day of this month'),
            'end_date'   => strtotime('+3 months'),
        ]);

        foreach ($bookings as $booking) {
            $status = $booking->get_status();
            if (!in_array($status, ['confirmed', 'paid', 'complete'])) continue;

            $bookings_data[] = [
                'start'    => date('Y-m-d', $booking->get_start()),
                'end'      => date('Y-m-d', $booking->get_end()),
                'price'    => $booking->get_cost(),
                'status'   => $status,
                'property' => get_the_title($product_id),
            ];
        }
    }

    // Bloqueos propios del propietario (uso personal)
    $personal_blocks = get_field('owner_personal_blocks', $owner_id) ?: [];

    ob_start();
    ?>
    <div class="owner-calendar-section">
        <div class="calendar-legend">
            <span class="legend-item occupied">🔴 Ocupado</span>
            <span class="legend-item free">🟢 Libre</span>
            <span class="legend-item blocked">⬜ Bloqueado (uso personal)</span>
        </div>

        <!-- Contenedor del calendario: se inicializa con JS -->
        <div id="owner-calendar"
             data-bookings="<?php echo esc_attr(json_encode($bookings_data)); ?>"
             data-blocks="<?php echo esc_attr(json_encode($personal_blocks)); ?>">
        </div>

        <!-- Formulario para bloquear fechas propias -->
        <div class="owner-block-dates">
            <h3>🔒 Bloquear fechas para uso personal</h3>
            <div class="block-form">
                <input type="text" id="block-start" placeholder="Fecha entrada" class="datepicker">
                <input type="text" id="block-end"   placeholder="Fecha salida"  class="datepicker">
                <button id="btn-block-dates"
                        data-nonce="<?php echo wp_create_nonce('owner_block_dates'); ?>"
                        data-owner-id="<?php echo $owner_id; ?>">
                    Bloquear fechas
                </button>
            </div>
            <p class="block-notice">
                ⚠️ Los bloqueos se confirman con la agencia. No garantizan disponibilidad inmediata.
            </p>
        </div>
    </div>

    <script>
    // Inicializar calendario con la librería Flatpickr o similar
    document.addEventListener('DOMContentLoaded', function() {
        const calEl    = document.getElementById('owner-calendar');
        const bookings = JSON.parse(calEl.dataset.bookings || '[]');
        const blocks   = JSON.parse(calEl.dataset.blocks   || '[]');

        // Renderizar calendar grid mensual con días coloreados
        // (implementar con librería de calendario o custom JS)
        AlquipressCalendar.init('owner-calendar', bookings, blocks);

        // Bloquear fechas vía AJAX
        document.getElementById('btn-block-dates').addEventListener('click', function() {
            const start = document.getElementById('block-start').value;
            const end   = document.getElementById('block-end').value;
            if (!start || !end) return alert('Selecciona las fechas.');

            fetch(alquipressOwner.ajaxUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action:   'alquipress_owner_block_dates',
                    nonce:    this.dataset.nonce,
                    owner_id: this.dataset.ownerId,
                    start,
                    end,
                }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) alert('Solicitud de bloqueo enviada. La agencia lo confirmará.');
                else              alert('Error: ' + data.data.message);
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
```

---

## 8. Módulo Liquidaciones mensuales

```php
function alquipress_render_owner_settlements(array $product_ids, float $commission): string {
    // Obtener pedidos completados de los últimos 12 meses agrupados por mes
    $orders = wc_get_orders([
        'status'      => ['wc-completed', 'wc-deposit-refunded'],
        'date_after'  => date('Y-m-d', strtotime('-12 months')),
        'limit'       => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ]);

    $settlements = []; // ['2025-07' => ['gross' => 0, 'nights' => 0, 'orders' => []]]

    foreach ($orders as $order) {
        $product_id = alquipress_get_product_id_from_order($order->get_id());
        if (!in_array($product_id, $product_ids)) continue;

        $month_key = date('Y-m', strtotime($order->get_date_created()));
        if (!isset($settlements[$month_key])) {
            $settlements[$month_key] = ['gross' => 0, 'orders' => [], 'label' => ''];
        }

        $settlements[$month_key]['gross']    += $order->get_total();
        $settlements[$month_key]['orders'][]  = $order;
        $settlements[$month_key]['label']     = date_i18n('F Y', strtotime($month_key . '-01'));
    }

    ob_start();
    ?>
    <div class="owner-settlements">
        <h2>Historial de Liquidaciones</h2>

        <?php if (empty($settlements)): ?>
            <p>No hay liquidaciones en los últimos 12 meses.</p>
        <?php else: ?>
        <table class="settlements-table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Ingresos Brutos</th>
                    <th>Comisión (<?php echo $commission; ?>%)</th>
                    <th>A percibir (Neto)</th>
                    <th>Reservas</th>
                    <th>Liquidación</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($settlements as $month_key => $data):
                $gross      = $data['gross'];
                $comm_amt   = $gross * ($commission / 100);
                $net        = $gross - $comm_amt;
                $num_orders = count($data['orders']);
                $pdf_url    = get_post_meta(
                    alquipress_get_settlement_post_id($month_key, $product_ids[0]),
                    '_settlement_pdf_url', true
                );
            ?>
            <tr>
                <td><strong><?php echo esc_html($data['label']); ?></strong></td>
                <td><?php echo wc_price($gross); ?></td>
                <td class="commission-col">- <?php echo wc_price($comm_amt); ?></td>
                <td class="net-col"><strong><?php echo wc_price($net); ?></strong></td>
                <td><?php echo $num_orders; ?></td>
                <td>
                    <?php if ($pdf_url): ?>
                        <a href="<?php echo esc_url($pdf_url); ?>"
                           target="_blank"
                           class="btn-download-pdf">📥 PDF</a>
                    <?php else: ?>
                        <span class="pending-pdf">Pendiente</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
```

---

## 9. Módulo Documentos

```php
function alquipress_render_owner_documents(int $owner_id): string {
    $contract_pdf    = get_field('owner_contract_pdf', $owner_id);
    $contract_expiry = get_field('owner_contract_expiry', $owner_id);
    $drive_url       = get_field('owner_drive_folder_url', $owner_id);

    ob_start();
    ?>
    <div class="owner-documents">
        <h2>Documentos</h2>

        <div class="doc-grid">

            <!-- Contrato de gestión -->
            <div class="doc-card">
                <span class="doc-icon">📄</span>
                <div class="doc-info">
                    <strong>Contrato de Gestión</strong>
                    <?php if ($contract_expiry): ?>
                    <small>Vence: <?php echo date_i18n('d/m/Y', strtotime($contract_expiry)); ?></small>
                    <?php endif; ?>
                </div>
                <?php if ($contract_pdf): ?>
                <a href="<?php echo esc_url($contract_pdf['url']); ?>"
                   target="_blank"
                   class="btn-doc-download">Ver PDF</a>
                <?php else: ?>
                <span class="doc-pending">Pendiente</span>
                <?php endif; ?>
            </div>

            <!-- Carpeta Google Drive -->
            <?php if ($drive_url): ?>
            <div class="doc-card">
                <span class="doc-icon">☁️</span>
                <div class="doc-info">
                    <strong>Documentación en la nube</strong>
                    <small>Facturas, escrituras y más</small>
                </div>
                <a href="<?php echo esc_url($drive_url); ?>"
                   target="_blank"
                   class="btn-doc-download">Abrir Drive</a>
            </div>
            <?php endif; ?>

            <!-- Licencia turística -->
            <?php
            // Obtener licencia de la primera propiedad asignada
            $properties = get_field('owner_properties', $owner_id);
            if ($properties):
                $first_product  = $properties[0];
                $licencia       = get_field('licencia_turistica', $first_product->ID);
                if ($licencia):
            ?>
            <div class="doc-card">
                <span class="doc-icon">🏛️</span>
                <div class="doc-info">
                    <strong>Licencia Turística</strong>
                    <small><?php echo esc_html($licencia); ?></small>
                </div>
            </div>
            <?php endif; endif; ?>

        </div>
    </div>
    <?php
    return ob_get_clean();
}
```

---

## 10. Seguridad: proteger la página del panel

```php
/**
 * Redirigir a login si un usuario sin sesión intenta acceder al panel
 * Añadir en functions.php o en el plugin
 */
add_action('template_redirect', 'alquipress_protect_owner_panel');

function alquipress_protect_owner_panel() {
    // Obtener el ID de la página del panel (configurable por opción)
    $panel_page_id = get_option('alquipress_owner_panel_page_id');
    if (!$panel_page_id || !is_page($panel_page_id)) return;

    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }

    // Verificar que el usuario es propietario o admin
    $user = wp_get_current_user();
    $allowed_roles = ['propietario_alquipress', 'administrator', 'shop_manager'];
    if (!array_intersect($allowed_roles, $user->roles)) {
        wp_redirect(home_url());
        exit;
    }
}
```

---

## 11. Encolado de assets del panel

```php
add_action('wp_enqueue_scripts', 'alquipress_owner_panel_assets');

function alquipress_owner_panel_assets() {
    $panel_page_id = get_option('alquipress_owner_panel_page_id');
    if (!is_page($panel_page_id)) return;

    // Flatpickr para datepickers
    wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', [], '4.6.13', true);

    // Estilos del panel
    wp_enqueue_style(
        'alquipress-owner-panel',
        plugin_dir_url(__FILE__) . 'assets/css/owner-panel.css',
        [],
        ALQUIPRESS_VERSION
    );

    // JS del panel (tabs, calendario, AJAX)
    wp_enqueue_script(
        'alquipress-owner-panel',
        plugin_dir_url(__FILE__) . 'assets/js/owner-panel.js',
        ['flatpickr'],
        ALQUIPRESS_VERSION,
        true
    );

    // Datos para el JS
    wp_localize_script('alquipress-owner-panel', 'alquipressOwner', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('alquipress_owner_nonce'),
        'locale'  => get_locale(),
    ]);
}
```

---

## 12. JavaScript: tabs del panel

```javascript
/**
 * ALQUIPRESS Owner Panel — Tabs
 * Archivo: /assets/js/owner-panel.js
 */
document.addEventListener('DOMContentLoaded', function () {

    // ── Tabs ─────────────────────────────────────────────────────────────────
    const tabBtns     = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetTab = this.dataset.tab;

            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            this.classList.add('active');
            document.getElementById('tab-' + targetTab).classList.add('active');
        });
    });

    // ── Flatpickr datepickers para bloqueo de fechas ─────────────────────────
    if (document.getElementById('block-start')) {
        flatpickr('#block-start', {
            locale: 'es',
            dateFormat: 'Y-m-d',
            minDate: 'today',
        });
        flatpickr('#block-end', {
            locale: 'es',
            dateFormat: 'Y-m-d',
            minDate: 'today',
        });
    }
});
```

---

## 13. Guía de estilos UI del panel

```css
/* ── Variables de color (coherentes con el CRM admin) ── */
:root {
    --owner-primary:    #1e3a5f;   /* Azul marino profundo */
    --owner-accent:     #e07b54;   /* Coral / teja */
    --owner-success:    #10b981;   /* Verde menta */
    --owner-warning:    #f59e0b;   /* Ámbar */
    --owner-bg:         #f8fafb;
    --owner-card:       #ffffff;
    --owner-text:       #1a2636;
    --owner-text-muted: #6b7280;
    --owner-border:     #e1e8ef;
}

/* ── KPIs ── */
.owner-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.kpi-card {
    background: var(--owner-card);
    border: 1px solid var(--owner-border);
    border-radius: 0.75rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.kpi-card strong {
    font-size: 1.75rem;
    color: var(--owner-primary);
    display: block;
}

/* ── Tabs ── */
.owner-tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid var(--owner-border);
    margin-bottom: 1.5rem;
}
.tab-btn {
    padding: 0.6rem 1.25rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--owner-text-muted);
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
}
.tab-btn.active {
    color: var(--owner-primary);
    border-bottom-color: var(--owner-primary);
    font-weight: 600;
}
.tab-content { display: none; }
.tab-content.active { display: block; }

/* ── Tabla liquidaciones ── */
.settlements-table {
    width: 100%;
    border-collapse: collapse;
}
.settlements-table th,
.settlements-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--owner-border);
    text-align: left;
}
.settlements-table th { background: var(--owner-bg); font-weight: 600; }
.commission-col { color: var(--owner-warning); }
.net-col        { color: var(--owner-success); }
.btn-download-pdf {
    background: var(--owner-primary);
    color: #fff;
    padding: 0.35rem 0.75rem;
    border-radius: 0.4rem;
    text-decoration: none;
    font-size: 0.85rem;
}

/* ── Documentos ── */
.doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem;
}
.doc-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: var(--owner-card);
    border: 1px solid var(--owner-border);
    border-radius: 0.75rem;
}
.doc-icon { font-size: 2rem; }
.doc-info { flex: 1; }
.doc-info strong { display: block; }
.doc-info small  { color: var(--owner-text-muted); font-size: 0.8rem; }
```

---

## 14. Checklist de implementación

```
FASE 1 — CPT y campos ACF
[ ] Registrar CPT "propietario" (público: false, solo wp-admin)
[ ] Importar grupos ACF: Contacto, Financiero, Propiedades/Contrato
[ ] Verificar que campo "owner_wp_user" vincula correctamente con usuarios WP
[ ] Test: crear propietario de prueba con datos completos

FASE 2 — Rol y acceso
[ ] Registrar rol "propietario_alquipress" con permisos mínimos
[ ] Hook de redirección post-login hacia el panel
[ ] Bloquear acceso al wp-admin para este rol
[ ] Crear usuario de prueba con el rol y verificar flujo de login

FASE 3 — Página del panel
[ ] Crear página WordPress: "Mi Panel" en /propietarios/mi-panel/
[ ] Guardar ID de página en opción: alquipress_owner_panel_page_id
[ ] Añadir shortcode [alquipress_owner_dashboard] al contenido
[ ] Aplicar template sin sidebar (page-owner-dashboard.php)
[ ] Hook de protección: redirigir a login si no autenticado
[ ] Test: acceder sin sesión → debe redirigir al login

FASE 4 — Módulo KPIs
[ ] Función alquipress_calculate_owner_stats()
[ ] Función alquipress_render_owner_kpis()
[ ] Verificar que el cálculo de comisión es correcto
[ ] Test: propietario con reservas en el mes actual

FASE 5 — Calendario
[ ] Función alquipress_render_owner_calendar()
[ ] Librería de calendario JS (Flatpickr o custom grid)
[ ] Colorear días: rojo (ocupado), verde (libre), gris (bloqueado)
[ ] AJAX handler para bloqueo de fechas personales
[ ] Test: verificar que solo aparecen reservas de sus propiedades

FASE 6 — Liquidaciones
[ ] Función alquipress_render_owner_settlements()
[ ] Agrupación correcta por mes
[ ] Cálculo de comisión y neto
[ ] Columna PDF (enlace cuando está disponible)
[ ] Test: 12 meses de historial con datos reales

FASE 7 — Documentos
[ ] Función alquipress_render_owner_documents()
[ ] Mostrar contrato PDF (si existe)
[ ] Enlace a carpeta Google Drive
[ ] Mostrar licencia turística
[ ] Test: propietario con y sin documentos

FASE 8 — Estilos y UX
[ ] Aplicar variables CSS coherentes con el CRM admin
[ ] Responsive: panel usable en móvil
[ ] Test de carga en dispositivo móvil real
[ ] Verificar que los datos financieros (IBAN, NIF) NO aparecen en el frontend
```

---

## 15. Evolución futura (v2)

- **Notificaciones push / email semanal:** Resumen automático de ingresos y ocupación enviado cada lunes.
- **Gráfico de ingresos anuales:** Chart.js con barras mensuales para ver la estacionalidad.
- **Chat directo con la agencia:** Widget de contacto dentro del panel (WhatsApp o formulario de contacto pre-rellenado).
- **Solicitud de obras/reparaciones:** Formulario para que el propietario reporte incidencias en la propiedad.
- **Comparativa con el mercado:** "Tu casa tuvo 85% de ocupación. La media de Dénia fue 72%." — dato de valor añadido.

---

> **Autor:** Arquitectura ALQUIPRESS  
> **Última revisión:** Febrero 2026  
> **Módulo anterior:** [MÓDULO 03 — Pipeline CRM Kanban]  
> **Siguiente módulo:** [MÓDULO 05 — Sincronización iCal (Airbnb / Booking.com)]
