<?php
/**
 * Gestión del rol de propietarios.
 *
 * @package Alquipress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alquipress_Owner_Role_Manager
{
    const ROLE_PROPERTY_OWNER = 'property_owner';
    const ROLE_LEGACY_OWNER = 'owner';

    /**
     * Capacidades mínimas para el portal de propietario.
     *
     * @return array<string, bool>
     */
    public static function get_caps()
    {
        return [
            'read' => true,
            'view_own_properties' => true,
            'view_own_bookings' => true,
            'view_own_reports' => true,
        ];
    }

    /**
     * Asegura que exista el rol objetivo con capacidades esperadas.
     *
     * @return void
     */
    public static function ensure_role_exists()
    {
        $role = get_role(self::ROLE_PROPERTY_OWNER);

        if (!$role) {
            add_role(self::ROLE_PROPERTY_OWNER, __('Propietario', 'alquipress'), self::get_caps());
            return;
        }

        foreach (self::get_caps() as $cap => $grant) {
            if ($grant && !$role->has_cap($cap)) {
                $role->add_cap($cap);
            }
        }
    }

    /**
     * Migra usuarios desde el rol legado "owner" al nuevo "property_owner".
     *
     * @return int Número de usuarios migrados.
     */
    public static function migrate_legacy_owner_users()
    {
        self::ensure_role_exists();

        $legacy_role = get_role(self::ROLE_LEGACY_OWNER);
        if (!$legacy_role) {
            return 0;
        }

        $users = get_users([
            'role' => self::ROLE_LEGACY_OWNER,
            'fields' => ['ID'],
            'number' => 1000,
        ]);

        if (empty($users)) {
            return 0;
        }

        $migrated = 0;
        foreach ($users as $user) {
            $wp_user = get_userdata((int) $user->ID);
            if (!$wp_user) {
                continue;
            }

            if (!in_array(self::ROLE_PROPERTY_OWNER, (array) $wp_user->roles, true)) {
                $wp_user->add_role(self::ROLE_PROPERTY_OWNER);
                $migrated++;
            }
        }

        return $migrated;
    }
}
