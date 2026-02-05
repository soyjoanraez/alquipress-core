<?php
/**
 * Sidebar compartido para todas las páginas del admin Alquipress (Panel, Propiedades, Reservas, Propietarios, Informes, Ajustes).
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Devuelve el SVG de un icono para la navegación del sidebar.
 *
 * @param string $name  Nombre del icono (layout-dashboard, building, calendar, briefcase, wallet, bar-chart, settings, building-2).
 * @param string $class Clase CSS (por defecto ap-owners-icon).
 * @return string HTML del SVG.
 */
function alquipress_sidebar_icon_svg($name, $class = 'ap-owners-icon')
{
    $icons = [
        'layout-dashboard' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="8" height="9" rx="1"/><rect x="13" y="3" width="8" height="5" rx="1"/><rect x="13" y="10" width="8" height="11" rx="1"/><rect x="3" y="14" width="8" height="7" rx="1"/></svg>',
        'building' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h3"/><path d="M14 7h3"/><path d="M7 12h3"/><path d="M14 12h3"/><path d="M7 17h3"/><path d="M14 17h3"/></svg>',
        'calendar' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'briefcase' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M2 12h20"/></svg>',
        'wallet' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/><path d="M16 12h2"/></svg>',
        'credit-card' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>',
        'bar-chart' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><line x1="6" y1="20" x2="6" y2="14"/><line x1="12" y1="20" x2="12" y2="8"/><line x1="18" y1="20" x2="18" y2="4"/></svg>',
        'settings' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V22a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-.4-1.1 1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H2a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.1-.4 1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V2a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 .4 1.1 1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.25.34.45.71.6 1.1.1.33.35.56.7.6H22a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.1.4c-.34.25-.56.6-.6.9Z"/></svg>',
        'building-2' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"/><path d="M6 12h12"/><path d="M10 6h4"/><path d="M10 16h4"/></svg>',
        'columns' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="9.5" y="3" width="5" height="18" rx="1"/><rect x="16" y="3" width="5" height="18" rx="1"/></svg>',
        'mail' => '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    ];
    return isset($icons[$name]) ? $icons[$name] : '';
}

/**
 * Iniciales del usuario actual para el avatar del sidebar.
 *
 * @return string Dos letras (ej. NA, JR).
 */
function alquipress_sidebar_user_initials()
{
    $user = wp_get_current_user();
    $name = $user->display_name ?: $user->user_login;
    $name = trim((string) $name);
    if ($name === '') {
        return 'NA';
    }
    $parts = preg_split('/\s+/', $name);
    if (empty($parts)) {
        return strtoupper(substr($name, 0, 2));
    }
    $first = strtoupper(substr($parts[0], 0, 1));
    $last = count($parts) > 1 ? strtoupper(substr($parts[count($parts) - 1], 0, 1)) : '';
    return $first . $last;
}

/**
 * Etiqueta del rol del usuario para mostrar en el sidebar.
 *
 * @return string
 */
function alquipress_sidebar_user_role_label()
{
    $user = wp_get_current_user();
    $role_key = !empty($user->roles[0]) ? $user->roles[0] : '';
    $role_map = [
        'administrator' => __('Administrador', 'alquipress'),
        'editor' => __('Editor', 'alquipress'),
        'author' => __('Autor', 'alquipress'),
        'contributor' => __('Colaborador', 'alquipress'),
        'subscriber' => __('Suscriptor', 'alquipress'),
        'shop_manager' => __('Gestor de tienda', 'alquipress'),
    ];
    return isset($role_map[$role_key]) ? $role_map[$role_key] : ucfirst($role_key);
}

/**
 * Renderiza el sidebar de navegación de Alquipress (mismo diseño en Panel, Propiedades, Reservas, Propietarios, Informes, Pipeline, Ajustes).
 *
 * @param string $current_page Clave de la página activa: 'dashboard' | 'properties' | 'bookings' | 'clients' | 'owners' | 'finances' | 'reports' | 'pipeline' | 'settings'.
 */
function alquipress_render_sidebar($current_page)
{
    $user_name = wp_get_current_user()->display_name ?: wp_get_current_user()->user_login;
    $items = [
        'dashboard' => [
            'url' => admin_url('admin.php?page=alquipress-dashboard'),
            'label' => __('Panel', 'alquipress'),
            'icon' => 'layout-dashboard',
        ],
        'properties' => [
            'url' => admin_url('admin.php?page=alquipress-properties'),
            'label' => __('Propiedades', 'alquipress'),
            'icon' => 'building',
        ],
        'bookings' => [
            'url' => admin_url('admin.php?page=alquipress-bookings'),
            'label' => __('Reservas', 'alquipress'),
            'icon' => 'calendar',
        ],
        'clients' => [
            'url' => admin_url('admin.php?page=alquipress-clients'),
            'label' => __('Clientes', 'alquipress'),
            'icon' => 'building-2',
        ],
        'owners' => [
            'url' => admin_url('admin.php?page=alquipress-owners'),
            'label' => __('Propietarios', 'alquipress'),
            'icon' => 'briefcase',
        ],
        'finances' => [
            'url' => admin_url('admin.php?page=alquipress-finanzas'),
            'label' => __('Finanzas', 'alquipress'),
            'icon' => 'wallet',
        ],
        'reports' => [
            'url' => admin_url('admin.php?page=alquipress-reports'),
            'label' => __('Informes', 'alquipress'),
            'icon' => 'bar-chart',
        ],
        'payment-pipeline' => [
            'url' => admin_url('admin.php?page=alquipress-payment-pipeline'),
            'label' => __('Cobros', 'alquipress'),
            'icon' => 'wallet',
        ],
        'pipeline' => [
            'url' => admin_url('admin.php?page=alquipress-pipeline'),
            'label' => __('Pipeline', 'alquipress'),
            'icon' => 'columns',
        ],
        'payment-pipeline' => [
            'url' => admin_url('admin.php?page=alquipress-payment-pipeline'),
            'label' => __('Pipeline de Cobros', 'alquipress'),
            'icon' => 'credit-card',
        ],
        'communications' => [
            'url' => admin_url('admin.php?page=alquipress-comunicacion'),
            'label' => __('Comunicación', 'alquipress'),
            'icon' => 'mail',
        ],
        'settings' => [
            'url' => admin_url('admin.php?page=alquipress-settings'),
            'label' => __('Ajustes', 'alquipress'),
            'icon' => 'settings',
        ],
    ];
    ?>
    <aside class="ap-owners-sidebar">
        <div class="ap-owners-logo">
            <div class="ap-owners-logo-icon">
                <?php echo alquipress_sidebar_icon_svg('building-2', 'ap-owners-icon ap-owners-icon-inverse'); ?>
            </div>
            <div class="ap-owners-logo-text">
                <span class="ap-owners-logo-name">ALQUIPRESS</span>
                <span class="ap-owners-logo-sub"><?php esc_html_e('Inmobiliaria', 'alquipress'); ?></span>
            </div>
        </div>
        <nav class="ap-owners-nav">
            <?php foreach ($items as $key => $item) : ?>
                <a class="ap-owners-nav-item <?php echo $key === $current_page ? 'is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>">
                    <?php echo alquipress_sidebar_icon_svg($item['icon']); ?>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="ap-owners-sidebar-spacer"></div>
        <div class="ap-owners-user">
            <div class="ap-owners-avatar"><?php echo esc_html(alquipress_sidebar_user_initials()); ?></div>
            <div class="ap-owners-user-info">
                <span class="ap-owners-user-name"><?php echo esc_html($user_name); ?></span>
                <span class="ap-owners-user-role"><?php echo esc_html(alquipress_sidebar_user_role_label()); ?></span>
            </div>
        </div>
    </aside>
    <?php
}
