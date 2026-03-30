<?php
/**
 * API REST para el motor de reservas Alquipress.
 *
 * Namespace: ap-bookings/v1
 *
 * Endpoints:
 *  GET  /calendar          → matriz de días (estado + precio) para un producto y rango
 *  GET  /price             → breakdown de precio para checkin/checkout/guests
 *  GET  /rules             → lista de reglas de temporada de un producto
 *  POST /rules             → crear regla
 *  PUT  /rules/{id}        → actualizar regla
 *  DELETE /rules/{id}      → eliminar regla
 *  GET  /blocks            → lista de bloqueos de disponibilidad
 *  POST /blocks            → crear bloqueo
 *  DELETE /blocks/{id}     → eliminar bloqueo
 *  GET  /bookings          → lista de reservas de un producto
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ap_Bookings_REST_API
{
    const NAMESPACE = 'ap-bookings/v1';

    public static function init(): void
    {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes(): void
    {
        $ns = self::NAMESPACE;

        // Calendar
        register_rest_route($ns, '/calendar', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [__CLASS__, 'get_calendar'],
            'permission_callback' => [__CLASS__, 'check_public_product_access'],
            'args'                => [
                'product_id' => ['required' => true, 'sanitize_callback' => 'absint'],
                'from'       => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                'to'         => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);

        // Price breakdown
        register_rest_route($ns, '/price', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [__CLASS__, 'get_price'],
            'permission_callback' => [__CLASS__, 'check_public_product_access'],
            'args'                => [
                'product_id' => ['required' => true, 'sanitize_callback' => 'absint'],
                'checkin'    => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                'checkout'   => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                'guests'     => ['default' => 1, 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Rules
        register_rest_route($ns, '/rules', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [__CLASS__, 'get_rules'],
                'permission_callback' => [__CLASS__, 'check_admin'],
                'args'                => [
                    'product_id' => ['required' => true, 'sanitize_callback' => 'absint'],
                ],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [__CLASS__, 'create_rule'],
                'permission_callback' => [__CLASS__, 'check_admin'],
            ],
        ]);

        register_rest_route($ns, '/rules/(?P<id>\d+)', [
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [__CLASS__, 'update_rule'],
                'permission_callback' => [__CLASS__, 'check_admin'],
                'args'                => [
                    'id' => ['sanitize_callback' => 'absint'],
                ],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [__CLASS__, 'delete_rule'],
                'permission_callback' => [__CLASS__, 'check_admin'],
                'args'                => [
                    'id' => ['sanitize_callback' => 'absint'],
                ],
            ],
        ]);

        // Blocks
        register_rest_route($ns, '/blocks', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [__CLASS__, 'get_blocks'],
                'permission_callback' => [__CLASS__, 'check_admin'],
                'args'                => [
                    'product_id' => ['required' => true, 'sanitize_callback' => 'absint'],
                ],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [__CLASS__, 'create_block'],
                'permission_callback' => [__CLASS__, 'check_admin'],
            ],
        ]);

        register_rest_route($ns, '/blocks/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [__CLASS__, 'delete_block'],
            'permission_callback' => [__CLASS__, 'check_admin'],
            'args'                => [
                'id' => ['sanitize_callback' => 'absint'],
            ],
        ]);

        // Bookings list (admin only)
        register_rest_route($ns, '/bookings', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [__CLASS__, 'get_bookings'],
            'permission_callback' => [__CLASS__, 'check_admin'],
            'args'                => [
                'product_id' => ['required' => true, 'sanitize_callback' => 'absint'],
                'from'       => ['default' => null, 'sanitize_callback' => 'sanitize_text_field'],
                'to'         => ['default' => null, 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);
    }

    // ── Permission callbacks ─────────────────────────────────────────────────

    public static function check_admin(\WP_REST_Request $request): bool
    {
        return current_user_can('manage_woocommerce') || current_user_can('edit_products');
    }

    /**
     * Límites de rate para endpoints públicos:
     * - 60 peticiones por minuto por IP (uso legítimo normal).
     * - Usuarios autenticados tienen el doble de margen.
     */
    private const RATE_LIMIT_PUBLIC = 60;
    private const RATE_LIMIT_WINDOW = 60; // segundos

    public static function check_public_product_access(\WP_REST_Request $request): bool|\WP_Error
    {
        // Rate limiting: protege /calendar y /price de abuso
        if (class_exists('Alquipress_Rate_Limiter')) {
            $max = is_user_logged_in() ? self::RATE_LIMIT_PUBLIC * 2 : self::RATE_LIMIT_PUBLIC;
            if (!Alquipress_Rate_Limiter::check_limit('rest_public', $max, self::RATE_LIMIT_WINDOW)) {
                return new \WP_Error(
                    'too_many_requests',
                    __('Demasiadas peticiones. Por favor, espera un momento.', 'alquipress'),
                    ['status' => 429]
                );
            }
        }

        $product_id = absint($request->get_param('product_id'));
        if (!$product_id) {
            return false;
        }

        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'product') {
            return false;
        }

        if (get_post_status($product_id) === 'publish') {
            return true;
        }

        return current_user_can('edit_post', $product_id) || current_user_can('manage_woocommerce');
    }

    // ── Calendar ─────────────────────────────────────────────────────────────

    /** TTL del cache de calendario en segundos (10 minutos). */
    private const CALENDAR_CACHE_TTL = 600;

    public static function get_calendar(\WP_REST_Request $request): WP_REST_Response
    {
        $product_id = $request->get_param('product_id');
        $from       = $request->get_param('from');
        $to         = $request->get_param('to');

        if (!self::valid_product($product_id)) {
            return self::error('Producto no encontrado', 404);
        }

        $from_ts = strtotime($from);
        $to_ts   = strtotime($to);
        if (!$from_ts || !$to_ts || $to_ts <= $from_ts) {
            return self::error('Rango de fechas inválido');
        }

        // Limitar a 13 meses para evitar queries pesadas
        if (($to_ts - $from_ts) > 13 * 31 * DAY_IN_SECONDS) {
            $to_ts = $from_ts + 13 * 31 * DAY_IN_SECONDS;
        }

        $to_date = gmdate('Y-m-d', $to_ts);

        // Cache por producto + rango de fechas. Se invalida al crear/modificar reservas o bloqueos.
        $cache_key = 'ap_cal_' . $product_id . '_' . md5($from . '_' . $to_date);
        $matrix    = get_transient($cache_key);

        if ($matrix === false) {
            $matrix = Ap_Booking_Availability_Service::get_calendar_matrix($product_id, $from, $to_date);
            set_transient($cache_key, $matrix, self::CALENDAR_CACHE_TTL);
        }

        return rest_ensure_response($matrix);
    }

    /**
     * Invalidar el cache de calendario de un producto.
     * Llamar desde Ap_Booking_Store al crear/modificar/eliminar reservas o bloqueos.
     *
     * @param int $product_id
     */
    public static function invalidate_calendar_cache(int $product_id): void
    {
        global $wpdb;
        // Buscar y eliminar todos los transients de este producto
        $like = $wpdb->esc_like('_transient_ap_cal_' . $product_id . '_') . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $like
            )
        );
    }

    // ── Price breakdown ──────────────────────────────────────────────────────

    public static function get_price(\WP_REST_Request $request): WP_REST_Response
    {
        $product_id = $request->get_param('product_id');
        $checkin    = $request->get_param('checkin');
        $checkout   = $request->get_param('checkout');
        $guests     = max(1, (int) $request->get_param('guests'));

        if (!self::valid_product($product_id)) {
            return self::error('Producto no encontrado', 404);
        }

        // Check availability
        if (!Ap_Booking_Availability_Service::is_available($product_id, $checkin, $checkout, $guests)) {
            return rest_ensure_response([
                'available' => false,
                'message'   => __('Las fechas seleccionadas no están disponibles.', 'alquipress'),
            ]);
        }

        $breakdown = Ap_Booking_Pricing_Service::calculate_price($product_id, $checkin, $checkout, $guests);
        $breakdown['available'] = true;

        // Format prices for display
        if (function_exists('wc_price')) {
            $breakdown['formatted'] = [
                'subtotal'         => wp_strip_all_tags(wc_price($breakdown['subtotal'])),
                'cleaning_fee'     => wp_strip_all_tags(wc_price($breakdown['cleaning_fee'])),
                'laundry_fee'      => wp_strip_all_tags(wc_price($breakdown['laundry_fee'])),
                'security_deposit' => wp_strip_all_tags(wc_price($breakdown['security_deposit'])),
                'total'            => wp_strip_all_tags(wc_price($breakdown['total'])),
                'base_price'       => wp_strip_all_tags(wc_price($breakdown['base_price'])),
            ];
        }

        return rest_ensure_response($breakdown);
    }

    // ── Pricing rules ────────────────────────────────────────────────────────

    public static function get_rules(\WP_REST_Request $request): WP_REST_Response
    {
        $product_id = $request->get_param('product_id');
        if (!self::valid_product($product_id)) {
            return self::error('Producto no encontrado', 404);
        }

        $rules = Ap_Booking_Pricing_Service::get_rules_for_product($product_id);
        return rest_ensure_response($rules);
    }

    public static function create_rule(\WP_REST_Request $request): WP_REST_Response
    {
        $data = self::extract_rule_data($request);
        if (is_wp_error($data)) {
            return self::error($data->get_error_message());
        }

        $id = Ap_Booking_Pricing_Service::upsert_rule($data);
        if (!$id) {
            return self::error('No se pudo crear la regla');
        }

        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_booking_pricing_rules WHERE id = %d", $id),
            ARRAY_A
        );

        return new WP_REST_Response($row, 201);
    }

    public static function update_rule(\WP_REST_Request $request): WP_REST_Response
    {
        $id   = (int) $request->get_param('id');
        $data = self::extract_rule_data($request);
        if (is_wp_error($data)) {
            return self::error($data->get_error_message());
        }

        $data['id'] = $id;
        Ap_Booking_Pricing_Service::upsert_rule($data);

        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_booking_pricing_rules WHERE id = %d AND product_id = %d", $id, $data['product_id']),
            ARRAY_A
        );

        if (!$row) {
            return self::error('Regla no encontrada', 404);
        }

        return rest_ensure_response($row);
    }

    public static function delete_rule(\WP_REST_Request $request): WP_REST_Response
    {
        $id         = (int) $request->get_param('id');
        $product_id = (int) ($request->get_param('product_id') ?: $request->get_body_params()['product_id'] ?? 0);

        if (!$product_id) {
            return self::error('product_id requerido');
        }

        $deleted = Ap_Booking_Pricing_Service::delete_rule($id, $product_id);
        if (!$deleted) {
            return self::error('Regla no encontrada', 404);
        }

        return new WP_REST_Response(['deleted' => true, 'id' => $id]);
    }

    // ── Availability blocks ──────────────────────────────────────────────────

    public static function get_blocks(\WP_REST_Request $request): WP_REST_Response
    {
        $product_id = $request->get_param('product_id');
        if (!self::valid_product($product_id)) {
            return self::error('Producto no encontrado', 404);
        }

        $blocks = Ap_Booking_Store::get_blocks_for_product($product_id);
        return rest_ensure_response($blocks);
    }

    public static function create_block(\WP_REST_Request $request): WP_REST_Response
    {
        $product_id = (int) ($request->get_param('product_id') ?: 0);
        $from       = sanitize_text_field($request->get_param('date_from') ?? '');
        $to         = sanitize_text_field($request->get_param('date_to') ?? '');
        $type       = sanitize_key($request->get_param('type') ?? 'owner_block');
        $note       = sanitize_text_field($request->get_param('note') ?? '');

        if (!$product_id || !$from || !$to) {
            return self::error('product_id, date_from y date_to son obligatorios');
        }

        $allowed_types = ['closed', 'owner_block', 'maintenance'];
        if (!in_array($type, $allowed_types, true)) {
            $type = 'owner_block';
        }

        $id = Ap_Booking_Store::create_block($product_id, $from, $to, $type, $note);
        if (!$id) {
            return self::error('No se pudo crear el bloqueo');
        }

        self::invalidate_calendar_cache($product_id);

        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_booking_availability_rules WHERE id = %d", $id),
            ARRAY_A
        );

        return new WP_REST_Response($row, 201);
    }

    public static function delete_block(\WP_REST_Request $request): WP_REST_Response
    {
        $id         = (int) $request->get_param('id');
        $product_id = (int) ($request->get_param('product_id') ?: $request->get_body_params()['product_id'] ?? 0);

        if (!$product_id) {
            return self::error('product_id requerido');
        }

        $deleted = Ap_Booking_Store::delete_block($id, $product_id);
        if (!$deleted) {
            return self::error('Bloqueo no encontrado', 404);
        }

        self::invalidate_calendar_cache($product_id);

        return new WP_REST_Response(['deleted' => true, 'id' => $id]);
    }

    // ── Bookings list ────────────────────────────────────────────────────────

    public static function get_bookings(\WP_REST_Request $request): WP_REST_Response
    {
        $product_id = $request->get_param('product_id');
        $from       = $request->get_param('from');
        $to         = $request->get_param('to');

        if (!self::valid_product($product_id)) {
            return self::error('Producto no encontrado', 404);
        }

        if ($from && $to) {
            $bookings = Ap_Booking_Store::get_bookings_for_product(
                $product_id,
                strtotime($from),
                strtotime($to),
                ['held', 'confirmed']
            );
        } else {
            $bookings = Ap_Booking_Store::get_all_bookings_for_product($product_id);
        }

        $data = array_map(fn($b) => (array) $b, $bookings);
        return rest_ensure_response($data);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private static function valid_product(int $product_id): bool
    {
        if (!$product_id) {
            return false;
        }
        return get_post_type($product_id) === 'product';
    }

    private static function error(string $message, int $status = 400): WP_REST_Response
    {
        return new WP_REST_Response(['code' => 'ap_bookings_error', 'message' => $message], $status);
    }

    /**
     * Extraer y validar datos de regla desde una request.
     */
    private static function extract_rule_data(\WP_REST_Request $request)
    {
        $product_id = (int) ($request->get_param('product_id') ?: 0);
        $date_from  = sanitize_text_field($request->get_param('date_from') ?? '');
        $date_to    = sanitize_text_field($request->get_param('date_to') ?? '');

        if (!$product_id || !$date_from || !$date_to) {
            return new WP_Error('missing_fields', 'product_id, date_from y date_to son obligatorios');
        }

        if (strtotime($date_from) >= strtotime($date_to)) {
            return new WP_Error('invalid_dates', 'date_from debe ser anterior a date_to');
        }

        return [
            'product_id'         => $product_id,
            'name'               => sanitize_text_field($request->get_param('name') ?? ''),
            'date_from'          => $date_from,
            'date_to'            => $date_to,
            'dow_mask'           => $request->get_param('dow_mask') ? sanitize_text_field($request->get_param('dow_mask')) : null,
            'min_nights'         => max(1, (int) ($request->get_param('min_nights') ?? 1)),
            'max_nights'         => max(1, (int) ($request->get_param('max_nights') ?? 365)),
            'base_price'         => (float) ($request->get_param('base_price') ?? 0),
            'extra_guest_price'  => (float) ($request->get_param('extra_guest_price') ?? 0),
            'weekend_multiplier' => max(0.1, (float) ($request->get_param('weekend_multiplier') ?? 1.0)),
            'priority'           => (int) ($request->get_param('priority') ?? 0),
        ];
    }
}
