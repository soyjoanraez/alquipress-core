<?php
/**
 * Módulo: Motor de reservas propio (Ap_Booking)
 *
 * Núcleo de dominio: tablas, objetos de reserva y servicios
 * de pricing y disponibilidad, integrados con WooCommerce.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Versión del esquema de base de datos del motor de reservas.
 * 1.1.0 — Añade columna `name` en ap_booking_pricing_rules.
 */
const AP_BOOKINGS_DB_VERSION = '1.1.0';

require_once __DIR__ . '/class-ap-booking-deposits.php';
require_once __DIR__ . '/class-ap-bookings-rest-api.php';
require_once __DIR__ . '/class-ap-booking-widget.php';

/**
 * Inicializar el módulo de reservas.
 */
function ap_bookings_init()
{
    ap_bookings_maybe_create_tables();
    Ap_Booking_Store::init_hooks();
    Ap_Booking_Deposit_Manager::init_hooks();
    Ap_Bookings_REST_API::init();
}
add_action('plugins_loaded', 'ap_bookings_init', 40);

/**
 * Guardar metadatos de producto relacionados con el motor de reservas.
 */
function ap_bookings_save_product_meta($post_id, $post = null, $update = true)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id)) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['post_type']) || $_POST['post_type'] !== 'product') {
        return;
    }

    $enabled = isset($_POST['ap_booking_enabled']) && $_POST['ap_booking_enabled'] === '1' ? '1' : '';
    if ($enabled === '1') {
        update_post_meta($post_id, 'ap_booking_enabled', '1');
    } else {
        delete_post_meta($post_id, 'ap_booking_enabled');
    }

    if (isset($_POST['ap_base_price'])) {
        $base_price_raw = wc_clean(wp_unslash($_POST['ap_base_price']));
        if ($base_price_raw === '' || !is_numeric($base_price_raw)) {
            delete_post_meta($post_id, 'ap_base_price');
        } else {
            $base_price = (float) $base_price_raw;
            update_post_meta($post_id, 'ap_base_price', $base_price);

            // Sincronizar también el precio base con el producto WooCommerce.
            update_post_meta($post_id, '_regular_price', $base_price);
            update_post_meta($post_id, '_price', $base_price);
        }
    }

    // ── Configuración de depósito por producto ──────────────────────────────
    $deposit_enabled = isset($_POST['ap_deposit_enabled']) && $_POST['ap_deposit_enabled'] === '1' ? '1' : '';
    if ($deposit_enabled === '1') {
        update_post_meta($post_id, 'ap_deposit_enabled', '1');
    } else {
        delete_post_meta($post_id, 'ap_deposit_enabled');
    }

    $deposit_type = isset($_POST['ap_deposit_type']) ? sanitize_key($_POST['ap_deposit_type']) : 'percent';
    update_post_meta($post_id, 'ap_deposit_type', in_array($deposit_type, ['percent', 'fixed'], true) ? $deposit_type : 'percent');

    foreach (['ap_deposit_percent', 'ap_deposit_fixed_amount', 'ap_deposit_balance_days_before', 'ap_security_deposit_amount'] as $meta_key) {
        if (isset($_POST[$meta_key])) {
            $val = wc_clean(wp_unslash($_POST[$meta_key]));
            if ($val === '' || !is_numeric($val)) {
                delete_post_meta($post_id, $meta_key);
            } else {
                update_post_meta($post_id, $meta_key, (float) $val);
            }
        }
    }
}
add_action('save_post_product', 'ap_bookings_save_product_meta', 20, 3);

/**
 * Crear/actualizar tablas necesarias para el motor de reservas.
 */
