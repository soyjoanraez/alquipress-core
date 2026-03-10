<?php
/**
 * REST API Quotes controller.
 *
 * Handles requests to the /quotes endpoint.
 *
 * @package WooCommerce\Bookings\Rest\Controller
 */

/**
 * REST API Quotes controller class.
 */
class WC_Bookings_REST_Quotes_V2_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = WC_Bookings_REST_API::V2_NAMESPACE;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'quotes';

	/**
	 * Register the route for quotes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'start'       => array(
							'type'     => 'string',
							'required' => true,
						),
						'end'         => array(
							'type'     => 'string',
							'required' => true,
						),
						'product_id'  => array(
							'type'     => 'integer',
							'required' => true,
						),
						'resource_id' => array(
							'type'     => 'integer',
							'required' => false,
						),
						'persons'     => array(
							'type'     => 'array',
							'required' => false,
						),
						'customer_id' => array(
							'type'     => 'integer',
							'required' => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Get a quote.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {

		if ( strtotime( $request['start'] ) < time() ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Start date cannot be in the past.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		if ( strtotime( $request['end'] ) < strtotime( $request['start'] ) ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'End date cannot be before start date.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		$product = wc_get_product( $request['product_id'] );
		if ( ! $product ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_product_id', __( 'Invalid product ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		if ( $product->has_resources() && $product->is_resource_assignment_type( 'customer' ) && ! isset( $request['resource_id'] ) ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_request', __( 'Resource ID is required.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		// Parse request data.
		$start       = strtotime( $request['start'] );
		$end         = strtotime( $request['end'] );
		$product_id  = $product->get_id();
		$resource_id = isset( $request['resource_id'] ) ? absint( $request['resource_id'] ) : 0;
		$customer_id = isset( $request['customer_id'] ) ? absint( $request['customer_id'] ) : 0;
		$customer    = $customer_id ? wc_get_container()->get( LegacyProxy::class )->get_instance_of( WC_Customer::class, $customer_id ) : null;
		if ( $customer_id && ( ! $customer || ! $customer->get_id() ) ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_customer_id', __( 'Invalid customer ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		// Assign an available resource automatically.
		if ( $product->has_resources() && $product->is_resource_assignment_type( 'automatic' ) ) {

			$available_slots = wc_bookings_get_total_available_bookings_for_range( $product, $start, $end, 0 );
			if ( empty( $available_slots ) || ! is_array( $available_slots ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Requested time is not available.', 'woocommerce-bookings' ), array( 'status' => 409 ) );
			}

			$resource_id = absint( current( array_keys( $available_slots ) ) );
			// Update the request with the assigned resource ID.
			$request['resource_id'] = $resource_id;
		}

		// Hint: This also validates min/max date range. When out of range, it returns empty array.
		$blocks   = $product->get_blocks_in_range( $start, $end, array(), $resource_id );
		$duration = count( $blocks );

		if ( empty( $duration ) ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_duration', __( 'Invalid duration.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}
		if ( $duration > $product->get_max_duration() ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_duration', __( 'Requested time exceeds the maximum duration.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}
		if ( $duration < $product->get_min_duration() ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_duration', __( 'Requested time is less than the minimum duration.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		/*
		 * Hint: Cost calculation is also checking for availability.
		 * If the booking is not available, it will return an error.
		 */
		$cost = WC_Bookings_Cost_Calculation::calculate_booking_cost( $this->map_to_legacy_posted_data( $request, $duration ), $product );
		if ( is_wp_error( $cost ) ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Requested time is not available.', 'woocommerce-bookings' ), array( 'status' => 409 ) );
		}

		// If tax is enabled, calculate the tax.
		if ( wc_tax_enabled() ) {

			if ( wc_prices_include_tax() ) {

				$tax_rates    = WC_Tax::get_rates( $product->get_tax_class(), $customer );
				$remove_taxes = WC_Tax::calc_tax( $cost, $tax_rates, true );
				$tax          = array_sum( $remove_taxes );

				$cost_without_tax = wc_get_price_excluding_tax(
					$product,
					array(
						'qty'   => 1,
						'price' => $cost,
					)
				);
				$tax              = $cost - $cost_without_tax;
				$cost             = $cost_without_tax;
				$cost_total       = $cost + $tax;
			} else {

				$cost_with_tax = wc_get_price_including_tax(
					$product,
					array(
						'qty'   => 1,
						'price' => $cost,
					)
				);
				$tax           = $cost_with_tax - $cost;
				$cost_total    = $cost_with_tax;
			}
		} else {

			$tax        = 0;
			$cost_total = $cost;
		}

		$response_data = array(
			'request_info'   => array(
				'start' => $start,
				'end'   => $end,
			),
			'product_id'     => $product_id,
			'bookable_name'  => $product->get_name(),
			'type'           => $product->get_type(),
			'resource_id'    => $resource_id,
			'cost_breakdown' => array(
				'cost'  => wc_format_decimal( $cost, wc_get_price_decimals() ),
				'tax'   => wc_format_decimal( $tax, wc_get_price_decimals() ),
				'total' => wc_format_decimal( $cost_total, wc_get_price_decimals() ),
			),
			'cost'           => wc_format_decimal( $cost_total, wc_get_price_decimals() ),
			'currency'       => get_woocommerce_currency(),
		);

		return rest_ensure_response( $response_data );
	}

	/**
	 * Map request data to wc_booking_get_posted_data.
	 *
	 * @see WC_Product_Booking::is_bookable()
	 *
	 * @param WP_REST_Request $request  Request data.
	 * @param int             $duration Duration of the request.
	 * @return array
	 */
	private function map_to_legacy_posted_data( WP_REST_Request $request, int $duration ): array {

		// Parse request data.
		$start       = strtotime( $request['start'] );
		$end         = strtotime( $request['end'] );
		$resource_id = isset( $request['resource_id'] ) ? absint( $request['resource_id'] ) : 0;
		$persons     = isset( $request['persons'] ) ? (array) $request['persons'] : array();
		$customer_id = isset( $request['customer_id'] ) ? absint( $request['customer_id'] ) : 0;

		// Mapper.
		$date  = gmdate( get_option( 'date_format' ), $start );
		$_date = gmdate( 'Y-m-d', $start );
		$time  = gmdate( 'H:i', $start );
		$_time = gmdate( 'G:i', $start );

		$data = array(
			'_start_date'  => $start,
			'_end_date'    => $end,
			'_persons'     => $persons,
			'_customer_id' => $customer_id,
			'_duration'    => $duration,
			'_date'        => $_date,
			'date'         => $date,
			'_time'        => $_time,
			'time'         => $time,
			'_qty'         => 1,
		);

		if ( $resource_id ) {
			$data['_resource_id'] = $resource_id;
		}

		return $data;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce-bookings' ), array( 'status' => rest_authorization_required_code() ) );
		}

		// Most probably included in the manage_woocommerce capability but just in case this is manually changed.
		if ( ! wc_rest_check_post_permissions( 'wc_booking', 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce-bookings' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}
}
