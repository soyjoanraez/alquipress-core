<?php
/**
 * Template para Mis Reservas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Solo para usuarios logueados
if (!is_user_logged_in()) {
    echo '<div class="alq-my-bookings-login">';
    echo '<p>' . esc_html__('Debes iniciar sesión para ver tus reservas.', 'alquipress-theme') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="button">' . esc_html__('Iniciar Sesión', 'alquipress-theme') . '</a>';
    echo '</div>';
    return;
}

$current_user_id = get_current_user_id();
$show_upcoming = isset($attributes['showUpcoming']) ? $attributes['showUpcoming'] : true;
$show_history = isset($attributes['showHistory']) ? $attributes['showHistory'] : true;

// Obtener reservas del usuario
$upcoming_bookings = [];
$past_bookings = [];

// Usar pedidos WooCommerce vinculados al usuario; el motor Ap_Booking utiliza metas ap_checkin/ap_checkout.
$orders = wc_get_orders([
    'customer_id' => $current_user_id,
    'status'      => ['completed', 'processing', 'on-hold'],
    'limit'       => -1,
]);

foreach ($orders as $order) {
    $checkin = $order->get_meta('ap_checkin');
    $check_ts = $checkin ? strtotime($checkin) : $order->get_date_created()->getTimestamp();
    if ($check_ts >= current_time('timestamp')) {
        $upcoming_bookings[] = $order;
    } else {
        $past_bookings[] = $order;
    }
}
?>

<div class="alq-my-bookings">
    <h2 class="alq-my-bookings-title"><?php esc_html_e('Mis Reservas', 'alquipress-theme'); ?></h2>
    
    <?php if ($show_upcoming && !empty($upcoming_bookings)): ?>
        <div class="alq-my-bookings-section">
            <h3 class="alq-my-bookings-section-title"><?php esc_html_e('Próximas Reservas', 'alquipress-theme'); ?></h3>
            <div class="alq-my-bookings-list">
                <?php foreach ($upcoming_bookings as $order): ?>
                    <div class="alq-my-bookings-item">
                        <div class="alq-my-bookings-item-content">
                            <h4><?php printf(esc_html__('Reserva #%s', 'alquipress-theme'), $order->get_order_number()); ?></h4>
                            <div class="alq-my-bookings-item-dates">
                                <?php
                                $checkin = $order->get_meta('ap_checkin');
                                $checkout = $order->get_meta('ap_checkout');
                                if ($checkin && $checkout) :
                                ?>
                                    <span>📅 <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($checkin))); ?></span>
                                    <span>→</span>
                                    <span><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($checkout))); ?></span>
                                <?php else : ?>
                                    <span>📅 <?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format'))); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="alq-my-bookings-item-status">
                                <span class="alq-booking-status alq-booking-status--<?php echo esc_attr($order->get_status()); ?>">
                                    <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($show_upcoming): ?>
        <div class="alq-my-bookings-empty">
            <p><?php esc_html_e('No tienes reservas próximas.', 'alquipress-theme'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($show_history && !empty($past_bookings)): ?>
        <div class="alq-my-bookings-section">
            <h3 class="alq-my-bookings-section-title"><?php esc_html_e('Historial', 'alquipress-theme'); ?></h3>
            <div class="alq-my-bookings-list">
                <?php foreach ($past_bookings as $order): ?>
                    <div class="alq-my-bookings-item alq-my-bookings-item--past">
                        <div class="alq-my-bookings-item-content">
                            <h4><?php printf(esc_html__('Reserva #%s', 'alquipress-theme'), $order->get_order_number()); ?></h4>
                            <div class="alq-my-bookings-item-dates">
                                <?php
                                $checkin = $order->get_meta('ap_checkin');
                                $checkout = $order->get_meta('ap_checkout');
                                if ($checkin && $checkout) :
                                ?>
                                    <span>📅 <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($checkin))); ?></span>
                                    <span>→</span>
                                    <span><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($checkout))); ?></span>
                                <?php else : ?>
                                    <span>📅 <?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format'))); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
