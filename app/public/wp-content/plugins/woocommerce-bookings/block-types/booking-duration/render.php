<?php
/**
 * Server-side rendering of the `woocommerce-bookings/booking-duration` block.
 *
 * @package WooCommerce Bookings
 * @since 3.0.1
 * @version 3.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id = $block->context['postId'];
if ( ! $product_id ) {
	return;
}

$product = wc_get_product( $product_id );
if ( ! $product || ! is_wc_booking_product( $product ) ) {
	return;
}

$duration = $product->get_duration();
if ( empty( $duration ) ) {
	return;
}

$duration_unit = $product->get_duration_unit();
$unit_display  = '';
if ( 'minute' === $duration_unit ) {
	$unit_display = __( 'min', 'woocommerce-bookings' );
} elseif ( 'hour' === $duration_unit ) {
	$unit_display = _n( 'hour', 'hours', $duration, 'woocommerce-bookings' );
} elseif ( 'day' === $duration_unit ) {
	$unit_display = _n( 'day', 'days', $duration, 'woocommerce-bookings' );
} elseif ( 'month' === $duration_unit ) {
	$unit_display = _n( 'month', 'months', $duration, 'woocommerce-bookings' );
} elseif ( 'night' === $duration_unit ) {
	$unit_display = _n( 'night', 'nights', $duration, 'woocommerce-bookings' );
} else {
	$unit_display = $duration_unit;
}

$duration_display = sprintf( '%s %s', $duration, $unit_display );

$classes = array( 'wc-bookings-block-components-booking-duration' );
if ( isset( $attributes['textAlign'] ) ) {
	$classes[] = 'has-text-align-' . $attributes['textAlign'];
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );

$prefix = '';
if ( isset( $attributes['prefix'] ) && $attributes['prefix'] ) {
	$prefix = '<span class="wc-bookings-block-components-booking-duration__prefix">' . $attributes['prefix'] . '</span>';
}

$suffix = '';
if ( isset( $attributes['suffix'] ) && $attributes['suffix'] ) {
	$suffix = '<span class="wc-bookings-block-components-booking-duration__suffix">' . $attributes['suffix'] . '</span>';
}

printf(
	'<div %1$s>%2$s<span>%3$s</span>%4$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	wp_kses_post( $prefix ),
	esc_html( $duration_display ),
	wp_kses_post( $suffix )
);
