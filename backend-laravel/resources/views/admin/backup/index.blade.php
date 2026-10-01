@extends('layouts.admin', ['headerTitle' => 'Database Backup & Restore'])

@section('content')
<div class="admin-page">
<div class="max-w-4xl mx-auto space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Create Backup Card -->
        <div class="admin-surface p-4 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Create SQLite Database Snapshot</h3>
            <p class="text-xs text-slate-500">Backs up all 41 SQLite database tables, records, schemas, and manifest into a secure downloadable ZIP package.</p>

            <form action="{{ route('admin.backup.create') }}" method="POST" class="space-y-4 pt-2">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Security PIN Verification *</label>
                    <input type="password" name="pin" required placeholder="Enter security PIN" 
                        class="w-full text-xs admin-control focus:border-brand-green-500 focus:outline-none font-mono">
                    <p class="text-[10px] text-slate-400 mt-1">Hint: {{ $challenge ?? 'Server Challenge Code is active.' }}</p>
                </div>

                <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-[6px] bg-brand-green-500 text-sm font-medium text-white hover:bg-brand-green-600">
                    Generate Full Backup Now
                </button>
            </form>
        </div>

        <!-- Info Card -->
        <div class="admin-surface p-4 space-y-3 text-xs">
            <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs border-b border-slate-100 pb-2">Backup Architecture</h3>
            <p class="text-slate-600">Mama Bazar uses SQLite as its primary database file at <code class="bg-slate-100 px-1 py-0.5 rounded text-brand-green-700">database/database.sqlite</code>.</p>
            <div class="p-3 rounded-[10px] bg-brand-green-50/60 border border-brand-green-100 space-y-1">
                <span class="font-bold text-brand-green-800 text-[11px] block">Safe Atomic Restores</span>
                <p class="text-slate-600 text-[11px]">When restoring, a pre-restore safety snapshot is automatically generated before importing.</p>
            </div>
            <p class="text-slate-500">All backup archives are stored in <code class="bg-slate-100 px-1 py-0.5 rounded">storage/app/backups/</code>.</p>
        </div>

    </div>

    <!-- Existing Backups Table -->
    <div class="admin-table-wrap">
        <div class="p-4 border-b border-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">Stored Backups ({{ count($backups) }})</div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="p-4">Filename</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Size</th>
                        <th class="p-4">Tables</th>
                        <th class="p-4">Created Date</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($backups as $b)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="p-4 font-mono font-bold text-brand-green-700">{{ $b['filename'] }}</td>
                            <td class="p-4 uppercase text-[10px] font-semibold text-slate-500">{{ $b['type'] ?? 'manual' }}</td>
                            <td class="p-4 text-slate-600">{{ number_format(($b['size'] ?? 0) / 1024, 1) }} KB</td>
                            <td class="p-4 text-slate-600">{{ $b['table_count'] ?? 41 }}</td>
                            <td class="p-4 text-slate-500">{{ $b['created_at'] ?? 'Recently' }}</td>
                            <td class="p-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.backup.download', $b['id']) }}" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-[11px] font-bold text-slate-700 hover:bg-slate-50">Download</a>
                                    <form action="{{ route('admin.backup.destroy', $b['id']) }}" method="POST" class="flex items-center gap-1" onsubmit="return confirm('Permanently delete this backup archive?');">
                                        @csrf @method('DELETE')
                                        <input type="password" name="pin" required placeholder="PIN" title="Security PIN required" class="w-20 rounded-lg border border-slate-200 px-2 py-1.5 font-mono text-[11px]">
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-red-200 text-[11px] font-bold text-red-600 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">No backups created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Restore Card (destructive — PIN protected, safety snapshot auto-created) -->
    <div class="admin-surface p-4 space-y-3 border-red-100">
        <h3 class="text-sm font-bold text-red-700 border-b border-red-100 pb-2">Restore From Archive (Danger Zone)</h3>
        @if($errors->any())
            <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <form action="{{ route('admin.backup.restore') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2" onsubmit="return confirm('Restore will replace the live database (a safety snapshot is auto-created first). Continue?');">
            @csrf
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Backup .zip file</label>
                <input type="file" name="file" accept=".zip" required class="text-xs admin-control file:mr-2 file:rounded file:border-0 file:bg-slate-100 file:px-2 file:py-1">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Security PIN *</label>
                <input type="password" name="pin" required placeholder="Enter security PIN" class="text-xs admin-control font-mono">
            </div>
            <button type="submit" class="h-9 px-4 rounded-[6px] bg-red-600 text-xs font-bold text-white hover:bg-red-700">Restore Database</button>
        </form>
    </div>

</div>
</div>
@endsection
