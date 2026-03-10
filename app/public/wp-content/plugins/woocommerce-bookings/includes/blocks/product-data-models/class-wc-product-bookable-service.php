<?php
/**
 * Main data model for the Service product type.
 *
 * @package WooCommerce\Bookings
 * @since 3.0.0
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Product_Bookable_Service class.

 * @since 3.0.0
 * @version 3.0.0
 */
class WC_Product_Bookable_Service extends WC_Product_Booking {

	/**
	 * Public constant to get the service product type.
	 */
	const PRODUCT_TYPE = 'bookable-service';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'product-booking';

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return self::PRODUCT_TYPE;
	}

	/**
	 * Service products are always virtual.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function is_virtual( $context = 'view' ) {
		return true;
	}

	/**
	 * Service products duration type is always fixed.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_duration_type( $context = 'view' ) {
		return 'fixed';
	}

	/**
	 * Service products duration unit is always minutes.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_duration_unit( $context = 'view' ) {
		return 'minute';
	}

	/**
	 * Services are unavailable by default. They depend on the availability of the store.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_default_date_availability( $context = 'view' ) {
		return 'non-available';
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function single_add_to_cart_text() {
		/**
		 * Filter the add to cart button text for the service product.
		 *
		 * @param string $text The add to cart button text.
		 * @param WC_Product_Bookable_Service $this The service product object.
		 * @since 3.0.0
		 * @version 3.0.0
		 */
		return apply_filters( 'woocommerce_bookable_service_single_add_to_cart_text', __( 'Book now', 'woocommerce-bookings' ), $this );
	}
}
