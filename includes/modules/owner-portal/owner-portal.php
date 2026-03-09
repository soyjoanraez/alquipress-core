<?php
/**
 * Módulo: Portal propietarios - ver ocupación de sus propiedades
 * Área privada frontend donde el propietario ve la ocupación
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Owner_Portal
{
    const META_USER_ID = 'owner_user_id';
    const PAGE_SLUG = 'mi-area';

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_owner_user_metabox']);
        add_action('save_post_propietario', [$this, 'save_owner_user_id'], 10, 2);
        add_action('init', [$this, 'register_shortcode']);
        add_action('init', [$this, 'maybe_create_portal_page'], 20);
        add_filter('login_redirect', [$this, 'owner_login_redirect'], 10, 3);
        add_action('admin_init', [$this, 'block_owner_from_admin'], 5);
        add_action('template_redirect', [$this, 'protect_panel_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_panel_assets']);
    }

    /**
     * Crear página del portal si no existe (una sola vez)
     */
    public function maybe_create_portal_page()
    {
        if (get_option('alquipress_owner_portal_page_created')) {
            return;
        }
        $existing = get_page_by_path(self::PAGE_SLUG);
        if ($existing) {
            update_option('alquipress_owner_portal_page_created', true);
            update_option('alquipress_owner_panel_page_id', $existing->ID);
            return;
        }
        $page_id = wp_insert_post([
            'post_title' => __('Mi Área', 'alquipress'),
            'post_name' => self::PAGE_SLUG,
            'post_content' => '[alquipress_owner_portal]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
        ], true);
        if (!is_wp_error($page_id)) {
            update_option('alquipress_owner_portal_page_created', true);
            update_option('alquipress_owner_panel_page_id', $page_id);
        }
    }

    public function owner_login_redirect($redirect_to, $request, $user) {
        if (!is_wp_error($user) && isset($user->roles) && is_array($user->roles) && in_array('propietario_alquipress', $user->roles, true)) {
            $page_id = (int) get_option('alquipress_owner_panel_page_id');
            if ($page_id) {
                return get_permalink($page_id);
            }
            return home_url('/' . self::PAGE_SLUG . '/');
        }
        return $redirect_to;
    }

    public function block_owner_from_admin() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        if (current_user_can('manage_options') || current_user_can('manage_woocommerce')) {
            return;
        }
        $user = wp_get_current_user();
        if (!$user->exists() || !in_array('propietario_alquipress', (array) $user->roles, true)) {
            return;
        }
        $page_id = (int) get_option('alquipress_owner_panel_page_id');
        $url = $page_id ? get_permalink($page_id) : home_url('/' . self::PAGE_SLUG . '/');
        wp_safe_redirect($url);
        exit;
    }

    public function protect_panel_page() {
        $page_id = (int) get_option('alquipress_owner_panel_page_id');
        if (!$page_id || !is_page($page_id)) {
            return;
        }
        if (!is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(get_permalink($page_id)));
            exit;
        }
        $user = wp_get_current_user();
        $allowed = ['propietario_alquipress', 'administrator', 'shop_manager'];
        if (!array_intersect($allowed, (array) $user->roles)) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    public function enqueue_panel_assets() {
        $page_id = (int) get_option('alquipress_owner_panel_page_id');
        if (!$page_id || !is_page($page_id)) {
            return;
        }
        wp_enqueue_style(
            'alquipress-owner-panel',
            ALQUIPRESS_URL . 'includes/modules/owner-portal/assets/owner-panel.css',
            [],
            ALQUIPRESS_VERSION
        );
        wp_enqueue_script(
            'alquipress-owner-panel',
            ALQUIPRESS_URL . 'includes/modules/owner-portal/assets/owner-panel.js',
            ['jquery'],
            ALQUIPRESS_VERSION,
            true
        );
    }

    public function add_owner_user_metabox()
    {
        add_meta_box(
            'owner_portal_user',
            __('Portal propietario', 'alquipress'),
            [$this, 'render_owner_user_metabox'],
            'propietario',
            'side',
            'default'
        );
    }

    public function render_owner_user_metabox($post)
    {
        $user_id = (int) get_post_meta($post->ID, self::META_USER_ID, true);
        wp_nonce_field('owner_portal_user_nonce', 'owner_portal_user_nonce');
        $users = get_users(['orderby' => 'display_name', 'number' => 500]);
        ?>
        <p><?php esc_html_e('Vincula un usuario para que pueda acceder al portal de propietarios.', 'alquipress'); ?></p>
        <select name="owner_user_id" id="owner_user_id" style="width:100%">
            <option value=""><?php esc_html_e('Sin vincular', 'alquipress'); ?></option>
            <?php foreach ($users as $u) : ?>
            <option value="<?php echo (int) $u->ID; ?>" <?php selected($user_id, $u->ID); ?>>
                <?php echo esc_html($u->display_name . ' (' . $u->user_login . ')'); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function save_owner_user_id($post_id, $post)
    {
        if (!isset($_POST['owner_portal_user_nonce']) || !wp_verify_nonce($_POST['owner_portal_user_nonce'], 'owner_portal_user_nonce')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $user_id = isset($_POST['owner_user_id']) ? absint($_POST['owner_user_id']) : 0;
        if ($user_id > 0) {
            update_post_meta($post_id, self::META_USER_ID, $user_id);
        } else {
            delete_post_meta($post_id, self::META_USER_ID);
        }
    }

    public function register_shortcode()
    {
        add_shortcode('alquipress_owner_portal', [$this, 'render_portal']);
    }

    /**
     * Obtener ID del propietario (post) vinculado al user_id
     */
    public static function get_owner_id_for_user($user_id)
    {
        if (!$user_id) {
            return 0;
        }
        $owners = get_posts([
            'post_type' => 'propietario',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_query' => [
                ['key' => self::META_USER_ID, 'value' => (int) $user_id, 'compare' => '=']
            ],
            'fields' => 'ids',
        ]);
        return !empty($owners) ? (int) $owners[0] : 0;
    }

    /**
     * Obtener propiedades del propietario
     */
    private function get_owner_properties($owner_id)
    {
        $properties = get_field('owner_properties', $owner_id);
        if (empty($properties)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map(function ($p) {
            return is_object($p) && isset($p->ID) ? (int) $p->ID : (int) $p;
        }, (array) $properties))));
    }

    /**
     * Obtener reservas para las propiedades del propietario
     */
    private function get_bookings_for_owner($owner_id, $year = null)
    {
        $property_ids = $this->get_owner_properties($owner_id);
        if (empty($property_ids)) {
            return [];
        }

        $args = [
            'limit' => -1,
            'status' => ['completed', 'processing', 'deposito-ok', 'in-progress', 'checkout-review', 'pending', 'on-hold'],
            'return' => 'objects',
        ];

        if ($year) {
            $args['date_created'] = $year . '-01-01...' . $year . '-12-31';
        }

        $orders = wc_get_orders($args);
        $bookings = [];

        foreach ($orders as $order) {
            $product_id = (int) $order->get_meta('_booking_product_id');
            if (!$product_id) {
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if ($product) {
                        $product_id = $product->get_id();
                        break;
                    }
                }
            }
            if ($product_id && in_array($product_id, $property_ids, true)) {
                $checkin = $order->get_meta('_booking_checkin_date');
                $checkout = $order->get_meta('_booking_checkout_date');
                $bookings[] = [
                    'order_id' => $order->get_id(),
                    'property_id' => $product_id,
                    'property_name' => get_the_title($product_id),
                    'checkin' => $checkin,
                    'checkout' => $checkout,
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                ];
            }
        }

        usort($bookings, function ($a, $b) {
            return strcmp($a['checkin'] ?? '', $b['checkin'] ?? '');
        });
        return $bookings;
    }

    public function render_portal($atts)
    {
        if (!is_user_logged_in()) {
            return '<div class="alquipress-owner-portal"><p>' . esc_html__('Debes iniciar sesión para acceder al portal de propietarios.', 'alquipress') . '</p><p><a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('Iniciar sesión', 'alquipress') . '</a></p></div>';
        }

        $user_id = get_current_user_id();
        $owner_id = self::get_owner_id_for_user($user_id);
        if (!$owner_id) {
            return '<div class="alquipress-owner-portal"><p>' . esc_html__('No tienes acceso al portal de propietarios. Contacta con la agencia si crees que es un error.', 'alquipress') . '</p></div>';
        }

        $owner = get_post($owner_id);
        $owner_name = $owner ? $owner->post_title : '';
        $stats = $this->get_owner_stats($owner_id);
        $year = isset($_GET['ano']) ? absint($_GET['ano']) : (int) date('Y');
        $bookings = $this->get_bookings_for_owner($owner_id, $year);

        ob_start();
        ?>
        <div class="alquipress-owner-portal alquipress-owner-panel">
            <div class="owner-header">
                <div class="owner-greeting">
                    <h1><?php printf(esc_html__('Bienvenido, %s', 'alquipress'), esc_html($owner_name)); ?></h1>
                    <p><?php echo esc_html(date_i18n('F Y')); ?></p>
                </div>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="owner-logout-btn"><?php esc_html_e('Cerrar sesión', 'alquipress'); ?></a>
            </div>

            <?php $this->render_kpis($stats); ?>

            <div class="owner-tabs">
                <button type="button" class="tab-btn active" data-tab="calendar"><?php esc_html_e('Calendario', 'alquipress'); ?></button>
                <button type="button" class="tab-btn" data-tab="settlements"><?php esc_html_e('Liquidaciones', 'alquipress'); ?></button>
                <button type="button" class="tab-btn" data-tab="documents"><?php esc_html_e('Documentos', 'alquipress'); ?></button>
            </div>

            <div class="tab-content active" id="tab-calendar">
                <div class="owner-calendar-section">
                    <form method="get" class="owner-year-form">
                        <label><?php esc_html_e('Año:', 'alquipress'); ?>
                            <select name="ano" onchange="this.form.submit()">
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--) : ?>
                                <option value="<?php echo (int) $y; ?>" <?php selected($year, $y); ?>><?php echo (int) $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </form>
                    <?php if (empty($bookings)) : ?>
                    <p><?php esc_html_e('No hay reservas registradas para este periodo.', 'alquipress'); ?></p>
                    <?php else : ?>
                    <table class="alquipress-portal-table owner-bookings-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Propiedad', 'alquipress'); ?></th>
                                <th><?php esc_html_e('Check-in', 'alquipress'); ?></th>
                                <th><?php esc_html_e('Check-out', 'alquipress'); ?></th>
                                <th><?php esc_html_e('Estado', 'alquipress'); ?></th>
                                <th class="col-total"><?php esc_html_e('Total', 'alquipress'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b) : ?>
                            <tr>
                                <td><?php echo esc_html($b['property_name']); ?></td>
                                <td><?php echo $b['checkin'] ? esc_html(date_i18n('d/m/Y', strtotime($b['checkin']))) : '—'; ?></td>
                                <td><?php echo $b['checkout'] ? esc_html(date_i18n('d/m/Y', strtotime($b['checkout']))) : '—'; ?></td>
                                <td><?php echo esc_html(wc_get_order_status_name($b['status'])); ?></td>
                                <td class="col-total"><?php echo wc_price($b['total']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-content" id="tab-settlements">
                <?php $this->render_tab_settlements($owner_id); ?>
            </div>

            <div class="tab-content" id="tab-documents">
                <?php $this->render_tab_documents($owner_id); ?>
            </div>

            <p class="owner-footer-note"><?php esc_html_e('Para más información, contacta con la agencia.', 'alquipress'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_owner_stats($owner_id) {
        $month_start = date('Y-m-01 00:00:00');
        $month_end = date('Y-m-t 23:59:59');
        $revenue = class_exists('Alquipress_Owner_Revenue') ? Alquipress_Owner_Revenue::get_instance()->calculate_owner_revenue($owner_id, $month_start, $month_end) : ['total' => 0, 'commission' => 0, 'net' => 0, 'count' => 0];
        $commission_rate = (float) get_field('owner_commission_rate', $owner_id);
        if ($commission_rate <= 0) {
            $commission_rate = 20;
        }
        $property_ids = $this->get_owner_properties($owner_id);
        $booked_nights = 0;
        $next_checkin = null;
        $now = current_time('timestamp');
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['wc-completed', 'wc-processing', 'wc-deposito-ok', 'wc-in-progress'],
            'date_after' => $month_start,
            'date_before' => $month_end,
            'return' => 'objects',
        ]);
        foreach ($orders as $order) {
            $pid = (int) $order->get_meta('_booking_product_id');
            if (!$pid && $order->get_items()) {
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if ($product) {
                        $pid = $product->get_id();
                        break;
                    }
                }
            }
            if (!$pid || !in_array($pid, $property_ids, true)) {
                continue;
            }
            $start = $order->get_meta('_booking_start');
            $end = $order->get_meta('_booking_end');
            if ($start && $end) {
                $nights = max(1, (strtotime($end) - strtotime($start)) / DAY_IN_SECONDS);
                $booked_nights += $nights;
            }
        }
        $all_bookings = $this->get_bookings_for_owner($owner_id, null);
        foreach ($all_bookings as $b) {
            $checkin_ts = !empty($b['checkin']) ? strtotime($b['checkin']) : 0;
            if ($checkin_ts > $now && ($next_checkin === null || $checkin_ts < $next_checkin['ts'])) {
                $next_checkin = [
                    'ts' => $checkin_ts,
                    'label' => date_i18n('j \d\e F', $checkin_ts),
                    'guest' => $b['customer'] ?? '',
                ];
            }
        }
        $days_in_month = (int) date('t');
        $occupancy_pct = $days_in_month > 0 ? round(($booked_nights / $days_in_month) * 100) : 0;
        return [
            'gross_income' => $revenue['total'],
            'commission_amount' => $revenue['commission'],
            'net_income' => $revenue['net'],
            'booked_nights' => $booked_nights,
            'occupancy_pct' => $occupancy_pct,
            'next_checkin' => $next_checkin,
        ];
    }

    private function render_kpis($stats) {
        ?>
        <div class="owner-kpis">
            <div class="kpi-card kpi-income">
                <span class="kpi-icon"><?php esc_html_e('Ingresos', 'alquipress'); ?></span>
                <div class="kpi-data">
                    <strong><?php echo wc_price($stats['net_income']); ?></strong>
                    <span><?php esc_html_e('Ingresos netos este mes', 'alquipress'); ?></span>
                    <small><?php esc_html_e('Bruto:', 'alquipress'); ?> <?php echo wc_price($stats['gross_income']); ?> · <?php esc_html_e('Comisión:', 'alquipress'); ?> <?php echo wc_price($stats['commission_amount']); ?></small>
                </div>
            </div>
            <div class="kpi-card kpi-occupancy">
                <span class="kpi-icon"><?php esc_html_e('Ocupación', 'alquipress'); ?></span>
                <div class="kpi-data">
                    <strong><?php echo (int) $stats['occupancy_pct']; ?>%</strong>
                    <span><?php esc_html_e('Ocupación este mes', 'alquipress'); ?></span>
                    <small><?php echo (int) $stats['booked_nights']; ?> <?php esc_html_e('noches reservadas', 'alquipress'); ?></small>
                </div>
            </div>
            <div class="kpi-card kpi-checkin">
                <span class="kpi-icon"><?php esc_html_e('Próxima entrada', 'alquipress'); ?></span>
                <div class="kpi-data">
                    <?php if (!empty($stats['next_checkin'])) : ?>
                    <strong><?php echo esc_html($stats['next_checkin']['label']); ?></strong>
                    <span><?php esc_html_e('Próxima entrada', 'alquipress'); ?></span>
                    <small><?php echo esc_html($stats['next_checkin']['guest']); ?></small>
                    <?php else : ?>
                    <strong><?php esc_html_e('Sin reservas', 'alquipress'); ?></strong>
                    <span><?php esc_html_e('No hay entradas próximas', 'alquipress'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_tab_settlements($owner_id) {
        $revenue = class_exists('Alquipress_Owner_Revenue') ? Alquipress_Owner_Revenue::get_instance() : null;
        $settlements = [];
        for ($i = 0; $i < 12; $i++) {
            $date = strtotime("-$i months");
            $month_start = date('Y-m-01 00:00:00', $date);
            $month_end = date('Y-m-t 23:59:59', $date);
            $data = $revenue ? $revenue->calculate_owner_revenue($owner_id, $month_start, $month_end) : ['total' => 0, 'commission' => 0, 'net' => 0, 'count' => 0];
            if ($data['total'] > 0 || $data['count'] > 0) {
                $settlements[] = [
                    'label' => date_i18n('F Y', $date),
                    'key' => date('Y-m', $date),
                    'gross' => $data['total'],
                    'commission' => $data['commission'],
                    'net' => $data['net'],
                    'count' => $data['count'],
                ];
            }
        }
        ?>
        <div class="owner-settlements">
            <h2><?php esc_html_e('Historial de liquidaciones', 'alquipress'); ?></h2>
            <?php if (empty($settlements)) : ?>
            <p><?php esc_html_e('No hay liquidaciones en los últimos 12 meses.', 'alquipress'); ?></p>
            <?php else : ?>
            <table class="settlements-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Mes', 'alquipress'); ?></th>
                        <th><?php esc_html_e('Ingresos brutos', 'alquipress'); ?></th>
                        <th><?php esc_html_e('Comisión', 'alquipress'); ?></th>
                        <th><?php esc_html_e('A percibir (neto)', 'alquipress'); ?></th>
                        <th><?php esc_html_e('Reservas', 'alquipress'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($settlements as $row) : ?>
                <tr>
                    <td><strong><?php echo esc_html($row['label']); ?></strong></td>
                    <td><?php echo wc_price($row['gross']); ?></td>
                    <td class="commission-col">- <?php echo wc_price($row['commission']); ?></td>
                    <td class="net-col"><strong><?php echo wc_price($row['net']); ?></strong></td>
                    <td><?php echo (int) $row['count']; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_tab_documents($owner_id) {
        $contract = get_field('owner_contract_pdf', $owner_id);
        $contract_expiry = get_field('owner_contract_expiry', $owner_id);
        $drive_url = get_field('owner_drive_folder_url', $owner_id);
        $properties = get_field('owner_properties', $owner_id);
        $first_product_id = null;
        if (!empty($properties)) {
            $first = is_object($properties[0]) ? $properties[0]->ID : (int) $properties[0];
            $first_product_id = $first;
        }
        $licencia = $first_product_id ? get_field('licencia_turistica', $first_product_id) : '';
        ?>
        <div class="owner-documents">
            <h2><?php esc_html_e('Documentos', 'alquipress'); ?></h2>
            <div class="doc-grid">
                <div class="doc-card">
                    <span class="doc-icon"><?php esc_html_e('Contrato', 'alquipress'); ?></span>
                    <div class="doc-info">
                        <strong><?php esc_html_e('Contrato de gestión', 'alquipress'); ?></strong>
                        <?php if ($contract_expiry) : ?>
                        <small><?php esc_html_e('Vence:', 'alquipress'); ?> <?php echo esc_html(date_i18n('d/m/Y', strtotime($contract_expiry))); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($contract['url'])) : ?>
                    <a href="<?php echo esc_url($contract['url']); ?>" target="_blank" rel="noopener" class="btn-doc-download"><?php esc_html_e('Ver PDF', 'alquipress'); ?></a>
                    <?php else : ?>
                    <span class="doc-pending"><?php esc_html_e('Pendiente', 'alquipress'); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($drive_url) : ?>
                <div class="doc-card">
                    <span class="doc-icon"><?php esc_html_e('Drive', 'alquipress'); ?></span>
                    <div class="doc-info">
                        <strong><?php esc_html_e('Documentación en la nube', 'alquipress'); ?></strong>
                        <small><?php esc_html_e('Facturas, escrituras y más', 'alquipress'); ?></small>
                    </div>
                    <a href="<?php echo esc_url($drive_url); ?>" target="_blank" rel="noopener" class="btn-doc-download"><?php esc_html_e('Abrir Drive', 'alquipress'); ?></a>
                </div>
                <?php endif; ?>
                <?php if ($licencia) : ?>
                <div class="doc-card">
                    <span class="doc-icon"><?php esc_html_e('Licencia', 'alquipress'); ?></span>
                    <div class="doc-info">
                        <strong><?php esc_html_e('Licencia turística', 'alquipress'); ?></strong>
                        <small><?php echo esc_html($licencia); ?></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

Alquipress_Owner_Portal::get_instance();
