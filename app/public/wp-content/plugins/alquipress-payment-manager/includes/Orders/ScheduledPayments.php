<?php
/**
 * Procesador de pagos programados
 *
 * @package ALQUIPRESS\PaymentManager\Orders
 */

namespace ALQUIPRESS\PaymentManager\Orders;

defined('ABSPATH') || exit;

/**
 * Class ScheduledPayments
 * Gestiona el cron y procesamiento de pagos automáticos
 */
class ScheduledPayments {

    /**
     * Máximo de intentos antes de marcar como fallido
     */
    const MAX_ATTEMPTS = 3;

    /**
     * Constructor
     */
    public function __construct() {
        // Registrar el evento cron
        add_action('apm_process_scheduled_payments', [$this, 'process_pending_payments']);

        // Enviar recordatorios
        add_action('apm_send_payment_reminders', [$this, 'send_reminders']);

        // Programar cron si no existe
        if (!wp_next_scheduled('apm_process_scheduled_payments')) {
            wp_schedule_event(time(), 'hourly', 'apm_process_scheduled_payments');
        }

        if (!wp_next_scheduled('apm_send_payment_reminders')) {
            wp_schedule_event(time(), 'daily', 'apm_send_payment_reminders');
        }

        // Acciones manuales desde el admin
        add_action('wp_ajax_apm_process_payment_now', [$this, 'ajax_process_payment_now']);
        add_action('wp_ajax_apm_mark_paid_cash', [$this, 'ajax_mark_paid_cash']);
    }

