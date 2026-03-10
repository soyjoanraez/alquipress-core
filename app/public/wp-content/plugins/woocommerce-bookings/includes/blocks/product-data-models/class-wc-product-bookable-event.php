<?php
/**
 * Main data model for the Event product type.
 *
 * @package WooCommerce\Bookings
 * @since 3.0.0
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Product_Bookable_Event class.

 * @since 3.0.0
 * @version 3.0.0
 */
class WC_Product_Bookable_Event extends WC_Product_Booking {

	/**
	 * Public constant to get the event product type.
	 */
	const PRODUCT_TYPE = 'bookable-event';

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
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function single_add_to_cart_text() {
		/**
		 * Filter the add to cart button text for the event product.
		 *
		 * @param string $text The add to cart button text.
		 * @param WC_Product_Bookable_Event $this The event product object.
		 * @since 3.0.0
		 * @version 3.0.0
		 */
		return apply_filters( 'woocommerce_bookable_event_single_add_to_cart_text', __( 'Buy tickets', 'woocommerce-bookings' ), $this );
	}
}