function ap_bookings_maybe_create_tables()
{
    global $wpdb;

    $installed = get_option('ap_bookings_db_version');
    $booking_table = $wpdb->prefix . 'ap_booking';
    $pricing_table = $wpdb->prefix . 'ap_booking_pricing_rules';
    $availability_table = $wpdb->prefix . 'ap_booking_availability_rules';
    $guests_table = $wpdb->prefix . 'ap_booking_guests';

    // Comprobar si las tablas existen realmente. Es posible que la opción de versión
    // se haya actualizado en algún momento sin que dbDelta llegara a crear las tablas.
    $booking_table_exists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $booking_table)) === $booking_table);

    if ($installed === AP_BOOKINGS_DB_VERSION && $booking_table_exists) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $pricing_table = $wpdb->prefix . 'ap_booking_pricing_rules';
    $availability_table = $wpdb->prefix . 'ap_booking_availability_rules';
    $guests_table = $wpdb->prefix . 'ap_booking_guests';

    $sql_booking = "CREATE TABLE {$booking_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id BIGINT(20) UNSIGNED NOT NULL,
        order_id BIGINT(20) UNSIGNED DEFAULT 0,
        customer_id BIGINT(20) UNSIGNED DEFAULT 0,
        checkin DATETIME NOT NULL,
        checkout DATETIME NOT NULL,
        guests INT(11) NOT NULL DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        total DECIMAL(19,4) NOT NULL DEFAULT 0,
        currency VARCHAR(10) NOT NULL DEFAULT 'EUR',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY product_dates (product_id, checkin, checkout),
        KEY order_id (order_id),
        KEY customer_id (customer_id),
        KEY status (status)
    ) {$charset_collate};";

    $sql_pricing = "CREATE TABLE {$pricing_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(120) NOT NULL DEFAULT '',
        date_from DATE NOT NULL,
        date_to DATE NOT NULL,
        dow_mask VARCHAR(7) DEFAULT NULL,
        min_nights INT(11) NOT NULL DEFAULT 1,
        max_nights INT(11) NOT NULL DEFAULT 365,
        base_price DECIMAL(19,4) NOT NULL DEFAULT 0,
        extra_guest_price DECIMAL(19,4) NOT NULL DEFAULT 0,
        weekend_multiplier DECIMAL(10,4) NOT NULL DEFAULT 1,
        priority INT(11) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY product_dates (product_id, date_from, date_to),
        KEY priority (priority)
    ) {$charset_collate};";

    $sql_availability = "CREATE TABLE {$availability_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id BIGINT(20) UNSIGNED NOT NULL,
        date_from DATE NOT NULL,
        date_to DATE NOT NULL,
        type VARCHAR(20) NOT NULL DEFAULT 'closed',
        note TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY product_dates (product_id, date_from, date_to),
        KEY type (type)
    ) {$charset_collate};";

    $sql_guests = "CREATE TABLE {$guests_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        booking_id BIGINT(20) UNSIGNED NOT NULL,
        first_name VARCHAR(80) NOT NULL,
        last_name VARCHAR(120) NOT NULL,
        document_type VARCHAR(20) NOT NULL DEFAULT 'dni',
        document_number VARCHAR(50) NOT NULL,
        birth_date DATE NOT NULL,
        nationality VARCHAR(3) DEFAULT '',
        is_main_guest TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY booking_id (booking_id),
        KEY document_number (document_number)
    ) {$charset_collate};";

    dbDelta($sql_booking);
    dbDelta($sql_pricing);
    dbDelta($sql_availability);
    dbDelta($sql_guests);

    update_option('ap_bookings_db_version', AP_BOOKINGS_DB_VERSION);
}

/**
 * Objeto de dominio: reserva.
 */
class Ap_Booking
{
    public int $id;
    public int $product_id;
    public int $order_id;
    public int $customer_id;
    public string $checkin;
    public string $checkout;
    public int $guests;
    public string $status;
    public float $total;
    public string $currency;
    public string $created_at;
    public string $updated_at;

    public static function from_row(array $row): self
    {
        $b = new self();
        $b->id          = (int) ($row['id'] ?? 0);
        $b->product_id  = (int) ($row['product_id'] ?? 0);
        $b->order_id    = (int) ($row['order_id'] ?? 0);
        $b->customer_id = (int) ($row['customer_id'] ?? 0);
        $b->checkin     = (string) ($row['checkin'] ?? '');
        $b->checkout    = (string) ($row['checkout'] ?? '');
        $b->guests      = (int) ($row['guests'] ?? 1);
        $b->status      = (string) ($row['status'] ?? 'pending');
        $b->total       = (float) ($row['total'] ?? 0);
        $b->currency    = (string) ($row['currency'] ?? 'EUR');
        $b->created_at  = (string) ($row['created_at'] ?? '');
        $b->updated_at  = (string) ($row['updated_at'] ?? '');
        return $b;
    }

    public function get_nights(): int
    {
        $start = strtotime($this->checkin);
        $end = strtotime($this->checkout);
        if (!$start || !$end || $end <= $start) {
            return 0;
        }
        return max(1, (int) ceil(($end - $start) / DAY_IN_SECONDS));
    }

    public function get_total(): float
    {
        return $this->total;
    }
}

/**
 * Almacén de reservas y hooks WooCommerce.
 */
class Ap_Booking_Store
{
    public static function init_hooks(): void
    {
        // Capturar datos de reserva al añadir al carrito y trasladarlos al pedido.
        add_filter('woocommerce_add_cart_item_data', [__CLASS__, 'capture_cart_item_booking_data'], 10, 3);
        add_action('woocommerce_checkout_create_order_line_item', [__CLASS__, 'copy_booking_meta_to_order_item'], 10, 4);

        // Crear reserva al crear pedido si existen metadatos de reserva en los items.
        add_action('woocommerce_checkout_order_created', [__CLASS__, 'handle_order_created'], 20, 1);
        add_action('woocommerce_order_status_changed', [__CLASS__, 'handle_order_status_changed'], 20, 4);
    }

    /**
     * Guardar datos de check-in/out y huéspedes en el item del carrito.
     */
    public static function capture_cart_item_booking_data(array $cart_item_data, int $product_id, int $variation_id): array
    {
        if (!get_post_meta($product_id, 'ap_booking_enabled', true)) {
            return $cart_item_data;
        }

        $checkin = isset($_POST['ap_checkin']) ? sanitize_text_field(wp_unslash($_POST['ap_checkin'])) : '';
        $checkout = isset($_POST['ap_checkout']) ? sanitize_text_field(wp_unslash($_POST['ap_checkout'])) : '';
        $guests = isset($_POST['ap_guests']) ? absint($_POST['ap_guests']) : 1;

        if ($checkin && $checkout) {
            $cart_item_data['ap_checkin'] = $checkin;
            $cart_item_data['ap_checkout'] = $checkout;
            $cart_item_data['ap_guests'] = max(1, $guests);
        }

        return $cart_item_data;
    }

