/**
 * Enmascarar IBAN con botón toggle show/hide
 */

jQuery(document).ready(function ($) {

    // Buscar el campo IBAN de ACF
    const ibanField = $('[data-name="datos_bancarios_iban"]');

    if (ibanField.length) {
        const ibanInput = ibanField.find('input[type="text"]');
        const ibanValue = ibanInput.val();

        // Solo procesar si hay valor y es suficientemente largo
        if (ibanValue && ibanValue.length > 8) {

            // Crear valor enmascarado (mostrar primeros 4 y últimos 4 caracteres)
            const first = ibanValue.substring(0, 4);
            const last = ibanValue.substring(ibanValue.length - 4);
            const maskedValue = first + ' •••• •••• •••• •••• ' + last;

            // Envolver el input en un contenedor
            ibanInput.wrap('<div class="iban-wrapper"></div>');
            const wrapper = ibanInput.parent();

            // Crear elementos de UI
            const maskedSpan = $('<span class="iban-masked">' + maskedValue + '</span>');
            const toggleBtn = $('<button type="button" class="button iban-toggle">👁️ Mostrar IBAN</button>');

            // Añadir elementos al DOM
            wrapper.append(maskedSpan);
            wrapper.append(toggleBtn);

            // Ocultar input real por defecto
            ibanInput.addClass('iban-hidden');

            // Toggle visibility al hacer click
            toggleBtn.on('click', function (e) {
                e.preventDefault();

                if (ibanInput.hasClass('iban-hidden')) {
                    // Mostrar IBAN real
                    ibanInput.removeClass('iban-hidden');
                    maskedSpan.hide();
                    toggleBtn.html('🔒 Ocultar IBAN');
                    toggleBtn.addClass('iban-visible');

                    // Log de auditoría SERVER-SIDE
                    $.post(ibanMaskData.ajaxUrl, {
                        action: 'alquipress_log_iban_access',
                        nonce: ibanMaskData.nonce,
                        owner_id: ibanMaskData.ownerId,
                        action_type: 'reveal_iban'
                    });

                } else {
                    // Ocultar IBAN
                    ibanInput.addClass('iban-hidden');
                    maskedSpan.show();
                    toggleBtn.html('👁️ Mostrar IBAN');
                    toggleBtn.removeClass('iban-visible');
                }
            });

            // Warning visual en el campo
            const warningNotice = $('<div class="iban-warning">🔒 Dato Sensible - Acceso Registrado</div>');
            ibanField.find('.acf-label').append(warningNotice);
        }
    }
});
