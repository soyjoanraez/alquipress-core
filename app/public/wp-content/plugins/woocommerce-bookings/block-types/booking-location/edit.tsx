/**
 * External dependencies
 */
import {
	useBlockProps,
	RichText,
	useBlockEditingMode,
	BlockControls,
	AlignmentToolbar,
} from '@wordpress/block-editor';
import { useEntityRecord } from '@wordpress/core-data';
import type { BlockEditProps } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import type { Attributes } from './types';

// Allowed formats for the prefix and suffix fields.
const ALLOWED_FORMATS = [
	'core/bold',
	'core/image',
	'core/italic',
	'core/link',
	'core/strikethrough',
	'core/text-color',
];

const Edit = ( {
	attributes,
	setAttributes,
	isSelected,
	context,
}: BlockEditProps< Attributes > ): JSX.Element => {
	const blockEditingMode = useBlockEditingMode();
	const showControls = blockEditingMode === 'default';

	const { prefix, suffix, textAlign } = attributes;
	const blockProps = useBlockProps( {
		className: clsx( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
		} ),
	} );

	const { postId } = context;
	const { record: product } = useEntityRecord( 'root', 'product', postId );
	const location = product?.booking_location || 'Booking Location';

	return (
		<>
			{ showControls && (
				<BlockControls>
					<AlignmentToolbar
						value={ textAlign }
						onChange={ ( nextAlign ) => {
							setAttributes( { textAlign: nextAlign } );
						} }
					/>
				</BlockControls>
			) }
			<div { ...blockProps }>
				{ ( isSelected || prefix ) && (
					<RichText
						className="wc-bookings-block-components-booking-location__prefix"
						allowedFormats={ ALLOWED_FORMATS }
						tagName="span"
						placeholder={ __( 'Prefix', 'woocommerce' ) + ' ' }
						aria-label={ __( 'Prefix', 'woocommerce' ) }
						value={ prefix }
						onChange={ ( value ) =>
							setAttributes( { prefix: value } )
						}
					/>
				) }
				<span>{ location }</span>
				{ ( isSelected || suffix ) && (
					<RichText
						className="wc-bookings-block-components-booking-location__suffix"
						allowedFormats={ ALLOWED_FORMATS }
						tagName="span"
						placeholder={ ' ' + __( 'Suffix', 'woocommerce' ) }
						aria-label={ __( 'Suffix', 'woocommerce' ) }
						value={ suffix }
						onChange={ ( value ) =>
							setAttributes( { suffix: value } )
						}
					/>
				) }
			</div>
		</>
	);
};

export default Edit;
