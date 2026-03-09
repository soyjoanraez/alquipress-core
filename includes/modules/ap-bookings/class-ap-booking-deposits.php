<?php
/**
 * Módulo: Motor de depósitos propio (Ap_Booking Deposits)
 *
 * Sustituye WooCommerce Deposits para reservas gestionadas por Ap_Booking.
 * Se apoya en alquipress-payment-manager para la orquestación de cobros.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Política de depósito por producto
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Encapsula la política de depósito configurada en un producto.
 */
class Ap_Booking_Deposit_Policy
{
    private bool   $enabled          = false;
    private string $type             = 'percent'; // 'percent' | 'fixed'
    private float  $percent          = 40.0;
    private float  $fixed_amount     = 0.0;
    private int    $days_before      = 7;
    private float  $security_amount  = 0.0;

    private function __construct() {}

    /**
     * Cargar política para un producto (meta del producto, fallback a opciones globales).
     */
    public static function for_product(int $product_id): self
    {
        $policy = new self();

        // Si el producto tiene configuración propia de depósito
        if (get_post_meta($product_id, 'ap_deposit_enabled', true) === '1') {
            $policy->enabled = true;
            $policy->type    = get_post_meta($product_id, 'ap_deposit_type', true) ?: 'percent';

            $percent = get_post_meta($product_id, 'ap_deposit_percent', true);
            $policy->percent = ($percent !== '' && is_numeric($percent)) ? (float) $percent : 40.0;

            $fixed = get_post_meta($product_id, 'ap_deposit_fixed_amount', true);
            $policy->fixed_amount = ($fixed !== '' && is_numeric($fixed)) ? (float) $fixed : 0.0;

            $days = get_post_meta($product_id, 'ap_deposit_balance_days_before', true);
            $policy->days_before = ($days !== '' && is_numeric($days)) ? (int) $days : self::global_days_before();

            $security = get_post_meta($product_id, 'ap_security_deposit_amount', true);
            $policy->security_amount = ($security !== '' && is_numeric($security)) ? (float) $security : self::global_security();

            return $policy;
        }

        // Si el Payment Manager tiene override por producto
        if (
            class_exists('ALQUIPRESS\PaymentManager\Core') &&
            get_post_meta($product_id, '_apm_override_global', true) === 'yes'
        ) {
            $policy->enabled = true;
            $policy->type    = 'percent';
            $policy->percent = (float) (get_post_meta($product_id, '_apm_deposit_percent', true) ?: 40);
            $policy->days_before    = (int) (get_post_meta($product_id, '_apm_days_before', true) ?: 7);
            $policy->security_amount = (float) (get_post_meta($product_id, '_apm_security_deposit', true) ?: 0);
            return $policy;
        }

        // Fallback a configuración global del Payment Manager / opciones del sitio
        $apm_percent = get_option('apm_default_deposit_percent', '');
        if ($apm_percent !== '' && is_numeric($apm_percent)) {
            $policy->enabled      = true;
            $policy->type         = 'percent';
            $policy->percent      = (float) $apm_percent;
            $policy->days_before  = (int) get_option('apm_default_days_before', 7);
            $policy->security_amount = (float) get_option('apm_default_security_deposit', 0);
            return $policy;
        }

        // Fallback: opciones del motor propio
        $ap_percent = get_option('ap_bookings_default_deposit_pct', '');
        if ($ap_percent !== '' && is_numeric($ap_percent) && (float) $ap_percent > 0) {
            $policy->enabled = true;
            $policy->type    = 'percent';
            $policy->percent = (float) $ap_percent;
        }

        return $policy;
    }

    public function is_enabled(): bool
    {
        return $this->enabled;
    }

    public function get_deposit_type(): string
    {
        return $this->type;
    }

    public function get_deposit_percent(): float
    {
        return $this->percent;
    }

    public function get_deposit_fixed_amount(): float
    {
        return $this->fixed_amount;
    }

    public function get_balance_days_before(): int
    {
        return $this->days_before;
    }

    public function get_security_amount(): float
    {
        return $this->security_amount;
    }

    /**
     * Calcular el importe del depósito dado el total de la reserva.
     */
    public function calculate_deposit(float $total): float
    {
        if ($this->type === 'fixed') {
            return min($this->fixed_amount, $total);
        }
        return round(($total * $this->percent) / 100, 2);
    }

    private static function global_days_before(): int
    {
        return (int) get_option('apm_default_days_before', get_option('ap_bookings_default_deposit_days', 7));
    }

