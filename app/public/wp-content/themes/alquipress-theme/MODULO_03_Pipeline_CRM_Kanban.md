# MÓDULO 03 — Pipeline CRM: Kanban de Reservas

> **Proyecto:** ALQUIPRESS  
> **Stack:** WordPress · WooCommerce · WooCommerce Bookings · ACF Pro · SortableJS  
> **Objetivo:** Vista Kanban del ciclo completo de una reserva, con cambios de estado y automatizaciones en cada columna  
> **Referencia visual:** `/mnt/project/code.html` (diseño UI definido)

---

## 1. Concepto: El problema que resuelve este módulo

WooCommerce muestra "Pedidos". ALQUIPRESS necesita ver "Estancias en curso" con su ciclo operativo completo: desde que entra el dinero hasta que se devuelve la fianza. El Pipeline Kanban es la **torre de control** del administrador: un vistazo y sabe exactamente qué requiere acción hoy.

```
PAGO DEPÓSITO     PENDIENTE        EN CURSO       REVISIÓN        FIANZA
  RECIBIDO      → CHECK-IN     →  (Huésped     → SALIDA      →  DEVUELTA
                                   dentro)
  (Automático)   (Preparar       (Estancia      (Limpieza       (Ciclo
                  limpieza,       activa)         revisa          cerrado)
                  llaves, SES)                    daños)
```

---

## 2. Los 5 estados del pipeline

Cada estado es un **custom order status** de WooCommerce con su color, icono y lógica de automatización asociada.

| # | Slug | Etiqueta visible | Color | Quién lo activa |
|---|---|---|---|---|
| 1 | `wc-deposit-received` | 💚 Pago Depósito Recibido | Verde esmeralda | Automático (Stripe/Redsys) |
| 2 | `wc-pending-checkin` | 🔵 Pendiente Check-in | Azul | Manual o cron D-3 |
| 3 | `wc-in-progress` | 🟣 En Curso | Morado | Manual en check-in |
| 4 | `wc-departure-review` | 🟠 Revisión Salida | Naranja | Manual en check-out |
| 5 | `wc-deposit-refunded` | ⚫ Fianza Devuelta | Gris | Manual tras revisión |

---

## 3. Arquitectura técnica

```
WordPress Admin
└── Página personalizada: /wp-admin/admin.php?page=alquipress-pipeline
    ├── Panel Kanban (HTML/CSS/JS)
    │   ├── 5 columnas (una por estado)
    │   ├── Tarjetas arrastrables (SortableJS)
    │   └── Datos vía WP AJAX (nonce verificado)
    └── Meta Box en pedido individual
        ├── Checklist operativa
        ├── Badge huésped (VIP / Blacklist)
        └── Estado SES HOSPEDAJES
```

---

## 4. Registro de custom order statuses

