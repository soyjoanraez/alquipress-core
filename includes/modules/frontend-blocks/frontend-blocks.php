<?php
/**
 * Módulo: Frontend Blocks
 * Registra bloques de Gutenberg para AlquiPress integrados con Astra/Spectra
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Frontend_Blocks
{
    public function __construct()
    {
        add_action('init', [$this, 'register_blocks']);
    }

    public function register_blocks()
    {
        register_block_type(ALQUIPRESS_PATH . 'includes/modules/frontend-blocks/blocks/property-search', [
            'render_callback' => [$this, 'render_property_search_block']
        ]);
    }

    /**
     * Renderiza el bloque de búsqueda en el frontend
     */
    public function render_property_search_block($attributes)
    {
        $placeholder = $attributes['placeholder'] ?? __('¿Dónde quieres ir?', 'alquipress');
        $button_text = $attributes['buttonText'] ?? __('Buscar', 'alquipress');
        
        ob_start();
        ?>
        <div class="wp-block-alquipress-property-search">
            <form action="<?php echo esc_url(get_post_type_archive_link('propiedad')); ?>" method="get" class="ap-search-form" style="display:contents;">
                <div class="ap-search-field">
                    <label class="ap-search-label"><?php esc_html_e('Ubicación', 'alquipress'); ?></label>
                    <input type="text" name="location" class="ap-search-input" placeholder="<?php echo esc_attr($placeholder); ?>">
                </div>
                
                <div class="ap-search-field">
                    <label class="ap-search-label"><?php esc_html_e('Llegada - Salida', 'alquipress'); ?></label>
                    <input type="text" name="dates" class="ap-search-input" placeholder="<?php esc_attr_e('Selecciona fechas', 'alquipress'); ?>" readonly>
                </div>

                <div class="ap-search-field">
                    <label class="ap-search-label"><?php esc_html_e('Huéspedes', 'alquipress'); ?></label>
                    <input type="number" name="guests" class="ap-search-input" min="1" value="1">
                </div>

                <button type="submit" class="ap-search-button">
                    <?php echo esc_html($button_text); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Alquipress_Frontend_Blocks();
