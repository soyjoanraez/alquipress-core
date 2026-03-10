<?php
/**
 * Helper functions.
 *
 * @package woocommerce-bookings-availability
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate default block parameters.
 *
 * @since 1.0.0
 */
function wc_bookings_availability_default_block_parameters() {
	/**
	 * Filters the Booking availability block parameters.
	 *
	 * @since 1.1.16
	 * @param array $parameters Default block parameters.
	 */
	return apply_filters(
		'woocommerce_bookings_availability_block_parameters',
		array(
			'nonces'        => array(
				'add_booking_to_cart' => wp_create_nonce( 'add-booking-to-cart' ),
			),
			'ajax_url'            => WC_AJAX::get_endpoint(),
			'checkout_url'        => wc_get_page_permalink( 'checkout' ),
			'start_of_week'       => get_option( 'start_of_week' ),
			'timezone_conversion' => wc_should_convert_timezone(),
			'server_timezone'     => wc_booking_get_timezone_string(),
			'time_format_moment'  => wc_bookings_convert_to_moment_format( get_option( 'time_format' ) ),
			'time_format'         => get_option( 'time_format' ),
			'display_timezone'    => class_exists( 'WC_Bookings_Timezone_Settings' ) ? WC_Bookings_Timezone_Settings::get( 'display_timezone' ) : 'yes',
		)
	);
}

/**
 * Registers JS script asset.
 *
 * @param string $handle   Script handle.
 * @param string $filename Script filename.
 * @param array  $deps     Script dependencies.
 */
function wc_bookings_availability_register_script( $handle = '', $filename = '', $deps = array() ) {
	// Sanitize filename to allow only alphanumeric, dash, and underscore characters.
	$filename = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $filename );

	$script_url        = WC_BOOKINGS_AVAILABILITY_PLUGIN_URL . '/build/' . $filename . '.js';
	$script_asset_path = WC_BOOKINGS_AVAILABILITY_ABSPATH . 'build/' . $filename . '.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path // nosemgrep: audit.php.lang.security.file.inclusion-arg -- already sanitized above.
		: array(
			'dependencies' => array(),
			'version'      => WC_BOOKINGS_AVAILABILITY_MIN_BOOKINGS_VERSION,
		);

	wp_register_script(
		$handle,
		$script_url,
		array_merge(
			$script_asset['dependencies'],
			$deps
		),
		$script_asset['version'],
		true
	);
}
