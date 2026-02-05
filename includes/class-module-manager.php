<?php
class Alquipress_Module_Manager
{

    private $modules = [];
    private $active_modules = [];

    public function __construct()
    {
        $this->register_modules();
        $this->active_modules = get_option('alquipress_modules', []);
        // Si la opción está vacía (plugin no activado por hook o opción borrada), usar defaults para que Panel/Propiedades/Propietarios funcionen
        if (empty($this->active_modules)) {
            $this->active_modules = [
                'taxonomies' => true,
                'crm-guests' => true,
                'crm-owners' => true,
                'booking-pipeline' => true,
                'email-automation' => true,
                'seo-master' => true,
                'booking-enforcer' => true,
                'order-columns' => true,
                'dashboard-widgets' => true,
                'properties-page' => true,
                'property-editor' => true,
                'owners-page' => true,
                'bookings-page' => true,
                'clients-page' => true,
                'booking-calendar-prices' => true,
                'operational-health' => true,
                'payment-pipeline' => true,
                'communications' => true,
                'payments' => false,
                'alquipress-tester' => false,
            ];
            update_option('alquipress_modules', $this->active_modules);
        }
        if (!array_key_exists('booking-calendar-prices', $this->active_modules)) {
            $this->active_modules['booking-calendar-prices'] = true;
            update_option('alquipress_modules', $this->active_modules);
        }
        if (!array_key_exists('communications', $this->active_modules)) {
            $this->active_modules['communications'] = true;
            update_option('alquipress_modules', $this->active_modules);
        }
        if (!array_key_exists('property-editor', $this->active_modules)) {
            $this->active_modules['property-editor'] = true;
            update_option('alquipress_modules', $this->active_modules);
        }
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'handle_form_submit']);
        add_action('admin_init', [$this, 'redirect_wp_dashboard_to_alquipress'], 5);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_settings_assets']);
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

        // Estilos críticos para Panel, Propiedades, Propietarios, Reservas, etc.: fondo claro y layout flex
        // Evita que tema/plugins (ej. Astra) cubran el contenido con fondo negro o rompan el layout.
        $critical_layout = '#wpcontent,#wpbody-content{background:#f8fafb!important;}'
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
            ]
        ];
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
        
        // Forzar siempre la carga de los módulos de páginas del menú para que Panel, Propiedades, Reservas, Clientes y Propietarios funcionen
        $page_modules = ['dashboard-widgets', 'properties-page', 'owners-page', 'bookings-page', 'clients-page', 'communications'];
        foreach ($page_modules as $module_id) {
            if (!isset($this->modules[$module_id])) {
                continue;
            }
            
            // Solo cargar si no se cargó ya
            if (in_array($module_id, $loaded_modules, true)) {
                continue;
            }
            
            $file = ALQUIPRESS_PATH . 'includes/modules/' . $this->modules[$module_id]['file'];
            if (file_exists($file)) {
                require_once $file;
            }
        }
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
                __('Comunicación', 'alquipress'),
                __('Comunicación', 'alquipress'),
                'manage_options',
                'alquipress-comunicacion',
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
        if (isset($_POST['alquipress_save_modules']) && check_admin_referer('alquipress_modules_nonce')) {
            $new_modules = [];
            foreach ($this->modules as $id => $module) {
                $new_modules[$id] = isset($_POST['modules'][$id]);
            }
            update_option('alquipress_modules', $new_modules);
            $this->active_modules = $new_modules;

            add_settings_error(
                'alquipress_messages',
                'alquipress_message',
                '✓ Módulos actualizados correctamente.',
                'success'
            );
        }
    }

    public function render_settings_page()
    {
        // Mostrar mensajes (guardado se maneja en handle_form_submit via admin_init)
        settings_errors('alquipress_messages');

        // Render interfaz
        require_once ALQUIPRESS_PATH . 'includes/admin/settings-page.php';
    }
}
