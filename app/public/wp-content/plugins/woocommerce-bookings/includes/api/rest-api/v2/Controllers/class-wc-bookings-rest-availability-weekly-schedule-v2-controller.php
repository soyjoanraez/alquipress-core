<?php
/**
 * REST API for weekly schedule objects.
 *
 * Handles requests to the /weekly-schedule endpoint.
 *
 * @package WooCommerce\Bookings\Rest\Controller
 */

/**
 * REST API Weekly Schedule controller class.
 */
class WC_Bookings_REST_Availability_Weekly_Schedule_V2_Controller extends WC_REST_Data_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'weekly-schedule';

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = WC_Bookings_REST_API::V2_NAMESPACE;

	/**
	 * Register the routes for weekly schedule.
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
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Individual day routes: /weekly-schedule/{day}.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<day>[1-7])',
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
	 * Get weekly schedule rules.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$data_store = WC_Data_Store::load( 'booking-global-availability' );
		$results    = $data_store->get_time_slots_grouped( 'weekly', false );

		$weekly_schedule = array(
			array(
				'id'         => 1,
				'time_slots' => array(),
			),
			array(
				'id'         => 2,
				'time_slots' => array(),
			),
			array(
				'id'         => 3,
				'time_slots' => array(),
			),
			array(
				'id'         => 4,
				'time_slots' => array(),
			),
			array(
				'id'         => 5,
				'time_slots' => array(),
			),
			array(
				'id'         => 6,
				'time_slots' => array(),
			),
			array(
				'id'         => 7,
				'time_slots' => array(),
			),
		);

		foreach ( $results as $row ) {
			// Extract day number from range_type (e.g., 'time:1' -> '1').
			$day    = str_replace( 'time:', '', $row['range_type'] );
			$ranges = $row['ranges'];

			if ( empty( $ranges ) ) {
				continue;
			}

			// Parse the ranges string into individual time slots.
			$rules       = array();
			$range_pairs = explode( ', ', $ranges );

			foreach ( $range_pairs as $range_pair ) {
				$parts = explode( '-', $range_pair );

				if ( count( $parts ) < 2 ) {
					continue;
				}

				$rules[] = array(
					'start' => $parts[0],
					'end'   => $parts[1],
				);
			}

			$weekly_schedule[ $day - 1 ]['time_slots'] = $rules;
		}

		return rest_ensure_response( $weekly_schedule );
	}

	/**
	 * Get rules for a specific day from database.
	 *
	 * @param int $day Day of the week (1-7).
	 * @return array Time slots for the specific day.
	 */
	private function get_time_slots_for_day( $day ) {

		$data_store = WC_Data_Store::load( 'booking-global-availability' );
		$filters    = array(
			array(
				'key'     => 'rule_type',
				'value'   => 'weekly',
				'compare' => '=',
			),
			array(
				'key'     => 'range_type',
				'value'   => "time:{$day}",
				'compare' => '=',
			),
			array(
				'key'     => 'bookable',
				'value'   => 'yes',
				'compare' => '=',
			),
		);

		$availability_rules = $data_store->get_all( $filters );

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
	 * Delete rules for a specific day from database.
	 *
	 * @param int $day Day of the week (1-7).
	 * @return bool True on success, false on failure.
	 */
	private function delete_rules_for_day( $day ) {
		$data_store = WC_Data_Store::load( 'booking-global-availability' );
		$filters    = array(
			array(
				'key'     => 'rule_type',
				'value'   => 'weekly',
				'compare' => '=',
			),
			array(
				'key'     => 'range_type',
				'value'   => "time:{$day}",
				'compare' => '=',
			),
		);

		$availability_rules = $data_store->get_all( $filters );

		foreach ( $availability_rules as $availability_rule ) {
			$data_store->delete( $availability_rule );
		}

		return true;
	}

	/**
	 * Get a single day's schedule.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$day      = (int) $request['day'];
		$day_data = array();
		$results  = $this->get_time_slots_for_day( $day );

		// Convert results to the expected format.
		foreach ( $results as $result ) {
			$day_data[] = array(
				'start' => $result['start'],
				'end'   => $result['end'],
			);
		}

		return rest_ensure_response(
			array(
				'id'         => $day,
				'time_slots' => $day_data,
			)
		);
	}

	/**
	 * Update a single day's schedule.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$day        = (int) $request['day'];
		$time_slots = $request->get_param( 'time_slots' );

		if ( ! is_array( $time_slots ) ) {
			return new WP_Error( 'invalid_time_slots', __( 'Time slots must be an array.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		// Validate the time slots for this specific day.
		$validation_error = WC_Bookings_Availability_Utils::validate_time_slots( $time_slots );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		try {
			// Delete existing rules for this specific day.
			$this->delete_rules_for_day( $day );

			$availability_rules = WC_Bookings_Availability_Utils::map_weekly_availability_rules( array( $day => $time_slots ) );

			$data_store = WC_Data_Store::load( 'booking-global-availability' );

			foreach ( $availability_rules as $availability_rule ) {
				$availability_object = new WC_Global_Availability();
				$availability_object->set_range_type( $availability_rule['type'] );
				$availability_object->set_rule_type( $availability_rule['rule_type'] );
				$availability_object->set_from_range( $availability_rule['from'] );
				$availability_object->set_to_range( $availability_rule['to'] );
				$availability_object->set_bookable( $availability_rule['bookable'] );
				$availability_object->set_priority( $availability_rule['priority'] );
				$data_store->create( $availability_object );
			}

			return rest_ensure_response(
				array(
					'id'         => $day,
					'time_slots' => $time_slots,
				)
			);

		} catch ( Exception $e ) {
			return new WP_Error( 'rest_weekly_schedule_update_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Get the weekly schedule schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'weekly_schedule',
			'type'       => 'object',
			'properties' => array(
				'1' => array(
					'description' => __( 'Monday time slots.', 'woocommerce-bookings' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
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
				'2' => array(
					'description' => __( 'Tuesday time slots.', 'woocommerce-bookings' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
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
				'3' => array(
					'description' => __( 'Wednesday time slots.', 'woocommerce-bookings' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
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
				'4' => array(
					'description' => __( 'Thursday time slots.', 'woocommerce-bookings' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
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
				'5' => array(
					'description' => __( 'Friday time slots.', 'woocommerce-bookings' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
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
				'6' => array(
					'description' => __( 'Saturday time slots.', 'woocommerce-bookings' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
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
				'7' => array(
					'description' => __( 'Sunday time slots.', 'woocommerce-bookings' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
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
