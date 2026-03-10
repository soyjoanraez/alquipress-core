<?php
require('wp-load.php');
$id = 3549; // ID that failed before
$post = get_post($id);

if (!$post) {
    echo "Post $id not found. Creating a new one for test...\n";
    $id = wp_insert_post([
        'post_title' => 'DEBUG TRASH TEST',
        'post_type' => 'product',
        'post_status' => 'publish'
    ]);
    echo "Created Post ID: $id\n";
    $post = get_post($id);
}

$pt = get_post_type_object('product');
echo "Post Type Object Dump:\n";
echo " Name: " . $pt->name . "\n";
echo " Cap->delete_posts: " . $pt->cap->delete_posts . "\n";
echo " Map Meta Cap: " . ($pt->map_meta_cap ? 'TRUE' : 'FALSE') . "\n";
echo " Exclude From Search: " . ($pt->exclude_from_search ? 'TRUE' : 'FALSE') . "\n";
echo " _builtin: " . ($pt->_builtin ? 'TRUE' : 'FALSE') . "\n";

echo "--------------------------------\n";
echo "Attempting wp_trash_post($id)...\n";

// Check capabilities first
if (!current_user_can('delete_post', $id)) {
    echo "WARNING: Current user (ID " . get_current_user_id() . ") cannot delete_post $id.\n";
    // Force admin user
    $admin = get_user_by('login', 'joanraez');
    if ($admin) {
        wp_set_current_user($admin->ID);
        echo "Switched to user: " . $admin->user_login . "\n";
        echo "Can delete now? " . (current_user_can('delete_post', $id) ? 'YES' : 'NO') . "\n";
    }
}

$result = wp_trash_post($id);

if ($result) {
    echo "SUCCESS: wp_trash_post returned a post object/data.\n";
    print_r($result);
} else {
    echo "FAILURE: wp_trash_post returned FALSE.\n";
    // Check if EMPTY_TRASH_DAYS is involved
    if (defined('EMPTY_TRASH_DAYS') && EMPTY_TRASH_DAYS === 0) {
        echo "Reason: EMPTY_TRASH_DAYS is 0.\n";
    }
}
