<?php
/**
 * Módulo: Página Clientes (Huéspedes)
 * Listado de clientes con datos, pagos, método de pago, estancia y documentación (DNI, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Clients_Page
{
    public function __construct()
    {
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_section_assets']);
    }

    public function maybe_render_section($page)
    {
        if ($page === 'alquipress-clients') {
            $this->render_page();
        }
    }

    public function enqueue_section_assets($page)
    {
        if ($page !== 'alquipress-clients') {
            return;
        }
        wp_enqueue_style(
            'alquipress-clients-page',
            ALQUIPRESS_URL . 'includes/modules/clients-page/assets/clients-page.css',
            [],
            ALQUIPRESS_VERSION
        );
    }

    /**
     * IDs de clientes que tuvieron una estancia en el rango de fechas (solapamiento).
     */
    private function get_customer_ids_with_stay_in_date_range($date_from, $date_to)
    {
        if (!function_exists('wc_get_orders') || $date_from === '' || $date_to === '') {
            return null;
        }
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'processing', 'deposito-ok', 'in-progress', 'checkout-review'],
            'return' => 'ids',
        ]);
        $customer_ids = [];
        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }
            $checkin = $order->get_meta('_booking_checkin_date');
            $checkout = $order->get_meta('_booking_checkout_date');
            if (!$checkin || !$checkout) {
                continue;
            }
            $cin = strtotime($checkin);
            $cout = strtotime($checkout);
            $t_from = strtotime($date_from);
            $t_to = strtotime($date_to);
            if ($cin <= $t_to && $cout >= $t_from) {
                $cid = $order->get_customer_id();
                if ($cid) {
                    $customer_ids[$cid] = true;
                }
            }
        }
        return array_keys($customer_ids);
    }

    /**
     * IDs de clientes que han reservado el inmueble (product_id).
     */
    private function get_customer_ids_for_property($product_id)
    {
        if (!function_exists('wc_get_orders') || !$product_id) {
            return null;
        }
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => array_keys(wc_get_order_statuses()),
            'return' => 'objects',
        ]);
        $customer_ids = [];
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $item_product_id = $item->get_product_id();
                if ((int) $item_product_id === (int) $product_id) {
                    $cid = $order->get_customer_id();
                    if ($cid) {
                        $customer_ids[$cid] = true;
                    }
                    break;
                }
            }
        }
        return array_keys($customer_ids);
    }

    /**
     * IDs de clientes por segmento (gasto, frecuencia, última visita).
     *
     * @param string $segment high_spender|frequent|active_6m|inactive_1y
     * @return array|null IDs de clientes o null si no aplica
     */
    private function get_customer_ids_by_segment($segment)
    {
        if (!function_exists('wc_get_orders') || $segment === '') {
            return null;
        }
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'processing', 'deposito-ok', 'in-progress'],
            'return' => 'objects',
        ]);
        $by_customer = [];
        foreach ($orders as $order) {
            $cid = $order->get_customer_id();
            if (!$cid) {
                continue;
            }
            if (!isset($by_customer[$cid])) {
                $by_customer[$cid] = ['total' => 0, 'count' => 0, 'last_date' => ''];
            }
            $total = (float) ($order->get_meta('_apm_booking_total') ?: $order->get_total());
            $by_customer[$cid]['total'] += $total;
            $by_customer[$cid]['count']++;
            $date = $order->get_date_created() ? $order->get_date_created()->format('Y-m-d') : '';
            if ($date && (!$by_customer[$cid]['last_date'] || $date > $by_customer[$cid]['last_date'])) {
                $by_customer[$cid]['last_date'] = $date;
            }
        }
        $ids = [];
        $cutoff_6m = gmdate('Y-m-d', strtotime('-6 months'));
        $cutoff_1y = gmdate('Y-m-d', strtotime('-12 months'));
        foreach ($by_customer as $cid => $data) {
            $match = false;
            switch ($segment) {
                case 'high_spender':
                    $match = $data['total'] >= 2000;
                    break;
                case 'frequent':
                    $match = $data['count'] >= 2;
                    break;
                case 'active_6m':
                    $match = $data['last_date'] >= $cutoff_6m;
                    break;
                case 'inactive_1y':
                    $match = $data['last_date'] === '' || $data['last_date'] < $cutoff_1y;
                    break;
            }
            if ($match) {
                $ids[] = $cid;
            }
        }
        if ($segment === 'inactive_1y') {
            $with_qualifying_orders = array_keys($by_customer);
            $all_customers = get_users(['role' => 'customer', 'fields' => 'ID']);
            foreach ($all_customers as $uid) {
                if (!in_array($uid, $with_qualifying_orders, true)) {
                    $ids[] = $uid;
                }
            }
        }
        return $ids;
    }

    /**
     * Lista de productos (inmuebles) para el filtro.
     */
    private function get_properties_for_filter()
    {
        if (!function_exists('wc_get_products')) {
            $posts = get_posts([
                'post_type' => 'product',
                'numberposts' => 500,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
            ]);
            $list = [];
            foreach ($posts as $p) {
                $list[(int) $p->ID] = $p->post_title;
            }
            return $list;
        }
        $products = wc_get_products([
            'limit' => 500,
            'status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        $list = [];
        foreach ($products as $p) {
            $list[$p->get_id()] = $p->get_name();
        }
        return $list;
    }

    /**
     * Obtener clientes (usuarios con rol customer). Acepta filtros y paginación.
     *
     * @param int   $limit   Límite por página
     * @param array $filters Filtros (name, date_from, date_to, property_id)
     * @param int   $paged   Página actual (1-based)
     * @return array [clients => array, total => int]
     */
    private function get_clients($limit = 25, $filters = [], $paged = 1)
    {
        $filter_name = isset($filters['name']) ? trim((string) $filters['name']) : '';
        $filter_date_from = isset($filters['date_from']) ? sanitize_text_field($filters['date_from']) : '';
        $filter_date_to = isset($filters['date_to']) ? sanitize_text_field($filters['date_to']) : '';
        $filter_property_id = isset($filters['property_id']) ? (int) $filters['property_id'] : 0;
        $filter_segment = isset($filters['segment']) ? sanitize_key($filters['segment']) : '';
        $offset = ($paged - 1) * $limit;

        $filter_by_ids = null;
        if ($filter_segment !== '' && in_array($filter_segment, ['high_spender', 'frequent', 'active_6m', 'inactive_1y'], true)) {
            $ids_seg = $this->get_customer_ids_by_segment($filter_segment);
            if ($ids_seg !== null) {
                $filter_by_ids = $filter_by_ids === null ? $ids_seg : array_intersect($filter_by_ids, $ids_seg);
            }
        }
        if ($filter_date_from !== '' || $filter_date_to !== '') {
            $from = $filter_date_from ?: '1970-01-01';
            $to = $filter_date_to ?: gmdate('Y-m-d');
            $ids_date = $this->get_customer_ids_with_stay_in_date_range($from, $to);
            if ($ids_date !== null) {
                $filter_by_ids = $filter_by_ids === null ? $ids_date : array_intersect($filter_by_ids, $ids_date);
            }
        }
        if ($filter_property_id > 0) {
            $ids_prop = $this->get_customer_ids_for_property($filter_property_id);
            if ($ids_prop !== null) {
                $filter_by_ids = $filter_by_ids === null ? $ids_prop : array_intersect($filter_by_ids, $ids_prop);
            }
        }

        if ($filter_by_ids !== null) {
            $filter_by_ids = array_values($filter_by_ids);
            $args = [
                'role' => 'customer',
                'include' => $filter_by_ids,
                'orderby' => 'registered',
                'order' => 'DESC',
                'number' => -1,
            ];
            if ($filter_name !== '') {
                $args['search'] = '*' . $filter_name . '*';
                $args['search_columns'] = ['user_login', 'user_email', 'display_name', 'user_nicename'];
            }
            $users = get_users($args);
            $total = count($users);
            $users = array_slice(array_values($users), $offset, $limit);
        } else {
            $args = [
                'role' => 'customer',
                'number' => $limit,
                'offset' => $offset,
                'orderby' => 'registered',
                'order' => 'DESC',
                'count_total' => true,
            ];
            if ($filter_name !== '') {
                $args['search'] = '*' . $filter_name . '*';
                $args['search_columns'] = ['user_login', 'user_email', 'display_name', 'user_nicename'];
            }
            $user_query = new WP_User_Query($args);
            $users = $user_query->get_results();
            $total = $user_query->get_total();
        }

        $clients = [];
        foreach ($users as $user) {
            $total_spent = $this->get_user_total_spent($user->ID);
            $orders = $this->get_user_orders($user->ID, 1);
            $last_order = !empty($orders) ? $orders[0] : null;
            $last_payment = $last_order && function_exists('wc_get_order') ? $last_order->get_payment_method_title() : '';
            $last_stay = $this->get_last_stay($user->ID);
            $has_docs = $this->user_has_documents($user->ID);
            $docs_info = $this->get_user_documents_info($user->ID);
            $phone = get_field('guest_phone', 'user_' . $user->ID);
            if (empty($phone) && $last_order) {
                $phone = $last_order->get_billing_phone();
            }
            $guest_rating = get_field('guest_rating', 'user_' . $user->ID);
            if ($guest_rating === '' || $guest_rating === null || $guest_rating === false) {
                $guest_rating = (int) get_user_meta($user->ID, 'guest_rating', true);
            }
            $guest_rating = is_numeric($guest_rating) ? (float) $guest_rating : null;

            $clients[] = [
                'id' => $user->ID,
                'name' => $user->display_name ?: ($user->first_name . ' ' . $user->last_name) ?: $user->user_login,
                'email' => $user->user_email,
                'phone' => is_string($phone) ? $phone : '',
                'total_spent' => $total_spent,
                'payment_method' => $last_payment ?: '-',
                'last_stay' => $last_stay,
                'has_documents' => $has_docs,
                'documents_info' => $docs_info,
                'guest_rating' => $guest_rating,
                'profile_url' => admin_url('users.php?page=alquipress-guest-profile&user_id=' . $user->ID),
                'edit_user_url' => get_edit_user_link($user->ID),
            ];
        }

        return ['clients' => $clients, 'total' => $total];
    }

    private function get_user_total_spent($user_id)
    {
        if (!function_exists('wc_get_orders')) {
            return 0;
        }
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit' => -1,
            'status' => ['completed', 'processing', 'deposito-ok', 'in-progress'],
        ]);
        $total = 0;
        foreach ($orders as $order) {
            $total += (float) $order->get_total();
        }
        return $total;
    }

    private function get_user_orders($user_id, $limit = 1)
    {
        if (!function_exists('wc_get_orders')) {
            return [];
        }
        return wc_get_orders([
            'customer_id' => $user_id,
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    /**
     * Última estancia: fechas + propiedad
     */
    private function get_last_stay($user_id)
    {
        $orders = $this->get_user_orders($user_id, 5);
        foreach ($orders as $order) {
            $checkin = $order->get_meta('_booking_checkin_date');
            $checkout = $order->get_meta('_booking_checkout_date');
            if ($checkin || $checkout) {
                $prop = $this->get_order_property_name($order);
                $dates = trim(($checkin ? date_i18n('j M Y', strtotime($checkin)) : '') . ' – ' . ($checkout ? date_i18n('j M Y', strtotime($checkout)) : ''), ' –');
                return ['dates' => $dates, 'property' => $prop ?: '-'];
            }
        }
        return null;
    }

    private function get_order_property_name($order)
    {
        return Alquipress_Property_Helper::get_order_property_name($order);
    }

    /**
     * Si el usuario tiene documentación (DNI/Pasaporte) en ACF guest_documents
     */
    private function user_has_documents($user_id)
    {
        $docs = get_field('guest_documents', 'user_' . $user_id);
        return is_array($docs) && count($docs) > 0;
    }

    /**
     * Obtener información detallada de documentos del usuario
     */
    private function get_user_documents_info($user_id)
    {
        $docs = get_field('guest_documents', 'user_' . $user_id);
        if (!is_array($docs) || empty($docs)) {
            return [
                'has_documents' => false,
                'count' => 0,
                'expired_count' => 0,
                'expiring_soon_count' => 0,
                'documents' => []
            ];
        }

        $expired_count = 0;
        $expiring_soon_count = 0;
        $documents_list = [];
        $today = strtotime('today');
        $expiring_threshold = strtotime('+30 days');

        foreach ($docs as $doc) {
            $tipo = isset($doc['tipo_doc']) ? $doc['tipo_doc'] : (isset($doc['nombre_doc']) ? $doc['nombre_doc'] : '');
            $numero = isset($doc['numero_doc']) ? $doc['numero_doc'] : '';
            $fecha_vencimiento = isset($doc['fecha_vencimiento']) ? $doc['fecha_vencimiento'] : '';
            $archivo = isset($doc['archivo_doc']) ? $doc['archivo_doc'] : null;

            $is_expired = false;
            $is_expiring_soon = false;
            if ($fecha_vencimiento) {
                $expiry_timestamp = strtotime($fecha_vencimiento);
                if ($expiry_timestamp < $today) {
                    $is_expired = true;
                    $expired_count++;
                } elseif ($expiry_timestamp <= $expiring_threshold) {
                    $is_expiring_soon = true;
                    $expiring_soon_count++;
                }
            }

            $documents_list[] = [
                'tipo' => $tipo,
                'numero' => $numero,
                'fecha_vencimiento' => $fecha_vencimiento,
                'archivo' => $archivo,
                'is_expired' => $is_expired,
                'is_expiring_soon' => $is_expiring_soon,
            ];
        }

        return [
            'has_documents' => true,
            'count' => count($docs),
            'expired_count' => $expired_count,
            'expiring_soon_count' => $expiring_soon_count,
            'documents' => $documents_list
        ];
    }

    /**
     * Métricas para la cabecera
     */
    private function get_metrics()
    {
        $users = get_users(['role' => 'customer', 'number' => -1, 'fields' => 'ID']);
        $total = count($users);
        $with_docs = 0;
        $expired_docs = 0;
        $expiring_soon_docs = 0;
        $high_rating = 0; // Valoración interna ≥ 4
        foreach ($users as $uid) {
            if ($this->user_has_documents($uid)) {
                $with_docs++;
                $docs_info = $this->get_user_documents_info($uid);
                if ($docs_info['expired_count'] > 0) {
                    $expired_docs++;
                }
                if ($docs_info['expiring_soon_count'] > 0) {
                    $expiring_soon_docs++;
                }
            }
            $r = get_field('guest_rating', 'user_' . $uid);
            if ($r === '' || $r === null || $r === false) {
                $r = get_user_meta($uid, 'guest_rating', true);
            }
            if (is_numeric($r) && (float) $r >= 4) {
                $high_rating++;
            }
        }
        return [
            'total_clients' => $total,
            'with_documents' => $with_docs,
            'expired_documents' => $expired_docs,
            'expiring_soon_documents' => $expiring_soon_docs,
            'high_rating' => $high_rating,
        ];
    }

    public function render_page()
    {
        $page_url = admin_url('admin.php?page=alquipress-clients');
        $filter_name = isset($_GET['filter_name']) ? sanitize_text_field(wp_unslash($_GET['filter_name'])) : '';
        $filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field(wp_unslash($_GET['filter_date_from'])) : '';
        $filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field(wp_unslash($_GET['filter_date_to'])) : '';
        $filter_property_id = isset($_GET['filter_property']) ? (int) $_GET['filter_property'] : 0;
        $filter_segment = isset($_GET['filter_segment']) ? sanitize_key($_GET['filter_segment']) : '';
        $paged = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $per_page = 24;

        $filters = [
            'name' => $filter_name,
            'date_from' => $filter_date_from,
            'date_to' => $filter_date_to,
            'property_id' => $filter_property_id,
            'segment' => $filter_segment,
        ];

        $metrics = $this->get_metrics();
        $result = $this->get_clients($per_page, $filters, $paged);
        $clients = $result['clients'];
        $total_clients = $result['total'];
        $total_pages = (int) ceil($total_clients / $per_page);
        $users_url = admin_url('users.php');
        $properties_list = $this->get_properties_for_filter();
        $has_filters = $filter_name !== '' || $filter_date_from !== '' || $filter_date_to !== '' || $filter_property_id > 0 || $filter_segment !== '';

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap alquipress-clients-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('clients'); ?>
                <main class="ap-owners-main">
                    <header class="ap-clients-header">
                        <div class="ap-clients-header-left">
                            <h1 class="ap-clients-title"><?php esc_html_e('Clientes', 'alquipress'); ?></h1>
                            <p class="ap-clients-subtitle"><?php esc_html_e('Datos, pagos, estancia y documentación (DNI, etc.)', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-clients-header-right">
                            <a href="<?php echo esc_url($users_url); ?>" class="ap-clients-btn ap-clients-btn-primary"><?php esc_html_e('Ver todos en Usuarios', 'alquipress'); ?></a>
                        </div>
                    </header>

                    <form method="get" action="<?php echo esc_url($page_url); ?>" class="ap-clients-filters">
                        <input type="hidden" name="page" value="alquipress-clients">
                        <div class="ap-clients-filters-row">
                            <div class="ap-clients-filter-group">
                                <label for="filter_name" class="ap-clients-filter-label"><?php esc_html_e('Nombre o email', 'alquipress'); ?></label>
                                <input type="text" id="filter_name" name="filter_name" value="<?php echo esc_attr($filter_name); ?>"
                                    placeholder="<?php esc_attr_e('Buscar por nombre, email...', 'alquipress'); ?>" class="ap-clients-filter-input">
                            </div>
                            <div class="ap-clients-filter-group">
                                <label for="filter_date_from" class="ap-clients-filter-label"><?php esc_html_e('Estancia desde', 'alquipress'); ?></label>
                                <input type="date" id="filter_date_from" name="filter_date_from" value="<?php echo esc_attr($filter_date_from); ?>" class="ap-clients-filter-input">
                            </div>
                            <div class="ap-clients-filter-group">
                                <label for="filter_date_to" class="ap-clients-filter-label"><?php esc_html_e('Estancia hasta', 'alquipress'); ?></label>
                                <input type="date" id="filter_date_to" name="filter_date_to" value="<?php echo esc_attr($filter_date_to); ?>" class="ap-clients-filter-input">
                            </div>
                            <div class="ap-clients-filter-group">
                                <label for="filter_property" class="ap-clients-filter-label"><?php esc_html_e('Inmueble', 'alquipress'); ?></label>
                                <select id="filter_property" name="filter_property" class="ap-clients-filter-select">
                                    <option value=""><?php esc_html_e('Todos los inmuebles', 'alquipress'); ?></option>
                                    <?php foreach ($properties_list as $pid => $title) : ?>
                                        <option value="<?php echo (int) $pid; ?>" <?php selected($filter_property_id, $pid); ?>><?php echo esc_html($title); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="ap-clients-filter-group">
                                <label for="filter_segment" class="ap-clients-filter-label"><?php esc_html_e('Segmento', 'alquipress'); ?></label>
                                <select id="filter_segment" name="filter_segment" class="ap-clients-filter-select">
                                    <option value=""><?php esc_html_e('Todos', 'alquipress'); ?></option>
                                    <option value="high_spender" <?php selected($filter_segment, 'high_spender'); ?>><?php esc_html_e('Alto gasto (≥2000€)', 'alquipress'); ?></option>
                                    <option value="frequent" <?php selected($filter_segment, 'frequent'); ?>><?php esc_html_e('Cliente frecuente (2+ reservas)', 'alquipress'); ?></option>
                                    <option value="active_6m" <?php selected($filter_segment, 'active_6m'); ?>><?php esc_html_e('Activo (últimos 6 meses)', 'alquipress'); ?></option>
                                    <option value="inactive_1y" <?php selected($filter_segment, 'inactive_1y'); ?>><?php esc_html_e('Inactivo (>1 año sin estancia)', 'alquipress'); ?></option>
                                </select>
                            </div>
                            <div class="ap-clients-filter-actions">
                                <button type="submit" class="ap-clients-btn ap-clients-btn-filter"><?php esc_html_e('Filtrar', 'alquipress'); ?></button>
                                <?php if ($has_filters) : ?>
                                    <a href="<?php echo esc_url($page_url); ?>" class="ap-clients-btn ap-clients-btn-clear"><?php esc_html_e('Limpiar', 'alquipress'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <div class="ap-clients-metrics-row">
                        <div class="ap-clients-metric-card">
                            <span class="ap-clients-metric-label"><?php esc_html_e('Total clientes', 'alquipress'); ?></span>
                            <span class="ap-clients-metric-value"><?php echo (int) $metrics['total_clients']; ?></span>
                        </div>
                        <div class="ap-clients-metric-card">
                            <span class="ap-clients-metric-label"><?php esc_html_e('Con documentación (DNI/pasaporte)', 'alquipress'); ?></span>
                            <span class="ap-clients-metric-value"><?php echo (int) $metrics['with_documents']; ?></span>
                        </div>
                        <?php if ($metrics['expired_documents'] > 0) : ?>
                            <div class="ap-clients-metric-card ap-clients-metric-warning">
                                <span class="ap-clients-metric-label"><?php esc_html_e('Documentos vencidos', 'alquipress'); ?></span>
                                <span class="ap-clients-metric-value"><?php echo (int) $metrics['expired_documents']; ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($metrics['expiring_soon_documents'] > 0) : ?>
                            <div class="ap-clients-metric-card ap-clients-metric-info">
                                <span class="ap-clients-metric-label"><?php esc_html_e('Documentos próximos a vencer', 'alquipress'); ?></span>
                                <span class="ap-clients-metric-value"><?php echo (int) $metrics['expiring_soon_documents']; ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="ap-clients-metric-card">
                            <span class="ap-clients-metric-label"><?php esc_html_e('Valoración interna alta (≥4)', 'alquipress'); ?></span>
                            <span class="ap-clients-metric-value"><?php echo (int) $metrics['high_rating']; ?></span>
                        </div>
                    </div>

                    <div class="ap-clients-table-wrap">
                        <table class="ap-clients-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Cliente', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Contacto', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Valoración', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Total pagado', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Cómo paga', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Última estancia', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Documentación', 'alquipress'); ?></th>
                                    <th><?php esc_html_e('Acciones', 'alquipress'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clients)) : ?>
                                    <tr>
                                        <td colspan="8" class="ap-clients-empty"><?php esc_html_e('No hay clientes registrados (rol Cliente).', 'alquipress'); ?></td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($clients as $c) : ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($c['name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="ap-clients-email"><?php echo esc_html($c['email']); ?></span>
                                                <?php if ($c['phone']) : ?>
                                                    <br><span class="ap-clients-phone"><?php echo esc_html($c['phone']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="ap-clients-rating-cell">
                                                <?php if ($c['guest_rating'] !== null && $c['guest_rating'] > 0) : ?>
                                                    <?php
                                                    $r = (float) $c['guest_rating'];
                                                    $full = (int) floor($r);
                                                    $half = ($r - $full) >= 0.5;
                                                    ?>
                                                    <span class="ap-clients-rating-stars" title="<?php echo esc_attr(sprintf(__('%s de 5 (valoración interna)', 'alquipress'), number_format_i18n($r, 1))); ?>">
                                                        <?php echo str_repeat('★', $full); ?><?php echo $half ? '½' : ''; ?>
                                                    </span>
                                                    <span class="ap-clients-rating-num"><?php echo number_format_i18n($r, 1); ?></span>
                                                <?php else : ?>
                                                    <span class="ap-clients-rating-none">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo function_exists('wc_price') ? wc_price($c['total_spent']) : number_format_i18n($c['total_spent'], 2) . ' €'; ?></td>
                                            <td><?php echo esc_html($c['payment_method']); ?></td>
                                            <td>
                                                <?php if ($c['last_stay']) : ?>
                                                    <span class="ap-clients-dates"><?php echo esc_html($c['last_stay']['dates']); ?></span>
                                                    <br><span class="ap-clients-property"><?php echo esc_html($c['last_stay']['property']); ?></span>
                                                <?php else : ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                            <td class="ap-clients-doc-cell">
                                                <?php if ($c['has_documents']) : ?>
                                                    <?php
                                                    $docs_info = $c['documents_info'];
                                                    $badge_class = 'ap-clients-doc-ok';
                                                    $badge_text = sprintf(_n('%d documento', '%d documentos', $docs_info['count'], 'alquipress'), $docs_info['count']);
                                                    $tooltip_parts = [];
                                                    
                                                    if ($docs_info['expired_count'] > 0) {
                                                        $badge_class = 'ap-clients-doc-expired';
                                                        $tooltip_parts[] = sprintf(_n('%d documento vencido', '%d documentos vencidos', $docs_info['expired_count'], 'alquipress'), $docs_info['expired_count']);
                                                    } elseif ($docs_info['expiring_soon_count'] > 0) {
                                                        $badge_class = 'ap-clients-doc-expiring';
                                                        $tooltip_parts[] = sprintf(_n('%d documento próximo a vencer', '%d documentos próximos a vencer', $docs_info['expiring_soon_count'], 'alquipress'), $docs_info['expiring_soon_count']);
                                                    }
                                                    
                                                    if (!empty($docs_info['documents'])) {
                                                        foreach ($docs_info['documents'] as $doc) {
                                                            $doc_label = '';
                                                            if (!empty($doc['tipo'])) {
                                                                $doc_label = alquipress_ses_get_document_label($doc['tipo']);
                                                            }
                                                            if (!empty($doc['numero'])) {
                                                                $doc_label .= ($doc_label ? ': ' : '') . $doc['numero'];
                                                            }
                                                            if (!empty($doc['fecha_vencimiento'])) {
                                                                $doc_label .= ($doc_label ? ' - ' : '') . date_i18n('d/m/Y', strtotime($doc['fecha_vencimiento']));
                                                                if ($doc['is_expired']) {
                                                                    $doc_label .= ' (Vencido)';
                                                                } elseif ($doc['is_expiring_soon']) {
                                                                    $doc_label .= ' (Próximo a vencer)';
                                                                }
                                                            }
                                                            if ($doc_label) {
                                                                $tooltip_parts[] = $doc_label;
                                                            }
                                                        }
                                                    }
                                                    
                                                    $tooltip = !empty($tooltip_parts) ? implode("\n", $tooltip_parts) : '';
                                                    ?>
                                                    <span class="ap-clients-doc-badge <?php echo esc_attr($badge_class); ?>" 
                                                          title="<?php echo esc_attr($tooltip); ?>">
                                                        <?php echo esc_html($badge_text); ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="ap-clients-doc-badge ap-clients-doc-missing"><?php esc_html_e('No', 'alquipress'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo esc_url($c['profile_url']); ?>" class="ap-clients-link"><?php esc_html_e('Ver perfil', 'alquipress'); ?></a>
                                                <span class="ap-clients-sep">|</span>
                                                <a href="<?php echo esc_url($c['edit_user_url']); ?>" class="ap-clients-link"><?php esc_html_e('Editar', 'alquipress'); ?></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1) : ?>
                        <nav class="ap-clients-pagination" aria-label="<?php esc_attr_e('Paginación', 'alquipress'); ?>">
                            <?php
                            $paginate_args = ['page' => 'alquipress-clients'];
                            if ($filter_name !== '') $paginate_args['filter_name'] = $filter_name;
                            if ($filter_date_from !== '') $paginate_args['filter_date_from'] = $filter_date_from;
                            if ($filter_date_to !== '') $paginate_args['filter_date_to'] = $filter_date_to;
                            if ($filter_property_id > 0) $paginate_args['filter_property'] = $filter_property_id;
                            if ($filter_segment !== '') $paginate_args['filter_segment'] = $filter_segment;
                            $paginate_base = add_query_arg($paginate_args, admin_url('admin.php'));
                            echo wp_kses_post(paginate_links([
                                'base' => add_query_arg('paged', '%#%', $paginate_base),
                                'format' => '',
                                'prev_text' => '&larr; ' . __('Anterior', 'alquipress'),
                                'next_text' => __('Siguiente', 'alquipress') . ' &rarr;',
                                'total' => $total_pages,
                                'current' => $paged,
                                'type' => 'list',
                            ]));
                            ?>
                        </nav>
                    <?php endif; ?>
                </main>
            </div>
        </div>
        <?php
    }
}

new Alquipress_Clients_Page();