    private static function global_security(): float
    {
        return (float) get_option('apm_default_security_deposit', 0);
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Gestor de depósitos para reservas Ap_Booking
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Conecta Ap_Booking con alquipress-payment-manager:
 *  - Al crear el pedido aplica la política de depósito sobre los metadatos _apm_*.
 *  - Al confirmar el pago del depósito vincula el booking_id en apm_payment_schedule.
 *  - Al cancelar cancela los pagos programados pendientes.
 */
class Ap_Booking_Deposit_Manager
{
    public static function init_hooks(): void
    {
        // Aplicar política de depósito cuando se crea el pedido (después de Ap_Booking_Store priority 20).
        add_action('woocommerce_checkout_order_created', [__CLASS__, 'apply_deposit_on_order'], 25, 1);

        // Vincular booking_id en apm_payment_schedule tras pago completado.
        add_action('woocommerce_payment_complete', [__CLASS__, 'link_booking_to_schedule'], 30, 1);

        // Cancelar pagos programados cuando se cancela la reserva.
        add_action('woocommerce_order_status_changed', [__CLASS__, 'handle_order_status_for_deposits'], 30, 4);
    }

    /**
     * Aplicar la política de depósito al pedido recién creado.
     * Rellena los metadatos _apm_* para que DepositHandler::schedule_balance_payment() los use.
     */
    public static function apply_deposit_on_order(\WC_Order $order): void
    {
        // Solo actuar si el Payment Manager no ha calculado ya el desglose.
        if ($order->get_meta('_apm_is_staged_payment') === 'yes') {
            // Ya procesado por CheckoutModifier, solo completar la fecha de check-in si falta.
            self::ensure_checkin_date($order);
            return;
        }

        $product_id = self::get_ap_booking_product_id($order);
        if (!$product_id) {
            return;
        }

        $policy = Ap_Booking_Deposit_Policy::for_product($product_id);
        if (!$policy->is_enabled()) {
            return;
        }

        $checkin = self::get_checkin_from_order($order);
        if (!$checkin) {
            return;
        }

        $total = (float) $order->get_total();
        $deposit = $policy->calculate_deposit($total);
        $balance  = round($total - $deposit, 2);
        $security = $policy->get_security_amount();

        $order->update_meta_data('_apm_is_staged_payment', 'yes');
        $order->update_meta_data('_apm_deposit_amount',    $deposit);
        $order->update_meta_data('_apm_balance_amount',    $balance);
        $order->update_meta_data('_apm_security_amount',   $security);
        $order->update_meta_data('_apm_booking_total',     $total);
        $order->update_meta_data('_apm_check_in_date',     $checkin);
        $order->update_meta_data('_apm_days_before',       $policy->get_balance_days_before());
        $order->save();
    }

    /**
     * Tras el pago completado, guardar el booking_id de Ap_Booking en la fila
     * de apm_payment_schedule que acaba de crearse para el saldo.
     */
    public static function link_booking_to_schedule(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (!$order || $order->get_meta('_apm_is_staged_payment') !== 'yes') {
            return;
        }

        $schedule_id = (int) $order->get_meta('_apm_balance_payment_id');
        if (!$schedule_id) {
            return;
        }

        $booking_id = self::get_ap_booking_id_from_order($order);
        if (!$booking_id) {
            return;
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'apm_payment_schedule',
            ['booking_id' => $booking_id, 'updated_at' => current_time('mysql')],
            ['id' => $schedule_id],
            ['%d', '%s'],
            ['%d']
        );
    }

    /**
     * Cuando el pedido se cancela o reembolsa, cancelar pagos programados pendientes.
     */
    public static function handle_order_status_for_deposits(int $order_id, string $old_status, string $new_status, \WC_Order $order): void
    {
        if (!in_array($new_status, ['cancelled', 'refunded'], true)) {
            return;
        }

        if ($order->get_meta('_apm_is_staged_payment') !== 'yes') {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'apm_payment_schedule';

        $wpdb->update(
            $table,
            ['status' => 'cancelled', 'updated_at' => current_time('mysql')],
            ['order_id' => $order_id, 'status' => 'pending'],
            ['%s', '%s'],
            ['%d', '%s']
        );
    }

    // ─── Helpers internos ───────────────────────────────────────────────────

    /**
     * Obtener el product_id del primer producto con ap_booking_enabled en el pedido.
     */
    private static function get_ap_booking_product_id(\WC_Order $order): int
    {
        foreach ($order->get_items() as $item) {
            /** @var \WC_Order_Item_Product $item */
            if (!method_exists($item, 'get_product')) {
                continue;
            }
            /** @var \WC_Product|false $product */
            $product = $item->get_product();
            if ($product && get_post_meta($product->get_id(), 'ap_booking_enabled', true)) {
                return (int) $product->get_id();
            }
        }
        return 0;
    }

    /**
     * Obtener la fecha de check-in del primer item con datos Ap_Booking en el pedido.
     */
    private static function get_checkin_from_order(\WC_Order $order): string
    {
        foreach ($order->get_items() as $item) {
            $checkin = $item->get_meta('ap_checkin');
            if ($checkin) {
                return sanitize_text_field($checkin);
            }
        }
        return '';
    }

    /**
     * Asegurar que _apm_check_in_date esté relleno (cuando CheckoutModifier no lo hizo).
     */
    private static function ensure_checkin_date(\WC_Order $order): void
    {
        if ($order->get_meta('_apm_check_in_date')) {
            return;
        }
        $checkin = self::get_checkin_from_order($order);
        if ($checkin) {
            $order->update_meta_data('_apm_check_in_date', $checkin);
            $order->save();
        }
    }

    /**
     * Obtener el Ap_Booking id asociado al pedido (busca en metadatos de items).
     */
    private static function get_ap_booking_id_from_order(\WC_Order $order): int
    {
        foreach ($order->get_items() as $item) {
            $bid = (int) $item->get_meta('ap_booking_id');
            if ($bid > 0) {
                return $bid;
            }
        }
        return 0;
    }
}
