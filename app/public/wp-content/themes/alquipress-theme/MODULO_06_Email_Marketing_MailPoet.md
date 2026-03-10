# MÓDULO 06 — Email Marketing con MailPoet

> **Proyecto:** ALQUIPRESS  
> **Stack:** WordPress · WooCommerce Bookings · MailPoet 4 · MailPoet for WooCommerce · ACF Pro  
> **Objetivo:** Sistema de comunicación automatizado con huéspedes en cada fase del ciclo de vida de la reserva, captación de leads con RGPD y segmentación avanzada para fidelización  
> **Plugins:** MailPoet (gratuito hasta 1.000 suscriptores) + MailPoet Premium (para WooCommerce Automations)

---

## 1. Concepto: Los tres momentos que importan

El email marketing en un alquiler vacacional no es newsletter genérica. Es comunicación de servicio en tres momentos críticos con objetivos muy distintos:

```
ANTES DE LLEGAR          DURANTE LA ESTANCIA         DESPUÉS DE MARCHARSE
      │                         │                            │
      ▼                         ▼                            ▼
Reducir la ansiedad       Crear la experiencia        Capturar el valor
del viajero               (reducir quejas)            (reseña + repetición)

  ├── Confirmación          ├── Check-in +24h           ├── Post check-out +1d
  ├── Guía de llegada T-7   └── Emergencia               ├── Solicitud de reseña
  └── Recordatorio T-2                                   ├── Cupón para repetir
                                                         └── Recuperador 11 meses
```

---

## 2. Arquitectura MailPoet en ALQUIPRESS

```
WordPress / WooCommerce Bookings
        │
        ├── Hook: woocommerce_order_status_changed
        ├── Hook: alquipress_checkin_approaching   (cron T-7)
        ├── Hook: alquipress_checkin_tomorrow      (cron T-2)
        ├── Hook: woocommerce_order_status_in-progress
        └── Hook: woocommerce_order_status_completed
                │
                ▼
        MailPoet API (wp_mailpoet)
                │
                ├── Listas dinámicas (segmentos por zona, perfil, VIP)
                ├── Automatizaciones WooCommerce (triggers nativos)
                └── Campañas puntuales (newsletter temporal, ofertas)
```

---

## 3. Listas y segmentos

### 3.1 Listas base

| Lista | Descripción | Cómo se nutre |
|---|---|---|
| `newsletter-general` | Leads captados en web (no han reservado) | Formulario footer / landing |
| `clientes-compradores` | Han reservado al menos una vez | Checkout opt-in automático |
| `clientes-vip` | >3 reservas o gasto >3.000€ | Cron semanal de clasificación |
| `zona-denia` | Reservaron propiedad en Dénia | Hook post-reserva por taxonomía |
| `zona-javea` | Reservaron propiedad en Jávea | Hook post-reserva por taxonomía |
| `zona-calpe` | Reservaron propiedad en Calpe | Hook post-reserva por taxonomía |
| `mascotas` | `guest_preferences` contiene `mascotas` | ACF + hook de actualización |
| `familias` | `guest_preferences` contiene `familia` | ACF + hook de actualización |

### 3.2 Registro PHP de suscripción al confirmar reserva

```php
<?php
/**
 * ALQUIPRESS — Módulo 06: Suscribir cliente a listas MailPoet tras reserva
 * Se ejecuta al cambiar el estado del pedido a cualquier estado "activo"
 */
add_action('woocommerce_order_status_changed', 'alquipress_mailpoet_subscribe_on_booking', 10, 4);

function alquipress_mailpoet_subscribe_on_booking(int $order_id, string $old_status, string $new_status, WC_Order $order) {
    // Solo cuando llega a un estado de reserva confirmada
    $active_statuses = ['processing', 'wc-deposit-received', 'completed'];
    if (!in_array('wc-' . $new_status, $active_statuses) && !in_array($new_status, $active_statuses)) return;

    // Evitar doble suscripción
    if (get_post_meta($order_id, '_mailpoet_subscribed', true)) return;

    $customer_email = $order->get_billing_email();
    $first_name     = $order->get_billing_first_name();
    $last_name      = $order->get_billing_last_name();
    $customer_id    = $order->get_customer_id();

    // Listas a las que suscribir
    $list_slugs = ['clientes-compradores'];

    // Añadir lista por zona de la propiedad reservada
    $product_id = alquipress_get_product_id_from_order($order_id);
    if ($product_id) {
        $poblaciones = wp_get_post_terms($product_id, 'poblacion', ['fields' => 'slugs']);
        foreach ($poblaciones as $slug) {
            if (in_array('zona-' . $slug, ['zona-denia', 'zona-javea', 'zona-calpe'])) {
                $list_slugs[] = 'zona-' . $slug;
            }
        }
    }

    // Comprobar preferencias ACF del usuario (si ya existe en WP)
    if ($customer_id) {
        $preferences = get_user_meta($customer_id, 'guest_preferences', true) ?: [];
        if (in_array('mascotas', (array) $preferences)) $list_slugs[] = 'mascotas';
        if (in_array('familia', (array) $preferences))  $list_slugs[] = 'familias';
    }

    // Obtener IDs de listas en MailPoet por nombre
    $list_ids = alquipress_get_mailpoet_list_ids($list_slugs);

    try {
        // Suscribir usando la función nativa de MailPoet
        \MailPoet\API\API::MP('v1')->addSubscriber(
            [
                'email'      => $customer_email,
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'status'     => 'subscribed',
            ],
            $list_ids,
            ['send_confirmation_email' => false] // Ya tiene el email de confirmación de pedido
        );

        update_post_meta($order_id, '_mailpoet_subscribed', true);

    } catch (\Exception $e) {
        // Loguear sin romper el flujo de la reserva
        error_log('ALQUIPRESS MailPoet subscribe error: ' . $e->getMessage());
    }
}

/**
 * Helper: obtener IDs de listas MailPoet por array de slugs/nombres
 */
function alquipress_get_mailpoet_list_ids(array $slugs): array {
    try {
        $all_lists = \MailPoet\API\API::MP('v1')->getLists();
        $ids = [];
        foreach ($all_lists as $list) {
            // Comparar por nombre (MailPoet no tiene slug nativo)
            $list_name_slug = sanitize_title($list['name']);
            if (in_array($list_name_slug, $slugs)) {
                $ids[] = $list['id'];
            }
        }
        return $ids;
    } catch (\Exception $e) {
        return [];
    }
}
```

