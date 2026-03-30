<?php
/**
 * Búsqueda live en sidebar: AJAX endpoint y lógica compartida.
 */
if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Live_Search
{
    public function __construct()
    {
        add_action('wp_ajax_alquipress_live_search', [$this, 'ajax_search']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets($page)
    {
        if ($page === '' || strpos($page, 'alquipress-') !== 0) {
            return;
        }

        wp_enqueue_script(
            'alquipress-live-search',
            ALQUIPRESS_URL . 'includes/admin/assets/live-search.js',
            ['jquery'],
            ALQUIPRESS_VERSION,
            true
        );
        wp_localize_script('alquipress-live-search', 'alquipressLiveSearch', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alquipress_live_search'),
            'searchAllUrl' => admin_url('admin.php?page=alquipress-search'),
            'i18n' => [
                'properties' => __('Propiedades', 'alquipress'),
                'bookings' => __('Reservas', 'alquipress'),
                'clients' => __('Clientes', 'alquipress'),
                'noResults' => __('Sin resultados', 'alquipress'),
                'searchAll' => __('Buscar en todo', 'alquipress'),
            ],
        ]);
    }

    public function ajax_search()
    {
        check_ajax_referer('alquipress_live_search', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }

        $query = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $query = trim($query);
        if (strlen($query) < 2) {
            wp_send_json_success(['properties' => [], 'bookings' => [], 'clients' => []]);
        }

        $props = [];
        $props_raw = get_posts([
            'post_type' => 'product',
            'post_status' => ['publish', 'draft'],
            's' => $query,
            'posts_per_page' => 5,
        ]);
        foreach ($props_raw as $p) {
            $props[] = [
                'title' => $p->post_title,
                'url' => admin_url('admin.php?page=alquipress-edit-property&post_id=' . $p->ID),
            ];
        }

        $bookings = [];
        if (function_exists('wc_get_orders')) {
            $order_ids = get_posts([
                'post_type' => 'shop_order',
                'post_status' => 'any',
                's' => $query,
                'posts_per_page' => 5,
                'fields' => 'ids',
            ]);
            foreach ($order_ids as $oid) {
                $o = wc_get_order($oid);
                if ($o) {
                    $bookings[] = [
                        'title' => '#' . $o->get_id() . ' – ' . trim($o->get_billing_first_name() . ' ' . $o->get_billing_last_name()) . ' (' . wp_strip_all_tags(wc_price($o->get_total())) . ')',
                        'url' => $o->get_edit_order_url(),
                    ];
                }
            }
        }

        $clients = [];
        $user_query = new WP_User_Query([
            'role' => 'customer',
            'search' => '*' . $query . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number' => 5,
        ]);
        foreach ($user_query->get_results() as $u) {
            $clients[] = [
                'title' => ($u->display_name ?: $u->user_login) . ' (' . $u->user_email . ')',
                'url' => admin_url('users.php?page=alquipress-guest-profile&user_id=' . $u->ID),
            ];
        }

        wp_send_json_success([
            'properties' => $props,
            'bookings' => $bookings,
            'clients' => $clients,
        ]);
    }
}
