<?php
/**
 * WC_Booking_Cart_Store_API class
 *
 * @package  WooCommerce Bookings
 * @since    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * Extends the store add-to-cart API with booking related data.
 *
 * @version 3.0.0
 */
class WC_Bookings_Cart_Store_API {

	/**
	 * Bootstraps the class and hooks required data.
	 */
	public function __construct() {
		// Add Store API parse request data.
		add_filter( 'woocommerce_store_api_add_to_cart_data', array( $this, 'add_cart_item_data' ), 10, 2 );
	}

	/**
	 * Add cart item data.
	 *
	 * @throws RouteException When invalid params are provided.
	 *
	 * @param array           $cart_item_data Cart item data.
	 * @param WP_REST_Request $request Request data.
	 * @return array $cart_item_data
	 */
	public function add_cart_item_data( array $cart_item_data, WP_REST_Request $request ): array {
		$params = $request->get_json_params();

		$product_id = $cart_item_data['id'];
		$product    = wc_get_product( $product_id );
		if ( ! is_wc_booking_product( $product ) ) {
			return $cart_item_data;
		}

		if ( ! isset( $params['booking_configuration'] ) ) {
			throw new RouteException( 'woocommerce_rest_cart_invalid_product_booking', esc_html__( 'Booking configuration is required.', 'woocommerce-bookings' ) );
		}

		$store_api_cart_data                         = self::map_store_api_to_legacy_posted_data( $params['booking_configuration'], $product );
		$cart_item_data['cart_item_data']['booking'] = $store_api_cart_data;
		return $cart_item_data;
	}

	/**
	 * Map store API to legacy posted data.
	 *
	 * @see wc_bookings_get_posted_data().
	 *
	 * @throws RouteException When invalid params are provided.
	 *
	 * @param array              $params Params.
	 * @param WC_Product_Booking $product Product.
	 * @return array $store_api_cart_data
	 */
	private function map_store_api_to_legacy_posted_data( $params, $product ): array {

		// Hint:
		// This function:
		// - Validates the request params.
		// - Parses the request params and fills-in the calculated params.
		// - Returns the posted data as they were processed via wc_bookings_get_posted_data() in a $_POST request.

		// Expected Params:
		// - Date: Timezone-aware date string in ISO 8601 format.
		// - Resource: Resource ID. Required if product has resources.
		// - Local timezone (Optional.)
		// Calculated:
		// - Duration: Fixed based on product duration.
		// - End date: based on start date and duration.
		// - All day: based on start and end date.

		// Validate request params.
		$this->validate_store_api_params( $params, $product );

		// Parse Request params and fill-in calculated params.
		$add_to_cart_params = array(
			'start_date'     => strtotime( $params['date'] ),
			'resource_id'    => $product->has_resources() && $product->is_resource_assignment_type( 'customer' ) ? $params['resource_id'] : 0,
			'local_timezone' => $params['local_timezone'] ?? '',
		);
		$requires_time      = in_array( $product->get_duration_unit(), array( 'minute', 'hour' ), true );
		$mocked_post        = array(
			'wc_bookings_field_start_date_year'           => gmdate( 'Y', $add_to_cart_params['start_date'] ),
			'wc_bookings_field_start_date_month'          => gmdate( 'm', $add_to_cart_params['start_date'] ),
			'wc_bookings_field_start_date_day'            => gmdate( 'd', $add_to_cart_params['start_date'] ),
			'wc_bookings_field_start_date_time'           => $requires_time ? gmdate( 'Y-m-d H:i:s', $add_to_cart_params['start_date'] ) : '',
			'wc_bookings_field_resource'                  => $add_to_cart_params['resource_id'],
			'wc_bookings_field_duration'                  => $product->get_duration(),
			'wc_bookings_field_start_date_local_timezone' => $add_to_cart_params['local_timezone'],
		);

		try {
			$posted_data  = wc_bookings_get_posted_data( $mocked_post, $product );
			$cart_manager = WC_Booking_Cart_Manager::get_instance();
			$posted_data  = $cart_manager->configure_cart_item_data( $posted_data, $product );
		} catch ( Exception $e ) {
			throw new RouteException( 'woocommerce_rest_cart_invalid_product_booking', esc_html__( 'Request time is not available.', 'woocommerce-bookings' ), 409 );
		}

		return $posted_data;
	}

	/**
	 * Validate store API params.
	 *
	 * @throws RouteException When invalid params are provided.
	 *
	 * @param array              $params Params.
	 * @param WC_Product_Booking $product Product.
	 * @return void
	 */
	private function validate_store_api_params( $params, $product ) {

		// Validate request params.
		if ( empty( $params['date'] ) ) {
			throw new RouteException( 'woocommerce_rest_cart_invalid_product_booking', esc_html__( 'Booking date is required.', 'woocommerce-bookings' ) );
		}

		// Check date is a date time format via regex YYYY-MM-DD HH:MM:SS.
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $params['date'] ) ) {
			throw new RouteException( 'woocommerce_rest_cart_invalid_product_booking', esc_html__( 'Booking date is invalid.', 'woocommerce-bookings' ) );
		}

		if ( isset( $params['resource_id'] ) && ! is_numeric( $params['resource_id'] ) ) {
			throw new RouteException( 'woocommerce_rest_cart_invalid_product_booking', esc_html__( 'Booking resource is invalid.', 'woocommerce-bookings' ) );
		}

		// Validate request params.
		if ( $product->has_resources() ) {
			$has_automatic_resource_assignment = $product->is_resource_assignment_type( 'automatic' );
			if ( empty( $params['resource_id'] ) && ! $has_automatic_resource_assignment ) {
				throw new RouteException( 'woocommerce_rest_cart_invalid_product_booking', esc_html__( 'Booking resource is required.', 'woocommerce-bookings' ) );
			}

			if ( isset( $params['resource_id'] ) && $has_automatic_resource_assignment ) {
				throw new RouteException( 'woocommerce_rest_cart_invalid_product_booking', esc_html__( 'Cannot specify a resource when resource assignment is automatic.', 'woocommerce-bookings' ) );
			}
		}
	}
}
