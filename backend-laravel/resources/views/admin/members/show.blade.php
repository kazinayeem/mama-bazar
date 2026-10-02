@extends('layouts.admin', ['headerTitle' => 'Team Member: ' . $member->name])

@section('content')
<div class="admin-page space-y-6" x-data="{
    editModalOpen: false,
    editingMember: {
        id: {{ $member->id }},
        name: '{{ addslashes($member->name) }}',
        phone: '{{ addslashes($member->phone) }}',
        email: '{{ addslashes($member->email ?? '') }}',
        role: '{{ $member->role }}',
        status: '{{ $member->status }}',
        password: ''
    }
}">
    <x-admin.page-header title="{{ $member->name }}" subtitle="Administrator profile, activity monitoring, and login tracking history">
        <x-slot:actions>
            <a href="{{ route('admin.members.index') }}" class="inline-flex items-center gap-1.5 rounded-[6px] border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Team
            </a>
            <x-admin.button type="button" size="sm" @click="editModalOpen = true">
                <svg class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Edit Member
            </x-admin.button>
            @if($member->email)
                <form action="{{ route('admin.members.resend-invitation', $member->id) }}" method="POST" onsubmit="return confirm('Send an account invitation email to {{ $member->email }}?')" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-[6px] border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 shadow-xs hover:bg-emerald-100 transition">
                        <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Resend Invitation Link
                    </button>
                </form>
            @endif
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

    <!-- Profile & Metrics Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-[8px] border border-slate-200 bg-white p-4 shadow-panel">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Account Status</p>
            <div class="mt-2 flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-slate-800">
                    <span class="h-2.5 w-2.5 rounded-full {{ $member->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    {{ ucfirst($member->status) }}
                </span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ match($member->role) {
                    'super_admin', 'admin' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
                    'manager' => 'bg-sky-100 text-sky-800',
                    default => 'bg-slate-100 text-slate-700',
                } }}">
                    {{ $member->custom_role ?: $member->role }}
                </span>
            </div>
            <p class="mt-3 text-[11px] text-slate-400">Created: {{ optional($member->created_at)->format('M d, Y H:i') ?? '—' }}</p>
        </div>

        <div class="rounded-[8px] border border-slate-200 bg-white p-4 shadow-panel">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Successful Logins</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-brand-green-700">{{ $successfulLogins }}</p>
            <p class="mt-2 text-[11px] text-slate-400">Total login attempts: {{ $totalLogins }} (Failed: {{ $failedLogins }})</p>
        </div>

        <div class="rounded-[8px] border border-slate-200 bg-white p-4 shadow-panel">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Most Recent Login</p>
            <p class="mt-1 text-sm font-bold text-slate-800">
                {{ $member->last_login_at ? $member->last_login_at->format('M d, Y H:i') : 'Never logged in' }}
            </p>
            <p class="mt-2 font-mono text-[11px] text-slate-500">
                {{ $member->last_login_ip ?: 'No IP record' }}
            </p>
        </div>

        <div class="rounded-[8px] border border-slate-200 bg-white p-4 shadow-panel">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Approximate Location</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">
                {{ $member->last_login_location ?: 'Location unavailable' }}
            </p>
            <p class="mt-2 text-[11px] text-slate-400">Derived from IP (approximate country/city)</p>
        </div>
    </div>

    <!-- Member Profile Overview Card -->
    <div class="rounded-[8px] border border-slate-200 bg-white p-6 shadow-panel">
        <h2 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-brand-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Member Information
        </h2>
        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 text-xs">
            <div>
                <span class="text-slate-400 block font-medium">Full Name</span>
                <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $member->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium">Email Address</span>
                <span class="font-mono text-slate-800 mt-0.5 block">{{ $member->email ?: '—' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium">Phone Number</span>
                <span class="font-medium text-slate-800 mt-0.5 block">{{ $member->phone }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium">Invitation Status</span>
                <div class="mt-1">
                    @if($member->isInvitationPending())
                        <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            Invitation Pending
                        </span>
                    @elseif($member->invitation_accepted_at)
                        <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200">
                            <svg class="h-3 w-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Accepted ({{ $member->invitation_accepted_at->format('M d, Y') }})
                        </span>
                    @else
                        <span class="text-slate-500 font-medium">Active Member</span>
                    @endif
                </div>
            </div>
            <div>
                <span class="text-slate-400 block font-medium">Password Status</span>
                <span class="mt-1 block font-medium {{ $member->must_change_password ? 'text-amber-600' : 'text-slate-700' }}">
                    {{ $member->must_change_password ? '⚠ Password change required on next login' : 'Configured' }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium">Account ID</span>
                <span class="font-mono text-slate-500 mt-0.5 block">#{{ $member->id }}</span>
            </div>
        </div>
    </div>

    <!-- Login History Table -->
    <div class="admin-table-wrap">
        <div class="border-b bg-slate-50 px-4 py-3 sm:flex sm:items-center sm:justify-between">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Login Tracking History</h3>
                <p class="text-[11px] text-slate-400">Chronological authentication attempts and IP geolocation records</p>
            </div>
            <div class="mt-2 sm:mt-0 flex items-center gap-2">
                <a href="{{ route('admin.members.show', $member->id) }}" class="rounded px-2.5 py-1 text-xs font-medium {{ !$statusFilter ? 'bg-slate-200 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                    All ({{ $totalLogins }})
                </a>
                <a href="{{ route('admin.members.show', ['id' => $member->id, 'status' => 'success']) }}" class="rounded px-2.5 py-1 text-xs font-medium {{ $statusFilter === 'success' ? 'bg-emerald-100 text-emerald-800 font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                    Success ({{ $successfulLogins }})
                </a>
                <a href="{{ route('admin.members.show', ['id' => $member->id, 'status' => 'failure']) }}" class="rounded px-2.5 py-1 text-xs font-medium {{ $statusFilter === 'failure' ? 'bg-rose-100 text-rose-800 font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                    Failed ({{ $failedLogins }})
                </a>
            </div>
        </div>

        <table class="admin-table">
            <thead class="border-b text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Date & Time</th>
                    <th class="px-4 py-3">Result</th>
                    <th class="px-4 py-3">IP Address</th>
                    <th class="px-4 py-3">Approximate Location</th>
                    <th class="px-4 py-3">Browser / Device</th>
                    <th class="px-4 py-3">Operating System</th>
                </tr>
            </thead>
            <tbody class="divide-y text-xs">
                @forelse($loginHistories as $history)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-700 whitespace-nowrap">
                            {{ optional($history->logged_at)->format('Y-m-d H:i:s') ?: '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($history->status === 'success')
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Success
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-200" title="{{ $history->failure_reason }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    Failed: {{ Str::limit($history->failure_reason ?: 'Invalid credentials', 20) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-600">
                            {{ $history->ip_address }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $history->location ?: 'Location unavailable' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $history->browser ?: 'Unknown' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $history->platform ?: 'Unknown' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                            No login history records found for this member.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($loginHistories->hasPages())
            <div class="border-t border-slate-100 p-4">
                {{ $loginHistories->links() }}
            </div>
        @endif
    </div>

    <!-- Security Audit Log for Member -->
    <div class="admin-table-wrap">
        <div class="border-b bg-slate-50 px-4 py-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Security Audit Events</h3>
            <p class="text-[11px] text-slate-400">Immutable administrative actions involving this account</p>
        </div>
        <table class="admin-table">
            <thead class="border-b text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Timestamp</th>
                    <th class="px-4 py-3">Actor</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y text-xs">
                @forelse($auditLogs as $log)
                    <tr>
                        <td class="px-4 py-3 text-slate-500 font-mono">
                            {{ optional($log->created_at)->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-800">
                            {{ $log->actor_name }}
                        </td>
                        <td class="px-4 py-3 font-mono font-medium text-slate-700">
                            {{ $log->action }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $log->status === 'success' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' }}">
                                {{ $log->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-400">
                            {{ $log->ip_address ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                            No security audit logs recorded for this member.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Edit Member Modal -->
    <x-admin.modal name="editModalOpen" title="Edit Member: {{ $member->name }}" subtitle="Update account details, role permissions, and status">
        <form action="{{ route('admin.members.update', $member->id) }}" method="POST" class="space-y-4">
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
                    <label class="mb-1 block text-xs font-bold text-slate-700">New Password <span class="font-normal text-slate-400">(leave blank to keep)</span></label>
                    <input type="password" name="password" x-model="editingMember.password" class="admin-control w-full text-sm" placeholder="Min 8 characters">
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
                    <label class="mb-1 block text-xs font-bold text-slate-700">Status *</label>
                    <select name="status" x-model="editingMember.status" required class="admin-control w-full text-sm">
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
</div>
@endsection
