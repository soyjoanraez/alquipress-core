<?php
namespace Alquipress\Suite\Core;

if (!defined('ABSPATH'))
    exit;

class Manager
{

    private static $instance = null;
    private $modules = [];
    private $active_modules = [];

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->load_active_modules_data();
        $this->define_modules();
        $this->initialize_modules();

        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_menu'], 20);
        }
    }

    private function define_modules()
    {
        $this->modules = [
            'wpo' => [
                'name' => 'WPO Module',
                'description' => 'Lazy load, Critical CSS y optimización de JS/CSS.',
                'class' => '\\Alquipress\\Suite\\Modules\\WPO\\Module',
                'priority' => 10,
            ],
            'image_optimizer' => [
                'name' => 'Image Optimizer',
                'description' => 'Conversión WebP, limpieza EXIF y srcset avanzado.',
                'class' => '\\Alquipress\\Suite\\Modules\\ImageOptimizer\\Module',
                'priority' => 10,
            ],
            'security' => [
                'name' => 'Security Suite',
                'description' => 'Firewall para reservas, Audit Log y Honeypot.',
                'class' => '\\Alquipress\\Suite\\Modules\\Security\\Module',
                'priority' => 20,
            ]
        ];
    }

    private function load_active_modules_data()
    {
        $this->active_modules = get_option('alq_suite_active_modules', [
            'wpo' => true,
            'image_optimizer' => true,
            'security' => false
        ]);
    }

    private function initialize_modules()
    {
        foreach ($this->modules as $id => $config) {
            if (!empty($this->active_modules[$id])) {
                if (class_exists($config['class'])) {
                    $config['class']::instance();
                }
            }
        }
    }

    public function add_menu()
    {
        add_submenu_page(
            'alquipress-settings', // Parent slug (from alquipress-core)
            'ALQUIPRESS Suite',
            'Performance & Security',
            'manage_options',
            'alquipress-suite',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page()
    {
        // Cargar assets del dashboard antes de renderizar
        if (function_exists('do_action')) {
            do_action('alquipress_enqueue_section_assets', 'alquipress-suite');
        }
        
        require_once ALQ_SUITE_PATH . 'includes/admin/settings-view.php';
    }

    public function get_modules()
    {
        return $this->modules;
    }

    public function is_module_active($id)
    {
        return !empty($this->active_modules[$id]);
    }
}
