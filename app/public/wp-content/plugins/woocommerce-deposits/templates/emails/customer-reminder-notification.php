<?php
/**
 * Customer Notification: Manual payment needed.
 *
 * @package WooCommerce Deposits
 */

// phpcs:ignoreFile WooCommerce.Commenting.CommentHooks.MissingSinceComment -- hooks are documented in WooCommerce core.
 defined( 'ABSPATH' ) || exit;

/**
 * Core WooCommerce Email Template action.
 *
 * @hooked WC_Emails::email_header() Output the email header.
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>

<?php
		echo esc_html(
			sprintf(
				/* translators: %s: Customer first name */
				__( 'Hi %s.', 'woocommerce-deposits' ),
				$order->get_billing_first_name()
			)
		);
		?>
	</p>


	<p>
		<?php
		echo wp_kses(
			sprintf(
				// translators: %1$s: human readable time difference (eg 3 days, 1 day), %2$s: date in local format.
				__(
					'Your payment is due in %1$s — that’s <strong>%2$s</strong>.',
					'woocommerce-deposits'
				),
				$deposits_due_date_relative,
				$deposits_due_date
			),
			array( 'strong' => array() )
		);
		?>
	</p>

	<p>
		<strong>
		<?php
			esc_html_e( 'This payment will not be processed automatically.', 'woocommerce-deposits' );
		?>
		</strong>
	</p>

	<p>
		<?php
		printf(
			wp_kses(
				/* translators: %1$s Order pay link */
				__( 'Your order details are below, with a link to make a payment when you’re ready: %1$s', 'woocommerce-deposits' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			'<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay for this order', 'woocommerce-deposits' ) . '</a>'
		);
		?>
	</p>

<?php

/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer.
 */
do_action( 'woocommerce_email_footer', $email );
