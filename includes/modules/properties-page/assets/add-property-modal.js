/**
 * Modal Añadir propiedad: overlay, input nombre, botones Cancelar/Crear
 */
(function ($) {
    'use strict';

    var config = window.alquipressAddProperty || {};
    var $portal, $modal, $overlay, $input, $submit, $cancel, $error;

    function open() {
        if (!$modal) {
            buildModal();
        }
        $input.val('');
        $error.text('').hide();
        $modal.addClass('is-open');
        $overlay.addClass('is-open');
        $('body').addClass('ap-add-property-modal-open');
        $input.focus();
    }

    function close() {
        if ($modal) {
            $modal.removeClass('is-open');
            $overlay.removeClass('is-open');
            $('body').removeClass('ap-add-property-modal-open');
        }
    }

    function buildModal() {
        if ($('#ap-add-property-portal').length) {
            $('#ap-add-property-portal').remove();
        }
        $('body').append('<div id="ap-add-property-portal" class="ap-add-property-portal"></div>');
        $portal = $('#ap-add-property-portal');

        var i18n = config.i18n || {};
        var html = '<div class="ap-add-property-overlay" id="ap-add-property-overlay"></div>' +
            '<div class="ap-add-property-modal" id="ap-add-property-modal" role="dialog" aria-labelledby="ap-add-property-title" aria-modal="true">' +
            '<h2 id="ap-add-property-title">' + (i18n.title || 'Añadir propiedad') + '</h2>' +
            '<p class="ap-add-property-desc">' + (i18n.desc || 'Introduce el nombre o título de la propiedad.') + '</p>' +
            '<div class="ap-add-property-form">' +
            '<input type="text" id="ap-add-property-title-input" class="ap-add-property-input" placeholder="' + (i18n.placeholder || 'Nombre de la propiedad') + '" required>' +
            '<div class="ap-add-property-error" id="ap-add-property-error"></div>' +
            '<div class="ap-add-property-actions">' +
            '<button type="button" class="button ap-add-property-cancel">' + (i18n.cancel || 'Cancelar') + '</button>' +
            '<button type="button" class="button button-primary ap-add-property-submit">' + (i18n.create || 'Crear') + '</button>' +
            '</div></div></div>';
        $portal.append(html);

        $overlay = $('#ap-add-property-overlay');
        $modal = $('#ap-add-property-modal');
        $input = $('#ap-add-property-title-input');
        $submit = $modal.find('.ap-add-property-submit');
        $cancel = $modal.find('.ap-add-property-cancel');
        $error = $('#ap-add-property-error');

        $overlay.on('click', close);
        $cancel.on('click', close);
        $submit.on('click', submit);
        $input.on('keydown', function (e) {
            if (e.key === 'Escape') close();
            if (e.key === 'Enter') { e.preventDefault(); submit(); }
        });

        $(document).on('keydown.apAddPropertyModal', function (e) {
            if (e.key === 'Escape' && $modal.hasClass('is-open')) {
                close();
            }
        });
    }

    function submit() {
        var title = $.trim($input.val());
        $error.text('').hide();
        if (!title) {
            $error.text(config.i18n && config.i18n.required ? config.i18n.required : 'El nombre es obligatorio.').show();
            $input.focus();
            return;
        }
        $submit.prop('disabled', true);
        $.post(config.ajaxurl, {
            action: 'alquipress_create_property',
            nonce: config.nonce,
            title: title,
        })
            .done(function (resp) {
                if (resp.success && resp.data && resp.data.edit_url) {
                    window.location.href = resp.data.edit_url;
                } else {
                    $error.text(config.i18n && config.i18n.error ? config.i18n.error : 'Error al crear la propiedad.').show();
                    $submit.prop('disabled', false);
                }
            })
            .fail(function () {
                $error.text(config.i18n && config.i18n.error ? config.i18n.error : 'Error al crear la propiedad.').show();
                $submit.prop('disabled', false);
            });
    }

    $(function () {
        $(document).on('click', '.ap-add-property-trigger', open);
    });
})(jQuery);
