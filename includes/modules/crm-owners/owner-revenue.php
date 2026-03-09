<?php
/**
 * Módulo: Owner Revenue Reporting
 * 
 * Calcula y muestra los ingresos totales de cada propietario
 * basándose en los pedidos completados de sus propiedades.
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Owner_Revenue
{
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
        // Añadir metabox en la página de edición de propietario
        add_action('add_meta_boxes', [$this, 'add_revenue_metabox']);

        // Añadir columna de ingresos en el listado de propietarios
        add_filter('manage_propietario_posts_columns', [$this, 'add_revenue_column'], 20);
        add_action('manage_propietario_posts_custom_column', [$this, 'populate_revenue_column'], 20, 2);

        // Shortcode para mostrar panel de propietario
        add_shortcode('owner_dashboard', [$this, 'render_owner_dashboard']);

        // Invalidación de cache en cambios relevantes
        add_action('save_post_propietario', [$this, 'invalidate_owner_cache_on_save'], 20, 3);
        add_action('save_post_product', [$this, 'invalidate_cache_on_product_save'], 20, 3);
        add_action('woocommerce_order_status_changed', [$this, 'invalidate_cache_for_order'], 10, 4);
        add_action('woocommerce_new_order', [$this, 'invalidate_cache_for_order_id'], 10, 1);
        add_action('woocommerce_update_order', [$this, 'invalidate_cache_for_order_id'], 10, 1);
        add_action('save_post_shop_order', [$this, 'invalidate_cache_for_order_post'], 20, 3);
    }

    /**
     * Calcula los ingresos totales de un propietario (Altamente Optimizado)
     */
    public function calculate_owner_revenue($owner_id, $start_date = null, $end_date = null)
    {
        if (is_object($owner_id)) {
            $owner_id = isset($owner_id->ID) ? $owner_id->ID : 0;
        }
        $owner_id = (int) $owner_id;

        // Obtener las propiedades del propietario
        $properties = get_field('owner_properties', $owner_id);
        if (empty($properties)) {
            return ['total' => 0, 'commission' => 0, 'net' => 0, 'count' => 0, 'properties' => []];
        }

        // ACF puede devolver IDs o objetos WP_Post; normalizar siempre a IDs.
        $property_ids = array_values(array_unique(array_filter(array_map(function ($p) {
            if (is_object($p) && isset($p->ID)) {
                return (int) $p->ID;
            }
            return (int) $p;
        }, (array) $properties))));

        if (empty($property_ids)) {
            return ['total' => 0, 'commission' => 0, 'net' => 0, 'count' => 0, 'properties' => []];
        }

        $cache_key = $this->get_revenue_cache_key($owner_id, $start_date, $end_date, $property_ids);
        $cached = $this->cache_get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $this->log_cache_event('cache_miss', [
            'owner_id' => (int) $owner_id,
            'cache_key' => $cache_key
        ]);

        global $wpdb;
        $property_list = implode(',', $property_ids);
        $status_string = "'wc-completed', 'wc-processing', 'wc-deposito-ok', 'wc-in-progress'";

        // QUERY MAESTRA: Calcula total, conteo y desglose por propiedad en una sola pasada.
        // Soporta pagos escalonados usando COALESCE para priorizar el total real de Alquipress.
        $sql = "
            SELECT 
                pm_prod.meta_value as product_id,
                COUNT(DISTINCT p.ID) as bookings_count,
                SUM(COALESCE(CAST(pm_real.meta_value AS DECIMAL(12,2)), CAST(pm_wc.meta_value AS DECIMAL(12,2)))) as total_revenue
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_wc ON p.ID = pm_wc.post_id AND pm_wc.meta_key = '_order_total'
            INNER JOIN {$wpdb->postmeta} pm_prod ON p.ID = pm_prod.post_id AND pm_prod.meta_key = '_booking_product_id'
            LEFT JOIN {$wpdb->postmeta} pm_real ON p.ID = pm_real.post_id AND pm_real.meta_key = '_apm_booking_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ($status_string)
            AND pm_prod.meta_value IN ($property_list)
        ";

        if ($start_date && $end_date) {
            $sql .= $wpdb->prepare(" AND p.post_date >= %s AND p.post_date <= %s", $start_date . ' 00:00:00', $end_date . ' 23:59:59');
        }

        $sql .= " GROUP BY product_id";
        
        $results = $wpdb->get_results($sql);

        $total_revenue = 0;
        $total_bookings = 0;
        $property_breakdown = [];

        foreach ($results as $row) {
            $pid = (int) $row->product_id;
            $property_breakdown[$pid] = [
                'name' => get_the_title($pid),
                'revenue' => (float) $row->total_revenue,
                'bookings' => (int) $row->bookings_count
            ];
            $total_revenue += (float) $row->total_revenue;
            $total_bookings += (int) $row->bookings_count;
        }

        // Comisión por propiedad: usar property_commission_rate si existe, si no owner_commission_rate
        $commission = 0;
        foreach ($property_breakdown as $pid => $prop_data) {
            $prop_rate = get_post_meta($pid, 'property_commission_rate', true);
            if ($prop_rate === '' || $prop_rate === false) {
                $prop_rate = get_field('owner_commission_rate', $owner_id);
            }
            $prop_rate = floatval($prop_rate);
            if ($prop_rate > 0) {
                $commission += ($prop_data['revenue'] * $prop_rate) / 100;
            }
        }

        $result = [
            'total' => $total_revenue,
            'commission' => $commission,
            'net' => $total_revenue - $commission,
            'count' => $total_bookings,
            'properties' => $property_breakdown
        ];

        $this->cache_set($cache_key, $result, $this->get_cache_ttl());
        return $result;
    }

    private function get_revenue_cache_key($owner_id, $start_date, $end_date, $property_ids)
    {
        $version = $this->get_revenue_cache_version($owner_id);
        $payload = [
            'owner' => (int) $owner_id,
            'start' => $start_date ?: '',
            'end' => $end_date ?: '',
            'properties' => $property_ids,
            'v' => $version
        ];

        return 'alq_owner_revenue_' . md5(wp_json_encode($payload));
    }

    private function get_cache_ttl()
    {
        return (int) apply_filters('alquipress_owner_revenue_cache_ttl', 10 * MINUTE_IN_SECONDS);
    }

    private function get_invalidation_statuses()
    {
        $statuses = apply_filters('alquipress_owner_revenue_invalidation_statuses', ['processing', 'completed']);
        if (!is_array($statuses)) {
            return ['processing', 'completed'];
        }

        return array_values(array_unique(array_map('sanitize_key', $statuses)));
    }

    private function should_use_object_cache()
    {
        $use = wp_using_ext_object_cache();
        return (bool) apply_filters('alquipress_owner_revenue_use_object_cache', $use);
    }

    private function cache_get($key)
    {
        if ($this->should_use_object_cache()) {
            return wp_cache_get($key, 'alquipress_owner_revenue');
        }

        return get_transient($key);
    }

    private function cache_set($key, $value, $ttl)
    {
        if ($this->should_use_object_cache()) {
            wp_cache_set($key, $value, 'alquipress_owner_revenue', $ttl);
            return;
        }

        set_transient($key, $value, $ttl);
    }

    private function is_cache_logging_enabled()
    {
        return (bool) apply_filters('alquipress_owner_revenue_cache_log', false);
    }

    private function log_cache_event($event, array $context = [])
    {
        if (!$this->is_cache_logging_enabled()) {
            return;
        }

        $context['event'] = $event;
        $context['timestamp'] = time();
        $context['datetime'] = current_time('mysql');

        do_action('alquipress_owner_revenue_cache_event', $event, $context);

        $log_line = 'ALQUIPRESS revenue cache: ' . wp_json_encode($context) . PHP_EOL;
        $log_file = $this->get_cache_log_file();
        if ($log_file) {
            $this->rotate_cache_log_if_needed($log_file);
            error_log($log_line, 3, $log_file);
            return;
        }

        error_log($log_line);
    }

    private function get_cache_log_file()
    {
        $upload_dir = wp_upload_dir();
        if (empty($upload_dir['basedir'])) {
            return '';
        }

        $dir = trailingslashit($upload_dir['basedir']) . 'alquipress-logs';
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            return '';
        }

        $file = trailingslashit($dir) . 'owner-revenue-cache.log';

        return apply_filters('alquipress_owner_revenue_cache_log_file', $file);
    }

    private function rotate_cache_log_if_needed($file)
    {
        if (!file_exists($file)) {
            return;
        }

        $max_bytes = (int) apply_filters('alquipress_owner_revenue_cache_log_max_bytes', 5 * 1024 * 1024);
        $max_files = (int) apply_filters('alquipress_owner_revenue_cache_log_max_files', 3);

        if ($max_bytes <= 0 || $max_files <= 0) {
            return;
        }

        $size = filesize($file);
        if ($size === false || $size < $max_bytes) {
            return;
        }

        for ($i = $max_files - 1; $i >= 1; $i--) {
            $src = $file . '.' . $i;
            $dest = $file . '.' . ($i + 1);
            if (file_exists($src)) {
                @rename($src, $dest);
            }
        }

        @rename($file, $file . '.1');
    }

    private function get_revenue_cache_version($owner_id)
    {
        return (int) get_post_meta($owner_id, '_alq_owner_revenue_cache_v', true);
    }

    private function bump_revenue_cache_version($owner_id)
    {
        $owner_id = (int) $owner_id;
        if ($owner_id <= 0) {
            return;
        }

        $current = $this->get_revenue_cache_version($owner_id);
        update_post_meta($owner_id, '_alq_owner_revenue_cache_v', $current + 1);
    }

    public function invalidate_owner_cache_on_save($post_id, $post, $update)
    {
        if (!is_admin()) {
            return;
        }

        if ($post && $post->post_type === 'propietario') {
            $this->bump_revenue_cache_version($post_id);
        }
    }

    public function invalidate_cache_on_product_save($post_id, $post, $update)
    {
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        $owner_ids = $this->get_owner_ids_for_product($post_id);
        foreach ($owner_ids as $owner_id) {
            $this->bump_revenue_cache_version($owner_id);
        }
    }

    public function invalidate_owner_cache_on_acf_save($post_id)
    {
        if (is_numeric($post_id) && get_post_type((int) $post_id) === 'propietario') {
            $this->bump_revenue_cache_version((int) $post_id);
        }
    }

    public function invalidate_cache_for_order($order_id, $old_status, $new_status, $order)
    {
        if (!$order || !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        $allowed_statuses = $this->get_invalidation_statuses();
        $current_status = $order->get_status();

        if ($new_status === null && $old_status === null) {
            if (!in_array($current_status, $allowed_statuses, true)) {
                return;
            }
        } else {
            if (
                !in_array((string) $new_status, $allowed_statuses, true) &&
                !in_array((string) $old_status, $allowed_statuses, true)
            ) {
                return;
            }
        }

        $owner_ids = $this->get_owner_ids_for_order($order);
        foreach ($owner_ids as $owner_id) {
            $this->bump_revenue_cache_version($owner_id);
            $this->log_cache_event('cache_invalidate', [
                'owner_id' => (int) $owner_id,
                'order_id' => (int) $order_id,
                'order_status' => $current_status
            ]);
        }
    }

    public function invalidate_cache_for_order_id($order_id)
    {
        if (!$order_id) {
            return;
        }

        $this->invalidate_cache_for_order($order_id, null, null, null);
    }

    public function invalidate_cache_for_order_post($post_id, $post, $update)
    {
        if ($post && $post->post_type === 'shop_order') {
            $this->invalidate_cache_for_order($post_id, null, null, null);
        }
    }

    private function get_owner_ids_for_order($order)
    {
        $owner_ids = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }
            $product_id = $product->get_id();
            $owner_ids = array_merge($owner_ids, $this->get_owner_ids_for_product($product_id));
        }

        return array_values(array_unique(array_filter(array_map('intval', $owner_ids))));
    }

    private function get_owner_ids_for_product($product_id)
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return [];
        }

        return get_posts([
            'post_type' => 'propietario',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => 'owner_properties',
                    'value' => '"' . $product_id . '"',
                    'compare' => 'LIKE'
                ]
            ]
        ]);
    }

    /**
     * Añadir metabox de ingresos
     */
    public function add_revenue_metabox()
    {
        add_meta_box(
            'owner_revenue_metabox',
            '💰 Resumen de Ingresos',
            [$this, 'render_revenue_metabox'],
            'propietario',
            'side',
            'high'
        );
    }

    /**
     * Renderizar metabox de ingresos
     */
    public function render_revenue_metabox($post)
    {
        $stats = $this->calculate_owner_revenue($post->ID);
        $effective_rate = $stats['total'] > 0 ? ($stats['commission'] / $stats['total']) * 100 : 0;

        ?>
        <div style="padding: 10px 0;">
            <div style="margin-bottom: 15px; padding: 10px; background: #f0f6fb; border-radius: 4px;">
                <strong style="display: block; font-size: 11px; color: #666; margin-bottom: 5px;">INGRESOS BRUTOS</strong>
                <span style="font-size: 24px; font-weight: bold; color: #2c99e2;">
                    <?php echo wc_price($stats['total']); ?>
                </span>
            </div>

            <?php if ($stats['commission'] > 0): ?>
                <div style="margin-bottom: 15px; padding: 10px; background: #fff3cd; border-radius: 4px;">
                    <strong style="display: block; font-size: 11px; color: #666; margin-bottom: 5px;">COMISIÓN
                        <?php if ($effective_rate > 0): ?>(<?php echo number_format($effective_rate, 1); ?>% ef.)<?php endif; ?>
                    </strong>
                    <span style="font-size: 18px; font-weight: bold; color: #ff9800;">-
                        <?php echo wc_price($stats['commission']); ?>
                    </span>
                </div>

                <div style="margin-bottom: 15px; padding: 10px; background: #d4edda; border-radius: 4px;">
                    <strong style="display: block; font-size: 11px; color: #666; margin-bottom: 5px;">NETO A PAGAR</strong>
                    <span style="font-size: 24px; font-weight: bold; color: #28a745;">
                        <?php echo wc_price($stats['net']); ?>
                    </span>
                </div>
            <?php endif; ?>

            <div style="text-align: center; padding: 10px; border-top: 1px solid #ddd; margin-top: 15px;">
                <p style="margin: 0; font-size: 13px; color: #666;">
                    <strong>
                        <?php echo $stats['count']; ?>
                    </strong> reservas completadas
                </p>
            </div>

            <?php if (!empty($stats['properties'])): ?>
                <div style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                    <strong style="display: block; margin-bottom: 10px; font-size: 12px;">Desglose por Propiedad:</strong>
                    <?php foreach ($stats['properties'] as $prop_data): ?>
                        <div style="margin-bottom: 8px; padding: 8px; background: #f9f9f9; border-radius: 3px; font-size: 11px;">
                            <div><strong>
                                    <?php echo $prop_data['name']; ?>
                                </strong></div>
                            <div style="color: #666;">
                                <?php echo wc_price($prop_data['revenue']); ?>
                                •
                                <?php echo $prop_data['bookings']; ?> reserva(s)
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Añadir columna de ingresos en listado
     */
    public function add_revenue_column($columns)
    {
        // Insertar antes de la fecha
        $new_columns = [];
        foreach ($columns as $key => $value) {
            if ($key === 'date') {
                $new_columns['revenue'] = '💰 Ingresos';
            }
            $new_columns[$key] = $value;
        }
        return $new_columns;
    }

    /**
     * Poblar columna de ingresos
     */
    public function populate_revenue_column($column, $post_id)
    {
        if ($column === 'revenue') {
            $stats = $this->calculate_owner_revenue($post_id);
            echo '<strong style="color: #2c99e2;">' . wc_price($stats['total']) . '</strong><br>';
            echo '<small style="color: #666;">' . $stats['count'] . ' reservas</small>';
        }
    }

    /**
     * Shortcode para dashboard del propietario
     */
    public function render_owner_dashboard($atts)
    {
        // Este shortcode se puede usar si creas un área de propietarios en el frontend
        $atts = shortcode_atts(['owner_id' => get_current_user_id()], $atts);

        // Lógica futura para mostrar dashboard completo
        return '<p>Dashboard de propietario en desarrollo...</p>';
    }
}

Alquipress_Owner_Revenue::get_instance();