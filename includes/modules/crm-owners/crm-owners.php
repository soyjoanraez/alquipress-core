<?php
/**
 * Módulo: CRM de Propietarios
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_CRM_Owners
{

    public function __construct()
    {
        add_action('init', [$this, 'register_cpt']);
        add_action('acf/init', [$this, 'load_acf_fields']);
        add_filter('manage_propietario_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_propietario_posts_custom_column', [$this, 'populate_custom_columns'], 10, 2);
        add_action('acf/input/admin_footer', [$this, 'enqueue_iban_mask']);
        add_filter('post_row_actions', [$this, 'add_row_actions'], 10, 2);

        // Cargar vista detallada
        require_once dirname(__FILE__) . '/owner-profile.php';
    }

    /**
     * Enqueue assets para IBAN enmascarado
     */
    public function enqueue_iban_mask()
    {
        global $post;

        // Solo cargar en páginas de edición de propietarios
        if ($post && $post->post_type === 'propietario') {
            wp_enqueue_script(
                'alquipress-iban-mask',
                ALQUIPRESS_URL . 'includes/modules/crm-owners/assets/iban-mask.js',
                ['jquery'],
                ALQUIPRESS_VERSION,
                true
            );

            wp_enqueue_style(
                'alquipress-iban-mask',
                ALQUIPRESS_URL . 'includes/modules/crm-owners/assets/iban-mask.css',
                [],
                ALQUIPRESS_VERSION
            );

            // Pasar datos al JS
            wp_localize_script('alquipress-iban-mask', 'ibanMaskData', [
                'userLogin' => wp_get_current_user()->user_login,
                'ownerId' => $post->ID,
                'nonce' => wp_create_nonce('alquipress_iban_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php')
            ]);
        }
    }

    public function load_acf_fields()
    {
        // Verificar que ACF esté disponible
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        
        $json_file = dirname(__FILE__) . '/acf-fields.json';
        if (!file_exists($json_file)) {
            return;
        }
        
        $json = file_get_contents($json_file);
        $fields = json_decode($json, true);
        
        if (!is_array($fields)) {
            return;
        }
        
        // Cargar cada grupo de campos solo si no existe ya
        foreach ($fields as $field_group) {
            // Verificar que el grupo tenga una key válida
            if (!isset($field_group['key']) || empty($field_group['key'])) {
                continue;
            }
            
            // Verificar si el grupo ya existe antes de agregarlo
            if (!function_exists('acf_is_local_field_group') || !acf_is_local_field_group($field_group['key'])) {
                acf_add_local_field_group($field_group);
            }
        }
    }

    public function register_cpt()
    {
        register_post_type('propietario', [
            'public' => false,
            'show_ui' => true,
            'label' => 'Propietarios',
            'labels' => [
                'name' => 'Propietarios',
                'singular_name' => 'Propietario',
                'add_new' => 'Añadir Propietario',
                'add_new_item' => 'Añadir Nuevo Propietario',
                'edit_item' => 'Editar Propietario',
                'new_item' => 'Nuevo Propietario',
                'view_item' => 'Ver Propietario',
                'search_items' => 'Buscar Propietarios',
                'not_found' => 'No se encontraron propietarios',
                'not_found_in_trash' => 'No se encontraron propietarios en la papelera',
            ],
            'supports' => ['title'],
            'menu_icon' => 'dashicons-businessperson',
            'menu_position' => 26,
            'capability_type' => 'post',
            'show_in_rest' => false,
            'has_archive' => false,
            'hierarchical' => false,
        ]);
    }

    public function add_custom_columns($columns)
    {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['owner_email'] = 'Email';
        $new_columns['owner_phone'] = 'Teléfono';
        $new_columns['owner_properties'] = 'Propiedades';
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    public function populate_custom_columns($column, $post_id)
    {
        if ($column == 'owner_email') {
            $email = get_post_meta($post_id, 'owner_email_management', true);
            echo $email ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '-';
        }

        if ($column == 'owner_phone') {
            $phone = get_post_meta($post_id, 'owner_phone', true);
            echo $phone ? esc_html($phone) : '-';
        }

        if ($column == 'owner_properties') {
            $properties = get_post_meta($post_id, 'owner_properties', true);
            if (is_array($properties) && !empty($properties)) {
                echo count($properties) . ' propiedades';
            } else {
                echo '-';
            }
        }
    }

    /**
     * Añadir acción "Ver Ficha" en la lista de propietarios
     */
    public function add_row_actions($actions, $post)
    {
        if ($post->post_type === 'propietario') {
            $actions['view_profile'] = '<a href="' . admin_url('admin.php?page=alquipress-owner-profile&owner_id=' . $post->ID) . '" style="color: #3b82f6; font-weight: 600;">Ver Ficha CRM</a>';
        }
        return $actions;
    }
}

new Alquipress_CRM_Owners();

// Cargar módulo de cálculo de ingresos
require_once dirname(__FILE__) . '/owner-revenue.php';

// Cargar endpoint seguro de liquidaciones (PDF firmado y con expiración)
$owner_settlement_endpoint = dirname(__FILE__) . '/owner-settlement-endpoint.php';
if (file_exists($owner_settlement_endpoint)) {
    require_once $owner_settlement_endpoint;
}
