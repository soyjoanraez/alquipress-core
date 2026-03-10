const { test, expect } = require('@playwright/test');

test.describe('Properties Page Audit', () => {

    test.beforeEach(async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=alquipress-properties');
        await page.waitForLoadState('networkidle');
    });

    test('Verify Property Listing', async ({ page }) => {
        console.log('🔍 Verifying property listing...');
        
        // Check if the grid or empty state is visible
        const grid = page.locator('.ap-props-grid');
        const emptyState = page.locator('.ap-props-empty');
        
        if (await grid.isVisible()) {
            const cardCount = await page.locator('.ap-props-card').count();
            console.log(`   Found ${cardCount} properties.`);
            expect(cardCount).toBeGreaterThan(0);
            
            // Check first card content
            const firstCardTitle = page.locator('.ap-props-card-title').first();
            await expect(firstCardTitle).toBeVisible();
            console.log(`   First property: ${await firstCardTitle.innerText()}`);
        } else if (await emptyState.isVisible()) {
            console.log('   No properties found (Empty State).');
        } else {
            throw new Error('Neither grid nor empty state is visible');
        }
    });

    test('Search Functionality', async ({ page }) => {
        console.log('🔍 Testing search...');
        const searchInput = page.locator('.ap-props-search-input');
        await searchInput.fill('PropiedadInexistentexyz123');
        await page.keyboard.press('Enter');
        await page.waitForLoadState('networkidle');
        
        // Should show empty state
        await expect(page.locator('.ap-props-empty')).toBeVisible();
        console.log('   Search for non-existent property works (shows empty state).');
    });

    test('Filter Panel Toggle', async ({ page }) => {
        console.log('🔍 Testing filter panel toggle...');
        const toggleBtn = page.locator('#ap-props-filter-toggle');
        const filterPanel = page.locator('#ap-props-filter-panel');
        
        // Initial state
        const isHidden = await filterPanel.getAttribute('hidden') !== null;
        console.log(`   Panel initially hidden: ${isHidden}`);
        
        await toggleBtn.click();
        await expect(filterPanel).not.toHaveAttribute('hidden', '');
        console.log('   Panel visible after click.');
        
        await toggleBtn.click();
        await expect(filterPanel).toHaveAttribute('hidden', '');
        console.log('   Panel hidden after second click.');
    });

    test('Add Property Button Navigation', async ({ page }) => {
        console.log('🔍 Testing "Add Property" button...');
        const addBtn = page.locator('.ap-props-add-btn');
        await addBtn.click();
        await page.waitForLoadState('networkidle');
        
        // Should navigate to standard WP product editor or custom editor
        await expect(page).toHaveURL(/post-new\.php\?post_type=product/);
        await expect(page.locator('h1.wp-heading-inline')).toContainText(/Añadir/i);
        console.log('   Navigated to "Add New Property" page.');
    });

    test('Property Edit Navigation', async ({ page }) => {
        if (await page.locator('.ap-props-card').first().isVisible()) {
            console.log('🔍 Testing "Edit Property" link...');
            const firstCardEditLink = page.locator('.ap-props-card-title a').first();
            const title = await firstCardEditLink.innerText();
            await firstCardEditLink.click();
            await page.waitForLoadState('networkidle');
            
            // Should navigate to custom editor
            await expect(page).toHaveURL(/page=alquipress-edit-property/);
            console.log(`   Navigated to edit page for: ${title}`);
            
            await page.screenshot({ path: `../../output/playwright/edit-property-view.png`, fullPage: true });
        } else {
            console.log('   Skipping edit test: No properties available.');
        }
    });
});
