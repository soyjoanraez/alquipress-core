<?php
/**
 * Módulo: Vista Detallada de Propietario
 * Ficha del propietario con el layout del dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Owner_Profile
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_owner_profile_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'maybe_acf_form_head']);
        add_action('load-post.php', [$this, 'maybe_redirect_owner_edit']);
        add_filter('get_edit_post_link', [$this, 'filter_edit_post_link'], 10, 3);
        add_filter('post_row_actions', [$this, 'filter_post_row_actions'], 10, 2);
    }

    public function add_owner_profile_page()
    {
        $parent_slug = 'alquipress-dashboard';
        add_submenu_page(
            $parent_slug,
            'Perfil del Propietario',
            'Perfil del Propietario',
            'edit_posts',
            'alquipress-owner-profile',
            [$this, 'render_owner_profile']
        );

        remove_submenu_page($parent_slug, 'alquipress-owner-profile');
    }

    public function enqueue_assets($hook)
    {
        if ($hook !== 'admin_page_alquipress-owner-profile') {
            return;
        }

        wp_enqueue_style(
            'alquipress-owners-page',
            ALQUIPRESS_URL . 'includes/modules/owners-page/assets/owners-page.css',
            [],
            ALQUIPRESS_VERSION
        );

        if (!empty($_GET['mode']) && $_GET['mode'] === 'edit') {
            return;
        }

        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
        }
    }

    public function maybe_acf_form_head()
    {
        if (!function_exists('acf_form_head')) {
            return;
        }
        if (!is_admin()) {
            return;
        }
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $mode = isset($_GET['mode']) ? sanitize_text_field(wp_unslash($_GET['mode'])) : '';
        if ($page !== 'alquipress-owner-profile' || $mode !== 'edit') {
            return;
        }
        acf_form_head();
    }

    public function maybe_redirect_owner_edit()
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
        if (!$post || $post->post_type !== 'propietario') {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_GET['alquipress_native_edit'])) {
            return;
        }
        wp_safe_redirect($this->get_owner_profile_url($post_id, 'edit'));
        exit;
    }

    public function filter_edit_post_link($link, $post_id, $context)
    {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'propietario') {
            return $link;
        }
        return $this->get_owner_profile_url($post_id, 'edit');
    }

    public function filter_post_row_actions($actions, $post)
    {
        if (!$post || $post->post_type !== 'propietario') {
            return $actions;
        }
        $actions['edit'] = '<a href="' . esc_url($this->get_owner_profile_url($post->ID, 'edit')) . '">' . esc_html__('Editar', 'alquipress') . '</a>';
        if (isset($actions['inline hide-if-no-js'])) {
            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    }

    private function get_owner_profile_url($owner_id, $mode = 'view')
    {
        $url = admin_url('admin.php?page=alquipress-owner-profile&owner_id=' . (int) $owner_id);
        if ($mode === 'edit') {
            $url = add_query_arg('mode', 'edit', $url);
        }
        return $url;
    }

    private function get_icon_svg($name, $class = 'ap-owners-icon')
    {
        $icons = [
            'search' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>',
            'bell' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" /></svg>',
            'layout-dashboard' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="8" height="9" rx="1" /><rect x="13" y="3" width="8" height="5" rx="1" /><rect x="13" y="10" width="8" height="11" rx="1" /><rect x="3" y="14" width="8" height="7" rx="1" /></svg>',
            'building' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" /><path d="M7 7h3" /><path d="M14 7h3" /><path d="M7 12h3" /><path d="M14 12h3" /><path d="M7 17h3" /><path d="M14 17h3" /></svg>',
            'building-2' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18" /><path d="M6 12h12" /><path d="M10 6h4" /><path d="M10 16h4" /></svg>',
            'calendar' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>',
            'briefcase' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2" /><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" /><path d="M2 12h20" /></svg>',
            'wallet' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z" /><path d="M16 12h2" /></svg>',
            'bar-chart' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><line x1="6" y1="20" x2="6" y2="14" /><line x1="12" y1="20" x2="12" y2="8" /><line x1="18" y1="20" x2="18" y2="4" /></svg>',
            'settings' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V22a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-.4-1.1 1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H2a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.1-.4 1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V2a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 .4 1.1 1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.25.34.45.71.6 1.1.1.33.35.56.7.6H22a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.1.4c-.34.25-.56.6-.6.9Z" /></svg>',
            'edit' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>',
            'download' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="7 10 12 15 17 10" /><line x1="12" y1="15" x2="12" y2="3" /></svg>',
        ];

        if (!isset($icons[$name])) {
            return '';
        }

        return $icons[$name];
    }

    private function get_initials($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return 'NA';
        }
        $parts = preg_split('/\s+/', $text);
        if (!$parts) {
            return strtoupper(substr($text, 0, 2));
        }
        $first = strtoupper(substr($parts[0], 0, 1));
        $last = count($parts) > 1 ? strtoupper(substr($parts[count($parts) - 1], 0, 1)) : '';
        return $first . $last;
    }

    private function get_owner_monthly_revenue($owner_id, $months = 6)
    {
        if (!class_exists('Alquipress_Owner_Revenue')) {
            return [];
        }
        $revenue_mod = Alquipress_Owner_Revenue::get_instance();
        $labels = [];
        $values = [];
        $timezone = wp_timezone();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = new DateTime('first day of this month', $timezone);
            if ($i > 0) {
                $date->modify('-' . $i . ' months');
            }
            $start = $date->format('Y-m-01');
            $end = $date->format('Y-m-t');
            $stats = $revenue_mod->calculate_owner_revenue($owner_id, $start, $end);
            $labels[] = date_i18n('M', $date->getTimestamp());
            $values[] = isset($stats['net']) ? (float) $stats['net'] : 0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Normaliza un mes en formato YYYY-MM.
     *
     * @param string $month Mes.
     * @return string
     */
    private function normalize_month($month)
    {
        $month = trim((string) $month);
        if (!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $month)) {
            return '';
        }
        return $month;
    }

    /**
     * Devuelve opciones de mes para selector histórico.
     *
     * @param int $months_back Número de meses hacia atrás (incluyendo actual).
     * @return array<int, array{value:string,label:string}>
     */
    private function get_settlement_month_options($months_back = 24)
    {
        $months_back = max(1, (int) $months_back);
        $timezone = wp_timezone();
        $base = new DateTime('first day of this month', $timezone);
        $options = [];

        for ($i = 0; $i < $months_back; $i++) {
            $date = clone $base;
            if ($i > 0) {
                $date->modify('-' . $i . ' months');
            }

            $options[] = [
                'value' => $date->format('Y-m'),
                'label' => date_i18n('F Y', $date->getTimestamp()),
            ];
        }

        return $options;
    }

    /**
     * Verifica si el usuario actual puede acceder a la ficha del propietario.
     *
     * @param int $owner_id ID del propietario.
     * @return bool
     */
    private function can_access_owner_profile($owner_id)
    {
        $owner_id = (int) $owner_id;
        $user_id = (int) get_current_user_id();

        if ($owner_id <= 0 || $user_id <= 0) {
            return false;
        }

        if (
            current_user_can('manage_options') ||
            current_user_can('manage_woocommerce') ||
            current_user_can('edit_post', $owner_id)
        ) {
            return true;
        }

        if (
            class_exists('Alquipress_Owner_Role_Manager') &&
            current_user_can(Alquipress_Owner_Role_Manager::ROLE_PROPERTY_OWNER) &&
            $this->is_owner_linked_to_user($owner_id, $user_id)
        ) {
            return true;
        }

        return (bool) apply_filters('alquipress_owner_profile_can_access', false, $user_id, $owner_id);
    }

    /**
     * Comprueba vinculación usuario <-> propietario.
     *
     * @param int $owner_id ID propietario.
     * @param int $user_id ID usuario.
     * @return bool
     */
    private function is_owner_linked_to_user($owner_id, $user_id)
    {
        $owner_id = (int) $owner_id;
        $user_id = (int) $user_id;
        if ($owner_id <= 0 || $user_id <= 0) {
            return false;
        }

        $meta_user_keys = [
            'owner_user_id',
            'owner_wp_user_id',
            '_owner_user_id',
            '_owner_wp_user_id',
        ];

        foreach ($meta_user_keys as $meta_key) {
            $linked_user_id = (int) get_post_meta($owner_id, $meta_key, true);
            if ($linked_user_id > 0 && $linked_user_id === $user_id) {
                return true;
            }
        }

        if (function_exists('get_field')) {
            $acf_user = get_field('owner_user', $owner_id);
            if (is_numeric($acf_user) && (int) $acf_user === $user_id) {
                return true;
            }
            if (is_object($acf_user) && isset($acf_user->ID) && (int) $acf_user->ID === $user_id) {
                return true;
            }
        }

        $owner_email = sanitize_email((string) get_post_meta($owner_id, 'owner_email_management', true));
        $user = get_userdata($user_id);
        if ($user && !empty($owner_email) && strtolower($owner_email) === strtolower((string) $user->user_email)) {
            return true;
        }

        return false;
    }

    public function render_owner_profile()
    {
        $owner_id = isset($_GET['owner_id']) ? (int) $_GET['owner_id'] : 0;
        $owner_post = get_post($owner_id);
        $mode = isset($_GET['mode']) ? sanitize_text_field(wp_unslash($_GET['mode'])) : 'view';
        $is_edit = $mode === 'edit';

        if (!$owner_post || $owner_post->post_type !== 'propietario') {
            echo '<div class="notice notice-error"><p>' . esc_html__('Propietario no encontrado.', 'alquipress') . '</p></div>';
            return;
        }

        if (!$this->can_access_owner_profile($owner_id)) {
            wp_die(
                esc_html__('No tienes permisos para ver esta ficha de propietario.', 'alquipress'),
                esc_html__('Acceso denegado', 'alquipress'),
                ['response' => 403]
            );
        }

        $revenue_mod = class_exists('Alquipress_Owner_Revenue') ? Alquipress_Owner_Revenue::get_instance() : null;
        $stats = $revenue_mod ? $revenue_mod->calculate_owner_revenue($owner_id) : [
            'total' => 0,
            'commission' => 0,
            'net' => 0,
            'count' => 0,
            'properties' => [],
        ];

        $email = get_post_meta($owner_id, 'owner_email_management', true);
        $phone = get_post_meta($owner_id, 'owner_phone', true);
        $iban = get_post_meta($owner_id, 'owner_iban', true);

        $back_url = admin_url('edit.php?post_type=propietario');
        $edit_url = $this->get_owner_profile_url($owner_id, 'edit');
        $view_url = $this->get_owner_profile_url($owner_id);
        $current_month = current_time('Y-m');
        $requested_month = isset($_GET['settlement_month']) ? sanitize_text_field(wp_unslash($_GET['settlement_month'])) : '';
        $selected_settlement_month = $this->normalize_month($requested_month);
        if (empty($selected_settlement_month)) {
            $selected_settlement_month = $current_month;
        }
        $settlement_month_options = $this->get_settlement_month_options(36);
        $settlement_download_url = function_exists('alquipress_get_owner_settlement_download_url')
            ? alquipress_get_owner_settlement_download_url($owner_id, $selected_settlement_month)
            : '';
        $monthly = $this->get_owner_monthly_revenue($owner_id, 6);

        $user = wp_get_current_user();
        $user_name = $user && $user->exists() ? $user->display_name : __('Usuario', 'alquipress');
        $user_role = __('Administrador', 'alquipress');
        if ($user && $user->exists() && !empty($user->roles)) {
            $role_key = $user->roles[0];
            $role_map = [
                'administrator' => __('Administrador', 'alquipress'),
                'editor' => __('Editor', 'alquipress'),
                'author' => __('Autor', 'alquipress'),
                'contributor' => __('Colaborador', 'alquipress'),
                'subscriber' => __('Suscriptor', 'alquipress'),
                'shop_manager' => __('Gestor de tienda', 'alquipress'),
            ];
            $user_role = $role_map[$role_key] ?? ucfirst($role_key);
        }
        ?>
        <div class="wrap alquipress-owners-page ap-owner-profile">
            <div class="ap-owners-layout">
                <aside class="ap-owners-sidebar">
                    <div class="ap-owners-logo">
                        <div class="ap-owners-logo-icon">
                            <?php echo $this->get_icon_svg('building-2', 'ap-owners-icon ap-owners-icon-inverse'); ?>
                        </div>
                        <div class="ap-owners-logo-text">
                            <span class="ap-owners-logo-name">ALQUIPRESS</span>
                            <span class="ap-owners-logo-sub">Inmobiliaria</span>
                        </div>
                    </div>
                    <nav class="ap-owners-nav">
                        <a class="ap-owners-nav-item" href="<?php echo esc_url(admin_url('admin.php?page=alquipress-dashboard')); ?>">
                            <?php echo $this->get_icon_svg('layout-dashboard'); ?>
                            <span><?php esc_html_e('Panel', 'alquipress'); ?></span>
                        </a>
                        <a class="ap-owners-nav-item" href="<?php echo esc_url(admin_url('admin.php?page=alquipress-properties')); ?>">
                            <?php echo $this->get_icon_svg('building'); ?>
                            <span><?php esc_html_e('Propiedades', 'alquipress'); ?></span>
                        </a>
                        <a class="ap-owners-nav-item" href="<?php echo esc_url(admin_url('admin.php?page=alquipress-bookings')); ?>">
                            <?php echo $this->get_icon_svg('calendar'); ?>
                            <span><?php esc_html_e('Reservas', 'alquipress'); ?></span>
                        </a>
                        <a class="ap-owners-nav-item is-active" href="<?php echo esc_url(admin_url('admin.php?page=alquipress-owners')); ?>">
                            <?php echo $this->get_icon_svg('briefcase'); ?>
                            <span><?php esc_html_e('Propietarios', 'alquipress'); ?></span>
                        </a>
                        <a class="ap-owners-nav-item" href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order')); ?>">
                            <?php echo $this->get_icon_svg('wallet'); ?>
                            <span><?php esc_html_e('Finanzas', 'alquipress'); ?></span>
                        </a>
                        <a class="ap-owners-nav-item" href="<?php echo esc_url(admin_url('admin.php?page=alquipress-reports')); ?>">
                            <?php echo $this->get_icon_svg('bar-chart'); ?>
                            <span><?php esc_html_e('Informes', 'alquipress'); ?></span>
                        </a>
                        <a class="ap-owners-nav-item" href="<?php echo esc_url(admin_url('admin.php?page=alquipress-settings')); ?>">
                            <?php echo $this->get_icon_svg('settings'); ?>
                            <span><?php esc_html_e('Ajustes', 'alquipress'); ?></span>
                        </a>
                    </nav>
                    <div class="ap-owners-sidebar-spacer"></div>
                    <div class="ap-owners-user">
                        <div class="ap-owners-avatar"><?php echo esc_html($this->get_initials($user_name)); ?></div>
                        <div class="ap-owners-user-info">
                            <span class="ap-owners-user-name"><?php echo esc_html($user_name); ?></span>
                            <span class="ap-owners-user-role"><?php echo esc_html($user_role); ?></span>
                        </div>
                    </div>
                </aside>

                <main class="ap-owners-main">
                    <header class="ap-owners-header">
                        <div class="ap-owners-header-left">
                            <h1 class="ap-owners-title"><?php echo esc_html($owner_post->post_title); ?></h1>
                            <p class="ap-owners-subtitle"><?php esc_html_e('Ficha del propietario y rendimiento financiero', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-owners-header-right ap-owners-header-actions">
                            <a href="<?php echo esc_url($back_url); ?>" class="ap-owners-top-view"><?php esc_html_e('Volver a Propietarios', 'alquipress'); ?></a>
                            <?php if (!$is_edit && !empty($settlement_download_url)) : ?>
                                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="ap-owner-settlement-form">
                                    <input type="hidden" name="page" value="alquipress-owner-profile">
                                    <input type="hidden" name="owner_id" value="<?php echo (int) $owner_id; ?>">
                                    <label for="ap-settlement-month" class="screen-reader-text"><?php esc_html_e('Seleccionar mes de liquidación', 'alquipress'); ?></label>
                                    <select id="ap-settlement-month" name="settlement_month" class="ap-select-small">
                                        <?php foreach ($settlement_month_options as $month_option) : ?>
                                            <option value="<?php echo esc_attr($month_option['value']); ?>" <?php selected($selected_settlement_month, $month_option['value']); ?>>
                                                <?php echo esc_html($month_option['label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="button button-secondary button-small"><?php esc_html_e('Cambiar mes', 'alquipress'); ?></button>
                                </form>
                                <a href="<?php echo esc_url($settlement_download_url); ?>" class="ap-owners-action-btn">
                                    <?php echo $this->get_icon_svg('download'); ?>
                                    <?php
                                    printf(
                                        esc_html__('Descargar liquidación (%s)', 'alquipress'),
                                        esc_html(date_i18n('m/Y', strtotime($selected_settlement_month . '-01')))
                                    );
                                    ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($is_edit) : ?>
                                <a href="<?php echo esc_url($view_url); ?>" class="ap-owners-action-btn">
                                    <?php echo $this->get_icon_svg('edit'); ?>
                                    <?php esc_html_e('Ver ficha', 'alquipress'); ?>
                                </a>
                            <?php else : ?>
                                <a href="<?php echo esc_url($edit_url); ?>" class="ap-owners-action-btn is-primary">
                                    <?php echo $this->get_icon_svg('edit'); ?>
                                    <?php esc_html_e('Editar propietario', 'alquipress'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </header>

                    <div class="ap-owners-metrics-row">
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Ingresos brutos', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo function_exists('wc_price') ? wc_price($stats['total']) : number_format_i18n($stats['total'], 2) . ' €'; ?></span>
                                <span class="ap-owners-metric-change is-neutral"><?php esc_html_e('Total histórico', 'alquipress'); ?></span>
                            </div>
                        </div>
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Comisión Alquipress', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo function_exists('wc_price') ? wc_price($stats['commission']) : number_format_i18n($stats['commission'], 2) . ' €'; ?></span>
                                <span class="ap-owners-metric-change is-warning"><?php esc_html_e('Comisión acumulada', 'alquipress'); ?></span>
                            </div>
                        </div>
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Neto liquidado', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo function_exists('wc_price') ? wc_price($stats['net']) : number_format_i18n($stats['net'], 2) . ' €'; ?></span>
                                <span class="ap-owners-metric-change is-positive"><?php echo (int) $stats['count']; ?> <?php echo $stats['count'] === 1 ? esc_html__('reserva', 'alquipress') : esc_html__('reservas', 'alquipress'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="ap-owners-content-row">
                        <div class="ap-owners-left-col">
                            <?php if ($is_edit) : ?>
                                <div class="ap-owner-card">
                                    <div class="ap-owner-card-header">
                                        <h3><?php esc_html_e('Editar propietario', 'alquipress'); ?></h3>
                                        <span class="ap-owner-card-sub"><?php esc_html_e('Actualiza los datos generales y la información del CPT', 'alquipress'); ?></span>
                                    </div>
                                    <?php if (function_exists('acf_form')) : ?>
                                        <?php
                                        acf_form([
                                            'post_id' => $owner_id,
                                            'post_title' => true,
                                            'post_content' => true,
                                            'submit_value' => __('Guardar cambios', 'alquipress'),
                                            'updated_message' => __('Propietario actualizado correctamente.', 'alquipress'),
                                            'return' => $view_url,
                                        ]);
                                        ?>
                                    <?php else : ?>
                                        <p class="ap-owner-empty"><?php esc_html_e('ACF no está activo. No se puede editar desde esta vista.', 'alquipress'); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php else : ?>
                                <div class="ap-owner-card">
                                    <div class="ap-owner-card-header">
                                        <h3><?php esc_html_e('Mis propiedades', 'alquipress'); ?></h3>
                                        <span class="ap-owner-card-sub"><?php echo (int) $stats['count']; ?> <?php echo $stats['count'] === 1 ? esc_html__('propiedad activa', 'alquipress') : esc_html__('propiedades activas', 'alquipress'); ?></span>
                                    </div>
                                    <div class="ap-owner-table-wrap">
                                        <?php if (!empty($stats['properties'])) : ?>
                                            <table class="ap-owner-table">
                                                <thead>
                                                    <tr>
                                                        <th><?php esc_html_e('Propiedad', 'alquipress'); ?></th>
                                                        <th><?php esc_html_e('Reservas', 'alquipress'); ?></th>
                                                        <th><?php esc_html_e('Ingresos', 'alquipress'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($stats['properties'] as $property) : ?>
                                                        <tr>
                                                            <td><strong><?php echo esc_html($property['name']); ?></strong></td>
                                                            <td><?php echo (int) $property['bookings']; ?></td>
                                                            <td><?php echo function_exists('wc_price') ? wc_price($property['revenue']) : number_format_i18n($property['revenue'], 2) . ' €'; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else : ?>
                                            <p class="ap-owner-empty"><?php esc_html_e('No hay propiedades asignadas.', 'alquipress'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="ap-owner-card">
                                    <div class="ap-owner-card-header">
                                        <h3><?php esc_html_e('Evolución de ingresos (últimos 6 meses)', 'alquipress'); ?></h3>
                                        <span class="ap-owner-card-sub"><?php esc_html_e('Datos reales por mes', 'alquipress'); ?></span>
                                    </div>
                                    <?php if (!empty($monthly)) : ?>
                                        <div class="ap-owner-chart">
                                            <canvas id="apOwnerRevenueChart" height="100"></canvas>
                                        </div>
                                    <?php else : ?>
                                        <p class="ap-owner-empty"><?php esc_html_e('No hay datos suficientes para mostrar el gráfico.', 'alquipress'); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="ap-owner-card">
                                    <div class="ap-owner-card-header">
                                        <h3><?php esc_html_e('Notas del propietario', 'alquipress'); ?></h3>
                                        <span class="ap-owner-card-sub"><?php esc_html_e('Contenido del CPT', 'alquipress'); ?></span>
                                    </div>
                                    <?php if (!empty($owner_post->post_content)) : ?>
                                        <div class="ap-owner-notes">
                                            <?php echo wp_kses_post(wpautop($owner_post->post_content)); ?>
                                        </div>
                                    <?php else : ?>
                                        <p class="ap-owner-empty"><?php esc_html_e('No hay notas registradas para este propietario.', 'alquipress'); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="ap-owners-right-col">
                            <div class="ap-owner-card">
                                <div class="ap-owner-card-header">
                                    <h3><?php esc_html_e('Datos de contacto', 'alquipress'); ?></h3>
                                    <span class="ap-owner-card-sub"><?php esc_html_e('Información registrada', 'alquipress'); ?></span>
                                </div>
                                <div class="ap-owner-info-list">
                                    <div class="ap-owner-info-item">
                                        <span class="ap-owner-info-label"><?php esc_html_e('Email', 'alquipress'); ?></span>
                                        <span class="ap-owner-info-value"><?php echo esc_html($email ?: '-'); ?></span>
                                    </div>
                                    <div class="ap-owner-info-item">
                                        <span class="ap-owner-info-label"><?php esc_html_e('Teléfono', 'alquipress'); ?></span>
                                        <span class="ap-owner-info-value"><?php echo esc_html($phone ?: '-'); ?></span>
                                    </div>
                                    <div class="ap-owner-info-item">
                                        <span class="ap-owner-info-label"><?php esc_html_e('IBAN', 'alquipress'); ?></span>
                                        <span class="ap-owner-info-value"><?php echo esc_html($iban ? '****' . substr($iban, -4) : '-'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="ap-owner-card">
                                <div class="ap-owner-card-header">
                                    <h3><?php esc_html_e('Documentación', 'alquipress'); ?></h3>
                                    <span class="ap-owner-card-sub"><?php esc_html_e('Archivos del propietario', 'alquipress'); ?></span>
                                </div>
                                <p class="ap-owner-empty"><?php esc_html_e('No hay documentos cargados.', 'alquipress'); ?></p>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <?php if (!$is_edit && !empty($monthly)) : ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const settlementMonth = document.getElementById('ap-settlement-month');
                    if (settlementMonth && settlementMonth.form) {
                        settlementMonth.addEventListener('change', function () {
                            settlementMonth.form.submit();
                        });
                    }

                    if (typeof Chart === 'undefined') {
                        return;
                    }
                    const ctx = document.getElementById('apOwnerRevenueChart');
                    if (!ctx) {
                        return;
                    }
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo wp_json_encode($monthly['labels']); ?>,
                            datasets: [{
                                label: '<?php echo esc_js(__('Ingresos netos (€)', 'alquipress')); ?>',
                                data: <?php echo wp_json_encode($monthly['values']); ?>,
                                borderColor: '#2c99e2',
                                tension: 0.4,
                                fill: true,
                                backgroundColor: 'rgba(44, 153, 226, 0.12)'
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true } }
                        }
                    });
                });
            </script>
        <?php endif; ?>
        <?php
    }
}

new Alquipress_Owner_Profile();
