#!/bin/bash
echo "╔═══════════════════════════════════════╗"
echo "║  ALQUIPRESS - Estado de Instalación   ║"
echo "╚═══════════════════════════════════════╝"
echo ""

echo "✓ WordPress Core"
wp core version

echo ""
echo "✓ Tema Activo"
wp theme list --status=active --field=name

echo ""
echo "✓ Plugins Activos"
wp plugin list --status=active --fields=name,version

echo ""
echo "✓ Plugin ALQUIPRESS Core"
if wp plugin is-active alquipress-core; then
  echo "  ✓ Activo"
else
  echo "  ✗ No activo"
fi

echo ""
echo "✓ Taxonomías Registradas"
wp taxonomy list --field=name | grep -E "(poblacion|zona|caracteristicas)"

echo ""
echo "✓ CPT Propietarios"
if wp post-type list --field=name | grep -q "propietario"; then
  echo "  ✓ Registrado"
  echo "  Total propietarios: $(wp post list --post_type=propietario --format=count)"
else
  echo "  ✗ No registrado"
fi

echo ""
echo "✓ Términos Creados"
echo "  Poblaciones: $(wp term list poblacion --format=count)"
echo "  Zonas: $(wp term list zona --format=count)"
echo "  Características: $(wp term list caracteristicas --format=count)"

echo ""
echo "╚═══════════════════════════════════════╝"
