<?php
/**
 * Phase 7: Payment Gateways & Deposits Module
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Payment_Gates
{

    public function __construct()
    {
        // Forzar guardado de tarjeta para depósitos (Stripe)
        add_filter('wc_deposits_force_save_card', '__return_true');

        // Añadir aviso de cobro automático en el checkout
        add_filter('woocommerce_get_order_item_totals', [$this, 'add_auto_pay_notice'], 10, 3);
    }

    /**
     * Muestra un aviso en el resumen del pedido si hay un depósito pendiente
     */
    public function add_auto_pay_notice($total_rows, $order, $tax_display)
    {
        $has_deposit = false;

        foreach ($order->get_items() as $item) {
            if (function_exists('wc_deposits_get_order_item_deposit_status')) {
                if (wc_deposits_get_order_item_deposit_status($item)) {
                    $has_deposit = true;
                    break;
                }
            }
        }

        if ($has_deposit) {
            $total_rows['remanente_info'] = [
                'label' => '⚠️ Importante:',
                'value' => '<span style="color: #d63638; font-weight: bold;">El saldo restante se cobrará automáticamente 7 días antes del check-in.</span>',
            ];
        }

        return $total_rows;
    }
}

new Alquipress_Payment_Gates();