```php
<?php
/**
 * ALQUIPRESS — Módulo 03: Registro de estados de pedido del pipeline
 * Archivo: /wp-content/plugins/alquipress-core/includes/pipeline-statuses.php
 */

/**
 * 1. Registrar los estados en WordPress
 */
add_action('init', 'alquipress_register_order_statuses');

function alquipress_register_order_statuses() {
    $statuses = [
        'wc-deposit-received' => [
            'label'                     => '💚 Pago Depósito Recibido',
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Pago Depósito Recibido <span class="count">(%s)</span>',
                'Pago Depósito Recibido <span class="count">(%s)</span>'
            ),
        ],
        'wc-pending-checkin' => [
            'label'                     => '🔵 Pendiente Check-in',
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Pendiente Check-in <span class="count">(%s)</span>',
                'Pendiente Check-in <span class="count">(%s)</span>'
            ),
        ],
        'wc-in-progress' => [
            'label'                     => '🟣 En Curso',
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'En Curso <span class="count">(%s)</span>',
                'En Curso <span class="count">(%s)</span>'
            ),
        ],
        'wc-departure-review' => [
            'label'                     => '🟠 Revisión Salida',
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Revisión Salida <span class="count">(%s)</span>',
                'Revisión Salida <span class="count">(%s)</span>'
            ),
        ],
        'wc-deposit-refunded' => [
            'label'                     => '⚫ Fianza Devuelta',
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Fianza Devuelta <span class="count">(%s)</span>',
                'Fianza Devuelta <span class="count">(%s)</span>'
            ),
        ],
    ];

    foreach ($statuses as $slug => $args) {
        register_post_status($slug, $args);
    }
}

/**
 * 2. Añadirlos al listado de estados de WooCommerce
 */
add_filter('wc_order_statuses', 'alquipress_add_order_statuses_to_wc');

function alquipress_add_order_statuses_to_wc($order_statuses) {
    $new_statuses = [
        'wc-deposit-received'  => '💚 Pago Depósito Recibido',
        'wc-pending-checkin'   => '🔵 Pendiente Check-in',
        'wc-in-progress'       => '🟣 En Curso',
        'wc-departure-review'  => '🟠 Revisión Salida',
        'wc-deposit-refunded'  => '⚫ Fianza Devuelta',
    ];

    // Insertar después del estado "processing" nativo
    $position = array_search('wc-processing', array_keys($order_statuses));
    $before    = array_slice($order_statuses, 0, $position + 1, true);
    $after     = array_slice($order_statuses, $position + 1, null, true);

    return $before + $new_statuses + $after;
}

/**
 * 3. Transición automática: cuando se completa el pago del depósito,
 *    pasar al primer estado del pipeline.
 */
add_action('woocommerce_order_status_processing', 'alquipress_set_initial_pipeline_status');

function alquipress_set_initial_pipeline_status($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Solo si es un pedido de depósito (primer cobro)
    $is_deposit = get_post_meta($order_id, '_wc_deposits_deposit_paid', true);
    if ($is_deposit) {
        $order->update_status(
            'deposit-received',
            'Depósito recibido. Reserva en pipeline.'
        );
    }
}
```

---

## 5. Página del Pipeline Kanban (backend)

### 5.1 Registro de la página de administración

```php
/**
 * Registrar la página del pipeline como submenú de WooCommerce
 */
add_action('admin_menu', 'alquipress_register_pipeline_page');

function alquipress_register_pipeline_page() {
    add_submenu_page(
        'woocommerce',
        'Pipeline de Reservas — ALQUIPRESS',
        '🏠 Pipeline',
        'manage_woocommerce',
        'alquipress-pipeline',
        'alquipress_render_pipeline_page'
    );
}
```

### 5.2 Función de renderizado

```php
function alquipress_render_pipeline_page() {
    // Verificar permisos
    if (!current_user_can('manage_woocommerce')) {
        wp_die('Sin permisos.');
    }

    // Obtener pedidos agrupados por estado del pipeline
    $pipeline_columns = [
        'deposit-received'  => ['label' => 'Pago Depósito Recibido', 'color' => '#10b981'],
        'pending-checkin'   => ['label' => 'Pendiente Check-in',     'color' => '#3b82f6'],
        'in-progress'       => ['label' => 'En Curso',               'color' => '#8b5cf6'],
        'departure-review'  => ['label' => 'Revisión Salida',        'color' => '#f97316'],
        'deposit-refunded'  => ['label' => 'Fianza Devuelta',        'color' => '#6b7280'],
    ];

    $orders_by_status = [];
    foreach ($pipeline_columns as $status => $config) {
        $orders_by_status[$status] = wc_get_orders([
            'status'  => 'wc-' . $status,
            'limit'   => 50,
            'orderby' => 'date',
            'order'   => 'DESC',
        ]);
    }

    // Pasar datos al JS via wp_localize_script
    wp_localize_script('alquipress-pipeline', 'alquipressData', [
        'nonce'   => wp_create_nonce('alquipress_pipeline_nonce'),
        'ajaxUrl' => admin_url('admin-ajax.php'),
    ]);

    // Incluir la vista HTML
    include plugin_dir_path(__FILE__) . 'views/pipeline-kanban.php';
}
```

### 5.3 Estructura HTML del Kanban (`views/pipeline-kanban.php`)

