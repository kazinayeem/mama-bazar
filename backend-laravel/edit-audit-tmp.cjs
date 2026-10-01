const puppeteer = require('puppeteer-core');

const BASE = 'http://localhost:8123';
const EMAIL = 'admin@example.com';
const PASS = 'DevAdmin@12345';

(async () => {
    const browser = await puppeteer.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: 'new',
        args: ['--no-sandbox', '--disable-dev-shm-usage', '--window-size=1440,900'],
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });
    const consoleErrors = [];
    page.on('console', (msg) => {
        if (msg.type() === 'error') consoleErrors.push(msg.text().slice(0, 300));
    });
    page.on('pageerror', (err) => consoleErrors.push('PAGEERROR: ' + (err.stack || String(err)).slice(0, 600)));

    // Login (admin login form)
    await page.goto(BASE + '/admin/login', { waitUntil: 'networkidle0' });
    // Prefer dev quick-login buttons when present.
    const quickLogin = await page.$('button');
    let usedQuick = false;
    const allBtns = await page.$$('button');
    for (const b of allBtns) {
        const t = await b.evaluate((el) => (el.innerText || '').trim());
        if (/Login \(Email\)|Quick Login/i.test(t)) {
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 }).catch(() => {}),
                b.click(),
            ]);
            usedQuick = true;
            break;
        }
    }
    if (!usedQuick) {
        const hasLogin = await page.$('input[name="login"]');
        if (hasLogin) await page.type('input[name="login"]', EMAIL);
        const pw = await page.$('input[name="password"]');
        if (pw) await page.type('input[name="password"]', 'ChangeMe123!');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 }).catch(() => {}),
            page.$eval('form', (f) => f.submit()),
        ]);
    }
    console.log('after login url:', page.url());
    const bodyText = await page.evaluate(() => document.body.innerText.slice(0, 400));
    console.log('login page text:', JSON.stringify(bodyText));

    const results = [];
    async function testEditButtons(url, label) {
        consoleErrors.length = 0;
        await page.goto(BASE + url, { waitUntil: 'networkidle0' });
        await new Promise((r) => setTimeout(r, 800));
        const alpine = await page.evaluate(() => !!window.Alpine);
        // find visible Edit buttons (exact text "Edit")
        const btns = await page.$$('button, a');
        const editHandles = [];
        for (const h of btns) {
            const t = await h.evaluate((b) => (b.innerText || '').trim());
            if (t === 'Edit') {
                const box = await h.boundingBox().catch(() => null);
                if (box) editHandles.push(h);
            }
        }
        const btnCount = editHandles.length;
        let opened = 0, detail = '';
        for (let i = 0; i < Math.min(editHandles.length, 2); i++) {
            const btn = editHandles[i];
            try {
                await btn.evaluate((b) => b.scrollIntoView());
                await new Promise((r) => setTimeout(r, 200));
                await btn.click();
                await new Promise((r) => setTimeout(r, 700));
                const state = await page.evaluate(() => {
                    // any visible modal/dialog/details[open]?
                    const modals = [...document.querySelectorAll('[x-show]')].filter((el) => {
                        const s = getComputedStyle(el);
                        return s.display !== 'none' && el.getBoundingClientRect().height > 50;
                    }).length;
                    const openDetails = document.querySelectorAll('details[open]').length;
                    return { modals, openDetails };
                });
                if (state.modals > 0 || state.openDetails > 0) opened++;
                else detail = JSON.stringify(state);
                // close via Escape for next iteration
                await page.keyboard.press('Escape');
                await new Promise((r) => setTimeout(r, 300));
            } catch (e) {
                detail = 'CLICK-ERR ' + String(e).slice(0, 120);
            }
        }
        results.push({ label, url, alpine, editBtns: btnCount, opened, detail, consoleErrors: [...consoleErrors] });
    }

    await testEditButtons('/admin/categories', 'categories');
    await testEditButtons('/admin/brands', 'brands');
    await testEditButtons('/admin/coupons', 'coupons');
    await testEditButtons('/admin/members', 'members');
    await testEditButtons('/admin/shipping', 'shipping');
    await testEditButtons('/admin/payment-methods', 'payments');
    await testEditButtons('/admin/marketing', 'marketing');
    await testEditButtons('/admin/banners', 'banners');
    await testEditButtons('/admin/expenses', 'expenses');
    await testEditButtons('/admin/policies', 'policies');
    await testEditButtons('/admin/colors', 'colors');

    for (const r of results) console.log(JSON.stringify(r));
    await browser.close();
})().catch((e) => { console.error('FATAL', e); process.exit(1); });
