/**
 * Sistema de Notificaciones Toast para Alquipress
 * Reemplaza alert() con notificaciones visuales modernas
 * 
 * @package Alquipress
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Objeto principal para gestionar notificaciones toast
     */
    var AlquipressToast = {
        container: null,
        maxToasts: 5,
        defaultDuration: 4000,
        toasts: [],

        /**
         * Inicializar el contenedor de toasts
         */
        init: function() {
            if (this.container) {
                return;
            }

            // Crear contenedor si no existe
            if (!$('#alquipress-toast-container').length) {
                $('body').append('<div id="alquipress-toast-container" class="alquipress-toast-container"></div>');
            }

            this.container = $('#alquipress-toast-container');
        },

        /**
         * Mostrar una notificación toast
         * 
         * @param {string} message Mensaje a mostrar
         * @param {string} type Tipo de toast: 'success', 'error', 'warning', 'info'
         * @param {number} duration Duración en milisegundos (opcional)
         */
        show: function(message, type, duration) {
            // Inicializar si es necesario
            this.init();

            // Validar tipo
            var validTypes = ['success', 'error', 'warning', 'info'];
            if (validTypes.indexOf(type) === -1) {
                type = 'info';
            }

            // Duración por defecto
            duration = duration || this.defaultDuration;

            // Limpiar toasts antiguos si hay demasiados
            if (this.toasts.length >= this.maxToasts) {
                var oldestToast = this.toasts.shift();
                this.removeToast(oldestToast);
            }

            // Crear ID único para el toast
            var toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            
            // Iconos según el tipo
            var icons = {
                success: '<span class="dashicons dashicons-yes-alt"></span>',
                error: '<span class="dashicons dashicons-dismiss"></span>',
                warning: '<span class="dashicons dashicons-warning"></span>',
                info: '<span class="dashicons dashicons-info"></span>'
            };

            // Crear elemento del toast
            var $toast = $('<div>', {
                id: toastId,
                class: 'alquipress-toast alquipress-toast-' + type,
                html: '<div class="alquipress-toast-icon">' + icons[type] + '</div>' +
                      '<div class="alquipress-toast-message">' + this.escapeHtml(message) + '</div>' +
                      '<button class="alquipress-toast-close" aria-label="Cerrar">&times;</button>'
            });

            // Agregar al contenedor
            this.container.append($toast);
            this.toasts.push(toastId);

            // Trigger animación de entrada
            setTimeout(function() {
                $toast.addClass('alquipress-toast-show');
            }, 10);

            // Auto-dismiss
            var dismissTimeout;
            var self = this;

            var scheduleDismiss = function() {
                dismissTimeout = setTimeout(function() {
                    self.removeToast(toastId);
                }, duration);
            };

            // Pausar auto-dismiss al hacer hover
            $toast.on('mouseenter', function() {
                if (dismissTimeout) {
                    clearTimeout(dismissTimeout);
                }
            });

            $toast.on('mouseleave', function() {
                scheduleDismiss();
            });

            // Cerrar manualmente
            $toast.find('.alquipress-toast-close').on('click', function() {
                self.removeToast(toastId);
            });

            // Programar auto-dismiss inicial
            scheduleDismiss();
        },

        /**
         * Remover un toast
         * 
         * @param {string} toastId ID del toast a remover
         */
        removeToast: function(toastId) {
            var $toast = $('#' + toastId);
            if (!$toast.length) {
                return;
            }

            // Remover de la lista
            var index = this.toasts.indexOf(toastId);
            if (index > -1) {
                this.toasts.splice(index, 1);
            }

            // Animación de salida
            $toast.removeClass('alquipress-toast-show');
            
            setTimeout(function() {
                $toast.remove();
            }, 300);
        },

        /**
         * Escapar HTML para prevenir XSS
         * 
         * @param {string} text Texto a escapar
         * @return {string} Texto escapado
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Mostrar notificación de éxito
         * 
         * @param {string} message Mensaje
         * @param {number} duration Duración opcional
         */
        success: function(message, duration) {
            this.show(message, 'success', duration);
        },

        /**
         * Mostrar notificación de error
         * 
         * @param {string} message Mensaje
         * @param {number} duration Duración opcional
         */
        error: function(message, duration) {
            this.show(message, 'error', duration);
        },

        /**
         * Mostrar notificación de advertencia
         * 
         * @param {string} message Mensaje
         * @param {number} duration Duración opcional
         */
        warning: function(message, duration) {
            this.show(message, 'warning', duration);
        },

        /**
         * Mostrar notificación informativa
         * 
         * @param {string} message Mensaje
         * @param {number} duration Duración opcional
         */
        info: function(message, duration) {
            this.show(message, 'info', duration);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        AlquipressToast.init();
    });

    // Exponer globalmente
    window.AlquipressToast = AlquipressToast;

})(jQuery);
