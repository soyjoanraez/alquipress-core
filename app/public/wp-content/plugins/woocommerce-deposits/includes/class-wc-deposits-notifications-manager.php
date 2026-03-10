<?php
/**
 * Deposits notifications manager
 *
 * @package woocommerce-deposits
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Deposits_Notifications_Manager class.
 *
 * Handles notifications for upcoming payments.
 */
class WC_Deposits_Notifications_Manager {

	/**
	 * Class instance
	 *
	 * @var WC_Deposits_Notifications_Manager
	 */
	private static $instance;

	/**
	 * Action scheduler hook name for processing batches.
	 *
	 * @var string
	 */
	const NOTIFICATION_BATCH_PROCESSING_ACTION = 'wc_deposits_batch_reminder_notifications';

	/**
	 * Action scheduler hook name for processing a single reminder notification.
	 *
	 * @var string
	 */
	const NOTIFICATION_REMINDER_PROCESSING_ACTION = 'wc_deposits_process_reminder_notifications';

	/**
	 * WP Cron hook name for processing a batch.
	 *
	 * @var string
	 */
	const NOTIFICATIONS_BATCH_SCHEDULER_CRON = 'wc_deposits_reminder_notifications_scheduler';

	/**
	 * Get the class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_deposits_get_settings', array( $this, 'add_settings' ) );
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_classes' ) );

		if ( ! wp_next_scheduled( self::NOTIFICATIONS_BATCH_SCHEDULER_CRON ) ) {
			wp_schedule_event( time(), 'hourly', self::NOTIFICATIONS_BATCH_SCHEDULER_CRON );
		}
		add_action( self::NOTIFICATIONS_BATCH_SCHEDULER_CRON, array( $this, 'maybe_batch_schedule_reminder_notifications' ) );
		add_action( self::NOTIFICATION_BATCH_PROCESSING_ACTION, array( $this, 'process_batch' ), 10, 2 );
		add_action( self::NOTIFICATION_REMINDER_PROCESSING_ACTION, array( $this, 'send_notification_email' ), 10, 2 );
	}

	/**
	 * Add email classes to WooCommerce.
	 *
	 * @param array $emails Email classes.
	 * @return array Modified email classes to include deposits notifications.
	 */
	public function add_email_classes( $emails ) {
		require_once WC_DEPOSITS_ABSPATH . 'includes/emails/class-wc-deposits-email-customer-reminder-notification.php';
		$emails['WC_Deposits_Email_Customer_Reminder_Notification'] = new WC_Deposits_Email_Customer_Reminder_Notification();

		return $emails;
	}

	/**
	 * Add notification settings to the global settings page.
	 *
	 * @param array $global_settings Global settings.
	 * @return array Modified settings to include notifications.
	 */
	public function add_settings( $global_settings ) {

		$notification_settings = array(
			array(
				'title' => __( 'Customer Notifications', 'woocommerce-deposits' ),
				'type'  => 'title',
				'id'    => 'deposits_customer_notifications',
				'desc'  => sprintf(
					/* translators: Link to WC Settings > Email. */
					__( 'Send emails to customers to advise them of an upcoming payment for their payment plan. To customize the template, visit the <a href="%s">Email settings</a>.', 'woocommerce-deposits' ),
					admin_url( 'admin.php?page=wc-settings&tab=email' )
				),
			),

			array(
				'title'    => __( 'Email Reminders', 'woocommerce-deposits' ),
				'desc'     => __( 'Send email reminders to customers for upcoming payment plan invoices.', 'woocommerce-deposits' ),
				'id'       => 'woocommerce_deposits_enable_reminders',
				'type'     => 'checkbox',
				'default'  => 'yes',
				'autoload' => false,
			),

			array(
				'title'    => __( 'Reminder Timing', 'woocommerce-deposits' ),
				'desc'     => __( 'How long before the event should the notification be sent.', 'woocommerce-deposits' ),
				'id'       => 'woocommerce_deposits_reminder_timing',
				'type'     => 'relative_date_selector',
				'default'  => array(
					'number' => '3',
					'unit'   => 'days',
				),
				'autoload' => false,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'deposits_customer_notifications',
			),
		);

		$global_settings = array_merge( $global_settings, $notification_settings );

		return $global_settings;
	}

	/**
	 * Generate a unique batch ID for a set of order IDs.
	 *
	 * Normalizes the order IDs to generate a consistent batch ID for
	 * a set of due orders.
	 *
	 * @since 2.4.2
	 *
	 * @param array $order_ids Order IDs.
	 * @return string Batch ID.
	 */
	private static function generate_batch_id( $order_ids = array() ) {
		// Tidy and sort the order IDs to ensure the batch ID is consistent.
		$order_ids = array_map( 'absint', $order_ids );
		$order_ids = array_unique( $order_ids );
		$order_ids = array_filter( $order_ids );
		sort( $order_ids );

		return md5( wp_json_encode( $order_ids ) );
	}