### 3.3 Opt-in en el checkout

```php
/**
 * Añadir casilla de suscripción RGPD en el checkout de WooCommerce
 * MailPoet puede hacer esto de forma nativa desde sus ajustes,
 * pero aquí lo controlamos manualmente para mayor flexibilidad
 */
add_action('woocommerce_review_order_before_submit', 'alquipress_checkout_newsletter_optin');

function alquipress_checkout_newsletter_optin() {
    // MailPoet gestiona esto desde: MailPoet → Ajustes → WooCommerce → "Mostrar suscripción en el checkout"
    // Esta función es solo un recordatorio de dónde activarlo.
    // La casilla generada por MailPoet ya incluye texto RGPD y doble opt-in.
}

// Texto legal sugerido para la casilla (configurar en MailPoet → Ajustes → Suscripción):
// "Acepto recibir comunicaciones de ALQUIPRESS sobre ofertas y novedades. 
//  Puedes cancelar la suscripción en cualquier momento. [Política de Privacidad]"
```

---

## 4. Los 8 flujos de automatización

### Flujo 1 — Confirmación de reserva (inmediato)

**Trigger:** Estado del pedido → `wc-deposit-received` (depósito pagado)  
**Objetivo:** Tranquilizar al cliente, confirmar la reserva y preparar expectativas del pago restante.

**Asunto sugerido:** `✅ Reserva confirmada — [Nombre propiedad], [Fechas]`

**Estructura del email:**
```
CABECERA: Logo ALQUIPRESS + foto de la propiedad
HERO: "¡Tu reserva está confirmada!" + badge verde

RESUMEN:
  🏠 Villa Sol — Dénia
  📅 12 – 18 de agosto
  👥 4 personas
  💶 Depósito pagado: 400 €
  🕐 Pago restante: 600 € — se cargará automáticamente el 5 de agosto

PRÓXIMOS PASOS:
  1. Guarda este email
  2. Recibirás la guía de llegada 7 días antes
  3. Si tienes dudas: hola@alquipress.com

FOOTER: Política de cancelación + enlace a Mi Cuenta
```

**Variables MailPoet (shortcodes):**
```
[wc:order_number]     → número de pedido
[wc:product_name]     → nombre de la propiedad
[subscriber:firstname] → nombre del cliente
```

**Implementación PHP (envío manual si el trigger automático de MailPoet no cubre el estado custom):**

```php
add_action('woocommerce_order_status_wc-deposit-received', 'alquipress_send_booking_confirmation', 10, 1);

function alquipress_send_booking_confirmation(int $order_id) {
    if (get_post_meta($order_id, '_confirmation_email_sent', true)) return;

    $order       = wc_get_order($order_id);
    $newsletter_id = get_option('alquipress_mailpoet_confirmation_id'); // ID del newsletter en MailPoet

    if (!$newsletter_id) return;

    try {
        \MailPoet\API\API::MP('v1')->sendTransactionalEmail(
            (int) $newsletter_id,
            $order->get_billing_email(),
            ['order_id' => $order_id]
        );
        update_post_meta($order_id, '_confirmation_email_sent', true);
    } catch (\Exception $e) {
        error_log('ALQUIPRESS: Error confirmación email — ' . $e->getMessage());
    }
}
```

---

### Flujo 2 — Guía de llegada (T-7 días antes del check-in)

