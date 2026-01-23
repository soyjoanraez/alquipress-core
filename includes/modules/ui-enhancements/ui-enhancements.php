<?php
/**
 * Módulo: Mejoras UI
 * Mejoras visuales para CPTs y páginas de edición
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_UI_Enhancements
{

    public function __construct()
    {
        // Cargar estilos en admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Mejorar página de edición de propietario
        add_action('admin_head', [$this, 'owner_edit_page_styles']);

        // Mejorar página de edición de usuario (huéspedes)
        add_action('admin_head', [$this, 'guest_edit_page_styles']);
    }

    /**
     * Cargar assets CSS
     */
    public function enqueue_assets($hook)
    {
        // Páginas donde cargar los estilos
        $allowed_hooks = [
            'post.php',
            'post-new.php',
            'user-edit.php',
            'profile.php',
            'user-new.php'
        ];

        if (!in_array($hook, $allowed_hooks)) {
            return;
        }

        wp_enqueue_style(
            'alquipress-ui-enhancements',
            ALQUIPRESS_URL . 'includes/modules/ui-enhancements/assets/ui-enhancements.css',
            [],
            ALQUIPRESS_VERSION
        );
    }

    /**
     * Estilos inline para página de propietario
     */
    public function owner_edit_page_styles()
    {
        global $post;

        if (!$post || $post->post_type !== 'propietario') {
            return;
        }

        ?>
        <style>
            /* Warning Banner para Sección Financiera */
            .acf-tab-wrap .acf-tab-group li[data-key*="finance"],
            .acf-tab-wrap .acf-tab-group li[data-key*="financiero"] {
                background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                border-left: 4px solid #f59e0b;
                font-weight: 600;
            }

            .acf-tab-wrap .acf-tab-group li[data-key*="finance"]:hover,
            .acf-tab-wrap .acf-tab-group li[data-key*="financiero"]:hover {
                background: #fef3c7;
            }

            /* Estilo mejorado para campo IBAN */
            .acf-field[data-name="owner_iban"],
            .acf-field[data-name="datos_bancarios_iban"] {
                position: relative;
            }

            /* Indicador de campo sensible */
            .acf-field[data-name="owner_iban"]::before,
            .acf-field[data-name="datos_bancarios_iban"]::before {
                content: "⚠️ DATO SENSIBLE - ACCESO REGISTRADO";
                display: block;
                background: #dc3232;
                color: white;
                padding: 8px 15px;
                border-radius: 4px 4px 0 0;
                font-weight: 700;
                font-size: 11px;
                text-align: center;
                letter-spacing: 1px;
                margin: -20px -20px 15px -20px;
            }

            /* Mejorar tabs */
            .acf-tab-wrap .acf-tab-group {
                background: #f9fafb;
                border-radius: 8px;
                padding: 10px;
                margin-bottom: 20px;
            }

            .acf-tab-wrap .acf-tab-group li {
                border-radius: 6px;
                transition: all 0.2s;
            }

            .acf-tab-wrap .acf-tab-group li.active {
                background: white;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            /* Mejorar campos de contacto */
            .acf-field[data-name="owner_phone"],
            .acf-field[data-name="owner_email_management"],
            .acf-field[data-name="owner_whatsapp"] {
                background: #f0f6fc;
                padding: 15px;
                border-radius: 6px;
                border-left: 3px solid #2ea2cc;
            }

            /* Título del post más visible */
            #post-body #titlediv #title {
                font-size: 24px;
                font-weight: 600;
                padding: 12px;
            }
        </style>
        <?php
    }

    /**
     * Estilos inline para página de usuario (huésped)
     */
    public function guest_edit_page_styles()
    {
        $screen = get_current_screen();

        if (!$screen || !in_array($screen->id, ['user-edit', 'profile'])) {
            return;
        }

        ?>
        <style>
            /* Mejorar sección de campos ACF del huésped */
            .acf-field-group-crm-cliente {
                background: #f9fafb;
                border-radius: 8px;
                padding: 20px;
                margin-top: 20px;
            }

            /* Badge VIP en selector */
            .acf-field[data-name="guest_status"] select option[value="vip"] {
                background: #fbbf24;
                font-weight: bold;
            }

            .acf-field[data-name="guest_status"] select option[value="blacklist"] {
                background: #dc3232;
                color: white;
                font-weight: bold;
            }

            /* Rating con estrellas */
            .acf-field[data-name="guest_rating"] {
                background: #fffbeb;
                padding: 15px;
                border-radius: 6px;
                border-left: 3px solid #fbbf24;
            }

            /* Preferencias en grid */
            .acf-field[data-name="guest_preferences"] .acf-checkbox-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .acf-field[data-name="guest_preferences"] .acf-checkbox-list label {
                background: #f0f6fc;
                padding: 10px;
                border-radius: 4px;
                border: 2px solid #e5e7eb;
                transition: all 0.2s;
            }

            .acf-field[data-name="guest_preferences"] .acf-checkbox-list label:hover {
                border-color: #2ea2cc;
                background: #dbeafe;
            }

            .acf-field[data-name="guest_preferences"] .acf-checkbox-list input:checked+label {
                border-color: #2ea2cc;
                background: #dbeafe;
                font-weight: 600;
            }

            /* Notas privadas */
            .acf-field[data-name="guest_internal_notes"] {
                background: #fef2f2;
                padding: 15px;
                border-radius: 6px;
                border-left: 3px solid #dc3232;
            }

            .acf-field[data-name="guest_internal_notes"] .acf-label label::before {
                content: "🔒 ";
                font-size: 16px;
            }
        </style>
        <?php
    }
}

new Alquipress_UI_Enhancements();
