import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress-child/property-grid', {
    edit: function (props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Configuración', 'alquipress-child')}>
                        <RangeControl
                            label={__('Columnas', 'alquipress-child')}
                            value={attributes.columns}
                            onChange={(v) => setAttributes({ columns: v })}
                            min={2}
                            max={4}
                        />
                        <RangeControl
                            label={__('Por página', 'alquipress-child')}
                            value={attributes.postsPerPage}
                            onChange={(v) => setAttributes({ postsPerPage: v })}
                            min={4}
                            max={24}
                        />
                        <SelectControl
                            label={__('Ordenar por', 'alquipress-child')}
                            value={attributes.orderBy}
                            options={[
                                { label: __('Fecha', 'alquipress-child'), value: 'date' },
                                { label: __('Precio', 'alquipress-child'), value: 'price' },
                                { label: __('Título', 'alquipress-child'), value: 'title' },
                            ]}
                            onChange={(v) => setAttributes({ orderBy: v })}
                        />
                        <SelectControl
                            label={__('Orden', 'alquipress-child')}
                            value={attributes.order}
                            options={[
                                { label: __('Descendente', 'alquipress-child'), value: 'DESC' },
                                { label: __('Ascendente', 'alquipress-child'), value: 'ASC' },
                            ]}
                            onChange={(v) => setAttributes({ order: v })}
                        />
                        <ToggleControl
                            label={__('Mostrar paginación', 'alquipress-child')}
                            checked={attributes.showPagination}
                            onChange={(v) => setAttributes({ showPagination: v })}
                        />
                    </PanelBody>
                </InspectorControls>
                <ServerSideRender block="alquipress-child/property-grid" attributes={attributes} />
            </div>
        );
    },
    save: () => null,
});