```html
<div class="wrap alquipress-pipeline">
    <h1 class="wp-heading-inline">Pipeline de Reservas</h1>
    <p class="description">Gestiona el flujo completo de cada estancia.</p>

    <!-- Filtros -->
    <div class="pipeline-filters">
        <input type="search" id="pipeline-search" placeholder="Buscar reserva, huésped, propiedad...">
        <select id="pipeline-month-filter">
            <option value="">Todos los meses</option>
            <!-- Opciones generadas por PHP -->
        </select>
    </div>

    <!-- Tablero Kanban -->
    <div class="kanban-board" id="kanban-board">
        <?php foreach ($pipeline_columns as $status => $config): ?>
        <div class="kanban-column"
             data-status="<?php echo esc_attr($status); ?>"
             style="--column-color: <?php echo esc_attr($config['color']); ?>">

            <!-- Cabecera columna -->
            <div class="column-header">
                <span class="column-dot"></span>
                <h3><?php echo esc_html($config['label']); ?></h3>
                <span class="column-count">
                    <?php echo count($orders_by_status[$status]); ?>
                </span>
            </div>

            <!-- Tarjetas -->
            <div class="kanban-cards sortable-list"
                 data-status="<?php echo esc_attr($status); ?>">

                <?php foreach ($orders_by_status[$status] as $order): ?>
                    <?php echo alquipress_render_kanban_card($order); ?>
                <?php endforeach; ?>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
```

### 5.4 Renderizado de cada tarjeta

```php
function alquipress_render_kanban_card(WC_Order $order) {
    $order_id    = $order->get_id();
    $customer    = $order->get_formatted_billing_full_name();
    $total       = $order->get_formatted_order_total();
    $booking     = alquipress_get_booking_from_order($order_id);
    $property    = alquipress_get_property_name($order_id);
    $checkin     = $booking ? get_post_meta($booking->ID, '_booking_start', true) : '';
    $checkout    = $booking ? get_post_meta($booking->ID, '_booking_end', true) : '';

    // Estado del huésped (ACF en user)
    $user_id      = $order->get_customer_id();
    $guest_status = get_field('guest_status', 'user_' . $user_id);

    // Badge de urgencia: check-in mañana
    $is_tomorrow  = $checkin && date('Y-m-d', strtotime($checkin)) === date('Y-m-d', strtotime('+1 day'));

    // Estado SES
    $ses_estado   = get_post_meta($order_id, '_ses_estado', true);

    ob_start();
    ?>
    <div class="kanban-card"
         data-order-id="<?php echo $order_id; ?>"
         draggable="true">

        <!-- Barra de color lateral según urgencia -->
        <?php if ($is_tomorrow): ?>
        <div class="urgency-bar urgent"></div>
        <?php endif; ?>

        <!-- Propiedad -->
        <div class="card-property">
            <span class="property-tag"><?php echo esc_html($property); ?></span>
            <?php if ($is_tomorrow): ?>
            <span class="badge-urgent">⚠ MAÑANA</span>
            <?php endif; ?>
            <button class="card-menu-btn" data-order-id="<?php echo $order_id; ?>">⋮</button>
        </div>

        <!-- Huésped -->
        <div class="card-guest">
            <div class="guest-avatar"><?php echo strtoupper(substr($customer, 0, 2)); ?></div>
            <div class="guest-info">
                <strong><?php echo esc_html($customer); ?></strong>
                <?php if ($guest_status === 'vip'): ?>
                <span class="badge-vip">⭐ VIP</span>
                <?php elseif ($guest_status === 'blacklist'): ?>
                <span class="badge-blacklist">🚫</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fechas -->
        <div class="card-dates">
            📅 <?php echo alquipress_format_date($checkin); ?>
            — <?php echo alquipress_format_date($checkout); ?>
        </div>

        <!-- Footer: importe + estado SES -->
        <div class="card-footer">
            <span class="card-total"><?php echo $total; ?></span>
            <span class="ses-badge ses-<?php echo esc_attr($ses_estado); ?>">
                SES: <?php echo esc_html(strtoupper($ses_estado ?: 'pendiente')); ?>
            </span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
```

---

## 6. JavaScript: Drag & Drop con SortableJS