    /**
     * Copiar metadatos de reserva del carrito al item del pedido.
     */
    public static function copy_booking_meta_to_order_item($item, $cart_item_key, $values, $order): void
    {
        if (!is_array($values)) {
            return;
        }
        foreach (['ap_checkin', 'ap_checkout', 'ap_guests'] as $key) {
            if (isset($values[$key])) {
                $item->add_meta_data($key, $values[$key], true);
            }
        }
    }

    public static function handle_order_created(WC_Order $order): void
    {
        /** @var WC_Order_Item_Product $item */
        foreach ($order->get_items() as $item) {
            if (!method_exists($item, 'get_product')) {
                continue;
            }
            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $product_id = $product->get_id();
            // Solo actuar si el producto tiene el flag de motor de reservas activado.
            if (!get_post_meta($product_id, 'ap_booking_enabled', true)) {
                continue;
            }

            $checkin = $item->get_meta('ap_checkin');
            $checkout = $item->get_meta('ap_checkout');
            $guests = (int) $item->get_meta('ap_guests');

            if (empty($checkin) || empty($checkout)) {
                continue;
            }

            $customer_id = $order->get_customer_id() ? (int) $order->get_customer_id() : 0;

            $price_breakdown = Ap_Booking_Pricing_Service::calculate_price($product_id, $checkin, $checkout, $guests ?: 1);
            $total = isset($price_breakdown['total']) ? (float) $price_breakdown['total'] : (float) $order->get_total();

            $booking = self::create_booking(
                $product_id,
                $checkin,
                $checkout,
                $guests ?: 1,
                [
                    'order_id' => $order->get_id(),
                    'customer_id' => $customer_id,
                    'status' => 'held',
                    'total' => $total,
                    'currency' => $order->get_currency(),
                ]
            );

            if ($booking) {
                $item->add_meta_data('ap_booking_id', $booking->id, true);
                $item->save();
            }
        }
    }

    public static function handle_order_status_changed($order_id, $old_status, $new_status, $order): void
    {
        if (!$order instanceof WC_Order) {
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }
        }

        $target_status = null;
        if (in_array($new_status, ['processing', 'completed'], true)) {
            $target_status = 'confirmed';
        } elseif (in_array($new_status, ['cancelled', 'refunded'], true)) {
            $target_status = 'cancelled';
        }

