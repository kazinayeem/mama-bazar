@extends('layouts.admin', ['headerTitle' => 'Email Management'])

@section('content')
<div class="admin-page max-w-3xl">
    <x-admin.page-header title="Email Management" subtitle="One-time database setup is required before the email system can be used." />

    <div class="admin-surface p-5 space-y-4">
        <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
            The email system database migrations have not been run on this database yet. Email pages are disabled until they are applied.
        </div>

        <div class="space-y-2 text-xs text-slate-700">
            <p class="font-semibold text-slate-900">Run these steps on the server, from the Laravel project folder:</p>
            <ol class="list-decimal space-y-1 pl-5">
                <li>Back up the database (cPanel › Backup, or <code>mysqldump</code>).</li>
                <li>Check what is pending: <code class="rounded bg-slate-100 px-1">php artisan migrate:status</code></li>
                <li>Apply the migrations: <code class="rounded bg-slate-100 px-1">php artisan migrate --force</code></li>
                <li>Reload this page.</li>
            </ol>
        </div>

        @if(!empty($missing))
            <div class="text-xs text-slate-500">
                <p class="font-semibold text-slate-700">Missing database objects</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach($missing as $item)
                        <li><code>{{ $item }}</code></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection
