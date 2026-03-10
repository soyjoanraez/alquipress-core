/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { people } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	icon: people,
	edit: () => null,
} );