        if ($target_status === null) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $booking_id = (int) $item->get_meta('ap_booking_id');
            if ($booking_id > 0) {
                self::update_status($booking_id, $target_status);
            }
        }
    }

    public static function create_booking(int $product_id, string $checkin, string $checkout, int $guests, array $context = []): ?Ap_Booking
    {
        global $wpdb;

        $table = $wpdb->prefix . 'ap_booking';
        $order_id = isset($context['order_id']) ? (int) $context['order_id'] : 0;
        $customer_id = isset($context['customer_id']) ? (int) $context['customer_id'] : 0;
        $status = isset($context['status']) ? sanitize_key($context['status']) : 'pending';
        $total = isset($context['total']) ? (float) $context['total'] : 0.0;
        $currency = isset($context['currency']) ? sanitize_text_field($context['currency']) : 'EUR';

        $data = [
            'product_id' => $product_id,
            'order_id' => $order_id,
            'customer_id' => $customer_id,
            'checkin' => gmdate('Y-m-d H:i:s', strtotime($checkin)),
            'checkout' => gmdate('Y-m-d H:i:s', strtotime($checkout)),
            'guests' => max(1, $guests),
            'status' => $status,
            'total' => $total,
            'currency' => $currency,
        ];

        $inserted = $wpdb->insert(
            $table,
            $data,
            [
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%f',
                '%s',
            ]
        );

        if (!$inserted) {
            return null;
        }

        $data['id'] = (int) $wpdb->insert_id;
        return Ap_Booking::from_row($data);
    }

    /**
     * Obtener reservas de un producto entre dos fechas.
     */
    public static function get_bookings_for_product(int $product_id, int $from_ts, int $to_ts, array $statuses = []): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';

        $product_id = (int) $product_id;
        $from = gmdate('Y-m-d H:i:s', $from_ts);
        $to = gmdate('Y-m-d H:i:s', $to_ts);

        $where = $wpdb->prepare(
            'product_id = %d AND checkin < %s AND checkout > %s',
            $product_id,
            $to,
            $from
        );

        if (!empty($statuses)) {
            $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
            $statuses = array_map('sanitize_key', $statuses);
            $where .= ' AND status IN (' . $placeholders . ')';
            $params = array_merge([$product_id, $to, $from], $statuses);
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE product_id = %d AND checkin < %s AND checkout > %s AND status IN (" . $placeholders . ')',
                ...$params
            );
        } else {
            $sql = "SELECT * FROM {$table} WHERE {$where}";
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        $result = [];
        foreach ($rows as $row) {
            $result[] = Ap_Booking::from_row($row);
        }
        return $result;
    }

    public static function block_dates(int $product_id, string $from, string $to, string $reason = 'owner_block'): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking_availability_rules';
        $wpdb->insert(
            $table,
            [
                'product_id' => $product_id,
                'date_from' => gmdate('Y-m-d', strtotime($from)),
                'date_to' => gmdate('Y-m-d', strtotime($to)),
                'type' => sanitize_key($reason),
                'note' => '',
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }

    public static function update_status(int $booking_id, string $new_status): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';
        $wpdb->update(
            $table,
            ['status' => sanitize_key($new_status)],
            ['id' => $booking_id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Obtener todos los bloqueos de disponibilidad de un producto en un rango.
     */
    public static function get_blocks_for_product(int $product_id, ?string $from = null, ?string $to = null): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking_availability_rules';

        if ($from && $to) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE product_id = %d AND date_from <= %s AND date_to >= %s ORDER BY date_from ASC",
                    $product_id, $to, $from
                ),
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE product_id = %d ORDER BY date_from ASC",
                    $product_id
                ),
                ARRAY_A
            );
        }

        return $rows ?: [];
    }

    /**
     * Crear un bloqueo con nota opcional.
     */
    public static function create_block(int $product_id, string $from, string $to, string $type = 'owner_block', string $note = ''): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking_availability_rules';
        $wpdb->insert(
            $table,
            [
                'product_id' => $product_id,
                'date_from'  => gmdate('Y-m-d', strtotime($from)),
                'date_to'    => gmdate('Y-m-d', strtotime($to)),
                'type'       => sanitize_key($type),
                'note'       => sanitize_text_field($note),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
        return (int) $wpdb->insert_id;
    }

    /**
     * Eliminar un bloqueo por ID.
     */
    public static function delete_block(int $block_id, int $product_id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking_availability_rules';
        return (bool) $wpdb->delete(
            $table,
            ['id' => $block_id, 'product_id' => $product_id],
            ['%d', '%d']
        );
    }

    /**
     * Obtener todas las reservas de un producto (para el panel admin).
     *
     * @return Ap_Booking[]
     */
    public static function get_all_bookings_for_product(int $product_id, array $statuses = []): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';

        if (!empty($statuses)) {
            $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
            $statuses = array_map('sanitize_key', $statuses);
            $params = array_merge([$product_id], $statuses);
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE product_id = %d AND status IN ({$placeholders}) ORDER BY checkin ASC",
                ...$params
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE product_id = %d ORDER BY checkin ASC",
                $product_id
            );
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        return array_map([Ap_Booking::class, 'from_row'], $rows ?: []);
    }

    /**
     * Obtener una reserva concreta por ID.
     */
    public static function get_booking(int $booking_id): ?Ap_Booking
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking';
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $booking_id),
            ARRAY_A
        );
        if (!$row) {
            return null;
        }
        return Ap_Booking::from_row($row);
    }
}

/**
 * Gestión de huéspedes asociados a una reserva (para Guardia Civil, etc.).
 */
class Ap_Booking_Guests
{
    /**
     * Obtener todos los huéspedes de una reserva.
     *
     * @return array<int, array<string,mixed>>
     */
    public static function get_for_booking(int $booking_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking_guests';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE booking_id = %d ORDER BY id ASC",
                $booking_id
            ),
            ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Reemplazar la lista completa de huéspedes de una reserva.
     *
     * @param int   $booking_id
     * @param array $guests Cada elemento: [
     *   'first_name','last_name','document_type','document_number','birth_date','nationality','is_main_guest'
     * ]
     */
    public static function save_for_booking(int $booking_id, array $guests): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking_guests';
        $booking_id = (int) $booking_id;
        if ($booking_id <= 0) {
            return;
        }

        // Borrar huéspedes existentes.
        $wpdb->delete($table, ['booking_id' => $booking_id], ['%d']);

        foreach ($guests as $g) {
            $first_name = isset($g['first_name']) ? sanitize_text_field($g['first_name']) : '';
            $last_name  = isset($g['last_name']) ? sanitize_text_field($g['last_name']) : '';
            $doc_type   = isset($g['document_type']) ? sanitize_key($g['document_type']) : 'dni';
            $doc_number = isset($g['document_number']) ? sanitize_text_field($g['document_number']) : '';
            $birth_date = isset($g['birth_date']) ? sanitize_text_field($g['birth_date']) : '';
            $nationality = isset($g['nationality']) ? strtoupper(sanitize_text_field($g['nationality'])) : '';
            $is_main    = !empty($g['is_main_guest']) ? 1 : 0;

            if ($first_name === '' || $last_name === '' || $doc_number === '' || $birth_date === '') {
                continue;
            }

            $wpdb->insert(
                $table,
                [
                    'booking_id'      => $booking_id,
                    'first_name'      => $first_name,
                    'last_name'       => $last_name,
                    'document_type'   => $doc_type,
                    'document_number' => $doc_number,
                    'birth_date'      => $birth_date,
                    'nationality'     => $nationality,
                    'is_main_guest'   => $is_main,
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%d']
            );
        }
    }
}

/**
 * Servicio de pricing con motor de reglas de temporada.
 */
