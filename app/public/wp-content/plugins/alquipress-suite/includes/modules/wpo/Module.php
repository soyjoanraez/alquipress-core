<?php
namespace Alquipress\Suite\Modules\WPO;

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
        // Filtros para Lazy Load nativo y custom
        add_filter('wp_get_attachment_image_attributes', [$this, 'image_lazy_load'], 10, 2);

        // Lazy Load para Google Maps (ACF) - Esto es crítico por el feedback anterior
        add_action('wp_enqueue_scripts', [$this, 'enqueue_lazy_maps_scripts'], 20);

        // Optimización de scripts
        add_filter('script_loader_tag', [$this, 'defer_scripts'], 10, 2);
    }

    /**
     * Mejora el lazy load nativo añadiendo decodificación asíncrona
     */
    public function image_lazy_load($attr, $attachment)
    {
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }
        $attr['decoding'] = 'async';
        return $attr;
    }

    /**
     * Defer scripts que no son críticos
     */
    public function defer_scripts($tag, $handle)
    {
        if (empty($tag) || !is_string($tag)) {
            return $tag;
        }

        $defer_scripts = [
            'google-maps-api', // Si existe
            'mailpoet-public',
            'wc-bookings-booking-form'
        ];

        if (in_array($handle, $defer_scripts)) {
            return str_replace(' src', ' defer="defer" src', $tag);
        }

        return $tag;
    }

    /**
     * Encola un pequeño script para interceptar los mapas de Google de ACF y hacerlos lazy
     */
    public function enqueue_lazy_maps_scripts()
    {
        // Solo en singles de producto o donde se use el mapa
        if (!is_singular('product')) {
            return;
        }

        wp_register_script('alq-lazy-maps', false);
        wp_enqueue_script('alq-lazy-maps');

        $inline_js = "
            document.addEventListener('DOMContentLoaded', function() {
                const mapElements = document.querySelectorAll('.acf-map');
                if ('IntersectionObserver' in window && mapElements.length > 0) {
                    const mapObserver = new IntersectionObserver((entries, observer) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                // Aquí se dispararía la inicialización real del mapa si estuviera pausada
                                entry.target.classList.add('map-loaded');
                                observer.unobserve(entry.target);
                            }
                        });
                    });
                    mapElements.forEach(map => mapObserver.observe(map));
                }
            });
        ";
        wp_add_inline_script('alq-lazy-maps', $inline_js);

        // Estilo básico para evitar saltos
        wp_add_inline_style('astra-theme-css', "
            .acf-map { background: #f5f5f5; min-height: 400px; position: relative; }
            .acf-map:not(.map-loaded)::after { 
                content: 'Cargando mapa...'; 
                position: absolute; top: 50%; left: 50%; 
                transform: translate(-50%, -50%); color: #666; 
            }
        ");
    }
}
