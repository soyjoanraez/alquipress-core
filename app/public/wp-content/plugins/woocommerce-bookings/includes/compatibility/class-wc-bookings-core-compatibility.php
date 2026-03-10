<?php
/**
 * WC_Bookings_Core_Compatibility class
 *
 * @package  WooCommerce Bookings
 * @since    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Functions related to WC core backwards compatibility.
 *
 * @class    WC_Bookings_Core_Compatibility
 * @version  3.0.0
 */
class WC_Bookings_Core_Compatibility {
	/*
	|--------------------------------------------------------------------------
	| Version check methods.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Cache 'gte' comparison results.
	 *
	 * @var array
	 */
	private static $is_wc_version_gte = array();

	/**
	 * Cache 'gt' comparison results.
	 *
	 * @var array
	 */
	private static $is_wc_version_gt = array();

	/**
	 * Cache 'gt' comparison results for WP version.
	 *
	 * @var    array
	 */
	private static $is_wp_version_gt = array();

	/**
	 * Cache 'gte' comparison results for WP version.
	 *
	 * @var    array
	 */
	private static $is_wp_version_gte = array();

	/**
	 * Current REST request stack.
	 * An array containing WP_REST_Request instances.
	 *
	 * @var array
	 */
	private static $requests = array();

	/**
	 * Constructor.
	 */
	public static function init() {
		// Save current rest request. Is there a better way to get it?
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'save_rest_request' ), 10, 3 );
		add_filter( 'woocommerce_hydration_dispatch_request', array( __CLASS__, 'save_hydration_request' ), 10, 2 );
		add_filter( 'rest_request_after_callbacks', array( __CLASS__, 'pop_rest_request' ), PHP_INT_MAX );
		add_filter( 'woocommerce_hydration_request_after_callbacks', array( __CLASS__, 'pop_rest_request' ), PHP_INT_MAX );

		// Refactoring of Analytics in WC 9.3 deprecated Query class (https://github.com/woocommerce/woocommerce/pull/49425).
		if ( version_compare( WC_VERSION, '9.3', '<' ) && ! class_exists( 'Automattic\WooCommerce\Admin\API\Reports\GenericQuery' ) ) {
			class_alias( 'Automattic\WooCommerce\Admin\API\Reports\Query', 'Automattic\WooCommerce\Admin\API\Reports\GenericQuery' );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Callbacks.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Pops the current request from the execution stack.
	 *
	 * @param  WP_REST_Response $response Response.
	 * @return mixed
	 */
	public static function pop_rest_request( $response ) {
		if ( ! empty( self::$requests ) && is_array( self::$requests ) ) {
			array_pop( self::$requests );
		}

		return $response;
	}

	/**
	 * Saves the current hydration request.
	 *
	 * @param  mixed           $result Result.
	 * @param  WP_REST_Request $request Request.
	 * @return mixed
	 */
	public static function save_hydration_request( $result, $request ) {
		if ( ! is_array( self::$requests ) ) {
			self::$requests = array();
		}

		self::$requests[] = $request;
		return $result;
	}

	/**
	 * Saves the current rest request.
	 *
	 * @param  mixed           $result Result.
	 * @param  WP_REST_Server  $server Server.
	 * @param  WP_REST_Request $request Request.
	 * @return mixed
	 */
	public static function save_rest_request( $result, $server, $request ) {
		if ( ! is_array( self::$requests ) ) {
			self::$requests = array();
		}

		self::$requests[] = $request;
		return $result;
	}

	/*
	|--------------------------------------------------------------------------
	| Utilities.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Helper method to get the version of the currently installed WooCommerce.
	 *
	 * @return string
	 */
	public static function get_wc_version() {
		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than or equal to $version.
	 *
	 * @param  string $version Version.
	 * @return boolean
	 */
	public static function is_wc_version_gte( $version ) {
		if ( ! isset( self::$is_wc_version_gte[ $version ] ) ) {
			self::$is_wc_version_gte[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>=' );
		}
		return self::$is_wc_version_gte[ $version ];
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than $version.
	 *
	 * @param  string $version Version.
	 * @return boolean
	 */
	public static function is_wc_version_gt( $version ) {
		if ( ! isset( self::$is_wc_version_gt[ $version ] ) ) {
			self::$is_wc_version_gt[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
		}
		return self::$is_wc_version_gt[ $version ];
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than or equal to $version.
	 *
	 * @param  string $version Version.
	 * @return boolean
	 */
	public static function is_wp_version_gt( $version ) {
		if ( ! isset( self::$is_wp_version_gt[ $version ] ) ) {
			global $wp_version;
			self::$is_wp_version_gt[ $version ] = $wp_version && version_compare( WC_BOOKINGS_VERSION, $version, '>' );
		}
		return self::$is_wp_version_gt[ $version ];
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than or equal to $version.
	 *
	 * @param  string $version Version.
	 * @return boolean
	 */
	public static function is_wp_version_gte( $version ) {
		if ( ! isset( self::$is_wp_version_gte[ $version ] ) ) {
			global $wp_version;
			self::$is_wp_version_gte[ $version ] = $wp_version && version_compare( WC_BOOKINGS_VERSION, $version, '>=' );
		}
		return self::$is_wp_version_gte[ $version ];
	}

	/**
	 * Whether this is a Store/REST API request.
	 *
	 * @since  8.4.0
	 *
	 * @return boolean
	 */
	public static function is_api_request() {
		return self::is_store_api_request() || self::is_rest_api_request();
	}

	/**
	 * Returns the current Store/REST API request or false.
	 *
	 * @since  8.4.0
	 *
	 * @return WP_REST_Request|false
	 */
	public static function get_api_request() {
		if ( empty( self::$requests ) || ! is_array( self::$requests ) ) {
			return false;
		}

		return end( self::$requests );
	}

	/**
	 * Whether this is a Store API request.
	 *
	 * @since  8.4.0
	 *
	 * @param  string $route Route.
	 * @return boolean
	 */
	public static function is_store_api_request( $route = '' ) {

		// Check the request URI.
		$request = self::get_api_request();

		if ( false !== $request && strpos( $request->get_route(), 'wc/store' ) !== false ) {
			if ( '' === $route || strpos( $request->get_route(), $route ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/*
	|--------------------------------------------------------------------------
	| Compatibility wrappers.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Back-compat wrapper for 'is_rest_api_request'.
	 *
	 * @return boolean
	 */
	public static function is_rest_api_request() {

		if ( ! isset( $_SERVER['REQUEST_URI'] ) || false === strpos( wc_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ), rest_get_url_prefix() ) ) {
			return false;
		}

		if ( false !== self::get_api_request() ) {
			return true;
		}

		return method_exists( WC(), 'is_rest_api_request' ) ? WC()->is_rest_api_request() : defined( 'REST_REQUEST' );
	}

	/**
	 *
	 * Whether this is a Store Editor REST API request.
	 *
	 * @param  string $route Route.
	 * @return boolean
	 */
	public static function is_block_editor_api_request( $route = '' ) {

		if ( ! self::is_rest_api_request() ) {
			return false;
		}

		$request = self::get_api_request();

		if ( false !== $request && strpos( $request->get_route(), '/pages/' ) !== false ) {
			if ( '' === $route || strpos( $request->get_route(), $route ) !== false ) {
				return true;
			}
		}

		return false;
	}
}

WC_Bookings_Core_Compatibility::init();
