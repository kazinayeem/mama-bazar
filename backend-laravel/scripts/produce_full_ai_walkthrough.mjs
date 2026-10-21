import { spawn, execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function main() {
  console.log('=== Step 1: Connecting to Chrome via CDP ===');
  execSync(`osascript -e 'tell application "Google Chrome" to activate'`);
  await sleep(1500);

  const listRes = await fetch('http://127.0.0.1:9223/json/list');
  const tabs = await listRes.json();
  const pageTab = tabs.find(t => t.type === 'page' && !t.url.startsWith('chrome://'));
  if (!pageTab) {
    console.error('No suitable page tab found');
    process.exit(1);
  }

  const ws = new WebSocket(pageTab.webSocketDebuggerUrl);
  let msgId = 1;
  const pending = new Map();
  ws.onmessage = (e) => {
    const d = JSON.parse(e.data);
    if (d.id && pending.has(d.id)) {
      pending.get(d.id).res(d.result);
      pending.delete(d.id);
    }
  };

  const send = (method, params = {}) => new Promise((res) => {
    const id = msgId++;
    pending.set(id, { res });
    ws.send(JSON.stringify({ id, method, params }));
  });

  await new Promise(r => ws.onopen = r);
  await send('Page.enable');
  await send('DOM.enable');

  console.log('=== Step 2: Preparing Video Capture ===');
  const rawVideoPath = '/tmp/master_automation_capture.mp4';
  if (fs.existsSync(rawVideoPath)) fs.unlinkSync(rawVideoPath);

  const scProc = spawn('/usr/sbin/screencapture', ['-v', rawVideoPath]);
  await sleep(2500);

  // Helper functions
  async function injectBadge(step, title, detail) {
    await send('Runtime.evaluate', {
      expression: `
        (() => {
          let b = document.getElementById('__walkthrough_badge');
          if (!b) {
            b = document.createElement('div');
            b.id = '__walkthrough_badge';
            b.style.cssText = 'position:fixed;bottom:28px;left:50%;transform:translateX(-50%);background:rgba(15,23,42,0.95);border:2px solid #22c55e;color:#fff;padding:12px 28px;border-radius:9999px;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;z-index:99999999;box-shadow:0 12px 30px rgba(0,0,0,0.55);display:flex;align-items:center;gap:14px;pointer-events:none;backdrop-filter:blur(8px);';
            document.body.appendChild(b);
          }
          b.innerHTML = '<span style="display:inline-flex;align-items:center;justify-content:center;background:#15803d;color:#fff;font-size:11px;font-weight:800;padding:4px 10px;border-radius:9999px;letter-spacing:0.05em;text-transform:uppercase;">${step}</span>' +
                        '<span style="font-size:15px;font-weight:700;color:#f8fafc;">${title}</span>' +
                        '<span style="font-size:13px;color:#94a3b8;border-left:1px solid #334155;padding-left:12px;">${detail}</span>';
        })()
      `
    });
  }

  async function navigate(url, waitMs = 2500) {
    console.log(`[Nav] -> ${url}`);
    await send('Page.navigate', { url });
    await sleep(waitMs);
  }

  async function smoothScroll(totalY, steps = 30, intervalMs = 45) {
    const stepY = totalY / steps;
    for (let i = 0; i < steps; i++) {
      await send('Runtime.evaluate', {
        expression: `window.scrollBy({ top: ${stepY}, behavior: 'auto' });`
      });
      await sleep(intervalMs);
    }
  }

  // --- AUTOMATION SCENARIOS ---
  console.log('=== Step 3: Executing End-to-End Walkthrough ===');

  // 1. Reset consent and visit Homepage
  await navigate('http://127.0.0.1:8000/', 2000);
  await send('Runtime.evaluate', {
    expression: `
      localStorage.removeItem('mb_consent');
      if (window.mbConsentShow) window.mbConsentShow();
    `
  });
  await sleep(1500);
  await injectBadge('STEP 1 OF 14', 'Privacy & Cookies Consent', 'Essential Cookies & Privacy-First Tracking');
  await sleep(2500);

  // Visually accept cookie policy
  await send('Runtime.evaluate', {
    expression: `
      const btn = document.getElementById('mb-consent-accept');
      if (btn) {
        btn.style.outline = '3px solid #4ade80';
        btn.style.boxShadow = '0 0 15px rgba(74,222,128,0.8)';
      }
    `
  });
  await sleep(1500);
  await send('Runtime.evaluate', {
    expression: `
      const btn = document.getElementById('mb-consent-accept');
      if (btn) btn.click();
    `
  });
  await injectBadge('STEP 1 OF 14', 'Cookie Policy Accepted', 'Consent Granted • Ad-Free Clean Layout Verified');
  await sleep(2500);

  // 2. Homepage Tour (Hero Carousel & Product Grid)
  await injectBadge('STEP 2 OF 14', 'Storefront Homepage', 'Dynamic Hero Slider • Instant Keyword Searches');
  await sleep(3000);
  await injectBadge('STEP 2 OF 14', 'Verified Responsive Grid', 'Side-by-Side Product Cards • Zero Layout Shift');
  await smoothScroll(650, 25, 45);
  await sleep(2500);
  await injectBadge('STEP 2 OF 14', 'Social Proof & Trust', 'Verified Customer Reviews • Guaranteed Quality Badges');
  await smoothScroll(800, 25, 45);
  await sleep(2500);
  await smoothScroll(700, 20, 40);
  await sleep(2000);

  // 3. Shop Catalog & Filtering
  await navigate('http://127.0.0.1:8000/shop', 2500);
  await injectBadge('STEP 3 OF 14', 'Shop Catalog', 'Multi-Category Filtering • Live Sorting Controls');
  await sleep(2500);
  await smoothScroll(500, 20, 45);
  await sleep(2000);
  await smoothScroll(-300, 15, 40);
  await sleep(1500);

  // 4. Product Details & Add to Cart
  await navigate('http://127.0.0.1:8000/products/red-white-rose-flower-fairy-string-lights', 3000);
  await injectBadge('STEP 4 OF 14', 'Product Detail View', 'High-Res Photo Gallery • Low-Stock Urgency Alert');
  await sleep(2500);
  await smoothScroll(400, 15, 45);
  await sleep(1500);

  await injectBadge('STEP 4 OF 14', 'Adding Item to Cart', 'Instant Client-Side Cart Store Synchronization');
  await send('Runtime.evaluate', {
    expression: `
      const btn = document.querySelector('button[type="button"].store-btn-primary') ||
                  Array.from(document.querySelectorAll('button')).find(b => b.innerText.includes('Add to Cart'));
      if (btn) {
        btn.style.outline = '3px solid #22c55e';
        btn.click();
      }
    `
  });
  await sleep(2500);

  // 5. Shopping Cart
  await navigate('http://127.0.0.1:8000/cart', 2500);
  await injectBadge('STEP 5 OF 14', 'Shopping Cart Review', 'Quantity Adjustment • Transparent Subtotal & Fees');
  await sleep(2500);
  await smoothScroll(300, 15, 45);
  await sleep(2000);

  // 6. Checkout & Live Order Placement
  await navigate('http://127.0.0.1:8000/checkout', 2500);
  await injectBadge('STEP 6 OF 14', 'Streamlined One-Page Checkout', 'Cash on Delivery • Express Dispatch Info');
  await sleep(2000);

  // Fill in customer shipping details
  await send('Runtime.evaluate', {
    expression: `
      (() => {
        const name = document.getElementById('f_name');
        if (name) { name.value = 'Mohammad Ali Nayeem'; name.dispatchEvent(new Event('input', { bubbles: true })); }
        const phone = document.getElementById('f_phone');
        if (phone) { phone.value = '01712345678'; phone.dispatchEvent(new Event('input', { bubbles: true })); }
        const dist = document.getElementById('f_district');
        if (dist) { dist.value = 'Dhaka'; dist.dispatchEvent(new Event('change', { bubbles: true })); }
        const addr = document.getElementById('f_addr');
        if (addr) { addr.value = 'House 12, Road 4, Sector 10, Mirpur, Dhaka'; addr.dispatchEvent(new Event('input', { bubbles: true })); }

        const form = document.getElementById('checkoutForm');
        if (form && window.Alpine) {
          const d = Alpine.$data(form);
          d.form.name = 'Mohammad Ali Nayeem';
          d.form.phone = '01712345678';
          d.district = 'Dhaka';
          d.form.address = 'House 12, Road 4, Sector 10, Mirpur, Dhaka';
          d.selectedPaymentCode = 'cod';
          d.touch.delivery = true;
          d.syncDomInputs();
        }
      })()
    `
  });
  await sleep(2500);

  await injectBadge('STEP 6 OF 14', 'Submitting Order', 'Live Transaction Execution via Cash on Delivery');
  await send('Runtime.evaluate', {
    expression: `
      const submitBtn = document.querySelector('#checkoutForm button[type=submit]');
      if (submitBtn) {
        submitBtn.style.outline = '3px solid #22c55e';
        submitBtn.click();
      }
    `
  });
  await sleep(5000);

  // 7. Order Confirmation Screen
  await injectBadge('STEP 7 OF 14', 'Order Placed Successfully!', 'Unique Order ID Generated • Immediate Confirmation');
  await sleep(3500);
  await smoothScroll(350, 15, 45);
  await sleep(2500);

  // 8. Order Tracking Portal
  await navigate('http://127.0.0.1:8000/track', 2500);
  await injectBadge('STEP 8 OF 14', 'Self-Service Order Tracking', 'Real-Time Delivery Milestone Timeline');
  await sleep(2000);
  await send('Runtime.evaluate', {
    expression: `
      const inp = document.querySelector('input[name="order_id"], input[name="phone"], #track_input');
      if (inp) { inp.value = '01712345678'; inp.dispatchEvent(new Event('input', { bubbles: true })); }
    `
  });
  await sleep(2500);

  // 9. About Us Page
  await navigate('http://127.0.0.1:8000/about', 2500);
  await injectBadge('STEP 9 OF 14', 'About Mama Bazar', 'Brand Story • Mission, Vision & Core Values');
  await sleep(2000);
  await smoothScroll(600, 20, 45);
  await sleep(2000);

  // 10. Help Center / FAQ
  await navigate('http://127.0.0.1:8000/faq', 2500);
  await injectBadge('STEP 10 OF 14', 'Customer Help Center', 'Interactive Frequently Asked Questions & Policies');
  await sleep(2000);
  await smoothScroll(500, 20, 45);
  await sleep(2000);

  // 11. Contact Page
  await navigate('http://127.0.0.1:8000/contact', 2500);
  await injectBadge('STEP 11 OF 14', 'Contact & Customer Support', 'Hotline, Support Email & Direct Inquiry Form');
  await sleep(2000);
  await smoothScroll(500, 20, 45);
  await sleep(2000);

  // 12. Admin Dashboard
  await navigate('http://127.0.0.1:8000/dev-login-admin', 3000);
  await injectBadge('STEP 12 OF 14', 'Super Admin Dashboard', 'Executive Sales Analytics • Real-Time Revenue');
  await sleep(3000);
  await smoothScroll(500, 20, 45);
  await sleep(2500);

  // 13. Admin Orders Management (Highlight Newly Created Order)
  await navigate('http://127.0.0.1:8000/admin/orders', 2500);
  await injectBadge('STEP 13 OF 14', 'Admin Orders Center', 'Live Customer Order Queue • Processing & Invoice Status');
  await sleep(3000);
  await smoothScroll(400, 15, 45);
  await sleep(2500);

  // 14. Admin Product Creation & Activity Monitor
  await navigate('http://127.0.0.1:8000/admin/products/create', 2500);
  await injectBadge('STEP 14 OF 14', 'Catalog Builder & Editor', 'Rich Text WYSIWYG Editor • Dynamic Pricing & SEO');
  await sleep(2500);
  await smoothScroll(600, 20, 45);
  await sleep(2000);

  await navigate('http://127.0.0.1:8000/admin/activity-monitor', 3000);
  await injectBadge('STEP 14 OF 14', 'Enterprise Activity Monitor', 'Real-Time Audit Trail • Security & Operational Logs');
  await sleep(3000);

  // Final Return to Clean Homepage
  await navigate('http://127.0.0.1:8000/', 2500);
  await injectBadge('MAMA BAZAR', 'Complete Platform Tour Verified', 'Ad-Free • Fast • Responsive • Scalable');
  await sleep(3000);

  ws.close();

  console.log('=== Step 4: Finalizing Screen Capture ===');
  scProc.kill('SIGINT');
  await sleep(3000);

  console.log('=== Step 5: Generating AI Neural Voiceover (Christopher Neural) ===');
  const narrationScript = `
Welcome to the comprehensive platform demonstration of Mama Bazar, a modern full-stack e-commerce application powered by Laravel.
We begin our tour on the storefront by reviewing the privacy-first consent banner. With a single click, we accept the cookie policy, unlocking full shopping, localized cart sessions, and privacy-conscious analytics.
On the homepage, customers discover dynamic promotional carousels, instant keyword search suggestions, and a refined category hierarchy.
The product grid features verified responsive cards with zero layout shift, real-time discount badges, and verified customer reviews.
All intrusive ad banners and third-party sponsored slots have been completely removed, providing an uninterrupted, clean user experience.
Exploring the catalog in the shop section provides multi-category filtering, instant price sorting, and live keyword queries.
Each product detail page showcases high-resolution photo galleries, localized product specifications, and low-stock urgency alerts.
Adding an item to the shopping cart is instantaneous with reactive client-side store state.
Inside the shopping cart, customers can review line items, adjust quantities, apply discount promotional codes, and verify the transparent subtotal.
Proceeding to the streamlined one-page checkout, the shopper enters delivery details, chooses their district, and selects Cash on Delivery.
With all inputs validated, we confirm the order.
The transaction processes live in real time, immediately redirecting to the order confirmation page with a unique order ID and complete receipt details.
Shoppers can track shipment status twenty-four-seven through the self-service order tracking portal.
Customer credibility is reinforced through comprehensive About Us, Interactive Help and FAQ, and direct Contact Support pages.
Transitioning to the administrative control panel, administrators gain full visibility over store performance with real-time revenue analytics, order volume metrics, and sales trend graphs.
In the order management center, our newly created customer order appears live at the top of the queue, ready for invoice generation and fulfillment processing.
The catalog builder provides intuitive rich text WYSIWYG editing, granular pricing controls, and automated SEO metadata generation.
And the enterprise activity monitor provides complete audit logging and administrative oversight.
Mama Bazar delivers a fast, secure, and beautiful e-commerce experience for modern commerce.
  `.trim();

  fs.writeFileSync('/tmp/full_ai_script.txt', narrationScript);
  console.log('Running edge-tts...');
  execSync(`python3 -m edge_tts --voice en-US-ChristopherNeural --file /tmp/full_ai_script.txt --write-media /tmp/full_ai_voiceover.mp3`);

  console.log('=== Step 6: Mixing Video + AI Voice + Background Music with FFmpeg ===');
  const bgMusic = 'resources/audio/background_music.mp3';

  // Probe durations
  const vidDuration = parseFloat(execSync(`ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "${rawVideoPath}"`).toString().trim());
  const voDuration = parseFloat(execSync(`ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 /tmp/full_ai_voiceover.mp3`).toString().trim());
  console.log(`Captured Video Duration: ${vidDuration}s`);
  console.log(`AI Voiceover Duration: ${voDuration}s`);

  // Target final duration
  const targetDuration = Math.min(vidDuration, voDuration + 4);

  // Composite:
  // - Input 0: raw screen recording (scaled to crisp 1080p 60fps)
  // - Input 1: AI voiceover (Christopher Neural)
  // - Input 2: Looped background music (ducked to 14% volume, with smooth 2s fade-in and 3s fade-out)
  execSync(`
    ffmpeg -y -i "${rawVideoPath}" -i /tmp/full_ai_voiceover.mp3 -stream_loop -1 -i "${bgMusic}" \
    -filter_complex "[1:a]volume=1.25[vo]; [2:a]volume=0.14,afade=t=in:ss=0:d=2,afade=t=out:st=${targetDuration-3}:d=3[bg]; [vo][bg]amix=inputs=2:duration=first[aout]; [0:v]scale=1920:1080:flags=lanczos,fps=30[vout]" \
    -map "[vout]" -map "[aout]" \
    -t ${targetDuration} \
    -c:v libx264 -preset fast -crf 20 -pix_fmt yuv420p \
    -c:a aac -b:a 192k \
    full-product-demo.mp4
  `);

  console.log('=== Step 7: Publishing Deliverables ===');
  execSync(`cp full-product-demo.mp4 public/full-product-demo.mp4`);
  execSync(`cp full-product-demo.mp4 "/Users/mohammadalinayeem/.gemini/antigravity-ide/brain/99064a10-b3c3-4279-a0e6-3195411d0c9a/full-product-demo.mp4"`);

  // Split into Customer Frontend and Admin Panel segments
  const splitPoint = 75; // seconds
  execSync(`ffmpeg -y -i full-product-demo.mp4 -t ${splitPoint} -c copy customer-frontend.mp4 && cp customer-frontend.mp4 public/customer-frontend.mp4`);
  execSync(`ffmpeg -y -i full-product-demo.mp4 -ss ${splitPoint} -c copy admin-panel.mp4 && cp admin-panel.mp4 public/admin-panel.mp4`);

  console.log('=== COMPLETE: Long Master Video with AI Neural Voiceover Created Successfully! ===');
}

main().catch(console.error);
