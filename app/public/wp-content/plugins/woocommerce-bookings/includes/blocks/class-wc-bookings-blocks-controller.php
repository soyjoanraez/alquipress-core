<?php
/**
 * WooCommerce Bookings Gutenberg Blocks Controller.
 *
 * @package WooCommerce\Bookings
 * @since 3.0.0
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class is responsible for handling Gutenberg blocks.
 */
class WC_Bookings_Blocks_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {

		if ( ! defined( 'WC_BOOKINGS_EXPERIMENTAL_ENABLED' ) || ! WC_BOOKINGS_EXPERIMENTAL_ENABLED ) {
			return;
		}

		if ( ! is_admin() && ( function_exists( 'wp_is_serving_rest_request' ) && ! wp_is_serving_rest_request() ) ) {
			add_action( 'init', array( $this, 'add_to_cart_actions' ) );
		}

		// Handle the booking product types.
		add_filter( 'woocommerce_data_stores', array( $this, 'register_data_stores' ) );
		add_action( 'product_type_selector', array( $this, 'add_woocommerce_additional_product_types' ) );
		add_filter( 'woocommerce_bookings_product_types', array( $this, 'add_internal_booking_product_types' ) );

		// Toggle the legacy booking product type in the Editor preview.
		add_filter( 'enqueue_block_editor_assets', array( $this, 'remove_booking_product_type_from_editor_preview' ), 9 );
		add_action( 'enqueue_block_editor_assets', array( $this, 're_add_booking_product_type_after_editor_hydration' ), 11 );

