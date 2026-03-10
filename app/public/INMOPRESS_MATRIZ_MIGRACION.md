# Matriz de Migracion Inmopress -> Alquipress

Fecha: 2026-02-06  
Origen funcional: `/Users/joanraez/Downloads/inmopress-documentacion/*`  
Destino tecnico: `/Users/joanraez/Local Sites/alquipress/app/public/*`

## Criterio

- `Estado`: `Implementado`, `Parcial`, `No implementado`
- `Prioridad`: `P0` (bloqueante), `P1` (alto impacto), `P2` (mejora)
- `Esfuerzo`: `S` (1-3 dias), `M` (4-10 dias), `L` (2+ semanas)

## Matriz principal

| Area | Requisito Inmopress | Estado actual | Brecha | Implementacion propuesta | Prioridad | Esfuerzo | Dependencias | Entregable verificable |
|---|---|---|---|---|---|---|---|---|
| Arquitectura | Suite modular equivalente a `inmopress-*` | Parcial | Nombres/alcance difieren (`alquipress-*` enfocado vacacional) | Mantener modularidad actual y crear modulos CRM inmobiliario nuevos en `alquipress-core/includes/modules/inmopress-*` | P0 | M | Base plugin activa | Lista de modulos nuevos en ajustes + carga sin errores |
| Core datos | 8 CPT CRM (`impress_property`, `impress_client`, `impress_lead`, `impress_event`, `impress_owner`, `impress_agent`, `impress_agency`, `impress_promotion`) | No implementado | Solo existen `product`, `propietario`, `alquipress_comm` | Registrar CPT nuevos con soporte REST/admin y migracion progresiva desde `product`/users | P0 | L | Decidir estrategia de coexistencia con WooCommerce | `wp post-type list` incluye los 8 CPT |
| Taxonomias SEO | `impress_city`, `impress_area`, `impress_property_type`, `impress_operation`, `impress_feature` | Parcial | Hay `poblacion`, `zona`, `caracteristicas` | Renombrar o mapear taxonomias actuales + crear faltantes `operation/type` | P0 | M | Definicion URL final | `wp taxonomy list` con taxonomias objetivo y archivo de mapeo |
| ACF estructura | 188 campos/27 grupos orientados a CRM inmobiliario | Parcial | Campos actuales son vacacional/bookings | Crear grupos ACF por CPT Inmopress (JSON versionado) y script de migracion | P0 | L | Definir MVP de campos | Carpeta ACF JSON nueva + import sin conflictos |
| Relaciones | Modelo bidireccional y relacion propietario-propiedad por referencia | Parcial | Hoy se usa Relationship por ID | Implementar `owner_ref` + resolver/cachear IDs en `acf/save_post` | P1 | M | Estandar de referencia unica | Hook funcionando + tabla/listado coherente en owner profile |
| Roles/permisos | Roles `agencia/agente/trabajador/cliente` + capacidades | No implementado | Solo roles WP/Woo por defecto | Crear modulo `roles-permissions` con `add_role`, caps y asignacion por entidad | P0 | M | Definir matriz de permisos | `wp role list` incluye roles + pruebas de acceso |
| Multi-tenant | Scope por `agency_id` en queries y UI | No implementado | No hay filtro por agencia ni boundary de datos | Inyectar `agency_id` en CPTs y filtros globales (`pre_get_posts`, endpoints, AJAX) | P0 | L | Roles + agencia CPT | Pruebas con 2 agencias sin fuga de datos |
| Panel agente | Dashboard frontend + agenda/tareas/eventos | Parcial | Panel actual es admin-centric | Crear panel frontend dedicado (bloques/shortcodes) para agentes, separado del admin | P1 | L | Auth frontend + roles | Ruta frontend operativa con widgets/agendas |
| Bloques Gutenberg | Bloques de propiedades/filtros/listados CRM | Parcial | Ya hay bloques, pero orientados vacacional | Reutilizar bloques y adaptar data source a CPT `impress_property` | P1 | M | CPT/property query API | Bloque property-grid mostrando nuevo CPT |
| API REST personalizada | Endpoints propios con auth y rate limiting | Parcial | Endpoints actuales publicos y sin rate limit | Endpoints versionados `/inmopress/v1` con permisos por rol + throttle | P1 | M | Seguridad Suite alineada | Coleccion de endpoints con tests de permisos |
| Email CRM | SMTP + IMAP + asociacion a entidad/hilo | Parcial | Existe modulo comunicaciones, faltan thread/rules/BCC token | Extender `alquipress_comm` con `thread_id`, token BCC y reglas de asociacion | P1 | M | Config SMTP/IMAP estable | Envio/recepcion enlazado a cliente/lead/owner con hilo |
| Automatizaciones | Motor trigger/condition/action + cola + logs | Parcial | Hay notificaciones puntuales, no workflow engine | Crear `automation-rules` + ejecutor con Action Scheduler | P1 | L | Modelo eventos/entidades | Regla de ejemplo activa (lead nuevo -> tarea + email) |
| Matching | Matching cliente-propiedad con scoring/dedupe | No implementado | No existe scoring CRM | Servicio de matching con score y cache + centro de oportunidades | P2 | L | Preferencias cliente normalizadas | Lista de matches con score y no-duplicacion |
| IA + SEO | OpenAI para SEO y escritura en Rank Math | No implementado | No hay OpenAI ni Rank Math integration | Modulo `ai-seo` con prompts controlados y modo borrador/publicacion | P2 | M | API key y limites uso | Boton "Generar SEO" + guardado en metacampos SEO |
| Licencias SaaS | License key, heartbeat, estados, bloqueo suave | No implementado | Payment manager no cubre licensing SaaS | Nuevo plugin `inmopress-licensing` + servidor validacion | P0 | L | Definir arquitectura cliente-servidor | Activacion/desactivacion por estado de licencia |
| Stripe SaaS | Webhooks suscripcion, portal cliente, dunning | Parcial | Hay Stripe para cobros de reservas, no subs SaaS | Crear modulo Stripe Billing separado del flujo reservas | P1 | L | Licencias + cuenta Stripe Billing | Webhook end-to-end cambia estado de licencia |
| Activity log | Auditoria transversal de eventos CRM | Parcial | Hay logs tecnicos y auditoria financiera parcial | Implementar `impress_activity_log` (CPT o tabla) + visor | P1 | M | Normalizar eventos dominio | Timeline de actividad por entidad |
| PDF | Fichas/dosier/contratos/reportes | No implementado | Sin modulo printables | Modulo `printables` con plantillas y generacion on-demand | P2 | M | Plantillas legales/comerciales | PDF generado y adjuntable a entidad |
| Dashboard metricas | KPIs comparativas y exportacion | Parcial | Existen dashboards vacacionales, no CRM inmobiliario completo | Nuevo dashboard por rol con KPIs CRM (leads, pipeline, cierres) | P2 | M | Activity log + datos pipeline | KPI cards + export CSV/JSON |

