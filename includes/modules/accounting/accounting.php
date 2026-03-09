<?php
/**
 * Módulo: Contabilidad automática por propiedad y propietario
 * Registro automático de movimientos (ingresos, comisión, limpieza, lavandería)
 *
 * Los datos se almacenan en la tabla SQL wp_alquipress_accounting con índices
 * propios para consultas eficientes. La tabla CPT legacy (accounting_entry) ya
 * no se usa para nuevas entradas; los datos históricos se migran automáticamente.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Accounting
{
    const DB_VERSION_OPTION = 'alquipress_accounting_db_version';
    const DB_VERSION = '2.0';
    const TABLE_NAME = 'alquipress_accounting';

    // Mantenemos la constante CPT solo para la migración de datos legacy
    const CPT_LEGACY = 'accounting_entry';

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
        add_action('init', [$this, 'maybe_create_table']);
        add_action('woocommerce_order_status_changed', [$this, 'on_order_status_changed'], 20, 4);
        add_action('alquipress_render_section', [$this, 'maybe_render_section']);
    }

    /**
     * Crear tabla SQL si no existe o si la versión ha cambiado.
     */
    public function maybe_create_table()
    {
        if (get_option(self::DB_VERSION_OPTION) === self::DB_VERSION) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entry_type  VARCHAR(20)     NOT NULL,
            amount      DECIMAL(10,2)   NOT NULL DEFAULT 0,
            property_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            owner_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
            order_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
            entry_date  DATE            NOT NULL,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_order_id    (order_id),
            KEY idx_property_id (property_id),
            KEY idx_owner_id    (owner_id),
            KEY idx_entry_date  (entry_date),
            KEY idx_entry_type  (entry_type)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Migrar datos legacy del CPT si los hay
        $this->migrate_legacy_cpt_data();

        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
    }

    /**
     * Migrar entradas del CPT legacy a la tabla SQL.
     * Solo se ejecuta una vez (controlado por DB_VERSION_OPTION).
     */
    private function migrate_legacy_cpt_data()
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $legacy_ids = get_posts([
            'post_type'      => self::CPT_LEGACY,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        if (empty($legacy_ids)) {
            return;
        }

        foreach ($legacy_ids as $pid) {
            $entry_date_raw = get_post_meta($pid, '_entry_date', true);
            $entry_date = $entry_date_raw ? $entry_date_raw : current_time('Y-m-d');

            $wpdb->insert(
                $table,
                [
                    'entry_type'  => sanitize_key(get_post_meta($pid, '_entry_type', true)),
                    'amount'      => (float) get_post_meta($pid, '_amount', true),
                    'property_id' => (int) get_post_meta($pid, '_property_id', true),
                    'owner_id'    => (int) get_post_meta($pid, '_owner_id', true),
                    'order_id'    => (int) get_post_meta($pid, '_order_id', true),
                    'entry_date'  => $entry_date,
                ],
                ['%s', '%f', '%d', '%d', '%d', '%s']
            );
        }
    }

    public function maybe_render_section($page)
    {
        if ($page === 'alquipress-accounting') {
            $this->render_accounting_page();
        }
    }

    /**
     * Crear entradas contables cuando un pedido alcanza un estado billable.
     * Usa una transacción DB para garantizar que todas las entradas se crean o ninguna.
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
        $owner_id  = !empty($owner_ids) ? (int) $owner_ids[0] : 0;

        $total = (float) $order->get_meta('_apm_booking_total');
        if ($total <= 0) {
            $total = (float) $order->get_total();
        }

        $cleaning_fee = floatval(get_post_meta($product_id, '_cleaning_fee', true)) ?: 0;
        $laundry_fee  = floatval(get_post_meta($product_id, '_laundry_fee', true)) ?: 0;

        $prop_rate = get_post_meta($product_id, 'property_commission_rate', true);
        if ($prop_rate === '' || $prop_rate === false) {
            $prop_rate = $owner_id ? get_field('owner_commission_rate', $owner_id) : 0;
        }
        $commission = $prop_rate ? ($total * floatval($prop_rate)) / 100 : 0;

        $entry_date = $order->get_date_created()
            ? $order->get_date_created()->format('Y-m-d')
            : current_time('Y-m-d');

        // Transacción DB: todas las entradas se crean o ninguna
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $entries_to_create = [
            [self::TYPE_INGRESO, $total],
        ];
        if ($commission > 0) {
            $entries_to_create[] = [self::TYPE_COMISION, $commission];
        }
        if ($cleaning_fee > 0) {
            $entries_to_create[] = [self::TYPE_LIMPIEZA, $cleaning_fee];
        }
        if ($laundry_fee > 0) {
            $entries_to_create[] = [self::TYPE_LAVANDERIA, $laundry_fee];
        }

        $all_ok = true;
        foreach ($entries_to_create as [$type, $amount]) {
            $result = $this->create_entry($type, $amount, $product_id, $owner_id, $order_id, $entry_date);
            if (!$result) {
                $all_ok = false;
                break;
            }
        }

        if ($all_ok) {
            $wpdb->query('COMMIT');
        } else {
            $wpdb->query('ROLLBACK');
            if (class_exists('Alquipress_Logger')) {
                Alquipress_Logger::error(
                    sprintf('Error al crear entradas contables para el pedido #%d', $order_id),
                    Alquipress_Logger::CONTEXT_PAYMENT,
                    ['order_id' => $order_id, 'product_id' => $product_id]
                );
            }
        }
    }

    private function order_has_entries($order_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        return (bool) $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE order_id = %d LIMIT 1", $order_id)
        );
    }

    /**
     * Insertar una entrada contable directamente en la tabla SQL.
     *
     * @return int|false ID insertado o false en caso de error.
     */
    private function create_entry($type, $amount, $property_id, $owner_id, $order_id, $entry_date)
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->insert(
            $table,
            [
                'entry_type'  => $type,
                'amount'      => $amount,
                'property_id' => $property_id,
                'owner_id'    => $owner_id,
                'order_id'    => $order_id,
                'entry_date'  => $entry_date,
            ],
            ['%s', '%f', '%d', '%d', '%d', '%s']
        );

        return $result ? $wpdb->insert_id : false;
    }

    private function get_owner_ids_for_product($product_id)
    {
        return get_posts([
            'post_type'      => 'propietario',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'     => 'owner_properties',
                    'value'   => '"' . (int) $product_id . '"',
                    'compare' => 'LIKE',
                ],
            ],
        ]);
    }

    /**
     * Obtener resumen por propiedad, propietario o periodo.
     * Una sola query SQL con GROUP BY en lugar de N+1 queries.
     */
    public function get_summary($group_by = 'property', $start_date = null, $end_date = null)
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $group_col = match ($group_by) {
            'owner'  => 'owner_id',
            'period' => 'entry_date',
            default  => 'property_id',
        };

        $where = '';
        $params = [];

        if ($start_date && $end_date) {
            $where = 'WHERE entry_date BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }

        $sql = "SELECT
                    {$group_col} AS group_key,
                    entry_type,
                    SUM(amount) AS total
                FROM {$table}
                {$where}
                GROUP BY {$group_col}, entry_type";

        $results = $params
            ? $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A)
            : $wpdb->get_results($sql, ARRAY_A);

        $rows = [];
        foreach ((array) $results as $row) {
            $key  = $row['group_key'];
            $type = $row['entry_type'];
            if (!isset($rows[$key])) {
                $rows[$key] = [
                    self::TYPE_INGRESO    => 0,
                    self::TYPE_COMISION   => 0,
                    self::TYPE_LIMPIEZA   => 0,
                    self::TYPE_LAVANDERIA => 0,
                ];
            }
            if (isset($rows[$key][$type])) {
                $rows[$key][$type] += (float) $row['total'];
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
