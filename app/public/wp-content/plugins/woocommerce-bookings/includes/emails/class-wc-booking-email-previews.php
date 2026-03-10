<?php
/**
 * Bookings Email Preview Class
 *
 * @package woocommerce-bookings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bookings Email Preview Class
 */
class WC_Booking_Email_Previews {
	/**
	 * Init method.
	 */
	public function init() {
		add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'prepare_email_for_preview' ) );
	}

	/**
	 * Prepare email for preview
	 *
	 * @param WC_Email $email Email object.
	 * @return WC_Email
	 */
	public function prepare_email_for_preview( $email ) {
		// Only modify booking emails.
		if ( false === strpos( get_class( $email ), 'Booking' ) ) {
			return $email;
		}

		// Create a Bookings object.
		$booking = new WC_Booking();
		$booking->set_id( 999999 );
		$booking->set_order_id( $email->object->get_ID() );
		$booking->set_start( 1716940800 );
		$booking->set_end( 1717027200 );
		$booking->set_status( 'email_preview' );

		$email->object = $booking;

		// Modify the email heading.
		$email->subject = str_replace( '{product_title}', 'Dummy Product', $email->subject );

		// Modify the email content.
		if ( isset( $email->settings['subject'] ) ) {
			$email->settings['subject'] = str_replace( '{product_title}', 'Dummy Product', $email->settings['subject'] );
		}

		return $email;
	}
}