**Trigger:** Cron diario, busca reservas con check-in en exactamente 7 días  
**Objetivo:** Dar toda la información práctica antes del viaje. El email que más valora el huésped.

**Asunto:** `🗝️ Tu guía de llegada — Villa Sol te espera en 7 días`

**Estructura del email:**
```
HERO: Foto de la propiedad + countdown "Faltan 7 días"

SECCIÓN 1 — CÓMO LLEGAR
  📍 Dirección completa
  🗺️ Botón "Abrir en Google Maps"
  🚗 Indicaciones si viene en coche (AP-7, salida...)

SECCIÓN 2 — RECOGIDA DE LLAVES
  🔑 Caja de seguridad en [ubicación]
  🔢 Código: [se revelará 48h antes por seguridad] ← IMPORTANTE
  📞 Teléfono de guardia: [número]

SECCIÓN 3 — LA CASA
  📶 WiFi: [nombre de red] / [contraseña]
  🅿️ Aparcamiento: [descripción]
  🧹 Check-in: a partir de las 15:00h
  🚪 Check-out: antes de las 11:00h

SECCIÓN 4 — LA ZONA
  3 recomendaciones locales (restaurante, playa, actividad)

FOOTER: Normas de la casa PDF + contacto de emergencia
```

**Código del cron:**

```php
/**
 * Registrar evento cron diario para emails T-7 y T-2
 */
add_action('init', 'alquipress_schedule_arrival_emails');

function alquipress_schedule_arrival_emails() {
    if (!wp_next_scheduled('alquipress_checkin_email_check')) {
        wp_schedule_event(
            strtotime('today 09:00:00'), // Enviar a las 9:00 AM
            'daily',
            'alquipress_checkin_email_check'
        );
    }
}

add_action('alquipress_checkin_email_check', 'alquipress_run_checkin_email_check');

function alquipress_run_checkin_email_check() {
    $today        = current_time('timestamp');
    $target_7days = $today + (7 * DAY_IN_SECONDS);
    $target_2days = $today + (2 * DAY_IN_SECONDS);

    // Buscar reservas con check-in en 7 días
    $bookings_7 = alquipress_get_bookings_checkin_on_date($target_7days);
    foreach ($bookings_7 as $booking) {
        alquipress_send_arrival_guide_email($booking);
    }

    // Buscar reservas con check-in en 2 días (recordatorio)
    $bookings_2 = alquipress_get_bookings_checkin_on_date($target_2days);
    foreach ($bookings_2 as $booking) {
        alquipress_send_reminder_email($booking);
    }
}

/**
 * Obtener reservas cuyo check-in es en una fecha específica (±12 horas)
 */
function alquipress_get_bookings_checkin_on_date(int $target_ts): array {
    global $wpdb;

    $date_start = date('Y-m-d 00:00:00', $target_ts);
    $date_end   = date('Y-m-d 23:59:59', $target_ts);

    $booking_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta}
         WHERE meta_key = '_booking_start'
           AND meta_value BETWEEN %s AND %s",
        $date_start,
        $date_end
    ));

    $bookings = [];
    foreach ($booking_ids as $bid) {
        $booking = new WC_Booking($bid);
        if (in_array($booking->get_status(), ['confirmed', 'paid'])) {
            $bookings[] = $booking;
        }
    }

    return $bookings;
}

/**
 * Enviar guía de llegada T-7
 */
function alquipress_send_arrival_guide_email(WC_Booking $booking) {
    // Evitar reenvíos
    if (get_post_meta($booking->get_id(), '_arrival_guide_sent', true)) return;

    $order      = $booking->get_order();
    if (!$order) return;

    $email_id   = get_option('alquipress_mailpoet_arrival_guide_id');
    $product_id = $booking->get_product_id();

    // Preparar datos específicos de la propiedad
    $checkin_time  = get_field('horario_checkin', $product_id)  ?: '15:00';
    $checkout_time = get_field('horario_checkout', $product_id) ?: '11:00';
    $wifi_name     = get_field('wifi_nombre', $product_id)      ?: '';
    $wifi_pass     = get_field('wifi_password', $product_id)    ?: '';
    $address       = get_field('direccion_completa', $product_id) ?: '';

    // Guardar datos en la sesión del suscriptor para que MailPoet los use
    // (alternativamente, usar wp_mail con plantilla propia)
    alquipress_send_custom_email(
        $order->get_billing_email(),
        '🗝️ Tu guía de llegada — ' . get_the_title($product_id) . ' te espera en 7 días',
        alquipress_build_arrival_guide_html([
            'guest_name'    => $order->get_billing_first_name(),
            'property_name' => get_the_title($product_id),
            'checkin_date'  => date_i18n('j \d\e F', $booking->get_start()),
            'checkout_date' => date_i18n('j \d\e F', $booking->get_end()),
            'address'       => $address,
            'checkin_time'  => $checkin_time,
            'checkout_time' => $checkout_time,
            'wifi_name'     => $wifi_name,
            'wifi_pass'     => $wifi_pass,
            'maps_url'      => 'https://www.google.com/maps/search/' . urlencode($address),
        ])
    );

    update_post_meta($booking->get_id(), '_arrival_guide_sent', true);
}
```

