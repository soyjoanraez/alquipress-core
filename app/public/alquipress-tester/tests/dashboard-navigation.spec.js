const { test, expect } = require('@playwright/test');

test.describe('Dashboard Navigation Test', () => {

    test.beforeEach(async ({ page }) => {
        // Alquipress Dashboard is the new landing for wp-admin
        await page.goto('/wp-admin/admin.php?page=alquipress-dashboard');
        // Wait for the page to load
        await page.waitForLoadState('networkidle');
    });

    const pages = [
        { name: 'Panel Principal', url: '/wp-admin/admin.php?page=alquipress-dashboard', title: /Panel/ },
        { name: 'Propiedades', url: '/wp-admin/admin.php?page=alquipress-properties', title: /Propiedades/ },
        { name: 'Reservas', url: '/wp-admin/admin.php?page=alquipress-bookings', title: /Reservas/ },
        { name: 'Clientes', url: '/wp-admin/admin.php?page=alquipress-clients', title: /Clientes/ },
        { name: 'Propietarios', url: '/wp-admin/admin.php?page=alquipress-owners', title: /Propietarios/ },
        { name: 'Finanzas', url: '/wp-admin/admin.php?page=alquipress-finanzas', title: /Finanzas/ },
        { name: 'Informes', url: '/wp-admin/admin.php?page=alquipress-reports', title: /Informes/ },
        { name: 'Ajustes', url: '/wp-admin/admin.php?page=alquipress-settings', title: /Ajustes/ },
    ];

    for (const p of pages) {
        test(`Navigate to ${p.name}`, async ({ page }) => {
            console.log(`🔍 Navigating to ${p.name}...`);
            await page.goto(p.url);
            await page.waitForLoadState('networkidle');

            // Check for fatal errors or warnings
            const bodyContent = await page.innerText('body');
            expect(bodyContent).not.toContain('Fatal error');
            expect(bodyContent).not.toContain('Warning:');
            expect(bodyContent).not.toContain('Sección no disponible');

            // Take screenshot
            await page.screenshot({ path: `../../output/playwright/nav-${p.name.toLowerCase().replace(/ /g, '-')}.png`, fullPage: true });
            
            console.log(`✅ ${p.name} loaded correctly.`);
        });
    }

    test('Audit Standard WordPress Lists', async ({ page }) => {
        const wpPages = [
            { name: 'WP-Products', url: '/wp-admin/edit.php?post_type=product' },
            { name: 'WP-Bookings', url: '/wp-admin/edit.php?post_type=wc_booking' },
            { name: 'WP-Owners', url: '/wp-admin/edit.php?post_type=propietario' },
            { name: 'WP-Users', url: '/wp-admin/users.php' }
        ];

        for (const p of wpPages) {
            console.log(`🔍 Checking standard WP list: ${p.name}...`);
            await page.goto(p.url);
            await page.waitForLoadState('networkidle');
            
            const bodyContent = await page.innerText('body');
            expect(bodyContent).not.toContain('Fatal error');
            expect(bodyContent).not.toContain('Warning:');
            
            await page.screenshot({ path: `../../output/playwright/nav-${p.name.toLowerCase()}.png`, fullPage: true });
        }
    });
});
