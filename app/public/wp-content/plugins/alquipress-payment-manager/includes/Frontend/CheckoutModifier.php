<?php
/**
 * Modificador del checkout
 *
 * @package ALQUIPRESS\PaymentManager\Frontend
 */

namespace ALQUIPRESS\PaymentManager\Frontend;

defined('ABSPATH') || exit;

/**
 * Class CheckoutModifier
 * Modifica el checkout para mostrar el desglose de pagos
 */
class CheckoutModifier {

    /**
     * Constructor
     */
    public function __construct() {
        // Mostrar desglose antes de métodos de pago
        add_action('woocommerce_review_order_before_payment', [$this, 'display_payment_breakdown']);

        // Modificar el total a pagar en el checkout
        add_filter('woocommerce_calculated_total', [$this, 'modify_checkout_total'], 10, 2);

        // Guardar datos del pago escalonado en el pedido
        add_action('woocommerce_checkout_create_order', [$this, 'save_payment_data_to_order'], 10, 2);

        // Validación del checkout
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout']);
    }

    /**
     * Mostrar desglose de pagos en el checkout
     */
    public function display_payment_breakdown() {
        // Verificar si está habilitado
        if (get_option('apm_show_breakdown', 'yes') !== 'yes') {
            return;
        }

        $breakdown = $this->calculate_cart_breakdown();
        if (!$breakdown || !$breakdown['is_booking']) {
            return;
        }

        // No mostrar desglose si la reserva es muy próxima
        if (!$breakdown['allow_deposit']) {
            return;
        }

        $allow_full = get_option('apm_allow_full_payment', 'yes') === 'yes';
        ?>
        <div class="apm-payment-breakdown" id="apm-payment-breakdown">
            <h3><?php _e('Desglose de Pagos', 'apm'); ?></h3>

            <table class="apm-breakdown-table">
                <tbody>
                    <tr class="apm-deposit-row">
                        <td class="apm-label">
                            <strong><?php _e('Pago hoy (Depósito)', 'apm'); ?></strong>
                            <span class="apm-percent"><?php echo esc_html($breakdown['deposit_percent']); ?>%</span>
                        </td>
                        <td class="apm-amount">
                            <strong><?php echo wc_price($breakdown['deposit']); ?></strong>
                        </td>
                    </tr>

                    <tr class="apm-balance-row">
                        <td class="apm-label">
                            <?php printf(
                                __('2º Pago (%d días antes del check-in)', 'apm'),
                                $breakdown['days_before']
                            ); ?>
                            <small class="apm-note"><?php _e('Se cobrará automáticamente en tu tarjeta', 'apm'); ?></small>
                        </td>
                        <td class="apm-amount">
                            <?php echo wc_price($breakdown['balance']); ?>
                        </td>
                    </tr>

                    <?php if ($breakdown['security'] > 0) : ?>
                    <tr class="apm-security-row">
                        <td class="apm-label">
                            <?php _e('Fianza (Retención)', 'apm'); ?>
                            <small class="apm-note"><?php _e('Se libera tras la salida si no hay incidencias', 'apm'); ?></small>
                        </td>
                        <td class="apm-amount">
                            <?php echo wc_price($breakdown['security']); ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <tr class="apm-total-row">
                        <td class="apm-label">
                            <strong><?php _e('Total Reserva', 'apm'); ?></strong>
                        </td>
                        <td class="apm-amount">
                            <strong><?php echo wc_price($breakdown['total']); ?></strong>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if ($allow_full) : ?>
            <div class="apm-full-payment-option">
                <label>
                    <input type="checkbox" name="apm_pay_full" id="apm_pay_full" value="1">
                    <?php _e('Prefiero pagar el importe total ahora', 'apm'); ?>
                    <span class="apm-full-total">(<?php echo wc_price($breakdown['booking_total']); ?>)</span>
                </label>
            </div>
            <?php endif; ?>

            <input type="hidden" name="apm_deposit_amount" value="<?php echo esc_attr($breakdown['deposit']); ?>">
            <input type="hidden" name="apm_balance_amount" value="<?php echo esc_attr($breakdown['balance']); ?>">
            <input type="hidden" name="apm_security_amount" value="<?php echo esc_attr($breakdown['security']); ?>">
        </div>

        <style>
            .apm-payment-breakdown {
                background: #f8f9fa;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
                border: 1px solid #e1e5eb;
            }
            .apm-payment-breakdown h3 {
                margin: 0 0 15px;
                font-size: 1.1em;
            }
            .apm-breakdown-table {
                width: 100%;
                border-collapse: collapse;
            }
            .apm-breakdown-table td {
                padding: 10px 0;
                border-bottom: 1px solid #e1e5eb;
            }
            .apm-breakdown-table tr:last-child td {
                border-bottom: none;
            }
            .apm-label {
                text-align: left;
            }
            .apm-amount {
                text-align: right;
                white-space: nowrap;
            }
            .apm-percent {
                background: #e7f3ff;
                color: #0066cc;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 0.85em;
                margin-left: 8px;
            }
            .apm-note {
                display: block;
                color: #666;
                font-size: 0.85em;
                margin-top: 3px;
            }
            .apm-total-row {
                border-top: 2px solid #333 !important;
            }
            .apm-total-row td {
                padding-top: 15px !important;
            }
            .apm-security-row td {
                color: #666;
            }
            .apm-full-payment-option {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px dashed #ccc;
            }
            .apm-full-payment-option label {
                cursor: pointer;
            }
            .apm-full-total {
                color: #666;
            }
        </style>
        <?php
    }

