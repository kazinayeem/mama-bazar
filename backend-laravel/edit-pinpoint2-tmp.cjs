const puppeteer = require('puppeteer-core');
const BASE = 'http://localhost:8123';
(async () => {
    const browser = await puppeteer.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: 'new', args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
    const page = await browser.newPage();
    let errs = [];
    page.on('pageerror', (err) => errs.push(String(err).split('\n')[0].slice(0, 120)));
    await page.goto(BASE + '/admin/login', { waitUntil: 'networkidle0' });
    for (const b of await page.$$('button')) {
        const t = await b.evaluate((el) => (el.innerText || '').trim());
        if (/Login \(Email\)/i.test(t)) {
            await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 }).catch(() => {}), b.click()]);
            break;
        }
    }
    errs = [];
    await page.goto(BASE + '/admin/payment-methods', { waitUntil: 'networkidle0' });
    await new Promise((r) => setTimeout(r, 1200));
    console.log('after load:', JSON.stringify(errs));
    // click first Edit
    for (const h of await page.$$('button, a')) {
        const t = await h.evaluate((b) => (b.innerText || '').trim());
        if (t === 'Edit') { const box = await h.boundingBox().catch(() => null); if (box) { await h.click(); break; } }
    }
    await new Promise((r) => setTimeout(r, 800));
    console.log('after edit click:', JSON.stringify(errs));
    // toggle a row checkbox
    const boxes = await page.$$('input[type="checkbox"]');
    console.log('checkboxes:', boxes.length);
    if (boxes.length) { await boxes[1].click().catch(() => {}); await new Promise((r) => setTimeout(r, 500)); }
    console.log('after checkbox:', JSON.stringify(errs));
    await browser.close();
})().catch((e) => { console.error('FATAL', e); process.exit(1); });
