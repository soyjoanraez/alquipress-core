#!/bin/bash
# ALQUIPRESS - Setup Automatizado

echo "🚀 Iniciando instalación de ALQUIPRESS..."

# Colores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 1. CONFIGURACIÓN BÁSICA WORDPRESS
echo -e "${BLUE}📝 Configurando WordPress...${NC}"

wp option update blogname "ALQUIPRESS"
wp option update blogdescription "Gestión Profesional de Alquiler Vacacional"
wp option update timezone_string "Europe/Madrid"
wp option update date_format "d/m/Y"
wp option update time_format "H:i"

# Permalinks
wp rewrite structure '/%postname%/' --hard
wp rewrite flush --hard

# Deshabilitar comentarios por defecto
wp option update default_comment_status "closed"

# Desindexación temporal (desarrollo)
wp option update blog_public 0

echo -e "${GREEN}✓ WordPress configurado${NC}"

# 2. INSTALACIÓN DE TEMA
echo -e "${BLUE}📦 Instalando tema Astra...${NC}"

wp theme install astra --activate
wp theme delete twentytwentyone twentytwentytwo twentytwentythree twentytwentyfour

echo -e "${GREEN}✓ Tema Astra instalado${NC}"

# 3. PLUGINS ESENCIALES
echo -e "${BLUE}🔌 Instalando plugins esenciales...${NC}"

# Plugins gratuitos del repositorio
wp plugin install \
  woocommerce \
  mailpoet \
  code-snippets \
  wp-crontrol \
  query-monitor \
  enable-media-replace \
  all-in-one-wp-migration \
  --activate

# Desactivar plugins por defecto que vienen con Local
wp plugin delete akismet hello

echo -e "${GREEN}✓ Plugins base instalados${NC}"

# 4. CONFIGURACIÓN DE WOOCOMMERCE
echo -e "${BLUE}🛒 Configurando WooCommerce...${NC}"

# Configuración general
wp option update woocommerce_store_address "Calle Principal 123"
wp option update woocommerce_store_city "Dénia"
wp option update woocommerce_default_country "ES"
wp option update woocommerce_store_postcode "03700"
wp option update woocommerce_currency "EUR"
wp option update woocommerce_currency_pos "right_space"
wp option update woocommerce_price_thousand_sep "."
wp option update woocommerce_price_decimal_sep ","
wp option update woocommerce_price_num_decimals "2"

# Desactivar impuestos
wp option update woocommerce_calc_taxes "no"

# Checkout
wp option update woocommerce_enable_guest_checkout "no"
wp option update woocommerce_enable_signup_and_login_from_checkout "yes"

# Stock
wp option update woocommerce_manage_stock "no"

# Crear páginas de WooCommerce
wp wc tool run install_pages --user=1

echo -e "${GREEN}✓ WooCommerce configurado${NC}"

# 5. CREAR PÁGINAS BÁSICAS
echo -e "${BLUE}📄 Creando páginas...${NC}"

# Página de inicio
wp post create --post_type=page --post_title='Inicio' --post_status=publish --post_content='<!-- wp:paragraph --><p>Contenido de inicio</p><!-- /wp:paragraph -->'

# Página Alojamientos (ya creada por Woo como Shop)

# Otras páginas
wp post create --post_type=page --post_title='Nosotros' --post_status=publish
wp post create --post_type=page --post_title='Contacto' --post_status=publish
wp post create --post_type=page --post_title='Políticas de Cancelación' --post_status=publish
wp post create --post_type=page --post_title='Términos y Condiciones' --post_status=publish

# Configurar página de inicio
HOMEPAGE_ID=$(wp post list --post_type=page --post_title='Inicio' --field=ID --format=ids)
wp option update show_on_front "page"
wp option update page_on_front "$HOMEPAGE_ID"

echo -e "${GREEN}✓ Páginas creadas${NC}"

# 6. MENÚS
echo -e "${BLUE}📋 Configurando menús...${NC}"

# Crear menú principal
wp menu create "Menú Principal"
MENU_ID=$(wp menu list --fields=term_id --format=ids)

