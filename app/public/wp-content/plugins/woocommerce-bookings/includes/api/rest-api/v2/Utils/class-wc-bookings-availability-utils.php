<?php
/**
 * Availability utility functions for WooCommerce Bookings REST API.
 *
 * @package WooCommerce\Bookings\Rest\Utils
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility class for availability operations.
 */
class WC_Bookings_Availability_Utils {

	/**
	 * Validate time slots.
	 *
	 * @param array $time_slots Array of time slots.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public static function validate_time_slots( $time_slots ) {
		foreach ( $time_slots as $time_slot ) {
			if ( ! isset( $time_slot['start'] ) || ! isset( $time_slot['end'] ) ) {
				return new WP_Error( 'missing_time_fields', __( 'Each time slot must have start and end times.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			// Validate time format (HH:MM).
			if ( ! preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time_slot['start'] ) ) {
				return new WP_Error( 'invalid_start_time', __( 'Start time must be in HH:MM format.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			if ( ! preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time_slot['end'] ) ) {
				return new WP_Error( 'invalid_end_time', __( 'End time must be in HH:MM format.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}

			if ( $time_slot['start'] > $time_slot['end'] ) {
				return new WP_Error( 'invalid_time_range', __( 'Start time must be before end time.', 'woocommerce-bookings' ), array( 'status' => 400 ) );
			}
		}

		// Check for overlapping time slots within the same day.
		if ( count( $time_slots ) > 1 ) {
			$overlap_error = self::check_time_slot_overlaps( $time_slots );
			if ( is_wp_error( $overlap_error ) ) {
				return $overlap_error;
			}
		}

		return true;
	}

	/**
	 * Check for overlapping time slots within the same day.
	 *
	 * @param array $time_slots Array of time slots for a single day.
	 * @return bool|WP_Error True if no overlaps, WP_Error if overlaps found.
	 */
	public static function check_time_slot_overlaps( $time_slots ) {
		// Convert time slots to comparable format and sort by start time.
		$sorted_slots = array();
		foreach ( $time_slots as $index => $slot ) {
			$sorted_slots[] = array(
				'index'     => $index,
				'start'     => $slot['start'],
				'end'       => $slot['end'],
				'start_min' => self::time_to_minutes( $slot['start'] ),
				'end_min'   => self::time_to_minutes( $slot['end'] ),
			);
		}

		// Sort by start time.
		usort(
			$sorted_slots,
			function ( $a, $b ) {
				return $a['start_min'] - $b['start_min'];
			}
		);

		// Check for overlaps.
		$slot_count = count( $sorted_slots );
		for ( $i = 0; $i < $slot_count - 1; $i++ ) {
			$current = $sorted_slots[ $i ];
			$next    = $sorted_slots[ $i + 1 ];

			// Check if current slot overlaps with next slot.
			if ( $current['end_min'] > $next['start_min'] ) {
				return new WP_Error(
					'overlapping_time_slots',
					sprintf(
						/* translators: 1: first time slot, 2: second time slot */
						__( 'Time slots overlap: %1$s-%2$s and %3$s-%4$s.', 'woocommerce-bookings' ),
						$current['start'],
						$current['end'],
						$next['start'],
						$next['end']
					),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Convert time string (HH:MM) to minutes since midnight.
	 *
	 * @param string $time Time in HH:MM format.
	 * @return int Minutes since midnight.
	 */
	public static function time_to_minutes( $time ) {
		list( $hours, $minutes ) = explode( ':', $time );
		return (int) $hours * 60 + (int) $minutes;
	}

	/**
	 * Process time slots and generate availability rules with gaps.
	 *
	 * @param array  $time_slots Array of time slots.
	 * @param string $type The type field for the rules.
	 * @param string $rule_type The rule_type field for the rules.
	 * @param int    $priority The priority for the rules.
	 * @param string $date Optional date for date-specific rules.
	 * @return array Array of availability rules.
	 */
	private static function process_time_slots( $time_slots, $type, $rule_type, $priority, $date = null ) {
		$results = array();

		if ( ! is_array( $time_slots ) ) {
			return $results;
		}

		// If no time slots, consider the whole day/date unavailable.
		if ( empty( $time_slots ) ) {

			$rule = array(
				'type'      => $type,
				'rule_type' => $rule_type,
				'priority'  => $priority,
				'bookable'  => 'no',
				'from'      => '00:00',
				'to'        => '23:59',
			);

			// Add date fields if provided.
			if ( $date ) {
				$rule['from_date'] = $date;
				$rule['to_date']   = $date;
			}

			$results[] = $rule;
			return $results;
		}

		// Sort slots by start time.
		usort(
			$time_slots,
			function ( $a, $b ) {
				return strcmp( $a['start'], $b['start'] );
			}
		);

		$previous_end = '00:00';

		foreach ( $time_slots as $slot ) {
			if ( ! is_array( $slot ) ) {
				continue;
			}

			if ( ! isset( $slot['start'] ) || ! isset( $slot['end'] ) ) {
				continue;
			}

			$start = $slot['start'];
			$end   = $slot['end'];

			// Add a "no" slot if there is a gap before this one.
			if ( $previous_end < $start ) {
				$rule = array(
					'type'      => $type,
					'rule_type' => $rule_type,
					'priority'  => $priority,
					'bookable'  => 'no',
					'from'      => $previous_end,
					'to'        => $start,
				);

				// Add date fields if provided.
				if ( $date ) {
					$rule['from_date'] = $date;
					$rule['to_date']   = $date;
				}

				$results[] = $rule;
			}

			// Add the "yes" slot.
			$rule = array(
				'type'      => $type,
				'rule_type' => $rule_type,
				'priority'  => $priority,
				'bookable'  => 'yes',
				'from'      => $start,
				'to'        => $end,
			);

			// Add date fields if provided.
			if ( $date ) {
				$rule['from_date'] = $date;
				$rule['to_date']   = $date;
			}

			$results[]    = $rule;
			$previous_end = $end;
		}

		// Add final "no" slot until midnight.
		if ( '00:00' !== $previous_end ) {
			$rule = array(
				'type'      => $type,
				'rule_type' => $rule_type,
				'priority'  => $priority,
				'bookable'  => 'no',
				'from'      => $previous_end,
				'to'        => '00:00',
			);

			// Add date fields if provided.
			if ( $date ) {
				$rule['from_date'] = $date;
				$rule['to_date']   = $date;
			}

			$results[] = $rule;
		}

		return $results;
	}

	/**
	 * Map date overrides availability rules to expected DB format.
	 *
	 * Expected format:
	 *
	 * array(
	 *     'date'       => '2025-09-23',
	 *     'time_slots' => array(
	 *         array(
	 *             'start' => '09:00',
	 *             'end' => '12:00',
	 *         ),
	 *         array(
	 *             'start' => '14:00',
	 *             'end' => '17:00',
	 *         ),
	 *     ),
	 * )
	 *
	 * Expected return:
	 *
	 * array(
	 *   array(
	 *           'type'       => 'custom:daterange',
	 *           'priority'  => 90,
	 *           'bookable'  => 'no',
	 *           'from_date' => '2025-09-23',
	 *           'to_date'   => '2025-09-23',
	 *           'from'      => '00:00',
	 *           'to'        => '09:00',
	 *     ),
	 *   array(
	 *           'type'       => 'custom:daterange',
	 *           'priority'  => 90,
	 *           'bookable'  => 'yes',
	 *            'from_date' => '2025-09-23',
	 *           'to_date'   => '2025-09-23',
	 *           'from'      => '09:00',
	 *           'to'        => '12:00',
	 *     ),
	 *   array(
	 *           'type'       => 'custom:daterange',
	 *           'priority'  => 90,
	 *           'bookable'  => 'no',
	 *           'from_date' => '2025-09-23',
	 *           'to_date'   => '2025-09-23',
	 *           'from'      => '12:00',
	 *           'to'        => '14:00',
	 *     ),
	 *   array(
	 *           'type'       => 'custom:daterange',
	 *           'priority'  => 90,
	 *           'bookable'  => 'yes',
	 *           'from_date' => '2025-09-23',
	 *           'to_date'   => '2025-09-23',
	 *           'from'      => '14:00',
	 *           'to'        => '17:00',
	 *     ),
	 *   array(
	 *           'type'       => 'custom:daterange',
	 *           'priority'  => 90,
	 *           'bookable'  => 'no',
	 *           'from_date' => '2025-09-23',
	 *           'to_date'   => '2025-09-23',
	 *           'from'      => '14:00',
	 *           'to'        => '00:00',
	 *     ),
	 * )
	 *
	 * @param array $availability_rules Availability rules.
	 * @return array
	 */
	public static function map_date_overrides_rules( $availability_rules ) {
		$results = array();

		foreach ( $availability_rules as $availability_rule ) {

			if ( ! is_array( $availability_rule ) ) {
				continue;
			}

			if ( ! isset( $availability_rule['date'] ) || ! isset( $availability_rule['time_slots'] ) ) {
				continue;
			}

			$date       = $availability_rule['date'];
			$time_slots = $availability_rule['time_slots'];

			if ( ! is_array( $time_slots ) ) {
				continue;
			}

			$slot_results = self::process_time_slots(
				$time_slots,
				'custom:daterange',
				'date_override',
				90,
				$date
			);

			$results = array_merge( $results, $slot_results );
		}

		return $results;
	}

	/**
	 * Map weekly availability rules to expected DB format.
	 *
	 * Expected format:
	 *
	 * array(
	 *     '1' => array( // Monday
	 *         array(
	 *             'start' => '09:00',
	 *             'end' => '12:00'
	 *         ),
	 *         array(
	 *             'start' => '14:00',
	 *             'end' => '17:00'
	 *         ),
	 *     ),
	 * )
	 *
	 * Expected return:
	 *
	 * array(
	 *   array(
	 *           'type'      => 'time:1',
	 *           'priority'  => 100,
	 *           'bookable'  => 'yes',
	 *           'from'      => '09:00',
	 *           'to'        => '12:00',
	 *     ),
	 *   array(
	 *           'type'      => 'time:1',
	 *           'priority'  => 100,
	 *           'bookable'  => 'no',
	 *           'from'      => '00:00',
	 *           'to'        => '09:00',
	 *     ),
	 *   array(
	 *           'type'      => 'time:1',
	 *           'priority'  => 100,
	 *           'bookable'  => 'no',
	 *           'from'      => '12:00',
	 *           'to'        => '14:00',
	 *     ),
	 *   array(
	 *           'type'      => 'time:1',
	 *           'priority'  => 100,
	 *           'bookable'  => 'yes',
	 *           'from'      => '14:00',
	 *           'to'        => '17:00',
	 *     ),
	 *   array(
	 *           'type'      => 'time:1',
	 *           'priority'  => 100,
	 *           'bookable'  => 'no',
	 *           'from'      => '17:00',
	 *           'to'        => '00:00',
	 *     ),
	 * )
	 *
	 * @param array $availability_rules Availability rules.
	 * @return array
	 */
	public static function map_weekly_availability_rules( $availability_rules ) {
		$results = array();

		foreach ( $availability_rules as $day_index => $time_slots ) {
			$range_type = "time:{$day_index}";

			if ( ! is_array( $time_slots ) ) {
				continue;
			}

			$slot_results = self::process_time_slots(
				$time_slots,
				$range_type,
				'weekly',
				100
			);

			$results = array_merge( $results, $slot_results );
		}

		return $results;
	}
}
