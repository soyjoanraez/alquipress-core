/**
 * Recursively searches up the DOM tree to find the first non-transparent color of the specified type.
 *
 * @param element   - The DOM element to check for color.
 * @param colorType - Whether to check for color or background color.
 *
 * @return The computed RGB color string or null if not found.
 */
function getClosestColor(
	element: Element | null,
	colorType: 'color' | 'backgroundColor'
): string | null {
	if ( ! element ) {
		return null;
	}
	const computedColor = window.getComputedStyle( element )[ colorType ];

	// Skip transparent or default "empty" colors.
	if (
		computedColor !== 'rgba(0, 0, 0, 0)' &&
		computedColor !== 'transparent'
	) {
		// Extract RGB values from the color string.
		const rgbValues = computedColor.match( /\d+/g );

		if ( ! rgbValues || rgbValues.length < 3 ) {
			return null;
		}

		const [ red, green, blue ] = rgbValues.slice( 0, 3 );
		return `rgb(${ red }, ${ green }, ${ blue })`;
	}

	// If current element has transparent color, check parent element.
	return getClosestColor( element.parentElement, colorType );
}

/**
 * Sets the appropriate styles for variation selector pills to ensure
 * visibility in both light and dark themes.
 *
 * This function swaps the text and background colors for selected pills
 * to create better contrast in all theme environments.
 */
function setStyles(): void {
	// For simplicity, we only consider the background color of the calendar.
	const calendarContainer = document.querySelector( '.wc-bookings-calendar' );

	if ( ! calendarContainer ) {
		return;
	}

	const style = document.createElement( 'style' );

	const selectedCalendarBackgroundColor =
		getClosestColor( calendarContainer, 'backgroundColor' ) || '#fff';
	const selectedCalendarColor =
		getClosestColor( calendarContainer, 'color' ) || '#000';

	// We use :where here to reduce specificity so customized colors and theme CSS take priority.
	style.appendChild(
		document.createTextNode(
			`:where(.wc-bookings-calendar),
			:where(.wc-bookings-modal) {
				--wc-bookings--calendar--color: ${ selectedCalendarColor };
				--wc-bookings--calendar--background-color: ${ selectedCalendarBackgroundColor };
			}`
		)
	);

	document.head.appendChild( style );
}

export default setStyles;
