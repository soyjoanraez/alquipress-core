# MÓDULO 02 — Sistema de Pagos: Stripe + Redsys (Segundo Cobro Automático)

> **Proyecto:** ALQUIPRESS  
> **Stack:** WordPress · WooCommerce · WooCommerce Bookings · WooCommerce Deposits  
> **Objetivo:** Cobro automático en dos fases sin intervención humana  
> **Pasarelas:** Stripe (principal) + Redsys (secundaria para mercado español)

---

## 1. Concepto: El flujo de doble cobro

El modelo de pago de ALQUIPRESS sigue el estándar del sector del alquiler vacacional: **depósito al reservar + saldo restante X días antes del check-in**, todo de forma completamente automática.

```
CLIENTE RESERVA
      │
      ▼
COBRO 1: 40% del total
(al confirmar la reserva)
      │
      ▼
Token de tarjeta guardado en Stripe
      │
      ▼
[Espera hasta D-7 antes del check-in]
      │
      ▼
COBRO 2: 60% restante
(automático, sin que el cliente intervenga)
      │
      ├── OK → Email "Pago completado" → Estancia confirmada
      └── FAIL → Email "Actualiza tu tarjeta" → Gestión manual
```

---

## 2. Herramientas necesarias

| Herramienta | Función | Coste aprox. |
|---|---|---|
| **WooCommerce Deposits** | Gestiona el split de pago 40/60 | ~$79/año (WooCommerce.com) |
| **WooCommerce Stripe** | Pasarela principal, tokenización | Gratuito (oficial) |
| **Redsys Gateway Pro** (José Conti) | Pasarela secundaria española | ~60€/año |
| **Server Cron Job** | Dispara cobros a la hora exacta | Incluido en hosting |

> ⚠️ **Importante:** NO usar el plugin gratuito de Redsys del repositorio para cobros automáticos. No soporta tokenización MIT (Merchant Initiated Transaction) y el banco rechazará el segundo cobro.

---

## 3. El problema central: PSD2 y 3D Secure

La normativa europea PSD2 obliga a autenticar al cliente (SMS/app bancaria) en cada compra. Esto hace imposible cobrar el segundo pago automáticamente... **a menos que se usen las excepciones reglamentadas:**

- **Stripe:** Gestiona esto automáticamente con su Payment Intents API. Cuando guarda la tarjeta, marca la transacción futura como MIT (Merchant Initiated Transaction) y la exime del 3DS.
- **Redsys:** Requiere que el banco del comerciante active explícitamente las **"Operaciones COF" (Credential on File)**. Sin esta activación, el segundo cobro fracasará siempre.

---

## 4. Configuración de Stripe (Pasarela Principal)

### 4.1 Requisitos previos

- Cuenta Stripe verificada con TPV activo en España.
- Plugin oficial `woocommerce-gateway-stripe` instalado y activado.
- Modo Live con claves API de producción configuradas.

### 4.2 Ajustes clave en WooCommerce → Pagos → Stripe

```
✅ Activar: "Guardar tarjetas de pago"
✅ Activar: "Pagos recurrentes automáticos"
✅ Modo de pago: "Intención de pago" (Payment Intent)
✅ Guardar método de pago: SIEMPRE (forzado por código, ver §6)
```

### 4.3 Cómo funciona la tokenización en Stripe

```
1. Cliente paga el 40% → Stripe crea un "Customer" con su ID único
2. Stripe guarda un PaymentMethod vinculado al Customer
3. En el cobro 2, WooCommerce usa ese PaymentMethod sin pedir datos de nuevo
4. Stripe etiqueta la operación como MIT → banco la aprueba sin 3DS
```

---

## 5. Configuración de Redsys (Pasarela Secundaria)

### 5.1 Trámite burocrático previo (OBLIGATORIO)

Antes de tocar nada en WordPress, el titular del TPV Virtual debe enviar este email a su banco o a soporte de Sermepa:

---

**Asunto:** Solicitud activación Pago por Referencia / Operaciones COF en TPV Virtual

