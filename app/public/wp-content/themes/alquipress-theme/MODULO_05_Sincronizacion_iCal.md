# MÓDULO 05 — Sincronización iCal (Airbnb / Booking.com / VRBO)

> **Proyecto:** ALQUIPRESS  
> **Stack:** WordPress · WooCommerce Bookings · Server Cron · PHP  
> **Objetivo:** Sincronización bidireccional de disponibilidad entre la web y canales externos, eliminando el riesgo de overbooking  
> **Canales:** Airbnb · Booking.com · VRBO · Google Calendar (como puente maestro)

---

## 1. Concepto: El problema del overbooking

Sin sincronización, una reserva hecha en Airbnb a las 14:05 puede coexistir con una reserva directa en tu web a las 14:07 para las mismas fechas y la misma propiedad. El resultado: overbooking, cliente furioso, reputación dañada.

La solución es mantener un **calendario maestro** que se actualice en tiempo real desde todos los canales y bloquee fechas en todos ellos al mismo tiempo.

```
ALQUIPRESS (web propia)
        │
        ├──► Exporta .ics → Airbnb lo importa  (bloquea fechas en Airbnb)
        ├──► Exporta .ics → Booking.com         (bloquea fechas en Booking)
        ├──► Exporta .ics → VRBO                (bloquea fechas en VRBO)
        │
        ◄──── Importa .ics de Airbnb            (bloquea fechas en la web)
        ◄──── Importa .ics de Booking.com       (bloquea fechas en la web)
        ◄──── Importa .ics de VRBO              (bloquea fechas en la web)
```

El cron del servidor actualiza la importación cada **15 minutos** para minimizar la ventana de riesgo.

---

## 2. Estrategia: iCal directo vs Google Calendar como puente

### Opción A — iCal directo (recomendada para ALQUIPRESS)

Cada propiedad expone su propia URL `.ics` y consume directamente las URLs `.ics` de los canales externos. Sin intermediarios, sin dependencias de terceros.

**Ventajas:** control total, sin costes extra, sin punto único de fallo externo.  
**Requisito:** cron del servidor fiable (ya cubierto en el Módulo 02).

### Opción B — Google Calendar como puente

`Airbnb → Google Calendar ← Booking.com` y la web sincroniza con Google Calendar.

**Ventajas:** interfaz visual para el equipo, fácil de depurar manualmente.  
**Inconvenientes:** dependencia de Google, latencia añadida (2 saltos en lugar de 1), límites de quota de la API de Google.

> **Decisión para ALQUIPRESS:** Opción A (iCal directo) como base. Google Calendar como capa de visualización opcional para el equipo, no como motor de sincronización.

---

## 3. Cómo funciona iCal en WooCommerce Bookings

WooCommerce Bookings incluye soporte nativo para iCal. Cada producto (propiedad) puede:

- **Exportar** su disponibilidad como feed `.ics` (URL pública)
- **Importar** feeds `.ics` externos que crean bloqueos automáticos

### 3.1 Activar la exportación iCal en WooCommerce Bookings

Ruta: **WooCommerce → Bookings → Sincronización de Calendar**

```
☑ Activar sincronización de calendario
Feed URL por propiedad:
  https://tuweb.com/?wc-bookings-ics=PRODUCT_ID&key=SECRET_KEY
```

Cada canal externo (Airbnb, Booking.com) consumirá esta URL para saber cuándo está ocupada la propiedad.

### 3.2 URL de exportación personalizada (más segura)

WooCommerce Bookings genera URLs predecibles. Para mayor seguridad, generamos una URL con clave única por propiedad:

