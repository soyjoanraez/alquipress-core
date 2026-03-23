<?php
require_once('wp-load.php');
$args = array('post_type' => 'product', 'posts_per_page' => -1);
$query = new WP_Query($args);
echo "Price Debug List (Full):\n";
while ($query->have_posts()) {
    $query->the_post();
    $id = get_the_ID();
    echo "ID: $id | Title: " . get_the_title() . "\n";
    echo "  _price: '" . get_post_meta($id, '_price', true) . "'\n";
    echo "  _regular_price: '" . get_post_meta($id, '_regular_price', true) . "'\n";
    echo "  ap_base_price: '" . get_post_meta($id, 'ap_base_price', true) . "'\n";
    echo "  _wc_booking_cost: '" . get_post_meta($id, '_wc_booking_cost', true) . "'\n";
    echo "  _wc_display_cost: '" . get_post_meta($id, '_wc_display_cost', true) . "'\n";
}
