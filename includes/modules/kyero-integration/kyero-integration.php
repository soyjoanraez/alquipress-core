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
 * Archivo del feed Kyero (cache en uploads)
 */
function alquipress_kyero_feed_file_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'kyero-feed.xml';
}

/**
 * Determina si el feed cacheado sigue fresco
 */
function alquipress_kyero_feed_is_fresh($file_path) {
    $ttl = (int) apply_filters('alquipress_kyero_feed_ttl', 6 * HOUR_IN_SECONDS);
    if ($ttl <= 0) {
        return false;
    }
    return file_exists($file_path) && (time() - filemtime($file_path) < $ttl);
}

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
    <?php wp_nonce_field('alquipress_kyero_export', 'alquipress_kyero_export_nonce'); ?>
    <div id="kyero-export-box" class="ap-card ap-card--info">
        <label class="ap-flex ap-items-center ap-gap-2" style="cursor: pointer;">
            <input type="checkbox"
                   name="kyero_export_checkbox"
                   value="1"
                   <?php checked($is_checked); ?>>
            <span class="ap-text-semibold">
                <span class="dashicons dashicons-upload"></span> Exportar esta propiedad a Kyero
            </span>
        </label>
        <p class="ap-text-sm ap-text-muted" style="margin: 8px 0 0 26px;">
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
    // Validaciones básicas
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        error_log('ALQUIPRESS Kyero: Usuario sin permisos intentó editar export (Post ID: ' . $post_id . ')');
        return;
    }

    // Validación de nonce con logging
    if (empty($_POST['alquipress_kyero_export_nonce'])) {
        error_log('ALQUIPRESS Kyero: Nonce faltante en save_post_product (Post ID: ' . $post_id . ')');
        return;
    }

    if (!wp_verify_nonce($_POST['alquipress_kyero_export_nonce'], 'alquipress_kyero_export')) {
        error_log('ALQUIPRESS Kyero: Nonce inválido en save_post_product (Post ID: ' . $post_id . ')');
        return;
    }

    // Procesar el formulario
    $export_term = get_term_by('slug', 'exportar', 'kyero_export');

    if (!$export_term) {
        error_log('ALQUIPRESS Kyero: Término "exportar" no encontrado en taxonomía kyero_export');
        return;
    }

    if (isset($_POST['kyero_export_checkbox']) && $_POST['kyero_export_checkbox'] == '1') {
        wp_set_post_terms($post_id, [$export_term->term_id], 'kyero_export', false);
    } else {
        wp_remove_object_terms($post_id, $export_term->term_id, 'kyero_export');
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

        $feed_file = alquipress_kyero_feed_file_path();
        if (!alquipress_kyero_feed_is_fresh($feed_file)) {
            $feed = new Alquipress_Kyero_Feed();
            $result = $feed->save_to_file();

            if ($result === false) {
                error_log('ALQUIPRESS Kyero: Error generando feed');
            }

            clearstatcache(true, $feed_file);
        }

        if (file_exists($feed_file)) {
            // Verificar permisos de lectura
            if (!is_readable($feed_file)) {
                error_log('ALQUIPRESS Kyero: Feed file exists but is not readable - ' . $feed_file);
                header('HTTP/1.1 500 Internal Server Error');
                echo '<?xml version="1.0" encoding="UTF-8"?><error>Feed file not accessible</error>';
                exit;
            }

            // Intentar leer con error handling
            $result = @readfile($feed_file);

            if ($result === false) {
                error_log('ALQUIPRESS Kyero: Failed to read feed file - ' . $feed_file);
                header('HTTP/1.1 500 Internal Server Error');
                echo '<?xml version="1.0" encoding="UTF-8"?><error>Failed to read feed</error>';
                exit;
            }
        } else {
            $feed = new Alquipress_Kyero_Feed();
            echo $feed->generate();
        }
        
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

        // Validar y sanitizar URL de importación
        $import_url = isset($_POST['kyero_import_url']) ? esc_url_raw($_POST['kyero_import_url']) : '';

        // Validar que sea una URL válida si no está vacía
        if (!empty($import_url) && !filter_var($import_url, FILTER_VALIDATE_URL)) {
            echo '<div class="ap-notice ap-notice--error"><span class="dashicons dashicons-warning"></span><p>La URL proporcionada no es válida</p></div>';
        } else {
            update_option('kyero_import_url', $import_url);
            update_option('kyero_auto_import', isset($_POST['kyero_auto_import']) ? 1 : 0);

            echo '<div class="ap-notice ap-notice--success"><span class="dashicons dashicons-yes-alt"></span><p>Configuración guardada correctamente</p></div>';
        }
    }

    // Ejecutar exportación manual
    if (isset($_POST['kyero_manual_export'])) {
        check_admin_referer('kyero_export');

        $feed = new Alquipress_Kyero_Feed();
        $url = $feed->save_to_file();

        echo '<div class="ap-notice ap-notice--success"><span class="dashicons dashicons-yes-alt"></span><p>Feed exportado: <a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a></p></div>';
    }

    // Ejecutar importación manual
    if (isset($_POST['kyero_manual_import'])) {
        check_admin_referer('kyero_import');

        $import_url = get_option('kyero_import_url');

        if (!$import_url) {
            echo '<div class="ap-notice ap-notice--error"><span class="dashicons dashicons-warning"></span><p>No has configurado la URL de importación</p></div>';
        } else {
            $importer = new Alquipress_Kyero_Importer($import_url);
            $result = $importer->import_properties();

            if ($result['success']) {
                echo '<div class="ap-notice ap-notice--success"><span class="dashicons dashicons-yes-alt"></span><p>';
                echo 'Importación completada: ';
                echo $result['imported'] . ' nuevas, ';
                echo $result['updated'] . ' actualizadas, ';
                echo $result['errors'] . ' errores';
                echo '</p></div>';
            } else {
                echo '<div class="ap-notice ap-notice--error"><span class="dashicons dashicons-warning"></span><p>Error en la importación</p></div>';
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
    <div class="ap-wrap ap-wrap--narrow">
        <div class="ap-page-header">
            <h1>
                <span class="dashicons dashicons-admin-home"></span>
                Kyero Import/Export Manager
            </h1>
            <p>Sincroniza tus propiedades con el portal inmobiliario Kyero.</p>
        </div>

        <div class="ap-card">
            <h2>
                <span class="dashicons dashicons-upload"></span>
                Exportación a Kyero
            </h2>

            <p>Propiedades marcadas para exportar: <strong class="ap-text-primary"><?php echo $export_count; ?></strong></p>
            <p>URL del Feed XML: <code><?php echo home_url('/kyero-feed.xml'); ?></code></p>

            <form method="post" class="ap-mt-5">
                <?php wp_nonce_field('kyero_export'); ?>
                <button type="submit" name="kyero_manual_export" class="ap-button ap-button--primary">
                    <span class="dashicons dashicons-update"></span> Generar Feed Ahora
                </button>
            </form>

            <hr class="ap-divider">

            <h3>
                <span class="dashicons dashicons-list-view"></span>
                Instrucciones para Kyero
            </h3>
            <ol>
                <li>Inicia sesión en tu cuenta de Kyero</li>
                <li>Ve a <strong>Settings &gt; Data Feed</strong></li>
                <li>Pega esta URL: <code><?php echo home_url('/kyero-feed.xml'); ?></code></li>
                <li>Kyero sincronizará automáticamente cada 24h</li>
            </ol>
        </div>

        <div class="ap-card">
            <h2>
                <span class="dashicons dashicons-download"></span>
                Importación desde Kyero
            </h2>

            <form method="post">
                <?php wp_nonce_field('kyero_settings'); ?>

                <table class="ap-form-table">
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

                <div class="ap-submit-area">
                    <button type="submit" name="kyero_save_settings" class="ap-button ap-button--success">
                        <span class="dashicons dashicons-saved"></span> Guardar Configuración
                    </button>
                </div>
            </form>

            <hr class="ap-divider">

            <form method="post">
                <?php wp_nonce_field('kyero_import'); ?>
                <button type="submit" name="kyero_manual_import" class="ap-button ap-button--secondary">
                    <span class="dashicons dashicons-download"></span> Importar Ahora
                </button>
            </form>
        </div>

        <div class="ap-card ap-card--info">
            <h2>
                <span class="dashicons dashicons-yes-alt"></span>
                Validar Feed
            </h2>
            <p>Valida tu feed antes de enviarlo a Kyero:</p>
            <a href="https://www.kyero.com/xml-validator" target="_blank" class="ap-button ap-button--secondary">
                <span class="dashicons dashicons-search"></span> Abrir Validador de Kyero
            </a>
        </div>
    </div>
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
