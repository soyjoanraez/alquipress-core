<?php
class Alquipress_Module_Manager
{
    const DASHBOARD_TEMPLATE_OPTION = 'alquipress_dashboard_template';
    const DASHBOARD_TEMPLATE_DEFAULT = 'pencil';
    const DARK_MODE_OPTION = 'alquipress_dark_mode';

    private $modules = [];
    private $active_modules = [];

    /**
     * Valores por defecto para módulos nuevos.
     * Cuando se añade un módulo al registro, si no existe en la opción guardada
     * se usa el valor de este array (true = activo, false = inactivo).
     * Solo hay que editar aquí al añadir nuevos módulos.
     */
    private const MODULE_DEFAULTS = [
        'taxonomies'               => true,
        'crm-guests'               => true,
        'crm-owners'               => true,
        'booking-pipeline'         => true,
        'email-automation'         => true,
        'seo-master'               => true,
        'booking-enforcer'         => true,
        'order-columns'            => true,
        'dashboard-widgets'        => true,
        'properties-page'          => true,
        'property-editor'          => true,
        'owners-page'              => true,
        'bookings-page'            => true,
        'clients-page'             => true,
        'booking-calendar-prices'  => true,
        'ses-compliance'           => true,
        'operational-health'       => true,
        'payment-pipeline'         => true,
        'communications'           => true,
        'ical-sync'                => true,
        'ap-bookings'              => true,
        'property-pricing-fields'  => true,
        'accounting'               => true,
        'owner-invoicing'          => true,
        'checkout-document-fields' => true,
        'owner-portal'             => true,
        'email-campaigns'          => true,
        'payments'                 => false,
        'alquipress-tester'        => false,
    ];

