<?php
/**
 * Admin View: Booking Templates
 *
 * @package WooCommerce Bookings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="bookings_templates" class="woocommerce_options_panel panel wc-metaboxes-wrapper tab_bookings_templates">
	<div class="options_group" id="template_options">
		<?php
			$tempaltes_obj = new WC_Bookings_Templates();
			$tempaltes_obj->product_templates();
		?>
	</div>
</div>
