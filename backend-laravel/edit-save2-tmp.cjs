const puppeteer = require('puppeteer-core');

const BASE = 'http://localhost:8123';

(async () => {
    const browser = await puppeteer.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: 'new',
        args: ['--no-sandbox', '--disable-dev-shm-usage', '--window-size=1440,900'],
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });
    const consoleErrors = [];
    page.on('pageerror', (err) => consoleErrors.push('PAGEERROR: ' + (err.stack || String(err)).slice(0, 300)));

    await page.goto(BASE + '/admin/login', { waitUntil: 'networkidle0' });
    for (const b of await page.$$('button')) {
        const t = await b.evaluate((el) => (el.innerText || '').trim());
        if (/Login \(Email\)|Quick Login/i.test(t)) {
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 }).catch(() => {}),
                b.click(),
            ]);
            break;
        }
    }

    async function saveFlow(url, label, fieldName, makeValue) {
        consoleErrors.length = 0;
        await page.goto(BASE + url, { waitUntil: 'networkidle0' });
        await new Promise((r) => setTimeout(r, 600));
        // click first Edit
        let clicked = false;
        for (const h of await page.$$('button, a')) {
            const t = await h.evaluate((b) => (b.innerText || '').trim());
            if (t === 'Edit') {
                const box = await h.boundingBox().catch(() => null);
                if (box) { await h.evaluate((b) => b.scrollIntoView()); await new Promise((r) => setTimeout(r, 200)); await h.click(); clicked = true; break; }
            }
        }
        if (!clicked) return { label, result: 'NO-EDIT-BUTTON' };
        await new Promise((r) => setTimeout(r, 600));
        const stamp = makeValue();
        // find last visible fieldName input (modal one)
        const handles = await page.$$(`[name="${fieldName}"]`);
        let target = null;
        for (const h of handles.reverse()) {
            const box = await h.boundingBox().catch(() => null);
            if (box && box.width > 10) { target = h; break; }
        }
        if (!target) return { label, result: 'NO-FIELD:' + fieldName };
        const tag = await target.evaluate((el) => el.tagName);
        if (tag === 'SELECT') {
            await target.select(stamp);
        } else {
            await target.click({ clickCount: 3 });
            await target.type(stamp);
        }
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 }).catch(() => {}),
            page.evaluate((fn) => {
                const inputs = [...document.querySelectorAll(`[name="${fn}"]`)];
                const inp = inputs.reverse().find((el) => { const r = el.getBoundingClientRect(); return r.width > 10; });
                inp.closest('form').submit();
            }, fieldName),
        ]);
        await new Promise((r) => setTimeout(r, 600));
        const full = await page.evaluate(() => document.body.innerText);
        const ok = full.includes(stamp) || /successfully|updated/i.test(full);
        return { label, result: ok ? 'SAVED-VISIBLE' : 'SAVE-UNCONFIRMED', stamp, errors: [...consoleErrors] };
    }

    const stamp5 = () => 'E' + Date.now().toString().slice(-5);
    console.log(JSON.stringify(await saveFlow('/admin/coupons', 'coupons', 'code', () => 'TST' + Date.now().toString().slice(-5))));
    console.log(JSON.stringify(await saveFlow('/admin/shipping', 'shipping', 'name', stamp5)));
    console.log(JSON.stringify(await saveFlow('/admin/brands', 'brands', 'name', stamp5)));
    console.log(JSON.stringify(await saveFlow('/admin/payment-methods', 'payments', 'name', stamp5)));
    await browser.close();
})().catch((e) => { console.error('FATAL', e); process.exit(1); });
