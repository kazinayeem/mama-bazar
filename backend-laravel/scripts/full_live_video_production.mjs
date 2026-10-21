import { spawn, execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function main() {
  console.log('=== Step 1: Preparing GUI Chrome Window ===');
  execSync(`osascript -e 'tell application "Google Chrome" to activate'`);
  await sleep(1000);

  // Get active tab or open new tab on port 9223
  let wsUrl;
  try {
    const listRes = await fetch('http://127.0.0.1:9223/json/list');
    const tabs = await listRes.json();
    const pageTab = tabs.find(t => t.type === 'page');
    if (pageTab) {
      wsUrl = pageTab.webSocketDebuggerUrl;
    } else {
      const newRes = await fetch('http://127.0.0.1:9223/json/new?http://127.0.0.1:8000/', { method: 'PUT' });
      const newTab = await newRes.json();
      wsUrl = newTab.webSocketDebuggerUrl;
    }
  } catch (e) {
    console.error('Error connecting to Chrome on 9223:', e);
    process.exit(1);
  }

  const ws = new WebSocket(wsUrl);
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

  console.log('=== Step 2: Starting Live Screen Capture ===');
  const rawVideoPath = '/tmp/live_automation_capture.mp4';
  if (fs.existsSync(rawVideoPath)) fs.unlinkSync(rawVideoPath);

  // Launch screencapture -v
  const scProc = spawn('/usr/sbin/screencapture', ['-v', rawVideoPath]);
  await sleep(2000);

  // Helper functions for smooth cinematic automation
  async function navigate(url, waitMs = 2500) {
    console.log(`[Automation] Navigating to ${url}`);
    await send('Page.navigate', { url });
    await sleep(waitMs);
    // Inject floating badge
    await send('Runtime.evaluate', {
      expression: `
        (() => {
          let b = document.getElementById('__demo_badge');
          if (!b) {
            b = document.createElement('div');
            b.id = '__demo_badge';
            b.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:rgba(15,23,42,0.92);border:2px solid #22c55e;color:#fff;padding:10px 24px;border-radius:30px;font-family:sans-serif;font-size:15px;font-weight:700;z-index:9999999;box-shadow:0 10px 25px rgba(0,0,0,0.5);display:flex;align-items:center;gap:10px;pointer-events:none;';
            document.body.appendChild(b);
          }
          b.innerHTML = '<span style="width:10px;height:10px;border-radius:50%;background:#4ade80;display:inline-block;"></span> <span>MAMA BAZAR — Live Feature Tour</span>';
        })()
      `
    });
  }

  async function updateBadge(text) {
    await send('Runtime.evaluate', {
      expression: `
        (() => {
          let b = document.getElementById('__demo_badge');
          if (b) b.querySelector('span:last-child').textContent = ${JSON.stringify(text)};
        })()
      `
    });
  }

  async function smoothScroll(totalY, steps = 30, intervalMs = 40) {
    const stepY = totalY / steps;
    for (let i = 0; i < steps; i++) {
      await send('Runtime.evaluate', {
        expression: `window.scrollBy({ top: ${stepY}, behavior: 'auto' });`
      });
      await sleep(intervalMs);
    }
  }

  // --- AUTOMATION SEQUENCE ---
  console.log('=== Step 3: Executing Live Browser Automation ===');

  // 1. Homepage
  await navigate('http://127.0.0.1:8000/', 3000);
  await updateBadge('Homepage — Hero Carousel & Popular Searches');
  await sleep(2000);

  await updateBadge('Homepage — Fixed Product Grid (Side-by-Side Responsive Layout)');
  await smoothScroll(650, 25, 45);
  await sleep(2500);

  await updateBadge('Homepage — Verified Customer Reviews & Trust Highlights');
  await smoothScroll(900, 25, 45);
  await sleep(2000);

  await updateBadge('Homepage — Footer & Helpline Links');
  await smoothScroll(900, 20, 40);
  await sleep(1500);

  // 2. Shop Catalog
  await navigate('http://127.0.0.1:8000/shop', 2500);
  await updateBadge('Shop Catalog — Multi-Category Filters & Live Sorting');
  await sleep(1500);
  await smoothScroll(500, 20, 45);
  await sleep(2000);

  // 3. Product Details
  await navigate('http://127.0.0.1:8000/products/red-white-rose-flower-fairy-string-lights', 3000);
  await updateBadge('Product Details — Gallery, Specifications & Low-Stock Urgency');
  await sleep(2000);
  await smoothScroll(400, 15, 45);
  await sleep(1500);

  await updateBadge('Adding Item to Shopping Cart...');
  await send('Runtime.evaluate', {
    expression: `
      const btn = document.querySelector('button[type="button"].store-btn-primary') || Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Add to Cart'));
      if (btn) btn.click();
    `
  });
  await sleep(2000);

  // 4. Cart Page
  await navigate('http://127.0.0.1:8000/cart', 2500);
  await updateBadge('Shopping Cart — Subtotal & Line Item Calculation');
  await sleep(2000);

  // 5. Checkout
  await navigate('http://127.0.0.1:8000/checkout', 2500);
  await updateBadge('One-Page Checkout — Delivery Information & COD');
  await sleep(1000);

  // Type in details smoothly
  await send('Runtime.evaluate', {
    expression: `
      const nameInput = document.querySelector('input[name="customer_name"], input[name="name"], #customer_name');
      if (nameInput) { nameInput.value = 'Mohammad Ali Nayeem'; nameInput.dispatchEvent(new Event('input', { bubbles: true })); }
      const phoneInput = document.querySelector('input[name="customer_phone"], input[name="phone"], #customer_phone');
      if (phoneInput) { phoneInput.value = '01943124211'; phoneInput.dispatchEvent(new Event('input', { bubbles: true })); }
      const addrInput = document.querySelector('textarea[name="shipping_address"], input[name="address"], #shipping_address');
      if (addrInput) { addrInput.value = 'House 12, Road 4, Sector 10, Mirpur, Dhaka'; addrInput.dispatchEvent(new Event('input', { bubbles: true })); }
    `
  });
  await sleep(2500);

  // 6. Order Tracking
  await navigate('http://127.0.0.1:8000/track', 2000);
  await updateBadge('Order Tracking — 24/7 Self-Service Shipment Status');
  await sleep(2000);

  // 7. Admin Dashboard
  await navigate('http://127.0.0.1:8000/dev-login-admin', 3000);
  await updateBadge('Admin Dashboard — Real-Time Sales Metrics & Analytics');
  await sleep(2500);

  // 8. Admin Products
  await navigate('http://127.0.0.1:8000/admin/products', 2500);
  await updateBadge('Admin Catalog Management — Inventory & Status Controls');
  await sleep(2000);

  // 9. Admin Create Product
  await navigate('http://127.0.0.1:8000/admin/products/create', 2500);
  await updateBadge('Admin Product Builder — Rich Text WYSIWYG Editor');
  await sleep(1500);
  await smoothScroll(600, 20, 45);
  await sleep(2000);

  // 10. Admin Activity Monitor (Fixed)
  await navigate('http://127.0.0.1:8000/admin/activity-monitor', 3000);
  await updateBadge('Super Admin Activity Monitor & Security Audit (Verified & Fixed)');
  await sleep(3000);

  // Return to Homepage
  await navigate('http://127.0.0.1:8000/', 2500);
  await updateBadge('Mama Bazar — Complete, Scalable E-Commerce');
  await sleep(2500);

  ws.close();

  console.log('=== Step 4: Stopping Screen Capture ===');
  scProc.kill('SIGINT');
  await sleep(3000);

  console.log('=== Step 5: Generating Spoken Audio Narration ===');
  const narrationScript = `
Welcome to Mama Bazar, a modern full-stack e-commerce platform built with Laravel.
On the homepage, shoppers discover dynamic promotional carousels, instant keyword search, and customer trust highlights.
The product grid features verified responsive cards with zero layout shift, real-time discount badges, and verified reviews.
Browsing the catalog offers intuitive category filters, price sliders, and instant sorting.
Each product detail page showcases high resolution galleries, localized specifications, and low stock urgency alerts.
Adding items to the cart is instant, and the streamlined one-page checkout provides effortless ordering with Cash on Delivery.
Customers can track their shipments twenty four seven through the self-service order tracking portal.
The administrator control panel provides comprehensive visibility over sales analytics, inventory management, and rich text product creation.
And the enterprise activity monitor ensures complete security auditing and operational logging.
Mama Bazar delivers a fast, reliable, and beautiful e-commerce experience.
  `.trim();

  fs.writeFileSync('/tmp/narration.txt', narrationScript);
  execSync(`say -v Samantha -f /tmp/narration.txt -o /tmp/full_narration.aiff`);
  execSync(`ffmpeg -y -i /tmp/full_narration.aiff -c:a aac -b:a 192k /tmp/full_narration.m4a`);

  console.log('=== Step 6: Mixing Video + Voiceover + Background Music ===');
  const bgMusic = 'resources/audio/background_music.mp3';

  // Get durations
  const voDuration = parseFloat(execSync(`ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 /tmp/full_narration.m4a`).toString().trim());
  console.log(`Voiceover duration: ${voDuration}s`);

  // Final Composite with FFmpeg:
  // - Input 0: Live screen recording
  // - Input 1: Voiceover narration
  // - Input 2: Background music (ducked to 15% volume, fading out smoothly)
  execSync(`
    ffmpeg -y -i "${rawVideoPath}" -i /tmp/full_narration.m4a -i "${bgMusic}" \
    -filter_complex "[1:a]volume=1.2[vo]; [2:a]volume=0.18,afade=t=in:ss=0:d=2,afade=t=out:st=${voDuration-2}:d=3[bg]; [vo][bg]amix=inputs=2:duration=first[aout]" \
    -map 0:v -map "[aout]" \
    -c:v libx264 -preset fast -crf 20 -pix_fmt yuv420p \
    -c:a aac -b:a 192k \
    -shortest full-product-demo.mp4
  `);

  console.log('=== Step 7: Publishing Video Deliverables ===');
  execSync(`cp full-product-demo.mp4 public/full-product-demo.mp4`);
  execSync(`cp full-product-demo.mp4 "/Users/mohammadalinayeem/.gemini/antigravity-ide/brain/99064a10-b3c3-4279-a0e6-3195411d0c9a/full-product-demo.mp4"`);

  // Also create customer-frontend and admin-panel excerpts
  execSync(`ffmpeg -y -i full-product-demo.mp4 -t 45 -c copy customer-frontend.mp4 && cp customer-frontend.mp4 public/customer-frontend.mp4`);
  execSync(`ffmpeg -y -i full-product-demo.mp4 -ss 45 -c copy admin-panel.mp4 && cp admin-panel.mp4 public/admin-panel.mp4`);

  console.log('=== SUCCESS: Real Screen Recording with Voiceover and Music Complete! ===');
}

main().catch(console.error);
