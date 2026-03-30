<?php
/**
 * Guardado nativo de campos de producto (reemplaza ACF save_post).
 *
 * Registra un meta box en el editor de productos de WooCommerce y guarda
 * todos los campos del grupo "Alojamiento: Detalles y Configuración" usando
 * update_post_meta() puro.
 *
 * NOTA: La UI principal ya existe en el dashboard personalizado
 * (property-edit-layout.php). Este meta box aparece en el editor nativo de
 * WordPress/WooCommerce como panel secundario, útil cuando ACF no está activo.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Product_Fields
{
    /** Campos escalares: nombre → función de saneamiento. */
    private static array $scalar_fields = [
        'licencia_turistica'       => 'sanitize_text_field',
        'referencia_interna'       => 'sanitize_text_field',
        'superficie_m2'            => 'floatval',
        'numero_habitaciones'      => 'absint',
        'numero_banos'             => 'absint',
        'capacidad_maxima'         => 'absint',
        'distancia_playa'          => 'absint',
        'distancia_centro'         => 'absint',
        'hora_checkin'             => 'sanitize_text_field',
        'hora_checkout'            => 'sanitize_text_field',
        'fianza_texto'             => 'sanitize_text_field',
        'codigo_caja_llaves'       => 'sanitize_text_field',
        'password_wifi'            => 'sanitize_text_field',
        'contacto_emergencia'      => 'sanitize_text_field',
        'direccion_completa'       => 'sanitize_text_field',
        'seo_title_override'       => 'sanitize_text_field',
        'seo_description_override' => 'sanitize_textarea_field',
    ];

    /** Campos textarea con kses. */
    private static array $textarea_fields = [
        'instrucciones_checkin',
        'instrucciones_checkout',
        'normas_casa',
    ];

    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post_product', [$this, 'save'], 15, 2);
    }

    public function register_meta_box(): void
    {
        add_meta_box(
            'alquipress_product_fields',
            __('Alquipress — Detalles del Alojamiento', 'alquipress'),
            [$this, 'render'],
            'product',
            'normal',
            'default'
        );
    }

    public function render(\WP_Post $post): void
    {
        wp_nonce_field('alquipress_product_fields_save', 'alquipress_product_fields_nonce');

        $pid = $post->ID;

        // Leer valores actuales
        $data = [];
        foreach (array_merge(array_keys(self::$scalar_fields), self::$textarea_fields) as $key) {
            $data[$key] = get_post_meta($pid, $key, true);
        }

        // Coordenadas GPS (array)
        $coords = get_post_meta($pid, 'coordenadas_gps', true);
        if (!is_array($coords)) {
            $coords = [];
        }
        $data['gps_lat']     = $coords['lat']     ?? $coords['latitude']  ?? '';
        $data['gps_lng']     = $coords['lng']      ?? $coords['longitude'] ?? '';
        $data['gps_address'] = $coords['address']  ?? '';

        // Propietario asignado (relationship max:1)
        $raw_prop = get_post_meta($pid, 'propietario_asignado', true);
        $prop_id  = is_array($raw_prop) ? (int) reset($raw_prop) : (int) $raw_prop;

        // Distribución habitaciones (repeater)
        $habs = get_post_meta($pid, 'distribucion_habitaciones', true);
        if (!is_array($habs)) {
            $habs = [];
        }

        // Propietarios disponibles
        $owners = get_posts([
            'post_type'      => 'propietario',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        ?>
        <style>
            .ap-product-fields table.form-table th { width: 200px; }
            .ap-product-fields h3 { font-size: 13px; font-weight: 700; margin: 20px 0 10px; color: #374151; border-top: 1px solid #e5e7eb; padding-top: 12px; }
            .ap-product-fields .ap-gps-row { display: flex; gap: 12px; }
            .ap-product-fields .ap-gps-row div { flex: 1; }
            .ap-product-fields .ap-gps-row label { display: block; font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 3px; }
            .ap-hab-row { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; margin-bottom: 8px; display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 8px; align-items: end; }
            .ap-hab-row label { font-size: 11px; font-weight: 600; color: #6b7280; display: block; margin-bottom: 2px; }
            .ap-hab-row input, .ap-hab-row select { width: 100%; }
            .ap-remove-hab { background: none; border: none; color: #dc2626; cursor: pointer; font-size: 18px; line-height: 1; padding: 4px; }
        </style>

        <div class="ap-product-fields">
            <table class="form-table">
                <tr>
                    <th><label for="licencia_turistica"><?php esc_html_e('Licencia Turística', 'alquipress'); ?></label></th>
                    <td><input type="text" id="licencia_turistica" name="licencia_turistica" value="<?php echo esc_attr($data['licencia_turistica']); ?>" style="width:200px" /></td>
                </tr>
                <tr>
                    <th><label for="referencia_interna"><?php esc_html_e('Referencia Interna', 'alquipress'); ?></label></th>
                    <td><input type="text" id="referencia_interna" name="referencia_interna" value="<?php echo esc_attr($data['referencia_interna']); ?>" style="width:160px" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Características', 'alquipress'); ?></th>
                    <td style="display:flex;gap:12px;flex-wrap:wrap">
                        <?php
                        $char_fields = [
                            'superficie_m2'       => ['label' => 'm²',          'min' => 0, 'step' => 1],
                            'numero_habitaciones' => ['label' => __('Habitaciones', 'alquipress'), 'min' => 0, 'step' => 1],
                            'numero_banos'        => ['label' => __('Baños', 'alquipress'),        'min' => 0, 'step' => 1],
                            'capacidad_maxima'    => ['label' => __('Plazas', 'alquipress'),       'min' => 1, 'step' => 1],
                        ];
                        foreach ($char_fields as $cf_key => $cf) :
                        ?>
                            <div>
                                <label for="<?php echo esc_attr($cf_key); ?>" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:2px"><?php echo esc_html($cf['label']); ?></label>
                                <input type="number" id="<?php echo esc_attr($cf_key); ?>" name="<?php echo esc_attr($cf_key); ?>"
                                    value="<?php echo esc_attr($data[$cf_key]); ?>"
                                    min="<?php echo esc_attr($cf['min']); ?>" step="<?php echo esc_attr($cf['step']); ?>"
                                    style="width:80px" />
                            </div>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Distancias (metros)', 'alquipress'); ?></th>
                    <td style="display:flex;gap:12px">
                        <div>
                            <label for="distancia_playa" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:2px"><?php esc_html_e('Playa', 'alquipress'); ?></label>
                            <input type="number" id="distancia_playa" name="distancia_playa" value="<?php echo esc_attr($data['distancia_playa']); ?>" min="0" style="width:100px" />
                        </div>
                        <div>
                            <label for="distancia_centro" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:2px"><?php esc_html_e('Centro', 'alquipress'); ?></label>
                            <input type="number" id="distancia_centro" name="distancia_centro" value="<?php echo esc_attr($data['distancia_centro']); ?>" min="0" style="width:100px" />
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Horarios', 'alquipress'); ?></th>
                    <td style="display:flex;gap:12px">
                        <div>
                            <label for="hora_checkin" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:2px">Check-in</label>
                            <input type="time" id="hora_checkin" name="hora_checkin" value="<?php echo esc_attr($data['hora_checkin'] ?: '16:00'); ?>" />
                        </div>
                        <div>
                            <label for="hora_checkout" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:2px">Check-out</label>
                            <input type="time" id="hora_checkout" name="hora_checkout" value="<?php echo esc_attr($data['hora_checkout'] ?: '11:00'); ?>" />
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label for="fianza_texto"><?php esc_html_e('Info Fianza', 'alquipress'); ?></label></th>
                    <td><input type="text" id="fianza_texto" name="fianza_texto" value="<?php echo esc_attr($data['fianza_texto']); ?>" style="width:300px" placeholder="<?php esc_attr_e('300€ mediante retención en tarjeta', 'alquipress'); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="codigo_caja_llaves"><?php esc_html_e('Código Caja de Llaves', 'alquipress'); ?></label></th>
                    <td><input type="text" id="codigo_caja_llaves" name="codigo_caja_llaves" value="<?php echo esc_attr($data['codigo_caja_llaves']); ?>" style="width:160px" /></td>
                </tr>
                <tr>
                    <th><label for="password_wifi"><?php esc_html_e('Contraseña WiFi', 'alquipress'); ?></label></th>
                    <td><input type="text" id="password_wifi" name="password_wifi" value="<?php echo esc_attr($data['password_wifi']); ?>" style="width:200px" /></td>
                </tr>
                <tr>
                    <th><label for="contacto_emergencia"><?php esc_html_e('Contacto Emergencia', 'alquipress'); ?></label></th>
                    <td><input type="text" id="contacto_emergencia" name="contacto_emergencia" value="<?php echo esc_attr($data['contacto_emergencia']); ?>" style="width:220px" /></td>
                </tr>
                <tr>
                    <th><label for="direccion_completa"><?php esc_html_e('Dirección Completa', 'alquipress'); ?></label></th>
                    <td><input type="text" id="direccion_completa" name="direccion_completa" value="<?php echo esc_attr($data['direccion_completa']); ?>" style="width:100%;max-width:400px" /></td>
                </tr>
                <tr>
                    <th><label for="instrucciones_checkin"><?php esc_html_e('Instrucciones Check-in', 'alquipress'); ?></label></th>
                    <td><textarea id="instrucciones_checkin" name="instrucciones_checkin" rows="4" style="width:100%;max-width:500px"><?php echo esc_textarea($data['instrucciones_checkin']); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="instrucciones_checkout"><?php esc_html_e('Instrucciones Check-out', 'alquipress'); ?></label></th>
                    <td><textarea id="instrucciones_checkout" name="instrucciones_checkout" rows="4" style="width:100%;max-width:500px"><?php echo esc_textarea($data['instrucciones_checkout']); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="normas_casa"><?php esc_html_e('Normas de la casa', 'alquipress'); ?></label></th>
                    <td><textarea id="normas_casa" name="normas_casa" rows="5" style="width:100%;max-width:500px"><?php echo esc_textarea($data['normas_casa']); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="propietario_asignado"><?php esc_html_e('Propietario Asignado', 'alquipress'); ?></label></th>
                    <td>
                        <select id="propietario_asignado" name="propietario_asignado">
                            <option value=""><?php esc_html_e('— Sin propietario —', 'alquipress'); ?></option>
                            <?php foreach ($owners as $owner) : ?>
                                <option value="<?php echo esc_attr($owner->ID); ?>" <?php selected($prop_id, $owner->ID); ?>>
                                    <?php echo esc_html($owner->post_title ?: '#' . $owner->ID); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Coordenadas GPS -->
            <h3><?php esc_html_e('Coordenadas GPS', 'alquipress'); ?></h3>
            <div class="ap-gps-row">
                <div>
                    <label for="gps_lat"><?php esc_html_e('Latitud', 'alquipress'); ?></label>
                    <input type="text" id="gps_lat" name="gps_lat" value="<?php echo esc_attr($data['gps_lat']); ?>" placeholder="38.8408" style="width:140px" />
                </div>
                <div>
                    <label for="gps_lng"><?php esc_html_e('Longitud', 'alquipress'); ?></label>
                    <input type="text" id="gps_lng" name="gps_lng" value="<?php echo esc_attr($data['gps_lng']); ?>" placeholder="0.1056" style="width:140px" />
                </div>
                <div style="flex:2">
                    <label for="gps_address"><?php esc_html_e('Dirección (para el mapa)', 'alquipress'); ?></label>
                    <input type="text" id="gps_address" name="gps_address" value="<?php echo esc_attr($data['gps_address']); ?>" style="width:100%" />
                </div>
            </div>

            <!-- Distribución de habitaciones -->
            <h3><?php esc_html_e('Distribución de Habitaciones', 'alquipress'); ?></h3>
            <div id="ap-habs-container">
                <?php foreach ($habs as $idx => $hab) : ?>
                    <?php self::render_hab_row($idx, $hab); ?>
                <?php endforeach; ?>
            </div>
            <button type="button" id="ap-add-hab" class="button"><?php esc_html_e('+ Añadir habitación', 'alquipress'); ?></button>

            <!-- Template habitaciones -->
            <script type="text/html" id="ap-hab-template">
                <?php self::render_hab_row('__IDX__', []); ?>
            </script>

            <!-- SEO -->
            <h3><?php esc_html_e('SEO', 'alquipress'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="seo_title_override"><?php esc_html_e('Título SEO', 'alquipress'); ?></label></th>
                    <td><input type="text" id="seo_title_override" name="seo_title_override" value="<?php echo esc_attr($data['seo_title_override']); ?>" maxlength="60" style="width:100%;max-width:400px" /></td>
                </tr>
                <tr>
                    <th><label for="seo_description_override"><?php esc_html_e('Meta Description', 'alquipress'); ?></label></th>
                    <td><textarea id="seo_description_override" name="seo_description_override" rows="3" maxlength="160" style="width:100%;max-width:400px"><?php echo esc_textarea($data['seo_description_override']); ?></textarea></td>
                </tr>
            </table>
        </div>

        <script>
        (function() {
            var habIdx = <?php echo count($habs); ?>;
            document.getElementById('ap-add-hab').addEventListener('click', function() {
                var tpl = document.getElementById('ap-hab-template').innerHTML;
                tpl = tpl.replace(/__IDX__/g, habIdx);
                var div = document.createElement('div');
                div.innerHTML = tpl;
                document.getElementById('ap-habs-container').appendChild(div.firstElementChild);
                habIdx++;
            });
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('ap-remove-hab')) {
                    e.target.closest('.ap-hab-row').remove();
                }
            });
        })();
        </script>
        <?php
    }

    /**
     * Renderizar fila del repeater de habitaciones.
     */
    private static function render_hab_row(int|string $idx, array $hab): void
    {
        $nombre = $hab['nombre_hab']  ?? '';
        $cama   = $hab['tipo_cama']   ?? 'matrimonio';
        $suite  = !empty($hab['bano_en_suite']);
        ?>
        <div class="ap-hab-row">
            <div>
                <label><?php esc_html_e('Nombre', 'alquipress'); ?></label>
                <input type="text" name="distribucion_habitaciones[<?php echo esc_attr($idx); ?>][nombre_hab]" value="<?php echo esc_attr($nombre); ?>" placeholder="<?php esc_attr_e('Habitación principal', 'alquipress'); ?>" />
            </div>
            <div>
                <label><?php esc_html_e('Tipo de Cama', 'alquipress'); ?></label>
                <select name="distribucion_habitaciones[<?php echo esc_attr($idx); ?>][tipo_cama]">
                    <option value="matrimonio" <?php selected($cama, 'matrimonio'); ?>><?php esc_html_e('Cama Matrimonio', 'alquipress'); ?></option>
                    <option value="individual"  <?php selected($cama, 'individual');  ?>><?php esc_html_e('Camas Individuales', 'alquipress'); ?></option>
                    <option value="litera"      <?php selected($cama, 'litera');      ?>>Litera</option>
                    <option value="sofa"        <?php selected($cama, 'sofa');        ?>><?php esc_html_e('Sofá Cama', 'alquipress'); ?></option>
                </select>
            </div>
            <div>
                <label><?php esc_html_e('Baño en Suite', 'alquipress'); ?></label>
                <input type="checkbox" name="distribucion_habitaciones[<?php echo esc_attr($idx); ?>][bano_en_suite]" value="1" <?php checked($suite); ?> />
            </div>
            <div>
                <button type="button" class="ap-remove-hab" title="<?php esc_attr_e('Eliminar', 'alquipress'); ?>">✕</button>
            </div>
        </div>
        <?php
    }

    // ── Guardado ─────────────────────────────────────────────────────────────

    public function save(int $post_id, \WP_Post $post): void
    {
        if (!isset($_POST['alquipress_product_fields_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alquipress_product_fields_nonce'])), 'alquipress_product_fields_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Campos escalares
        foreach (self::$scalar_fields as $key => $sanitizer) {
            if (isset($_POST[$key])) {
                $val = wp_unslash($_POST[$key]);
                update_post_meta($post_id, $key, $sanitizer($val));
            }
        }

        // Campos textarea (kses básico)
        foreach (self::$textarea_fields as $key) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $key, wp_kses_post(wp_unslash($_POST[$key])));
            }
        }

        // Propietario asignado (relationship max:1 → guardar como array para compatibilidad ACF)
        if (isset($_POST['propietario_asignado'])) {
            $owner_id = absint($_POST['propietario_asignado']);
            update_post_meta($post_id, 'propietario_asignado', $owner_id ? [$owner_id] : []);
        }

        // Coordenadas GPS
        $lat  = isset($_POST['gps_lat'])     ? (float) $_POST['gps_lat']                                                : null;
        $lng  = isset($_POST['gps_lng'])     ? (float) $_POST['gps_lng']                                                : null;
        $addr = isset($_POST['gps_address']) ? sanitize_text_field(wp_unslash($_POST['gps_address']))                   : '';

        if ($lat !== null || $lng !== null || $addr !== '') {
            update_post_meta($post_id, 'coordenadas_gps', [
                'lat'     => $lat,
                'lng'     => $lng,
                'address' => $addr,
                // Alias usados en algunos bloques
                'latitude'  => $lat,
                'longitude' => $lng,
            ]);
        }

        // Distribución de habitaciones (repeater)
        $habs_raw = isset($_POST['distribucion_habitaciones']) && is_array($_POST['distribucion_habitaciones'])
            ? $_POST['distribucion_habitaciones']
            : [];

        $habs = [];
        foreach ($habs_raw as $hab) {
            if (!is_array($hab)) {
                continue;
            }
            $nombre = sanitize_text_field(wp_unslash($hab['nombre_hab'] ?? ''));
            if ($nombre === '') {
                continue;
            }
            $allowed_cama = ['matrimonio', 'individual', 'litera', 'sofa'];
            $cama = sanitize_text_field(wp_unslash($hab['tipo_cama'] ?? 'matrimonio'));
            if (!in_array($cama, $allowed_cama, true)) {
                $cama = 'matrimonio';
            }
            $habs[] = [
                'nombre_hab'    => $nombre,
                'tipo_cama'     => $cama,
                'bano_en_suite' => !empty($hab['bano_en_suite']) ? 1 : 0,
            ];
        }
        update_post_meta($post_id, 'distribucion_habitaciones', $habs);

        // Sincronizar el campo escalar numero_habitaciones con el número real de filas.
        update_post_meta($post_id, 'numero_habitaciones', count($habs));
    }
}
