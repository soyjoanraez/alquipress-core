<?php
/**
 * ================================================
 * GENERADOR DE FEED XML KYERO
 * ================================================
 */

class Alquipress_Kyero_Feed
{

    private $xml;
    private $properties_node;

    public function __construct()
    {
        $this->xml = new DOMDocument('1.0', 'UTF-8');
        $this->xml->formatOutput = true;

        // Crear nodo raíz
        $kyero = $this->xml->createElement('kyero');
        $this->xml->appendChild($kyero);

        // Versión del feed
        $version = $this->xml->createElement('feed_version', '3');
        $kyero->appendChild($version);

        // Contenedor de propiedades
        $this->properties_node = $this->xml->createElement('properties');
        $kyero->appendChild($this->properties_node);
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
        // Aquí puedes usar una taxonomía custom o un campo ACF
        $type_mapping = [
            'villa' => 'Villa',
            'apartamento' => 'Apartment',
            'casa' => 'House',
            'atico' => 'Penthouse',
            'chalet' => 'Chalet'
        ];

        // Ejemplo: desde una taxonomía 'tipo_propiedad'
        $terms = wp_get_post_terms($product_id, 'tipo_propiedad', ['fields' => 'slugs']);
        $slug = !empty($terms) ? $terms[0] : 'villa';

        return $type_mapping[$slug] ?? 'House';
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
        $id = $this->xml->createElement('id', sanitize_title($post->post_name));
        $property_node->appendChild($id);

        // Fecha de última modificación
        $date = $this->xml->createElement('date', get_the_modified_date('Y-m-d', $post->ID));
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
            $lat = $this->xml->createElement('latitude', $coordenadas['lat']);
            $property_node->appendChild($lat);

            $lng = $this->xml->createElement('longitude', $coordenadas['lng']);
            $property_node->appendChild($lng);
        }

        // Precio (desde WooCommerce)
        $price_val = $product->get_regular_price();
        $price = $this->xml->createElement('price', $price_val);
        $property_node->appendChild($price);

        $currency = $this->xml->createElement('currency', 'EUR');
        $property_node->appendChild($currency);

        // Frecuencia de alquiler
        $frequency = $this->xml->createElement('frequency', 'weekly');
        $property_node->appendChild($frequency);

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

        // URL de la propiedad
        $url = $this->xml->createElement('url', get_permalink($post->ID));
        $property_node->appendChild($url);

        // Características
        $this->add_features($property_node, $post->ID);

        // Disponibilidad (desde Bookings)
        $this->add_availability($property_node, $product);

        // Energía (Optional defaults)
        $energy_node = $this->xml->createElement('energy_rating');
        $consumption = $this->xml->createElement('consumption', 'X'); // Default/Unknown
        $emissions = $this->xml->createElement('emissions', 'X'); // Default/Unknown
        $energy_node->appendChild($consumption);
        $energy_node->appendChild($emissions);
        $property_node->appendChild($energy_node);

        // Añadir al feed
        $this->properties_node->appendChild($property_node);
    }

    /**
     * Añadir descripciones multiidioma
     */
    private function add_descriptions($property_node, $post)
    {
        // Descripción en inglés
        $desc_en = $this->xml->createElement('desc');
        $lang_en = $this->xml->createElement('language', 'en');
        $desc_en->appendChild($lang_en);

        $title_en = $this->xml->createElement('title', get_the_title($post->ID));
        $desc_en->appendChild($title_en);

        $description_en = $this->xml->createElement('description');
        $description_en->appendChild($this->xml->createCDATASection(
            wp_strip_all_tags($post->post_content)
        ));
        $desc_en->appendChild($description_en);

        $property_node->appendChild($desc_en);

        // Descripción en español (duplicar si no tienes traducción)
        $desc_es = $this->xml->createElement('desc');
        $lang_es = $this->xml->createElement('language', 'es');
        $desc_es->appendChild($lang_es);

        $title_es = $this->xml->createElement('title', get_the_title($post->ID));
        $desc_es->appendChild($title_es);

        $description_es = $this->xml->createElement('description');
        $description_es->appendChild($this->xml->createCDATASection(
            wp_strip_all_tags($post->post_content)
        ));
        $desc_es->appendChild($description_es);

        $property_node->appendChild($desc_es);
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
            $url = wp_get_attachment_image_url($featured_id, 'full');
            $url_node = $this->xml->createElement('url', $url);
            $image_node->appendChild($url_node);
            $id_node = $this->xml->createElement('id', '1');
            $image_node->appendChild($id_node);
            $images_node->appendChild($image_node);
        }

        // Galería
        $gallery_ids = $product->get_gallery_image_ids();
        $counter = 2;
        foreach ($gallery_ids as $img_id) {
            $image_node = $this->xml->createElement('image');
            $url = wp_get_attachment_image_url($img_id, 'full');
            $url_node = $this->xml->createElement('url', $url);
            $image_node->appendChild($url_node);
            $id_node = $this->xml->createElement('id', $counter);
            $image_node->appendChild($id_node);
            $images_node->appendChild($image_node);
            $counter++;
        }

        $property_node->appendChild($images_node);
    }

    /**
     * Añadir características
     */
    private function add_features($property_node, $product_id)
    {
        $features_node = $this->xml->createElement('features');
        $features = $this->get_features_in_english($product_id);

        foreach ($features as $feature_name) {
            $feature = $this->xml->createElement('feature', $feature_name);
            $features_node->appendChild($feature);
        }

        $property_node->appendChild($features_node);
    }

    /**
     * Añadir disponibilidad (integrado con Bookings)
     */
    private function add_availability($property_node, $product)
    {
        $availability_node = $this->xml->createElement('availability');

        // Disponible desde hoy
        $available_from = $this->xml->createElement('available_from', date('Y-m-d'));
        $availability_node->appendChild($available_from);

        // Disponible hasta fin de año (o el rango que tengas)
        $available_to = $this->xml->createElement('available_to', date('Y') . '-12-31');
        $availability_node->appendChild($available_to);

        $property_node->appendChild($availability_node);
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
        $file_path = $upload_dir['basedir'] . '/kyero-feed.xml';

        file_put_contents($file_path, $xml_content);

        return $upload_dir['baseurl'] . '/kyero-feed.xml';
    }
}
