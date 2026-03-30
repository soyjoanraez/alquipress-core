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
        'tertiary_action_html' => '',
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
    $price_range = class_exists('Alquipress_Property_Helper') ? Alquipress_Property_Helper::get_product_price_range($post->ID) : null;
    if ($price_range && $price_range['min'] !== $price_range['max'] && function_exists('wc_price')) {
        $price_html = wc_price($price_range['min']) . ' – ' . wc_price($price_range['max']);
    } elseif ($product && $price !== '' && function_exists('wc_price')) {
        $price_html = wc_price($price);
    } else {
        $price_html = '—';
    }

    $beds = class_exists('Alquipress_Property_Helper') ? Alquipress_Property_Helper::get_product_beds($post->ID) : null;
    $baths = class_exists('Alquipress_Property_Helper') ? Alquipress_Property_Helper::get_product_baths($post->ID) : null;
    $guests = class_exists('Alquipress_Property_Helper') ? Alquipress_Property_Helper::get_product_guests($post->ID) : null;

    $surface = '';
    if (function_exists('get_field')) {
        $surface = get_field('superficie', $post->ID) ?: get_field('superficie_m2', $post->ID);
        $surface = is_string($surface) ? $surface : (is_numeric($surface) ? (string) $surface : '');
        if ($surface !== '' && is_numeric($surface)) {
            $surface .= ' m²';
        }
    }

    $cleaning_fee = (float) get_post_meta($post->ID, '_cleaning_fee', true);
    $cleaning_fee_html = function_exists('wc_price') && ($cleaning_fee > 0 || get_post_meta($post->ID, '_cleaning_fee', true) !== '') ? wc_price($cleaning_fee) : '—';

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
        $month_start = gmdate('Y-m-01');
        $month_end = gmdate('Y-m-t');
        $days_in_month = (int) gmdate('t');
        $orders = wc_get_orders([
            'status' => ['wc-completed', 'wc-processing', 'wc-deposito-ok', 'wc-in-progress', 'wc-pending', 'wc-on-hold'],
            'limit' => -1,
            'return' => 'objects',
            'meta_query' => [
                ['key' => '_booking_checkin_date', 'compare' => 'EXISTS'],
                ['key' => '_booking_checkout_date', 'compare' => 'EXISTS'],
            ],
        ]);
        $nights_booked = 0;
        foreach ($orders as $order) {
            $product_id = (int) $order->get_meta('_booking_product_id');
            if ($product_id !== (int) $post->ID) {
                $found = false;
                foreach ($order->get_items() as $item) {
                    if ((int) $item->get_product_id() === (int) $post->ID) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    continue;
                }
            }
            $checkin = $order->get_meta('_booking_checkin_date');
            $checkout = $order->get_meta('_booking_checkout_date');
            if (!$checkin || !$checkout) {
                continue;
            }
            $checkin_ts = strtotime($checkin);
            $checkout_ts = strtotime($checkout);
            $month_start_ts = strtotime($month_start);
            $month_end_ts = strtotime($month_end);
            if ($checkout_ts <= $month_start_ts || $checkin_ts > $month_end_ts) {
                continue;
            }
            $overlap_start = max($checkin_ts, $month_start_ts);
            $overlap_end = min($checkout_ts, $month_end_ts);
            $nights = (int) (($overlap_end - $overlap_start) / 86400);
            if ($nights < 1) {
                $nights = 1;
            }
            $nights_booked += $nights;
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
                    <?php if (!empty($args['tertiary_action_html'])) : ?>
                        <?php echo wp_kses_post($args['tertiary_action_html']); ?>
                    <?php endif; ?>
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
        <div class="ap-prop-hero-actions">
            <button type="button" class="ap-prop-hero-add-images button">
                <?php esc_html_e('Añadir imágenes de la propiedad', 'alquipress'); ?>
            </button>
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
            <div class="ap-prop-stat">
                <span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
                <div class="ap-prop-stat-content">
                    <span class="ap-prop-stat-value"><?php echo wp_kses_post($cleaning_fee_html); ?></span>
                    <span class="ap-prop-stat-label"><?php esc_html_e('Limpieza', 'alquipress'); ?></span>
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
                        <p class="ap-prop-card-muted"><?php esc_html_e('Definidas en los campos personalizados y taxonomías de la propiedad.', 'alquipress'); ?></p>
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
                        <p class="ap-prop-card-muted"><?php esc_html_e('Configurables en el producto y campos personalizados.', 'alquipress'); ?></p>
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
                            <?php
                            $native_edit_url = admin_url('post.php?post=' . (int) $post->ID . '&action=edit&alquipress_native_edit=1');
                            ?>
                            <a href="<?php echo esc_url($native_edit_url); ?>" class="ap-prop-widget-action-btn ap-prop-widget-action-full-edit"><?php esc_html_e('Edición completa (WordPress)', 'alquipress'); ?></a>
                            <a href="<?php echo esc_url($view_url); ?>" target="_blank" rel="noopener" class="ap-prop-widget-action-btn"><?php esc_html_e('Ver en web', 'alquipress'); ?></a>
                            <a href="<?php echo esc_url($list_url); ?>" class="ap-prop-widget-action-btn"><?php esc_html_e('Volver a propiedades', 'alquipress'); ?></a>
                            <?php
                            $trash_url = get_delete_post_link($post->ID, '', true);
                            ?>
                            <a href="<?php echo esc_url($trash_url); ?>" class="ap-prop-widget-action-btn ap-prop-widget-action-danger ap-prop-delete-property" onclick="return confirm('<?php echo esc_js(__('¿Seguro que quieres eliminar esta propiedad? Esta acción moverá la propiedad a la papelera y afectará a su visibilidad en la web pública.', 'alquipress')); ?>');">
                                <?php esc_html_e('Eliminar propiedad', 'alquipress'); ?>
                            </a>
                        </div>
                        <p class="ap-prop-widget-muted ap-prop-widget-actions-note"><?php esc_html_e('La edición completa permite cambiar precio, galería, población, zona, características, habitaciones y todos los campos.', 'alquipress'); ?></p>
                    </div>
                </aside>
            </div>
            <div class="ap-prop-card ap-prop-card-product-data ap-prop-card-fullwidth">
                <h3 class="ap-prop-card-title"><?php esc_html_e('Datos del producto (Reservas, Disponibilidad, Costes)', 'alquipress'); ?></h3>
                <p class="ap-prop-card-muted"><?php esc_html_e('Configuración de duración, calendario, disponibilidad, costes, Pagos ALQUIPRESS y depósitos.', 'alquipress'); ?></p>
                <div id="ap-prop-overview-content"></div>
            </div>
        </div>

        <div id="ap-prop-panel-calendario" class="ap-prop-tab-panel" role="tabpanel" aria-labelledby="ap-prop-tab-calendario" hidden>
            <div class="ap-prop-calendar-layout">
                <div class="ap-prop-card ap-prop-card-calendar-config">
                    <h3 class="ap-prop-card-title"><?php esc_html_e('Motor de reservas Alquipress', 'alquipress'); ?></h3>
                    <p class="ap-prop-card-muted"><?php esc_html_e('Activa el motor de reservas propio para esta propiedad y define un precio base por noche. Las reglas de temporada y bloqueos avanzados se gestionarán en siguientes iteraciones.', 'alquipress'); ?></p>
                    <?php
                    $ap_booking_enabled = get_post_meta($post->ID, 'ap_booking_enabled', true);
                    $ap_base_price = get_post_meta($post->ID, 'ap_base_price', true);
                    ?>
                    <div class="ap-prop-field-row">
                        <label for="ap_booking_enabled">
                            <input type="checkbox" id="ap_booking_enabled" name="ap_booking_enabled" value="1" <?php checked((bool) $ap_booking_enabled, true); ?> />
                            <?php esc_html_e('Usar motor de reservas Alquipress en lugar de WC Bookings para esta propiedad', 'alquipress'); ?>
                        </label>
                    </div>
                    <div class="ap-prop-field-row">
                        <label for="ap_base_price">
                            <?php esc_html_e('Precio base por noche (€)', 'alquipress'); ?>
                        </label>
                        <input
                            type="number"
                            id="ap_base_price"
                            name="ap_base_price"
                            step="0.01"
                            min="0"
                            value="<?php echo esc_attr($ap_base_price !== '' ? $ap_base_price : ''); ?>"
                            class="ap-prop-input"
                        />
                    </div>
                    <p class="ap-prop-card-note">
                        <?php esc_html_e('En esta primera versión el precio base se aplica a todas las noches. Más adelante se podrán definir reglas de temporada (verano, invierno, puentes, etc.).', 'alquipress'); ?>
                    </p>
                </div>

                <div class="ap-prop-card ap-prop-card-deposit-config">
                    <h3 class="ap-prop-card-title"><?php esc_html_e('Sistema de depósitos propio', 'alquipress'); ?></h3>
                    <p class="ap-prop-card-muted"><?php esc_html_e('Configura el pago a cuenta y el cobro del saldo restante para esta propiedad. Si está vacío se usarán los valores globales del Payment Manager.', 'alquipress'); ?></p>
                    <?php
                    $ap_deposit_enabled      = get_post_meta($post->ID, 'ap_deposit_enabled', true);
                    $ap_deposit_type         = get_post_meta($post->ID, 'ap_deposit_type', true) ?: 'percent';
                    $ap_deposit_percent      = get_post_meta($post->ID, 'ap_deposit_percent', true);
                    $ap_deposit_fixed        = get_post_meta($post->ID, 'ap_deposit_fixed_amount', true);
                    $ap_deposit_days         = get_post_meta($post->ID, 'ap_deposit_balance_days_before', true);
                    $ap_security_amount      = get_post_meta($post->ID, 'ap_security_deposit_amount', true);
                    ?>
                    <div class="ap-prop-field-row">
                        <label for="ap_deposit_enabled">
                            <input type="checkbox" id="ap_deposit_enabled" name="ap_deposit_enabled" value="1" <?php checked((bool) $ap_deposit_enabled, true); ?> />
                            <?php esc_html_e('Activar depósitos propios para esta propiedad', 'alquipress'); ?>
                        </label>
                    </div>
                    <div class="ap-prop-field-row" id="ap-deposit-config-fields" <?php echo $ap_deposit_enabled ? '' : 'style="display:none"'; ?>>
                        <label for="ap_deposit_type"><?php esc_html_e('Tipo de depósito', 'alquipress'); ?></label>
                        <select id="ap_deposit_type" name="ap_deposit_type" class="ap-prop-input">
                            <option value="percent" <?php selected($ap_deposit_type, 'percent'); ?>><?php esc_html_e('Porcentaje (%)', 'alquipress'); ?></option>
                            <option value="fixed" <?php selected($ap_deposit_type, 'fixed'); ?>><?php esc_html_e('Importe fijo (€)', 'alquipress'); ?></option>
                        </select>
                    </div>
                    <div class="ap-prop-field-row" id="ap-deposit-percent-row" <?php echo ($ap_deposit_enabled && $ap_deposit_type === 'percent') ? '' : 'style="display:none"'; ?>>
                        <label for="ap_deposit_percent"><?php esc_html_e('Porcentaje del depósito (%)', 'alquipress'); ?></label>
                        <input type="number" id="ap_deposit_percent" name="ap_deposit_percent" step="1" min="1" max="100"
                            value="<?php echo esc_attr($ap_deposit_percent !== '' ? $ap_deposit_percent : ''); ?>"
                            class="ap-prop-input" placeholder="<?php esc_attr_e('Ej: 40', 'alquipress'); ?>" />
                    </div>
                    <div class="ap-prop-field-row" id="ap-deposit-fixed-row" <?php echo ($ap_deposit_enabled && $ap_deposit_type === 'fixed') ? '' : 'style="display:none"'; ?>>
                        <label for="ap_deposit_fixed_amount"><?php esc_html_e('Importe fijo del depósito (€)', 'alquipress'); ?></label>
                        <input type="number" id="ap_deposit_fixed_amount" name="ap_deposit_fixed_amount" step="0.01" min="0"
                            value="<?php echo esc_attr($ap_deposit_fixed !== '' ? $ap_deposit_fixed : ''); ?>"
                            class="ap-prop-input" placeholder="<?php esc_attr_e('Ej: 300', 'alquipress'); ?>" />
                    </div>
                    <div class="ap-prop-field-row" id="ap-deposit-days-row" <?php echo $ap_deposit_enabled ? '' : 'style="display:none"'; ?>>
                        <label for="ap_deposit_balance_days_before"><?php esc_html_e('Días antes del check-in para cobrar el saldo', 'alquipress'); ?></label>
                        <input type="number" id="ap_deposit_balance_days_before" name="ap_deposit_balance_days_before" step="1" min="0"
                            value="<?php echo esc_attr($ap_deposit_days !== '' ? $ap_deposit_days : ''); ?>"
                            class="ap-prop-input" placeholder="<?php esc_attr_e('Ej: 7 (global por defecto)', 'alquipress'); ?>" />
                    </div>
                    <div class="ap-prop-field-row" id="ap-deposit-security-row" <?php echo $ap_deposit_enabled ? '' : 'style="display:none"'; ?>>
                        <label for="ap_security_deposit_amount"><?php esc_html_e('Fianza (retención Stripe, €)', 'alquipress'); ?></label>
                        <input type="number" id="ap_security_deposit_amount" name="ap_security_deposit_amount" step="0.01" min="0"
                            value="<?php echo esc_attr($ap_security_amount !== '' ? $ap_security_amount : ''); ?>"
                            class="ap-prop-input" placeholder="<?php esc_attr_e('Ej: 300', 'alquipress'); ?>" />
                    </div>
                    <p class="ap-prop-card-note">
                        <?php esc_html_e('Si no se configura aquí, se aplicará el porcentaje global definido en Ajustes → Payment Manager. WooCommerce Deposits quedará inactivo para reservas de esta propiedad.', 'alquipress'); ?>
                    </p>
                </div>

                <?php if ($ap_booking_enabled) : ?>
                <div class="ap-prop-card" style="grid-column:1/-1">
                    <h3 class="ap-prop-card-title"><?php esc_html_e('Calendario de disponibilidad y precios', 'alquipress'); ?></h3>
                    <p class="ap-prop-card-muted"><?php esc_html_e('Selecciona un rango de días para crear una regla de precio de temporada o bloquear fechas. Los días con precios de temporada se muestran en azul.', 'alquipress'); ?></p>
                    <div id="ap-booking-admin-calendar-root"></div>
                </div>
                <?php else : ?>
                <div class="ap-prop-card" style="grid-column:1/-1">
                    <p class="ap-prop-card-muted"><?php esc_html_e('Activa el motor de reservas Alquipress para gestionar el calendario de precios y disponibilidad.', 'alquipress'); ?></p>
                </div>
                <?php endif; ?>
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

        // Botón \"Añadir imágenes\" bajo el héroe: dispara el flujo estándar de WooCommerce.
        var addImagesBtn = document.querySelector('.ap-prop-hero-add-images');
        if (addImagesBtn) {
            addImagesBtn.addEventListener('click', function() {
                var thumbBtn = document.querySelector('#set-post-thumbnail');
                var galleryBtn = document.querySelector('#woocommerce-product-images .add_product_images');
                if (galleryBtn) {
                    galleryBtn.click();
                } else if (thumbBtn) {
                    thumbBtn.click();
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
                            if (box.id === 'woocommerce-product-data') {
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
                            if (box.id === 'woocommerce-product-data' && metaBoxesToMove.indexOf(box) === -1) {
                                metaBoxesToMove.push(box);
                            }
                        });
                    }

                    metaBoxesToMove.forEach(function(box) {
                        if (overviewContent.contains(box)) return;
                        if (box.id === 'woocommerce-product-data') {
                            overviewContent.insertBefore(box, overviewContent.firstChild);
                        } else {
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

        // ── Lógica de visibilidad para configuración de depósitos ──
        (function() {
            var toggleDepositFields = function() {
                var enabled = document.getElementById('ap_deposit_enabled');
                var configFields = ['ap-deposit-config-fields', 'ap-deposit-days-row', 'ap-deposit-security-row'];
                configFields.forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.style.display = (enabled && enabled.checked) ? '' : 'none';
                });
                if (enabled && enabled.checked) {
                    toggleDepositType();
                }
            };
            var toggleDepositType = function() {
                var typeSelect = document.getElementById('ap_deposit_type');
                var percentRow = document.getElementById('ap-deposit-percent-row');
                var fixedRow = document.getElementById('ap-deposit-fixed-row');
                if (!typeSelect) return;
                if (percentRow) percentRow.style.display = typeSelect.value === 'percent' ? '' : 'none';
                if (fixedRow)   fixedRow.style.display   = typeSelect.value === 'fixed' ? '' : 'none';
            };
            var depositCheckbox = document.getElementById('ap_deposit_enabled');
            var depositType     = document.getElementById('ap_deposit_type');
            if (depositCheckbox) depositCheckbox.addEventListener('change', toggleDepositFields);
            if (depositType)     depositType.addEventListener('change', toggleDepositType);
        })();
    })();
    </script>
    <?php
}
