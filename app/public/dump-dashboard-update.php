<?php
$file = 'c:\\Users\\yagos\\Local Sites\\alquipressyago2\\app\\public\\wp-content\\plugins\\alquipress-core\\includes\\modules\\dashboard-widgets\\class-dashboard-data.php';

$content = file_get_contents($file);

// Add get_owner_property_ids function right after class declaration
$helper = "
    /**
     * Obtener IDs de propiedades permitidas para el usuario actual.
     * Si no es propietario (es admin), devuelve null.
     */
    public static function get_owner_property_ids() {
        if (!function_exists('wp_get_current_user')) return null;
        \$current_user = wp_get_current_user();
        if (!\$current_user->exists() || !in_array('propietario_alquipress', (array) \$current_user->roles, true) || current_user_can('manage_options') || current_user_can('manage_woocommerce')) {
            return null;
        }
        \$owner_posts = get_posts([
            'post_type'      => 'propietario',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_query'     => [
                ['key' => 'owner_user_id', 'value' => \$current_user->ID, 'compare' => '=']
            ],
            'fields' => 'ids',
        ]);
        if (empty(\$owner_posts)) return [0];
        \$owner_prop_ids = get_post_meta((int) \$owner_posts[0], 'owner_properties', true);
        if (!is_array(\$owner_prop_ids)) return [0];
        \$owner_prop_ids = array_map(function(\$p) { return is_object(\$p) && isset(\$p->ID) ? (int) \$p->ID : (int) \$p; }, \$owner_prop_ids);
        \$owner_prop_ids = array_values(array_filter(\$owner_prop_ids));
        return empty(\$owner_prop_ids) ? [0] : \$owner_prop_ids;
    }
";

$content = preg_replace('/class Alquipress_Dashboard_Data\s*\{/', "class Alquipress_Dashboard_Data {\n" . $helper, $content);

// 1. get_bookings_by_checkin_date
$find = <<<PHP
    public static function get_bookings_by_checkin_date(\$date)
    {
        global \$wpdb;

        \$order_ids = \$wpdb->get_col(\$wpdb->prepare(
            "SELECT post_id FROM {\$wpdb->postmeta}
            WHERE meta_key = '_booking_checkin_date'
            AND meta_value = %s",
            \$date
        ));

        return \$order_ids;
    }
PHP;

