@extends('layouts.admin', ['headerTitle' => 'Team Members'])

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Team Members</h1>
        <p class="text-sm text-slate-500">Admin staff, roles, and security audit log</p>
    </div>
    <button type="button" onclick="document.getElementById('member-form').classList.toggle('hidden')"
            class="rounded-full bg-brand-green-500 px-5 py-2.5 text-sm font-medium text-white">Add Member</button>
</div>

<div id="member-form" class="mt-4 hidden rounded-xl border bg-white p-5 shadow-soft">
    <form action="{{ route('admin.members.store') }}" method="POST" class="grid gap-3 sm:grid-cols-2">
        @csrf
        <div><label class="mb-1 block text-xs font-bold">Name *</label><input name="name" required class="w-full rounded-xl border p-2.5 text-xs"></div>
        <div><label class="mb-1 block text-xs font-bold">Phone *</label><input name="phone" required class="w-full rounded-xl border p-2.5 text-xs"></div>
        <div><label class="mb-1 block text-xs font-bold">Email</label><input type="email" name="email" class="w-full rounded-xl border p-2.5 text-xs"></div>
        <div><label class="mb-1 block text-xs font-bold">Password *</label><input type="password" name="password" required class="w-full rounded-xl border p-2.5 text-xs"></div>
        <div>
            <label class="mb-1 block text-xs font-bold">Role *</label>
            <select name="role" required class="w-full rounded-xl border bg-white p-2.5 text-xs">
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="editor">Editor</option>
                <option value="staff">Staff</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold">Status</label>
            <select name="status" class="w-full rounded-xl border bg-white p-2.5 text-xs">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <div class="sm:col-span-2 flex justify-end"><button class="rounded-full bg-brand-green-500 px-5 py-2 text-sm font-medium text-white">Create Member</button></div>
    </form>
</div>

<div class="mt-4 overflow-hidden rounded-xl border bg-white shadow-soft">
    <div class="border-b bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Team</div>
    <table class="w-full text-sm">
        <thead class="border-b text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3 text-left">Member</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Last Login</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($members as $member)
                @php
                    $roleCls = match($member->role) {
                        'super_admin', 'admin' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
                        'manager' => 'bg-sky-100 text-sky-800',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp
                <tr>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-green-50 text-xs font-bold text-brand-green-700">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900">{{ $member->name }}</p>
                                <p class="text-xs text-slate-400">{{ $member->phone }} {{ $member->email ? '· '.$member->email : '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $roleCls }}">{{ $member->custom_role ?: $member->role }}</span></td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full {{ $member->status==='active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                            {{ $member->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ optional($member->last_login_at)->format('Y-m-d H:i') ?: '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <form action="{{ route('admin.members.destroy', $member->id) }}" method="POST" onsubmit="return confirm('Remove member?')" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-xs font-semibold text-red-600" @disabled($member->id === auth()->id())>Remove</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No team members</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6 overflow-hidden rounded-xl border bg-white shadow-soft">
    <div class="border-b bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Security Audit Log</div>
    <table class="w-full text-sm">
        <thead class="border-b text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3 text-left">Time</th>
                <th class="px-4 py-3">Actor</th>
                <th class="px-4 py-3">Action</th>
                <th class="px-4 py-3">Target</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($auditLogs as $log)
                <tr>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">{{ $log->actor_name }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $log->action }}</td>
                    <td class="px-4 py-3 text-xs">{{ $log->target_type }} {{ $log->target_id }}</td>
                    <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $log->status==='success' ? 'bg-brand-green-50 text-brand-green-700' : 'bg-red-50 text-red-700' }}">{{ $log->status }}</span></td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No audit events yet</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