```php
<?php
/**
 * ALQUIPRESS — Módulo 05: URL de exportación iCal segura por propiedad
 */

/**
 * Generar o recuperar la clave secreta de una propiedad para el feed iCal
 */
function alquipress_get_ical_key(int $product_id): string {
    $key = get_post_meta($product_id, '_alquipress_ical_key', true);
    if (!$key) {
        $key = wp_generate_password(32, false);
        update_post_meta($product_id, '_alquipress_ical_key', $key);
    }
    return $key;
}

/**
 * Devolver la URL de exportación pública para una propiedad
 */
function alquipress_get_export_ical_url(int $product_id): string {
    $key = alquipress_get_ical_key($product_id);
    return add_query_arg([
        'alquipress_ical' => 'export',
        'product_id'      => $product_id,
        'key'             => $key,
    ], home_url('/'));
}

/**
 * Endpoint de exportación: responde con el archivo .ics
 */
add_action('init', 'alquipress_ical_export_endpoint');

function alquipress_ical_export_endpoint() {
    if (!isset($_GET['alquipress_ical']) || $_GET['alquipress_ical'] !== 'export') return;

    $product_id = intval($_GET['product_id'] ?? 0);
    $key        = sanitize_text_field($_GET['key'] ?? '');

    // Validar clave
    $stored_key = get_post_meta($product_id, '_alquipress_ical_key', true);
    if (!$product_id || !$key || $key !== $stored_key) {
        status_header(403);
        die('Acceso no autorizado.');
    }

    $property_name = get_the_title($product_id);

    // Obtener bookings confirmados de los próximos 12 meses
    $bookings = WC_Bookings_Controller::get_bookings_for_objects([$product_id], [
        'start_date' => time(),
        'end_date'   => strtotime('+12 months'),
        'status'     => ['confirmed', 'paid', 'complete'],
    ]);

    // Generar iCal
    $ical  = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//ALQUIPRESS//" . sanitize_title($property_name) . "//ES\r\n";
    $ical .= "CALSCALE:GREGORIAN\r\n";
    $ical .= "METHOD:PUBLISH\r\n";
    $ical .= "X-WR-CALNAME:" . esc_html($property_name) . "\r\n";
    $ical .= "X-WR-TIMEZONE:Europe/Madrid\r\n";

    foreach ($bookings as $booking) {
        $uid       = 'booking-' . $booking->get_id() . '@alquipress';
        $start     = date('Ymd', $booking->get_start());
        $end       = date('Ymd', $booking->get_end());
        $created   = date('Ymd\THis\Z', strtotime($booking->get_date_created()));
        $summary   = 'Reservado — ' . $property_name;

        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:{$uid}\r\n";
        $ical .= "DTSTART;VALUE=DATE:{$start}\r\n";
        $ical .= "DTEND;VALUE=DATE:{$end}\r\n";
        $ical .= "DTSTAMP:{$created}\r\n";
        $ical .= "SUMMARY:{$summary}\r\n";
        $ical .= "STATUS:CONFIRMED\r\n";
        $ical .= "TRANSP:OPAQUE\r\n";
        $ical .= "END:VEVENT\r\n";
    }

    // Añadir bloqueos manuales del propietario (uso personal)
    $manual_blocks = get_post_meta($product_id, '_alquipress_manual_blocks', true) ?: [];
    foreach ($manual_blocks as $idx => $block) {
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:block-{$product_id}-{$idx}@alquipress\r\n";
        $ical .= "DTSTART;VALUE=DATE:" . date('Ymd', strtotime($block['start'])) . "\r\n";
        $ical .= "DTEND;VALUE=DATE:"   . date('Ymd', strtotime($block['end']))   . "\r\n";
        $ical .= "SUMMARY:Bloqueado — Uso personal\r\n";
        $ical .= "STATUS:CONFIRMED\r\n";
        $ical .= "END:VEVENT\r\n";
    }

    $ical .= "END:VCALENDAR\r\n";

    // Enviar respuesta
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitize_title($property_name) . '.ics"');
    header('Cache-Control: no-cache, must-revalidate');
    echo $ical;
    exit;
}
```

---

## 4. Importación de calendarios externos (bloqueos automáticos)

### 4.1 Tabla de feeds externos por propiedad

Cada propiedad tiene guardados sus feeds de importación como meta:

```php
/**
 * Estructura de feeds externos almacenados en post_meta:
 * _alquipress_ical_feeds = [
 *   [
 *     'channel' => 'airbnb',
 *     'label'   => 'Airbnb — Villa Sol',
 *     'url'     => 'https://www.airbnb.com/calendar/ical/XXXXXXX.ics?s=TOKEN',
 *     'active'  => true,
 *     'last_sync' => '2025-07-10 14:30:00',
 *     'last_status' => 'ok', // ok | error | empty
 *   ],
 *   [
 *     'channel' => 'booking',
 *     'url'     => 'https://ical.booking.com/v1/export?t=TOKEN',
 *     ...
 *   ],
 * ]
 */
```