class Ap_Booking_Pricing_Service
{
    /**
     * Calcular precio total para un rango de fechas aplicando reglas de temporada.
     *
     * Algoritmo día a día:
     *  1. Para cada noche del rango, busca la regla de mayor prioridad que cubra esa fecha.
     *  2. Si hay reglas, aplica su base_price × weekend_multiplier.
     *  3. Si no hay reglas, usa el precio base del producto.
     *  4. Si extra_guest_price > 0 y guests > 1, suma (guests-1) × extra_guest_price por noche.
     *
     * @param int    $product_id
     * @param string $checkin   Fecha YYYY-MM-DD o timestamp string
     * @param string $checkout  Fecha YYYY-MM-DD o timestamp string
     * @param int    $guests
     *
     * @return array{
     *   nights: int,
     *   base_price: float,
     *   subtotal: float,
     *   cleaning_fee: float,
     *   laundry_fee: float,
     *   security_deposit: float,
     *   total: float,
     *   currency: string,
     *   breakdown: array<string, float>
     * }
     */
    public static function calculate_price(int $product_id, string $checkin, string $checkout, int $guests = 1): array
    {
        $empty = [
            'nights'           => 0,
            'base_price'       => 0.0,
            'subtotal'         => 0.0,
            'cleaning_fee'     => 0.0,
            'laundry_fee'      => 0.0,
            'security_deposit' => 0.0,
            'total'            => 0.0,
            'currency'         => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'EUR',
            'breakdown'        => [],
        ];

        $checkin_ts  = strtotime($checkin);
        $checkout_ts = strtotime($checkout);
        if (!$checkin_ts || !$checkout_ts || $checkout_ts <= $checkin_ts) {
            return $empty;
        }

        $nights = max(1, (int) ceil(($checkout_ts - $checkin_ts) / DAY_IN_SECONDS));

        // Precio base fallback del producto
        $base_price_meta = get_post_meta($product_id, 'ap_base_price', true);
        $global_base = $base_price_meta !== '' ? (float) $base_price_meta : 0.0;
        if ($global_base <= 0 && function_exists('wc_get_product')) {
            $wc_product = wc_get_product($product_id);
            if ($wc_product) {
                $global_base = (float) $wc_product->get_price();
            }
        }

        // Cargar todas las reglas que intersectan el rango (puede haber varias temporadas)
        $rules = self::get_rules_for_range($product_id, $checkin_ts, $checkout_ts - DAY_IN_SECONDS);

        // Calcular precio noche a noche
        $subtotal       = 0.0;
        $breakdown      = [];
        $avg_base_price = 0.0;

        for ($ts = $checkin_ts; $ts < $checkout_ts; $ts += DAY_IN_SECONDS) {
            $day_date   = gmdate('Y-m-d', $ts);
            $dow        = (int) gmdate('N', $ts); // 1=Mon … 7=Sun
            $is_weekend = $dow >= 5; // Vie, Sáb, Dom

            // Buscar la regla de mayor prioridad válida para este día
            $best_rule  = null;
            foreach ($rules as $rule) {
                $rule_from = strtotime($rule['date_from']);
                $rule_to   = strtotime($rule['date_to']);
                if ($ts < $rule_from || $ts > $rule_to) {
                    continue;
                }
                // dow_mask: "1111100" → L M X J V, posición = dow-1
                if ($rule['dow_mask'] !== null && strlen($rule['dow_mask']) === 7) {
                    if (($rule['dow_mask'][$dow - 1] ?? '1') === '0') {
                        continue;
                    }
                }
                if ($best_rule === null || (int) $rule['priority'] > (int) $best_rule['priority']) {
                    $best_rule = $rule;
                }
            }

            if ($best_rule !== null) {
                $night_price = (float) $best_rule['base_price'];
                if ($is_weekend && (float) $best_rule['weekend_multiplier'] > 0) {
                    $night_price *= (float) $best_rule['weekend_multiplier'];
                }
                if ($guests > 1 && (float) $best_rule['extra_guest_price'] > 0) {
                    $night_price += ($guests - 1) * (float) $best_rule['extra_guest_price'];
                }
            } else {
                $night_price = $global_base;
                if ($guests > 1) {
                    $extra_guest_meta = (float) get_post_meta($product_id, 'ap_extra_guest_price', true);
                    if ($extra_guest_meta > 0) {
                        $night_price += ($guests - 1) * $extra_guest_meta;
                    }
                }
            }

            $breakdown[$day_date] = round($night_price, 2);
            $subtotal += $night_price;
            $avg_base_price += $night_price;
        }

        $subtotal    = round($subtotal, 2);
        $avg_nightly = $nights > 0 ? round($avg_base_price / $nights, 2) : $global_base;

        $cleaning_fee     = (float) (get_post_meta($product_id, '_cleaning_fee', true) ?: 0.0);
        $laundry_fee      = (float) (get_post_meta($product_id, '_laundry_fee', true) ?: 0.0);
        $security_deposit = (float) (get_post_meta($product_id, 'ap_security_deposit_amount', true) ?: 0.0);

        $total = $subtotal + $cleaning_fee + $laundry_fee;

        return [
            'nights'           => $nights,
            'base_price'       => $avg_nightly,
            'subtotal'         => $subtotal,
            'cleaning_fee'     => $cleaning_fee,
            'laundry_fee'      => $laundry_fee,
            'security_deposit' => $security_deposit,
            'total'            => round($total, 2),
            'currency'         => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'EUR',
            'breakdown'        => $breakdown,
        ];
    }

