<?php
/**
 * Registro centralizado de bloques Gutenberg custom para ALQUIPRESS
 * 
 * @package Alquipress Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar todos los bloques custom
 */
function alquipress_register_blocks() {
    // Verificar que Gutenberg esté disponible
    if (!function_exists('register_block_type')) {
        return;
    }

    $blocks = [
        'hero-search',
        'property-grid',
        'property-filters',
        'property-card',
        'property-gallery',
        'property-specs',
        'booking-widget',
        'availability-calendar',
        'my-bookings',
        'house-guide'
    ];
    
    foreach ($blocks as $block) {
        $block_path = get_stylesheet_directory() . "/blocks/{$block}";
        
        // Solo registrar si el directorio existe
        if (file_exists($block_path)) {
            register_block_type($block_path);
        }
    }
}
add_action('init', 'alquipress_register_blocks');

/**
 * Encolar assets globales de bloques
 */
function alquipress_enqueue_block_assets() {
    // CSS global de bloques
    wp_enqueue_style(
        'alquipress-blocks',
        get_stylesheet_directory_uri() . '/assets/css/blocks.css',
        [],
        wp_get_theme()->get('Version')
    );
    
    // JavaScript para hero-search
    if (has_block('alquipress/hero-search')) {
        wp_enqueue_script(
            'alquipress-hero-search',
            get_stylesheet_directory_uri() . '/assets/js/hero-search.js',
            [],
            wp_get_theme()->get('Version'),
            true
        );
        
        wp_localize_script('alquipress-hero-search', 'alquipressHeroSearch', [
            'restUrl' => rest_url('alquipress/v1/'),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
    }
    
    // JavaScript para property-filters
    if (has_block('alquipress/property-filters')) {
        wp_enqueue_script(
            'alquipress-property-filters',
            get_stylesheet_directory_uri() . '/assets/js/property-filters.js',
            [],
            wp_get_theme()->get('Version'),
            true
        );
    }
    
    // JavaScript para booking-widget
    if (has_block('alquipress/booking-widget')) {
        wp_enqueue_script(
            'alquipress-booking-widget',
            get_stylesheet_directory_uri() . '/assets/js/booking-widget.js',
            [],
            wp_get_theme()->get('Version'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'alquipress_enqueue_block_assets');

/**
 * Encolar assets del editor para bloques
 */
function alquipress_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'alquipress-blocks-editor',
        get_stylesheet_directory_uri() . '/assets/js/blocks-editor.js',
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'],
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('enqueue_block_editor_assets', 'alquipress_enqueue_block_editor_assets');
