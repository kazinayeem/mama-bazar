{{-- Cookie / tracking consent banner. First-party only, remembers choice, re-openable. --}}
<div id="mb-consent-banner" class="fixed inset-x-3 bottom-3 z-[90] hidden sm:inset-x-auto sm:bottom-5 sm:right-5 sm:max-w-sm" role="dialog" aria-live="polite" aria-label="Cookie consent">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl">
        <p class="text-sm font-bold text-slate-900">We value your privacy</p>
        <p class="mt-1 text-xs leading-5 text-slate-500">
            We use essential cookies for cart &amp; checkout. With your permission we also use analytics &amp; marketing
            pixels to improve Mama Bazar. You can decline — the shop works fully either way.
        </p>
        <div class="mt-3 flex gap-2">
            <button type="button" id="mb-consent-decline" class="h-10 flex-1 rounded-full border border-slate-300 text-xs font-bold text-slate-600 transition hover:bg-slate-50">Decline</button>
            <button type="button" id="mb-consent-accept" class="h-10 flex-1 rounded-full bg-brand-green-600 text-xs font-bold text-white transition hover:bg-brand-green-700">Accept</button>
        </div>
    </div>
</div>
<script>
(function () {
    function show() {
        var el = document.getElementById('mb-consent-banner');
        if (el) el.classList.remove('hidden');
    }
    function hide() {
        var el = document.getElementById('mb-consent-banner');
        if (el) el.classList.add('hidden');
    }
    window.mbConsentShow = show;
    document.addEventListener('DOMContentLoaded', function () {
        if (window.mbConsent && window.mbConsent.status() === 'pending') {
            setTimeout(show, 800);
        }
        var a = document.getElementById('mb-consent-accept');
        var d = document.getElementById('mb-consent-decline');
        if (a) a.addEventListener('click', function () { window.mbConsent.set(true); hide(); });
        if (d) d.addEventListener('click', function () { window.mbConsent.set(false); hide(); });
    });
})();
</script>
