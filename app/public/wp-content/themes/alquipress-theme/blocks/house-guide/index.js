/**
 * JavaScript del editor para Guía de la Casa
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress/house-guide', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Secciones', 'alquipress-theme')}>
                        <ToggleControl
                            label={__('Mostrar Check-in', 'alquipress-theme')}
                            checked={attributes.showCheckIn}
                            onChange={(value) => setAttributes({ showCheckIn: value })}
                        />
                        <ToggleControl
                            label={__('Mostrar Check-out', 'alquipress-theme')}
                            checked={attributes.showCheckOut}
                            onChange={(value) => setAttributes({ showCheckOut: value })}
                        />
                        <ToggleControl
                            label={__('Mostrar Normas', 'alquipress-theme')}
                            checked={attributes.showRules}
                            onChange={(value) => setAttributes({ showRules: value })}
                        />
                        <ToggleControl
                            label={__('Mostrar Información Útil', 'alquipress-theme')}
                            checked={attributes.showAmenities}
                            onChange={(value) => setAttributes({ showAmenities: value })}
                        />
                        <ToggleControl
                            label={__('Mostrar Ubicación', 'alquipress-theme')}
                            checked={attributes.showLocation}
                            onChange={(value) => setAttributes({ showLocation: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <ServerSideRender
                    block="alquipress/house-guide"
                    attributes={attributes}
                />
            </div>
        );
    },
    
    save: function() {
        return null;
    },
});
