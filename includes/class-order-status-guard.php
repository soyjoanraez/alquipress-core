<?php
/**
 * Valida estados y transiciones de pedidos en ALQUIPRESS.
 *
 * @package Alquipress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Order_Status_Guard
{
    /**
     * Estados permitidos para cambios manuales desde interfaces ALQUIPRESS.
     *
     * @return string[]
     */
    public static function get_allowed_statuses()
    {
        $statuses = [
            'pending',
            'processing',
            'on-hold',
            'completed',
            'cancelled',
            'refunded',
            'failed',
            'deposito-ok',
            'pending-checkin',
            'in-progress',
            'checkout-review',
            'deposit-refunded',
            'deposit-paid',
            'fully-paid',
            'balance-pending',
            'security-held',
            'payment-failed',
        ];

        return apply_filters('alquipress_allowed_order_statuses', $statuses);
    }

    /**
     * Mapa de transiciones permitidas.
     *
     * @return array<string, string[]>
     */
    public static function get_allowed_transitions()
    {
        $transitions = [
            'pending' => ['processing', 'on-hold', 'cancelled', 'failed', 'deposit-paid', 'deposito-ok'],
            'on-hold' => ['pending', 'processing', 'cancelled', 'failed'],
            'processing' => ['deposit-paid', 'deposito-ok', 'balance-pending', 'fully-paid', 'pending-checkin', 'cancelled', 'refunded', 'failed'],
            'deposit-paid' => ['balance-pending', 'fully-paid', 'pending-checkin', 'payment-failed', 'cancelled', 'refunded'],
            'balance-pending' => ['fully-paid', 'pending-checkin', 'payment-failed', 'cancelled', 'refunded'],
            'fully-paid' => ['pending-checkin', 'in-progress', 'security-held', 'completed', 'refunded'],
            'deposito-ok' => ['pending-checkin', 'in-progress', 'balance-pending', 'security-held', 'cancelled'],
            'pending-checkin' => ['in-progress', 'cancelled'],
            'in-progress' => ['checkout-review', 'completed', 'cancelled'],
            'checkout-review' => ['security-held', 'deposit-refunded', 'completed'],
            'security-held' => ['deposit-refunded', 'completed'],
            'deposit-refunded' => ['completed'],
            'payment-failed' => ['pending', 'on-hold', 'cancelled'],
            'failed' => ['pending', 'on-hold', 'cancelled'],
            'cancelled' => ['pending', 'on-hold'],
            'completed' => [],
            'refunded' => [],
        ];

        return apply_filters('alquipress_allowed_order_status_transitions', $transitions);
    }

    /**
     * Normaliza un estado removiendo prefijos y caracteres no esperados.
     *
     * @param string $status Estado a normalizar.
     * @return string
     */
    public static function normalize_status($status)
    {
        $status = (string) $status;
        $status = str_replace('wc-', '', $status);

        return sanitize_key($status);
    }

    /**
     * Verifica si un estado pertenece al conjunto permitido.
     *
     * @param string $status Estado.
     * @return bool
     */
    public static function is_valid_status($status)
    {
        $status = self::normalize_status($status);

        return in_array($status, self::get_allowed_statuses(), true);
    }

    /**
     * Verifica si la transición from -> to está permitida.
     *
     * @param string $from Estado origen.
     * @param string $to Estado destino.
     * @return bool
     */
    public static function can_transition($from, $to)
    {
        $from = self::normalize_status($from);
        $to = self::normalize_status($to);

        if ($from === $to) {
            return true;
        }

        if (!self::is_valid_status($from) || !self::is_valid_status($to)) {
            return false;
        }

        $transitions = self::get_allowed_transitions();
        if (!isset($transitions[$from])) {
            return false;
        }

        return in_array($to, $transitions[$from], true);
    }
}