### 4.2 Motor de importación y creación de bloqueos

```php
<?php
/**
 * Importar un feed iCal externo y crear bloqueos en WooCommerce Bookings
 */
function alquipress_import_ical_feed(int $product_id, array $feed): array {
    $result = ['status' => 'ok', 'blocked' => 0, 'skipped' => 0, 'error' => ''];

    // Descargar el archivo .ics
    $response = wp_remote_get($feed['url'], [
        'timeout'    => 15,
        'user-agent' => 'ALQUIPRESS/1.0 (iCal sync)',
    ]);

    if (is_wp_error($response)) {
        $result['status'] = 'error';
        $result['error']  = $response->get_error_message();
        return $result;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        $result['status'] = 'error';
        $result['error']  = 'HTTP ' . $status_code;
        return $result;
    }

    $ical_content = wp_remote_retrieve_body($response);
    if (empty($ical_content)) {
        $result['status'] = 'empty';
        return $result;
    }

    // Parsear eventos VEVENT del iCal
    $events = alquipress_parse_ical_events($ical_content);

    foreach ($events as $event) {
        $start_ts = $event['start'];
        $end_ts   = $event['end'];
        $uid      = $event['uid'];

        // Evitar duplicados: buscar si ya existe un bloqueo con este UID
        $existing = get_posts([
            'post_type'  => 'wc_booking',
            'meta_query' => [
                ['key' => '_ical_uid',        'value' => $uid],
                ['key' => '_product_id',      'value' => $product_id],
                ['key' => '_ical_channel',    'value' => $feed['channel']],
            ],
            'numberposts' => 1,
        ]);

        if (!empty($existing)) {
            $result['skipped']++;
            continue;
        }

        // Verificar que las fechas son futuras (no importar pasado)
        if ($end_ts < time()) {
            $result['skipped']++;
            continue;
        }

        // Crear bloqueo en WooCommerce Bookings
        $booking_data = [
            'product_id'  => $product_id,
            'status'      => 'confirmed',
            'start_date'  => $start_ts,
            'end_date'    => $end_ts,
            'all_day'     => true,
        ];

        $booking = new WC_Booking($booking_data);
        $booking_id = $booking->create();

        if ($booking_id) {
            // Guardar metadatos del origen externo
            update_post_meta($booking_id, '_ical_uid',     $uid);
            update_post_meta($booking_id, '_ical_channel', $feed['channel']);
            update_post_meta($booking_id, '_ical_source',  'external_import');
            update_post_meta($booking_id, '_customer_id',  0); // Sin cliente interno

            $result['blocked']++;
        }
    }

    return $result;
}

/**
 * Parser iCal básico: extrae eventos VEVENT del contenido .ics
 */
function alquipress_parse_ical_events(string $ical_content): array {
    $events   = [];
    $lines    = explode("\n", str_replace(["\r\n", "\r"], "\n", $ical_content));
    $in_event = false;
    $current  = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === 'BEGIN:VEVENT') {
            $in_event = true;
            $current  = [];
            continue;
        }

        if ($line === 'END:VEVENT') {
            $in_event = false;
            if (!empty($current['start']) && !empty($current['end'])) {
                $events[] = $current;
            }
            continue;
        }

        if (!$in_event) continue;

        // Parsear propiedades clave
        if (strpos($line, 'DTSTART') === 0) {
            $current['start'] = alquipress_parse_ical_date($line);
        } elseif (strpos($line, 'DTEND') === 0) {
            $current['end'] = alquipress_parse_ical_date($line);
        } elseif (strpos($line, 'UID:') === 0) {
            $current['uid'] = substr($line, 4);
        } elseif (strpos($line, 'SUMMARY:') === 0) {
            $current['summary'] = substr($line, 8);
        }
    }

    return $events;
}

/**
 * Convertir fecha iCal a timestamp Unix
 * Soporta: DATE (20250710), DATETIME (20250710T150000Z), DATETIME con zona
 */
function alquipress_parse_ical_date(string $line): int {
    // Extraer solo el valor (después de los dos puntos, ignorando parámetros)
    $value = preg_replace('/^[^:]+:/', '', $line);
    $value = trim($value);

    if (strlen($value) === 8) {
        // Formato DATE: YYYYMMDD
        return mktime(0, 0, 0,
            (int) substr($value, 4, 2),
            (int) substr($value, 6, 2),
            (int) substr($value, 0, 4)
        );
    }

    // Formato DATETIME: YYYYMMDDTHHMMSSZ o con offset
    $dt = DateTime::createFromFormat('Ymd\THis\Z', $value, new DateTimeZone('UTC'));
    if ($dt) {
        $dt->setTimezone(new DateTimeZone('Europe/Madrid'));
        return $dt->getTimestamp();
    }

    // Intentar parseo genérico como último recurso
    return strtotime($value) ?: 0;
}
```