## Practicas clave (cumplimiento y accion)

| Practica documentada | Estado | Accion recomendada | Prioridad |
|---|---|---|---|
| Datos reales manuales, IA solo para SEO | Parcial | Definir politica tecnica en modulo `ai-seo` y bloquear escritura de datos operativos | P1 |
| Taxonomias para SEO (no inflar repeaters) | Parcial | Mantener taxonomias para filtros/landing y evitar campos redundantes | P1 |
| Un CPT unico de eventos | No implementado | Crear `impress_event` y migrar tareas/llamadas/visitas ahi | P1 |
| Relacion propietario-propiedad por referencia | No implementado | Implementar `owner_ref` + resolver IDs cacheados | P1 |
| Multiagencia con aislamiento real | No implementado | Introducir `agency_id` obligatorio en entidades y queries | P0 |
| Panel frontend sin depender del admin WP | No implementado | Construir panel frontend por rol | P1 |

## Plan por fases (recomendado)

| Fase | Objetivo | Incluye | Resultado |
|---|---|---|---|
| V1 (fundacion) | Modelo de datos y seguridad | CPTs base, taxonomias, ACF MVP, roles, multi-tenant base, activity log minimo | Base Inmopress operativa |
| V2 (operativa CRM) | Flujo diario comercial | Eventos, panel frontend agente, comunicaciones mejoradas, automatizaciones base, API segura | Operacion CRM completa |
| V3 (SaaS + diferencial) | Escala y producto | Licencias SaaS, Stripe Billing/webhooks, IA SEO, matching, PDFs, dashboards avanzados | Producto SaaS diferenciable |

## Backlog inicial ejecutable (top 12)

