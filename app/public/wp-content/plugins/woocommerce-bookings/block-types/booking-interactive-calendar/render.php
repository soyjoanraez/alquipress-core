<?php
/**
 * Render the Booking Interactive Calendar block.
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

/**
 * I18n month names.
 */
$month_names = array(
	_x( 'January', 'month name', 'woocommerce-bookings' ),
	_x( 'February', 'month name', 'woocommerce-bookings' ),
	_x( 'March', 'month name', 'woocommerce-bookings' ),
	_x( 'April', 'month name', 'woocommerce-bookings' ),
	_x( 'May', 'month name', 'woocommerce-bookings' ),
	_x( 'June', 'month name', 'woocommerce-bookings' ),
	_x( 'July', 'month name', 'woocommerce-bookings' ),
	_x( 'August', 'month name', 'woocommerce-bookings' ),
	_x( 'September', 'month name', 'woocommerce-bookings' ),
	_x( 'October', 'month name', 'woocommerce-bookings' ),
	_x( 'November', 'month name', 'woocommerce-bookings' ),
	_x( 'December', 'month name', 'woocommerce-bookings' ),
);

/**
 * Get week start day setting.
 * 0 = Sunday, 1 = Monday, etc.
 */
$week_starts_on = get_option( 'start_of_week', 0 );

/**
 * Define all weekday names in order (Sunday to Saturday).
 */
$all_weekday_names = array(
	_x( 'Sun', 'weekday abbreviation', 'woocommerce-bookings' ),
	_x( 'Mon', 'weekday abbreviation', 'woocommerce-bookings' ),
	_x( 'Tue', 'weekday abbreviation', 'woocommerce-bookings' ),
	_x( 'Wed', 'weekday abbreviation', 'woocommerce-bookings' ),
	_x( 'Thu', 'weekday abbreviation', 'woocommerce-bookings' ),
	_x( 'Fri', 'weekday abbreviation', 'woocommerce-bookings' ),
	_x( 'Sat', 'weekday abbreviation', 'woocommerce-bookings' ),
);

/**
 * Reorder weekday names based on week start setting.
 */
$weekday_abbrev_names = array();
for ( $i = 0; $i < 7; $i++ ) {
	$day_index              = ( $week_starts_on + $i ) % 7;
	$weekday_abbrev_names[] = $all_weekday_names[ $day_index ];
}

/**
 * Build context.
 */
$context = array(
	'viewMonth'    => gmdate( 'n' ),
	'viewYear'     => gmdate( 'Y' ),
	'minDateData'  => $product->get_min_date(), // Value + Unit array.
	'maxDateData'  => $product->get_max_date(), // Value + Unit array.
	'monthNames'   => $month_names,
	'weekStartsOn' => (int) $week_starts_on,
);
?>
<div
	class="wc-bookings-calendar"
	data-wp-interactive="woocommerce-bookings/booking-interactive-calendar"
	data-wp-init="callbacks.initCalendar"
	<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-on--touchstart="actions.onTouchStart"
	data-wp-on--touchmove="actions.onTouchMove"
	data-wp-on--touchend="actions.onTouchEnd"
	>

	<div class="wc-bookings-calendar__header">
		<button
			class="wc-bookings-calendar__nav wc-bookings-calendar__nav--prev"
			type="button"
			data-wp-on--click="actions.navigateToPreviousMonth"
			data-wp-bind--aria-label="state.prevMonthLabel"
			data-wp-bind--disabled="state.isPreviousMonthDisabled"
			>
				&lsaquo;
			</button>
		<div class="wc-bookings-calendar__title">
			<span data-wp-text="state.viewMonthName"></span>
			<span data-wp-text="context.viewYear"></span>
			<span
				class="wc-bookings-calendar__spinner"
				data-wp-class--visible="woocommerce/add-to-cart-with-options::state.isLoadingAvailability"
				>
			</span>
		</div>
		<button
			class="wc-bookings-calendar__nav wc-bookings-calendar__nav--next"
			type="button"
			data-wp-on--click="actions.navigateToNextMonth"
			data-wp-bind--aria-label="state.nextMonthLabel"
			data-wp-bind--disabled="state.isNextMonthDisabled"
			>
				&rsaquo;
			</button>
	</div>

	<div class="wc-bookings-calendar__weekdays">
		<?php foreach ( $weekday_abbrev_names as $weekday_name ) : ?>
			<div class="wc-bookings-calendar__weekday"><?php echo esc_html( $weekday_name ); ?></div>
		<?php endforeach; ?>
	</div>

	<div
		class="wc-bookings-calendar__grid"
		data-wp-bind--key="state.calendarKey"
		data-wp-class--loading="woocommerce/add-to-cart-with-options::state.isLoadingAvailability"
		>
		<template
			data-wp-each--day="state.calendarDays"
			data-wp-each-key="context.day.key"
			>
			<div class="wc-bookings-calendar__day-container">
				<button
					class="wc-bookings-calendar__day"
					type="button"
					data-wp-on--click="actions.handleSelectDate"
					data-wp-class--selected="context.day.isSelected"
					data-wp-class--disabled="context.day.isDisabled"
					data-wp-class--other-month="!context.day.isCurrentMonth"
					data-wp-class--today="context.day.isToday"
					data-wp-bind--data-date="context.day.dateString"
					data-wp-bind--aria-disabled="context.day.isDisabled"
					data-wp-bind--aria-label="context.day.ariaLabel"
					data-wp-bind--tabindex="context.day.tabIndex"
					>
						<span data-wp-text="context.day.dayNumber"></span>
				</button>
			</div>
		</template>
	</div>
</div>
