const { test, expect } = require('@playwright/test');

test.describe('Clients Page Audit', () => {

    test.beforeEach(async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=alquipress-clients');
        await page.waitForLoadState('networkidle');
    });

    test('Verify Clients Table and Data', async ({ page }) => {
        console.log('🔍 Verifying Clients table...');
        
        const table = page.locator('.ap-clients-table');
        await expect(table).toBeVisible();
        
        const rows = page.locator('.ap-clients-table tbody tr');
        const rowCount = await rows.count();
        console.log(`   Found ${rowCount} rows in the clients table.`);
        
        if (rowCount > 0 && !((await rows.first().innerText()).includes('No hay clientes registrados'))) {
            // Check essential columns for the first row
            const firstRow = rows.first();
            await expect(firstRow.locator('strong')).toBeVisible(); // Name
            await expect(firstRow.locator('.ap-clients-email')).toBeVisible(); // Email
            console.log(`   First client found: ${await firstRow.locator('strong').innerText()}`);
        }
    });

    test('Verify Client Metrics', async ({ page }) => {
        console.log('🔍 Verifying Client metrics...');
        const metricCards = page.locator('.ap-clients-metric-card');
        const count = await metricCards.count();
        console.log(`   Found ${count} metric cards.`);
        expect(count).toBeGreaterThanOrEqual(3);
        
        await expect(page.locator('.ap-clients-metric-label:has-text("Total clientes")')).toBeVisible();
        await expect(page.locator('.ap-clients-metric-label:has-text("Con documentación")')).toBeVisible();
    });

    test('Search and Filter Logic', async ({ page }) => {
        console.log('🔍 Testing client search filter...');
        const searchInput = page.locator('#filter_name');
        await searchInput.fill('NonExistentCustomerNameXYZ');
        await page.locator('.ap-clients-btn-filter').click();
        await page.waitForLoadState('networkidle');
        
        await expect(page.locator('.ap-clients-empty')).toBeVisible();
        console.log('   Filter for non-existent client works correctly.');
    });

    test('Verify Documentation Status Badges', async ({ page }) => {
        console.log('🔍 Checking documentation badges...');
        const badges = page.locator('.ap-clients-doc-badge');
        if (await badges.count() > 0) {
            const firstBadge = badges.first();
            await expect(firstBadge).toBeVisible();
            const text = await firstBadge.innerText();
            console.log(`   Found documentation badge with text: ${text}`);
        }
    });
});