| # | Tarea | Prioridad | Esfuerzo |
|---|---|---|---|
| 1 | Crear modulo `inmopress-cpts` con 8 CPT | P0 | L |
| 2 | Crear modulo `inmopress-taxonomies` con 5 taxonomias SEO objetivo | P0 | M |
| 3 | Implementar ACF JSON MVP para property/client/lead/event/owner | P0 | L |
| 4 | Crear roles `agencia/agente/trabajador/cliente` y caps base | P0 | M |
| 5 | Introducir `agency_id` y filtro global de queries | P0 | L |
| 6 | Implementar `owner_ref` + sincronizacion a IDs | P1 | M |
| 7 | Crear `impress_event` y UI basica de agenda | P1 | M |
| 8 | Endpoints `/inmopress/v1` con permisos por rol | P1 | M |
| 9 | Extender comunicaciones con `thread_id` + token BCC | P1 | M |
| 10 | Motor automatizaciones v1 (trigger->action) | P1 | L |
| 11 | Activity log de dominio (CPT/tabla) | P1 | M |
| 12 | Spike licenciamiento SaaS (plugin + servidor) | P0 | M |

## Riesgos y decisiones pendientes

| Decision | Impacto | Necesaria antes de |
|---|---|---|
| Coexistencia `product` vs `impress_property` | Muy alto | Migracion de datos |
| Mantener o sustituir WooCommerce Bookings en el core CRM | Alto | Diseno final de `impress_event` |
| Arquitectura de licenciamiento (single repo vs servicio externo) | Muy alto | Implementar billing SaaS |
| Estrategia panel frontend (tema, app React, bloques server-rendered) | Alto | V2 panel agente |
| Politica de seguridad REST publica vs autenticada | Alto | Publicar endpoints en produccion |

## Definicion de "Hecho" para migracion

| Criterio | Medicion |
|---|---|
| Modelo Inmopress base activo | 8 CPT + 5 taxonomias + ACF MVP cargados |
| Multi-tenant efectivo | Tests de no-fuga entre 2 agencias |
| Operativa minima CRM | Alta lead -> evento -> comunicacion -> seguimiento |
| Observabilidad | Activity log visible por entidad |
| Seguridad base | Roles/caps, endpoints protegidos, secretos fuera de texto plano donde aplique |

## Plan de ejecucion por sprints (fechas y orden exacto)

Supuestos de planificacion:
- Sprint de 2 semanas (lunes a viernes).
- Inicio operativo: lunes 2026-02-09.
- Orden estricto por dependencias: `P0 -> P1 -> P2`.
- Equipo objetivo: 1-2 dev backend + 1 dev frontend (ajustable).

### Calendario maestro

