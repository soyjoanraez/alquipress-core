<?php
/**
 * Módulo: CRM de Huéspedes
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_CRM_Guests
{

    public function __construct()
    {
        add_action('acf/init', [$this, 'load_acf_fields']);
        add_filter('manage_users_columns', [$this, 'add_custom_columns']);
        add_filter('manage_users_custom_column', [$this, 'populate_custom_columns'], 10, 3);
    }

    public function load_acf_fields()
    {
        $json_file = dirname(__FILE__) . '/acf-fields.json';
        if (file_exists($json_file)) {
            $json = file_get_contents($json_file);
            $fields = json_decode($json, true);
            if (function_exists('acf_add_local_field_group') && is_array($fields)) {
                foreach ($fields as $field_group) {
                    acf_add_local_field_group($field_group);
                }
            }
        }
    }

    public function add_custom_columns($columns)
    {
        $columns['guest_status'] = 'Estado';
        $columns['guest_rating'] = 'Valoración';
        return $columns;
    }

    public function populate_custom_columns($value, $column_name, $user_id)
    {
        if ($column_name == 'guest_status') {
            $status = get_field('guest_status', 'user_' . $user_id);
            $badges = [
                'standard' => '<span style="color: #666;">Estándar</span>',
                'vip' => '<span style="color: #F39C12; font-weight: bold;">⭐ VIP</span>',
                'blacklist' => '<span style="color: #E74C3C; font-weight: bold;">🚫 LISTA NEGRA</span>'
            ];
            return $badges[$status] ?? '<span style="color: #999;">-</span>';
        }

        if ($column_name == 'guest_rating') {
            $rating = get_field('guest_rating', 'user_' . $user_id);
            if ($rating) {
                $stars = str_repeat('⭐', (int) $rating);
                return $stars . ' (' . $rating . '/5)';
            }
            return '<span style="color: #999;">-</span>';
        }

        return $value;
    }
}

new Alquipress_CRM_Guests();
