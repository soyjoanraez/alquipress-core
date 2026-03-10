<?php
/**
 * Plugin Name: Fix WP 6.7 Deprecations
 * Description: Workaround for WordPress 6.7.0 bug causing deprecated notices in PHP 8.1+ when translation paths are null.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('override_load_textdomain', function($override, $domain, $mofile) {
    // Check if mofile is null or empty to prevent passing null to wp_normalize_path
    if (null === $mofile || empty($mofile)) {
        return true; // Return true to skip loading and prevent the error
    }
    return $override;
}, 1, 3);
