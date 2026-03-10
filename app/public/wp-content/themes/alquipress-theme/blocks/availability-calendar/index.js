/**
 * JavaScript del editor para Calendario
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress/availability-calendar', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Configuración', 'alquipress-theme')}>
                        <RangeControl
                            label={__('Meses a mostrar', 'alquipress-theme')}
                            value={attributes.monthsToShow}
                            onChange={(value) => setAttributes({ monthsToShow: value })}
                            min={1}
                            max={6}
                        />
                        <ToggleControl
                            label={__('Mostrar precios', 'alquipress-theme')}
                            checked={attributes.showPrices}
                            onChange={(value) => setAttributes({ showPrices: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <ServerSideRender
                    block="alquipress/availability-calendar"
                    attributes={attributes}
                />
            </div>
        );
    },
    
    save: function() {
        return null;
    },
});
