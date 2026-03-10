<?php
/**
 * REST API Endpoints para bloques ALQUIPRESS
 * 
 * @package Alquipress Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar namespace REST API
 */
add_action('rest_api_init', function() {
    register_rest_route('alquipress/v1', '/locations', [
        'methods' => 'GET',
        'callback' => 'alquipress_rest_get_locations',
        'permission_callback' => '__return_true',
        'args' => [
            'search' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ]);
    
    register_rest_route('alquipress/v1', '/properties-filtered', [
        'methods' => 'GET',
        'callback' => 'alquipress_rest_get_filtered_properties',
        'permission_callback' => '__return_true',
        'args' => [
            'poblacion' => ['type' => 'string'],
            'zona' => ['type' => 'string'],
            'caracteristicas' => ['type' => 'array'],
            'precio_min' => ['type' => 'integer'],
            'precio_max' => ['type' => 'integer'],
            'habitaciones_min' => ['type' => 'integer'],
            'banos_min' => ['type' => 'integer'],
            'search' => ['type' => 'string'],
            'orderby' => ['type' => 'string'],
            'order' => ['type' => 'string'],
            'per_page' => ['type' => 'integer', 'default' => 12],
            'page' => ['type' => 'integer', 'default' => 1],
        ],
    ]);
    
    register_rest_route('alquipress/v1', '/availability-check', [
        'methods' => 'GET',
        'callback' => 'alquipress_rest_check_availability',
        'permission_callback' => '__return_true',
        'args' => [
            'product_id' => [
                'type' => 'integer',
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
            ],
            'checkin' => [
                'type' => 'string',
                'required' => true,
                'validate_callback' => function($param) {
                    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                },
            ],
            'checkout' => [
                'type' => 'string',
                'required' => true,
                'validate_callback' => function($param) {
                    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                },
            ],
        ],
    ]);
    
    register_rest_route('alquipress/v1', '/price-calculation', [
        'methods' => 'GET',
        'callback' => 'alquipress_rest_calculate_price',
        'permission_callback' => '__return_true',
        'args' => [
            'product_id' => [
                'type' => 'integer',
                'required' => true,
            ],
            'checkin' => [
                'type' => 'string',
                'required' => true,
            ],
            'checkout' => [
                'type' => 'string',
                'required' => true,
            ],
            'guests' => [
                'type' => 'integer',
                'default' => 1,
            ],
        ],
    ]);
});

/**
 * Endpoint: Obtener ubicaciones para autocompletado
 */
function alquipress_rest_get_locations($request) {
    $search = $request->get_param('search');
    $locations = alquipress_get_locations($search);
    
    return new WP_REST_Response($locations, 200);
}

/**
 * Endpoint: Obtener propiedades filtradas
 */
function alquipress_rest_get_filtered_properties($request) {
    $filters = [
        'poblacion' => $request->get_param('poblacion'),
        'zona' => $request->get_param('zona'),
        'caracteristicas' => $request->get_param('caracteristicas'),
        'precio_min' => $request->get_param('precio_min'),
        'precio_max' => $request->get_param('precio_max'),
        'habitaciones_min' => $request->get_param('habitaciones_min'),
        'banos_min' => $request->get_param('banos_min'),
        'search' => $request->get_param('search'),
        'orderby' => $request->get_param('orderby'),
        'order' => $request->get_param('order'),
        'per_page' => $request->get_param('per_page'),
    ];
    
    $query = alquipress_get_filtered_properties($filters);
    
    $properties = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $property_data = alquipress_get_property_data(get_the_ID());
            if ($property_data) {
                $properties[] = $property_data;
            }
        }
        wp_reset_postdata();
    }
    
    return new WP_REST_Response([
        'properties' => $properties,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages,
    ], 200);
}

/**
 * Endpoint: Verificar disponibilidad
 */
function alquipress_rest_check_availability($request) {
    $product_id = absint($request->get_param('product_id'));
    $checkin = sanitize_text_field($request->get_param('checkin'));
    $checkout = sanitize_text_field($request->get_param('checkout'));
    
    $available = alquipress_check_availability($product_id, $checkin, $checkout);
    
    if (is_wp_error($available)) {
        return new WP_Error(
            $available->get_error_code(),
            $available->get_error_message(),
            ['status' => 400]
        );
    }
    
    return new WP_REST_Response([
        'available' => $available,
        'checkin' => $checkin,
        'checkout' => $checkout,
    ], 200);
}

/**
 * Endpoint: Calcular precio
 */
function alquipress_rest_calculate_price($request) {
    $product_id = absint($request->get_param('product_id'));
    $checkin = sanitize_text_field($request->get_param('checkin'));
    $checkout = sanitize_text_field($request->get_param('checkout'));
    $guests = absint($request->get_param('guests')) ?: 1;
    
    $price_data = alquipress_calculate_booking_price($product_id, $checkin, $checkout, $guests);
    
    if (!$price_data) {
        return new WP_Error(
            'invalid_product',
            __('Producto no encontrado', 'alquipress-theme'),
            ['status' => 404]
        );
    }
    
    return new WP_REST_Response($price_data, 200);
}
