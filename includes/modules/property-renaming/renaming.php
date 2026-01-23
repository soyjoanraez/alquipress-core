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
}

new Alquipress_Property_Renaming();
