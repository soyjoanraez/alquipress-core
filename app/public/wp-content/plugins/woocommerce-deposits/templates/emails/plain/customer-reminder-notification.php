<?php
/**
 * Customer Notification: Manual payment needed (plain text).
 *
 * @package WooCommerce Deposits
 */

// phpcs:ignoreFile WooCommerce.Commenting.CommentHooks.MissingSinceComment -- hooks are documented in WooCommerce core.
 defined( 'ABSPATH' ) || exit;

 echo esc_html( $email_heading . "\n" );

 echo "\n----------------------------------------\n\n";

 echo wp_strip_all_tags(
	sprintf(
		/* translators: %s: Customer first name */
		__( 'Hi %s.', 'woocommerce-deposits' ),
		$order->get_billing_first_name()
	)
);

echo "\n\n";

echo wp_strip_all_tags(
	sprintf(
		/*
		 * Developers: Match the HTML version of this string.
		 *
		 * For the plain text version of the email, the HTML tags are removed
		 * but the common string is kept to reduce the number of strings requiring
		 * translation.
		 */
		// translators: %1$s: human readable time difference (eg 3 days, 1 day), %2$s: date in local format.
		__(
			'Your payment is due in %1$s — that’s <strong>%2$s</strong>.',
			'woocommerce-deposits'
		),
		$deposits_due_date_relative,
		$deposits_due_date
	)
);

echo "\n\n";

echo wp_strip_all_tags( __( 'This payment will not be processed automatically.', 'woocommerce-deposits' ) ) . "\n\n";

echo wp_strip_all_tags(
	sprintf(
		/* translators: %1$s Order pay link */
		__( 'Your order details are below, with a link to make a payment when you’re ready: %1$s', 'woocommerce-deposits' ),
		"\n" . esc_url( $order->get_checkout_payment_url() )
	)
);

echo "\n----------------------------------------\n\n";

/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
