import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress-child/booking-widget', {
    edit: function (props) {
        const blockProps = useBlockProps();
        return (
            <div {...blockProps}>
                <ServerSideRender block="alquipress-child/booking-widget" attributes={props.attributes} />
            </div>
        );
    },
    save: () => null,
});
