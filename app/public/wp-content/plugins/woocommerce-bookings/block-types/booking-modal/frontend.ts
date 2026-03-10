/**
 * WordPress dependencies
 */
import {
	store,
	getContext,
	getConfig,
	getElement,
	withSyncEvent,
} from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import setStyles from './set-styles';

setStyles();

type DurationData = {
	value: string;
	unit: string;
};

const calculateFutureDate = ( durationData: DurationData ) => {
	const fromDate = new Date();
	const { value, unit } = durationData;
	const valueNumber = parseInt( value, 10 );
	if ( isNaN( valueNumber ) ) {
		throw new Error( 'Invalid value for duration data' );
	}

	if ( 0 === valueNumber ) {
		return fromDate;
	}

	switch ( unit ) {
		case 'minute':
			fromDate.setMinutes( fromDate.getMinutes() + valueNumber );
			break;
		case 'day':
			fromDate.setDate( fromDate.getDate() + valueNumber );
			break;
		case 'week':
			// For weeks, we can just add the number of days (value * 7).
			fromDate.setDate( fromDate.getDate() + valueNumber * 7 );
			break;
		case 'month':
			fromDate.setMonth( fromDate.getMonth() + valueNumber );
			break;
		case 'year':
			fromDate.setFullYear( fromDate.getFullYear() + valueNumber );
			break;
		default:
			// If the unit is not recognized, throw an error.
			throw new Error( `Unsupported time unit: "${ unit }"` );
	}

	return fromDate;
};

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

type AddToCartWithOptionsContext = {
	availabilityCache: Record<
		number,
		Record< string, Record< string, Record< string, number > | null > >
	>;
	isLoadingAvailability: boolean;
	isAddingBookingToCart: boolean;
};

const { state: wooState } = store( 'woocommerce', {}, { lock: universalLock } );

const { state: productDataState } = store(
	'woocommerce/product-data',
	{},
	{ lock: universalLock }
);

