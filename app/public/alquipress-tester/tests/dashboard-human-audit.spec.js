const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const DASHBOARD_URL = '/wp-admin/admin.php?page=alquipress-dashboard';

function jitter(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

async function humanPause(page, min = 250, max = 900) {
  await page.waitForTimeout(jitter(min, max));
}

async function gotoDashboard(page) {
  let lastError = null;
  for (let i = 0; i < 3; i++) {
    try {
      await page.goto(DASHBOARD_URL, { waitUntil: 'domcontentloaded', timeout: 25000 });
      return;
    } catch (error) {
      lastError = error;
      await page.waitForTimeout(500);
    }
  }
  throw lastError;
}

async function collectDashboardTargets(page) {
  return page.evaluate(() => {
    const root =
      document.querySelector('.ap-owners-main') ||
      document.querySelector('main') ||
      document.body;

    const selector =
      'a[href], button, [role="button"], input[type="button"], input[type="submit"]';
    const nodes = Array.from(root.querySelectorAll(selector));

    const out = [];
    let i = 0;

    for (const el of nodes) {
      if (!el || !el.isConnected) continue;
      if (el.closest('#wpadminbar, #adminmenuwrap, #adminmenuback')) continue;

      const style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden') continue;

      const rect = el.getBoundingClientRect();
      if (rect.width < 8 || rect.height < 8) continue;

      if (el.hasAttribute('disabled') || el.getAttribute('aria-disabled') === 'true') continue;

      const id = `ap-human-audit-${i++}`;
      el.setAttribute('data-ap-human-audit-id', id);

      const text =
        (el.innerText || el.value || el.getAttribute('aria-label') || el.getAttribute('title') || '')
          .trim()
          .replace(/\s+/g, ' ')
          .slice(0, 180);

      const hrefRaw = el.getAttribute('href') || '';
      const href = el.tagName.toLowerCase() === 'a' ? (el.href || hrefRaw) : hrefRaw;

      out.push({
        id,
        tag: el.tagName.toLowerCase(),
        text,
        href,
      });
    }

    return out;
  });
}

test.describe('Dashboard Human Audit', () => {
  test('audits visible buttons and links like a human user', async ({ page, context }) => {
    test.setTimeout(10 * 60 * 1000);

    const reportDir = path.resolve(__dirname, '../../output/playwright/dashboard-human-audit');
    fs.mkdirSync(reportDir, { recursive: true });

    const consoleIssues = [];
    const requestIssues = [];

    page.on('console', (msg) => {
      if (msg.type() === 'error' || msg.type() === 'warning') {
        consoleIssues.push({
          type: msg.type(),
          text: msg.text(),
          url: page.url(),
          time: new Date().toISOString(),
        });
      }
    });

    page.on('requestfailed', (request) => {
      requestIssues.push({
        type: 'requestfailed',
        url: request.url(),
        method: request.method(),
        failure: request.failure() ? request.failure().errorText : 'unknown',
        pageUrl: page.url(),
        time: new Date().toISOString(),
      });
    });

    page.on('response', (response) => {
      if (response.status() >= 400) {
        requestIssues.push({
          type: 'response',
          url: response.url(),
          status: response.status(),
          statusText: response.statusText(),
          pageUrl: page.url(),
          time: new Date().toISOString(),
        });
      }
    });

    await gotoDashboard(page);
    await expect(page).toHaveURL(/alquipress-dashboard/);
    await humanPause(page, 600, 1200);

    const rawTargets = await collectDashboardTargets(page);
    const counters = new Map();
    const targets = rawTargets
      .filter((t) => (t.text || '').toLowerCase() !== 'descartar este aviso.')
      .map((t) => {
        const key = `${t.tag}|${t.text}|${t.href}`;
        const occurrence = counters.get(key) || 0;
        counters.set(key, occurrence + 1);
        return { ...t, key, occurrence };
      });
    const failures = [];
    const successes = [];

    for (const target of targets) {
      await gotoDashboard(page);
      await humanPause(page, 350, 850);

      const liveId = `ap-human-live-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
      const found = await page.evaluate(
        ({ target, liveId }) => {
          const root =
            document.querySelector('.ap-owners-main') ||
            document.querySelector('main') ||
            document.body;

          const selector =
            'a[href], button, [role="button"], input[type="button"], input[type="submit"]';
          const nodes = Array.from(root.querySelectorAll(selector));

          const normalized = [];
          for (const el of nodes) {
            if (!el || !el.isConnected) continue;
            if (el.closest('#wpadminbar, #adminmenuwrap, #adminmenuback')) continue;

            const style = window.getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden') continue;

            const rect = el.getBoundingClientRect();
            if (rect.width < 8 || rect.height < 8) continue;
            if (el.hasAttribute('disabled') || el.getAttribute('aria-disabled') === 'true') continue;

            const text =
              (el.innerText ||
                el.value ||
                el.getAttribute('aria-label') ||
                el.getAttribute('title') ||
                '')
                .trim()
                .replace(/\s+/g, ' ')
                .slice(0, 180);

            const hrefRaw = el.getAttribute('href') || '';
            const href = el.tagName.toLowerCase() === 'a' ? (el.href || hrefRaw) : hrefRaw;
            const tag = el.tagName.toLowerCase();
            const key = `${tag}|${text}|${href}`;

            normalized.push({ el, key });
          }

          const matched = normalized.filter((x) => x.key === target.key);
          if (!matched.length || !matched[target.occurrence]) {
            return false;
          }
          matched[target.occurrence].el.setAttribute('data-ap-human-live-id', liveId);
          return true;
        },
        { target, liveId }
      );

      if (!found) {
        failures.push({
          ...target,
          reason: 'not_found_in_fresh_dashboard',
          url: page.url(),
        });
        continue;
      }

      const selector = `[data-ap-human-live-id="${liveId}"]`;
      const locator = page.locator(selector);

      try {
        await locator.scrollIntoViewIfNeeded();
        const box = await locator.boundingBox();
        if (box) {
          await page.mouse.move(
            box.x + Math.min(Math.max(6, box.width / 2), box.width - 6),
            box.y + Math.min(Math.max(6, box.height / 2), box.height - 6),
            { steps: jitter(8, 20) }
          );
        }

        await humanPause(page, 180, 420);

        const beforeUrl = page.url();
        const popupPromise = context.waitForEvent('page', { timeout: 1200 }).catch(() => null);

        await locator.click({ timeout: 6000 });
        await humanPause(page, 450, 1100);

        const popup = await popupPromise;
        if (popup) {
          try {
            await popup.waitForLoadState('domcontentloaded', { timeout: 5000 });
            await humanPause(popup, 250, 500);
            const popupUrl = popup.url();
            if (/404|wp_die|error/i.test(popupUrl)) {
              failures.push({
                ...target,
                reason: 'popup_navigation_error',
                popupUrl,
                url: beforeUrl,
              });
            } else {
              successes.push({
                ...target,
                action: 'popup_opened',
                to: popupUrl,
              });
            }
          } finally {
            await popup.close({ runBeforeUnload: true }).catch(() => {});
          }
          await page.bringToFront();
          continue;
        }

        const afterUrl = page.url();
        const urlChanged = afterUrl !== beforeUrl;

        const hrefLooksNavigable =
          !!target.href &&
          !target.href.startsWith('#') &&
          !target.href.toLowerCase().startsWith('javascript:');

        if (hrefLooksNavigable && !urlChanged) {
          failures.push({
            ...target,
            reason: 'click_without_navigation',
            url: afterUrl,
          });
          continue;
        }

        if (urlChanged) {
          const badNav =
            /wp-admin\/admin-ajax\.php/.test(afterUrl) ||
            /404|not-found|wp_die/i.test(afterUrl);
          if (badNav) {
            failures.push({
              ...target,
              reason: 'bad_navigation_destination',
              from: beforeUrl,
              to: afterUrl,
            });
          } else {
            successes.push({
              ...target,
              action: 'navigated',
              from: beforeUrl,
              to: afterUrl,
            });
          }

        } else {
          successes.push({
            ...target,
            action: 'click_ok_same_view',
            to: afterUrl,
          });
        }
      } catch (error) {
        failures.push({
          ...target,
          reason: 'click_exception',
          message: error && error.message ? error.message : String(error),
          url: page.url(),
        });
      }
    }

    await page.screenshot({
      path: path.join(reportDir, 'dashboard-human-audit-final.png'),
      fullPage: true,
    });

    const report = {
      auditedAt: new Date().toISOString(),
      dashboardUrl: new URL(DASHBOARD_URL, page.url()).toString(),
      totalTargets: targets.length,
      successCount: successes.length,
      failureCount: failures.length,
      failures,
      consoleIssues,
      requestIssues,
    };

    const jsonPath = path.join(reportDir, 'dashboard-human-audit-report.json');
    fs.writeFileSync(jsonPath, JSON.stringify(report, null, 2), 'utf8');

    const lines = [];
    lines.push('# Dashboard Human Audit');
    lines.push('');
    lines.push(`- Audited at: ${report.auditedAt}`);
    lines.push(`- Targets: ${report.totalTargets}`);
    lines.push(`- Successes: ${report.successCount}`);
    lines.push(`- Failures: ${report.failureCount}`);
    lines.push(`- Console issues: ${consoleIssues.length}`);
    lines.push(`- Network issues: ${requestIssues.length}`);
    lines.push('');
    lines.push('## Failing Buttons/Links');
    if (!failures.length) {
      lines.push('- None detected');
    } else {
      for (const f of failures) {
        lines.push(
          `- [${f.tag}] "${f.text || '(sin texto)'}" | href="${f.href || ''}" | reason=${f.reason}`
        );
      }
    }

    const mdPath = path.join(reportDir, 'dashboard-human-audit-report.md');
    fs.writeFileSync(mdPath, lines.join('\n'), 'utf8');
  });
});
