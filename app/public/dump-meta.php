<?php
require_once('wp-load.php');
$post_id = 4060;
$meta = get_post_custom($post_id);
echo "All meta for post $post_id:\n";
foreach($meta as $key => $values) {
    echo "$key: " . implode(', ', $values) . "\n";
}
