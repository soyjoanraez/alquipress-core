<?php
/**
 * Módulo: Contabilidad automática por propiedad y propietario
 * Registro automático de movimientos (ingresos, comisión, limpieza, lavandería)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Accounting
{
    const CPT = 'accounting_entry';
    const META_ENTRY_TYPE = '_entry_type';
    const META_AMOUNT = '_amount';
    const META_PROPERTY_ID = '_property_id';
    const META_OWNER_ID = '_owner_id';
    const META_ORDER_ID = '_order_id';
    const META_ENTRY_DATE = '_entry_date';

    const TYPE_INGRESO = 'ingreso';
    const TYPE_COMISION = 'comision';
    const TYPE_LIMPIEZA = 'limpieza';
    const TYPE_LAVANDERIA = 'lavanderia';

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
        add_action('init', [$this, 'register_cpt']);
        add_action('woocommerce_order_status_changed', [$this, 'on_order_status_changed'], 20, 4);
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
    }

    public function register_cpt()
    {
        register_post_type(self::CPT, [
            'labels' => [
                'name' => __('Entradas contables', 'alquipress'),
                'singular_name' => __('Entrada contable', 'alquipress'),
                'menu_name' => __('Contabilidad', 'alquipress'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'capability_type' => 'manage_options',
            'supports' => ['title'],
        ]);
    }

    public function maybe_render_section($page)
    {
        if ($page === 'alquipress-accounting') {
            $this->render_accounting_page();
        }
    }

    /**
     * Crear entradas contables cuando un pedido alcanza un estado billable
     */
    public function on_order_status_changed($order_id, $old_status, $new_status, $order)
    {
        $billable = ['completed', 'processing', 'deposito-ok', 'in-progress'];
        if (!in_array($new_status, $billable, true)) {
            return;
        }

        if (!$order || !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }
        if (!$order) {
            return;
        }

        if ($this->order_has_entries($order_id)) {
            return;
        }

        $product_id = (int) $order->get_meta('_booking_product_id');
        if (!$product_id) {
            foreach ($order->get_items() as $item) {
                if (is_callable([$item, 'get_product'])) {
                    $product = $item->get_product();
                    if ($product && $product->get_type() === 'booking') {
                        $product_id = $product->get_id();
                        break;
                    }
                }
            }
        }

        if (!$product_id) {
            return;
        }

        $owner_ids = $this->get_owner_ids_for_product($product_id);
        $owner_id = !empty($owner_ids) ? (int) $owner_ids[0] : 0;

        $total = (float) $order->get_meta('_apm_booking_total');
        if ($total <= 0) {
            $total = (float) $order->get_total();
        }

        $cleaning_fee = floatval(get_post_meta($product_id, '_cleaning_fee', true)) ?: 0;
        $laundry_fee = floatval(get_post_meta($product_id, '_laundry_fee', true)) ?: 0;

        $prop_rate = get_post_meta($product_id, 'property_commission_rate', true);
        if ($prop_rate === '' || $prop_rate === false) {
            $prop_rate = $owner_id ? get_field('owner_commission_rate', $owner_id) : 0;
        }
        $commission = $prop_rate ? ($total * floatval($prop_rate)) / 100 : 0;

        $entry_date = $order->get_date_created() ? $order->get_date_created()->format('Y-m-d') : current_time('Y-m-d');

        $this->create_entry(self::TYPE_INGRESO, $total, $product_id, $owner_id, $order_id, $entry_date);
        if ($commission > 0) {
            $this->create_entry(self::TYPE_COMISION, $commission, $product_id, $owner_id, $order_id, $entry_date);
        }
        if ($cleaning_fee > 0) {
            $this->create_entry(self::TYPE_LIMPIEZA, $cleaning_fee, $product_id, $owner_id, $order_id, $entry_date);
        }
        if ($laundry_fee > 0) {
            $this->create_entry(self::TYPE_LAVANDERIA, $laundry_fee, $product_id, $owner_id, $order_id, $entry_date);
        }
    }

    private function order_has_entries($order_id)
    {
        $existing = get_posts([
            'post_type' => self::CPT,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_query' => [
                ['key' => self::META_ORDER_ID, 'value' => $order_id, 'compare' => '=']
            ],
            'fields' => 'ids',
        ]);
        return !empty($existing);
    }

    private function create_entry($type, $amount, $property_id, $owner_id, $order_id, $entry_date)
    {
        $labels = [
            self::TYPE_INGRESO => __('Ingreso', 'alquipress'),
            self::TYPE_COMISION => __('Comisión', 'alquipress'),
            self::TYPE_LIMPIEZA => __('Limpieza', 'alquipress'),
            self::TYPE_LAVANDERIA => __('Lavandería', 'alquipress'),
        ];
        $title = sprintf('%s - #%d - %s', $labels[$type], $order_id, get_the_title($property_id) ?: '');

        $post_id = wp_insert_post([
            'post_type' => self::CPT,
            'post_title' => $title,
            'post_status' => 'publish',
            'post_author' => 1,
        ], true);

        if (is_wp_error($post_id)) {
            return;
        }

        update_post_meta($post_id, self::META_ENTRY_TYPE, $type);
        update_post_meta($post_id, self::META_AMOUNT, $amount);
        update_post_meta($post_id, self::META_PROPERTY_ID, $property_id);
        update_post_meta($post_id, self::META_OWNER_ID, $owner_id);
        update_post_meta($post_id, self::META_ORDER_ID, $order_id);
        update_post_meta($post_id, self::META_ENTRY_DATE, $entry_date);
    }

    private function get_owner_ids_for_product($product_id)
    {
        return get_posts([
            'post_type' => 'propietario',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => 'owner_properties',
                    'value' => '"' . (int) $product_id . '"',
                    'compare' => 'LIKE'
                ]
            ]
        ]);
    }

    /**
     * Obtener resumen por propiedad, propietario o periodo
     */
    public function get_summary($group_by = 'property', $start_date = null, $end_date = null)
    {
        $args = [
            'post_type' => self::CPT,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        if ($start_date && $end_date) {
            $args['meta_query'] = [
                [
                    'key' => self::META_ENTRY_DATE,
                    'value' => [$start_date, $end_date],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ],
            ];
        }

        $ids = get_posts($args);
        $rows = [];
        foreach ($ids as $pid) {
            $type = get_post_meta($pid, self::META_ENTRY_TYPE, true);
            $amount = (float) get_post_meta($pid, self::META_AMOUNT, true);
            $prop_id = (int) get_post_meta($pid, self::META_PROPERTY_ID, true);
            $owner_id = (int) get_post_meta($pid, self::META_OWNER_ID, true);
            $date = get_post_meta($pid, self::META_ENTRY_DATE, true);

            $key = $group_by === 'property' ? $prop_id : ($group_by === 'owner' ? $owner_id : $date);
            if (!isset($rows[$key])) {
                $rows[$key] = ['ingreso' => 0, 'comision' => 0, 'limpieza' => 0, 'lavanderia' => 0];
            }
            if (isset($rows[$key][$type])) {
                $rows[$key][$type] += $amount;
            }
        }
        return $rows;
    }

    public function render_accounting_page()
    {
        $start = isset($_GET['start']) ? sanitize_text_field(wp_unslash($_GET['start'])) : date('Y-m-01');
        $end = isset($_GET['end']) ? sanitize_text_field(wp_unslash($_GET['end'])) : date('Y-m-d');
        $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'property';

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';

        $by_property = $this->get_summary('property', $start, $end);
        $by_owner = $this->get_summary('owner', $start, $end);
        $by_period = $this->get_summary('period', $start, $end);
        ?>
        <div class="wrap alquipress-accounting-page ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('accounting'); ?>
                <main class="ap-owners-main">
                    <header class="ap-header">
                        <h1 class="ap-header-title"><?php esc_html_e('Contabilidad', 'alquipress'); ?></h1>
                        <p class="ap-header-subtitle"><?php esc_html_e('Resumen de movimientos por propiedad, propietario y periodo', 'alquipress'); ?></p>
                    </header>

                    <form method="get" class="ap-accounting-filters" style="margin-bottom: 24px; display: flex; gap: 12px; align-items: center;">
                        <input type="hidden" name="page" value="alquipress-accounting">
                        <label>
                            <?php esc_html_e('Desde', 'alquipress'); ?>
                            <input type="date" name="start" value="<?php echo esc_attr($start); ?>">
                        </label>
                        <label>
                            <?php esc_html_e('Hasta', 'alquipress'); ?>
                            <input type="date" name="end" value="<?php echo esc_attr($end); ?>">
                        </label>
                        <label>
                            <?php esc_html_e('Ver', 'alquipress'); ?>
                            <select name="view">
                                <option value="property" <?php selected($view, 'property'); ?>><?php esc_html_e('Por propiedad', 'alquipress'); ?></option>
                                <option value="owner" <?php selected($view, 'owner'); ?>><?php esc_html_e('Por propietario', 'alquipress'); ?></option>
                            </select>
                        </label>
                        <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', 'alquipress'); ?></button>
                    </form>

                    <?php if ($view === 'property') : ?>
                    <div class="ap-accounting-table-wrap">
                        <table class="ap-reports-perf-table widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Propiedad', 'alquipress'); ?></th>
                                    <th class="ap-reports-th-num"><?php esc_html_e('Ingresos', 'alquipress'); ?></th>
                                    <th class="ap-reports-th-num"><?php esc_html_e('Comisión', 'alquipress'); ?></th>
                                    <th class="ap-reports-th-num"><?php esc_html_e('Limpieza', 'alquipress'); ?></th>
                                    <th class="ap-reports-th-num"><?php esc_html_e('Lavandería', 'alquipress'); ?></th>
                                    <th class="ap-reports-th-num"><?php esc_html_e('Neto', 'alquipress'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($by_property as $prop_id => $data) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($prop_id ? get_the_title($prop_id) : '—'); ?></strong></td>
                                    <td><?php echo wc_price($data['ingreso']); ?></td>
                                    <td><?php echo wc_price($data['comision']); ?></td>
                                    <td><?php echo wc_price($data['limpieza']); ?></td>
                                    <td><?php echo wc_price($data['lavanderia']); ?></td>
                                    <td><strong><?php echo wc_price($data['ingreso'] - $data['comision']); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($by_property)) : ?>
                                <tr><td colspan="6"><?php esc_html_e('No hay movimientos en el periodo seleccionado.', 'alquipress'); ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else : ?>
                    <div class="ap-accounting-table-wrap">
                        <table class="ap-reports-perf-table widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Propietario', 'alquipress'); ?></th>
                                    <th class="ap-reports-th-num"><?php esc_html_e('Ingresos', 'alquipress'); ?></th>
                                    <th class="ap-reports-th-num"><?php esc_html_e('Comisión', 'alquipress'); ?></th>
                                    <th class="ap-reports-th-num"><?php esc_html_e('Limpieza', 'alquipress'); ?></th>
                                    <th class="ap-reports-th-num"><?php esc_html_e('Lavandería', 'alquipress'); ?></th>
                                    <th class="ap-reports-th-num"><?php esc_html_e('Neto', 'alquipress'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($by_owner as $owner_id => $data) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($owner_id ? get_the_title($owner_id) : '—'); ?></strong></td>
                                    <td><?php echo wc_price($data['ingreso']); ?></td>
                                    <td><?php echo wc_price($data['comision']); ?></td>
                                    <td><?php echo wc_price($data['limpieza']); ?></td>
                                    <td><?php echo wc_price($data['lavanderia']); ?></td>
                                    <td><strong><?php echo wc_price($data['ingreso'] - $data['comision']); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($by_owner)) : ?>
                                <tr><td colspan="6"><?php esc_html_e('No hay movimientos en el periodo seleccionado.', 'alquipress'); ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
        <?php
    }
}

Alquipress_Accounting::get_instance();
