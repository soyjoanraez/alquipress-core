<?php
/**
 * Búsqueda global: propiedades, reservas, clientes
 */
if (!defined('ABSPATH')) {
    exit;
}

$query = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$query = trim($query);

require_once ALQUIPRESS_PATH . 'includes/admin/alquipress-sidebar.php';

$props = [];
$orders = [];
$clients = [];

if (strlen($query) >= 2) {

    $props = get_posts([
        'post_type' => 'product',
        'post_status' => 'publish',
        's' => $query,
        'posts_per_page' => 10,
    ]);

    if (function_exists('wc_get_order')) {
        $order_posts = get_posts([
            'post_type' => 'shop_order',
            'post_status' => 'any',
            's' => $query,
            'posts_per_page' => 10,
        ]);
        foreach ($order_posts as $p) {
            $o = wc_get_order($p->ID);
            if ($o) {
                $orders[] = $o;
            }
        }
    }

    $user_query = new WP_User_Query([
        'role' => 'customer',
        'search' => '*' . $query . '*',
        'search_columns' => ['user_login', 'user_email', 'display_name'],
        'number' => 10,
    ]);
    $clients = $user_query->get_results();
}
?>
<div class="wrap alquipress-search-page ap-has-sidebar">
    <div class="ap-owners-layout">
        <?php alquipress_render_sidebar('search'); ?>
        <main class="ap-owners-main">
            <header class="ap-header">
                <h1 class="ap-header-title"><?php esc_html_e('Búsqueda global', 'alquipress'); ?></h1>
                <p class="ap-header-subtitle"><?php esc_html_e('Propiedades, reservas y clientes', 'alquipress'); ?></p>
            </header>

            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="ap-search-global-form" style="margin-bottom: 24px;">
                <input type="hidden" name="page" value="alquipress-search">
                <input type="search" name="s" value="<?php echo esc_attr($query); ?>" placeholder="<?php esc_attr_e('Buscar propiedades, reservas, clientes...', 'alquipress'); ?>" style="width: 100%; max-width: 500px; padding: 12px 16px; font-size: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <button type="submit" class="button button-primary" style="margin-left: 8px; padding: 12px 20px;"><?php esc_html_e('Buscar', 'alquipress'); ?></button>
            </form>

            <?php if ($query === '') : ?>
                <p style="color: #64748b;"><?php esc_html_e('Escribe al menos 2 caracteres para buscar en propiedades, reservas y clientes.', 'alquipress'); ?></p>
            <?php elseif (strlen($query) < 2) : ?>
                <p style="color: #64748b;"><?php esc_html_e('Introduce al menos 2 caracteres.', 'alquipress'); ?></p>
            <?php else : ?>
                <div class="ap-search-results">
                    <section class="ap-search-section">
                        <h2><?php esc_html_e('Propiedades', 'alquipress'); ?></h2>
                        <?php if (empty($props)) : ?>
                            <p><?php esc_html_e('No hay propiedades que coincidan.', 'alquipress'); ?></p>
                        <?php else : ?>
                            <ul class="ap-search-list">
                                <?php foreach ($props as $p) : ?>
                                    <li>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=alquipress-edit-property&post_id=' . $p->ID)); ?>"><?php echo esc_html($p->post_title); ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                    <section class="ap-search-section">
                        <h2><?php esc_html_e('Reservas', 'alquipress'); ?></h2>
                        <?php if (empty($orders)) : ?>
                            <p><?php esc_html_e('No hay reservas que coincidan.', 'alquipress'); ?></p>
                        <?php else : ?>
                            <ul class="ap-search-list">
                                <?php foreach ($orders as $order) : ?>
                                    <li>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>">#<?php echo $order->get_id(); ?> – <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?> (<?php echo wc_price($order->get_total()); ?>)</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                    <section class="ap-search-section">
                        <h2><?php esc_html_e('Clientes', 'alquipress'); ?></h2>
                        <?php if (empty($clients)) : ?>
                            <p><?php esc_html_e('No hay clientes que coincidan.', 'alquipress'); ?></p>
                        <?php else : ?>
                            <ul class="ap-search-list">
                                <?php foreach ($clients as $u) : ?>
                                    <li>
                                        <a href="<?php echo esc_url(admin_url('users.php?page=alquipress-guest-profile&user_id=' . $u->ID)); ?>"><?php echo esc_html($u->display_name ?: $u->user_login); ?> (<?php echo esc_html($u->user_email); ?>)</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<style>
.ap-search-results { display: grid; gap: 24px; margin-top: 24px; }
.ap-search-section h2 { font-size: 18px; margin: 0 0 12px; color: var(--ap-text-primary, #0e161b); }
.ap-search-list { list-style: none; margin: 0; padding: 0; }
.ap-search-list li { padding: 8px 0; border-bottom: 1px solid var(--ap-border, #e8eef3); }
.ap-search-list li:last-child { border-bottom: 0; }
.ap-search-list a { color: var(--ap-primary, #2c99e2); text-decoration: none; font-weight: 500; }
.ap-search-list a:hover { text-decoration: underline; }
</style>
