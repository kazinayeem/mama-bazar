{{-- Privacy-first marketing tags.
     - Rendered once from active DB integrations. No duplicates (GA4-direct skipped when GTM present).
     - Third-party scripts load ONLY after visitor grants consent (localStorage `mb_consent`).
     - No PII is ever sent in event payloads (ids, values, currency only).
--}}
@php
    try {
        $mbIntegrations = \App\Models\MarketingIntegration::where('status', 'active')->get();
    } catch (\Throwable $e) {
        $mbIntegrations = collect();
    }
    $mbGtmId = $mbIntegrations->firstWhere('type', 'google_tag_manager')?->pixel_id
        ?? config('services.google_tag_manager.id')
        ?? env('GTM_ID');
    $mbGaId = $mbIntegrations->firstWhere('type', 'google_analytics')?->pixel_id
        ?? $mbIntegrations->firstWhere('type', 'google_tag')?->pixel_id
        ?? config('services.google_analytics.id')
        ?? env('GOOGLE_ANALYTICS_ID')
        ?? env('GA_MEASUREMENT_ID');
    $mbFbPixel = $mbIntegrations->firstWhere('type', 'facebook_pixel')?->pixel_id;
    $mbTtPixel = $mbIntegrations->firstWhere('type', 'tiktok_pixel')?->pixel_id;
    $mbCustom = $mbIntegrations->where('type', 'custom')->filter(fn ($i) => !empty($i->script_code))->values();
    $mbTagConfig = array_filter([
        'gtmId' => $mbGtmId,
        'gaId' => (!$mbGtmId ? $mbGaId : null),
        'fbPixel' => $mbFbPixel,
        'ttPixel' => $mbTtPixel,
    ]);
    $mbHasTags = !empty($mbTagConfig) || $mbCustom->isNotEmpty();
@endphp

{{-- Consent core always renders (even with zero integrations) so the banner's
      window.mbConsent calls never throw and choice is recorded before any
      pixel is configured. Third-party scripts still load only after consent. --}}
<script>
window.dataLayer = window.dataLayer || [];
window.mbTagConfig = @js($mbTagConfig);
window.mbCustomScripts = @js($mbCustom->map(fn ($i) => $i->script_code)->values());

(function () {
    var KEY = 'mb_consent';
    function choice() { try { return localStorage.getItem(KEY); } catch (e) { return null; } }

    window.mbConsent = {
        status: function () { return choice() || 'pending'; },
        granted: function () { return choice() === 'granted'; },
        set: function (value) {
            try { localStorage.setItem(KEY, value ? 'granted' : 'denied'); } catch (e) {}
            document.dispatchEvent(new CustomEvent('mb-consent-change', { detail: { granted: !!value } }));
            if (value) window.mbTagsLoad();
        },
    };

    var tagsLoaded = false;
    function loadScript(src, onload) {
        var s = document.createElement('script');
        s.async = true; s.src = src;
        if (onload) s.onload = onload;
        document.head.appendChild(s);
    }

    window.mbTagsLoad = function () {
        if (tagsLoaded || !window.mbConsent.granted()) return;
        tagsLoaded = true;
        var cfg = window.mbTagConfig || {};
        if (cfg.gtmId) {
            (function (w, d, s, l, i) {
                w[l] = w[l] || []; w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
                var f = d.getElementsByTagName(s)[0], j = d.createElement(s);
                j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i;
                f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', cfg.gtmId);
        } else if (cfg.gaId) {
            loadScript('https://www.googletagmanager.com/gtag/js?id=' + cfg.gaId, function () {
                function gtag() { window.dataLayer.push(arguments); }
                gtag('js', new Date());
                gtag('config', cfg.gaId, { anonymize_ip: true });
            });
        }
        if (cfg.fbPixel) {
            (function (f, b, e, v, n, t, s) {
                if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
                if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = [];
                t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s);
            })(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
            try { fbq('init', cfg.fbPixel); fbq('track', 'PageView'); } catch (e) {}
        }
        if (cfg.ttPixel) {
            (function (w, d, t) {
                w.TiktokAnalyticsObject = t; var ttq = w[t] = w[t] || [];
                ttq.methods = ['page', 'track']; ttq.setAndDefer = function (o, m) { o[m] = function () { o.push([m].concat(Array.prototype.slice.call(arguments, 0))); }; };
                for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]);
                ttq.load = function (id) { var n = 'https://analytics.tiktok.com/i18n/pixel/events.js'; var o = d.createElement('script'); o.async = !0; o.src = n + '?sdkid=' + id + '&lib=' + t; var a = d.getElementsByTagName('script')[0]; a.parentNode.insertBefore(o, a); };
                ttq.load(cfg.ttPixel); ttq.page();
            })(window, document, 'ttq');
        }
        (window.mbCustomScripts || []).forEach(function (code) {
            try {
                var s = document.createElement('script');
                s.text = code;
                document.head.appendChild(s);
            } catch (e) {}
        });
    };

    // Standard e-commerce fan-out. Payloads contain no PII.
    window.mbTrack = function (eventName, params) {
        params = params || {};
        try {
            var map = {
                view_item: { dl: 'view_item', fb: 'ViewContent', tt: 'ViewContent' },
                add_to_cart: { dl: 'add_to_cart', fb: 'AddToCart', tt: 'AddToCart' },
                remove_from_cart: { dl: 'remove_from_cart', fb: null, tt: null },
                view_cart: { dl: 'view_cart', fb: null, tt: null },
                begin_checkout: { dl: 'begin_checkout', fb: 'InitiateCheckout', tt: 'InitiateCheckout' },
                add_shipping_info: { dl: 'add_shipping_info', fb: null, tt: null },
                add_payment_info: { dl: 'add_payment_info', fb: null, tt: null },
                purchase: { dl: 'purchase', fb: 'Purchase', tt: 'CompletePayment' },
            };
            var m = map[eventName] || { dl: eventName, fb: null, tt: null };
            var dlPayload = Object.assign({ event: m.dl }, params);
            window.dataLayer.push(dlPayload);
            if (!window.mbConsent.granted()) return;
            if (window.fbq && m.fb) {
                var fbParams = {};
                if (params.value !== undefined) fbParams.value = params.value;
                if (params.currency) fbParams.currency = params.currency;
                if (params.content_ids) fbParams.content_ids = params.content_ids;
                if (params.content_type) fbParams.content_type = params.content_type;
                if (params.transaction_id) fbParams.order_id = params.transaction_id;
                window.fbq('track', m.fb, fbParams, params.transaction_id ? { eventID: 'mb-' + params.transaction_id + '-' + eventName } : undefined);
            }
            if (window.ttq && window.ttq.track && m.tt) {
                window.ttq.track(m.tt, { value: params.value, currency: params.currency, content_id: (params.content_ids || []).join(',') });
            }
        } catch (e) {}
    };

    // Auto: begin_checkout on the checkout page (no template edits needed).
    document.addEventListener('DOMContentLoaded', function () {
        if (window.mbConsent.granted()) window.mbTagsLoad();
        if (document.getElementById('checkoutForm') && !sessionStorage.getItem('mb_begin_checkout')) {
            try {
                var items = JSON.parse(localStorage.getItem('mamabazar_cart') || '[]');
                var value = items.reduce(function (t, i) { return t + (i.price * i.quantity); }, 0);
                sessionStorage.setItem('mb_begin_checkout', '1');
                window.mbTrack('begin_checkout', { value: Math.round(value), currency: 'BDT' });
            } catch (e) {}
        }
    });
    document.addEventListener('mb-consent-change', function () { window.mbTagsLoad(); });
})();
</script>
