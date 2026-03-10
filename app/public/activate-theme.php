<?php
require_once('wp-load.php');

$theme_slug = 'alquipress-theme';

// Verificar si existe el tema
$theme = wp_get_theme($theme_slug);

if ($theme->exists()) {
    switch_theme($theme_slug);
    echo "✅ Tema hijo '{$theme->get('Name')}' activado correctamente.\n";
    echo "   Parent: " . $theme->get('Template') . "\n";
} else {
    echo "❌ Error: El tema '$theme_slug' no existe.\n";
}
