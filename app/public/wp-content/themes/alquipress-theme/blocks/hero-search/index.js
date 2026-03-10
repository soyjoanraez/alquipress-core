/**
 * JavaScript del editor para bloque Hero con Buscador
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, RangeControl, MediaUpload, MediaUploadCheck } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress/hero-search', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Contenido', 'alquipress-theme')}>
                        <TextControl
                            label={__('Título', 'alquipress-theme')}
                            value={attributes.title}
                            onChange={(value) => setAttributes({ title: value })}
                        />
                        <TextControl
                            label={__('Subtítulo', 'alquipress-theme')}
                            value={attributes.subtitle}
                            onChange={(value) => setAttributes({ subtitle: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Fondo', 'alquipress-theme')}>
                        <SelectControl
                            label={__('Tipo de fondo', 'alquipress-theme')}
                            value={attributes.backgroundType}
                            options={[
                                { label: __('Imagen', 'alquipress-theme'), value: 'image' },
                                { label: __('Video', 'alquipress-theme'), value: 'video' },
                            ]}
                            onChange={(value) => setAttributes({ backgroundType: value })}
                        />
                        
                        {attributes.backgroundType === 'image' && (
                            <MediaUploadCheck>
                                <MediaUpload
                                    onSelect={(media) => setAttributes({ backgroundImage: { url: media.url, id: media.id } })}
                                    allowedTypes={['image']}
                                    value={attributes.backgroundImage?.id}
                                    render={({ open }) => (
                                        <button onClick={open}>
                                            {attributes.backgroundImage ? __('Cambiar imagen', 'alquipress-theme') : __('Seleccionar imagen', 'alquipress-theme')}
                                        </button>
                                    )}
                                />
                            </MediaUploadCheck>
                        )}
                        
                        {attributes.backgroundType === 'video' && (
                            <TextControl
                                label={__('URL del video', 'alquipress-theme')}
                                value={attributes.backgroundVideo}
                                onChange={(value) => setAttributes({ backgroundVideo: value })}
                                help={__('URL del archivo de video (MP4)', 'alquipress-theme')}
                            />
                        )}
                        
                        <RangeControl
                            label={__('Opacidad del overlay', 'alquipress-theme')}
                            value={attributes.overlayOpacity}
                            onChange={(value) => setAttributes({ overlayOpacity: value })}
                            min={0}
                            max={1}
                            step={0.1}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <ServerSideRender
                    block="alquipress/hero-search"
                    attributes={attributes}
                />
            </div>
        );
    },
    
    save: function() {
        // Renderizado del lado del servidor
        return null;
    },
});
