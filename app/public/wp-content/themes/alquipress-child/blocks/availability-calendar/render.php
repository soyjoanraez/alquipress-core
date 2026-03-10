<?php
/**
 * Calendario de disponibilidad solo lectura – días ocupados/libres
 */
if (!defined('ABSPATH')) {
    exit;
}
if (!is_singular('product')) {
    echo '<p class="ap-child-avail-placeholder">' . esc_html__('Bloque para ficha de propiedad.', 'alquipress-child') . '</p>';
    return;
}
global $product;
$product_id = $product ? $product->get_id() : (int) get_the_ID();
$months_to_show = isset($attributes['monthsToShow']) ? max(1, min(12, (int) $attributes['monthsToShow'])) : 6;
$from = strtotime('first day of this month');
$to = strtotime('+' . $months_to_show . ' months', $from);

// Intentar usar el nuevo motor de reservas si está activo para esta propiedad.
$use_ap_engine = (bool) get_post_meta($product_id, 'ap_booking_enabled', true) && class_exists('Ap_Booking_Availability_Service');
$booked_ranges = [];

if ($use_ap_engine) {
    $calendar_matrix = Ap_Booking_Availability_Service::get_calendar_matrix($product_id, gmdate('Y-m-d', $from), gmdate('Y-m-d', $to));
    foreach ($calendar_matrix as $date_key => $info) {
        if ($info['status'] === 'booked') {
            $day_start = strtotime($date_key);
            $booked_ranges[] = ['start' => $day_start, 'end' => $day_start + DAY_IN_SECONDS];
        }
    }
} else {
    echo '<p class="ap-child-avail-placeholder">' . esc_html__('No hay motor de reservas activo para esta propiedad.', 'alquipress-child') . '</p>';
    return;
}

function alquipress_child_is_date_booked($ts, $ranges) {
    foreach ($ranges as $r) {
        if ($ts >= $r['start'] && $ts < $r['end']) {
            return true;
        }
    }
    return false;
}
$block_id = 'ap-child-avail-' . uniqid();
?>
<div class="ap-child-availability-calendar" id="<?php echo esc_attr($block_id); ?>">
    <h3 class="ap-child-availability-calendar-title"><?php esc_html_e('Disponibilidad', 'alquipress-child'); ?></h3>
    <div class="ap-child-availability-calendar-legend">
        <span class="ap-child-availability-legend-free"><?php esc_html_e('Libre', 'alquipress-child'); ?></span>
        <span class="ap-child-availability-legend-booked"><?php esc_html_e('Ocupado', 'alquipress-child'); ?></span>
    </div>
    <div class="ap-child-availability-calendar-months">
        <?php for ($m = 0; $m < $months_to_show; $m++) :
            $month_start = strtotime('+' . $m . ' months', $from);
            $month_name = date_i18n('F Y', $month_start);
            $days_in_month = (int) date('t', $month_start);
            $first_dow = (int) date('w', strtotime(date('Y-m-01', $month_start)));
            $first_dow = $first_dow === 0 ? 7 : $first_dow;
        ?>
            <div class="ap-child-availability-calendar-month">
                <h4 class="ap-child-availability-calendar-month-title"><?php echo esc_html($month_name); ?></h4>
                <div class="ap-child-availability-calendar-grid">
                    <span class="ap-child-availability-dow"><?php esc_html_e('L', 'alquipress-child'); ?></span>
                    <span class="ap-child-availability-dow"><?php esc_html_e('M', 'alquipress-child'); ?></span>
                    <span class="ap-child-availability-dow"><?php esc_html_e('X', 'alquipress-child'); ?></span>
                    <span class="ap-child-availability-dow"><?php esc_html_e('J', 'alquipress-child'); ?></span>
                    <span class="ap-child-availability-dow"><?php esc_html_e('V', 'alquipress-child'); ?></span>
                    <span class="ap-child-availability-dow"><?php esc_html_e('S', 'alquipress-child'); ?></span>
                    <span class="ap-child-availability-dow"><?php esc_html_e('D', 'alquipress-child'); ?></span>
                    <?php
                    for ($i = 1; $i < $first_dow; $i++) {
                        echo '<span class="ap-child-availability-day ap-child-availability-day--empty"></span>';
                    }
                    for ($d = 1; $d <= $days_in_month; $d++) {
                        $ts = strtotime(date('Y-m-' . sprintf('%02d', $d), $month_start));
                        $is_past = $ts < strtotime('today');
                        $booked = alquipress_child_is_date_booked($ts, $booked_ranges);
                        $class = 'ap-child-availability-day';
                        if ($is_past) {
                            $class .= ' ap-child-availability-day--past';
                        } elseif ($booked) {
                            $class .= ' ap-child-availability-day--booked';
                        } else {
                            $class .= ' ap-child-availability-day--free';
                        }
                        echo '<span class="' . esc_attr($class) . '" title="' . esc_attr(date('Y-m-d', $ts)) . '">' . esc_html($d) . '</span>';
                    }
                    ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>
</div>
