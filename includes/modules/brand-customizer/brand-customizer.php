<?php
/**
 * Module Name: Brand Customizer
 * Description: Personalización de marca, colores, tipografías y logo del Dashboard
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Brand_Customizer
{
    /**
     * Opciones por defecto
     */
    private static $defaults = [
        'company_name' => 'ALQUIPRESS',
        'company_logo' => '',
        'color_primary' => '#2271b1',
        'color_secondary' => '#0ea5e9',
        'color_success' => '#00a32a',
        'color_warning' => '#f0b849',
        'color_error' => '#dc3232',
        'font_primary' => 'system-ui',
        'font_headings' => 'system-ui',
    ];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_customizer_page'], 20);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_head', [$this, 'inject_custom_styles']);
        add_action('admin_head', [$this, 'load_custom_fonts']);
        add_action('login_head', [$this, 'inject_custom_styles']);
        add_action('login_head', [$this, 'load_custom_fonts']);
    }

    /**
     * Añadir página de personalización
     */
    public function add_customizer_page()
    {
        add_submenu_page(
            'alquipress-settings',
            'Personalización de Marca',
            'Personalización',
            'manage_options',
            'alquipress-brand-customizer',
            [$this, 'render_customizer_page']
        );
    }

    /**
     * Registrar settings
     */
    public function register_settings()
    {
        register_setting('alquipress_brand_customizer', 'alquipress_brand_settings', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default' => self::$defaults,
        ]);
    }

    /**
     * Sanitizar settings
     */
    public function sanitize_settings($input)
    {
        $sanitized = [];

        // Sanitizar nombre de empresa
        $sanitized['company_name'] = isset($input['company_name'])
            ? sanitize_text_field($input['company_name'])
            : self::$defaults['company_name'];

        // Sanitizar URL de logo
        $sanitized['company_logo'] = isset($input['company_logo'])
            ? esc_url_raw($input['company_logo'])
            : self::$defaults['company_logo'];

        // Sanitizar colores (validar formato hex)
        $color_fields = ['color_primary', 'color_secondary', 'color_success', 'color_warning', 'color_error'];
        foreach ($color_fields as $field) {
            if (isset($input[$field]) && preg_match('/^#[a-f0-9]{6}$/i', $input[$field])) {
                $sanitized[$field] = $input[$field];
            } else {
                $sanitized[$field] = self::$defaults[$field];
            }
        }

        // Sanitizar fuentes
        $sanitized['font_primary'] = isset($input['font_primary'])
            ? sanitize_text_field($input['font_primary'])
            : self::$defaults['font_primary'];

        $sanitized['font_headings'] = isset($input['font_headings'])
            ? sanitize_text_field($input['font_headings'])
            : self::$defaults['font_headings'];

        return $sanitized;
    }

    /**
     * Obtener settings con valores por defecto
     */
    public static function get_settings()
    {
        $settings = get_option('alquipress_brand_settings', self::$defaults);
        return wp_parse_args($settings, self::$defaults);
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets($hook)
    {
        if ($hook !== 'alquipress_page_alquipress-brand-customizer') {
            return;
        }

        // WordPress Color Picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // WordPress Media Uploader
        wp_enqueue_media();

        // Custom JS
        wp_add_inline_script('wp-color-picker', "
            jQuery(document).ready(function($) {
                // Color Pickers
                $('.color-picker').wpColorPicker({
                    change: function(event, ui) {
                        updatePreview();
                    }
                });

                // Media Uploader para Logo
                $('#upload-logo-button').on('click', function(e) {
                    e.preventDefault();
                    var mediaUploader = wp.media({
                        title: 'Seleccionar Logo',
                        button: { text: 'Usar este logo' },
                        multiple: false,
                        library: { type: 'image' }
                    });

                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#company_logo').val(attachment.url);
                        $('#logo-preview').attr('src', attachment.url).show();
                        $('#remove-logo-button').show();
                        updatePreview();
                    });

                    mediaUploader.open();
                });

                // Remover logo
                $('#remove-logo-button').on('click', function(e) {
                    e.preventDefault();
                    $('#company_logo').val('');
                    $('#logo-preview').hide();
                    $(this).hide();
                    updatePreview();
                });

                // Actualizar preview en tiempo real
                function updatePreview() {
                    var primaryColor = $('#color_primary').val();
                    var companyName = $('#company_name').val() || 'ALQUIPRESS';
                    var logoUrl = $('#company_logo').val();

                    // Actualizar preview de nombre
                    $('.preview-company-name').text(companyName);

                    // Actualizar preview de logo
                    if (logoUrl) {
                        $('.preview-logo').attr('src', logoUrl).show();
                    } else {
                        $('.preview-logo').hide();
                    }

                    // Actualizar colores en preview
                    $('.preview-box').css('background', primaryColor);
                }

                // Trigger inicial
                updatePreview();

                // Actualizar al cambiar inputs
                $('#company_name, #company_logo').on('input change', updatePreview);
            });
        ");
    }

    /**
     * Inyectar estilos personalizados en admin
     */
    public function inject_custom_styles()
    {
        $settings = self::get_settings();

        // Generar CSS dinámico
        $css = $this->generate_custom_css($settings);

        echo '<style id="alquipress-brand-custom-styles">' . $css . '</style>';
    }

    /**
     * Cargar fuentes personalizadas de Google Fonts
     */
    public function load_custom_fonts()
    {
        $settings = self::get_settings();
        $google_fonts = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins'];
        $fonts_to_load = [];

        // Verificar si se usan Google Fonts
        if (in_array($settings['font_primary'], $google_fonts)) {
            $fonts_to_load[] = str_replace(' ', '+', $settings['font_primary']) . ':400,500,600,700';
        }

        if (in_array($settings['font_headings'], $google_fonts) && $settings['font_headings'] !== $settings['font_primary']) {
            $fonts_to_load[] = str_replace(' ', '+', $settings['font_headings']) . ':400,600,700';
        }

        if (!empty($fonts_to_load)) {
            $fonts_url = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $fonts_to_load) . '&display=swap';
            echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
            echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
            echo '<link href="' . esc_url($fonts_url) . '" rel="stylesheet">';
        }
    }

    /**
     * Generar CSS personalizado
     */
    private function generate_custom_css($settings)
    {
        $css = "
        /* ALQUIPRESS Brand Customization */
        :root {
            --ap-primary: {$settings['color_primary']};
            --ap-primary-hover: " . $this->darken_color($settings['color_primary'], 10) . ";
            --ap-primary-light: " . $this->lighten_color($settings['color_primary'], 90) . ";
            --ap-primary-dark: " . $this->darken_color($settings['color_primary'], 20) . ";

            --ap-secondary: {$settings['color_secondary']};
            --ap-success: {$settings['color_success']};
            --ap-warning: {$settings['color_warning']};
            --ap-error: {$settings['color_error']};

            --ap-font-primary: {$settings['font_primary']}, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --ap-font-headings: {$settings['font_headings']}, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Aplicar fuentes personalizadas */
        .ap-wrap,
        .ap-card,
        .ap-button,
        .alquipress-reports-wrap,
        #alquipress-brand-customizer-form {
            font-family: var(--ap-font-primary);
        }

        .ap-page-header h1,
        .ap-card h2,
        .ap-card h3,
        .alquipress-reports-wrap h1,
        .alquipress-reports-wrap h2 {
            font-family: var(--ap-font-headings);
        }

        /* Colores de éxito actualizados */
        .ap-badge--success {
            background: " . $this->lighten_color($settings['color_success'], 85) . ";
            color: " . $this->darken_color($settings['color_success'], 30) . ";
        }

        /* Colores de advertencia actualizados */
        .ap-badge--warning {
            background: " . $this->lighten_color($settings['color_warning'], 85) . ";
            color: " . $this->darken_color($settings['color_warning'], 40) . ";
        }

        /* Colores de error actualizados */
        .ap-badge--error {
            background: " . $this->lighten_color($settings['color_error'], 85) . ";
            color: " . $this->darken_color($settings['color_error'], 20) . ";
        }

        /* Logo en login page */
        .login h1 a {
            background-image: url('{$settings['company_logo']}');
            background-size: contain;
            width: 100%;
        }
        ";

        return $css;
    }

    /**
     * Oscurecer color
     */
    private function darken_color($hex, $percent)
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Aclarar color
     */
    private function lighten_color($hex, $percent)
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ((255 - $r) * $percent / 100)));
        $g = max(0, min(255, $g + ((255 - $g) * $percent / 100)));
        $b = max(0, min(255, $b + ((255 - $b) * $percent / 100)));

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Renderizar página de personalización
     */
    public function render_customizer_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para acceder a esta página.');
        }

        $settings = self::get_settings();
        ?>
        <div class="ap-wrap">
            <div class="ap-page-header">
                <h1>
                    <span class="dashicons dashicons-admin-customizer"></span>
                    Personalización de Marca
                </h1>
                <p>Personaliza la apariencia del Dashboard con tu marca: logo, colores y tipografías.</p>
            </div>

            <form method="post" action="options.php" id="alquipress-brand-customizer-form">
                <?php settings_fields('alquipress_brand_customizer'); ?>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">

                    <!-- Panel de Configuración -->
                    <div>
                        <!-- Identidad de Marca -->
                        <div class="ap-card">
                            <h2>
                                <span class="dashicons dashicons-building"></span>
                                Identidad de Marca
                            </h2>

                            <table class="ap-form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="company_name">Nombre de la Empresa</label>
                                    </th>
                                    <td>
                                        <input type="text"
                                               id="company_name"
                                               name="alquipress_brand_settings[company_name]"
                                               value="<?php echo esc_attr($settings['company_name']); ?>"
                                               class="regular-text">
                                        <p class="description">Este nombre aparecerá en el menú principal y en el dashboard.</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="company_logo">Logo de la Empresa</label>
                                    </th>
                                    <td>
                                        <input type="hidden"
                                               id="company_logo"
                                               name="alquipress_brand_settings[company_logo]"
                                               value="<?php echo esc_url($settings['company_logo']); ?>">

                                        <button type="button" id="upload-logo-button" class="ap-button ap-button--secondary">
                                            <span class="dashicons dashicons-upload"></span> Subir Logo
                                        </button>

                                        <button type="button" id="remove-logo-button" class="ap-button ap-button--error" style="<?php echo empty($settings['company_logo']) ? 'display: none;' : ''; ?>">
                                            <span class="dashicons dashicons-trash"></span> Eliminar
                                        </button>

                                        <?php if (!empty($settings['company_logo'])): ?>
                                            <div class="ap-mt-3">
                                                <img id="logo-preview"
                                                     src="<?php echo esc_url($settings['company_logo']); ?>"
                                                     style="max-width: 200px; height: auto; border: 1px solid #ddd; padding: 10px; background: white;">
                                            </div>
                                        <?php else: ?>
                                            <img id="logo-preview" style="display: none; max-width: 200px; height: auto; border: 1px solid #ddd; padding: 10px; background: white; margin-top: 10px;">
                                        <?php endif; ?>

                                        <p class="description">Logo que aparecerá en la página de login y opcionalmente en el menú admin.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Colores -->
                        <div class="ap-card ap-mt-5">
                            <h2>
                                <span class="dashicons dashicons-art"></span>
                                Paleta de Colores
                            </h2>

                            <table class="ap-form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="color_primary">Color Primario</label>
                                    </th>
                                    <td>
                                        <input type="text"
                                               id="color_primary"
                                               name="alquipress_brand_settings[color_primary]"
                                               value="<?php echo esc_attr($settings['color_primary']); ?>"
                                               class="color-picker">
                                        <p class="description">Color principal usado en botones, enlaces y elementos destacados.</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="color_secondary">Color Secundario</label>
                                    </th>
                                    <td>
                                        <input type="text"
                                               id="color_secondary"
                                               name="alquipress_brand_settings[color_secondary]"
                                               value="<?php echo esc_attr($settings['color_secondary']); ?>"
                                               class="color-picker">
                                        <p class="description">Color usado en elementos secundarios y badges informativos.</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="color_success">Color de Éxito</label>
                                    </th>
                                    <td>
                                        <input type="text"
                                               id="color_success"
                                               name="alquipress_brand_settings[color_success]"
                                               value="<?php echo esc_attr($settings['color_success']); ?>"
                                               class="color-picker">
                                        <p class="description">Color para mensajes de éxito y confirmaciones.</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="color_warning">Color de Advertencia</label>
                                    </th>
                                    <td>
                                        <input type="text"
                                               id="color_warning"
                                               name="alquipress_brand_settings[color_warning]"
                                               value="<?php echo esc_attr($settings['color_warning']); ?>"
                                               class="color-picker">
                                        <p class="description">Color para advertencias y mensajes de atención.</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="color_error">Color de Error</label>
                                    </th>
                                    <td>
                                        <input type="text"
                                               id="color_error"
                                               name="alquipress_brand_settings[color_error]"
                                               value="<?php echo esc_attr($settings['color_error']); ?>"
                                               class="color-picker">
                                        <p class="description">Color para errores y mensajes críticos.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Tipografías -->
                        <div class="ap-card ap-mt-5">
                            <h2>
                                <span class="dashicons dashicons-editor-textcolor"></span>
                                Tipografías
                            </h2>

                            <table class="ap-form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="font_primary">Fuente Principal</label>
                                    </th>
                                    <td>
                                        <select id="font_primary"
                                                name="alquipress_brand_settings[font_primary]"
                                                class="regular-text">
                                            <?php
                                            $fonts = [
                                                'system-ui' => 'System UI (Por defecto)',
                                                'Arial' => 'Arial',
                                                'Helvetica' => 'Helvetica',
                                                'Georgia' => 'Georgia',
                                                'Times New Roman' => 'Times New Roman',
                                                'Courier New' => 'Courier New',
                                                'Verdana' => 'Verdana',
                                                'Trebuchet MS' => 'Trebuchet MS',
                                                'Inter' => 'Inter (Google Fonts)',
                                                'Roboto' => 'Roboto (Google Fonts)',
                                                'Open Sans' => 'Open Sans (Google Fonts)',
                                                'Lato' => 'Lato (Google Fonts)',
                                                'Montserrat' => 'Montserrat (Google Fonts)',
                                                'Poppins' => 'Poppins (Google Fonts)',
                                            ];
                                            foreach ($fonts as $value => $label) {
                                                printf(
                                                    '<option value="%s" %s>%s</option>',
                                                    esc_attr($value),
                                                    selected($settings['font_primary'], $value, false),
                                                    esc_html($label)
                                                );
                                            }
                                            ?>
                                        </select>
                                        <p class="description">Fuente usada en todo el texto del dashboard.</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="font_headings">Fuente para Títulos</label>
                                    </th>
                                    <td>
                                        <select id="font_headings"
                                                name="alquipress_brand_settings[font_headings]"
                                                class="regular-text">
                                            <?php
                                            foreach ($fonts as $value => $label) {
                                                printf(
                                                    '<option value="%s" %s>%s</option>',
                                                    esc_attr($value),
                                                    selected($settings['font_headings'], $value, false),
                                                    esc_html($label)
                                                );
                                            }
                                            ?>
                                        </select>
                                        <p class="description">Fuente usada en títulos y encabezados.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="ap-submit-area">
                            <button type="submit" class="ap-button ap-button--primary ap-button--large">
                                <span class="dashicons dashicons-saved"></span> Guardar Personalización
                            </button>

                            <a href="<?php echo add_query_arg('reset', '1', admin_url('admin.php?page=alquipress-brand-customizer')); ?>"
                               class="ap-button ap-button--secondary"
                               onclick="return confirm('¿Estás seguro de que quieres restaurar los valores por defecto?');">
                                <span class="dashicons dashicons-image-rotate"></span> Restaurar Valores por Defecto
                            </a>
                        </div>
                    </div>

                    <!-- Panel de Preview -->
                    <div>
                        <div class="ap-card" style="position: sticky; top: 32px;">
                            <h2>
                                <span class="dashicons dashicons-visibility"></span>
                                Vista Previa
                            </h2>

                            <div style="text-align: center; padding: 20px; background: #f0f0f1; border-radius: 8px; margin-bottom: 15px;">
                                <img class="preview-logo"
                                     src="<?php echo esc_url($settings['company_logo']); ?>"
                                     style="max-width: 150px; height: auto; margin-bottom: 10px; <?php echo empty($settings['company_logo']) ? 'display: none;' : ''; ?>">

                                <h3 class="preview-company-name" style="margin: 0; font-size: 24px; color: #2c3338;">
                                    <?php echo esc_html($settings['company_name']); ?>
                                </h3>
                            </div>

                            <div class="preview-box"
                                 style="background: <?php echo esc_attr($settings['color_primary']); ?>;
                                        color: white;
                                        padding: 20px;
                                        border-radius: 8px;
                                        text-align: center;
                                        margin-bottom: 15px;">
                                <strong>Color Primario</strong>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div style="background: <?php echo esc_attr($settings['color_success']); ?>; color: white; padding: 15px; border-radius: 6px; text-align: center; font-size: 12px;">
                                    Éxito
                                </div>
                                <div style="background: <?php echo esc_attr($settings['color_warning']); ?>; color: white; padding: 15px; border-radius: 6px; text-align: center; font-size: 12px;">
                                    Advertencia
                                </div>
                                <div style="background: <?php echo esc_attr($settings['color_error']); ?>; color: white; padding: 15px; border-radius: 6px; text-align: center; font-size: 12px;">
                                    Error
                                </div>
                                <div style="background: <?php echo esc_attr($settings['color_secondary']); ?>; color: white; padding: 15px; border-radius: 6px; text-align: center; font-size: 12px;">
                                    Secundario
                                </div>
                            </div>

                            <div class="ap-mt-5">
                                <p class="ap-text-sm ap-text-muted" style="font-family: var(--ap-font-primary);">
                                    <strong>Fuente Principal:</strong> <?php echo esc_html($settings['font_primary']); ?>
                                </p>
                                <p class="ap-text-sm ap-text-muted" style="font-family: var(--ap-font-headings);">
                                    <strong>Fuente Títulos:</strong> <?php echo esc_html($settings['font_headings']); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
        <?php

        // Manejar reset
        if (isset($_GET['reset'])) {
            update_option('alquipress_brand_settings', self::$defaults);
            wp_redirect(admin_url('admin.php?page=alquipress-brand-customizer'));
            exit;
        }
    }
}

new Alquipress_Brand_Customizer();
