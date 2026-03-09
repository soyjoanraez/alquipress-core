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
    const DB_VERSION_OPTION = 'alquipress_reminders_db_version';
    const DB_VERSION        = '1.0';
    const REMINDERS_TABLE   = 'alquipress_payment_reminders';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->maybe_create_table();

        // Programar evento diario para enviar recordatorios
        add_action('alquipress_payment_reminders_daily', [$this, 'send_scheduled_reminders']);

        // Registrar cron job si no existe
        if (!wp_next_scheduled('alquipress_payment_reminders_daily')) {
            wp_schedule_event(time(), 'daily', 'alquipress_payment_reminders_daily');
        }
    }

    /**
     * Crear tabla de recordatorios si no existe.
     * Sustituye el almacenamiento en wp_postmeta, que requería consultas LIKE ineficientes.
     */
    private function maybe_create_table(): void
    {
        if (get_option(self::DB_VERSION_OPTION) === self::DB_VERSION) {
            return;
        }

        global $wpdb;
        $table           = $wpdb->prefix . self::REMINDERS_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            payment_schedule_id BIGINT UNSIGNED NOT NULL,
            order_id            BIGINT UNSIGNED NOT NULL,
            reminder_key        VARCHAR(20)     NOT NULL,
            sent_at             DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_schedule_reminder (payment_schedule_id, reminder_key),
            KEY idx_order_id (order_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Migrar datos legacy de postmeta a la nueva tabla
        $this->migrate_legacy_postmeta_reminders();

        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
    }

    /**
     * Migrar recordatorios guardados en postmeta a la tabla SQL.
     * Formato legacy: meta_key = '_payment_reminder_{schedule_id}_{key}' en el pedido.
     */
    private function migrate_legacy_postmeta_reminders(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::REMINDERS_TABLE;
        $sched = $wpdb->prefix . 'apm_payment_schedule';

        $legacy_meta = $wpdb->get_results(
            "SELECT post_id AS order_id, meta_key, meta_value
             FROM {$wpdb->postmeta}
             WHERE meta_key LIKE '_payment_reminder_%'",
            ARRAY_A
        );

        foreach ((array) $legacy_meta as $row) {
            // Extraer schedule_id y reminder_key del meta_key
            if (!preg_match('/^_payment_reminder_(\d+)_([a-z0-9]+)$/', $row['meta_key'], $m)) {
                continue;
            }
            $schedule_id  = (int) $m[1];
            $reminder_key = $m[2];

            $wpdb->insert(
                $table,
                [
                    'payment_schedule_id' => $schedule_id,
                    'order_id'            => (int) $row['order_id'],
                    'reminder_key'        => $reminder_key,
                    'sent_at'             => $row['meta_value'] ?: current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s']
            );
        }
    }
    
    /**
     * Enviar recordatorios programados (ejecutado por cron diario).
     * Carga los estados de recordatorio en una sola query JOIN para evitar N+1.
     */
    public function send_scheduled_reminders(): void
    {
        global $wpdb;

        $sched_table     = $wpdb->prefix . 'apm_payment_schedule';
        $reminder_table  = $wpdb->prefix . self::REMINDERS_TABLE;
        $today           = current_time('Y-m-d');

        // Obtener pagos pendientes junto con sus recordatorios ya enviados en una sola query
        $rows = $wpdb->get_results(
            "SELECT s.id, s.order_id, s.scheduled_date,
                    GROUP_CONCAT(r.reminder_key) AS sent_keys
             FROM {$sched_table} s
             LEFT JOIN {$reminder_table} r ON r.payment_schedule_id = s.id
             WHERE s.status = 'pending'
             GROUP BY s.id
             ORDER BY s.scheduled_date ASC",
            ARRAY_A
        );

        foreach ((array) $rows as $payment) {
            $scheduled_date = gmdate('Y-m-d', strtotime($payment['scheduled_date']));
            $days_until_due = (int) floor((strtotime($scheduled_date) - strtotime($today)) / DAY_IN_SECONDS);
            $days_overdue   = -$days_until_due;

            // Conjunto de claves ya enviadas para este pago (lookup O(1))
            $sent = $payment['sent_keys']
                ? array_flip(explode(',', $payment['sent_keys']))
                : [];

            $schedule = [
                ['key' => '7d',      'days' => 7,  'condition' => $days_until_due === 7],
                ['key' => '3d',      'days' => 3,  'condition' => $days_until_due === 3],
                ['key' => 'due',     'days' => 0,  'condition' => $days_until_due === 0],
                ['key' => 'overdue', 'days' => -3, 'condition' => $days_overdue === 3],
            ];

            foreach ($schedule as $item) {
                if ($item['condition'] && !isset($sent[$item['key']])) {
                    $this->send_reminder((int) $payment['id'], $item['days']);
                }
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
     * Verificar si un recordatorio ya fue enviado.
     * Consulta la tabla SQL dedicada (O(1) con índice UNIQUE).
     */
    private function was_reminder_sent(int $payment_schedule_id, string $reminder_key): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . self::REMINDERS_TABLE;

        return (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table}
                 WHERE payment_schedule_id = %d AND reminder_key = %s
                 LIMIT 1",
                $payment_schedule_id,
                $reminder_key
            )
        );
    }

    /**
     * Marcar recordatorio como enviado en la tabla SQL.
     * El índice UNIQUE previene duplicados de forma garantizada.
     */
    private function mark_reminder_sent(int $payment_schedule_id, string $reminder_key): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::REMINDERS_TABLE;
        $sched = $wpdb->prefix . 'apm_payment_schedule';

        $order_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT order_id FROM {$sched} WHERE id = %d", $payment_schedule_id)
        );

        if (!$order_id) {
            return;
        }

        // INSERT IGNORE para respetar el UNIQUE KEY sin lanzar error si ya existe
        $wpdb->query(
            $wpdb->prepare(
                "INSERT IGNORE INTO {$table}
                 (payment_schedule_id, order_id, reminder_key, sent_at)
                 VALUES (%d, %d, %s, %s)",
                $payment_schedule_id,
                $order_id,
                $reminder_key,
                current_time('mysql')
            )
        );
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
