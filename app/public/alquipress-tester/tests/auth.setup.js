const { test: setup, expect } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

const envPath = path.join(__dirname, '../.env');
let envFromFile = {};

if (fs.existsSync(envPath)) {
    const rawEnv = fs.readFileSync(envPath, 'utf8');
    envFromFile = rawEnv
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter((line) => line && !line.startsWith('#') && line.includes('='))
        .reduce((acc, line) => {
            const index = line.indexOf('=');
            const key = line.slice(0, index).trim();
            const value = line.slice(index + 1).trim();
            acc[key] = value;
            return acc;
        }, {});
}

const adminUser = envFromFile.WP_ADMIN_USER || process.env.WP_ADMIN_USER;
const adminPass = envFromFile.WP_ADMIN_PASS || process.env.WP_ADMIN_PASS;

const authFile = path.join(__dirname, '../playwright/.auth/user.json');

setup('authenticate', async ({ page }) => {
    await page.goto('/wp-login.php');
    await page.fill('#user_login', adminUser);
    await page.fill('#user_pass', adminPass);
    await page.click('#wp-submit');

    try {
        await page.waitForURL(url => url.pathname.includes('/wp-admin/'), { timeout: 15000 });
    } catch (e) {
        const errorMsg = await page.locator('#login_error').innerText().catch(() => 'No error message found');
        console.error('❌ Login failed:', errorMsg);
        throw e;
    }
    
    // Wait for the admin bar to be present in the DOM, even if hidden initially
    await page.waitForSelector('#wp-admin-bar-my-account', { state: 'attached', timeout: 10000 });
    
    await page.context().storageState({ path: authFile });
});
