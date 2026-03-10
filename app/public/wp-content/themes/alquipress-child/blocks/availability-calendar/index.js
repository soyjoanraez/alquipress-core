import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('alquipress-child/availability-calendar', {
    edit: function (props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Opciones', 'alquipress-child')}>
                        <RangeControl
                            label={__('Meses a mostrar', 'alquipress-child')}
                            value={attributes.monthsToShow}
                            onChange={(v) => setAttributes({ monthsToShow: v })}
                            min={1}
                            max={12}
                        />
                    </PanelBody>
                </InspectorControls>
                <ServerSideRender block="alquipress-child/availability-calendar" attributes={props.attributes} />
            </div>
        );
    },
    save: () => null,
});
