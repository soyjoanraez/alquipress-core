<?php
/**
 * REST API for bookings objects.
 *
 * Handles requests to the /bookings endpoint.
 *
 * @package WooCommerce\Bookings\Rest\Controller
 */

/**
 * REST API Products controller class.
 */
class WC_Bookings_REST_Booking_V2_Controller extends WC_Bookings_REST_CRUD_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'bookings';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'wc_booking';

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = WC_Bookings_REST_API::V2_NAMESPACE;

	/**
	 * Get object.
	 *
	 * @param int $booking_id Object ID.
	 *
	 * @return WC_Booking|false
	 */
	protected function get_object( $booking_id ) {
		$booking = get_wc_booking( $booking_id );
		if ( ! $booking ) {
			return false;
		}

		return $booking;
	}

	/**
	 * Get objects (i.e. Bookings).
	 *
	 * @param array $query_args Query args.
	 *
	 * @return array Bookings data.
	 */
	protected function get_objects( $query_args ) {
		/**
		 * Get all public post statuses list and include a few.
		 * This is done to include `wc-partial-payment` for now.
		 *
		 * Fix https://github.com/woocommerce/woocommerce-bookings/issues/3082
		 */
		if ( ! isset( $query_args['post_status'] ) && empty( $query_args['post_status'] ) ) {
			$post_statuses             = array_values( get_post_stati( array( 'public' => true ) ) );
			$include_statuses          = array( 'wc-partial-payment' );
			$query_args['post_status'] = array_merge( $post_statuses, $include_statuses );
		}

		return parent::get_objects( $query_args );
	}

	/**
	 * Prepare a single booking output for response.
	 *
	 * @param WC_Booking      $booking  Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $booking, $request ) {
		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$cost     = wc_format_decimal( (float) $booking->get_cost( $context ), wc_get_price_decimals() );
		$currency = get_woocommerce_currency();
		if ( ! empty( $booking->get_order_id() ) ) {
			$order = wc_get_order( $booking->get_order_id() );
			if ( $order ) {
				$currency = $order->get_currency();
			}
		}

		$data = array(
			'id'                       => $booking->get_id(),
			'start'                    => $booking->get_start( $context ),
			'end'                      => $booking->get_end( $context ),
			'all_day'                  => $booking->get_all_day( $context ),
			'status'                   => $booking->get_status( $context ),
			'cost'                     => $cost,
			'currency'                 => $currency,
			'customer_id'              => $booking->get_customer_id( $context ),
			'product_id'               => $booking->get_product_id( $context ),
			'resource_id'              => $booking->get_resource_id( $context ),
			'date_created'             => $booking->get_date_created( $context ),
			'date_modified'            => $booking->get_date_modified( $context ),
			'date_cancelled'           => $booking->get_date_cancelled( $context ),
			'google_calendar_event_id' => $booking->get_google_calendar_event_id( $context ),
			'order_id'                 => $booking->get_order_id( $context ),
			'order_item_id'            => $booking->get_order_item_id( $context ),
			'parent_id'                => $booking->get_parent_id( $context ),
			'person_counts'            => $booking->get_person_counts( $context ),
			'local_timezone'           => $booking->get_local_timezone( $context ),
			'note'                     => $booking->get_note( $context ),
		);

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $booking, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * @since 3.0.0
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Data          $booking  Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}_object", $response, $booking, $request );
	}

	/**
	 * Validate the request for updating a booking.
	 *
	 * @since 3.0.0
	 *
	 * @param array $request  The request data.
	 * @param bool  $creating Whether the request is for creating a new booking.
	 * @return WP_Error|true Returns a WP_Error object if the request is invalid, otherwise returns true.
	 */
	private function validate_update_request( $request, $creating = false ) {

		if ( $creating ) {

			if ( ! isset( $request['start'] ) || ! isset( $request['end'] ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Start and end date are required.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			if ( strtotime( $request['start'] ) < time() ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Start date cannot be in the past.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			if ( ! isset( $request['product_id'] ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Product ID is required.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			$product = wc_get_product( $request['product_id'] );
			if ( ! $product ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_product_id', __( 'Invalid product ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			// Check only if there is an issue for not setting the resource id here.
			// Business logic should be handled in the main prepare_object_for_database method.
			if ( $product->has_resources() && ! $product->is_resource_assignment_type( 'automatic' ) && ! isset( $request['resource_id'] ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_resource_id', __( 'Resource ID is required.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $request['status'] ) ) {
			if ( ! in_array( $request['status'], $this->get_allowed_statuses(), true ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_status', __( 'Invalid status.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}
		}

		return true;
	}

	/**
	 * Prepare a single booking for create or update.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {

		/**
		 * Handle the WC_Booking object.
		 */
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		if ( ! $creating && ! $id ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_booking_id', __( 'Invalid booking ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		if ( $id ) {
			$booking = get_wc_booking( $id );

			if ( ! $booking ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking_id', __( 'Invalid booking ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}
		} else {
			$booking = new WC_Booking();
		}

		/**
		 * Handle validation and sanity checks.
		 */
		$validation = $this->validate_update_request( $request, $creating );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		/**
		 * Set props.
		 */
		if ( isset( $request['start'] ) ) {
			$booking->set_start( $request['start'] );
		}

		if ( isset( $request['end'] ) ) {
			$booking->set_end( $request['end'] );
		}

		if ( isset( $request['status'] ) ) {
			$booking->set_status( $request['status'] );
		}

		if ( isset( $request['note'] ) ) {
			$booking->set_note( $request['note'] );
		}

		if ( isset( $request['customer_id'] ) ) {
			$booking->set_customer_id( absint( $request['customer_id'] ) );
		}

		if ( isset( $request['product_id'] ) ) {
			$booking->set_product_id( absint( $request['product_id'] ) );
		}

		$product = wc_get_product( $booking->get_product_id() );
		if ( ! $product ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_product_id', __( 'Invalid product ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		if ( isset( $request['resource_id'] ) ) {

			if ( ! $product->has_resources() ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_resource_id', __( 'Invalid resource ID. Bookable Product does not have resources.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			if ( $product->has_resources() && $product->is_resource_assignment_type( 'automatic' ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_resource_id', __( 'Invalid resource ID. Bookable Product has automatic resource assignment.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			if ( $product->has_resources() && ! in_array( $request['resource_id'], $product->get_resource_ids(), true ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_resource_id', __( 'Invalid resource ID. Resource ID does not exist for this product.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			$booking->set_resource_id( absint( $request['resource_id'] ) );
		}

		// Assign an available resource automatically.
		if ( $creating && $product->has_resources() && $product->is_resource_assignment_type( 'automatic' ) ) {

			// Hint: This needs to accept a resource id = 0. Null won't work.
			// Hint: This is yet another place where we need to check for availability.
			$available_slots = wc_bookings_get_total_available_bookings_for_range( $product, $booking->get_start(), $booking->get_end(), 0 );
			if ( empty( $available_slots ) || ! is_array( $available_slots ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Requested time is not available.', 'woocommerce-bookings' ), array( 'status' => 409 ) );
			}

			$booking->set_resource_id( absint( current( array_keys( $available_slots ) ) ) );
		}

		if ( isset( $request['order_id'] ) ) {
			$booking->set_order_id( absint( $request['order_id'] ) );
		}

		if ( isset( $request['order_item_id'] ) ) {
			// Hint: This could be part of the set_order_item_id method.
			$order = wc_get_order( $booking->get_order_id() );
			if ( ! $order ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_order_id', __( 'Invalid order ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			$order_items    = $order->get_items();
			$order_item_id  = absint( $request['order_item_id'] );
			$order_item_ids = array_map(
				function ( $item ) {
					return $item->get_id();
				},
				$order_items
			);
			if ( ! in_array( $order_item_id, $order_item_ids, true ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_order_item_id', __( 'Invalid order item ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			$booking->set_order_item_id( $order_item_id );
		}

		if ( isset( $request['local_timezone'] ) ) {
			$booking->set_local_timezone( sanitize_text_field( $request['local_timezone'] ) );
		}

		if ( is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				if ( ! isset( $meta['key'] ) || ! isset( $meta['value'] ) ) {
					continue;
				}

				$booking->update_meta_data( $meta['key'], wc_clean( $meta['value'] ), isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		/**
		 * Availability and cost.
		 */
		$changes                     = array_keys( $booking->get_changes() );
		$requires_availability_check = ! empty( $changes ) && array_intersect( array( 'product_id', 'resource_id', 'start', 'end' ), $changes );

		if ( $creating || $requires_availability_check ) {
			/**
			 * Blocks and duration.
			 */
			// Hint: This also validates min/max date range. When out of range, it returns empty array.
			$blocks   = $product->get_blocks_in_range( $booking->get_start(), $booking->get_end(), array(), $booking->get_resource_id() );
			$duration = count( $blocks );

			if ( empty( $duration ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_duration', __( 'Invalid duration.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			if ( $duration < $product->get_duration() ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Requested time is less than the minimum duration.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			if ( $duration < $product->get_min_duration() ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Requested time is less than the minimum duration.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			if ( $duration > $product->get_max_duration() ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Requested time exceeds the maximum duration.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $request['cost'] ) ) {
			$booking->set_cost( $request['cost'] );
		} elseif ( $creating && empty( $booking->get_cost() ) ) {
			/*
			 * Hint: Cost calculation is also checking for availability.
			 * If the booking is not available, it will return an error.
			 */
			$cost = WC_Bookings_Cost_Calculation::calculate_booking_cost( $this->map_to_legacy_posted_data( $booking, $duration ), $product );
			if ( is_wp_error( $cost ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Requested time is not available.', 'woocommerce-bookings' ), array( 'status' => 409 ) );
			}
			$requires_availability_check = false;
			$booking->set_cost( $cost );
		}

		if ( $requires_availability_check ) {
			/*
			 * If the cost is not set automatically (see above), we need to check availability.
			 */
			if ( ! isset( $blocks ) ) {
				$blocks = $product->get_blocks_in_range( $booking->get_start(), $booking->get_end(), array(), $booking->get_resource_id() );
			}
			$available_blocks = wc_bookings_get_time_slots( $product, $blocks, array(), $booking->get_resource_id(), $booking->get_start(), $booking->get_end() );

			if ( empty( $available_blocks ) ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_booking', __( 'Requested time is not available.', 'woocommerce-bookings' ), array( 'status' => 409 ) );
			}
		}

		/**
		 * Mark all day bookings.
		 *
		 * Hint: All day bookings are those that start and end at the start and end of the day.
		 */
		if ( $booking->get_start() === strtotime( gmdate( 'Y-m-d 00:00:00', $booking->get_start() ) ) && $booking->get_end() === strtotime( gmdate( 'Y-m-d 23:59:59', $booking->get_end() ) ) ) {
			$booking->set_all_day( true );
		}

		/**
		 * Order creation (if creating a new booking and no order ID or order item ID).
		 */
		if ( $creating && ( ! $booking->get_order_id() || ! $booking->get_order_item_id() ) ) {
			$this->handle_order_creation( $booking );
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 *  @since 3.0.0
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param WC_Data         $booking  Object object.
		 * @param WP_REST_Request $request  Request object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $booking, $request, $creating );
	}

	/**
	 * Handle order creation.
	 *
	 * @param WC_Booking $booking Booking object.
	 * @return bool|WP_Error
	 */
	private function handle_order_creation( WC_Booking $booking ) {

		// Sanity check. We don't want to create an order if the booking isn't new.
		if ( $booking->get_id() ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_booking_id', __( 'Cannot create order for existing booking.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		// Check if no order, then create one.
		// Check if no order item id, then create one. If order is created, then force-create the order item too.
		$order_created = false;
		$order_id      = $booking->get_order_id();
		if ( ! $order_id ) {
			// Create order.
			$order_id = $this->create_order( $booking );
			if ( is_wp_error( $order_id ) ) {
				return $order_id;
			}

			$booking->set_order_id( $order_id );
			$order_created = true;
		}

		$order_line_item_created = false;
		if ( $order_created || ! $booking->get_order_item_id() ) {
			$order_item_id = $this->create_order_item( $booking );
			if ( is_wp_error( $order_item_id ) ) {
				return $order_item_id;
			}
			$order_line_item_created = true;
			$booking->set_order_item_id( $order_item_id );
		}

		if ( $order_created || $order_line_item_created ) {
			// Calculate the order totals with taxes.
			$order = wc_get_order( $order_id );
			if ( is_a( $order, 'WC_Order' ) ) {
				$order->calculate_totals( wc_tax_enabled() );
			}
		}

		return true;
	}

	/**
	 * Create an order.
	 *
	 * @param WC_Booking $booking Booking object.
	 * @return int|WP_Error
	 */
	private function create_order( WC_Booking $booking ) {
		$order = new WC_Order();
		$order->set_customer_id( $booking->get_customer_id() );
		$order->set_created_via( 'bookings' );
		$order_id = $order->save();

		if ( ! $order_id ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_order_id', __( 'Error: Could not create order', 'woocommerce-bookings' ) );
		}

		return $order_id;
	}

	/**
	 * Create an order item.
	 *
	 * @param WC_Booking $booking Booking object.
	 * @return int|WP_Error
	 */
	private function create_order_item( WC_Booking $booking ) {
		$product = wc_get_product( $booking->get_product_id() );
		if ( ! is_wc_booking_product( $product ) ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_product_id', __( 'Invalid product ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		$order_id = $booking->get_order_id();
		if ( ! $order_id ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_order_id', __( 'Invalid order ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		$item_id = wc_add_order_item(
			$order_id,
			array(
				'order_item_name' => $product->get_title(),
				'order_item_type' => 'line_item',
			)
		);

		if ( ! $item_id ) {
			return new WP_Error( 'woocommerce_bookings_rest_invalid_item_id', __( 'Error: Could not create item', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $booking->get_customer_id() ) ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return new WP_Error( 'woocommerce_bookings_rest_invalid_order_id', __( 'Invalid order ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}
			$keys = array(
				'first_name',
				'last_name',
				'company',
				'address_1',
				'address_2',
				'city',
				'state',
				'postcode',
				'country',
				'phone',
			);

			$types       = $product->is_virtual() ? array( 'billing' ) : array( 'shipping', 'billing' );
			$customer_id = $booking->get_customer_id();
			foreach ( $types as $type ) {
				foreach ( $keys as $key ) {
					$value = (string) get_user_meta( $customer_id, $type . '_' . $key, true );
					$order->update_meta_data( '_' . $type . '_' . $key, $value );
				}
			}
			$order->save();
		}

		$booking_cost = $booking->get_cost();
		if ( wc_prices_include_tax() ) {
			$base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class() );
			$base_taxes     = WC_Tax::calc_tax( $booking->get_cost(), $base_tax_rates, true );
			$booking_cost   = $booking->get_cost() - array_sum( $base_taxes );
		}

		// Add line item meta.
		wc_update_order_item_meta( $item_id, '_qty', 1 );
		wc_update_order_item_meta( $item_id, '_tax_class', $product->get_tax_class() );
		wc_update_order_item_meta( $item_id, '_product_id', $product->get_id() );
		wc_update_order_item_meta( $item_id, '_variation_id', '' );
		wc_update_order_item_meta( $item_id, '_line_subtotal', $booking_cost );
		wc_update_order_item_meta( $item_id, '_line_total', $booking_cost );
		wc_update_order_item_meta( $item_id, '_line_tax', 0 );
		wc_update_order_item_meta( $item_id, '_line_subtotal_tax', 0 );

		return $item_id;
	}

	/**
	 * Map booking data to wc_booking_get_posted_data.
	 *
	 * @param WC_Booking $booking Booking object.
	 * @param int        $duration Duration of the request.
	 * @return array
	 */
	private function map_to_legacy_posted_data( WC_Booking $booking, int $duration ): array {
		$date  = gmdate( get_option( 'date_format' ), $booking->get_start( 'view', true ) );
		$_date = gmdate( 'Y-m-d', $booking->get_start() );
		$time  = gmdate( 'H:i', $booking->get_start( 'view', true ) );
		$_time = gmdate( 'G:i', $booking->get_start() );

		$data = array(
			'_start_date'  => $booking->get_start(),
			'_end_date'    => $booking->get_end(),
			'_persons'     => $booking->get_person_counts(),
			'_customer_id' => $booking->get_customer_id(),
			'_duration'    => $duration,
			'_date'        => $_date,
			'date'         => $date,
			'_time'        => $_time,
			'time'         => $time,
			'_qty'         => 1,
		);

		if ( $booking->get_resource_id() ) {
			$data['_resource_id'] = $booking->get_resource_id();
		}

		return $data;
	}

	/**
	 * Get allowed statuses.
	 *
	 * @return array Array of allowed statuses.
	 */
	private function get_allowed_statuses(): array {
		return array_values(
			array_unique(
				array_merge(
					get_wc_booking_statuses( '', false ),
					get_wc_booking_statuses( 'user', false ),
					get_wc_booking_statuses( 'cancel', false ),
					get_wc_booking_statuses( 'scheduled', false ),
				)
			)
		);
	}

	/**
	 * Get the booking schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$allowed_statuses = $this->get_allowed_statuses();
		$schema           = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => __( 'Unique identifier for the object.', 'woocommerce-bookings' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'start'          => array(
					'description' => __( 'Start date and time of the booking.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'end'            => array(
					'description' => __( 'End date and time of the booking.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'status'         => array(
					'description' => __( 'Status of the booking.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'enum'        => $allowed_statuses,
					'context'     => array( 'view', 'edit' ),
				),
				'cost'           => array(
					'description' => __( 'Cost of the booking.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'customer_id'    => array(
					'description' => __( 'ID of the customer who made the booking.', 'woocommerce-bookings' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'product_id'     => array(
					'description' => __( 'ID of the product that the booking is for.', 'woocommerce-bookings' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'resource_id'    => array(
					'description' => __( 'ID of the resource that the booking is for.', 'woocommerce-bookings' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'order_id'       => array(
					'description' => __( 'ID of the order that the booking is for.', 'woocommerce-bookings' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'order_item_id'  => array(
					'description' => __( 'ID of the order item that the booking is for.', 'woocommerce-bookings' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'local_timezone' => array(
					'description' => __( 'Local timezone of the booking.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'note'           => array(
					'description' => __( 'Booking note.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'   => array(
					'description' => __( 'Date created of the booking.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
				),
				'date_modified'  => array(
					'description' => __( 'Date modified of the booking.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
				),
				'date_cancelled' => array(
					'description' => __( 'Date cancelled of the booking. Applicable only for cancelled bookings.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
				),
				'meta_data'      => array(
					'description' => __( 'Meta data.', 'woocommerce-bookings' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID.', 'woocommerce-bookings' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => __( 'Meta key.', 'woocommerce-bookings' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', 'woocommerce-bookings' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Modify query args to support filtering and searching.
	 *
	 * @param WP_REST_Request $request  Request.
	 * @return array Query args for WP_Query.
	 */
	protected function prepare_objects_query( $request ) {

		$args = parent::prepare_objects_query( $request );

		// Filter bookings by product IDs.
		if ( ! empty( $request['product'] ) ) {
			$product_ids = $request['product'];

			if ( ! is_array( $product_ids ) ) {
				$product_ids = array( $product_ids );
			}

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			$args['meta_query'][] = array(
				'key'     => '_booking_product_id',
				'value'   => $product_ids,
				'compare' => 'IN',
			);
		}

		// Filter bookings by excluding product IDs.
		if ( ! empty( $request['product_exclude'] ) ) {
			$exclude_product_ids = $request['product_exclude'];

			if ( ! is_array( $exclude_product_ids ) ) {
				$exclude_product_ids = array( $exclude_product_ids );
			}

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			$args['meta_query'][] = array(
				'key'     => '_booking_product_id',
				'value'   => $exclude_product_ids,
				'compare' => 'NOT IN',
			);
		}

		// Filter bookings by customer IDs.
		if ( ! empty( $request['customer'] ) ) {
			$customer_ids = $request['customer'];

			if ( ! is_array( $customer_ids ) ) {
				$customer_ids = array( $customer_ids );
			}

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			$args['meta_query'][] = array(
				'key'     => '_booking_customer_id',
				'value'   => $customer_ids,
				'compare' => 'IN',
			);
		}

		// Filter bookings by excluding customer IDs.
		if ( ! empty( $request['customer_exclude'] ) ) {
			$exclude_customer_ids = $request['customer_exclude'];

			if ( ! is_array( $exclude_customer_ids ) ) {
				$exclude_customer_ids = array( $exclude_customer_ids );
			}

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			$args['meta_query'][] = array(
				'key'     => '_booking_customer_id',
				'value'   => $exclude_customer_ids,
				'compare' => 'NOT IN',
			);
		}

		// Filter bookings by resource IDs.
		if ( ! empty( $request['resource'] ) ) {
			$resource_ids = $request['resource'];

			if ( ! is_array( $resource_ids ) ) {
				$resource_ids = array( $resource_ids );
			}

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			$args['meta_query'][] = array(
				'key'     => '_booking_resource_id',
				'value'   => $resource_ids,
				'compare' => 'IN',
			);
		}

		// Filter bookings by excluding resource IDs.
		if ( ! empty( $request['resource_exclude'] ) ) {
			$exclude_resource_ids = $request['resource_exclude'];

			if ( ! is_array( $exclude_resource_ids ) ) {
				$exclude_resource_ids = array( $exclude_resource_ids );
			}

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			$args['meta_query'][] = array(
				'key'     => '_booking_resource_id',
				'value'   => $exclude_resource_ids,
				'compare' => 'NOT IN',
			);
		}

		// Filter bookings by start date.
		if ( ! empty( $request['start_date'] ) ) {
			$date_value     = sanitize_text_field( $request['start_date'] );
			$date_timestamp = strtotime( $date_value );

			if ( $date_timestamp ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
				$args['meta_query'][] = array(
					'key'     => '_booking_start',
					'value'   => gmdate( 'YmdHi', $date_timestamp ),
					'compare' => 'LIKE',
				);
			}
		}

		// Filter bookings by start date before.
		if ( ! empty( $request['start_date_before'] ) ) {
			$date_before_value     = sanitize_text_field( $request['start_date_before'] );
			$date_before_timestamp = strtotime( $date_before_value );

			if ( $date_before_timestamp ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
				$args['meta_query'][] = array(
					'key'     => '_booking_start',
					'value'   => gmdate( 'YmdHis', $date_before_timestamp ),
					'compare' => '<',
				);
			}
		}

		// Filter bookings by start date after.
		if ( ! empty( $request['start_date_after'] ) ) {
			$date_after_value     = sanitize_text_field( $request['start_date_after'] );
			$date_after_timestamp = strtotime( $date_after_value );

			if ( $date_after_timestamp ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
				$args['meta_query'][] = array(
					'key'     => '_booking_start',
					'value'   => gmdate( 'YmdHis', $date_after_timestamp ),
					'compare' => '>',
				);
			}
		}

		// Filter bookings by status.
		if ( ! empty( $request['booking_status'] ) ) {
			$statuses = $request['booking_status'];

			if ( ! is_array( $statuses ) ) {
				$statuses = array( $statuses );
			}

			// Validate statuses against allowed booking statuses.
			$allowed_statuses = $this->get_allowed_statuses();
			$valid_statuses   = array_intersect( $statuses, $allowed_statuses );

			if ( ! empty( $valid_statuses ) ) {
				$args['post_status'] = $valid_statuses;
			}
		}

		// Filter bookings by excluding statuses.
		if ( ! empty( $request['booking_status_exclude'] ) ) {
			$exclude_statuses = $request['booking_status_exclude'];

			if ( ! is_array( $exclude_statuses ) ) {
				$exclude_statuses = array( $exclude_statuses );
			}

			// Validate statuses against allowed booking statuses.
			$allowed_statuses       = $this->get_allowed_statuses();
			$valid_exclude_statuses = array_intersect( $exclude_statuses, $allowed_statuses );

			if ( ! empty( $valid_exclude_statuses ) ) {
				// Remove excluded statuses from the query.
				$current_post_statuses = isset( $args['post_status'] ) ? $args['post_status'] : $this->get_allowed_statuses();
				$args['post_status']   = array_diff( $current_post_statuses, $valid_exclude_statuses );
			}
		}

		// Filter bookings by cost.
		if ( isset( $request['cost'] ) ) {
			$cost_param    = (array) $request['cost']; // List of cost values supports single and between.
			$cost_value    = $cost_param[0] ?? 0;
			$cost_operator = '=';

			// Map rest api operators to the operators the query expects. These are the ones defined in the enum.
			switch ( $request['cost_operator'] ?? 'is' ) {
				case 'isNot':
					$cost_operator = '!=';
					break;
				case 'lessThan':
					$cost_operator = '<';
					break;
				case 'greaterThan':
					$cost_operator = '>';
					break;
				case 'lessThanOrEqual':
					$cost_operator = '<=';
					break;
				case 'greaterThanOrEqual':
					$cost_operator = '>=';
					break;
				case 'between':
					$cost_operator = 'BETWEEN';
					$cost_value    = array( $cost_param[0] ?? 0, $cost_param[1] ?? 0 );
					break;
			}

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			$args['meta_query'][] = array(
				'key'     => '_booking_cost',
				'value'   => $cost_value,
				'compare' => $cost_operator,
				'type'    => 'NUMERIC',
			);
		}

		// Search.
		if ( ! empty( $args['s'] ) ) {
			$booking_ids = wc_booking_search( $args['s'] );
			if ( ! empty( $booking_ids ) ) {
				unset( $args['s'] );
				$args['post__in'] = array_merge( $booking_ids, array( 0 ) );
			}
		}

		// Handle booking-specific sorting.
		if ( ! empty( $request['orderby'] ) ) {
			$orderby = $request['orderby'];
			$order   = ! empty( $request['order'] ) ? strtoupper( $request['order'] ) : 'DESC';

			if ( 'start_date' === $orderby ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$args['meta_query'] = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
				$args['orderby']    = 'meta_value';
				$args['meta_key']   = '_booking_start'; //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['order']      = $order;
			} elseif ( 'cost' === $orderby ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$args['meta_query'] = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
				$args['orderby']    = 'meta_value';
				$args['meta_key']   = '_booking_cost'; //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['order']      = $order;
			}
		}

		return $args;
	}

	/**
	 * Get collection parameters.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['orderby']['enum'][] = 'start_date';
		$params['orderby']['enum'][] = 'cost';

		$params['product'] = array(
			'description'       => __( 'Filter bookings by product ID(s).', 'woocommerce-bookings' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => array( $this, 'sanitize_integer_array' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['product_exclude'] = array(
			'description'       => __( 'Exclude bookings by product ID(s).', 'woocommerce-bookings' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => array( $this, 'sanitize_integer_array' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['customer'] = array(
			'description'       => __( 'Filter bookings by customer ID(s).', 'woocommerce-bookings' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => array( $this, 'sanitize_integer_array' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['customer_exclude'] = array(
			'description'       => __( 'Exclude bookings by customer ID(s).', 'woocommerce-bookings' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => array( $this, 'sanitize_integer_array' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['resource'] = array(
			'description'       => __( 'Filter bookings by resource ID(s).', 'woocommerce-bookings' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => array( $this, 'sanitize_integer_array' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['resource_exclude'] = array(
			'description'       => __( 'Exclude bookings by resource ID(s).', 'woocommerce-bookings' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => array( $this, 'sanitize_integer_array' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['start_date'] = array(
			'description'       => __( 'Filter bookings by start date (ISO 8601 format: YYYY-MM-DDTHH:MM:SS).', 'woocommerce-bookings' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['start_date_before'] = array(
			'description'       => __( 'Filter bookings by start date before (ISO 8601 format: YYYY-MM-DDTHH:MM:SS).', 'woocommerce-bookings' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['start_date_after'] = array(
			'description'       => __( 'Filter bookings by start date after (ISO 8601 format: YYYY-MM-DDTHH:MM:SS).', 'woocommerce-bookings' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['booking_status'] = array(
			'description'       => __( 'Filter bookings by status(s).', 'woocommerce-bookings' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => $this->get_allowed_statuses(),
			),
			'sanitize_callback' => array( $this, 'sanitize_string_array' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['booking_status_exclude'] = array(
			'description'       => __( 'Exclude bookings by status(s).', 'woocommerce-bookings' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => $this->get_allowed_statuses(),
			),
			'sanitize_callback' => array( $this, 'sanitize_string_array' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['cost'] = array(
			'description'       => __( 'Limit result set to bookings with specific cost amounts. For between operators, list two values.', 'woocommerce-bookings' ),
			'type'              => array( 'number', 'array' ),
			'items'             => array(
				'type' => 'number',
			),
			'sanitize_callback' => 'wp_parse_list',
		);

		$params['cost_operator'] = array(
			'description'       => __( 'The comparison operator to use for cost filtering.', 'woocommerce-bookings' ),
			'type'              => 'string',
			'enum'              => array( 'between', 'is', 'isNot', 'lessThan', 'greaterThan', 'lessThanOrEqual', 'greaterThanOrEqual' ),
			'default'           => 'is',
			'validate_callback' => function ( $param, $request, $key ) {
				$valid = rest_validate_request_arg( $param, $request, $key );

				if ( true === $valid && 'between' === $param ) {
					$cost_field = wp_parse_list( $request->get_param( 'cost' ) );

					if ( ! is_array( $cost_field ) || count( $cost_field ) !== 2 ) {
						return new WP_Error( 'rest_invalid_param', __( 'Cost value must be an array with exactly 2 numbers for between operators.', 'woocommerce-bookings' ), array( 'status' => WP_Http::BAD_REQUEST ) );
					}
				}

				return $valid;
			},
		);
		return $params;
	}

	/**
	 * Sanitize callback for integer array parameters.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array Array of sanitized integers.
	 */
	public function sanitize_integer_array( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'absint', $value );
		}
		return array( absint( $value ) );
	}

	/**
	 * Sanitize callback for string array parameters.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array Array of sanitized strings.
	 */
	public function sanitize_string_array( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}
		return array( sanitize_text_field( $value ) );
	}
}
