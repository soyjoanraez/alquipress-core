# MÓDULO 01 — Registro de Viajeros (SES HOSPEDAJES)

> **Proyecto:** ALQUIPRESS  
> **Stack:** WordPress · WooCommerce · WooCommerce Bookings · ACF Pro  
> **Prioridad:** Alta — Obligación legal (Ley Orgánica 4/2015)  
> **Plazo máximo de comunicación:** 24h desde el check-in

---

## 1. ¿Qué es y por qué es obligatorio?

En España, cualquier establecimiento de alojamiento turístico está **obligado por ley** a comunicar los datos de los viajeros a las Fuerzas y Cuerpos de Seguridad del Estado. La plataforma oficial es:

👉 [https://hospedajes.ses.mir.es](https://hospedajes.ses.mir.es)

El incumplimiento está tipificado como **infracción grave** en la Ley de Seguridad Ciudadana (multas de 600€ a 30.000€).

---

## 2. Enfoque elegido: XML Automático desde WordPress

De las tres opciones posibles (manual, PMS externo, API de intermediario), la arquitectura de ALQUIPRESS implementa la **Opción 1: generación de XML desde WordPress** con carga masiva en la plataforma SES.

**Justificación:**
- 100% bajo nuestro control, sin costes recurrentes de terceros.
- Encaja nativamente con WooCommerce Bookings y nuestra estructura de datos.
- Escalable: si el volumen crece, la migración a API intermediario es sencilla porque los datos ya están bien estructurados.

---

## 3. Arquitectura del Módulo

```
CHECKOUT (Huésped rellena datos)
        │
        ▼
ACF Repeater en Order Meta
(1 fila por viajero)
        │
        ▼
Admin Dashboard "Partes SES"
  ├── Listado: pendiente / generado / enviado
  └── Botón "Generar XML (Alta masiva)"
        │
        ▼
Descarga archivo XML  →  Carga manual en hospedajes.ses.mir.es
        │
        ▼
Estado actualizado en el pedido: ✅ ENVIADO
```

---

## 4. Datos obligatorios por viajero (campos SES)

| Campo | Nombre interno | Tipo ACF | Validación |
|---|---|---|---|
| Nombre | `ses_nombre` | Text | Requerido |
| Primer apellido | `ses_apellido1` | Text | Requerido |
| Segundo apellido | `ses_apellido2` | Text | Opcional |
| Sexo | `ses_sexo` | Select (`M` / `F`) | Requerido |
| Fecha de nacimiento | `ses_fecha_nacimiento` | Date Picker | ISO `YYYY-MM-DD` |
| Nacionalidad | `ses_nacionalidad` | Select (ISO 3166-1 alpha-3) | Requerido |
| Tipo de documento | `ses_tipo_documento` | Select (`NIF`,`NIE`,`PAS`,`OTRO`) | Requerido |
| Número de documento | `ses_num_documento` | Text | Requerido |
| Fecha de expedición | `ses_fecha_expedicion` | Date Picker | ISO `YYYY-MM-DD` |
| País de expedición | `ses_pais_expedicion` | Select (ISO 3166-1 alpha-3) | Requerido |
| Rol en la reserva | `ses_rol` | Hidden (fijo `VI`) | Siempre `VI` |

> **Nota:** El rol siempre será `VI` (Viajero). No exponemos selector al cliente para evitar errores.

---

## 5. Datos de la reserva (cabecera del parte)

| Campo | Fuente | Notas |
|---|---|---|
| Fecha entrada | `_booking_start` (WC Bookings) | Convertir a `YYYY-MM-DD` |
| Fecha salida | `_booking_end` (WC Bookings) | Convertir a `YYYY-MM-DD` |
| Tipo de pago | `ses_tipo_pago` | `PLATF` (plataforma online) |
| Referencia del alojamiento | `licencia_turistica` (ACF en producto) | Campo EGVT/VT |

---

## 6. Implementación técnica — Paso a paso

### 6.1 Campos ACF en el Checkout

Añadimos un **Repeater ACF** en el Order Meta para capturar los datos de cada viajero. Se muestra en la página de checkout como sección adicional "Datos de viajeros".

**Nombre del Repeater:** `viajeros_ses`  
**Ubicación ACF:** Group asociado a `woocommerce_order`

```php
// functions.php o plugin personalizado
// Hook para añadir los campos al checkout de WooCommerce

add_action('woocommerce_after_order_notes', 'alquipress_campos_viajeros_checkout');

function alquipress_campos_viajeros_checkout($checkout) {
    echo '<div id="alquipress-viajeros">';
    echo '<h3>' . __('Datos de los viajeros (obligatorio por ley)', 'alquipress') . '</h3>';
    // Renderizar formulario dinámico (JS añade filas por cada huésped)
    // Los campos se guardan vía AJAX en order_meta al confirmar pedido
    echo '</div>';
}
```

### 6.2 Guardado en Order Meta

```php
add_action('woocommerce_checkout_update_order_meta', 'alquipress_guardar_viajeros');

function alquipress_guardar_viajeros($order_id) {
    if (!empty($_POST['viajeros_ses'])) {
        $viajeros = array_map('alquipress_sanitizar_viajero', $_POST['viajeros_ses']);
        update_post_meta($order_id, '_viajeros_ses', $viajeros);
        update_post_meta($order_id, '_ses_estado', 'pendiente');
    }
}

function alquipress_sanitizar_viajero($viajero) {
    return [
        'nombre'           => sanitize_text_field($viajero['nombre']),
        'apellido1'        => sanitize_text_field($viajero['apellido1']),
        'apellido2'        => sanitize_text_field($viajero['apellido2'] ?? ''),
        'sexo'             => in_array($viajero['sexo'], ['M','F']) ? $viajero['sexo'] : 'M',
        'fecha_nacimiento' => alquipress_fecha_iso($viajero['fecha_nacimiento']),
        'nacionalidad'     => sanitize_text_field($viajero['nacionalidad']),
        'tipo_documento'   => in_array($viajero['tipo_documento'], ['NIF','NIE','PAS','OTRO']) 
                              ? $viajero['tipo_documento'] : 'PAS',
        'num_documento'    => sanitize_text_field($viajero['num_documento']),
        'fecha_expedicion' => alquipress_fecha_iso($viajero['fecha_expedicion']),
        'pais_expedicion'  => sanitize_text_field($viajero['pais_expedicion']),
        'rol'              => 'VI', // Siempre fijo
    ];
}

function alquipress_fecha_iso($fecha_input) {
    // Acepta DD/MM/YYYY o YYYY-MM-DD, siempre devuelve YYYY-MM-DD
    $dt = DateTime::createFromFormat('d/m/Y', $fecha_input) 
          ?: DateTime::createFromFormat('Y-m-d', $fecha_input);
    return $dt ? $dt->format('Y-m-d') : '';
}
```

### 6.3 Generador de XML

```php
function alquipress_generar_xml_ses($order_id) {
    $order        = wc_get_order($order_id);
    $viajeros     = get_post_meta($order_id, '_viajeros_ses', true);
    $booking      = alquipress_get_booking_from_order($order_id);
    $producto_id  = alquipress_get_product_from_order($order_id);
    $licencia     = get_field('licencia_turistica', $producto_id);

    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><COMUNICACION/>');
    $parte = $xml->addChild('PARTE_ENTRADA');
    $parte->addChild('ESTABLECIMIENTO', esc_xml($licencia));
    $parte->addChild('FECHA_ENTRADA', get_post_meta($booking->ID, '_booking_start', true));
    $parte->addChild('FECHA_SALIDA',  get_post_meta($booking->ID, '_booking_end', true));
    $parte->addChild('TIPO_PAGO', 'PLATF');

    $lista = $parte->addChild('VIAJEROS');
    foreach ($viajeros as $v) {
        $viajero = $lista->addChild('VIAJERO');
        $viajero->addChild('NOMBRE',            esc_xml($v['nombre']));
        $viajero->addChild('APELLIDO1',          esc_xml($v['apellido1']));
        $viajero->addChild('APELLIDO2',          esc_xml($v['apellido2']));
        $viajero->addChild('SEXO',               $v['sexo']);
        $viajero->addChild('FECHA_NACIMIENTO',   $v['fecha_nacimiento']);
        $viajero->addChild('NACIONALIDAD',       esc_xml($v['nacionalidad']));
        $viajero->addChild('TIPO_DOCUMENTO',     $v['tipo_documento']);
        $viajero->addChild('NUM_DOCUMENTO',      esc_xml($v['num_documento']));
        $viajero->addChild('FECHA_EXPEDICION',   $v['fecha_expedicion']);
        $viajero->addChild('PAIS_EXPEDICION',    esc_xml($v['pais_expedicion']));
        $viajero->addChild('ROL',                'VI');
    }

    return $xml->asXML();
}
```

### 6.4 Estado del parte (flujo)

```
_ses_estado en order_meta:

pendiente  →  xml_generado  →  enviado  →  ok
    │              │               │
    │          (descarga)     (confirmado
    │                          en SES)
    └── Alerta si han pasado 20h y sigue "pendiente"
```

Añadimos una columna personalizada en la lista de Pedidos de WooCommerce con el estado actual del parte.

---

## 7. Dashboard Admin "Partes SES"

Creamos una **página de administración personalizada** (`admin.php?page=alquipress-ses`) con:

- Tabla de reservas próximas/activas con su estado SES.
- Filtros: Pendiente / Generado / Enviado.
- Botón por fila: **"Generar XML"** → descarga el archivo.
- Botón: **"Marcar como enviado"** → actualiza el estado.
- Indicador visual de alerta (rojo) si la reserva tiene check-in en menos de 4h y el parte sigue pendiente.

```php
add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'Partes SES HOSPEDAJES',
        'Partes SES',
        'manage_woocommerce',
        'alquipress-ses',
        'alquipress_render_ses_dashboard'
    );
});
```

---

## 8. Notificaciones automáticas

### Email al administrador — Recordatorio pendiente

Se dispara con un cron de WordPress **20 horas después del check-in** si el parte sigue en estado `pendiente`.

```php
add_action('alquipress_ses_recordatorio', function($order_id) {
    $estado = get_post_meta($order_id, '_ses_estado', true);
    if ($estado === 'pendiente') {
        wp_mail(
            get_option('admin_email'),
            '⚠️ ALQUIPRESS: Parte SES pendiente — Pedido #' . $order_id,
            'Tienes 4 horas para enviar el parte a SES HOSPEDAJES. ' .
            admin_url('admin.php?page=alquipress-ses')
        );
    }
});
```

---

## 9. GDPR y retención de datos

Los datos de identificación de viajeros son **datos personales sensibles**. Aplicar:

- Almacenamiento cifrado en `order_meta` (no en texto plano si el hosting lo permite).
- **NO guardar** número de tarjeta, CVV ni datos bancarios (no los necesitamos para SES).
- Política de retención: eliminar los datos de viajeros de la base de datos **pasados 3 años** (obligación legal de conservación).
- Añadir cláusula específica en la política de privacidad de la web.

---

## 10. Checklist de implementación

```
FASE 1 — Estructura de datos
[ ] Crear ACF Group "Datos Viajeros SES" en woocommerce_order
[ ] Repeater "viajeros_ses" con todos los subcampos definidos en §4
[ ] Añadir campo "licencia_turistica" al CPT producto (si no existe)
[ ] Añadir meta "_ses_estado" con valor inicial "pendiente"

FASE 2 — Frontend checkout
[ ] Formulario dinámico de viajeros en checkout (JS para añadir/quitar filas)
[ ] Validación JS en tiempo real (fecha, tipo doc, campos requeridos)
[ ] Hook de guardado en order_meta con sanitización completa
[ ] Test: reserva de prueba con datos reales

FASE 3 — Generador XML
[ ] Función alquipress_generar_xml_ses() según esquema oficial SES
[ ] Endpoint admin AJAX para descarga del XML
[ ] Test: validar XML generado contra plantilla oficial del Ministerio

FASE 4 — Dashboard Admin
[ ] Página admin "Partes SES" (submenú de WooCommerce)
[ ] Tabla de reservas con columna de estado SES
[ ] Botones: "Generar XML" y "Marcar como enviado"
[ ] Indicadores de alerta visual (reservas urgentes)

FASE 5 — Automatización y alertas
[ ] Cron WP Cron (o sistema cron real del servidor) para alertas a 20h
[ ] Email de recordatorio al administrador
[ ] Log de acciones en order notes de WooCommerce

FASE 6 — GDPR
[ ] Revisar política de privacidad (añadir cláusula SES)
[ ] Configurar eliminación automática de datos tras 3 años
[ ] Test de seguridad: verificar que datos no son accesibles públicamente
```

---

## 11. Evolución futura (v2)

Cuando el volumen de reservas justifique la inversión:

- **Integración con API intermediario** (seshospedajes.es u otro proveedor certificado) para envío 100% automático sin intervención humana.
- **Integración con Chekin.io** como solución llave en mano con firma digital de contrato + registro SES en un solo paso.
- **Conexión directa con SES** si el Ministerio habilita API oficial pública.

---

> **Autor:** Arquitectura ALQUIPRESS  
> **Última revisión:** Febrero 2026  
> **Siguiente módulo:** [MÓDULO 02 — Pasarela Redsys (segundo pago)]
