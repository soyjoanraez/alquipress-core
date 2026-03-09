/**
 * JavaScript para Panel de Salud Operativa
 * 
 * @package Alquipress
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // ========== Filtros por Prioridad ==========
        
        $('.health-filter-btn').on('click', function() {
            const priority = $(this).data('priority');
            
            // Actualizar botones activos
            $('.health-filter-btn').removeClass('active');
            $(this).addClass('active');
            
            // Filtrar alertas
            if (priority === 'all') {
                $('.health-alert-card').show();
            } else {
                $('.health-alert-card').hide();
                $('.health-alert-card.health-priority-' + priority).show();
            }
        });
        
        // ========== Ver Todos los Items ==========
        
        $('.health-view-all').on('click', function(e) {
            e.preventDefault();
            const alertType = $(this).data('alert-type');
            const $card = $(this).closest('.health-alert-card');
            
            // Mostrar modal con todos los items
            showAlertModal(alertType, $card);
        });
        
        /**
         * Mostrar modal con todos los items de una alerta
         */
        function showAlertModal(alertType, $card) {
            // Crear modal si no existe
            if (!$('#health-alert-modal').length) {
                $('body').append(`
                    <div id="health-alert-modal" class="health-modal">
                        <div class="health-modal-content">
                            <span class="health-modal-close">&times;</span>
                            <h2 class="health-modal-title"></h2>
                            <div class="health-modal-body"></div>
                        </div>
                    </div>
                `);
            }
            
            const $modal = $('#health-alert-modal');
            const $title = $modal.find('.health-modal-title');
            const $body = $modal.find('.health-modal-body');
            
            // Obtener título de la card
            const title = $card.find('.health-alert-title').text();
            $title.text(title);
            
            // Cargar datos via AJAX
            $body.html('<div style="text-align: center; padding: 40px;"><span class="spinner is-active" style="float: none; margin: 0;"></span><p style="margin: 15px 0 0; color: #666;">Cargando detalles...</p></div>');
            
            $.ajax({
                url: alquipressHealth.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alquipress_get_alert_details',
                    alert_type: alertType,
                    nonce: alquipressHealth.nonce
                },
                success: function(response) {
                    if (response.success && response.data.items) {
                        let html = '<ul class="health-modal-items">';
                        response.data.items.forEach(function(item) {
                            html += '<li>';
                            if (item.order_id) {
                                html += '<a href="' + item.order_url + '" target="_blank">';
                                html += 'Pedido #' + (item.order_number || item.order_id);
                                html += '</a>';
                                if (item.customer_name) {
                                    html += ' - ' + item.customer_name;
                                }
                                if (item.days_pending !== undefined) {
                                    html += ' <span class="health-item-meta">(' + item.days_pending + ' días pendiente)</span>';
                                }
                                if (item.days_until_checkin !== undefined) {
                                    html += ' <span class="health-item-meta">(Check-in en ' + item.days_until_checkin + ' días)</span>';
                                }
                                if (item.missing_docs && item.missing_docs.length > 0) {
                                    html += ' <span class="health-item-meta">[Faltan: ' + item.missing_docs.join(', ') + ']</span>';
                                }
                            }
                            html += '</li>';
                        });
                        html += '</ul>';
                        $body.html(html);
                    } else {
                        $body.html('<p style="text-align: center; color: #646970;">No se encontraron elementos.</p>');
                    }
                },
                error: function() {
                    $body.html('<p style="text-align: center; color: #dc3232;">Error al cargar los detalles.</p>');
                }
            });
            
            // Mostrar modal
            $modal.fadeIn();
        }
        
        // Cerrar modal
        $(document).on('click', '.health-modal-close, .health-modal', function(e) {
            if (e.target === this || $(e.target).hasClass('health-modal-close')) {
                $('#health-alert-modal').fadeOut();
            }
        });
        
        // Scroll suave al hacer click en contador del admin bar
        $('#wpadminbar .alquipress-health-counter a').on('click', function() {
            setTimeout(function() {
                const $widget = $('.alquipress-health-widget');
                if ($widget.length) {
                    $('html, body').animate({
                        scrollTop: $widget.offset().top - 100
                    }, 500);
                }
            }, 100);
        });
        
    });
    
})(jQuery);
