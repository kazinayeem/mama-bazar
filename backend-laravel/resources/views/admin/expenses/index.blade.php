@extends('layouts.admin', ['headerTitle' => 'Expenses'])

@section('content')
<div class="admin-page" x-data="{ formOpen: false, filtersOpen: {{ request()->hasAny(['q','status','category_id']) ? 'true' : 'false' }} }">
    <x-admin.page-header title="Expenses" :subtitle="$stats['count'].' records · ৳'.number_format($stats['total'], 0).' total'">
        <x-slot:actions>
            <x-admin.button type="button" size="sm" @click="formOpen = !formOpen">Add Expense</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-metric-grid">
        <x-admin.metric-card label="Total" :value="'৳'.number_format($stats['total'], 0)" />
        <x-admin.metric-card label="Approved" :value="'৳'.number_format($stats['approved'], 0)" tone="success" />
        <x-admin.metric-card label="Pending" :value="'৳'.number_format($stats['pending'], 0)" tone="warning" />
        <x-admin.metric-card label="Count" :value="number_format($stats['count'])" />
    </div>

    <div x-show="formOpen" x-cloak class="admin-surface p-4">
        <form action="{{ route('admin.expenses.store') }}" method="POST" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            <div class="sm:col-span-2">
                <x-admin.input label="Title *" name="title" required />
            </div>
            <x-admin.input label="Amount *" type="number" name="amount" step="0.01" required />
            <x-admin.input label="Date *" type="date" name="expense_date" value="{{ date('Y-m-d') }}" required />
            <x-admin.select label="Category" name="category_id">
                <option value="">—</option>
                @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
            </x-admin.select>
            <x-admin.select label="Member" name="member_id">
                <option value="">—</option>
                @foreach($members as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
            </x-admin.select>
            <x-admin.input label="Payment Method" name="payment_method" value="cash" />
            <x-admin.select label="Status" name="status">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </x-admin.select>
            <x-admin.input label="Reference" name="reference_number" />
            <x-admin.input label="Vendor" name="vendor" />
            <div class="sm:col-span-2">
                <x-admin.textarea label="Description" name="description" rows="2" />
            </div>
            <div class="sm:col-span-2">
                <x-admin.textarea label="Notes" name="notes" rows="2" />
            </div>
            <div class="flex justify-end sm:col-span-2 lg:col-span-4">
                <x-admin.button type="submit" size="sm">Create Expense</x-admin.button>
            </div>
        </form>
    </div>

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
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-sm font-bold text-slate-900">৳{{ number_format($expense->amount, 0) }}</p>
                            <form action="{{ route('admin.expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Delete expense?')">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" class="text-red-600">Delete</x-admin.button>
                            </form>
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
                                    <form action="{{ route('admin.expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Delete expense?')" class="inline">
                                        @csrf @method('DELETE')
                                        <x-admin.button type="submit" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50">Delete</x-admin.button>
                                    </form>
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
