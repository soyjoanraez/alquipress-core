const { test, expect } = require('@playwright/test');

test.describe('Web Auditor Agent', () => {
    test.setTimeout(5 * 60 * 1000);

    const visitedUrls = new Set();
    const urlsToVisit = ['/'];
    const maxPages = 50;
    const baseUrl = 'http://alquipress.local';

    test('Crawl and Audit Site', async ({ page }) => {
        let pagesCount = 0;

        // Listen to console errors
        page.on('console', msg => {
            if (msg.type() === 'error') {
                console.error(`  ❌ [Console Error]: ${msg.text()}`);
            }
        });

        // Listen to failed requests
        page.on('requestfailed', request => {
            // Ignore some common external failures if any
            if (request.url().includes(baseUrl)) {
                console.error(`  ❌ [Request Failed]: ${request.url()} (${request.failure().errorText})`);
            }
        });

        while (urlsToVisit.length > 0 && pagesCount < maxPages) {
            const currentPath = urlsToVisit.shift();
            if (visitedUrls.has(currentPath)) continue;

            visitedUrls.add(currentPath);
            pagesCount++;

            console.log(`\n🔍 Auditing: ${currentPath}`);

            try {
                const response = await page.goto(currentPath, { waitUntil: 'networkidle', timeout: 30000 });

                // Check for server errors or 404s
                const status = response.status();
                if (status >= 400) {
                    console.error(`  ❌ [HTTP ${status}]: ${currentPath}`);
                }

                // Check for specific WordPress error strings in the page content
                const bodyText = await page.innerText('body');
                const errorKeywords = [
                    'Fatal error',
                    'Warning:',
                    'Stack trace:',
                    '404 Not Found',
                    'Error al establecer una conexión',
                    'There has been a critical error',
                    'Parse error'
                ];

                for (const keyword of errorKeywords) {
                    if (bodyText.includes(keyword)) {
                        console.error(`  ⚠️ [Potential PHP/WP Error Found]: "${keyword}" on ${currentPath}`);
                    }
                }

                // Extract internal links
                const links = await page.evaluate((base) => {
                    return Array.from(document.querySelectorAll('a'))
                        .map(a => a.getAttribute('href'))
                        .filter(href => {
                            if (!href) return false;
                            // Accept relative or absolute within base domain
                            const isInternal = href.startsWith('/') || href.includes(window.location.host);
                            // Ignore standard WP admin paths, logout, anchors and common binary files
                            const isIgnored =
                                href.includes('wp-admin') ||
                                href.includes('wp-login') ||
                                href.includes('logout') ||
                                href.includes('/wp-content/uploads/') ||
                                href.includes('#') ||
                                href.match(/\.(jpg|jpeg|png|gif|webp|svg|pdf|zip|css|js|xml|ico|mp4|webm)$/i);
                            return isInternal && !isIgnored;
                        })
                        .map(href => {
                            try {
                                const url = new URL(href, window.location.origin);
                                return url.pathname;
                            } catch (e) {
                                return null;
                            }
                        })
                        .filter(path => path !== null);
                }, baseUrl);

                for (const link of links) {
                    if (!visitedUrls.has(link) && !urlsToVisit.includes(link)) {
                        urlsToVisit.push(link);
                    }
                }

            } catch (error) {
                console.error(`  ❌ [Navigation Error]: Could not visit ${currentPath} - ${error.message}`);
            }
        }

        console.log(`\n✅ Audit finished.`);
        console.log(`Total pages audited: ${pagesCount}`);
        console.log(`Summary of paths reached: ${Array.from(visitedUrls).join(', ')}`);
    });
});
