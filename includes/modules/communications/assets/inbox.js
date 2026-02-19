/**
 * Inbox Omnicanal - Interactividad básica
 * Toggle Ghost (Modo Colaboración), selección de conversación, sin envío real
 */
(function ($) {
    'use strict';

    $(function () {
        var $inbox = $('.ap-inbox');
        if (!$inbox.length) return;

        var $convItems = $('.ap-inbox-conv-item');
        var $threadEmpty = $('.ap-inbox-thread-empty');
        var $messages = $('.ap-inbox-messages');
        var $ghostToggle = $('.ap-inbox-ghost-toggle');
        var $inputWrap = $('.ap-inbox-input-wrap');
        var $input = $('.ap-inbox-input');

        // Clic en conversación: marcar activa, mostrar hilo mock
        $convItems.on('click', function () {
            var $item = $(this);
            $convItems.removeClass('is-active');
            $item.addClass('is-active');
            $threadEmpty.hide();
            $messages.show();
        });

        // Toggle Modo Colaboración (Ghost Write): azul = cliente, amarillo = nota interna
        $ghostToggle.on('click', function () {
            var $btn = $(this);
            $btn.toggleClass('is-ghost');
            $inputWrap.toggleClass('is-ghost');
            if ($btn.hasClass('is-ghost')) {
                $input.attr('placeholder', 'Nota interna (solo staff)...');
            } else {
                $input.attr('placeholder', 'Escribe un mensaje...');
            }
        });

        // Placeholder inicial del input
        if (!$input.attr('placeholder')) {
            $input.attr('placeholder', 'Escribe un mensaje...');
        }
    });
})(jQuery);