---

## 5. Cron de sincronización: el motor cada 15 minutos

### 5.1 Registrar el intervalo y el evento cron

```php
/**
 * Registrar intervalo de 15 minutos para WP-Cron
 */
add_filter('cron_schedules', 'alquipress_add_cron_intervals');

function alquipress_add_cron_intervals($schedules) {
    $schedules['every_15_minutes'] = [
        'interval' => 900,
        'display'  => 'Cada 15 minutos',
    ];
    return $schedules;
}

/**
 * Registrar el evento cron en la activación del plugin
 */
register_activation_hook(__FILE__, 'alquipress_schedule_ical_sync');

function alquipress_schedule_ical_sync() {
    if (!wp_next_scheduled('alquipress_ical_sync_all')) {
        wp_schedule_event(time(), 'every_15_minutes', 'alquipress_ical_sync_all');
    }
}

/**
 * Limpiar el cron al desactivar el plugin
 */
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('alquipress_ical_sync_all');
});

/**
 * El trabajo del cron: iterar todas las propiedades y sus feeds
 */
add_action('alquipress_ical_sync_all', 'alquipress_run_ical_sync');

function alquipress_run_ical_sync() {
    // Obtener todas las propiedades activas
    $products = get_posts([
        'post_type'   => 'product',
        'numberposts' => -1,
        'meta_query'  => [
            ['key' => '_alquipress_ical_feeds', 'compare' => 'EXISTS'],
        ],
    ]);

    $log = [];

    foreach ($products as $product) {
        $feeds = get_post_meta($product->ID, '_alquipress_ical_feeds', true) ?: [];

        foreach ($feeds as $idx => $feed) {
            if (empty($feed['active']) || empty($feed['url'])) continue;

            $result = alquipress_import_ical_feed($product->ID, $feed);

            // Actualizar estado del feed
            $feeds[$idx]['last_sync']   = current_time('mysql');
            $feeds[$idx]['last_status'] = $result['status'];

            $log[] = sprintf(
                '[%s] %s — %s: %s (bloqueados: %d, omitidos: %d)',
                current_time('mysql'),
                get_the_title($product->ID),
                $feed['channel'],
                $result['status'],
                $result['blocked'],
                $result['skipped']
            );

            if ($result['status'] === 'error') {
                // Notificar al admin si un feed falla 3 veces seguidas
                alquipress_check_feed_failures($product->ID, $idx, $feed, $result);
            }
        }

        update_post_meta($product->ID, '_alquipress_ical_feeds', $feeds);
    }

    // Guardar log de la última sincronización
    update_option('alquipress_ical_last_sync_log', implode("\n", $log));
    update_option('alquipress_ical_last_sync_time', current_time('mysql'));
}

/**
 * Alertar al admin si un feed falla repetidamente
 */
function alquipress_check_feed_failures(int $product_id, int $feed_idx, array $feed, array $result) {
    $fail_key   = "_ical_fail_count_{$feed_idx}";
    $fail_count = (int) get_post_meta($product_id, $fail_key, true);
    $fail_count++;
    update_post_meta($product_id, $fail_key, $fail_count);

    if ($fail_count >= 3) {
        wp_mail(
            get_option('admin_email'),
            '⚠️ ALQUIPRESS: Fallo en sincronización iCal — ' . get_the_title($product_id),
            sprintf(
                "El feed iCal de \"%s\" (%s) ha fallado %d veces consecutivas.\n\nURL: %s\nError: %s\n\nRevisa la configuración del canal externo.",
                get_the_title($product_id),
                $feed['channel'],
                $fail_count,
                $feed['url'],
                $result['error']
            )
        );

        // Reset contador tras notificar
        update_post_meta($product_id, $fail_key, 0);
    }
}
```

