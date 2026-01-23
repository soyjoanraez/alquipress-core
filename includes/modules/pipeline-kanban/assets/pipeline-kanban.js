/**
 * JavaScript para Pipeline Kanban
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        // ==========  Funcionalidades del Pipeline ==========

        /**
         * Resaltar tarjetas al pasar el mouse
         */
        $('.order-card').on('mouseenter', function () {
            $(this).addClass('hover');
        }).on('mouseleave', function () {
            $(this).removeClass('hover');
        });

        /**
         * Smooth scroll horizontal en el tablero
         */
        const pipelineBoard = $('.alquipress-pipeline-board');

        if (pipelineBoard.length) {
            // Detectar si el usuario puede hacer scroll horizontal
            const hasHorizontalScroll = pipelineBoard[0].scrollWidth > pipelineBoard[0].clientWidth;

            if (hasHorizontalScroll) {
                // Añadir indicador visual de scroll
                pipelineBoard.addClass('has-scroll');

                // Scroll suave con la rueda del mouse
                pipelineBoard.on('wheel', function (e) {
                    if (e.originalEvent.deltaY !== 0) {
                        e.preventDefault();
                        this.scrollLeft += e.originalEvent.deltaY;
                    }
                });
            }
        }

        /**
         * Contador total de pedidos
         */
        function updateTotalCount() {
            let total = 0;
            $('.column-count').each(function () {
                total += parseInt($(this).text()) || 0;
            });

            // Añadir al título si no existe
            if (!$('.total-orders-count').length) {
                $('.wp-heading-inline').after(
                    '<span class="total-orders-count" style="color: #666; font-size: 16px; font-weight: 400; margin-left: 10px;">(' + total + ' reservas)</span>'
                );
            } else {
                $('.total-orders-count').text('(' + total + ' reservas)');
            }
        }

        updateTotalCount();

        /**
         * Animación de entrada de tarjetas
         */
        $('.order-card').each(function (index) {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(20px)'
            });

            setTimeout(() => {
                $(this).css({
                    'opacity': '1',
                    'transform': 'translateY(0)',
                    'transition': 'all 0.3s ease'
                });
            }, index * 50); // Delay escalonado
        });

        /**
         * Abrir pedido en nueva pestaña al hacer click en la tarjeta
         */
        $('.order-card').on('click', function (e) {
            // No abrir si se hizo click en un enlace o botón
            if ($(e.target).is('a') || $(e.target).is('button') || $(e.target).closest('a, button').length) {
                return;
            }

            const orderUrl = $(this).data('order-url');
            if (orderUrl) {
                window.open(orderUrl, '_blank');
            }
        });

        /**
         * Añadir tooltip a tarjetas urgentes
         */
        $('.order-card.urgent').each(function () {
            $(this).attr('title', 'Check-in en menos de 3 días');
        });

        /**
         * Resaltar columna al hacer hover sobre tarjeta
         */
        $('.order-card').on('mouseenter', function () {
            $(this).closest('.pipeline-column').addClass('column-highlighted');
        }).on('mouseleave', function () {
            $('.pipeline-column').removeClass('column-highlighted');
        });

        /**
         * Añadir clase CSS para columnas resaltadas
         */
        $('<style>')
            .prop('type', 'text/css')
            .html(`
                .column-highlighted .column-header {
                    background: #f0f6fc;
                    transition: background 0.2s;
                }
            `)
            .appendTo('head');

        /**
         * Búsqueda rápida de pedidos
         */
        function addQuickSearch() {
            const searchHTML = `
                <div class="quick-search-wrapper" style="margin-bottom: 15px;">
                    <input type="text" id="quick-search" class="regular-text" placeholder="🔍 Buscar por pedido, cliente o propiedad..." style="width: 100%; max-width: 400px;" />
                </div>
            `;

            $('.alquipress-pipeline-filters').before(searchHTML);

            $('#quick-search').on('input', function () {
                const searchTerm = $(this).val().toLowerCase();

                $('.order-card').each(function () {
                    const cardText = $(this).text().toLowerCase();

                    if (cardText.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                // Actualizar contadores
                $('.pipeline-column').each(function () {
                    const visibleCards = $(this).find('.order-card:visible').length;
                    $(this).find('.column-count').text(visibleCards);
                });

                // Mostrar mensaje si no hay resultados
                $('.pipeline-column').each(function () {
                    const $column = $(this);
                    const visibleCards = $column.find('.order-card:visible').length;

                    if (visibleCards === 0 && searchTerm !== '') {
                        if (!$column.find('.no-results-message').length) {
                            $column.find('.column-cards').append(
                                '<div class="no-results-message" style="text-align: center; padding: 20px; color: #999; font-size: 12px;">Sin resultados</div>'
                            );
                        }
                    } else {
                        $column.find('.no-results-message').remove();
                    }
                });
            });
        }

        addQuickSearch();

        /**
         * Mostrar loading state al recargar
         */
        $('#refresh-pipeline').on('click', function () {
            const $button = $(this);
            const originalText = $button.text();

            $button.prop('disabled', true).text('🔄 Cargando...');

            setTimeout(function () {
                location.reload();
            }, 300);
        });

        /**
         * Log para debugging
         */
        console.log('[ALQUIPRESS Pipeline] Inicializado correctamente');
        console.log('[ALQUIPRESS Pipeline] Total de tarjetas:', $('.order-card').length);
        console.log('[ALQUIPRESS Pipeline] Total de columnas:', $('.pipeline-column').length);

    });

})(jQuery);
