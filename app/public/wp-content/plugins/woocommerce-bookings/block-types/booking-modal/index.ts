/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { calendar } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	icon: calendar,
	edit: () => null,
} );
