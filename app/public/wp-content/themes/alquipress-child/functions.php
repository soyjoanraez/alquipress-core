<?php
/**
 * Alquipress Child Theme – Web de alquiler con bloques Gutenberg
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ALQUIPRESS_CHILD_VERSION', '1.0.0');

/**
 * Enqueue parent and child styles
 */
function alquipress_child_enqueue_styles() {
    wp_enqueue_style(
        'alquipress-theme-parent',
        get_template_directory_uri() . '/style.css',
        [],
        ALQUIPRESS_CHILD_VERSION
    );
    wp_enqueue_style(
        'alquipress-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['alquipress-theme-parent'],
        ALQUIPRESS_CHILD_VERSION
    );
}
add_action('wp_enqueue_scripts', 'alquipress_child_enqueue_styles');

/**
 * Enqueue parent block assets when parent blocks are used
 */
function alquipress_child_enqueue_block_assets() {
    $template_uri = get_template_directory_uri();
    $template_ver = wp_get_theme(get_template())->get('Version');
    if (file_exists(get_template_directory() . '/assets/css/blocks.css')) {
        wp_enqueue_style('alquipress-blocks', $template_uri . '/assets/css/blocks.css', [], $template_ver);
    }
    if (has_block('alquipress/hero-search')) {
        wp_enqueue_script('alquipress-hero-search', $template_uri . '/assets/js/hero-search.js', [], $template_ver, true);
        wp_localize_script('alquipress-hero-search', 'alquipressHeroSearch', [
            'restUrl' => rest_url('alquipress/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
    if (has_block('alquipress/property-filters')) {
        wp_enqueue_script('alquipress-property-filters', $template_uri . '/assets/js/property-filters.js', [], $template_ver, true);
    }
    if (has_block('alquipress/booking-widget')) {
        wp_enqueue_script('alquipress-booking-widget', $template_uri . '/assets/js/booking-widget.js', [], $template_ver, true);
    }
}
add_action('wp_enqueue_scripts', 'alquipress_child_enqueue_block_assets');

/**
 * Enqueue parent block editor assets
 */
function alquipress_child_enqueue_block_editor_assets() {
    $template_uri = get_template_directory_uri();
    $template_ver = wp_get_theme(get_template())->get('Version');
    wp_enqueue_script(
        'alquipress-blocks-editor',
        $template_uri . '/assets/js/blocks-editor.js',
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'],
        $template_ver,
        true
    );
}
add_action('enqueue_block_editor_assets', 'alquipress_child_enqueue_block_editor_assets');

/**
 * Load parent theme helpers and REST (do not load parent blocks-register: it uses get_stylesheet_directory and would look in child)
 */
require_once get_template_directory() . '/inc/block-helpers.php';
if (file_exists(get_template_directory() . '/inc/rest-api.php')) {
    require_once get_template_directory() . '/inc/rest-api.php';
}

/**
 * Register parent theme blocks from template directory, then child blocks
 */
function alquipress_child_register_blocks() {
    if (!function_exists('register_block_type')) {
        return;
    }
    $parent_blocks = [
        'hero-search', 'property-grid', 'property-filters', 'property-card',
        'property-gallery', 'property-specs', 'booking-widget', 'availability-calendar',
        'my-bookings', 'house-guide',
    ];
    $template = get_template_directory();
    foreach ($parent_blocks as $block) {
        $block_path = $template . '/blocks/' . $block;
        if (file_exists($block_path . '/block.json')) {
            register_block_type($block_path);
        }
    }
    $child_blocks = [
        'property-search',
        'property-grid',
        'booking-widget',
        'property-kpis',
        'availability-calendar',
        'owner-cta',
    ];
    foreach ($child_blocks as $block) {
        $block_path = get_stylesheet_directory() . '/blocks/' . $block;
        if (file_exists($block_path) && file_exists($block_path . '/block.json')) {
            register_block_type($block_path);
        }
    }
}
add_action('init', 'alquipress_child_register_blocks', 20);

/**
 * Register block category for Alquipress blocks
 */
function alquipress_child_block_categories($categories) {
    $exists = wp_list_pluck($categories, 'slug');
    if (!in_array('alquipress', $exists, true)) {
        $categories[] = [
            'slug' => 'alquipress',
            'title' => _x('Alquipress', 'Block category', 'alquipress-child'),
            'icon' => null,
            'description' => __('Bloques para alquiler vacacional.', 'alquipress-child'),
        ];
    }
    return $categories;
}
add_filter('block_categories_all', 'alquipress_child_block_categories', 10, 2);

/**
 * Register block pattern category and load patterns
 */
function alquipress_child_register_patterns() {
    if (!function_exists('register_block_pattern_category')) {
        return;
    }
    register_block_pattern_category('alquipress', [
        'label' => _x('Alquipress', 'Block pattern category', 'alquipress-child'),
        'description' => __('Patrones para la web de alquiler.', 'alquipress-child'),
    ]);
    $patterns = [
        'home-rental',
        'search-results',
        'single-property',
    ];
    foreach ($patterns as $name) {
        $file = get_stylesheet_directory() . '/patterns/' . $name . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
add_action('init', 'alquipress_child_register_patterns', 25);

/**
 * Helper: get property meta for blocks (centralized for child)
 */
function alquipress_child_get_property_meta($product_id) {
    $product = function_exists('wc_get_product') ? wc_get_product($product_id) : null;
    $price = $product ? $product->get_price() : '';
    $price_html = $product ? $product->get_price_html() : '';
    return [
        'habitaciones' => (int) get_field('numero_habitaciones', $product_id) ?: 0,
        'banos' => (int) get_field('numero_banos', $product_id) ?: 0,
        'plazas' => (int) get_field('capacidad_maxima', $product_id) ?: 0,
        'superficie' => get_field('superficie_m2', $product_id) ? get_field('superficie_m2', $product_id) : get_field('superficie', $product_id),
        'cleaning_fee' => (float) get_post_meta($product_id, '_cleaning_fee', true) ?: 0,
        'laundry_fee' => (float) get_post_meta($product_id, '_laundry_fee', true) ?: 0,
        'price' => $price,
        'price_html' => $price_html,
    ];
}
