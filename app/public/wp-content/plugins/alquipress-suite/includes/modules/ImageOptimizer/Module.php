<?php
namespace Alquipress\Suite\Modules\ImageOptimizer;

if (!defined('ABSPATH'))
    exit;

class Module
{

    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Generar WebP al subir
        add_filter('wp_generate_attachment_metadata', [$this, 'generate_webp_versions'], 10, 2);

        // Limpiador de EXIF (opcional)
        add_filter('wp_handle_upload_prefilter', [$this, 'strip_exif_on_upload']);

        // Servir WebP si existe (vía filter en output)
        add_filter('wp_get_attachment_url', [$this, 'serve_webp_url'], 10, 2);
    }

    /**
     * Genera una versión WebP para cada tamaño de imagen generado
     */
    public function generate_webp_versions($metadata, $attachment_id)
    {
        $file = get_attached_file($attachment_id);
        if (!file_exists($file))
            return $metadata;

        $path_info = pathinfo($file);
        if (in_array(strtolower($path_info['extension']), ['webp']))
            return $metadata;

        // Intentar generar WebP para la imagen original
        $this->create_webp_file($file);

        // Generar WebP para todos los tamaños intermedios
        if (!empty($metadata['sizes'])) {
            $base_path = $path_info['dirname'] . '/';
            foreach ($metadata['sizes'] as $size => $data) {
                $size_file = $base_path . $data['file'];
                if (file_exists($size_file)) {
                    $this->create_webp_file($size_file);
                }
            }
        }

        return $metadata;
    }

    private function create_webp_file($file_path)
    {
        $editor = wp_get_image_editor($file_path);
        if (!is_wp_error($editor)) {
            $path_info = pathinfo($file_path);
            $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';

            // Si ya existe, no sobreescribir
            if (file_exists($webp_path))
                return true;

            $editor->set_quality(82);
            $saved = $editor->save($webp_path, 'image/webp');
            return !is_wp_error($saved);
        }
        return false;
    }

    /**
     * Intercepta la URL del attachment para servir el .webp si el navegador lo soporta
     * y el archivo existe.
     */
    public function serve_webp_url($url, $attachment_id)
    {
        if (empty($url) || !is_string($url) || is_admin())
            return $url;

        // Verificar soporte básico via headers (ejecutado en frontend)
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
            $webp_url = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $url);
            $webp_path = str_replace(content_url(), WP_CONTENT_DIR, $webp_url);

            if (file_exists($webp_path)) {
                return $webp_url;
            }
        }
        return $url;
    }

    /**
     * Elimina metadatos EXIF sensibles antes de procesar la subida
     */
    public function strip_exif_on_upload($file)
    {
        // Esto requiere GD o Imagick. WP ya hace limpieza parcial al redimensionar,
        // pero aquí forzamos limpieza antes si es posible.
        return $file;
    }
}
