<?php
/**
 * ================================================
 * GENERADOR DE FEED XML KYERO
 * ================================================
 */

class Alquipress_Kyero_Feed
{

    private $xml;
    private $root_node;

    public function __construct()
    {
        $this->xml = new DOMDocument('1.0', 'UTF-8');
        $this->xml->formatOutput = true;

        // Crear nodo raíz <root>
        $this->root_node = $this->xml->createElement('root');
        $this->xml->appendChild($this->root_node);

        // Nodo <kyero>
        $kyero = $this->xml->createElement('kyero');
        $this->root_node->appendChild($kyero);

        // Versión del feed
        $version = $this->xml->createElement('feed_version', '3');
        $kyero->appendChild($version);

        // Fecha de generación del feed
        $generated = $this->xml->createElement('feed_generated', date('Y-m-d H:i:s'));
        $kyero->appendChild($generated);

        $this->add_agent_node();
    }

    /**
     * Obtener propiedades marcadas para exportar
     */
    public function get_exportable_properties()
    {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'kyero_export',
                    'field' => 'slug',
                    'terms' => 'exportar',
                )
            )
        );

        return get_posts($args);
    }

    /**
     * Mapear tipo de propiedad WooCommerce → Kyero
     */
    private function get_property_type($product_id)
    {
        $preferred = apply_filters('alquipress_tipo_vivienda_list', [
            'Villa',
            'Apartamento',
            'Ático',
            'Casa de Pueblo',
            'Bungalow',
            'Chalet'
        ]);

        $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term_name) {
                if (in_array($term_name, $preferred, true)) {
                    return $this->normalize_kyero_type($term_name);
                }
            }
        }

        // Fallback: taxonomía legacy si existiera
        $legacy_terms = wp_get_post_terms($product_id, 'tipo_propiedad', ['fields' => 'names']);
        if (!is_wp_error($legacy_terms) && !empty($legacy_terms)) {
            return $this->normalize_kyero_type($legacy_terms[0]);
        }

        return 'House';
    }

    /**
     * Contar dormitorios desde el repeater ACF
     */
    private function count_bedrooms($product_id)
    {
        $habitaciones = get_field('distribucion_habitaciones', $product_id);
        return $habitaciones ? count($habitaciones) : 0;
    }

    /**
     * Contar baños (puedes añadir este campo a ACF)
     */
    private function count_bathrooms($product_id)
    {
        // Si no tienes este campo, añádelo a ACF
        $banos = get_field('numero_banos', $product_id);
        return $banos ?: 1;
    }

    /**
     * Obtener características en inglés (Kyero las prefiere en inglés)
     */
    private function get_features_in_english($product_id)
    {
        $caracteristicas = wp_get_post_terms($product_id, 'caracteristicas', ['fields' => 'names']);

        $translation_map = [
            'Piscina Privada' => 'Swimming Pool',
            'WiFi Fibra' => 'WiFi',
            'Aire Acondicionado' => 'Air Conditioning',
            'Parking Privado' => 'Parking',
            'Vistas al Mar' => 'Sea Views',
            'Jardín' => 'Garden',
            'Barbacoa' => 'BBQ',
            'Terraza' => 'Terrace',
            'Admite Mascotas' => 'Pets Allowed',
            'Calefacción' => 'Heating',
            'Chimenea' => 'Fireplace',
            'Lavadora' => 'Washing Machine',
            'Lavavajillas' => 'Dishwasher',
            'Smart TV' => 'TV',
        ];

        $features = [];
        foreach ($caracteristicas as $caract) {
            $features[] = $translation_map[$caract] ?? $caract;
        }

        return $features;
    }

    /**
     * Añadir una propiedad al XML
     */
    public function add_property($post)
    {
        $product = wc_get_product($post->ID);
        if (!$product)
            return;

        // Nodo <property>
        $property_node = $this->xml->createElement('property');

        // ID único
        $id_val = substr(sanitize_title($post->post_name), 0, 50);
        $id = $this->xml->createElement('id', $id_val);
        $property_node->appendChild($id);

        // Fecha de última modificación
        $date = $this->xml->createElement('date', get_the_modified_date('Y-m-d H:i:s', $post->ID));
        $property_node->appendChild($date);

        // Referencia interna
        $ref_val = get_field('referencia_interna', $post->ID) ?: $post->ID;
        $ref = $this->xml->createElement('ref', $ref_val);
        $property_node->appendChild($ref);

        // Tipo de propiedad
        $type = $this->xml->createElement('type', $this->get_property_type($post->ID));
        $property_node->appendChild($type);

        // Ubicación
        $poblacion_terms = wp_get_post_terms($post->ID, 'poblacion', ['fields' => 'names']);
        $poblacion = !empty($poblacion_terms) ? $poblacion_terms[0] : 'Unknown';

        $town = $this->xml->createElement('town', $poblacion);
        $property_node->appendChild($town);

        $province = $this->xml->createElement('province', 'Alicante'); // O dinámico
        $property_node->appendChild($province);

        $country = $this->xml->createElement('country', 'Spain');
        $property_node->appendChild($country);

        // Coordenadas GPS
        $coordenadas = get_field('coordenadas_gps', $post->ID);
        if ($coordenadas) {
            $location = $this->xml->createElement('location');
            $lng = $this->xml->createElement('longitude', $coordenadas['lng']);
            $lat = $this->xml->createElement('latitude', $coordenadas['lat']);
            $location->appendChild($lng);
            $location->appendChild($lat);
            $property_node->appendChild($location);
        }

        // Precio (desde WooCommerce)
        $price_val = $product->get_regular_price();
        if ($price_val === '' || $price_val === null) {
            $price_val = 'x';
        } else {
            $price_val = (string) (int) round((float) $price_val);
        }
        $price = $this->xml->createElement('price', $price_val);
        $property_node->appendChild($price);

        $currency = $this->xml->createElement('currency', 'EUR');
        $property_node->appendChild($currency);

        // Frecuencia de precio (Kyero: sale/week/month)
        $price_freq = $this->xml->createElement('price_freq', $this->get_price_freq($post->ID, $product));
        $property_node->appendChild($price_freq);

        // Dormitorios y baños
        $beds = $this->xml->createElement('beds', $this->count_bedrooms($post->ID));
        $property_node->appendChild($beds);

        $baths = $this->xml->createElement('baths', $this->count_bathrooms($post->ID));
        $property_node->appendChild($baths);

        // Superficie
        $superficie = get_field('superficie_m2', $post->ID);
        if ($superficie) {
            $surface_node = $this->xml->createElement('surface_area');
            $built = $this->xml->createElement('built', $superficie);
            $surface_node->appendChild($built);
            $property_node->appendChild($surface_node);
        }

        // Descripciones (Inglés y Español)
        $this->add_descriptions($property_node, $post);

        // Imágenes
        $this->add_images($property_node, $product);

        // URL multi-idioma
        $this->add_urls($property_node, $post);

        // Características
        $this->add_features($property_node, $post->ID);

        // Energía (Optional defaults)
        $energy_node = $this->xml->createElement('energy_rating');
        $consumption = $this->xml->createElement('consumption', 'X'); // Default/Unknown
        $emissions = $this->xml->createElement('emissions', 'X'); // Default/Unknown
        $energy_node->appendChild($consumption);
        $energy_node->appendChild($emissions);
        $property_node->appendChild($energy_node);

        // Añadir al feed
        $this->root_node->appendChild($property_node);
    }

    /**
     * Añadir descripciones multiidioma
     */
    private function add_descriptions($property_node, $post)
    {
        $desc = $this->xml->createElement('desc');

        $text = trim(wp_strip_all_tags($post->post_content));
        if ($text === '') {
            $text = get_the_title($post->ID);
        }

        $en = $this->xml->createElement('en', $text);
        $es = $this->xml->createElement('es', $text);

        $desc->appendChild($en);
        $desc->appendChild($es);

        $property_node->appendChild($desc);
    }

    /**
     * Añadir imágenes
     */
    private function add_images($property_node, $product)
    {
        $images_node = $this->xml->createElement('images');

        // Imagen destacada
        $featured_id = $product->get_image_id();
        if ($featured_id) {
            $image_node = $this->xml->createElement('image');
            $image_node->setAttribute('id', '1');
            $url = $this->get_attachment_original_url($featured_id);
            if (!$this->is_valid_image_url($url)) {
                $url = '';
            }
            if ($url !== '') {
                $url_node = $this->xml->createElement('url', $url);
                $image_node->appendChild($url_node);
                $images_node->appendChild($image_node);
            }
        }

        // Galería
        $gallery_ids = $product->get_gallery_image_ids();
        $counter = 2;
        foreach ($gallery_ids as $img_id) {
            $image_node = $this->xml->createElement('image');
            $image_node->setAttribute('id', (string) $counter);
            $url = $this->get_attachment_original_url($img_id);
            if (!$this->is_valid_image_url($url)) {
                $counter++;
                continue;
            }
            $url_node = $this->xml->createElement('url', $url);
            $image_node->appendChild($url_node);
            $images_node->appendChild($image_node);
            $counter++;
        }

        $property_node->appendChild($images_node);
    }

    /**
     * URLs multi-idioma
     */
    private function add_urls($property_node, $post)
    {
        $url_node = $this->xml->createElement('url');
        $permalink = get_permalink($post->ID);
        $url_node->appendChild($this->xml->createElement('en', $permalink));
        $url_node->appendChild($this->xml->createElement('es', $permalink));
        $property_node->appendChild($url_node);
    }

    private function get_price_freq($post_id, $product)
    {
        $allowed = ['sale', 'week', 'month'];
        $freq = '';

        if (function_exists('get_field')) {
            $freq = get_field('kyero_price_freq', $post_id);
        }
        if (!$freq) {
            $freq = get_post_meta($post_id, 'kyero_price_freq', true);
        }
        if (!$freq) {
            $freq = get_post_meta($post_id, 'price_freq', true);
        }
        if (!$freq) {
            $freq = get_post_meta($post_id, '_kyero_price_freq', true);
        }

        $freq = strtolower(trim((string) $freq));
        if ($freq && in_array($freq, $allowed, true)) {
            return $freq;
        }

        $default = apply_filters('alquipress_kyero_price_freq', 'week', $post_id, $product);
        $default = strtolower(trim((string) $default));
        if (in_array($default, $allowed, true)) {
            return $default;
        }

        return 'week';
    }

    private function normalize_kyero_type($type)
    {
        $type = remove_accents((string) $type);
        $type = preg_replace('/[^a-zA-Z&\\s()\\/-]/', '', $type);
        $type = trim($type);

        if ($type === '') {
            return 'House';
        }

        return $type;
    }

    /**
     * Obtener URL original del adjunto (evita WebP)
     */
    private function get_attachment_original_url($attachment_id)
    {
        $meta = wp_get_attachment_metadata($attachment_id);
        $upload_dir = wp_upload_dir();
        if (!empty($meta['file']) && !empty($upload_dir['baseurl'])) {
            return trailingslashit($upload_dir['baseurl']) . $meta['file'];
        }

        return wp_get_attachment_url($attachment_id);
    }

    private function is_valid_image_url($url)
    {
        if (!$url) {
            return false;
        }

        $url = strtolower((string) $url);
        return (bool) preg_match('/\\.(gif|jpe?g|png)$/', $url);
    }

    private function add_agent_node()
    {
        $agent = apply_filters('alquipress_kyero_agent', [
            'id' => '',
            'name' => get_bloginfo('name'),
            'email' => get_bloginfo('admin_email'),
            'tel' => '',
            'fax' => '',
            'mob' => '',
            'addr1' => '',
            'addr2' => '',
            'town' => '',
            'region' => '',
            'postcode' => '',
            'country' => ''
        ]);

        if (!is_array($agent)) {
            return;
        }

        $agent = array_filter($agent, function ($value) {
            return $value !== null && $value !== '';
        });

        if (empty($agent)) {
            return;
        }

        $agent_node = $this->xml->createElement('agent');
        foreach ($agent as $key => $value) {
            if ($key === 'id' && !is_numeric($value)) {
                continue;
            }
            $agent_node->appendChild($this->xml->createElement($key, $value));
        }

        $this->root_node->appendChild($agent_node);
    }

    /**
     * Añadir características
     */
    private function add_features($property_node, $product_id)
    {
        $features = $this->get_features_in_english($product_id);

        if (empty($features)) {
            return;
        }

        $features_node = $this->xml->createElement('features');
        foreach ($features as $feature_name) {
            $feature = $this->xml->createElement('feature', $feature_name);
            $features_node->appendChild($feature);
        }

        $property_node->appendChild($features_node);
    }

    /**
     * Generar el XML
     */
    public function generate()
    {
        $properties = $this->get_exportable_properties();

        foreach ($properties as $property) {
            $this->add_property($property);
        }

        return $this->xml->saveXML();
    }

    /**
     * Guardar en archivo
     */
    public function save_to_file()
    {
        $xml_content = $this->generate();
        $upload_dir = wp_upload_dir();

        // Verificar errores en upload_dir
        if (!empty($upload_dir['error'])) {
            error_log('ALQUIPRESS Kyero: Error en wp_upload_dir - ' . $upload_dir['error']);
            return false;
        }

        $file_path = $upload_dir['basedir'] . '/kyero-feed.xml';

        // Verificar que el directorio existe
        if (!file_exists($upload_dir['basedir'])) {
            if (!wp_mkdir_p($upload_dir['basedir'])) {
                error_log('ALQUIPRESS Kyero: No se pudo crear directorio - ' . $upload_dir['basedir']);
                return false;
            }
        }

        // Verificar permisos de escritura
        if (file_exists($file_path) && !is_writable($file_path)) {
            error_log('ALQUIPRESS Kyero: Archivo no escribible - ' . $file_path);
            return false;
        }

        if (!is_writable($upload_dir['basedir'])) {
            error_log('ALQUIPRESS Kyero: Directorio no escribible - ' . $upload_dir['basedir']);
            return false;
        }

        // Escribir archivo con error handling
        $result = file_put_contents($file_path, $xml_content);

        if ($result === false) {
            error_log('ALQUIPRESS Kyero: No se pudo escribir archivo - ' . $file_path);
            return false;
        }

        return $upload_dir['baseurl'] . '/kyero-feed.xml';
    }
}
