<?php
namespace Alquipress\Suite\Modules\Security;

if (!defined('ABSPATH'))
    exit;

class Module
{

    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Honeypot en login
        add_action('login_form', [$this, 'add_honeypot_field']);
        add_filter('authenticate', [$this, 'check_honeypot'], 99, 3);

        // Bloqueo de XML-RPC (muy común en ataques)
        add_filter('xmlrpc_enabled', '__return_false');

        // WP Hardening
        add_filter('the_generator', '__return_empty_string'); // Hide WP version
        add_filter('login_errors', function () {
            return 'Algo ha ido mal, inténtalo de nuevo.'; }); // Generic login errors
        add_filter('rest_authentication_errors', [$this, 'restrict_rest_api_access']);

        // Audit Log de cambios financieros (en Propietarios)
        add_action('acf/save_post', [$this, 'audit_financial_changes'], 5); // Prioridad alta para obtener valores antiguos
    }

    public function restrict_rest_api_access($result)
    {
        if (is_user_logged_in()) {
            return $result;
        }

        if (is_wp_error($result)) {
            return $result;
        }

        $allowed_routes = apply_filters('alq_suite_rest_allowed_public_routes', []);
        if ($this->is_rest_route_allowed($allowed_routes)) {
            return $result;
        }

        return new \WP_Error('rest_not_logged_in', 'Acceso restringido.', ['status' => 401]);
    }

    private function is_rest_route_allowed($allowed_routes)
    {
        if (empty($allowed_routes)) {
            return false;
        }

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        $prefix = '/' . rest_get_url_prefix() . '/';

        if (empty($path) || !is_string($path) || strpos($path, $prefix) === false) {
            return false;
        }

        foreach ($allowed_routes as $allowed) {
            if (!$allowed) {
                continue;
            }
            $allowed = ltrim($allowed, '/');
            if (strpos($path, $prefix . $allowed) === 0) {
                return true;
            }
        }

        return false;
    }

    public function add_honeypot_field()
    {
        echo '<p style="display:none;"><label>Si eres humano, deja esto vacío: <input type="text" name="alq_honeypot" value=""></label></p>';
    }

    public function check_honeypot($user, $username, $password)
    {
        if (!empty($_POST['alq_honeypot'])) {
            return new \WP_Error('denied', 'Bot detectado.');
        }
        return $user;
    }

    /**
     * Registra cambios en campos críticos (comisiones, datos bancarios)
     */
    public function audit_financial_changes($post_id)
    {
        if (get_post_type($post_id) !== 'propietario')
            return;

        // Lista de campos críticos a auditar
        $critical_fields = [
            'owner_commission_rate',
            'datos_bancarios_iban',
            'titular_cuenta'
        ];

        if (empty($_POST['acf']) || !is_array($_POST['acf']) || !function_exists('acf_get_field')) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'alquipress_security_log';

        foreach ($_POST['acf'] as $field_key => $raw_value) {
            $field = acf_get_field($field_key);
            if (!$field || empty($field['name'])) {
                continue;
            }

            if (!in_array($field['name'], $critical_fields, true)) {
                continue;
            }

            $old_value = get_field($field['name'], $post_id, false);
            $new_value = $raw_value;

            if (maybe_serialize($old_value) === maybe_serialize($new_value)) {
                continue;
            }

            $wpdb->insert($table_name, [
                'user_id' => get_current_user_id(),
                'action' => 'update_financial_field',
                'entity_type' => 'propietario',
                'entity_id' => $post_id,
                'old_value' => maybe_serialize($this->sanitize_log_value($old_value)),
                'new_value' => maybe_serialize($this->sanitize_log_value($new_value)),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'created_at' => current_time('mysql')
            ]);
        }
    }

    private function sanitize_log_value($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->sanitize_log_value($item);
            }
            return $value;
        }

        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        return sanitize_text_field((string) $value);
    }
}
