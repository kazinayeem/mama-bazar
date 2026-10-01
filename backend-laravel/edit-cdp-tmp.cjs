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
    const cdp = await page.createCDPSession();
    await cdp.send('Debugger.enable');
    await cdp.send('Debugger.setPauseOnExceptions', { state: 'all' });
    let captured = null;
    cdp.on('Debugger.paused', async (ev) => {
        try {
            captured = {
                reason: ev.reason,
                desc: (ev.data && ev.data.description || '').slice(0, 200),
                frames: ev.callFrames.slice(0, 8).map((f) => ({
                    fn: f.functionName || '(anon)',
                    url: (f.url || '').slice(-50),
                    line: f.location.lineNumber,
                    col: f.location.columnNumber,
                })),
            };
        } catch (e) { captured = { err: String(e).slice(0, 200) }; }
        await cdp.send('Debugger.resume');
    });
    await page.goto(BASE + '/admin/payment-methods', { waitUntil: 'networkidle0' });
    await new Promise((r) => setTimeout(r, 1500));
    console.log('CAPTURED: ' + JSON.stringify(captured, null, 1).slice(0, 2000));
    await browser.close();
})().catch((e) => { console.error('FATAL', e); process.exit(1); });
