/**
 * Atajos de teclado en páginas Alquipress CRM
 * N: Pipeline (nueva reserva)
 * B: Reservas
 * P: Propiedades
 */
(function() {
    'use strict';

    var shortcuts = {
        'n': { url: 'admin.php?page=alquipress-pipeline', label: 'Pipeline' },
        'b': { url: 'admin.php?page=alquipress-bookings', label: 'Reservas' },
        'p': { url: 'admin.php?page=alquipress-properties', label: 'Propiedades' }
    };

    function getBaseUrl() {
        var href = window.location.href;
        var base = href.replace(/\?.*$/, '').replace(/\/[^\/]*$/, '/');
        return base || (window.location.origin || '') + '/wp-admin/';
    }

    function isTyping(el) {
        if (!el) return false;
        var tag = (el.tagName || '').toLowerCase();
        var role = (el.getAttribute && el.getAttribute('role')) || '';
        if (tag === 'input' || tag === 'textarea') return true;
        if (el.isContentEditable || role === 'textbox') return true;
        return false;
    }

    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        var key = (e.key || '').toLowerCase();
        var shortcut = shortcuts[key];
        if (!shortcut) return;
        if (isTyping(document.activeElement)) return;
        e.preventDefault();
        window.location.href = getBaseUrl() + shortcut.url;
    });
})();
