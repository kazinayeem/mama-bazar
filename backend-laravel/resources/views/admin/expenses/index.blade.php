@extends('layouts.admin', ['headerTitle' => 'Expenses'])

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Expenses</h1>
        <p class="text-sm text-slate-500">{{ $stats['count'] }} records · ৳{{ number_format($stats['total'], 0) }} total</p>
    </div>
    <button type="button" onclick="document.getElementById('expense-form').classList.toggle('hidden')"
            class="rounded-full bg-brand-green-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-green-600">Add Expense</button>
</div>

<div class="mt-4 grid grid-cols-2 gap-4 xl:grid-cols-4">
    <div class="rounded-xl border bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Total</p><p class="mt-1 text-2xl font-bold">৳{{ number_format($stats['total'], 0) }}</p></div>
    <div class="rounded-xl border bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Approved</p><p class="mt-1 text-2xl font-bold text-brand-green-700">৳{{ number_format($stats['approved'], 0) }}</p></div>
    <div class="rounded-xl border bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Pending</p><p class="mt-1 text-2xl font-bold text-amber-600">৳{{ number_format($stats['pending'], 0) }}</p></div>
    <div class="rounded-xl border bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Count</p><p class="mt-1 text-2xl font-bold">{{ number_format($stats['count']) }}</p></div>
</div>

<div id="expense-form" class="mt-4 hidden rounded-xl border bg-white p-5 shadow-soft">
    <form action="{{ route('admin.expenses.store') }}" method="POST" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @csrf
        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold">Title *</label><input name="title" required class="w-full rounded-xl border p-2.5 text-xs"></div>
        <div><label class="mb-1 block text-xs font-bold">Amount *</label><input type="number" step="0.01" name="amount" required class="w-full rounded-xl border p-2.5 text-xs"></div>
        <div><label class="mb-1 block text-xs font-bold">Date *</label><input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border p-2.5 text-xs"></div>
        <div>
            <label class="mb-1 block text-xs font-bold">Category</label>
            <select name="category_id" class="w-full rounded-xl border bg-white p-2.5 text-xs">
                <option value="">—</option>
                @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold">Member</label>
            <select name="member_id" class="w-full rounded-xl border bg-white p-2.5 text-xs">
                <option value="">—</option>
                @foreach($members as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="mb-1 block text-xs font-bold">Payment Method</label><input name="payment_method" value="cash" class="w-full rounded-xl border p-2.5 text-xs"></div>
        <div>
            <label class="mb-1 block text-xs font-bold">Status</label>
            <select name="status" class="w-full rounded-xl border bg-white p-2.5 text-xs">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div><label class="mb-1 block text-xs font-bold">Reference</label><input name="reference_number" class="w-full rounded-xl border p-2.5 text-xs"></div>
        <div><label class="mb-1 block text-xs font-bold">Vendor</label><input name="vendor" class="w-full rounded-xl border p-2.5 text-xs"></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold">Description</label><textarea name="description" rows="2" class="w-full rounded-xl border p-2.5 text-xs"></textarea></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold">Notes</label><textarea name="notes" rows="2" class="w-full rounded-xl border p-2.5 text-xs"></textarea></div>
        <div class="sm:col-span-2 lg:col-span-4 flex justify-end"><button class="rounded-full bg-brand-green-500 px-5 py-2 text-sm font-medium text-white">Create Expense</button></div>
    </form>
</div>

<form method="GET" class="mt-4 grid gap-3 lg:grid-cols-4">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..." class="rounded-lg border p-2.5 text-sm lg:col-span-2">
    <select name="status" class="rounded-lg border bg-white p-2.5 text-sm" onchange="this.form.submit()">
        <option value="">All statuses</option>
        @foreach(['pending','approved','rejected'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <select name="category_id" class="rounded-lg border bg-white p-2.5 text-sm" onchange="this.form.submit()">
        <option value="">All categories</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name }}</option>
        @endforeach
    </select>
</form>

<div class="mt-4 overflow-hidden rounded-xl border bg-white shadow-soft">
    <table class="w-full text-left text-sm">
        <thead class="border-b bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Title</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3">Member</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Amount</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($expenses as $expense)
                <tr class="hover:bg-slate-50/60">
                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $expense->title }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $expense->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $expense->member_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ optional($expense->expense_date)->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 font-bold">৳{{ number_format($expense->amount, 0) }}</td>
                    <td class="px-4 py-3">
                        @php $sc = match($expense->status) { 'approved' => 'bg-brand-green-50 text-brand-green-700', 'pending' => 'bg-amber-50 text-amber-700', default => 'bg-red-50 text-red-700' }; @endphp
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $sc }}">{{ $expense->status }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form action="{{ route('admin.expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Delete expense?')" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-xs font-semibold text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">No expenses found</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($expenses->hasPages())
        <div class="border-t p-3">{{ $expenses->links() }}</div>
    @endif
</div>
@endsection