		// Register the blocks.
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'wp_head', array( $this, 'set_blocks_color_variables' ) );

		// Enqueue block scripts.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_scripts' ), 100 );

		// Define a custom Add to Cart + Options template part with a modal.
		add_filter( '__experimental_woocommerce_' . WC_Product_Bookable_Event::PRODUCT_TYPE . '_add_to_cart_with_options_block_template_part', array( $this, 'get_add_to_cart_with_options_template_part_path' ) );
		add_filter( '__experimental_woocommerce_' . WC_Product_Bookable_Service::PRODUCT_TYPE . '_add_to_cart_with_options_block_template_part', array( $this, 'get_add_to_cart_with_options_template_part_path' ) );
		add_filter( 'render_block_woocommerce/add-to-cart-with-options', array( $this, 'hook_add_to_cart_with_options_button_to_modal' ), 10, 3 );

		// Load product collection block types.
		$this->register_product_collection_types();
	}

	/**
	 * Adds the add to cart actions.
	 *
	 * Hint: This is to backfill the add to cart templates on the legacy frontend form.
	 */
	public function add_to_cart_actions() {
		$cart_manager            = WC_Booking_Cart_Manager::get_instance();
		$add_cart_action_event   = sprintf( 'woocommerce_%s_add_to_cart', WC_Product_Bookable_Event::PRODUCT_TYPE );
		$add_cart_action_service = sprintf( 'woocommerce_%s_add_to_cart', WC_Product_Bookable_Service::PRODUCT_TYPE );

		add_action( $add_cart_action_event, array( $cart_manager, 'add_to_cart' ), 30 );
		add_action( $add_cart_action_service, array( $cart_manager, 'add_to_cart' ), 30 );
	}

	/**
	 * Sets CSS color variables based on global styles.
	 */
	public function set_blocks_color_variables() {

		/**
		 * Get the calendar primary color via the global style button configuration.
		 */
		$global_styles     = wp_get_global_styles();
		$button_color      = $global_styles['elements']['button']['color']['text'] ?? 'white';
		$button_background = $global_styles['elements']['button']['color']['background'] ?? 'black';
		printf(
			'<style>
				body {
					--wc-bookings--calendar--primary-color: %s;
					--wc-bookings--calendar--primary-background-color: %s;
				}
			</style>',
			esc_attr( $button_color ),
			esc_attr( $button_background ),
		);
	}

	/**
	 * Adds the booking additional product types to the product types.
	 *
	 * @param array $types The product types.
	 * @return array The product types.
	 */
	public function add_internal_booking_product_types( $types ) {
		return array_merge( $types, array( WC_Product_Bookable_Event::PRODUCT_TYPE, WC_Product_Bookable_Service::PRODUCT_TYPE ) );
	}

	/**
	 * Adds the booking additional product types to the product types.
	 *
	 * @param array $types The product types.
	 * @return array The product types.
	 */
	public function add_woocommerce_additional_product_types( $types ) {
		$types[ WC_Product_Bookable_Event::PRODUCT_TYPE ]   = __( 'Event product', 'woocommerce-bookings' );
		$types[ WC_Product_Bookable_Service::PRODUCT_TYPE ] = __( 'Service product', 'woocommerce-bookings' );

		return $types;
	}

	/**
	 * Removes the booking product type from the Editor preview.
	 *
	 * @internal
	 */
	public function remove_booking_product_type_from_editor_preview() {
		add_action( 'product_type_selector', array( $this, 'remove_legacy_booking_product_type' ) );
	}


	/**
	 * Re-adds the booking product type after the Editor preview is hydrated.
	 *
	 * @internal
	 */
	public function re_add_booking_product_type_after_editor_hydration() {
		remove_action( 'product_type_selector', array( $this, 'remove_legacy_booking_product_type' ) );
	}

	/**
	 * Removes the legacy booking product type.
	 *
	 * @param array $types The product types.
	 * @return array The product types.
	 */
	public function remove_legacy_booking_product_type( $types ) {
		unset( $types['booking'] );
		return $types;
	}

	/**
	 * Adds the booking product type to the product type selector.
	 *
	 * @param array $types The product types.
	 * @return array The product types.
	 */
	public function add_booking_product_type( $types ) {
		if ( isset( $types[ WC_Product_Bookable_Event::PRODUCT_TYPE ] ) ) {
			\wc_get_logger()->log( 'warning', 'Bookings: Product type "Event" already exists' );
		}

		if ( isset( $types[ WC_Product_Bookable_Service::PRODUCT_TYPE ] ) ) {
			\wc_get_logger()->log( 'warning', 'Bookings: Product type "Service" already exists' );
		}

		$types[ WC_Product_Bookable_Event::PRODUCT_TYPE ]   = __( 'Event', 'woocommerce-bookings' );
		$types[ WC_Product_Bookable_Service::PRODUCT_TYPE ] = __( 'Service', 'woocommerce-bookings' );

		return $types;
	}

	/**
	 * Registers the data stores.
	 *
	 * @param array $data_stores The Woo data stores.
	 * @return array
	 */
	public function register_data_stores( $data_stores ) {
		$data_stores[ 'product-' . WC_Product_Bookable_Event::PRODUCT_TYPE ]   = 'WC_Product_Booking_Data_Store_CPT';
		$data_stores[ 'product-' . WC_Product_Bookable_Service::PRODUCT_TYPE ] = 'WC_Product_Booking_Data_Store_CPT';
		return $data_stores;
	}

	/**
	 * Registers the blocks via metadata collection.
	 */
	public function register_blocks() {
		$path     = untrailingslashit( WC_BOOKINGS_ABSPATH ) . '/build';
		$manifest = untrailingslashit( WC_BOOKINGS_ABSPATH ) . '/build/blocks-manifest.php';

		if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
			wp_register_block_types_from_metadata_collection( $path, $manifest );
		} else {
			if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
				wp_register_block_metadata_collection( $path, $manifest );
			}
			$manifest_data = require $manifest;
			foreach ( array_keys( $manifest_data ) as $block_type ) {
				register_block_type( $path . "/{$block_type}" );
			}
		}
	}

	/**
	 * Enqueues block scripts.
	 */
	public function enqueue_block_scripts() {
		$base_path  = WC_BOOKINGS_PLUGIN_PATH;
		$asset_url  = WC_BOOKINGS_PLUGIN_URL . '/dist/block-editor.js';
		$version    = WC_BOOKINGS_VERSION;
		$asset_path = $base_path . '/dist/block-editor.asset.php';

		$dependencies = array();
		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$version      = is_array( $asset ) && isset( $asset['version'] )
				? $asset['version']
				: $version;
			$dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
				? $asset['dependencies']
				: $dependencies;
		}

		wp_enqueue_script(
			'wc-bookings-block-editor',
			$asset_url,
			$dependencies,
			$version,
			true
		);
	}

	/**
	 * Returns the custom Add to Cart + Options template part path for events and services.
	 *
	 * @return string The template part path.
	 */
	public function get_add_to_cart_with_options_template_part_path() {
		return WC_BOOKINGS_ABSPATH . 'block-templates/add-to-cart-with-options.html';
	}

	/**
	 * Modifies the Add to Cart + Options button so it opens a modal.
	 *
	 * @param string   $block_content The block content.
	 * @param array    $block The full block, including name and attributes.
	 * @param WP_Block $instance The block instance.
	 * @return string The updated block content.
	 */
	public function hook_add_to_cart_with_options_button_to_modal( $block_content, $block, $instance ) {
		$post_id = isset( $instance->context['postId'] ) ? $instance->context['postId'] : '';
		$product = wc_get_product( $post_id );

		if ( ! $product instanceof WC_Product_Bookable_Event && ! $product instanceof WC_Product_Bookable_Service ) {
			return $block_content;
		}

		$p = new WP_HTML_Tag_Processor( $block_content );

		while ( $p->next_tag() ) {
			if ( $p->get_tag() === 'BUTTON' && $p->has_class( 'add_to_cart_button' ) ) {
				$p->set_attribute( 'data-wp-on--click', 'woocommerce/add-to-cart-with-options::actions.openBookingModal' );
				$p->set_attribute( 'type', 'button' );
			}
			if ( $p->get_tag() === 'DIV' && $p->has_class( 'continue-shopping' ) ) {
				$p->next_tag( 'a' );
				$p->set_attribute( 'data-wp-on--click', 'woocommerce/add-to-cart-with-options::actions.addToCartAndContinueShopping' );
				$current_url = home_url( add_query_arg( null, null ) );
				$p->set_attribute( 'href', $current_url );
			}
			if ( $p->get_tag() === 'DIV' && $p->has_class( 'complete-booking' ) ) {
				$p->next_tag( 'a' );
				$p->set_attribute( 'data-wp-on--click', 'woocommerce/add-to-cart-with-options::actions.addToCartAndCompleteBooking' );
				$checkout_url = wc_get_checkout_url();
				$p->set_attribute( 'href', $checkout_url );
			}
		}

		return $p->get_updated_html();
	}

	/**
	 * Register product collection types.
	 */
	private function register_product_collection_types() {
		$collections = array(
			'WC_Bookings_Blocks_Collection_Services',
		);

		foreach ( $collections as $collection ) {
			if ( class_exists( $collection ) ) {
				new $collection();
			}
		}
	}
}
