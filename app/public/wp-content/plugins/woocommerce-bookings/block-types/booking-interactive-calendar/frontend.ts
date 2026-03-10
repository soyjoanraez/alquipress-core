/**
 * WordPress dependencies
 */
import {
	store,
	getContext,
	getElement,
	getConfig,
	withSyncEvent,
} from '@wordpress/interactivity';

export type CalendarDay = {
	key: string;
	dayNumber: number;
	dateString: string;
	isCurrentMonth: boolean;
	isToday: boolean;
	isSelected: boolean;
	isDisabled: boolean;
	ariaLabel: string;
	tabIndex: number;
};

export type BookingInteractiveCalendarStore = {
	state: {
		viewMonthName: string;
		prevMonthLabel: string;
		isPreviousMonthDisabled: boolean;
		nextMonthLabel: string;
		isNextMonthDisabled: boolean;
		calendarDays: CalendarDay[];
		calendarKey: string;
	};
	actions: {
		navigateToPreviousMonth: () => void;
		navigateToNextMonth: () => void;
		onTouchStart: ( event: TouchEvent ) => void;
		onTouchMove: ( event: TouchEvent ) => void;
		onTouchEnd: () => void;
	};
};

export type Context = {
	viewMonth: number;
	viewYear: number;
	monthNames: string[];
	weekStartsOn: number;
	touchStartX: number;
	touchCurrentX: number;
	isDragging: boolean;
};

export type BookingInteractiveCalendarContext = {
	selectedDate: string;
};

const formatDateString = ( date: Date ) => {
	return `${ date.getFullYear() }-${ String( date.getMonth() + 1 ).padStart(
		2,
		'0'
	) }-${ String( date.getDate() ).padStart( 2, '0' ) }`;
};

const formatMonthString = ( date: Date ) => {
	return `${ date.getFullYear() }-${ String( date.getMonth() + 1 ).padStart(
		2,
		'0'
	) }`;
};

/**
 * Get the previous month and year.
 *
 * @param month The month.
 * @param year  The year.
 * @return The previous month and year.
 */
const getPreviousMonth = ( month: number, year: number ) => {
	const previousMonth = month === 1 ? 12 : month - 1;
	const previousYear = month === 1 ? year - 1 : year;
	return { previousMonth, previousYear };
};

/**
 * Get the next month and year.
 *
 * @param month The month.
 * @param year  The year.
 * @return The next month and year.
 */
const getNextMonth = ( month: number, year: number ) => {
	const nextMonth = month === 12 ? 1 : month + 1;
	const nextYear = month === 12 ? year + 1 : year;
	return { nextMonth, nextYear };
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
			get selectedDate() {
				const context =
					getContext< BookingInteractiveCalendarContext >();
				return context.selectedDate;
			},
		},
		actions: {
			selectDate( dateString: string | null ) {
				const context = getContext< Context >();
				context.selectedDate = dateString;
			},
		},
	},
	{ lock: universalLock }
);

