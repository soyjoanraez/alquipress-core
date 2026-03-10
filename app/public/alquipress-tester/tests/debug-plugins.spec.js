const { test, expect } = require('@playwright/test');

test('Log Plugins', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    const slugs = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('tr[data-slug]')).map(tr => tr.getAttribute('data-slug'));
    });
    console.log('Available slugs:', slugs);
});
