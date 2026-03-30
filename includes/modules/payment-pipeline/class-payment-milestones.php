<?php
/**
 * Clase para Gestión de Hitos de Pago
 * Lee datos del payment manager y proporciona información sobre hitos de pago
 * 
 * @package Alquipress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Payment_Milestones
{
    /**
     * Obtener todos los hitos de pago de un pedido
     * 
     * @param int $order_id ID del pedido
     * @return array Array de hitos con información detallada
     */
    public static function get_order_milestones($order_id)
    {
        global $wpdb;
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return [];
        }
        
        $table_schedule = $wpdb->prefix . 'apm_payment_schedule';
        $table_security = $wpdb->prefix . 'apm_security_deposits';
        
        $milestones = [];
        
        // Obtener pagos programados
        $scheduled_payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_schedule} WHERE order_id = %d ORDER BY scheduled_date ASC",
            $order_id
        ), ARRAY_A);
        
        foreach ($scheduled_payments as $payment) {
            $milestones[] = [
                'id' => $payment['id'],
                'type' => $payment['payment_type'], // 'deposit', 'balance'
                'amount' => floatval($payment['amount']),
                'currency' => $payment['currency'],
                'scheduled_date' => $payment['scheduled_date'],
                'paid_date' => $payment['paid_date'],
                'status' => $payment['status'], // 'pending', 'paid', 'failed'
                'payment_method' => $payment['payment_method'],
                'days_until_due' => self::calculate_days_until_due($payment['scheduled_date']),
                'is_overdue' => self::is_overdue($payment['scheduled_date'], $payment['status']),
                'order_id' => $order_id
            ];
        }
        
        // Obtener información de fianza
        $security_deposit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_security} WHERE order_id = %d LIMIT 1",
            $order_id
        ), ARRAY_A);
        
        if ($security_deposit) {
            $milestones[] = [
                'id' => 'security_' . $security_deposit['id'],
                'type' => 'security',
                'amount' => floatval($security_deposit['amount']),
                'currency' => $security_deposit['currency'],
                'scheduled_date' => $security_deposit['held_at'] ?? null,
                'paid_date' => $security_deposit['released_at'] ?? null,
                'status' => $security_deposit['status'], // 'pending', 'held', 'released', 'captured'
                'payment_method' => $security_deposit['method'],
                'days_until_due' => null, // Las fianzas no tienen fecha de vencimiento
                'is_overdue' => false,
                'order_id' => $order_id
            ];
        }
        
        return $milestones;
    }
    
    /**
     * Obtener próximos vencimientos de pagos
     * 
     * @param int $days Días hacia adelante para buscar
     * @return array Array de pagos próximos a vencer
     */
    public static function get_upcoming_due_dates($days = 7)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'apm_payment_schedule';
        $start_date = current_time('mysql');
        $end_date = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        
        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, o.post_status as order_status
             FROM {$table} p
             INNER JOIN {$wpdb->posts} o ON p.order_id = o.ID
             WHERE p.status = 'pending'
             AND p.scheduled_date BETWEEN %s AND %s
             ORDER BY p.scheduled_date ASC",
            $start_date,
            $end_date
        ), ARRAY_A);
        
        $result = [];
        foreach ($payments as $payment) {
            $order = wc_get_order($payment['order_id']);
            if (!$order) {
                continue;
            }
            
            $result[] = [
                'payment_id' => $payment['id'],
                'order_id' => $payment['order_id'],
                'order_number' => $order->get_order_number(),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'property_name' => self::get_order_property_name($order),
                'payment_type' => $payment['payment_type'],
                'amount' => floatval($payment['amount']),
                'currency' => $payment['currency'],
                'scheduled_date' => $payment['scheduled_date'],
                'days_until_due' => self::calculate_days_until_due($payment['scheduled_date']),
                'order_url' => admin_url('post.php?post=' . $payment['order_id'] . '&action=edit')
            ];
        }
        
        return $result;
    }
    
    /**
     * Obtener pagos vencidos
     * 
     * @param int $days Días después del vencimiento para considerar "vencido"
     * @return array Array de pagos vencidos
     */
    public static function get_overdue_payments($days = 3)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'apm_payment_schedule';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, o.post_status as order_status
             FROM {$table} p
             INNER JOIN {$wpdb->posts} o ON p.order_id = o.ID
             WHERE p.status = 'pending'
             AND p.scheduled_date < %s
             ORDER BY p.scheduled_date ASC",
            $cutoff_date
        ), ARRAY_A);
        
        $result = [];
        foreach ($payments as $payment) {
            $order = wc_get_order($payment['order_id']);
            if (!$order) {
                continue;
            }
            
            $days_overdue = floor((time() - strtotime($payment['scheduled_date'])) / DAY_IN_SECONDS);
            
            $result[] = [
                'payment_id' => $payment['id'],
                'order_id' => $payment['order_id'],
                'order_number' => $order->get_order_number(),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'property_name' => self::get_order_property_name($order),
                'payment_type' => $payment['payment_type'],
                'amount' => floatval($payment['amount']),
                'currency' => $payment['currency'],
                'scheduled_date' => $payment['scheduled_date'],
                'days_overdue' => $days_overdue,
                'order_url' => admin_url('post.php?post=' . $payment['order_id'] . '&action=edit')
            ];
        }
        
        return $result;
    }
    
    /**
     * Obtener todos los pagos agrupados por estado para el Kanban
     * 
     * @param array $filters Filtros opcionales (owner_id, property_id, date_range)
     * @return array Array agrupado por estado
     */
    public static function get_payments_by_status($filters = [])
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'apm_payment_schedule';
        
        // Construir query base
        $where = ["p.status != 'cancelled'"];
        $join = "INNER JOIN {$wpdb->posts} o ON p.order_id = o.ID";
        
        // Aplicar filtros
        if (!empty($filters['date_from'])) {
            $where[] = $wpdb->prepare("p.scheduled_date >= %s", $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $where[] = $wpdb->prepare("p.scheduled_date <= %s", $filters['date_to']);
        }
        
        // Filtro por propiedad (necesita meta query)
        if (!empty($filters['property_id'])) {
            $join .= " INNER JOIN {$wpdb->postmeta} pm ON o.ID = pm.post_id AND pm.meta_key = '_booking_product_id'";
            $where[] = $wpdb->prepare("pm.meta_value = %d", $filters['property_id']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        $payments = $wpdb->get_results(
            "SELECT p.*, o.post_status as order_status
             FROM {$table} p
             {$join}
             WHERE {$where_clause}
             ORDER BY p.scheduled_date ASC",
            ARRAY_A
        );
        
        // Agrupar por estado
        $grouped = [
            'deposit-pending' => [],
            'deposit-paid' => [],
            'balance-pending' => [],
            'fully-paid' => [],
            'security-held' => [],
            'security-refunded' => []
        ];
        
        foreach ($payments as $payment) {
            $order = wc_get_order($payment['order_id']);
            if (!$order) {
                continue;
            }
            
            // Determinar estado del Kanban basado en payment_type y status
            $kanban_status = self::determine_kanban_status($payment, $order);
            
            if (!isset($grouped[$kanban_status])) {
                $grouped[$kanban_status] = [];
            }
            
            $grouped[$kanban_status][] = [
                'payment_id' => $payment['id'],
                'order_id' => $payment['order_id'],
                'order_number' => $order->get_order_number(),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'property_name' => self::get_order_property_name($order),
                'payment_type' => $payment['payment_type'],
                'amount' => floatval($payment['amount']),
                'amount_formatted' => wc_price($payment['amount'], ['currency' => $payment['currency']]),
                'currency' => $payment['currency'],
                'scheduled_date' => $payment['scheduled_date'],
                'paid_date' => $payment['paid_date'],
                'status' => $payment['status'],
                'days_until_due' => self::calculate_days_until_due($payment['scheduled_date']),
                'is_overdue' => self::is_overdue($payment['scheduled_date'], $payment['status']),
                'order_url' => admin_url('post.php?post=' . $payment['order_id'] . '&action=edit')
            ];
        }
        
        return $grouped;
    }
    
    /**
     * Determinar estado del Kanban basado en payment y order
     */
    private static function determine_kanban_status($payment, $order)
    {
        $order_status = $order->get_status();
        
        // Si el pedido está completamente pagado
        if ($order_status === 'completed' || $order_status === 'fully-paid') {
            return 'fully-paid';
        }
        
        // Si es depósito
        if ($payment['payment_type'] === 'deposit') {
            return $payment['status'] === 'paid' ? 'deposit-paid' : 'deposit-pending';
        }
        
        // Si es saldo
        if ($payment['payment_type'] === 'balance') {
            return $payment['status'] === 'paid' ? 'fully-paid' : 'balance-pending';
        }
        
        // Por defecto
        return 'deposit-pending';
    }
    
    /**
     * Calcular días hasta el vencimiento
     */
    private static function calculate_days_until_due($scheduled_date)
    {
        if (empty($scheduled_date)) {
            return null;
        }
        
        $scheduled_timestamp = strtotime($scheduled_date);
        $now = current_time('timestamp');
        
        return floor(($scheduled_timestamp - $now) / DAY_IN_SECONDS);
    }
    
    /**
     * Verificar si un pago está vencido
     */
    private static function is_overdue($scheduled_date, $status)
    {
        if ($status === 'paid') {
            return false;
        }
        
        if (empty($scheduled_date)) {
            return false;
        }
        
        $scheduled_timestamp = strtotime($scheduled_date);
        $now = current_time('timestamp');
        
        return $scheduled_timestamp < $now;
    }
    
    /**
     * Obtener nombre de la propiedad de un pedido
     */
    private static function get_order_property_name($order)
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
