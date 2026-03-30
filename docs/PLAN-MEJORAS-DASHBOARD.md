# Plan de Mejoras del Dashboard Alquipress

**Fecha:** Febrero 2025  
**Objetivo:** Análisis y propuestas de mejora para cada sección del dashboard, tanto en funcionalidad como en diseño/UX.

---

## 1. Resumen ejecutivo

El dashboard de Alquipress cubre el ciclo operativo de una agencia de alquiler vacacional: Panel → Propiedades → Reservas → Clientes → Propietarios → Finanzas → Pipeline. Este documento revisa **cada punto del flujo**, identifica brechas y propone mejoras priorizadas según buenas prácticas de la industria (TravelNest, Lodgify, BookingSync, Baymard UX Research) y el case study de HomeNow sobre property management dashboards.

### Pilares de UX de referencia

- **Navegación intuitiva**: De A a B en pocos clics
- **Acceso rápido**: Lo más usado a mano
- **Equilibrio visual**: Información suficiente sin sobrecarga
- **KPIs accionables**: Clic para profundizar

---

## 2. Panel principal (alquipress-dashboard)

### Estado actual

- 4 métricas: Total propiedades, Reservas activas, Ingresos del mes, Ocupación hoy
- Widget Salud Operativa (alertas: pagos pendientes, check-ins sin docs, propietarios sin IBAN, reservas sin contrato)
- Reservas recientes (tabla con propiedad, huésped, fechas, estado)
- Actividad reciente (pagos, check-ins/outs, nuevas reservas)
- Plantillas: Pencil (por defecto), Compacto, Executive
- Barra de búsqueda que redirige a productos (WordPress)

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Acciones rápidas desde KPIs** — Clic en “Reservas activas” → filtrar reservas activas; clic en “Ingresos del mes” → desglose | Alinear con tendencia de “deep-dive analytics” (Lodgify) |
| **Alta** | **Comparativa YoY** — Mostrar variación ingresos/ocupación vs mismo mes año anterior | KPIs típicos en BI de vacation rentals |
| **Media** | **Mini calendario contextual** — Panel lateral con próximos 7 días (check-ins/outs destacados) | TravelNest muestra “upcoming bookings” muy destacados |
| **Media** | **Reservas recientes con mini acción** — Botón “Ver” o enlace directo al pedido sin salir de contexto | Reducir clics para tareas frecuentes |
| **Media** | **Empty states mejorados** — Ilustración + CTA claro cuando no hay reservas/actividad | UX research Baymard: empty states son críticos |
| **Baja** | **Selector de período** — Ver métricas de esta semana, este mes, trimestre | Flexibilidad para análisis |
| **Baja** | **Atajos de teclado** — N (nueva reserva), B (reservas), P (propiedades) | Power users |

### Diseño

- Revisar contraste de los badges de variación (ap-change-success, ap-change-info)
- Considerar gráfico sparkline en las métricas (línea mini de últimos 7 días)

---

## 3. Propiedades (alquipress-properties)

### Estado actual

- Header con título y buscador
- Filtros: estado, precio, población, zona, características, habitaciones, baños
- Grid de tarjetas: imagen, título, ubicación, iconos (camas, baños, plazas), ocupación
- Enlace a editor de propiedad (alquipress-edit-property)

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Vista de lista** — Alternativa al grid para comparar muchas propiedades | Patrón común en PMS |
| **Alta** | **Filtros guardados / vistas** — “Solo Denia”, “Sin fotos”, “Ocupación &lt; 50%” | Ahorro de tiempo operativo |
| **Alta** | **Ordenación** — Por precio, ocupación, fecha modificación, ingresos | Necesario para gestión eficiente |
| **Media** | **Acciones masivas** — Seleccionar varias y editar estado, exportar, duplicar | Escala operativa |
| **Media** | **Badge de alertas por propiedad** — Sin fotos, sin precio, sin disponibilidad | Extensión de salud operativa a nivel propiedad |
| **Media** | **Búsqueda por referencia interna** | Campo clave en operación diaria |
| **Baja** | **Mapa de propiedades** — Ubicación en mapa (si hay lat/lon) | Muy valorado en herramientas tipo Kyero |
| **Baja** | **Vista calendario** — Disponibilidad global de todas las propiedades | Complementaria al grid |

### Diseño

- Tarjetas: asegurar que el “Call to action” (Ver/Editar) sea siempre visible sin hover en móvil
- Filtros colapsables en móvil con indicador de filtros activos

---

## 4. Editor de propiedad (alquipress-edit-property)

### Estado actual

