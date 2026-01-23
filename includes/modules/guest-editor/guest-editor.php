<?php
/**
 * Módulo: Editor de Huésped
 * Formulario mejorado para editar datos del huésped
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Guest_Editor
{

    public function __construct()
    {
        // Añadir enlace "Editar en CRM" en listado de usuarios
        add_filter('user_row_actions', [$this, 'add_edit_link'], 10, 2);

        // Añadir página al menú
        add_action('admin_menu', [$this, 'add_editor_page']);

        // Procesar formulario
        add_action('admin_init', [$this, 'process_form']);

        // Cargar estilos
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Añadir enlace "Editar en CRM" en listado de usuarios
     */
    public function add_edit_link($actions, $user)
    {
        if (in_array('customer', $user->roles) || in_array('subscriber', $user->roles)) {
            $edit_url = admin_url('users.php?page=alquipress-edit-guest&user_id=' . $user->ID);
            $actions['edit_guest_crm'] = '<a href="' . esc_url($edit_url) . '">✏️ Editar en CRM</a>';
        }

        return $actions;
    }

    /**
     * Registrar página del editor
     */
    public function add_editor_page()
    {
        add_submenu_page(
            'users.php',
            'Editar Huésped',
            null, // No mostrar en menú
            'edit_users',
            'alquipress-edit-guest',
            [$this, 'render_editor_page']
        );
    }

    /**
     * Procesar formulario
     */
    public function process_form()
    {
        if (!isset($_POST['alquipress_save_guest']) || !isset($_POST['user_id'])) {
            return;
        }

        $user_id = intval($_POST['user_id']);

        // Verificar nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'alquipress_edit_guest_' . $user_id)) {
            wp_die('Verificación de seguridad fallida');
        }

        // Verificar permisos
        if (!current_user_can('edit_users')) {
            wp_die('No tienes permisos para editar usuarios');
        }

        // Actualizar campos ACF
        if (isset($_POST['guest_status'])) {
            update_field('guest_status', sanitize_text_field($_POST['guest_status']), 'user_' . $user_id);
        }

        if (isset($_POST['guest_rating'])) {
            update_field('guest_rating', floatval($_POST['guest_rating']), 'user_' . $user_id);
        }

        if (isset($_POST['guest_preferences'])) {
            update_field('guest_preferences', array_map('sanitize_text_field', $_POST['guest_preferences']), 'user_' . $user_id);
        }

        if (isset($_POST['guest_internal_notes'])) {
            update_field('guest_internal_notes', wp_kses_post($_POST['guest_internal_notes']), 'user_' . $user_id);
        }

        if (isset($_POST['guest_phone'])) {
            update_field('guest_phone', sanitize_text_field($_POST['guest_phone']), 'user_' . $user_id);
        }

        if (isset($_POST['guest_nationality'])) {
            update_field('guest_nationality', sanitize_text_field($_POST['guest_nationality']), 'user_' . $user_id);
        }

        // Actualizar datos básicos del usuario
        $userdata = [
            'ID' => $user_id,
        ];

        if (isset($_POST['first_name'])) {
            $userdata['first_name'] = sanitize_text_field($_POST['first_name']);
        }

        if (isset($_POST['last_name'])) {
            $userdata['last_name'] = sanitize_text_field($_POST['last_name']);
        }

        if (isset($_POST['user_email'])) {
            $userdata['user_email'] = sanitize_email($_POST['user_email']);
        }

        wp_update_user($userdata);

        // Redirigir con mensaje de éxito
        $redirect_url = add_query_arg([
            'page' => 'alquipress-edit-guest',
            'user_id' => $user_id,
            'updated' => 'true'
        ], admin_url('users.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Renderizar página del editor
     */
    public function render_editor_page()
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

        // Mensaje de éxito
        $updated = isset($_GET['updated']) && $_GET['updated'] === 'true';

        ?>
        <div class="wrap alquipress-edit-guest-wrap">
            <div class="edit-header">
                <div class="header-left">
                    <h1>✏️ Editar Huésped: <?php echo esc_html($user->display_name); ?></h1>
                    <p class="subtitle">Gestionar perfil, preferencias y notas internas.</p>
                </div>
                <div class="header-right">
                    <a href="<?php echo admin_url('users.php?page=alquipress-guest-profile&user_id=' . $user_id); ?>"
                        class="button">👤 Ver Perfil Completo</a>
                    <a href="<?php echo admin_url('users.php'); ?>" class="button">← Volver</a>
                </div>
            </div>

            <?php if ($updated): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✓ Huésped actualizado correctamente.</strong></p>
                </div>
            <?php endif; ?>

            <form method="post" action="" class="edit-guest-form">
                <?php wp_nonce_field('alquipress_edit_guest_' . $user_id); ?>
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                <div class="form-grid">
                    <!-- Columna Izquierda -->
                    <div class="form-column">
                        <!-- Información Básica -->
                        <div class="form-card">
                            <h2 class="card-title">
                                <span class="dashicons dashicons-admin-users"></span>
                                Información Básica
                            </h2>

                            <div class="form-field">
                                <label for="first_name">Nombre</label>
                                <input type="text" id="first_name" name="first_name"
                                    value="<?php echo esc_attr($user->first_name); ?>" class="regular-text">
                            </div>

                            <div class="form-field">
                                <label for="last_name">Apellidos</label>
                                <input type="text" id="last_name" name="last_name"
                                    value="<?php echo esc_attr($user->last_name); ?>" class="regular-text">
                            </div>

                            <div class="form-field">
                                <label for="user_email">Email</label>
                                <input type="email" id="user_email" name="user_email"
                                    value="<?php echo esc_attr($user->user_email); ?>" class="regular-text" required>
                            </div>

                            <div class="form-field">
                                <label for="guest_phone">Teléfono</label>
                                <input type="tel" id="guest_phone" name="guest_phone"
                                    value="<?php echo esc_attr($phone); ?>" class="regular-text">
                            </div>

                            <div class="form-field">
                                <label for="guest_nationality">Nacionalidad</label>
                                <input type="text" id="guest_nationality" name="guest_nationality"
                                    value="<?php echo esc_attr($nationality); ?>" class="regular-text">
                            </div>
                        </div>

                        <!-- Estado y Valoración -->
                        <div class="form-card">
                            <h2 class="card-title">
                                <span class="dashicons dashicons-star-filled"></span>
                                Estado y Valoración
                            </h2>

                            <div class="form-field">
                                <label for="guest_status">Estado del Cliente</label>
                                <select id="guest_status" name="guest_status" class="regular-text">
                                    <option value="standard" <?php selected($status, 'standard'); ?>>👤 Estándar</option>
                                    <option value="vip" <?php selected($status, 'vip'); ?>>⭐ VIP</option>
                                    <option value="blacklist" <?php selected($status, 'blacklist'); ?>>🚫 Lista Negra
                                    </option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="guest_rating">Valoración Interna (0-5)</label>
                                <div class="rating-input">
                                    <input type="number" id="guest_rating" name="guest_rating"
                                        value="<?php echo esc_attr($rating); ?>" min="0" max="5" step="0.5"
                                        class="small-text">
                                    <span class="rating-stars" id="rating-stars-display"></span>
                                </div>
                                <p class="description">Calificación interna del equipo (no visible para el cliente)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha -->
                    <div class="form-column">
                        <!-- Preferencias -->
                        <div class="form-card">
                            <h2 class="card-title">
                                <span class="dashicons dashicons-admin-generic"></span>
                                Preferencias del Huésped
                            </h2>

                            <div class="preferences-checkboxes">
                                <?php
                                $pref_options = [
                                    'mascotas' => ['icon' => '🐾', 'label' => 'Admite Mascotas'],
                                    'nofumador' => ['icon' => '🚭', 'label' => 'No Fumador'],
                                    'familia' => ['icon' => '👨‍👩‍👧', 'label' => 'Familia'],
                                    'accesibilidad' => ['icon' => '♿', 'label' => 'Accesibilidad'],
                                    'nomada' => ['icon' => '💻', 'label' => 'Nómada Digital'],
                                    'silencio' => ['icon' => '🤫', 'label' => 'Zona Tranquila'],
                                    'parking' => ['icon' => '🚗', 'label' => 'Requiere Parking']
                                ];

                                foreach ($pref_options as $key => $config) {
                                    $checked = in_array($key, $preferences) ? 'checked' : '';
                                    ?>
                                    <label class="preference-checkbox">
                                        <input type="checkbox" name="guest_preferences[]" value="<?php echo esc_attr($key); ?>"
                                            <?php echo $checked; ?>>
                                        <span class="checkbox-card">
                                            <span class="pref-icon"><?php echo $config['icon']; ?></span>
                                            <span class="pref-label"><?php echo $config['label']; ?></span>
                                        </span>
                                    </label>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Notas Privadas -->
                        <div class="form-card notes-card">
                            <h2 class="card-title">
                                <span class="dashicons dashicons-lock"></span>
                                Notas Privadas (Solo Staff)
                            </h2>

                            <div class="form-field">
                                <?php
                                wp_editor($internal_notes, 'guest_internal_notes', [
                                    'textarea_rows' => 10,
                                    'media_buttons' => false,
                                    'teeny' => true,
                                    'quicktags' => false
                                ]);
                                ?>
                                <p class="description">
                                    🔒 Estas notas son confidenciales y solo visibles para administradores
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="form-actions">
                    <button type="submit" name="alquipress_save_guest" class="button button-primary button-large">
                        💾 Guardar Cambios
                    </button>
                    <a href="<?php echo admin_url('users.php'); ?>" class="button button-large">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                // Actualizar estrellas en tiempo real
                function updateStars(rating) {
                    const container = $('#rating-stars-display');
                    container.empty();

                    const fullStars = Math.floor(rating);
                    const hasHalf = (rating - fullStars) >= 0.5;

                    for (let i = 0; i < fullStars; i++) {
                        container.append('<span class="star filled">⭐</span>');
                    }

                    if (hasHalf) {
                        container.append('<span class="star half">⭐</span>');
                    }

                    const emptyStars = 5 - fullStars - (hasHalf ? 1 : 0);
                    for (let i = 0; i < emptyStars; i++) {
                        container.append('<span class="star empty">☆</span>');
                    }
                }

                // Inicializar
                updateStars($('#guest_rating').val());

                // Actualizar al cambiar
                $('#guest_rating').on('input change', function () {
                    updateStars($(this).val());
                });
            });
        </script>
        <?php
    }

    /**
     * Cargar estilos
     */
    public function enqueue_assets($hook)
    {
        if ($hook !== 'users_page_alquipress-edit-guest') {
            return;
        }

        wp_enqueue_style(
            'alquipress-guest-editor',
            ALQUIPRESS_URL . 'includes/modules/guest-editor/assets/guest-editor.css',
            [],
            ALQUIPRESS_VERSION
        );
    }
}

new Alquipress_Guest_Editor();
