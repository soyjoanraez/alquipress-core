/**
 * JavaScript del editor para bloque Grid de Propiedades
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress/property-grid', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Configuración', 'alquipress-theme')}>
                        <RangeControl
                            label={__('Columnas', 'alquipress-theme')}
                            value={attributes.columns}
                            onChange={(value) => setAttributes({ columns: value })}
                            min={1}
                            max={4}
                        />
                        
                        <RangeControl
                            label={__('Propiedades por página', 'alquipress-theme')}
                            value={attributes.postsPerPage}
                            onChange={(value) => setAttributes({ postsPerPage: value })}
                            min={1}
                            max={50}
                        />
                        
                        <SelectControl
                            label={__('Ordenar por', 'alquipress-theme')}
                            value={attributes.orderBy}
                            options={[
                                { label: __('Fecha', 'alquipress-theme'), value: 'date' },
                                { label: __('Precio', 'alquipress-theme'), value: 'price' },
                                { label: __('Popularidad', 'alquipress-theme'), value: 'popularity' },
                                { label: __('Título', 'alquipress-theme'), value: 'title' },
                            ]}
                            onChange={(value) => setAttributes({ orderBy: value })}
                        />
                        
                        <SelectControl
                            label={__('Orden', 'alquipress-theme')}
                            value={attributes.order}
                            options={[
                                { label: __('Descendente', 'alquipress-theme'), value: 'DESC' },
                                { label: __('Ascendente', 'alquipress-theme'), value: 'ASC' },
                            ]}
                            onChange={(value) => setAttributes({ order: value })}
                        />
                        
                        <SelectControl
                            label={__('Layout', 'alquipress-theme')}
                            value={attributes.layout}
                            options={[
                                { label: __('Grid', 'alquipress-theme'), value: 'grid' },
                                { label: __('Lista', 'alquipress-theme'), value: 'list' },
                                { label: __('Carrusel', 'alquipress-theme'), value: 'carousel' },
                            ]}
                            onChange={(value) => setAttributes({ layout: value })}
                        />
                        
                        <ToggleControl
                            label={__('Mostrar paginación', 'alquipress-theme')}
                            checked={attributes.showPagination}
                            onChange={(value) => setAttributes({ showPagination: value })}
                        />
                        
                        <ToggleControl
                            label={__('Paginación AJAX', 'alquipress-theme')}
                            checked={attributes.useAjaxPagination}
                            onChange={(value) => setAttributes({ useAjaxPagination: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <ServerSideRender
                    block="alquipress/property-grid"
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
