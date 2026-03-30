<?php
/**
 * Módulo: Acciones Rápidas (Quick Actions)
 * Atajos y acciones rápidas en el admin
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Quick_Actions
{

    public function __construct()
    {
        // Admin bar shortcuts
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);

        // Botones de acción rápida en listados
        add_action('admin_footer', [$this, 'add_quick_action_buttons']);

        // AJAX handlers
        add_action('wp_ajax_alquipress_quick_status_change', [$this, 'ajax_quick_status_change']);
        add_action('wp_ajax_alquipress_quick_view', [$this, 'ajax_quick_view']);

        // Cargar assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Añadir menú en admin bar
     */
    public function add_admin_bar_menu($wp_admin_bar)
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        // Menú principal
        $wp_admin_bar->add_node([
            'id' => 'alquipress_quick',
            'title' => '⚡ ALQUIPRESS',
            'href' => admin_url('admin.php?page=alquipress-settings'),
            'meta' => ['class' => 'alquipress-quick-menu']
        ]);

        // Submenu: Pipeline
        $wp_admin_bar->add_node([
            'id' => 'alquipress_pipeline',
            'parent' => 'alquipress_quick',
            'title' => '📊 Pipeline de Reservas',
            'href' => admin_url('admin.php?page=alquipress-pipeline'),
        ]);

        // Submenu: Nuevo Pedido
        $wp_admin_bar->add_node([
            'id' => 'alquipress_new_order',
            'parent' => 'alquipress_quick',
            'title' => '➕ Nueva Reserva',
            'href' => admin_url('post-new.php?post_type=shop_order'),
        ]);

        // Submenu: Ver Pedidos
        $wp_admin_bar->add_node([
            'id' => 'alquipress_orders',
            'parent' => 'alquipress_quick',
            'title' => '📦 Ver Pedidos',
            'href' => admin_url('edit.php?post_type=shop_order'),
        ]);

        // Submenu: Propietarios
        $wp_admin_bar->add_node([
            'id' => 'alquipress_owners',
            'parent' => 'alquipress_quick',
            'title' => '👥 Propietarios',
            'href' => admin_url('edit.php?post_type=propietario'),
        ]);

        // Submenu: Huéspedes
        $wp_admin_bar->add_node([
            'id' => 'alquipress_guests',
            'parent' => 'alquipress_quick',
            'title' => '👤 Huéspedes',
            'href' => admin_url('users.php?role=customer'),
        ]);

        // Submenu: Propiedades
        $wp_admin_bar->add_node([
            'id' => 'alquipress_properties',
            'parent' => 'alquipress_quick',
            'title' => '🏠 Propiedades',
            'href' => admin_url('edit.php?post_type=product'),
        ]);

        // Separador
        $wp_admin_bar->add_node([
            'id' => 'alquipress_separator',
            'parent' => 'alquipress_quick',
            'title' => '---',
            'meta' => ['class' => 'separator']
        ]);

        // Submenu: Check-ins Hoy
        $today = date('Y-m-d');
        $checkins_today = $this->count_checkins_today();

        $wp_admin_bar->add_node([
            'id' => 'alquipress_checkins_today',
            'parent' => 'alquipress_quick',
            'title' => '↓ Check-ins Hoy (' . $checkins_today . ')',
            'href' => admin_url('edit.php?post_type=shop_order&checkin_date=' . $today),
        ]);

        // Submenu: Check-outs Hoy
        $checkouts_today = $this->count_checkouts_today();

        $wp_admin_bar->add_node([
            'id' => 'alquipress_checkouts_today',
            'parent' => 'alquipress_quick',
            'title' => '↑ Check-outs Hoy (' . $checkouts_today . ')',
            'href' => admin_url('edit.php?post_type=shop_order&checkout_date=' . $today),
        ]);
    }

    /**
     * Añadir botones de acción rápida
     */
    public function add_quick_action_buttons()
    {
        $screen = get_current_screen();

        // Solo en páginas específicas
        if (!$screen || !in_array($screen->id, ['edit-shop_order', 'shop_order', 'users'])) {
            return;
        }

        ?>
        <script>
            jQuery(document).ready(function ($) {

                // ========== Quick Actions en Listado de Pedidos ==========

                if ($('.wp-list-table.orders').length || $('.type-shop_order').length) {

                    // Añadir botones de cambio rápido de estado
                    $('.wp-list-table tbody tr').each(function () {
                        const $row = $(this);
                        const orderId = $row.attr('id') ? $row.attr('id').replace('post-', '') : null;

                        if (!orderId) return;

                        // Añadir botón de vista rápida
                        const $actionsCell = $row.find('.column-order_actions, .column-primary');

                        if ($actionsCell.length) {
                            const quickViewBtn = $('<button class="button button-small alq-quick-view" data-order-id="' +
                                orderId + '">👁️ Vista Rápida</button>');
                            $actionsCell.append(quickViewBtn);
                        }
                    });

                    // Handler: Vista Rápida
                    $(document).on('click', '.alq-quick-view', function (e) {
                        e.preventDefault();
                        const $btn = $(this);
                        const orderId = $btn.data('order-id');

                        // Deshabilitar botón y mostrar loading
                        $btn.prop('disabled', true).text('Cargando...');

                        // Crear modal
                        if (!$('#alq-quick-view-modal').length) {
                            $('body').append(`
                            <div id="alq-quick-view-modal" class="alq-modal">
                                <div class="alq-modal-content">
                                    <span class="alq-modal-close">&times;</span>
                                    <div id="alq-quick-view-content"></div>
                                </div>
                            </div>
                        `);
                        }

                        $('#alq-quick-view-modal').fadeIn();
                        $('#alq-quick-view-content').html(
                            '<div style="text-align: center; padding: 40px;"><span class="spinner is-active" style="float: none; margin: 0;"></span><p style="margin: 15px 0 0; color: #666;">Cargando información del pedido...</p></div>'
                        );

                        // AJAX
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'alquipress_quick_view',
                                order_id: orderId,
                                nonce: '<?php echo wp_create_nonce('alquipress_quick_actions'); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#alq-quick-view-content').html(response.data.html);
                                    
                                    // Mostrar toast de éxito si está disponible
                                    if (typeof AlquipressToast !== 'undefined') {
                                        AlquipressToast.success('Información del pedido cargada correctamente', 3000);
                                    }
                                } else {
                                    const errorMsg = response.data && response.data.message 
                                        ? response.data.message 
                                        : 'Error al cargar el pedido.';
                                    
                                    $('#alq-quick-view-content').html(
                                        '<div style="padding: 40px; text-align: center;"><p style="color: #dc3232; font-weight: 600;">' + errorMsg + '</p></div>'
                                    );
                                    
                                    // Mostrar toast de error
                                    if (typeof AlquipressToast !== 'undefined') {
                                        AlquipressToast.error(errorMsg);
                                    }
                                }
                            },
                            error: function() {
                                $('#alq-quick-view-content').html(
                                    '<div style="padding: 40px; text-align: center;"><p style="color: #dc3232; font-weight: 600;">Error de conexión. Por favor, intenta de nuevo.</p></div>'
                                );
                                
                                // Mostrar toast de error
                                if (typeof AlquipressToast !== 'undefined') {
                                    AlquipressToast.error('Error de conexión. Por favor, intenta de nuevo.');
                                }
                            },
                            complete: function() {
                                // Rehabilitar botón
                                $btn.prop('disabled', false).text('👁️ Vista Rápida');
                            }
                        });
                    });

                    // Cerrar modal
                    $(document).on('click', '.alq-modal-close, .alq-modal', function (e) {
                        if (e.target === this) {
                            $('#alq-quick-view-modal').fadeOut();
                        }
                    });
                }

                // ========== Atajos de Teclado ==========

                $(document).on('keydown', function (e) {
                    // Ctrl/Cmd + K: Abrir búsqueda rápida
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                        e.preventDefault();
                        $('#adminmenu .wp-menu-open input[type="search"]').focus();
                    }

                    // Ctrl/Cmd + P: Ir a Pipeline
                    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                        e.preventDefault();
                        window.location.href = '<?php echo admin_url('admin.php?page=alquipress-pipeline'); ?>';
                    }

                    // Ctrl/Cmd + H: Ir a Dashboard
                    if ((e.ctrlKey || e.metaKey) && e.key === 'h') {
                        e.preventDefault();
                        window.location.href = '<?php echo admin_url(); ?>';
                    }
                });

                // ========== Tooltip de Atajos ==========

                if (!$('.alq-shortcuts-hint').length) {
                    $('body').append(`
                    <div class="alq-shortcuts-hint">
                        <strong>⌨️ Atajos:</strong><br>
                        <kbd>Ctrl</kbd> + <kbd>K</kbd> Buscar<br>
                        <kbd>Ctrl</kbd> + <kbd>P</kbd> Pipeline<br>
                        <kbd>Ctrl</kbd> + <kbd>H</kbd> Dashboard
                    </div>
                `);
                }

                // Mostrar/ocultar hint con Shift
                $(document).on('keydown keyup', function (e) {
                    if (e.shiftKey && e.type === 'keydown') {
                        $('.alq-shortcuts-hint').fadeIn();
                    } else if (e.type === 'keyup' && e.key === 'Shift') {
                        $('.alq-shortcuts-hint').fadeOut();
                    }
                });

            });
        </script>
        <?php
    }

    /**
     * AJAX: Cambio rápido de estado de pedido
     */
    public function ajax_quick_status_change()
    {
        check_ajax_referer('alquipress_quick_actions', 'nonce');

        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'alquipress')], 403);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $new_status_raw = '';

        if (isset($_POST['new_status'])) {
            $new_status_raw = sanitize_text_field(wp_unslash($_POST['new_status']));
        } elseif (isset($_POST['status'])) {
            $new_status_raw = sanitize_text_field(wp_unslash($_POST['status']));
        }

        if (!$order_id || $new_status_raw === '') {
            wp_send_json_error(['message' => __('Parámetros inválidos', 'alquipress')], 400);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => __('Pedido no encontrado', 'alquipress')], 404);
        }

        $new_status = str_replace('wc-', '', sanitize_key($new_status_raw));
        $valid_statuses = array_map(
            static function ($status_key) {
                return str_replace('wc-', '', sanitize_key($status_key));
            },
            array_keys(wc_get_order_statuses())
        );

        if (!in_array($new_status, $valid_statuses, true)) {
            wp_send_json_error(['message' => __('Estado no válido', 'alquipress')], 400);
        }

        $order->update_status($new_status);

        wp_send_json_success([
            'message' => __('Estado actualizado correctamente', 'alquipress'),
            'status' => $new_status,
            'status_label' => wc_get_order_status_name($new_status),
        ]);
    }

    /**
     * AJAX: Vista rápida de pedido
     */
    public function ajax_quick_view()
    {
        check_ajax_referer('alquipress_quick_actions', 'nonce');

        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

        if (!$order_id || $order_id <= 0) {
            wp_send_json_error([
                'message' => __('ID de pedido inválido', 'alquipress')
            ]);
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error([
                'message' => __('Pedido no encontrado', 'alquipress')
            ]);
            return;
        }

        // Verificar que el usuario tiene permisos para ver este pedido específico
        // (ya verificado con current_user_can('edit_shop_orders') arriba, pero añadimos validación adicional)

        // Obtener datos
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_email = $order->get_billing_email();
        $customer_phone = $order->get_billing_phone();

        $property_name = '';
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $property_name = $product->get_name();
                break;
            }
        }

        $checkin = $order->get_meta('_booking_checkin_date');
        $checkout = $order->get_meta('_booking_checkout_date');

        $nights = 0;
        if ($checkin && $checkout) {
            $diff = strtotime($checkout) - strtotime($checkin);
            $nights = floor($diff / (60 * 60 * 24));
        }

        ob_start();
        ?>
        <div class="alq-quick-view-header">
            <h2>Pedido #<?php echo $order_id; ?></h2>
            <span class="order-status status-<?php echo $order->get_status(); ?>">
                <?php echo wc_get_order_status_name($order->get_status()); ?>
            </span>
        </div>

        <div class="alq-quick-view-grid">
            <div class="quick-view-section">
                <h3>👤 Cliente</h3>
                <p><strong><?php echo esc_html($customer_name); ?></strong></p>
                <p>📧 <?php echo esc_html($customer_email); ?></p>
                <?php if ($customer_phone): ?>
                    <p>📱 <?php echo esc_html($customer_phone); ?></p>
                <?php endif; ?>
            </div>

            <div class="quick-view-section">
                <h3>🏠 Propiedad</h3>
                <p><strong><?php echo esc_html($property_name); ?></strong></p>
                <?php if ($checkin && $checkout): ?>
                    <p>↓ Check-in: <strong><?php echo date_i18n('d/m/Y', strtotime($checkin)); ?></strong></p>
                    <p>↑ Check-out: <strong><?php echo date_i18n('d/m/Y', strtotime($checkout)); ?></strong></p>
                    <p>🌙 <?php echo $nights; ?> noches</p>
                <?php endif; ?>
            </div>

            <div class="quick-view-section">
                <h3>💰 Pagos</h3>
                <p>Total: <strong><?php echo wc_price($order->get_total()); ?></strong></p>
                <p>Pagado: <strong><?php echo wc_price($order->get_total() - $order->get_total_refunded()); ?></strong></p>
                <p>Método: <?php echo $order->get_payment_method_title(); ?></p>
            </div>
        </div>

        <div class="quick-view-actions">
            <a href="<?php echo get_edit_post_link($order_id); ?>" class="button button-primary" target="_blank">
                ✏️ Editar Pedido Completo
            </a>
            <a href="<?php echo $order->get_view_order_url(); ?>" class="button" target="_blank">
                👁️ Ver en Frontend
            </a>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Contar check-ins hoy
     */
    private function count_checkins_today()
    {
        global $wpdb;

        $today = date('Y-m-d');

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
            WHERE meta_key = '_booking_checkin_date'
            AND meta_value = %s",
            $today
        ));

        return intval($count);
    }

    /**
     * Contar check-outs hoy
     */
    private function count_checkouts_today()
    {
        global $wpdb;

        $today = date('Y-m-d');

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
            WHERE meta_key = '_booking_checkout_date'
            AND meta_value = %s",
            $today
        ));

        return intval($count);
    }

    /**
     * Cargar estilos
     */
    public function enqueue_assets($hook)
    {
        // Cargar sistema de toast notifications
        wp_enqueue_style(
            'alquipress-toast-notifications',
            ALQUIPRESS_URL . 'includes/admin/assets/toast-notifications.css',
            [],
            ALQUIPRESS_VERSION
        );
        
        wp_enqueue_script(
            'alquipress-toast-notifications',
            ALQUIPRESS_URL . 'includes/admin/assets/toast-notifications.js',
            ['jquery'],
            ALQUIPRESS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'alquipress-quick-actions',
            ALQUIPRESS_URL . 'includes/modules/quick-actions/assets/quick-actions.css',
            [],
            ALQUIPRESS_VERSION
        );
    }
}

new Alquipress_Quick_Actions();