    /**
     * Procesar pagos pendientes
     */
    public function process_pending_payments() {
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';

        // Obtener pagos pendientes cuya fecha ya pasó
        $pending_payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE status = 'pending'
             AND scheduled_date <= %s
             AND attempts < %d
             ORDER BY scheduled_date ASC
             LIMIT 20",
            current_time('mysql'),
            self::MAX_ATTEMPTS
        ));

        if (empty($pending_payments)) {
            return;
        }

        foreach ($pending_payments as $payment) {
            $this->process_single_payment($payment);
        }
    }

    /**
     * Procesar un pago individual
     *
     * @param object $payment Registro de pago
     * @return bool
     */
    private function process_single_payment($payment) {
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';

        $order = wc_get_order($payment->order_id);
        if (!$order) {
            $this->update_payment_status($payment->id, 'failed', __('Pedido no encontrado', 'apm'));
            return false;
        }

        // Incrementar intentos
        $wpdb->update(
            $table,
            [
                'attempts'   => $payment->attempts + 1,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $payment->id],
            ['%d', '%s'],
            ['%d']
        );

        // Verificar método de pago configurado
        $payment_method = $order->get_meta('_apm_payment_method_id');

        if (empty($payment_method)) {
            // No hay método de pago guardado, marcar para pago manual
            $this->update_payment_status($payment->id, 'manual_required', __('No hay método de pago guardado', 'apm'));
            $this->notify_manual_payment_required($order, $payment);
            return false;
        }

        // Intentar cobrar con Stripe
        $result = $this->charge_with_stripe($order, $payment, $payment_method);

        if ($result['success']) {
            $this->update_payment_status(
                $payment->id,
                'completed',
                __('Pago procesado correctamente', 'apm'),
                [
                    'stripe_intent_id' => $result['intent_id'],
                    'stripe_charge_id' => $result['charge_id'] ?? null,
                    'paid_date'        => current_time('mysql'),
                ]
            );

            $order->add_order_note(sprintf(
                __('Saldo de %s cobrado automáticamente (Stripe Intent: %s)', 'apm'),
                wc_price($payment->amount),
                $result['intent_id']
            ));

            // Verificar si todos los pagos están completos
            $this->check_all_payments_complete($order);

            return true;
        } else {
            $error_message = $result['error'] ?? __('Error desconocido', 'apm');

            // Si alcanzó el máximo de intentos, marcar como fallido
            if ($payment->attempts + 1 >= self::MAX_ATTEMPTS) {
                $this->update_payment_status($payment->id, 'failed', $error_message);
                $this->notify_payment_failed($order, $payment, $error_message);
            } else {
                $wpdb->update(
                    $table,
                    ['last_error' => $error_message],
                    ['id' => $payment->id],
                    ['%s'],
                    ['%d']
                );
            }

            $order->add_order_note(sprintf(
                __('Error al cobrar saldo: %s (Intento %d/%d)', 'apm'),
                $error_message,
                $payment->attempts + 1,
                self::MAX_ATTEMPTS
            ));

            return false;
        }
    }

    /**
     * Cobrar con Stripe
     *
     * @param \WC_Order $order          Pedido
     * @param object    $payment        Registro de pago
     * @param string    $payment_method ID del método de pago
     * @return array
     */
    private function charge_with_stripe($order, $payment, $payment_method) {
        // Verificar si Stripe está disponible
        if (!class_exists('WC_Stripe_API') && !class_exists('\Stripe\Stripe')) {
            return [
                'success' => false,
                'error'   => __('Stripe no está configurado', 'apm'),
            ];
        }

        try {
            // Obtener API key de Stripe desde WooCommerce
            $stripe_settings = get_option('woocommerce_stripe_settings', []);
            $testmode = isset($stripe_settings['testmode']) && $stripe_settings['testmode'] === 'yes';
            $secret_key = $testmode
                ? ($stripe_settings['test_secret_key'] ?? '')
                : ($stripe_settings['secret_key'] ?? '');

            if (empty($secret_key)) {
                return [
                    'success' => false,
                    'error'   => __('API key de Stripe no configurada', 'apm'),
                ];
            }

            \Stripe\Stripe::setApiKey($secret_key);

            // Obtener customer ID de Stripe
            $customer_id = $order->get_meta('_stripe_customer_id');

            if (empty($customer_id)) {
                return [
                    'success' => false,
                    'error'   => __('Cliente de Stripe no encontrado', 'apm'),
                ];
            }

            // Crear Payment Intent off-session
            $intent = \Stripe\PaymentIntent::create([
                'amount'               => round($payment->amount * 100), // Céntimos
                'currency'             => strtolower($payment->currency),
                'customer'             => $customer_id,
                'payment_method'       => $payment_method,
                'off_session'          => true,
                'confirm'              => true,
                'description'          => sprintf(
                    __('Saldo reserva #%s - %s', 'apm'),
                    $order->get_order_number(),
                    get_bloginfo('name')
                ),
                'metadata'             => [
                    'order_id'      => $order->get_id(),
                    'payment_id'    => $payment->id,
                    'payment_type'  => 'balance',
                    'source'        => 'alquipress_payment_manager',
                ],
            ]);

            if ($intent->status === 'succeeded') {
                return [
                    'success'   => true,
                    'intent_id' => $intent->id,
                    'charge_id' => $intent->latest_charge ?? null,
                ];
            } else {
                return [
                    'success' => false,
                    'error'   => sprintf(__('Estado del pago: %s', 'apm'), $intent->status),
                ];
            }
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'success' => false,
                'error'   => $e->getError()->message ?? $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Actualizar estado de un pago
     *
     * @param int    $payment_id ID del pago
     * @param string $status     Nuevo estado
     * @param string $message    Mensaje/nota
     * @param array  $extra      Datos extra a actualizar
     */
    private function update_payment_status($payment_id, $status, $message = '', $extra = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';

        $data = array_merge([
            'status'     => $status,
            'updated_at' => current_time('mysql'),
        ], $extra);

        if (!empty($message)) {
            $data['notes'] = $message;
        }

        $wpdb->update($table, $data, ['id' => $payment_id]);

        // Log
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $payment_id
        ));

        if ($payment) {
            $this->log_action($payment->order_id, 'payment_status_changed', $status, $payment->amount, $message);
        }
    }

    /**
     * Verificar si todos los pagos están completos
     *
     * @param \WC_Order $order Pedido
     */
    private function check_all_payments_complete($order) {
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';

        $pending = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE order_id = %d AND status IN ('pending', 'manual_required')",
            $order->get_id()
        ));

        if ($pending == 0) {
            $order->update_status('fully-paid', __('Todos los pagos completados', 'apm'));
        }
    }

    /**
     * Notificar que se requiere pago manual
     *
     * @param \WC_Order $order   Pedido
     * @param object    $payment Pago
     */
    private function notify_manual_payment_required($order, $payment) {
        // TODO: Enviar email al cliente y admin
        do_action('apm_manual_payment_required', $order, $payment);
    }

    /**
     * Notificar pago fallido
     *
     * @param \WC_Order $order   Pedido
     * @param object    $payment Pago
     * @param string    $error   Mensaje de error
     */
    private function notify_payment_failed($order, $payment, $error) {
        // Notificar al admin si está configurado
        if (get_option('apm_notify_admin_on_failure', 'yes') === 'yes') {
            $admin_email = get_option('admin_email');
            $subject = sprintf(__('[%s] Pago fallido - Pedido #%s', 'apm'), get_bloginfo('name'), $order->get_order_number());
            $message = sprintf(
                __("El cobro automático del saldo ha fallado.\n\nPedido: #%s\nCliente: %s\nImporte: %s\nError: %s\n\nPor favor, revisa el pedido y contacta con el cliente.", 'apm'),
                $order->get_order_number(),
                $order->get_billing_email(),
                wc_price($payment->amount),
                $error
            );

            wp_mail($admin_email, $subject, $message);
        }

        do_action('apm_payment_failed', $order, $payment, $error);
    }

    /**
     * Enviar recordatorios de pago próximo
     */
    public function send_reminders() {
        $reminder_days = (int) get_option('apm_reminder_days', 3);
        if ($reminder_days <= 0) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';

        $reminder_date = date('Y-m-d', strtotime("+{$reminder_days} days"));

        $upcoming = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE status = 'pending'
             AND DATE(scheduled_date) = %s
             AND (notes IS NULL OR notes NOT LIKE '%%reminder_sent%%')",
            $reminder_date
        ));

        foreach ($upcoming as $payment) {
            $this->send_payment_reminder($payment);
        }
    }

    /**
     * Enviar recordatorio individual
     *
     * @param object $payment Pago
     */
    private function send_payment_reminder($payment) {
        $order = wc_get_order($payment->order_id);
        if (!$order) {
            return;
        }

        // TODO: Implementar email de recordatorio
        do_action('apm_send_payment_reminder', $order, $payment);

        // Marcar como enviado
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';

        $current_notes = $payment->notes ?? '';
        $wpdb->update(
            $table,
            ['notes' => $current_notes . ' [reminder_sent:' . current_time('mysql') . ']'],
            ['id' => $payment->id]
        );
    }

    /**
     * AJAX: Procesar pago ahora (manualmente)
     */
    public function ajax_process_payment_now() {
        check_ajax_referer('apm_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Sin permisos', 'apm'));
        }

        $payment_id = absint($_POST['payment_id'] ?? 0);
        if (!$payment_id) {
            wp_send_json_error(__('ID de pago inválido', 'apm'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';
        $payment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $payment_id));

        if (!$payment) {
            wp_send_json_error(__('Pago no encontrado', 'apm'));
        }

        $result = $this->process_single_payment($payment);

        if ($result) {
            wp_send_json_success(__('Pago procesado correctamente', 'apm'));
        } else {
            wp_send_json_error(__('Error al procesar el pago', 'apm'));
        }
    }

    /**
     * AJAX: Marcar como pagado en efectivo
     */
    public function ajax_mark_paid_cash() {
        check_ajax_referer('apm_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Sin permisos', 'apm'));
        }

        $payment_id = absint($_POST['payment_id'] ?? 0);
        if (!$payment_id) {
            wp_send_json_error(__('ID de pago inválido', 'apm'));
        }

        $this->update_payment_status($payment_id, 'completed', __('Pagado en efectivo', 'apm'), [
            'payment_method' => 'cash',
            'paid_date'      => current_time('mysql'),
        ]);

        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';
        $payment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $payment_id));

        if ($payment) {
            $order = wc_get_order($payment->order_id);
            if ($order) {
                $order->add_order_note(sprintf(
                    __('Saldo de %s marcado como pagado en efectivo por %s', 'apm'),
                    wc_price($payment->amount),
                    wp_get_current_user()->display_name
                ));
                $this->check_all_payments_complete($order);
            }
        }

        wp_send_json_success(__('Marcado como pagado', 'apm'));
    }

    /**
     * Registrar acción en log
     *
     * @param int    $order_id ID del pedido
     * @param string $action   Acción
     * @param string $status   Estado
     * @param float  $amount   Importe
     * @param string $message  Mensaje
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
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%f', '%s', '%d', '%s']
        );
    }
}