- Layout Pencil: header con ref, estado, acciones; quick stats (habitaciones, baños, plazas, superficie, valoración, precio/noche, limpieza, ocupación)
- Tabs: Overview, Calendario, Más campos editables
- Panel WC Bookings integrado (Datos del producto, Reservas, Disponibilidad, Costes)
- Enlaces a edición nativa de WordPress

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Breadcrumb** — Panel → Propiedades → [Nombre propiedad] | Navegación clara (UX HomeNow) |
| **Alta** | **Resumen de próximas reservas** — Widget con check-ins/outs de los próximos 14 días | Contexto operativo inmediato |
| **Media** | **Histórico de cambios** — Últimas modificaciones (precio, disponibilidad) | Trazabilidad |
| **Media** | **Indicadores de completitud** — % de campos obligatorios cumplidos | Reducir propiedades “incompletas” |
| **Media** | **Pestaña Documentos** — Contratos tipo, normas de la casa, etc. por propiedad | SES/legal |
| **Baja** | **Comparativa con similar** — “Propiedades similares en la zona tienen ocupación X” | Insights de pricing |

### Diseño

- Quick stats ya mejorados (precio rango, limpieza); revisar que el scroll horizontal no oculte información en móvil

---

## 5. Reservas (alquipress-bookings)

### Estado actual

- Resumen: KPIs (activas, check-ins semana, ingresos mes, check-outs semana), “Requieren atención”, reservas recientes
- Tabs: Resumen, Pipeline, Calendario, Nueva reserva, Notificaciones, Config. reservas
- Integración WC Bookings para calendario, crear reserva, notificaciones

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Filtros por período** — Ver reservas de este mes, próximos 30 días, etc. | Esencial para operación diaria |
| **Alta** | **“Requieren atención” como primera sección** — Arriba de KPIs, más visible | Priorizar lo urgente (HomeNow) |
| **Alta** | **Enlace directo a Pipeline desde cada reserva** — No depender de tab | Flujo más corto |
| **Alta** | **Búsqueda de reservas** — Por huésped, propiedad, ref pedido | Acceso rápido a información |
| **Media** | **Vista por propiedad** — Agrupar reservas por inmueble | Útil para equipos que gestionan por zona |
| **Media** | **Exportar reservas** — CSV/Excel de reservas en rango | Reporting y limpieza |
| **Media** | **Recordatorio visual** — “3 reservas con check-in mañana” siempre visible | TravelNest destaca upcoming |
| **Baja** | **Integración canal** — Mencionar reservas de Airbnb/Booking si se integran en futuro | Escalabilidad |

### Diseño

- Unificar estética entre Resumen Alquipress y vistas WC Bookings (calendario, etc.)
- Tab “Calendario” como posible vista por defecto para algunos roles

---

## 6. Clientes (alquipress-clients)

### Estado actual

- Tabla con nombre, email, teléfono, última estancia, documentación, valoración
- Filtros: nombre, fechas de estancia, inmueble
- Enlaces a perfil y editor de huésped

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Búsqueda por email/teléfono** — Campo único que busque en ambos | Evitar duplicados y encontrar rápido |
| **Alta** | **Indicador de huésped recurrente** — Badge “2ª reserva”, “Cliente frecuente” | Valor para marketing y trato personalizado |
| **Media** | **Segmentación** — Por gasto total, número de estancias, última visita | Base para campañas y ofertas |
| **Media** | **Historial de comunicaciones** — Últimos emails/SMS enviados | Contexto en atención al cliente |
| **Media** | **Exportar clientes** — Para GDPR, campañas externas | Requisito operativo |
| **Baja** | **Sugerencia de reunificación** — Detectar posibles duplicados (mismo email, nombre similar) | Calidad de datos |

### Diseño

- Tabla responsiva con prioridad de columnas en móvil (nombre, última estancia, acción)

---

## 7. Propietarios (alquipress-owners)

### Estado actual

- “Requieren atención”: sin IBAN, sin propiedades, contacto incompleto
- Top propietarios por ingresos
- Tarjetas con nombre, propiedades, ingresos, enlace a ficha

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Acción directa desde alertas** — “Completar IBAN” abre modal o enlace contextual | Reducir fricción |
| **Alta** | **Búsqueda y filtros** — Por nombre, población, número de propiedades | Escalabilidad |
| **Media** | **Próximos pagos a propietarios** — Widget con vencimientos | Flujo financiero |
| **Media** | **Comunicación reciente** — Último contacto con cada propietario | Relación comercial |
| **Baja** | **Dashboard propietario preview** — Cómo ve su portal el propietario | Soporte y formación |

### Diseño