---

### Flujo 3 — Recordatorio T-2 (código de llaves)

**Trigger:** Cron diario, reservas con check-in en 2 días  
**Objetivo:** Dar el código de la caja de llaves. Seguridad operativa: no enviarlo antes.

**Asunto:** `🔐 Tu código de acceso — Llegada pasado mañana`

**Contenido clave:**
```
📍 Recuerda: llegas el [fecha] a partir de las [hora]
🔑 Código de la caja: [CÓDIGO] → Calle [dirección], junto a la puerta principal
📞 Emergencias 24h: [teléfono]
❓ Preguntas frecuentes: [link FAQ]
```

```php
function alquipress_send_reminder_email(WC_Booking $booking) {
    if (get_post_meta($booking->get_id(), '_reminder_email_sent', true)) return;

    $order      = $booking->get_order();
    if (!$order) return;

    $product_id  = $booking->get_product_id();
    $access_code = get_field('codigo_caja_llaves', $product_id) ?: '[Ver instrucciones adjuntas]';

    alquipress_send_custom_email(
        $order->get_billing_email(),
        '🔐 Tu código de acceso — Llegada el ' . date_i18n('j \d\e F', $booking->get_start()),
        alquipress_build_reminder_html([
            'guest_name'   => $order->get_billing_first_name(),
            'checkin_date' => date_i18n('j \d\e F \a \l\a\s G:i', $booking->get_start()),
            'access_code'  => $access_code,
            'property_name'=> get_the_title($product_id),
            'emergency_phone' => get_option('alquipress_emergency_phone', ''),
        ])
    );

    update_post_meta($booking->get_id(), '_reminder_email_sent', true);
}
```

---

### Flujo 4 — Check-in cortesía (T+24h de la entrada)

**Trigger:** Hook `woocommerce_order_status_in-progress` + `wp_schedule_single_event` T+24h  
**Objetivo:** Detectar problemas antes de que el cliente se queje en Airbnb.

**Asunto:** `☀️ ¿Todo bien en [nombre propiedad]?`

**Contenido:**
```
Hola [nombre],

Esperamos que estéis disfrutando de vuestra estancia en [propiedad].
¿Hay algo que podamos mejorar o necesitáis algo?

Estamos a vuestra disposición:
📞 [teléfono] · ✉️ [email]

Si todo está perfecto, ¡disfrutad de [ciudad]! 🌊
```

```php
// El hook ya está en el Módulo 03 (Pipeline):
add_action('woocommerce_order_status_in-progress', function($order_id) {
    // ... (código del Módulo 03)
    // Programar email de cortesía T+24h
    wp_schedule_single_event(
        time() + DAY_IN_SECONDS,
        'alquipress_send_wellbeing_email',
        [$order_id]
    );
});

add_action('alquipress_send_wellbeing_email', function(int $order_id) {
    $order      = wc_get_order($order_id);
    $booking    = alquipress_get_booking_from_order($order_id);
    if (!$order || !$booking) return;

    $product_id = $booking->get_product_id();

    alquipress_send_custom_email(
        $order->get_billing_email(),
        '☀️ ¿Todo bien en ' . get_the_title($product_id) . '?',
        alquipress_build_wellbeing_html([
            'guest_name'    => $order->get_billing_first_name(),
            'property_name' => get_the_title($product_id),
            'phone'         => get_option('alquipress_contact_phone', ''),
            'email'         => get_option('admin_email'),
        ])
    );
});
```

---

### Flujo 5 — Solicitud de reseña (T+1 día del check-out)

**Trigger:** `woocommerce_order_status_wc-departure-review` + `wp_schedule_single_event` T+24h  
**Objetivo:** Capturar la reseña cuando la experiencia aún está fresca.

**Asunto:** `⭐ ¿Cómo fue tu estancia en [propiedad]?`

**Contenido:**
```
Hola [nombre],

Ha sido un placer tenerte en [propiedad]. Esperamos que hayas 
disfrutado de tu estancia en [ciudad].

Si tienes un momento, tu opinión nos ayuda a mejorar y a que 
otros viajeros puedan encontrar su casa ideal:

[⭐ Dejar reseña en Google]    [✍️ Reseña en nuestra web]

Como agradecimiento, aquí tienes un descuento del 5% para 
tu próxima reserva: GRACIAS2025

Válido hasta [fecha = hoy + 90 días]

¡Hasta pronto!
```

