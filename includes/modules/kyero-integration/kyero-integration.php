<?php
/**
 * Module Name: Kyero Integration
 * Description: Sistema de importación y exportación de propiedades mediante XML Kyero.
 */

if (!defined('ABSPATH')) exit;

// Cargar Clases
require_once __DIR__ . '/class-kyero-feed.php';
require_once __DIR__ . '/class-kyero-importer.php';

/**
 * ================================================
 * TAXONOMÍA: Exportar a Kyero (Checkbox)
 * ================================================
 */
function alquipress_register_kyero_taxonomy() {
    register_taxonomy('kyero_export', 'product', array(
        'label' => 'Exportar a Kyero',
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_admin_column' => true,
        'hierarchical' => false,
        'meta_box_cb' => 'alquipress_kyero_metabox',
        'rewrite' => false,
    ));
}
add_action('init', 'alquipress_register_kyero_taxonomy');

/**
 * Meta Box Custom: Checkbox Simple
 */
function alquipress_kyero_metabox($post) {
    $terms = wp_get_post_terms($post->ID, 'kyero_export', ['fields' => 'ids']);
    $is_checked = !empty($terms);
    
    // Crear el término "exportar" si no existe
    if (!term_exists('exportar', 'kyero_export')) {
        wp_insert_term('Exportar', 'kyero_export', ['slug' => 'exportar']);
    }
    
    ?>
    <div id="kyero-export-box" style="padding: 10px; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 4px;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" 
                   name="kyero_export_checkbox" 
                   value="1" 
                   <?php checked($is_checked); ?>
                   style="width: 18px; height: 18px;">
            <span style="font-weight: 600; color: #0c4a6e;">
                📤 Exportar esta propiedad a Kyero
            </span>
        </label>
        <p style="margin: 8px 0 0 26px; font-size: 12px; color: #64748b;">
            Esta propiedad aparecerá en el feed XML de Kyero en menos de 24h.
        </p>
    </div>
    <?php
}

/**
 * Guardar el estado del checkbox
 */
add_action('save_post_product', 'alquipress_save_kyero_export');

function alquipress_save_kyero_export($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    if (isset($_POST['kyero_export_checkbox']) && $_POST['kyero_export_checkbox'] == '1') {
        $export_term = get_term_by('slug', 'exportar', 'kyero_export');
        if ($export_term) {
            wp_set_post_terms($post_id, [$export_term->term_id], 'kyero_export', false);
        }
    } else {
        $export_term = get_term_by('slug', 'exportar', 'kyero_export');
        if ($export_term) {
            wp_remove_object_terms($post_id, $export_term->term_id, 'kyero_export');
        }
    }
}

/**
 * ================================================
 * ENDPOINT PÚBLICO: /kyero-feed.xml
 * ================================================
 */
add_action('init', 'alquipress_kyero_feed_endpoint');

function alquipress_kyero_feed_endpoint() {
    add_rewrite_rule('^kyero-feed\.xml$', 'index.php?kyero_feed=1', 'top');
    add_rewrite_tag('%kyero_feed%', '([^&]+)');
}

add_action('template_redirect', 'alquipress_serve_kyero_feed');

function alquipress_serve_kyero_feed() {
    if (get_query_var('kyero_feed')) {
        header('Content-Type: application/xml; charset=utf-8');
        
        $feed = new Alquipress_Kyero_Feed();
        echo $feed->generate();
        
        exit;
    }
}

/**
 * ================================================
 * PANEL DE ADMINISTRACIÓN: Kyero Import/Export
 * ================================================
 */

add_action('admin_menu', 'alquipress_kyero_admin_menu');

function alquipress_kyero_admin_menu() {
    add_submenu_page(
        'alquipress-settings', // Parent slug from Alquipress_Module_Manager
        'Kyero Sync',
        'Kyero Sync',
        'manage_options',
        'alquipress-kyero',
        'alquipress_kyero_admin_page'
    );
}