const { state, actions } = store< BookingInteractiveCalendarStore >(
	'woocommerce-bookings/booking-interactive-calendar',
	{
		state: {
			get viewMonthName() {
				const context = getContext< Context >();
				return context.monthNames[ context.viewMonth - 1 ];
			},

			get prevMonthLabel() {
				const context = getContext< Context >();
				const viewMonth = parseInt( context.viewMonth, 10 );
				const viewYear = parseInt( context.viewYear, 10 );
				const { previousMonth, previousYear } = getPreviousMonth(
					viewMonth,
					viewYear
				);
				return `Go to ${
					context.monthNames[ previousMonth - 1 ]
				} ${ previousYear }`;
			},

			get nextMonthLabel() {
				const context = getContext< Context >();
				const viewMonth = parseInt( context.viewMonth, 10 );
				const viewYear = parseInt( context.viewYear, 10 );
				const { nextMonth, nextYear } = getNextMonth(
					viewMonth,
					viewYear
				);
				return `Go to ${
					context.monthNames[ nextMonth - 1 ]
				} ${ nextYear }`;
			},

			get calendarDays() {
				const context = getContext< Context >();
				const year = parseInt( context.viewYear, 10 );
				const month = parseInt( context.viewMonth, 10 );

				const firstDay = new Date( year, month - 1, 1 );
				const daysInMonth = new Date( year, month, 0 ).getDate();
				// Get the day of week (0 = Sunday, 1 = Monday, etc.)
				const firstDayOfWeek = firstDay.getDay();
				// Calculate offset based on week start setting
				const weekStartsOn = context.weekStartsOn || 0;
				const startingDayOfWeek =
					( firstDayOfWeek - weekStartsOn + 7 ) % 7;

				const days: CalendarDay[] = [];
				const today = new Date();
				const todayString = formatDateString( today );

				const minDate = addToCartWithOptionsState.wcBookingsMinDate;
				minDate.setHours( 0, 0, 0, 0 );
				const maxDate = addToCartWithOptionsState.wcBookingsMaxDate;
				maxDate.setHours( 0, 0, 0, 0 );
				const availabilityCache =
					addToCartWithOptionsState.availabilityCache as Record<
						string,
						Record< string, number >
					>;

				/**
				 * Internal function to check if a date is disabled.
				 * // Hint: I'm sure this could be improved.
				 *
				 * @param dateString The date string to check.
				 * @return True if the date is disabled, false otherwise.
				 */
				const isDateDisabled = ( dateString: string ) => {
					// Create date objects at 00:00:00 for consistent day-based comparison
					const dateParts = dateString.split( '-' );
					const dateToCheck = new Date(
						parseInt( dateParts[ 0 ] ),
						parseInt( dateParts[ 1 ] ) - 1,
						parseInt( dateParts[ 2 ] )
					);

					if ( dateToCheck < minDate || dateToCheck > maxDate ) {
						return true;
					}

					const cacheKey = formatMonthString( dateToCheck );
					let isDisabled = true;
					if (
						availabilityCache &&
						availabilityCache?.[ cacheKey ]
					) {
						isDisabled =
							! availabilityCache[ cacheKey ]?.[ dateString ];
					}

					return isDisabled;
				};

				// Previous month days.
				const { previousMonth, previousYear } = getPreviousMonth(
					month,
					year
				);
				const prevMonthDays = new Date(
					previousYear,
					previousMonth,
					0
				).getDate();

				const currentlySelectedDate =
					addToCartWithOptionsState.selectedDate;

				for ( let i = startingDayOfWeek - 1; i >= 0; i-- ) {
					const dayNumber = prevMonthDays - i;
					const dateString = `${ previousYear }-${ String(
						previousMonth
					).padStart( 2, '0' ) }-${ String( dayNumber ).padStart(
						2,
						'0'
					) }`;
					const isDisabled = isDateDisabled( dateString );

					days.push( {
						key: `prev-${ dayNumber }`,
						dayNumber,
						dateString,
						isCurrentMonth: false,
						isToday: dateString === todayString,
						isSelected: currentlySelectedDate === dateString,
						isDisabled,
						ariaLabel: `${
							context.monthNames[ previousMonth - 1 ]
						} ${ dayNumber }, ${ previousYear }`,
						tabIndex: isDisabled ? -1 : 0,
					} );
				}

				// Current month days.
				for ( let day = 1; day <= daysInMonth; day++ ) {
					const dateString = `${ year }-${ String( month ).padStart(
						2,
						'0'
					) }-${ String( day ).padStart( 2, '0' ) }`;

					const isDisabled = isDateDisabled( dateString );
					days.push( {
						key: `current-${ day }`,
						dayNumber: day,
						dateString,
						isCurrentMonth: true,
						isToday: dateString === todayString,
						isSelected: currentlySelectedDate === dateString,
						isDisabled,
						ariaLabel: `${
							context.monthNames[ month - 1 ]
						} ${ day }, ${ year }`,
						tabIndex: isDisabled ? -1 : 0,
					} );
				}

				// Next month days.
				const totalCells = 35; // 7 x 5.
				const remainingCells = totalCells - days.length;
				if ( 0 >= remainingCells ) {
					return days;
				}

				const { nextMonth, nextYear } = getNextMonth( month, year );
				for ( let day = 1; day <= remainingCells; day++ ) {
					const dateString = `${ nextYear }-${ String(
						nextMonth
					).padStart( 2, '0' ) }-${ String( day ).padStart(
						2,
						'0'
					) }`;

					const isDisabled = isDateDisabled( dateString );
					days.push( {
						key: `next-${ day }`,
						dayNumber: day,
						dateString,
						isCurrentMonth: false,
						isToday: dateString === todayString,
						isSelected: currentlySelectedDate === dateString,
						isDisabled,
						ariaLabel: `${
							context.monthNames[ nextMonth - 1 ]
						} ${ day }, ${ nextYear }`,
						tabIndex: isDisabled ? -1 : 0,
					} );
				}

				return days;
			},

			get calendarKey() {
				const context = getContext< Context >();
				return `${ context.viewYear }-${ context.viewMonth }`;
			},

			get isPreviousMonthDisabled() {
				if ( addToCartWithOptionsState.isLoadingAvailability ) {
					return true;
				}

				const minDate = addToCartWithOptionsState.wcBookingsMinDate;
				const context = getContext< Context >();
				const viewMonth = parseInt( context.viewMonth, 10 );
				const viewYear = parseInt( context.viewYear, 10 );

				if (
					( viewMonth <= minDate.getMonth() + 1 &&
						viewYear === minDate.getFullYear() ) ||
					viewYear < minDate.getFullYear()
				) {
					return true;
				}

				return false;
			},

			get isNextMonthDisabled() {
				if ( addToCartWithOptionsState.isLoadingAvailability ) {
					return true;
				}

				const maxDate = addToCartWithOptionsState.wcBookingsMaxDate;
				const context = getContext< Context >();
				const viewMonth = parseInt( context.viewMonth, 10 );
				const viewYear = parseInt( context.viewYear, 10 );

				if (
					( viewMonth >= maxDate.getMonth() + 1 &&
						viewYear === maxDate.getFullYear() ) ||
					viewYear > maxDate.getFullYear()
				) {
					return true;
				}

				return false;
			},
		},

		actions: {
			async navigateToPreviousMonth() {
				const context = getContext< Context >();
				const viewMonth = parseInt( context.viewMonth, 10 );
				const viewYear = parseInt( context.viewYear, 10 );
				if ( viewMonth === 1 ) {
					context.viewMonth = 12;
					context.viewYear = viewYear - 1;
				} else {
					context.viewMonth = viewMonth - 1;
				}
			},
			async navigateToNextMonth() {
				const context = getContext< Context >();
				const viewMonth = parseInt( context.viewMonth, 10 );
				const viewYear = parseInt( context.viewYear, 10 );
				if ( viewMonth === 12 ) {
					context.viewMonth = 1;
					context.viewYear = viewYear + 1;
				} else {
					context.viewMonth = viewMonth + 1;
				}
			},
			setView( month: number, year: number ) {
				const context = getContext< Context >();
				context.viewMonth = month;
				context.viewYear = year;
			},
			handleViewByDate( dateString: string ) {
				const context = getContext< Context >();
				const month = parseInt( context.viewMonth, 10 );
				const year = parseInt( context.viewYear, 10 );
				const selectedDate = new Date( dateString );
				const selectedMonth = selectedDate.getMonth() + 1;
				const selectedYear = selectedDate.getFullYear();
				if ( selectedMonth !== month || selectedYear !== year ) {
					actions.setView( selectedMonth, selectedYear );
				}
			},
			handleSelectDate: withSyncEvent( ( event: Event ) => {
				const dayElement = getElement()?.ref as HTMLElement;
				if ( ! dayElement ) {
					return;
				}

				const isDisabled =
					dayElement.getAttribute( 'aria-disabled' ) === 'true';
				if ( isDisabled ) {
					event.preventDefault();
					return;
				}

				const dateString = dayElement.dataset.date;

				// Do nothing if clicked on the same date.
				if ( addToCartWithOptionsState.selectedDate === dateString ) {
					return;
				}

				addToCartWithOptionsActions.selectDate( dateString );
				actions.handleViewByDate( dateString );

				// Focus the time slots block if config requires time selection.
				const config = getConfig(
					'woocommerce/add-to-cart-with-options'
				);
				if ( config.wcBookingsRequiresTimeSelection ) {
					const form = dayElement.closest( 'form' );
					const timeSlotsBlock = form?.querySelector(
						'.wc-bookings-time-slots__grid'
					);
					if ( timeSlotsBlock ) {
						setTimeout( () => {
							const firstButton = (
								timeSlotsBlock as HTMLElement
							 )?.querySelector( 'button' );
							if ( firstButton ) {
								( firstButton as HTMLElement )?.focus();
							}
						}, 0 );
					}
				}

				// Reset time selection.
				addToCartWithOptionsActions.selectTime( null );
				addToCartWithOptionsActions.setTimeSlotsPage( 1 );
			} ),
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

				const SNAP_THRESHOLD = 0.4;
				const delta = context.touchCurrentX - context.touchStartX;
				const element = getElement()?.ref;
				const calendarWidth = element?.offsetWidth || 0;

				// Only trigger swipe actions if there was significant movement
				if ( Math.abs( delta ) > calendarWidth * SNAP_THRESHOLD ) {
					if ( delta > 0 && ! state.isPreviousMonthDisabled ) {
						actions.navigateToPreviousMonth();
					} else if ( delta < 0 && ! state.isNextMonthDisabled ) {
						actions.navigateToNextMonth();
					}
				}

				// Reset touch state
				context.isDragging = false;
				context.touchStartX = 0;
				context.touchCurrentX = 0;
			},
		},
	}
);
