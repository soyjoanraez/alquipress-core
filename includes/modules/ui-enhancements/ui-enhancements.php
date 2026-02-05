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

        // Header tipo Dashboard en páginas de edición de CPTs Pencil
        add_action('edit_form_after_title', [$this, 'edit_screen_dashboard_header']);

        // Mejorar página de edición de propietario
        add_action('admin_head', [$this, 'owner_edit_page_styles']);

        // Mejorar página de edición de usuario (huéspedes)
        add_action('admin_head', [$this, 'guest_edit_page_styles']);
        
        // Añadir sidebar y layout para user-edit.php
        add_action('admin_footer', [$this, 'add_user_edit_sidebar']);
        
        // Ocultar metaboxes innecesarios en la edición de clientes
        add_action('admin_init', [$this, 'remove_unnecessary_user_meta_boxes'], 999);
        add_action('admin_head', [$this, 'hide_meta_boxes_css'], 999);
        
        // Cargar estilos del dashboard para páginas específicas
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_dashboard_styles_for_pages']);
    }

    /**
     * Cargar assets CSS
     */
    public function enqueue_assets($hook)
    {
        $screen = get_current_screen();

        // Tema Pencil para CPTs: listados y edición de propietario, product, shop_order
        $cpt_pencil_screens = [
            'edit-propietario',
            'propietario',
            'edit-product',
            'product',
            'edit-shop_order',
            'shop_order',
        ];
        if ($screen && in_array($screen->id, $cpt_pencil_screens, true)) {
            wp_enqueue_style(
                'alquipress-admin-pencil',
                ALQUIPRESS_URL . 'includes/modules/ui-enhancements/assets/admin-pencil.css',
                [],
                ALQUIPRESS_VERSION
            );
            return;
        }

        // Resto de páginas: mejoras UI (user-edit, profile, post.php/post-new sin CPT Pencil)
        $allowed_hooks = [
            'post.php',
            'post-new.php',
            'user-edit.php',
            'profile.php',
            'user-new.php',
        ];
        if (!in_array($hook, $allowed_hooks, true)) {
            return;
        }

        // Para user-edit.php y profile.php, si es un cliente, cargar layout del dashboard
        if (in_array($hook, ['user-edit.php', 'profile.php'], true)) {
            $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
            $user = get_userdata($user_id);
            if ($user && (in_array('customer', $user->roles) || in_array('subscriber', $user->roles))) {
                // Cargar CSS del layout del dashboard
                wp_enqueue_style(
                    'alquipress-admin-layout',
                    ALQUIPRESS_URL . 'includes/admin/assets/alquipress-admin-layout.css',
                    [],
                    ALQUIPRESS_VERSION
                );

                // Estilos críticos del layout
                $critical_layout = '#wpcontent,#wpbody-content{background:#f8fafb!important;}'
                    . '.wrap{min-height:80vh!important;width:100%!important;position:relative!important;z-index:999998!important;max-width:none!important;margin-top:12px!important;padding:0!important;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif!important;}'
                    . 'body.user-edit .wrap,body.profile .wrap{display:flex!important;min-height:calc(100vh - 140px)!important;background:#f8fafb!important;border:1px solid #e8eef3!important;border-radius:16px!important;overflow:hidden!important;}'
                    . 'body.user-edit #wpbody-content,body.profile #wpbody-content{flex:1!important;min-width:0!important;padding:0!important;background:transparent!important;}';
                wp_add_inline_style('alquipress-admin-layout', $critical_layout);
            }
        }

        wp_enqueue_style(
            'alquipress-ui-enhancements',
            ALQUIPRESS_URL . 'includes/modules/ui-enhancements/assets/ui-enhancements.css',
            [],
            ALQUIPRESS_VERSION
        );
    }

    /**
     * Cargar estilos del dashboard para páginas específicas (Kyero, Performance/Security)
     */
    public function enqueue_dashboard_styles_for_pages($page)
    {
        $dashboard_pages = ['alquipress-kyero', 'alquipress-suite'];
        
        if (!in_array($page, $dashboard_pages, true)) {
            return;
        }
        
        // Cargar CSS del layout del dashboard
        wp_enqueue_style(
            'alquipress-admin-layout',
            ALQUIPRESS_URL . 'includes/admin/assets/alquipress-admin-layout.css',
            [],
            ALQUIPRESS_VERSION
        );
        
        // Estilos críticos del layout
        $critical_layout = '#wpcontent,#wpbody-content{background:#f8fafb!important;}'
            . '.wrap.ap-has-sidebar{min-height:80vh!important;width:100%!important;position:relative!important;z-index:999998!important;max-width:none!important;margin-top:12px!important;padding:0!important;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif!important;}'
            . '.wrap.ap-has-sidebar .ap-owners-layout{display:flex!important;min-height:calc(100vh - 140px)!important;background:#f8fafb!important;border:1px solid #e8eef3!important;border-radius:16px!important;overflow:hidden!important;}'
            . '.wrap.ap-has-sidebar .ap-owners-sidebar{width:256px!important;min-width:256px!important;background:#ffffff!important;border-right:1px solid #e8eef3!important;display:flex!important;flex-direction:column!important;}'
            . '.wrap.ap-has-sidebar .ap-owners-main{flex:1!important;min-width:0!important;padding:32px!important;background:#f8fafb!important;}';
        wp_add_inline_style('alquipress-admin-layout', $critical_layout);
    }

    /**
     * Header tipo Dashboard en pantalla de edición (propietario, product, shop_order)
     * Para product (propiedad) se renderiza el layout completo Pencil "Edit Property".
     */
    public function edit_screen_dashboard_header($post)
    {
        if (!$post || !is_a($post, 'WP_Post')) {
            return;
        }
        if ($post->post_type === 'product') {
            require_once ALQUIPRESS_PATH . 'includes/admin/property-edit-layout.php';
            alquipress_render_property_edit_layout($post);
            return;
        }
        $cpt_slugs = ['propietario', 'shop_order'];
        if (!in_array($post->post_type, $cpt_slugs, true)) {
            return;
        }

        $list_url = admin_url('edit.php?post_type=' . $post->post_type);
        $labels = [
            'propietario' => ['list' => __('Propietarios', 'alquipress'), 'edit' => __('Editar propietario', 'alquipress')],
            'shop_order'  => ['list' => __('Pedidos', 'alquipress'), 'edit' => __('Editar pedido', 'alquipress')],
        ];
        $label_list = $labels[$post->post_type]['list'] ?? $post->post_type;
        $label_edit = $labels[$post->post_type]['edit'] ?? __('Editar', 'alquipress');
        ?>
        <div class="ap-edit-header-bar">
            <a href="<?php echo esc_url($list_url); ?>" class="ap-edit-back-link">&larr; <?php echo esc_html(sprintf(__('Volver a %s', 'alquipress'), $label_list)); ?></a>
            <span class="ap-edit-subtitle"><?php echo esc_html($label_edit); ?></span>
        </div>
        <?php
    }

    /**
     * Layout Pencil "Edit Property Screen": breadcrumb, hero, property header, quick stats bar.
     */
    private function render_property_edit_pencil_layout($post)
    {
        require_once ALQUIPRESS_PATH . 'includes/admin/property-edit-layout.php';
        alquipress_render_property_edit_layout($post);
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

        // Solo aplicar si es un usuario con rol customer (cliente) o subscriber
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
        $user = get_userdata($user_id);
        if (!$user || (!in_array('customer', $user->roles) && !in_array('subscriber', $user->roles))) {
            return;
        }

        ?>
        <style>
            /* Estilos consistentes con dashboard Alquipress */
            :root {
                --ap-background: #f8fafb;
                --ap-surface: #ffffff;
                --ap-border: #e8eef3;
                --ap-primary: #2c99e2;
                --ap-text-primary: #0e161b;
                --ap-text-secondary: #507a95;
                --ap-warning: #f59e0b;
                --ap-radius-lg: 12px;
                --ap-radius-md: 8px;
            }

            /* Mejorar sección de campos ACF del huésped */
            .acf-field-group-crm-cliente {
                background: var(--ap-surface);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius-lg);
                padding: 24px;
                margin-top: 24px;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            }

            .acf-field-group-crm-cliente .acf-field-group-header {
                border-bottom: 1px solid var(--ap-border);
                padding-bottom: 12px;
                margin-bottom: 20px;
            }

            /* Badge VIP en selector */
            .acf-field[data-name="guest_status"] select {
                height: 36px;
                padding: 0 10px;
                border: 1px solid var(--ap-border);
                border-radius: 6px;
                font-size: 14px;
            }

            .acf-field[data-name="guest_status"] select option[value="vip"] {
                background: #fef3c7;
                color: #92400e;
                font-weight: 600;
            }

            .acf-field[data-name="guest_status"] select option[value="blacklist"] {
                background: #fee2e2;
                color: #991b1b;
                font-weight: 600;
            }

            /* Rating con estrellas */
            .acf-field[data-name="guest_rating"] {
                background: #fffbeb;
                padding: 16px;
                border-radius: var(--ap-radius-md);
                border-left: 3px solid var(--ap-warning);
            }

            .acf-field[data-name="guest_rating"] input[type="number"] {
                height: 36px;
                padding: 0 10px;
                border: 1px solid var(--ap-border);
                border-radius: 6px;
                font-size: 14px;
            }

            /* Preferencias en grid */
            .acf-field[data-name="guest_preferences"] .acf-checkbox-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .acf-field[data-name="guest_preferences"] .acf-checkbox-list label {
                background: var(--ap-background);
                padding: 12px;
                border-radius: var(--ap-radius-md);
                border: 2px solid var(--ap-border);
                transition: all 0.2s;
            }

            .acf-field[data-name="guest_preferences"] .acf-checkbox-list label:hover {
                border-color: var(--ap-primary);
                background: rgba(44, 153, 226, 0.06);
            }

            .acf-field[data-name="guest_preferences"] .acf-checkbox-list input:checked + label {
                border-color: var(--ap-primary);
                background: rgba(44, 153, 226, 0.1);
                font-weight: 600;
            }

            /* Notas privadas */
            .acf-field[data-name="guest_internal_notes"] {
                background: #fffbeb;
                padding: 16px;
                border-radius: var(--ap-radius-md);
                border-left: 3px solid var(--ap-warning);
            }

            .acf-field[data-name="guest_internal_notes"] .acf-label label::before {
                content: "🔒 ";
                font-size: 16px;
            }

            /* Documentos mejorados */
            .acf-field[data-name="guest_documents"] {
                background: var(--ap-surface);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius-lg);
                padding: 20px;
            }

            .acf-field[data-name="guest_documents"] .acf-repeater {
                border: none;
            }

            .acf-field[data-name="guest_documents"] .acf-repeater .acf-row {
                background: var(--ap-background);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius-md);
                margin-bottom: 12px;
            }

            .acf-field[data-name="guest_documents"] .acf-file-uploader {
                background: var(--ap-surface);
                border: 2px dashed var(--ap-border);
                border-radius: var(--ap-radius-md);
                padding: 20px;
            }

            /* Inputs consistentes */
            .acf-field input[type="text"],
            .acf-field input[type="email"],
            .acf-field input[type="tel"],
            .acf-field input[type="date"],
            .acf-field select {
                height: 36px;
                padding: 0 10px;
                border: 1px solid var(--ap-border);
                border-radius: 6px;
                font-size: 14px;
            }

            .acf-field input:focus,
            .acf-field select:focus {
                border-color: var(--ap-primary);
                outline: none;
                box-shadow: 0 0 0 3px rgba(44, 153, 226, 0.1);
            }

            /* Layout del dashboard para user-edit */
            body.user-edit .wrap,
            body.profile .wrap {
                display: flex !important;
                min-height: calc(100vh - 140px) !important;
                background: #f8fafb !important;
                border: 1px solid #e8eef3 !important;
                border-radius: 16px !important;
                overflow: hidden !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                position: relative !important;
            }

            body.user-edit .wrap .ap-owners-layout,
            body.profile .wrap .ap-owners-layout {
                width: 100% !important;
                display: flex !important;
            }

            body.user-edit #wpbody-content,
            body.profile #wpbody-content {
                flex: 1 !important;
                min-width: 0 !important;
                padding: 0 !important;
                background: transparent !important;
            }

            /* Ocultar sidebar de WordPress si existe */
            body.user-edit #wpbody,
            body.profile #wpbody {
                margin: 0 !important;
            }

            /* Mejorar header de la página */
            body.user-edit .wrap h1,
            body.profile .wrap h1 {
                font-size: 28px !important;
                font-weight: 700 !important;
                color: var(--ap-text-primary) !important;
                margin-bottom: 8px !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            }

            /* Mejorar formularios */
            body.user-edit form-table th,
            body.profile form-table th {
                font-weight: 600 !important;
                font-size: 13px !important;
                color: var(--ap-text-secondary) !important;
                padding: 12px 10px 12px 0 !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            }

            body.user-edit form-table td,
            body.profile form-table td {
                padding: 12px 10px !important;
            }

            body.user-edit input[type="text"],
            body.user-edit input[type="email"],
            body.user-edit input[type="password"],
            body.user-edit input[type="url"],
            body.user-edit select,
            body.profile input[type="text"],
            body.profile input[type="email"],
            body.profile input[type="password"],
            body.profile input[type="url"],
            body.profile select {
                height: 36px !important;
                padding: 0 10px !important;
                border: 1px solid var(--ap-border) !important;
                border-radius: 6px !important;
                font-size: 14px !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
            }

            body.user-edit input:focus,
            body.user-edit select:focus,
            body.profile input:focus,
            body.profile select:focus {
                border-color: var(--ap-primary) !important;
                outline: none !important;
                box-shadow: 0 0 0 3px rgba(44, 153, 226, 0.1) !important;
            }

            /* Mejorar botones */
            body.user-edit .button,
            body.profile .button {
                height: 36px !important;
                padding: 0 18px !important;
                border-radius: var(--ap-radius-md) !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                transition: background 0.2s, border-color 0.2s !important;
            }

            body.user-edit .button-primary,
            body.profile .button-primary {
                background: var(--ap-primary) !important;
                border-color: var(--ap-primary) !important;
                color: #fff !important;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
            }

            body.user-edit .button-primary:hover,
            body.profile .button-primary:hover {
                background: #2380c7 !important;
                border-color: #2380c7 !important;
            }

            /* Mejorar postboxes */
            body.user-edit .postbox,
            body.profile .postbox {
                background: var(--ap-surface) !important;
                border: 1px solid var(--ap-border) !important;
                border-radius: var(--ap-radius-lg) !important;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
                margin-bottom: 20px !important;
            }

            body.user-edit .postbox .postbox-header,
            body.profile .postbox .postbox-header {
                background: var(--ap-background) !important;
                border-bottom: 1px solid var(--ap-border) !important;
                padding: 12px 16px !important;
            }

            body.user-edit .postbox .postbox-header h2,
            body.profile .postbox .postbox-header h2 {
                font-size: 16px !important;
                font-weight: 600 !important;
                color: var(--ap-text-primary) !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            }

            body.user-edit .postbox .inside,
            body.profile .postbox .inside {
                padding: 20px !important;
            }
        </style>
        <?php
    }

    /**
     * Añadir sidebar y layout para user-edit.php usando JavaScript
     */
    public function add_user_edit_sidebar()
    {
        $screen = get_current_screen();
        
        // Debug: verificar screen
        if (!$screen) {
            // Intentar detectar por hook o URL
            global $pagenow;
            if ($pagenow !== 'user-edit.php' && $pagenow !== 'profile.php') {
                return;
            }
        } elseif (!in_array($screen->id, ['user-edit', 'profile'])) {
            return;
        }

        // Solo añadir sidebar si es customer o subscriber
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
        $user = get_userdata($user_id);
        $is_customer_or_subscriber = $user && (in_array('customer', $user->roles) || in_array('subscriber', $user->roles));
        
        if ($is_customer_or_subscriber) {
            require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
            
            // Obtener el HTML del sidebar
            ob_start();
            alquipress_render_sidebar('clients');
            $sidebar_html = ob_get_clean();
        } else {
            $sidebar_html = '';
        }

        ?>
        <div id="ap-sidebar-html" style="display:none;"><?php echo $sidebar_html; ?></div>
        <script>
        (function() {
            function initUserEditLayout() {
                // Verificar si estamos en la página correcta
                var isUserEditPage = document.body.classList.contains('user-edit') || 
                                     document.body.classList.contains('profile') ||
                                     window.location.href.indexOf('user-edit.php') !== -1 ||
                                     window.location.href.indexOf('profile.php') !== -1;
                
                if (!isUserEditPage) {
                    return;
                }

                var wrap = document.querySelector('.wrap');
                if (!wrap || wrap.querySelector('.ap-owners-layout')) {
                    return; // Ya está inicializado
                }

                // Obtener HTML del sidebar desde el div oculto
                var sidebarContainer = document.getElementById('ap-sidebar-html');
                if (!sidebarContainer || !sidebarContainer.innerHTML.trim()) {
                    return;
                }
                
                var sidebarHtml = sidebarContainer.innerHTML;

                // Crear contenedor del layout
                var layout = document.createElement('div');
                layout.className = 'ap-owners-layout';
                layout.style.cssText = 'display:flex!important;min-height:calc(100vh - 140px)!important;background:#f8fafb!important;border:1px solid #e8eef3!important;border-radius:16px!important;overflow:hidden!important;';

                // Crear sidebar
                var sidebar = document.createElement('aside');
                sidebar.className = 'ap-owners-sidebar';
                sidebar.style.cssText = 'width:256px!important;min-width:256px!important;background:#ffffff!important;border-right:1px solid #e8eef3!important;display:flex!important;flex-direction:column!important;';
                sidebar.innerHTML = sidebarHtml;

                // Crear main content wrapper
                var main = document.createElement('main');
                main.className = 'ap-owners-main';
                main.style.cssText = 'flex:1!important;min-width:0!important;padding:32px!important;background:#f8fafb!important;';

                // Mover contenido existente al main (preservar orden)
                var children = Array.from(wrap.childNodes);
                children.forEach(function(child) {
                    if (child.nodeType === 1 && child.id !== 'ap-sidebar-html' && !child.classList.contains('ap-owners-layout')) {
                        main.appendChild(child);
                    }
                });

                // Construir layout
                layout.appendChild(sidebar);
                layout.appendChild(main);
                wrap.appendChild(layout);

                // Eliminar el contenedor temporal del sidebar
                if (sidebarContainer && sidebarContainer.parentNode) {
                    sidebarContainer.parentNode.removeChild(sidebarContainer);
                }

                // Añadir clase al body
                document.body.classList.add('ap-has-sidebar');
            }

            // Ejecutar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initUserEditLayout);
            } else {
                initUserEditLayout();
            }

            // También ejecutar después de un pequeño delay para asegurar que todo esté cargado
            setTimeout(initUserEditLayout, 100);
            setTimeout(initUserEditLayout, 500);
        })();
        </script>
        <?php
    }

    /**
     * Ocultar metaboxes innecesarios en la página de edición de clientes
     * Solo mostrar: Nombre, Campos ACF CRM Ficha de Huésped, Dirección de pedido WooCommerce
     */
    public function remove_unnecessary_user_meta_boxes()
    {
        global $pagenow;
        
        // Solo aplicar en páginas de edición de usuarios
        if ($pagenow !== 'user-edit.php' && $pagenow !== 'profile.php') {
            return;
        }

        // Obtener el ID del usuario que se está editando
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
        $user = get_userdata($user_id);
        
        // Solo aplicar si es un cliente (customer o subscriber)
        if (!$user || (!in_array('customer', $user->roles) && !in_array('subscriber', $user->roles))) {
            return;
        }

        // Obtener todos los metaboxes registrados
        global $wp_meta_boxes;
        $screen = get_current_screen();
        
        // Verificación adicional: asegurarse de que estamos en la página correcta
        if (!$screen || !in_array($screen->id, ['user-edit', 'profile'])) {
            return;
        }

        // Lista de metaboxes a mantener (solo estos se mostrarán)
        $keep_meta_boxes = [
            'acf-group_crm_cliente', // Campos ACF CRM Ficha de Huésped
        ];

        // Ocultar todos los metaboxes excepto los que queremos mantener
        foreach (['normal', 'side', 'advanced'] as $context) {
            if (!isset($wp_meta_boxes[$screen->id][$context])) {
                continue;
            }
            
            foreach ($wp_meta_boxes[$screen->id][$context] as $priority => $boxes) {
                foreach ($boxes as $box_id => $box) {
                    // Mantener solo los metaboxes permitidos
                    if (!in_array($box_id, $keep_meta_boxes, true)) {
                        // No ocultar metaboxes de WooCommerce (dirección de pedido)
                        if (strpos($box_id, 'woocommerce') === false) {
                            remove_meta_box($box_id, $screen->id, $context);
                        }
                    }
                }
            }
        }

        // Ocultar campos específicos de WordPress
        remove_meta_box('show_user_color_scheme', 'user', 'normal');
        remove_meta_box('show_user_color_scheme', 'user', 'side');
        remove_meta_box('keyboard_shortcuts', 'user', 'normal');
        remove_meta_box('keyboard_shortcuts', 'user', 'side');
        remove_meta_box('application_passwords', 'user', 'normal');
        remove_meta_box('wpseo_meta', 'user', 'normal');
    }

    /**
     * Ocultar metaboxes con CSS como respaldo
     * Solo mostrar: Nombre, Campos ACF CRM, Dirección WooCommerce
     */
    public function hide_meta_boxes_css()
    {
        global $pagenow;
        
        // Solo aplicar en páginas de edición de usuarios
        if ($pagenow !== 'user-edit.php' && $pagenow !== 'profile.php') {
            return;
        }

        // Obtener el ID del usuario que se está editando
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
        $user = get_userdata($user_id);
        
        // Solo aplicar si es un cliente (customer o subscriber)
        if (!$user || (!in_array('customer', $user->roles) && !in_array('subscriber', $user->roles))) {
            return;
        }
        ?>
        <style>
            /* Ocultar TODOS los campos de WordPress excepto Nombre y Apellidos */
            body.user-edit .form-table tr:not([id*="first_name"]):not([id*="last_name"]),
            body.profile .form-table tr:not([id*="first_name"]):not([id*="last_name"]) {
                display: none !important;
            }
            
            /* Mantener solo campos básicos: Nombre, Apellidos */
            body.user-edit .form-table tr[id*="first_name"],
            body.user-edit .form-table tr[id*="last_name"],
            body.profile .form-table tr[id*="first_name"],
            body.profile .form-table tr[id*="last_name"] {
                display: table-row !important;
            }
            
            /* Ocultar campos específicos de WordPress */
            body.user-edit .form-table tr[id*="nickname"],
            body.user-edit .form-table tr[id*="display_name"],
            body.user-edit .form-table tr[id*="description"],
            body.user-edit .form-table tr[id*="url"],
            body.user-edit .form-table tr[id*="user_login"],
            body.user-edit .form-table tr[id*="user_email"],
            body.user-edit .form-table tr[id*="user_pass"],
            body.user-edit .form-table tr[id*="rich_editing"],
            body.user-edit .form-table tr[id*="comment_shortcuts"],
            body.user-edit .form-table tr[id*="admin_color"],
            body.user-edit .form-table tr[id*="show_admin_bar"],
            body.profile .form-table tr[id*="nickname"],
            body.profile .form-table tr[id*="display_name"],
            body.profile .form-table tr[id*="description"],
            body.profile .form-table tr[id*="url"],
            body.profile .form-table tr[id*="user_login"],
            body.profile .form-table tr[id*="user_email"],
            body.profile .form-table tr[id*="user_pass"],
            body.profile .form-table tr[id*="rich_editing"],
            body.profile .form-table tr[id*="comment_shortcuts"],
            body.profile .form-table tr[id*="admin_color"],
            body.profile .form-table tr[id*="show_admin_bar"] {
                display: none !important;
            }
            
            /* Ocultar todos los metaboxes excepto ACF CRM y WooCommerce */
            body.user-edit .postbox:not([id*="acf-group_crm_cliente"]):not([id*="woocommerce"]),
            body.profile .postbox:not([id*="acf-group_crm_cliente"]):not([id*="woocommerce"]) {
                display: none !important;
            }
            
            /* Ocultar cualquier postbox que contenga "color" o "keyboard" en su ID o clase */
            body.user-edit .postbox[id*="color"],
            body.profile .postbox[id*="color"],
            body.user-edit .postbox[id*="keyboard"],
            body.profile .postbox[id*="keyboard"],
            body.user-edit .postbox[class*="color"],
            body.profile .postbox[class*="color"],
            body.user-edit .postbox[class*="keyboard"],
            body.profile .postbox[class*="keyboard"] {
                display: none !important;
            }
            
            /* Ocultar opciones de pantalla y ayuda contextual */
            body.user-edit #screen-options-link-wrap,
            body.profile #screen-options-link-wrap,
            body.user-edit #contextual-help-link-wrap,
            body.profile #contextual-help-link-wrap {
                display: none !important;
            }
            
            /* Estilo del dashboard para los campos visibles */
            body.user-edit .form-table tr[id*="first_name"] th,
            body.user-edit .form-table tr[id*="last_name"] th,
            body.profile .form-table tr[id*="first_name"] th,
            body.profile .form-table tr[id*="last_name"] th {
                font-weight: 600;
                color: var(--ap-text-primary);
                padding: 16px 0;
                width: 200px;
            }
            
            body.user-edit .form-table tr[id*="first_name"] td,
            body.user-edit .form-table tr[id*="last_name"] td,
            body.profile .form-table tr[id*="first_name"] td,
            body.profile .form-table tr[id*="last_name"] td {
                padding: 16px 0;
            }
            
            body.user-edit .form-table tr[id*="first_name"] input,
            body.user-edit .form-table tr[id*="last_name"] input,
            body.profile .form-table tr[id*="first_name"] input,
            body.profile .form-table tr[id*="last_name"] input {
                width: 100%;
                max-width: 400px;
                padding: 10px 12px;
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius-md);
                font-size: 14px;
                transition: border-color 0.2s;
            }
            
            body.user-edit .form-table tr[id*="first_name"] input:focus,
            body.user-edit .form-table tr[id*="last_name"] input:focus,
            body.profile .form-table tr[id*="first_name"] input:focus,
            body.profile .form-table tr[id*="last_name"] input:focus {
                border-color: var(--ap-primary);
                outline: none;
                box-shadow: 0 0 0 3px rgba(44, 153, 226, 0.1);
            }
            
            /* Estilos para metaboxes ACF y WooCommerce */
            body.user-edit .postbox[id*="acf-group_crm_cliente"],
            body.user-edit .postbox[id*="woocommerce"],
            body.profile .postbox[id*="acf-group_crm_cliente"],
            body.profile .postbox[id*="woocommerce"] {
                background: var(--ap-surface);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius-lg);
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
                margin-bottom: 24px;
            }
            
            body.user-edit .postbox[id*="acf-group_crm_cliente"] .postbox-header,
            body.user-edit .postbox[id*="woocommerce"] .postbox-header,
            body.profile .postbox[id*="acf-group_crm_cliente"] .postbox-header,
            body.profile .postbox[id*="woocommerce"] .postbox-header {
                border-bottom: 1px solid var(--ap-border);
                padding: 16px 20px;
                background: var(--ap-surface);
            }
            
            body.user-edit .postbox[id*="acf-group_crm_cliente"] .postbox-header h2,
            body.user-edit .postbox[id*="woocommerce"] .postbox-header h2,
            body.profile .postbox[id*="acf-group_crm_cliente"] .postbox-header h2,
            body.profile .postbox[id*="woocommerce"] .postbox-header h2 {
                font-size: 16px;
                font-weight: 600;
                color: var(--ap-text-primary);
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            
            body.user-edit .postbox[id*="acf-group_crm_cliente"] .inside,
            body.user-edit .postbox[id*="woocommerce"] .inside,
            body.profile .postbox[id*="acf-group_crm_cliente"] .inside,
            body.profile .postbox[id*="woocommerce"] .inside {
                padding: 20px;
            }
        </style>
        <?php
    }
}

new Alquipress_UI_Enhancements();
