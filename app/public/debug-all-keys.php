<?php
require_once('wp-load.php');
$args = array('post_type' => 'product', 'posts_per_page' => -1);
$query = new WP_Query($args);
echo "Price Keys Investigation:\n";
$found_keys = [];
while ($query->have_posts()) {
    $query->the_post();
    $id = get_the_ID();
    $meta = get_post_meta($id);
    foreach ($meta as $key => $values) {
        if (stripos($key, 'preci') !== false || stripos($key, 'price') !== false || stripos($key, 'cost') !== false) {
            $found_keys[$key] = true;
        }
    }
}
echo "Relevant Keys found in products: " . implode(', ', array_keys($found_keys)) . "\n\n";

$query->rewind_posts();
while ($query->have_posts()) {
    $query->the_post();
    $id = get_the_ID();
    $output = "ID: $id | " . get_the_title() . "\n";
    $has_val = false;
    foreach ($found_keys as $key => $v) {
        $val = get_post_meta($id, $key, true);
        if ($val !== '') {
            $output .= "  $key: '$val'\n";
            $has_val = true;
        }
    }
    if ($has_val) echo $output;
}