	/**
	 * Checks if there's already a scheduled batch processor action for a given set of products.
	 *
	 * @param int[]  $order_ids The order IDs to batch process invoices for.
	 * @param string $batch_id The unique batch ID for this set of orders.
	 *
	 * @return bool True if there's a scheduled action already, otherwise false.
	 */
	private static function has_scheduled_batch_processor( $order_ids, $batch_id ) {
		return null !== WC()->queue()->get_next(
			self::NOTIFICATION_BATCH_PROCESSING_ACTION,
			array(
				'order_ids' => $order_ids,
				'batch_id'  => $batch_id,
			)
		);
	}

	/**
	 * Schedule a batch processor for a set of due orders.
	 *
	 * @since 2.4.2
	 *
	 * @param int[]  $order_ids The order IDs to batch process pre-orders for.
	 * @param string $batch_id  The unique batch ID for this set of orders.
	 */
	private static function schedule_batch_processor( $order_ids, $batch_id ) {
		WC()->queue()->add(
			self::NOTIFICATION_BATCH_PROCESSING_ACTION,
			array(
				'order_ids' => $order_ids,
				'batch_id'  => $batch_id,
			)
		);
	}

	/**
	 * Schedule a batch job to send reminder notifications.
	 *
	 * @since 2.4.2
	 */
	public static function maybe_batch_schedule_reminder_notifications() {
		if ( ! self::should_send_notification_emails() ) {
			return;
		}

		$reminder_timing = get_option(
			'woocommerce_deposits_reminder_timing',
			array(
				'number' => '3',
				'unit'   => 'days',
			)
		);

		/*
		 * Get the time stamps for the reminders.
		 *
		 * The end date is the time selected by the merchant.
		 *
		 * The start date is 2 days prior to this action running to ensure that the
		 * database query is somewhat limited to a reasonable number of orders. The
		 * two day buffer is arbitrary but ensures that notifications are not sent
		 * excessively late.
		 */
		$reminder_end_date   = strtotime( "+{$reminder_timing['number']} {$reminder_timing['unit']}", time() );
		$reminder_start_date = strtotime( '-2 days', time() );

		// Get all scheduled payment orders.
		$args = array(
			'status'       => 'wc-scheduled-payment',
			'limit'        => -1,
			'return'       => 'ids',
			'date_created' => $reminder_start_date . '...' . $reminder_end_date,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- NOT EXISTS is against the index.
			'meta_query'   => array(
				array(
					'key'     => '_wc_deposits_reminder_email_sent',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$orders = wc_get_orders( $args );

		if ( empty( $orders ) ) {
			return;
		}

		// Batch process the orders.
		$batch_id = self::generate_batch_id( $orders );

		if ( self::has_scheduled_batch_processor( $orders, $batch_id ) ) {
			// Already scheduled.
			return;
		}

		// Schedule the batch processor.
		self::schedule_batch_processor( $orders, $batch_id );
	}

	/**
	 * Process a batch of scheduled orders.
	 *
	 * @param int[]  $order_ids The order IDs to batch process pre-orders for.
	 * @param string $batch_id The unique batch ID for this set of orders.
	 */
	public static function process_batch( $order_ids, $batch_id ) {
		$meta_key_flag = '_wc_deposits_notifications_batch_' . $batch_id;

		/**
		 * Filters the number of scheduled orders to process in a single batch.
		 *
		 * @since 2.4.2
		 *
		 * @param int $batch_size The number of scheduled orders to process in a single batch. Default 100.
		 */
		$batch_size = apply_filters( 'wc_deposits_notification_batch_size', 100 );

		// Order query arguments.
		$args = array(
			'post__in'       => $order_ids,
			'post_status'    => 'wc-scheduled-payment',
			'post_type'      => 'shop_order',
			'posts_per_page' => $batch_size,
			'fields'         => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- meta_key column is indexed.
			'meta_query'     => array(
				array(
					'key'     => $meta_key_flag,
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$results = array();
		if ( WC_Deposits_COT_Compatibility::is_cot_enabled() ) {
			$results = wc_get_orders( $args );
		} else {
			$query   = new WP_Query( $args );
			$results = $query->posts;
		}

		// If we got a full batch of orders, we haven't finished.
		$is_batch_complete = count( $results ) !== $batch_size;

		foreach ( $results as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			// Store meta on the order so we know it has been checked by this batch so it can be excluded by future batch queries.
			// Skip setting this meta if this is the last batch, as we've deleted it from all previous orders at this point.
			if ( ! $is_batch_complete ) {
				$order->update_meta_data( $meta_key_flag, 'true' );
				$order->save_meta_data();
			}

			$args = array(
				'order'          => $order->get_id(),
				'batch_meta_key' => $meta_key_flag,
			);

			if ( null === WC()->queue()->get_next( self::NOTIFICATION_REMINDER_PROCESSING_ACTION, $args ) ) {
				WC()->queue()->schedule_single( time() + MINUTE_IN_SECONDS, self::NOTIFICATION_REMINDER_PROCESSING_ACTION, $args );
			}
		}

		// Process the next group of orders in the batch if it's not yet complete.
		if ( ! $is_batch_complete ) {
			self::schedule_batch_processor( $order_ids, $batch_id );
		}
	}

	/**
	 * Send a notification email of an upcoming payment.
	 *
	 * Sends an email to the customer informing them of an upcoming
	 * payment for a scheduled payment order.
	 *
	 * @since 2.4.2
	 *
	 * @param int|WC_Order $order          Order ID or WC_Order object.
	 * @param string       $batch_meta_key Meta key for the batch. Optional.
	 */
	public static function send_notification_email( $order, $batch_meta_key = null ) {
		if ( ! self::should_send_notification_emails() ) {
			return;
		}

		$order = wc_get_order( $order );
		if ( ! $order ) {
			return;
		}

		// Do not send repeat emails.
		$previously_sent = $order->get_meta( '_wc_deposits_reminder_email_sent' );
		if ( $previously_sent ) {
			return;
		}

		// Only send emails for scheduled payments that are a follow up order.
		if ( 'scheduled-payment' !== $order->get_status() || ! WC_Deposits_Order_Manager::is_follow_up_order( $order ) ) {
			return;
		}

		if ( $batch_meta_key ) {
			/*
			 * Batch meta keys are deleted after the email is sent.
			 *
			 * This is to allow cache management is handled by WC_Order etc rather than
			 * having to determine the correct group, key name and related values that
			 * vary depending on the use of HPOS or the posts table for order storage.
			 */
			$order->delete_meta_data( $batch_meta_key );
		}
		$order->save_meta_data();

		$mailer = WC_Emails::instance();

		if ( ! isset( $mailer->emails['WC_Deposits_Email_Customer_Reminder_Notification'] ) ) {
			return;
		}

		/**
		 * Trigger the email notification for an upcoming payment.
		 *
		 * @since 2.4.2
		 *
		 * @param int      $order_id Order ID.
		 * @param WC_Order $order    Order object.
		 */
		do_action( 'woocommerce_deposits_upcoming_payment_reminder', $order->get_id(), $order );
	}

	/**
	 * Check if the notification email should be sent.
	 *
	 * Determines whether notifications emails should be sent based on the
	 * site options and the current environment.
	 *
	 * @since 2.4.2
	 *
	 * @return bool True if the notification email should be sent, false otherwise.
	 */
	public static function should_send_notification_emails() {
		if ( 'yes' !== get_option( 'woocommerce_deposits_enable_reminders', 'yes' ) ) {
			// Merchant has turned off reminders.
			return false;
		}

		$allowed_environments = array( 'production' );

		/**
		 * Filters the allowed environments for sending reminder emails.
		 *
		 * By default reminder emails are only set in the `production` environment
		 * to ensure that purchasers don't receive emails for orders that have been
		 * created in a staging or development environment.
		 *
		 * When using this filter to allow emails to be sent on a non-production
		 * environment it is **strongly recommended** to ensure that the environment either
		 * does not have real customer data or that emails are logged but not sent.
		 *
		 * Valid environment types are:
		 *
		 * - production: A site that is running live, connected to the internet and reachable on the internet
		 * - staging: A site that is a near copy of the production site, probably both connected to and reachable on the internet
		 * - development: A site that includes unreleased code but is connected and reachable on the internet
		 * - local: A development environment that is connected to the internet but not reachable on the internet
		 *
		 * These environment types are defined by WordPress Core, you can read more about them at
		 * https://make.wordpress.org/core/2020/07/24/new-wp_get_environment_type-function-in-wordpress-5-5/
		 *
		 * @since 2.4.2
		 *
		 * @param string[] $allowed_environments The allowed environments for sending reminder emails. Default `[ 'production' ]`.
		 */
		$allowed_environments = apply_filters( 'wc_deposits_reminder_email_allowed_environments', $allowed_environments );

		// Check if the current environment is allowed.
		if ( ! in_array( wp_get_environment_type(), $allowed_environments, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the due date for a scheduled payment order.
	 *
	 * @since 2.4.2
	 *
	 * @param WC_Order $order Order object.
	 * @return string|bool Due date or false if not a scheduled payment order.
	 */
	public static function get_due_date( $order ) {
		if ( 'scheduled-payment' !== $order->get_status() ) {
			return false;
		}

		$due_date_gmt = $order->get_date_created();

		if ( ! $due_date_gmt ) {
			return false;
		}

		// Convert timestamp to local date.
		return wc_format_datetime( $due_date_gmt );
	}

	/**
	 * Get the relative due date for a scheduled payment order.
	 *
	 * This is a human-readable format of the due date, eg "3 days".
	 *
	 * @since 2.4.2
	 *
	 * @param WC_Order $order Order object.
	 * @return string|bool Relative due date or false if not a scheduled payment order.
	 */
	public static function get_relative_due_date( $order ) {
		if ( 'scheduled-payment' !== $order->get_status() ) {
			return false;
		}

		$due_date_gmt = $order->get_date_created();

		if ( ! $due_date_gmt ) {
			return false;
		}

		return human_time_diff( $due_date_gmt->getTimestamp(), time() );
	}
}