// Register modal state and actions to the WooCommerce Add to Cart + Options store.
const { state, actions } = store(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			get shouldHideAddToCartButton() {
				const context = getContext< AddToCartWithOptionsContext >();
				const config = getConfig();
				return (
					! context.selectedDate ||
					( ! context.selectedTime &&
						config.wcBookingsRequiresTimeSelection )
				);
			},
			get isLoadingAvailability() {
				const context = getContext< AddToCartWithOptionsContext >();
				return context.isLoadingAvailability;
			},
			get availabilityCache() {
				const context = getContext< AddToCartWithOptionsContext >();
				return (
					context.availabilityCache?.[
						context.selectedTeamMember || 0
					] ?? {}
				);
			},
			get isAddingBookingToCart() {
				const context = getContext< AddToCartWithOptionsContext >();
				return context.isAddingBookingToCart;
			},
			get wcBookingsMinDate() {
				const { wcBookingsMinDate } = getConfig();
				const minDate = calculateFutureDate( wcBookingsMinDate );
				return minDate;
			},
			get wcBookingsMaxDate() {
				const { wcBookingsMaxDate } = getConfig();
				const maxDate = calculateFutureDate( wcBookingsMaxDate );
				return maxDate;
			},
		},
		actions: {
			openBookingModal: () => {
				const context = getContext< AddToCartWithOptionsContext >();
				context.isModalOpen = true;
				actions.fetchBookingAvailability( null, null );

				// Focus the modal.
				const modalRef = getElement()?.ref as HTMLElement;
				const form = modalRef.closest( 'form' );
				const dialog = form?.querySelector( 'dialog' );
				if ( dialog ) {
					setTimeout( () => {
						dialog.focus();
					}, 0 );
				}

				// Lock page scroll.
				document.body.style.overflow = 'hidden';
			},
			closeBookingModal: () => {
				const context = getContext< AddToCartWithOptionsContext >();
				context.isModalOpen = false;

				// Unlock page scroll.
				document.body.style.overflow = '';
			},
			onModalKeyDown: withSyncEvent( ( event: KeyboardEvent ) => {
				if ( event.key === 'Escape' ) {
					event.preventDefault();
					actions.closeBookingModal();
				}

				if ( event.key === 'Tab' ) {
					const focusableElementsSelectors =
						'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])';

					const dialogPopUp = getElement()?.ref as HTMLElement;
					const focusableElements = dialogPopUp.querySelectorAll(
						focusableElementsSelectors
					);

					if ( ! focusableElements.length ) {
						return;
					}

					const firstFocusableElement =
						focusableElements[ 0 ] as HTMLElement;
					const lastFocusableElement = focusableElements[
						focusableElements.length - 1
					] as HTMLElement;

					if (
						! event.shiftKey &&
						event.target === lastFocusableElement
					) {
						event.preventDefault();
						firstFocusableElement.focus();
						return;
					}

					if (
						event.shiftKey &&
						event.target === firstFocusableElement
					) {
						event.preventDefault();
						lastFocusableElement.focus();
						return;
					}

					if ( event.target === dialogPopUp ) {
						event.preventDefault();
						firstFocusableElement.focus();
					}
				}
			} ),
			*addBookingToCart() {
				const context = getContext< AddToCartWithOptionsContext >();
				try {
					if ( ! state.selectedDate ) {
						throw new Error( 'No date selected' );
					}

					if ( ! state.selectedTime ) {
						throw new Error( 'No time selected' );
					}

					context.isAddingBookingToCart = true;

					const bookingConfiguration = {
						date: `${ state.selectedDate } ${ state.selectedTime }`,
					} as Record< string, string >;
					if ( context.selectedTeamMember ) {
						bookingConfiguration.resource_id =
							context.selectedTeamMember.toString();
					}

					const res: Response = yield fetch(
						`${ wooState.restUrl }wc/store/v1/cart/add-item`,
						{
							method: 'POST',
							headers: {
								Nonce: wooState.nonce,
								'Content-Type': 'application/json',
							},
							body: JSON.stringify( {
								id: productDataState.productId,
								booking_configuration: bookingConfiguration,
							} ),
						}
					);
					const json = yield res.json();
					context.isAddingBookingToCart = false;

					if ( json.data?.status < 200 || json.data?.status >= 300 ) {
						throw new Error( json.data?.message );
					}

					wooState.cart = json;

					return true;
				} catch ( error ) {
					// eslint-disable-next-line no-console
					console.error( error );

					return false;
				}
			},
			*addToCartAndContinueShopping( event: MouseEvent ) {
				if ( ! ( event.target instanceof HTMLAnchorElement ) ) {
					return;
				}
				event.preventDefault();

				const success = yield actions.addBookingToCart();
				if ( success ) {
					// Reset the selected date and time.
					actions.selectDate( null );
					actions.selectTime( null );
					actions.closeBookingModal();
				}
			},
			*addToCartAndCompleteBooking( event: MouseEvent ) {
				if ( ! ( event.target instanceof HTMLAnchorElement ) ) {
					return;
				}
				event.preventDefault();

				const success = yield actions.addBookingToCart();
				if ( success ) {
					window.location.href = event.target.href;
				}
			},
			*fetchBookingAvailability(
				fromDate: Date | null,
				toDate: Date | null
			) {
				const context = getContext< AddToCartWithOptionsContext >();

				try {
					if ( context.isLoadingAvailability ) {
						return;
					}

					// Handle defaults and limits.
					let fromDateFinal: Date | null = fromDate;
					const minFromDate = state.wcBookingsMinDate;
					if (
						fromDateFinal === null ||
						fromDateFinal < minFromDate
					) {
						fromDateFinal = minFromDate;
					}
					let toDateFinal: Date | null = toDate;
					const maxToDate = state.wcBookingsMaxDate;
					if ( toDateFinal === null || toDateFinal > maxToDate ) {
						toDateFinal = maxToDate;
					}

					context.isLoadingAvailability = true;

					// Request availability.
					const params = new URLSearchParams();
					params.set(
						'start_date',
						fromDateFinal?.toISOString() ?? ''
					);
					params.set( 'end_date', toDateFinal?.toISOString() ?? '' );
					if ( context.selectedTeamMember ) {
						params.set(
							'resource_id',
							context.selectedTeamMember?.toString() ?? ''
						);
					}
					const { isPermalinksPlain } = getConfig(
						'woocommerce/add-to-cart-with-options'
					);
					const urlParamsSeparator = isPermalinksPlain ? '&' : '?';
					const res: Response = yield fetch(
						`${
							wooState.restUrl
						}wc-bookings/v2/products/${ parseInt(
							productDataState.productId,
							10
						) }/availability${ urlParamsSeparator }${ params.toString() }`,
						{
							method: 'GET',
							headers: {
								// Nonce: wooState.nonce,
								'Content-Type': 'application/json',
							},
						}
					);
					const json = yield res.json();

					if ( json.data?.status < 200 || json.data?.status >= 300 ) {
						throw new Error( json.data?.message );
					}

					const availabilityResponse = json?.availability;
					if ( ! availabilityResponse ) {
						throw new Error( 'No availability response' );
					}

					// Map the response to context.availabilityCache.
					const availabilityCacheObjects: Record<
						number,
						Record<
							string,
							Record< string, Record< string, number > | null >
						>
					> = {};
					Object.entries( availabilityResponse ).forEach(
						( [ month, availability ] ) => {
							availabilityCacheObjects[ month ] =
								availability as Record<
									string,
									Record< string, number > | null
								>;
						}
					);

					if ( ! context.availabilityCache ) {
						context.availabilityCache = {};
					}

					context.availabilityCache[
						context.selectedTeamMember || 0
					] = availabilityCacheObjects;
					context.isLoadingAvailability = false;
				} catch ( error ) {
					// eslint-disable-next-line no-console
					console.error( error );
					context.isLoadingAvailability = false;
				}
			},
		},
	},
	{ lock: universalLock }
);
