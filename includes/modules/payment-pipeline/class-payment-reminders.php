<?php
/**
 * Clase para Sistema de Recordatorios de Pago
 * Envía emails automáticos según programación de recordatorios
 * 
 * @package Alquipress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Payment_Reminders
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Programar evento diario para enviar recordatorios
        add_action('alquipress_payment_reminders_daily', [$this, 'send_scheduled_reminders']);
        
        // Registrar cron job si no existe
        if (!wp_next_scheduled('alquipress_payment_reminders_daily')) {
            wp_schedule_event(time(), 'daily', 'alquipress_payment_reminders_daily');
        }
    }
    
    /**
     * Enviar recordatorios programados (ejecutado por cron diario)
     */
    public function send_scheduled_reminders()
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'apm_payment_schedule';
        $today = current_time('Y-m-d');
        
        // Obtener todos los pagos pendientes
        $pending_payments = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY scheduled_date ASC",
            ARRAY_A
        );
        
        foreach ($pending_payments as $payment) {
            $scheduled_date = date('Y-m-d', strtotime($payment['scheduled_date']));
            $days_until_due = floor((strtotime($scheduled_date) - strtotime($today)) / DAY_IN_SECONDS);
            $days_overdue = -$days_until_due; // Negativo si está vencido
            
            // Enviar recordatorio 7 días antes
            if ($days_until_due === 7 && !$this->was_reminder_sent($payment['id'], '7d')) {
                $this->send_reminder($payment['id'], 7);
            }
            
            // Enviar recordatorio 3 días antes
            if ($days_until_due === 3 && !$this->was_reminder_sent($payment['id'], '3d')) {
                $this->send_reminder($payment['id'], 3);
            }
            
            // Enviar recordatorio el día del vencimiento
            if ($days_until_due === 0 && !$this->was_reminder_sent($payment['id'], 'due')) {
                $this->send_reminder($payment['id'], 0);
            }
            
            // Enviar recordatorio 3 días después del vencimiento (overdue)
            if ($days_overdue === 3 && !$this->was_reminder_sent($payment['id'], 'overdue')) {
                $this->send_reminder($payment['id'], -3);
            }
        }
    }
    
    /**
     * Enviar recordatorio individual
     * 
     * @param int $payment_schedule_id ID del pago programado
     * @param int $days_before Días antes/después del vencimiento (negativo = después)
     * @return bool True si se envió correctamente, false en caso contrario
     */
    public function send_reminder($payment_schedule_id, $days_before)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'apm_payment_schedule';
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $payment_schedule_id
        ), ARRAY_A);
        
        if (!$payment || $payment['status'] !== 'pending') {
            return false;
        }
        
        $order = wc_get_order($payment['order_id']);
        if (!$order) {
            return false;
        }
        
        // Obtener email del cliente
        $customer_email = $order->get_billing_email();
        if (empty($customer_email)) {
            return false;
        }
        
        // Generar template de email
        $email_data = $this->get_email_template($order, $payment, $days_before);
        
        // Enviar email
        $sent = wp_mail(
            $customer_email,
            $email_data['subject'],
            $email_data['message'],
            [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
            ]
        );
        
        if ($sent) {
            // Marcar recordatorio como enviado
            $this->mark_reminder_sent($payment_schedule_id, $this->get_reminder_key($days_before));
            
            // Log
            if (class_exists('Alquipress_Logger')) {
                Alquipress_Logger::info(
                    sprintf('Recordatorio de pago enviado: Pedido #%d, %d días antes', $order->get_id(), $days_before),
                    Alquipress_Logger::CONTEXT_EMAIL,
                    [
                        'order_id' => $order->get_id(),
                        'payment_id' => $payment_schedule_id,
                        'days_before' => $days_before
                    ]
                );
            }
        }
        
        return $sent;
    }
    
    /**
     * Obtener template de email
     * 
     * @param WC_Order $order Pedido
     * @param array $payment Datos del pago programado
     * @param int $days_before Días antes/después del vencimiento
     * @return array ['subject', 'message']
     */
    private function get_email_template($order, $payment, $days_before)
    {
        $order_number = $order->get_order_number();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $amount = wc_price($payment['amount'], ['currency' => $payment['currency']]);
        $due_date = date_i18n(get_option('date_format'), strtotime($payment['scheduled_date']));
        
        // Determinar tipo de recordatorio
        if ($days_before > 0) {
            $reminder_type = sprintf(__('Recordatorio: Pago pendiente en %d días', 'alquipress'), $days_before);
            $urgency = $days_before === 3 ? __('urgente', 'alquipress') : __('importante', 'alquipress');
        } elseif ($days_before === 0) {
            $reminder_type = __('Recordatorio: Pago vence hoy', 'alquipress');
            $urgency = __('muy urgente', 'alquipress');
        } else {
            $days_overdue = abs($days_before);
            $reminder_type = sprintf(__('Recordatorio: Pago vencido hace %d días', 'alquipress'), $days_overdue);
            $urgency = __('crítico', 'alquipress');
        }
        
        $subject = sprintf(__('Recordatorio de pago - Pedido #%s', 'alquipress'), $order_number);
        
        // Obtener nombre de la propiedad
        $property_name = $this->get_order_property_name($order);
        
        // Generar enlace de pago (si existe)
        $payment_url = $order->get_checkout_payment_url();
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2271b1; color: #fff; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .payment-info { background: #fff; border-left: 4px solid #2271b1; padding: 15px; margin: 20px 0; }
                .button { display: inline-block; background: #2271b1; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . esc_html($reminder_type) . '</h1>
                </div>
                <div class="content">
                    <p>Hola ' . esc_html($customer_name) . ',</p>
                    <p>Te recordamos que tienes un pago ' . esc_html($urgency) . ' pendiente:</p>
                    
                    <div class="payment-info">
                        <p><strong>Pedido:</strong> #' . esc_html($order_number) . '</p>
                        <p><strong>Propiedad:</strong> ' . esc_html($property_name) . '</p>
                        <p><strong>Monto:</strong> ' . $amount . '</p>
                        <p><strong>Fecha de vencimiento:</strong> ' . esc_html($due_date) . '</p>
                    </div>
                    
                    ' . (!empty($payment_url) ? '<p><a href="' . esc_url($payment_url) . '" class="button">Realizar Pago</a></p>' : '') . '
                    
                    <p>Si ya realizaste el pago, puedes ignorar este mensaje.</p>
                    <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
                </div>
                <div class="footer">
                    <p>' . esc_html(get_bloginfo('name')) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return [
            'subject' => $subject,
            'message' => $message
        ];
    }
    
    /**
     * Verificar si un recordatorio ya fue enviado
     */
    private function was_reminder_sent($payment_schedule_id, $reminder_key)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';
        
        $meta_key = '_reminder_sent_' . $reminder_key;
        $meta_value = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
            $payment_schedule_id,
            $meta_key
        ));
        
        return !empty($meta_value);
    }
    
    /**
     * Marcar recordatorio como enviado
     */
    private function mark_reminder_sent($payment_schedule_id, $reminder_key)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';
        
        // Usar meta del pedido para almacenar recordatorios enviados
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT order_id FROM {$table} WHERE id = %d",
            $payment_schedule_id
        ), ARRAY_A);
        
        if ($payment) {
            $order = wc_get_order($payment['order_id']);
            if ($order) {
                $meta_key = '_payment_reminder_' . $payment_schedule_id . '_' . $reminder_key;
                $order->update_meta_data($meta_key, current_time('mysql'));
                $order->save();
            }
        }
    }
    
    /**
     * Obtener clave de recordatorio según días antes/después
     */
    private function get_reminder_key($days_before)
    {
        if ($days_before === 7) {
            return '7d';
        } elseif ($days_before === 3) {
            return '3d';
        } elseif ($days_before === 0) {
            return 'due';
        } elseif ($days_before < 0) {
            return 'overdue';
        }
        return 'unknown';
    }
    
    /**
     * Obtener nombre de la propiedad de un pedido
     */
    private function get_order_property_name($order)
    {
        $items = $order->get_items();
        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product) {
                return $product->get_name();
            }
        }
        return __('Sin propiedad', 'alquipress');
    }
}