```php
add_action('woocommerce_order_status_wc-departure-review', function($order_id) {
    wp_schedule_single_event(
        time() + DAY_IN_SECONDS,
        'alquipress_send_review_request',
        [$order_id]
    );
});

add_action('alquipress_send_review_request', function(int $order_id) {
    if (get_post_meta($order_id, '_review_request_sent', true)) return;

    $order      = wc_get_order($order_id);
    $booking    = alquipress_get_booking_from_order($order_id);
    if (!$order || !$booking) return;

    $product_id   = $booking->get_product_id();
    $coupon_code  = 'GRACIAS' . date('Y');
    $expiry_date  = date('d/m/Y', strtotime('+90 days'));

    // Crear cupón automáticamente si no existe
    alquipress_ensure_loyalty_coupon($coupon_code, 5, $expiry_date);

    alquipress_send_custom_email(
        $order->get_billing_email(),
        '⭐ ¿Cómo fue tu estancia en ' . get_the_title($product_id) . '?',
        alquipress_build_review_request_html([
            'guest_name'    => $order->get_billing_first_name(),
            'property_name' => get_the_title($product_id),
            'city'          => alquipress_get_property_city($product_id),
            'google_url'    => get_option('alquipress_google_review_url', '#'),
            'coupon_code'   => $coupon_code,
            'expiry_date'   => $expiry_date,
        ])
    );

    update_post_meta($order_id, '_review_request_sent', true);
});

/**
 * Crear cupón de fidelización si no existe ya
 */
function alquipress_ensure_loyalty_coupon(string $code, int $discount_pct, string $expiry): void {
    $existing = get_page_by_title($code, OBJECT, 'shop_coupon');
    if ($existing) return;

    $coupon = new WC_Coupon();
    $coupon->set_code($code);
    $coupon->set_discount_type('percent');
    $coupon->set_amount($discount_pct);
    $coupon->set_date_expires(strtotime($expiry));
    $coupon->set_usage_limit(500);
    $coupon->set_individual_use(true);
    $coupon->save();
}
```

---

### Flujo 6 — Recuperador "hace un año" (T+11 meses)

**Trigger:** Cron mensual que busca reservas completadas hace ~11 meses  
**Objetivo:** El email de mayor ROI. Recupera clientes que se olvidan de repetir.

**Asunto:** `🌊 Hace un año estuviste en [ciudad]... ¿vuelves este verano?`

**Contenido:**
```
Hola [nombre],

El verano pasado disfrutaste de [propiedad] en [ciudad]. 
¡Casi un año ya! 

Este verano vuelven los días perfectos en la Costa Blanca.
¿Te reservamos tu sitio antes de que se agote?

Como cliente habitual, tienes un 5% de descuento:
Código: REPITE2025  (válido hasta [fecha])

[→ Ver disponibilidad de [propiedad]]
[→ Explorar otras propiedades en [ciudad]]
```

```php
add_action('alquipress_monthly_recovery_emails', 'alquipress_send_recovery_emails');

// Registrar el evento mensual
if (!wp_next_scheduled('alquipress_monthly_recovery_emails')) {
    wp_schedule_event(time(), 'monthly', 'alquipress_monthly_recovery_emails');
}

function alquipress_send_recovery_emails() {
    global $wpdb;

    // Buscar pedidos completados hace entre 10.5 y 11.5 meses
    $date_min = date('Y-m-d', strtotime('-11 months -15 days'));
    $date_max = date('Y-m-d', strtotime('-10 months -15 days'));

    $order_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type = 'shop_order'
           AND post_status IN ('wc-completed', 'wc-deposit-refunded')
           AND post_date BETWEEN %s AND %s",
        $date_min . ' 00:00:00',
        $date_max . ' 23:59:59'
    ));

    foreach ($order_ids as $order_id) {
        // Evitar reenvíos
        if (get_post_meta($order_id, '_recovery_email_sent', true)) continue;

        $order   = wc_get_order($order_id);
        $booking = alquipress_get_booking_from_order($order_id);
        if (!$order || !$booking) continue;

        // No enviar a clientes en lista negra
        $customer_id = $order->get_customer_id();
        if ($customer_id) {
            $status = get_user_meta($customer_id, 'guest_status', true);
            if ($status === 'blacklist') continue;
        }

        $product_id  = $booking->get_product_id();
        $coupon_code = 'REPITE' . date('Y');
        alquipress_ensure_loyalty_coupon($coupon_code, 5, date('d/m/Y', strtotime('+60 days')));

        alquipress_send_custom_email(
            $order->get_billing_email(),
            '🌊 Hace un año estuviste en ' . alquipress_get_property_city($product_id) . '... ¿vuelves?',
            alquipress_build_recovery_html([
                'guest_name'    => $order->get_billing_first_name(),
                'property_name' => get_the_title($product_id),
                'product_url'   => get_permalink($product_id),
                'city'          => alquipress_get_property_city($product_id),
                'coupon_code'   => $coupon_code,
                'expiry_date'   => date('d/m/Y', strtotime('+60 days')),
            ])
        );

        update_post_meta($order_id, '_recovery_email_sent', true);
    }
}
```

---

