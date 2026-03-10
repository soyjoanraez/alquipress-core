/**
 * JavaScript del editor para Mis Reservas
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress/my-bookings', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Opciones', 'alquipress-theme')}>
                        <ToggleControl
                            label={__('Mostrar próximas reservas', 'alquipress-theme')}
                            checked={attributes.showUpcoming}
                            onChange={(value) => setAttributes({ showUpcoming: value })}
                        />
                        <ToggleControl
                            label={__('Mostrar historial', 'alquipress-theme')}
                            checked={attributes.showHistory}
                            onChange={(value) => setAttributes({ showHistory: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <ServerSideRender
                    block="alquipress/my-bookings"
                    attributes={attributes}
                />
            </div>
        );
    },
    
    save: function() {
        return null;
    },
});
