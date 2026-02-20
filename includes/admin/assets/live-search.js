/**
 * Búsqueda live en sidebar: dropdown de resultados en tiempo real
 */
(function ($) {
    'use strict';

    var config = window.alquipressLiveSearch || {};
    var ajaxurl = config.ajaxurl || '';
    var nonce = config.nonce || '';
    var i18n = config.i18n || {};

    function debounce(fn, delay) {
        var t;
        return function () {
            var args = arguments;
            clearTimeout(t);
            t = setTimeout(function () {
                fn.apply(null, args);
            }, delay);
        };
    }

    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return '';
        }
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function (m) {
            return map[m];
        });
    }

    function renderDropdown(data, query) {
        var html = [];
        var hasAny = false;

        if (data.properties && data.properties.length) {
            hasAny = true;
            html.push('<div class="ap-live-search-group">');
            html.push('<div class="ap-live-search-group-title">' + (i18n.properties || 'Propiedades') + '</div>');
            data.properties.forEach(function (item) {
                var url = escapeHtml(item.url || '#');
                var title = escapeHtml(item.title || '');
                html.push('<a href="' + url + '" class="ap-live-search-item">' + title + '</a>');
            });
            html.push('</div>');
        }
        if (data.bookings && data.bookings.length) {
            hasAny = true;
            html.push('<div class="ap-live-search-group">');
            html.push('<div class="ap-live-search-group-title">' + (i18n.bookings || 'Reservas') + '</div>');
            data.bookings.forEach(function (item) {
                var url = escapeHtml(item.url || '#');
                var title = escapeHtml(item.title || '');
                html.push('<a href="' + url + '" class="ap-live-search-item">' + title + '</a>');
            });
            html.push('</div>');
        }
        if (data.clients && data.clients.length) {
            hasAny = true;
            html.push('<div class="ap-live-search-group">');
            html.push('<div class="ap-live-search-group-title">' + (i18n.clients || 'Clientes') + '</div>');
            data.clients.forEach(function (item) {
                var url = escapeHtml(item.url || '#');
                var title = escapeHtml(item.title || '');
                html.push('<a href="' + url + '" class="ap-live-search-item">' + title + '</a>');
            });
            html.push('</div>');
        }

        if (!hasAny) {
            html = ['<div class="ap-live-search-empty">' + (i18n.noResults || 'Sin resultados') + '</div>'];
        }

        var searchAllHref = (config.searchAllUrl || '') + (query ? '&s=' + encodeURIComponent(query) : '');
        html.push('<a href="' + searchAllHref + '" class="ap-live-search-footer">' + (i18n.searchAll || 'Buscar en todo') + '</a>');
        return html.join('');
    }

    function search(query, $dropdown) {
        if (!query || query.length < 2) {
            $dropdown.hide().empty();
            return;
        }

        $.get(ajaxurl, {
            action: 'alquipress_live_search',
            nonce: nonce,
            s: query,
        })
            .done(function (resp) {
                if (resp.success && resp.data) {
                    $dropdown.html(renderDropdown(resp.data, query)).show();
                } else {
                    $dropdown.html('<div class="ap-live-search-empty">' + (i18n.noResults || 'Sin resultados') + '</div>').show();
                }
            })
            .fail(function () {
                $dropdown.html('<div class="ap-live-search-empty">' + (i18n.noResults || 'Sin resultados') + '</div>').show();
            });
    }

    function init() {
        var $form = $('.ap-sidebar-search-form');
        var $input = $('.ap-sidebar-search-input');
        var $wrap = $form.closest('.ap-sidebar-search-wrap');
        if (!$wrap.length) {
            $wrap = $form.wrap('<div class="ap-sidebar-search-wrap"></div>').parent();
        }

        var $dropdown = $wrap.find('.ap-live-search-dropdown');
        if (!$dropdown.length) {
            $dropdown = $('<div class="ap-live-search-dropdown" role="listbox" aria-hidden="true"></div>');
            $wrap.append($dropdown);
        }

        if (!config.searchAllUrl) {
            config.searchAllUrl = (window.location.origin || '') + '/wp-admin/admin.php?page=alquipress-search';
        }

        var debouncedSearch = debounce(function (q) {
            search(q, $dropdown);
        }, 280);

        $input
            .on('input', function () {
                var q = $.trim($(this).val());
                debouncedSearch(q);
            })
            .on('focus', function () {
                var q = $.trim($(this).val());
                if (q.length >= 2) {
                    search(q, $dropdown);
                }
            });

        $(document).on('click', function (e) {
            if ($wrap.length && !$wrap[0].contains(e.target)) {
                $dropdown.hide();
            }
        });

        $input.on('keydown', function (e) {
            if (e.key === 'Escape') {
                $dropdown.hide();
            }
        });
    }

    $(function () {
        init();
    });
})(jQuery);
