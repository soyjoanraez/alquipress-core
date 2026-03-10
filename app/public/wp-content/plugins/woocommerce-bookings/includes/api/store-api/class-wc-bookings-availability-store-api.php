<?php
/**
 * WC_Booking_Availability_Store_API class
 *
 * @package  WooCommerce Bookings
 * @since    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends the store availability API with booking related data.
 *
 * @version 3.0.0
 */
class WC_Bookings_Availability_Store_API extends WC_REST_Controller {

	/**
	 * Namespace for the API.
	 *
	 * @var string
	 */
	protected $namespace = WC_Bookings_REST_API::V2_NAMESPACE;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes for the API.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/products/(?P<id>[\d]+)/availability',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_availability' ),
				'permission_callback' => array( $this, 'get_availability_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
		);
	}

	/**
	 * Get the availability for a product.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_Error|array The availability data, or a WP_Error if the request is invalid.
	 */
	public function get_availability( $request ) {

		$validation_error = $this->validate_params( $request );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		$product_id       = absint( $request->get_param( 'id' ) );
		$resource_id      = $request->has_param( 'resource_id' ) ? absint( $request->get_param( 'resource_id' ) ) : 0;
		$start_date_input = $request->get_param( 'start_date' );
		$end_date_input   = $request->get_param( 'end_date' );
		$timezone_offset  = $request->has_param( 'timezone_offset' ) ? $request->get_param( 'timezone_offset' ) : 0;

		$product = get_wc_product_booking( $product_id );
		if ( ! $product ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_product_id', esc_html__( 'Invalid product ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		// Adjust the start and end dates for the timezone offset.
		$start_date = $timezone_offset ? strtotime( $start_date_input . ' ' . $timezone_offset . ' hours' ) : strtotime( $start_date_input );
		$end_date   = $timezone_offset ? strtotime( $end_date_input . ' ' . $timezone_offset . ' hours' ) : strtotime( $end_date_input );

		// Cache bucketing strategy tbd.
		$blocks           = $product->get_blocks_in_range( $start_date, $end_date, null, $resource_id, array() );
		$slots            = $product->get_time_slots( $blocks, $resource_id, $start_date, $end_date );
		$available_blocks = $this->adjust_blocks_for_timezone( $slots, $timezone_offset );
		$response         = array(
			'product_id'      => $product_id,
			'resource_id'     => $resource_id,
			'start_date'      => $start_date_input,
			'end_date'        => $end_date_input,
			'timezone_offset' => $timezone_offset,
			'availability'    => $this->get_formatted_availability( $available_blocks ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Update the timezone offset for the availability blocks.
	 *
	 * @param array $blocks Array of availability blocks.
	 * @param int   $timezone_offset  Timezone offset in hours.
	 * @return array Updated availability blocks.
	 */
	private function adjust_blocks_for_timezone( array $blocks, int $timezone_offset = 0 ): array {
		if ( 0 === $timezone_offset || 'yes' !== WC_Bookings_Timezone_Settings::get( 'use_client_timezone' ) ) {
			return $blocks;
		}

		$server_timezone = wc_booking_get_timezone_string();
		$updated_blocks  = array();

		foreach ( $blocks as $timestamp => $block ) {
			// Create DateTime in server's timezone (otherwise UTC is assumed).
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$dt = new DateTime( date( 'Y-m-d\TH:i:s', $timestamp ), new DateTimeZone( $server_timezone ) );

			// Calling simply `getTimestamp` will not calculate the timezone.
			$new_timestamp                    = $dt->getTimestamp() + $timezone_offset * HOUR_IN_SECONDS;
			$updated_blocks[ $new_timestamp ] = $block;
		}

		return $updated_blocks;
	}

	/**
	 * Format availability slots into a structured array grouped by month and day.
	 *
	 * Takes an array of raw availability slots and formats them into a nested array structure:
	 * - First level: Months (YYYY-MM)
	 * - Second level: Days (YYYY-MM-DD)
	 * - Third level: Individual time slots with availability count
	 *
	 * @param array $blocks Array of availability blocks with timestamps as keys.
	 * @return array Formatted availability data grouped by month and day.
	 */
	private function get_formatted_availability( array $blocks ) {
		$data = array();
		foreach ( $blocks as $timestamp => $block ) {

			$day_bucket_key   = gmdate( 'Y-m-d', $timestamp );
			$month_bucket_key = gmdate( 'Y-m', $timestamp );
			if ( ! isset( $data[ $month_bucket_key ] ) ) {
				$data[ $month_bucket_key ] = array();
			}

			if ( ! isset( $data[ $month_bucket_key ][ $day_bucket_key ] ) ) {
				$data[ $month_bucket_key ][ $day_bucket_key ] = array();
			}

			$data[ $month_bucket_key ][ $day_bucket_key ][ gmdate( 'H:i:s', $timestamp ) ] = $block['available'] ?? 0;
		}

		return $data;
	}

	/**
	 * Validate the request parameters.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_Error|null The validation error, or null if valid.
	 */
	private function validate_params( $request ) {

		$product_id = $request->get_param( 'id' );
		$product    = wc_get_product( $product_id );
		if ( ! is_wc_booking_product( $product ) ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_product_id', esc_html__( 'Invalid product ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		$start_date   = strtotime( $request->get_param( 'start_date' ) );
		$end_date     = strtotime( $request->get_param( 'end_date' ) );
		$now_midnight = strtotime( 'today midnight' );
		if ( $start_date < $now_midnight ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_start', esc_html__( 'Start date must be in the future.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		if ( $start_date >= $end_date ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_end', esc_html__( 'End date must be after start date.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		if ( $product->has_resources() && ! $product->is_resource_assignment_type( 'automatic' ) ) {
			$resource_id = $request->get_param( 'resource_id' );
			if ( ! $resource_id ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_resource_id', esc_html__( 'Resource ID is required.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}
		}

		if ( $product->has_resources() && $product->is_resource_assignment_type( 'automatic' ) ) {
			$resource_id = $request->get_param( 'resource_id' );
			if ( $resource_id ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_resource_id', esc_html__( 'Resource ID is not allowed.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}
		}

		// Validate sane timezone offset.
		$timezone_offset = $request->get_param( 'timezone_offset' );
		if ( $timezone_offset && ( $timezone_offset < -12 || $timezone_offset > 12 ) ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_timezone_offset', esc_html__( 'Invalid timezone offset.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Get the availability schema.
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$schema = array(
			'product_id'      => array(
				'type'        => 'integer',
				'description' => __( 'Product ID.', 'woocommerce-bookings' ),
			),
			'resource_id'     => array(
				'type'        => 'integer',
				'description' => __( 'Resource ID.', 'woocommerce-bookings' ),
			),
			'start_date'      => array(
				'type'        => 'string',
				'required'    => true,
				'format'      => 'date-time',
				'description' => __( 'Start date.', 'woocommerce-bookings' ),
			),
			'end_date'        => array(
				'type'        => 'string',
				'required'    => true,
				'format'      => 'date-time',
				'description' => __( 'End date.', 'woocommerce-bookings' ),
			),
			'timezone_offset' => array(
				'type'        => 'integer',
				'description' => __( 'Timezone offset.', 'woocommerce-bookings' ),
			),
			'availability'    => array(
				'type'        => 'array',
				'readonly'    => true,
				'description' => __( 'Availability.', 'woocommerce-bookings' ),
			),
		);
		return $schema;
	}

	/**
	 * Check if the user has permission to get the availability.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool True if the user has permission, false otherwise.
	 */
	public function get_availability_permissions_check( $request ) {
		// TODO: Maybe rate limit this, or nonce it. Mind HTML caching.
		return true;
	}
}
