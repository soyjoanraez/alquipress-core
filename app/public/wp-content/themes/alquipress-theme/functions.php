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

/**
 * Añadir "/noche" al precio en el listado de la tienda (frontend).
 * No se aplica en el admin para no interferir con WooCommerce.
 */
add_filter('woocommerce_get_price_html', 'alquipress_append_noche_to_price', 10, 2);
function alquipress_append_noche_to_price($price_html, $product)
{
    if (is_admin() || empty($price_html)) {
        return $price_html;
    }
    // Solo en listados (shop/archive), no en la ficha individual del producto
    if (is_shop() || is_product_taxonomy() || (is_page() && !is_singular('product'))) {
        $price_html .= ' <span class="alquipress-per-night">/noche</span>';
    }
    return $price_html;
}

/**
 * Fallback: si _price está vacío, usar _regular_price.
 * Esto asegura que todas las propiedades muestren precio en la tienda.
 */
add_filter('woocommerce_product_get_price', 'alquipress_fallback_price', 10, 2);
function alquipress_fallback_price($price, $product)
{
    if ($price === '' || $price === null) {
        $regular = get_post_meta($product->get_id(), '_regular_price', true);
        if ($regular !== '' && $regular !== null) {
            return $regular;
        }
    }
    return $price;
}
