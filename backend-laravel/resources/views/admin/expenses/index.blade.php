@extends('layouts.admin', ['headerTitle' => 'Expenses'])

@section('content')
<div class="admin-page" x-data="{
    createModalOpen: false,
    editModalOpen: false,
    editingExpense: {},
    filtersOpen: {{ request()->hasAny(['q','status','category_id']) ? 'true' : 'false' }},
    openEdit(exp) {
        this.editingExpense = {
            id: exp.id,
            title: exp.title || '',
            amount: exp.amount || '',
            expense_date: exp.expense_date ? String(exp.expense_date).substring(0, 10) : '',
            category_id: exp.category_id || '',
            member_id: exp.member_id || '',
            payment_method: exp.payment_method || 'cash',
            status: exp.status || 'pending',
            reference_number: exp.reference_number || '',
            vendor: exp.vendor || '',
            description: exp.description || '',
            notes: exp.notes || ''
        };
        this.editModalOpen = true;
    }
}">
    <x-admin.page-header title="Expenses" :subtitle="$stats['count'].' records · ৳'.number_format($stats['total'], 0).' total'">
        <x-slot:actions>
            <x-admin.button type="button" size="sm" @click="createModalOpen = true">
                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Expense
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($errors->any())
        <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="admin-metric-grid">
        <x-admin.metric-card label="Total" :value="'৳'.number_format($stats['total'], 0)" />
        <x-admin.metric-card label="Approved" :value="'৳'.number_format($stats['approved'], 0)" tone="success" />
        <x-admin.metric-card label="Pending" :value="'৳'.number_format($stats['pending'], 0)" tone="warning" />
        <x-admin.metric-card label="Count" :value="number_format($stats['count'])" />
    </div>

    <!-- Create Expense Modal -->
    <x-admin.modal name="createModalOpen" title="Record New Expense" subtitle="Record an operational expense, vendor invoice, or utility payment">
        <form action="{{ route('admin.expenses.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-admin.input label="Title *" name="title" placeholder="e.g. Office internet bill" required />
                </div>
                <x-admin.input label="Amount (৳) *" type="number" name="amount" step="0.01" min="0" placeholder="0.00" required />
                <x-admin.input label="Date *" type="date" name="expense_date" value="{{ date('Y-m-d') }}" required />
                <x-admin.select label="Category" name="category_id">
                    <option value="">— Select Category —</option>
                    @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
                </x-admin.select>
                <x-admin.select label="Member / Incurred By" name="member_id">
                    <option value="">— Select Member —</option>
                    @foreach($members as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                </x-admin.select>
                <x-admin.input label="Payment Method" name="payment_method" value="cash" placeholder="Cash, bKash, Bank, etc." />
                <x-admin.select label="Status" name="status">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </x-admin.select>
                <x-admin.input label="Reference Number" name="reference_number" placeholder="Voucher # or TrxID" />
                <x-admin.input label="Vendor / Payee" name="vendor" placeholder="Vendor company or person name" />
                <div class="sm:col-span-2">
                    <x-admin.textarea label="Description" name="description" rows="2" placeholder="Details about this expense..." />
                </div>
                <div class="sm:col-span-2">
                    <x-admin.textarea label="Internal Notes" name="notes" rows="2" placeholder="Internal remarks..." />
                </div>
            </div>
            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <x-admin.button type="button" variant="outline" size="sm" @click="createModalOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Create Expense</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Edit Expense Modal -->
    <x-admin.modal name="editModalOpen" x-title="'Edit Expense: ' + (editingExpense ? editingExpense.title : '')" subtitle="Modify expense amount, category, payment details and status">
        <form :action="'{{ url('admin/expenses') }}/' + (editingExpense && editingExpense.id ? editingExpense.id : '')" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Title *</label>
                    <input name="title" x-model="editingExpense.title" required class="admin-control w-full text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Amount (৳) *</label>
                    <input type="number" step="0.01" min="0" name="amount" x-model="editingExpense.amount" required class="admin-control w-full text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Date *</label>
                    <input type="date" name="expense_date" x-model="editingExpense.expense_date" required class="admin-control w-full text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Category</label>
                    <select name="category_id" x-model="editingExpense.category_id" class="admin-control w-full text-sm">
                        <option value="">— Select Category —</option>
                        @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Member / Incurred By</label>
                    <select name="member_id" x-model="editingExpense.member_id" class="admin-control w-full text-sm">
                        <option value="">— Select Member —</option>
                        @foreach($members as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Payment Method</label>
                    <input name="payment_method" x-model="editingExpense.payment_method" class="admin-control w-full text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Status</label>
                    <select name="status" x-model="editingExpense.status" class="admin-control w-full text-sm">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Reference Number</label>
                    <input name="reference_number" x-model="editingExpense.reference_number" class="admin-control w-full text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Vendor / Payee</label>
                    <input name="vendor" x-model="editingExpense.vendor" class="admin-control w-full text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Description</label>
                    <textarea name="description" x-model="editingExpense.description" rows="2" class="admin-control w-full text-sm"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Internal Notes</label>
                    <textarea name="notes" x-model="editingExpense.notes" rows="2" class="admin-control w-full text-sm"></textarea>
                </div>
            </div>
            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <x-admin.button type="button" variant="outline" size="sm" @click="editModalOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <form method="GET" class="admin-filter-bar">
        <x-admin.search-input name="q" placeholder="Search expenses..." class="lg:max-w-md" />
        <button type="button" @click="filtersOpen = !filtersOpen"
                class="flex w-full items-center justify-between rounded-[6px] border border-[var(--admin-border)] bg-[var(--admin-muted)] px-3 py-2.5 text-xs font-semibold text-slate-700 md:hidden">
            <span>Filters</span>
            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="filtersOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
             :class="filtersOpen ? 'flex' : 'hidden md:flex'">
            <select name="status" class="admin-control w-full sm:w-40" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(['pending','approved','rejected'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="category_id" class="admin-control w-full sm:w-44" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="admin-table-wrap">
        @if($expenses->isEmpty())
            <x-admin.empty-state title="No expenses found" description="Add an expense or adjust filters." />
        @else
            <div class="md:hidden">
                @foreach($expenses as $expense)
                    @php
                        $statusVariant = match($expense->status) {
                            'approved' => 'default',
                            'pending' => 'warning',
                            default => 'destructive',
                        };
                    @endphp
                    <div class="admin-mobile-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $expense->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $expense->category?->name ?? '—' }} · {{ optional($expense->expense_date)->format('Y-m-d') }}</p>
                            </div>
                            <x-admin.badge :variant="$statusVariant">{{ $expense->status }}</x-admin.badge>
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5">
                            <p class="text-sm font-bold text-slate-900">৳{{ number_format($expense->amount, 0) }}</p>
                            <div class="inline-flex items-center gap-1">
                                <button type="button" @click="openEdit(@js($expense))" class="rounded px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                    Edit
                                </button>
                                <form action="{{ route('admin.expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Delete expense?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th class="admin-hide-sm">Category</th>
                            <th class="admin-hide-md">Member</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                            @php
                                $statusVariant = match($expense->status) {
                                    'approved' => 'default',
                                    'pending' => 'warning',
                                    default => 'destructive',
                                };
                            @endphp
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $expense->title }}</td>
                                <td class="admin-hide-sm text-slate-600">{{ $expense->category?->name ?? '—' }}</td>
                                <td class="admin-hide-md text-slate-600">{{ $expense->member_name ?? '—' }}</td>
                                <td class="text-slate-500">{{ optional($expense->expense_date)->format('Y-m-d') }}</td>
                                <td class="font-bold text-slate-900">৳{{ number_format($expense->amount, 0) }}</td>
                                <td><x-admin.badge :variant="$statusVariant">{{ $expense->status }}</x-admin.badge></td>
                                <td class="text-right">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <button type="button" @click="openEdit(@js($expense))" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Delete expense?')" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="rounded px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.pagination :paginator="$expenses" />
        @endif
    </div>
</div>
@endsection