| Sprint | Fechas | Objetivo | Entregables comprometidos | Gate de salida |
|---|---|---|---|---|
| Sprint 0 | 2026-02-09 -> 2026-02-13 | Alineacion tecnica y preparacion | Decision de coexistencia `product` vs `impress_property`, estrategia de licencias (documentada), arquitectura panel frontend (documentada), roadmap de migracion de datos | Documento de decisiones firmado + backlog refinado |
| Sprint 1 | 2026-02-16 -> 2026-02-27 | Fundacion de dominio Inmopress | Modulo `inmopress-cpts` con 8 CPT, modulo `inmopress-taxonomies` con 5 taxonomias objetivo, ACF MVP de `property/owner/client/lead/event` | `wp post-type list` y `wp taxonomy list` con objetivo completo en entorno dev |
| Sprint 2 | 2026-03-02 -> 2026-03-13 | Seguridad de datos y tenancy | Modulo `roles-permissions`, introduccion de `agency_id`, filtros globales multi-tenant en admin/AJAX/queries, pruebas de no-fuga entre 2 agencias | Suite de pruebas de aislamiento multiagencia en verde |
| Sprint 3 | 2026-03-16 -> 2026-03-27 | Relaciones y agenda CRM | Relacion propietario-propiedad por referencia (`owner_ref` + resolucion/cache IDs), CPT `impress_event` operativo (visita/llamada/tarea/email/reunion), vistas admin basicas | Flujo: alta propiedad -> asignacion owner_ref -> evento asociado funcionando |
| Sprint 4 | 2026-03-30 -> 2026-04-10 | API y comunicaciones CRM | API `/inmopress/v1` con permisos por rol + throttling base, extension de comunicaciones (thread_id, token BCC, matching por entidad), hardening de secretos SMTP/IMAP | Pruebas E2E: enviar/recibir email asociado a entidad + endpoints protegidos |
| Sprint 5 | 2026-04-13 -> 2026-04-24 | Panel agente frontend V1 | Shell de panel frontend, dashboard de agente (mis propiedades, mis clientes, mi agenda), widgets base y navegacion movil | Agente puede operar sin entrar al admin WP para tareas diarias clave |
| Sprint 6 | 2026-04-27 -> 2026-05-08 | Automatizaciones V1 | Motor trigger-condition-action minimo, cola con Action Scheduler, 5 reglas base (lead nuevo, evento completado, cambio estado propiedad, recordatorio sin contacto, seguimiento visita) | Reglas ejecutan acciones reales con logs de ejecucion |
| Sprint 7 | 2026-05-11 -> 2026-05-22 | Activity log + reportes basicos | `impress_activity_log` (tabla o CPT), timeline por entidad, dashboard KPI minimo (leads, eventos, conversion), export CSV basico | Auditoria visible por entidad y export funcional |
| Sprint 8 | 2026-05-25 -> 2026-06-05 | SaaS baseline | Spike implementado de `inmopress-licensing` (clave + heartbeat + estados), integracion inicial Stripe Billing (suscripcion + webhook estado) | Licencia cambia estado por webhook y aplica bloqueo suave |
| Sprint 9 | 2026-06-08 -> 2026-06-19 | Cierre V1 y estabilizacion | Hardening, migraciones pendientes, correccion bugs criticos, performance pass, smoke test completo | Go/No-Go V1 aprobado |
| Sprint 10 | 2026-06-22 -> 2026-07-03 | V2 diferencial (inicio) | IA SEO (modo borrador/publicar), matching v1 con scoring, backlog PDF preparado | Inicio de V2 con 2 diferenciales activos |

### Orden exacto de ejecucion (critical path)

| Orden | Item | Sprint asignado | Bloquea a |
|---|---|---|---|
| 1 | Decidir coexistencia `product` vs `impress_property` | Sprint 0 | S1-S10 |
| 2 | Definir arquitectura de licenciamiento | Sprint 0 | S8+ |
| 3 | Crear 8 CPT y 5 taxonomias objetivo | Sprint 1 | S2-S10 |
| 4 | Cargar ACF MVP por entidades CRM | Sprint 1 | S3-S10 |
| 5 | Crear roles/capacidades y tenancy base (`agency_id`) | Sprint 2 | S3-S10 |
| 6 | Implementar aislamiento global de datos (queries/endpoints/AJAX) | Sprint 2 | S4-S10 |
| 7 | Implementar relaciones por referencia + `impress_event` | Sprint 3 | S5-S10 |
| 8 | Publicar API `/inmopress/v1` segura | Sprint 4 | S5-S10 |
| 9 | Extender comunicaciones con hilos + asociacion robusta | Sprint 4 | S6-S10 |
| 10 | Construir panel frontend agente V1 | Sprint 5 | S6-S10 |
| 11 | Motor automatizaciones V1 + cola | Sprint 6 | S7-S10 |
| 12 | Activity log + KPIs minimos | Sprint 7 | S9-S10 |
| 13 | Licencias + Stripe Billing baseline | Sprint 8 | S9-S10 |
| 14 | Estabilizacion y salida a V1 | Sprint 9 | S10 |

### Definicion de done por sprint (operativa)

| Sprint | DoD |
|---|---|
| S0 | Decisiones tecnicas cerradas y backlog sin bloqueos de arquitectura |
| S1 | Entidades y taxonomias visibles en admin + CRUD base estable |
| S2 | Dos agencias de prueba con aislamiento validado en lectura/escritura |
| S3 | Eventos CRM funcionando y relaciones owner-property consistentes |
| S4 | API y comunicaciones con permisos/seguridad validados |
| S5 | Flujo diario de agente usable en frontend (sin admin) |
| S6 | Automatizaciones ejecutadas por cola con trazabilidad |
| S7 | Auditoria y KPIs minimos consumibles por negocio |
| S8 | Licencia y billing alteran estado de producto en tiempo real |
| S9 | Cero bloqueantes P0/P1 abiertos para salida V1 |
| S10 | Diferenciales V2 iniciados y en produccion controlada |

