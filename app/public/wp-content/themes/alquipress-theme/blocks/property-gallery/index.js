/**
 * JavaScript del editor para Galería
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress/property-gallery', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Configuración', 'alquipress-theme')}>
                        <SelectControl
                            label={__('Layout', 'alquipress-theme')}
                            value={attributes.layout}
                            options={[
                                { label: __('Principal + Grid', 'alquipress-theme'), value: 'main-plus-grid' },
                                { label: __('Carrusel', 'alquipress-theme'), value: 'carousel' },
                                { label: __('Masonry', 'alquipress-theme'), value: 'masonry' },
                            ]}
                            onChange={(value) => setAttributes({ layout: value })}
                        />
                        
                        <ToggleControl
                            label={__('Mostrar tour virtual', 'alquipress-theme')}
                            checked={attributes.showVideoTour}
                            onChange={(value) => setAttributes({ showVideoTour: value })}
                        />
                        
                        {attributes.showVideoTour && (
                            <TextControl
                                label={__('URL del tour virtual', 'alquipress-theme')}
                                value={attributes.videoTourUrl}
                                onChange={(value) => setAttributes({ videoTourUrl: value })}
                                help={__('URL de Matterport, Kuula o YouTube', 'alquipress-theme')}
                            />
                        )}
                    </PanelBody>
                </InspectorControls>
                
                <ServerSideRender
                    block="alquipress/property-gallery"
                    attributes={attributes}
                />
            </div>
        );
    },
    
    save: function() {
        return null;
    },
});
