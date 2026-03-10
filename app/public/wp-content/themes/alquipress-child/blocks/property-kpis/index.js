import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress-child/property-kpis', {
    edit: function (props) {
        const blockProps = useBlockProps();
        return (
            <div {...blockProps}>
                <ServerSideRender block="alquipress-child/property-kpis" attributes={props.attributes} />
            </div>
        );
    },
    save: () => null,
});