### Flujo 7 — Email de cumpleaños

**Trigger:** Cron diario que compara `guest_dob` (ACF) con la fecha de hoy  
**Objetivo:** Sorprender al cliente con un detalle personal. Muy bajo esfuerzo, alto impacto en fidelización.

**Asunto:** `🎂 ¡Feliz cumpleaños, [nombre]! Un regalo de ALQUIPRESS`

```php
add_action('alquipress_checkin_email_check', 'alquipress_send_birthday_emails');

function alquipress_send_birthday_emails() {
    $today_md = date('m-d'); // Mes-día para comparar independientemente del año

    // Buscar usuarios cuyo guest_dob tenga el mismo mes-día de hoy
    $users = get_users([
        'meta_key'   => 'guest_dob',
        'meta_value' => '',
        'compare'    => '!=',
        'role'       => 'customer',
        'number'     => -1,
    ]);

    foreach ($users as $user) {
        $dob = get_user_meta($user->ID, 'guest_dob', true);
        if (!$dob) continue;

        $dob_md = date('m-d', strtotime($dob));
        if ($dob_md !== $today_md) continue;

        // Evitar reenvío este año
        $sent_year = get_user_meta($user->ID, '_birthday_email_year', true);
        if ((int) $sent_year === (int) date('Y')) continue;

        $coupon_code = 'CUMPLE' . $user->ID . date('Y');
        alquipress_ensure_loyalty_coupon($coupon_code, 10, date('d/m/Y', strtotime('+30 days')));

        alquipress_send_custom_email(
            $user->user_email,
            '🎂 ¡Feliz cumpleaños, ' . $user->first_name . '! Un regalo de ALQUIPRESS',
            alquipress_build_birthday_html([
                'guest_name'  => $user->first_name,
                'coupon_code' => $coupon_code,
                'expiry_date' => date('d/m/Y', strtotime('+30 days')),
            ])
        );

        update_user_meta($user->ID, '_birthday_email_year', date('Y'));
    }
}
```

---

### Flujo 8 — Alerta interna al equipo (no al cliente)

**Trigger:** Check-in en las próximas 24 horas con checklist incompleto  
**Objetivo:** Evitar que el equipo operativo llegue a un check-in sin preparar.

```php
add_action('alquipress_checkin_email_check', 'alquipress_alert_team_pending_checkins');

function alquipress_alert_team_pending_checkins() {
    $tomorrow_start = strtotime('tomorrow 00:00:00');
    $tomorrow_end   = strtotime('tomorrow 23:59:59');

    $bookings = alquipress_get_bookings_between($tomorrow_start, $tomorrow_end);
    $pending  = [];

    foreach ($bookings as $booking) {
        $order_id = $booking->get_order_id();
        $checklist = [
            'ses_enviado'         => get_post_meta($order_id, '_checklist_ses', true),
            'limpieza_programada' => get_post_meta($order_id, '_checklist_cleaning', true),
            'llaves_preparadas'   => get_post_meta($order_id, '_checklist_keys', true),
        ];

        $incomplete = array_filter($checklist, fn($v) => empty($v));
        if (!empty($incomplete)) {
            $pending[] = [
                'property' => get_the_title($booking->get_product_id()),
                'order_id' => $order_id,
                'missing'  => array_keys($incomplete),
            ];
        }
    }

    if (empty($pending)) return;

    $body = "⚠️ CHECK-INS MAÑANA CON TAREAS PENDIENTES\n\n";
    foreach ($pending as $item) {
        $body .= "🏠 {$item['property']} (Pedido #{$item['order_id']})\n";
        $body .= "   Pendiente: " . implode(', ', $item['missing']) . "\n\n";
    }
    $body .= "Accede al pipeline: " . admin_url('admin.php?page=alquipress-pipeline');

    wp_mail(
        get_option('admin_email'),
        '⚠️ ALQUIPRESS — ' . count($pending) . ' check-ins mañana con tareas incompletas',
        $body
    );
}
```

---

## 5. Función base de envío de emails (helper)