    public function __construct()
    {
        $this->register_modules();
        $this->active_modules = $this->resolve_active_modules();

        $saved_template = get_option(self::DASHBOARD_TEMPLATE_OPTION, '');
        if (!is_string($saved_template) || $saved_template === '') {
            update_option(self::DASHBOARD_TEMPLATE_OPTION, self::DASHBOARD_TEMPLATE_DEFAULT);
        }

        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'handle_form_submit']);
        add_action('admin_init', [$this, 'redirect_wp_dashboard_to_alquipress'], 5);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_settings_assets']);
        add_filter('admin_body_class', [$this, 'add_fullscreen_body_class']);
    }

    /**
     * Resolver el estado de los módulos activos:
     * - Si no hay opción guardada, usar los defaults.
     * - Si hay opción guardada, fusionar con los defaults para que los módulos
     *   nuevos (no presentes en la opción) reciban su valor por defecto.
     * - Solo llama a update_option() una vez si hubo cambios, nunca en bucle.
     */
    private function resolve_active_modules(): array
    {
        $saved = get_option('alquipress_modules', null);

        if (!is_array($saved) || empty($saved)) {
            update_option('alquipress_modules', self::MODULE_DEFAULTS);
            return self::MODULE_DEFAULTS;
        }

        // Detectar módulos nuevos que no están en la opción guardada
        $new_modules = array_diff_key(self::MODULE_DEFAULTS, $saved);

        if (empty($new_modules)) {
            return $saved;
        }

        // Fusionar en una sola operación y guardar una sola vez
        $merged = array_merge(self::MODULE_DEFAULTS, $saved, $new_modules);
        update_option('alquipress_modules', $merged);

        return $merged;
    }

    /**
     * Añadir clase para modo pantalla completa y modo oscuro en páginas Alquipress.
     */
    public function add_fullscreen_body_class($classes)
    {
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if ($page !== '' && strpos($page, 'alquipress-') === 0) {
            $classes .= ' alquipress-fullscreen';
            if (get_option(self::DARK_MODE_OPTION, false)) {
                $classes .= ' ap-dark-mode';
            }
        }
        return $classes;
    }

    /**
     * Hacer que el dashboard de WordPress sea el Panel ALQUIPRESS: redirigir index.php al Panel.
     */
    public function redirect_wp_dashboard_to_alquipress()
    {
        global $pagenow;
        if ($pagenow !== 'index.php') {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        $redirect = admin_url('admin.php?page=alquipress-dashboard');
        if (isset($_GET['page']) && $_GET['page'] === 'alquipress-dashboard') {
            return;
        }
        wp_safe_redirect($redirect);
        exit;
    }

    public function enqueue_settings_assets($hook)
    {
        // Cargar en Ajustes y en todas las subpáginas (Panel, Propiedades, Reservas, Propietarios, etc.)
        // Usar tanto $hook como $_GET['page'] porque el hook puede variar (ej. primera subpágina = toplevel_page_*)
        $hook = $hook !== null ? (string) $hook : '';
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $is_hook_alquipress = ($hook === 'toplevel_page_alquipress-settings')
            || (strpos($hook, 'alquipress-settings_page_') === 0);
        $is_page_alquipress = ($page !== '' && strpos($page, 'alquipress-') === 0);
        if (!$is_hook_alquipress && !$is_page_alquipress) {
            return;
        }

        wp_enqueue_style(
            'alquipress-admin-layout',
            ALQUIPRESS_URL . 'includes/admin/assets/alquipress-admin-layout.css',
            [],
            ALQUIPRESS_VERSION
        );

        // Modo pantalla completa: ocultar barra superior y menú lateral de WordPress
        $fullscreen_css = 'html:has(body.alquipress-fullscreen){margin-top:0!important;}'
            . 'body.alquipress-fullscreen #wpadminbar{display:none!important;}'
            . 'body.alquipress-fullscreen.admin-bar{margin-top:0!important;}'
            . 'body.alquipress-fullscreen #adminmenuback,body.alquipress-fullscreen #adminmenuwrap{display:none!important;}'
            . 'body.alquipress-fullscreen #wpcontent,body.alquipress-fullscreen #wpbody{margin-left:0!important;}'
            . 'body.alquipress-fullscreen .wrap.ap-has-sidebar .ap-owners-layout{min-height:100vh!important;}';

        $wpcontent_bg = get_option(self::DARK_MODE_OPTION, false) ? '#111318' : '#f8fafb';
        // Estilos críticos para Panel, Propiedades, Propietarios, Reservas, etc.: fondo claro y layout flex
        // Evita que tema/plugins (ej. Astra) cubran el contenido con fondo negro o rompan el layout.
        $critical_layout = $fullscreen_css
            . '#wpcontent,#wpbody-content{background:' . esc_attr($wpcontent_bg) . '!important;}'
            . '.wrap.ap-has-sidebar{min-height:80vh!important;width:100%!important;position:relative!important;z-index:999998!important;max-width:none!important;margin-top:12px!important;padding:0!important;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif!important;}'
            . '.wrap.ap-has-sidebar .ap-owners-layout{display:flex!important;min-height:calc(100vh - 140px)!important;background:#f8fafb!important;border:1px solid #e8eef3!important;border-radius:16px!important;overflow:hidden!important;}'
            . '.wrap.ap-has-sidebar .ap-owners-sidebar{width:256px!important;min-width:256px!important;background:#ffffff!important;border-right:1px solid #e8eef3!important;display:flex!important;flex-direction:column!important;}'
            . '.wrap.ap-has-sidebar .ap-owners-main{flex:1!important;min-width:0!important;padding:32px!important;background:#f8fafb!important;}'
            . '.wrap.ap-has-sidebar .ap-owners-icon{stroke:currentColor!important;fill:none!important;}';
        wp_add_inline_style('alquipress-admin-layout', $critical_layout);

        if ($page === 'alquipress-settings') {
            wp_enqueue_style(
                'alquipress-settings-page',
                ALQUIPRESS_URL . 'includes/admin/assets/settings-page.css',
                [],
                ALQUIPRESS_VERSION
            );
        }

        if ($is_page_alquipress) {
            wp_enqueue_script(
                'alquipress-keyboard-shortcuts',
                ALQUIPRESS_URL . 'includes/admin/assets/keyboard-shortcuts.js',
                [],
                ALQUIPRESS_VERSION,
                true
            );
        }

        // SortableJS: disponible en ambas pestañas del Pipeline (Reservas y Cobros) para evitar dependencia no registrada.
        if ($page === 'alquipress-pipeline') {
            wp_enqueue_script(
                'sortable-js',
                ALQUIPRESS_URL . 'includes/admin/assets/sortable.min.js',
                [],
                '1.15.0',
                true
            );
        }

        do_action('alquipress_enqueue_section_assets', $page);
    }

    private function register_modules()
    {
        $this->modules = [
            'taxonomies' => [
                'name' => 'Taxonomías Personalizadas',
                'description' => 'Población, Zona, Características',
                'file' => 'taxonomies/taxonomies.php',
                'dependencies' => []
            ],
            'crm-guests' => [
                'name' => 'CRM de Huéspedes',
                'description' => 'Gestión de clientes con preferencias y valoraciones',
                'file' => 'crm-guests/crm-guests.php',
                'dependencies' => []
            ],
            'crm-owners' => [
                'name' => 'CRM de Propietarios',
                'description' => 'Gestión de propietarios con datos financieros',
                'file' => 'crm-owners/crm-owners.php',
                'dependencies' => []
            ],
            'booking-pipeline' => [
                'name' => 'Pipeline de Reservas',
                'description' => 'Estados personalizados de pedidos',
                'file' => 'booking-pipeline/pipeline.php',
                'dependencies' => []
            ],
            'email-automation' => [
                'name' => 'Automatización Email',
                'description' => 'Integración con MailPoet',
                'file' => 'email-automation/mailpoet-integration.php',
                'dependencies' => []
            ],
            'payments' => [
                'name' => 'Pasarelas de Pago',
                'description' => 'Stripe + Redsys',
                'file' => 'payments/payment-gates.php',
                'dependencies' => []
            ],
            'alquipress-tester' => [
                'name' => 'Generador de Pruebas',
                'description' => 'Herramientas para generar datos ficticios (CPTs, Usuarios)',
                'file' => 'alquipress-tester/tester.php',
                'dependencies' => []
            ],
            'seo-master' => [
                'name' => 'SEO Master & Renombrado',
                'description' => 'Arquitectura /alquiler-vacacional/, Schema VacationRental y WPO',
                'file' => 'seo-optimization/seo-master.php',
                'dependencies' => []
            ],
            'kyero-integration' => [
                'name' => 'Integración Kyero XML',
                'description' => 'Importación y exportación de inmuebles vía XML Kyero',
                'file' => 'kyero-integration/kyero-integration.php',
                'dependencies' => []
            ],
            'booking-enforcer' => [
                'name' => 'Forzar Reservas Virtuales',
                'description' => 'Obliga a que todos los inmuebles sean reservables y virtuales',
                'file' => 'booking-enforcer/booking-enforcer.php',
                'dependencies' => []
            ],
            'ap-bookings' => [
                'name' => 'Motor de reservas Alquipress',
                'description' => 'Motor propio de reservas para alquiler vacacional (sustituye WooCommerce Bookings a medio plazo)',
                'file' => 'ap-bookings/ap-bookings.php',
                'dependencies' => []
            ],
            'order-columns' => [
                'name' => 'Columnas de Pedidos',
                'description' => 'Añade columnas personalizadas en listado de pedidos WooCommerce: Propiedad, Fechas, Propietario, Semáforo',
                'file' => 'order-columns/order-columns.php',
                'dependencies' => []
            ],
            'dashboard-widgets' => [
                'name' => 'Dashboard Widgets',
                'description' => 'Widgets informativos en el dashboard: Movimientos, Ingresos, Estado de Propiedades, Alertas',
                'file' => 'dashboard-widgets/dashboard-widgets.php',
                'dependencies' => []
            ],
            'pipeline-kanban' => [
                'name' => 'Pipeline Kanban',
                'description' => 'Vista tablero tipo Kanban para gestionar reservas visualmente por estado',
                'file' => 'pipeline-kanban/pipeline-kanban.php',
                'dependencies' => ['booking-pipeline']
            ],
            'guest-profile' => [
                'name' => 'Perfil de Huésped',
                'description' => 'Vista detallada read-only del perfil de cliente con historial de reservas',
                'file' => 'guest-profile/guest-profile.php',
                'dependencies' => ['crm-guests']
            ],
            'guest-editor' => [
                'name' => 'Editor de Huésped',
                'description' => 'Formulario mejorado para editar datos del huésped',
                'file' => 'guest-editor/guest-editor.php',
                'dependencies' => ['crm-guests']
            ],
            'ui-enhancements' => [
                'name' => 'Mejoras UI',
                'description' => 'Mejoras visuales para CPTs y páginas de edición (propietarios, huéspedes)',
                'file' => 'ui-enhancements/ui-enhancements.php',
                'dependencies' => []
            ],
            'advanced-preferences' => [
                'name' => 'Preferencias Avanzadas',
                'description' => 'Sistema mejorado de preferencias con estadísticas y análisis',
                'file' => 'advanced-preferences/advanced-preferences.php',
                'dependencies' => ['crm-guests']
            ],
            'quick-actions' => [
                'name' => 'Acciones Rápidas',
                'description' => 'Atajos de teclado, admin bar mejorado y acciones rápidas',
                'file' => 'quick-actions/quick-actions.php',
                'dependencies' => []
            ],
            'crm-notifications' => [
                'name' => 'Notificaciones CRM',
                'description' => 'Sistema de alertas y recordatorios automáticos',
                'file' => 'crm-notifications/crm-notifications.php',
                'dependencies' => []
            ],
            'advanced-reports' => [
                'name' => 'Informes y Analíticas',
                'description' => 'Reportes avanzados con Chart.js: ingresos, ocupación, top clientes y propiedades',
                'file' => 'advanced-reports/advanced-reports.php',
                'dependencies' => []
            ],
            'properties-page' => [
                'name' => 'Página Propiedades (Pencil)',
                'description' => 'Vista de listado de propiedades con diseño Pencil: grid de tarjetas, búsqueda, filtros',
                'file' => 'properties-page/properties-page.php',
                'dependencies' => []
            ],
            'property-editor' => [
                'name' => 'Editor de Propiedades',
                'description' => 'Editor propio con layout Pencil para propiedades',
                'file' => 'property-editor/property-editor.php',
                'dependencies' => []
            ],
            'owners-page' => [
                'name' => 'Página Propietarios (Pencil)',
                'description' => 'Dashboard de propietarios: métricas, requieren atención, top propietarios',
                'file' => 'owners-page/owners-page.php',
                'dependencies' => []
            ],
            'bookings-page' => [
                'name' => 'Página Reservas (Pencil)',
                'description' => 'Dashboard de reservas: KPIs, requieren atención, reservas recientes',
                'file' => 'bookings-page/bookings-page.php',
                'dependencies' => []
            ],
            'ap-bookings-dashboard' => [
                'name' => 'Dashboard Ap Bookings',
                'description' => 'Próximas entradas/salidas y reservas activas desde el motor Ap_Booking',
                'file' => 'ap-bookings-dashboard/ap-bookings-dashboard.php',
                'dependencies' => ['bookings-page']
            ],
            'clients-page' => [
                'name' => 'Página Clientes (Huéspedes)',
                'description' => 'Listado de clientes: datos, pagos, método de pago, estancia y documentación (DNI, etc.)',
                'file' => 'clients-page/clients-page.php',
                'dependencies' => []
            ],
            'booking-calendar-prices' => [
                'name' => 'Precios en Calendario de Reservas',
                'description' => 'Muestra el coste por día en el calendario de WooCommerce Bookings',
                'file' => 'booking-calendar-prices/booking-calendar-prices.php',
                'dependencies' => []
            ],
            'ses-compliance' => [
                'name' => 'Cumplimiento SES Hospedajes',
                'description' => 'Metadatos operativos SES en pedidos: estado, tipo de pago y referencia de envío',
                'file' => 'ses-compliance/ses-compliance.php',
                'dependencies' => []
            ],
            'operational-health' => [
                'name' => 'Panel de Salud Operativa',
                'description' => 'Dashboard centralizado con alertas accionables sobre problemas operativos críticos',
                'file' => 'operational-health/operational-health.php',
                'dependencies' => ['dashboard-widgets']
            ],
            'payment-pipeline' => [
                'name' => 'Pipeline de Cobros',
                'description' => 'Sistema de seguimiento visual de pagos por hitos con recordatorios automáticos',
                'file' => 'payment-pipeline/payment-pipeline.php',
                'dependencies' => []
            ],
            'communications' => [
                'name' => 'Comunicación',
                'description' => 'Sistema de emails con SMTP/IMAP e histórico completo',
                'file' => 'communications/communications.php',
                'dependencies' => []
            ],
            'property-pricing-fields' => [
                'name' => 'Campos de precios por propiedad',
                'description' => 'Limpieza, lavandería y comisión por propiedad',
                'file' => 'property-pricing-fields/property-pricing-fields.php',
                'dependencies' => []
            ],
            'accounting' => [
                'name' => 'Contabilidad automática',
                'description' => 'Registro automático de ingresos, comisiones, limpieza y lavandería por propiedad y propietario',
                'file' => 'accounting/accounting.php',
                'dependencies' => []
            ],
            'owner-invoicing' => [
                'name' => 'Facturación propietarios',
                'description' => 'Generar facturas PDF/HTML con el desglose a pagar a cada propietario',
                'file' => 'owner-invoicing/owner-invoicing.php',
                'dependencies' => ['crm-owners']
            ],
            'checkout-document-fields' => [
                'name' => 'DNI/Pasaporte en checkout',
                'description' => 'Campos obligatorios de documento de identidad antes de confirmar la reserva',
                'file' => 'checkout-document-fields/checkout-document-fields.php',
                'dependencies' => []
            ],
            'owner-portal' => [
                'name' => 'Portal propietarios',
                'description' => 'Área privada frontend para que los propietarios vean la ocupación de sus propiedades',
                'file' => 'owner-portal/owner-portal.php',
                'dependencies' => ['crm-owners']
            ],
            'email-campaigns' => [
                'name' => 'Campañas de email',
                'description' => 'Email masivo a clientes, listas Mailpoet fin de año, recordatorios mismas fechas',
                'file' => 'email-campaigns/email-campaigns.php',
                'dependencies' => []
            ],
            'ical-sync' => [
                'name' => 'Sincronización iCal',
                'description' => 'Export/import calendarios para Airbnb, Booking.com, VRBO',
                'file' => 'ical-sync/ical-sync.php',
                'dependencies' => []
            ],
            'frontend-blocks' => [
                'name' => 'Bloques Gutenberg AlquiPress',
                'description' => 'Bloques personalizados integrados con Astra y Spectra',
                'file' => 'frontend-blocks/frontend-blocks.php',
                'dependencies' => []
            ]
        ];
    }

    public static function get_dashboard_template_choices()
    {
        return [
            'pencil' => [
                'label' => __('Pencil (actual)', 'alquipress'),
                'description' => __('Layout equilibrado para uso diario del equipo.', 'alquipress'),
            ],
            'compact' => [
                'label' => __('Compacto', 'alquipress'),
                'description' => __('Mayor densidad de datos y menos espacios.', 'alquipress'),
            ],
            'executive' => [
                'label' => __('Executive', 'alquipress'),
                'description' => __('Vista enfocada a KPIs con estilo más visual.', 'alquipress'),
            ],
        ];
    }

    public static function sanitize_dashboard_template($template)
    {
        $template = sanitize_key((string) $template);
        $choices = self::get_dashboard_template_choices();
        if (!isset($choices[$template])) {
            return self::DASHBOARD_TEMPLATE_DEFAULT;
        }
        return $template;
    }

    public static function get_dashboard_template()
    {
        $saved = get_option(self::DASHBOARD_TEMPLATE_OPTION, self::DASHBOARD_TEMPLATE_DEFAULT);
        return self::sanitize_dashboard_template($saved);
    }

    public static function is_dark_mode()
    {
        return (bool) get_option(self::DARK_MODE_OPTION, false);
    }

    /**
     * Verificar si un módulo tiene todas sus dependencias activas
     * 
     * @param string $module_id ID del módulo a verificar
     * @return array ['valid' => bool, 'missing' => array] Resultado de la validación
     */
    public function check_dependencies($module_id)
    {
        if (!isset($this->modules[$module_id])) {
            return [
                'valid' => false,
                'missing' => [],
                'error' => sprintf(__('Módulo "%s" no encontrado', 'alquipress'), $module_id)
            ];
        }

        $module = $this->modules[$module_id];
        $dependencies = isset($module['dependencies']) ? $module['dependencies'] : [];
        
        if (empty($dependencies)) {
            return ['valid' => true, 'missing' => []];
        }

        $missing = [];
        foreach ($dependencies as $dep_id) {
            if (empty($this->active_modules[$dep_id])) {
                $missing[] = $dep_id;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing
        ];
    }

    public function load_active_modules()
    {
        $loaded_modules = [];
        $failed_modules = [];

        foreach ($this->active_modules as $module_id => $is_active) {
            if ($is_active && isset($this->modules[$module_id])) {
                // Verificar dependencias antes de cargar
                $deps_check = $this->check_dependencies($module_id);
                
                if (!$deps_check['valid']) {
                    $failed_modules[$module_id] = $deps_check;
                    // Continuar con otros módulos, pero registrar el error
                    if (current_user_can('manage_options')) {
                        add_action('admin_notices', function() use ($module_id, $deps_check) {
                            $missing_names = array_map(function($dep_id) {
                                return isset($this->modules[$dep_id]) 
                                    ? $this->modules[$dep_id]['name'] 
                                    : $dep_id;
                            }, $deps_check['missing']);
                            
                            echo '<div class="notice notice-warning is-dismissible">';
                            echo '<p><strong>' . esc_html__('Alquipress:', 'alquipress') . '</strong> ';
                            printf(
                                esc_html__('El módulo "%s" no se pudo cargar porque faltan las siguientes dependencias: %s', 'alquipress'),
                                esc_html($this->modules[$module_id]['name'] ?? $module_id),
                                esc_html(implode(', ', $missing_names))
                            );
                            echo '</p></div>';
                        });
                    }
                    continue;
                }

                $file = ALQUIPRESS_PATH . 'includes/modules/' . $this->modules[$module_id]['file'];
                if (file_exists($file)) {
                    require_once $file;
                    $loaded_modules[] = $module_id;
                } else {
                    $failed_modules[$module_id] = [
                        'valid' => false,
                        'error' => sprintf(__('Archivo no encontrado: %s', 'alquipress'), $file)
                    ];
                }
            }
        }
        
        // Ya no forzamos módulos desactivados: si el usuario los desactivó en Ajustes,
        // la página correspondiente mostrará un mensaje de módulo inactivo a través
        // del fallback del router (sección no disponible). Esto respeta la configuración
        // del usuario y evita cargar código innecesario.
    }

    public function add_settings_page()
    {
        add_menu_page(
            __('Dashboard', 'alquipress'),
            __('Dashboard', 'alquipress'),
            'manage_options',
            'alquipress-settings',
            [$this, 'router_render_section'],
            'dashicons-admin-multisite',
            3
        );
        add_action('admin_menu', [$this, 'register_dashboard_sections'], 11);
    }

    /**
     * Registrar todas las secciones del Dashboard en orden: Panel, Propiedades, Reservas, Propietarios, Finanzas, Informes, Pipeline, Ajustes.
     */
    public function register_dashboard_sections()
    {
        remove_submenu_page('alquipress-settings', 'alquipress-settings');

        add_submenu_page(
            'alquipress-settings',
            __('Buscar', 'alquipress'),
            __('Buscar', 'alquipress'),
            'edit_posts',
            'alquipress-search',
            [$this, 'router_render_section']
        );
        remove_submenu_page('alquipress-settings', 'alquipress-search');

        add_submenu_page(
            'alquipress-settings',
            __('Panel', 'alquipress'),
            __('Panel', 'alquipress'),
            'manage_options',
            'alquipress-dashboard',
            [$this, 'router_render_section']
        );
        add_submenu_page(
            'alquipress-settings',
            __('Propiedades', 'alquipress'),
            __('Propiedades', 'alquipress'),
            'edit_posts',
            'alquipress-properties',
            [$this, 'router_render_section']
        );
        add_submenu_page(
            'alquipress-settings',
            __('Reservas', 'alquipress'),
            __('Reservas', 'alquipress'),
            'edit_shop_orders',
            'alquipress-bookings',
            [$this, 'router_render_section']
        );
        add_submenu_page(
            'alquipress-settings',
            __('Clientes', 'alquipress'),
            __('Clientes', 'alquipress'),
            'edit_users',
            'alquipress-clients',
            [$this, 'router_render_section']
        );
        add_submenu_page(
            'alquipress-settings',
            __('Propietarios', 'alquipress'),
            __('Propietarios', 'alquipress'),
            'edit_posts',
            'alquipress-owners',
            [$this, 'router_render_section']
        );
        add_submenu_page(
            'alquipress-settings',
            __('Finanzas', 'alquipress'),
            __('Finanzas', 'alquipress'),
            'edit_shop_orders',
            'alquipress-finanzas',
            [$this, 'router_render_section']
        );
        if (!empty($this->active_modules['owner-invoicing'])) {
            add_submenu_page(
                'alquipress-settings',
                __('Facturación propietarios', 'alquipress'),
                __('Facturación propietarios', 'alquipress'),
                'manage_options',
                'alquipress-owner-invoicing',
                [$this, 'router_render_section']
            );
        }
        if (!empty($this->active_modules['accounting'])) {
            add_submenu_page(
                'alquipress-settings',
                __('Contabilidad', 'alquipress'),
                __('Contabilidad', 'alquipress'),
                'manage_options',
                'alquipress-accounting',
                [$this, 'router_render_section']
            );
        }
        add_submenu_page(
            'alquipress-settings',
            __('Informes', 'alquipress'),
            __('Informes', 'alquipress'),
            'manage_options',
            'alquipress-reports',
            [$this, 'router_render_section']
        );

        if (!empty($this->active_modules['pipeline-kanban'])) {
            add_submenu_page(
                'alquipress-settings',
                __('Pipeline', 'alquipress'),
                __('Pipeline', 'alquipress'),
                'edit_shop_orders',
                'alquipress-pipeline',
                [$this, 'router_render_section']
            );
        }

        if (!empty($this->active_modules['communications'])) {
            add_submenu_page(
                'alquipress-settings',
                __('Inbox', 'alquipress'),
                __('Inbox', 'alquipress'),
                'manage_options',
                'alquipress-comunicacion',
                [$this, 'router_render_section']
            );
        }
        if (!empty($this->active_modules['email-campaigns'])) {
            add_submenu_page(
                'alquipress-settings',
                __('Campañas de email', 'alquipress'),
                __('Campañas de email', 'alquipress'),
                'manage_options',
                'alquipress-email-campaigns',
                [$this, 'router_render_section']
            );
        }

        add_submenu_page(
            'alquipress-settings',
            __('Ajustes', 'alquipress'),
            __('Ajustes', 'alquipress'),
            'manage_options',
            'alquipress-settings',
            [$this, 'router_render_section']
        );
    }

    /**
     * Router: despacha la sección correspondiente (todo dentro del mismo Dashboard).
     */
    public function router_render_section()
    {
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

        if ($page === 'alquipress-dashboard') {
            $this->render_dashboard_page();
            return;
        }

        if ($page === 'alquipress-settings') {
            $this->render_settings_page();
            return;
        }

        if ($page === 'alquipress-search') {
            require_once ALQUIPRESS_PATH . 'includes/admin/global-search-page.php';
            return;
        }

        if ($page === 'alquipress-finanzas') {
            // Cargar assets antes de renderizar
            do_action('alquipress_enqueue_section_assets', $page);
            require_once ALQUIPRESS_PATH . 'includes/admin/finances-page.php';
            return;
        }

        // Cargar assets antes de renderizar cualquier sección
        do_action('alquipress_enqueue_section_assets', $page);

        ob_start();
        do_action('alquipress_render_section', $page);
        $content = ob_get_clean();

        if (empty(trim($content))) {
            echo '<div class="wrap"><h1>' . esc_html__('Sección no disponible', 'alquipress') . '</h1><p>' . esc_html__('El módulo correspondiente a esta sección no está activo o no genera contenido. Por favor, verifica la configuración en Ajustes.', 'alquipress') . '</p></div>';
        } else {
            echo $content;
        }
    }

    /**
     * Renderizar la página del Panel (Dashboard).
     */
    public function render_dashboard_page()
    {
        if (!empty($GLOBALS['alquipress_dashboard_widgets']) && is_object($GLOBALS['alquipress_dashboard_widgets'])) {
            $GLOBALS['alquipress_dashboard_widgets']->render_full_dashboard();
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Panel', 'alquipress') . '</h1><p>' . esc_html__('Activa el módulo Dashboard Widgets en Ajustes para ver el panel de control.', 'alquipress') . '</p><p><a href="' . esc_url(admin_url('admin.php?page=alquipress-settings')) . '" class="button button-primary">' . esc_html__('Ir a Ajustes', 'alquipress') . '</a></p></div>';
        }
    }

    public function handle_form_submit()
    {
        $is_modules_submit = isset($_POST['alquipress_save_modules']);
        $is_bookings_submit = isset($_POST['alquipress_save_bookings_settings']);

        if (!$is_modules_submit && !$is_bookings_submit) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para modificar esta configuración.', 'alquipress'), 403);
        }

        if (isset($_POST['alquipress_save_modules']) && check_admin_referer('alquipress_modules_nonce')) {
            $new_modules = [];
            foreach ($this->modules as $id => $module) {
                $new_modules[$id] = isset($_POST['modules'][$id]);
            }
            update_option('alquipress_modules', $new_modules);
            $this->active_modules = $new_modules;

            $dashboard_template = isset($_POST['dashboard_template']) ? wp_unslash($_POST['dashboard_template']) : self::DASHBOARD_TEMPLATE_DEFAULT;
            $dashboard_template = self::sanitize_dashboard_template($dashboard_template);
            update_option(self::DASHBOARD_TEMPLATE_OPTION, $dashboard_template);

            $dark_mode = isset($_POST['dark_mode']) ? '1' : '0';
            update_option(self::DARK_MODE_OPTION, $dark_mode);

            add_settings_error(
                'alquipress_messages',
                'alquipress_message',
                '✓ Módulos y plantilla de dashboard actualizados correctamente.',
                'success'
            );
        } elseif (isset($_POST['alquipress_save_bookings_settings']) && check_admin_referer('alquipress_bookings_settings_nonce')) {
            $deposit = isset($_POST['ap_bookings_default_deposit_pct']) ? (float) wp_unslash($_POST['ap_bookings_default_deposit_pct']) : 40.0;
            $min_nights = isset($_POST['ap_bookings_default_min_nights']) ? (int) wp_unslash($_POST['ap_bookings_default_min_nights']) : 1;
            $max_nights = isset($_POST['ap_bookings_default_max_nights']) ? (int) wp_unslash($_POST['ap_bookings_default_max_nights']) : 365;

            if ($deposit < 0) {
                $deposit = 0;
            }
            if ($deposit > 100) {
                $deposit = 100;
            }
            if ($min_nights < 1) {
                $min_nights = 1;
            }
            if ($max_nights < $min_nights) {
                $max_nights = $min_nights;
            }

            update_option('ap_bookings_default_deposit_pct', $deposit);
            update_option('ap_bookings_default_min_nights', $min_nights);
            update_option('ap_bookings_default_max_nights', $max_nights);

            add_settings_error(
                'alquipress_messages',
                'alquipress_bookings_message',
                '✓ Ajustes del motor de reservas actualizados correctamente.',
                'success'
            );
        }
    }

    public function render_settings_page()
    {
        // Mostrar mensajes (guardado se maneja en handle_form_submit via admin_init)
        settings_errors('alquipress_messages');

        // Pasar variables al template para evitar dependencia de $this en contexto incluido
        $modules = $this->modules;
        $active_modules = $this->active_modules;
        require_once ALQUIPRESS_PATH . 'includes/admin/settings-page.php';
    }
}
