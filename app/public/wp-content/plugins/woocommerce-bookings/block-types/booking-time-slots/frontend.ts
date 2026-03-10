/**
 * WordPress dependencies
 */
import {
	store,
	getContext,
	getElement,
	getConfig,
} from '@wordpress/interactivity';

export type Context = {
	slots: object;
	currentPage: number;
	slotsPerPage: number;
	touchStartX: number;
	touchCurrentX: number;
	isDragging: boolean;
};

export type BookingTimeSlotsStore = {
	state: {
		isVisible: boolean;
		shouldShowPlaceholder: boolean;
		shouldShowPagination: boolean;
		totalPages: number;
		pages: {
			pageNumber: number;
			ariaLabel: string;
			isSelected: boolean;
		}[];
	};
	actions: {
		nextPage: () => void;
		prevPage: () => void;
		handleGoToPage: () => void;
		handleSelectTime: () => void;
		onTouchStart: ( event: TouchEvent ) => void;
		onTouchMove: ( event: TouchEvent ) => void;
		onTouchEnd: () => void;
	};
};

export type AddToCartWithOptionsContext = {
	selectedTime: string | null;
	timeslotsPage: number;
};

/**
 * Accepts HH:MM:SS needs to return e.g. 09:00 PM or 09:00 AM.
 *
 * // TODO: Replace this with formatted date from the API so we streamline locale and formatting settings.
 *
 * @param timeString HH:MM:SS
 * @return 09:00 PM or 09:00 AM
 */
const formatTimeString = ( timeString: string ) => {
	const [ hours, minutes ] = timeString.split( ':' ).map( Number );
	const ampm = hours >= 12 ? 'pm' : 'am';
	const hours12 = hours % 12 || 12;
	return `${ String( hours12 ).padStart( 2, '0' ) }:${ String(
		minutes
	).padStart( 2, '0' ) } ${ ampm }`;
};

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// We store the selected data in the Add to Cart + Options store so it's
// accessible in sibling blocks. It's necessary to handle submitting the form.
const {
	state: addToCartWithOptionsState,
	actions: addToCartWithOptionsActions,
} = store(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			get selectedTime() {
				const context = getContext< AddToCartWithOptionsContext >();
				return context.selectedTime;
			},
			get currentTimeSlotsPage() {
				const context = getContext< Context >();
				return context.timeslotsPage || 1;
			},
		},
		actions: {
			selectTime( time: string | null ) {
				const context = getContext< AddToCartWithOptionsContext >();
				context.selectedTime = time;
			},
			setTimeSlotsPage( page: number ) {
				const context = getContext< Context >();
				context.timeslotsPage = page;
			},
		},
	},
	{ lock: universalLock }
);

