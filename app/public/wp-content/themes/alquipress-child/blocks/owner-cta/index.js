import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress-child/owner-cta', {
    edit: function (props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Contenido', 'alquipress-child')}>
                        <TextControl label={__('Título', 'alquipress-child')} value={attributes.title} onChange={(v) => setAttributes({ title: v })} />
                        <TextControl label={__('Subtítulo', 'alquipress-child')} value={attributes.subtitle} onChange={(v) => setAttributes({ subtitle: v })} />
                        <TextControl label={__('Texto del botón', 'alquipress-child')} value={attributes.buttonText} onChange={(v) => setAttributes({ buttonText: v })} />
                        <TextControl label={__('URL del botón', 'alquipress-child')} value={attributes.buttonUrl} onChange={(v) => setAttributes({ buttonUrl: v })} help={__('Vacío: enlace a Mi Área o Contacto.', 'alquipress-child')} />
                    </PanelBody>
                </InspectorControls>
                <ServerSideRender block="alquipress-child/owner-cta" attributes={attributes} />
            </div>
        );
    },
    save: () => null,
});
