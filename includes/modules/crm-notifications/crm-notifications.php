<?php
/**
 * Módulo: Notificaciones CRM
 * Sistema de alertas y recordatorios
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_CRM_Notifications
{

    public function __construct()
    {
        // Mostrar notificaciones en admin
        add_action('admin_notices', [$this, 'show_admin_notices']);

        // Añadir contador en menú
        add_action('admin_menu', [$this, 'add_notification_badge'], 999);

        // AJAX para descartar notificaciones
        add_action('wp_ajax_alquipress_dismiss_notification', [$this, 'ajax_dismiss_notification']);

        // Cargar estilos
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Mostrar notificaciones en admin
     */
    public function show_admin_notices()
    {
        $notifications = $this->get_active_notifications();

        if (empty($notifications)) {
            return;
        }

        foreach ($notifications as $notification) {
            $this->render_notification($notification);
        }
    }

    /**
     * Obtener notificaciones activas
     */
    private function get_active_notifications()
    {
        $notifications = [];
        $dismissed = get_user_meta(get_current_user_id(), 'alquipress_dismissed_notifications', true) ?: [];

        // Notificación: Check-ins hoy
        $checkins_today = $this->get_checkins_today();
        if (!empty($checkins_today) && !in_array('checkins_today_' . date('Y-m-d'), $dismissed)) {
            $notifications[] = [
                'id' => 'checkins_today_' . date('Y-m-d'),
                'type' => 'info',
                'title' => 'Check-ins Programados Hoy',
                'message' => 'Hay ' . count($checkins_today) . ' check-in(s) programado(s) para hoy.',
                'action_url' => admin_url('admin.php?page=alquipress-pipeline'),
                'action_text' => 'Ver Pipeline',
                'dismissible' => true
            ];
        }

        // Notificación: Check-outs hoy
        $checkouts_today = $this->get_checkouts_today();
        if (!empty($checkouts_today) && !in_array('checkouts_today_' . date('Y-m-d'), $dismissed)) {
            $notifications[] = [
                'id' => 'checkouts_today_' . date('Y-m-d'),
                'type' => 'warning',
                'title' => 'Check-outs Programados Hoy',
                'message' => 'Hay ' . count($checkouts_today) . ' check-out(s) programado(s) para hoy. Prepara las revisiones de salida.',
                'action_url' => admin_url('admin.php?page=alquipress-pipeline'),
                'action_text' => 'Ver Pipeline',
                'dismissible' => true
            ];
        }

        // Notificación: Check-ins mañana (recordatorio)
        $checkins_tomorrow = $this->get_checkins_tomorrow();
        if (!empty($checkins_tomorrow) && !in_array('checkins_tomorrow_' . date('Y-m-d'), $dismissed)) {
            $notifications[] = [
                'id' => 'checkins_tomorrow_' . date('Y-m-d'),
                'type' => 'info',
                'title' => 'Recordatorio: Check-ins Mañana',
                'message' => count($checkins_tomorrow) . ' check-in(s) programado(s) para mañana. Asegúrate de que las propiedades estén listas.',
                'action_url' => admin_url('admin.php?page=alquipress-pipeline'),
                'action_text' => 'Ver Detalles',
                'dismissible' => true
            ];
        }

        // Notificación: Pedidos pendientes de pago
        $pending_orders = $this->get_pending_payment_orders();
        if (count($pending_orders) >= 5 && !in_array('pending_payments', $dismissed)) {
            $notifications[] = [
                'id' => 'pending_payments',
                'type' => 'warning',
                'title' => 'Pedidos Pendientes de Pago',
                'message' => 'Tienes ' . count($pending_orders) . ' pedidos pendientes de pago. Revisa y haz seguimiento.',
                'action_url' => admin_url('edit.php?post_type=shop_order&post_status=wc-pending'),
                'action_text' => 'Ver Pedidos',
                'dismissible' => true
            ];
        }

        // Notificación: Propietarios sin IBAN
        $owners_without_iban = $this->get_owners_without_iban();
        if ($owners_without_iban > 0 && !in_array('owners_no_iban', $dismissed)) {
            $notifications[] = [
                'id' => 'owners_no_iban',
                'type' => 'error',
                'title' => 'Propietarios Sin Datos Bancarios',
                'message' => $owners_without_iban . ' propietario(s) no tiene(n) IBAN registrado. Completa esta información para procesar pagos.',
                'action_url' => admin_url('edit.php?post_type=propietario'),
                'action_text' => 'Ver Propietarios',
                'dismissible' => true
            ];
        }

        // Notificación: Reservas en revisión de salida
        $review_orders = $this->get_checkout_review_orders();
        if (!empty($review_orders) && !in_array('checkout_reviews', $dismissed)) {
            $notifications[] = [
                'id' => 'checkout_reviews',
                'type' => 'warning',
                'title' => 'Propiedades en Revisión',
                'message' => count($review_orders) . ' propiedad(es) están pendientes de revisión de salida.',
                'action_url' => admin_url('edit.php?post_type=shop_order&post_status=wc-checkout-review'),
                'action_text' => 'Ver Revisiones',
                'dismissible' => true
            ];
        }

        return $notifications;
    }

    /**
     * Renderizar notificación
     */
    private function render_notification($notification)
    {
        $type_class = 'notice-' . $notification['type'];
        $dismissible = $notification['dismissible'] ? 'is-dismissible' : '';

        ?>
        <div class="notice <?php echo esc_attr($type_class); ?> alquipress-notification <?php echo esc_attr($dismissible); ?>"
            data-notification-id="<?php echo esc_attr($notification['id']); ?>">
            <div class="notification-content">
                <div class="notification-icon">
                    <?php
                    $icons = [
                        'info' => '📅',
                        'warning' => '⚠️',
                        'error' => '🚨',
                        'success' => '✅'
                    ];
                    echo $icons[$notification['type']] ?? '📢';
                    ?>
                </div>
                <div class="notification-body">
                    <h3 class="notification-title"><?php echo esc_html($notification['title']); ?></h3>
                    <p class="notification-message"><?php echo esc_html($notification['message']); ?></p>
                    <?php if (isset($notification['action_url'])): ?>
                        <a href="<?php echo esc_url($notification['action_url']); ?>" class="notification-action button">
                            <?php echo esc_html($notification['action_text']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($notification['dismissible']): ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.alquipress-notification[data-notification-id="<?php echo esc_js($notification['id']); ?>"] .notice-dismiss')
                        .on('click', function () {
                            $.ajax({
                                url: ajaxurl,
                                method: 'POST',
                                data: {
                                    action: 'alquipress_dismiss_notification',
                                    notification_id: '<?php echo esc_js($notification['id']); ?>',
                                    nonce: '<?php echo wp_create_nonce('alquipress_notifications'); ?>'
                                }
                            });
                        });
                });
            </script>
        <?php endif; ?>
        <?php
    }

    /**
     * AJAX: Descartar notificación
     */
    public function ajax_dismiss_notification()
    {
        check_ajax_referer('alquipress_notifications', 'nonce');

        $notification_id = isset($_POST['notification_id']) ? sanitize_text_field($_POST['notification_id']) : '';

        if (empty($notification_id)) {
            wp_send_json_error();
        }

        $user_id = get_current_user_id();
        $dismissed = get_user_meta($user_id, 'alquipress_dismissed_notifications', true) ?: [];

        if (!in_array($notification_id, $dismissed)) {
            $dismissed[] = $notification_id;
            update_user_meta($user_id, 'alquipress_dismissed_notifications', $dismissed);
        }

        wp_send_json_success();
    }

    /**
     * Añadir badge de contador en menú
     */
    public function add_notification_badge()
    {
        global $menu;

        $count = count($this->get_active_notifications());

        if ($count === 0) {
            return;
        }

        // Buscar el menú de ALQUIPRESS
        foreach ($menu as $key => $item) {
            if (isset($item[2]) && $item[2] === 'alquipress-settings') {
                $menu[$key][0] .= ' <span class="awaiting-mod update-plugins count-' . $count . '"><span class="plugin-count">' . $count . '</span></span>';
                break;
            }
        }
    }

    // ========== Métodos auxiliares ==========

    private function get_checkins_today()
    {
        global $wpdb;

        $today = date('Y-m-d');

        return $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_booking_checkin_date'
            AND meta_value = %s",
            $today
        ));
    }

    private function get_checkouts_today()
    {
        global $wpdb;

        $today = date('Y-m-d');

        return $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_booking_checkout_date'
            AND meta_value = %s",
            $today
        ));
    }

    private function get_checkins_tomorrow()
    {
        global $wpdb;

        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        return $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_booking_checkin_date'
            AND meta_value = %s",
            $tomorrow
        ));
    }

    private function get_pending_payment_orders()
    {
        return wc_get_orders([
            'status' => 'pending',
            'limit' => -1,
        ]);
    }

    private function get_owners_without_iban()
    {
        $owners = get_posts([
            'post_type' => 'propietario',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        $count = 0;
        foreach ($owners as $owner_id) {
            $iban = get_field('owner_iban', $owner_id);
            if (empty($iban)) {
                $count++;
            }
        }

        return $count;
    }

    private function get_checkout_review_orders()
    {
        return wc_get_orders([
            'status' => 'checkout-review',
            'limit' => -1,
        ]);
    }

    /**
     * Cargar estilos
     */
    public function enqueue_assets($hook)
    {
        wp_enqueue_style(
            'alquipress-crm-notifications',
            ALQUIPRESS_URL . 'includes/modules/crm-notifications/assets/crm-notifications.css',
            [],
            ALQUIPRESS_VERSION
        );
    }
}

new Alquipress_CRM_Notifications();
