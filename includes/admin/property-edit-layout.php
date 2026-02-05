<?php
/**
 * Layout Pencil reutilizable para edición de propiedades.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza el header/hero/tabs de la edición de propiedades.
 *
 * @param WP_Post $post
 * @param array   $args {
 *   @type string $list_url URL de retorno al listado.
 *   @type string $dashboard_url URL del dashboard.
 *   @type string $primary_action_html HTML del botón primario.
 *   @type string $secondary_action_html HTML del botón secundario.
 *   @type bool   $editable_title Si se muestra input editable para el título.
 *   @type bool   $render_editor Si se renderiza el editor en el card de descripción.
 * }
 */
function alquipress_render_property_edit_layout($post, $args = [])
{
    if (!$post || !is_a($post, 'WP_Post')) {
        return;
    }

    $defaults = [
        'list_url' => admin_url('admin.php?page=alquipress-properties'),
        'dashboard_url' => admin_url('admin.php?page=alquipress-dashboard'),
        'primary_action_html' => '<span class="ap-prop-btn ap-prop-btn-primary">' . esc_html__('Editar', 'alquipress') . '</span>',
        'secondary_action_html' => '',
        'editable_title' => false,
        'render_editor' => false,
    ];
    $args = wp_parse_args($args, $defaults);

    $list_url = $args['list_url'];
    $dashboard_url = $args['dashboard_url'];

    $view_url = get_permalink($post->ID);
    if (!is_string($view_url) || $view_url === '') {
        $view_url = '#';
    }
    if ($args['secondary_action_html'] === '') {
        $args['secondary_action_html'] = '<a href="' . esc_url($view_url) . '" target="_blank" rel="noopener" class="ap-prop-btn ap-prop-btn-secondary">' . esc_html__('Ver en web', 'alquipress') . '</a>';
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
    $featured = $product && $product->get_catalog_visibility() === 'visible';
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
            $occupancy_label = sprintf(__('Ocupación (%s)', 'alquipress'), gmdate('M'));
        }
    }
    if ($occupancy_label === '') {
        $occupancy_label = __('Ocupación', 'alquipress');
    }

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
                    <?php echo wp_kses_post($args['secondary_action_html']); ?>
                    <?php echo wp_kses_post($args['primary_action_html']); ?>
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
                <?php if (!empty($args['editable_title'])) : ?>
                    <label class="screen-reader-text" for="ap-prop-title-input"><?php esc_html_e('Título', 'alquipress'); ?></label>
                    <input id="ap-prop-title-input" class="ap-prop-title-input" type="text" name="post_title" value="<?php echo esc_attr($title); ?>" />
                <?php else : ?>
                    <h2 class="ap-prop-title"><?php echo esc_html($title); ?></h2>
                <?php endif; ?>
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
                    <span class="ap-prop-stat-label"><?php echo $rating_count > 0 ? sprintf(_n('%d valoración', '%d valoraciones', $rating_count, 'alquipress'), $rating_count) : esc_html__('Valoración', 'alquipress'); ?></span>
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
                        <?php if (!empty($args['render_editor'])) : ?>
                            <div class="ap-prop-description-editor">
                                <?php
                                wp_editor(
                                    $post->post_content,
                                    'content',
                                    [
                                        'textarea_name' => 'content',
                                        'editor_height' => 180,
                                        'media_buttons' => true,
                                    ]
                                );
                                ?>
                            </div>
                        <?php else : ?>
                            <p class="ap-prop-card-text"><?php echo esc_html($description_text); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="ap-prop-card ap-prop-card-amenities">
                        <h3 class="ap-prop-card-title"><?php esc_html_e('Características y equipamiento', 'alquipress'); ?></h3>
                        <p class="ap-prop-card-muted"><?php esc_html_e('Definidas en los campos personalizados (taxonomías, ACF).', 'alquipress'); ?></p>
                    </div>
                    <div class="ap-prop-card ap-prop-card-rooms">
                        <h3 class="ap-prop-card-title"><?php esc_html_e('Configuración de habitaciones', 'alquipress'); ?></h3>
                        <p class="ap-prop-card-muted"><?php echo $beds !== null ? sprintf(_n('%d habitación', '%d habitaciones', (int) $beds, 'alquipress'), (int) $beds) : esc_html__('Sin datos.', 'alquipress'); ?></p>
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
                setTimeout(function() {
                    var normalSortables = document.getElementById('normal-sortables');
                    var postBodyContent = document.getElementById('post-body-content');
                    var metaBoxesToMove = [];

                    if (normalSortables) {
                        var boxes = normalSortables.querySelectorAll('.postbox');
                        boxes.forEach(function(box) {
                            if (box.querySelector('.acf-fields') || box.querySelector('.acf-field-group')) {
                                metaBoxesToMove.push(box);
                            }
                        });
                    }

                    if (postBodyContent) {
                        var boxes = postBodyContent.querySelectorAll('.postbox');
                        boxes.forEach(function(box) {
                            if (box.querySelector('.acf-fields') || box.querySelector('.acf-field-group')) {
                                if (metaBoxesToMove.indexOf(box) === -1) {
                                    metaBoxesToMove.push(box);
                                }
                            }
                        });
                    }

                    metaBoxesToMove.forEach(function(box) {
                        if (!overviewContent.contains(box)) {
                            overviewContent.appendChild(box);
                        }
                    });

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
