<?php
/**
 * Meta box nativo para el CPT "propietario".
 * Reemplaza el grupo de campos ACF "group_crm_propietario".
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Owner_Metabox
{
    /** Meta fields simples del propietario. */
    private static array $simple_fields = [
        'owner_phone',
        'owner_email_management',
        'owner_whatsapp',
        'owner_commission_rate',
        'owner_iban',
        'owner_cloud_folder',
        'owner_contract_link',
        'owner_contract_pdf',
        'owner_contract_expiry',
        'owner_drive_folder_url',
    ];

    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post_propietario', [$this, 'save'], 10, 2);
    }

    public function register_meta_box(): void
    {
        add_meta_box(
            'alquipress_owner_crm',
            __('CRM — Gestión Propietario', 'alquipress'),
            [$this, 'render'],
            'propietario',
            'normal',
            'high'
        );
    }

    public function render(\WP_Post $post): void
    {
        wp_nonce_field('alquipress_owner_metabox_save', 'alquipress_owner_nonce');

        $phone      = (string) get_post_meta($post->ID, 'owner_phone', true);
        $email      = (string) get_post_meta($post->ID, 'owner_email_management', true);
        $whatsapp   = (string) get_post_meta($post->ID, 'owner_whatsapp', true);
        $commission = (string) get_post_meta($post->ID, 'owner_commission_rate', true);
        $iban       = (string) get_post_meta($post->ID, 'owner_iban', true);
        $cloud      = (string) get_post_meta($post->ID, 'owner_cloud_folder', true);
        $contract   = (string) get_post_meta($post->ID, 'owner_contract_link', true);
        $contract_pdf    = (string) get_post_meta($post->ID, 'owner_contract_pdf', true);
        $contract_expiry = (string) get_post_meta($post->ID, 'owner_contract_expiry', true);
        $drive_url       = (string) get_post_meta($post->ID, 'owner_drive_folder_url', true);

        // Propiedades asignadas (array de IDs)
        $raw_props  = get_post_meta($post->ID, 'owner_properties', true);
        $prop_ids   = is_array($raw_props) ? array_map('intval', $raw_props) : [];

        // Obtener todos los productos disponibles para el selector
        $products = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        ?>
        <style>
            .ap-owner-metabox { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
            .ap-owner-metabox .ap-tab-nav { display: flex; gap: 4px; border-bottom: 2px solid #e5e7eb; margin-bottom: 20px; }
            .ap-owner-metabox .ap-tab-btn { padding: 8px 16px; border: none; background: none; cursor: pointer; font-size: 13px; font-weight: 500; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -2px; }
            .ap-owner-metabox .ap-tab-btn.active { color: #2563eb; border-bottom-color: #2563eb; }
            .ap-owner-metabox .ap-tab-panel { display: none; }
            .ap-owner-metabox .ap-tab-panel.active { display: block; }
            .ap-owner-metabox .ap-field-row { margin-bottom: 16px; }
            .ap-owner-metabox .ap-field-row label { display: block; font-weight: 600; font-size: 12px; color: #374151; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
            .ap-owner-metabox .ap-field-row input[type="text"],
            .ap-owner-metabox .ap-field-row input[type="email"],
            .ap-owner-metabox .ap-field-row input[type="url"],
            .ap-owner-metabox .ap-field-row input[type="number"],
            .ap-owner-metabox .ap-field-row input[type="date"] { width: 100%; max-width: 400px; }
            .ap-owner-metabox .ap-props-select { width: 100%; min-height: 120px; max-width: 500px; }
            .ap-owner-metabox .ap-field-hint { font-size: 11px; color: #9ca3af; margin-top: 3px; }
        </style>

        <div class="ap-owner-metabox">
            <nav class="ap-tab-nav">
                <button type="button" class="ap-tab-btn active" data-tab="contacto"><?php esc_html_e('Contacto', 'alquipress'); ?></button>
                <button type="button" class="ap-tab-btn" data-tab="financiero"><?php esc_html_e('Financiero', 'alquipress'); ?></button>
                <button type="button" class="ap-tab-btn" data-tab="docs"><?php esc_html_e('Documentación', 'alquipress'); ?></button>
                <button type="button" class="ap-tab-btn" data-tab="propiedades"><?php esc_html_e('Propiedades', 'alquipress'); ?></button>
            </nav>

            <!-- Contacto -->
            <div class="ap-tab-panel active" data-panel="contacto">
                <div class="ap-field-row">
                    <label for="owner_phone"><?php esc_html_e('Teléfono Móvil', 'alquipress'); ?></label>
                    <input type="text" id="owner_phone" name="owner_phone" value="<?php echo esc_attr($phone); ?>" placeholder="+34 600 000 000" />
                </div>
                <div class="ap-field-row">
                    <label for="owner_email_management"><?php esc_html_e('Email Gestión', 'alquipress'); ?></label>
                    <input type="email" id="owner_email_management" name="owner_email_management" value="<?php echo esc_attr($email); ?>" />
                </div>
                <div class="ap-field-row">
                    <label for="owner_whatsapp"><?php esc_html_e('Enlace WhatsApp Directo', 'alquipress'); ?></label>
                    <input type="url" id="owner_whatsapp" name="owner_whatsapp" value="<?php echo esc_attr($whatsapp); ?>" placeholder="https://wa.me/34600000000" />
                    <p class="ap-field-hint"><?php esc_html_e('Formato: https://wa.me/34600000000', 'alquipress'); ?></p>
                </div>
            </div>

            <!-- Financiero -->
            <div class="ap-tab-panel" data-panel="financiero">
                <div class="ap-field-row">
                    <label for="owner_commission_rate"><?php esc_html_e('% Comisión Agencia', 'alquipress'); ?></label>
                    <input type="number" id="owner_commission_rate" name="owner_commission_rate" value="<?php echo esc_attr($commission); ?>" min="0" max="100" step="0.01" />
                    <p class="ap-field-hint">%</p>
                </div>
                <div class="ap-field-row">
                    <label for="owner_iban"><?php esc_html_e('IBAN', 'alquipress'); ?></label>
                    <input type="text" id="owner_iban" name="owner_iban" value="<?php echo esc_attr($iban); ?>" class="owner-iban-field" autocomplete="off" />
                </div>
            </div>

            <!-- Documentación -->
            <div class="ap-tab-panel" data-panel="docs">
                <div class="ap-field-row">
                    <label for="owner_cloud_folder"><?php esc_html_e('📂 Carpeta Drive/Dropbox', 'alquipress'); ?></label>
                    <input type="url" id="owner_cloud_folder" name="owner_cloud_folder" value="<?php echo esc_attr($cloud); ?>" />
                    <p class="ap-field-hint"><?php esc_html_e('Enlace a la carpeta compartida con toda la documentación.', 'alquipress'); ?></p>
                </div>
                <div class="ap-field-row">
                    <label for="owner_contract_link"><?php esc_html_e('📄 Enlace Contrato PDF', 'alquipress'); ?></label>
                    <input type="url" id="owner_contract_link" name="owner_contract_link" value="<?php echo esc_attr($contract); ?>" />
                </div>
                <div class="ap-field-row">
                    <label for="owner_contract_pdf"><?php esc_html_e('Contrato PDF (URL directa)', 'alquipress'); ?></label>
                    <input type="url" id="owner_contract_pdf" name="owner_contract_pdf" value="<?php echo esc_attr($contract_pdf); ?>" />
                </div>
                <div class="ap-field-row">
                    <label for="owner_contract_expiry"><?php esc_html_e('Vencimiento del Contrato', 'alquipress'); ?></label>
                    <input type="date" id="owner_contract_expiry" name="owner_contract_expiry" value="<?php echo esc_attr($contract_expiry); ?>" />
                </div>
                <div class="ap-field-row">
                    <label for="owner_drive_folder_url"><?php esc_html_e('URL Carpeta Drive del Propietario', 'alquipress'); ?></label>
                    <input type="url" id="owner_drive_folder_url" name="owner_drive_folder_url" value="<?php echo esc_attr($drive_url); ?>" />
                </div>
            </div>

            <!-- Propiedades -->
            <div class="ap-tab-panel" data-panel="propiedades">
                <div class="ap-field-row">
                    <label for="owner_properties"><?php esc_html_e('Propiedades Asignadas', 'alquipress'); ?></label>
                    <select id="owner_properties" name="owner_properties[]" multiple class="ap-props-select">
                        <?php foreach ($products as $product) : ?>
                            <option value="<?php echo esc_attr($product->ID); ?>"
                                <?php echo in_array($product->ID, $prop_ids, true) ? 'selected' : ''; ?>>
                                <?php echo esc_html($product->post_title ?: '(sin título) #' . $product->ID); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="ap-field-hint"><?php esc_html_e('Mantén Ctrl / Cmd para seleccionar varias.', 'alquipress'); ?></p>
                </div>
            </div>
        </div>

        <script>
        (function() {
            var tabs = document.querySelectorAll('.ap-owner-metabox .ap-tab-btn');
            var panels = document.querySelectorAll('.ap-owner-metabox .ap-tab-panel');
            tabs.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var target = this.dataset.tab;
                    tabs.forEach(function(b) { b.classList.remove('active'); });
                    panels.forEach(function(p) { p.classList.remove('active'); });
                    this.classList.add('active');
                    document.querySelector('.ap-owner-metabox [data-panel="' + target + '"]').classList.add('active');
                });
            });
        })();
        </script>
        <?php
    }

    public function save(int $post_id, \WP_Post $post): void
    {
        if (!isset($_POST['alquipress_owner_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alquipress_owner_nonce'])), 'alquipress_owner_metabox_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Campos simples
        $sanitize_map = [
            'owner_phone'            => 'sanitize_text_field',
            'owner_email_management' => 'sanitize_email',
            'owner_whatsapp'         => 'esc_url_raw',
            'owner_commission_rate'  => 'floatval',
            'owner_iban'             => 'sanitize_text_field',
            'owner_cloud_folder'     => 'esc_url_raw',
            'owner_contract_link'    => 'esc_url_raw',
            'owner_contract_pdf'     => 'esc_url_raw',
            'owner_contract_expiry'  => 'sanitize_text_field',
            'owner_drive_folder_url' => 'esc_url_raw',
        ];

        foreach ($sanitize_map as $key => $sanitizer) {
            if (isset($_POST[$key])) {
                $val = wp_unslash($_POST[$key]);
                update_post_meta($post_id, $key, $sanitizer($val));
            }
        }

        // Propiedades asignadas (array de IDs)
        $props = isset($_POST['owner_properties']) && is_array($_POST['owner_properties'])
            ? array_map('absint', $_POST['owner_properties'])
            : [];
        update_post_meta($post_id, 'owner_properties', $props);
    }
}
