const { test, expect } = require('@playwright/test');

test.describe('WordPress Admin Auditor', () => {

    test.beforeEach(async ({ page }) => {
        // El setup ya maneja la sesión, solo vamos a la admin
        await page.goto('/wp-admin/');
        await expect(page).toHaveTitle(/Escritorio/);
    });

    test('Audit Main Admin Dashboard', async ({ page }) => {
        console.log('🔍 Checking Admin Dashboard...');
        // Verificar que no hay errores fatales visibles
        const bodyContent = await page.innerText('body');
        expect(bodyContent).not.toContain('Fatal error');
        expect(bodyContent).not.toContain('Warning:');
    });

    test('Audit CRM Propietarios', async ({ page }) => {
        console.log('🔍 Checking CRM Propietarios...');
        await page.goto('/wp-admin/edit.php?post_type=propietario');

        // Verificar que la lista carga y tiene las columnas personalizadas
        await expect(page.locator('th#owner_email')).toBeVisible();
        await expect(page.locator('th#owner_phone')).toBeVisible();

        // Intentar entrar en "Añadir nuevo"
        await page.click('.page-title-action');
        await expect(page.locator('h1.wp-heading-inline')).toContainText('Añadir Nuevo Propietario');

        // Verificar que los campos de ACF están presentes (un selector genérico de ACF)
        const acfFields = page.locator('.acf-field');
        const count = await acfFields.count();
        console.log(`   Found ${count} ACF fields in Propietario editor`);
        expect(count).toBeGreaterThan(0);
    });

    test('Audit Custom Taxonomies', async ({ page }) => {
        console.log('🔍 Checking Taxonomies (Población, Zonas)...');

        // Check Población
        await page.goto('/wp-admin/edit-tags.php?taxonomy=poblacion&post_type=product');
        await expect(page.locator('h1.wp-heading-inline')).toContainText('Poblaciones');

        // Verificar si Alicante existe (se creó en el populate)
        // Filtramos por #the-list para evitar Query Monitor y usamos .first() por si acaso
        const alicanteRow = page.locator('#the-list tr:has-text("Alicante")');
        await expect(alicanteRow.first()).toBeVisible();

        // Check Zonas
        await page.goto('/wp-admin/edit-tags.php?taxonomy=zona&post_type=product');
        await expect(page.locator('h1.wp-heading-inline')).toContainText('Zonas');

        // Verificar si alguna zona existe (ej: "Playa")
        const zonaRow = page.locator('#the-list tr:has-text("Playa")');
        await expect(zonaRow.first()).toBeVisible();
    });

    test('Audit Critical Plugins & Health', async ({ page }) => {
        console.log('🔍 Checking Plugins Status...');
        await page.goto('/wp-admin/plugins.php');

        // Verificar que Alquipress Core está activo
        const corePlugin = page.locator('tr[data-slug="alquipress-core"]');
        await expect(corePlugin).toContainText('Desactivar'); // Si pone "Desactivar" es que está activo

        // Verificar WooCommerce Bookings
        const bookingsPlugin = page.locator('tr[data-slug="woocommerce-bookings"], tr[data-slug="woocommerce-com-woocommerce-bookings"]');
        await expect(bookingsPlugin.first()).toContainText('Desactivar');
    });

});
