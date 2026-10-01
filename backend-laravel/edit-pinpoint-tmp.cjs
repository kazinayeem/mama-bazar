const puppeteer = require('puppeteer-core');
const BASE = 'http://localhost:8123';
(async () => {
    const browser = await puppeteer.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: 'new', args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
    const page = await browser.newPage();
    await page.goto(BASE + '/admin/login', { waitUntil: 'networkidle0' });
    for (const b of await page.$$('button')) {
        const t = await b.evaluate((el) => (el.innerText || '').trim());
        if (/Login \(Email\)/i.test(t)) {
            await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 }).catch(() => {}), b.click()]);
            break;
        }
    }
    await page.goto(BASE + '/admin/payment-methods', { waitUntil: 'networkidle0' });
    await new Promise((r) => setTimeout(r, 1000));
    const bad = await page.evaluate(() => {
        const out = [];
        const els = [...document.querySelectorAll('*')].filter((el) => {
            return [...el.attributes].some((a) => a.value && a.value.includes('.includes('));
        });
        for (const el of els.slice(0, 40)) {
            for (const a of [...el.attributes]) {
                if (!a.value.includes('.includes(')) continue;
                // reconstruct directive: :checked -> checked, @change -> change, x-show stays
                let expr = a.value;
                let dir = a.name;
                try {
                    const val = window.Alpine.evaluate(el, expr);
                    void val;
                } catch (e) {
                    out.push({ tag: el.tagName, attr: dir, expr: expr.slice(0, 160), err: String(e).slice(0, 160) });
                }
            }
        }
        return out;
    });
    console.log('THROWERS: ' + JSON.stringify(bad, null, 1).slice(0, 3000));
    await browser.close();
})().catch((e) => { console.error('FATAL', e); process.exit(1); });
