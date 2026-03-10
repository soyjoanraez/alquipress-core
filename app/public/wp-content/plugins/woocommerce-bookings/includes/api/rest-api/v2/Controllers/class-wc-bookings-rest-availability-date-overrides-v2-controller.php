<?php
/**
 * REST API for date override availability objects.
 *
 * Handles requests to the /date-overrides endpoint.
 *
 * @package WooCommerce\Bookings\Rest\Controller
 */

/**
 * REST API date overrides controller class.
 */
class WC_Bookings_REST_Availability_Date_Overrides_V2_Controller extends WC_REST_Data_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'date-overrides';

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = WC_Bookings_REST_API::V2_NAMESPACE;

	/**
	 * Register the routes for date overrides.
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
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Individual day routes: /date-overrides/{id}.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d{4}-\d{2}-\d{2})',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'time_slots' => array(
							'description' => __( 'Array of time slots for this day.', 'woocommerce-bookings' ),
							'type'        => 'array',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'start' => array(
										'type'        => 'string',
										'description' => __( 'Start time in HH:MM format.', 'woocommerce-bookings' ),
										'pattern'     => '^([01]?[0-9]|2[0-3]):[0-5][0-9]$',
									),
									'end'   => array(
										'type'        => 'string',
										'description' => __( 'End time in HH:MM format.', 'woocommerce-bookings' ),
										'pattern'     => '^([01]?[0-9]|2[0-3]):[0-5][0-9]$',
									),
								),
								'required'   => array( 'start', 'end' ),
							),
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * Check if a request has access to create items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * Check if a given request has access to read a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * Check if a request has access to update items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * Check if a request has access to delete items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * Get date overrides availability rules.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$data_store = WC_Data_Store::load( 'booking-global-availability' );
		$results    = $data_store->get_time_slots_grouped( 'date_override', true );

		$date_overrides = array();

		foreach ( $results as $row ) {
			$date   = $row['from_date'];
			$ranges = $row['ranges'];

			if ( empty( $ranges ) ) {
				continue;
			}

			// Parse the ranges string into individual time slots.
			$rules       = array();
			$range_pairs = explode( ', ', $ranges );
			foreach ( $range_pairs as $range_pair ) {
				$parts = explode( '-', $range_pair );

				if ( count( $parts ) < 3 ) {
					continue;
				}

				$bookable = $parts[2];

				// For fully non-bookable dates, we need to store an empty array.
				if ( 'no' === $bookable ) {
					continue;
				}

				$rules[] = array(
					'start' => $parts[0],
					'end'   => $parts[1],
				);
			}

			$date_overrides[] = array(
				'id'         => $date,
				'time_slots' => $rules,
			);
		}

		// Handle pagination parameters.
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );

		if ( $per_page ) {

			$page = max( $page, 1 );

			// Calculate pagination.
			$total_items = count( $date_overrides );
			$total_pages = (int) ceil( $total_items / $per_page );
			$offset      = ( $page - 1 ) * $per_page;

			// Get the items for the current page.
			$paginated_items = array_slice( $date_overrides, $offset, $per_page );
			$response        = rest_ensure_response( $paginated_items );

			// Add pagination headers.
			$response->header( 'X-WP-Total', $total_items );
			$response->header( 'X-WP-TotalPages', $total_pages );
		} else {
			$response = rest_ensure_response( $date_overrides );
		}

		return $response;
	}

	/**
	 * Get time slots for a specific date from database.
	 *
	 * @param int $date Date.
	 * @return array Time slots for the specific date.
	 */
	private function get_time_slots_for_date( $date ) {
		$data_store = WC_Data_Store::load( 'booking-global-availability' );
		$filters    = array(
			array(
				'key'     => 'rule_type',
				'value'   => 'date_override',
				'compare' => '=',
			),
			array(
				'key'     => 'bookable',
				'value'   => 'yes',
				'compare' => '=',
			),
		);

		$availability_rules = $data_store->get_all( $filters, $date, $date );

		$time_slots = array();
		foreach ( $availability_rules as $availability_rule ) {
			$time_slots[] = array(
				'start' => $availability_rule->get_from_range(),
				'end'   => $availability_rule->get_to_range(),
			);
		}

		return $time_slots;
	}

	/**
	 * Get all rules for a specific date.
	 *
	 * @param int $date Date.
	 * @return array Rules for the specific date.
	 */
	private function get_rules_for_date( $date ) {
		$data_store = WC_Data_Store::load( 'booking-global-availability' );
		$filters    = array(
			array(
				'key'     => 'rule_type',
				'value'   => 'date_override',
				'compare' => '=',
			),
		);

		return $data_store->get_all( $filters, $date, $date );
	}

	/**
	 * Delete rules for a specific date.
	 *
	 * @param int $date Date.
	 * @return bool True on success, false on failure.
	 */
	private function delete_rules_for_date( $date ) {

		$data_store = WC_Data_Store::load( 'booking-global-availability' );
		$filters    = array(
			array(
				'key'     => 'rule_type',
				'value'   => 'date_override',
				'compare' => '=',
			),
		);

		$availability_rules = $data_store->get_all( $filters, $date, $date );

		foreach ( $availability_rules as $availability_rule ) {
			$data_store->delete( $availability_rule );
		}

		return true;
	}

	/**
	 * Create item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$date_override = $request->get_params();

		if ( ! is_array( $date_override ) ) {
			$date_override = array();
		}

		if ( ! isset( $date_override['id'] ) ) {
			return new WP_Error( 'missing_date', __( 'Date ID is required.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		if ( ! isset( $date_override['time_slots'] ) ) {
			return new WP_Error( 'missing_time_slots', __( 'Time slots are required.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		$date       = $date_override['id'];
		$time_slots = $date_override['time_slots'];

		$validation_error = WC_Bookings_Availability_Utils::validate_time_slots( $time_slots );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		try {
			// Delete existing rules for this specific date.
			$this->delete_rules_for_date( $date );

			$availability_rules = \WC_Bookings_Availability_Utils::map_date_overrides_rules(
				array(
					array(
						'date'       => $date,
						'time_slots' => $time_slots,
					),
				)
			);

			$data_store = WC_Data_Store::load( 'booking-global-availability' );

			foreach ( $availability_rules as $availability_rule ) {
				$availability_object = new WC_Global_Availability();
				$availability_object->set_range_type( $availability_rule['type'] );
				$availability_object->set_rule_type( $availability_rule['rule_type'] );
				$availability_object->set_from_date( $availability_rule['from_date'] );
				$availability_object->set_to_date( $availability_rule['to_date'] );
				$availability_object->set_from_range( $availability_rule['from'] );
				$availability_object->set_to_range( $availability_rule['to'] );
				$availability_object->set_bookable( $availability_rule['bookable'] );
				$availability_object->set_priority( $availability_rule['priority'] );
				$data_store->create( $availability_object );
			}

			$response = rest_ensure_response(
				array(
					'id'         => $date,
					'time_slots' => $time_slots,
				)
			);
			$response->set_status( 201 );
			return $response;

		} catch ( Exception $e ) {
			return new WP_Error( 'rest_global_availability_save_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Get a single date's schedule.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$date    = $request['id'];
		$results = $this->get_time_slots_for_date( $date );

		return rest_ensure_response(
			array(
				'id'         => $date,
				'time_slots' => $results,
			)
		);
	}

	/**
	 * Update a single date's schedule.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$date       = $request['id'];
		$time_slots = $request->get_param( 'time_slots' );

		if ( ! is_array( $time_slots ) ) {
			return new WP_Error( 'invalid_time_slots', __( 'Time slots must be an array.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		// Validate the time slots for this specific date.
		$validation_error = WC_Bookings_Availability_Utils::validate_time_slots( $time_slots );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		try {

			$existing_rules = $this->get_rules_for_date( $date );

			if ( empty( $existing_rules ) ) {
				return $this->create_item( $request );
			}

			// Delete existing rules for this specific date.
			$this->delete_rules_for_date( $date );

			$availability_rules = \WC_Bookings_Availability_Utils::map_date_overrides_rules(
				array(
					array(
						'date'       => $date,
						'time_slots' => $time_slots,
					),
				)
			);

			$data_store = WC_Data_Store::load( 'booking-global-availability' );

			foreach ( $availability_rules as $availability_rule ) {
				$availability_object = new WC_Global_Availability();
				$availability_object->set_range_type( $availability_rule['type'] );
				$availability_object->set_rule_type( $availability_rule['rule_type'] );
				$availability_object->set_from_date( $availability_rule['from_date'] );
				$availability_object->set_to_date( $availability_rule['to_date'] );
				$availability_object->set_from_range( $availability_rule['from'] );
				$availability_object->set_to_range( $availability_rule['to'] );
				$availability_object->set_bookable( $availability_rule['bookable'] );
				$availability_object->set_priority( $availability_rule['priority'] );
				$data_store->create( $availability_object );
			}

			return rest_ensure_response(
				array(
					'id'         => $date,
					'time_slots' => $time_slots,
				)
			);

		} catch ( Exception $e ) {
			return new WP_Error( 'rest_weekly_schedule_update_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Delete a single date's schedule.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$date = $request['id'];

		try {

			$existing_rules = $this->get_rules_for_date( $date );

			if ( empty( $existing_rules ) ) {
				return new WP_Error( 'date_not_found', __( 'No rules found for this date.', 'woocommerce-bookings' ), array( 'status' => 404 ) );
			}

			// Get the time slots before deletion so we can return them.
			$time_slots = $this->get_time_slots_for_date( $date );

			// Prepare the previous data to return.
			$previous = rest_ensure_response(
				array(
					'id'         => $date,
					'time_slots' => $time_slots,
				)
			);

			// Delete the rules for this specific date.
			$this->delete_rules_for_date( $date );

			// Return 200 with the deleted item data instead of 204 No Content.
			// This helps WordPress core-data properly invalidate caches.
			$response = new WP_REST_Response();
			$response->set_data(
				array(
					'deleted'  => true,
					'previous' => $previous->get_data(),
				)
			);

			return $response;

		} catch ( Exception $e ) {
			return new WP_Error( 'rest_date_override_delete_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Get the date overrides schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'date_overrides',
			'type'       => 'object',
			'required'   => array( 'id', 'time_slots' ),
			'properties' => array(
				'id'         => array(
					'type'        => 'string',
					'description' => __( 'Date in YYYY-MM-DD format.', 'woocommerce-bookings' ),
					'pattern'     => '^(\d{4}-\d{2}-\d{2})$',
				),
				'time_slots' => array(
					'type'        => 'array',
					'description' => __( 'Time slots for this date.', 'woocommerce-bookings' ),
					'items'       => array(
						'type'       => 'object',
						'required'   => array( 'start', 'end' ),
						'properties' => array(
							'start' => array(
								'type'        => 'string',
								'description' => __( 'Start time in HH:MM format.', 'woocommerce-bookings' ),
							),
							'end'   => array(
								'type'        => 'string',
								'description' => __( 'End time in HH:MM format.', 'woocommerce-bookings' ),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