    /**
     * Obtener el precio por noche para un día concreto (para mostrar en calendarios).
     */
    public static function get_day_price(int $product_id, string $date): float
    {
        $result = self::calculate_price($product_id, $date, gmdate('Y-m-d', strtotime($date) + DAY_IN_SECONDS), 1);
        return $result['base_price'] ?? 0.0;
    }

    /**
     * Obtener reglas de pricing para un producto que intersectan el rango.
     *
     * @return array<int, array{id:int,name:string,date_from:string,date_to:string,dow_mask:?string,base_price:float,extra_guest_price:float,weekend_multiplier:float,priority:int,min_nights:int,max_nights:int}>
     */
    public static function get_rules_for_range(int $product_id, int $from_ts, int $to_ts): array
    {
        global $wpdb;

        $table    = $wpdb->prefix . 'ap_booking_pricing_rules';
        $from_str = gmdate('Y-m-d', $from_ts);
        $to_str   = gmdate('Y-m-d', $to_ts);

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE product_id = %d
                   AND date_from <= %s
                   AND date_to >= %s
                 ORDER BY priority DESC",
                $product_id,
                $to_str,
                $from_str
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }

    /**
     * Obtener todas las reglas de un producto.
     */
    public static function get_rules_for_product(int $product_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking_pricing_rules';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE product_id = %d ORDER BY date_from ASC, priority DESC",
                $product_id
            ),
            ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Crear o actualizar una regla de pricing.
     *
     * @param array $data Campos de la regla.
     * @return int ID de la regla creada/actualizada.
     */
    public static function upsert_rule(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking_pricing_rules';

        $row = [
            'product_id'         => (int) ($data['product_id'] ?? 0),
            'name'               => sanitize_text_field($data['name'] ?? ''),
            'date_from'          => sanitize_text_field($data['date_from'] ?? ''),
            'date_to'            => sanitize_text_field($data['date_to'] ?? ''),
            'dow_mask'           => isset($data['dow_mask']) ? sanitize_text_field($data['dow_mask']) : null,
            'min_nights'         => max(1, (int) ($data['min_nights'] ?? 1)),
            'max_nights'         => max(1, (int) ($data['max_nights'] ?? 365)),
            'base_price'         => (float) ($data['base_price'] ?? 0),
            'extra_guest_price'  => (float) ($data['extra_guest_price'] ?? 0),
            'weekend_multiplier' => max(0.0, (float) ($data['weekend_multiplier'] ?? 1.0)),
            'priority'           => (int) ($data['priority'] ?? 0),
        ];

        $formats = ['%d', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%d'];

        if (!empty($data['id'])) {
            $wpdb->update($table, $row, ['id' => (int) $data['id']], $formats, ['%d']);
            return (int) $data['id'];
        }

        $wpdb->insert($table, $row, $formats);
        return (int) $wpdb->insert_id;
    }

    /**
     * Eliminar una regla.
     */
    public static function delete_rule(int $rule_id, int $product_id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking_pricing_rules';
        return (bool) $wpdb->delete(
            $table,
            ['id' => $rule_id, 'product_id' => $product_id],
            ['%d', '%d']
        );
    }
}

/**
 * Servicio de disponibilidad.
 */
class Ap_Booking_Availability_Service
{
    /**
     * Comprobar disponibilidad de un rango.
     */
    public static function is_available(int $product_id, string $checkin, string $checkout, int $guests = 1): bool
    {
        $checkin_ts = strtotime($checkin);
        $checkout_ts = strtotime($checkout);
        if (!$checkin_ts || !$checkout_ts || $checkout_ts <= $checkin_ts) {
            return false;
        }

        // Comprobar bloqueos manuales.
        if (self::has_blocking_rule($product_id, $checkin_ts, $checkout_ts)) {
            return false;
        }

        // Comprobar reservas existentes confirmadas o retenidas.
        $existing = Ap_Booking_Store::get_bookings_for_product(
            $product_id,
            $checkin_ts,
            $checkout_ts,
            ['held', 'confirmed']
        );

        return empty($existing);
    }

    /**
     * Obtener matriz de calendario (estado + precio por día).
     *
     * @return array<string, array{status:string, price:float}>
     */
    public static function get_calendar_matrix(int $product_id, string $from, string $to): array
    {
        $from_ts = strtotime($from);
        $to_ts = strtotime($to);
        if (!$from_ts || !$to_ts || $to_ts <= $from_ts) {
            return [];
        }

        $matrix = [];

        // Pre-cargar reservas para rango.
        $bookings = Ap_Booking_Store::get_bookings_for_product($product_id, $from_ts, $to_ts, ['held', 'confirmed']);

        // Pre-cargar bloqueos manuales.
        $blocks = self::get_blocking_rules($product_id, $from_ts, $to_ts);

        for ($ts = $from_ts; $ts < $to_ts; $ts += DAY_IN_SECONDS) {
            $date_key = gmdate('Y-m-d', $ts);
            $status = 'free';

            foreach ($blocks as $block) {
                if ($ts >= $block['from'] && $ts <= $block['to']) {
                    $status = 'blocked';
                    break;
                }
            }

            if ($status === 'free') {
                foreach ($bookings as $booking) {
                    $b_start = strtotime($booking->checkin);
                    $b_end = strtotime($booking->checkout);
                    if ($ts >= $b_start && $ts < $b_end) {
                        $status = 'booked';
                        break;
                    }
                }
            }

            $price_info = Ap_Booking_Pricing_Service::calculate_price(
                $product_id,
                $date_key,
                gmdate('Y-m-d', $ts + DAY_IN_SECONDS),
                1
            );

            $matrix[$date_key] = [
                'status' => $status,
                'price' => (float) $price_info['total'],
            ];
        }

        return $matrix;
    }

