<?php
/**
 * Funciones helper para bloques de ALQUIPRESS
 * 
 * @package Alquipress Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener ubicaciones (poblaciones) para autocompletado
 * 
 * @param string $search Término de búsqueda
 * @return array Array de términos con nombre y slug
 */
function alquipress_get_locations($search = '') {
    $args = [
        'taxonomy' => 'poblacion',
        'hide_empty' => false,
    ];
    
    if (!empty($search)) {
        $args['name__like'] = $search;
    }
    
    $terms = get_terms($args);
    
    if (is_wp_error($terms)) {
        return [];
    }
    
    $locations = [];
    foreach ($terms as $term) {
        $locations[] = [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'count' => $term->count
        ];
    }
    
    return $locations;
}

/**
 * Obtener características (amenities) con iconos
 * 
 * @return array Array de términos con iconos
 */
function alquipress_get_characteristics() {
    $terms = get_terms([
        'taxonomy' => 'caracteristicas',
        'hide_empty' => false,
    ]);
    
    if (is_wp_error($terms)) {
        return [];
    }
    
    $characteristics = [];
    foreach ($terms as $term) {
        $icon = get_field('icono_clase', 'caracteristicas_' . $term->term_id);
        $characteristics[] = [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'icon' => $icon ?: 'fa-circle',
            'count' => $term->count
        ];
    }
    
    return $characteristics;
}

/**
 * Obtener propiedades filtradas
 * 
 * @param array $filters Array de filtros
 * @return WP_Query Query de propiedades
 */
function alquipress_get_filtered_properties($filters = []) {
    $args = [
        'post_type' => 'product',
        'posts_per_page' => isset($filters['per_page']) ? absint($filters['per_page']) : 12,
        'post_status' => 'publish',
        'orderby' => isset($filters['orderby']) ? sanitize_key($filters['orderby']) : 'date',
        'order' => isset($filters['order']) ? strtoupper($filters['order']) : 'DESC',
    ];
    
    // Tax query
    $tax_query = [];
    
    if (!empty($filters['poblacion'])) {
        $tax_query[] = [
            'taxonomy' => 'poblacion',
            'field' => 'slug',
            'terms' => is_array($filters['poblacion']) ? $filters['poblacion'] : [$filters['poblacion']]
        ];
    }
    
    if (!empty($filters['zona'])) {
        $tax_query[] = [
            'taxonomy' => 'zona',
            'field' => 'slug',
            'terms' => is_array($filters['zona']) ? $filters['zona'] : [$filters['zona']]
        ];
    }
    
    if (!empty($filters['caracteristicas'])) {
        $tax_query[] = [
            'taxonomy' => 'caracteristicas',
            'field' => 'term_id',
            'terms' => is_array($filters['caracteristicas']) ? array_map('absint', $filters['caracteristicas']) : [absint($filters['caracteristicas'])],
            'operator' => 'AND'
        ];
    }
    
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    // Meta query
    $meta_query = [];
    
    if (!empty($filters['precio_min'])) {
        $meta_query[] = [
            'key' => '_price',
            'value' => absint($filters['precio_min']),
            'compare' => '>=',
            'type' => 'NUMERIC'
        ];
    }
    
    if (!empty($filters['precio_max'])) {
        $meta_query[] = [
            'key' => '_price',
            'value' => absint($filters['precio_max']),
            'compare' => '<=',
            'type' => 'NUMERIC'
        ];
    }
    
    if (!empty($filters['habitaciones_min'])) {
        $meta_query[] = [
            'key' => 'numero_habitaciones',
            'value' => absint($filters['habitaciones_min']),
            'compare' => '>=',
            'type' => 'NUMERIC'
        ];
    }
    
    if (!empty($filters['banos_min'])) {
        $meta_query[] = [
            'key' => 'numero_banos',
            'value' => absint($filters['banos_min']),
            'compare' => '>=',
            'type' => 'NUMERIC'
        ];
    }
    
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
    
    // Búsqueda por texto
    if (!empty($filters['search'])) {
        $args['s'] = sanitize_text_field($filters['search']);
    }
    
    return new WP_Query($args);
}

/**
 * Obtener datos de una propiedad para mostrar en tarjeta
 * 
 * @param int $product_id ID del producto
 * @return array Datos de la propiedad
 */
function alquipress_get_property_data($product_id) {
    $product = wc_get_product($product_id);
    
    if (!$product) {
        return null;
    }
    
    // Obtener taxonomías
    $poblacion_terms = get_the_terms($product_id, 'poblacion');
    $zona_terms = get_the_terms($product_id, 'zona');
    
    // Obtener campos ACF
    $habitaciones = get_field('numero_habitaciones', $product_id) ?: 0;
    $banos = get_field('numero_banos', $product_id) ?: 0;
    $capacidad = get_field('capacidad_maxima', $product_id) ?: 0;
    $gallery = get_field('galeria_fotos', $product_id);
    
    // Imagen principal
    $image_id = $product->get_image_id();
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : wc_placeholder_img_src('large');
    
    // Si hay galería ACF, usar primera imagen
    if ($gallery && is_array($gallery) && !empty($gallery)) {
        $first_image = $gallery[0];
        if (is_array($first_image) && isset($first_image['ID'])) {
            $image_url = wp_get_attachment_image_url($first_image['ID'], 'large');
        } elseif (is_numeric($first_image)) {
            $image_url = wp_get_attachment_image_url($first_image, 'large');
        }
    }
    
    return [
        'id' => $product_id,
        'title' => get_the_title($product_id),
        'permalink' => get_permalink($product_id),
        'image' => $image_url,
        'gallery' => $gallery,
        'price' => $product->get_price(),
        'price_html' => $product->get_price_html(),
        'poblacion' => $poblacion_terms && !is_wp_error($poblacion_terms) && !empty($poblacion_terms) ? $poblacion_terms[0]->name : '',
        'zona' => $zona_terms && !is_wp_error($zona_terms) && !empty($zona_terms) ? $zona_terms[0]->name : '',
        'habitaciones' => $habitaciones,
        'banos' => $banos,
        'capacidad' => $capacidad,
        'licencia' => get_field('licencia_turistica', $product_id),
    ];
}

