<?php
if (!defined('ABSPATH'))
    exit;

$manager = \Alquipress\Suite\Core\Manager::instance();
$modules = $manager->get_modules();

// Procesar guardado si es necesario
if (isset($_POST['alq_save_suite_settings']) && check_admin_referer('alq_suite_settings_nonce')) {
    $active_data = [];
    foreach ($modules as $id => $config) {
        $active_data[$id] = isset($_POST['modules'][$id]);
    }
    update_option('alq_suite_active_modules', $active_data);
    $saved = true;
} else {
    $saved = false;
}

$active_modules = get_option('alq_suite_active_modules', []);

// Cargar sidebar del dashboard
$alquipress_path = '';
if (defined('ALQUIPRESS_PATH')) {
    $alquipress_path = ALQUIPRESS_PATH;
} else {
    // Intentar encontrar la ruta del plugin alquipress-core
    $possible_paths = [
        WP_PLUGIN_DIR . '/alquipress-core/',
        dirname(dirname(dirname(__DIR__))) . '/alquipress-core/',
    ];
    foreach ($possible_paths as $path) {
        if (file_exists($path . 'includes/admin/alquipress-sidebar.php')) {
            $alquipress_path = $path;
            break;
        }
    }
}

if ($alquipress_path && file_exists($alquipress_path . 'includes/admin/alquipress-sidebar.php')) {
    require_once $alquipress_path . 'includes/admin/alquipress-sidebar.php';
}
?>

<div class="wrap alquipress-dashboard-page ap-has-sidebar">
    <div class="ap-owners-layout">
        <?php if (function_exists('alquipress_render_sidebar')): ?>
            <?php alquipress_render_sidebar('performance'); ?>
        <?php endif; ?>
        <main class="ap-owners-main">
            <header class="ap-header">
                <div class="ap-header-left">
                    <h1 class="ap-header-title"><?php esc_html_e('Performance & Security', 'alquipress'); ?></h1>
                    <p class="ap-header-subtitle"><?php esc_html_e('Activa o desactiva los módulos de optimización y seguridad para tu sitio', 'alquipress'); ?></p>
                </div>
            </header>
            
            <?php if ($saved): ?>
                <div class="notice notice-success is-dismissible" style="margin: 0 0 24px;">
                    <p><strong>✓ <?php esc_html_e('Configuración guardada correctamente.', 'alquipress'); ?></strong></p>
                </div>
            <?php endif; ?>
            
            <div class="ap-content-row">
                <div class="ap-content-left" style="flex: 1;">
                    <section class="ap-recent-bookings">
                        <div class="ap-recent-bookings-header">
                            <h2 class="ap-recent-bookings-title"><?php esc_html_e('Módulos Disponibles', 'alquipress'); ?></h2>
                        </div>
                        <div style="padding: 20px;">
                            <form method="post">
                                <?php wp_nonce_field('alq_suite_settings_nonce'); ?>
                                
                                <?php foreach ($modules as $id => $config):
                                    $is_active = !empty($active_modules[$id]);
                                    ?>
                                    <div class="ap-module-card" style="background: var(--ap-surface); border: 1px solid var(--ap-border); border-radius: var(--ap-radius-md); padding: 20px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; transition: all 0.2s;">
                                        <div style="flex: 1;">
                                            <h3 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 600; color: var(--ap-text-primary);">
                                                <?php echo esc_html($config['name']); ?>
                                            </h3>
                                            <p style="margin: 0; font-size: 14px; color: var(--ap-text-secondary);">
                                                <?php echo esc_html($config['description']); ?>
                                            </p>
                                        </div>
                                        <label class="ap-toggle-switch" style="position: relative; display: inline-block; width: 50px; height: 26px; margin-left: 20px;">
                                            <input type="checkbox" name="modules[<?php echo esc_attr($id); ?>]" <?php checked($is_active); ?> style="opacity: 0; width: 0; height: 0;">
                                            <span class="ap-toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;">
                                                <span style="position: absolute; content: ''; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%;"></span>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                
                                <button type="submit" name="alq_save_suite_settings" class="button button-primary button-large" style="width: 100%; margin-top: 8px;">
                                    <?php esc_html_e('Guardar Configuración', 'alquipress'); ?>
                                </button>
                            </form>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.ap-module-card:hover {
    border-color: var(--ap-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transform: translateY(-1px);
}

.ap-toggle-switch input:checked + .ap-toggle-slider {
    background-color: var(--ap-primary);
}

.ap-toggle-switch input:checked + .ap-toggle-slider span {
    transform: translateX(24px);
}

.ap-toggle-switch input:focus + .ap-toggle-slider {
    box-shadow: 0 0 0 3px rgba(44, 153, 226, 0.1);
}
</style>
    <style>
        .alquipress-suite-admin {
            max-width: 900px;
            margin: 20px auto;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .alq-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alq-header h1 {
            color: white;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .alq-content {
            background: white;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .alq-module-card {
            border: 1px solid #f3f4f6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .alq-module-card:hover {
            border-color: #10b981;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .alq-module-info h3 {
            margin: 0 0 5px 0;
            color: #111827;
        }

        .alq-module-info p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .alq-toggle {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .alq-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #10b981;
        }

        input:checked+.slider:before {
            transform: translateX(24px);
        }

        .alq-save-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 20px;
            width: 100%;
        }

        .alq-save-btn:hover {
            background: #059669;
        }

        .alq-badge {
            background: #ecfdf5;
            color: #065f46;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-left: 10px;
        }
    </style>

    <div class="alq-header">
        <h1>ALQUIPRESS <span style="font-weight:300">Suite</span></h1>
        <span class="alq-badge">v1.0.0</span>
    </div>

    <div class="alq-content">
        <p>Activa o desactiva los módulos de optimización y seguridad para tu sitio.</p>

        <form method="post">
            <?php wp_nonce_field('alq_suite_settings_nonce'); ?>

            <?php foreach ($modules as $id => $config):
                $is_active = !empty($active_modules[$id]);
                ?>
                <div class="alq-module-card">
                    <div class="alq-module-info">
                        <h3>
                            <?php echo esc_html($config['name']); ?>
                        </h3>
                        <p>
                            <?php echo esc_html($config['description']); ?>
                        </p>
                    </div>
                    <label class="alq-toggle">
                        <input type="checkbox" name="modules[<?php echo esc_attr($id); ?>]" <?php checked($is_active); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            <?php endforeach; ?>

            <button type="submit" name="alq_save_suite_settings" class="alq-save-btn">
                Guardar Configuración
            </button>
        </form>
    </div>
</div>