# Añadir items al menú
wp menu item add-post $MENU_ID $HOMEPAGE_ID
SHOP_ID=$(wp post list --post_type=page --post_title='Tienda' --field=ID --format=ids)
wp menu item add-post $MENU_ID $SHOP_ID
NOSOTROS_ID=$(wp post list --post_type=page --post_title='Nosotros' --field=ID --format=ids)
wp menu item add-post $MENU_ID $NOSOTROS_ID
CONTACTO_ID=$(wp post list --post_type=page --post_title='Contacto' --field=ID --format=ids)
wp menu item add-post $MENU_ID $CONTACTO_ID

# Asignar ubicación
wp menu location assign $MENU_ID primary

echo -e "${GREEN}✓ Menús configurados${NC}"

# 7. CONFIGURACIÓN DE MEDIOS
echo -e "${BLUE}🖼️  Configurando medios...${NC}"

wp option update thumbnail_size_w 300
wp option update thumbnail_size_h 300
wp option update thumbnail_crop 1
wp option update medium_size_w 768
wp option update medium_size_h 768
wp option update large_size_w 1024
wp option update large_size_h 1024

# Crear directorio para uploads si no existe
mkdir -p wp-content/uploads

echo -e "${GREEN}✓ Medios configurados${NC}"

# 8. MAILPOET CONFIGURACIÓN
echo -e "${BLUE}📧 Configurando MailPoet...${NC}"

# Activar envío automático con WooCommerce
wp option update mailpoet_woocommerce_optin_on_checkout '{"enabled":"1"}'

# Crear lista por defecto
wp mailpoet subscriber:create clientes@example.com --status=subscribed

echo -e "${GREEN}✓ MailPoet configurado${NC}"

# 9. USUARIOS DE PRUEBA
echo -e "${BLUE}👥 Creando usuarios de prueba...${NC}"

# Cliente VIP
wp user create cliente1 cliente1@example.com \
  --role=customer \
  --user_pass=cliente123 \
  --first_name=Juan \
  --last_name=Pérez \
  --display_name="Juan Pérez"

# Propietario
wp user create propietario1 propietario1@example.com \
  --role=subscriber \
  --user_pass=propietario123 \
  --first_name=María \
  --last_name=González \
  --display_name="María González"

echo -e "${GREEN}✓ Usuarios de prueba creados${NC}"

# 10. LIMPIEZA
echo -e "${BLUE}🧹 Limpiando instalación...${NC}"

# Eliminar contenido de ejemplo
wp post delete 1 --force # Hello World
wp post delete 2 --force # Sample Page
wp comment delete 1 --force

# Eliminar widgets por defecto
wp widget delete search-1
wp widget delete recent-posts-1
wp widget delete recent-comments-1
wp widget delete archives-1
wp widget delete categories-1
wp widget delete meta-1

echo -e "${GREEN}✓ Limpieza completada${NC}"

# RESUMEN
echo -e "\n${GREEN}═══════════════════════════════════════${NC}"
echo -e "${GREEN}✓ INSTALACIÓN COMPLETADA CON ÉXITO ✓${NC}"
echo -e "${GREEN}═══════════════════════════════════════${NC}\n"

echo "📌 Accesos de prueba:"
echo "   Admin: $(wp option get siteurl)/wp-admin"
echo "   Usuario: admin / (tu contraseña)"
echo ""
echo "   Cliente: cliente1@example.com / cliente123"
echo "   Propietario: propietario1@example.com / propietario123"
echo ""
echo "🔗 URLs importantes:"
echo "   Frontend: $(wp option get siteurl)"
echo "   Tienda: $(wp option get siteurl)/tienda"
echo "   Mi Cuenta: $(wp option get siteurl)/mi-cuenta"
echo ""
echo "⚠️  PENDIENTE:"
echo "   - Instalar ACF PRO manualmente (licencia requerida)"
echo "   - Instalar WooCommerce Bookings manualmente (licencia requerida)"
echo "   - Instalar WooCommerce Deposits manualmente (licencia requerida)"
echo ""
echo "▶️  Siguiente paso: Ejecutar setup-alquipress-plugin.sh"