$replace = <<<PHP
    public static function get_bookings_by_checkin_date(\$date)
    {
        global \$wpdb;
        \$owner_props = self::get_owner_property_ids();
        
        if (\$owner_props !== null) {
            \$prop_list = implode(',', array_map('intval', \$owner_props));
            return \$wpdb->get_col(\$wpdb->prepare(
                "SELECT pm1.post_id FROM {\$wpdb->postmeta} pm1
                INNER JOIN {\$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
                WHERE pm1.meta_key = '_booking_checkin_date' AND pm1.meta_value = %s
                AND pm2.meta_key = '_booking_product_id' AND pm2.meta_value IN (\$prop_list)",
                \$date
            ));
        }

        \$order_ids = \$wpdb->get_col(\$wpdb->prepare(
            "SELECT post_id FROM {\$wpdb->postmeta}
            WHERE meta_key = '_booking_checkin_date'
            AND meta_value = %s",
            \$date
        ));

        return \$order_ids;
    }
PHP;
$content = str_replace($find, $replace, $content);

// 2. get_bookings_by_checkout_date
$find = <<<PHP
    public static function get_bookings_by_checkout_date(\$date)
    {
        global \$wpdb;

        \$order_ids = \$wpdb->get_col(\$wpdb->prepare(
            "SELECT post_id FROM {\$wpdb->postmeta}
            WHERE meta_key = '_booking_checkout_date'
            AND meta_value = %s",
            \$date
        ));

        return \$order_ids;
    }
PHP;

$replace = <<<PHP
    public static function get_bookings_by_checkout_date(\$date)
    {
        global \$wpdb;
        \$owner_props = self::get_owner_property_ids();
        
        if (\$owner_props !== null) {
            \$prop_list = implode(',', array_map('intval', \$owner_props));
            return \$wpdb->get_col(\$wpdb->prepare(
                "SELECT pm1.post_id FROM {\$wpdb->postmeta} pm1
                INNER JOIN {\$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
                WHERE pm1.meta_key = '_booking_checkout_date' AND pm1.meta_value = %s
                AND pm2.meta_key = '_booking_product_id' AND pm2.meta_value IN (\$prop_list)",
                \$date
            ));
        }

        \$order_ids = \$wpdb->get_col(\$wpdb->prepare(
            "SELECT post_id FROM {\$wpdb->postmeta}
            WHERE meta_key = '_booking_checkout_date'
            AND meta_value = %s",
            \$date
        ));

        return \$order_ids;
    }
PHP;
$content = str_replace($find, $replace, $content);

// 3. get_revenue_between_dates
$find = <<<PHP
        \$sql = "
            SELECT SUM(COALESCE(CAST(pm_real.meta_value AS DECIMAL(12,2)), CAST(pm_wc.meta_value AS DECIMAL(12,2))))
            FROM {\$wpdb->posts} p
            INNER JOIN {\$wpdb->postmeta} pm_wc ON p.ID = pm_wc.post_id AND pm_wc.meta_key = '_order_total'
            LEFT JOIN {\$wpdb->postmeta} pm_real ON p.ID = pm_real.post_id AND pm_real.meta_key = '_apm_booking_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-deposito-ok', 'wc-in-progress')
            AND p.post_date >= %s
            AND p.post_date <= %s
        ";

        \$end_date_full = \$end_date . ' 23:59:59';
        \$total = \$wpdb->get_var(\$wpdb->prepare(\$sql, \$start_date, \$end_date_full));
PHP;

$replace = <<<PHP
        \$owner_props = self::get_owner_property_ids();
        \$owner_join = '';
        \$owner_where = '';
        if (\$owner_props !== null) {
            \$prop_list = implode(',', array_map('intval', \$owner_props));
            \$owner_join = " INNER JOIN {\$wpdb->postmeta} pm_prod ON p.ID = pm_prod.post_id AND pm_prod.meta_key = '_booking_product_id' ";
            \$owner_where = " AND pm_prod.meta_value IN (\$prop_list) ";
        }

        \$sql = "
            SELECT SUM(COALESCE(CAST(pm_real.meta_value AS DECIMAL(12,2)), CAST(pm_wc.meta_value AS DECIMAL(12,2))))
            FROM {\$wpdb->posts} p
            INNER JOIN {\$wpdb->postmeta} pm_wc ON p.ID = pm_wc.post_id AND pm_wc.meta_key = '_order_total'
            LEFT JOIN {\$wpdb->postmeta} pm_real ON p.ID = pm_real.post_id AND pm_real.meta_key = '_apm_booking_total'
            \$owner_join
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-deposito-ok', 'wc-in-progress')
            AND p.post_date >= %s
            AND p.post_date <= %s
            \$owner_where
        ";

        \$end_date_full = \$end_date . ' 23:59:59';
        \$total = \$wpdb->get_var(\$wpdb->prepare(\$sql, \$start_date, \$end_date_full));
PHP;
$content = str_replace($find, $replace, $content);

// 4. get_revenue_by_status
$find = <<<PHP
            \$sql = "
                SELECT SUM(COALESCE(CAST(pm_real.meta_value AS DECIMAL(12,2)), CAST(pm_wc.meta_value AS DECIMAL(12,2))))
                FROM {\$wpdb->posts} p
                INNER JOIN {\$wpdb->postmeta} pm_wc ON p.ID = pm_wc.post_id AND pm_wc.meta_key = '_order_total'
                LEFT JOIN {\$wpdb->postmeta} pm_real ON p.ID = pm_real.post_id AND pm_real.meta_key = '_apm_booking_total'
                WHERE p.post_type = 'shop_order'
                AND p.post_status = %s
                AND p.post_date >= %s
                AND p.post_date <= %s
            ";

            \$total = \$wpdb->get_var(\$wpdb->prepare(\$sql, \$status_key, \$start_date, \$end_date_full));
PHP;

$replace = <<<PHP
            \$owner_props = self::get_owner_property_ids();
            \$owner_join = '';
            \$owner_where = '';
            if (\$owner_props !== null) {
                \$prop_list = implode(',', array_map('intval', \$owner_props));
                \$owner_join = " INNER JOIN {\$wpdb->postmeta} pm_prod ON p.ID = pm_prod.post_id AND pm_prod.meta_key = '_booking_product_id' ";
                \$owner_where = " AND pm_prod.meta_value IN (\$prop_list) ";
            }

            \$sql = "
                SELECT SUM(COALESCE(CAST(pm_real.meta_value AS DECIMAL(12,2)), CAST(pm_wc.meta_value AS DECIMAL(12,2))))
                FROM {\$wpdb->posts} p
                INNER JOIN {\$wpdb->postmeta} pm_wc ON p.ID = pm_wc.post_id AND pm_wc.meta_key = '_order_total'
                LEFT JOIN {\$wpdb->postmeta} pm_real ON p.ID = pm_real.post_id AND pm_real.meta_key = '_apm_booking_total'
                \$owner_join
                WHERE p.post_type = 'shop_order'
                AND p.post_status = %s
                AND p.post_date >= %s
                AND p.post_date <= %s
                \$owner_where
            ";

            \$total = \$wpdb->get_var(\$wpdb->prepare(\$sql, \$status_key, \$start_date, \$end_date_full));
PHP;
$content = str_replace($find, $replace, $content);

// 5. get_occupied_properties_count
$find = <<<PHP
        // Buscar reservas activas en esta fecha
        \$occupied_properties = \$wpdb->get_results(\$wpdb->prepare(
            "SELECT DISTINCT pm1.post_id, pm2.meta_value as product_id
            FROM {\$wpdb->postmeta} pm1
            INNER JOIN {\$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            INNER JOIN {\$wpdb->postmeta} pm3 ON pm1.post_id = pm3.post_id
            WHERE pm1.meta_key = '_booking_checkin_date'
            AND pm2.meta_key = '_booking_product_id'
            AND pm3.meta_key = '_booking_checkout_date'
            AND pm1.meta_value <= %s
            AND pm3.meta_value >= %s",
            \$date,
            \$date
        ));
PHP;

$replace = <<<PHP
        \$owner_props = self::get_owner_property_ids();
        \$owner_where = '';
        if (\$owner_props !== null) {
            \$prop_list = implode(',', array_map('intval', \$owner_props));
            \$owner_where = " AND pm2.meta_value IN (\$prop_list) ";
        }

        // Buscar reservas activas en esta fecha
        \$occupied_properties = \$wpdb->get_results(\$wpdb->prepare(
            "SELECT DISTINCT pm1.post_id, pm2.meta_value as product_id
            FROM {\$wpdb->postmeta} pm1
            INNER JOIN {\$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            INNER JOIN {\$wpdb->postmeta} pm3 ON pm1.post_id = pm3.post_id
            WHERE pm1.meta_key = '_booking_checkin_date'
            AND pm2.meta_key = '_booking_product_id'
            AND pm3.meta_key = '_booking_checkout_date'
            AND pm1.meta_value <= %s
            AND pm3.meta_value >= %s
            \$owner_where",
            \$date,
            \$date
        ));
PHP;
$content = str_replace($find, $replace, $content);

// 6. get_active_bookings_count
$find = <<<PHP
        // Query optimizada: Busca pedidos que tengan checkin <= hoy <= checkout
        \$sql = "
            SELECT COUNT(DISTINCT p.ID)
            FROM {\$wpdb->posts} p
            INNER JOIN {\$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            INNER JOIN {\$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN (\$status_string)
            AND pm1.meta_key = '_booking_checkin_date' AND pm1.meta_value <= %s
            AND pm2.meta_key = '_booking_checkout_date' AND pm2.meta_value >= %s
        ";
PHP;

$replace = <<<PHP
        \$owner_props = self::get_owner_property_ids();
        \$owner_join = '';
        \$owner_where = '';
        if (\$owner_props !== null) {
            \$prop_list = implode(',', array_map('intval', \$owner_props));
            \$owner_join = " INNER JOIN {\$wpdb->postmeta} pm_prod ON p.ID = pm_prod.post_id ";
            \$owner_where = " AND pm_prod.meta_key = '_booking_product_id' AND pm_prod.meta_value IN (\$prop_list) ";
        }

        // Query optimizada: Busca pedidos que tengan checkin <= hoy <= checkout
        \$sql = "
            SELECT COUNT(DISTINCT p.ID)
            FROM {\$wpdb->posts} p
            INNER JOIN {\$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            INNER JOIN {\$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            \$owner_join
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN (\$status_string)
            AND pm1.meta_key = '_booking_checkin_date' AND pm1.meta_value <= %s
            AND pm2.meta_key = '_booking_checkout_date' AND pm2.meta_value >= %s
            \$owner_where
        ";
PHP;
$content = str_replace($find, $replace, $content);

file_put_contents($file, $content);
echo "Dashboard data class modified successfully.\n";
