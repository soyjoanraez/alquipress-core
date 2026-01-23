<?php
class Alquipress_Module_Manager
{

    private $modules = [];
    private $active_modules = [];

    public function __construct()
    {
        $this->register_modules();
        $this->active_modules = get_option('alquipress_modules', []);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'handle_form_submit']);
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
            ]
        ];
    }

    public function load_active_modules()
    {
        foreach ($this->active_modules as $module_id => $is_active) {
            if ($is_active && isset($this->modules[$module_id])) {
                $file = ALQUIPRESS_PATH . 'includes/modules/' . $this->modules[$module_id]['file'];
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        }
    }

    public function add_settings_page()
    {
        add_menu_page(
            'ALQUIPRESS',
            'ALQUIPRESS',
            'manage_options',
            'alquipress-settings',
            [$this, 'render_settings_page'],
            'dashicons-admin-multisite',
            3
        );
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
        // Guardar cambios
        if (isset($_POST['alquipress_save_modules'])) {
            check_admin_referer('alquipress_modules_nonce');
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

        // Mostrar mensajes
        settings_errors('alquipress_messages');

        // Render interfaz
        require_once ALQUIPRESS_PATH . 'includes/admin/settings-page.php';
    }
}