- Cards de “requieren atención” más prominentes (color, icono)
- Lista/tabla alternativa para propietarios con muchas propiedades

---

## 8. Finanzas (alquipress-finanzas)

### Estado actual

- KPIs: Ingresos brutos mes, Saldos pendientes, Fianzas retenidas
- Próximos cobros automáticos (tabla)
- Depende de tablas `apm_payment_schedule` y `apm_security_deposits`

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Gráfico de ingresos** — Evolución últimos 6-12 meses | Visión temporal (Chart.js ya usado en Informes) |
| **Alta** | **Desglose por estado** — Ingresos por completed, deposito-ok, etc. | Detalle operativo |
| **Alta** | **Filtro de período** — Mes actual, trimestre, año | Flexibilidad reporting |
| **Media** | **Próximos cobros con acción** — Marcar como cobrado, reprogramar | Gestión activa |
| **Media** | **Fianzas: detalle por reserva** — Qué fianzas están retenidas y por qué pedido | Transparencia |
| **Media** | **Enlace a Contabilidad** — Si está activa | Flujo integrado |
| **Baja** | **Exportar para gestoría** — CSV/Excel compatible con software contable | Integración |

### Diseño

- Colores consistentes (verde ingresos, azul pendiente, violeta fianzas)
- Tabla de próximos cobros con estados visuales (próximo, vencido, cobrado)

---

## 9. Pipeline (alquipress-pipeline)

### Estado actual

- Kanban por estado de pedido (drag & drop)
- Tab “Cobros” con pipeline de cobros (payment-pipeline)
- Estados: Pendiente, Depósito OK, En curso, etc.

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Filtro por propiedad/periodo** — Ver solo reservas de una zona o rango de fechas | Gestión por equipos/zonas |
| **Alta** | **Indicadores en columnas** — Total € en cada columna | Visión financiera rápida |
| **Media** | **Vista compacta** — Más tarjetas visibles, menos detalle | Para overview rápido |
| **Media** | **Acciones rápidas en tarjeta** — Enviar recordatorio, marcar pagado, sin abrir pedido | Reducir clics |
| **Media** | **Recordatorio de SLA** — Resaltar pedidos pendientes &gt; X días | Salud operativa |
| **Baja** | **Vista por propietario** — Agrupar por propietario en lugar de estado | Caso de uso alternativo |

### Diseño

- Columnas con scroll horizontal en pantallas pequeñas
- Feedback visual al soltar (animación, confirmación)

---

## 10. Informes (alquipress-reports)

### Estado actual

- Gráficos Chart.js: ingresos, ocupación, top clientes, top propiedades
- Módulo advanced-reports

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Comparativa año anterior** — Todos los gráficos con línea YoY | Benchmark estándar |
| **Alta** | **Selector de rango de fechas** — Personalizar período | Flexibilidad |
| **Media** | **Exportar gráficos** — PNG/PDF para informes a propietarios | Comunicación |
| **Media** | **Precio medio por noche** — KPI típico en vacation rental BI | BookingSync, Hostfully |
| **Media** | **Ocupación por propiedad** — Ranking y evolución | Análisis por inmueble |
| **Baja** | **Predicción / tendencia** — Proyección basada en histórico | Valor diferencial |

### Diseño

- Gráficos responsivos
- Tooltips en hover con valores exactos

---

## 11. Salud operativa (operational-health)

### Estado actual

- Alertas: Pagos pendientes, Check-ins sin documentación, Propietarios sin IBAN, Reservas sin contrato
- Prioridades: critical, high, medium
- Enlaces a acciones

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Expandir desde Panel** — Ver lista sin salir del dashboard | Acceso rápido |
| **Alta** | **Configurar umbrales** — Días para “crítico” en pagos, check-ins sin docs | Adaptabilidad |
| **Media** | **Marcar como “visto”** — No repetir alerta ya gestionada | Ruido reducido |
| **Media** | **Nuevas alertas** — Propiedades sin fotos, sin descripción, precio fuera de rango | Cobertura |
| **Baja** | **Historial de alertas resueltas** | Auditoría |

### Diseño

- Colores por prioridad (rojo critical, naranja high, amarillo medium)
- Colapsar por tipo cuando hay muchas

---

## 12. Sidebar y navegación global

### Estado actual

