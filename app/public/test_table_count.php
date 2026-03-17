<?php
require_once 'wp-load.php';
global $wpdb;
$table = $wpdb->prefix . 'apm_payment_schedule';
$count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
if ($wpdb->last_error) {
    echo "Error: " . $wpdb->last_error . "\n";
} else {
    echo "Table {$table} row count: " . $count . "\n";
}
