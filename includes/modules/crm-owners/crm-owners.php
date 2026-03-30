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
        add_filter('manage_propietario_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_propietario_posts_custom_column', [$this, 'populate_custom_columns'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_iban_mask']);
        add_filter('post_row_actions', [$this, 'add_row_actions'], 10, 2);

        // Meta box nativo (reemplaza ACF)
        require_once dirname(__FILE__) . '/class-owner-metabox.php';
        new Alquipress_Owner_Metabox();

        // Cargar vista detallada
        require_once dirname(__FILE__) . '/owner-profile.php';
    }

    /**
     * Encolar assets para IBAN enmascarado (solo en edición de propietarios).
     */
    public function enqueue_iban_mask(string $hook): void
    {
        global $post;

        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        if (!$post || $post->post_type !== 'propietario') {
            return;
        }

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

        wp_localize_script('alquipress-iban-mask', 'ibanMaskData', [
            'userLogin' => wp_get_current_user()->user_login,
            'ownerId'   => $post->ID,
            'nonce'     => wp_create_nonce('alquipress_iban_nonce'),
            'ajaxUrl'   => admin_url('admin-ajax.php'),
        ]);
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
