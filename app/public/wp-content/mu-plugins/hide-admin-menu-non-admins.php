<?php
/**
 * Plugin Name: Hide Admin Menu for Non-Admins
 * Description: Removes all admin sidebar menus for non-admin users.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    if (current_user_can('administrator')) {
        return;
    }

    global $menu;
    if (!is_array($menu)) {
        return;
    }

    foreach ($menu as $item) {
        if (!empty($item[2])) {
            remove_menu_page($item[2]);
        }
    }
}, 999);
