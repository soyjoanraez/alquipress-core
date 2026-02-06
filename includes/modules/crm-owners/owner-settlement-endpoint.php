<?php
/**
 * Endpoint seguro para descarga de liquidaciones de propietario.
 *
 * @package Alquipress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Owner_Settlement_Endpoint
{
    const ACTION = 'alquipress_download_owner_settlement';
    const NONCE_ACTION = 'alquipress_owner_settlement_download';
    const DEFAULT_TTL = 15 * MINUTE_IN_SECONDS;

    public function __construct()
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handle_download']);
    }

    /**
     * Genera URL firmada, con nonce y expiración.
     *
     * @param int   $owner_id ID del propietario (CPT propietario).
     * @param string $month   Mes en formato YYYY-MM.
     * @param array $args     Argumentos opcionales: ttl, user_id.
     * @return string
     */
    public static function build_signed_url($owner_id, $month, array $args = [])
    {
        $owner_id = (int) $owner_id;
        $month = self::normalize_month($month);
        $user_id = isset($args['user_id']) ? (int) $args['user_id'] : (int) get_current_user_id();
        $ttl = isset($args['ttl']) ? max(60, (int) $args['ttl']) : self::DEFAULT_TTL;

        if ($owner_id <= 0 || !$month || $user_id <= 0) {
            return '';
        }

        $owner_post = get_post($owner_id);
        if (!$owner_post || $owner_post->post_type !== 'propietario') {
            return '';
        }

        $expires = time() + $ttl;
        $signature = self::build_signature($owner_id, $month, $expires, $user_id);
        $nonce = wp_create_nonce(self::nonce_action_for($owner_id, $month, $expires, $user_id));

        return add_query_arg(
            [
                'action' => self::ACTION,
                'owner_id' => $owner_id,
                'month' => $month,
                'uid' => $user_id,
                'expires' => $expires,
                'sig' => $signature,
                'nonce' => $nonce,
            ],
            admin_url('admin-post.php')
        );
    }

    /**
     * Endpoint de descarga protegido.
     *
     * @return void
     */
    public function handle_download()
    {
        if (!is_user_logged_in()) {
            $this->die_with_status(__('Debes iniciar sesión para descargar la liquidación.', 'alquipress'), 401);
        }

        $owner_id = isset($_GET['owner_id']) ? absint(wp_unslash($_GET['owner_id'])) : 0;
        $month = isset($_GET['month']) ? sanitize_text_field(wp_unslash($_GET['month'])) : '';
        $uid = isset($_GET['uid']) ? absint(wp_unslash($_GET['uid'])) : 0;
        $expires = isset($_GET['expires']) ? (int) wp_unslash($_GET['expires']) : 0;
        $signature = isset($_GET['sig']) ? sanitize_text_field(wp_unslash($_GET['sig'])) : '';
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';

        $month = self::normalize_month($month);
        if ($owner_id <= 0 || !$month || $uid <= 0 || $expires <= 0 || empty($signature) || empty($nonce)) {
            $this->die_with_status(__('Solicitud de descarga inválida.', 'alquipress'), 400);
        }

        if ((int) get_current_user_id() !== $uid) {
            $this->deny_request('Intento de descarga con identidad no válida.', 403, $owner_id, $month, $uid);
        }

        if (time() > $expires) {
            $this->deny_request('Enlace de liquidación expirado.', 403, $owner_id, $month, $uid);
        }

        $expected_signature = self::build_signature($owner_id, $month, $expires, $uid);
        if (!hash_equals($expected_signature, $signature)) {
            $this->deny_request('Firma de enlace inválida.', 403, $owner_id, $month, $uid);
        }

        if (!wp_verify_nonce($nonce, self::nonce_action_for($owner_id, $month, $expires, $uid))) {
            $this->deny_request('Nonce de descarga inválido.', 403, $owner_id, $month, $uid);
        }

        if (!$this->can_user_access_owner($uid, $owner_id)) {
            $this->deny_request('Acceso no autorizado a liquidación de propietario.', 403, $owner_id, $month, $uid);
        }

        $owner_post = get_post($owner_id);
        if (!$owner_post || $owner_post->post_type !== 'propietario') {
            $this->die_with_status(__('Propietario no encontrado.', 'alquipress'), 404);
        }

        $settlement = $this->build_settlement_payload($owner_id, $month);
        $pdf = $this->build_pdf_from_settlement($settlement);

        $filename = sprintf(
            'liquidacion-%s-%s.pdf',
            sanitize_title($owner_post->post_title),
            str_replace('-', '', $month)
        );

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));

        echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Valida si el usuario puede acceder a la liquidación del propietario.
     *
     * @param int $user_id Usuario autenticado.
     * @param int $owner_id CPT propietario.
     * @return bool
     */
    private function can_user_access_owner($user_id, $owner_id)
    {
        $user_id = (int) $user_id;
        $owner_id = (int) $owner_id;

        if ($user_id <= 0 || $owner_id <= 0) {
            return false;
        }

        if (
            user_can($user_id, 'manage_options') ||
            user_can($user_id, 'manage_woocommerce') ||
            user_can($user_id, 'edit_post', $owner_id)
        ) {
            return true;
        }

        if (user_can($user_id, Alquipress_Owner_Role_Manager::ROLE_PROPERTY_OWNER) && $this->is_owner_linked_to_user($owner_id, $user_id)) {
            return true;
        }

        return (bool) apply_filters('alquipress_owner_settlement_can_access', false, $user_id, $owner_id);
    }

    /**
     * Comprueba relación propietario <-> usuario.
     *
     * @param int $owner_id ID del propietario.
     * @param int $user_id  ID del usuario.
     * @return bool
     */
    private function is_owner_linked_to_user($owner_id, $user_id)
    {
        $meta_user_keys = [
            'owner_user_id',
            'owner_wp_user_id',
            '_owner_user_id',
            '_owner_wp_user_id',
        ];

        foreach ($meta_user_keys as $meta_key) {
            $linked_user_id = (int) get_post_meta($owner_id, $meta_key, true);
            if ($linked_user_id > 0 && $linked_user_id === (int) $user_id) {
                return true;
            }
        }

        if (function_exists('get_field')) {
            $acf_user = get_field('owner_user', $owner_id);
            if (is_numeric($acf_user) && (int) $acf_user === (int) $user_id) {
                return true;
            }
            if (is_object($acf_user) && isset($acf_user->ID) && (int) $acf_user->ID === (int) $user_id) {
                return true;
            }
        }

        $owner_email = sanitize_email((string) get_post_meta($owner_id, 'owner_email_management', true));
        $user = get_userdata((int) $user_id);
        if ($user && !empty($owner_email) && strtolower($owner_email) === strtolower($user->user_email)) {
            return true;
        }

        return false;
    }

    /**
     * Construye la firma HMAC del enlace.
     *
     * @param int $owner_id ID del propietario.
     * @param string $month Mes YYYY-MM.
     * @param int $expires Expiración UNIX timestamp.
     * @param int $uid Usuario firmante.
     * @return string
     */
    private static function build_signature($owner_id, $month, $expires, $uid)
    {
        $payload = implode('|', [(int) $owner_id, $month, (int) $expires, (int) $uid]);

        return hash_hmac('sha256', $payload, wp_salt('logged_in'));
    }

    /**
     * Acción de nonce con contexto fuerte.
     *
     * @param int $owner_id ID del propietario.
     * @param string $month Mes YYYY-MM.
     * @param int $expires Expiración UNIX timestamp.
     * @param int $uid Usuario.
     * @return string
     */
    private static function nonce_action_for($owner_id, $month, $expires, $uid)
    {
        return self::NONCE_ACTION . '|' . (int) $owner_id . '|' . $month . '|' . (int) $expires . '|' . (int) $uid;
    }

    /**
     * Normaliza y valida el formato YYYY-MM.
     *
     * @param string $month Mes.
     * @return string
     */
    private static function normalize_month($month)
    {
        $month = trim((string) $month);
        if (!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $month)) {
            return '';
        }

        return $month;
    }

    /**
     * Arma datos de liquidación para el mes solicitado.
     *
     * @param int $owner_id ID propietario.
     * @param string $month Mes YYYY-MM.
     * @return array
     */
    private function build_settlement_payload($owner_id, $month)
    {
        $start = $month . '-01';
        $end = gmdate('Y-m-t', strtotime($start));

        $stats = [
            'total' => 0,
            'commission' => 0,
            'net' => 0,
            'count' => 0,
        ];

        if (class_exists('Alquipress_Owner_Revenue')) {
            $stats = Alquipress_Owner_Revenue::get_instance()->calculate_owner_revenue($owner_id, $start, $end);
        }

        $commission_rate = (float) get_post_meta($owner_id, 'owner_commission_rate', true);
        $owner_name = get_the_title($owner_id);
        $generated_at = current_time('mysql');

        return [
            'owner_id' => (int) $owner_id,
            'owner_name' => $owner_name ? $owner_name : __('Propietario', 'alquipress'),
            'month' => $month,
            'start' => $start,
            'end' => $end,
            'commission_rate' => $commission_rate,
            'gross_total' => isset($stats['total']) ? (float) $stats['total'] : 0.0,
            'commission_total' => isset($stats['commission']) ? (float) $stats['commission'] : 0.0,
            'net_total' => isset($stats['net']) ? (float) $stats['net'] : 0.0,
            'bookings_count' => isset($stats['count']) ? (int) $stats['count'] : 0,
            'generated_at' => $generated_at,
        ];
    }

    /**
     * Construye un PDF simple para descarga.
     *
     * @param array $settlement Datos de liquidación.
     * @return string PDF binario.
     */
    private function build_pdf_from_settlement(array $settlement)
    {
        $month_label = date_i18n('F Y', strtotime($settlement['start']));
        $generated_label = date_i18n('d/m/Y H:i', strtotime($settlement['generated_at']));

        $lines = [
            'ALQUIPRESS - Liquidacion de propietario',
            '---------------------------------------',
            'Propietario: ' . $settlement['owner_name'],
            'Mes liquidado: ' . $month_label,
            'Periodo: ' . $settlement['start'] . ' a ' . $settlement['end'],
            '',
            'Total bruto: ' . $this->format_amount($settlement['gross_total']),
            'Comision (' . $this->format_percentage($settlement['commission_rate']) . '): ' . $this->format_amount($settlement['commission_total']),
            'Total neto: ' . $this->format_amount($settlement['net_total']),
            'Reservas computadas: ' . (int) $settlement['bookings_count'],
            '',
            'Generado: ' . $generated_label,
            'Documento interno ALQUIPRESS.',
        ];

        $commands = [];
        $commands[] = 'BT';
        $commands[] = '/F1 12 Tf';
        $commands[] = '50 790 Td';

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $commands[] = '0 -16 Td';
            }
            $commands[] = '(' . $this->pdf_escape($line) . ') Tj';
        }

        $commands[] = 'ET';
        $stream = implode("\n", $commands) . "\n";

        return $this->build_pdf_document($stream);
    }

    /**
     * Construye un PDF mínimo válido.
     *
     * @param string $stream Content stream PDF.
     * @return string
     */
    private function build_pdf_document($stream)
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xref_offset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref_offset . "\n%%EOF";

        return $pdf;
    }

    /**
     * Escapa texto para literales PDF.
     *
     * @param string $text Texto.
     * @return string
     */
    private function pdf_escape($text)
    {
        $text = (string) $text;
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);
    }

    /**
     * Formatea importe en EUR sin depender de WooCommerce.
     *
     * @param float $amount Importe.
     * @return string
     */
    private function format_amount($amount)
    {
        $amount = (float) $amount;
        if (function_exists('wc_price')) {
            return wp_strip_all_tags(wc_price($amount));
        }

        return number_format_i18n($amount, 2) . ' EUR';
    }

    /**
     * Formatea porcentaje.
     *
     * @param float $value Porcentaje.
     * @return string
     */
    private function format_percentage($value)
    {
        return number_format_i18n((float) $value, 2) . '%';
    }

    /**
     * Deniega solicitud con logging.
     *
     * @param string $message Mensaje.
     * @param int $code HTTP code.
     * @param int $owner_id ID propietario.
     * @param string $month Mes.
     * @param int $uid Usuario.
     * @return void
     */
    private function deny_request($message, $code, $owner_id, $month, $uid)
    {
        if (class_exists('Alquipress_Logger')) {
            Alquipress_Logger::warning(
                $message,
                Alquipress_Logger::CONTEXT_SECURITY,
                [
                    'owner_id' => (int) $owner_id,
                    'month' => $month,
                    'uid' => (int) $uid,
                    'current_user' => (int) get_current_user_id(),
                    'ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
                ]
            );
        }

        $this->die_with_status($message, (int) $code);
    }

    /**
     * Termina la solicitud con código HTTP explícito.
     *
     * @param string $message Mensaje visible.
     * @param int $code Código HTTP.
     * @return void
     */
    private function die_with_status($message, $code)
    {
        wp_die(
            esc_html($message),
            esc_html__('Acceso denegado', 'alquipress'),
            ['response' => (int) $code]
        );
    }
}

/**
 * Helper público para construir URL de descarga de liquidación.
 *
 * @param int $owner_id ID propietario.
 * @param string $month Mes YYYY-MM.
 * @param array $args  ttl/user_id opcionales.
 * @return string
 */
function alquipress_get_owner_settlement_download_url($owner_id, $month, array $args = [])
{
    return Alquipress_Owner_Settlement_Endpoint::build_signed_url($owner_id, $month, $args);
}

new Alquipress_Owner_Settlement_Endpoint();
