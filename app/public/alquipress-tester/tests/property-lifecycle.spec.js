const { test, expect } = require('@playwright/test');

test.describe('Property Lifecycle & Deletion Test', () => {

    test.beforeEach(async ({ page }) => {
        // Asumimos que el setup de auth ya corrió.
        await page.goto('/wp-admin/edit.php?post_type=product');
    });

    test('Create and Delete Property (Product)', async ({ page }) => {
        test.setTimeout(60000);
        console.log('🏁 Starting Property Lifecycle Test...');

        // 1. Crear nuevo producto
        console.log('   Creating new test property...');
        await page.goto('/wp-admin/post-new.php?post_type=product');

        // Esperar a que cargue el editor
        const titleField = page.locator('#title, .wp-block-post-title');
        await expect(titleField).toBeVisible();

        const timestamp = Date.now();
        const testTitle = `Test Property ${timestamp}`;
        await titleField.fill(testTitle);

        // Publicar
        console.log('   Publishing...');
        const publishButton = page.locator('.editor-post-publish-button__button, #publish');
        await publishButton.click();

        // Gutenberg: Panel de confirmación
        const prePublishPanel = page.locator('.editor-post-publish-panel__header');
        try {
            await prePublishPanel.waitFor({ state: 'visible', timeout: 3000 });
            console.log('   Double-confirmation panel detected. Clicking confirm...');
            await page.locator('.editor-post-publish-button__button.is-primary').click();
        } catch (e) {
            console.log('   No pre-publish panel detected (or timed out), proceeding...');
        }

        // Esperar confirmación (o que desaparezca el botón de publicar/cambie a actualizar)
        try {
            await expect(page.locator('text=Producto publicado, text=Product published, .components-snackbar, #message')).toBeVisible({ timeout: 5000 });
            console.log('   ✅ Success message appeared.');
        } catch (e) {
            console.log('   ⚠️ Alert: Success message missed. Checking status...');
            const updateButton = page.locator('button:has-text("Actualizar"), button:has-text("Update"), input[value="Actualizar"]');
            if (await updateButton.count() > 0) {
                console.log('   ✅ "Update" button found. Product is published.');
            } else {
                console.log('   ⚠️ Could not confirm publish via UI. Assuming it might have worked, checking list...');
            }
        }

        const postIdInput = page.locator('input#post_ID');
        let postId = null;
        if (await postIdInput.count()) {
            postId = await postIdInput.inputValue();
        } else {
            const postIdMatch = page.url().match(/post=(\d+)/);
            postId = postIdMatch ? postIdMatch[1] : null;
        }

        // 2. Ir al listado y buscarlo
        console.log('   Going to list view to attempt deletion...');
        await page.goto('/wp-admin/edit.php?post_type=product');

        // Buscar el elemento en la lista
        const searchBox = page.locator('#post-search-input');
        await searchBox.fill(testTitle);
        await page.locator('#search-submit').click();
        await page.waitForURL(url => url.searchParams.get('s') === testTitle);

        // Localizar la fila
        const row = page.locator(`#the-list tr:has-text("${testTitle}")`).first();
        // Si no está visible, intentamos recargar una vez
        try {
            await expect(row).toBeVisible({ timeout: 8000 });
        } catch (e) {
            await page.reload();
            try {
                await expect(row).toBeVisible({ timeout: 8000 });
            } catch (e2) {
                if (postId) {
                    console.log(`   ⚠️ Not found in list, deleting directly by ID ${postId}...`);
                    await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
                    const moveToTrash = page.locator('#delete-action a.submitdelete, #delete-action a:has-text("Mover a la papelera"), #delete-action a:has-text("Move to Trash")');
                    await expect(moveToTrash).toBeVisible({ timeout: 8000 });
                    await moveToTrash.click();
                    await expect(page.locator('#message')).toBeVisible({ timeout: 8000 });
                    return;
                }
                throw e2;
            }
        }

        // 3. Intentar borrar (Mover a la papelera)
        console.log('   Attempting to move to Trash from Quick Actions...');

        await row.hover();
        const trashLink = row.locator('a.submitdelete');

        // Listener para errores
        page.on('dialog', dialog => dialog.accept()); // Aceptar alertas JS

        // Clic en papelera
        await trashLink.click();

        // 4. Verificar resultado
        console.log('   Verifying deletion...');

        // Esperamos un momento porque puede ser AJAX
        await page.waitForTimeout(2000);

        const isRowGone = await row.isHidden();
        if (isRowGone) {
            console.log('   ✅ Row disappeared from list. Deletion likely successful.');
        } else {
            // Puede que necesite recarga si no fue AJAX
            await page.reload();
            const rowAfterReload = page.locator(`#the-list tr:has-text("${testTitle}")`).first();
            if (await rowAfterReload.count() === 0) {
                console.log('   ✅ Row gone after reload.');
            } else {
                console.error('   ❌ Row STILL present. Deletion FAILED.');
                // Ver si hay mensaje de error en la página
                const errorMsg = await page.locator('.error').innerText().catch(() => '');
                if (errorMsg) console.error(`   ❌ Error Message on page: ${errorMsg}`);
                throw new Error('Deletion failed: Item verified as still present.');
            }
        }
    });
});
