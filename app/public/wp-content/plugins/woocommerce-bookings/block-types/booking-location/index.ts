/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { mapMarker } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

registerBlockType( metadata.name, {
	icon: mapMarker,
	edit,
} );