const { state, actions } = store< BookingTimeSlotsStore >(
	'woocommerce-bookings/booking-time-slots',
	{
		state: {
			get isVisible() {
				return !! getConfig( 'woocommerce/add-to-cart-with-options' )
					?.wcBookingsRequiresTimeSelection;
			},
			get shouldShowPlaceholder() {
				return ! addToCartWithOptionsState.selectedDate;
			},
			get shouldShowPagination() {
				return state.totalPages > 1;
			},
			get totalPages() {
				const slotsPerPage = getContext< Context >().slotsPerPage;
				return Math.ceil( state.slots.length / slotsPerPage );
			},
			get pages() {
				return Array.from(
					{ length: state.totalPages },
					( _, index ) => ( {
						pageNumber: index + 1,
						ariaLabel: `Go to page ${ index + 1 }`,
						isSelected:
							index + 1 ===
							addToCartWithOptionsState.currentTimeSlotsPage,
					} )
				);
			},
			get slots() {
				const availabilityCache =
					addToCartWithOptionsState.availabilityCache;
				if ( ! availabilityCache ) {
					return [];
				}

				const selectedTime = addToCartWithOptionsState.selectedTime;
				const selectedDate = addToCartWithOptionsState.selectedDate
					? new Date( addToCartWithOptionsState.selectedDate )
					: null;
				if ( ! selectedDate ) {
					return [];
				}

				selectedDate?.setHours( 0, 0, 0, 0 );

				// get YYYY-MM from string addToCartWithOptionsState.selectedDate.
				const monthKey =
					addToCartWithOptionsState.selectedDate.substring( 0, 7 );
				if ( ! availabilityCache[ monthKey ] ) {
					return [];
				}
				const slots =
					availabilityCache[ monthKey ][
						addToCartWithOptionsState.selectedDate
					];
				if ( ! slots ) {
					return [];
				}

				const formattedSlots = Object.entries( slots ).map(
					( [ time, capacity ] ) => {
						return {
							time,
							capacity,
							isSelected: selectedTime && selectedTime === time,
							ariaLabel: `Select ${ formatTimeString( time ) }`,
							timeString: formatTimeString( time ),
						};
					}
				);

				// Prevent selecting past times.
				const todayMidnight = new Date();
				todayMidnight.setHours( 0, 0, 0, 0 );
				const minDate = addToCartWithOptionsState.wcBookingsMinDate;
				const isTodaySelected =
					todayMidnight.getTime() === selectedDate?.getTime();

				const finalSlots = isTodaySelected
					? formattedSlots.filter( ( slot ) => {
							const slotDateTime = new Date(
								`${ addToCartWithOptionsState.selectedDate } ${ slot.time }`
							);
							return slotDateTime >= minDate;
					  } )
					: formattedSlots;

				// If selected value was in the past and filtered out, reset the selected time state.
				if (
					isTodaySelected &&
					addToCartWithOptionsState.selectedTime
				) {
					const isSelected = finalSlots.some(
						( slot ) => slot.isSelected
					);
					if ( ! isSelected ) {
						addToCartWithOptionsActions.selectTime( null );
					}
				}

				if ( ! finalSlots.length ) {
					return [];
				}

				return finalSlots;
			},
			get slotsForPage() {
				const currentPage =
					addToCartWithOptionsState.currentTimeSlotsPage;
				const slotsPerPage = getContext< Context >().slotsPerPage;
				return state.slots.slice(
					( currentPage - 1 ) * slotsPerPage,
					currentPage * slotsPerPage
				);
			},
			get isPreviousPageDisabled() {
				return addToCartWithOptionsState.currentTimeSlotsPage === 1;
			},
			get isNextPageDisabled() {
				return (
					addToCartWithOptionsState.currentTimeSlotsPage ===
					state.totalPages
				);
			},
		},
		actions: {
			nextPage() {
				const nextPage =
					addToCartWithOptionsState.currentTimeSlotsPage ===
					state.totalPages
						? 1
						: addToCartWithOptionsState.currentTimeSlotsPage + 1;
				addToCartWithOptionsActions.setTimeSlotsPage( nextPage );
			},
			prevPage() {
				const nextPage =
					addToCartWithOptionsState.currentTimeSlotsPage === 1
						? state.totalPages
						: addToCartWithOptionsState.currentTimeSlotsPage - 1;
				addToCartWithOptionsActions.setTimeSlotsPage( nextPage );
			},
			handleGoToPage() {
				const elementButton = getElement()?.ref as HTMLElement;
				if ( ! elementButton ) {
					return;
				}
				const pageNumber = parseInt(
					elementButton.getAttribute( 'data-pageNumber' ) as string,
					10
				);
				if ( pageNumber < 1 || pageNumber > state.totalPages ) {
					return;
				}

				addToCartWithOptionsActions.setTimeSlotsPage( pageNumber );
			},

			handleSelectTime() {
				const timeElement = getElement()?.ref as HTMLElement;
				if ( ! timeElement ) {
					return;
				}

				const time = timeElement.dataset.time;

				// Do nothing if clicked on the same time.
				if ( addToCartWithOptionsState.selectedTime === time ) {
					return;
				}

				addToCartWithOptionsActions.selectTime( time );

				// Focus confirm button.
				const form = timeElement.closest( 'form' );
				const confirmButtonsBlock = form?.querySelector(
					'.wc-bookings-modal-buttons'
				);
				if ( confirmButtonsBlock ) {
					setTimeout( () => {
						const firstButton = (
							confirmButtonsBlock as HTMLElement
						 )?.querySelector( 'a' );
						if ( firstButton ) {
							( firstButton as HTMLElement )?.focus();
						}
					}, 0 );
				}
			},

			onTouchStart( event: TouchEvent ) {
				const context = getContext< Context >();
				const { clientX } = event.touches[ 0 ];

				context.touchStartX = clientX;
				context.touchCurrentX = clientX;
				context.isDragging = true;
			},
			onTouchMove( event: TouchEvent ) {
				const context = getContext< Context >();
				if ( ! context.isDragging ) {
					return;
				}

				const { clientX } = event.touches[ 0 ];
				context.touchCurrentX = clientX;

				// Only prevent default if there's significant horizontal movement
				const delta = clientX - context.touchStartX;
				if ( Math.abs( delta ) > 10 ) {
					event.preventDefault();
				}
			},
			onTouchEnd: () => {
				const context = getContext< Context >();
				if ( ! context.isDragging ) {
					return;
				}

				const SNAP_THRESHOLD = 0.3;
				const delta = context.touchCurrentX - context.touchStartX;
				const element = getElement()?.ref;
				const calendarWidth = element?.offsetWidth || 0;

				// Only trigger swipe actions if there was significant movement
				if ( Math.abs( delta ) > calendarWidth * SNAP_THRESHOLD ) {
					if ( delta > 0 ) {
						actions.prevPage();
					} else if ( delta < 0 ) {
						actions.nextPage();
					}
				}

				// Reset touch state
				context.isDragging = false;
				context.touchStartX = 0;
				context.touchCurrentX = 0;
			},
		},
		callbacks: {
			preselectInitialTime() {
				if (
					! addToCartWithOptionsState.selectedTime &&
					state.slots.length
				) {
					addToCartWithOptionsActions.selectTime(
						state.slots[ 0 ]?.time
					);
				}
			},
		},
	}
);
