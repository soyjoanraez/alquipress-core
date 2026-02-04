<?php
/**
 * Módulo: Vista Detallada de Propietario
 * Proporciona una ficha completa del propietario basada en ux_ui.md
 */

if (!defined('ABSPATH'))
    exit;

class Alquipress_Owner_Profile
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_owner_profile_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_owner_profile_page()
    {
        add_submenu_page(
            null,
            'Perfil del Propietario',
            'Perfil del Propietario',
            'edit_posts',
            'alquipress-owner-profile',
            [$this, 'render_owner_profile']
        );
    }

    public function enqueue_assets($hook)
    {
        if ($hook !== 'admin_page_alquipress-owner-profile')
            return;

        wp_enqueue_style(
            'alquipress-owner-profile-css',
            ALQUIPRESS_URL . 'includes/modules/crm-owners/assets/owner-profile.css',
            [],
            ALQUIPRESS_VERSION
        );

        // Cargar Chart.js para el reporte de ingresos
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
    }

    public function render_owner_profile()
    {
        $owner_id = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;
        $owner_post = get_post($owner_id);

        if (!$owner_post || $owner_post->post_type !== 'propietario') {
            echo '<div class="notice notice-error"><p>Propietario no encontrado.</p></div>';
            return;
        }

        // Obtener estadísticas usando el módulo de ingresos
        $revenue_mod = Alquipress_Owner_Revenue::get_instance();
        $stats = $revenue_mod->calculate_owner_revenue($owner_id);

        $email = get_post_meta($owner_id, 'owner_email_management', true);
        $phone = get_post_meta($owner_id, 'owner_phone', true);
        $iban = get_post_meta($owner_id, 'owner_iban', true);

        ?>
        <div class="wrap alquipress-owner-profile-wrap">
            <a href="edit.php?post_type=propietario" class="back-link">← Volver a Propietarios</a>

            <div class="owner-header">
                <div class="owner-icon">
                    <span class="dashicons dashicons-businessperson"></span>
                </div>
                <div class="owner-info-main">
                    <h1>
                        <?php echo esc_html($owner_post->post_title); ?>
                    </h1>
                    <div class="owner-badges">
                        <span class="badge-status">PROPIETARIO ACTIVO</span>
                        <span class="badge-commission">COMISIÓN:
                            <?php echo get_field('owner_commission_rate', $owner_id) ?: '0'; ?>%
                        </span>
                    </div>
                </div>
                <div class="owner-actions">
                    <a href="post.php?post=<?php echo $owner_id; ?>&action=edit" class="button">Editar Datos</a>
                    <button class="button button-primary">Liquidar Mes</button>
                </div>
            </div>

            <div class="owner-stats-grid">
                <div class="stat-card total">
                    <label>Ingresos Brutos</label>
                    <div class="value">
                        <?php echo wc_price($stats['total']); ?>
                    </div>
                </div>
                <div class="stat-card commission">
                    <label>Comisiones Alquipress</label>
                    <div class="value">
                        <?php echo wc_price($stats['commission']); ?>
                    </div>
                </div>
                <div class="stat-card net">
                    <label>Neto Liquidado</label>
                    <div class="value">
                        <?php echo wc_price($stats['net']); ?>
                    </div>
                </div>
            </div>

            <div class="owner-grid">
                <!-- Columna Principal -->
                <div class="owner-column-main">
                    <div class="owner-card">
                        <h3>🏠 Mis Propiedades</h3>
                        <div class="properties-list">
                            <?php if (!empty($stats['properties'])): ?>
                                <table class="widefat">
                                    <thead>
                                        <tr>
                                            <th>Propiedad</th>
                                            <th>Reservas</th>
                                            <th>Ingresos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['properties'] as $id => $data): ?>
                                            <tr>
                                                <td><strong>
                                                        <?php echo esc_html($data['name']); ?>
                                                    </strong></td>
                                                <td>
                                                    <?php echo $data['bookings']; ?>
                                                </td>
                                                <td>
                                                    <?php echo wc_price($data['revenue']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="empty-state">No hay propiedades asignadas.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="owner-card">
                        <h3>📈 Evolución de Ingresos</h3>
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Columna Lateral -->
                <div class="owner-column-side">
                    <div class="owner-card card-banking">
                        <h3>💳 Datos de Pago</h3>
                        <div class="info-list">
                            <div class="info-item">
                                <label>Email:</label>
                                <span>
                                    <?php echo esc_html($email ?: '-'); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Teléfono:</label>
                                <span>
                                    <?php echo esc_html($phone ?: '-'); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>IBAN:</label>
                                <span class="iban-masked">
                                    <?php echo $iban ? '****' . substr($iban, -4) : '-'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="owner-card card-documents">
                        <h3>📂 Documentación</h3>
                        <ul class="doc-list">
                            <li><span class="dashicons dashicons-pdf"></span> Contrato de Gestión.pdf</li>
                            <li><span class="dashicons dashicons-pdf"></span> DNI_Anverso.jpg</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('revenueChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Ingresos Netos (€)',
                            data: [1200, 1900, 1500, 2500, 2200, 3000],
                            borderColor: '#3b82f6',
                            tension: 0.4,
                            fill: true,
                            backgroundColor: 'rgba(59, 130, 246, 0.1)'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            });
        </script>
        <?php
    }
}

new Alquipress_Owner_Profile();
