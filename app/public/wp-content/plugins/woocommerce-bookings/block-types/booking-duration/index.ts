/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { scheduled } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

registerBlockType( metadata.name, {
	icon: scheduled,
	edit,
} );
