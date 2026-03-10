/**
 * JavaScript del editor para Widget de Reserva
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress/booking-widget', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Opciones', 'alquipress-theme')}>
                        <ToggleControl
                            label={__('Sticky (fijo al hacer scroll)', 'alquipress-theme')}
                            checked={attributes.sticky}
                            onChange={(value) => setAttributes({ sticky: value })}
                        />
                        <ToggleControl
                            label={__('Mostrar coste de limpieza', 'alquipress-theme')}
                            checked={attributes.showCleaningFee}
                            onChange={(value) => setAttributes({ showCleaningFee: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <ServerSideRender
                    block="alquipress/booking-widget"
                    attributes={attributes}
                />
            </div>
        );
    },
    
    save: function() {
        return null;
    },
});
