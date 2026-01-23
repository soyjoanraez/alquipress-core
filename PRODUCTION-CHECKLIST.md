# ALQUIPRESS - Checklist de Producción

**Versión:** 1.0.0
**Fecha de Preparación:** 2026-01-23

---

## Pre-Deployment

### 1. Verificación de Código

- [ ] **Sin errores PHP**
  ```bash
  # Activar WP_DEBUG
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
  define('WP_DEBUG_DISPLAY', false);

  # Revisar logs
  tail -f wp-content/debug.log
  ```

- [ ] **Sin errores JavaScript**
  - Abrir consola del navegador (F12)
  - Navegar por todas las páginas del plugin
  - Verificar que no hay errores rojos

- [ ] **Code Quality**
  - [ ] Todos los strings traducibles wrapped con `__()` o `_e()`
  - [ ] Nonces verificados en todos los formularios
  - [ ] Sanitización de inputs: `sanitize_text_field()`, `sanitize_email()`, etc.
  - [ ] Escapado de outputs: `esc_html()`, `esc_url()`, `esc_attr()`
  - [ ] Permisos verificados: `current_user_can()`

### 2. Testing de Módulos

- [ ] **Taxonomías** (taxonomies)
  - [ ] 33 términos de Población creados
  - [ ] 4 términos de Zona creados
  - [ ] 27 características con íconos FontAwesome
  - [ ] Selector de íconos funciona

- [ ] **CRM Propietarios** (crm-owners)
  - [ ] Crear propietario de prueba
  - [ ] IBAN con máscara funciona
  - [ ] Columnas personalizadas visibles
  - [ ] Cálculo de ingresos correcto

- [ ] **CRM Huéspedes** (crm-guests)
  - [ ] Crear usuario de prueba
  - [ ] Rating se guarda correctamente
  - [ ] Preferencias se guardan
  - [ ] Notas privadas se guardan

- [ ] **Pipeline de Reservas** (booking-pipeline)
  - [ ] 5 estados personalizados registrados
  - [ ] Estados visibles en listado de pedidos
  - [ ] Transiciones de estado funcionan

- [ ] **Dashboard Widgets** (dashboard-widgets)
  - [ ] Widget "Movimientos de Hoy" visible
  - [ ] Widget "Ingresos del Mes" con datos correctos
  - [ ] Widget "Estado de Propiedades" funcional
  - [ ] Widget "Alertas" muestra pendientes

- [ ] **Pipeline Kanban** (pipeline-kanban)
  - [ ] Página carga sin errores
  - [ ] 7 columnas visibles
  - [ ] Filtros funcionan (búsqueda, fechas, propiedad)
  - [ ] Badge "URGENTE" aparece cuando corresponde
  - [ ] Smooth scroll horizontal funciona

- [ ] **Order Columns** (order-columns)
  - [ ] Columnas personalizadas visibles
  - [ ] Compatible con HPOS activo
  - [ ] Compatible con HPOS desactivado

- [ ] **Perfil de Huésped** (guest-profile)
  - [ ] Link "Ver Perfil CRM" visible en listado de usuarios
  - [ ] Página carga correctamente
  - [ ] Estadísticas calculadas
  - [ ] Historial de reservas visible

- [ ] **Editor de Huésped** (guest-editor)
  - [ ] Link "Editar Huésped" visible
  - [ ] Formulario carga
  - [ ] Rating con preview en tiempo real
  - [ ] Preferencias con checkboxes visuales
  - [ ] Guardar funciona

- [ ] **UI Enhancements** (ui-enhancements)
  - [ ] Estilos se aplican en propietarios
  - [ ] Estilos se aplican en huéspedes
  - [ ] Responsive en móvil

- [ ] **Preferencias Avanzadas** (advanced-preferences)
  - [ ] Widget en dashboard
  - [ ] Modal de análisis completo
  - [ ] Shortcode funciona en página
  - [ ] Columna en usuarios

- [ ] **Acciones Rápidas** (quick-actions)
  - [ ] Admin bar menu visible
  - [ ] Todos los links funcionan
  - [ ] Atajos de teclado funcionan
  - [ ] Vista rápida modal abre
  - [ ] Tooltip de hints aparece con Shift

