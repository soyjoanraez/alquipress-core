<?php
/**
 * WooCommerce Bookings API
 *
 * @package WooCommerce\Bookings\Rest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API class which registers all the routes.
 */
class WC_Bookings_REST_API {

	const V1_NAMESPACE = 'wc-bookings/v1';
	const V2_NAMESPACE = 'wc-bookings/v2';

	/**
	 * Construct.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_filter( 'woocommerce_rest_bookable_resource_object_trashable', '__return_false', 10, 2 );

		if ( defined( 'WC_BOOKINGS_EXPERIMENTAL_ENABLED' ) && WC_BOOKINGS_EXPERIMENTAL_ENABLED ) {
			add_action( 'rest_api_init', array( $this, 'rest_api_v2_init' ) );
		}
	}

	/**
	 * Initialize the REST API.
	 */
	public function rest_api_init() {
		$controller = new WC_Bookings_REST_Products_Controller();
		$controller->register_routes();

		$controller = new WC_Bookings_REST_Products_Categories_Controller();
		$controller->register_routes();

		$controller = new WC_Bookings_REST_Resources_Controller();
		$controller->register_routes();

		$controller = new WC_Bookings_REST_Booking_Controller();
		$controller->register_routes();

		$controller = new WC_Bookings_REST_Products_Slots_Controller();
		$controller->register_routes();
	}

	/**
	 * Initialize the REST API v2.
	 */
	public function rest_api_v2_init() {

		$controller = new WC_Bookings_REST_Quotes_V2_Controller();
		$controller->register_routes();

		$controller = new WC_Bookings_REST_Booking_V2_Controller();
		$controller->register_routes();

		$controller = new WC_Bookings_REST_Availability_Weekly_Schedule_V2_Controller();
		$controller->register_routes();

		$controller = new WC_Bookings_REST_Availability_Date_Overrides_V2_Controller();
		$controller->register_routes();

		$controller = new WC_Bookings_REST_Team_Members_V2_Controller();
		$controller->register_routes();
	}
}
