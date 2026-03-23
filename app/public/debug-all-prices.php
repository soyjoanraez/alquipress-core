<?php
require_once('wp-load.php');

$args = array(
    'post_type' => 'product',
    'posts_per_page' => 10,
);
$query = new WP_Query($args);

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $id = get_the_ID();
        echo "Property ID: " . $id . " - Title: " . get_the_title() . "\n";
        echo "  _price: " . get_post_meta($id, '_price', true) . "\n";
        echo "  _regular_price: " . get_post_meta($id, '_regular_price', true) . "\n";
        echo "  precio: " . get_post_meta($id, 'precio', true) . "\n";
        echo "  precio_noche: " . get_post_meta($id, 'precio_noche', true) . "\n";
        echo "  price: " . get_post_meta($id, 'price', true) . "\n";
        echo "---------------------------\n";
    }
} else {
    echo "No properties found.\n";
}
wp_reset_postdata();
