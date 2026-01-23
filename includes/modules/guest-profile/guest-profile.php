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

        ?>
        <div class="wrap alquipress-guest-profile-wrap">
            <div class="profile-header">
                <a href="<?php echo admin_url('users.php'); ?>" class="back-link">← Volver a Usuarios</a>
                <a href="<?php echo get_edit_user_link($user_id); ?>" class="button button-primary">✏️ Editar Usuario</a>
            </div>

            <!-- Header Card -->
            <div class="profile-card header-card">
                <div class="profile-avatar">
                    <?php echo get_avatar($user_id, 100, '', '', ['class' => 'avatar-circle']); ?>
                </div>

                <div class="profile-info">
                    <h1 class="profile-name"><?php echo esc_html($user->display_name); ?></h1>

                    <div class="profile-meta">
                        <span class="meta-item">
                            <span class="dashicons dashicons-email"></span>
                            <?php echo esc_html($user->user_email); ?>
                        </span>
                        <?php if ($phone): ?>
                            <span class="meta-item">
                                <span class="dashicons dashicons-phone"></span>
                                <?php echo esc_html($phone); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($nationality): ?>
                            <span class="meta-item">
                                <span class="dashicons dashicons-admin-site"></span>
                                <?php echo esc_html($nationality); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="profile-rating">
                        <?php $this->render_stars($rating); ?>
                        <span class="rating-text"><?php echo number_format($rating, 1); ?> / 5.0</span>
                    </div>
                </div>

                <div class="profile-badges">
                    <?php
                    $badge_config = [
                        'vip' => ['label' => 'VIP', 'color' => '#f39c12', 'icon' => '⭐'],
                        'blacklist' => ['label' => 'Lista Negra', 'color' => '#e74c3c', 'icon' => '🚫'],
                        'standard' => ['label' => 'Estándar', 'color' => '#95a5a6', 'icon' => '👤']
                    ];

                    $badge = $badge_config[$status] ?? $badge_config['standard'];
                    ?>
                    <div class="status-badge" style="background: <?php echo $badge['color']; ?>">
                        <span class="badge-icon"><?php echo $badge['icon']; ?></span>
                        <span class="badge-label"><?php echo $badge['label']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📧</div>
                    <div class="stat-content">
                        <div class="stat-label">Email</div>
                        <div class="stat-value"><?php echo esc_html($user->user_email); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📱</div>
                    <div class="stat-content">
                        <div class="stat-label">Teléfono</div>
                        <div class="stat-value"><?php echo $phone ? esc_html($phone) : '-'; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🌍</div>
                    <div class="stat-content">
                        <div class="stat-label">Nacionalidad</div>
                        <div class="stat-value"><?php echo $nationality ? esc_html($nationality) : '-'; ?></div>
                    </div>
                </div>

                <div class="stat-card highlight">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <div class="stat-label">Gasto Total</div>
                        <div class="stat-value"><?php echo wc_price($total_spent); ?></div>
                    </div>
                </div>
            </div>

            <!-- Preferencias -->
            <?php if (!empty($preferences)): ?>
                <div class="profile-card">
                    <h2 class="card-title">
                        <span class="dashicons dashicons-admin-generic"></span>
                        Preferencias del Huésped
                    </h2>

                    <div class="preferences-grid">
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
                            <div class="preference-item">
                                <span class="pref-icon"><?php echo $config['icon']; ?></span>
                                <span class="pref-label"><?php echo esc_html($config['label']); ?></span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Notas Privadas -->
            <?php if ($internal_notes): ?>
                <div class="profile-card notes-card">
                    <h2 class="card-title">
                        <span class="dashicons dashicons-lock"></span>
                        Notas Privadas (Solo Staff)
                    </h2>
                    <div class="notes-content">
                        <?php echo wpautop($internal_notes); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Historial de Reservas -->
            <div class="profile-card">
                <h2 class="card-title">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    Historial de Reservas (<?php echo count($bookings); ?>)
                </h2>

                <?php if (!empty($bookings)): ?>
                    <table class="wp-list-table widefat fixed striped bookings-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Propiedad</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Noches</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
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
                                        <a href="<?php echo get_edit_post_link($order_id); ?>" class="button button-small"
                                            target="_blank">Ver</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px 0;">
                        Este huésped aún no tiene reservas registradas.
                    </p>
                <?php endif; ?>
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
            'alquipress-guest-profile',
            ALQUIPRESS_URL . 'includes/modules/guest-profile/assets/guest-profile.css',
            [],
            ALQUIPRESS_VERSION
        );
    }
}

new Alquipress_Guest_Profile();
