# Mama Bazar — Uncovered / Environmental Features Note

This document lists features or configurations that require external credentials, live merchant accounts, or third-party gateways that are bypassed or simulated in the local environment.

---

### 1. Live Payment Gateway Sandbox (SSLCommerz)
- **Status:** Integrated via `SslcommerzPaymentController.php`.
- **Reason Not Live-Charged:** In the local development sandbox, mock or Cash on Delivery (COD) payment is utilized to prevent real credit card / mobile financial service (bKash/Nagad) debiting.
- **Workflow:** Tested via standard checkout flow using Cash on Delivery.

### 2. Live SMS & WhatsApp Notification Gateways
- **Status:** Integrated via notification drivers in `app/Services/`.
- **Reason:** Real SMS dispatch to end-user mobile phones was muted to avoid incurring SMS gateway charges during automated testing.

### 3. Automated Browser Subagent Driver Installation
- **Status:** System environment limitation.
- **Reason:** The internal browser subagent tool could not auto-download `playwright-1.57.0-mac-arm64.zip` due to an upstream Azureedge CDN 404 response.
- **Resolution:** All 35 application states were captured via Google Chrome CDP automation, and compiled into both an animated WebP presentation and an interactive HTML5 video player (`public/walkthrough.html`).

### 4. Direct CLI MP4 Compilation (FFmpeg Requirement)
- **Status:** Requires FFmpeg system binary.
- **Reason:** macOS does not bundle the `ffmpeg` CLI encoder by default.
- **Resolution:** Full animated presentation compiled as WebP (`full-product-demo.webp`, `customer-frontend.webp`, `admin-panel.webp`). If native `.mp4` format is required, installing `ffmpeg` via Homebrew (`brew install ffmpeg`) enables instantaneous MP4 conversion.
