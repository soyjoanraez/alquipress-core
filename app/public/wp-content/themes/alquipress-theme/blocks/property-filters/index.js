/**
 * JavaScript del editor para bloque Filtros
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress/property-filters', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Opciones', 'alquipress-theme')}>
                        <ToggleControl
                            label={__('Mostrar ubicación', 'alquipress-theme')}
                            checked={attributes.showLocation}
                            onChange={(value) => setAttributes({ showLocation: value })}
                        />
                        <ToggleControl
                            label={__('Mostrar precio', 'alquipress-theme')}
                            checked={attributes.showPrice}
                            onChange={(value) => setAttributes({ showPrice: value })}
                        />
                        <ToggleControl
                            label={__('Mostrar habitaciones', 'alquipress-theme')}
                            checked={attributes.showRooms}
                            onChange={(value) => setAttributes({ showRooms: value })}
                        />
                        <ToggleControl
                            label={__('Mostrar características', 'alquipress-theme')}
                            checked={attributes.showCharacteristics}
                            onChange={(value) => setAttributes({ showCharacteristics: value })}
                        />
                        <ToggleControl
                            label={__('Usar AJAX', 'alquipress-theme')}
                            checked={attributes.useAjax}
                            onChange={(value) => setAttributes({ useAjax: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <ServerSideRender
                    block="alquipress/property-filters"
                    attributes={attributes}
                />
            </div>
        );
    },
    
    save: function() {
        return null;
    },
});