### 5.2 Cron real del servidor (obligatorio)

Con `DISABLE_WP_CRON = true` en `wp-config.php`, añadir en cPanel / Plesk:

```bash
# Sincronización iCal cada 15 minutos
*/15 * * * * cd /var/www/html && wp cron event run alquipress_ical_sync_all --allow-root > /dev/null 2>&1
```

---

## 6. Gestión de feeds en el wp-admin (Meta Box en la ficha del producto)

```php
/**
 * Meta box para gestionar feeds iCal en la ficha de cada propiedad
 */
add_action('add_meta_boxes', function() {
    add_meta_box(
        'alquipress_ical_feeds',
        '🔄 Sincronización iCal — Canales externos',
        'alquipress_render_ical_meta_box',
        'product',
        'normal',
        'high'
    );
});

function alquipress_render_ical_meta_box($post) {
    $product_id  = $post->ID;
    $feeds       = get_post_meta($product_id, '_alquipress_ical_feeds', true) ?: [];
    $export_url  = alquipress_get_export_ical_url($product_id);
    $last_sync   = get_option('alquipress_ical_last_sync_time', 'Nunca');

    wp_nonce_field('alquipress_ical_nonce', 'alquipress_ical_nonce');
    ?>
    <div class="alquipress-ical-box">

        <!-- URL de exportación (para dar a Airbnb / Booking) -->
        <div class="ical-export-section">
            <h4>📤 URL de exportación (dar a los canales externos)</h4>
            <div class="ical-url-row">
                <input type="text"
                       value="<?php echo esc_url($export_url); ?>"
                       readonly
                       class="large-text"
                       onclick="this.select()">
                <button type="button"
                        onclick="navigator.clipboard.writeText('<?php echo esc_url($export_url); ?>')"
                        class="button">Copiar</button>
            </div>
            <p class="description">
                Pega esta URL en Airbnb → Calendario → Exportar / Sincronizar con otro calendario.
                Haz lo mismo en Booking.com → Sync → Añadir calendario externo.
            </p>
        </div>

        <hr>

        <!-- Feeds de importación -->
        <div class="ical-import-section">
            <h4>📥 Feeds de importación (calendarios externos → esta propiedad)</h4>
            <p class="description">
                Última sincronización: <strong><?php echo esc_html($last_sync); ?></strong>
                · Intervalo: cada 15 minutos
            </p>

            <table class="widefat ical-feeds-table" id="ical-feeds-table">
                <thead>
                    <tr>
                        <th>Canal</th>
                        <th>Nombre / Descripción</th>
                        <th>URL .ics</th>
                        <th>Último estado</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($feeds as $idx => $feed): ?>
                <tr data-idx="<?php echo $idx; ?>">
                    <td>
                        <select name="alquipress_ical_feeds[<?php echo $idx; ?>][channel]">
                            <option value="airbnb"  <?php selected($feed['channel'], 'airbnb'); ?>>Airbnb</option>
                            <option value="booking" <?php selected($feed['channel'], 'booking'); ?>>Booking.com</option>
                            <option value="vrbo"    <?php selected($feed['channel'], 'vrbo'); ?>>VRBO</option>
                            <option value="other"   <?php selected($feed['channel'], 'other'); ?>>Otro</option>
                        </select>
                    </td>
                    <td>
                        <input type="text"
                               name="alquipress_ical_feeds[<?php echo $idx; ?>][label]"
                               value="<?php echo esc_attr($feed['label'] ?? ''); ?>"
                               placeholder="Ej: Airbnb Villa Sol">
                    </td>
                    <td>
                        <input type="url"
                               name="alquipress_ical_feeds[<?php echo $idx; ?>][url]"
                               value="<?php echo esc_url($feed['url'] ?? ''); ?>"
                               class="large-text"
                               placeholder="https://www.airbnb.com/calendar/ical/...">
                    </td>
                    <td>
                        <?php
                        $status = $feed['last_status'] ?? 'pendiente';
                        $icons  = ['ok' => '🟢', 'error' => '🔴', 'empty' => '🟡', 'pendiente' => '⚪'];
                        echo ($icons[$status] ?? '⚪') . ' ' . esc_html($status);
                        if (!empty($feed['last_sync'])):
                        ?>
                        <br><small><?php echo esc_html($feed['last_sync']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <input type="checkbox"
                               name="alquipress_ical_feeds[<?php echo $idx; ?>][active]"
                               value="1"
                               <?php checked(!empty($feed['active'])); ?>>
                    </td>
                    <td>
                        <button type="button" class="button button-small btn-remove-feed"
                                data-idx="<?php echo $idx; ?>">Eliminar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <button type="button" class="button" id="btn-add-feed" style="margin-top:8px">
                + Añadir canal
            </button>
        </div>

        <!-- Sincronización manual -->
        <div class="ical-manual-sync" style="margin-top:12px">
            <button type="button" class="button button-secondary"
                    id="btn-sync-now"
                    data-product-id="<?php echo $product_id; ?>"
                    data-nonce="<?php echo wp_create_nonce('ical_manual_sync'); ?>">
                🔄 Sincronizar ahora
            </button>
            <span id="sync-status" style="margin-left:10px; color:#666"></span>
        </div>
    </div>
    <?php
}

/**
 * Guardar los feeds al salvar el producto
 */
add_action('save_post_product', 'alquipress_save_ical_feeds');

function alquipress_save_ical_feeds(int $post_id) {
    if (!isset($_POST['alquipress_ical_nonce'])) return;
    if (!wp_verify_nonce($_POST['alquipress_ical_nonce'], 'alquipress_ical_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $feeds = [];
    if (isset($_POST['alquipress_ical_feeds']) && is_array($_POST['alquipress_ical_feeds'])) {
        foreach ($_POST['alquipress_ical_feeds'] as $feed) {
            if (empty($feed['url'])) continue;
            $feeds[] = [
                'channel'     => sanitize_key($feed['channel']),
                'label'       => sanitize_text_field($feed['label']),
                'url'         => esc_url_raw($feed['url']),
                'active'      => !empty($feed['active']),
                'last_sync'   => '', // Se rellena en el cron
                'last_status' => 'pendiente',
            ];
        }
    }

    update_post_meta($post_id, '_alquipress_ical_feeds', $feeds);
}
```

