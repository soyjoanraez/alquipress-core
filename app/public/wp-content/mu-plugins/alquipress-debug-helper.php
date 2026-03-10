<?php
/**
 * Plugin Name: Alquipress Debug Helper
 * Description: Helper functions for debugging Alquipress development.
 * Version: 1.0.0
 * Author: Antigravity
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom logging function for Alquipress.
 * Saves logs to wp-content/alquipress-debug.log for easier access.
 *
 * @param mixed $data Content to log.
 * @param string $label Optional label for the log entry.
 */
if (!function_exists('aq_log')) {
    function aq_log($data, $label = '')
    {
        $log_file = WP_CONTENT_DIR . '/alquipress-debug.log';
        $timestamp = date('Y-m-d H:i:s');

        $output = "[$timestamp]";
        if ($label) {
            $output .= " [$label]";
        }
        $output .= ": " . (is_array($data) || is_object($data) ? print_r($data, true) : $data);
        $output .= "\n" . str_repeat('-', 40) . "\n";

        error_log($output, 3, $log_file);
    }
}


/**
 * Quick dump and die (like Laravel's dd() or Symfony's dump()).
 *
 * @param mixed $data Content to dump.
 */
if (!function_exists('aq_dd')) {
    function aq_dd($data)
    {
        echo '<pre style="background: #222; color: #0f0; padding: 20px; border-radius: 5px; font-family: monospace; overflow: auto; max-height: 80vh;">';
        echo '<strong>[AQ-DEBUG]</strong> ' . date('H:i:s') . "\n";
        var_dump($data);
        echo '</pre>';
        die();
    }
}

/**
 * Quick dump without dying.
 *
 * @param mixed $data Content to dump.
 */
if (!function_exists('aq_d')) {
    function aq_d($data)
    {
        echo '<pre style="background: #222; color: #0fb; padding: 15px; border-radius: 5px; font-family: monospace; overflow: auto; max-height: 500px; border: 1px solid #0fb; margin: 10px 0; position: relative; z-index: 9999;">';
        echo '<strong>[AQ-DEBUG]</strong> ' . date('H:i:s') . "\n";
        print_r($data);
        echo '</pre>';
    }
}

/**
 * Add a Debug Log menu item to the Admin Bar and Admin Menu.
 */
add_action('admin_menu', 'aq_debug_menu');
function aq_debug_menu()
{
    add_management_page(
        'Alquipress Debug Log',
        'AQ Debug Logs',
        'manage_options',
        'aq-debug-logs',
        'aq_debug_logs_page'
    );
}

function aq_debug_logs_page()
{
    $log_file = WP_CONTENT_DIR . '/alquipress-debug.log';
    $wp_log_file = WP_CONTENT_DIR . '/debug.log';

    echo '<div class="wrap" style="background: #f0f0f1; padding: 20px; border-radius: 8px;">';
    echo '<h1 style="color: #2271b1; display: flex; align-items: center;"><span class="dashicons dashicons-bug" style="font-size: 30px; width: 30px; height: 30px; margin-right: 10px;"></span> Alquipress Debug System</h1>';

    // Procesar limpieza de log con verificación de nonce
    if (isset($_POST['aq_clear_log']) && isset($_POST['_aq_nonce'])) {
        if (wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_aq_nonce'])), 'aq_clear_log_action')) {
            $tab = isset($_POST['tab']) ? sanitize_text_field(wp_unslash($_POST['tab'])) : 'custom';
            if ($tab === 'wp') {
                file_put_contents($wp_log_file, '');
            } else {
                file_put_contents($log_file, '');
            }
            echo '<div class="notice notice-success is-dismissible"><p>Log cleared successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Security check failed.</p></div>';
        }
    }

    echo '<h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">';
    echo '<a href="?page=aq-debug-logs&tab=custom" class="nav-tab ' . (!isset($_GET['tab']) || $_GET['tab'] === 'custom' ? 'nav-tab-active' : '') . '">📋 Custom Alquipress Log</a>';
    echo '<a href="?page=aq-debug-logs&tab=wp" class="nav-tab ' . (isset($_GET['tab']) && $_GET['tab'] === 'wp' ? 'nav-tab-active' : '') . '">🌐 WordPress Error Log</a>';
    echo '</h2>';

    $current_log = (isset($_GET['tab']) && $_GET['tab'] === 'wp') ? $wp_log_file : $log_file;
    $log_name = (isset($_GET['tab']) && $_GET['tab'] === 'wp') ? 'WordPress Debug Log' : 'Custom Alquipress Log';

    $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'custom';
    echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
    echo '<h3>Viewing: ' . esc_html($log_name) . '</h3>';
    echo '<form method="post" style="display: inline;" onsubmit="return confirm(\'¿Estás seguro de que quieres limpiar este log?\');">';
    wp_nonce_field('aq_clear_log_action', '_aq_nonce');
    echo '<input type="hidden" name="tab" value="' . esc_attr($current_tab) . '">';
    echo '<button type="submit" name="aq_clear_log" value="1" class="button button-link-delete">Limpiar Log</button>';
    echo '</form>';
    echo '</div>';

    if (file_exists($current_log)) {
        $content = file_get_contents($current_log);
        if (empty($content)) {
            echo '<div style="background: #fff; border-left: 4px solid #72aee6; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
            echo '<p>El archivo de log está vacío.</p>';
            echo '</div>';
        } else {
            echo '<pre id="aq-debug-terminal" style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 6px; overflow: auto; max-height: 600px; font-family: \'Consolas\', \'Monaco\', monospace; font-size: 13px; line-height: 1.6; border: 1px solid #333; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">';
            echo esc_html($content);
            echo '</pre>';
        }
    } else {
        echo '<div style="background: #fff; border-left: 4px solid #d63638; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
        echo '<p>No se encontró el archivo en: <code>' . esc_html($current_log) . '</code></p>';
        echo '</div>';
    }

    echo '<div style="margin-top: 30px; padding: 20px; background: #fff; border-radius: 8px; border: 1px solid #ccd0d4;">';
    echo '<h4>📚 Cómo usar el Debug:</h4>';
    echo '<ul>';
    echo '<li><code>aq_log($dato, "Mi Etiqueta");</code> - Guarda cualquier dato en el log personalizado.</li>';
    echo '<li><code>aq_d($dato);</code> - Muestra un dump visual en pantalla sin detener la ejecución.</li>';
    echo '<li><code>aq_dd($dato);</code> - Muestra un dump visual y detiene la ejecución (Dump & Die).</li>';
    echo '</ul>';
    echo '</div>';

    echo '</div>'; // End wrap

    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var pre = document.getElementById('aq-debug-terminal');
            if (pre) {
                pre.scrollTop = pre.scrollHeight;
            }
        });
    </script>
    <?php
}

/**
 * Add AQ Debug to the Admin Bar.
 */
add_action('admin_bar_menu', 'aq_admin_bar_debug_link', 999);
function aq_admin_bar_debug_link($wp_admin_bar)
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $args = array(
        'id' => 'aq-debug',
        'title' => '<span class="ab-icon dashicons dashicons-bug" style="top:2px"></span> AQ Debug',
        'href' => admin_url('tools.php?page=aq-debug-logs'),
    );
    $wp_admin_bar->add_node($args);
}
