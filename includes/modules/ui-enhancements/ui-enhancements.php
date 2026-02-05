<?php
/**
 * Módulo: Mejoras UI
 * Mejoras visuales para CPTs y páginas de edición
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_UI_Enhancements
{

    public function __construct()
    {
        // Cargar estilos en admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Header tipo Dashboard en páginas de edición de CPTs Pencil
        add_action('edit_form_after_title', [$this, 'edit_screen_dashboard_header']);

        // Mejorar página de edición de propietario
        add_action('admin_head', [$this, 'owner_edit_page_styles']);

        // Mejorar página de edición de usuario (huéspedes)
        add_action('admin_head', [$this, 'guest_edit_page_styles']);
        
        // Añadir sidebar y layout para user-edit.php
        add_action('admin_footer', [$this, 'add_user_edit_sidebar']);
        
        // Ocultar metaboxes innecesarios en la edición de clientes
        add_action('admin_init', [$this, 'remove_unnecessary_user_meta_boxes'], 999);
        add_action('admin_head', [$this, 'hide_meta_boxes_css'], 999);
    }

    /**
     * Cargar assets CSS
     */
    public function enqueue_assets($hook)
    {
        $screen = get_current_screen();

        // Tema Pencil para CPTs: listados y edición de propietario, product, shop_order
        $cpt_pencil_screens = [
            'edit-propietario',
            'propietario',
            'edit-product',
            'product',
            'edit-shop_order',
            'shop_order',
        ];
        if ($screen && in_array($screen->id, $cpt_pencil_screens, true)) {
            wp_enqueue_style(
                'alquipress-admin-pencil',
                ALQUIPRESS_URL . 'includes/modules/ui-enhancements/assets/admin-pencil.css',
                [],
                ALQUIPRESS_VERSION
            );
            return;
        }

        // Resto de páginas: mejoras UI (user-edit, profile, post.php/post-new sin CPT Pencil)
        $allowed_hooks = [
            'post.php',
            'post-new.php',
            'user-edit.php',
            'profile.php',
            'user-new.php',
        ];
        if (!in_array($hook, $allowed_hooks, true)) {
            return;
        }

        // Para user-edit.php y profile.php, si es un cliente, cargar layout del dashboard
        if (in_array($hook, ['user-edit.php', 'profile.php'], true)) {
            $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
            $user = get_userdata($user_id);
            if ($user && (in_array('customer', $user->roles) || in_array('subscriber', $user->roles))) {
                // Cargar CSS del layout del dashboard
                wp_enqueue_style(
                    'alquipress-admin-layout',
                    ALQUIPRESS_URL . 'includes/admin/assets/alquipress-admin-layout.css',
                    [],
                    ALQUIPRESS_VERSION
                );

                // Estilos críticos del layout
                $critical_layout = '#wpcontent,#wpbody-content{background:#f8fafb!important;}'
                    . '.wrap{min-height:80vh!important;width:100%!important;position:relative!important;z-index:999998!important;max-width:none!important;margin-top:12px!important;padding:0!important;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif!important;}'
                    . 'body.user-edit .wrap,body.profile .wrap{display:flex!important;min-height:calc(100vh - 140px)!important;background:#f8fafb!important;border:1px solid #e8eef3!important;border-radius:16px!important;overflow:hidden!important;}'
                    . 'body.user-edit #wpbody-content,body.profile #wpbody-content{flex:1!important;min-width:0!important;padding:0!important;background:transparent!important;}';
                wp_add_inline_style('alquipress-admin-layout', $critical_layout);
            }
        }

        wp_enqueue_style(
            'alquipress-ui-enhancements',
            ALQUIPRESS_URL . 'includes/modules/ui-enhancements/assets/ui-enhancements.css',
            [],
            ALQUIPRESS_VERSION
        );
    }

    /**
     * Header tipo Dashboard en pantalla de edición (propietario, product, shop_order)
     * Para product (propiedad) se renderiza el layout completo Pencil "Edit Property".
     */
    public function edit_screen_dashboard_header($post)
    {
        if (!$post || !is_a($post, 'WP_Post')) {
            return;
        }
        if ($post->post_type === 'product') {
            $this->render_property_edit_pencil_layout($post);
            return;
        }
        $cpt_slugs = ['propietario', 'shop_order'];
        if (!in_array($post->post_type, $cpt_slugs, true)) {
            return;
        }

        $list_url = admin_url('edit.php?post_type=' . $post->post_type);
        $labels = [
            'propietario' => ['list' => __('Propietarios', 'alquipress'), 'edit' => __('Editar propietario', 'alquipress')],
            'shop_order'  => ['list' => __('Pedidos', 'alquipress'), 'edit' => __('Editar pedido', 'alquipress')],
        ];
        $label_list = $labels[$post->post_type]['list'] ?? $post->post_type;
        $label_edit = $labels[$post->post_type]['edit'] ?? __('Editar', 'alquipress');
        ?>
        <div class="ap-edit-header-bar">
            <a href="<?php echo esc_url($list_url); ?>" class="ap-edit-back-link">&larr; <?php echo esc_html(sprintf(__('Volver a %s', 'alquipress'), $label_list)); ?></a>
            <span class="ap-edit-subtitle"><?php echo esc_html($label_edit); ?></span>
        </div>
        <?php
    }

    /**
     * Layout Pencil "Edit Property Screen": breadcrumb, hero, property header, quick stats bar.
     */
    private function render_property_edit_pencil_layout($post)
    {
        $list_url = (string) admin_url('admin.php?page=alquipress-properties');
        $edit_url = get_edit_post_link($post->ID, 'raw');
        if (!is_string($edit_url) || $edit_url === '') {
            $edit_url = admin_url('post.php?post=' . (int) $post->ID . '&action=edit');
        }
        $view_url = get_permalink($post->ID);
        if (!is_string($view_url) || $view_url === '') {
            $view_url = '#';
        }
        $title = $post->post_title ?: __('Sin título', 'alquipress');
        $status = $post->post_status;
        $status_label = $status === 'publish' ? __('Activa', 'alquipress') : __('Borrador', 'alquipress');
        $status_class = $status === 'publish' ? 'ap-prop-status-active' : 'ap-prop-status-draft';
        $ref = get_post_meta($post->ID, '_sku', true);
        if ($ref === '') {
            $ref = function_exists('get_field') ? (string) get_field('referencia_interna', $post->ID) : '';
        }
        if ($ref === '') {
            $ref = '#' . $post->ID;
        }
        $address = '';
        $terms = get_the_terms($post->ID, 'poblacion');
        if (is_array($terms) && !empty($terms)) {
            $address = implode(', ', wp_list_pluck($terms, 'name'));
        }
        if ($address === '' && function_exists('get_field')) {
            $address = (string) get_field('direccion', $post->ID);
        }
        $product = function_exists('wc_get_product') ? wc_get_product($post->ID) : null;
        $price = $product ? $product->get_price() : '';
        $price_html = $product && $price !== '' && function_exists('wc_price') ? wc_price($price) : '—';
        $beds = null;
        $baths = null;
        $guests = null;
        $surface = '';
        if (function_exists('get_field')) {
            $rows = get_field('distribucion_habitaciones', $post->ID);
            $beds = is_array($rows) ? count($rows) : null;
            $baths = get_field('numero_banos', $post->ID);
            if (is_numeric($baths) && (int) $baths > 0) {
                $baths = (int) $baths;
            } else {
                $baths = null;
            }
            $guests = get_field('plazas', $post->ID) ?: get_field('capacidad', $post->ID);
            $guests = is_numeric($guests) && (int) $guests > 0 ? (int) $guests : null;
            $surface = get_field('superficie', $post->ID);
            $surface = is_string($surface) ? $surface : '';
        }
        $featured = $product && $product->get_catalog_visibility() === 'visible' ? true : false;
        $thumb_url = get_the_post_thumbnail_url($post->ID, 'large');
        $gallery_ids = $product && method_exists($product, 'get_gallery_image_ids') ? $product->get_gallery_image_ids() : [];
        if (!is_array($gallery_ids)) {
            $gallery_ids = [];
        }
        $rating_value = null;
        $rating_count = 0;
        if ($product && method_exists($product, 'get_average_rating')) {
            $avg = $product->get_average_rating();
            if (is_numeric($avg) && (float) $avg > 0) {
                $rating_value = (float) $avg;
            }
            if (method_exists($product, 'get_review_count')) {
                $rating_count = (int) $product->get_review_count();
            }
        }
        $occupancy_pct = null;
        $occupancy_label = '';
        if (function_exists('wc_get_orders')) {
            $month_start = gmdate('Y-m-01 00:00:00');
            $month_end = gmdate('Y-m-t 23:59:59');
            $orders = wc_get_orders([
                'status' => ['wc-completed', 'wc-processing'],
                'date_created' => $month_start . '...' . $month_end,
                'limit' => -1,
            ]);
            $nights_booked = 0;
            $days_in_month = (int) gmdate('t');
            foreach ($orders as $order) {
                foreach ($order->get_items() as $item) {
                    if ((int) $item->get_product_id() === (int) $post->ID) {
                        $nights = (int) $item->get_quantity();
                        if ($nights <= 0) {
                            $nights = 1;
                        }
                        $nights_booked += $nights;
                    }
                }
            }
            if ($days_in_month > 0) {
                $occupancy_pct = min(100, (int) round(($nights_booked / $days_in_month) * 100));
                $occupancy_label = sprintf(/* translators: month name */ __('Ocupación (%s)', 'alquipress'), gmdate('M'));
            }
        }
        if ($occupancy_label === '') {
            $occupancy_label = __('Ocupación', 'alquipress');
        }
        $dashboard_url = (string) admin_url('admin.php?page=alquipress-dashboard');
        $description_text = $post->post_excerpt ?: $post->post_content;
        $description_text = wp_trim_words(wp_strip_all_tags($description_text), 60);
        if ($description_text === '') {
            $description_text = __('Sin descripción.', 'alquipress');
        }
        ?>
        <div class="ap-property-edit-pencil">
            <header class="ap-prop-edit-header">
                <div class="ap-prop-edit-back-row">
                    <a href="<?php echo esc_url($list_url); ?>" class="ap-prop-edit-back">&larr; <?php esc_html_e('Volver a Propiedades', 'alquipress'); ?></a>
                    <div class="ap-prop-edit-actions">
                        <a href="<?php echo esc_url($view_url); ?>" target="_blank" rel="noopener" class="ap-prop-btn ap-prop-btn-secondary"><?php esc_html_e('Ver en web', 'alquipress'); ?></a>
                        <span class="ap-prop-btn ap-prop-btn-primary"><?php esc_html_e('Editar', 'alquipress'); ?></span>
                    </div>
                </div>
                <nav class="ap-prop-breadcrumb" aria-label="<?php esc_attr_e('Navegación', 'alquipress'); ?>">
                    <a href="<?php echo esc_url($dashboard_url); ?>"><?php esc_html_e('Panel', 'alquipress'); ?></a>
                    <span class="ap-prop-bc-sep" aria-hidden="true">›</span>
                    <a href="<?php echo esc_url($list_url); ?>"><?php esc_html_e('Propiedades', 'alquipress'); ?></a>
                    <span class="ap-prop-bc-sep" aria-hidden="true">›</span>
                    <span class="ap-prop-bc-current"><?php echo esc_html($title); ?></span>
                </nav>
            </header>

            <div class="ap-prop-hero">
                <div class="ap-prop-hero-main" style="<?php echo $thumb_url ? 'background-image:url(' . esc_url($thumb_url) . ')' : 'background:#e8eef3'; ?>">
                    <?php if (!$thumb_url) : ?>
                        <span class="ap-prop-hero-placeholder"><?php esc_html_e('Imagen destacada', 'alquipress'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="ap-prop-hero-thumbs">
                    <?php
                    $thumbs = array_slice($gallery_ids, 0, 4);
                    foreach ($thumbs as $img_id) :
                        $src = wp_get_attachment_image_url($img_id, 'medium');
                        if (!$src) {
                            continue;
                        }
                        ?>
                        <div class="ap-prop-thumb" style="background-image:url(<?php echo esc_url($src); ?>)"></div>
                    <?php endforeach; ?>
                    <?php for ($i = count($thumbs); $i < 4; $i++) : ?>
                        <div class="ap-prop-thumb ap-prop-thumb-empty"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="ap-prop-header-block">
                <div class="ap-prop-title-row">
                    <h2 class="ap-prop-title"><?php echo esc_html($title); ?></h2>
                    <span class="ap-prop-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                    <?php if ($featured) : ?>
                        <span class="ap-prop-badge ap-prop-badge-featured"><?php esc_html_e('Destacada', 'alquipress'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="ap-prop-meta-row">
                    <span class="ap-prop-ref-label"><?php esc_html_e('Ref. interna:', 'alquipress'); ?></span>
                    <code class="ap-prop-ref"><?php echo esc_html($ref); ?></code>
                </div>
                <?php if ($address !== '') : ?>
                <div class="ap-prop-address-row">
                    <span class="dashicons dashicons-location" aria-hidden="true"></span>
                    <span><?php echo esc_html($address); ?></span>
                </div>
                <?php endif; ?>
                <div class="ap-prop-url-row">
                    <span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
                    <a href="<?php echo esc_url($view_url); ?>" target="_blank" rel="noopener" class="ap-prop-url"><?php echo esc_html($view_url); ?></a>
                    <button type="button" class="ap-prop-copy-url" data-url="<?php echo esc_attr($view_url); ?>" title="<?php esc_attr_e('Copiar enlace', 'alquipress'); ?>"><span class="dashicons dashicons-admin-page"></span></button>
                </div>
            </div>

            <div class="ap-prop-quick-stats">
                <div class="ap-prop-stat">
                    <span class="dashicons dashicons-bed" aria-hidden="true"></span>
                    <div class="ap-prop-stat-content">
                        <span class="ap-prop-stat-value"><?php echo $beds !== null ? (int) $beds : '—'; ?></span>
                        <span class="ap-prop-stat-label"><?php esc_html_e('Habitaciones', 'alquipress'); ?></span>
                    </div>
                </div>
                <div class="ap-prop-stat">
                    <span class="dashicons dashicons-share" aria-hidden="true"></span>
                    <div class="ap-prop-stat-content">
                        <span class="ap-prop-stat-value"><?php echo $baths !== null ? (int) $baths : '—'; ?></span>
                        <span class="ap-prop-stat-label"><?php esc_html_e('Baños', 'alquipress'); ?></span>
                    </div>
                </div>
                <div class="ap-prop-stat">
                    <span class="dashicons dashicons-groups" aria-hidden="true"></span>
                    <div class="ap-prop-stat-content">
                        <span class="ap-prop-stat-value"><?php echo $guests !== null ? (int) $guests : '—'; ?></span>
                        <span class="ap-prop-stat-label"><?php esc_html_e('Plazas', 'alquipress'); ?></span>
                    </div>
                </div>
                <?php if ($surface !== '') : ?>
                <div class="ap-prop-stat">
                    <span class="dashicons dashicons-editor-expand" aria-hidden="true"></span>
                    <div class="ap-prop-stat-content">
                        <span class="ap-prop-stat-value"><?php echo esc_html($surface); ?></span>
                        <span class="ap-prop-stat-label"><?php esc_html_e('Superficie', 'alquipress'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="ap-prop-stat ap-prop-stat-rating">
                    <span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
                    <div class="ap-prop-stat-content">
                        <span class="ap-prop-stat-value"><?php echo $rating_value !== null ? number_format_i18n($rating_value, 1) : '—'; ?></span>
                        <span class="ap-prop-stat-label"><?php echo $rating_count > 0 ? sprintf(/* translators: number of reviews */ _n('%d valoración', '%d valoraciones', $rating_count, 'alquipress'), $rating_count) : esc_html__('Valoración', 'alquipress'); ?></span>
                    </div>
                </div>
                <div class="ap-prop-stat">
                    <span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
                    <div class="ap-prop-stat-content">
                        <span class="ap-prop-stat-value"><?php echo wp_kses_post($price_html); ?></span>
                        <span class="ap-prop-stat-label"><?php esc_html_e('Precio/noche', 'alquipress'); ?></span>
                    </div>
                </div>
                <div class="ap-prop-stat ap-prop-stat-occupancy">
                    <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                    <div class="ap-prop-stat-content">
                        <span class="ap-prop-stat-value"><?php echo $occupancy_pct !== null ? (int) $occupancy_pct . '%' : '—'; ?></span>
                        <span class="ap-prop-stat-label"><?php echo esc_html($occupancy_label); ?></span>
                    </div>
                </div>
            </div>

            <nav class="ap-prop-tabs-nav" role="tablist" aria-label="<?php esc_attr_e('Secciones de la propiedad', 'alquipress'); ?>">
                <button type="button" class="ap-prop-tab is-active" role="tab" id="ap-prop-tab-overview" aria-selected="true" aria-controls="ap-prop-panel-overview" data-tab="overview"><?php esc_html_e('Overview', 'alquipress'); ?></button>
                <button type="button" class="ap-prop-tab" role="tab" id="ap-prop-tab-calendario" aria-selected="false" aria-controls="ap-prop-panel-calendario" data-tab="calendario"><?php esc_html_e('Calendario', 'alquipress'); ?></button>
                <button type="button" class="ap-prop-tab" role="tab" id="ap-prop-tab-rendimiento" aria-selected="false" aria-controls="ap-prop-panel-rendimiento" data-tab="rendimiento"><?php esc_html_e('Rendimiento', 'alquipress'); ?></button>
                <button type="button" class="ap-prop-tab" role="tab" id="ap-prop-tab-documentos" aria-selected="false" aria-controls="ap-prop-panel-documentos" data-tab="documentos"><?php esc_html_e('Documentos', 'alquipress'); ?></button>
                <button type="button" class="ap-prop-tab" role="tab" id="ap-prop-tab-propietario" aria-selected="false" aria-controls="ap-prop-panel-propietario" data-tab="propietario"><?php esc_html_e('Propietario', 'alquipress'); ?></button>
            </nav>

            <div id="ap-prop-panel-overview" class="ap-prop-tab-panel is-active" role="tabpanel" aria-labelledby="ap-prop-tab-overview">
                <div class="ap-prop-overview-layout">
                    <div class="ap-prop-overview-left">
                        <div class="ap-prop-card ap-prop-card-description">
                            <h3 class="ap-prop-card-title"><?php esc_html_e('Sobre esta propiedad', 'alquipress'); ?></h3>
                            <p class="ap-prop-card-text"><?php echo esc_html($description_text); ?></p>
                        </div>
                        <div class="ap-prop-card ap-prop-card-amenities">
                            <h3 class="ap-prop-card-title"><?php esc_html_e('Características y equipamiento', 'alquipress'); ?></h3>
                            <p class="ap-prop-card-muted"><?php esc_html_e('Definidas en los campos personalizados (taxonomías, ACF).', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-prop-card ap-prop-card-rooms">
                            <h3 class="ap-prop-card-title"><?php esc_html_e('Configuración de habitaciones', 'alquipress'); ?></h3>
                            <p class="ap-prop-card-muted"><?php echo $beds !== null ? sprintf(/* translators: number of rooms */ _n('%d habitación', '%d habitaciones', (int) $beds, 'alquipress'), (int) $beds) : esc_html__('Sin datos.', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-prop-card ap-prop-card-location">
                            <h3 class="ap-prop-card-title"><?php esc_html_e('Ubicación', 'alquipress'); ?></h3>
                            <p class="ap-prop-card-text"><?php echo $address !== '' ? esc_html($address) : esc_html__('Sin dirección definida.', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-prop-card ap-prop-card-rules">
                            <h3 class="ap-prop-card-title"><?php esc_html_e('Normas y políticas', 'alquipress'); ?></h3>
                            <p class="ap-prop-card-muted"><?php esc_html_e('Configurables en el producto o campos ACF.', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-prop-card ap-prop-card-product-data">
                            <h3 class="ap-prop-card-title"><?php esc_html_e('Datos del producto y campos personalizados', 'alquipress'); ?></h3>
                            <div id="ap-prop-overview-content"></div>
                        </div>
                    </div>
                    <aside class="ap-prop-overview-sidebar">
                        <div class="ap-prop-widget ap-prop-widget-status">
                            <h4 class="ap-prop-widget-title"><?php esc_html_e('Estado de la propiedad', 'alquipress'); ?></h4>
                            <div class="ap-prop-widget-status-badge <?php echo esc_attr($status_class); ?>">
                                <span class="ap-prop-widget-status-dot"></span>
                                <span class="ap-prop-widget-status-text"><?php echo esc_html($status_label); ?></span>
                            </div>
                            <p class="ap-prop-widget-meta"><?php echo esc_html($ref); ?> · <?php echo $beds !== null ? (int) $beds . ' ' . esc_html__('hab.', 'alquipress') : ''; ?></p>
                        </div>
                        <div class="ap-prop-widget ap-prop-widget-next-booking">
                            <h4 class="ap-prop-widget-title"><?php esc_html_e('Próxima reserva', 'alquipress'); ?></h4>
                            <p class="ap-prop-widget-muted"><?php esc_html_e('Sin reservas próximas o no disponibles.', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-prop-widget ap-prop-widget-stats">
                            <h4 class="ap-prop-widget-title"><?php esc_html_e('Este mes', 'alquipress'); ?></h4>
                            <div class="ap-prop-widget-stat-row">
                                <span class="ap-prop-widget-stat-label"><?php esc_html_e('Ingresos', 'alquipress'); ?></span>
                                <span class="ap-prop-widget-stat-value"><?php echo wp_kses_post($price_html); ?></span>
                            </div>
                            <div class="ap-prop-widget-stat-row">
                                <span class="ap-prop-widget-stat-label"><?php esc_html_e('Ocupación', 'alquipress'); ?></span>
                                <span class="ap-prop-widget-stat-value"><?php echo $occupancy_pct !== null ? (int) $occupancy_pct . '%' : '—'; ?></span>
                            </div>
                        </div>
                        <div class="ap-prop-widget ap-prop-widget-actions">
                            <h4 class="ap-prop-widget-title"><?php esc_html_e('Acciones rápidas', 'alquipress'); ?></h4>
                            <div class="ap-prop-widget-actions-list">
                                <a href="<?php echo esc_url($view_url); ?>" target="_blank" rel="noopener" class="ap-prop-widget-action-btn"><?php esc_html_e('Ver en web', 'alquipress'); ?></a>
                                <a href="<?php echo esc_url($list_url); ?>" class="ap-prop-widget-action-btn"><?php esc_html_e('Volver a propiedades', 'alquipress'); ?></a>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>

            <div id="ap-prop-panel-calendario" class="ap-prop-tab-panel" role="tabpanel" aria-labelledby="ap-prop-tab-calendario" hidden>
                <div class="ap-prop-tab-placeholder">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('Calendario de reservas de esta propiedad.', 'alquipress'); ?></p>
                    <p class="ap-prop-tab-placeholder-note"><?php esc_html_e('Próximamente: vista de calendario integrada.', 'alquipress'); ?></p>
                </div>
            </div>

            <div id="ap-prop-panel-rendimiento" class="ap-prop-tab-panel" role="tabpanel" aria-labelledby="ap-prop-tab-rendimiento" hidden>
                <div class="ap-prop-tab-placeholder">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('Rendimiento e ingresos de esta propiedad.', 'alquipress'); ?></p>
                    <p class="ap-prop-tab-placeholder-note"><?php esc_html_e('Próximamente: gráficos y métricas.', 'alquipress'); ?></p>
                </div>
            </div>

            <div id="ap-prop-panel-documentos" class="ap-prop-tab-panel" role="tabpanel" aria-labelledby="ap-prop-tab-documentos" hidden>
                <div class="ap-prop-tab-placeholder">
                    <span class="dashicons dashicons-media-default"></span>
                    <p><?php esc_html_e('Documentos adjuntos a la propiedad.', 'alquipress'); ?></p>
                    <p class="ap-prop-tab-placeholder-note"><?php esc_html_e('Próximamente: gestor de documentos.', 'alquipress'); ?></p>
                </div>
            </div>

            <div id="ap-prop-panel-propietario" class="ap-prop-tab-panel" role="tabpanel" aria-labelledby="ap-prop-tab-propietario" hidden>
                <div class="ap-prop-tab-placeholder">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p><?php esc_html_e('Propietario asignado a esta propiedad.', 'alquipress'); ?></p>
                    <p class="ap-prop-tab-placeholder-note"><?php esc_html_e('Próximamente: enlace a ficha del propietario.', 'alquipress'); ?></p>
                </div>
            </div>
        </div>
        <script>
        (function() {
            var btn = document.querySelector('.ap-prop-copy-url');
            if (btn && btn.dataset.url) {
                btn.addEventListener('click', function() {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(btn.dataset.url).then(function() {
                            var dashicons = btn.querySelector('.dashicons');
                            if (dashicons) { dashicons.className = 'dashicons dashicons-yes'; }
                            setTimeout(function() { if (dashicons) dashicons.className = 'dashicons dashicons-admin-page'; }, 1500);
                        });
                    }
                });
            }

            var pencil = document.querySelector('.ap-property-edit-pencil');
            if (pencil) {
                var overviewContent = document.getElementById('ap-prop-overview-content');
                if (overviewContent) {
                    // Esperar a que ACF cargue completamente los campos
                    setTimeout(function() {
                        // Buscar todos los meta boxes de ACF en el área principal
                        var normalSortables = document.getElementById('normal-sortables');
                        var postBodyContent = document.getElementById('post-body-content');
                        
                        var metaBoxesToMove = [];
                        
                        // Buscar meta boxes en #normal-sortables
                        if (normalSortables) {
                            var boxes = normalSortables.querySelectorAll('.postbox');
                            boxes.forEach(function(box) {
                                // Solo mover meta boxes que contengan campos ACF
                                if (box.querySelector('.acf-fields') || box.querySelector('.acf-field-group')) {
                                    metaBoxesToMove.push(box);
                                }
                            });
                        }
                        
                        // Buscar meta boxes directamente en #post-body-content
                        if (postBodyContent) {
                            var boxes = postBodyContent.querySelectorAll('.postbox');
                            boxes.forEach(function(box) {
                                if (box.querySelector('.acf-fields') || box.querySelector('.acf-field-group')) {
                                    // Evitar duplicados
                                    if (metaBoxesToMove.indexOf(box) === -1) {
                                        metaBoxesToMove.push(box);
                                    }
                                }
                            });
                        }
                        
                        // Mover los meta boxes al contenedor overview
                        metaBoxesToMove.forEach(function(box) {
                            if (!overviewContent.contains(box)) {
                                overviewContent.appendChild(box);
                            }
                        });
                        
                        // Ocultar contenedores vacíos después de mover
                        if (normalSortables && normalSortables.children.length === 0) {
                            normalSortables.style.display = 'none';
                        }
                        if (postBodyContent && postBodyContent.children.length === 0) {
                            postBodyContent.style.display = 'none';
                        }
                    }, 100);
                }
                var tabs = pencil.querySelectorAll('.ap-prop-tab');
                var panels = pencil.querySelectorAll('.ap-prop-tab-panel');
                function setActiveTab(tab) {
                    var tabId = tab.getAttribute('data-tab');
                    tabs.forEach(function(t) { t.classList.remove('is-active'); t.setAttribute('aria-selected', 'false'); });
                    panels.forEach(function(p) { p.classList.remove('is-active'); p.hidden = true; });
                    tab.classList.add('is-active');
                    tab.setAttribute('aria-selected', 'true');
                    var panel = document.getElementById(tab.getAttribute('aria-controls'));
                    if (panel) { panel.classList.add('is-active'); panel.hidden = false; }
                    document.body.classList.toggle('ap-prop-tab-non-overview', tabId !== 'overview');
                }
                tabs.forEach(function(tab) {
                    tab.addEventListener('click', function() { setActiveTab(tab); });
                });
            }
        })();
        </script>
        <?php
    }

    /**
     * Estilos inline para página de propietario
     */
    public function owner_edit_page_styles()
    {
        global $post;

        if (!$post || $post->post_type !== 'propietario') {
            return;
        }

        ?>
        <style>
            /* Warning Banner para Sección Financiera */
            .acf-tab-wrap .acf-tab-group li[data-key*="finance"],
            .acf-tab-wrap .acf-tab-group li[data-key*="financiero"] {
                background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                border-left: 4px solid #f59e0b;
                font-weight: 600;
            }

            .acf-tab-wrap .acf-tab-group li[data-key*="finance"]:hover,
            .acf-tab-wrap .acf-tab-group li[data-key*="financiero"]:hover {
                background: #fef3c7;
            }

            /* Estilo mejorado para campo IBAN */
            .acf-field[data-name="owner_iban"],
            .acf-field[data-name="datos_bancarios_iban"] {
                position: relative;
            }

            /* Indicador de campo sensible */
            .acf-field[data-name="owner_iban"]::before,
            .acf-field[data-name="datos_bancarios_iban"]::before {
                content: "⚠️ DATO SENSIBLE - ACCESO REGISTRADO";
                display: block;
                background: #dc3232;
                color: white;
                padding: 8px 15px;
                border-radius: 4px 4px 0 0;
                font-weight: 700;
                font-size: 11px;
                text-align: center;
                letter-spacing: 1px;
                margin: -20px -20px 15px -20px;
            }

            /* Mejorar tabs */
            .acf-tab-wrap .acf-tab-group {
                background: #f9fafb;
                border-radius: 8px;
                padding: 10px;
                margin-bottom: 20px;
            }

            .acf-tab-wrap .acf-tab-group li {
                border-radius: 6px;
                transition: all 0.2s;
            }

            .acf-tab-wrap .acf-tab-group li.active {
                background: white;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            /* Mejorar campos de contacto */
            .acf-field[data-name="owner_phone"],
            .acf-field[data-name="owner_email_management"],
            .acf-field[data-name="owner_whatsapp"] {
                background: #f0f6fc;
                padding: 15px;
                border-radius: 6px;
                border-left: 3px solid #2ea2cc;
            }

            /* Título del post más visible */
            #post-body #titlediv #title {
                font-size: 24px;
                font-weight: 600;
                padding: 12px;
            }
        </style>
        <?php
    }

    /**
     * Estilos inline para página de usuario (huésped)
     */
    public function guest_edit_page_styles()
    {
        $screen = get_current_screen();

        if (!$screen || !in_array($screen->id, ['user-edit', 'profile'])) {
            return;
        }

        // Solo aplicar si es un usuario con rol customer (cliente) o subscriber
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
        $user = get_userdata($user_id);
        if (!$user || (!in_array('customer', $user->roles) && !in_array('subscriber', $user->roles))) {
            return;
        }

        ?>
        <style>
            /* Estilos consistentes con dashboard Alquipress */
            :root {
                --ap-background: #f8fafb;
                --ap-surface: #ffffff;
                --ap-border: #e8eef3;
                --ap-primary: #2c99e2;
                --ap-text-primary: #0e161b;
                --ap-text-secondary: #507a95;
                --ap-warning: #f59e0b;
                --ap-radius-lg: 12px;
                --ap-radius-md: 8px;
            }

            /* Mejorar sección de campos ACF del huésped */
            .acf-field-group-crm-cliente {
                background: var(--ap-surface);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius-lg);
                padding: 24px;
                margin-top: 24px;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            }

            .acf-field-group-crm-cliente .acf-field-group-header {
                border-bottom: 1px solid var(--ap-border);
                padding-bottom: 12px;
                margin-bottom: 20px;
            }

            /* Badge VIP en selector */
            .acf-field[data-name="guest_status"] select {
                height: 36px;
                padding: 0 10px;
                border: 1px solid var(--ap-border);
                border-radius: 6px;
                font-size: 14px;
            }

            .acf-field[data-name="guest_status"] select option[value="vip"] {
                background: #fef3c7;
                color: #92400e;
                font-weight: 600;
            }

            .acf-field[data-name="guest_status"] select option[value="blacklist"] {
                background: #fee2e2;
                color: #991b1b;
                font-weight: 600;
            }

            /* Rating con estrellas */
            .acf-field[data-name="guest_rating"] {
                background: #fffbeb;
                padding: 16px;
                border-radius: var(--ap-radius-md);
                border-left: 3px solid var(--ap-warning);
            }

            .acf-field[data-name="guest_rating"] input[type="number"] {
                height: 36px;
                padding: 0 10px;
                border: 1px solid var(--ap-border);
                border-radius: 6px;
                font-size: 14px;
            }

            /* Preferencias en grid */
            .acf-field[data-name="guest_preferences"] .acf-checkbox-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .acf-field[data-name="guest_preferences"] .acf-checkbox-list label {
                background: var(--ap-background);
                padding: 12px;
                border-radius: var(--ap-radius-md);
                border: 2px solid var(--ap-border);
                transition: all 0.2s;
            }

            .acf-field[data-name="guest_preferences"] .acf-checkbox-list label:hover {
                border-color: var(--ap-primary);
                background: rgba(44, 153, 226, 0.06);
            }

            .acf-field[data-name="guest_preferences"] .acf-checkbox-list input:checked + label {
                border-color: var(--ap-primary);
                background: rgba(44, 153, 226, 0.1);
                font-weight: 600;
            }

            /* Notas privadas */
            .acf-field[data-name="guest_internal_notes"] {
                background: #fffbeb;
                padding: 16px;
                border-radius: var(--ap-radius-md);
                border-left: 3px solid var(--ap-warning);
            }

            .acf-field[data-name="guest_internal_notes"] .acf-label label::before {
                content: "🔒 ";
                font-size: 16px;
            }

            /* Documentos mejorados */
            .acf-field[data-name="guest_documents"] {
                background: var(--ap-surface);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius-lg);
                padding: 20px;
            }

            .acf-field[data-name="guest_documents"] .acf-repeater {
                border: none;
            }

            .acf-field[data-name="guest_documents"] .acf-repeater .acf-row {
                background: var(--ap-background);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius-md);
                margin-bottom: 12px;
            }

            .acf-field[data-name="guest_documents"] .acf-file-uploader {
                background: var(--ap-surface);
                border: 2px dashed var(--ap-border);
                border-radius: var(--ap-radius-md);
                padding: 20px;
            }

            /* Inputs consistentes */
            .acf-field input[type="text"],
            .acf-field input[type="email"],
            .acf-field input[type="tel"],
            .acf-field input[type="date"],
            .acf-field select {
                height: 36px;
                padding: 0 10px;
                border: 1px solid var(--ap-border);
                border-radius: 6px;
                font-size: 14px;
            }

            .acf-field input:focus,
            .acf-field select:focus {
                border-color: var(--ap-primary);
                outline: none;
                box-shadow: 0 0 0 3px rgba(44, 153, 226, 0.1);
            }

            /* Layout del dashboard para user-edit */
            body.user-edit .wrap,
            body.profile .wrap {
                display: flex !important;
                min-height: calc(100vh - 140px) !important;
                background: #f8fafb !important;
                border: 1px solid #e8eef3 !important;
                border-radius: 16px !important;
                overflow: hidden !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                position: relative !important;
            }

            body.user-edit .wrap .ap-owners-layout,
            body.profile .wrap .ap-owners-layout {
                width: 100% !important;
                display: flex !important;
            }

            body.user-edit #wpbody-content,
            body.profile #wpbody-content {
                flex: 1 !important;
                min-width: 0 !important;
                padding: 0 !important;
                background: transparent !important;
            }

            /* Ocultar sidebar de WordPress si existe */
            body.user-edit #wpbody,
            body.profile #wpbody {
                margin: 0 !important;
            }

            /* Mejorar header de la página */
            body.user-edit .wrap h1,
            body.profile .wrap h1 {
                font-size: 28px !important;
                font-weight: 700 !important;
                color: var(--ap-text-primary) !important;
                margin-bottom: 8px !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            }

            /* Mejorar formularios */
            body.user-edit form-table th,
            body.profile form-table th {
                font-weight: 600 !important;
                font-size: 13px !important;
                color: var(--ap-text-secondary) !important;
                padding: 12px 10px 12px 0 !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            }

            body.user-edit form-table td,
            body.profile form-table td {
                padding: 12px 10px !important;
            }

            body.user-edit input[type="text"],
            body.user-edit input[type="email"],
            body.user-edit input[type="password"],
            body.user-edit input[type="url"],
            body.user-edit select,
            body.profile input[type="text"],
            body.profile input[type="email"],
            body.profile input[type="password"],
            body.profile input[type="url"],
            body.profile select {
                height: 36px !important;
                padding: 0 10px !important;
                border: 1px solid var(--ap-border) !important;
                border-radius: 6px !important;
                font-size: 14px !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
            }

            body.user-edit input:focus,
            body.user-edit select:focus,
            body.profile input:focus,
            body.profile select:focus {
                border-color: var(--ap-primary) !important;
                outline: none !important;
                box-shadow: 0 0 0 3px rgba(44, 153, 226, 0.1) !important;
            }

            /* Mejorar botones */
            body.user-edit .button,
            body.profile .button {
                height: 36px !important;
                padding: 0 18px !important;
                border-radius: var(--ap-radius-md) !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                transition: background 0.2s, border-color 0.2s !important;
            }

            body.user-edit .button-primary,
            body.profile .button-primary {
                background: var(--ap-primary) !important;
                border-color: var(--ap-primary) !important;
                color: #fff !important;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
            }

            body.user-edit .button-primary:hover,
            body.profile .button-primary:hover {
                background: #2380c7 !important;
                border-color: #2380c7 !important;
            }

            /* Mejorar postboxes */
            body.user-edit .postbox,
            body.profile .postbox {
                background: var(--ap-surface) !important;
                border: 1px solid var(--ap-border) !important;
                border-radius: var(--ap-radius-lg) !important;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
                margin-bottom: 20px !important;
            }

            body.user-edit .postbox .postbox-header,
            body.profile .postbox .postbox-header {
                background: var(--ap-background) !important;
                border-bottom: 1px solid var(--ap-border) !important;
                padding: 12px 16px !important;
            }

            body.user-edit .postbox .postbox-header h2,
            body.profile .postbox .postbox-header h2 {
                font-size: 16px !important;
                font-weight: 600 !important;
                color: var(--ap-text-primary) !important;
                font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            }

            body.user-edit .postbox .inside,
            body.profile .postbox .inside {
                padding: 20px !important;
            }
        </style>
        <?php
    }

    /**
     * Añadir sidebar y layout para user-edit.php usando JavaScript
     */
    public function add_user_edit_sidebar()
    {
        $screen = get_current_screen();
        
        // Debug: verificar screen
        if (!$screen) {
            // Intentar detectar por hook o URL
            global $pagenow;
            if ($pagenow !== 'user-edit.php' && $pagenow !== 'profile.php') {
                return;
            }
        } elseif (!in_array($screen->id, ['user-edit', 'profile'])) {
            return;
        }

        // Solo añadir sidebar si es customer o subscriber
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
        $user = get_userdata($user_id);
        $is_customer_or_subscriber = $user && (in_array('customer', $user->roles) || in_array('subscriber', $user->roles));
        
        if ($is_customer_or_subscriber) {
            require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
            
            // Obtener el HTML del sidebar
            ob_start();
            alquipress_render_sidebar('clients');
            $sidebar_html = ob_get_clean();
        } else {
            $sidebar_html = '';
        }

        ?>
        <div id="ap-sidebar-html" style="display:none;"><?php echo $sidebar_html; ?></div>
        <script>
        (function() {
            function initUserEditLayout() {
                // Verificar si estamos en la página correcta
                var isUserEditPage = document.body.classList.contains('user-edit') || 
                                     document.body.classList.contains('profile') ||
                                     window.location.href.indexOf('user-edit.php') !== -1 ||
                                     window.location.href.indexOf('profile.php') !== -1;
                
                if (!isUserEditPage) {
                    return;
                }

                var wrap = document.querySelector('.wrap');
                if (!wrap || wrap.querySelector('.ap-owners-layout')) {
                    return; // Ya está inicializado
                }

                // Obtener HTML del sidebar desde el div oculto
                var sidebarContainer = document.getElementById('ap-sidebar-html');
                if (!sidebarContainer || !sidebarContainer.innerHTML.trim()) {
                    return;
                }
                
                var sidebarHtml = sidebarContainer.innerHTML;

                // Crear contenedor del layout
                var layout = document.createElement('div');
                layout.className = 'ap-owners-layout';
                layout.style.cssText = 'display:flex!important;min-height:calc(100vh - 140px)!important;background:#f8fafb!important;border:1px solid #e8eef3!important;border-radius:16px!important;overflow:hidden!important;';

                // Crear sidebar
                var sidebar = document.createElement('aside');
                sidebar.className = 'ap-owners-sidebar';
                sidebar.style.cssText = 'width:256px!important;min-width:256px!important;background:#ffffff!important;border-right:1px solid #e8eef3!important;display:flex!important;flex-direction:column!important;';
                sidebar.innerHTML = sidebarHtml;

                // Crear main content wrapper
                var main = document.createElement('main');
                main.className = 'ap-owners-main';
                main.style.cssText = 'flex:1!important;min-width:0!important;padding:32px!important;background:#f8fafb!important;';

                // Mover contenido existente al main (preservar orden)
                var children = Array.from(wrap.childNodes);
                children.forEach(function(child) {
                    if (child.nodeType === 1 && child.id !== 'ap-sidebar-html' && !child.classList.contains('ap-owners-layout')) {
                        main.appendChild(child);
                    }
                });

                // Construir layout
                layout.appendChild(sidebar);
                layout.appendChild(main);
                wrap.appendChild(layout);

                // Eliminar el contenedor temporal del sidebar
                if (sidebarContainer && sidebarContainer.parentNode) {
                    sidebarContainer.parentNode.removeChild(sidebarContainer);
                }

                // Añadir clase al body
                document.body.classList.add('ap-has-sidebar');
            }

            // Ejecutar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initUserEditLayout);
            } else {
                initUserEditLayout();
            }

            // También ejecutar después de un pequeño delay para asegurar que todo esté cargado
            setTimeout(initUserEditLayout, 100);
            setTimeout(initUserEditLayout, 500);
        })();
        </script>
        <?php
    }

    /**
     * Ocultar metaboxes innecesarios en la página de edición de clientes
     */
    public function remove_unnecessary_user_meta_boxes()
    {
        global $pagenow;
        
        // Solo aplicar en páginas de edición de usuarios
        if ($pagenow !== 'user-edit.php' && $pagenow !== 'profile.php') {
            return;
        }

        // Obtener el ID del usuario que se está editando
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
        $user = get_userdata($user_id);
        
        // Solo aplicar si es un cliente (customer o subscriber)
        if (!$user || (!in_array('customer', $user->roles) && !in_array('subscriber', $user->roles))) {
            return;
        }

        // Ocultar esquema de color
        remove_meta_box('show_user_color_scheme', 'user', 'normal');
        remove_meta_box('show_user_color_scheme', 'user', 'side');
        
        // Ocultar atajos de teclado (Keyboard Shortcuts)
        remove_meta_box('keyboard_shortcuts', 'user', 'normal');
        remove_meta_box('keyboard_shortcuts', 'user', 'side');
        
        // Ocultar contraseñas de aplicación
        remove_meta_box('application_passwords', 'user', 'normal');
        
        // Ocultar SEO de usuarios (si existe)
        remove_meta_box('wpseo_meta', 'user', 'normal');
    }

    /**
     * Ocultar metaboxes con CSS como respaldo
     */
    public function hide_meta_boxes_css()
    {
        global $pagenow;
        
        // Solo aplicar en páginas de edición de usuarios
        if ($pagenow !== 'user-edit.php' && $pagenow !== 'profile.php') {
            return;
        }

        // Obtener el ID del usuario que se está editando
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
        $user = get_userdata($user_id);
        
        // Solo aplicar si es un cliente (customer o subscriber)
        if (!$user || (!in_array('customer', $user->roles) && !in_array('subscriber', $user->roles))) {
            return;
        }
        ?>
        <style>
            /* Ocultar esquema de color */
            body.user-edit #show_user_color_scheme,
            body.profile #show_user_color_scheme,
            body.user-edit .user-color-scheme-wrap,
            body.profile .user-color-scheme-wrap,
            body.user-edit .user-color-scheme-wrap ~ *,
            body.profile .user-color-scheme-wrap ~ * {
                display: none !important;
            }
            
            /* Ocultar atajos de teclado */
            body.user-edit #keyboard_shortcuts,
            body.profile #keyboard_shortcuts,
            body.user-edit .keyboard-shortcuts-wrap,
            body.profile .keyboard-shortcuts-wrap,
            body.user-edit .keyboard-shortcuts-wrap ~ *,
            body.profile .keyboard-shortcuts-wrap ~ * {
                display: none !important;
            }
            
            /* Ocultar contraseñas de aplicación */
            body.user-edit #application_passwords,
            body.profile #application_passwords,
            body.user-edit #application_passwords ~ *,
            body.profile #application_passwords ~ * {
                display: none !important;
            }
            
            /* Ocultar cualquier postbox que contenga "color" o "keyboard" en su ID o clase */
            body.user-edit .postbox[id*="color"],
            body.profile .postbox[id*="color"],
            body.user-edit .postbox[id*="keyboard"],
            body.profile .postbox[id*="keyboard"],
            body.user-edit .postbox[class*="color"],
            body.profile .postbox[class*="color"],
            body.user-edit .postbox[class*="keyboard"],
            body.profile .postbox[class*="keyboard"] {
                display: none !important;
            }
            
            /* Ocultar opciones de pantalla y ayuda contextual */
            body.user-edit #screen-options-link-wrap,
            body.profile #screen-options-link-wrap,
            body.user-edit #contextual-help-link-wrap,
            body.profile #contextual-help-link-wrap {
                display: none !important;
            }
        </style>
        <?php
    }
}

new Alquipress_UI_Enhancements();