---

## 7. Sincronización manual vía AJAX

```php
/**
 * AJAX: forzar sincronización de una propiedad concreta
 */
add_action('wp_ajax_alquipress_sync_now', 'alquipress_ajax_sync_now');

function alquipress_ajax_sync_now() {
    check_ajax_referer('ical_manual_sync', 'nonce');
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Sin permisos.'], 403);
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $feeds      = get_post_meta($product_id, '_alquipress_ical_feeds', true) ?: [];

    $summary = [];
    foreach ($feeds as $idx => $feed) {
        if (empty($feed['active']) || empty($feed['url'])) continue;
        $result = alquipress_import_ical_feed($product_id, $feed);

        $feeds[$idx]['last_sync']   = current_time('mysql');
        $feeds[$idx]['last_status'] = $result['status'];

        $summary[] = sprintf('%s: %s (%d bloqueados)', $feed['channel'], $result['status'], $result['blocked']);
    }

    update_post_meta($product_id, '_alquipress_ical_feeds', $feeds);

    wp_send_json_success(['summary' => implode(' | ', $summary)]);
}
```

---

## 8. Dashboard de sincronización (página admin)

Una página centralizada para ver el estado de todos los feeds de todas las propiedades.

```php
add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'Sincronización iCal — ALQUIPRESS',
        '🔄 Sync iCal',
        'manage_woocommerce',
        'alquipress-ical-dashboard',
        'alquipress_render_ical_dashboard'
    );
});

function alquipress_render_ical_dashboard() {
    $last_sync = get_option('alquipress_ical_last_sync_time', 'Nunca');
    $last_log  = get_option('alquipress_ical_last_sync_log', '');

    $products = get_posts([
        'post_type'   => 'product',
        'numberposts' => -1,
        'meta_query'  => [
            ['key' => '_alquipress_ical_feeds', 'compare' => 'EXISTS'],
        ],
    ]);
    ?>
    <div class="wrap">
        <h1>🔄 Panel de Sincronización iCal</h1>
        <p>Última sincronización automática: <strong><?php echo esc_html($last_sync); ?></strong></p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Propiedad</th>
                    <th>Canal</th>
                    <th>Último estado</th>
                    <th>Última sync</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product):
                $feeds = get_post_meta($product->ID, '_alquipress_ical_feeds', true) ?: [];
                foreach ($feeds as $feed):
                    $status_icons = ['ok' => '🟢', 'error' => '🔴', 'empty' => '🟡', 'pendiente' => '⚪'];
                    $icon = $status_icons[$feed['last_status'] ?? 'pendiente'] ?? '⚪';
            ?>
            <tr>
                <td><a href="<?php echo get_edit_post_link($product->ID); ?>"><?php echo get_the_title($product->ID); ?></a></td>
                <td><?php echo esc_html(ucfirst($feed['channel'])); ?> — <?php echo esc_html($feed['label'] ?? ''); ?></td>
                <td><?php echo $icon . ' ' . esc_html($feed['last_status'] ?? 'pendiente'); ?></td>
                <td><?php echo esc_html($feed['last_sync'] ?? '—'); ?></td>
                <td>
                    <button class="button button-small btn-sync-property"
                            data-product-id="<?php echo $product->ID; ?>"
                            data-nonce="<?php echo wp_create_nonce('ical_manual_sync'); ?>">
                        Sync ahora
                    </button>
                </td>
            </tr>
            <?php endforeach; endforeach; ?>
            </tbody>
        </table>

        <?php if ($last_log): ?>
        <h3>Log de última sincronización automática</h3>
        <pre style="background:#f5f5f5; padding:12px; overflow:auto; max-height:300px; font-size:12px">
            <?php echo esc_html($last_log); ?>
        </pre>
        <?php endif; ?>
    </div>
    <?php
}
```

