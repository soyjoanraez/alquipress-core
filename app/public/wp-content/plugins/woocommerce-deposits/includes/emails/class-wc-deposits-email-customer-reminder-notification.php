<?php
/**
 * WooCommerce Deposits
 *
 * @package     WC_Deposits/Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Customer Reminder Notification Email.
 *
 * An email sent to the customer to remind them of an upcoming payment.
 *
 * @class       WC_Deposits_Email_Customer_Reminder_Notification
 * @extends     WC_Email
 */
class WC_Deposits_Email_Customer_Reminder_Notification extends WC_Email {

	/**
	 * Whether the improved email is enabled in WooCommerce.
	 *
	 * @var bool
	 */
	public $improved_email_enabled = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'wc_deposits_customer_reminder_notification';
		$this->title          = __( 'Customer Notification: Payment Plan Reminder Notification', 'woocommerce-deposits' );
		$this->description    = __( 'Customer reminder notification emails are sent to customers to remind them of an upcoming payment.', 'woocommerce-deposits' );
		$this->customer_email = true;

		$this->heading = __( 'Upcoming Payment Reminder', 'woocommerce-deposits' );
		$this->subject = sprintf(
			// translators: 1: Site title, 2: Time until invoice will be sent.
			__( '[%1$s]: Your payment is due in %2$s.', 'woocommerce-deposits' ),
			'{site_title}',
			'{time_until_due_date}'
		);

		$this->template_base  = WC_DEPOSITS_TEMPLATE_PATH;
		$this->template_html  = 'emails/customer-reminder-notification.php';
		$this->template_plain = 'emails/plain/customer-reminder-notification.php';

		// Triggers for this email.
		add_action( 'woocommerce_deposits_upcoming_payment_reminder', array( $this, 'trigger' ), 10, 2 );

		$improved_email_enabled = false;

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) && \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
			$improved_email_enabled = true;
		}

		$this->improved_email_enabled = $improved_email_enabled;

		add_filter( 'woocommerce_email_preview_dummy_product', array( $this, 'modify_preview_dummy_product' ), 10, 2 );
		add_filter( 'woocommerce_email_preview_dummy_product_variation', array( $this, 'modify_preview_dummy_product_variation' ), 10, 2 );
		add_filter( 'woocommerce_email_preview_dummy_order', array( $this, 'modify_preview_order_object' ), 10, 2 );
		add_filter( 'woocommerce_email_preview_placeholders', array( $this, 'prepare_placeholders_for_email' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Filter the preview product.
	 *
	 * Modifies the dummy product title to indicate that it is a payment plan.
	 *
	 * @param WC_Product $item       Product.
	 * @param string     $email_type Email type.
	 * @return WC_Product Modified product.
	 */
	public function modify_preview_dummy_product( $item, $email_type ) {
		if ( __CLASS__ !== $email_type ) {
			return $item;
		}
		$item->set_name( __( 'Payment #2 for Dummy Product', 'woocommerce-deposits' ) );

		return $item;
	}

	/**
	 * Filter the preview variation product.
	 *
	 * Removes the variation product from the preview as the email
	 * typically only has one product.
	 *
	 * @param WC_Product_Variation $item       Product variation.
	 * @param string               $email_type Email type.
	 * @return bool False to remove the variation product.
	 */
	public function modify_preview_dummy_product_variation( $item, $email_type ) {
		if ( __CLASS__ !== $email_type ) {
			return $item;
		}
		return false;
	}

	/**
	 * Modify the preview order object.
	 *
	 * Sets the order status to 'scheduled-payment' and modifies the order
	 * details for the preview.
	 *
	 * @param WC_Order $order      Order object.
	 * @param string   $email_type Email type.
	 * @return WC_Order Modified order object.
	 */
	public function modify_preview_order_object( $order, $email_type ) {
		if ( __CLASS__ !== $email_type ) {
			return $order;
		}

		$due_date = time() + ( 3 * DAY_IN_SECONDS );
		$order->set_status( 'scheduled-payment' );
		$order->set_date_created( gmdate( 'Y-m-d H:i:s', $due_date ) );

		// Modify the order details to account for the product changes above.
		$order->set_discount_total( 0 );
		$order->set_shipping_total( false );
		$order->set_total( 50 );

		return $order;
	}

	/**
	 * Prepare the email for preview.
	 *
	 * Replaces the placeholders in the email with dummy data.
	 *
	 * @param string[] $placeholders The email placeholders.
	 * @param string   $email_type   The email type.
	 * @return string[] The modified placeholders.
	 */
	public function prepare_placeholders_for_email( $placeholders, $email_type ) {
		if ( __CLASS__ !== $email_type ) {
			return $placeholders;
		}

		$placeholders['{time_until_due_date}'] = __( '3 days', 'woocommerce-deposits' );
		return $placeholders;
	}

	/**
	 * Trigger the email.
	 *
	 * @param int|WC_Order $order_id Order ID or WC_Order object.
	 * @param WC_Order     $order    Order object.
	 */
	public function trigger( $order_id, $order = false ) {
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                                 = $order;
			$this->recipient                              = $this->object->get_billing_email();
			$this->placeholders['{order_date}']           = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}']         = $this->object->get_order_number();
			$this->placeholders['{customers_first_name}'] = $this->object->get_billing_first_name();
			$this->placeholders['{time_until_due_date}']  = WC_Deposits_Notifications_Manager::get_relative_due_date( $this->object );
		}

		$this->setup_locale();

		try {

			if ( ! WC_Deposits_Notifications_Manager::should_send_notification_emails() ) {
				$this->restore_locale();
				return;
			}

			$result = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			if ( $result ) {
				/* translators: 1: Notification type, 2: customer's email. */
				$order_note_msg = sprintf( __( '%1$s was successfully sent to %2$s.', 'woocommerce-deposits' ), $this->title, $this->recipient );
				$order->update_meta_data( '_wc_deposits_reminder_email_sent', time() );
				$order->save_meta_data();
			} else {
				/* translators: 1: Notification type, 2: customer's email. */
				$order_note_msg = sprintf( __( 'Attempt to send %1$s to %2$s failed.', 'woocommerce-deposits' ), $this->title, $this->recipient );
			}

			$order->add_order_note( $order_note_msg );
		} finally {
			$this->restore_locale();
		}
	}

	/**
	 * Get content for the HTML-version of the email.
	 *
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template(
			$this->template_html,
			array(
				'order'                      => $this->object,
				'email_heading'              => $this->get_heading(),
				'additional_content'         => $this->get_additional_content(),
				'plain_text'                 => false,
				'sent_to_admin'              => false,
				'email'                      => $this,
				'email_improvements_enabled' => $this->improved_email_enabled,
				'deposits_due_date'          => WC_Deposits_Notifications_Manager::get_due_date( $this->object ),
				'deposits_due_date_relative' => WC_Deposits_Notifications_Manager::get_relative_due_date( $this->object ),
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * Get content for the HTML-version of the email.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template(
			$this->template_plain,
			array(
				'order'                      => $this->object,
				'email_heading'              => $this->get_heading(),
				'additional_content'         => $this->get_additional_content(),
				'plain_text'                 => true,
				'sent_to_admin'              => false,
				'email'                      => $this,
				'email_improvements_enabled' => $this->improved_email_enabled,
				'deposits_due_date'          => WC_Deposits_Notifications_Manager::get_due_date( $this->object ),
				'deposits_due_date_relative' => WC_Deposits_Notifications_Manager::get_relative_due_date( $this->object ),
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}
}
