<?php
/**
 * Vista de la página de ajustes de ALQUIPRESS
 */
if (!defined('ABSPATH')) exit;
?>
<div class="ap-wrap">
    <div class="ap-page-header">
        <h1>
            <span class="dashicons dashicons-admin-multisite"></span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </h1>
        <p>Bienvenido al centro de control de tu CRM. Activa o desactiva módulos según tus necesidades.</p>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('alquipress_modules_nonce'); ?>

        <div class="ap-card">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Activo</th>
                        <th>Módulo</th>
                        <th>Descripción</th>
                        <th style="width: 120px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->modules as $id => $module): ?>
                        <tr>
                            <td>
                                <label class="ap-switch">
                                    <input
                                        type="checkbox"
                                        name="modules[<?php echo esc_attr($id); ?>]"
                                        value="1"
                                        <?php checked($this->active_modules[$id] ?? false); ?>
                                    >
                                    <span class="ap-switch__slider"></span>
                                </label>
                            </td>
                            <td>
                                <strong class="ap-text-primary"><?php echo esc_html($module['name']); ?></strong>
                            </td>
                            <td class="ap-text-muted">
                                <?php echo esc_html($module['description']); ?>
                            </td>
                            <td>
                                <?php if ($this->active_modules[$id] ?? false): ?>
                                    <span class="ap-badge ap-badge--success">
                                        <span class="dashicons dashicons-yes-alt"></span> Activo
                                    </span>
                                <?php else: ?>
                                    <span class="ap-badge ap-badge--inactive">
                                        <span class="dashicons dashicons-dismiss"></span> Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="ap-submit-area">
            <button type="submit" name="alquipress_save_modules" class="ap-button ap-button--primary ap-button--large">
                Guardar Configuración de Módulos
            </button>
        </div>
    </form>

    <div class="ap-mt-10">
        <h2 class="ap-mb-5">Estado del Sistema</h2>
        <div class="ap-stats-grid">
            <div class="ap-stat-card">
                <span class="ap-stat-card__label">WordPress</span>
                <strong class="ap-stat-card__value"><?php echo get_bloginfo('version'); ?></strong>
            </div>
            <div class="ap-stat-card <?php echo defined('WC_VERSION') ? '' : 'ap-stat-card--error'; ?>">
                <span class="ap-stat-card__label">WooCommerce</span>
                <strong class="ap-stat-card__value"><?php echo defined('WC_VERSION') ? WC_VERSION : 'No instalado'; ?></strong>
            </div>
            <div class="ap-stat-card <?php echo class_exists('ACF') ? '' : 'ap-stat-card--warning'; ?>">
                <span class="ap-stat-card__label">ACF PRO</span>
                <strong class="ap-stat-card__value"><?php echo class_exists('ACF') ? '✓ Instalado' : '✗ Requerido'; ?></strong>
            </div>
            <div class="ap-stat-card <?php echo class_exists('\MailPoet\API\API') ? '' : 'ap-stat-card--inactive'; ?>">
                <span class="ap-stat-card__label">MailPoet</span>
                <strong class="ap-stat-card__value"><?php echo class_exists('\MailPoet\API\API') ? '✓ Instalado' : 'No instalado'; ?></strong>
            </div>
        </div>
    </div>
</div>