---

## 9. Configuración en cada canal externo

### Airbnb
```
Perfil → Anuncios → [Propiedad] → Disponibilidad → Sincronizar calendarios
  Exportar: Copiar URL iCal → Dar a Booking.com / web propia
  Importar: Pegar URL iCal de tu web (la que genera ALQUIPRESS)
  Frecuencia de actualización: Airbnb actualiza cada 1-3 horas por su parte
```

### Booking.com
```
Extranet → Propiedades → [Propiedad] → Calendario → Sincronización
  → "Añadir sincronización de iCal"
  Exportar URL: copiar y pegar en Airbnb / web
  Importar URL: pegar la URL de ALQUIPRESS
  Booking.com actualiza cada ~2 horas
```

### VRBO / HomeAway
```
Dashboard → [Propiedad] → Calendarios → Importar/Exportar calendarios
  Proceso similar al de Airbnb
```

> ⚠️ **Importante:** Los canales externos no actualizan en tiempo real. Airbnb y Booking.com tienen latencias de 1-3 horas en su extremo. La web ALQUIPRESS sí actualiza cada 15 minutos. Por tanto, el riesgo de overbooking más alto es entre dos canales externos (Airbnb → Booking), no entre un canal externo y la web directa.

---

## 10. Tabla de latencias y riesgo por canal

| Canal | Frecuencia de importación (su lado) | Frecuencia exportación (nuestro lado) | Riesgo overbooking |
|---|---|---|---|
| ALQUIPRESS → Airbnb | ~1-3h (Airbnb actualiza) | 15 min (cron servidor) | 🟡 Bajo-medio |
| ALQUIPRESS → Booking.com | ~2h (Booking actualiza) | 15 min | 🟡 Bajo-medio |
| Airbnb → ALQUIPRESS | 15 min (nuestro cron) | — | 🟢 Bajo |
| Booking.com → ALQUIPRESS | 15 min (nuestro cron) | — | 🟢 Bajo |
| Airbnb ↔ Booking (sin pasar por ALQUIPRESS) | 1-3h cada uno | — | 🔴 Alto (no controlable) |

