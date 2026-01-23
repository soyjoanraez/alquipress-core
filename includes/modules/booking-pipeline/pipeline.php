<?php
/**
 * Módulo: Pipeline de Reservas
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Booking_Pipeline
{

    public function __construct()
    {
        add_action('init', [$this, 'register_order_statuses']);
        add_filter('wc_order_statuses', [$this, 'add_order_statuses']);
    }

    public function register_order_statuses()
    {
        register_post_status('wc-deposito-ok', [
            'label' => 'Pago Depósito Recibido',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Pago Depósito Recibido <span class="count">(%s)</span>',
                'Pagos Depósito Recibidos <span class="count">(%s)</span>'
            ),
        ]);

        register_post_status('wc-pending-checkin', [
            'label' => 'Pendiente Check-in',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Pendiente Check-in <span class="count">(%s)</span>',
                'Pendientes Check-in <span class="count">(%s)</span>'
            ),
        ]);

        register_post_status('wc-in-progress', [
            'label' => 'Estancia en Curso',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Estancia en Curso <span class="count">(%s)</span>',
                'Estancias en Curso <span class="count">(%s)</span>'
            ),
        ]);

        register_post_status('wc-checkout-review', [
            'label' => 'Revisión Salida',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Revisión Salida <span class="count">(%s)</span>',
                'Revisiones Salida <span class="count">(%s)</span>'
            ),
        ]);

        register_post_status('wc-deposit-refunded', [
            'label' => 'Fianza Devuelta',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Fianza Devuelta <span class="count">(%s)</span>',
                'Fianzas Devueltas <span class="count">(%s)</span>'
            ),
        ]);
    }

    public function add_order_statuses($order_statuses)
    {
        $new_statuses = [];

        foreach ($order_statuses as $key => $status) {
            $new_statuses[$key] = $status;

            if ('wc-processing' === $key) {
                $new_statuses['wc-deposito-ok'] = 'Pago Depósito Recibido';
                $new_statuses['wc-pending-checkin'] = 'Pendiente Check-in';
                $new_statuses['wc-in-progress'] = 'Estancia en Curso';
                $new_statuses['wc-checkout-review'] = 'Revisión Salida';
                $new_statuses['wc-deposit-refunded'] = 'Fianza Devuelta';
            }
        }

        return $new_statuses;
    }
}

new Alquipress_Booking_Pipeline();
