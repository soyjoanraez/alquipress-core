<?php
/**
 * Template para Calendario de Disponibilidad
 */

if (!defined('ABSPATH')) {
    exit;
}

// Solo en single product
if (!is_singular('product')) {
    return;
}

// El calendario de reserva y precios ahora lo gestiona el widget propio Ap_Booking.
// Este bloque actúa como contenedor/encabezado opcional.
?>

<div class="alq-availability-calendar">
    <h3 class="alq-availability-calendar-title"><?php esc_html_e('Disponibilidad y precios', 'alquipress-theme'); ?></h3>
    <p class="alq-availability-calendar-simple">
        <?php esc_html_e('El calendario interactivo de reservas se muestra mediante el motor propio Ap_Booking.', 'alquipress-theme'); ?>
    </p>
</div>
