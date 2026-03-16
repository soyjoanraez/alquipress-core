<?php
require_once('wp-load.php');
$id = 3873; // Villa Puerto en Moraira #10
echo "Meta for 3873:\n";
print_r(get_post_custom($id));
