/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { keyboard } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './style.scss';

registerBlockType( metadata.name, {
	icon: keyboard,
	edit,
} );