```php
<?php
/**
 * Helper centralizado para enviar emails HTML
 * Usa wp_mail con cabeceras HTML y plantilla base
 */
function alquipress_send_custom_email(string $to, string $subject, string $html_body): bool {
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ALQUIPRESS <' . get_option('admin_email') . '>',
    ];

    // Envolver en la plantilla base
    $full_html = alquipress_email_wrapper($subject, $html_body);

    return wp_mail($to, $subject, $full_html, $headers);
}

/**
 * Plantilla HTML base para todos los emails de ALQUIPRESS
 * Consistente con el look & feel del CRM (azul marino, coral, Inter)
 */
function alquipress_email_wrapper(string $title, string $content): string {
    $logo_url      = get_option('alquipress_logo_url', home_url('/wp-content/uploads/logo-alquipress.png'));
    $primary_color = '#1e3a5f';
    $accent_color  = '#e07b54';
    $unsubscribe   = home_url('/baja-newsletter/?email=[subscriber:email]');

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fa;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fa;padding:32px 16px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0"
               style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

          <!-- Header -->
          <tr>
            <td style="background:{$primary_color};padding:28px 40px;text-align:center;">
              <img src="{$logo_url}" alt="ALQUIPRESS" height="40" style="display:block;margin:0 auto;">
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="padding:40px;color:#1a2636;font-size:15px;line-height:1.6;">
              {$content}
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f0f4f8;padding:24px 40px;text-align:center;font-size:12px;color:#6b7280;">
              <p style="margin:0 0 8px;">
                ALQUIPRESS · Costa Blanca, España<br>
                <a href="mailto:hola@alquipress.com" style="color:{$primary_color};">hola@alquipress.com</a>
              </p>
              <p style="margin:0;">
                <a href="{$unsubscribe}" style="color:#9ca3af;text-decoration:underline;">
                  Cancelar suscripción
                </a>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}
```

---

## 6. Configuración SMTP (obligatorio para deliverability)

Usar `wp_mail` con el servidor de correo por defecto del hosting es la forma más rápida de acabar en la carpeta de spam. Para producción hay que configurar un SMTP transaccional.

### Opción recomendada: Brevo (ex Sendinblue) + WP Mail SMTP

```
1. Crear cuenta en brevo.com (gratuito hasta 300 emails/día)
2. Instalar plugin: WP Mail SMTP by WPForms
3. Configurar:
   Host:     smtp-relay.brevo.com
   Puerto:   587 (TLS)
   Usuario:  tu-email@dominio.com
   Password: clave SMTP de Brevo (en tu panel → SMTP & API)
4. Verificar dominio en Brevo (SPF + DKIM) → esto es crítico para deliverability
5. Test: enviar email de prueba desde WP Mail SMTP → verificar entrega
```

### Registros DNS obligatorios (en tu proveedor de dominio)

```dns
# SPF — Autorizar a Brevo a enviar en tu nombre
TXT  @  "v=spf1 include:spf.sendinblue.com ~all"

# DKIM — Firma criptográfica (Brevo te da el valor exacto)
TXT  mail._domainkey  "v=DKIM1; k=rsa; p=MIGfMA0G..."

# DMARC — Política de autenticación
TXT  _dmarc  "v=DMARC1; p=quarantine; rua=mailto:dmarc@tudominio.com"
```

---

## 7. Mapa completo de automatizaciones

```
EVENTO                          EMAIL ENVIADO                    T (timing)
──────────────────────────────────────────────────────────────────────────────
Depósito pagado                 ✅ Confirmación de reserva        Inmediato
                                   (resumen + fecha 2º pago)

7 días antes del check-in       🗝️ Guía de llegada               T-7 días
                                   (cómo llegar, WiFi, zona)

2 días antes del check-in       🔐 Código de acceso              T-2 días
                                   (caja de llaves, emergencias)

Check-in registrado             ☀️ Email de cortesía             T+24h
(estado: in-progress)              (¿todo bien?)

Check-out registrado            ⭐ Solicitud de reseña           T+24h
(estado: departure-review)         (Google + cupón 5%)

11 meses después del check-out  🌊 Email recuperador             T+11 meses
                                   (¿vuelves? cupón 5%)

Cumpleaños (ACF: guest_dob)     🎂 Felicitación + cupón 10%      Día del cumpleaños

Check-in mañana + tarea         ⚠️ Alerta interna al equipo      Diario 9:00h
pendiente en checklist             (SES, limpieza, llaves)
──────────────────────────────────────────────────────────────────────────────
```

---

## 8. RGPD: lo imprescindible

```php
/**
 * Registrar página de baja de newsletter (endpoint nativo WP)
 */
add_action('init', function() {
    add_rewrite_endpoint('baja-newsletter', EP_ROOT);
});

add_action('template_redirect', 'alquipress_handle_unsubscribe');

function alquipress_handle_unsubscribe() {
    if (!get_query_var('baja-newsletter', false) && strpos($_SERVER['REQUEST_URI'], '/baja-newsletter') === false) return;

    $email = sanitize_email($_GET['email'] ?? '');
    if (!$email) {
        wp_die('Email no válido.');
    }

    try {
        // Dar de baja en MailPoet
        $subscriber = \MailPoet\API\API::MP('v1')->getSubscriber($email);
        if ($subscriber) {
            \MailPoet\API\API::MP('v1')->unsubscribeSubscriber($subscriber['id']);
        }
    } catch (\Exception $e) {
        // Si no está en MailPoet, no es error
    }

    // Registrar la baja en user meta también
    $user = get_user_by('email', $email);
    if ($user) {
        update_user_meta($user->ID, 'newsletter_opt_out', true);
    }

    wp_die(
        '<h2>Baja confirmada</h2><p>Ya no recibirás más emails de ALQUIPRESS. <a href="' . home_url() . '">Volver a la web</a></p>',
        'Baja de newsletter',
        ['response' => 200]
    );
}
```

