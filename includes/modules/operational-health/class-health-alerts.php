<?php
/**
 * Clase para Detección de Alertas de Salud Operativa
 * 
 * @package Alquipress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Health_Alerts
{
    /**
     * Obtener todas las alertas activas
     * 
     * @return array Array de alertas con estructura: ['type', 'priority', 'count', 'items', 'action_url', 'action_text']
     */
    public static function get_all_alerts()
    {
        $alerts = [];
        
        // Alertas de pagos pendientes
        $pending_payments = self::detect_pending_payments(3);
        if (!empty($pending_payments)) {
            $critical = array_filter($pending_payments, function($item) {
                return $item['days_pending'] > 7;
            });
            
            $alerts[] = [
                'id' => 'pending_payments',
                'type' => 'pending_payments',
                'priority' => !empty($critical) ? 'critical' : 'high',
                'title' => 'Pagos Pendientes',
                'count' => count($pending_payments),
                'items' => $pending_payments,
                'action_url' => admin_url('edit.php?post_type=shop_order&post_status=wc-pending'),
                'action_text' => 'Ver Pedidos Pendientes',
                'icon' => '💰'
            ];
        }
        
        // Alertas de check-ins sin documentación
        $checkins_without_docs = self::detect_checkins_without_docs(7);
        if (!empty($checkins_without_docs)) {
            $today = array_filter($checkins_without_docs, function($item) {
                return $item['days_until_checkin'] === 0;
            });
            
            $alerts[] = [
                'id' => 'checkins_without_docs',
                'type' => 'checkins_without_docs',
                'priority' => !empty($today) ? 'critical' : 'high',
                'title' => 'Check-ins sin Documentación',
                'count' => count($checkins_without_docs),
                'items' => $checkins_without_docs,
                'action_url' => admin_url('admin.php?page=alquipress-pipeline'),
                'action_text' => 'Ver Pipeline',
                'icon' => '📋'
            ];
        }
        
        // Alertas de propietarios sin IBAN
        $owners_without_iban = self::detect_owners_without_iban();
        if ($owners_without_iban > 0) {
            $alerts[] = [
                'id' => 'owners_without_iban',
                'type' => 'owners_without_iban',
                'priority' => 'medium',
                'title' => 'Propietarios sin IBAN',
                'count' => $owners_without_iban,
                'items' => [],
                'action_url' => admin_url('edit.php?post_type=propietario'),
                'action_text' => 'Ver Propietarios',
                'icon' => '💳'
            ];
        }
        
        // Alertas de reservas sin contrato
        $bookings_without_contract = self::detect_bookings_without_contract();
        if (!empty($bookings_without_contract)) {
            $alerts[] = [
                'id' => 'bookings_without_contract',
                'type' => 'bookings_without_contract',
                'priority' => 'medium',
                'title' => 'Reservas sin Contrato',
                'count' => count($bookings_without_contract),
                'items' => $bookings_without_contract,
                'action_url' => admin_url('admin.php?page=alquipress-bookings'),
                'action_text' => 'Ver Reservas',
                'icon' => '📄'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Detectar pagos pendientes por más de X días
     * 
     * @param int $days_threshold Días mínimos pendientes
     * @return array Array de pedidos pendientes con información
     */
    public static function detect_pending_payments($days_threshold = 3)
    {
        global $wpdb;
        
        $threshold_date = date('Y-m-d H:i:s', strtotime("-{$days_threshold} days"));
        
        $orders = wc_get_orders([
            'status' => 'pending',
            'limit' => -1,
            'date_created' => '<' . strtotime($threshold_date),
            'orderby' => 'date',
            'order' => 'ASC'
        ]);
        
        $alerts = [];
        foreach ($orders as $order) {
            $days_pending = floor((time() - strtotime($order->get_date_created())) / DAY_IN_SECONDS);
            
            $alerts[] = [
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'amount' => $order->get_total(),
                'days_pending' => $days_pending,
                'order_url' => admin_url('post.php?post=' . $order->get_id() . '&action=edit')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Detectar check-ins próximos sin documentación completa
     * 
     * @param int $days_ahead Días hacia adelante para buscar
     * @return array Array de reservas sin documentación
     */
    public static function detect_checkins_without_docs($days_ahead = 7)
    {
        global $wpdb;
        
        $today = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$days_ahead} days"));
        
        // Buscar reservas con check-in en el rango
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['wc-deposito-ok', 'wc-pending-checkin', 'wc-in-progress'],
            'meta_query' => [
                [
                    'key' => '_booking_checkin_date',
                    'value' => [$today, $end_date],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ]
            ]
        ]);
        
        $alerts = [];
        foreach ($orders as $order) {
            $checkin_date = $order->get_meta('_booking_checkin_date');
            $dni_status = $order->get_meta('_booking_dni_status');
            $contract_status = $order->get_meta('_booking_contract_status');
            
            $missing_docs = [];
            if ($dni_status !== 'uploaded') {
                $missing_docs[] = 'DNI';
            }
            if ($contract_status !== 'signed') {
                $missing_docs[] = 'Contrato';
            }
            
            if (!empty($missing_docs)) {
                $days_until = floor((strtotime($checkin_date) - strtotime($today)) / DAY_IN_SECONDS);
                
                $alerts[] = [
                    'order_id' => $order->get_id(),
                    'order_number' => $order->get_order_number(),
                    'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'checkin_date' => $checkin_date,
                    'days_until_checkin' => max(0, $days_until),
                    'missing_docs' => $missing_docs,
                    'order_url' => admin_url('post.php?post=' . $order->get_id() . '&action=edit')
                ];
            }
        }
        
        // Ordenar por días hasta check-in (más urgentes primero)
        usort($alerts, function($a, $b) {
            return $a['days_until_checkin'] <=> $b['days_until_checkin'];
        });
        
        return $alerts;
    }
    
    /**
     * Detectar propietarios sin IBAN
     * 
     * @return int Número de propietarios sin IBAN
     */
    public static function detect_owners_without_iban()
    {
        return Alquipress_Dashboard_Data::get_owners_without_iban();
    }
    
    /**
     * Detectar reservas sin contrato
     * 
     * @return array Array de reservas sin contrato
     */
    public static function detect_bookings_without_contract()
    {
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['wc-deposito-ok', 'wc-pending-checkin', 'wc-in-progress', 'wc-checkout-review'],
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_booking_contract_status',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => '_booking_contract_status',
                    'value' => 'missing',
                    'compare' => '='
                ]
            ]
        ]);
        
        $alerts = [];
        foreach ($orders as $order) {
            $checkin_date = $order->get_meta('_booking_checkin_date');
            
            $alerts[] = [
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'checkin_date' => $checkin_date,
                'status' => $order->get_status(),
                'order_url' => admin_url('post.php?post=' . $order->get_id() . '&action=edit')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Obtener alertas filtradas por prioridad
     * 
     * @param string $priority Prioridad: 'critical', 'high', 'medium', 'low'
     * @return array Array de alertas filtradas
     */
    public static function get_alerts_by_priority($priority)
    {
        $all_alerts = self::get_all_alerts();
        return array_filter($all_alerts, function($alert) use ($priority) {
            return $alert['priority'] === $priority;
        });
    }
    
    /**
     * Obtener alertas filtradas por tipo
     * 
     * @param string $type Tipo de alerta
     * @return array Array de alertas filtradas
     */
    public static function get_alerts_by_type($type)
    {
        $all_alerts = self::get_all_alerts();
        return array_filter($all_alerts, function($alert) use ($type) {
            return $alert['type'] === $type;
        });
    }
    
    /**
     * Contar total de alertas críticas
     * 
     * @return int Número de alertas críticas
     */
    public static function count_critical_alerts()
    {
        $critical = self::get_alerts_by_priority('critical');
        $count = 0;
        foreach ($critical as $alert) {
            $count += $alert['count'];
        }
        return $count;
    }
}