    private static function has_blocking_rule(int $product_id, int $from_ts, int $to_ts): bool
    {
        $rules = self::get_blocking_rules($product_id, $from_ts, $to_ts);
        return !empty($rules);
    }

    /**
     * Obtener bloqueos manuales para un producto en un rango.
     *
     * @return array<int, array{from:int,to:int,type:string}>
     */
    private static function get_blocking_rules(int $product_id, int $from_ts, int $to_ts): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_booking_availability_rules';
        $product_id = (int) $product_id;

        $from = gmdate('Y-m-d', $from_ts);
        $to = gmdate('Y-m-d', $to_ts);

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE product_id = %d AND date_from <= %s AND date_to >= %s AND type IN ('closed','owner_block','maintenance')",
            $product_id,
            $to,
            $from
        );

        $rows = $wpdb->get_results($sql, ARRAY_A);
        $rules = [];
        foreach ($rows as $row) {
            $rules[] = [
                'from' => strtotime($row['date_from']),
                'to' => strtotime($row['date_to']),
                'type' => (string) $row['type'],
            ];
        }
        return $rules;
    }
}

/**
 * Capa de compatibilidad para módulos existentes que esperan funciones
 * similares a las de WC_Booking_Data_Store.
 *
 * Esta clase se puede usar desde otros módulos (iCal, Pipeline, Informes)
 * para ir migrando progresivamente desde WooCommerce Bookings al nuevo motor.
 */
class Ap_Booking_Compatibility
{
    /**
     * Obtener reservas para un conjunto de productos en un rango de fechas.
     *
     * @param int   $start_ts   Timestamp inicio (segundos).
     * @param int   $end_ts     Timestamp fin (segundos).
     * @param array $product_ids IDs de producto (vacío = todos).
     * @param array $statuses    Estados deseados (por defecto held + confirmed).
     *
     * @return Ap_Booking[]
     */
    public static function get_bookings_in_date_range(int $start_ts, int $end_ts, array $product_ids = [], array $statuses = ['held', 'confirmed']): array
    {
        if ($end_ts <= $start_ts) {
            return [];
        }

        $results = [];

        if (empty($product_ids)) {
            // Consultar todos los productos es costoso; en esta primera versión se requiere product_ids.
            return [];
        }

        foreach ($product_ids as $pid) {
            $pid = (int) $pid;
            if ($pid <= 0) {
                continue;
            }
            $bookings = Ap_Booking_Store::get_bookings_for_product($pid, $start_ts, $end_ts, $statuses);
            foreach ($bookings as $b) {
                $results[] = $b;
            }
        }

        return $results;
    }
}
/**
 * Shortcode frontal para que el propio huésped complete los datos de viajeros
 * (nombre, DNI/pasaporte, fecha de nacimiento, nacionalidad).
 *
 * Uso básico:
 *   [alquipress_guest_registration booking_id="123"]
 *
 * En esta primera versión se asume que la URL solo la recibe el cliente que
 * debe rellenar los datos (enlaces enviados por email).
 */