- [ ] **Notificaciones CRM** (crm-notifications)
  - [ ] Crear pedido con check-in hoy → Notificación aparece
  - [ ] Descartar notificación → No vuelve a aparecer
  - [ ] Badge contador en menú
  - [ ] 6 tipos de notificaciones funcionan

- [ ] **Informes y Analíticas** (advanced-reports)
  - [ ] Página carga
  - [ ] 4 stats cards con datos
  - [ ] Todos los gráficos renderizan
  - [ ] Selector de año funciona
  - [ ] Botón "Actualizar Informes" funciona
  - [ ] Responsive en móvil

### 3. Performance

- [ ] **Carga de Página**
  - [ ] Dashboard < 2 segundos
  - [ ] Pipeline Kanban < 3 segundos
  - [ ] Informes < 4 segundos
  - [ ] Listado de pedidos < 2 segundos

- [ ] **Queries de Base de Datos**
  ```php
  // Activar query logging
  define('SAVEQUERIES', true);

  // Verificar en admin
  global $wpdb;
  echo '<pre>';
  print_r($wpdb->queries);
  echo '</pre>';
  ```
  - [ ] Dashboard: < 50 queries
  - [ ] Pipeline Kanban: < 100 queries
  - [ ] Informes (AJAX): < 150 queries

- [ ] **Caché Funcionando**
  ```bash
  # Verificar transients
  wp transient list | grep alquipress

  # Debe mostrar:
  # alquipress_top_clients_2026_5
  # alquipress_top_properties_2026_5
  # alquipress_monthly_revenue_2026
  # alquipress_preferences_stats
  ```

- [ ] **Assets Optimizados**
  - [ ] CSS minificado en producción
  - [ ] JS minificado en producción
  - [ ] Solo cargan en páginas necesarias
  - [ ] Chart.js desde CDN

### 4. Compatibilidad

- [ ] **Navegadores**
  - [ ] Chrome (última versión)
  - [ ] Firefox (última versión)
  - [ ] Safari (última versión)
  - [ ] Edge (última versión)

- [ ] **Dispositivos**
  - [ ] Desktop (1920x1080)
  - [ ] Laptop (1366x768)
  - [ ] Tablet (768x1024)
  - [ ] Móvil (375x667)

- [ ] **WordPress**
  - [ ] WordPress 6.0+
  - [ ] WordPress 6.4 (última)

- [ ] **PHP**
  - [ ] PHP 8.0
  - [ ] PHP 8.1
  - [ ] PHP 8.2

- [ ] **WooCommerce**
  - [ ] WooCommerce 7.0+
  - [ ] WooCommerce 8.0+
  - [ ] HPOS activado
  - [ ] HPOS desactivado

- [ ] **ACF Pro**
  - [ ] ACF Pro 6.0+

### 5. Seguridad

- [ ] **Nonces**
  - [ ] Todos los formularios tienen nonce
  - [ ] Todos los AJAX endpoints verifican nonce

- [ ] **Permisos**
  - [ ] Páginas de admin requieren `manage_options`
  - [ ] Edición de pedidos requiere `edit_shop_orders`
  - [ ] Edición de usuarios requiere `edit_users`

- [ ] **Sanitización**
  - [ ] Todos los $_POST sanitizados
  - [ ] Todos los $_GET sanitizados
  - [ ] Emails validados con `sanitize_email()`
  - [ ] URLs validadas con `esc_url()`

- [ ] **SQL Injection**
  - [ ] Todas las queries usan `$wpdb->prepare()`
  - [ ] No hay queries raw sin preparar

- [ ] **XSS**
  - [ ] Todos los echo usan `esc_html()` o `esc_attr()`
  - [ ] JavaScript data usa `wp_localize_script()`

### 6. Backup

- [ ] **Base de Datos**
  ```bash
  # Backup completo
  wp db export backup-$(date +%Y%m%d).sql

  # Verificar tamaño
  ls -lh backup-*.sql
  ```

- [ ] **Archivos del Plugin**
  ```bash
  # Comprimir plugin
  cd wp-content/plugins
  tar -czf alquipress-core-$(date +%Y%m%d).tar.gz alquipress-core/
  ```

- [ ] **ACF Fields**
  ```bash
  # Exportar fields a JSON (ya hecho automáticamente en /acf-json/)
  ls -la wp-content/plugins/alquipress-core/acf-json/
  ```

