<?php
/**
 * Alquipress Child Theme functions and definitions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue Parent Styles
 */
function alquipress_enqueue_styles()
{
    wp_enqueue_style(
        'alquipress-fonts',
        'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap',
        [],
        null
    );
    wp_enqueue_style('astra-parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('alquipress-child-style', get_stylesheet_directory_uri() . '/style.css', ['astra-parent-style']);
}
add_action('wp_enqueue_scripts', 'alquipress_enqueue_styles');

/**
 * Opcional: Personalizaciones específicas del tema aquí.
 * Nota: La lógica de negocio está en el plugin 'ALQUIPRESS Core', no aquí.
 * Este archivo es solo para aspectos visuales/presentación.
 */

/**
 * Cargar archivos de bloques (si existen)
 * Evita errores fatales cuando el child theme no incluye estos ficheros.
 */
$alquipress_inc_files = [
    get_stylesheet_directory() . '/inc/blocks-register.php',
    get_stylesheet_directory() . '/inc/block-helpers.php',
    get_stylesheet_directory() . '/inc/rest-api.php',
];

foreach ($alquipress_inc_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    } else {
        // Log suave para diagnosticar sin romper la web.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[alquipress-theme] Missing include: ' . $file);
        }
    }
}
