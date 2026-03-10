import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress-child/property-search', {
    edit: function (props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Contenido', 'alquipress-child')}>
                        <TextControl
                            label={__('Título', 'alquipress-child')}
                            value={attributes.title}
                            onChange={(v) => setAttributes({ title: v })}
                        />
                        <TextControl
                            label={__('Subtítulo', 'alquipress-child')}
                            value={attributes.subtitle}
                            onChange={(v) => setAttributes({ subtitle: v })}
                        />
                        <TextControl
                            label={__('URL página resultados', 'alquipress-child')}
                            value={attributes.resultsPage}
                            onChange={(v) => setAttributes({ resultsPage: v })}
                            help={__('Dejar vacío para usar el listado de productos por defecto.', 'alquipress-child')}
                        />
                    </PanelBody>
                </InspectorControls>
                <ServerSideRender block="alquipress-child/property-search" attributes={attributes} />
            </div>
        );
    },
    save: () => null,
});
