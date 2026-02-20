/**
 * Inbox Omnicanal - Interactividad
 * Tabs, ghost mode, plantillas y envío real vía AJAX
 */
(function ($) {
    'use strict';

    $(function () {
        var inboxConfig = window.alquipressInbox || {};
        var i18n = inboxConfig.i18n || {};
        var $inbox = $('.ap-inbox');
        if (!$inbox.length) return;

        var $tabs = $('.ap-inbox-tab');
        var $convItems = $('.ap-inbox-conv-item');
        var $emptyList = $('.ap-inbox-empty-list');
        var $threadEmpty = $('.ap-inbox-thread-empty');
        var $messages = $('.ap-inbox-messages');
        var $ghostToggle = $('.ap-inbox-ghost-toggle');
        var $inputWrap = $('.ap-inbox-input-wrap');
        var $input = $('.ap-inbox-input');
        var $send = $('.ap-inbox-send');
        var $ctxButtons = $('.ap-inbox-ctx-btn[data-template]');
        var activeTab = ($tabs.filter('.is-active').data('tab') || 'pending').toString();
        var isSending = false;

        function notify(type, message) {
            if (typeof AlquipressToast !== 'undefined' && AlquipressToast[type]) {
                AlquipressToast[type](message);
                return;
            }
            if (type === 'error') {
                window.alert(message);
            } else {
                console.log(message);
            }
        }

        function formatNowTime() {
            var now = new Date();
            return now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        }

        function getActiveConversationMeta() {
            var $item = $convItems.filter('.is-active:not(.is-hidden)').first();
            if (!$item.length) {
                return null;
            }
            return {
                $item: $item,
                id: ($item.data('conv-id') || '').toString(),
                guest: ($item.data('guest') || '').toString(),
                email: ($item.data('email') || '').toString(),
                channel: ($item.data('channel') || '').toString(),
                entityType: ($item.data('entity-type') || '').toString(),
                entityId: parseInt($item.data('entity-id') || 0, 10) || 0,
            };
        }

        function appendMessage(text, role, timeLabel) {
            var $msg = $('<div>', { class: 'ap-inbox-msg ap-inbox-msg-role-' + role });
            $('<div>', { class: 'ap-inbox-msg-content', text: text }).appendTo($msg);
            $('<div>', { class: 'ap-inbox-msg-time', text: timeLabel || formatNowTime() }).appendTo($msg);
            $messages.append($msg);
            $messages.scrollTop($messages[0].scrollHeight);
        }

        function updateConversationPreview(meta, text, timeLabel) {
            var previewText = $.trim(text || '');
            if (previewText.length > 120) {
                previewText = previewText.slice(0, 117) + '...';
            }
            meta.$item.find('.ap-inbox-conv-preview').text(previewText);
            meta.$item.find('.ap-inbox-conv-time').text(timeLabel || formatNowTime());
            meta.$item.find('.ap-inbox-conv-unread').remove();
        }

        function selectConversation($item) {
            if (!$item || !$item.length) {
                return;
            }
            $convItems.removeClass('is-active');
            $item.addClass('is-active');
            $threadEmpty.hide();
            $messages.show();
        }

        function applyTabFilter(tab) {
            activeTab = tab;
            $tabs.removeClass('is-active');
            $tabs.filter('[data-tab="' + tab + '"]').addClass('is-active');

            var visibleCount = 0;
            $convItems.each(function () {
                var $item = $(this);
                var isVisible = ($item.data('bucket') || '').toString() === tab;
                $item.toggleClass('is-hidden', !isVisible);
                if (isVisible) {
                    visibleCount++;
                }
            });

            if (visibleCount === 0) {
                $emptyList.text(i18n.tabNoItems || 'No hay conversaciones en esta vista.').show();
                $threadEmpty.show();
                $messages.hide();
                $convItems.removeClass('is-active');
                return;
            }

            $emptyList.hide();

            var $active = $convItems.filter('.is-active:not(.is-hidden)').first();
            if (!$active.length) {
                $active = $convItems.filter(':not(.is-hidden)').first();
            }
            selectConversation($active);
        }

        function sendMessage() {
            if (isSending) {
                return;
            }

            var text = $.trim($input.val());
            if (!text) {
                notify('error', i18n.emptyMessage || 'Escribe un mensaje antes de enviar.');
                $input.focus();
                return;
            }

            var conversation = getActiveConversationMeta();
            if (!conversation) {
                notify('warning', i18n.selectConversation || 'Selecciona una conversación primero.');
                return;
            }

            var role = $ghostToggle.hasClass('is-ghost') ? 'note' : 'staff';
            var payload = {
                action: 'alquipress_inbox_send_message',
                nonce: inboxConfig.nonce || '',
                conversation_id: conversation.id,
                guest_name: conversation.guest,
                to_email: conversation.email,
                channel: conversation.channel,
                entity_type: conversation.entityType,
                entity_id: conversation.entityId,
                message: text,
                message_type: role === 'note' ? 'note' : 'message'
            };

            isSending = true;
            $send.prop('disabled', true).addClass('is-loading');
            notify('info', i18n.sending || 'Enviando mensaje...');

            $.ajax({
                url: inboxConfig.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php',
                method: 'POST',
                data: payload
            }).done(function (response) {
                if (!response || !response.success) {
                    var failMessage = response && response.data && response.data.message ? response.data.message : (i18n.sendError || 'No se pudo enviar el mensaje.');
                    notify('error', failMessage);
                    return;
                }

                var responseData = response.data || {};
                var messageTime = responseData.time || formatNowTime();
                appendMessage(text, role, messageTime);
                updateConversationPreview(conversation, responseData.preview || text, messageTime);
                $input.val('').focus();
                notify('success', responseData.message || (i18n.sent || 'Mensaje añadido en el hilo.'));
            }).fail(function () {
                notify('error', i18n.sendError || 'No se pudo enviar el mensaje.');
            }).always(function () {
                isSending = false;
                $send.prop('disabled', false).removeClass('is-loading');
            });
        }

        // Clic en conversación: marcar activa y mostrar hilo
        $convItems.on('click', function () {
            var $item = $(this);
            if ($item.hasClass('is-hidden')) {
                return;
            }
            selectConversation($item);
        });

        // Tabs columna izquierda
        $tabs.on('click', function () {
            applyTabFilter(($(this).data('tab') || '').toString());
        });

        // Toggle Modo Colaboración (Ghost Write): azul = cliente, amarillo = nota interna
        $ghostToggle.on('click', function () {
            var $btn = $(this);
            $btn.toggleClass('is-ghost');
            $inputWrap.toggleClass('is-ghost');
            if ($btn.hasClass('is-ghost')) {
                $input.attr('placeholder', i18n.placeholderNote || 'Nota interna (solo staff)...');
            } else {
                $input.attr('placeholder', i18n.placeholderMessage || 'Escribe un mensaje...');
            }
        });

        // Envío de mensajes
        $send.on('click', sendMessage);
        $input.on('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Atajos desde panel de contexto
        $ctxButtons.on('click', function () {
            var template = ($(this).data('template') || '').toString();
            if (template === 'extend') {
                $input.val(i18n.templateExtend || 'Hola, si quieres ampliar tu estancia puedo revisar disponibilidad y precio actualizado.');
            }
            if (template === 'payment') {
                $input.val(i18n.templatePayment || 'Te envío el recordatorio de pago pendiente de la reserva. En cuanto esté abonado te confirmo por aquí.');
            }
            $input.focus();
        });

        // Placeholder inicial y estado inicial
        if (!$input.attr('placeholder')) {
            $input.attr('placeholder', i18n.placeholderMessage || 'Escribe un mensaje...');
        }
        applyTabFilter(activeTab);
    });
})(jQuery);