> Buenos días,
>
> Solicito que activen en mi TPV Virtual (nº de comercio: **XXXXXXXX**) las siguientes funcionalidades:
>
> - **Pago por Referencia (Tokenización)**
> - **Operaciones COF (Credential on File)**
> - **MIT (Merchant Initiated Transactions)** para cobros diferidos automáticos
>
> El uso previsto es la automatización de cobros en plazos para reservas de alquiler vacacional. El cliente autoriza el primer cobro y acepta expresamente que se realizará un segundo cargo diferido en fecha programada.
>
> Quedo a su disposición para cualquier documentación adicional.

---

> ⏱️ **Plazo:** El banco suele tardar entre 1 y 2 semanas en activar estas funcionalidades. Planificarlo con antelación.

### 5.2 Configuración del plugin (Redsys Gateway Pro - José Conti)

```
✅ Activar: "Pago por referencia / Pago con un clic"
✅ Activar: "Operaciones COF"
✅ Nombre visible para el cliente: "Pago con Tarjeta (Banco Español)"
✅ Orden en checkout: 2 (después de Stripe)
✅ Modo de integración: "Redirect" (más estable para cobros diferidos)
```

---

## 6. Configuración de WooCommerce Deposits

### 6.1 Ajustes globales

Ruta: **WooCommerce → Ajustes → Productos → Depósitos**

```
Porcentaje de depósito: 40%
Cuándo cobrar el resto:  "X días antes de la fecha de inicio"
Días antes:              7
Método de pago balance:  "Misma pasarela usada en el depósito"
```

### 6.2 Por producto (sobreescribe el global si es necesario)

En la ficha de cada propiedad (WooCommerce Product → pestaña "Depósito"):

```
☑ Activar depósito para este producto
Tipo: Porcentaje
Valor: 40
Cuándo: 7 días antes del check-in (viene del global, editar si cada propiedad necesita diferente)
```

---

## 7. Código: Forzar guardado de tarjeta y avisos legales

Este snippet garantiza que el token se guarda **siempre**, eliminando el riesgo de que el cliente desmarque la casilla y el segundo cobro falle.

```php
<?php
/**
 * ALQUIPRESS — Módulo 02: Forzar tokenización y aviso legal en checkout
 * Añadir en plugin personalizado: /wp-content/plugins/alquipress-core/
 */

/**
 * 1. Forzar guardado de tarjeta cuando hay depósito
 * Elimina la opción de que el usuario NO guarde la tarjeta.
 */
add_filter('wc_deposits_force_save_card', '__return_true');

/**
 * 2. Forzar guardado en Stripe directamente (por si acaso el filtro anterior no es suficiente)
 */
add_filter('woocommerce_stripe_force_save_source', function($force, $order) {
    // Si el pedido tiene depósito, forzar guardado del token
    if (class_exists('WC_Deposits_Order_Manager')) {
        $has_deposit = WC_Deposits_Order_Manager::has_deposit($order->get_id());
        if ($has_deposit) return true;
    }
    return $force;
}, 10, 2);

/**
 * 3. Aviso legal en el resumen de pago del checkout
 * Informa al cliente de que se le cobrará el resto automáticamente.
 * Vital para evitar contracargos (chargebacks).
 */
add_filter('woocommerce_get_order_item_totals', function($total_rows, $order, $tax_display) {
    if (!class_exists('WC_Deposits_Order_Manager')) return $total_rows;
    
    if (WC_Deposits_Order_Manager::has_deposit($order->get_id())) {
        $balance_date = get_post_meta($order->get_id(), '_wc_deposits_payment_schedule', true);
        $aviso = 'El saldo restante (60%) se cargará automáticamente en su tarjeta 7 días antes del check-in.';
        
        $total_rows['alquipress_deposit_notice'] = [
            'label' => '⚠️ Aviso de pago automático:',
            'value' => $aviso,
        ];
    }
    return $total_rows;
}, 10, 3);

/**
 * 4. Registrar en el log del pedido cuando se guarda el token
 */
add_action('woocommerce_payment_token_added_to_order', function($order_id, $token_id, $token, $user_id) {
    $order = wc_get_order($order_id);
    if ($order) {
        $order->add_order_note(
            sprintf('Token de pago guardado para cobro automático. Token ID: %s | Gateway: %s',
                $token->get_id(),
                $token->get_gateway_id()
            )
        );
    }
}, 10, 4);
```

