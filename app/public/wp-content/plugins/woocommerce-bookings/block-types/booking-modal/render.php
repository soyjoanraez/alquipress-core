<?php
/**
 * Render the Booking Modal block.
 *
 * @package WooCommerce\Bookings
 * @since 3.0.0
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product = wc_get_product();
if ( ! is_wc_booking_product( $product ) ) {
	return;
}

$price   = wp_strip_all_tags( wc_price( $product->get_price() ) );
$buttons = do_blocks(
	'<!-- wp:buttons {"align":"full","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-buttons alignfull">
		<!-- wp:button {"width":100,"className":"is-style-outline continue-shopping"} -->
		<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-outline continue-shopping"><a class="wp-block-button__link wp-element-button">' . __( 'Confirm and continue shopping', 'woocommerce-bookings' ) . '</a></div>
		<!-- /wp:button -->
		<!-- wp:button {"width":100, "className":"complete-booking"} -->
		<div class="wp-block-button has-custom-width wp-block-button__width-100 complete-booking"><a class="wp-block-button__link wp-element-button">' . __( 'Complete booking', 'woocommerce-bookings' ) . '</a></div>
		<!-- /wp:button -->
	</div><!-- /wp:buttons -->'
);

// Hint: This will only work if a single Add-to-Cart + Options block exists on the page.
// TODO: Make it better. Move it to the add-to-cart-with-options block context.
wp_interactivity_config(
	'woocommerce/add-to-cart-with-options',
	array(
		'wcBookingsRequiresTimeSelection' => $product->requires_time_selection(),
		'wcBookingsMinDate'               => $product->get_min_date(),
		'wcBookingsMaxDate'               => $product->get_max_date(),
		'isPermalinksPlain'               => '' === get_option( 'permalink_structure' ),
	)
);
?>
<div
	class="wc-bookings-modal-overlay"
	data-wp-bind--hidden="!context.isModalOpen"
	data-wp-on--click="actions.closeBookingModal"
	>
</div>
<dialog
	class="wc-bookings-modal"
	data-wp-bind--open="context.isModalOpen"
	data-wp-bind--inert="!context.isModalOpen"
	data-wp-on--keydown="actions.onModalKeyDown"
	role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Choose a time', 'woocommerce-bookings' ); ?>"
	>
	<div class="wc-bookings-modal-header">
			<h3 class="wc-bookings-modal-title"><?php esc_attr_e( 'Select date and time', 'woocommerce-bookings' ); ?></h3>
			<button
				class="wc-bookings-modal-close"
				data-wp-on--click="actions.closeBookingModal"
				aria-label="<?php esc_attr_e( 'Close modal', 'woocommerce-bookings' ); ?>"
				type="button"
				>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
					<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
				</svg>
			</button>
		</div>
	<div class="wc-bookings-modal-container">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<div
		class="wc-bookings-modal-buttons"
		data-wp-class--is-disabled="woocommerce/add-to-cart-with-options::state.isAddingBookingToCart"
		data-wp-bind--hidden="woocommerce/add-to-cart-with-options::state.shouldHideAddToCartButton"
		>
		<?php echo $buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</dialog>
