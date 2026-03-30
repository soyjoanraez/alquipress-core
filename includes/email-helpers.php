<?php
/**
 * Helpers para envío de emails HTML (Módulo 06).
 *
 * @package Alquipress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enviar email HTML usando plantilla base.
 *
 * @param string $to    Destinatario.
 * @param string $subject Asunto.
 * @param string $html_body Cuerpo HTML (sin wrapper).
 * @return bool
 */
function alquipress_send_custom_email($to, $subject, $html_body) {
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
    ];
    $full_html = alquipress_email_wrapper($subject, $html_body);
    return wp_mail($to, $subject, $full_html, $headers);
}

/**
 * Plantilla HTML base para emails de ALQUIPRESS.
 *
 * @param string $title   Título (para <title>).
 * @param string $content Contenido HTML del cuerpo.
 * @return string
 */
function alquipress_email_wrapper($title, $content) {
    $logo_url = get_option('alquipress_logo_url', '');
    if (!$logo_url) {
        $logo_url = home_url('/wp-content/uploads/logo-alquipress.png');
    }
    $primary = '#1e3a5f';
    $unsubscribe = home_url('/baja-newsletter/');
    $content = wp_kses_post($content);
    return '<!DOCTYPE html><html lang="' . esc_attr(get_locale()) . '"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>' . esc_html($title) . '</title></head><body style="margin:0;padding:0;background:#f5f7fa;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;">' .
        '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fa;padding:32px 16px;"><tr><td align="center">' .
        '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">' .
        '<tr><td style="background:' . $primary . ';padding:28px 40px;text-align:center;">' .
        ($logo_url ? '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" height="40" style="display:block;margin:0 auto;">' : '<span style="color:#fff;font-size:1.5rem;font-weight:600;">' . esc_html(get_bloginfo('name')) . '</span>') .
        '</td></tr>' .
        '<tr><td style="padding:40px;color:#1a2636;font-size:15px;line-height:1.6;">' . $content . '</td></tr>' .
        '<tr><td style="background:#f0f4f8;padding:24px 40px;text-align:center;font-size:12px;color:#6b7280;">' .
        '<p style="margin:0 0 8px;">' . esc_html(get_bloginfo('name')) . '</p>' .
        '<p style="margin:0;"><a href="' . esc_url($unsubscribe) . '" style="color:#9ca3af;text-decoration:underline;">' . __('Cancelar suscripción', 'alquipress') . '</a></p>' .
        '</td></tr></table></td></tr></table></body></html>';
}
