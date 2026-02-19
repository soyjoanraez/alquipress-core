<?php
/**
 * Módulo: Sincronización iCal (export/import) para Airbnb, Booking.com, etc.
 *
 * @package Alquipress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Ical_Sync {

    const CRON_HOOK = 'alquipress_ical_sync_all';
    const META_FEEDS = '_alquipress_ical_feeds';
    const META_KEY = '_alquipress_ical_key';
    const META_BLOCKS = '_alquipress_manual_blocks';

    public function __construct() {
        add_action('init', [$this, 'export_endpoint']);
        add_action('init', [$this, 'maybe_schedule_cron'], 99);
        add_filter('cron_schedules', [$this, 'add_cron_interval']);
        add_action(self::CRON_HOOK, [$this, 'run_sync']);
        add_action('add_meta_boxes', [$this, 'add_metabox']);
        add_action('save_post_product', [$this, 'save_metabox']);
        add_action('admin_menu', [$this, 'add_dashboard_page']);
        add_action('wp_ajax_alquipress_ical_sync_now', [$this, 'ajax_sync_now']);
    }

    public static function get_ical_key($product_id) {
        $key = get_post_meta($product_id, self::META_KEY, true);
        if (!$key) {
            $key = wp_generate_password(32, false);
            update_post_meta($product_id, self::META_KEY, $key);
        }
        return $key;
    }

    public static function get_export_url($product_id) {
        $key = self::get_ical_key($product_id);
        return add_query_arg([
            'alquipress_ical' => 'export',
            'product_id' => (int) $product_id,
            'key' => $key,
        ], home_url('/'));
    }

    public function export_endpoint() {
        if (!isset($_GET['alquipress_ical']) || $_GET['alquipress_ical'] !== 'export') {
            return;
        }
        $product_id = (int) ($_GET['product_id'] ?? 0);
        $key = sanitize_text_field($_GET['key'] ?? '');
        $stored = get_post_meta($product_id, self::META_KEY, true);
        if (!$product_id || !$key || $key !== $stored) {
            status_header(403);
            exit('Acceso no autorizado.');
        }
        $name = get_the_title($product_id);
        $bookings = $this->get_bookings_for_product($product_id);
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//ALQUIPRESS//" . sanitize_title($name) . "//ES\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\nX-WR-CALNAME:" . $this->ical_escape($name) . "\r\nX-WR-TIMEZONE:Europe/Madrid\r\n";
        foreach ($bookings as $booking) {
            $uid = 'booking-' . $booking->get_id() . '@alquipress';
            $start = date('Ymd', $booking->get_start());
            $end = date('Ymd', $booking->get_end());
            $created = $booking->get_date_created() ? date('Ymd\THis\Z', strtotime($booking->get_date_created())) : date('Ymd\THis\Z');
            $summary = 'Reservado — ' . $name;
            $ical .= "BEGIN:VEVENT\r\nUID:{$uid}\r\nDTSTART;VALUE=DATE:{$start}\r\nDTEND;VALUE=DATE:{$end}\r\nDTSTAMP:{$created}\r\nSUMMARY:{$summary}\r\nSTATUS:CONFIRMED\r\nTRANSP:OPAQUE\r\nEND:VEVENT\r\n";
        }
        $blocks = get_post_meta($product_id, self::META_BLOCKS, true) ?: [];
        foreach ($blocks as $idx => $block) {
            if (empty($block['start']) || empty($block['end'])) continue;
            $ical .= "BEGIN:VEVENT\r\nUID:block-{$product_id}-{$idx}@alquipress\r\nDTSTART;VALUE=DATE:" . date('Ymd', strtotime($block['start'])) . "\r\nDTEND;VALUE=DATE:" . date('Ymd', strtotime($block['end'])) . "\r\nSUMMARY:Bloqueado — Uso personal\r\nSTATUS:CONFIRMED\r\nEND:VEVENT\r\n";
        }
        $ical .= "END:VCALENDAR\r\n";
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . sanitize_title($name) . '.ics"');
        header('Cache-Control: no-cache, must-revalidate');
        echo $ical;
        exit;
    }

    private function ical_escape($s) {
        return str_replace(["\r", "\n", ',', ';'], ['', '\n', '\,', '\;'], $s);
    }

    private function get_bookings_for_product($product_id) {
        if (!class_exists('WC_Booking')) {
            return [];
        }
        if (class_exists('WC_Booking_Data_Store') && method_exists('WC_Booking_Data_Store', 'get_bookings_for_objects')) {
            return WC_Booking_Data_Store::get_bookings_for_objects([$product_id], [
                'start_date' => time(),
                'end_date' => strtotime('+12 months'),
                'status' => ['confirmed', 'paid', 'complete'],
            ]);
        }
        global $wpdb;
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_booking_product_id' AND pm.meta_value = %s WHERE p.post_type = 'wc_booking' AND p.post_status IN ('confirmed','paid','complete')",
            $product_id
        ));
        $out = [];
        foreach ((array) $ids as $id) {
            $out[] = new WC_Booking($id);
        }
        return $out;
    }

    public function add_cron_interval($schedules) {
        $schedules['every_15_minutes'] = ['interval' => 900, 'display' => __('Cada 15 minutos', 'alquipress')];
        return $schedules;
    }

    public function maybe_schedule_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'every_15_minutes', self::CRON_HOOK);
        }
    }

    public function run_sync() {
        $products = get_posts([
            'post_type' => 'product',
            'numberposts' => -1,
            'post_status' => 'publish',
            'meta_query' => [['key' => self::META_FEEDS, 'compare' => 'EXISTS']],
        ]);
        $log = [];
        foreach ($products as $product) {
            $feeds = get_post_meta($product->ID, self::META_FEEDS, true) ?: [];
            foreach ($feeds as $idx => $feed) {
                if (empty($feed['active']) || empty($feed['url'])) continue;
                $result = $this->import_feed($product->ID, $feed);
                $feeds[$idx]['last_sync'] = current_time('mysql');
                $feeds[$idx]['last_status'] = $result['status'];
                $log[] = sprintf('[%s] %s — %s: %s', current_time('mysql'), get_the_title($product->ID), $feed['channel'] ?? 'feed', $result['status']);
            }
            update_post_meta($product->ID, self::META_FEEDS, $feeds);
        }
        update_option('alquipress_ical_last_sync_log', implode("\n", $log));
        update_option('alquipress_ical_last_sync_time', current_time('mysql'));
    }

    public function import_feed($product_id, $feed) {
        $result = ['status' => 'ok', 'blocked' => 0, 'skipped' => 0, 'error' => ''];
        $response = wp_remote_get($feed['url'], ['timeout' => 15, 'user-agent' => 'ALQUIPRESS/1.0 (iCal sync)']);
        if (is_wp_error($response)) {
            $result['status'] = 'error';
            $result['error'] = $response->get_error_message();
            return $result;
        }
        if (wp_remote_retrieve_response_code($response) !== 200) {
            $result['status'] = 'error';
            $result['error'] = 'HTTP ' . wp_remote_retrieve_response_code($response);
            return $result;
        }
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            $result['status'] = 'empty';
            return $result;
        }
        $events = $this->parse_ical_events($body);
        foreach ($events as $event) {
            $start_ts = $event['start'];
            $end_ts = $event['end'];
            $uid = $event['uid'] ?? '';
            if ($end_ts < time()) {
                $result['skipped']++;
                continue;
            }
            $existing = get_posts([
                'post_type' => 'wc_booking',
                'posts_per_page' => 1,
                'meta_query' => [
                    ['key' => '_alquipress_ical_uid', 'value' => $uid],
                    ['key' => '_booking_product_id', 'value' => $product_id],
                ],
            ]);
            if (!empty($existing)) {
                $result['skipped']++;
                continue;
            }
            if (class_exists('WC_Booking')) {
                try {
                    $booking = new WC_Booking([
                        'product_id' => $product_id,
                        'status' => 'confirmed',
                        'start_date' => $start_ts,
                        'end_date' => $end_ts,
                        'all_day' => true,
                    ]);
                    $id = method_exists($booking, 'create') ? $booking->create() : 0;
                    if ($id) {
                        update_post_meta($id, '_alquipress_ical_uid', $uid);
                        update_post_meta($id, '_alquipress_ical_channel', $feed['channel'] ?? 'external');
                        update_post_meta($id, '_booking_product_id', $product_id);
                        $result['blocked']++;
                    }
                } catch (\Exception $e) {
                    // skip this event
                }
            }
        }
        return $result;
    }

    private function parse_ical_events($content) {
        $events = [];
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $in_event = false;
        $current = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === 'BEGIN:VEVENT') {
                $in_event = true;
                $current = [];
                continue;
            }
            if ($line === 'END:VEVENT') {
                $in_event = false;
                if (!empty($current['start']) && !empty($current['end'])) {
                    $events[] = $current;
                }
                continue;
            }
            if (!$in_event) continue;
            if (preg_match('/^DTSTART(?:;.*)?:(.+)$/i', $line, $m)) {
                $current['start'] = $this->parse_ical_date(trim($m[1]));
            } elseif (preg_match('/^DTEND(?:;.*)?:(.+)$/i', $line, $m)) {
                $current['end'] = $this->parse_ical_date(trim($m[1]));
            } elseif (preg_match('/^UID:(.+)$/i', $line, $m)) {
                $current['uid'] = trim($m[1]);
            }
        }
        return $events;
    }

    private function parse_ical_date($value) {
        $value = trim($value);
        if (strlen($value) === 8 && ctype_digit($value)) {
            return mktime(0, 0, 0, (int) substr($value, 4, 2), (int) substr($value, 6, 2), (int) substr($value, 0, 4));
        }
        return strtotime($value) ?: 0;
    }

    public function add_metabox() {
        add_meta_box(
            'alquipress_ical_sync',
            __('Sincronización iCal', 'alquipress'),
            [$this, 'render_metabox'],
            'product',
            'normal',
            'high'
        );
    }

    public function render_metabox($post) {
        $product_id = $post->ID;
        $export_url = self::get_export_url($product_id);
        $feeds = get_post_meta($product_id, self::META_FEEDS, true) ?: [];
        wp_nonce_field('alquipress_ical_save', 'alquipress_ical_nonce');
        ?>
        <div class="alquipress-ical-box">
            <p><strong><?php esc_html_e('URL de exportación (para Airbnb, Booking.com):', 'alquipress'); ?></strong></p>
            <p><input type="text" value="<?php echo esc_url($export_url); ?>" readonly class="large-text" onclick="this.select()" style="max-width:100%;"></p>
            <p><button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($export_url); ?>')"><?php esc_html_e('Copiar', 'alquipress'); ?></button></p>
            <hr>
            <p><strong><?php esc_html_e('Feeds de importación (URLs .ics externas):', 'alquipress'); ?></strong></p>
            <p><?php esc_html_e('Última sync:', 'alquipress'); ?> <strong><?php echo esc_html(get_option('alquipress_ical_last_sync_time', __('Nunca', 'alquipress'))); ?></strong></p>
            <table class="widefat" style="max-width:800px;">
                <thead><tr><th><?php esc_html_e('Canal', 'alquipress'); ?></th><th><?php esc_html_e('URL', 'alquipress'); ?></th><th><?php esc_html_e('Activo', 'alquipress'); ?></th><th><?php esc_html_e('Estado', 'alquipress'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($feeds as $i => $f) : ?>
                <tr>
                    <td><input type="text" name="alquipress_ical_feeds[<?php echo (int) $i; ?>][channel]" value="<?php echo esc_attr($f['channel'] ?? ''); ?>" placeholder="airbnb"></td>
                    <td><input type="url" name="alquipress_ical_feeds[<?php echo (int) $i; ?>][url]" value="<?php echo esc_url($f['url'] ?? ''); ?>" class="large-text"></td>
                    <td><input type="checkbox" name="alquipress_ical_feeds[<?php echo (int) $i; ?>][active]" value="1" <?php checked(!empty($f['active'])); ?>></td>
                    <td><?php echo esc_html($f['last_status'] ?? '-'); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><input type="text" name="alquipress_ical_feeds[<?php echo count($feeds); ?>][channel]" placeholder="airbnb"></td>
                    <td><input type="url" name="alquipress_ical_feeds[<?php echo count($feeds); ?>][url]" class="large-text" placeholder="https://"></td>
                    <td><input type="checkbox" name="alquipress_ical_feeds[<?php echo count($feeds); ?>][active]" value="1"></td>
                    <td>—</td>
                </tr>
                </tbody>
            </table>
            <p><button type="button" class="button button-secondary alquipress-ical-sync-now" data-product-id="<?php echo (int) $product_id; ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('ical_sync_now')); ?>"><?php esc_html_e('Sincronizar ahora', 'alquipress'); ?></button> <span class="ical-sync-status"></span></p>
        </div>
        <?php
    }

    public function save_metabox($post_id) {
        if (!isset($_POST['alquipress_ical_nonce']) || !wp_verify_nonce($_POST['alquipress_ical_nonce'], 'alquipress_ical_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        $feeds = [];
        if (!empty($_POST['alquipress_ical_feeds']) && is_array($_POST['alquipress_ical_feeds'])) {
            foreach ($_POST['alquipress_ical_feeds'] as $f) {
                if (empty($f['url'])) continue;
                $feeds[] = [
                    'channel' => sanitize_key($f['channel'] ?? 'other'),
                    'url' => esc_url_raw($f['url']),
                    'active' => !empty($f['active']),
                    'last_sync' => '',
                    'last_status' => 'pendiente',
                ];
            }
        }
        update_post_meta($post_id, self::META_FEEDS, $feeds);
    }

    public function add_dashboard_page() {
        add_submenu_page(
            'woocommerce',
            __('Sync iCal', 'alquipress'),
            __('Sync iCal', 'alquipress'),
            'manage_woocommerce',
            'alquipress-ical-dashboard',
            [$this, 'render_dashboard']
        );
    }

    public function render_dashboard() {
        $last = get_option('alquipress_ical_last_sync_time', __('Nunca', 'alquipress'));
        $log = get_option('alquipress_ical_last_sync_log', '');
        echo '<div class="wrap"><h1>' . esc_html__('Sincronización iCal', 'alquipress') . '</h1>';
        echo '<p>' . esc_html__('Última sincronización:', 'alquipress') . ' <strong>' . esc_html($last) . '</strong></p>';
        if ($log) {
            echo '<h2>' . esc_html__('Log', 'alquipress') . '</h2><pre style="background:#f5f5f5;padding:12px;overflow:auto;max-height:300px;">' . esc_html($log) . '</pre>';
        }
        echo '</div>';
    }

    public function ajax_sync_now() {
        check_ajax_referer('ical_sync_now', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Sin permisos.']);
        }
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $feeds = get_post_meta($product_id, self::META_FEEDS, true) ?: [];
        $summary = [];
        foreach ($feeds as $idx => $feed) {
            if (empty($feed['active']) || empty($feed['url'])) continue;
            $result = $this->import_feed($product_id, $feed);
            $feeds[$idx]['last_sync'] = current_time('mysql');
            $feeds[$idx]['last_status'] = $result['status'];
            $summary[] = ($feed['channel'] ?? 'feed') . ': ' . $result['status'] . ' (' . $result['blocked'] . ' bloqueados)';
        }
        update_post_meta($product_id, self::META_FEEDS, $feeds);
        wp_send_json_success(['summary' => implode(' | ', $summary)]);
    }
}

new Alquipress_Ical_Sync();
