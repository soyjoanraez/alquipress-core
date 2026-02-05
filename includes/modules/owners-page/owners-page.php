<?php
/**
 * Módulo: Página Propietarios (diseño Pencil)
 * Dashboard de propietarios: métricas, requieren atención, top propietarios
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Owners_Page
{
    public function __construct()
    {
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
        add_action('alquipress_enqueue_section_assets', [$this, 'enqueue_section_assets']);
    }

    public function maybe_render_section($page)
    {
        if ($page === 'alquipress-owners') {
            $this->render_page();
        }
    }

    public function enqueue_section_assets($page)
    {
        if ($page !== 'alquipress-owners') {
            return;
        }
        wp_enqueue_style(
            'alquipress-owners-page',
            ALQUIPRESS_URL . 'includes/modules/owners-page/assets/owners-page.css',
            [],
            ALQUIPRESS_VERSION
        );
    }

    private function get_icon_svg($name, $class = 'ap-owners-icon')
    {
        $icons = [
            'search' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>',
            'bell' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" /></svg>',
            'triangle-alert' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" /></svg>',
            'layout-dashboard' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="8" height="9" rx="1" /><rect x="13" y="3" width="8" height="5" rx="1" /><rect x="13" y="10" width="8" height="11" rx="1" /><rect x="3" y="14" width="8" height="7" rx="1" /></svg>',
            'building' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" /><path d="M7 7h3" /><path d="M14 7h3" /><path d="M7 12h3" /><path d="M14 12h3" /><path d="M7 17h3" /><path d="M14 17h3" /></svg>',
            'building-2' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18" /><path d="M6 12h12" /><path d="M10 6h4" /><path d="M10 16h4" /></svg>',
            'calendar' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>',
            'briefcase' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2" /><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" /><path d="M2 12h20" /></svg>',
            'wallet' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z" /><path d="M16 12h2" /></svg>',
            'bar-chart' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><line x1="6" y1="20" x2="6" y2="14" /><line x1="12" y1="20" x2="12" y2="8" /><line x1="18" y1="20" x2="18" y2="4" /></svg>',
            'settings' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V22a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-.4-1.1 1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H2a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.1-.4 1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V2a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 .4 1.1 1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.25.34.45.71.6 1.1.1.33.35.56.7.6H22a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.1.4c-.34.25-.56.6-.6.9Z" /></svg>',
            'user-plus' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="8.5" cy="7" r="4" /><line x1="20" y1="8" x2="20" y2="14" /><line x1="17" y1="11" x2="23" y2="11" /></svg>',
            'file-text' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /><line x1="10" y1="9" x2="8" y2="9" /></svg>',
            'credit-card' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" /><line x1="6" y1="15" x2="10" y2="15" /></svg>',
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

    /**
     * Normaliza un array de propiedades (ACF puede devolver IDs u objetos WP_Post) a IDs.
     *
     * @param array $properties Array de IDs o WP_Post.
     * @return int[] IDs únicos.
     */
    private function normalize_property_ids($properties)
    {
        if (!is_array($properties) || empty($properties)) {
            return [];
        }
        $ids = array_map(function ($p) {
            if (is_object($p) && isset($p->ID)) {
                return (int) $p->ID;
            }
            return (int) $p;
        }, $properties);
        return array_values(array_unique(array_filter($ids)));
    }

    private function get_owner_properties_summary($owner_id)
    {
        $properties = get_field('owner_properties', $owner_id);
        $property_ids = $this->normalize_property_ids($properties);
        if (empty($property_ids)) {
            return [
                'count' => 0,
                'names' => '',
            ];
        }
        $names = array_map('get_the_title', $property_ids);
        $names = array_filter($names);

        return [
            'count' => count($property_ids),
            'names' => implode(', ', array_slice($names, 0, 3)),
        ];
    }

    private function get_owner_profile_url($owner_id, $mode = 'view')
    {
        $url = admin_url('admin.php?page=alquipress-owner-profile&owner_id=' . (int) $owner_id);
        if ($mode === 'edit') {
            $url = add_query_arg('mode', 'edit', $url);
        }
        return $url;
    }

    /**
     * Obtener propietarios que requieren atención: sin IBAN, sin propiedades, sin contacto
     */
    private function get_requires_attention()
    {
        $query = new WP_Query([
            'post_type' => 'propietario',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
        $items = [];
        foreach ($query->posts as $post) {
            $iban = get_field('owner_iban', $post->ID);
            $iban_clean = is_string($iban) ? preg_replace('/\s+/', '', $iban) : '';
            $properties = get_field('owner_properties', $post->ID);
            $props_count = count($this->normalize_property_ids(is_array($properties) ? $properties : []));
            $email = get_post_meta($post->ID, 'owner_email_management', true);
            $phone = get_post_meta($post->ID, 'owner_phone', true);

            if (strlen($iban_clean) < 4) {
                $items[] = [
                    'type' => 'warning',
                    'color' => 'amber',
                    'title' => get_the_title($post->ID) . ' - ' . __('Sin IBAN registrado', 'alquipress'),
                    'subtitle' => $props_count ? sprintf(__('%d propiedad(es) asignada(s)', 'alquipress'), $props_count) : __('Sin propiedades asignadas', 'alquipress'),
                    'url' => $this->get_owner_profile_url($post->ID, 'edit'),
                    'btn_text' => __('Completar datos', 'alquipress'),
                ];
            }
            if ($props_count === 0 && strlen($iban_clean) >= 4) {
                $items[] = [
                    'type' => 'info',
                    'color' => 'blue',
                    'title' => get_the_title($post->ID) . ' - ' . __('Sin propiedades', 'alquipress'),
                    'subtitle' => __('Asigna al menos una propiedad al propietario', 'alquipress'),
                    'url' => $this->get_owner_profile_url($post->ID, 'edit'),
                    'btn_text' => __('Asignar propiedades', 'alquipress'),
                ];
            }
            if ((empty($email) || empty($phone)) && $props_count > 0) {
                $items[] = [
                    'type' => 'danger',
                    'color' => 'red',
                    'title' => get_the_title($post->ID) . ' - ' . __('Contacto incompleto', 'alquipress'),
                    'subtitle' => __('Faltan datos de email o teléfono', 'alquipress'),
                    'url' => $this->get_owner_profile_url($post->ID, 'edit'),
                    'btn_text' => __('Actualizar contacto', 'alquipress'),
                ];
            }
        }
        return array_slice($items, 0, 10);
    }

    /**
     * Top propietarios por ingresos (este mes)
     */
    private function get_top_owners($limit = 5)
    {
        $query = new WP_Query([
            'post_type' => 'propietario',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        $revenue_class = class_exists('Alquipress_Owner_Revenue') ? Alquipress_Owner_Revenue::get_instance() : null;
        $this_month_start = gmdate('Y-m-01');
        $this_month_end = gmdate('Y-m-t');

        $with_revenue = [];
        foreach ($query->posts as $post) {
            $total = 0;
            if ($revenue_class) {
                $data = $revenue_class->calculate_owner_revenue($post->ID, $this_month_start, $this_month_end);
                $total = isset($data['total']) ? (float) $data['total'] : 0;
            }
            $props = $this->get_owner_properties_summary($post->ID);
            $with_revenue[] = [
                'id' => $post->ID,
                'title' => get_the_title($post->ID),
                'revenue' => $total,
                'properties_count' => $props['count'],
                'properties_names' => $props['names'],
                'initials' => $this->get_initials(get_the_title($post->ID)),
                'url' => $this->get_owner_profile_url($post->ID),
            ];
        }
        usort($with_revenue, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });
        return array_slice($with_revenue, 0, $limit);
    }

    private function get_order_due_date($order)
    {
        if (!$order) {
            return null;
        }
        $date = $order->get_meta('_booking_checkout_date');
        if (!$date) {
            $date = $order->get_meta('_booking_checkin_date');
        }
        if (!$date) {
            return null;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $date, wp_timezone());
        if (!$dt) {
            return null;
        }
        return $dt;
    }

    private function get_owner_name_for_order($order)
    {
        if (!$order) {
            return '';
        }
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }
            $owner_id = get_field('propietario_asignado', $product->get_id());
            if (is_array($owner_id) && !empty($owner_id)) {
                $owner_id = $owner_id[0];
            }
            $owner_id = (int) $owner_id;
            if ($owner_id > 0) {
                return get_the_title($owner_id);
            }
        }
        return '';
    }

    private function get_upcoming_payments($limit = 3)
    {
        if (!function_exists('wc_get_orders')) {
            return [];
        }

        $orders = wc_get_orders([
            'status' => ['pending', 'on-hold', 'processing'],
            'limit' => 60,
            'orderby' => 'date',
            'order' => 'ASC',
        ]);

        $today = new DateTime('today', wp_timezone());
        $items = [];
        foreach ($orders as $order) {
            $due = $this->get_order_due_date($order);
            if (!$due) {
                continue;
            }
            if ($due < $today) {
                continue;
            }
            $items[] = [
                'order' => $order,
                'due' => $due,
            ];
        }

        usort($items, function ($a, $b) {
            return $a['due'] <=> $b['due'];
        });

        $items = array_slice($items, 0, $limit);
        $colors = ['is-blue', 'is-green', 'is-purple'];
        $out = [];
        foreach ($items as $idx => $item) {
            $order = $item['order'];
            $owner = $this->get_owner_name_for_order($order);
            $owner = $owner !== '' ? $owner : __('Sin propietario asignado', 'alquipress');
            $out[] = [
                'owner' => $owner,
                'due' => $item['due'],
                'amount' => function_exists('wc_price') ? wc_price($order->get_total()) : number_format_i18n($order->get_total(), 2) . ' €',
                'class' => $colors[$idx % count($colors)],
            ];
        }

        return $out;
    }

    private function count_orders_due_within($days)
    {
        if (!function_exists('wc_get_orders')) {
            return 0;
        }
        $orders = wc_get_orders([
            'status' => ['pending', 'on-hold', 'processing'],
            'limit' => 200,
            'orderby' => 'date',
            'order' => 'ASC',
        ]);

        $today = new DateTime('today', wp_timezone());
        $limit_date = (clone $today)->modify('+' . (int) $days . ' days');
        $count = 0;
        foreach ($orders as $order) {
            $due = $this->get_order_due_date($order);
            if (!$due) {
                continue;
            }
            if ($due < $today) {
                continue;
            }
            if ($due > $limit_date) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    private function get_metrics()
    {
        $query = new WP_Query([
            'post_type' => 'propietario',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        $total = $query->found_posts;

        $active = 0;
        $owners_without_properties = 0;
        $new_this_month = 0;
        $revenue_class = class_exists('Alquipress_Owner_Revenue') ? Alquipress_Owner_Revenue::get_instance() : null;
        $this_month_start = gmdate('Y-m-01');
        $this_month_end = gmdate('Y-m-t');
        $last_month_start = gmdate('Y-m-01', strtotime('first day of last month'));
        $last_month_end = gmdate('Y-m-t', strtotime('last day of last month'));
        $commission_total = 0;
        $commission_last_month = 0;
        $month_start_datetime = gmdate('Y-m-01 00:00:00');

        foreach ($query->posts as $post) {
            $owner_id = is_object($post) && isset($post->ID) ? (int) $post->ID : (int) $post;
            $properties = get_field('owner_properties', $owner_id);
            $property_ids = $this->normalize_property_ids(is_array($properties) ? $properties : []);
            if (!empty($property_ids)) {
                $active++;
            } else {
                $owners_without_properties++;
            }

            $created_at = get_post_field('post_date', $owner_id);
            if ($created_at && $created_at >= $month_start_datetime) {
                $new_this_month++;
            }

            if ($revenue_class) {
                $data = $revenue_class->calculate_owner_revenue($owner_id, $this_month_start, $this_month_end);
                $commission_total += isset($data['commission']) ? (float) $data['commission'] : 0;
                $last_data = $revenue_class->calculate_owner_revenue($owner_id, $last_month_start, $last_month_end);
                $commission_last_month += isset($last_data['commission']) ? (float) $last_data['commission'] : 0;
            }
        }

        $renewals_due = $this->count_orders_due_within(60);
        $active_percentage = $total > 0 ? round(($active / $total) * 100, 1) : 0;

        return [
            'total_owners' => $total,
            'active_owners' => $active,
            'active_percentage' => $active_percentage,
            'new_this_month' => $new_this_month,
            'commission_this_month' => $commission_total,
            'commission_last_month' => $commission_last_month,
            'renewals_due' => $renewals_due,
            'owners_without_properties' => $owners_without_properties,
        ];
    }

    public function render_page()
    {
        $metrics = $this->get_metrics();
        $attention = $this->get_requires_attention();
        $top = $this->get_top_owners(5);
        $payments = $this->get_upcoming_payments(3);
        $search_url = admin_url('edit.php?post_type=propietario');
        $add_url = admin_url('post-new.php?post_type=propietario');
        $search_query = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        if ($search_query) {
            $search_url = add_query_arg('s', $search_query, $search_url);
        }

        $commission_change = 0;
        if ($metrics['commission_last_month'] > 0) {
            $commission_change = (($metrics['commission_this_month'] - $metrics['commission_last_month']) / $metrics['commission_last_month']) * 100;
        }
        $commission_change_label = $commission_change >= 0 ? '+' . number_format_i18n($commission_change, 1) . '%' : number_format_i18n($commission_change, 1) . '%';
        $commission_change_class = $commission_change >= 0 ? 'is-positive' : 'is-negative';

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap alquipress-owners-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('owners'); ?>

                <main class="ap-owners-main">
                    <header class="ap-owners-header">
                        <div class="ap-owners-header-left">
                            <h1 class="ap-owners-title"><?php esc_html_e('Propietarios', 'alquipress'); ?></h1>
                            <p class="ap-owners-subtitle"><?php esc_html_e('Gestiona propietarios, comisiones y documentación', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-owners-header-right">
                            <form action="<?php echo esc_url(admin_url('edit.php')); ?>" method="get" class="ap-owners-search-form">
                                <input type="hidden" name="post_type" value="propietario" />
                                <span class="ap-owners-search-icon"><?php echo $this->get_icon_svg('search'); ?></span>
                                <input type="search" name="s" class="ap-owners-search-bar" placeholder="<?php esc_attr_e('Buscar propietarios...', 'alquipress'); ?>" value="<?php echo esc_attr($search_query); ?>" />
                            </form>
                            <a href="<?php echo esc_url($search_url); ?>" class="ap-owners-icon-btn ap-owners-bell" title="<?php esc_attr_e('Notificaciones', 'alquipress'); ?>" aria-label="<?php esc_attr_e('Notificaciones', 'alquipress'); ?>">
                                <?php echo $this->get_icon_svg('bell'); ?>
                            </a>
                        </div>
                    </header>

                    <div class="ap-owners-metrics-row">
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Total propietarios', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo (int) $metrics['total_owners']; ?></span>
                                <span class="ap-owners-metric-change is-positive">+<?php echo (int) $metrics['new_this_month']; ?> <?php esc_html_e('este mes', 'alquipress'); ?></span>
                            </div>
                        </div>
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Propietarios activos', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo (int) $metrics['active_owners']; ?></span>
                                <span class="ap-owners-metric-change is-neutral"><?php echo esc_html(number_format_i18n($metrics['active_percentage'], 1)); ?>% <?php esc_html_e('del total', 'alquipress'); ?></span>
                            </div>
                        </div>
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Comisión este mes', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo function_exists('wc_price') ? wc_price($metrics['commission_this_month']) : number_format_i18n($metrics['commission_this_month'], 2) . ' €'; ?></span>
                                <span class="ap-owners-metric-change <?php echo esc_attr($commission_change_class); ?>"><?php echo esc_html($commission_change_label); ?> <?php esc_html_e('vs mes anterior', 'alquipress'); ?></span>
                            </div>
                        </div>
                        <div class="ap-owners-metric-card">
                            <span class="ap-owners-metric-label"><?php esc_html_e('Renovaciones pendientes', 'alquipress'); ?></span>
                            <div class="ap-owners-metric-value-row">
                                <span class="ap-owners-metric-value"><?php echo (int) $metrics['renewals_due']; ?></span>
                                <span class="ap-owners-metric-change is-warning"><?php esc_html_e('Próx. 60 días', 'alquipress'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="ap-owners-content-row">
                        <div class="ap-owners-left-col">
                            <div class="ap-owners-requires-attention">
                                <div class="ap-owners-req-header">
                                    <div class="ap-owners-req-header-left">
                                        <span class="ap-owners-req-icon"><?php echo $this->get_icon_svg('triangle-alert'); ?></span>
                                        <h3 class="ap-owners-req-title"><?php esc_html_e('Requieren atención', 'alquipress'); ?></h3>
                                    </div>
                                    <span class="ap-owners-req-count"><?php echo count($attention); ?> <?php echo count($attention) === 1 ? __('item', 'alquipress') : __('items', 'alquipress'); ?></span>
                                </div>
                                <div class="ap-owners-req-items">
                                    <?php if (empty($attention)) : ?>
                                        <p class="ap-owners-req-empty"><?php esc_html_e('No hay propietarios que requieran atención.', 'alquipress'); ?></p>
                                    <?php else : ?>
                                        <?php foreach ($attention as $item) : ?>
                                            <div class="ap-owners-req-item ap-owners-req-item-<?php echo esc_attr($item['color']); ?>">
                                                <div class="ap-owners-req-item-left">
                                                    <span class="ap-owners-req-dot"></span>
                                                    <div class="ap-owners-req-item-info">
                                                        <span class="ap-owners-req-item-title"><?php echo esc_html($item['title']); ?></span>
                                                        <span class="ap-owners-req-item-sub"><?php echo esc_html($item['subtitle']); ?></span>
                                                    </div>
                                                </div>
                                                <?php if (!empty($item['url'])) : ?>
                                                    <a href="<?php echo esc_url($item['url']); ?>" class="ap-owners-req-btn ap-owners-req-btn-<?php echo esc_attr($item['color']); ?>"><?php echo esc_html($item['btn_text']); ?></a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="ap-owners-top-performing">
                                <div class="ap-owners-top-header">
                                    <h3 class="ap-owners-top-title"><?php esc_html_e('Propietarios con mejor rendimiento', 'alquipress'); ?></h3>
                                    <a href="<?php echo esc_url($search_url); ?>" class="ap-owners-top-view"><?php esc_html_e('Ver todos', 'alquipress'); ?></a>
                                </div>
                                <ul class="ap-owners-top-list">
                                    <?php if (empty($top)) : ?>
                                        <li class="ap-owners-top-empty"><?php esc_html_e('No hay propietarios con ingresos aún.', 'alquipress'); ?></li>
                                    <?php else : ?>
                                        <?php foreach ($top as $i => $owner) : ?>
                                            <li class="ap-owners-top-item">
                                                <span class="ap-owners-top-rank <?php echo $i === 0 ? 'is-top' : ''; ?>"><?php echo (int) ($i + 1); ?></span>
                                                <span class="ap-owners-top-avatar"><?php echo esc_html($owner['initials']); ?></span>
                                                <div class="ap-owners-top-info">
                                                    <a href="<?php echo esc_url($owner['url']); ?>" class="ap-owners-top-name"><?php echo esc_html($owner['title']); ?></a>
                                                    <?php if ($owner['properties_count'] > 0) : ?>
                                                        <span class="ap-owners-top-sub"><?php echo esc_html($owner['properties_count']); ?> <?php echo $owner['properties_count'] === 1 ? __('propiedad', 'alquipress') : __('propiedades', 'alquipress'); ?><?php echo $owner['properties_names'] ? ' - ' . esc_html($owner['properties_names']) : ''; ?></span>
                                                    <?php else : ?>
                                                        <span class="ap-owners-top-sub"><?php esc_html_e('Sin propiedades asignadas', 'alquipress'); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ap-owners-top-revenue">
                                                    <span class="ap-owners-top-amount"><?php echo function_exists('wc_price') ? wc_price($owner['revenue']) : number_format_i18n($owner['revenue'], 2) . ' €'; ?></span>
                                                    <span class="ap-owners-top-period"><?php esc_html_e('al mes', 'alquipress'); ?></span>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="ap-owners-right-col">
                            <div class="ap-owners-quick-actions">
                                <h3 class="ap-owners-section-title"><?php esc_html_e('Acciones rápidas', 'alquipress'); ?></h3>
                                <div class="ap-owners-actions-list">
                                    <a href="<?php echo esc_url($add_url); ?>" class="ap-owners-action-btn is-primary">
                                        <?php echo $this->get_icon_svg('user-plus'); ?>
                                        <?php esc_html_e('Añadir propietario', 'alquipress'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-reports')); ?>" class="ap-owners-action-btn">
                                        <?php echo $this->get_icon_svg('file-text'); ?>
                                        <?php esc_html_e('Generar extractos', 'alquipress'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order&post_status=wc-pending')); ?>" class="ap-owners-action-btn">
                                        <?php echo $this->get_icon_svg('credit-card'); ?>
                                        <?php esc_html_e('Procesar pagos', 'alquipress'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-reports')); ?>" class="ap-owners-action-btn">
                                        <?php echo $this->get_icon_svg('download'); ?>
                                        <?php esc_html_e('Exportar informe financiero', 'alquipress'); ?>
                                    </a>
                                </div>
                            </div>

                            <div class="ap-owners-contract-summary">
                                <h3 class="ap-owners-section-title"><?php esc_html_e('Resumen de contratos', 'alquipress'); ?></h3>
                                <div class="ap-owners-summary-list">
                                    <div class="ap-owners-summary-item">
                                        <span class="ap-owners-summary-label"><?php esc_html_e('Contratos activos', 'alquipress'); ?></span>
                                        <span class="ap-owners-summary-pill is-success"><?php echo (int) $metrics['active_owners']; ?></span>
                                    </div>
                                    <div class="ap-owners-summary-item">
                                        <span class="ap-owners-summary-label"><?php esc_html_e('Por expirar (<60 días)', 'alquipress'); ?></span>
                                        <span class="ap-owners-summary-pill is-warning"><?php echo (int) $metrics['renewals_due']; ?></span>
                                    </div>
                                    <div class="ap-owners-summary-item">
                                        <span class="ap-owners-summary-label"><?php esc_html_e('Pendientes de activación', 'alquipress'); ?></span>
                                        <span class="ap-owners-summary-pill is-info"><?php echo (int) $metrics['owners_without_properties']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="ap-owners-payments">
                                <div class="ap-owners-payments-header">
                                    <h3 class="ap-owners-section-title"><?php esc_html_e('Pagos próximos', 'alquipress'); ?></h3>
                                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order')); ?>" class="ap-owners-top-view"><?php esc_html_e('Ver todos', 'alquipress'); ?></a>
                                </div>
                                <div class="ap-owners-payments-list">
                                    <?php if (empty($payments)) : ?>
                                        <p class="ap-owners-req-empty"><?php esc_html_e('No hay pagos próximos.', 'alquipress'); ?></p>
                                    <?php else : ?>
                                        <?php foreach ($payments as $payment) : ?>
                                            <div class="ap-owners-payment-item <?php echo esc_attr($payment['class']); ?>">
                                                <div class="ap-owners-payment-info">
                                                    <span class="ap-owners-payment-name"><?php echo esc_html($payment['owner']); ?></span>
                                                    <span class="ap-owners-payment-date"><?php echo esc_html(sprintf(__('Vence: %s', 'alquipress'), date_i18n('j M Y', $payment['due']->getTimestamp()))); ?></span>
                                                </div>
                                                <span class="ap-owners-payment-amount"><?php echo wp_kses_post($payment['amount']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <?php
    }
}

new Alquipress_Owners_Page();
