<?php
// phpcs:ignoreFile
/**
 * REST API for product objects.
 *
 * Handles requests to the WooCommerce Product REST API.
 *
 * @package WooCommerce\Bookings\Rest\Controller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom REST API product fields.
 *
 * @class WC_Bookings_Product_Rest_API
 */
class WC_Bookings_Product_Rest_API {

	/**
	 * Custom REST API product field names.
	 *
	 * @var array
	 */
	private $product_fields = array(
		'booking_location'      => array( 'get', 'update' ),
		'booking_location_type' => array( 'get', 'update' ),
		'booking_duration'      => array( 'get', 'update' ),
		'booking_duration_unit' => array( 'get', 'update' ),
		'booking_buffer'        => array( 'get', 'update' ),
		'booking_cost'          => array( 'get', 'update' ),
		'booking_resources'     => array( 'get', 'update' ),
	);

	/**
	 * Setup.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_product_fields' ), 0 );
	}

	/**
	 * Register custom REST API fields for product requests.
	 */
	public function register_product_fields() {

		foreach ( $this->product_fields as $field_name => $field_supports ) {

			$args = array(
				'schema' => $this->get_product_field_schema( $field_name ),
			);

			if ( in_array( 'get', $field_supports, true ) ) {
				$args['get_callback'] = array( $this, 'get_product_field_value' );
			}
			if ( in_array( 'update', $field_supports, true ) ) {
				$args['update_callback'] = array( $this, 'update_product_field_value' );
			}

			register_rest_field( 'product', $field_name, $args );
		}
	}

	/**
	 * Gets extended schema properties for products.
	 *
	 * @return array
	 */
	public function get_extended_product_schema() {

		return array(
			'booking_location'      => array(
				'description' => __( 'Booking location. Applicable for booking-type products only.', 'woocommerce-bookings' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'booking_location_type' => array(
				'description' => __( 'Booking location type. Applicable for booking-type products only.', 'woocommerce-bookings' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'enum'        => array( 'in-person', 'online' ),
			),
			'booking_duration'      => array(
				'description' => __( 'Duration. Applicable for booking-type products only.', 'woocommerce-bookings' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			),
			'booking_duration_unit' => array(
				'description' => __( 'Duration unit. Applicable for booking-type products only.', 'woocommerce-bookings' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'enum'        => array( 'minute', 'hour', 'day', 'night', 'month' ),
			),
			'booking_buffer'        => array(
				'description' => __( 'Buffer. Applicable for booking-type products only.', 'woocommerce-bookings' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			),
			'booking_cost'          => array(
				'description' => __( 'Cost. Applicable for booking-type products only.', 'woocommerce-bookings' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
			),
			'booking_resources'     => array(
				'description' => __( 'Resources. Applicable for booking-type products only.', 'woocommerce-bookings' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type' => 'integer',
				),
			),
		);
	}

	/**
	 * Gets schema properties for product fields.
	 *
	 * @param  string $field_name Field name.
	 * @return array
	 */
	public function get_product_field_schema( $field_name ) {

		$extended_schema = $this->get_extended_product_schema();
		$field_schema    = isset( $extended_schema[ $field_name ] ) ? $extended_schema[ $field_name ] : null;

		return $field_schema;
	}

	/**
	 * Gets values for product fields.
	 *
	 * @param  array           $response The response object.
	 * @param  string          $field_name Field name.
	 * @param  WP_REST_Request $request The request object.
	 * @return mixed
	 */
	public function get_product_field_value( $response, $field_name, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$data = null;

		if ( isset( $response['id'] ) ) {
			$product = wc_get_product( $response['id'] );
			$data    = $this->get_product_field( $field_name, $product );
		}

		return $data;
	}

	/**
	 * Updates values for product fields.
	 *
	 * @param  mixed  $field_value  Field value.
	 * @param  mixed  $response     The response object.
	 * @param  string $field_name   Field name.
	 * @return boolean|WP_Error True on success, WP_Error on validation failure.
	 */
	public function update_product_field_value( $field_value, $response, $field_name ) {

		$product_id = false;

		if ( $response instanceof WP_Post ) {
			$product_id = absint( $response->ID );
			$product    = wc_get_product( $product_id );
		} elseif ( $response instanceof WC_Product ) {
			$product_id = $response->get_id();
			$product    = $response;
		}

		if ( ! $product_id ) {
			return true;
		}

		if ( ! is_wc_booking_product( $product ) ) {
			// Silent fail.
			return true;
		}

		if ( 'booking_location' === $field_name ) {
			$product->set_booking_location( $field_value );
			$product->save();
		} elseif ( 'booking_location_type' === $field_name ) {
			$product->set_booking_location_type( $field_value );
			$product->save();
		} elseif ( 'booking_duration' === $field_name ) {
			$product->set_duration( $field_value );
			$product->save();
		} elseif ( 'booking_duration_unit' === $field_name ) {
			$product->set_duration_unit( $field_value );
			$product->save();
		} elseif ( 'booking_buffer' === $field_name ) {
			$product->set_buffer_period( $field_value );
			$product->save();
		} elseif ( 'booking_cost' === $field_name ) {
			$product->set_cost( $field_value );
			$product->save();
		} elseif ( 'booking_resources' === $field_name ) {
			// Validate that resources exist.
			if ( ! empty( $field_value ) && is_array( $field_value ) ) {
				$invalid_ids = array();

				foreach ( $field_value as $resource_id ) {
					if ( ! get_wc_product_booking_resource( absint( $resource_id ) ) ) {
						$invalid_ids[] = $resource_id;
					}
				}

				if ( ! empty( $invalid_ids ) ) {
					throw new WC_REST_Exception(
						'woocommerce_rest_invalid_booking_resources',
						esc_html( sprintf(
							/* translators: %s: comma-separated list of invalid resource IDs */
							__( 'Invalid booking resource IDs: %s', 'woocommerce-bookings' ),
							implode( ', ', $invalid_ids )
						) ),
						400
					);
				}
			}

			$product->set_resource_ids( $field_value );

			if ( empty( $field_value ) ) {
				$product->set_has_resources( false );
			} else {
				$product->set_has_resources( true );
			}

			$product->save();
		}

		return true;
	}

	/**
	 * Gets product data.
	 *
	 * @param  string     $key Field key.
	 * @param  WC_Product $product Product object.
	 * @return mixed
	 */
	public function get_product_field( $key, $product ) {

		if ( ! is_wc_booking_product( $product ) ) {
			return null;
		}

		$value = null;
		switch ( $key ) {

			case 'booking_location':
					$value = $product->get_booking_location();
				break;
			case 'booking_location_type':
					$value = $product->get_booking_location_type();
				break;
			case 'booking_duration':
					$value = $product->get_duration();
				break;
			case 'booking_duration_unit':
					$value = $product->get_duration_unit();
				break;
			case 'booking_buffer':
					$value = $product->get_buffer_period();
				break;
			case 'booking_cost':
					$value = $product->get_cost();
				break;
			case 'booking_resources':
					$value = $product->get_resource_ids();
				break;
		}
		return $value;
	}
}