```javascript
/**
 * ALQUIPRESS Pipeline — Drag & Drop
 * Archivo: /assets/js/pipeline-kanban.js
 */
document.addEventListener('DOMContentLoaded', function () {

    const lists = document.querySelectorAll('.sortable-list');

    lists.forEach(function (list) {
        new Sortable(list, {
            group: 'kanban',           // Permite arrastrar entre columnas
            animation: 150,
            ghostClass: 'card-ghost',
            dragClass: 'card-dragging',

            onEnd: function (evt) {
                const orderId   = evt.item.dataset.orderId;
                const newStatus = evt.to.dataset.status;
                const oldStatus = evt.from.dataset.status;

                if (newStatus === oldStatus) return; // Sin cambio real

                // Actualizar contadores visuales
                updateColumnCount(evt.from);
                updateColumnCount(evt.to);

                // Enviar cambio al servidor
                updateOrderStatus(orderId, newStatus);
            }
        });
    });

    /**
     * Actualizar el estado del pedido vía AJAX
     */
    function updateOrderStatus(orderId, newStatus) {
        const card = document.querySelector(`[data-order-id="${orderId}"]`);
        card.classList.add('card-loading');

        fetch(alquipressData.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action:   'alquipress_update_order_status',
                order_id: orderId,
                status:   newStatus,
                nonce:    alquipressData.nonce,
            }),
        })
        .then(res => res.json())
        .then(data => {
            card.classList.remove('card-loading');
            if (data.success) {
                showToast(`Reserva #${orderId} movida a "${data.data.label}"`, 'success');
            } else {
                showToast('Error al actualizar el estado. Recarga la página.', 'error');
                // Revertir la tarjeta visualmente si falla
            }
        })
        .catch(() => {
            card.classList.remove('card-loading');
            showToast('Error de conexión', 'error');
        });
    }

    /**
     * Actualizar el contador numérico de cada columna
     */
    function updateColumnCount(list) {
        const column = list.closest('.kanban-column');
        const count  = list.querySelectorAll('.kanban-card').length;
        column.querySelector('.column-count').textContent = count;
    }

    /**
     * Toast de notificación
     */
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `alquipress-toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('visible'), 10);
        setTimeout(() => { toast.classList.remove('visible'); setTimeout(() => toast.remove(), 300); }, 3000);
    }
});
```

---

## 7. Endpoint AJAX: actualizar estado + disparar automatizaciones

```php
/**
 * AJAX Handler: cambio de estado desde el Kanban
 */
add_action('wp_ajax_alquipress_update_order_status', 'alquipress_ajax_update_order_status');

function alquipress_ajax_update_order_status() {
    // Seguridad
    check_ajax_referer('alquipress_pipeline_nonce', 'nonce');
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Sin permisos.'], 403);
    }

    $order_id   = intval($_POST['order_id']);
    $new_status = sanitize_key($_POST['status']);
    $order      = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Pedido no encontrado.'], 404);
    }

    // Validar que el estado es uno de los del pipeline
    $valid_statuses = [
        'deposit-received', 'pending-checkin',
        'in-progress', 'departure-review', 'deposit-refunded'
    ];
    if (!in_array($new_status, $valid_statuses)) {
        wp_send_json_error(['message' => 'Estado no válido.'], 400);
    }

    // Actualizar estado
    $order->update_status($new_status, 'Estado cambiado desde Pipeline Kanban por ' . wp_get_current_user()->display_name);

    // Las automatizaciones se disparan desde los hooks de cambio de estado (ver §8)

    $labels = [
        'deposit-received'  => 'Pago Depósito Recibido',
        'pending-checkin'   => 'Pendiente Check-in',
        'in-progress'       => 'En Curso',
        'departure-review'  => 'Revisión Salida',
        'deposit-refunded'  => 'Fianza Devuelta',
    ];

    wp_send_json_success([
        'order_id' => $order_id,
        'status'   => $new_status,
        'label'    => $labels[$new_status],
    ]);
}
```

---

## 8. Automatizaciones por cambio de estado

Cada transición de columna dispara acciones automáticas.