function alquipress_guest_registration_shortcode($atts)
{
    $atts = shortcode_atts(
        [
            'booking_id' => 0,
        ],
        $atts,
        'alquipress_guest_registration'
    );

    $booking_id = (int) $atts['booking_id'];
    if ($booking_id <= 0) {
        return '<p>' . esc_html__('Reserva no encontrada.', 'alquipress') . '</p>';
    }

    $booking = Ap_Booking_Store::get_booking($booking_id);
    if (!$booking) {
        return '<p>' . esc_html__('Reserva no válida.', 'alquipress') . '</p>';
    }

    $output = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ap_guest_reg_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ap_guest_reg_nonce'])), 'ap_guest_registration_' . $booking_id)) {
        $guests = [];
        $first_names = isset($_POST['guest_first_name']) ? (array) $_POST['guest_first_name'] : [];
        $last_names  = isset($_POST['guest_last_name']) ? (array) $_POST['guest_last_name'] : [];
        $doc_types   = isset($_POST['guest_document_type']) ? (array) $_POST['guest_document_type'] : [];
        $doc_numbers = isset($_POST['guest_document_number']) ? (array) $_POST['guest_document_number'] : [];
        $birth_dates = isset($_POST['guest_birth_date']) ? (array) $_POST['guest_birth_date'] : [];
        $nationalities = isset($_POST['guest_nationality']) ? (array) $_POST['guest_nationality'] : [];
        $main_flags  = isset($_POST['guest_is_main']) ? (array) $_POST['guest_is_main'] : [];

        $rows = max(count($first_names), count($last_names), count($doc_numbers), count($birth_dates));
        for ($i = 0; $i < $rows; $i++) {
            $guests[] = [
                'first_name'      => $first_names[$i] ?? '',
                'last_name'       => $last_names[$i] ?? '',
                'document_type'   => $doc_types[$i] ?? 'dni',
                'document_number' => $doc_numbers[$i] ?? '',
                'birth_date'      => $birth_dates[$i] ?? '',
                'nationality'     => $nationalities[$i] ?? '',
                'is_main_guest'   => in_array((string) $i, $main_flags, true),
            ];
        }

        Ap_Booking_Guests::save_for_booking($booking_id, $guests);
        $output .= '<div class="ap-guest-reg-notice ap-guest-reg-success"><p>' . esc_html__('Datos de huéspedes guardados correctamente.', 'alquipress') . '</p></div>';
    }

    $existing   = Ap_Booking_Guests::get_for_booking($booking_id);
    $guest_rows = !empty($existing) ? $existing : [];

    if (empty($guest_rows)) {
        $total_slots = max(1, (int) $booking->guests);
        for ($i = 0; $i < $total_slots; $i++) {
            $guest_rows[] = [
                'first_name'      => '',
                'last_name'       => '',
                'document_type'   => 'dni',
                'document_number' => '',
                'birth_date'      => '',
                'nationality'     => '',
                'is_main_guest'   => $i === 0 ? 1 : 0,
            ];
        }
    }

    ob_start();
    ?>
    <form method="post" class="ap-guest-registration-form">
        <?php wp_nonce_field('ap_guest_registration_' . $booking_id, 'ap_guest_reg_nonce'); ?>
        <input type="hidden" name="ap_booking_id" value="<?php echo (int) $booking_id; ?>">

        <h2><?php esc_html_e('Datos de los huéspedes', 'alquipress'); ?></h2>
        <p><?php esc_html_e('Rellena los datos de todos los viajeros tal y como aparecen en su documento de identidad. Esta información se utiliza para el parte de viajeros de Guardia Civil.', 'alquipress'); ?></p>

        <table class="ap-guest-reg-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Titular', 'alquipress'); ?></th>
                    <th><?php esc_html_e('Nombre', 'alquipress'); ?></th>
                    <th><?php esc_html_e('Apellidos', 'alquipress'); ?></th>
                    <th><?php esc_html_e('Tipo doc.', 'alquipress'); ?></th>
                    <th><?php esc_html_e('Nº documento', 'alquipress'); ?></th>
                    <th><?php esc_html_e('Fecha nacimiento', 'alquipress'); ?></th>
                    <th><?php esc_html_e('Nacionalidad (ISO-3)', 'alquipress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guest_rows as $index => $g) : ?>
                    <tr>
                        <td>
                            <label>
                                <input type="radio" name="guest_is_main[]" value="<?php echo (int) $index; ?>" <?php checked(!empty($g['is_main_guest']), true); ?> />
                            </label>
                        </td>
                        <td><input type="text" name="guest_first_name[]" value="<?php echo esc_attr((string) ($g['first_name'] ?? '')); ?>" required></td>
                        <td><input type="text" name="guest_last_name[]" value="<?php echo esc_attr((string) ($g['last_name'] ?? '')); ?>" required></td>
                        <td>
                            <select name="guest_document_type[]">
                                <?php
                                $doc_type_val = isset($g['document_type']) ? (string) $g['document_type'] : 'dni';
                                ?>
                                <option value="dni" <?php selected($doc_type_val, 'dni'); ?>><?php esc_html_e('DNI', 'alquipress'); ?></option>
                                <option value="nie" <?php selected($doc_type_val, 'nie'); ?>><?php esc_html_e('NIE', 'alquipress'); ?></option>
                                <option value="passport" <?php selected($doc_type_val, 'passport'); ?>><?php esc_html_e('Pasaporte', 'alquipress'); ?></option>
                            </select>
                        </td>
                        <td><input type="text" name="guest_document_number[]" value="<?php echo esc_attr((string) ($g['document_number'] ?? '')); ?>" required></td>
                        <td><input type="date" name="guest_birth_date[]" value="<?php echo esc_attr((string) ($g['birth_date'] ?? '')); ?>" required></td>
                        <td><input type="text" name="guest_nationality[]" value="<?php echo esc_attr((string) ($g['nationality'] ?? '')); ?>" maxlength="3" placeholder="ESP"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="ap-guest-reg-help">
            <?php esc_html_e('Ejemplo de regla: 1 niño (hasta 3 años) puede alojarse gratis usando cuna; los niños de 4 a 17 años pueden tener suplemento por cama supletoria según la política de la propiedad.', 'alquipress'); ?>
        </p>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e('Guardar datos de huéspedes', 'alquipress'); ?></button>
        </p>
    </form>
    <?php

    $output .= ob_get_clean();
    return $output;
}
add_shortcode('alquipress_guest_registration', 'alquipress_guest_registration_shortcode');