**Conclusión:** el mayor riesgo siempre está entre dos canales externos. La solución definitiva es **centralizar todas las reservas en ALQUIPRESS** y usar los canales externos solo como escaparates, con reservas bloqueadas manualmente o a través del channel manager.

---

## 11. Checklist de implementación

```
FASE 1 — Exportación
[ ] Implementar función alquipress_get_ical_key() y generación de URL
[ ] Registrar endpoint de exportación .ics en init
[ ] Test: acceder a la URL de exportación y verificar que descarga un .ics válido
[ ] Test: importar el .ics en Google Calendar y verificar que aparecen las reservas
[ ] Registrar URL de ALQUIPRESS en Airbnb (importar en Airbnb)
[ ] Registrar URL de ALQUIPRESS en Booking.com

FASE 2 — Importación
[ ] Implementar parser iCal (alquipress_parse_ical_events)
[ ] Implementar motor de importación (alquipress_import_ical_feed)
[ ] Test: importar manualmente un .ics de Airbnb de prueba
[ ] Verificar que se crean bloqueos (WC_Booking) con status confirmed
[ ] Verificar que los duplicados se detectan y no se crean dos veces

FASE 3 — Cron
[ ] Registrar intervalo every_15_minutes
[ ] Registrar evento alquipress_ical_sync_all
[ ] DISABLE_WP_CRON = true en wp-config.php
[ ] Crear cron job real en el servidor (cada 15 min)
[ ] Test: verificar con wp cron event list que el evento está programado
[ ] Test: ejecutar wp cron event run alquipress_ical_sync_all manualmente
[ ] Verificar que el log se guarda correctamente

FASE 4 — Meta Box y configuración por propiedad
[ ] Añadir meta box de feeds iCal en la ficha del producto
[ ] Guardar feeds al salvar el producto
[ ] Mostrar URL de exportación con botón copiar
[ ] Test: guardar 2 feeds y verificar que se persisten correctamente

FASE 5 — Dashboard admin
[ ] Registrar página "Sync iCal" en el menú de WooCommerce
[ ] Renderizar tabla de estado por propiedad y canal
[ ] AJAX handler para sincronización manual
[ ] Botón "Sync ahora" funcional por propiedad
[ ] Alertas al admin cuando un feed falla 3 veces seguidas

FASE 6 — Configuración en los canales externos
[ ] Añadir URL de exportación de ALQUIPRESS en Airbnb (cada propiedad)
[ ] Añadir URL de exportación de ALQUIPRESS en Booking.com
[ ] Añadir URLs de Airbnb y Booking como feeds de importación en ALQUIPRESS
[ ] Test completo: hacer reserva en Airbnb → esperar sync → verificar bloqueo en ALQUIPRESS
[ ] Test completo: hacer reserva en ALQUIPRESS → esperar sync → verificar bloqueo en Airbnb
```

---

## 12. Evolución futura (v2)

- **Channel Manager API:** Para propiedades con alto volumen de reservas, integrar con un channel manager real (Smoobu, Lodgify, Guesty) que ofrezca sincronización en tiempo real vía API en lugar de iCal con latencia.
- **Detección activa de conflictos:** Al crear una reserva manual, cruzar contra los feeds externos en tiempo real (petición HTTP a los .ics de Airbnb/Booking) antes de confirmar.
- **Notificación push de nueva reserva:** Cuando el cron importa una reserva nueva de Airbnb o Booking, enviar notificación push o email al admin en tiempo real.
- **Log histórico de sincronizaciones:** Tabla en la base de datos con cada evento de sync, canal, propiedad, resultado y errores — para auditoría y debugging avanzado.
- **Limpieza automática:** Job semanal que elimina bloqueos importados cuyas fechas ya han pasado para mantener la base de datos limpia.

---

> **Autor:** Arquitectura ALQUIPRESS  
> **Última revisión:** Febrero 2026  
> **Módulo anterior:** [MÓDULO 04 — Panel del Propietario]  
> **Siguiente módulo:** [MÓDULO 06 — Email Marketing con MailPoet]
