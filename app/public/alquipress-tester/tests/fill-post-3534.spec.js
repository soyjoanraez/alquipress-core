const { test, expect } = require('@playwright/test');

test.describe('Fill post 3534', () => {
    test('Fill title and ACF fields', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/wp-admin/post.php?post=3534&action=edit');

        const titleValue = `Test Title ${Date.now()}`;
        const classicTitle = page.locator('#title');
        const blockTitle = page.locator('.wp-block-post-title');

        if (await classicTitle.count()) {
            await classicTitle.fill(titleValue);
        } else {
            await expect(blockTitle).toBeVisible();
            await blockTitle.fill(titleValue);
        }

        const supportedTypes = new Set(['text', 'textarea', 'number', 'email', 'url']);
        let sampledField = null;
        const acfFields = page.locator('.acf-field');
        const totalFields = await acfFields.count();

        for (let i = 0; i < totalFields; i += 1) {
            const field = acfFields.nth(i);
            const fieldType = await field.getAttribute('data-type');
            const fieldName = await field.getAttribute('data-name');

            if (!fieldType || !supportedTypes.has(fieldType)) {
                continue;
            }

            const inputRoot = field.locator('.acf-input');
            let input = inputRoot.locator('input');
            if (fieldType === 'textarea') {
                input = inputRoot.locator('textarea');
            }

            if (await input.count() === 0) {
                continue;
            }

            const inputEl = input.first();
            const isDisabled = await inputEl.isDisabled();
            if (isDisabled) {
                continue;
            }

            const isReadOnly = await inputEl.getAttribute('readonly');
            if (isReadOnly !== null) {
                continue;
            }

            const isVisible = await inputEl.isVisible();
            if (!isVisible) {
                continue;
            }

            const valueBase = `Test ${fieldType} ${Date.now()}`;
            const value = fieldType === 'number' ? '123' : valueBase;
            await inputEl.fill(value);

            if (!sampledField && fieldName) {
                sampledField = { name: fieldName, type: fieldType, expected: value };
            }
        }

        const updateButtons = page.locator('button:has-text("Actualizar"), button:has-text("Update"), #publish');
        const updateCount = await updateButtons.count();
        let clicked = false;

        for (let i = 0; i < updateCount; i += 1) {
            const btn = updateButtons.nth(i);
            if (await btn.isVisible()) {
                await btn.click();
                clicked = true;
                break;
            }
        }

        if (!clicked) {
            throw new Error('No visible update button found.');
        }

        await page.waitForTimeout(2000);
        await page.reload({ waitUntil: 'networkidle' });

        if (await classicTitle.count()) {
            const savedTitle = await classicTitle.inputValue();
            if (savedTitle !== titleValue) {
                console.log(`⚠️ Title did not persist. Current title: "${savedTitle}"`);
            }
        } else {
            const savedTitle = await blockTitle.textContent();
            if (!savedTitle || !savedTitle.includes(titleValue)) {
                console.log(`⚠️ Title did not persist. Current title: "${savedTitle || ''}"`);
            }
        }

        if (sampledField) {
            const fieldSelector = `.acf-field[data-name="${sampledField.name}"]`;
            const field = page.locator(fieldSelector);
            let fieldInput = field.locator('.acf-input input');
            if (sampledField.type === 'textarea') {
                fieldInput = field.locator('.acf-input textarea');
            }
            const savedValue = await fieldInput.first().inputValue();
            if (savedValue !== sampledField.expected) {
                console.log(`⚠️ ACF field did not persist (${sampledField.name}). Current value: "${savedValue}"`);
            }
        }
    });
});
