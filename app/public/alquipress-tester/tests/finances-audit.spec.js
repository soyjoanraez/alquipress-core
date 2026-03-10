const { test, expect } = require('@playwright/test');

test.describe('Finances Page Audit', () => {

    test.beforeEach(async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=alquipress-finanzas');
        await page.waitForLoadState('networkidle');
    });

    test('Verify Financial KPIs', async ({ page }) => {
        console.log('🔍 Verifying Finance KPIs...');
        const metrics = page.locator('.ap-metric-card');
        const count = await metrics.count();
        console.log(`   Found ${count} financial KPI cards.`);
        expect(count).toBeGreaterThanOrEqual(2);
        
        await expect(page.locator('.ap-metric-label:has-text("Ingresos Brutos")')).toBeVisible();
        await expect(page.locator('.ap-metric-label:has-text("Saldos Pendientes")')).toBeVisible();
    });

    test('Verify Automatic Payments Table', async ({ page }) => {
        console.log('🔍 Verifying upcoming payments table...');
        const table = page.locator('.ap-bookings-table');
        await expect(table).toBeVisible();
        
        const rows = page.locator('.ap-bookings-table tbody tr');
        if (await rows.count() > 0) {
            const firstRowText = await rows.first().innerText();
            if (!firstRowText.includes('No hay cobros programados')) {
                await expect(rows.first().locator('strong')).toBeVisible();
                console.log('   Upcoming payments table has data.');
            } else {
                console.log('   Upcoming payments table is empty (as expected if no scheduled payments).');
            }
        }
    });

    test('Verify Financial Tools', async ({ page }) => {
        console.log('🔍 Verifying financial tools...');
        const toolsSection = page.locator('.ap-recent-activity:has-text("Herramientas")');
        await expect(toolsSection).toBeVisible();
        
        const stripeBtn = page.locator('a:has-text("Configuración Stripe")');
        await expect(stripeBtn).toBeVisible();
    });
});
