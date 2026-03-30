/**
 * Editor script para el bloque Property Search
 */
const { registerBlockType } = wp.blocks;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, TextControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType('alquipress/property-search', {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( {
            className: 'wp-block-alquipress-property-search'
        } );

        return (
            <div { ...blockProps }>
                <InspectorControls>
                    <PanelBody title={ __( 'Ajustes del Buscador', 'alquipress' ) }>
                        <TextControl
                            label={ __( 'Texto de placeholder', 'alquipress' ) }
                            value={ attributes.placeholder }
                            onChange={ ( val ) => setAttributes( { placeholder: val } ) }
                        />
                        <TextControl
                            label={ __( 'Texto del botón', 'alquipress' ) }
                            value={ attributes.buttonText }
                            onChange={ ( val ) => setAttributes( { buttonText: val } ) }
                        />
                    </PanelBody>
                </InspectorControls>

                <div className="ap-search-field">
                    <span className="ap-search-label">{ __( 'Ubicación', 'alquipress' ) }</span>
                    <div className="ap-search-input">{ attributes.placeholder }</div>
                </div>
                <div className="ap-search-field">
                    <span className="ap-search-label">{ __( 'Fechas', 'alquipress' ) }</span>
                    <div className="ap-search-input">{ __( 'Selecciona fechas', 'alquipress' ) }</div>
                </div>
                <div className="ap-search-button">
                    { attributes.buttonText }
                </div>
            </div>
        );
    },
    save: () => null, // Renderizado dinámico vía PHP
});
