<?php
/**
 * Módulo: Dashboard integrado con WooCommerce Bookings
 * Integra Calendario, Nueva reserva, Notificaciones y Configuración de WC Bookings
 * dentro del layout de Alquipress.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_WC_Bookings_Dashboard
{
    const VIEW_RESUMEN = 'resumen';
    const VIEW_CALENDARIO = 'calendario';
    const VIEW_CREATE = 'create';
    const VIEW_NOTIFICATIONS = 'notifications';
    const VIEW_SETTINGS = 'settings';

    public function __construct()
    {
        add_action('alquipress_render_section', [$this, 'maybe_takeover'], 5);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_wc_assets'], 10);
    }

    private function wc_bookings_active()
    {
        return class_exists('WC_Bookings') && class_exists('WC_Bookings_Admin');
    }

    /**
     * Registra y encola assets del calendario WC Bookings cuando no están registrados por la pantalla nativa.
     */
    private function enqueue_wc_calendar_assets()
    {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('wc-enhanced-select');

        $plugin_url = defined('WC_BOOKINGS_PLUGIN_URL') ? WC_BOOKINGS_PLUGIN_URL : '';
        $version = defined('WC_BOOKINGS_VERSION') ? WC_BOOKINGS_VERSION : '1.0';

        if (!$plugin_url) {
            return;
        }

        if (!wp_script_is('wc_bookings_admin_js', 'registered')) {
            wp_register_script('wc_bookings_admin_js', $plugin_url . 'dist/admin.js', ['jquery-ui-datepicker', 'jquery-ui-sortable'], $version, true);
        }
        wp_enqueue_script('wc_bookings_admin_js');

        if (!wp_style_is('wc_bookings_admin_calendar_css', 'registered') && !wp_style_is('wc_bookings_admin_calendar_css', 'enqueued')) {
            $css_src = (defined('WC_BOOKINGS_GUTENBERG_EXISTS') && WC_BOOKINGS_GUTENBERG_EXISTS)
                ? $plugin_url . 'dist/admin-calendar-gutenberg.css'
                : $plugin_url . 'dist/admin-calendar-gutenberg.css';
            wp_enqueue_style('wc_bookings_admin_calendar_css', $css_src, null, $version);
        } else {
            wp_enqueue_style('wc_bookings_admin_calendar_css');
        }

        if (!wp_script_is('wc_bookings_admin_calendar_js', 'registered')) {
            wp_register_script('wc_bookings_admin_calendar_js', $plugin_url . 'dist/admin-calendar.js', ['jquery', 'wc_bookings_admin_js'], $version, true);
        }
        wp_enqueue_script('wc_bookings_admin_calendar_js');
    }

    /**
     * Si estamos en alquipress-bookings con view de WC, este módulo renderiza.
     */
    public function maybe_takeover($page)
    {
        if ($page !== 'alquipress-bookings') {
            return;
        }

        $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : '';
        $wc_views = [self::VIEW_CALENDARIO, self::VIEW_CREATE, self::VIEW_NOTIFICATIONS, self::VIEW_SETTINGS];

        if (in_array($view, $wc_views, true) && $this->wc_bookings_active()) {
            add_filter('alquipress_bookings_skip_resumen', '__return_true');
            add_action('alquipress_render_section', [$this, 'render_wc_view'], 15);
        }
    }

    public function enqueue_wc_assets($page)
    {
        if ($page !== 'alquipress-bookings' || !$this->wc_bookings_active()) {
            return;
        }

        $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : '';

        if ($view === self::VIEW_CALENDARIO) {
            $this->enqueue_wc_calendar_assets();
        } elseif ($view === self::VIEW_CREATE) {
            if (defined('WC_BOOKINGS_PLUGIN_URL')) {
                wp_enqueue_style('wc-bookings-styles', WC_BOOKINGS_PLUGIN_URL . '/dist/frontend.css', null, defined('WC_BOOKINGS_VERSION') ? WC_BOOKINGS_VERSION : '1.0');
            }
        } elseif ($view === self::VIEW_SETTINGS) {
            if (defined('WC_BOOKINGS_PLUGIN_URL')) {
                wp_enqueue_script('wc_bookings_admin_js');
                if (defined('WC_BOOKINGS_GUTENBERG_EXISTS') && WC_BOOKINGS_GUTENBERG_EXISTS) {
                    wp_enqueue_script('wc_bookings_admin_store_availability_js');
                    wp_enqueue_style('wc_bookings_admin_store_availability_css');
                }
            }
        }
    }

    /**
     * Renderiza la vista de WC Bookings dentro del layout Alquipress.
     */
    public function render_wc_view($page)
    {
        if ($page !== 'alquipress-bookings') {
            return;
        }

        $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : '';

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';

        $base_url = admin_url('admin.php?page=alquipress-bookings');
        $tabs = $this->get_tabs();
        $current = $view;
        ?>
        <div class="wrap alquipress-bookings-page ap-has-sidebar ap-wc-bookings-integrated">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('bookings'); ?>
                <main class="ap-owners-main">
                    <header class="ap-header">
                        <div class="ap-header-left">
                            <h1 class="ap-header-title"><?php esc_html_e('Reservas', 'alquipress'); ?></h1>
                            <p class="ap-header-subtitle"><?php echo esc_html($this->get_view_subtitle($view)); ?></p>
                        </div>
                        <div class="ap-header-right">
                            <?php $this->render_header_actions($view); ?>
                        </div>
                    </header>

                    <nav class="ap-bookings-tabs-nav" role="tablist">
                        <?php foreach ($tabs as $tab_key => $tab) : ?>
                            <?php
                            $tab_url = $tab_key === 'pipeline' ? admin_url('admin.php?page=alquipress-pipeline') : add_query_arg('view', $tab_key === self::VIEW_RESUMEN ? '' : $tab_key, $base_url);
                            ?>
                            <a href="<?php echo esc_url($tab_url); ?>"
                               class="ap-bookings-tab <?php echo $tab_key === $current ? 'is-active' : ''; ?>"
                               role="tab">
                                <?php if (!empty($tab['icon'])) : ?>
                                    <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                                <?php endif; ?>
                                <?php echo esc_html($tab['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <div class="ap-wc-bookings-content">
                        <?php $this->render_view_content($view); ?>
                    </div>
                </main>
            </div>
        </div>
        <?php

    }

    private function get_tabs()
    {
        $tabs = [
            self::VIEW_RESUMEN => [
                'label' => __('Resumen', 'alquipress'),
                'icon' => 'dashicons-calendar-alt',
            ],
            'pipeline' => [
                'label' => __('Pipeline', 'alquipress'),
                'icon' => 'dashicons-editor-table',
            ],
        ];

        if ($this->wc_bookings_active()) {
            $tabs[self::VIEW_CALENDARIO] = ['label' => __('Calendario', 'alquipress'), 'icon' => 'dashicons-calendar'];
            $tabs[self::VIEW_CREATE] = ['label' => __('Nueva reserva', 'alquipress'), 'icon' => 'dashicons-plus-alt2'];
            $tabs[self::VIEW_NOTIFICATIONS] = ['label' => __('Notificaciones', 'alquipress'), 'icon' => 'dashicons-email-alt'];
            $tabs[self::VIEW_SETTINGS] = ['label' => __('Config. reservas', 'alquipress'), 'icon' => 'dashicons-admin-generic'];
        }

        return $tabs;
    }

    private function get_view_subtitle($view)
    {
        $map = [
            self::VIEW_CALENDARIO => __('Calendario de disponibilidad y reservas de WooCommerce Bookings', 'alquipress'),
            self::VIEW_CREATE => __('Crear una nueva reserva manual', 'alquipress'),
            self::VIEW_NOTIFICATIONS => __('Enviar notificación por email a huéspedes con reservas futuras', 'alquipress'),
            self::VIEW_SETTINGS => __('Disponibilidad global, zonas horarias y conexión con Google Calendar', 'alquipress'),
        ];
        return $map[$view] ?? __('Gestión detallada y KPIs de reservas activas', 'alquipress');
    }

    private function render_header_actions($view)
    {
        $pipeline_url = admin_url('admin.php?page=alquipress-pipeline');
        $new_order_url = admin_url('post-new.php?post_type=shop_order');
        $wc_bookings_url = admin_url('edit.php?post_type=wc_booking');
        ?>
        <div class="ap-bookings-header-actions">
            <?php if ($view !== 'pipeline') : ?>
                <a href="<?php echo esc_url($pipeline_url); ?>" class="ap-bookings-view-btn"><span class="dashicons dashicons-editor-table"></span> <?php esc_html_e('Pipeline', 'alquipress'); ?></a>
            <?php endif; ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-ses-export')); ?>" class="ap-bookings-new-btn ap-bookings-btn-ses"><span class="dashicons dashicons-media-spreadsheet"></span> <?php esc_html_e('SES XML', 'alquipress'); ?></a>
            <a href="<?php echo esc_url($new_order_url); ?>" class="ap-bookings-new-btn"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nueva reserva', 'alquipress'); ?></a>
            <?php if ($this->wc_bookings_active()) : ?>
                <a href="<?php echo esc_url($wc_bookings_url); ?>" class="ap-bookings-new-btn ap-bookings-btn-wc" target="_blank" rel="noopener"><span class="dashicons dashicons-external"></span> <?php esc_html_e('Lista WC Bookings', 'alquipress'); ?></a>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_view_content($view)
    {
        if ($view === self::VIEW_CALENDARIO) {
            $this->render_calendar();
        } elseif ($view === self::VIEW_CREATE) {
            $this->render_create_booking();
        } elseif ($view === self::VIEW_NOTIFICATIONS) {
            $this->render_notifications();
        } elseif ($view === self::VIEW_SETTINGS) {
            $this->render_settings();
        }
    }

    private function render_calendar()
    {
        $wcb_path = defined('WC_BOOKINGS_ABSPATH') ? WC_BOOKINGS_ABSPATH : (WP_PLUGIN_DIR . '/woocommerce-bookings/');
        if (!class_exists('WC_Bookings_Calendar')) {
            require_once $wcb_path . 'includes/admin/class-wc-bookings-calendar.php';
        }
        echo '<div class="ap-wc-embed">';
        $calendar = new WC_Bookings_Calendar();
        $calendar->output();
        echo '</div>';
    }

    private function render_create_booking()
    {
        $wcb_path = defined('WC_BOOKINGS_ABSPATH') ? WC_BOOKINGS_ABSPATH : (WP_PLUGIN_DIR . '/woocommerce-bookings/');
        if (!class_exists('WC_Bookings_Create')) {
            require_once $wcb_path . 'includes/admin/class-wc-bookings-create.php';
        }
        echo '<div class="ap-wc-embed">';
        $create = new WC_Bookings_Create();
        $create->output();
        echo '</div>';
    }

    private function render_notifications()
    {
        $booking_products = WC_Bookings_Admin::get_booking_products();

        if (!empty($_POST) && check_admin_referer('send_booking_notification')) {
            $notification_product_id = absint($_POST['notification_product_id'] ?? 0);
            $notification_subject = isset($_POST['notification_subject']) ? sanitize_text_field(wp_unslash($_POST['notification_subject'])) : '';
            $notification_message = isset($_POST['notification_message']) ? wp_kses_post(wp_unslash($_POST['notification_message'])) : '';

            try {
                if (!$notification_product_id) {
                    throw new Exception(__('Elige un producto', 'alquipress'));
                }
                if (!$notification_message) {
                    throw new Exception(__('Introduce el mensaje', 'alquipress'));
                }
                if (!$notification_subject) {
                    throw new Exception(__('Introduce el asunto', 'alquipress'));
                }

                $bookings = WC_Booking_Data_Store::get_bookings_for_product($notification_product_id);
                $mailer = WC()->mailer();
                $notification = $mailer->emails['WC_Email_Booking_Notification'] ?? null;

                if ($notification) {
                    foreach ($bookings as $booking) {
                        $attachments = [];
                        if (!empty($_POST['notification_ics'])) {
                            $generate = new WC_Bookings_ICS_Exporter();
                            $attachments[] = $generate->get_booking_ics($booking);
                        }
                        $notification->reset_tags();
                        $notification->trigger($booking->get_id(), $notification_subject, $notification_message, $attachments);
                    }
                    do_action('wc_bookings_notification_sent', $bookings, $notification);
                }

                echo '<div class="notice notice-success"><p>' . esc_html__('Notificación enviada correctamente.', 'alquipress') . '</p></div>';
            } catch (Exception $e) {
                echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>';
            }
        }

        ?>
        <div class="ap-wc-embed ap-card">
            <h2><?php esc_html_e('Enviar notificación', 'alquipress'); ?></h2>
            <p><?php esc_html_e('Envía un email a todos los clientes con reservas futuras para un producto concreto.', 'alquipress'); ?></p>

            <form method="POST" class="ap-form">
                <?php wp_nonce_field('send_booking_notification'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="notification_product_id"><?php esc_html_e('Producto', 'alquipress'); ?></label></th>
                        <td>
                            <select id="notification_product_id" name="notification_product_id" class="regular-text">
                                <option value=""><?php esc_html_e('Seleccionar producto...', 'alquipress'); ?></option>
                                <?php foreach ($booking_products as $product) : ?>
                                    <option value="<?php echo esc_attr($product->get_id()); ?>"><?php echo esc_html($product->get_title()); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="notification_subject"><?php esc_html_e('Asunto', 'alquipress'); ?></label></th>
                        <td>
                            <input type="text" name="notification_subject" id="notification_subject" class="regular-text" placeholder="<?php esc_attr_e('Asunto del email', 'alquipress'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="notification_message"><?php esc_html_e('Mensaje', 'alquipress'); ?></label></th>
                        <td>
                            <textarea name="notification_message" id="notification_message" class="large-text" rows="6" placeholder="<?php esc_attr_e('El mensaje que deseas enviar', 'alquipress'); ?>"></textarea>
                            <p class="description"><?php esc_html_e('Etiquetas disponibles:', 'alquipress'); ?> <code>{booking_id} {product_title} {order_date} {order_number} {customer_name} {customer_first_name} {customer_last_name}</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Adjunto', 'alquipress'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="notification_ics" id="notification_ics">
                                <?php esc_html_e('Adjuntar archivo .ics', 'alquipress'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <button type="submit" name="send" class="button button-primary"><?php esc_html_e('Enviar notificación', 'alquipress'); ?></button>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
    }

    private function render_settings()
    {
        if (!current_user_can('manage_bookings_settings')) {
            echo '<p>' . esc_html__('No tienes permiso para acceder a esta sección.', 'alquipress') . '</p>';
            return;
        }

        $base_settings_url = admin_url('admin.php?page=alquipress-bookings&view=settings');
        $tabs_metadata = apply_filters('woocommerce_bookings_settings_page', [
            'availability' => [
                'name' => __('Disponibilidad global', 'woocommerce-bookings'),
                'href' => add_query_arg('tab', 'availability', $base_settings_url),
                'capability' => 'read_global_availability',
                'generate_html' => function () {
                    $wcb_path = defined('WC_BOOKINGS_ABSPATH') ? WC_BOOKINGS_ABSPATH : (WP_PLUGIN_DIR . '/woocommerce-bookings/');
                    if (defined('WC_BOOKINGS_ENABLE_STORE_AVAILABILITY_CALENDAR') && constant('WC_BOOKINGS_ENABLE_STORE_AVAILABILITY_CALENDAR')) {
                        $saved_view = get_option('wc_bookings_store_availability_view_setting', 'calendar');
                        $av_view = isset($_GET['wc_view']) ? sanitize_key(wp_unslash($_GET['wc_view'])) : $saved_view;
                        if ('classic' === $av_view) {
                            update_option('wc_bookings_store_availability_view_setting', 'classic');
                            include $wcb_path . 'includes/admin/views/html-classic-availability-settings.php';
                        } else {
                            update_option('wc_bookings_store_availability_view_setting', 'calendar');
                            include $wcb_path . 'includes/admin/views/html-store-availability-settings.php';
                        }
                    } else {
                        include $wcb_path . 'includes/admin/views/html-classic-availability-settings.php';
                    }
                },
            ],
            'timezones' => [
                'name' => __('Zonas horarias', 'woocommerce-bookings'),
                'href' => add_query_arg('tab', 'timezones', $base_settings_url),
                'capability' => 'manage_bookings_timezones',
                'generate_html' => 'WC_Bookings_Timezone_Settings::generate_form_html',
            ],
            'connection' => [
                'name' => __('Conexión con calendario', 'woocommerce-bookings'),
                'href' => add_query_arg('tab', 'connection', $base_settings_url),
                'capability' => 'manage_bookings_connection',
                'generate_html' => 'WC_Bookings_Google_Calendar_Connection::generate_form_html',
            ],
        ]);

        $current_tab = isset($_GET['tab']) && isset($tabs_metadata[sanitize_key(wp_unslash($_GET['tab']))])
            ? sanitize_title(wp_unslash($_GET['tab']))
            : 'availability';

        ?>
        <div class="ap-wc-embed ap-card">
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper ap-nav-tabs">
                <?php foreach ($tabs_metadata as $tab => $metadata) : ?>
                    <?php if (current_user_can($metadata['capability'])) : ?>
                        <a class="nav-tab <?php echo $tab === $current_tab ? 'nav-tab-active' : ''; ?>"
                           href="<?php echo esc_url($metadata['href']); ?>"><?php echo esc_html($metadata['name']); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <?php if (current_user_can($tabs_metadata[$current_tab]['capability'])) : ?>
                <div class="ap-wc-settings-content">
                    <?php call_user_func($tabs_metadata[$current_tab]['generate_html']); ?>
                </div>
            <?php else : ?>
                <p><?php esc_html_e('No tienes permiso para acceder a esta pestaña.', 'alquipress'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
