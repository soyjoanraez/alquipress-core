/**
 * ALQUIPRESS Payment Manager - Frontend JS
 */

(function($) {
    'use strict';

    var APM = {
        init: function() {
            this.bindEvents();
            this.updateTotalDisplay();
        },

        bindEvents: function() {
            // Toggle full payment option
            $(document).on('change', '#apm_pay_full', this.onFullPaymentToggle.bind(this));

            // Update on checkout update
            $(document.body).on('updated_checkout', this.onCheckoutUpdated.bind(this));
        },

        onFullPaymentToggle: function(e) {
            var $checkbox = $(e.target);
            var $breakdown = $('.apm-payment-breakdown');
            var isFullPayment = $checkbox.is(':checked');

            if (isFullPayment) {
                $breakdown.addClass('apm-full-selected');
            } else {
                $breakdown.removeClass('apm-full-selected');
            }

            // Trigger checkout update to recalculate totals
            $(document.body).trigger('update_checkout');
        },

        onCheckoutUpdated: function() {
            this.updateTotalDisplay();
        },

        updateTotalDisplay: function() {
            var $breakdown = $('#apm-payment-breakdown');
            if (!$breakdown.length) {
                return;
            }

            var $payFullCheckbox = $('#apm_pay_full');
            if ($payFullCheckbox.is(':checked')) {
                // Show full payment in order review
                this.highlightFullPayment();
            } else {
                // Show deposit only
                this.highlightDeposit();
            }
        },

        highlightFullPayment: function() {
            $('.apm-deposit-row').removeClass('apm-highlight');
            $('.order-total .woocommerce-Price-amount').css('color', '');
        },

        highlightDeposit: function() {
            $('.apm-deposit-row').addClass('apm-highlight');
        },

        // Utility: Format price
        formatPrice: function(amount) {
            if (typeof wc_price_params !== 'undefined') {
                return this.formatWithWooCommerce(amount);
            }
            return amount.toFixed(2) + ' €';
        },

        formatWithWooCommerce: function(amount) {
            var formatted = amount.toFixed(wc_price_params.num_decimals || 2);
            formatted = formatted.replace('.', wc_price_params.decimal_separator || ',');

            if (wc_price_params.currency_pos === 'left') {
                return wc_price_params.currency_symbol + formatted;
            } else if (wc_price_params.currency_pos === 'left_space') {
                return wc_price_params.currency_symbol + ' ' + formatted;
            } else if (wc_price_params.currency_pos === 'right_space') {
                return formatted + ' ' + wc_price_params.currency_symbol;
            }
            return formatted + wc_price_params.currency_symbol;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        APM.init();
    });

    // Also initialize after AJAX updates
    $(document.body).on('init_checkout', function() {
        APM.init();
    });

})(jQuery);