---

## 8. Cron real del servidor (Crítico para fiabilidad)

WP-Cron depende del tráfico web para ejecutarse. Si nadie visita la web a medianoche, el cobro no se dispara. Solución: cron real del servidor.

### 8.1 Desactivar WP-Cron nativo

En `wp-config.php`:
```php
define('DISABLE_WP_CRON', true);
```

### 8.2 Crear cron job real en el servidor

En cPanel / Plesk / terminal:
```bash
# Ejecutar cada 15 minutos — sustituir con la ruta real del servidor
*/15 * * * * php /var/www/html/wp-cron.php > /dev/null 2>&1

# O con WP-CLI (más limpio y recomendado)
*/15 * * * * cd /var/www/html && wp cron event run --due-now --allow-root > /dev/null 2>&1
```

### 8.3 Verificar que el cron de Deposits está registrado

```php
// Verificar en functions.php temporalmente — eliminar tras comprobar
add_action('init', function() {
    if (current_user_can('administrator')) {
        $scheduled = wp_next_scheduled('wc_deposits_process_payment_schedules');
        if ($scheduled) {
            error_log('Deposits cron activo: próxima ejecución ' . date('Y-m-d H:i:s', $scheduled));
        } else {
            error_log('ALERTA: Deposits cron NO está programado');
        }
    }
});
```

---

## 9. Gestión de fallos en el segundo cobro

### 9.1 Flujo de contingencia

```
COBRO 2 FALLA
      │
      ├── Stripe: Reintenta automáticamente (Smart Retries)
      │   └── 4 intentos en 8 días
      │
      ├── WooCommerce: Cambia estado → "Pago fallido"
      │
      ├── Email automático al cliente:
      │   "Tu pago falló. Actualiza tu tarjeta aquí [LINK]"
      │
      ├── Email automático al admin:
      │   "⚠️ Cobro fallido — Reserva #XXXX — Check-in en X días"
      │
      └── Si sigue sin pagar 48h antes:
          → Intervención manual del administrador
          → Posible cancelación de reserva
```

### 9.2 Código: Notificación de cobro fallido

```php
/**
 * Notificar al admin cuando falla el cobro automático del balance
 */
add_action('woocommerce_order_status_failed', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Solo actuar si es un pago de balance (segundo cobro)
    $is_balance = get_post_meta($order_id, '_wc_deposits_is_balance_order', true);
    if (!$is_balance) return;
    
    $parent_order_id = get_post_meta($order_id, '_wc_deposits_parent_order_id', true);
    $checkin_date = ''; // Obtener de booking meta
    
    $admin_email = get_option('admin_email');
    $subject = '⚠️ ALQUIPRESS: Cobro automático FALLIDO — Pedido #' . $parent_order_id;
    $message = sprintf(
        "El cobro automático del saldo restante ha fallado.\n\n" .
        "Reserva padre: #%s\n" .
        "Pedido de balance: #%s\n" .
        "Importe: %s\n\n" .
        "Acción requerida: Contactar al cliente o cancelar la reserva.\n\n" .
        "Ver reserva: %s",
        $parent_order_id,
        $order_id,
        $order->get_formatted_order_total(),
        admin_url('post.php?post=' . $parent_order_id . '&action=edit')
    );
    
    wp_mail($admin_email, $subject, $message);
}, 10);
```

---

## 10. Tabla comparativa de pasarelas

