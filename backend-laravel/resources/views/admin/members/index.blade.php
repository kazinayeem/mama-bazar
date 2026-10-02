@extends('layouts.admin', ['headerTitle' => 'Team Members'])

@section('content')
<div class="admin-page space-y-6" x-data="{
    createModalOpen: false,
    editModalOpen: false,
    editingMember: {},
    openEdit(m) {
        this.editingMember = { ...m, password: '' };
        this.editModalOpen = true;
    }
}">
    <x-admin.page-header title="Team Members" subtitle="Admin staff, roles, login tracking, and security audit log">
        <x-slot:actions>
            <x-admin.button type="button" size="sm" @click="createModalOpen = true">
                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Member
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if(session('success'))
        <div class="rounded-[8px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="rounded-[8px] border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-800">
            {{ session('warning') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-[8px] border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- Create Member Modal -->
    <x-admin.modal name="createModalOpen" title="Add New Team Member" subtitle="Create an administrative account with role-based access and automated invitation email">
        <form action="{{ route('admin.members.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Full Name *</label>
                    <input name="name" value="{{ old('name') }}" required class="admin-control w-full text-sm" placeholder="Full Name">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Phone Number *</label>
                    <input name="phone" value="{{ old('phone') }}" required class="admin-control w-full text-sm" placeholder="01XXXXXXXXX">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="admin-control w-full text-sm" placeholder="member@mama-bazar.com">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Role *</label>
                    <select name="role" required class="admin-control w-full text-sm">
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="editor" {{ old('role') === 'editor' ? 'selected' : '' }}>Editor</option>
                        <option value="staff" {{ old('role', 'staff') === 'staff' ? 'selected' : '' }}>Staff</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Account Status</label>
                    <select name="status" class="admin-control w-full text-sm">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="rounded-lg border border-sky-200 bg-sky-50/70 p-3 text-xs text-sky-800">
                <div class="flex items-start gap-2">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-bold">Automated Security Invitation:</span> A single-use, secure invitation link will be queued and sent to this email address. The member will establish their own password upon first access.
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <x-admin.button type="button" variant="outline" size="sm" @click="createModalOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Create Member & Send Invitation</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Edit Member Modal -->
    <x-admin.modal name="editModalOpen" x-title="'Edit Member: ' + (editingMember ? editingMember.name : '')" subtitle="Update account details, role permissions, and access status">
        <form :action="'{{ url('admin/members') }}/' + (editingMember && editingMember.id ? editingMember.id : '')" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Name *</label>
                    <input name="name" x-model="editingMember.name" required class="admin-control w-full text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Phone *</label>
                    <input name="phone" x-model="editingMember.phone" required class="admin-control w-full text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Email *</label>
                    <input type="email" name="email" x-model="editingMember.email" required class="admin-control w-full text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">New Password <span class="font-normal text-slate-400">(leave blank to keep current)</span></label>
                    <input type="password" name="password" x-model="editingMember.password" class="admin-control w-full text-sm" placeholder="••••••••">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Role *</label>
                    <select name="role" x-model="editingMember.role" required class="admin-control w-full text-sm">
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="editor">Editor</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Status</label>
                    <select name="status" x-model="editingMember.status" class="admin-control w-full text-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <x-admin.button type="button" variant="outline" size="sm" @click="editModalOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Search & Filter Controls -->
    <div class="rounded-[8px] border border-slate-200 bg-white p-4 shadow-panel">
        <form method="GET" action="{{ route('admin.members.index') }}" class="grid gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Search Members</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name, email, or phone..."
                    class="admin-control w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Role</label>
                <select name="role" class="admin-control w-full text-xs">
                    <option value="">All Roles</option>
                    <option value="admin" {{ ($roleFilter ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="manager" {{ ($roleFilter ?? '') === 'manager' ? 'selected' : '' }}>Manager</option>
                    <option value="editor" {{ ($roleFilter ?? '') === 'editor' ? 'selected' : '' }}>Editor</option>
                    <option value="staff" {{ ($roleFilter ?? '') === 'staff' ? 'selected' : '' }}>Staff</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Status</label>
                    <select name="status" class="admin-control w-full text-xs">
                        <option value="">All Statuses</option>
                        <option value="active" {{ ($statusFilter ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($statusFilter ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="rounded-[6px] bg-brand-green-500 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-brand-green-600 transition">
                    Filter
                </button>
                @if($search || $roleFilter || $statusFilter)
                    <a href="{{ route('admin.members.index') }}" class="rounded-[6px] border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Enhanced Team Members Table -->
    <div class="admin-table-wrap">
        <div class="border-b bg-slate-50 px-4 py-3 sm:flex sm:items-center sm:justify-between">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Team Members Directory</div>
            <div class="text-xs text-slate-400">Total: {{ $members->total() }}</div>
        </div>
        <div class="overflow-x-auto">
            <table class="admin-table min-w-full">
                <thead class="border-b text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Member</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Last Login</th>
                        <th class="px-4 py-3">IP Address</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y text-xs">
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
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-green-50 text-xs font-bold text-brand-green-700">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.members.show', $member->id) }}" class="font-semibold text-slate-900 hover:text-brand-green-600 transition block truncate">
                                            {{ $member->name }}
                                        </a>
                                        <p class="text-[11px] text-slate-500 truncate">
                                            {{ $member->phone }} {{ $member->email ? '· '.$member->email : '' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $roleCls }}">
                                    {{ $member->custom_role ?: $member->role }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="space-y-1">
                                    <span class="inline-flex items-center gap-1.5 text-xs">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $member->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        {{ ucfirst($member->status) }}
                                    </span>
                                    @if($member->isInvitationPending())
                                        <div>
                                            <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-amber-200">
                                                <span class="h-1 w-1 rounded-full bg-amber-500"></span>
                                                Pending Invite
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                @if($member->last_login_at)
                                    <span class="font-medium text-slate-800">{{ $member->last_login_at->format('Y-m-d H:i') }}</span>
                                    @if(isset($member->total_successful_logins) && $member->total_successful_logins > 0)
                                        <span class="block text-[10px] text-slate-400 font-mono">{{ $member->total_successful_logins }} login{{ $member->total_successful_logins > 1 ? 's' : '' }}</span>
                                    @endif
                                @else
                                    <span class="italic text-slate-400">Never logged in</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-600 whitespace-nowrap">
                                {{ $member->last_login_ip ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                {{ $member->last_login_location ?: 'Location unavailable' }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.members.show', $member->id) }}" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold text-brand-green-700 hover:bg-brand-green-50 transition" title="View details and login history">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Details
                                    </a>

                                    <button type="button" @click="openEdit(@js($member))" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition" title="Edit member">
                                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        Edit
                                    </button>

                                    @if($member->email)
                                        <form action="{{ route('admin.members.resend-invitation', $member->id) }}" method="POST" onsubmit="return confirm('Resend invitation email to {{ $member->email }}?')" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-sky-600 hover:bg-sky-50 transition" title="Resend invitation">
                                                <svg class="h-3.5 w-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                Resend
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('admin.members.destroy', $member->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently remove {{ $member->name }}?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" @disabled($member->id === auth()->id()) title="{{ $member->id === auth()->id() ? 'You cannot remove your own account' : 'Remove member' }}" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent transition">
                                            <svg class="h-3.5 w-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                No team members match your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($members->hasPages())
            <div class="border-t border-slate-100 p-4">
                {{ $members->links() }}
            </div>
        @endif
    </div>

    <!-- Security Audit Log -->
    <div class="admin-table-wrap">
        <div class="border-b bg-slate-50 px-4 py-3 sm:flex sm:items-center sm:justify-between">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Security Audit Log</div>
            <div class="text-xs text-slate-400">Latest 50 security events</div>
        </div>
        <div class="overflow-x-auto">
            <table class="admin-table min-w-full">
                <thead class="border-b text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Time</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Target</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y text-xs">
                    @forelse($auditLogs as $log)
                        <tr>
                            <td class="px-4 py-3 text-slate-500 font-mono whitespace-nowrap">
                                {{ optional($log->created_at)->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-800">
                                {{ $log->actor_name }}
                            </td>
                            <td class="px-4 py-3 font-mono font-medium text-slate-700">
                                {{ $log->action }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $log->target_type ? ucfirst($log->target_type) . ' #' . $log->target_id : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $log->status === 'success' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-400 whitespace-nowrap">
                                {{ $log->ip_address ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                No security audit events logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
