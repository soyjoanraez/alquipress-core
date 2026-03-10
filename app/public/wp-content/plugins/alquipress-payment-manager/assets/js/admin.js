/**
 * ALQUIPRESS Payment Manager - Admin JS
 */

(function($) {
    'use strict';

    var APM_Admin = {
        init: function() {
            this.bindEvents();
            this.initProductFields();
        },

        bindEvents: function() {
            // Toggle custom fields in product edit
            $(document).on('change', '#_apm_override_global', this.toggleCustomFields);

            // Process payment now button
            $(document).on('click', '.apm-process-now', this.processPaymentNow);

            // Mark as paid in cash
            $(document).on('click', '.apm-mark-cash', this.markPaidCash);
        },

        initProductFields: function() {
            // Initialize the toggle state on page load
            var $override = $('#_apm_override_global');
            if ($override.length) {
                this.toggleCustomFields.call($override[0]);
            }
        },

        toggleCustomFields: function() {
            var $this = $(this);
            var $customFields = $('.apm-custom-fields');

            if ($this.is(':checked')) {
                $customFields.slideDown(200);
            } else {
                $customFields.slideUp(200);
            }
        },

        processPaymentNow: function(e) {
            e.preventDefault();

            var $button = $(this);
            var paymentId = $button.data('payment-id');

            if (!paymentId) {
                alert('Error: Payment ID not found');
                return;
            }

            if (!confirm(apm_admin_vars.i18n.confirm_process)) {
                return;
            }

            $button.prop('disabled', true).text(apm_admin_vars.i18n.processing);

            $.ajax({
                url: apm_admin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'apm_process_payment_now',
                    payment_id: paymentId,
                    nonce: apm_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload();
                    } else {
                        alert(apm_admin_vars.i18n.error + ': ' + response.data);
                        $button.prop('disabled', false).text(apm_admin_vars.i18n.process_now);
                    }
                },
                error: function() {
                    alert(apm_admin_vars.i18n.error);
                    $button.prop('disabled', false).text(apm_admin_vars.i18n.process_now);
                }
            });
        },

        markPaidCash: function(e) {
            e.preventDefault();

            var $button = $(this);
            var paymentId = $button.data('payment-id');

            if (!paymentId) {
                alert('Error: Payment ID not found');
                return;
            }

            if (!confirm(apm_admin_vars.i18n.confirm_cash)) {
                return;
            }

            $button.prop('disabled', true).text(apm_admin_vars.i18n.saving);

            $.ajax({
                url: apm_admin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'apm_mark_paid_cash',
                    payment_id: paymentId,
                    nonce: apm_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload();
                    } else {
                        alert(apm_admin_vars.i18n.error + ': ' + response.data);
                        $button.prop('disabled', false).text(apm_admin_vars.i18n.mark_cash);
                    }
                },
                error: function() {
                    alert(apm_admin_vars.i18n.error);
                    $button.prop('disabled', false).text(apm_admin_vars.i18n.mark_cash);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        APM_Admin.init();
    });

})(jQuery);
