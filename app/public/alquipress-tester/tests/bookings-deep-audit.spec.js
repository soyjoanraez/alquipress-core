const { test, expect } = require('@playwright/test');

test.describe('Bookings Core Functionality Audit', () => {

    test('Standard WooCommerce Booking Creation', async ({ page }) => {
        console.log('🔍 Testing manual booking creation in standard WP...');
        await page.goto('/wp-admin/post-new.php?post_type=wc_booking');
        await page.waitForLoadState('networkidle');

        // Check if essential fields exist in standard WC Bookings
        await expect(page.locator('select#_booking_product_id')).toBeVisible();
        await expect(page.locator('input[name="booking_date"]')).toBeVisible();
        console.log('   Manual booking creation page is functional.');
    });

    test('Booking Enforcer in Property Editor', async ({ page }) => {
        console.log('🔍 Testing Booking Enforcer in Property Editor...');
        await page.goto('/wp-admin/post-new.php?post_type=product');
        await page.waitForLoadState('networkidle');

        // Enforcer should hide other product types and force 'booking'
        const productType = await page.locator('select#product-type').inputValue();
        expect(productType).toBe('booking');
        
        // Check if virtual is checked and hidden (or at least checked)
        const isVirtual = await page.locator('#_virtual').isChecked();
        expect(isVirtual).toBe(true);
        
        console.log('   Booking Enforcer correctly pre-sets product type and virtual flag.');
    });

    test('Kyero Public Feed Accessibility', async ({ page }) => {
        console.log('🔍 Checking Kyero XML feed accessibility...');
        // The feed is at /kyero-feed.xml
        const response = await page.goto('/kyero-feed.xml');
        expect(response.status()).toBe(200);
        
        const contentType = response.headers()['content-type'];
        expect(contentType).toContain('xml');
        
        const content = await response.text();
        expect(content).toContain('<?xml');
        console.log('   Kyero XML feed is accessible and serves XML content.');
    });

    test('Kyero Admin Sync Page', async ({ page }) => {
        console.log('🔍 Checking Kyero Sync admin page...');
        await page.goto('/wp-admin/admin.php?page=alquipress-kyero');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('h1:has-text("Feed Kyero")')).toBeVisible();
        await expect(page.locator('button:has-text("Generar Feed Ahora")')).toBeVisible();
        await expect(page.locator('button:has-text("Guardar Configuración")')).toBeVisible();
        console.log('   Kyero Sync admin page UI is correct.');
    });
});
