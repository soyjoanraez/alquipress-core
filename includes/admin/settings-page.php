<?php
/**
 * Vista de la página de Ajustes ALQUIPRESS (diseño Pencil: Settings Dashboard)
 */
if (!defined('ABSPATH')) {
    exit;
}

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
$tabs = [
    'general' => ['label' => __('General', 'alquipress'), 'icon' => 'dashicons-building'],
    'bookings' => ['label' => __('Reservas', 'alquipress'), 'icon' => 'dashicons-calendar-alt'],
    'payments' => ['label' => __('Pagos', 'alquipress'), 'icon' => 'dashicons-money-alt'],
    'email' => ['label' => __('Email y notificaciones', 'alquipress'), 'icon' => 'dashicons-email'],
    'legal' => ['label' => __('Legal y cumplimiento', 'alquipress'), 'icon' => 'dashicons-shield'],
    'team' => ['label' => __('Equipo y permisos', 'alquipress'), 'icon' => 'dashicons-groups'],
    'advanced' => ['label' => __('Avanzado', 'alquipress'), 'icon' => 'dashicons-admin-tools'],
];
$base_url = admin_url('admin.php?page=alquipress-settings');
require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
?>
<div class="wrap alquipress-settings-page ap-has-sidebar">
    <div class="ap-owners-layout">
        <?php alquipress_render_sidebar('settings'); ?>
        <main class="ap-owners-main">
    <header class="ap-settings-header">
        <div class="ap-settings-header-left">
            <h1 class="ap-settings-title"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p class="ap-settings-subtitle"><?php esc_html_e('Configura preferencias del CRM, integraciones y permisos', 'alquipress'); ?></p>
        </div>
    </header>

    <div class="ap-settings-content">
        <nav class="ap-settings-tabs" role="tablist" aria-label="<?php esc_attr_e('Secciones de ajustes', 'alquipress'); ?>">
            <?php foreach ($tabs as $tab_key => $tab) : ?>
                <a href="<?php echo esc_url(add_query_arg('tab', $tab_key, $base_url)); ?>"
                   class="ap-settings-tab <?php echo $current_tab === $tab_key ? 'ap-settings-tab-active' : ''; ?>"
                   role="tab"
                   aria-selected="<?php echo $current_tab === $tab_key ? 'true' : 'false'; ?>">
                    <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                    <span><?php echo esc_html($tab['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <main class="ap-settings-main" role="tabpanel">
            <?php if ($current_tab === 'general') : ?>
                <?php
                $dashboard_templates = Alquipress_Module_Manager::get_dashboard_template_choices();
                $current_dashboard_template = Alquipress_Module_Manager::get_dashboard_template();
                ?>
                <div class="ap-settings-section">
                    <h2 class="ap-settings-section-title"><?php esc_html_e('Configuración general', 'alquipress'); ?></h2>
                    <p class="ap-settings-section-desc"><?php esc_html_e('Activa o desactiva módulos del CRM y define el estilo del dashboard.', 'alquipress'); ?></p>
                </div>

                <form method="post" action="" class="ap-settings-form">
                    <?php wp_nonce_field('alquipress_modules_nonce'); ?>
                    <div class="ap-settings-card">
                        <div class="ap-settings-card-head">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <h3 class="ap-settings-card-title"><?php esc_html_e('Módulos', 'alquipress'); ?></h3>
                        </div>
                        <div class="ap-settings-table-wrap">
                            <table class="ap-settings-modules-table">
                                <thead>
                                    <tr>
                                        <th class="ap-settings-th-check"><?php esc_html_e('Activo', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Módulo', 'alquipress'); ?></th>
                                        <th><?php esc_html_e('Descripción', 'alquipress'); ?></th>
                                        <th class="ap-settings-th-status"><?php esc_html_e('Estado', 'alquipress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ((isset($modules) ? $modules : []) as $id => $module) : ?>
                                        <tr>
                                            <td class="ap-settings-td-check">
                                                <label class="ap-settings-switch">
                                                    <input type="checkbox"
                                                           name="modules[<?php echo esc_attr($id); ?>]"
                                                           value="1"
                                                           <?php checked(isset($active_modules) && ($active_modules[$id] ?? false)); ?>>
                                                    <span class="ap-settings-slider"></span>
                                                </label>
                                            </td>
                                            <td class="ap-settings-td-name"><?php echo esc_html($module['name']); ?></td>
                                            <td class="ap-settings-td-desc"><?php echo esc_html($module['description']); ?></td>
                                            <td class="ap-settings-td-status">
                                                <?php if (isset($active_modules) && ($active_modules[$id] ?? false)) : ?>
                                                    <span class="ap-settings-badge ap-settings-badge-active"><?php esc_html_e('Activo', 'alquipress'); ?></span>
                                                <?php else : ?>
                                                    <span class="ap-settings-badge ap-settings-badge-inactive"><?php esc_html_e('Inactivo', 'alquipress'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="ap-settings-form-actions">
                            <button type="submit" name="alquipress_save_modules" class="ap-settings-btn ap-settings-btn-primary">
                                <?php esc_html_e('Guardar configuración de módulos', 'alquipress'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="ap-settings-card">
                        <div class="ap-settings-card-head">
                            <span class="dashicons dashicons-layout"></span>
                            <h3 class="ap-settings-card-title"><?php esc_html_e('Plantilla del dashboard', 'alquipress'); ?></h3>
                        </div>
                        <div class="ap-settings-template-grid">
                            <?php foreach ($dashboard_templates as $template_key => $template) : ?>
                                <label class="ap-settings-template-option">
                                    <input type="radio"
                                           name="dashboard_template"
                                           value="<?php echo esc_attr($template_key); ?>"
                                           <?php checked($current_dashboard_template, $template_key); ?>>
                                    <span class="ap-settings-template-content">
                                        <strong><?php echo esc_html($template['label']); ?></strong>
                                        <small><?php echo esc_html($template['description']); ?></small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="ap-settings-form-actions">
                            <button type="submit" name="alquipress_save_modules" class="ap-settings-btn ap-settings-btn-primary">
                                <?php esc_html_e('Guardar módulos y plantilla', 'alquipress'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="ap-settings-card">
                        <div class="ap-settings-card-head">
                            <span class="dashicons dashicons-art"></span>
                            <h3 class="ap-settings-card-title"><?php esc_html_e('Modo oscuro', 'alquipress'); ?></h3>
                        </div>
                        <div class="ap-settings-dark-mode-wrap">
                            <label class="ap-settings-switch">
                                <input type="checkbox" name="dark_mode" value="1" <?php checked(Alquipress_Module_Manager::is_dark_mode()); ?>>
                                <span class="ap-settings-slider"></span>
                            </label>
                            <span class="ap-settings-dark-mode-label"><?php esc_html_e('Activar tema oscuro en el CRM', 'alquipress'); ?></span>
                        </div>
                        <div class="ap-settings-form-actions">
                            <button type="submit" name="alquipress_save_modules" class="ap-settings-btn ap-settings-btn-primary">
                                <?php esc_html_e('Guardar preferencias', 'alquipress'); ?>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="ap-settings-section ap-settings-system">
                    <h2 class="ap-settings-section-title"><?php esc_html_e('Estado del sistema', 'alquipress'); ?></h2>
                    <div class="ap-settings-cards-grid">
                        <div class="ap-settings-status-card">
                            <span class="ap-settings-status-label"><?php esc_html_e('WordPress', 'alquipress'); ?></span>
                            <strong><?php echo esc_html(get_bloginfo('version')); ?></strong>
                        </div>
                        <div class="ap-settings-status-card">
                            <span class="ap-settings-status-label"><?php esc_html_e('WooCommerce', 'alquipress'); ?></span>
                            <strong><?php echo defined('WC_VERSION') ? esc_html(constant('WC_VERSION')) : esc_html__('No instalado', 'alquipress'); ?></strong>
                        </div>
                        <div class="ap-settings-status-card">
                            <span class="ap-settings-status-label"><?php esc_html_e('MailPoet', 'alquipress'); ?></span>
                            <strong><?php echo class_exists('\MailPoet\API\API') ? esc_html__('Instalado', 'alquipress') : esc_html__('No instalado', 'alquipress'); ?></strong>
                        </div>
                    </div>
                </div>

            <?php elseif ($current_tab === 'bookings') : ?>
                <div class="ap-settings-section">
                    <h2 class="ap-settings-section-title"><?php esc_html_e('Ajustes de reservas', 'alquipress'); ?></h2>
                    <p class="ap-settings-section-desc"><?php esc_html_e('Configura el motor de reservas propio (Ap_Booking) y enlaza con el pipeline y el calendario.', 'alquipress'); ?></p>
                </div>
                <?php
                $default_deposit = (float) get_option('ap_bookings_default_deposit_pct', 40);
                $default_min_nights = (int) get_option('ap_bookings_default_min_nights', 1);
                $default_max_nights = (int) get_option('ap_bookings_default_max_nights', 365);
                ?>
                <form method="post" action="" class="ap-settings-form">
                    <?php wp_nonce_field('alquipress_bookings_settings_nonce'); ?>
                    <div class="ap-settings-card">
                        <div class="ap-settings-card-head">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <h3 class="ap-settings-card-title"><?php esc_html_e('Motor de reservas Alquipress', 'alquipress'); ?></h3>
                        </div>
                        <div class="ap-settings-grid-two">
                            <div class="ap-settings-field">
                                <label for="ap_bookings_default_deposit_pct">
                                    <?php esc_html_e('Depósito por defecto (%)', 'alquipress'); ?>
                                </label>
                                <input
                                    type="number"
                                    id="ap_bookings_default_deposit_pct"
                                    name="ap_bookings_default_deposit_pct"
                                    min="0"
                                    max="100"
                                    step="1"
                                    value="<?php echo esc_attr($default_deposit); ?>"
                                />
                                <p class="description">
                                    <?php esc_html_e('Porcentaje del total que se cobra como depósito inicial. Se puede sobreescribir por propiedad.', 'alquipress'); ?>
                                </p>
                            </div>
                            <div class="ap-settings-field">
                                <label for="ap_bookings_default_min_nights">
                                    <?php esc_html_e('Noches mínimas por defecto', 'alquipress'); ?>
                                </label>
                                <input
                                    type="number"
                                    id="ap_bookings_default_min_nights"
                                    name="ap_bookings_default_min_nights"
                                    min="1"
                                    max="365"
                                    step="1"
                                    value="<?php echo esc_attr($default_min_nights); ?>"
                                />
                                <p class="description">
                                    <?php esc_html_e('Número mínimo de noches si la propiedad no define una regla específica.', 'alquipress'); ?>
                                </p>
                            </div>
                            <div class="ap-settings-field">
                                <label for="ap_bookings_default_max_nights">
                                    <?php esc_html_e('Noches máximas por defecto', 'alquipress'); ?>
                                </label>
                                <input
                                    type="number"
                                    id="ap_bookings_default_max_nights"
                                    name="ap_bookings_default_max_nights"
                                    min="1"
                                    max="730"
                                    step="1"
                                    value="<?php echo esc_attr($default_max_nights); ?>"
                                />
                                <p class="description">
                                    <?php esc_html_e('Número máximo de noches permitidas en una sola reserva.', 'alquipress'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="ap-settings-form-actions">
                            <button type="submit" name="alquipress_save_bookings_settings" class="ap-settings-btn ap-settings-btn-primary">
                                <?php esc_html_e('Guardar ajustes de reservas', 'alquipress'); ?>
                            </button>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-pipeline')); ?>" class="ap-settings-btn ap-settings-btn-outline">
                                <?php esc_html_e('Abrir Pipeline', 'alquipress'); ?>
                            </a>
                        </div>
                    </div>
                </form>

            <?php elseif ($current_tab === 'payments') : ?>
                <div class="ap-settings-section">
                    <h2 class="ap-settings-section-title"><?php esc_html_e('Pagos', 'alquipress'); ?></h2>
                    <p class="ap-settings-section-desc"><?php esc_html_e('Pasarelas de pago, depósitos y métodos de cobro.', 'alquipress'); ?></p>
                </div>
                <div class="ap-settings-card ap-settings-placeholder">
                    <p><?php esc_html_e('Configura Stripe, Redsys y demás pasarelas desde WooCommerce → Ajustes → Pagos.', 'alquipress'); ?></p>
                    <p><a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')); ?>" class="ap-settings-btn ap-settings-btn-outline"><?php esc_html_e('Ir a pagos WooCommerce', 'alquipress'); ?></a></p>
                </div>

            <?php elseif ($current_tab === 'email') : ?>
                <div class="ap-settings-section">
                    <h2 class="ap-settings-section-title"><?php esc_html_e('Email y notificaciones', 'alquipress'); ?></h2>
                    <p class="ap-settings-section-desc"><?php esc_html_e('Plantillas, automatizaciones y integración con MailPoet.', 'alquipress'); ?></p>
                </div>
                <div class="ap-settings-card ap-settings-placeholder">
                    <p><?php esc_html_e('La automatización de emails se gestiona con el módulo MailPoet y las notificaciones del CRM.', 'alquipress'); ?></p>
                </div>

            <?php elseif ($current_tab === 'legal') : ?>
                <div class="ap-settings-section">
                    <h2 class="ap-settings-section-title"><?php esc_html_e('Legal y cumplimiento', 'alquipress'); ?></h2>
                    <p class="ap-settings-section-desc"><?php esc_html_e('Política de privacidad, condiciones y documentos legales.', 'alquipress'); ?></p>
                </div>
                <div class="ap-settings-card ap-settings-placeholder">
                    <p><?php esc_html_e('Configura páginas legales desde WordPress → Ajustes → Privacidad y las opciones de tu tema.', 'alquipress'); ?></p>
                    <p><a href="<?php echo esc_url(admin_url('options-privacy.php')); ?>" class="ap-settings-btn ap-settings-btn-outline"><?php esc_html_e('Privacidad', 'alquipress'); ?></a></p>
                </div>

            <?php elseif ($current_tab === 'team') : ?>
                <div class="ap-settings-section">
                    <h2 class="ap-settings-section-title"><?php esc_html_e('Equipo y permisos', 'alquipress'); ?></h2>
                    <p class="ap-settings-section-desc"><?php esc_html_e('Roles de usuario y acceso al CRM.', 'alquipress'); ?></p>
                </div>
                <div class="ap-settings-card ap-settings-placeholder">
                    <p><?php esc_html_e('Gestiona usuarios y roles desde Usuarios. Los permisos del CRM dependen de los roles de WordPress.', 'alquipress'); ?></p>
                    <p><a href="<?php echo esc_url(admin_url('users.php')); ?>" class="ap-settings-btn ap-settings-btn-outline"><?php esc_html_e('Ver usuarios', 'alquipress'); ?></a></p>
                </div>

            <?php else : ?>
                <div class="ap-settings-section">
                    <h2 class="ap-settings-section-title"><?php esc_html_e('Avanzado', 'alquipress'); ?></h2>
                    <p class="ap-settings-section-desc"><?php esc_html_e('Opciones técnicas y depuración.', 'alquipress'); ?></p>
                </div>
                <div class="ap-settings-card ap-settings-placeholder">
                    <p><?php esc_html_e('Aquí podrás configurar opciones avanzadas en futuras versiones.', 'alquipress'); ?></p>
                </div>
            <?php endif; ?>
        </main>
    </div>
        </main>
    </div>
</div>
