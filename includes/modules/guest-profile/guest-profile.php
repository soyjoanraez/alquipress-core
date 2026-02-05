<?php
/**
 * Módulo: Perfil de Huésped
 * Vista detallada read-only del perfil de cliente
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Guest_Profile
{

    public function __construct()
    {
        // Añadir enlace "Ver Perfil" en listado de usuarios
        add_filter('user_row_actions', [$this, 'add_profile_link'], 10, 2);

        // Añadir página al menú
        add_action('admin_menu', [$this, 'add_profile_page']);

        // Cargar estilos
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Añadir enlace "Ver Perfil" en listado de usuarios
     */
    public function add_profile_link($actions, $user)
    {
        // Solo para roles de cliente/suscriptor
        if (in_array('customer', $user->roles) || in_array('subscriber', $user->roles)) {
            $profile_url = admin_url('users.php?page=alquipress-guest-profile&user_id=' . $user->ID);
            $actions['view_guest_profile'] = '<a href="' . esc_url($profile_url) . '">👤 Ver Perfil CRM</a>';
        }

        return $actions;
    }

    /**
     * Registrar página del perfil
     */
    public function add_profile_page()
    {
        add_submenu_page(
            'users.php',
            'Perfil del Huésped',
            null, // No mostrar en menú
            'edit_users',
            'alquipress-guest-profile',
            [$this, 'render_profile_page']
        );
    }

    /**
     * Renderizar página de perfil
     */
    public function render_profile_page()
    {
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

        if (!$user_id) {
            echo '<div class="wrap"><h1>Error</h1><p>Usuario no encontrado.</p></div>';
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            echo '<div class="wrap"><h1>Error</h1><p>Usuario no válido.</p></div>';
            return;
        }

        // Obtener datos ACF
        $status = get_field('guest_status', 'user_' . $user_id) ?: 'standard';
        $rating = get_field('guest_rating', 'user_' . $user_id) ?: 0;
        $preferences = get_field('guest_preferences', 'user_' . $user_id) ?: [];
        $internal_notes = get_field('guest_internal_notes', 'user_' . $user_id) ?: '';
        $nationality = get_field('guest_nationality', 'user_' . $user_id) ?: '';
        $phone = get_field('guest_phone', 'user_' . $user_id) ?: '';
        $documents = get_field('guest_documents', 'user_' . $user_id) ?: [];

        // Calcular gasto total
        $total_spent = $this->get_user_total_spent($user_id);

        // Obtener historial de reservas
        $bookings = $this->get_user_bookings($user_id);

        require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';
        ?>
        <div class="wrap alquipress-guest-profile-wrap ap-has-sidebar">
            <div class="ap-owners-layout">
                <?php alquipress_render_sidebar('clients'); ?>
                <main class="ap-owners-main">
                    <header class="ap-clients-header">
                        <div class="ap-clients-header-left">
                            <h1 class="ap-clients-title"><?php echo esc_html($user->display_name); ?></h1>
                            <p class="ap-clients-subtitle"><?php esc_html_e('Perfil del cliente', 'alquipress'); ?></p>
                        </div>
                        <div class="ap-clients-header-right">
                            <a href="<?php echo admin_url('admin.php?page=alquipress-clients'); ?>" class="ap-clients-btn"><?php esc_html_e('Volver a Clientes', 'alquipress'); ?></a>
                            <a href="<?php echo get_edit_user_link($user_id); ?>" class="ap-clients-btn ap-clients-btn-primary"><?php esc_html_e('Editar cliente', 'alquipress'); ?></a>
                        </div>
                    </header>

                    <!-- Profile hero card -->
                    <div class="ap-guest-profile-hero">
                        <div class="ap-guest-profile-avatar">
                            <?php echo get_avatar($user_id, 80, '', '', ['class' => 'ap-guest-avatar-circle']); ?>
                        </div>
                        <div class="ap-guest-profile-info">
                            <div class="ap-guest-profile-meta">
                                <span class="ap-guest-meta-item">
                                    <span class="dashicons dashicons-email"></span>
                                    <?php echo esc_html($user->user_email); ?>
                                </span>
                                <?php if ($phone): ?>
                                    <span class="ap-guest-meta-item">
                                        <span class="dashicons dashicons-phone"></span>
                                        <?php echo esc_html($phone); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($nationality): ?>
                                    <span class="ap-guest-meta-item">
                                        <span class="dashicons dashicons-admin-site"></span>
                                        <?php echo esc_html($nationality); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="ap-guest-profile-rating">
                                <?php $this->render_stars($rating); ?>
                                <span class="ap-guest-rating-text"><?php echo number_format_i18n($rating, 1); ?> / 5.0</span>
                            </div>
                        </div>
                        <div class="ap-guest-profile-badges">
                            <?php
                            $badge_config = [
                                'vip' => ['label' => 'VIP', 'class' => 'ap-guest-badge-vip'],
                                'blacklist' => ['label' => 'Lista Negra', 'class' => 'ap-guest-badge-blacklist'],
                                'standard' => ['label' => 'Estándar', 'class' => 'ap-guest-badge-standard']
                            ];
                            $badge = $badge_config[$status] ?? $badge_config['standard'];
                            ?>
                            <span class="ap-guest-status-badge <?php echo esc_attr($badge['class']); ?>"><?php echo esc_html($badge['label']); ?></span>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="ap-guest-stats-grid">
                        <div class="ap-clients-metric-card">
                            <span class="ap-clients-metric-label"><?php esc_html_e('Email', 'alquipress'); ?></span>
                            <span class="ap-clients-metric-value ap-guest-stat-value"><?php echo esc_html($user->user_email); ?></span>
                        </div>
                        <div class="ap-clients-metric-card">
                            <span class="ap-clients-metric-label"><?php esc_html_e('Teléfono', 'alquipress'); ?></span>
                            <span class="ap-clients-metric-value ap-guest-stat-value"><?php echo $phone ? esc_html($phone) : '—'; ?></span>
                        </div>
                        <div class="ap-clients-metric-card">
                            <span class="ap-clients-metric-label"><?php esc_html_e('Nacionalidad', 'alquipress'); ?></span>
                            <span class="ap-clients-metric-value ap-guest-stat-value"><?php echo $nationality ? esc_html($nationality) : '—'; ?></span>
                        </div>
                        <div class="ap-clients-metric-card">
                            <span class="ap-clients-metric-label"><?php esc_html_e('Gasto total', 'alquipress'); ?></span>
                            <span class="ap-clients-metric-value"><?php echo function_exists('wc_price') ? wc_price($total_spent) : number_format_i18n($total_spent, 2) . ' €'; ?></span>
                        </div>
                    </div>

            <!-- Preferencias -->
            <?php if (!empty($preferences)): ?>
                <div class="ap-guest-profile-card">
                    <h2 class="ap-guest-card-title">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e('Preferencias del huésped', 'alquipress'); ?>
                    </h2>

                    <div class="ap-guest-preferences-grid">
                        <?php
                        $pref_icons = [
                            'mascotas' => ['icon' => '🐾', 'label' => 'Admite Mascotas'],
                            'nofumador' => ['icon' => '🚭', 'label' => 'No Fumador'],
                            'familia' => ['icon' => '👨‍👩‍👧', 'label' => 'Familia'],
                            'accesibilidad' => ['icon' => '♿', 'label' => 'Accesibilidad'],
                            'nomada' => ['icon' => '💻', 'label' => 'Nómada Digital'],
                            'silencio' => ['icon' => '🤫', 'label' => 'Zona Tranquila'],
                            'parking' => ['icon' => '🚗', 'label' => 'Requiere Parking']
                        ];

                        foreach ($preferences as $pref) {
                            $config = $pref_icons[$pref] ?? ['icon' => '✓', 'label' => ucfirst($pref)];
                            ?>
                            <div class="ap-guest-preference-item">
                                <span class="ap-guest-pref-icon"><?php echo $config['icon']; ?></span>
                                <span class="ap-guest-pref-label"><?php echo esc_html($config['label']); ?></span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Documentación -->
            <div class="ap-guest-profile-card">
                <h2 class="ap-guest-card-title">
                    <span class="dashicons dashicons-media-document"></span>
                    <?php esc_html_e('Documentación (DNI/Pasaporte)', 'alquipress'); ?>
                    <?php if (!empty($documents)): ?>
                        <span class="ap-guest-doc-count-badge"><?php echo count($documents); ?></span>
                    <?php endif; ?>
                </h2>

                <?php if (!empty($documents)): ?>
                    <div class="ap-guest-documents-grid">
                        <?php
                        $tipo_labels = [
                            'dni' => 'DNI',
                            'pasaporte' => 'Pasaporte',
                            'nie' => 'NIE',
                            'otro' => 'Otro'
                        ];
                        $today = strtotime('today');
                        $expiring_threshold = strtotime('+30 days');

                        foreach ($documents as $doc):
                            $tipo = isset($doc['tipo_doc']) ? $doc['tipo_doc'] : '';
                            $numero = isset($doc['numero_doc']) ? $doc['numero_doc'] : '';
                            $fecha_vencimiento = isset($doc['fecha_vencimiento']) ? $doc['fecha_vencimiento'] : '';
                            $nombre = isset($doc['nombre_doc']) ? $doc['nombre_doc'] : '';
                            $archivo = isset($doc['archivo_doc']) ? $doc['archivo_doc'] : null;
                            
                            $is_expired = false;
                            $is_expiring_soon = false;
                            $status_class = 'doc-status-ok';
                            $status_text = '';
                            
                            if ($fecha_vencimiento) {
                                $expiry_timestamp = strtotime($fecha_vencimiento);
                                if ($expiry_timestamp < $today) {
                                    $is_expired = true;
                                    $status_class = 'doc-status-expired';
                                    $status_text = 'Vencido';
                                } elseif ($expiry_timestamp <= $expiring_threshold) {
                                    $is_expiring_soon = true;
                                    $status_class = 'doc-status-expiring';
                                    $status_text = 'Próximo a vencer';
                                } else {
                                    $status_text = 'Vigente';
                                }
                            }
                            
                            $tipo_label = !empty($tipo) ? ($tipo_labels[$tipo] ?? ucfirst($tipo)) : ($nombre ?: 'Documento');
                            ?>
                            <div class="ap-guest-document-item <?php echo esc_attr($status_class); ?>">
                                <div class="ap-guest-doc-header">
                                    <span class="ap-guest-doc-type-icon">📄</span>
                                    <div class="ap-guest-doc-info">
                                        <div class="ap-guest-doc-type"><?php echo esc_html($tipo_label); ?></div>
                                        <?php if ($numero): ?>
                                            <div class="ap-guest-doc-number"><?php echo esc_html($numero); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($status_text): ?>
                                        <span class="ap-guest-doc-status-badge <?php echo esc_attr($status_class); ?>">
                                            <?php echo esc_html($status_text); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($fecha_vencimiento): ?>
                                    <div class="ap-guest-doc-expiry">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <span><?php esc_html_e('Vence:', 'alquipress'); ?> <?php echo date_i18n('d/m/Y', strtotime($fecha_vencimiento)); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($nombre && $nombre !== $tipo_label): ?>
                                    <div class="ap-guest-doc-description"><?php echo esc_html($nombre); ?></div>
                                <?php endif; ?>
                                
                                <?php if ($archivo): ?>
                                    <?php
                                    $file_url = is_array($archivo) && isset($archivo['url']) ? $archivo['url'] : (is_numeric($archivo) ? wp_get_attachment_url($archivo) : $archivo);
                                    ?>
                                    <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="ap-guest-doc-view-link">
                                        <span class="dashicons dashicons-external"></span>
                                        <?php esc_html_e('Ver documento', 'alquipress'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="ap-guest-empty-text"><?php esc_html_e('No hay documentos registrados para este cliente.', 'alquipress'); ?></p>
                    <p class="ap-guest-empty-actions">
                        <a href="<?php echo get_edit_user_link($user_id); ?>" class="ap-clients-btn ap-clients-btn-primary">
                            <?php esc_html_e('Añadir documentos', 'alquipress'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Notas Privadas -->
            <?php if ($internal_notes): ?>
                <div class="ap-guest-profile-card ap-guest-notes-card">
                    <h2 class="ap-guest-card-title">
                        <span class="dashicons dashicons-lock"></span>
                        <?php esc_html_e('Notas privadas (solo staff)', 'alquipress'); ?>
                    </h2>
                    <div class="ap-guest-notes-content">
                        <?php echo wpautop($internal_notes); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Historial de Reservas -->
            <div class="ap-guest-profile-card">
                <h2 class="ap-guest-card-title">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo esc_html(sprintf(__('Historial de reservas (%d)', 'alquipress'), count($bookings))); ?>
                </h2>

                <?php if (!empty($bookings)): ?>
                    <div class="ap-clients-table-wrap">
                    <table class="ap-clients-table ap-guest-bookings-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Pedido', 'alquipress'); ?></th>
                                <th><?php esc_html_e('Propiedad', 'alquipress'); ?></th>
                                <th><?php esc_html_e('Check-in', 'alquipress'); ?></th>
                                <th><?php esc_html_e('Check-out', 'alquipress'); ?></th>
                                <th><?php esc_html_e('Noches', 'alquipress'); ?></th>
                                <th><?php esc_html_e('Total', 'alquipress'); ?></th>
                                <th><?php esc_html_e('Estado', 'alquipress'); ?></th>
                                <th><?php esc_html_e('Acciones', 'alquipress'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $order): ?>
                                <?php
                                $order_id = $order->get_id();
                                $property = $this->get_order_property($order);
                                $checkin = $order->get_meta('_booking_checkin_date');
                                $checkout = $order->get_meta('_booking_checkout_date');

                                $nights = 0;
                                if ($checkin && $checkout) {
                                    $diff = strtotime($checkout) - strtotime($checkin);
                                    $nights = floor($diff / (60 * 60 * 24));
                                }

                                $status = $order->get_status();
                                $status_label = wc_get_order_status_name($status);
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($order_id); ?>" target="_blank">
                                            <strong>#<?php echo $order_id; ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo $property ? esc_html($property->get_name()) : '-'; ?></td>
                                    <td><?php echo $checkin ? date_i18n('d/m/Y', strtotime($checkin)) : '-'; ?></td>
                                    <td><?php echo $checkout ? date_i18n('d/m/Y', strtotime($checkout)) : '-'; ?></td>
                                    <td><?php echo $nights > 0 ? $nights : '-'; ?></td>
                                    <td><strong><?php echo wc_price($order->get_total()); ?></strong></td>
                                    <td><?php echo esc_html($status_label); ?></td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($order_id); ?>" class="ap-clients-link" target="_blank"><?php esc_html_e('Ver', 'alquipress'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php else: ?>
                    <p class="ap-guest-empty-text ap-guest-empty-reservas"><?php esc_html_e('Este cliente aún no tiene reservas registradas.', 'alquipress'); ?></p>
                <?php endif; ?>
            </div>
                </main>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar estrellas de rating
     */
    private function render_stars($rating)
    {
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

        echo '<div class="star-rating-display">';

        // Estrellas completas
        for ($i = 0; $i < $full_stars; $i++) {
            echo '<span class="star filled">⭐</span>';
        }

        // Media estrella
        if ($half_star) {
            echo '<span class="star half">⭐</span>';
        }

        // Estrellas vacías
        for ($i = 0; $i < $empty_stars; $i++) {
            echo '<span class="star empty">☆</span>';
        }

        echo '</div>';
    }

    /**
     * Obtener gasto total del usuario
     */
    private function get_user_total_spent($user_id)
    {
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit' => -1,
            'status' => ['completed', 'processing', 'deposito-ok', 'in-progress'],
        ]);

        $total = 0;
        foreach ($orders as $order) {
            $total += $order->get_total();
        }

        return $total;
    }

    /**
     * Obtener reservas del usuario
     */
    private function get_user_bookings($user_id)
    {
        return wc_get_orders([
            'customer_id' => $user_id,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    /**
     * Obtener propiedad de un pedido
     */
    private function get_order_property($order)
    {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                return $product;
            }
        }
        return null;
    }

    /**
     * Cargar estilos
     */
    public function enqueue_assets($hook)
    {
        if ($hook !== 'users_page_alquipress-guest-profile') {
            return;
        }

        wp_enqueue_style(
            'alquipress-admin-layout',
            ALQUIPRESS_URL . 'includes/admin/assets/alquipress-admin-layout.css',
            [],
            ALQUIPRESS_VERSION
        );

        $critical_layout = '#wpcontent,#wpbody-content{background:#f8fafb!important;}'
            . '.wrap.ap-has-sidebar{min-height:80vh!important;width:100%!important;position:relative!important;z-index:999998!important;max-width:none!important;margin-top:12px!important;padding:0!important;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif!important;}'
            . '.wrap.ap-has-sidebar .ap-owners-layout{display:flex!important;min-height:calc(100vh - 140px)!important;background:#f8fafb!important;border:1px solid #e8eef3!important;border-radius:16px!important;overflow:hidden!important;}'
            . '.wrap.ap-has-sidebar .ap-owners-sidebar{width:256px!important;min-width:256px!important;background:#ffffff!important;border-right:1px solid #e8eef3!important;}'
            . '.wrap.ap-has-sidebar .ap-owners-main{flex:1!important;min-width:0!important;padding:32px!important;background:#f8fafb!important;}';
        wp_add_inline_style('alquipress-admin-layout', $critical_layout);

        wp_enqueue_style(
            'alquipress-guest-profile',
            ALQUIPRESS_URL . 'includes/modules/guest-profile/assets/guest-profile.css',
            ['alquipress-admin-layout'],
            ALQUIPRESS_VERSION
        );
    }
}

new Alquipress_Guest_Profile();