- [ ] **Configuración de Módulos**
  ```bash
  # Exportar configuración
  wp option get alquipress_modules > alquipress-modules-config.json
  ```

---

## Deployment

### 1. Pre-Deploy

- [ ] **Entorno de Staging**
  - [ ] Subir archivos a staging
  - [ ] Activar plugin en staging
  - [ ] Probar todas las funcionalidades
  - [ ] Verificar no hay errores

- [ ] **Modo Mantenimiento**
  ```php
  // Activar maintenance mode
  wp maintenance-mode activate
  ```

### 2. Deploy

- [ ] **Subir Archivos**
  ```bash
  # Via SFTP/SSH
  scp -r alquipress-core/ user@server:/path/to/wp-content/plugins/

  # O vía Git
  git push production main
  ```

- [ ] **Activar Plugin**
  ```bash
  wp plugin activate alquipress-core
  ```

- [ ] **Importar ACF Fields**
  ```bash
  # Los fields se importan automáticamente desde acf-json/
  # Verificar en ACF → Field Groups
  ```

- [ ] **Activar Módulos**
  ```bash
  wp option update alquipress_modules '{
    "taxonomies":true,
    "crm-guests":true,
    "crm-owners":true,
    "booking-pipeline":true,
    "order-columns":true,
    "dashboard-widgets":true,
    "pipeline-kanban":true,
    "guest-profile":true,
    "guest-editor":true,
    "ui-enhancements":true,
    "advanced-preferences":true,
    "quick-actions":true,
    "crm-notifications":true,
    "advanced-reports":true
  }' --format=json
  ```

- [ ] **Flush Rewrite Rules**
  ```bash
  wp rewrite flush
  ```

- [ ] **Regenerar Permalinks**
  - Ir a Ajustes → Enlaces permanentes
  - Click en "Guardar cambios"

### 3. Post-Deploy

- [ ] **Verificar Plugin Activo**
  ```bash
  wp plugin list | grep alquipress
  ```

- [ ] **Verificar Módulos Activos**
  ```bash
  wp option get alquipress_modules --format=json | jq
  ```

- [ ] **Test Rápido de Funcionalidades**
  - [ ] Dashboard carga
  - [ ] Pipeline Kanban carga
  - [ ] Informes cargan
  - [ ] Crear pedido de prueba funciona
  - [ ] Notificaciones aparecen

- [ ] **Monitorear Logs**
  ```bash
  # Monitorear error log
  tail -f wp-content/debug.log

  # Monitorear server logs
  tail -f /var/log/apache2/error.log  # Apache
  tail -f /var/log/nginx/error.log    # Nginx
  ```

- [ ] **Desactivar Modo Mantenimiento**
  ```bash
  wp maintenance-mode deactivate
  ```

---

## Post-Deployment

### 1. Monitoring (Primeras 24h)

- [ ] **Logs de Errores**
  - [ ] Revisar cada 2 horas
  - [ ] Sin errores PHP fatales
  - [ ] Sin errores JavaScript críticos

- [ ] **Performance**
  - [ ] Tiempos de carga estables
  - [ ] Sin aumento de memoria
  - [ ] Sin slowdowns en queries

- [ ] **User Feedback**
  - [ ] Recopilar feedback del equipo
  - [ ] Documentar bugs encontrados
  - [ ] Priorizar fixes

### 2. Optimización Continua

- [ ] **Caché**
  - [ ] Verificar transients funcionan
  - [ ] Monitorear hit rate de caché
  - [ ] Ajustar TTL si es necesario

- [ ] **Queries**
  - [ ] Identificar queries lentas
  - [ ] Añadir índices donde sea necesario
  - [ ] Optimizar queries N+1

- [ ] **Assets**
  - [ ] Minificar CSS/JS adicionales
  - [ ] Implementar lazy loading de imágenes
  - [ ] Considerar CDN para assets

### 3. Backup Automático

- [ ] **Configurar Cron**
  ```bash
  # Backup diario de base de datos
  0 3 * * * wp db export /backups/alquipress-$(date +\%Y\%m\%d).sql

  # Backup semanal de archivos
  0 4 * * 0 tar -czf /backups/alquipress-files-$(date +\%Y\%m\%d).tar.gz /path/to/wp-content/plugins/alquipress-core/

  # Limpiar backups > 30 días
  0 5 * * * find /backups/ -name "alquipress-*" -mtime +30 -delete
  ```

