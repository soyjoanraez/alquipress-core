const { test, expect } = require('@playwright/test');

test.describe('Final Dashboard Sections Audit', () => {

    test('Pipeline Kanban Board Audit', async ({ page }) => {
        console.log('🔍 Auditing Pipeline Kanban...');
        await page.goto('/wp-admin/admin.php?page=alquipress-pipeline');
        await page.waitForLoadState('networkidle');

        // Check board and columns
        const board = page.locator('.alquipress-pipeline-board');
        await expect(board).toBeVisible();
        
        const columns = page.locator('.pipeline-column');
        const colCount = await columns.count();
        console.log(`   Found ${colCount} pipeline stages.`);
        expect(colCount).toBeGreaterThan(5);

        // Check tabs
        await expect(page.locator('.ap-tab:has-text("Reservas")')).toBeVisible();
        await expect(page.locator('.ap-tab:has-text("Cobros")')).toBeVisible();
    });

    test('Communications Section Audit', async ({ page }) => {
        console.log('🔍 Auditing Communications...');
        await page.goto('/wp-admin/admin.php?page=alquipress-comunicacion');
        await page.waitForLoadState('networkidle');

        // Check Send Email form
        await expect(page.locator('#ap-comm-send-form')).toBeVisible();
        await expect(page.locator('h2:has-text("Enviar email")')).toBeVisible();

        // Check History table
        await expect(page.locator('.ap-comm-table')).toBeVisible();
        
        // Check Sync button
        await expect(page.locator('button:has-text("Sincronizar bandeja")')).toBeVisible();
    });

    test('Settings Section Audit', async ({ page }) => {
        console.log('🔍 Auditing Settings...');
        await page.goto('/wp-admin/admin.php?page=alquipress-settings');
        await page.waitForLoadState('networkidle');

        // Check tabs
        const tabs = page.locator('.ap-settings-tab');
        expect(await tabs.count()).toBe(7);

        // Check Modules table (in General tab)
        await expect(page.locator('.ap-settings-modules-table')).toBeVisible();
        
        // Check System Status
        await expect(page.locator('.ap-settings-system')).toBeVisible();
        await expect(page.locator('.ap-settings-status-card:has-text("WordPress")')).toBeVisible();
    });
});
