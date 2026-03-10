const { test, expect } = require('@playwright/test');

test.describe('Owners Page Audit', () => {

    test.beforeEach(async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=alquipress-owners');
        await page.waitForLoadState('networkidle');
    });

    test('Verify Owner Metrics', async ({ page }) => {
        console.log('🔍 Verifying Owner metrics...');
        const metrics = page.locator('.ap-owners-metric-card');
        const count = await metrics.count();
        console.log(`   Found ${count} metric cards.`);
        expect(count).toBe(4);
        
        await expect(page.locator('.ap-owners-metric-label:has-text("Total propietarios")')).toBeVisible();
        await expect(page.locator('.ap-owners-metric-label:has-text("Propietarios activos")')).toBeVisible();
    });

    test('Verify Requires Attention Section', async ({ page }) => {
        console.log('🔍 Verifying Owners "Requires Attention" section...');
        const section = page.locator('.ap-owners-requires-attention');
        await expect(section).toBeVisible();
        
        const items = page.locator('.ap-owners-req-item');
        const itemCount = await items.count();
        console.log(`   Found ${itemCount} items requiring attention.`);
        
        if (itemCount > 0) {
            await expect(page.locator('.ap-owners-req-item-title').first()).toBeVisible();
        }
    });

    test('Verify Top Performing Owners', async ({ page }) => {
        console.log('🔍 Verifying Top Owners list...');
        const list = page.locator('.ap-owners-top-list');
        await expect(list).toBeVisible();
        
        const items = page.locator('.ap-owners-top-item');
        const itemCount = await items.count();
        console.log(`   Found ${itemCount} top performing owners.`);
    });

    test('Verify Quick Actions', async ({ page }) => {
        console.log('🔍 Verifying Quick Actions...');
        const actions = page.locator('.ap-owners-quick-actions');
        await expect(actions).toBeVisible();
        
        const addBtn = page.locator('.ap-owners-action-btn:has-text("Añadir propietario")');
        await expect(addBtn).toBeVisible();
    });

    test('Add Owner Button Navigation', async ({ page }) => {
        console.log('🔍 Testing "Add Owner" button...');
        const addBtn = page.locator('.ap-owners-action-btn:has-text("Añadir propietario")');
        await addBtn.click();
        await page.waitForLoadState('networkidle');
        
        await expect(page).toHaveURL(/post-new\.php\?post_type=propietario/);
        console.log('   Navigated to "Add New Owner" page.');
    });
});