    /**
     * Calcular desglose del carrito
     *
     * @return array|null
     */
    public function calculate_cart_breakdown() {
        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            return null;
        }

        // Buscar producto bookable en el carrito
        $booking_product = null;
        $booking_item = null;

        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if ($product && $this->is_bookable_product($product)) {
                $booking_product = $product;
                $booking_item = $cart_item;
                break;
            }
        }

        if (!$booking_product) {
            return ['is_booking' => false];
        }

        // Obtener configuración del producto
        $product_id = $booking_product->get_id();
        $config = \ALQUIPRESS\PaymentManager\Core::get_product_config($product_id);

        // Verificar si los depósitos están desactivados para este producto
        if (get_post_meta($product_id, '_apm_disable_deposits', true) === 'yes') {
            return ['is_booking' => false];
        }

        // Obtener fecha de check-in del booking
        $check_in_date = $this->get_booking_start_date($booking_item);
        $days_until_checkin = $check_in_date ? $this->days_until($check_in_date) : 999;

        // Verificar antelación mínima
        $min_days = (int) get_option('apm_min_days_for_deposit', 14);
        $allow_deposit = $days_until_checkin >= $min_days;

        // Calcular importes
        $cart_total = (float) $cart->get_total('edit');
        $security = (float) $config['security_deposit'];
        $booking_total = $cart_total; // Total sin fianza (la fianza es retención, no cobro)

        $deposit_percent = (float) $config['deposit_percent'];
        $deposit = round(($booking_total * $deposit_percent) / 100, 2);
        $balance = $booking_total - $deposit;

        return [
            'is_booking'      => true,
            'allow_deposit'   => $allow_deposit,
            'product_id'      => $product_id,
            'booking_total'   => $booking_total,
            'deposit'         => $deposit,
            'deposit_percent' => $deposit_percent,
            'balance'         => $balance,
            'security'        => $security,
            'total'           => $booking_total + $security, // Total informativo
            'days_before'     => (int) $config['days_before'],
            'check_in_date'   => $check_in_date,
            'days_until'      => $days_until_checkin,
        ];
    }

    /**
     * Modificar el total del checkout
     *
     * @param float    $total Total calculado
     * @param \WC_Cart $cart  Carrito
     * @return float
     */
    public function modify_checkout_total($total, $cart) {
        // Solo modificar en el checkout
        if (!is_checkout() || is_admin()) {
            return $total;
        }

        // Si el usuario eligió pagar todo, no modificar
        if (isset($_POST['apm_pay_full']) && $_POST['apm_pay_full'] == '1') {
            return $total;
        }

        $breakdown = $this->calculate_cart_breakdown();
        if (!$breakdown || !$breakdown['is_booking'] || !$breakdown['allow_deposit']) {
            return $total;
        }

        // Retornar solo el depósito
        return $breakdown['deposit'];
    }

    /**
     * Guardar datos de pago en el pedido
     *
     * @param \WC_Order $order Pedido
     * @param array     $data  Datos del checkout
     */
    public function save_payment_data_to_order($order, $data) {
        $breakdown = $this->calculate_cart_breakdown();
        if (!$breakdown || !$breakdown['is_booking']) {
            return;
        }

        $pay_full = isset($_POST['apm_pay_full']) && $_POST['apm_pay_full'] == '1';

        $order->update_meta_data('_apm_is_staged_payment', $pay_full ? 'no' : 'yes');
        $order->update_meta_data('_apm_pay_full', $pay_full ? 'yes' : 'no');
        $order->update_meta_data('_apm_deposit_amount', $breakdown['deposit']);
        $order->update_meta_data('_apm_balance_amount', $breakdown['balance']);
        $order->update_meta_data('_apm_security_amount', $breakdown['security']);
        $order->update_meta_data('_apm_booking_total', $breakdown['booking_total']);
        $order->update_meta_data('_apm_check_in_date', $breakdown['check_in_date']);
        $order->update_meta_data('_apm_days_before', $breakdown['days_before']);
    }

    /**
     * Validar checkout
     */
    public function validate_checkout() {
        // Aquí puedes añadir validaciones adicionales
    }

    /**
     * Verificar si un producto es bookable (WC Bookings o motor propio Ap_Booking).
     *
     * @param \WC_Product $product Producto
     * @return bool
     */
    private function is_bookable_product($product) {
        if (!$product) {
            return false;
        }

        return (bool) get_post_meta($product->get_id(), 'ap_booking_enabled', true);
    }

    /**
     * Obtener fecha de inicio del booking (Ap_Booking o WC Bookings).
     *
     * @param array $cart_item Item del carrito
     * @return string|null Fecha en formato Y-m-d
     */
    private function get_booking_start_date($cart_item) {
        if (!empty($cart_item['ap_checkin'])) {
            $ts = strtotime($cart_item['ap_checkin']);
            if ($ts) {
                return gmdate('Y-m-d', $ts);
            }
        }
        return null;
    }

    /**
     * Calcular días hasta una fecha
     *
     * @param string $date Fecha en formato Y-m-d
     * @return int
     */
    private function days_until($date) {
        $target = new \DateTime($date);
        $now = new \DateTime('today');
        $diff = $now->diff($target);
        return $diff->invert ? 0 : $diff->days;
    }
}
