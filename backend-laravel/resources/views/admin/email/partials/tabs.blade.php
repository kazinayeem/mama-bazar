@php
    $emailTabs = [
        ['label' => 'Dashboard', 'route' => 'admin.email.dashboard', 'match' => 'admin.email.dashboard', 'perm' => 'email.view'],
        ['label' => 'SMTP Settings', 'route' => 'admin.email.settings', 'match' => 'admin.email.settings*', 'perm' => 'email.settings.manage'],
        ['label' => 'Templates', 'route' => 'admin.email.templates.index', 'match' => 'admin.email.templates.*', 'perm' => 'email.templates.manage'],
        ['label' => 'Campaigns', 'route' => 'admin.email.campaigns.index', 'match' => 'admin.email.campaigns.*', 'perm' => 'email.campaigns.manage|email.campaigns.send'],
        ['label' => 'Automation', 'route' => 'admin.email.automation', 'match' => 'admin.email.automation*', 'perm' => 'email.settings.manage'],
        ['label' => 'Logs', 'route' => 'admin.email.logs.index', 'match' => 'admin.email.logs.*', 'perm' => 'email.logs.view'],
    ];
@endphp
<nav class="flex gap-1 overflow-x-auto border-b border-[var(--admin-border)] text-sm" aria-label="Email management">
    @foreach($emailTabs as $tab)
        @if(\App\Http\Middleware\EnsureAdminPermission::allows(auth()->user(), explode('|', $tab['perm'])))
            <a href="{{ route($tab['route']) }}"
               class="whitespace-nowrap border-b-2 px-3 py-2 font-medium transition {{ request()->routeIs($tab['match']) ? 'border-brand-green-600 text-brand-green-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                {{ $tab['label'] }}
            </a>
        @endif
    @endforeach
</nav>

@if($errors->any())
    <div class="rounded-[8px] border border-red-200 bg-red-50 p-3 text-xs text-red-700 space-y-1" role="alert">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