```php
/**
 * Automatizaciones vinculadas a cada estado del pipeline
 */

// ── Estado: Pendiente Check-in ───────────────────────────────────────────────
add_action('woocommerce_order_status_pending-checkin', function($order_id) {
    $order = wc_get_order($order_id);

    // 1. Notificación interna al equipo de limpieza (email)
    wp_mail(
        get_option('alquipress_cleaning_email', get_option('admin_email')),
        'ALQUIPRESS — Preparar propiedad: Reserva #' . $order_id,
        alquipress_get_cleaning_email_body($order)
    );

    // 2. Enviar email al huésped con instrucciones de llegada (T-3 días)
    // (Se gestiona desde MailPoet con trigger por estado de pedido)

    // 3. Alerta si el parte SES no está enviado
    $ses_estado = get_post_meta($order_id, '_ses_estado', true);
    if ($ses_estado !== 'ok') {
        wp_mail(
            get_option('admin_email'),
            '⚠️ SES pendiente — Reserva #' . $order_id,
            'El parte de viajeros SES no está enviado. Check-in próximo.'
        );
    }
});

// ── Estado: En Curso ─────────────────────────────────────────────────────────
add_action('woocommerce_order_status_in-progress', function($order_id) {
    $order = wc_get_order($order_id);

    // 1. Registrar fecha y hora real del check-in
    update_post_meta($order_id, '_alquipress_checkin_real', current_time('mysql'));

    // 2. Email al huésped: "¿Todo bien?" (T+1 día — programar cron)
    wp_schedule_single_event(
        strtotime('+24 hours'),
        'alquipress_send_wellbeing_email',
        [$order_id]
    );
});

// ── Estado: Revisión Salida ──────────────────────────────────────────────────
add_action('woocommerce_order_status_departure-review', function($order_id) {
    $order = wc_get_order($order_id);

    // 1. Registrar fecha de check-out real
    update_post_meta($order_id, '_alquipress_checkout_real', current_time('mysql'));

    // 2. Notificación al equipo: revisar daños en la propiedad
    wp_mail(
        get_option('alquipress_cleaning_email', get_option('admin_email')),
        'ALQUIPRESS — Revisión de salida: Reserva #' . $order_id,
        'El huésped ha salido. Revisar el estado de la propiedad y confirmar si se devuelve la fianza.'
    );
});

// ── Estado: Fianza Devuelta ──────────────────────────────────────────────────
add_action('woocommerce_order_status_deposit-refunded', function($order_id) {
    $order = wc_get_order($order_id);

    // 1. Marcar ciclo como completado
    update_post_meta($order_id, '_alquipress_cycle_completed', current_time('mysql'));

    // 2. Email al huésped: gracias + solicitud de reseña
    // (Gestionado desde MailPoet)

    // 3. Nota interna en el pedido
    $order->add_order_note('Ciclo completo. Fianza devuelta. Reserva archivada.');
});
```

---

## 9. Meta Box en la ficha del pedido

Dentro de cada pedido individual, añadimos una meta box lateral con la **checklist operativa** y el perfil rápido del huésped.

