<?php
/**
 * ================================================
 * IMPORTADOR XML KYERO
 * ================================================
 */

class Alquipress_Kyero_Importer
{

    private $xml_url;

    public function __construct($xml_url)
    {
        $this->xml_url = $xml_url;
    }

    /**
     * Descargar y parsear XML
     */
    public function fetch_xml()
    {
        $sslverify = (bool) apply_filters('alquipress_kyero_sslverify', true);
        $response = wp_remote_get($this->xml_url, [
            'timeout' => 30,
            'sslverify' => $sslverify
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $xml_content = wp_remote_retrieve_body($response);

        // Parsear XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);

        if ($xml === false) {
            $errors = libxml_get_errors();
            error_log('Kyero XML Error: ' . print_r($errors, true));
            return false;
        }

        return $xml;
    }

    /**
     * Importar propiedades
     */
    public function import_properties()
    {
        $xml = $this->fetch_xml();

        if (!$xml) {
            return ['success' => false, 'message' => 'XML inválido'];
        }

        if (isset($xml->property)) {
            $properties = $xml->property;
        } elseif (isset($xml->properties->property)) {
            $properties = $xml->properties->property;
        } else {
            return ['success' => false, 'message' => 'XML inválido'];
        }

        $imported = 0;
        $updated = 0;
        $errors = 0;

        foreach ($properties as $property) {
            $result = $this->import_single_property($property);

            if ($result === 'new') {
                $imported++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $errors++;
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        ];
    }

    /**
     * Importar una propiedad individual
     */
    private function import_single_property($property)
    {
        $kyero_id = (string) $property->id;

        // Buscar si ya existe por meta key
        $existing = get_posts([
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_kyero_id',
                    'value' => $kyero_id
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);

        $post_id = !empty($existing) ? $existing[0]->ID : 0;

        // Preparar datos del producto
        $title = $this->extract_title($property);
        $description = $this->extract_description($property);

        $post_data = [
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => 'product',
        ];

        if ($post_id) {
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
            $action = 'updated';
        } else {
            $post_id = wp_insert_post($post_data);
            $action = 'new';
        }

        if (!$post_id) {
            return 'error';
        }

        // Guardar ID de Kyero
        update_post_meta($post_id, '_kyero_id', $kyero_id);
        update_post_meta($post_id, '_kyero_ref', (string) $property->ref);

        // Configurar como producto simple
        wp_set_object_terms($post_id, 'simple', 'product_type');

        // Precio
        $price = (string) $property->price;
        update_post_meta($post_id, '_regular_price', $price);
        update_post_meta($post_id, '_price', $price);

        // Campos ACF
        $this->import_acf_fields($post_id, $property);

        // Taxonomías
        $this->import_taxonomies($post_id, $property);

        // Imágenes
        $this->import_images($post_id, $property);

        return $action;
    }

    /**
     * Importar campos ACF
     */
    private function import_acf_fields($post_id, $property)
    {
        // Referencia interna
        if (isset($property->ref)) {
            update_field('referencia_interna', (string) $property->ref, $post_id);
        }

        // Superficie
        if (isset($property->surface_area->built)) {
            update_field('superficie_m2', (int) $property->surface_area->built, $post_id);
        }

        // Coordenadas GPS
        if (isset($property->location->latitude) && isset($property->location->longitude)) {
            $coordenadas = [
                'lat' => (float) $property->location->latitude,
                'lng' => (float) $property->location->longitude
            ];
            update_field('coordenadas_gps', $coordenadas, $post_id);
        } elseif (isset($property->latitude) && isset($property->longitude)) {
            $coordenadas = [
                'lat' => (float) $property->latitude,
                'lng' => (float) $property->longitude
            ];
            update_field('coordenadas_gps', $coordenadas, $post_id);
        }

        // Dormitorios (crear repeater ACF de habitaciones)
        if (isset($property->beds)) {
            $num_beds = (int) $property->beds;
            $habitaciones = [];

            for ($i = 1; $i <= $num_beds; $i++) {
                $habitaciones[] = [
                    'nombre_hab' => 'Dormitorio ' . $i,
                    'tipo_cama' => 'matrimonio', // Valor por defecto
                    'bano_en_suite' => false
                ];
            }

            update_field('distribucion_habitaciones', $habitaciones, $post_id);
        }

        // Baños (añadir este campo a ACF si no existe)
        if (isset($property->baths)) {
            update_field('numero_banos', (int) $property->baths, $post_id);
        }
    }

    /**
     * Importar taxonomías
     */
    private function import_taxonomies($post_id, $property)
    {
        // Población
        if (isset($property->town)) {
            $poblacion = (string) $property->town;
            wp_set_object_terms($post_id, $poblacion, 'poblacion');
        }

        // Características (traducir de inglés a español)
        if (isset($property->features->feature)) {
            $translation_map = [
                'Swimming Pool' => 'Piscina Privada',
                'WiFi' => 'WiFi Fibra',
                'Air Conditioning' => 'Aire Acondicionado',
                'Parking' => 'Parking Privado',
                'Sea Views' => 'Vistas al Mar',
                'Garden' => 'Jardín',
                'BBQ' => 'Barbacoa',
                'Terraza' => 'Terraza',
                'Pets Allowed' => 'Admite Mascotas',
            ];

            $caracteristicas = [];
            foreach ($property->features->feature as $feature) {
                $feature_en = (string) $feature;
                $caracteristicas[] = $translation_map[$feature_en] ?? $feature_en;
            }

            wp_set_object_terms($post_id, $caracteristicas, 'caracteristicas');
        }
    }

    /**
     * Importar imágenes
     */
    private function import_images($post_id, $property)
    {
        if (!isset($property->images->image)) {
            return;
        }

        $gallery_ids = [];
        $is_first = true;

        foreach ($property->images->image as $image) {
            $image_url = (string) $image->url;

            // Descargar imagen
            $attachment_id = $this->download_image($image_url, $post_id);

            if ($attachment_id) {
                if ($is_first) {
                    // Primera imagen como destacada
                    set_post_thumbnail($post_id, $attachment_id);
                    $is_first = false;
                } else {
                    // Resto a la galería
                    $gallery_ids[] = $attachment_id;
                }
            }
        }

        // Guardar galería
        if (!empty($gallery_ids)) {
            update_post_meta($post_id, '_product_image_gallery', implode(',', $gallery_ids));
        }
    }

    /**
     * Descargar imagen desde URL
     */
    private function download_image($image_url, $post_id)
    {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = [
            'name' => basename($image_url),
            'tmp_name' => $tmp
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return false;
        }

        return $attachment_id;
    }

    private function extract_description($property)
    {
        if (isset($property->desc->description)) {
            return (string) $property->desc->description;
        }

        if (isset($property->desc)) {
            $desc = $property->desc;
            $langs = ['es', 'en', 'fr', 'de', 'it', 'pt'];
            foreach ($langs as $lang) {
                if (isset($desc->{$lang}) && (string) $desc->{$lang} !== '') {
                    return (string) $desc->{$lang};
                }
            }
        }

        if (isset($property->desc[0]->description)) {
            return (string) $property->desc[0]->description;
        }

        return '';
    }

    private function extract_title($property)
    {
        if (isset($property->desc->title) && (string) $property->desc->title !== '') {
            return (string) $property->desc->title;
        }

        if (isset($property->desc[0]->title) && (string) $property->desc[0]->title !== '') {
            return (string) $property->desc[0]->title;
        }

        $type = isset($property->type) ? (string) $property->type : '';
        $town = isset($property->town) ? (string) $property->town : '';
        $ref = isset($property->ref) ? (string) $property->ref : '';

        if ($type || $town) {
            $parts = array_filter([$type, $town]);
            return implode(' en ', $parts);
        }

        if ($ref) {
            return 'Ref ' . $ref;
        }

        $desc = $this->extract_description($property);
        if ($desc) {
            return wp_trim_words($desc, 6, '');
        }

        return 'Propiedad importada';
    }
}
