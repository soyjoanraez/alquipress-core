<?php
/**
 * Server-side rendering of the `woocommerce-bookings/booking-location` block.
 *
 * @package WooCommerce Bookings
 * @since 3.0.0
 * @version 3.0.0
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

$location = $product->get_booking_location();
if ( empty( $location ) ) {
	return;
}

$classes = array( 'wc-bookings-block-components-booking-location' );
if ( isset( $attributes['textAlign'] ) ) {
	$classes[] = 'has-text-align-' . $attributes['textAlign'];
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );

$prefix = '';
if ( isset( $attributes['prefix'] ) && $attributes['prefix'] ) {
	$prefix = '<span class="wc-bookings-block-components-booking-location__prefix">' . $attributes['prefix'] . '</span>';
}

$suffix = '';
if ( isset( $attributes['suffix'] ) && $attributes['suffix'] ) {
	$suffix = '<span class="wc-bookings-block-components-booking-location__suffix">' . $attributes['suffix'] . '</span>';
}

printf(
	'<div %1$s>%2$s<span>%3$s</span>%4$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	wp_kses_post( $prefix ),
	wp_kses_post( $location ),
	wp_kses_post( $suffix )
);