- Sidebar fijo 256px con iconos SVG
- Items: Panel, Propiedades, Reservas, Clientes, Propietarios, Finanzas, Contabilidad, Informes, Pipeline, Comunicación, Ajustes
- Submenús expandibles
- Avatar e iniciales del usuario
- Modo fullscreen (oculta barra WP y menú WP)

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Indicador de alertas** — Badge en Reservas si hay “requieren atención”; en Propietarios si hay sin IBAN | Visibilidad de lo urgente |
| **Media** | **Sidebar colapsable** — Solo iconos para más espacio en contenido | Flexibilidad |
| **Media** | **Atajo “Nueva reserva”** — Siempre visible en sidebar o header | Acción frecuente |
| **Media** | **Búsqueda global** — Buscar propiedades, reservas, clientes desde un único campo | Acceso rápido (patrón común) |
| **Baja** | **Favoritos / accesos rápidos** — Pin de secciones más usadas | Personalización |
| **Baja** | **Modo oscuro** — Opción en Ajustes | Preferencia de usuario |

### Diseño

- Revisar estado activo (ap-owners-nav-item.is-active) para contraste
- Ícono actual para “Limpieza” u otros conceptos: considerar set coherente (Lucide, Heroicons)

---

## 13. Ajustes (alquipress-settings)

### Estado actual

- Tabs: General, Reservas, Pagos, Email y notificaciones, Legal, Equipo, Avanzado
- Tabla de módulos (activar/desactivar)
- Plantilla de dashboard (Pencil/Compacto/Executive)

### Mejoras propuestas

| Prioridad | Mejora | Motivación |
|-----------|--------|------------|
| **Alta** | **Búsqueda en módulos** — Filtrar por nombre | Muchos módulos |
| **Media** | **Descripción corta de cada módulo** — Tooltip o texto bajo el nombre | Decisión informada |
| **Media** | **Vista previa de plantilla** — Antes de aplicar | Evitar cambios ciegos |
| **Baja** | **Importar/Exportar configuración** — Backup y replicar entre instalaciones | Implementación multi-site |
| **Baja** | **Log de auditoría** — Cambios en ajustes | Seguridad |

---

## 14. Otros módulos (Comunicación, Campañas, Kyero, SES, etc.)

Revisión rápida:

- **Comunicación**: Consolidar histórico y envíos; mejorar filtros.
- **Campañas de email**: Segmentación desde Clientes; preview antes de enviar.
- **Kyero / SES**: Mantener flujos actuales; documentar pasos para nuevos usuarios.
- **Contabilidad**: Enlace claro desde Finanzas; resumen de movimientos en dashboard si está activo.

---

## 15. Priorización sugerida (Roadmap)

### Fase 1 — Quick wins (1–2 sprints)

1. Acciones rápidas desde KPIs del Panel (clic → filtro)
2. “Requieren atención” más prominente en Reservas
3. Badges de alertas en sidebar (Reservas, Propietarios)
4. Búsqueda en Clientes por email/teléfono
5. Breadcrumb en editor de propiedad

### Fase 2 — Valor operativo (2–3 sprints)

1. Filtros guardados / vistas en Propiedades
2. Vista de lista en Propiedades + ordenación
3. Gráfico de ingresos en Finanzas + filtro período
4. Filtro por período en Reservas
5. Expandir alertas de salud operativa en Panel

### Fase 3 — Profundidad (3–4 sprints)

1. Comparativa YoY en Panel e Informes
2. Indicadores en columnas del Pipeline
3. Segmentación de clientes
4. Próximos pagos a propietarios en su dashboard
5. Búsqueda global en header/sidebar

### Fase 4 — Diferenciación

1. Mini calendario contextual en Panel
2. Mapa de propiedades
3. Predicción/tendencia en Informes
4. Modo oscuro
5. Atajos de teclado

---

## 16. Métricas de éxito sugeridas

- **Tiempo para tarea frecuente**: Ej. “Ver reservas activas” — de X clics a 1.
- **Tasa de uso de filtros/vistas**: Si se implementan, medir adopción.
- **Resolución de alertas**: Tiempo medio desde alerta hasta acción.
- **NPS o feedback cualitativo**: Encuesta periódica a usuarios del dashboard.

---

## 17. Referencias

- [TravelNest Management Dashboard](https://travelnest.com/features/management-dashboard)
- [BookingSync Key Data Dashboard](https://www.bookingsync.com/)
- [Lodgify Product Updates 2024](https://www.lodgify.com/blog/)
- [Baymard Travel Accommodations UX Research](https://baymard.com/research/travel-accommodations)
- [HomeNow Property Management Dashboard UX Case Study](https://medium.com/@turnbulleric/creating-a-modern-property-management-dashboard-a-ux-case-study-of-homenow-io-b591427c8ed6)
- [Hostfully STR Business Intelligence Tools](https://www.hostfully.com/blog/str-business-intelligence-tools/)
