<?php
require_once('wp-load.php');
if (!function_exists('acf_get_field_groups')) {
    echo "ACF not active";
    exit;
}
$groups = acf_get_field_groups();
foreach ($groups as $group) {
    echo "Group: " . $group['title'] . " (" . $group['key'] . ")\n";
    $fields = acf_get_fields($group['key']);
    foreach ($fields as $field) {
        echo "  - Field: " . $field['label'] . " (" . $field['name'] . ") Type: " . $field['type'] . "\n";
    }
}
