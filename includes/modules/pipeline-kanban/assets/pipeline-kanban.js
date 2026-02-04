/**
 * JavaScript para Pipeline Kanban Alquipress
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        // ========== Inicialización de SortableJS ==========

        const columns = document.querySelectorAll('.column-cards');
        
        columns.forEach(column => {
            new Sortable(column, {
                group: 'pipeline',
                animation: 150,
                ghostClass: 'card-ghost',
                dragClass: 'card-dragging',
                
                // Al soltar una tarjeta en una nueva columna
                onEnd: function (evt) {
                    const orderId = evt.item.getAttribute('data-order-id');
                    const newStatus = evt.to.closest('.pipeline-column').getAttribute('data-status');
                    const oldStatus = evt.from.closest('.pipeline-column').getAttribute('data-status');

                    if (newStatus === oldStatus) return;

                    updateOrderStatus(orderId, newStatus, evt.item);
                }
            });
        });

        /**
         * AJAX: Actualizar estado de pedido
         */
        function updateOrderStatus(orderId, status, cardElement) {
            const $card = $(cardElement);
            $card.addClass('updating');

            $.ajax({
                url: alquipressPipeline.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alquipress_update_order_status',
                    order_id: orderId,
                    status: status,
                    nonce: alquipressPipeline.nonce
                },
                success: function (response) {
                    if (response.success) {
                        updateColumnCounts();
                        // Notificación visual simple
                        $card.removeClass('updating').addClass('update-success');
                        setTimeout(() => $card.removeClass('update-success'), 1000);
                    } else {
                        alert('Error: ' + response.data);
                        location.reload(); // Recargar para volver al estado anterior
                    }
                },
                error: function () {
                    alert('Error de conexión al actualizar el estado.');
                    location.reload();
                }
            });
        }

        /**
         * Actualizar los contadores de las columnas
         */
        function updateColumnCounts() {
            $('.pipeline-column').each(function () {
                const count = $(this).find('.order-card').length;
                $(this).find('.column-count').text(count);
            });
            
            // Actualizar total general
            let total = 0;
            $('.column-count').each(function() {
                total += parseInt($(this).text()) || 0;
            });
            $('.total-orders-count').text('(' + total + ' reservas)');
        }

        // ========== Otras Funcionalidades ==========

        /**
         * Smooth scroll horizontal en el tablero
         */
        const pipelineBoard = $('.alquipress-pipeline-board');
        if (pipelineBoard.length) {
            pipelineBoard.on('wheel', function (e) {
                if (e.originalEvent.deltaY !== 0) {
                    e.preventDefault();
                    this.scrollLeft += e.originalEvent.deltaY;
                }
            });
        }

        /**
         * Búsqueda rápida
         */
        function initQuickSearch() {
            const searchHTML = `
                <div class="quick-search-wrapper" style="margin-bottom: 20px;">
                    <div class="search-input-group" style="position: relative; max-width: 400px;">
                        <span class="dashicons dashicons-search" style="position: absolute; left: 10px; top: 8px; color: #94a3b8;"></span>
                        <input type="text" id="quick-search" placeholder="Buscar por pedido, cliente o propiedad..." 
                               style="width: 100%; padding: 8px 12px 8px 35px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px;">
                    </div>
                </div>
            `;

            $('.alquipress-pipeline-filters').before(searchHTML);

            $('#quick-search').on('input', function () {
                const term = $(this).val().toLowerCase();
                $('.order-card').each(function () {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(term));
                });
                updateColumnCounts();
            });
        }

        initQuickSearch();

        /**
         * Abrir pedido en nueva pestaña al hacer click
         */
        $('.order-card').on('click', function (e) {
            if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) return;
            const url = $(this).data('order-url');
            if (url) window.open(url, '_blank');
        });

        console.log('[ALQUIPRESS Pipeline] Kanban con Drag & Drop inicializado');
    });

})(jQuery);
