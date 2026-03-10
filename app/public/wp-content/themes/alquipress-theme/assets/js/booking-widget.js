/**
 * JavaScript frontend para Widget de Reserva
 * Cálculo dinámico de precios y validación de disponibilidad
 */

(function() {
    'use strict';
    
    const bookingWidgets = document.querySelectorAll('.alq-booking-widget');
    
    bookingWidgets.forEach(function(widget) {
        initBookingWidget(widget);
    });
    
    function initBookingWidget(widget) {
        const productId = widget.dataset.productId;
        const checkinInput = widget.querySelector('input[name="checkin"]');
        const checkoutInput = widget.querySelector('input[name="checkout"]');
        const guestsSelect = widget.querySelector('select[name="guests"]');
        const summary = widget.querySelector('.alq-booking-widget-summary');
        
        if (!checkinInput || !checkoutInput || !summary) {
            return;
        }
        
        // Validar fechas y calcular precio cuando cambien
        [checkinInput, checkoutInput, guestsSelect].forEach(function(input) {
            if (input) {
                input.addEventListener('change', function() {
                    if (checkinInput.value && checkoutInput.value) {
                        validateAndCalculate(widget, productId, checkinInput.value, checkoutInput.value, guestsSelect.value);
                    }
                });
            }
        });
        
        // Actualizar fecha mínima de checkout cuando cambie checkin
        checkinInput.addEventListener('change', function() {
            if (this.value) {
                const minCheckout = new Date(this.value);
                minCheckout.setDate(minCheckout.getDate() + 1);
                checkoutInput.min = minCheckout.toISOString().split('T')[0];
                
                if (checkoutInput.value && new Date(checkoutInput.value) <= new Date(this.value)) {
                    checkoutInput.value = '';
                }
            }
        });
    }
    
    function validateAndCalculate(widget, productId, checkin, checkout, guests) {
        // Validar disponibilidad
        fetch('/wp-json/alquipress/v1/availability-check?product_id=' + productId + '&checkin=' + checkin + '&checkout=' + checkout)
            .then(response => response.json())
            .then(data => {
                if (!data.available) {
                    showError(widget, 'Las fechas seleccionadas no están disponibles');
                    return;
                }
                
                // Calcular precio
                return fetch('/wp-json/alquipress/v1/price-calculation?product_id=' + productId + '&checkin=' + checkin + '&checkout=' + checkout + '&guests=' + guests);
            })
            .then(response => response ? response.json() : null)
            .then(priceData => {
                if (priceData) {
                    updateSummary(widget, priceData);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    function updateSummary(widget, priceData) {
        const subtotalEl = widget.querySelector('.alq-booking-widget-summary-subtotal');
        const totalEl = widget.querySelector('.alq-booking-widget-summary-total-amount');
        const depositEl = widget.querySelector('.alq-booking-widget-summary-deposit-amount');
        const balanceEl = widget.querySelector('.alq-booking-widget-summary-balance-amount');
        
        if (subtotalEl) {
            subtotalEl.textContent = priceData.subtotal.toFixed(2) + '€';
            // Actualizar texto de noches
            const subtotalRow = subtotalEl.closest('.alq-booking-widget-summary-row');
            if (subtotalRow) {
                const label = subtotalRow.querySelector('span:first-child');
                if (label) {
                    label.textContent = 'Subtotal (' + priceData.nights + ' ' + (priceData.nights === 1 ? 'noche' : 'noches') + '):';
                }
            }
        }
        
        if (totalEl) {
            totalEl.textContent = priceData.total.toFixed(2) + '€';
        }
        
        if (depositEl) {
            depositEl.textContent = priceData.deposit.toFixed(2) + '€';
        }
        
        if (balanceEl) {
            balanceEl.textContent = priceData.balance.toFixed(2) + '€';
        }
    }
    
    function showError(widget, message) {
        // Mostrar mensaje de error (puedes usar un toast o alert)
        alert(message);
    }
})();