**Checklist RGPD obligatorio:**
- Casilla de opt-in no pre-marcada en el checkout
- Texto claro: "Acepto recibir comunicaciones comerciales. Puedes cancelar en cualquier momento."
- Enlace a Política de Privacidad en el formulario
- Link de baja en el footer de todos los emails
- No enviar emails de marketing a clientes que no dieron opt-in
- Emails transaccionales (confirmación de reserva, guía de llegada) **no requieren opt-in** — son emails de servicio

---

## 9. Checklist de implementación

```
FASE 1 — Infraestructura
[ ] Instalar MailPoet + MailPoet for WooCommerce
[ ] Configurar SMTP transaccional (Brevo recomendado)
[ ] Verificar dominio: SPF + DKIM + DMARC
[ ] Test de entrega: enviar desde WP Mail SMTP y verificar que llega en inbox (no spam)
[ ] Crear las 8 listas en MailPoet (nombres exactos para que el código PHP las encuentre)

FASE 2 — Listas y suscripción
[ ] Activar opt-in en el checkout (MailPoet → Ajustes → WooCommerce → Checkout)
[ ] Implementar hook de suscripción automática por zona y preferencias
[ ] Test: completar pedido de prueba → verificar que el cliente aparece en MailPoet
[ ] Crear formulario de captación para el footer (MailPoet → Formularios)
[ ] Colocar formulario en footer y landing pages clave

FASE 3 — Flujos automáticos
[ ] Diseñar plantilla HTML base en MailPoet (o usar alquipress_email_wrapper PHP)
[ ] Flujo 1: Email de confirmación → test con pedido de prueba
[ ] Flujo 2: Guía de llegada T-7 → test manual con booking creado para en 7 días
[ ] Flujo 3: Recordatorio T-2 con código de llaves → test manual
[ ] Flujo 4: Cortesía T+24h check-in → test activando estado in-progress
[ ] Flujo 5: Solicitud de reseña + cupón → test activando estado departure-review
[ ] Flujo 6: Recuperador 11 meses → test con pedido de fecha forzada
[ ] Flujo 7: Cumpleaños → test con usuario con guest_dob = hoy
[ ] Flujo 8: Alerta interna → test dejando checklist incompleto

FASE 4 — RGPD y deliverability
[ ] Registrar endpoint /baja-newsletter/
[ ] Verificar que el link de baja funciona desde el footer del email
[ ] Revisar que ningún email de marketing se envía sin opt-in
[ ] Test de spam: usar mail-tester.com para verificar puntuación (objetivo: 9/10+)
[ ] Confirmar que los registros DNS (SPF, DKIM, DMARC) están activos

FASE 5 — Monitorización
[ ] Configurar reporte semanal en MailPoet (tasa de apertura, clics, bajas)
[ ] KPI objetivo: tasa de apertura >40% (sector turismo), clics >5%
[ ] Revisar mensualmente qué emails tienen peor rendimiento y optimizar asunto/CTA
```

---

## 10. KPIs de referencia para el sector vacacional

| Métrica | Objetivo mínimo | Objetivo óptimo |
|---|---|---|
| Tasa de apertura (confirmación) | 60% | 80%+ |
| Tasa de apertura (guía de llegada) | 70% | 90%+ |
| Tasa de apertura (reseña) | 35% | 50%+ |
| Tasa de apertura (recuperador) | 20% | 35%+ |
| Conversión cupón recuperador | 5% | 12%+ |
| Tasa de baja (unsubscribe) | <0.5% | <0.2% |
| Puntuación spam (mail-tester.com) | 8/10 | 10/10 |

---

## 11. Evolución futura (v2)

- **WhatsApp Business API:** Los flujos clave (guía de llegada, código de llaves) tienen tasas de lectura del 98% vía WhatsApp vs ~70% por email. Integrar con Twilio o 360dialog para propiedades premium.
- **SMS de emergencia:** Para el código de acceso y alertas urgentes, SMS como canal de respaldo si el email no se abre en 4 horas.
- **Segmentación por idioma:** `guest_preferences → idioma_preferido` para enviar emails en inglés a clientes internacionales (Costa Blanca tiene alto % de turistas UK, DE, NL).
- **A/B testing de asuntos:** MailPoet Premium incluye tests A/B para optimizar las líneas de asunto de los emails de reseña y recuperación.
- **Upselling pre-llegada:** En el email T-7, incluir ofertas de extras: bolsa de bienvenida, alquiler de bicis, transfer desde aeropuerto. Alta conversión porque el cliente ya está en modo "viaje".

---

> **Autor:** Arquitectura ALQUIPRESS  
> **Última revisión:** Febrero 2026  
> **Módulo anterior:** [MÓDULO 05 — Sincronización iCal]  
> **Siguiente módulo:** [MÓDULO 07 — SEO Técnico y Rendimiento]