function alquipress_kyero_admin_page() {
    // Guardar configuración
    if (isset($_POST['kyero_save_settings'])) {
        check_admin_referer('kyero_settings');
        
        update_option('kyero_import_url', sanitize_url($_POST['kyero_import_url']));
        update_option('kyero_auto_import', isset($_POST['kyero_auto_import']) ? 1 : 0);
        
        echo '<div class="notice notice-success is-dismissible"><p>✅ Configuración guardada</p></div>';
    }
    
    // Ejecutar exportación manual
    if (isset($_POST['kyero_manual_export'])) {
        check_admin_referer('kyero_export');
        
        $feed = new Alquipress_Kyero_Feed();
        $url = $feed->save_to_file();
        
        echo '<div class="notice notice-success is-dismissible"><p>✅ Feed exportado: <a href="' . $url . '" target="_blank">' . $url . '</a></p></div>';
    }
    
    // Ejecutar importación manual
    if (isset($_POST['kyero_manual_import'])) {
        check_admin_referer('kyero_import');
        
        $import_url = get_option('kyero_import_url');
        
        if (!$import_url) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ No has configurado la URL de importación</p></div>';
        } else {
            $importer = new Alquipress_Kyero_Importer($import_url);
            $result = $importer->import_properties();
            
            if ($result['success']) {
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo '✅ Importación completada: ';
                echo $result['imported'] . ' nuevas, ';
                echo $result['updated'] . ' actualizadas, ';
                echo $result['errors'] . ' errores';
                echo '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>❌ Error en la importación</p></div>';
            }
        }
    }
    
    // Obtener valores actuales
    $import_url = get_option('kyero_import_url', '');
    $auto_import = get_option('kyero_auto_import', 0);
    
    // Contar propiedades exportables
    $export_count = 0;
    $export_term = get_term_by('slug', 'exportar', 'kyero_export');
    if ($export_term) {
        $export_count = $export_term->count;
    }
    
    ?>
    <div class="wrap">
        <h1>🏠 Kyero Import/Export Manager</h1>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>📤 Exportación a Kyero</h2>
            
            <p>Propiedades marcadas para exportar: <strong><?php echo $export_count; ?></strong></p>
            <p>URL del Feed XML: <code><?php echo home_url('/kyero-feed.xml'); ?></code></p>
            
            <form method="post">
                <?php wp_nonce_field('kyero_export'); ?>
                <button type="submit" name="kyero_manual_export" class="button button-primary">
                    🚀 Generar Feed Ahora
                </button>
            </form>
            
            <hr>
            
            <h3>📋 Instrucciones para Kyero</h3>
            <ol>
                <li>Inicia sesión en tu cuenta de Kyero</li>
                <li>Ve a <strong>Settings > Data Feed</strong></li>
                <li>Pega esta URL: <code><?php echo home_url('/kyero-feed.xml'); ?></code></li>
                <li>Kyero sincronizará automáticamente cada 24h</li>
            </ol>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>📥 Importación desde Kyero</h2>
            
            <form method="post">
                <?php wp_nonce_field('kyero_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">URL del Feed XML</th>
                        <td>
                            <input type="url" 
                                   name="kyero_import_url" 
                                   value="<?php echo esc_attr($import_url); ?>" 
                                   class="regular-text"
                                   placeholder="https://ejemplo.com/kyero-feed.xml">
                            <p class="description">URL del feed XML de la agencia desde la que importar</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Importación Automática</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="kyero_auto_import" 
                                       value="1" 
                                       <?php checked($auto_import, 1); ?>>
                                Importar automáticamente cada 24h
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="kyero_save_settings" class="button button-primary">
                        💾 Guardar Configuración
                    </button>
                </p>
            </form>
            
            <hr>
            
            <form method="post">
                <?php wp_nonce_field('kyero_import'); ?>
                <button type="submit" name="kyero_manual_import" class="button button-secondary">
                    ⬇️ Importar Ahora
                </button>
            </form>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>✅ Validar Feed</h2>
            <p>Valida tu feed antes de enviarlo a Kyero:</p>
            <a href="https://www.kyero.com/xml-validator" target="_blank" class="button">
                🔍 Abrir Validador de Kyero
            </a>
        </div>
    </div>
    
    <style>
        .card { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        .card h2 { margin-top: 0; }
        .card code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    </style>
    <?php
}

/**
 * ================================================
 * CRON: Exportar e Importar Automáticamente
 * ================================================
 */
add_action('wp', 'alquipress_kyero_schedule_cron');

function alquipress_kyero_schedule_cron() {
    if (!wp_next_scheduled('alquipress_kyero_daily_export')) {
        wp_schedule_event(time(), 'daily', 'alquipress_kyero_daily_export');
    }
    
    if (get_option('kyero_auto_import') && !wp_next_scheduled('alquipress_kyero_daily_import')) {
        wp_schedule_event(time(), 'daily', 'alquipress_kyero_daily_import');
    }
}

add_action('alquipress_kyero_daily_export', 'alquipress_run_kyero_export');

function alquipress_run_kyero_export() {
    $feed = new Alquipress_Kyero_Feed();
    $feed->save_to_file();
}

add_action('alquipress_kyero_daily_import', 'alquipress_run_kyero_import');

function alquipress_run_kyero_import() {
    $import_url = get_option('kyero_import_url');
    if ($import_url) {
        $importer = new Alquipress_Kyero_Importer($import_url);
        $importer->import_properties();
    }
}
