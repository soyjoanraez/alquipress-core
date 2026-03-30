<?php
/**
 * Módulo: Renombrado de Propiedades
 * 
 * Este módulo transforma la terminología de "Productos" de WooCommerce
 * a "Inmuebles" tanto en el admin como en las URLs.
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Property_Renaming
{

    public function __construct()
    {
        // Renombrar CPT Product
        add_filter('woocommerce_register_post_type_product', [$this, 'rename_product_cpt']);

        // Renombrar etiquetas en el menú de administración
        add_action('admin_menu', [$this, 'rename_admin_menu_labels'], 999);

        // Renombrar textos de WooCommerce
        add_filter('gettext', [$this, 'rename_woocommerce_strings'], 20, 3);

        // Modificar URLs para incluir referencia interna
        add_filter('post_type_link', [$this, 'custom_property_permalink'], 10, 2);
        add_action('save_post_product', [$this, 'update_property_slug'], 10, 3);
        // Segunda pasada tras el guardado de Alquipress_Product_Fields (priority 15)
        add_action('save_post_product', [$this, 'update_property_slug_on_acf_save'], 20, 1);

        // flush rules on activation (handled by module manager or manual trigger)
    }

    /**
     * Modifica las etiquetas y el slug del CPT product
     */
    public function rename_product_cpt($args)
    {
        $labels = [
            'name' => 'Inmuebles',
            'singular_name' => 'Inmueble',
            'menu_name' => 'Inmuebles',
            'add_new' => 'Añadir Inmueble',
            'add_new_item' => 'Añadir Nuevo Inmueble',
            'edit_item' => 'Editar Inmueble',
            'new_item' => 'Nuevo Inmueble',
            'view_item' => 'Ver Inmueble',
            'search_items' => 'Buscar Inmuebles',
            'not_found' => 'No se han encontrado inmuebles',
            'not_found_in_trash' => 'No se han encontrado inmuebles en la papelera',
            'all_items' => 'Todos los Inmuebles',
        ];

        $args['labels'] = array_merge($args['labels'], $labels);

        // Cambiar el slug de la URL
        $args['rewrite']['slug'] = 'inmueble';
        $args['has_archive'] = 'inmuebles';

        // Asegurar que la papelera funciona
        $args['map_meta_cap'] = true;
        $args['capability_type'] = 'product';

        return $args;
    }

    /**
     * Renombra las etiquetas del menú lateral
     */
    public function rename_admin_menu_labels()
    {
        global $menu, $submenu;

        foreach ($menu as $key => $item) {
            if ($item[0] === 'Productos') {
                $menu[$key][0] = 'Inmuebles';
            }
        }

        if (isset($submenu['edit.php?post_type=product'])) {
            foreach ($submenu['edit.php?post_type=product'] as $key => $item) {
                if ($item[0] === 'Todos los productos') {
                    $submenu['edit.php?post_type=product'][$key][0] = 'Todos los inmuebles';
                }
                if ($item[0] === 'Añadir nuevo') {
                    $submenu['edit.php?post_type=product'][$key][0] = 'Añadir inmueble';
                }
            }
        }
    }

    /**
     * Traducción dinámica de cadenas "Producto" -> "Inmueble"
     */
    public function rename_woocommerce_strings($translated_text, $text, $domain)
    {
        if ($domain === 'woocommerce') {
            switch ($text) {
                case 'Product':
                    $translated_text = 'Inmueble';
                    break;
                case 'Products':
                    $translated_text = 'Inmuebles';
                    break;
                case 'Related products':
                    $translated_text = 'Inmuebles relacionados';
                    break;
            }
        }
        return $translated_text;
    }

    /**
     * Modificar el permalink de las propiedades para incluir la referencia interna
     */
    public function custom_property_permalink($post_link, $post)
    {
        if ($post->post_type !== 'product') {
            return $post_link;
        }

        // Obtener referencia interna
        $ref = '';
        if (function_exists('get_field')) {
            $ref = get_field('referencia_interna', $post->ID);
        }
        
        // Si no hay referencia interna, usar SKU o ID
        if (empty($ref)) {
            $ref = get_post_meta($post->ID, '_sku', true);
        }
        
        if (empty($ref)) {
            $ref = $post->ID;
        }

        // Sanitizar referencia para URL
        $ref_slug = sanitize_title($ref);

        // Construir nueva URL: título + referencia interna
        $title_slug = sanitize_title($post->post_title);
        
        // Si el post_name ya incluye la referencia, usarlo directamente
        if (strpos($post->post_name, '-' . $ref_slug) !== false) {
            return $post_link;
        }

        // Construir nueva URL con referencia al final
        $new_slug = $title_slug . '-' . $ref_slug;
        
        // Reemplazar el slug en la URL
        $post_link = str_replace('/' . $post->post_name . '/', '/' . $new_slug . '/', $post_link);

        return $post_link;
    }

    /**
     * Actualizar el slug del post cuando se guarda para incluir la referencia interna
     */
    public function update_property_slug($post_id, $post, $update)
    {
        // Solo para productos publicados o actualizados
        if ($post->post_type !== 'product' || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        // Evitar recursión - verificar si ya estamos procesando este post
        static $processing = [];
        if (isset($processing[$post_id])) {
            return;
        }
        $processing[$post_id] = true;

        // Obtener referencia interna (después de que se haya guardado)
        $ref = '';
        if (function_exists('get_field')) {
            $ref = get_field('referencia_interna', $post_id);
        }
        
        // Si no hay referencia interna, usar SKU o ID
        if (empty($ref)) {
            $ref = get_post_meta($post_id, '_sku', true);
        }
        
        if (empty($ref)) {
            $ref = $post_id;
        }

        // Sanitizar referencia para slug
        $ref_slug = sanitize_title($ref);
        
        // Construir nuevo slug: título + referencia interna
        $title_slug = sanitize_title($post->post_title);
        
        // Evitar slug vacío
        if (empty($title_slug)) {
            $title_slug = 'inmueble';
        }
        
        $new_slug = $title_slug . '-' . $ref_slug;

        // Solo actualizar si el slug ha cambiado y no está vacío
        if (!empty($new_slug) && $post->post_name !== $new_slug) {
            // Evitar recursión removiendo temporalmente el hook
            remove_action('save_post_product', [$this, 'update_property_slug'], 10);
            
            global $wpdb;
            $lock_name = 'alquipress_slug_update_' . $post_id;
            $lock_timeout = 10; // segundos
            
            // Intentar obtener lock para evitar race conditions
            $lock_acquired = $wpdb->get_var($wpdb->prepare(
                "SELECT GET_LOCK(%s, %d)",
                $lock_name,
                $lock_timeout
            ));
            
            if ($lock_acquired) {
                try {
                    // Verificar que el slug no esté en uso por otro post (con lock, esta verificación es atómica)
                    $existing_post = get_page_by_path($new_slug, OBJECT, 'product');
                    if ($existing_post && $existing_post->ID !== $post_id) {
                        // Si existe otro post con ese slug, añadir ID al final
                        $new_slug = $new_slug . '-' . $post_id;
                        
                        // Verificar nuevamente después de modificar el slug
                        $existing_post = get_page_by_path($new_slug, OBJECT, 'product');
                        if ($existing_post && $existing_post->ID !== $post_id) {
                            // Si aún existe conflicto, usar timestamp
                            $new_slug = $new_slug . '-' . time();
                        }
                    }
                    
                    // Actualizar el slug sin disparar hooks adicionales
                    $updated = $wpdb->update(
                        $wpdb->posts,
                        ['post_name' => $new_slug],
                        ['ID' => $post_id],
                        ['%s'],
                        ['%d']
                    );
                    
                    if ($updated === false) {
                        if (class_exists('Alquipress_Logger')) {
                            Alquipress_Logger::error(
                                'Error al actualizar slug de propiedad',
                                Alquipress_Logger::CONTEXT_MODULE,
                                [
                                    'post_id' => $post_id,
                                    'new_slug' => $new_slug,
                                    'wpdb_error' => $wpdb->last_error
                                ]
                            );
                        }
                    } else {
                        // Logging de cambios de slug para auditoría
                        if (class_exists('Alquipress_Logger')) {
                            Alquipress_Logger::info(
                                sprintf('Slug de propiedad actualizado: %s -> %s', $post->post_name, $new_slug),
                                Alquipress_Logger::CONTEXT_MODULE,
                                ['post_id' => $post_id]
                            );
                        }
                    }
                    
                    // Limpiar caché
                    clean_post_cache($post_id);
                    
                } finally {
                    // Liberar lock siempre, incluso si hay error
                    $wpdb->get_var($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock_name));
                }
            } else {
                // No se pudo obtener el lock, loggear advertencia
                if (class_exists('Alquipress_Logger')) {
                    Alquipress_Logger::warning(
                        'No se pudo obtener lock para actualizar slug',
                        Alquipress_Logger::CONTEXT_MODULE,
                        ['post_id' => $post_id, 'lock_name' => $lock_name]
                    );
                }
            }
            
            // Restaurar el hook
            add_action('save_post_product', [$this, 'update_property_slug'], 10, 3);
        }
        
        unset($processing[$post_id]);
    }

    /**
     * Segunda pasada tras el guardado de campos del producto para actualizar el slug
     * cuando referencia_interna cambia (se engancha a save_post_product priority 20).
     */
    public function update_property_slug_on_acf_save(int $post_id): void
    {
        // Solo procesar productos
        if (get_post_type($post_id) !== 'product') {
            return;
        }

        // Evitar recursión - verificar si ya estamos procesando este post
        static $processing = [];
        if (isset($processing[$post_id])) {
            return;
        }
        $processing[$post_id] = true;

        // Obtener el post
        $post = get_post($post_id);
        if (!$post) {
            unset($processing[$post_id]);
            return;
        }

        // Llamar al método de actualización de slug
        $this->update_property_slug($post_id, $post, true);
        
        unset($processing[$post_id]);
    }
}

new Alquipress_Property_Renaming();
