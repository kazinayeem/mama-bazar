@extends('layouts.admin', ['headerTitle' => 'Database Backup & Restore'])

@section('content')
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
                        class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none font-mono">
                    <p class="text-[10px] text-slate-400 mt-1">Hint: Server Challenge Code is active.</p>
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
            <div class="p-3 rounded-2xl bg-brand-green-50/60 border border-brand-green-100 space-y-1">
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400">No backups created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
