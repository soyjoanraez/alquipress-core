<?php
/**
 * Módulo: Editor de Propiedades (Dashboard)
 * Editor propio con layout Pencil y sidebar Alquipress.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Property_Editor
{
    const PAGE_SLUG = 'alquipress-edit-property';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_editor_page']);
        add_action('current_screen', [$this, 'override_screen_for_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('woocommerce_screen_ids', [$this, 'add_screen_id_for_wc_assets']);
        add_action('admin_footer', [$this, 'init_postboxes']);
        add_action('load-post.php', [$this, 'maybe_redirect_native_edit']);
        add_filter('admin_body_class', [$this, 'add_body_class']);
        add_filter('get_edit_post_link', [$this, 'filter_edit_post_link'], 10, 3);
        add_filter('post_row_actions', [$this, 'add_edit_link'], 10, 2);
        add_filter('redirect_post_location', [$this, 'redirect_after_save'], 10, 2);
    }

    private function is_editor_page()
    {
        return is_admin() && isset($_GET['page']) && $_GET['page'] === self::PAGE_SLUG;
    }

    private function get_edit_url($post_id)
    {
        return admin_url('admin.php?page=' . self::PAGE_SLUG . '&post_id=' . (int) $post_id);
    }

    /**
     * Registrar página del editor (oculta en menú).
     */
    public function add_editor_page()
    {
        add_submenu_page(
            'alquipress-settings',
            __('Editar Propiedad', 'alquipress'),
            null,
            'edit_posts',
            self::PAGE_SLUG,
            [$this, 'render_editor_page']
        );
    }

    /**
     * Forzar screen id a "product" para que Woo/ACF carguen assets.
     */
    public function override_screen_for_assets($screen)
    {
        if (!$this->is_editor_page() || !$screen) {
            return;
        }

        $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
        if ($post_id) {
            $post = get_post($post_id);
            if ($post && $post->post_type === 'product') {
                $GLOBALS['post'] = $post;
                $GLOBALS['post_type'] = $post->post_type;
                $GLOBALS['typenow'] = $post->post_type;
            }
        }

        $screen->id = 'product';
        $screen->base = 'post';
        $screen->post_type = 'product';
        $screen->action = 'edit';
    }

    /**
     * Incluir la pantalla del editor de propiedad en woocommerce_screen_ids
     * para que WooCommerce y WC Bookings carguen sus scripts de producto.
     */
    public function add_screen_id_for_wc_assets($ids)
    {
        $ids[] = 'alquipress-settings_page_' . self::PAGE_SLUG;
        $ids[] = 'toplevel_page_alquipress-settings';
        return $ids;
    }

    /**
     * Añadir clase de body para reutilizar estilos Pencil.
     */
    public function add_body_class($classes)
    {
        if ($this->is_editor_page()) {
            $classes .= ' post-type-product ap-edit-property';
        }
        return $classes;
    }

    /**
     * Añadir enlace "Editar en ALQUIPRESS" en listado de productos.
     */
    public function add_edit_link($actions, $post)
    {
        if ($post->post_type !== 'product') {
            return $actions;
        }

        if (!current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        $edit_url = $this->get_edit_url($post->ID);
        $actions['edit'] = '<a href="' . esc_url($edit_url) . '">' . esc_html__('Editar', 'alquipress') . '</a>';
        if (isset($actions['inline hide-if-no-js'])) {
            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    }

    /**
     * Redirigir la edición nativa de productos al editor propio.
     */
    public function maybe_redirect_native_edit()
    {
        if (!is_admin()) {
            return;
        }

        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
        if ($action !== 'edit') {
            return;
        }

        $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
        if (!$post_id) {
            return;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'product') {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_GET['alquipress_native_edit'])) {
            return;
        }

        wp_safe_redirect($this->get_edit_url($post_id));
        exit;
    }

    /**
     * Forzar enlace de edición a la página del editor propio (solo admin).
     */
    public function filter_edit_post_link($link, $post_id, $context)
    {
        if (!is_admin()) {
            return $link;
        }

        if (isset($_GET['alquipress_native_edit'])) {
            return $link;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'product') {
            return $link;
        }

        return $this->get_edit_url($post_id);
    }

    /**
     * Encolar scripts básicos del editor.
     */
    public function enqueue_assets($hook)
    {
        if (!$this->is_editor_page()) {
            return;
        }

        wp_enqueue_style(
            'alquipress-admin-pencil',
            ALQUIPRESS_URL . 'includes/modules/ui-enhancements/assets/admin-pencil.css',
            [],
            ALQUIPRESS_VERSION
        );

        wp_enqueue_script('postbox');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_media();
        wp_enqueue_editor();

        if (function_exists('acf_enqueue_scripts')) {
            acf_enqueue_scripts();
        }

        $this->enqueue_wc_bookings_product_assets();
    }

    /**
     * Forzar carga de assets de WooCommerce Bookings en el editor de propiedad.
     * La pantalla personalizada (admin.php?page=alquipress-edit-property) no dispara
     * la carga automática de WC Bookings; sin estos scripts el tab "Reservas" no aparece.
     */
    private function enqueue_wc_bookings_product_assets()
    {
        if (!class_exists('WC_Bookings') || !defined('WC_BOOKINGS_PLUGIN_URL') || !defined('WC_BOOKINGS_VERSION')) {
            return;
        }

        $plugin_url = WC_BOOKINGS_PLUGIN_URL;
        $version = WC_BOOKINGS_VERSION;

        wp_enqueue_style('wc_bookings_admin_styles', $plugin_url . 'dist/admin.css', ['wp-components'], $version);

        wp_enqueue_style(
            'alquipress-property-editor-wc-bookings',
            ALQUIPRESS_URL . 'includes/modules/property-editor/assets/property-editor-wc-bookings.css',
            ['wc_bookings_admin_styles', 'alquipress-admin-pencil'],
            ALQUIPRESS_VERSION
        );

        if (!wp_script_is('wc_bookings_admin_js', 'registered')) {
            wp_register_script('wc_bookings_admin_js', $plugin_url . 'dist/admin.js', ['jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable'], $version, true);
        }
        wp_enqueue_script('wc_bookings_admin_js');

        if (!wp_script_is('wc_bookings_admin_edit_bookable_product_js', 'registered')) {
            $deps = function_exists('wc_booking_get_script_dependencies')
                ? wc_booking_get_script_dependencies('admin-edit-bookable-product', ['wc_bookings_admin_js'])
                : ['jquery', 'wc_bookings_admin_js'];
            wp_register_script('wc_bookings_admin_edit_bookable_product_js', $plugin_url . 'dist/admin-edit-bookable-product.js', $deps, $version, true);
        }
        wp_enqueue_script('wc_bookings_admin_edit_bookable_product_js');

        wp_localize_script('wc_bookings_admin_js', 'wc_bookings_admin_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'plugin_url' => WC()->plugin_url(),
        ]);
    }

    /**
     * Inicializar el comportamiento de metaboxes en el editor.
     */
    public function init_postboxes()
    {
        if (!$this->is_editor_page()) {
            return;
        }
        ?>
        <script>
        jQuery(function($) {
            if (typeof postboxes !== 'undefined') {
                postboxes.add_postbox_toggles('product');
            }
        });
        </script>
        <?php
    }

    /**
     * Redirigir tras guardar para mantener el editor propio.
     */
    public function redirect_after_save($location, $post_id)
    {
        if (!empty($_POST['ap_edit_property'])) {
            $location = add_query_arg([
                'page' => self::PAGE_SLUG,
                'post_id' => (int) $post_id,
                'updated' => '1',
            ], admin_url('admin.php'));
        }

        return $location;
    }

    /**
     * Renderizar página del editor.
     */
    public function render_editor_page()
    {
        // Verificar permisos básicos primero
        if (!current_user_can('edit_posts')) {
            wp_die(
                __('Lo siento, no tienes permisos para acceder a esta página.', 'alquipress'),
                __('Permisos insuficientes', 'alquipress'),
                [
                    'back_link' => true,
                    'response' => 403
                ]
            );
        }

        $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
        if (!$post_id) {
            echo '<div class="wrap"><h1>' . esc_html__('Error', 'alquipress') . '</h1><p>' . esc_html__('Propiedad no encontrada.', 'alquipress') . '</p></div>';
            return;
        }

        $property = get_post($post_id);
        if (!$property || $property->post_type !== 'product') {
            echo '<div class="wrap"><h1>' . esc_html__('Error', 'alquipress') . '</h1><p>' . esc_html__('Propiedad no válida.', 'alquipress') . '</p></div>';
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            wp_die(
                __('Permisos insuficientes', 'alquipress'),
                __('No tienes permisos para editar esta propiedad', 'alquipress'),
                [
                    'back_link' => true,
                    'response' => 403
                ]
            );
        }

        global $post, $post_type, $post_type_object, $typenow;
        $post = $property;
        $post_type = $property->post_type;
        $post_type_object = get_post_type_object($post_type);
        $typenow = $post_type;

        require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';
        do_action('add_meta_boxes', $post_type, $post);
        do_action("add_meta_boxes_{$post_type}", $post);
        do_action('do_meta_boxes', $post_type, $post);

        $updated = isset($_GET['updated']) && $_GET['updated'] === '1';

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        require_once ALQUIPRESS_PATH . 'includes/admin/property-edit-layout.php';
        ?>
        <div class="wrap alquipress-edit-property-wrap ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('properties'); ?>
                <main class="ap-owners-main">
                    <?php if ($updated): ?>
                        <div class="notice notice-success is-dismissible" style="margin: 0 0 24px;">
                            <p><strong>✓ <?php esc_html_e('Propiedad actualizada correctamente.', 'alquipress'); ?></strong></p>
                        </div>
                    <?php endif; ?>

                    <form id="ap-edit-property-form" method="post" action="<?php echo esc_url(admin_url('post.php')); ?>">
                        <?php
                        wp_nonce_field('update-post_' . $post_id);
                        wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
                        wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
                        wp_original_referer_field(true, 'previous');
                        ?>
                        <input type="hidden" name="action" value="editpost" />
                        <input type="hidden" name="post_ID" value="<?php echo (int) $post_id; ?>" />
                        <input type="hidden" name="post_type" value="product" />
                        <input type="hidden" name="ap_edit_property" value="1" />

                        <?php
                        $view_url = get_permalink($post_id);
                        if (!is_string($view_url) || $view_url === '') {
                            $view_url = '#';
                        }
                        $native_edit_url = admin_url('post.php?post=' . (int) $post_id . '&action=edit&alquipress_native_edit=1');
                        alquipress_render_property_edit_layout($post, [
                            'list_url' => admin_url('admin.php?page=alquipress-properties'),
                            'primary_action_html' => '<button type="submit" form="ap-edit-property-form" class="ap-prop-btn ap-prop-btn-primary">' . esc_html__('Guardar cambios', 'alquipress') . '</button>',
                            'secondary_action_html' => '<a href="' . esc_url($view_url) . '" target="_blank" rel="noopener" class="ap-prop-btn ap-prop-btn-secondary">' . esc_html__('Ver en web', 'alquipress') . '</a>',
                            'tertiary_action_html' => '<a href="' . esc_url($native_edit_url) . '" class="ap-prop-btn ap-prop-btn-full-edit">' . esc_html__('Edición completa', 'alquipress') . '</a>',
                            'editable_title' => true,
                            'render_editor' => true,
                        ]);
                        ?>

                        <?php do_action('edit_form_top', $post); ?>

                        <div class="ap-prop-metaboxes-section">
                            <div class="ap-prop-metaboxes-header">
                                <h3 class="ap-prop-metaboxes-title"><?php esc_html_e('Más campos editables', 'alquipress'); ?></h3>
                                <p class="ap-prop-metaboxes-desc"><?php esc_html_e('Precio, reservas (duración, disponibilidad), galería, población, zona, características y otros datos del producto.', 'alquipress'); ?></p>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . (int) $post_id . '&action=edit&alquipress_native_edit=1')); ?>" class="ap-prop-metaboxes-full-link"><?php esc_html_e('→ Edición completa en WordPress (todos los campos)', 'alquipress'); ?></a>
                            </div>
                        </div>
                        <div id="poststuff">
                            <div id="post-body" class="metabox-holder columns-2">
                                <div id="post-body-content">
                                    <?php do_action('edit_form_after_editor', $post); ?>
                                </div>
                                <div id="postbox-container-1" class="postbox-container">
                                    <?php do_action('submitpost_box', $post); ?>
                                    <?php do_meta_boxes($post_type, 'side', $post); ?>
                                </div>
                                <div id="postbox-container-2" class="postbox-container">
                                    <?php do_meta_boxes($post_type, 'normal', $post); ?>
                                    <?php do_meta_boxes($post_type, 'advanced', $post); ?>
                                </div>
                            </div>
                            <br class="clear" />
                        </div>
                    </form>
                </main>
            </div>
        </div>
        <?php
    }
}

new Alquipress_Property_Editor();
