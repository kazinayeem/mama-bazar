@extends('layouts.admin', ['headerTitle' => 'Team Members'])

@section('content')
<div class="admin-page" x-data="{
    createModalOpen: false,
    editModalOpen: false,
    editingMember: {},
    openEdit(m) {
        this.editingMember = { ...m, password: '' };
        this.editModalOpen = true;
    }
}">
    <x-admin.page-header title="Team Members" subtitle="Admin staff, roles, and security audit log">
        <x-slot:actions>
            <x-admin.button type="button" size="sm" @click="createModalOpen = true">
                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Member
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($errors->any())
        <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- Create Member Modal -->
    <x-admin.modal name="createModalOpen" title="Add New Team Member" subtitle="Create an administrative account with role-based access">
        <form action="{{ route('admin.members.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Name *</label>
                    <input name="name" required class="admin-control w-full text-sm" placeholder="Full Name">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Phone *</label>
                    <input name="phone" required class="admin-control w-full text-sm" placeholder="01XXXXXXXXX">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Email</label>
                    <input type="email" name="email" class="admin-control w-full text-sm" placeholder="user@example.com">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Password *</label>
                    <input type="password" name="password" required class="admin-control w-full text-sm" placeholder="Min 6 characters">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Role *</label>
                    <select name="role" required class="admin-control w-full text-sm">
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="editor">Editor</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Status</label>
                    <select name="status" class="admin-control w-full text-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <x-admin.button type="button" variant="outline" size="sm" @click="createModalOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Create Member</x-admin.button>
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
                    <label class="mb-1 block text-xs font-bold text-slate-700">Email</label>
                    <input type="email" name="email" x-model="editingMember.email" class="admin-control w-full text-sm">
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

    <div class="admin-table-wrap">
        <div class="border-b bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Team</div>
        <table class="admin-table">
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
                            <div class="inline-flex items-center justify-end gap-2">
                                <button type="button" @click="openEdit(@js($member))" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    Edit
                                </button>
                                <form action="{{ route('admin.members.destroy', $member->id) }}" method="POST" onsubmit="return confirm('Remove member?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" @disabled($member->id === auth()->id()) title="{{ $member->id === auth()->id() ? 'You cannot remove your own account' : 'Remove member' }}" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No team members</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-table-wrap">
        <div class="border-b bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Security Audit Log</div>
        <table class="admin-table">
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
</div>
@endsection
