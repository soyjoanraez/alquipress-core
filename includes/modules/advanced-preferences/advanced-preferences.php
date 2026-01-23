<?php
/**
 * Módulo: Preferencias Avanzadas
 * Sistema mejorado de preferencias con iconos, contadores y análisis
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Advanced_Preferences
{

    private $available_preferences = [
        'mascotas' => [
            'icon' => '🐾',
            'label' => 'Admite Mascotas',
            'category' => 'restricciones',
            'description' => 'El huésped viaja con mascotas'
        ],
        'nofumador' => [
            'icon' => '🚭',
            'label' => 'No Fumador',
            'category' => 'restricciones',
            'description' => 'El huésped prefiere ambiente sin humo'
        ],
        'familia' => [
            'icon' => '👨‍👩‍👧',
            'label' => 'Familia',
            'category' => 'tipo',
            'description' => 'Viaja en familia con niños'
        ],
        'accesibilidad' => [
            'icon' => '♿',
            'label' => 'Accesibilidad',
            'category' => 'necesidades',
            'description' => 'Requiere instalaciones accesibles'
        ],
        'nomada' => [
            'icon' => '💻',
            'label' => 'Nómada Digital',
            'category' => 'tipo',
            'description' => 'Trabaja remotamente, necesita buen WiFi'
        ],
        'silencio' => [
            'icon' => '🤫',
            'label' => 'Zona Tranquila',
            'category' => 'ambiente',
            'description' => 'Prefiere ubicaciones silenciosas'
        ],
        'parking' => [
            'icon' => '🚗',
            'label' => 'Requiere Parking',
            'category' => 'servicios',
            'description' => 'Necesita estacionamiento'
        ],
        'cocina' => [
            'icon' => '🍳',
            'label' => 'Cocina Equipada',
            'category' => 'servicios',
            'description' => 'Prefiere propiedades con cocina completa'
        ],
        'piscina' => [
            'icon' => '🏊',
            'label' => 'Piscina',
            'category' => 'ocio',
            'description' => 'Busca propiedades con piscina'
        ],
        'playa' => [
            'icon' => '🏖️',
            'label' => 'Cerca de la Playa',
            'category' => 'ubicacion',
            'description' => 'Prefiere proximidad a la playa'
        ]
    ];

    public function __construct()
    {
        // Widget en dashboard
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);

        // Añadir columna en listado de usuarios
        add_filter('manage_users_columns', [$this, 'add_preferences_column']);
        add_filter('manage_users_custom_column', [$this, 'populate_preferences_column'], 10, 3);

        // Shortcode para mostrar preferencias
        add_shortcode('guest_preferences', [$this, 'preferences_shortcode']);

        // Cargar estilos
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX para análisis de preferencias
        add_action('wp_ajax_alquipress_preferences_stats', [$this, 'ajax_preferences_stats']);
    }

    /**
     * Widget de dashboard con estadísticas de preferencias
     */
    public function add_dashboard_widget()
    {
        wp_add_dashboard_widget(
            'alquipress_preferences_stats',
            '🎯 Preferencias de Huéspedes',
            [$this, 'render_dashboard_widget']
        );
    }

    /**
     * Renderizar widget de dashboard
     */
    public function render_dashboard_widget()
    {
        $stats = $this->get_preferences_statistics();

        echo '<div class="preferences-dashboard-widget">';

        if (empty($stats)) {
            echo '<p style="text-align: center; color: #666; padding: 20px;">Sin datos de preferencias aún.</p>';
            echo '</div>';
            return;
        }

        // Mostrar top 5 preferencias
        arsort($stats);
        $top_preferences = array_slice($stats, 0, 5, true);

        echo '<div class="top-preferences">';

        foreach ($top_preferences as $pref_key => $count) {
            $pref = $this->available_preferences[$pref_key] ?? null;

            if (!$pref) {
                continue;
            }

            $percentage = $this->get_preference_percentage($pref_key);

            echo '<div class="preference-stat-row">';
            echo '<div class="pref-info">';
            echo '<span class="pref-icon">' . $pref['icon'] . '</span>';
            echo '<span class="pref-label">' . esc_html($pref['label']) . '</span>';
            echo '</div>';
            echo '<div class="pref-stats">';
            echo '<span class="pref-count">' . $count . ' huéspedes</span>';
            echo '<div class="pref-bar">';
            echo '<div class="pref-bar-fill" style="width: ' . $percentage . '%"></div>';
            echo '</div>';
            echo '<span class="pref-percentage">' . number_format($percentage, 1) . '%</span>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';

        // Botón para ver análisis completo
        echo '<div class="widget-footer">';
        echo '<a href="#" class="button" id="view-full-analysis">📊 Ver Análisis Completo</a>';
        echo '</div>';

        // Modal para análisis completo
        echo '<div id="preferences-analysis-modal" style="display: none;">';
        echo '<div class="modal-content">';
        echo '<span class="close-modal">&times;</span>';
        echo '<h2>📊 Análisis Completo de Preferencias</h2>';
        echo '<div id="full-analysis-content"></div>';
        echo '</div>';
        echo '</div>';

        echo '</div>';

        // JavaScript
        ?>
        <script>
            jQuery(document).ready(function ($) {
                $('#view-full-analysis').on('click', function (e) {
                    e.preventDefault();

                    $('#full-analysis-content').html('<p style="text-align: center;">Cargando...</p>');
                    $('#preferences-analysis-modal').fadeIn();

                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'alquipress_preferences_stats'
                        },
                        success: function (response) {
                            if (response.success) {
                                $('#full-analysis-content').html(response.data.html);
                            }
                        }
                    });
                });

                $('.close-modal, #preferences-analysis-modal').on('click', function (e) {
                    if (e.target === this) {
                        $('#preferences-analysis-modal').fadeOut();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Obtener estadísticas de preferencias
     */
    private function get_preferences_statistics()
    {
        $users = get_users(['fields' => 'ID']);
        $stats = [];

        foreach ($users as $user_id) {
            $preferences = get_field('guest_preferences', 'user_' . $user_id);

            if (is_array($preferences)) {
                foreach ($preferences as $pref) {
                    if (!isset($stats[$pref])) {
                        $stats[$pref] = 0;
                    }
                    $stats[$pref]++;
                }
            }
        }

        return $stats;
    }

    /**
     * Obtener porcentaje de una preferencia
     */
    private function get_preference_percentage($pref_key)
    {
        $total_users = count(get_users());

        if ($total_users === 0) {
            return 0;
        }

        $stats = $this->get_preferences_statistics();
        $count = $stats[$pref_key] ?? 0;

        return ($count / $total_users) * 100;
    }

    /**
     * AJAX: Obtener análisis completo
     */
    public function ajax_preferences_stats()
    {
        $stats = $this->get_preferences_statistics();
        $total_users = count(get_users());

        arsort($stats);

        ob_start();
        ?>
        <div class="full-preferences-analysis">
            <?php foreach ($this->available_preferences as $pref_key => $pref): ?>
                <?php
                $count = $stats[$pref_key] ?? 0;
                $percentage = $total_users > 0 ? ($count / $total_users) * 100 : 0;
                ?>
                <div class="analysis-row">
                    <div class="analysis-header">
                        <span class="pref-icon-large"><?php echo $pref['icon']; ?></span>
                        <div class="pref-details">
                            <h3><?php echo esc_html($pref['label']); ?></h3>
                            <p class="pref-description"><?php echo esc_html($pref['description']); ?></p>
                            <span class="pref-category"><?php echo esc_html(ucfirst($pref['category'])); ?></span>
                        </div>
                    </div>
                    <div class="analysis-stats">
                        <div class="stat-number"><?php echo $count; ?></div>
                        <div class="stat-label">huéspedes</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="stat-percentage"><?php echo number_format($percentage, 1); ?>%</div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="analysis-summary">
                <h3>📋 Resumen</h3>
                <ul>
                    <li><strong>Total de huéspedes:</strong> <?php echo $total_users; ?></li>
                    <li><strong>Huéspedes con preferencias:</strong> <?php echo count(array_filter($stats)); ?></li>
                    <li><strong>Preferencia más común:</strong>
                        <?php
                        if (!empty($stats)) {
                            reset($stats);
                            $top_pref = key($stats);
                            $top_config = $this->available_preferences[$top_pref];
                            echo $top_config['icon'] . ' ' . $top_config['label'];
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </li>
                </ul>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Añadir columna de preferencias en listado de usuarios
     */
    public function add_preferences_column($columns)
    {
        $columns['guest_preferences_quick'] = '🎯 Preferencias';
        return $columns;
    }

    /**
     * Poblar columna de preferencias
     */
    public function populate_preferences_column($value, $column_name, $user_id)
    {
        if ($column_name === 'guest_preferences_quick') {
            $preferences = get_field('guest_preferences', 'user_' . $user_id);

            if (empty($preferences) || !is_array($preferences)) {
                return '<span style="color: #999;">-</span>';
            }

            $output = '<div class="preferences-icons">';
            $count = 0;

            foreach ($preferences as $pref_key) {
                if ($count >= 3) {
                    $remaining = count($preferences) - 3;
                    $output .= '<span class="pref-more">+' . $remaining . '</span>';
                    break;
                }

                $pref = $this->available_preferences[$pref_key] ?? null;

                if ($pref) {
                    $output .= '<span class="pref-icon-small" title="' . esc_attr($pref['label']) . '">';
                    $output .= $pref['icon'];
                    $output .= '</span>';
                    $count++;
                }
            }

            $output .= '</div>';

            return $output;
        }

        return $value;
    }

    /**
     * Shortcode para mostrar preferencias
     */
    public function preferences_shortcode($atts)
    {
        $atts = shortcode_atts([
            'user_id' => get_current_user_id(),
            'style' => 'list' // list, icons, cards
        ], $atts);

        $preferences = get_field('guest_preferences', 'user_' . intval($atts['user_id']));

        if (empty($preferences)) {
            return '<p>Sin preferencias configuradas.</p>';
        }

        ob_start();

        switch ($atts['style']) {
            case 'icons':
                echo '<div class="preferences-icons-display">';
                foreach ($preferences as $pref_key) {
                    $pref = $this->available_preferences[$pref_key] ?? null;
                    if ($pref) {
                        echo '<span class="pref-icon" title="' . esc_attr($pref['label']) . '">';
                        echo $pref['icon'];
                        echo '</span>';
                    }
                }
                echo '</div>';
                break;

            case 'cards':
                echo '<div class="preferences-cards-display">';
                foreach ($preferences as $pref_key) {
                    $pref = $this->available_preferences[$pref_key] ?? null;
                    if ($pref) {
                        echo '<div class="pref-card">';
                        echo '<span class="pref-icon">' . $pref['icon'] . '</span>';
                        echo '<span class="pref-label">' . esc_html($pref['label']) . '</span>';
                        echo '</div>';
                    }
                }
                echo '</div>';
                break;

            default: // list
                echo '<ul class="preferences-list-display">';
                foreach ($preferences as $pref_key) {
                    $pref = $this->available_preferences[$pref_key] ?? null;
                    if ($pref) {
                        echo '<li>';
                        echo '<span class="pref-icon">' . $pref['icon'] . '</span>';
                        echo ' ' . esc_html($pref['label']);
                        echo '</li>';
                    }
                }
                echo '</ul>';
                break;
        }

        return ob_get_clean();
    }

    /**
     * Cargar estilos
     */
    public function enqueue_assets($hook)
    {
        if ($hook === 'index.php' || $hook === 'users.php') {
            wp_enqueue_style(
                'alquipress-advanced-preferences',
                ALQUIPRESS_URL . 'includes/modules/advanced-preferences/assets/advanced-preferences.css',
                [],
                ALQUIPRESS_VERSION
            );
        }
    }
}

new Alquipress_Advanced_Preferences();
