<?php
/**
 * Vista de la página de ajustes de ALQUIPRESS
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-top: 20px;">
    <h1 style="display: flex; align-items: center; gap: 10px;">
        <span class="dashicons dashicons-admin-multisite" style="font-size: 2rem; width: 32px; height: 32px;"></span> 
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    <p style="font-size: 1.1em; color: #666;">Bienvenido al centro de control de tu CRM. Activa o desactiva módulos según tus necesidades.</p>
    
    <hr style="margin: 20px 0;">

    <form method="post" action="">
        <?php wp_nonce_field('alquipress_modules_nonce'); ?>
        
        <table class="wp-list-table widefat fixed striped" style="border: none; box-shadow: none;">
            <thead>
                <tr>
                    <th style="width: 50px; padding: 15px;">Activo</th>
                    <th style="padding: 15px;">Módulo</th>
                    <th style="padding: 15px;">Descripción</th>
                    <th style="padding: 15px; width: 120px;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->modules as $id => $module): ?>
                    <tr>
                        <td style="padding: 15px; vertical-align: middle;">
                            <label class="switch">
                                <input 
                                    type="checkbox" 
                                    name="modules[<?php echo esc_attr($id); ?>]" 
                                    value="1"
                                    <?php checked($this->active_modules[$id] ?? false); ?>
                                >
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td style="padding: 15px; vertical-align: middle;">
                            <strong style="font-size: 1.1em; color: #2271b1;"><?php echo esc_html($module['name']); ?></strong>
                        </td>
                        <td style="padding: 15px; vertical-align: middle; color: #555;">
                            <?php echo esc_html($module['description']); ?>
                        </td>
                        <td style="padding: 15px; vertical-align: middle;">
                            <?php if ($this->active_modules[$id] ?? false): ?>
                                <span class="badge status-active" style="background: #e7f6ed; color: #208d50; padding: 4px 10px; border-radius: 12px; font-weight: 500;">
                                    <span class="dashicons dashicons-yes-alt" style="font-size: 16px; width: 16px; height: 16px; vertical-align: text-bottom;"></span> Activo
                                </span>
                            <?php else: ?>
                                <span class="badge status-inactive" style="background: #f0f0f1; color: #646970; padding: 4px 10px; border-radius: 12px; font-weight: 500;">
                                    <span class="dashicons dashicons-dismiss" style="font-size: 16px; width: 16px; height: 16px; vertical-align: text-bottom;"></span> Inactivo
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 6px;">
            <input 
                type="submit" 
                name="alquipress_save_modules" 
                class="button button-primary button-large" 
                value="Guardar Configuración de Módulos"
                style="height: 46px; padding: 0 30px; font-size: 15px;"
            >
        </div>
    </form>
    
    <div style="margin-top: 40px;">
        <h2 style="border-bottom: 2px solid #f0f0f1; padding-bottom: 10px;">📊 Estado del Sistema</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
            <div class="status-card" style="background: #f0f6fb; padding: 15px; border-radius: 8px; border-left: 4px solid #2271b1;">
                <span style="display: block; font-size: 0.9em; color: #666;">WordPress</span>
                <strong><?php echo get_bloginfo('version'); ?></strong>
            </div>
            <div class="status-card" style="background: #f0f6fb; padding: 15px; border-radius: 8px; border-left: 4px solid <?php echo defined('WC_VERSION') ? '#2271b1' : '#d63638'; ?>;">
                <span style="display: block; font-size: 0.9em; color: #666;">WooCommerce</span>
                <strong><?php echo defined('WC_VERSION') ? WC_VERSION : 'No instalado'; ?></strong>
            </div>
            <div class="status-card" style="background: #f0f6fb; padding: 15px; border-radius: 8px; border-left: 4px solid <?php echo class_exists('ACF') ? '#2271b1' : '#ffa500'; ?>;">
                <span style="display: block; font-size: 0.9em; color: #666;">ACF PRO</span>
                <strong><?php echo class_exists('ACF') ? '✓ Instalado' : '✗ Requerido'; ?></strong>
            </div>
            <div class="status-card" style="background: #f0f6fb; padding: 15px; border-radius: 8px; border-left: 4px solid <?php echo class_exists('\MailPoet\API\API') ? '#2271b1' : '#666'; ?>;">
                <span style="display: block; font-size: 0.9em; color: #666;">MailPoet</span>
                <strong><?php echo class_exists('\MailPoet\API\API') ? '✓ Instalado' : 'No instalado'; ?></strong>
            </div>
        </div>
    </div>
</div>

<style>
/* Switch styling */
.switch {
  position: relative;
  display: inline-block;
  width: 40px;
  height: 22px;
}
.switch input { 
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
  -webkit-transition: .4s;
  transition: .4s;
}
.slider:before {
  position: absolute;
  content: "";
  height: 16px;
  width: 16px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}
input:checked + .slider {
  background-color: #2271b1;
}
input:focus + .slider {
  box-shadow: 0 0 1px #2271b1;
}
input:checked + .slider:before {
  -webkit-transform: translateX(18px);
  -ms-transform: translateX(18px);
  transform: translateX(18px);
}
.slider.round {
  border-radius: 22px;
}
.slider.round:before {
  border-radius: 50%;
}
</style>
