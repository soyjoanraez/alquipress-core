<?php
/**
 * Manejador de depósitos y pagos
 *
 * @package ALQUIPRESS\PaymentManager\Payment
 */

namespace ALQUIPRESS\PaymentManager\Payment;

defined('ABSPATH') || exit;

/**
 * Class DepositHandler
 * Gestiona la lógica de depósitos y pagos escalonados
 */
class DepositHandler {

    /**
     * Constructor
     */
    public function __construct() {
        // Procesar después de crear el pedido
        add_action('woocommerce_checkout_order_processed', [$this, 'process_staged_payment'], 20, 3);

        // Programar el segundo pago después del pago exitoso
        add_action('woocommerce_payment_complete', [$this, 'schedule_balance_payment']);

        // Mostrar info de pagos en el admin del pedido
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_payment_info_admin']);
    }

    /**
     * Procesar pago escalonado
     *
     * @param int       $order_id    ID del pedido
     * @param array     $posted_data Datos enviados
     * @param \WC_Order $order       Objeto pedido
     */
    public function process_staged_payment($order_id, $posted_data, $order) {
        $is_staged = $order->get_meta('_apm_is_staged_payment');

        if ($is_staged !== 'yes') {
            return;
        }

        // Añadir nota al pedido
        $deposit = $order->get_meta('_apm_deposit_amount');
        $balance = $order->get_meta('_apm_balance_amount');
        $security = $order->get_meta('_apm_security_amount');
        $days_before = $order->get_meta('_apm_days_before');

        $note = sprintf(
            __('Pago escalonado configurado: Depósito %s (pagado ahora), Saldo %s (programado %d días antes del check-in), Fianza %s (retención)', 'apm'),
            wc_price($deposit),
            wc_price($balance),
            $days_before,
            wc_price($security)
        );

        $order->add_order_note($note);
    }

    /**
     * Programar el pago del saldo
     *
     * @param int $order_id ID del pedido
     */
    public function schedule_balance_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $is_staged = $order->get_meta('_apm_is_staged_payment');
        if ($is_staged !== 'yes') {
            return;
        }

        // Verificar que no se haya programado ya
        if ($order->get_meta('_apm_balance_scheduled')) {
            return;
        }

        $balance = (float) $order->get_meta('_apm_balance_amount');
        $check_in_date = $order->get_meta('_apm_check_in_date');
        $days_before = (int) $order->get_meta('_apm_days_before');

        if (!$check_in_date || $balance <= 0) {
            return;
        }

        // Calcular fecha del segundo pago
        $payment_date = date('Y-m-d H:i:s', strtotime($check_in_date . " -{$days_before} days"));

        // Resolver booking_id de Ap_Booking si está disponible
        $booking_id = null;
        foreach ($order->get_items() as $item) {
            if (method_exists($item, 'get_meta')) {
                $bid = (int) $item->get_meta('ap_booking_id');
                if ($bid > 0) {
                    $booking_id = $bid;
                    break;
                }
            }
        }

        // Insertar en la tabla de pagos programados
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';

        $row_data   = [
            'order_id'       => $order_id,
            'payment_type'   => 'balance',
            'amount'         => $balance,
            'currency'       => $order->get_currency(),
            'scheduled_date' => $payment_date,
            'status'         => 'pending',
            'created_at'     => current_time('mysql'),
            'updated_at'     => current_time('mysql'),
        ];
        $row_format = ['%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s'];

        if ($booking_id) {
            $row_data['booking_id'] = $booking_id;
            $row_format[]           = '%d';
        }

        $result = $wpdb->insert($table, $row_data, $row_format);

        if ($result) {
            $order->update_meta_data('_apm_balance_scheduled', 'yes');
            $order->update_meta_data('_apm_balance_payment_id', $wpdb->insert_id);
            $order->update_meta_data('_apm_balance_scheduled_date', $payment_date);
            $order->save();

            // Log
            $this->log_action($order_id, 'balance_scheduled', 'pending', $balance, sprintf(
                __('Pago de saldo programado para %s', 'apm'),
                date_i18n(get_option('date_format'), strtotime($payment_date))
            ));
        }
    }

    /**
     * Mostrar información de pagos en el admin
     *
     * @param \WC_Order $order Pedido
     */
    public function display_payment_info_admin($order) {
        $is_staged = $order->get_meta('_apm_is_staged_payment');

        if ($is_staged !== 'yes') {
            return;
        }

        $deposit = $order->get_meta('_apm_deposit_amount');
        $balance = $order->get_meta('_apm_balance_amount');
        $security = $order->get_meta('_apm_security_amount');
        $balance_date = $order->get_meta('_apm_balance_scheduled_date');
        ?>
        <div class="apm-payment-info" style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">
            <h4 style="margin: 0 0 10px;"><?php _e('Información de Pagos ALQUIPRESS', 'apm'); ?></h4>
            <table style="width: 100%;">
                <tr>
                    <td><strong><?php _e('Depósito (pagado):', 'apm'); ?></strong></td>
                    <td><?php echo wc_price($deposit); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Saldo pendiente:', 'apm'); ?></strong></td>
                    <td>
                        <?php echo wc_price($balance); ?>
                        <?php if ($balance_date) : ?>
                            <br><small><?php printf(__('Programado: %s', 'apm'), date_i18n(get_option('date_format'), strtotime($balance_date))); ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Fianza:', 'apm'); ?></strong></td>
                    <td><?php echo wc_price($security); ?></td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Registrar acción en log
     *
     * @param int    $order_id ID del pedido
     * @param string $action   Acción realizada
     * @param string $status   Estado
     * @param float  $amount   Importe
     * @param string $message  Mensaje descriptivo
     */
    private function log_action($order_id, $action, $status, $amount = null, $message = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_logs';

        $wpdb->insert(
            $table,
            [
                'order_id'   => $order_id,
                'action'     => $action,
                'status'     => $status,
                'amount'     => $amount,
                'message'    => $message,
                'user_id'    => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%f', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Obtener IP del cliente
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
