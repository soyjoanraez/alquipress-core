<?php
require_once('wp-load.php');

$post_id = 4060;
$product = wc_get_product($post_id);

echo "Product ID: " . $post_id . "\n";
if ($product) {
    echo "Price: " . $product->get_price() . "\n";
    echo "Regular Price: " . $product->get_regular_price() . "\n";
} else {
    echo "Product not found\n";
}

echo "Meta _price: " . get_post_meta($post_id, '_price', true) . "\n";
echo "Meta _regular_price: " . get_post_meta($post_id, '_regular_price', true) . "\n";
echo "Meta _sale_price: " . get_post_meta($post_id, '_sale_price', true) . "\n";