/**
 * Verificar disponibilidad de fechas para WC Bookings
 * 
 * @param int $product_id ID del producto
 * @param string $checkin Fecha check-in (Y-m-d)
 * @param string $checkout Fecha check-out (Y-m-d)
 * @return bool|WP_Error True si disponible, false o WP_Error si no
 */
function alquipress_check_availability($product_id, $checkin, $checkout) {
    if (!class_exists('WC_Bookings')) {
        return new WP_Error('no_bookings', __('WC Bookings no está activo', 'alquipress-theme'));
    }
    
    $product = wc_get_product($product_id);
    
    if (!$product || !$product->is_type('booking')) {
        return new WP_Error('not_booking', __('El producto no es un booking', 'alquipress-theme'));
    }
    
    // Convertir fechas a timestamps
    $checkin_timestamp = strtotime($checkin);
    $checkout_timestamp = strtotime($checkout);
    
    if ($checkin_timestamp === false || $checkout_timestamp === false) {
        return new WP_Error('invalid_dates', __('Fechas inválidas', 'alquipress-theme'));
    }
    
    if ($checkin_timestamp >= $checkout_timestamp) {
        return new WP_Error('invalid_range', __('La fecha de salida debe ser posterior a la de entrada', 'alquipress-theme'));
    }
    
    // Verificar disponibilidad usando WC Bookings
    $booking_form = new WC_Booking_Form($product);
    $available_blocks = $booking_form->get_available_blocks([
        'start' => $checkin_timestamp,
        'end' => $checkout_timestamp,
    ]);
    
    return !empty($available_blocks);
}

/**
 * Calcular precio total de reserva
 * 
 * @param int $product_id ID del producto
 * @param string $checkin Fecha check-in
 * @param string $checkout Fecha check-out
 * @param int $guests Número de huéspedes
 * @return array Desglose de precios
 */
function alquipress_calculate_booking_price($product_id, $checkin, $checkout, $guests = 1) {
    $product = wc_get_product($product_id);
    
    if (!$product) {
        return null;
    }
    
    $checkin_timestamp = strtotime($checkin);
    $checkout_timestamp = strtotime($checkout);
    $nights = max(1, ($checkout_timestamp - $checkin_timestamp) / DAY_IN_SECONDS);
    
    $base_price = floatval($product->get_price());
    $subtotal = $base_price * $nights;
    
    // Coste de limpieza y lavandería (post meta)
    $cleaning_fee = floatval(get_post_meta($product_id, '_cleaning_fee', true)) ?: 0;
    $laundry_fee = floatval(get_post_meta($product_id, '_laundry_fee', true)) ?: 0;
    
    // Total antes de depósito
    $total = $subtotal + $cleaning_fee + $laundry_fee;
    
    // Depósito (40% por defecto, o meta del producto)
    $deposit_percentage = floatval(get_post_meta($product_id, '_deposit_percentage', true)) ?: 40;
    $deposit = ($total * $deposit_percentage) / 100;
    $balance = $total - $deposit;
    
    return [
        'nights' => $nights,
        'base_price' => $base_price,
        'subtotal' => $subtotal,
        'cleaning_fee' => $cleaning_fee,
        'laundry_fee' => $laundry_fee,
        'total' => $total,
        'deposit' => $deposit,
        'balance' => $balance,
        'deposit_percentage' => $deposit_percentage,
    ];
}

/**
 * Añadir fees de limpieza y lavandería al carrito para productos booking
 */
function alquipress_add_booking_fees_to_cart($cart)
{
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    $total_cleaning = 0;
    $total_laundry = 0;

    foreach ($cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        if (!$product || $product->get_type() !== 'booking') {
            continue;
        }
        $product_id = $product->get_id();
        $total_cleaning += floatval(get_post_meta($product_id, '_cleaning_fee', true)) ?: 0;
        $total_laundry += floatval(get_post_meta($product_id, '_laundry_fee', true)) ?: 0;
    }

    if ($total_cleaning > 0) {
        $cart->add_fee(__('Limpieza', 'alquipress-theme'), $total_cleaning, false);
    }
    if ($total_laundry > 0) {
        $cart->add_fee(__('Lavandería', 'alquipress-theme'), $total_laundry, false);
    }
}
add_action('woocommerce_cart_calculate_fees', 'alquipress_add_booking_fees_to_cart');
