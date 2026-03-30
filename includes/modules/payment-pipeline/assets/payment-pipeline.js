/**
 * JavaScript para Pipeline de Cobros
 * 
 * @package Alquipress
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const PaymentPipeline = {
        
        init: function() {
            this.loadPipeline();
            this.setupFilters();
            this.setupSortable();
        },
        
        /**
         * Cargar datos del pipeline
         */
        loadPipeline: function() {
            const $container = $('#payment-pipeline-kanban');
            if (!$container.length) {
                return;
            }
            
            $container.html('<div class="pipeline-loading"><span class="spinner is-active"></span><p>Cargando pipeline de cobros...</p></div>');
            
            $.ajax({
                url: alquipressPaymentPipeline.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alquipress_get_payment_pipeline',
                    nonce: alquipressPaymentPipeline.nonce,
                    date_from: $('#filter-date-from').val() || '',
                    date_to: $('#filter-date-to').val() || ''
                },
                success: function(response) {
                    if (response.success) {
                        PaymentPipeline.renderKanban(response.data.payments);
                    } else {
                        $container.html('<div class="pipeline-loading"><p style="color: #dc3232;">Error al cargar el pipeline: ' + (response.data.message || 'Error desconocido') + '</p></div>');
                        if (typeof AlquipressToast !== 'undefined') {
                            AlquipressToast.error(response.data.message || 'Error al cargar el pipeline');
                        }
                    }
                },
                error: function() {
                    $container.html('<div class="pipeline-loading"><p style="color: #dc3232;">Error de conexión. Por favor, intenta de nuevo.</p></div>');
                    if (typeof AlquipressToast !== 'undefined') {
                        AlquipressToast.error('Error de conexión. Por favor, intenta de nuevo.');
                    }
                }
            });
        },
        
        /**
         * Renderizar Kanban
         */
        renderKanban: function(payments) {
            const $container = $('#payment-pipeline-kanban');
            
            const columns = [
                { id: 'deposit-pending', label: 'Depósito Pendiente', icon: '💰' },
                { id: 'deposit-paid', label: 'Depósito Pagado', icon: '✅' },
                { id: 'balance-pending', label: 'Saldo Pendiente', icon: '⏳' },
                { id: 'fully-paid', label: 'Totalmente Pagado', icon: '🎉' },
                { id: 'security-held', label: 'Fianza Retenida', icon: '🔒' },
                { id: 'security-refunded', label: 'Fianza Devuelta', icon: '💸' }
            ];
            
            let html = '<div class="payment-pipeline-board">';
            
            columns.forEach(function(column) {
                const columnPayments = payments[column.id] || [];
                
                html += '<div class="payment-column ' + column.id + '" data-status="' + column.id + '">';
                html += '<div class="payment-column-header">';
                html += '<div class="payment-column-title">';
                html += '<span class="column-icon">' + column.icon + '</span>';
                html += '<span class="column-label">' + column.label + '</span>';
                html += '</div>';
                html += '<span class="payment-column-count">' + columnPayments.length + '</span>';
                html += '</div>';
                html += '<div class="payment-column-body" data-status="' + column.id + '">';
                
                if (columnPayments.length === 0) {
                    html += '<div style="text-align: center; padding: 20px; color: #646970; font-size: 13px;">No hay pagos</div>';
                } else {
                    columnPayments.forEach(function(payment) {
                        html += PaymentPipeline.renderPaymentCard(payment);
                    });
                }
                
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            
            $container.html(html);
            
            // Reinicializar Sortable después de renderizar
            this.setupSortable();
        },
        
        /**
         * Renderizar card de pago
         */
        renderPaymentCard: function(payment) {
            const daysUntilDue = payment.days_until_due;
            const isOverdue = payment.is_overdue;
            const daysClass = isOverdue ? 'overdue' : (daysUntilDue !== null && daysUntilDue <= 3 ? 'due-soon' : '');
            const daysText = isOverdue 
                ? Math.abs(daysUntilDue) + ' días vencido'
                : (daysUntilDue === null ? '' : daysUntilDue + ' días');
            
            let html = '<div class="payment-card" data-payment-id="' + payment.payment_id + '" data-order-id="' + payment.order_id + '">';
            html += '<div class="payment-card-header">';
            html += '<a href="' + payment.order_url + '" target="_blank" class="payment-order-number">Pedido #' + payment.order_number + '</a>';
            if (isOverdue || (daysUntilDue !== null && daysUntilDue <= 3)) {
                html += '<span class="payment-status-badge ' + (isOverdue ? 'overdue' : 'due-soon') + '">' + (isOverdue ? 'Vencido' : 'Próximo') + '</span>';
            }
            html += '</div>';
            html += '<div class="payment-card-body">';
            html += '<div class="payment-customer">' + payment.customer_name + '</div>';
            html += '<div class="payment-property">' + payment.property_name + '</div>';
            html += '<div class="payment-amount">' + payment.amount_formatted + '</div>';
            html += '<div class="payment-meta">';
            if (payment.scheduled_date) {
                html += '<div class="payment-due-date">';
                html += '<span>Vence:</span> <span class="payment-days ' + daysClass + '">' + daysText + '</span>';
                html += '</div>';
            }
            html += '<div>Tipo: ' + (payment.payment_type === 'deposit' ? 'Depósito' : payment.payment_type === 'balance' ? 'Saldo' : 'Fianza') + '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            return html;
        },
        
        /**
         * Configurar filtros
         */
        setupFilters: function() {
            $('#filter-apply').on('click', function() {
                PaymentPipeline.loadPipeline();
            });
            
            $('#filter-reset').on('click', function() {
                $('#filter-date-from').val('');
                $('#filter-date-to').val('');
                PaymentPipeline.loadPipeline();
            });
        },
        
        /**
         * Configurar drag & drop con Sortable
         */
        setupSortable: function() {
            if (typeof Sortable === 'undefined') {
                return;
            }
            
            $('.payment-column-body').each(function() {
                const $body = $(this);
                const status = $body.data('status');
                
                // Destruir instancia anterior si existe
                if ($body.data('sortable')) {
                    $body.data('sortable').destroy();
                }
                
                const sortable = Sortable.create($body[0], {
                    group: 'payments',
                    animation: 150,
                    handle: '.payment-card',
                    onEnd: function(evt) {
                        const $card = $(evt.item);
                        const paymentId = $card.data('payment-id');
                        const newStatus = evt.to.dataset.status;
                        const oldStatus = evt.from.dataset.status;
                        
                        if (newStatus === oldStatus) {
                            return;
                        }
                        
                        // Actualizar estado
                        PaymentPipeline.updatePaymentStatus(paymentId, newStatus, $card);
                    }
                });
                
                $body.data('sortable', sortable);
            });
        },
        
        /**
         * Actualizar estado de pago
         */
        updatePaymentStatus: function(paymentId, newStatus, $card) {
            $card.addClass('updating');
            
            // Determinar nuevo estado según la columna
            let status = 'pending';
            if (newStatus === 'deposit-paid' || newStatus === 'fully-paid' || newStatus === 'security-refunded') {
                status = 'paid';
            }
            
            $.ajax({
                url: alquipressPaymentPipeline.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alquipress_update_payment_status',
                    nonce: alquipressPaymentPipeline.nonce,
                    payment_id: paymentId,
                    new_status: status
                },
                success: function(response) {
                    $card.removeClass('updating');
                    
                    if (response.success) {
                        if (typeof AlquipressToast !== 'undefined') {
                            AlquipressToast.success('Estado actualizado correctamente');
                        }
                        // Recargar pipeline para actualizar contadores
                        setTimeout(function() {
                            PaymentPipeline.loadPipeline();
                        }, 500);
                    } else {
                        // Revertir posición si falla
                        $card.closest('.payment-column-body').prepend($card);
                        if (typeof AlquipressToast !== 'undefined') {
                            AlquipressToast.error(response.data.message || 'Error al actualizar el estado');
                        }
                    }
                },
                error: function() {
                    $card.removeClass('updating');
                    // Revertir posición
                    $card.closest('.payment-column-body').prepend($card);
                    if (typeof AlquipressToast !== 'undefined') {
                        AlquipressToast.error('Error de conexión. Por favor, intenta de nuevo.');
                    }
                }
            });
        }
    };
    
    $(document).ready(function() {
        if ($('#payment-pipeline-kanban').length) {
            PaymentPipeline.init();
        }
    });
    
})(jQuery);
