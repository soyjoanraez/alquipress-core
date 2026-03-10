/**
 * JavaScript del editor para Ficha Técnica
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress/property-specs', {
    edit: function(props) {
        const blockProps = useBlockProps();
        
        return (
            <div {...blockProps}>
                <ServerSideRender
                    block="alquipress/property-specs"
                    attributes={props.attributes}
                />
            </div>
        );
    },
    
    save: function() {
        return null;
    },
});
