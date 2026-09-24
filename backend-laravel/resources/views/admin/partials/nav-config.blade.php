{{-- Exact clone of frontend/src/components/admin/adminNav.ts --}}
@php
$lucide = [
    'layout-dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
    'package' => '<path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="m7.5 4.2 9 5.2"/>',
    'tags' => '<path d="m15 5 6.3 6.3a2.4 2.4 0 0 1 0 3.4L17 19"/><path d="M9.586 5.586A2 2 0 0 0 8.172 5H3a1 1 0 0 0-1 1v5.172a2 2 0 0 0 .586 1.414L8.29 18.29a2.426 2.426 0 0 0 3.42 0l3.58-3.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="6.5" cy="9.5" r=".5" fill="currentColor"/>',
    'stamp' => '<path d="M5 18h14"/><path d="M5 14h14"/><circle cx="8" cy="8" r="3"/><path d="M11 8h9"/>',
    'folder-open' => '<path d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2"/>',
    'palette' => '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>',
    'ruler' => '<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/>',
    'store' => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M10 22v-4a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v4"/><path d="M15 5v2"/><path d="M9 5v2"/><path d="M2 7h20"/>',
    'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
    'shopping-cart' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
    'ticket-percent' => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M9 9h.01"/><path d="m15 9-6 6"/><path d="M15 15h.01"/>',
    'megaphone' => '<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
    'receipt-text' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/>',
    'list-ordered' => '<path d="M10 12h11"/><path d="M10 18h11"/><path d="M10 6h11"/><path d="M4 10h1"/><path d="M4 6h1.5a1.5 1.5 0 0 1 0 3H4v-3z"/><path d="M4 18h1.5a1.5 1.5 0 0 0 0-3H4v3z"/>',
    'chart-pie' => '<path d="M21 12c.552 0 1.005-.449.95-.998a10 10 0 0 0-8.953-8.951c-.55-.055-.998.398-.998.95v8a1 1 0 0 0 1 1z"/><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>',
    'wallet' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
    'map-pin' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
    'credit-card' => '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
    'bell-ring' => '<path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M22 8c0-2.3-.8-4.3-2-6"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/><path d="M4 2C2.8 3.7 2 5.7 2 8"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'panels-top-left' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>',
    'file-text' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
    'image' => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
    'bar-chart-3' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
    'user-check' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>',
    'database-backup' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 12a9 3 0 0 0 5 2.69"/><path d="M21 9.3V5"/><path d="M3 5v14a9 3 0 0 0 6.47 2.88"/><path d="M12 12v4h4"/><path d="M13 20a5 5 0 0 0 9-3 4.5 4.5 0 0 0-4.5-4.5c-1.33 0-2.54.54-3.41 1.41L12 16"/>',
    'boxes' => '<path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.5l-5-3-4.03 2.42Z"/><path d="m7 16.5-4.74-2.85"/><path d="m7 16.5 5-3"/><path d="M7 16.5v5.17"/><path d="M12 13.5V19l3.97 2.38a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5l-5 3Z"/><path d="m17 16.5-5-3"/><path d="m17 16.5 4.74-2.85"/><path d="M17 16.5v5.17"/><path d="M7.97 4.42A2 2 0 0 0 7 6.13v4.37l5 3 5-3V6.13a2 2 0 0 0-.97-1.71l-3-1.8a2 2 0 0 0-2.06 0l-3 1.8Z"/><path d="M12 8 7.26 5.15"/><path d="m12 8 4.74-2.85"/><path d="M12 13.5V8"/>',
    'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
];

