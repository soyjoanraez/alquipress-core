<?php
/**
 * Template para Galería de Propiedad
 */

if (!defined('ABSPATH')) {
    exit;
}

// Solo en single product
if (!is_singular('product')) {
    return;
}

global $product;
$product_id = $product->get_id();

// Obtener galería ACF
$gallery = get_field('galeria_fotos', $product_id);

// Si no hay galería ACF, usar galería de WooCommerce
if (empty($gallery)) {
    $attachment_ids = $product->get_gallery_image_ids();
    if (!empty($attachment_ids)) {
        $gallery = [];
        foreach ($attachment_ids as $attachment_id) {
            $gallery[] = [
                'ID' => $attachment_id,
                'url' => wp_get_attachment_image_url($attachment_id, 'large'),
                'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true)
            ];
        }
    }
}

// Si aún no hay imágenes, usar imagen destacada
if (empty($gallery)) {
    $image_id = $product->get_image_id();
    if ($image_id) {
        $gallery[] = [
            'ID' => $image_id,
            'url' => wp_get_attachment_image_url($image_id, 'large'),
            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
        ];
    }
}

// Fallback final: Si sigue vacío, usar la foto "fija" por defecto
if (empty($gallery)) {
    // Usamos una imagen de stock premium de Alquipress como fallback
    $default_image_url = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80';
    $gallery[] = [
        'ID' => 0,
        'url' => $default_image_url,
        'alt' => __('Propiedad Alquipress', 'alquipress-theme')
    ];
}

$layout = isset($attributes['layout']) ? $attributes['layout'] : 'main-plus-grid';
$show_video = isset($attributes['showVideoTour']) ? $attributes['showVideoTour'] : false;
$video_url = isset($attributes['videoTourUrl']) ? esc_url($attributes['videoTourUrl']) : '';

$main_image = $gallery[0];
$thumbnails = array_slice($gallery, 1, 8); // Máximo 8 thumbnails
$total_images = count($gallery);

$block_id = 'alq-property-gallery-' . uniqid();
?>

<div class="alq-property-gallery alq-property-gallery--<?php echo esc_attr($layout); ?>" id="<?php echo esc_attr($block_id); ?>">
    <div class="alq-property-gallery-main">
        <?php if (is_array($main_image) && isset($main_image['url'])): ?>
            <a href="<?php echo esc_url($main_image['url']); ?>" class="alq-property-gallery-main-link" data-glightbox="gallery-<?php echo esc_attr($block_id); ?>">
                <img 
                    src="<?php echo esc_url($main_image['url']); ?>" 
                    alt="<?php echo esc_attr($main_image['alt'] ?? get_the_title($product_id)); ?>"
                    class="alq-property-gallery-main-image"
                    loading="eager"
                >
            </a>
        <?php elseif (is_numeric($main_image)): ?>
            <a href="<?php echo esc_url(wp_get_attachment_image_url($main_image, 'full')); ?>" class="alq-property-gallery-main-link" data-glightbox="gallery-<?php echo esc_attr($block_id); ?>">
                <?php echo wp_get_attachment_image($main_image, 'large', false, ['class' => 'alq-property-gallery-main-image', 'loading' => 'eager']); ?>
            </a>
        <?php endif; ?>
        
        <div class="alq-property-gallery-controls">
            <button class="alq-property-gallery-prev" aria-label="<?php esc_attr_e('Imagen anterior', 'alquipress-theme'); ?>">←</button>
            <button class="alq-property-gallery-next" aria-label="<?php esc_attr_e('Imagen siguiente', 'alquipress-theme'); ?>">→</button>
        </div>
        
        <?php if ($total_images > 1): ?>
            <div class="alq-property-gallery-count">
                <a href="#" class="alq-property-gallery-view-all" data-glightbox="gallery-<?php echo esc_attr($block_id); ?>">
                    🔍 <?php printf(esc_html__('Ver todas (%d fotos)', 'alquipress-theme'), $total_images); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ($show_video && !empty($video_url)): ?>
            <a href="<?php echo esc_url($video_url); ?>" class="alq-property-gallery-video-tour" data-glightbox="gallery-<?php echo esc_attr($block_id); ?>">
                🎥 <?php esc_html_e('Tour Virtual', 'alquipress-theme'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($thumbnails)): ?>
        <div class="alq-property-gallery-thumbnails">
            <?php foreach ($thumbnails as $thumb): ?>
                <?php
                $thumb_url = is_array($thumb) && isset($thumb['url']) ? $thumb['url'] : (is_numeric($thumb) ? wp_get_attachment_image_url($thumb, 'medium') : '');
                $thumb_alt = is_array($thumb) && isset($thumb['alt']) ? $thumb['alt'] : (is_numeric($thumb) ? get_post_meta($thumb, '_wp_attachment_image_alt', true) : '');
                ?>
                <?php if ($thumb_url): ?>
                    <a href="<?php echo esc_url($thumb_url); ?>" class="alq-property-gallery-thumbnail" data-glightbox="gallery-<?php echo esc_attr($block_id); ?>">
                        <img 
                            src="<?php echo esc_url($thumb_url); ?>" 
                            alt="<?php echo esc_attr($thumb_alt); ?>"
                            loading="lazy"
                        >
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Encolar GLightbox
wp_enqueue_style('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css', [], '3.2.0');
wp_enqueue_script('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', [], '3.2.0', true);
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof GLightbox !== 'undefined') {
        const lightbox = GLightbox({
            selector: '[data-glightbox="gallery-<?php echo esc_js($block_id); ?>"]',
            touchNavigation: true,
            loop: true,
            autoplayVideos: true
        });
    }
});
</script>
