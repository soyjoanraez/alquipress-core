<?php

/**
 * WC Bookable Product Resource Data Store: Stored in CPT.
 */
class WC_Product_Booking_Resource_Data_Store_CPT extends WC_Data_Store_WP {

	/**
	 * Stores updated props.
	 *
	 * @var array
	 */
	protected $updated_props = array();

	/**
	 * Flush transients for all products related to a specific resource.
	 *
	 * @param WC_Product_Booking_Resource $resource
	 */
	public function flush_resource_transients( $resource ) {
		global $wpdb;

		$product_ids = wp_parse_id_list(
			$wpdb->get_col(
				$wpdb->prepare(
					"
			SELECT product_id
			FROM {$wpdb->prefix}wc_booking_relationships AS relationships
			WHERE relationships.resource_id = %d
			ORDER BY sort_order ASC
		",
					$resource->get_id()
				)
			)
		);

		foreach ( $product_ids as $product_id ) {
			WC_Bookings_Cache::delete_booking_slots_transient( $product_id );
		}
	}

	/**
	 * Create resource.
	 *
	 * @param WC_Product_Booking_Resource $resource
	 */
	public function create( &$resource ) {
		$id = wp_insert_post(
			/**
			 * Filters the data for a new product booking resource.
			 *
			 * @since 3.0.0
			 *
			 * @param array $data The data for the new product booking resource.
			 */
			apply_filters(
				'woocommerce_new_product_booking_resource_data',
				array(
					'post_type'    => $resource->get_post_type(),
					'post_title'   => $resource->get_name(),
					'menu_order'   => $resource->get_sort_order(),
					'post_content' => '',
					'post_status'  => $resource->get_status(),
					'post_author'  => get_current_user_id(),
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$resource->set_id( $id );

			$this->update_post_meta( $resource, true );
			$this->flush_resource_transients( $resource );

			$resource->save_meta_data();
			$resource->apply_changes();

			/**
			 * Fires after a new product booking resource is created.
			 *
			 * @since 3.0.0
			 *
			 * @param int $id The resource ID.
			 * @param WC_Product_Booking_Resource $resource The resource object.
			 */
			do_action( 'woocommerce_new_product_booking_resource', $id, $resource );
		}
	}

	/**
	 * Method to read a resource from the database.
	 *
	 * @param WC_Product_Booking_Resource &$resource Resource object.
	 *
	 * @throws Exception When resource cannot be read.
	 */
	public function read( &$resource ) {
		$resource->set_defaults();
		$post_object = get_post( $resource->get_id() );

		if ( ! $resource->get_id() || ! $post_object || ! in_array( $post_object->post_type, wc_booking_get_product_resource_post_types(), true ) ) {
			throw new Exception( sprintf( esc_html__( 'The booking resource object could not be read. This probably means the resource no longer exists or does not match the expected type. Here is the booking resource object: %s', 'woocommerce-bookings' ), print_r( $post_object, true ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.Security.EscapeOutput.ExceptionNotEscaped, WordPress.WP.I18n.MissingTranslatorsComment
		}

		$resource->set_props(
			array(
				'name'       => $post_object->post_title,
				'sort_order' => $post_object->menu_order,
				'status'     => $post_object->post_status,
			)
		);

		$this->read_resource_data( $resource );
		$this->read_extra_data( $resource );
		$resource->set_object_read( true );

		/**
		 * Fires after a product booking resource is read.
		 *
		 * @since 3.0.0
		 *
		 * @param int $id The resource ID.
		 * @param WC_Product_Booking_Resource $resource The resource object.
		 */
		do_action( 'woocommerce_product_booking_resource_read', $resource->get_id() );
	}

	/**
	 * Method to update a resource in the database.
	 *
	 * @param WC_Product_Booking_Resource $resource The resource object.
	 */
	public function update( &$resource ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.resourceFound

		$resource->save_meta_data();
		$changes = $resource->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'name', 'sort_order', 'status' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'ID'          => $resource->get_id(),
				'post_title'  => $resource->get_name( 'edit' ),
				'menu_order'  => $resource->get_sort_order( 'edit' ),
				'post_status' => $resource->get_status( 'edit' ),
			);

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $resource->get_id() ) );
				clean_post_cache( $resource->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $resource->get_id() ), $post_data ) );
			}
			$resource->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.

		} else { // Only update post modified time to record this save event.
			$GLOBALS['wpdb']->update(
				$GLOBALS['wpdb']->posts,
				array(
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', 1 ),
				),
				array(
					'ID' => $resource->get_id(),
				)
			);
			clean_post_cache( $resource->get_id() );
		}

		$this->update_post_meta( $resource );

		$resource->apply_changes();
		$this->flush_resource_transients( $resource );
	}

	/**
	 * Delete a resource.
	 *
	 * @param WC_Product_Booking_Resource $resource The resource object.
	 * @param array                       $args Array of args to pass to the delete method.
	 */
	public function delete( &$resource, $args = array() ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.resourceFound
		$id        = $resource->get_id();
		$post_type = $resource->get_post_type();

		$args = wp_parse_args(
			$args,
			array(
				'force_delete' => false,
			)
		);

		if ( ! $id ) {
			return;
		}

		// Backwards compatibility prior to v3.
		if ( 'bookable_resource' === $post_type ) {
			$args['force_delete'] = true;
		}

		if ( $args['force_delete'] ) {
			/**
			 * Fires before a product booking resource is deleted.
			 *
			 * @since 3.0.0
			 *
			 * @param int $id The resource ID.
			 * @param WC_Product_Booking_Resource $resource The resource object.
			 */
			do_action( 'woocommerce_before_delete_' . $post_type, $id );

			wp_delete_post( $id, true );
			$resource->set_id( 0 );

			/**
			 * Fires after a product booking resource is deleted.
			 *
			 * @since 3.0.0
			 *
			 * @param int $id The resource ID.
			 * @param WC_Product_Booking_Resource $resource The resource object.
			 */
			do_action( 'woocommerce_delete_' . $post_type, $id );
		} else {
			/**
			 * Fires before a product booking resource is trashed.
			 *
			 * @since 3.0.0
			 *
			 * @param int $id The resource ID.
			 * @param WC_Product_Booking_Resource $resource The resource object.
			 */
			do_action( 'woocommerce_before_trash_' . $post_type, $id );
			wp_trash_post( $id );
			$resource->set_status( 'trash' );

			/**
			 * Fires after a product booking resource is trashed.
			 *
			 * @since 3.0.0
			 *
			 * @param int $id The resource ID.
			 * @param WC_Product_Booking_Resource $resource The resource object.
			 */
			do_action( 'woocommerce_after_trash_' . $post_type, $id );
		}

		$this->flush_resource_transients( $resource );
	}

	/**
	 * Read resource data.
	 *
	 * @param WC_Product_Booking_Resource $resource Resource object.
	 */
	protected function read_resource_data( &$resource ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.resourceFound
		$resource_availability = get_post_meta( $resource->get_id(), '_wc_booking_availability', true );
		$resource->set_props(
			array(
				'availability' => $resource_availability ? $resource_availability : array(),
				'qty'          => get_post_meta( $resource->get_id(), 'qty', true ),
			)
		);
	}

	/**
	 * Read extra data associated with the resource.
	 *
	 * @param WC_Product_Booking_Resource $resource Resource object.
	 */
	protected function read_extra_data( &$resource ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.resourceFound
		foreach ( $resource->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $resource, $function ) ) ) {
				$resource->{$function}( get_post_meta( $resource->get_id(), $key, true ) );
			}
		}
	}

	/**
	 * Helper method that updates all the post meta.
	 *
	 * @param WC_Product_Booking_Resource $resource Resource object.
	 * @param bool                        $force Force update. Used during create.
	 */
	protected function update_post_meta( &$resource, $force = false ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.resourceFound
		$meta_key_to_props = array(
			'_wc_booking_availability' => 'availability',
			'qty'                      => 'qty',
		);

		// Make sure to take extra data (like product url or text for external products) into account.
		$extra_data_keys = $resource->get_extra_data_keys();

		foreach ( $extra_data_keys as $key ) {
			$meta_key_to_props[ $key ] = $key;
		}

		$props_to_update = $force ? $meta_key_to_props : $this->get_props_to_update( $resource, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$getter  = 'get_' . $prop;
			$value   = method_exists( $resource, $getter ) ? $resource->{$getter}( 'edit' ) : null;
			$value   = is_string( $value ) ? wp_slash( $value ) : $value;
			$updated = $this->update_or_delete_post_meta( $resource, $meta_key, $value );

			if ( $updated ) {
				$this->updated_props[] = $prop;
			}
		}
	}

	/**
	 * Get all booking product resources.
	 *
	 * @return array
	 */
	public static function get_bookable_product_resource_ids() {
		$ids = get_posts(
			apply_filters( // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
				'get_booking_resources_args',
				array(
					'post_status'      => 'publish',
					'post_type'        => wc_booking_get_product_resource_post_types(),
					'posts_per_page'   => -1,
					'orderby'          => 'menu_order',
					'order'            => 'asc',
					'suppress_filters' => true,
					'fields'           => 'ids',
				)
			)
		);
		return wp_parse_id_list( $ids );
	}
}
