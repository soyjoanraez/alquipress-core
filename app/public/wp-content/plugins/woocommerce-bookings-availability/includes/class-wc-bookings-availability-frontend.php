<?php
/**
 * Frontend list block class.
 *
 * @package woocommerce-bookings-availability-frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class.
 *
 * @since 1.0.0
 */
class WC_Bookings_Availability_Frontend {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'load_styles_scripts' ) );
		add_action( 'woocommerce_bookings_rest_slots_get_items', array( $this, 'filter_slots_items' ) );
	}

	/**
	 * Loads styles and scripts.
	 *
	 * @since 1.0.0
	 */
	public function load_styles_scripts() {
		wc_bookings_availability_register_script(
			'woocommerce-bookings-availability-calendar-block-frontend',
			'bookings-calendar-frontend',
			array( 'wc-bookings-availability-common' )
		);

		wc_bookings_availability_register_script(
			'woocommerce-bookings-availability-schedule-block-frontend',
			'bookings-schedule-frontend',
			array( 'wc-bookings-availability-common' )
		);

		wp_register_style(
			'woocommerce-bookings-availability-calendar-block-style-frontend',
			WC_BOOKINGS_AVAILABILITY_PLUGIN_URL . '/build/bookings-calendar-frontend.css',
			array(),
			WC_BOOKINGS_AVAILABILITY_VERSION
		);

		wp_register_style(
			'woocommerce-bookings-availability-schedule-block-style-frontend',
			WC_BOOKINGS_AVAILABILITY_PLUGIN_URL . '/build/bookings-schedule-frontend.css',
			array(),
			WC_BOOKINGS_AVAILABILITY_VERSION
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'woocommerce-bookings-availability-schedule-block-frontend', 'woocommerce-bookings-availability', WC_BOOKINGS_AVAILABILITY_ABSPATH . 'languages' );
			wp_set_script_translations( 'woocommerce-bookings-availability-calendar-block-frontend', 'woocommerce-bookings-availability', WC_BOOKINGS_AVAILABILITY_ABSPATH . 'languages' );
		}
	}

	/**
	 * Filters out multiple booking events that has
	 * more than one resources with same dates.
	 *
	 * @since 1.0.0
	 * @param array $data The payload data from Slots endpoint.
	 * @return array $data Filtered payload.
	 */
	public function filter_slots_items( $data ) {
		$data = array_map( function( $data ) {
			$_data = array_unique( array_column( $data['availability'], 'd' ) );
			$data['availability'] = array_intersect_key( $data['availability'], $_data );

			return $data;
		}, $data );

		return $data;
	}
}

new WC_Bookings_Availability_Frontend();
