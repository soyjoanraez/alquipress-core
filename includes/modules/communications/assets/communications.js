/**
 * JavaScript para módulo de Comunicaciones
 * 
 * @package Alquipress
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // ========== Mostrar/Ocultar filtro de ID según tipo de entidad ==========
        
        $('#filter-entity-type').on('change', function() {
            const $wrapper = $('#filter-entity-id-wrapper');
            if ($(this).val()) {
                $wrapper.show();
            } else {
                $wrapper.hide();
                $wrapper.find('input').val('');
            }
        });
        
        // ========== Autocompletado para destinatarios ==========
        
        let autocompleteCache = {};
        let autocompleteTimeout;
        
        $('#to_email, input[name="to_email"]').on('input', function() {
            const $input = $(this);
            const term = $input.val();
            
            // Limpiar timeout anterior
            clearTimeout(autocompleteTimeout);
            
            // Eliminar dropdown si el campo está vacío
            if (term.length < 2) {
                $input.siblings('.ap-comm-autocomplete').remove();
                return;
            }
            
            // Esperar 300ms antes de buscar (debounce)
            autocompleteTimeout = setTimeout(function() {
                // Cachear resultados
                if (autocompleteCache[term]) {
                    showAutocompleteResults($input, autocompleteCache[term]);
                    return;
                }
                
                $.ajax({
                    url: alquipressComm.ajaxUrl,
                    type: 'GET',
                    data: {
                        action: 'alquipress_comm_get_autocomplete',
                        term: term,
                        nonce: alquipressComm.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.results) {
                            autocompleteCache[term] = response.data.results;
                            showAutocompleteResults($input, response.data.results);
                        }
                    }
                });
            }, 300);
        });
        
        function showAutocompleteResults($input, results) {
            // Eliminar dropdown anterior
            $input.siblings('.ap-comm-autocomplete').remove();
            
            if (results.length === 0) {
                return;
            }
            
            const $dropdown = $('<div class="ap-comm-autocomplete"></div>');
            results.forEach(function(item) {
                const $item = $('<div class="ap-comm-autocomplete-item" data-email="' + item.id + '" data-type="' + item.type + '" data-entity-id="' + item.entity_id + '">' + item.text + '</div>');
                $item.on('click', function() {
                    $input.val(item.id);
                    $('#send-entity-type').val(item.type);
                    $('#send-entity-type-select').val(item.type);
                    $('#send-entity-id-input').val(item.entity_id);
                    $dropdown.remove();
                });
                $dropdown.append($item);
            });
            
            $input.after($dropdown);
        }
        
        // Cerrar autocompletado al hacer click fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.ap-comm-autocomplete, #to_email, input[name="to_email"]').length) {
                $('.ap-comm-autocomplete').remove();
            }
        });
        
        // ========== Ver contenido de email (usar delegación para elementos dinámicos) ==========
        
        $(document).on('click', '.ap-comm-view-email', function(e) {
            e.preventDefault();
            const emailId = $(this).data('email-id');
            
            $.ajax({
                url: alquipressComm.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alquipress_comm_get_email_content',
                    email_id: emailId,
                    nonce: alquipressComm.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showEmailModal(response.data);
                    } else {
                        if (typeof AlquipressToast !== 'undefined') {
                            AlquipressToast.error(response.data.message || 'Error al cargar el email');
                        }
                    }
                },
                error: function() {
                    if (typeof AlquipressToast !== 'undefined') {
                        AlquipressToast.error('Error de conexión');
                    }
                }
            });
        });
        
        function showEmailModal(data) {
            // Crear modal si no existe
            if (!$('#ap-comm-email-modal').length) {
                $('body').append(`
                    <div id="ap-comm-email-modal" class="ap-comm-modal">
                        <div class="ap-comm-modal-content">
                            <span class="ap-comm-modal-close">&times;</span>
                            <h2 class="ap-comm-modal-title"></h2>
                            <div class="ap-comm-modal-meta"></div>
                            <div class="ap-comm-modal-body"></div>
                        </div>
                    </div>
                `);
            }
            
            const $modal = $('#ap-comm-email-modal');
            $modal.find('.ap-comm-modal-title').text(data.subject);
            $modal.find('.ap-comm-modal-meta').html(
                '<p><strong>' + (data.direction === 'inbound' ? 'De:' : 'Para:') + '</strong> ' + (data.direction === 'inbound' ? data.from : data.to) + '</p>' +
                '<p><strong>Fecha:</strong> ' + data.date + '</p>' +
                '<p><strong>Estado:</strong> ' + data.status + '</p>'
            );
            $modal.find('.ap-comm-modal-body').html(data.content);
            $modal.fadeIn();
        }
        
        // Cerrar modal
        $(document).on('click', '.ap-comm-modal-close, .ap-comm-modal', function(e) {
            if (e.target === this || $(e.target).hasClass('ap-comm-modal-close')) {
                $('#ap-comm-email-modal').fadeOut();
            }
        });
        
        // ========== Reenviar email (usar delegación para elementos dinámicos) ==========
        
        $(document).on('click', '.ap-comm-resend', function(e) {
            e.preventDefault();
            const emailId = $(this).data('email-id');
            const $btn = $(this);
            
            if (!confirm('¿Estás seguro de que quieres reenviar este email?')) {
                return;
            }
            
            $btn.prop('disabled', true).text('...');
            
            $.ajax({
                url: alquipressComm.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alquipress_comm_resend_email',
                    email_id: emailId,
                    nonce: alquipressComm.nonce
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('↻');
                    if (response.success) {
                        if (typeof AlquipressToast !== 'undefined') {
                            AlquipressToast.success(response.data.message || 'Email reenviado correctamente');
                        }
                        // Recargar página después de 1 segundo
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        if (typeof AlquipressToast !== 'undefined') {
                            AlquipressToast.error(response.data.message || 'Error al reenviar');
                        }
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('↻');
                    if (typeof AlquipressToast !== 'undefined') {
                        AlquipressToast.error('Error de conexión');
                    }
                }
            });
        });
        
        // ========== Vista previa de email ==========
        
        $('#preview-email').on('click', function(e) {
            e.preventDefault();
            const $form = $('#ap-comm-send-form');
            const to = $form.find('input[name="to_email"]').val();
            const subject = $form.find('input[name="subject"]').val();
            const message = $form.find('textarea[name="message"]').val();
            
            if (!to || !subject || !message) {
                if (typeof AlquipressToast !== 'undefined') {
                    AlquipressToast.warning('Por favor, completa todos los campos antes de ver la vista previa');
                } else {
                    alert('Por favor, completa todos los campos antes de ver la vista previa');
                }
                return;
            }
            
            // Crear modal de vista previa
            if (!$('#ap-comm-preview-modal').length) {
                $('body').append(`
                    <div id="ap-comm-preview-modal" class="ap-comm-modal">
                        <div class="ap-comm-modal-content">
                            <span class="ap-comm-modal-close">&times;</span>
                            <h2 class="ap-comm-modal-title">Vista previa del email</h2>
                            <div class="ap-comm-modal-meta">
                                <p><strong>Para:</strong> ${to}</p>
                                <p><strong>Asunto:</strong> ${subject}</p>
                            </div>
                            <div class="ap-comm-modal-body"></div>
                        </div>
                    </div>
                `);
            }
            
            const $modal = $('#ap-comm-preview-modal');
            // Renderizar el mensaje con formato HTML básico
            const formattedMessage = message.replace(/\n/g, '<br>');
            $modal.find('.ap-comm-modal-body').html(formattedMessage);
            $modal.fadeIn();
        });
        
        // Cerrar modal de vista previa
        $(document).on('click', '#ap-comm-preview-modal .ap-comm-modal-close, #ap-comm-preview-modal', function(e) {
            if (e.target === this || $(e.target).hasClass('ap-comm-modal-close')) {
                $('#ap-comm-preview-modal').fadeOut();
            }
        });
        
        // ========== Exportar CSV ==========
        
        $('#export-csv').on('click', function(e) {
            e.preventDefault();
            
            const filters = {
                filter_direction: $('select[name="filter_direction"]').val() || '',
                filter_status: $('select[name="filter_status"]').val() || '',
                filter_entity_type: $('select[name="filter_entity_type"]').val() || '',
                filter_entity_id: $('input[name="filter_entity_id"]').val() || '',
                filter_owner_id: $('select[name="filter_owner_id"]').val() || '',
                filter_guest_id: $('select[name="filter_guest_id"]').val() || '',
                filter_date_from: $('input[name="filter_date_from"]').val() || '',
                filter_date_to: $('input[name="filter_date_to"]').val() || ''
            };
            
            const params = new URLSearchParams({
                action: 'alquipress_comm_export_csv',
                nonce: alquipressComm.nonce
            });
            
            // Agregar filtros al URLSearchParams
            Object.keys(filters).forEach(function(key) {
                if (filters[key]) {
                    params.append(key, filters[key]);
                }
            });
            
            window.location.href = alquipressComm.ajaxUrl + '?' + params.toString();
        });
        
    });
    
})(jQuery);