| Característica | Stripe (Principal) | Redsys (Secundaria) |
|---|---|---|
| **Comisión** | ~1.4% + 0.25€ | ~0.4% (según banco) |
| **Automatización 2º cobro** | ⭐⭐⭐⭐⭐ Nativa y fiable | ⭐⭐⭐ Requiere configuración bancaria |
| **Setup técnico** | API Keys inmediato | Solicitud al banco: 1-2 semanas |
| **Plugin** | Gratuito oficial | Premium obligatorio (~60€/año) |
| **UX cliente** | Paga en la web | Redirige al banco |
| **3DS / PSD2** | Gestionado automáticamente | Requiere activación COF en banco |
| **Smart Retries** | ✅ Sí (hasta 4 intentos) | ❌ No nativo |
| **Recomendado para** | Clientes internacionales y tech | Clientes que prefieren banca española |

---

## 11. Configuración del checkout (orden y presentación)

```
CHECKOUT → Métodos de pago visibles para el cliente:

[1] 💳 Pago con tarjeta (Stripe)     ← Desplegado por defecto
    ○ Visa ○ Mastercard ○ Amex
    [ ] Guardar tarjeta (oculto, siempre marcado)

[2] 🏦 Pago con tarjeta (Banco Español / Redsys)
    Serás redirigido a la web segura de tu banco.
```

**Aviso legal visible en el resumen del pedido:**
> ⚠️ *El saldo restante (60%) se cargará automáticamente en su tarjeta 7 días antes del check-in. Al confirmar el pedido acepta estos términos.*

---

## 12. Checklist de implementación

```
FASE 1 — Stripe
[ ] Instalar y configurar WooCommerce Stripe oficial
[ ] Activar guardado de tarjetas y Payment Intent
[ ] Verificar creación de Customer ID en Stripe Dashboard tras reserva de prueba
[ ] Añadir snippet de forzado de tokenización (§7)
[ ] Test: reserva completa con tarjeta de prueba Stripe (4242 4242 4242 4242)

FASE 2 — WooCommerce Deposits
[ ] Instalar y licenciar WooCommerce Deposits
[ ] Configurar split 40/60 global
[ ] Configurar disparo a D-7
[ ] Test: verificar que se crean dos pedidos (depósito + balance) correctamente
[ ] Test: verificar que el aviso legal aparece en el checkout

FASE 3 — Cron del servidor
[ ] Desactivar WP-Cron nativo en wp-config.php
[ ] Crear cron job real en el servidor (cada 15 min)
[ ] Verificar con WP-CLI que el cron de Deposits está programado

FASE 4 — Redsys (puede ir en paralelo mientras el banco tramita)
[ ] Solicitar activación COF/MIT al banco (email en §5.1)
[ ] Comprar e instalar Redsys Gateway Pro (José Conti)
[ ] Configurar plugin con referencia del TPV Virtual
[ ] Colocar Redsys como segunda opción en el checkout
[ ] Test en modo sandbox antes de producción

FASE 5 — Gestión de fallos
[ ] Añadir snippet de notificación de fallo al admin (§9.2)
[ ] Configurar emails de WooCommerce para pedidos fallidos
[ ] Definir protocolo operativo: ¿qué hace el equipo cuando falla un cobro?
[ ] Test: simular fallo de pago con tarjeta de prueba (4000 0000 0000 0002)

FASE 6 — Legal y UX
[ ] Revisar Términos y Condiciones: añadir cláusula de cobro automático diferido
[ ] Política de cancelación: indicar plazos y penalizaciones
[ ] Test de experiencia completa con reserva real de bajo importe
```

---

## 13. Evolución futura (v2)

Una vez estabilizado el sistema:

- **Stripe Radar:** Activar reglas antifraude personalizadas para reservas de alto importe.
- **Depósito de seguridad (fianza):** Implementar pre-autorización de Stripe (no captura) para la fianza de daños — esto es el **Módulo 07**.
- **Facturación automática:** Generación de facturas PDF en cada cobro y envío al cliente (Módulo a definir).
- **Multi-moneda:** Si se abren mercados fuera de España, activar presentación de precios en GBP/EUR según origen del visitante.

---

> **Autor:** Arquitectura ALQUIPRESS  
> **Última revisión:** Febrero 2026  
> **Módulo anterior:** [MÓDULO 01 — Registro de Viajeros SES]  
> **Siguiente módulo:** [MÓDULO 03 — Pipeline CRM (Kanban de Reservas)]
