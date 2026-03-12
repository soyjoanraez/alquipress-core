<?php
/**
 * A bookings notes meta-box class file.
 *
 * @package WooCommerce Bookings
 */

/**
 * WC_Bookings_Notes_Meta_Box class.
 */
class WC_Bookings_Notes_Meta_Box {

	/**
	 * Meta box ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Meta box title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Meta box context.
	 *
	 * @var string
	 */
	public $context;

	/**
	 * Meta box priority.
	 *
	 * @var string
	 */
	public $priority;

	/**
	 * Meta box post types.
	 *
	 * @var array
	 */
	public $post_types;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id         = 'woocommerce-notes-data';
		$this->title      = __( 'Notes', 'woocommerce-bookings' );
		$this->context    = 'side';
		$this->priority   = 'default';
		$this->post_types = array( 'wc_booking' );
	}

	/**
	 * Render inner part of meta box.
	 *
	 * @since 2.0.8
	 *
	 * @param object $post Post object.
	 */
	public function meta_box_inner( $post ) {
		// Get the global database access class.
		global $wpdb;
		global $booking;

		if ( ! is_a( $booking, 'WC_Booking' ) || $booking->get_id() !== $post->ID ) {
			try {
				$booking = new WC_Booking( $post->ID );
			} catch ( Exception $e ) {
				wc_get_logger()->error( $e->getMessage() );
				return;
			}
		}

		$order_id = $booking->get_order_id();
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		// Fetch and display order notes.
		if ( $order_id && $order ) {
			/*
			 * Custom queries to retrieve order notes.
			 *
			 * These queries are used to fetch the bookings notes from the associated order.
			 * Prior to version 2.2.9, the booking ID was not stored as meta data in the order
			 * notes, so we need to handle both the legacy and current cases.
			 *
			 * The current implementation uses the comment meta table to find notes and is
			 * therefore more reliable than the legacy implementation that relies on the
			 * comment content and makes use of a LIKE query.
			 *
			 * The database queries are split to fist get the comment IDs and then fetch
			 * the full comment objects. This is to avoid JOINs in the queries.
			 *
			 * See WOOBOOK-148 for details.
			 */

			// Since version 2.2.9 the order notes contain meta data for the booking ID.
			$note_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT comment_id FROM {$wpdb->prefix}commentmeta
					WHERE meta_key = %s
					AND meta_value = %d",
					'_wc_bookings_booking_id',
					$post->ID
				)
			);

			/*
			 * This translator note is a little misleading.
			 *
			 * The old status and new status are replaced with wildcards (%) for
			 * use in the SQL LIKE clause. The translation is used to match the
			 * content of the order note added in WC_Booking::status_transitioned_handler()
			 *
			 * To avoid warning messages about different translator notes this one
			 * uses the description of the original note.
			 */
			$comment_content_like = sprintf(
				/* translators: 1: booking id 2: old status 3: new status */
				__( 'Booking #%1$d status changed from "%2$s" to "%3$s"', 'woocommerce-bookings' ),
				$post->ID,
				'%',
				'%'
			);

			// Legacy support of order notes that do not have the booking ID in the meta data.
			$note_ids_legacy_query = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT comment_ID FROM {$wpdb->prefix}comments
					WHERE comment_post_ID = %d
					AND comment_type = 'order_note'
					AND (
						comment_content LIKE %s
						OR comment_content LIKE %s
					)",
					$order_id,
					$comment_content_like, // Translated string with wildcards.
					'%#' . $post->ID . '%' // Legacy like query for english #[booking_id].
				)
			);

			// No need to de-dupe as MySQL will handle that gracefully.
			$note_ids = array_merge( $note_ids, $note_ids_legacy_query );

			/*
			 * `order_note` clause is required due to the meta query above.
			 *
			 * This is to avoid fetching comments that are not order notes but
			 * happen to have the meta key `_wc_bookings_booking_id`.
			 */
			$notes = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}comments
					WHERE comment_ID IN ( " . implode( ',', array_fill( 0, count( $note_ids ), '%d' ) ) . " )
					AND comment_type = 'order_note'
					ORDER BY comment_date_gmt DESC",
					$note_ids
				)
			);

			if ( $notes ) {
				?>
				<ul class="order_notes">
					<?php foreach ( $notes as $note ) { ?>
						<li class="system-note">
							<div class="note_content">
								<p><?php echo wp_kses_post( $note->comment_content ); ?></p>
							</div>
							<p class="meta">
								<abbr class="exact-date" title="<?php echo esc_attr( date_i18n( 'c', strtotime( $note->comment_date ) ) ); ?>"><?php echo esc_html( date_i18n( wc_date_format(), strtotime( $note->comment_date ) ) ); ?></abbr>
								<?php echo ' ' . esc_html__( 'at', 'woocommerce-bookings' ) . ' ' . esc_html( date_i18n( 'h:i a', strtotime( $note->comment_date ) ) ); ?>
							</p>
						</li>
					<?php } ?>
				</ul>
				<?php
			}
		}
	}
}

