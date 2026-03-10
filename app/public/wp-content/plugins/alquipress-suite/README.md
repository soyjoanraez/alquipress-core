# ALQUIPRESS Performance & Security Suite

Plugin avanzado de optimización para la plataforma ALQUIPRESS.

## 🚀 Funcionalidades Incluidas (v1.0.0 - MVP)

### 1. WPO (Web Performance Optimization)
- **Lazy Load Avanzado**: Mejora del lazy load nativo con `decoding="async"`.
- **Lazy Load para Google Maps**: Los mapas de ACF se cargan solo cuando entran en el viewport, ahorrando >500KB de carga inicial.
- **Script Defer**: Carga diferida para MailPoet y Scripts de Bookings para no bloquear el renderizado.

### 2. Image Optimizer
- **Conversión WebP Automática**: Genera una versión `.webp` de cada imagen subida (y sus miniaturas).
- **Servicio Inteligente**: Sirve automáticamente la versión WebP si el navegador del usuario lo soporta.
- **Gestión de Calidad**: Ajuste automático al 82% para equilibrio entre peso y nitidez.

### 3. Security Module
- **Honeypot para Login**: Campo invisible que bloquea bots automáticamente sin necesidad de Captcha visual.
- **XML-RPC Protection**: Desactiva XML-RPC para prevenir ataques de fuerza bruta.
- **Audit Log Ready**: Infraestructura preparada para registrar cambios en datos bancarios de propietarios.

## 🛠️ Instalación

1. El plugin ya está en `/wp-content/plugins/alquipress-suite/`.
2. Ve al escritorio de WordPress -> Plugins.
3. Activa **ALQUIPRESS Performance & Security Suite**.
4. Accede a la configuración en **ALQUIPRESS -> Performance & Security**.

## 🏗️ Arquitectura
- **PSR-4 Autoloading**: Código limpio y organizado por namespaces.
- **Estructura Modular**: Cada funcionalidad es independiente y se puede activar/desactivar desde el panel.
- **Zero Dependencies**: No requiere Composer ni librerías externas para el núcleo básico, maximizando la compatibilidad.
