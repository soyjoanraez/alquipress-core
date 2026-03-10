<?php
/**
 * Render the Booking Time Slots block.
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

$timezone  = new DateTimeZone( wc_booking_get_timezone_string() );
$dt        = new DateTime( 'now', $timezone );
$formatter = new IntlDateFormatter(
	'en_US',
	IntlDateFormatter::NONE,
	IntlDateFormatter::NONE,
	$timezone,
	IntlDateFormatter::GREGORIAN,
	'zzzz'
);

$full_name        = $formatter->format( $dt );
$offset_string    = $dt->format( 'P' );
$offset_formatted = '+00:00' === $offset_string ? 'GMT' : 'GMT' . str_replace( ':00', '', $offset_string );

/* translators: 1: Timezone name, 2: Timezone offset. */
$timezone_string = esc_html__( 'Time Zone: %1$s (%2$s)', 'woocommerce-bookings' );
$timezone_string = sprintf( $timezone_string, $full_name, $offset_formatted );

$context = array(
	'currentPage'  => 1,
	'slotsPerPage' => 6,
);
?>
<div
	class="wc-bookings-time-slots"
	data-wp-interactive="woocommerce-bookings/booking-time-slots"
	data-wp-bind--hidden="!state.isVisible"
	data-wp-watch="callbacks.preselectInitialTime"
	data-wp-class--loading="woocommerce/add-to-cart-with-options::state.isLoadingAvailability"
	<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<div
		data-wp-bind--hidden="!state.shouldShowPlaceholder"
		class="wc-bookings-time-slots__placeholder"
	>
		<span><?php echo esc_html__( 'Select a date to view times.', 'woocommerce-bookings' ); ?></span>
	</div>
	<div
		class="wc-bookings-time-slots__container"
		data-wp-bind--hidden="state.shouldShowPlaceholder"
		data-wp-on--touchstart="actions.onTouchStart"
		data-wp-on--touchmove="actions.onTouchMove"
		data-wp-on--touchend="actions.onTouchEnd"
		>

		<div
			class="wc-bookings-time-slots__navigation-arrows previous"
			data-wp-bind--hidden="!state.shouldShowPagination"
			>
			<button
				type="button"
				data-wp-on--click="actions.prevPage"
				data-wp-bind--disabled="state.isPreviousPageDisabled"
				>
					&lsaquo;
			</button>
		</div>

		<div
			class="wc-bookings-time-slots__grid"
			>
			<template
				data-wp-each--slot="state.slotsForPage"
				data-wp-each-key="context.slot.time"
				>
				<button
					class="wc-bookings-time-slots__slot"
					type="button"
					data-wp-on--click="actions.handleSelectTime"
					data-time="context.slot.time"
					data-wp-class--selected="context.slot.isSelected"
					data-wp-bind--data-time="context.slot.time"
					data-wp-bind--aria-label="context.slot.ariaLabel"
					>
					<span data-wp-text="context.slot.timeString"></span>
				</button>
			</template>
		</div>

		<div
			class="wc-bookings-time-slots__navigation-arrows next"
			data-wp-bind--hidden="!state.shouldShowPagination"
			>
			<button
				type="button"
				data-wp-on--click="actions.nextPage"
				data-wp-bind--disabled="state.isNextPageDisabled"
				>
				&rsaquo;
			</button>
		</div>
	</div>
	<div class="wc-bookings-time-slots__pagination" data-wp-bind--hidden="!state.shouldShowPagination">
		<div class="wc-bookings-time-slots__pagination-pages">
			<template data-wp-each--page="state.pages">
				<button
					type="button"
					data-wp-bind--data-pageNumber="context.page.pageNumber"
					data-wp-on--click="actions.handleGoToPage"
					data-wp-class--selected="context.page.isSelected"
					data-wp-bind--aria-label="context.page.ariaLabel"
				>
				</button>
			</template>
		</div>
	</div>
	<div
		class="wc-bookings-time-slots__timezone"
		data-wp-bind--hidden="state.shouldShowPlaceholder"
		>
		<?php echo esc_html( $timezone_string ); ?>
	</div>
</div>