$adminNavSections = [
    ['label' => 'Overview', 'items' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'layout-dashboard'],
    ]],
    ['label' => 'Catalog', 'items' => [
        ['label' => 'Products', 'route' => 'admin.products.index', 'match' => 'admin.products.*', 'icon' => 'package'],
        ['label' => 'Categories', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'icon' => 'tags'],
        ['label' => 'Brands', 'route' => 'admin.brands.index', 'match' => 'admin.brands.*', 'icon' => 'stamp'],
        ['label' => 'Collections', 'route' => 'admin.collections.index', 'match' => 'admin.collections.*', 'icon' => 'folder-open'],
        ['label' => 'Colors', 'route' => 'admin.colors.index', 'match' => 'admin.colors.*', 'icon' => 'palette'],
        ['label' => 'Sizes', 'route' => 'admin.sizes.index', 'match' => 'admin.sizes.*', 'icon' => 'ruler'],
        ['label' => 'Vendors', 'route' => 'admin.vendors.index', 'match' => 'admin.vendors.*', 'icon' => 'store'],
        ['label' => 'Suppliers', 'route' => 'admin.suppliers.index', 'match' => 'admin.suppliers.*', 'icon' => 'truck'],
    ]],
    ['label' => 'Sales', 'items' => [
        ['label' => 'Orders', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'icon' => 'shopping-cart'],
        ['label' => 'Coupons', 'route' => 'admin.coupons.index', 'match' => 'admin.coupons.*', 'icon' => 'ticket-percent'],
        ['label' => 'Marketing', 'route' => 'admin.marketing.index', 'match' => 'admin.marketing.*', 'icon' => 'megaphone'],
    ]],
    ['label' => 'Finance', 'items' => [
        ['label' => 'Expenses', 'route' => 'admin.expenses.index', 'match' => 'admin.expenses.index', 'icon' => 'receipt-text'],
        ['label' => 'Expense Categories', 'route' => 'admin.expense-categories.index', 'match' => 'admin.expense-categories.*', 'icon' => 'list-ordered'],
        ['label' => 'Expense Reports', 'route' => 'admin.expenses.reports', 'match' => 'admin.expenses.reports', 'icon' => 'chart-pie', 'params' => []],
        ['label' => 'Profit Overview', 'route' => 'admin.expenses.reports', 'match' => 'admin.expenses.reports', 'icon' => 'wallet', 'params' => ['tab' => 'profit']],
    ]],
    ['label' => 'Checkout', 'items' => [
        ['label' => 'Shipping Methods', 'route' => 'admin.shipping.index', 'match' => 'admin.shipping.*', 'icon' => 'map-pin'],
        ['label' => 'Payment Methods', 'route' => 'admin.payment-methods.index', 'match' => 'admin.payment-methods.*', 'icon' => 'credit-card'],
        ['label' => 'Checkout Notices', 'route' => 'admin.checkout-notices.index', 'match' => 'admin.checkout-notices.*', 'icon' => 'bell-ring'],
    ]],
    ['label' => 'Customers', 'items' => [
        ['label' => 'Customers', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'icon' => 'users'],
    ]],
    ['label' => 'Content', 'items' => [
        ['label' => 'Homepage Builder', 'route' => 'admin.homepage.index', 'match' => 'admin.homepage.*', 'icon' => 'panels-top-left'],
        ['label' => 'Policies & Messages', 'route' => 'admin.policies.index', 'match' => 'admin.policies.*', 'icon' => 'file-text'],
        ['label' => 'Media Library', 'route' => 'admin.media.index', 'match' => 'admin.media.*', 'icon' => 'image'],
        ['label' => 'Banners', 'route' => 'admin.banners.index', 'match' => 'admin.banners.*', 'icon' => 'megaphone'],
    ]],
    ['label' => 'Insights', 'items' => [
        ['label' => 'Analytics', 'route' => 'admin.analytics.index', 'match' => 'admin.analytics.*', 'icon' => 'bar-chart-3'],
    ]],
    ['label' => 'Security & Access', 'items' => [
        ['label' => 'Team Members', 'route' => 'admin.members.index', 'match' => 'admin.members.*', 'icon' => 'user-check'],
        ['label' => 'Backup & Restore', 'route' => 'admin.backup.index', 'match' => 'admin.backup.*', 'icon' => 'database-backup'],
    ]],
    ['label' => 'System', 'items' => [
        ['label' => 'Inventory', 'route' => 'admin.inventory.index', 'match' => 'admin.inventory.*', 'icon' => 'boxes'],
        ['label' => 'Settings', 'route' => 'admin.settings.index', 'match' => 'admin.settings.*', 'icon' => 'settings'],
    ]],
];

$allAdminNavItems = [];
foreach ($adminNavSections as $section) {
    foreach ($section['items'] as $item) {
        $allAdminNavItems[] = array_merge($item, ['section' => $section['label']]);
    }
}
@endphp
