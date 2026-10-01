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
    page.on('console', (msg) => { if (msg.type() === 'error') consoleErrors.push(msg.text().slice(0, 200)); });
    page.on('pageerror', (err) => consoleErrors.push('PAGEERROR: ' + (err.stack || String(err)).slice(0, 400)));

    await page.goto(BASE + '/admin/login', { waitUntil: 'networkidle0' });
    const allBtns = await page.$$('button');
    for (const b of allBtns) {
        const t = await b.evaluate((el) => (el.innerText || '').trim());
        if (/Login \(Email\)|Quick Login/i.test(t)) {
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 }).catch(() => {}),
                b.click(),
            ]);
            break;
        }
    }
    console.log('logged in:', page.url());

    async function clickFirstEdit() {
        const btns = await page.$$('button, a');
        for (const h of btns) {
            const t = await h.evaluate((b) => (b.innerText || '').trim());
            if (t === 'Edit') {
                const box = await h.boundingBox().catch(() => null);
                if (box) { await h.evaluate((b) => b.scrollIntoView()); await new Promise((r) => setTimeout(r, 200)); await h.click(); return true; }
            }
        }
        return false;
    }
    async function visibleModalCount() {
        return page.evaluate(() => [...document.querySelectorAll('[x-show]')].filter((el) => {
            const s = getComputedStyle(el);
            return s.display !== 'none' && el.getBoundingClientRect().height > 50;
        }).length);
    }

    // CATEGORIES save flow
    consoleErrors.length = 0;
    await page.goto(BASE + '/admin/categories', { waitUntil: 'networkidle0' });
    await new Promise((r) => setTimeout(r, 600));
    await clickFirstEdit();
    await new Promise((r) => setTimeout(r, 600));
    const stamp = 'Edited' + Date.now().toString().slice(-5);
    const nameInput = await page.$('input[name="name"]:not([type="hidden"])');
    // there are two name inputs (create form + modal); pick the one inside visible modal
    const modalInputs = await page.$$eval('input[name="name"]', (els) => els.map((el) => {
        const r = el.getBoundingClientRect(); return { w: r.width, h: r.height, v: el.value };
    }));
    console.log('name inputs:', JSON.stringify(modalInputs));
    // type into the LAST visible name input (modal)
    const handles = await page.$$('input[name="name"]');
    let target = null;
    for (const h of handles.reverse()) {
        const box = await h.boundingBox().catch(() => null);
        if (box && box.width > 10) { target = h; break; }
    }
    if (target) {
        await target.click({ clickCount: 3 });
        await target.type(stamp);
        // submit the modal form
        const saved = await page.evaluate(() => {
            const inputs = [...document.querySelectorAll('input[name="name"]')];
            const inp = inputs.reverse().find((el) => el.getBoundingClientRect().width > 10);
            const form = inp ? inp.closest('form') : null;
            return { hasForm: !!form, action: form ? form.action : null };
        });
        console.log('modal form:', JSON.stringify(saved));
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 }).catch(() => {}),
            page.evaluate(() => {
                const inputs = [...document.querySelectorAll('input[name="name"]')];
                const inp = inputs.reverse().find((el) => el.getBoundingClientRect().width > 10);
                inp.closest('form').submit();
            }),
        ]);
        await new Promise((r) => setTimeout(r, 800));
        const after = await page.evaluate(() => document.body.innerText.slice(0, 600));
        console.log('after save url:', page.url());
        console.log('sees stamp:', after.includes(stamp), '| sees success:', /success|updated/i.test(after));
    }
    console.log('cat errors:', JSON.stringify(consoleErrors));
    await browser.close();
})().catch((e) => { console.error('FATAL', e); process.exit(1); });
