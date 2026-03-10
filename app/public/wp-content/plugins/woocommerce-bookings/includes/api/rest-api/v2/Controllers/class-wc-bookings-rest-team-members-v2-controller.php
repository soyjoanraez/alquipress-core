<?php
/**
 * REST API controller for resource team members objects.
 *
 * Handles requests to the /resources/team-members endpoint.
 *
 * @package WooCommerce\Bookings\Rest\Controller
 */

/**
 * REST API Team Members controller class.
 */
class WC_Bookings_REST_Team_Members_V2_Controller extends WC_Bookings_REST_CRUD_Controller {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'resources/team-members';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'bookable_team_member';

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = WC_Bookings_REST_API::V2_NAMESPACE;

	/**
	 * Get object.
	 *
	 * @since 3.0.0
	 *
	 * @param int $id Object ID.
	 *
	 * @return WC_Product_Booking_Team_Member
	 */
	protected function get_object( $id ) {
		return new WC_Product_Booking_Team_Member( $id );
	}

	/**
	 * Prepare a single product output for response.
	 *
	 * @since 3.0.0
	 *
	 * @param  WC_Product_Booking_Team_Member $resource_obj Object data.
	 * @param  WP_REST_Request                $request      Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $resource_obj, $request ) {

		parent::prepare_object_for_response( $resource_obj, $request );
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = array(
			'id'           => $resource_obj->get_id(),
			'status'       => $resource_obj->get_status( $context ),
			'name'         => $resource_obj->get_name( $context ),
			'qty'          => $resource_obj->get_qty( $context ),
			'role'         => $resource_obj->get_role( $context ),
			'email'        => $resource_obj->get_email( $context ),
			'phone_number' => $resource_obj->get_phone_number( $context ),
			'image_id'     => $resource_obj->get_image_id( $context ),
			'image_url'    => '',
			'description'  => $resource_obj->get_description( $context ),
			'note'         => $resource_obj->get_note( $context ),
		);

		// Get attachment URL from image_id.
		if ( $data['image_id'] ) {
			$image_url         = wp_get_attachment_image_url( $data['image_id'], 'thumbnail' );
			$data['image_url'] = $image_url ? $image_url : '';
		}

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $resource_obj, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @since 3.0.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Data          $resource_obj Object data.
		 * @param WP_REST_Request  $request Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}_object", $response, $resource_obj, $request );
	}

	/**
	 * Prepare objects query.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$query_args                = parent::prepare_objects_query( $request );
		$query_args['post_status'] = $request['status'];

		return $query_args;
	}

	/**
	 * Validate the request for updating a resource.
	 *
	 * @since 3.0.0
	 *
	 * @param array $request The request data.
	 *
	 * @return WP_Error|true Returns a WP_Error object if the request is invalid, otherwise returns true.
	 */
	private function validate_update_request( $request ) {

		if ( isset( $request['image_id'] ) && ! empty( $request['image_id'] ) ) {
			$attachment = get_post( $request['image_id'] );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				return new WP_Error( 'woocommerce_bookings_resources_rest_invalid_image_id', __( 'Invalid image ID. Must be a valid attachment ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $request['qty'] ) && (int) $request['qty'] < 0 ) {
			return new WP_Error( 'woocommerce_bookings_resources_rest_invalid_qty', __( 'Quantity must be a positive number.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Prepare a single resource for create or update.
	 *
	 * @since 3.0.0
	 *
	 * @param array $request Request object.
	 * @param bool  $creating If creating a new object.
	 *
	 * @return WP_Error|true Returns a WP_Error object if the request is invalid, otherwise returns true.
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {

		/**
		 * Handle the WC_Product_Booking_Team_Member object.
		 */
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		if ( ! $creating && ! $id ) {
			return new WP_Error( 'woocommerce_bookings_resources_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		if ( $id ) {
			$resource = get_wc_product_booking_resource( $id );
			if ( ! $resource ) {
				return new WP_Error( 'woocommerce_bookings_resources_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}
		} else {
			$resource = new WC_Product_Booking_Team_Member();
		}

		// When creating a new resource, name is required and should not be empty.
		if ( $creating && ( ! isset( $request['name'] ) || empty( $request['name'] ) ) ) {
			return new WP_Error( 'woocommerce_bookings_resources_rest_invalid_name', __( 'Name is required.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		// When updating a resource, name is optional and should not be empty.
		if ( ! $creating && isset( $request['name'] ) && empty( $request['name'] ) ) {
			return new WP_Error( 'woocommerce_bookings_resources_rest_invalid_name', __( 'Name cannot be empty.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
		}

		/**
		 * Handle validation and sanity checks.
		 */
		$validation = $this->validate_update_request( $request );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		/**
		 * Set props.
		 */
		if ( isset( $request['name'] ) ) {
			$resource->set_name( $request['name'] );
		}

		if ( isset( $request['qty'] ) ) {
			$resource->set_qty( $request['qty'] );
		}

		if ( isset( $request['role'] ) ) {
			$resource->set_role( $request['role'] );
		}

		if ( isset( $request['email'] ) ) {
			$resource->set_email( $request['email'] );
		}

		if ( isset( $request['phone_number'] ) ) {
			$resource->set_phone_number( $request['phone_number'] );
		}

		if ( isset( $request['image_id'] ) ) {
			$resource->set_image_id( $request['image_id'] );
		}

		if ( isset( $request['description'] ) ) {
			$resource->set_description( sanitize_textarea_field( $request['description'] ) );
		}

		if ( isset( $request['note'] ) ) {
			$resource->set_note( sanitize_textarea_field( $request['note'] ) );
		}

		if ( isset( $request['status'] ) ) {
			$resource->set_status( $request['status'] );
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * @since 3.0.0
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param  WC_Data          $resource  Object object.
		 * @param  WP_REST_Request  $request   Request object.
		 * @param  bool             $creating  Whether creating a new object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $resource, $request, $creating );
	}

	/**
	 * Get the resource schema, conforming to JSON Schema.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'resource',
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce-bookings' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'       => array(
					'description' => __( 'Status of the resource.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'enum'        => WC_Product_Booking_Resource::get_allowed_statuses(),
					'context'     => array( 'view', 'edit' ),
				),
				'name'         => array(
					'description' => __( 'Name of resource.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'qty'          => array(
					'description' => __( 'Quantity of resource.', 'woocommerce-bookings' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'role'         => array(
					'description' => __( 'Role of resource.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'email'        => array(
					'description' => __( 'Email of resource.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'view', 'edit' ),
				),
				'phone_number' => array(
					'description' => __( 'Phone number of resource.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'image_id'     => array(
					'description' => __( 'Attachment ID of resource.', 'woocommerce-bookings' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'image_url'    => array(
					'description' => __( 'URL of the resource image.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description'  => array(
					'description' => __( 'Description of resource.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'note'         => array(
					'description' => __( 'Note for resource.', 'woocommerce-bookings' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the collection params.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params           = parent::get_collection_params();
		$params['status'] = array(
			'description' => __( 'Status of the team members.', 'woocommerce-bookings' ),
			'type'        => 'string',
			'enum'        => WC_Product_Booking_Resource::get_allowed_statuses(),
			'default'     => 'publish',
		);

		return $params;
	}
}
