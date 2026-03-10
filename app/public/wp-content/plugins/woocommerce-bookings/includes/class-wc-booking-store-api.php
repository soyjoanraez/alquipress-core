<?php
/**
 * WC_Booking_Store_API class
 *
 * @package  WooCommerce Bookings
 * @since    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends the store public API with booking related data for each bookingable product.
 *
 * @version 3.0.0
 */
class WC_Booking_Store_API {

	/**
	 * Bootstraps the class and hooks required data.
	 */
	public function __construct() {
		// Cart Store API.
		new WC_Bookings_Cart_Store_API();
		if ( defined( 'WC_BOOKINGS_EXPERIMENTAL_ENABLED' ) && WC_BOOKINGS_EXPERIMENTAL_ENABLED ) {
			new WC_Bookings_Availability_Store_API();
		}
	}
}
