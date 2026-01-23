<?php
/**
 * Módulo: Booking Enforcer
 * 
 * Fuerza que todos los Inmuebles sean de tipo "Reservable" (Booking) 
 * y marcados como "Virtuales" de forma obligatoria.
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Booking_Enforcer
{

    public function __construct()
    {
        // 1. Establecer el tipo de producto por defecto a 'booking' en la interfaz
        add_filter('default_product_type', [$this, 'set_default_product_type']);

        // 2. Forzar que sean virtuales y reservables al guardar
        add_action('woocommerce_process_product_meta', [$this, 'force_booking_settings'], 999);

        // 3. Ocultar opciones innecesarias en el panel de datos del producto (Opcional, para simplificar UX)
        add_action('admin_footer', [$this, 'hide_non_booking_options']);

        // 4. Asegurar que al crear uno nuevo via script o manual se guarde correctamente
        add_action('wp_insert_post', [$this, 'ensure_type_on_creation'], 10, 3);
    }

    /**
     * Define 'booking' como el tipo de producto por defecto al entrar en "Nuevo Inmueble"
     */
    public function set_default_product_type($type)
    {
        return 'booking';
    }

    /**
     * Fuerza los metadatos de Virtual y Tipo de Producto al guardar
     */
    public function force_booking_settings($post_id)
    {
        // Forzar Virtual
        update_post_meta($post_id, '_virtual', 'yes');

        // Forzar Reservable (término de taxonomía)
        if (!has_term('booking', 'product_type', $post_id)) {
            wp_set_object_terms($post_id, 'booking', 'product_type');
        }

        // Deshabilitar "Descargable" por seguridad
        update_post_meta($post_id, '_downloadable', 'no');
    }

    /**
     * Asegura que el tipo sea correcto incluso antes de la primera edición manual
     */
    public function ensure_type_on_creation($post_id, $post, $update)
    {
        if ($post->post_type !== 'product' || $update)
            return;

        wp_set_object_terms($post_id, 'booking', 'product_type');
        update_post_meta($post_id, '_virtual', 'yes');
    }

    /**
     * Inyecta CSS/JS en el admin para que no se puedan desmarcar estas opciones
     * y se limpie la interfaz de lo que no usaremos (físicos, externos, etc)
     */
    public function hide_non_booking_options()
    {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product')
            return;
        ?>
        <style>
            /* Ocultar el checkbox de Virtual y Descargable (ya que los forzamos) */
            .show_if_simple.show_if_external {
                display: none !important;
            }

            label[for="_virtual"],
            input#_virtual,
            label[for="_downloadable"],
            input#_downloadable {
                display: none !important;
            }

            /* (Opcional) Si quieres que solo el tipo "Reserva" sea seleccionable */
            /* #product-type option:not([value="booking"]) { display: none; } */
        </style>
        <script>
            jQuery(document).ready(function ($) {
                // Asegurar que el checkbox de virtual esté siempre marcado visualmente si existiera
                $('#_virtual').prop('checked', true);

                // Si el tipo de producto cambia (por error), volver a booking
                /*
                $('#product-type').on('change', function() {
                    if($(this).val() !== 'booking') {
                        $(this).val('booking').change();
                    }
                });
                */
            });
        </script>
        <?php
    }
}

new Alquipress_Booking_Enforcer();