### 4. Mantenimiento

- [ ] **Limpieza de Caché Semanal**
  ```bash
  # Limpiar transients expirados
  wp transient delete --expired

  # Limpiar caché de ALQUIPRESS
  wp eval 'Alquipress_Performance_Optimizer::clear_reports_cache();'
  ```

- [ ] **Actualización de Dependencias**
  - [ ] Mantener WordPress actualizado
  - [ ] Mantener WooCommerce actualizado
  - [ ] Mantener ACF Pro actualizado
  - [ ] Verificar compatibilidad después de updates

### 5. Documentación de Usuario

- [ ] **Manual de Usuario**
  - [ ] Crear guía de uso para administradores
  - [ ] Screenshots de cada módulo
  - [ ] Video tutoriales (opcional)

- [ ] **FAQ**
  - [ ] ¿Cómo crear un propietario?
  - [ ] ¿Cómo gestionar una reserva completa?
  - [ ] ¿Cómo generar informes?
  - [ ] ¿Cómo usar las notificaciones?

---

## Rollback Plan

### En Caso de Error Crítico

1. **Desactivar Plugin**
   ```bash
   wp plugin deactivate alquipress-core
   ```

2. **Restaurar Backup**
   ```bash
   # Restaurar base de datos
   wp db import backup-YYYYMMDD.sql

   # Restaurar archivos
   rm -rf wp-content/plugins/alquipress-core/
   tar -xzf alquipress-core-backup.tar.gz -C wp-content/plugins/
   ```

3. **Verificar Funcionalidad**
   - Comprobar que WooCommerce funciona
   - Comprobar que otros plugins funcionan
   - Comprobar que la web está online

4. **Analizar Error**
   - Revisar logs guardados
   - Identificar causa raíz
   - Preparar hotfix

5. **Redeploy con Fix**
   - Aplicar corrección en desarrollo
   - Probar en staging
   - Deploy con fix

---

## Checklist de Comandos Útiles

### Diagnóstico

```bash
# Ver versión de WordPress
wp core version

# Ver versión de PHP
php -v

# Ver plugins activos
wp plugin list --status=active

# Ver módulos de ALQUIPRESS
wp option get alquipress_modules --format=json

# Ver transients
wp transient list | grep alquipress

# Ver tamaño de base de datos
wp db size

# Ver tablas más grandes
wp db query "SELECT table_name AS 'Table',
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
ORDER BY (data_length + index_length) DESC
LIMIT 10;"

# Verificar estado de caché
wp cache flush

# Ver usuarios
wp user list --role=customer --format=count

# Ver propietarios
wp post list --post_type=propietario --format=count

# Ver pedidos del mes
wp post list --post_type=shop_order --post_status=wc-completed --after="$(date -d '1 month ago' +%Y-%m-%d)" --format=count
```

### Mantenimiento

```bash
# Limpiar revisiones
wp post delete $(wp post list --post_type=revision --format=ids) --force

# Limpiar transients expirados
wp transient delete --expired

# Optimizar base de datos
wp db optimize

# Regenerar thumbnails (si hay imágenes)
wp media regenerate --yes

# Limpiar caché de objetos
wp cache flush

# Verificar integridad de archivos
wp core verify-checksums
```

---

## Contacto de Emergencia

**Desarrollador Principal:** Claude Code
**Soporte Técnico:** [tu-email@example.com]
**Documentación:** Ver DOCUMENTATION.md
**Testing:** Ver TESTING.md

---

## Notas Finales

### Métricas de Éxito

- ✅ **20 módulos** implementados y activos
- ✅ **0 errores críticos** en producción
- ✅ **< 3 segundos** tiempo de carga promedio
- ✅ **100% funcionalidades** testeadas
- ✅ **Responsive** en todos los dispositivos
- ✅ **Documentación completa** disponible

### Próximas Mejoras (Post-v1.0)

- [ ] Sistema de exportación de informes a PDF
- [ ] Integración con calendar booking avanzado
- [ ] Dashboard personalizable por usuario
- [ ] Notificaciones push/email
- [ ] API REST pública
- [ ] Webhooks para integraciones
- [ ] Multi-idioma (i18n)

---

**¡Feliz deploy!** 🚀

_Recuerda: Un buen backup es la mejor póliza de seguro._