```php
add_action('add_meta_boxes', function() {
    add_meta_box(
        'alquipress_pipeline_metabox',
        '🏠 ALQUIPRESS — Control Operativo',
        'alquipress_render_pipeline_metabox',
        'shop_order',
        'side',
        'high'
    );
});

function alquipress_render_pipeline_metabox($post) {
    $order_id   = $post->ID;
    $order      = wc_get_order($order_id);
    $user_id    = $order->get_customer_id();
    $guest_status = get_field('guest_status', 'user_' . $user_id);
    $ses_estado = get_post_meta($order_id, '_ses_estado', true);

    // Checklist operativa
    $checklist = [
        'ses_enviado'          => ['label' => '📋 Parte SES enviado',         'meta' => '_alquipress_ses_sent'],
        'limpieza_programada'  => ['label' => '🧹 Limpieza programada',       'meta' => '_alquipress_cleaning_scheduled'],
        'llaves_entregadas'    => ['label' => '🔑 Llaves entregadas',         'meta' => '_alquipress_keys_delivered'],
        'propiedad_revisada'   => ['label' => '✅ Propiedad revisada (salida)','meta' => '_alquipress_property_reviewed'],
        'fianza_procesada'     => ['label' => '💶 Fianza procesada',          'meta' => '_alquipress_deposit_processed'],
    ];

    wp_nonce_field('alquipress_pipeline_metabox', 'alquipress_pipeline_nonce');
    ?>
    <div class="alquipress-metabox">

        <!-- Badge de huésped -->
        <div class="guest-status-badge guest-<?php echo esc_attr($guest_status); ?>">
            <?php
            $badges = ['vip' => '⭐ Huésped VIP', 'blacklist' => '🚫 Lista Negra', 'standard' => '👤 Estándar'];
            echo $badges[$guest_status] ?? $badges['standard'];
            ?>
        </div>

        <!-- Estado SES -->
        <div class="ses-status">
            <strong>SES HOSPEDAJES:</strong>
            <span class="ses-<?php echo esc_attr($ses_estado); ?>">
                <?php echo strtoupper($ses_estado ?: 'pendiente'); ?>
            </span>
        </div>

        <hr>

        <!-- Checklist operativa -->
        <div class="operational-checklist">
            <?php foreach ($checklist as $key => $item):
                $checked = get_post_meta($order_id, $item['meta'], true);
            ?>
            <label class="checklist-item">
                <input type="checkbox"
                       name="alquipress_checklist[<?php echo esc_attr($key); ?>]"
                       <?php checked($checked, '1'); ?>
                       value="1">
                <?php echo esc_html($item['label']); ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

// Guardar la checklist al salvar el pedido
add_action('save_post_shop_order', function($post_id) {
    if (!isset($_POST['alquipress_pipeline_nonce'])) return;
    if (!wp_verify_nonce($_POST['alquipress_pipeline_nonce'], 'alquipress_pipeline_metabox')) return;

    $checklist_fields = [
        'ses_enviado'          => '_alquipress_ses_sent',
        'limpieza_programada'  => '_alquipress_cleaning_scheduled',
        'llaves_entregadas'    => '_alquipress_keys_delivered',
        'propiedad_revisada'   => '_alquipress_property_reviewed',
        'fianza_procesada'     => '_alquipress_deposit_processed',
    ];

    foreach ($checklist_fields as $key => $meta_key) {
        $value = isset($_POST['alquipress_checklist'][$key]) ? '1' : '0';
        update_post_meta($post_id, $meta_key, $value);
    }
});
```

---

## 10. Columnas personalizadas en el listado de pedidos de WooCommerce

```php
/**
 * Añadir columnas útiles al listado de pedidos: semáforo, propiedad, fechas
 */
add_filter('manage_edit-shop_order_columns', function($columns) {
    $new_columns = [];
    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;
        if ($key === 'order_status') {
            $new_columns['alquipress_property']  = '🏠 Propiedad';
            $new_columns['alquipress_dates']      = '📅 Fechas';
            $new_columns['alquipress_semaphore']  = '🚦 Estado';
        }
    }
    return $new_columns;
});

add_action('manage_shop_order_posts_custom_column', function($column, $post_id) {
    $order = wc_get_order($post_id);

    switch ($column) {
        case 'alquipress_property':
            echo esc_html(alquipress_get_property_name($post_id));
            break;

        case 'alquipress_dates':
            $booking  = alquipress_get_booking_from_order($post_id);
            $checkin  = $booking ? get_post_meta($booking->ID, '_booking_start', true) : '—';
            $checkout = $booking ? get_post_meta($booking->ID, '_booking_end', true) : '—';
            echo '<small>' . esc_html(alquipress_format_date($checkin)) . ' → ' . esc_html(alquipress_format_date($checkout)) . '</small>';
            break;

        case 'alquipress_semaphore':
            $second_paid  = get_post_meta($post_id, '_wc_deposits_second_payment_paid', true);
            $fianza_ok    = get_post_meta($post_id, '_alquipress_deposit_processed', true);

            if ($fianza_ok)       echo '<span style="color:#6b7280">⚫ Cerrado</span>';
            elseif ($second_paid) echo '<span style="color:#10b981">🟢 Pagado</span>';
            else                  echo '<span style="color:#ef4444">🔴 Falta 2º pago</span>';
            break;
    }
}, 10, 2);
```

