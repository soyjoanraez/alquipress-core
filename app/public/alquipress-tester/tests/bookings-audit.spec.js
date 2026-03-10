const { test, expect } = require('@playwright/test');

test.describe('Bookings Dashboard Audit', () => {

    test.beforeEach(async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=alquipress-bookings');
        await page.waitForLoadState('networkidle');
    });

    test('Verify KPI Cards', async ({ page }) => {
        console.log('🔍 Verifying Bookings KPIs...');
        
        const kpiRow = page.locator('.ap-bookings-kpi-row');
        await expect(kpiRow).toBeVisible();
        
        const cards = page.locator('.ap-bookings-kpi-card');
        const count = await cards.count();
        console.log(`   Found ${count} KPI cards.`);
        expect(count).toBe(4);
        
        // Check specific KPIs
        await expect(page.locator('.ap-bookings-kpi-label:has-text("Reservas activas")')).toBeVisible();
        await expect(page.locator('.ap-bookings-kpi-label:has-text("Check-ins esta semana")')).toBeVisible();
        await expect(page.locator('.ap-bookings-kpi-label:has-text("Ingresos este mes")')).toBeVisible();
        await expect(page.locator('.ap-bookings-kpi-label:has-text("Check-outs esta semana")')).toBeVisible();
    });

    test('Requires Attention Section', async ({ page }) => {
        console.log('🔍 Verifying "Requires Attention" section...');
        const section = page.locator('.ap-bookings-requires-attention');
        await expect(section).toBeVisible();
        
        const alertItems = page.locator('.ap-bookings-alert-item');
        const alertCount = await alertItems.count();
        console.log(`   Found ${alertCount} items requiring attention.`);
        
        if (alertCount > 0) {
            await expect(page.locator('.ap-bookings-alert-item-title').first()).toBeVisible();
            await expect(page.locator('.ap-bookings-alert-btn').first()).toBeVisible();
        }
    });

    test('Recent Bookings Table', async ({ page }) => {
        console.log('🔍 Verifying Recent Bookings table...');
        const table = page.locator('.ap-bookings-table');
        await expect(table).toBeVisible();
        
        const rows = page.locator('.ap-bookings-table tbody tr');
        const rowCount = await rows.count();
        console.log(`   Found ${rowCount} recent bookings in the table.`);
        
        if (rowCount > 0 && !((await rows.first().innerText()).includes('No hay reservas recientes'))) {
            await expect(page.locator('.ap-bookings-badge').first()).toBeVisible();
        }
    });

    test('Navigation Tabs', async ({ page }) => {
        console.log('🔍 Verifying navigation tabs...');
        const nav = page.locator('.ap-bookings-tabs-nav');
        await expect(nav).toBeVisible();
        
        const tabs = [
            { name: 'Resumen', url: /page=alquipress-bookings/ },
            { name: 'Pipeline', url: /page=alquipress-pipeline/ }
        ];
        
        for (const tab of tabs) {
            const tabLink = page.locator(`.ap-bookings-tab:has-text("${tab.name}")`);
            await expect(tabLink).toBeVisible();
        }
    });
});
