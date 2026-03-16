<?php
require_once('wp-load.php');
$args = array('post_type' => 'product', 'posts_per_page' => -1);
$query = new WP_Query($args);
echo "Non-numeric Price Check:\n";
while ($query->have_posts()) {
    $query->the_post();
    $id = get_the_ID();
    $p = get_post_meta($id, '_price', true);
    $rp = get_post_meta($id, '_regular_price', true);
    if (($p !== '' && !is_numeric($p)) || ($rp !== '' && !is_numeric($rp))) {
        echo "ID: $id | Title: " . get_the_title() . " | _price: '$p' | _regular_price: '$rp'\n";
    }
}
