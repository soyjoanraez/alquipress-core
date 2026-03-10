/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<h1>Booking Interactive Calendar</h1>
		</div>
	);
};

export default Edit;
