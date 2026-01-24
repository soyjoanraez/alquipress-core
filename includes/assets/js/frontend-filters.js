/**
 * JavaScript para Filtros de Taxonomía Frontend
 * ALQUIPRESS Core
 */

jQuery(document).ready(function($) {
    'use strict';

    $('.alquipress-filter-group input').on('change', function() {
        var url = new URL(window.location);
        var tax = $(this).closest('.alquipress-filter-group').data('taxonomy');
        var values = [];

        $(this).closest('.alquipress-filter-group').find('input:checked').each(function() {
            values.push($(this).val());
        });

        url.searchParams.delete(tax);
        if (values.length) {
            url.searchParams.append(tax, values.join(','));
        }

        window.location = url;
    });
});
