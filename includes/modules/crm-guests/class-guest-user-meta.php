<?php
/**
 * Campos de usuario nativos para el CRM de Huéspedes.
 * Reemplaza el grupo de campos ACF "group_crm_cliente" (user_form).
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Guest_User_Meta
{
    /** Opciones para campos select. */
    private static array $options = [];

    public function __construct()
    {
        self::$options = self::build_options();

        add_action('show_user_profile',    [$this, 'render_profile_fields']);
        add_action('edit_user_profile',    [$this, 'render_profile_fields']);

        add_action('personal_options_update',  [$this, 'save_profile_fields']);
        add_action('edit_user_profile_update', [$this, 'save_profile_fields']);
    }

    // ── Renderizar campos ────────────────────────────────────────────────────

    public function render_profile_fields(\WP_User $user): void
    {
        wp_nonce_field('alquipress_guest_profile_save', 'alquipress_guest_nonce');

        $uid    = $user->ID;
        $prefix = 'user_' . $uid;

        $status    = Ap_Fields::get('guest_status',    $prefix) ?: 'standard';
        $rating    = (int) (Ap_Fields::get('guest_rating',     $prefix) ?: 0);
        $notes     = (string) Ap_Fields::get('guest_internal_notes', $prefix);
        $sex       = (string) Ap_Fields::get('guest_sex',      $prefix);
        $birth     = (string) Ap_Fields::get('guest_birth_date', $prefix);
        $prefs     = (array)  Ap_Fields::get('guest_preferences', $prefix);
        $lang      = (string) Ap_Fields::get('guest_preferred_language', $prefix) ?: 'es';
        $channel   = (string) Ap_Fields::get('guest_contact_channel', $prefix) ?: 'whatsapp';
        $trip_type = (string) Ap_Fields::get('guest_trip_type', $prefix) ?: 'family';
        $needs     = (string) Ap_Fields::get('guest_special_needs', $prefix);
        $phone     = (string) Ap_Fields::get('guest_phone',    $prefix);
        $nat       = (string) Ap_Fields::get('guest_nationality', $prefix);
        $documents = (array)  Ap_Fields::get('guest_documents', $prefix);
        ?>
        <style>
            .ap-guest-profile h2 { font-size: 15px; font-weight: 700; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid #e5e7eb; color: #111; }
            .ap-guest-profile table.form-table th { width: 200px; }
            .ap-guest-profile .ap-stars label { cursor: pointer; font-size: 20px; color: #f59e0b; }
            .ap-guest-profile .ap-checkboxes { display: flex; flex-wrap: wrap; gap: 8px; }
            .ap-guest-profile .ap-checkboxes label { display: flex; align-items: center; gap: 4px; font-weight: normal; padding: 4px 10px; background: #f3f4f6; border-radius: 6px; cursor: pointer; font-size: 13px; }
            .ap-guest-docs-row { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
            .ap-guest-docs-row .ap-doc-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
            .ap-guest-docs-row .ap-doc-grid label { font-size: 11px; font-weight: 600; color: #6b7280; }
            .ap-guest-docs-row .ap-doc-grid input, .ap-guest-docs-row .ap-doc-grid select { width: 100%; }
            #ap-docs-container .ap-remove-doc { float: right; color: #dc2626; cursor: pointer; font-size: 11px; background: none; border: none; padding: 0; }
        </style>

        <div class="ap-guest-profile">
            <h2><?php esc_html_e('CRM Huésped — Alquipress', 'alquipress'); ?></h2>

            <table class="form-table">
                <tr>
                    <th><label for="guest_status"><?php esc_html_e('Estado del Cliente', 'alquipress'); ?></label></th>
                    <td>
                        <select id="guest_status" name="guest_status">
                            <?php foreach (self::$options['status'] as $val => $label) : ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($status, $val); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Valoración Interna', 'alquipress'); ?></th>
                    <td class="ap-stars">
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <label>
                                <input type="radio" name="guest_rating" value="<?php echo $i; ?>" <?php checked($rating, $i); ?> style="display:none">
                                <?php echo $i <= $rating ? '★' : '☆'; ?>
                            </label>
                        <?php endfor; ?>
                        <input type="hidden" id="guest_rating_val" name="guest_rating" value="<?php echo esc_attr($rating); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="guest_internal_notes"><?php esc_html_e('Notas Internas (Privado)', 'alquipress'); ?></label></th>
                    <td><textarea id="guest_internal_notes" name="guest_internal_notes" rows="4" style="width:100%;max-width:500px"><?php echo esc_textarea($notes); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="guest_phone"><?php esc_html_e('Teléfono', 'alquipress'); ?></label></th>
                    <td><input type="text" id="guest_phone" name="guest_phone" value="<?php echo esc_attr($phone); ?>" style="max-width:220px" /></td>
                </tr>
                <tr>
                    <th><label for="guest_nationality"><?php esc_html_e('Nacionalidad', 'alquipress'); ?></label></th>
                    <td><input type="text" id="guest_nationality" name="guest_nationality" value="<?php echo esc_attr($nat); ?>" placeholder="ESP" style="max-width:100px" /></td>
                </tr>
                <tr>
                    <th><label for="guest_sex"><?php esc_html_e('Sexo', 'alquipress'); ?></label></th>
                    <td>
                        <select id="guest_sex" name="guest_sex">
                            <option value=""></option>
                            <?php foreach (self::$options['sex'] as $val => $label) : ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($sex, $val); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="guest_birth_date"><?php esc_html_e('Fecha de nacimiento', 'alquipress'); ?></label></th>
                    <td><input type="date" id="guest_birth_date" name="guest_birth_date" value="<?php echo esc_attr($birth); ?>" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Preferencias', 'alquipress'); ?></th>
                    <td>
                        <div class="ap-checkboxes">
                            <?php foreach (self::$options['preferences'] as $val => $label) : ?>
                                <label>
                                    <input type="checkbox" name="guest_preferences[]" value="<?php echo esc_attr($val); ?>" <?php checked(in_array($val, $prefs, true)); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label for="guest_preferred_language"><?php esc_html_e('Idioma preferido', 'alquipress'); ?></label></th>
                    <td>
                        <select id="guest_preferred_language" name="guest_preferred_language">
                            <?php foreach (self::$options['languages'] as $val => $label) : ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($lang, $val); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="guest_contact_channel"><?php esc_html_e('Canal de contacto', 'alquipress'); ?></label></th>
                    <td>
                        <select id="guest_contact_channel" name="guest_contact_channel">
                            <?php foreach (self::$options['channels'] as $val => $label) : ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($channel, $val); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="guest_trip_type"><?php esc_html_e('Tipo de viaje', 'alquipress'); ?></label></th>
                    <td>
                        <select id="guest_trip_type" name="guest_trip_type">
                            <?php foreach (self::$options['trip_types'] as $val => $label) : ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($trip_type, $val); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="guest_special_needs"><?php esc_html_e('Necesidades especiales', 'alquipress'); ?></label></th>
                    <td><textarea id="guest_special_needs" name="guest_special_needs" rows="3" style="width:100%;max-width:500px" placeholder="<?php esc_attr_e('Ej: cuna, trona, acceso PMR', 'alquipress'); ?>"><?php echo esc_textarea($needs); ?></textarea></td>
                </tr>
            </table>

            <!-- Documentos de identidad (repeater nativo) -->
            <h2><?php esc_html_e('Documentación (DNI/Pasaporte)', 'alquipress'); ?></h2>
            <div id="ap-docs-container">
                <?php foreach ($documents as $idx => $doc) : ?>
                    <?php self::render_doc_row($idx, $doc); ?>
                <?php endforeach; ?>
            </div>
            <button type="button" id="ap-add-doc" class="button"><?php esc_html_e('+ Añadir documento', 'alquipress'); ?></button>

            <!-- Template para nuevas filas (hidden) -->
            <script type="text/html" id="ap-doc-template">
                <?php self::render_doc_row('__IDX__', []); ?>
            </script>
        </div>

        <script>
        (function() {
            var idx = <?php echo count($documents); ?>;
            document.getElementById('ap-add-doc').addEventListener('click', function() {
                var tpl = document.getElementById('ap-doc-template').innerHTML;
                tpl = tpl.replace(/__IDX__/g, idx);
                var div = document.createElement('div');
                div.innerHTML = tpl;
                document.getElementById('ap-docs-container').appendChild(div.firstElementChild);
                idx++;
            });
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('ap-remove-doc')) {
                    e.target.closest('.ap-guest-docs-row').remove();
                }
            });
        })();
        </script>
        <?php
    }

    /**
     * Renderizar una fila del repeater de documentos.
     */
    private static function render_doc_row(int|string $idx, array $doc): void
    {
        $tipo   = $doc['tipo_doc']          ?? '';
        $numero = $doc['numero_doc']        ?? '';
        $vence  = $doc['fecha_vencimiento'] ?? '';
        $expide = $doc['fecha_expedicion']  ?? '';
        $pais   = $doc['pais_expedicion']   ?? '';
        $nombre = $doc['nombre_doc']        ?? '';
        ?>
        <div class="ap-guest-docs-row">
            <button type="button" class="ap-remove-doc"><?php esc_html_e('Eliminar', 'alquipress'); ?></button>
            <div class="ap-doc-grid">
                <div>
                    <label><?php esc_html_e('Tipo', 'alquipress'); ?></label>
                    <select name="guest_documents[<?php echo esc_attr($idx); ?>][tipo_doc]">
                        <option value="NIF"  <?php selected($tipo, 'NIF');  ?>>NIF / DNI</option>
                        <option value="NIE"  <?php selected($tipo, 'NIE');  ?>>NIE</option>
                        <option value="PAS"  <?php selected($tipo, 'PAS');  ?>><?php esc_html_e('Pasaporte', 'alquipress'); ?></option>
                        <option value="OTRO" <?php selected($tipo, 'OTRO'); ?>><?php esc_html_e('Otro', 'alquipress'); ?></option>
                    </select>
                </div>
                <div>
                    <label><?php esc_html_e('Número de Documento', 'alquipress'); ?></label>
                    <input type="text" name="guest_documents[<?php echo esc_attr($idx); ?>][numero_doc]" value="<?php echo esc_attr($numero); ?>" placeholder="12345678A" />
                </div>
                <div>
                    <label><?php esc_html_e('País Expedición', 'alquipress'); ?></label>
                    <input type="text" name="guest_documents[<?php echo esc_attr($idx); ?>][pais_expedicion]" value="<?php echo esc_attr($pais); ?>" placeholder="ESP" maxlength="3" />
                </div>
                <div>
                    <label><?php esc_html_e('Fecha Expedición', 'alquipress'); ?></label>
                    <input type="date" name="guest_documents[<?php echo esc_attr($idx); ?>][fecha_expedicion]" value="<?php echo esc_attr($expide); ?>" />
                </div>
                <div>
                    <label><?php esc_html_e('Fecha Vencimiento', 'alquipress'); ?></label>
                    <input type="date" name="guest_documents[<?php echo esc_attr($idx); ?>][fecha_vencimiento]" value="<?php echo esc_attr($vence); ?>" />
                </div>
                <div>
                    <label><?php esc_html_e('Descripción', 'alquipress'); ?></label>
                    <input type="text" name="guest_documents[<?php echo esc_attr($idx); ?>][nombre_doc]" value="<?php echo esc_attr($nombre); ?>" placeholder="<?php esc_attr_e('DNI Anverso y Reverso', 'alquipress'); ?>" />
                </div>
            </div>
        </div>
        <?php
    }

    // ── Guardar campos ───────────────────────────────────────────────────────

    public function save_profile_fields(int $user_id): void
    {
        if (!isset($_POST['alquipress_guest_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alquipress_guest_nonce'])), 'alquipress_guest_profile_save')) {
            return;
        }
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        $prefix = 'user_' . $user_id;

        // Campos escalares simples
        $scalars = [
            'guest_status'             => 'sanitize_text_field',
            'guest_internal_notes'     => 'wp_kses_post',
            'guest_sex'                => 'sanitize_text_field',
            'guest_birth_date'         => 'sanitize_text_field',
            'guest_preferred_language' => 'sanitize_text_field',
            'guest_contact_channel'    => 'sanitize_text_field',
            'guest_trip_type'          => 'sanitize_text_field',
            'guest_special_needs'      => 'sanitize_textarea_field',
            'guest_phone'              => 'sanitize_text_field',
            'guest_nationality'        => 'sanitize_text_field',
        ];

        foreach ($scalars as $key => $sanitizer) {
            if (isset($_POST[$key])) {
                Ap_Fields::set($key, $prefix, $sanitizer(wp_unslash($_POST[$key])));
            }
        }

        // Rating (int 1-5)
        $rating = isset($_POST['guest_rating']) ? max(0, min(5, (int) $_POST['guest_rating'])) : 0;
        Ap_Fields::set('guest_rating', $prefix, $rating);

        // Preferencias (checkbox array)
        $allowed_prefs = array_keys(self::$options['preferences']);
        $prefs = [];
        if (!empty($_POST['guest_preferences']) && is_array($_POST['guest_preferences'])) {
            foreach ($_POST['guest_preferences'] as $pref) {
                $pref = sanitize_text_field(wp_unslash($pref));
                if (in_array($pref, $allowed_prefs, true)) {
                    $prefs[] = $pref;
                }
            }
        }
        Ap_Fields::set('guest_preferences', $prefix, $prefs);

        // Documentos (repeater → JSON array)
        $documents = [];
        if (!empty($_POST['guest_documents']) && is_array($_POST['guest_documents'])) {
            $allowed_types = ['NIF', 'NIE', 'PAS', 'OTRO'];
            foreach ($_POST['guest_documents'] as $doc) {
                if (!is_array($doc)) {
                    continue;
                }
                $tipo = sanitize_text_field(wp_unslash($doc['tipo_doc'] ?? ''));
                if (!in_array($tipo, $allowed_types, true)) {
                    continue;
                }
                $numero = sanitize_text_field(wp_unslash($doc['numero_doc'] ?? ''));
                if (empty($numero)) {
                    continue;
                }
                $documents[] = [
                    'tipo_doc'          => $tipo,
                    'numero_doc'        => $numero,
                    'fecha_vencimiento' => sanitize_text_field(wp_unslash($doc['fecha_vencimiento'] ?? '')),
                    'fecha_expedicion'  => sanitize_text_field(wp_unslash($doc['fecha_expedicion'] ?? '')),
                    'pais_expedicion'   => strtoupper(sanitize_text_field(wp_unslash($doc['pais_expedicion'] ?? ''))),
                    'nombre_doc'        => sanitize_text_field(wp_unslash($doc['nombre_doc'] ?? '')),
                ];
            }
        }
        // Guardar como JSON (nueva forma) y también como array serializado (compatibilidad)
        update_user_meta($user_id, 'guest_documents', $documents);
    }

    // ── Opciones estáticas ───────────────────────────────────────────────────

    private static function build_options(): array
    {
        return [
            'status'      => [
                'standard'  => __('Estándar', 'alquipress'),
                'vip'       => __('⭐ VIP', 'alquipress'),
                'blacklist' => __('🚫 Lista Negra', 'alquipress'),
            ],
            'sex'         => [
                'M' => __('Masculino', 'alquipress'),
                'F' => __('Femenino', 'alquipress'),
                'X' => __('Otro / No binario', 'alquipress'),
            ],
            'preferences' => [
                'mascotas'     => __('Mascotas', 'alquipress'),
                'nofumador'    => __('No fumador', 'alquipress'),
                'fumador'      => __('Fumador', 'alquipress'),
                'familia'      => __('Familia', 'alquipress'),
                'ninos'        => __('Niños/Familia', 'alquipress'),
                'accesibilidad'=> __('Movilidad Reducida', 'alquipress'),
                'nomada'       => __('Nómada digital', 'alquipress'),
                'silencio'     => __('Zona tranquila', 'alquipress'),
                'parking'      => __('Parking', 'alquipress'),
                'cocina'       => __('Cocina equipada', 'alquipress'),
                'piscina'      => __('Piscina', 'alquipress'),
                'playa'        => __('Cerca de la playa', 'alquipress'),
            ],
            'languages'   => [
                'es'    => 'Español',
                'en'    => 'English',
                'fr'    => 'Français',
                'de'    => 'Deutsch',
                'it'    => 'Italiano',
                'other' => __('Otro', 'alquipress'),
            ],
            'channels'    => [
                'whatsapp' => 'WhatsApp',
                'email'    => 'Email',
                'phone'    => __('Teléfono', 'alquipress'),
                'sms'      => 'SMS',
            ],
            'trip_types'  => [
                'family' => __('Familiar', 'alquipress'),
                'couple' => __('Pareja', 'alquipress'),
                'work'   => __('Trabajo', 'alquipress'),
                'group'  => __('Grupo', 'alquipress'),
                'other'  => __('Otro', 'alquipress'),
            ],
        ];
    }
}
