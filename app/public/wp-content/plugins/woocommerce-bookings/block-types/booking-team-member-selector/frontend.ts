/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// We store the selected team member in the Add to Cart + Options store so it's
// accessible in sibling blocks. It's necessary to handle submitting the form.
const { actions: addToCartWithOptionsActions } = store(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			get selectedTeamMember() {
				const context = getContext();
				return context.selectedTeamMember;
			},
		},
		actions: {
			*selectTeamMember( newTeamMember: number ) {
				const context = getContext();
				context.selectedTeamMember = newTeamMember;

				yield addToCartWithOptionsActions.fetchBookingAvailability(
					null,
					null
				);
			},
		},
	},
	{ lock: universalLock }
);

store( 'woocommerce-bookings/booking-team-member-selector', {
	actions: {
		handleSelectTeamMember: ( event: Event ) => {
			const target = event.target as HTMLSelectElement;
			if ( ! target?.value ) {
				addToCartWithOptionsActions.selectTeamMember( null );
			} else {
				addToCartWithOptionsActions.selectTeamMember(
					parseInt( target.value, 10 )
				);
			}
		},
	},
	callbacks: {
		teamMemberInit: () => {
			const target = getElement()?.ref;
			if ( ! target?.value ) {
				return;
			}

			addToCartWithOptionsActions.selectTeamMember(
				parseInt( target.value, 10 )
			);
		},
	},
} );
