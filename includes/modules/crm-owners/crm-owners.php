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
    }

    public function load_acf_fields()
    {
        $json_file = dirname(__FILE__) . '/acf-fields.json';
        if (file_exists($json_file)) {
            $json = file_get_contents($json_file);
            $fields = json_decode($json, true);
            if (function_exists('acf_add_local_field_group') && is_array($fields)) {
                foreach ($fields as $field_group) {
                    acf_add_local_field_group($field_group);
                }
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
}

new Alquipress_CRM_Owners();

// Cargar módulo de cálculo de ingresos
require_once dirname(__FILE__) . '/owner-revenue.php';