---

## 11. Encolado de assets

```php
add_action('admin_enqueue_scripts', function($hook) {
    // Solo en la página del pipeline
    if (strpos($hook, 'alquipress-pipeline') === false) return;

    // SortableJS (CDN o local)
    wp_enqueue_script(
        'sortablejs',
        'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
        [],
        '1.15.0',
        true
    );

    wp_enqueue_script(
        'alquipress-pipeline',
        plugin_dir_url(__FILE__) . 'assets/js/pipeline-kanban.js',
        ['sortablejs'],
        ALQUIPRESS_VERSION,
        true
    );

    wp_enqueue_style(
        'alquipress-pipeline',
        plugin_dir_url(__FILE__) . 'assets/css/pipeline-kanban.css',
        [],
        ALQUIPRESS_VERSION
    );
});
```

---

## 12. Checklist de implementación

```
FASE 1 — Registro de estados
[ ] Registrar los 5 custom order statuses en WordPress
[ ] Añadirlos al filtro wc_order_statuses
[ ] Verificar que aparecen en el dropdown de WooCommerce → Editar pedido
[ ] Añadir hook de transición automática desde "processing" a "deposit-received"
[ ] Test: crear pedido de prueba y verificar transición

FASE 2 — Página del Pipeline
[ ] Registrar página de administración como submenú de WooCommerce
[ ] Construir la vista HTML del Kanban (5 columnas)
[ ] Función de renderizado de tarjetas (alquipress_render_kanban_card)
[ ] Enqueue de SortableJS y CSS del Kanban
[ ] Test: verificar que se cargan las reservas en las columnas correctas

FASE 3 — Interactividad JS
[ ] Implementar Drag & Drop con SortableJS entre columnas
[ ] AJAX handler para persistir el cambio de estado en el servidor
[ ] Nonce verification en el endpoint AJAX
[ ] Actualización de contadores por columna al mover tarjetas
[ ] Toast de notificación en éxito/error
[ ] Test: arrastrar tarjeta entre columnas y verificar persistencia

FASE 4 — Automatizaciones
[ ] Hook en "pending-checkin": email a limpieza + alerta SES
[ ] Hook en "in-progress": registrar check-in real + programar email T+24h
[ ] Hook en "departure-review": email revisión al equipo
[ ] Hook en "deposit-refunded": cerrar ciclo + nota en pedido
[ ] Test: recorrer el pipeline completo con una reserva de prueba

FASE 5 — Meta Box y checklist
[ ] Añadir meta box "Control Operativo" en la ficha del pedido
[ ] Renderizar badge de huésped (VIP / Blacklist / Estándar)
[ ] Checklist operativa con guardado en order meta
[ ] Mostrar estado SES en la meta box
[ ] Test: marcar checklist y verificar persistencia

FASE 6 — Listado de pedidos
[ ] Añadir columnas: Propiedad, Fechas, Semáforo de pago
[ ] Verificar que no rompe la vista estándar de WooCommerce
[ ] Test: verificar semáforo con distintos estados de pago
```

---

## 13. Evolución futura (v2)

- **Filtro por propiedad:** Ver solo las reservas de "Villa Sol" en el Kanban.
- **Vista de lista alternativa:** Toggle entre Kanban y tabla para reservas pasadas.
- **Notificaciones en tiempo real:** WebSockets o polling cada 60s para actualizar el Kanban sin recargar.
- **App móvil / PWA:** El pipeline adaptado a pantalla pequeña para que el equipo de campo actualice estados desde el teléfono.
- **Integración con calendario maestro:** Vista Gantt de todas las propiedades con las estancias en curso (Módulo a definir).

---

> **Autor:** Arquitectura ALQUIPRESS  
> **Última revisión:** Febrero 2026  
> **Módulo anterior:** [MÓDULO 02 — Pasarela de Pagos Stripe + Redsys]  
> **Siguiente módulo:** [MÓDULO 04 — Panel del Propietario]
