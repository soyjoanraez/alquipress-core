<?php
/**
 * The "Services" Collection Controller.
 *
 * @package WooCommerce\Bookings
 * @since   3.0.1
 * @version 3.0.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Bookings_Blocks_Collection_Services class.
 */
class WC_Bookings_Blocks_Collection_Services {

	/**
	 * The collection name.
	 *
	 * @var string
	 */
	private $collection_name = 'woocommerce-bookings/product-collection/services';

	/**
	 * The product type to filter by.
	 *
	 * @var string
	 */
	private $product_type = WC_Product_Bookable_Service::PRODUCT_TYPE;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'query_loop_block_query_vars', array( $this, 'handle_query_vars' ), 20, 2 );
		add_filter( 'rest_product_query', array( $this, 'update_rest_query_in_editor' ), 20, 2 );
	}

	/**
	 * Update the query for the product query block in Editor.
	 *
	 * @param array           $query   Query args.
	 * @param WP_REST_Request $request Request.
	 * @return array Modified query args.
	 */
	public function update_rest_query_in_editor( $query, $request ): array {
		if ( ! $this->is_editor_request_valid( $request ) ) {
			return $query;
		}

		return $this->add_product_type_filter( $query );
	}

	/**
	 * Handle query vars for front-end product collection blocks.
	 *
	 * @param array    $query The WordPress WP_Query arguments.
	 * @param WP_Block $block The block being rendered.
	 * @return array Modified query args.
	 */
	public function handle_query_vars( $query, $block ) {
		if ( ! $this->is_valid_block( $block ) ) {
			return $query;
		}

		return $this->add_product_type_filter( $query );
	}

	/**
	 * Check if the REST API request is for this collection in the editor.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	private function is_editor_request_valid( $request ): bool {
		$is_product_collection_block = $request->get_param( 'isProductCollectionBlock' );
		if ( ! $is_product_collection_block ) {
			return false;
		}

		$query_context = $request->get_param( 'productCollectionQueryContext' );
		if ( empty( $query_context ) || ! is_array( $query_context ) ) {
			return false;
		}

		$collection_name = $query_context['collection'] ?? '';

		return $this->collection_name === $collection_name;
	}

	/**
	 * Check if the Product Collection block is the current variation.
	 *
	 * @param WP_Block $block The block being rendered.
	 * @return bool
	 */
	private function is_valid_block( $block ): bool {
		if ( ! is_array( $block->context ) ) {
			return false;
		}

		$is_product_collection_block = $block->context['query']['isProductCollectionBlock'] ?? false;
		if ( ! $is_product_collection_block ) {
			return false;
		}

		if ( ! isset( $block->context['collection'] ) ) {
			return false;
		}

		return $this->collection_name === $block->context['collection'];
	}

	/**
	 * Add product type filter to the tax query.
	 *
	 * @param array $query Query args.
	 * @return array Modified query args.
	 */
	private function add_product_type_filter( array $query ): array {

		$tax_query          = $this->ensure_tax_query( $query['tax_query'] ?? array() );
		$tax_query          = $this->add_product_type_tax_query( $tax_query );
		$tax_query          = $this->ensure_tax_query_relation( $tax_query );
		$query['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query

		return $query;
	}

	/**
	 * Ensure tax_query is a valid array.
	 *
	 * @param mixed $tax_query Existing tax_query value.
	 * @return array Valid tax_query array.
	 */
	private function ensure_tax_query( $tax_query ): array {
		if ( empty( $tax_query ) || ! is_array( $tax_query ) ) {
			return array();
		}

		return $tax_query;
	}

	/**
	 * Add product type filter to tax query.
	 *
	 * @param array $tax_query Existing tax_query array.
	 * @return array Modified tax_query array.
	 */
	private function add_product_type_tax_query( array $tax_query ): array {
		$tax_query[] = array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => array( $this->product_type ),
			'operator' => 'IN',
		);

		return $tax_query;
	}

	/**
	 * Ensure tax_query has a relation when multiple queries exist.
	 *
	 * @param array $tax_query Existing tax_query array.
	 * @return array Modified tax_query array with relation if needed.
	 */
	private function ensure_tax_query_relation( array $tax_query ): array {
		$query_count = count( array_filter( $tax_query, 'is_array' ) );

		if ( $query_count > 1 && ! isset( $tax_query['relation'] ) ) {
			$tax_query['relation'] = 'AND';
		}

		return $tax_query;
	}
}
