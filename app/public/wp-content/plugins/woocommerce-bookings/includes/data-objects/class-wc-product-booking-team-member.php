<?php
/**
 * Class for a booking product's team member type.
 *
 * @package WooCommerce/Bookings
 */

/**
 * Class for a booking product's team member resource type.
 *
 * @since 3.0.0
 * @version 3.0.0
 */
class WC_Product_Booking_Team_Member extends WC_Product_Booking_Resource {

	/**
	 * Extra team member data array.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'role'         => '',
		'email'        => '',
		'phone_number' => '',
		'image_id'     => 0,
		'description'  => '',
		'note'         => '',
	);

	/**
	 * Get the post type for the team member.
	 *
	 * @return string
	 */
	public function get_post_type() {
		return 'bookable_team_member';
	}

	/**
	 * Get resource role.
	 *
	 * @param  string $context Context for the data.
	 * @return string
	 */
	public function get_role( $context = 'view' ) {
		return $this->get_prop( 'role', $context );
	}

	/**
	 * Set resource role.
	 *
	 * @param string $value Role value.
	 */
	public function set_role( $value ) {
		$this->set_prop( 'role', $value );
	}

	/**
	 * Get resource email.
	 *
	 * @param  string $context Context for the data.
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_prop( 'email', $context );
	}

	/**
	 * Set resource email.
	 *
	 * @param string $value Email value.
	 */
	public function set_email( $value ) {
		$this->set_prop( 'email', $value );
	}

	/**
	 * Get resource phone number.
	 *
	 * @param  string $context Context for the data.
	 * @return string
	 */
	public function get_phone_number( $context = 'view' ) {
		return $this->get_prop( 'phone_number', $context );
	}

	/**
	 * Set resource phone number.
	 *
	 * @param string $value Phone number value.
	 */
	public function set_phone_number( $value ) {
		$this->set_prop( 'phone_number', $value );
	}

	/**
	 * Get resource image ID.
	 *
	 * @param  string $context Context for the data.
	 * @return int
	 */
	public function get_image_id( $context = 'view' ) {
		return (int) $this->get_prop( 'image_id', $context );
	}

	/**
	 * Set resource image ID.
	 *
	 * @param int $value Image ID value.
	 */
	public function set_image_id( $value ) {
		$this->set_prop( 'image_id', absint( $value ) );
	}

	/**
	 * Get resource description.
	 *
	 * @param  string $context Context for the data.
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		return $this->get_prop( 'description', $context );
	}

	/**
	 * Set resource description.
	 *
	 * @param string $value Description value.
	 */
	public function set_description( $value ) {
		$this->set_prop( 'description', $value );
	}

	/**
	 * Get resource note.
	 *
	 * @param  string $context Context for the data.
	 * @return string
	 */
	public function get_note( $context = 'view' ) {
		return $this->get_prop( 'note', $context );
	}

	/**
	 * Set resource note.
	 *
	 * @param string $value Note value.
	 */
	public function set_note( $value ) {
		$this->set_prop( 'note', $value );
	}
}
