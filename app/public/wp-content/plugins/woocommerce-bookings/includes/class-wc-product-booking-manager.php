<?php
/**
 * Handles product booking transitions.
 *
 * @package WooCommerce Bookings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Product_Booking_Manager class.
 *
 * Handles product booking transitions and lifecycle management.
 */
class WC_Product_Booking_Manager {

	/**
	 * Constructor sets up actions
	 */
	public function __construct() {
		add_action( 'wp_trash_post', array( __CLASS__, 'pre_trash_delete_handler' ), 10, 1 );
		add_action( 'before_delete_post', array( __CLASS__, 'pre_trash_delete_handler' ), 10, 1 );
	}

	/**
	 * Filters whether a bookable product deletion should take place.
	 * If there are Bookings linked to it, do not allow deletion.
	 *
	 * @since 1.10.9
	 *
	 * @param int $post_id  Post ID.
	 */
	public static function pre_trash_delete_handler( $post_id ) {
		if ( ! $post_id ) {
			return;
		}

		$post_type    = get_post_type( $post_id );
		$message_type = __( 'bookable product', 'woocommerce-bookings' );

		if ( 'product' === $post_type ) {
			$product = wc_get_product( $post_id );

			if ( is_wc_booking_product( $product ) ) {
				$booking_ids = WC_Booking_Data_Store::get_booking_ids_by(
					array(
						'object_id' => $post_id,
						'limit'     => 1,
						'status'    => get_wc_booking_statuses( 'fully_booked' ),
					)
				);
			}
		} elseif ( in_array( $post_type, wc_booking_get_product_resource_post_types(), true ) ) {
			$message_type = __( 'resource', 'woocommerce-bookings' );

			$booking_ids = WC_Booking_Data_Store::get_booking_ids_by(
				array(
					'object_id'   => $post_id,
					'object_type' => 'resource',
					'limit'       => 1,
					'status'      => get_wc_booking_statuses( 'fully_booked' ),
				)
			);
		}

		if ( ! empty( $booking_ids ) ) {
			$message = sprintf(
				/* translators: %s is the type of object being deleted */
				__( 'You cannot trash/delete a %s that has Bookings associated with it.', 'woocommerce-bookings' ),
				$message_type
			);

			$message .= ' ';
			$message .= sprintf(
				/* translators: %1$s and %2$s are the opening and closing tags for the link to the bookings */
				__( 'Please %1$sclick here%2$s to see the associated bookings.', 'woocommerce-bookings' ),
				'<a href="' . esc_url( admin_url( 'edit.php?s&post_status=all&post_type=wc_booking&filter_bookings=' . $post_id ) ) . '">',
				'</a>'
			);

			$message .= '<br/>';
			$message .= '<a href="https://woocommerce.com/document/introduction-to-woocommerce-bookings/woocommerce-bookings-store-manager-guide/creating-a-bookable-product/#deleting-bookable-products">';
			$message .= __( 'Please visit our documentation for more information', 'woocommerce-bookings' );
			$message .= '</a>.';

			wp_die( wp_kses_post( $message ) );
		}

		if ( 'product' === $post_type ) {
			WC_Bookings_Cache::delete_booking_slots_transient( $post_id );
		}
	}
}
