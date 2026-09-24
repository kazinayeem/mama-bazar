@extends('layouts.admin', ['headerTitle' => $headerTitle ?? 'Expense Reports'])

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="admin-page-title">{{ $headerTitle }}</h1>
        <p class="text-sm text-slate-500">Finance insights from SQLite expense + order data</p>
    </div>
</div>

<div class="mt-4 flex flex-wrap gap-2">
    @foreach(['overview' => 'Overview', 'monthly' => 'Monthly', 'members' => 'Members', 'categories' => 'Categories', 'profit' => 'Profit'] as $key => $label)
        <a href="{{ route('admin.expenses.reports', ['tab' => $key]) }}"
           class="inline-flex h-8 items-center rounded-[6px] px-3 text-xs font-semibold {{ $tab === $key ? 'bg-brand-green-500 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if($tab === 'profit')
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Revenue</p><p class="mt-1 text-2xl font-bold text-brand-green-700">৳{{ number_format($revenue, 0) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Expenses</p><p class="mt-1 text-2xl font-bold text-red-600">৳{{ number_format($expenseTotal, 0) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Profit</p><p class="mt-1 text-2xl font-bold {{ $profit >= 0 ? 'text-brand-green-700' : 'text-red-600' }}">৳{{ number_format($profit, 0) }}</p></div>
    </div>
    <div class="mt-4 admin-table-wrap">
        <table class="w-full text-sm">
            <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Month</th><th class="px-4 py-3 text-right">Revenue</th><th class="px-4 py-3 text-right">Expenses</th><th class="px-4 py-3 text-right">Profit</th></tr></thead>
            <tbody class="divide-y">
                @forelse($profitRows as $row)
                    <tr>
                        <td class="px-4 py-3">{{ $row['month'] }}</td>
                        <td class="px-4 py-3 text-right">৳{{ number_format($row['revenue'], 0) }}</td>
                        <td class="px-4 py-3 text-right">৳{{ number_format($row['expenses'], 0) }}</td>
                        <td class="px-4 py-3 text-right font-bold {{ $row['profit'] >= 0 ? 'text-brand-green-700' : 'text-red-600' }}">৳{{ number_format($row['profit'], 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@elseif($tab === 'categories')
    <div class="mt-4 admin-table-wrap">
        <table class="w-full text-sm">
            <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Category</th><th class="px-4 py-3 text-right">Total</th></tr></thead>
            <tbody class="divide-y">
                @foreach($byCategory as $row)
                    <tr><td class="px-4 py-3">{{ $row->name }}</td><td class="px-4 py-3 text-right font-bold">৳{{ number_format($row->total, 0) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif($tab === 'members')
    <div class="mt-4 admin-table-wrap">
        <table class="w-full text-sm">
            <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Member</th><th class="px-4 py-3 text-right">Total</th></tr></thead>
            <tbody class="divide-y">
                @foreach($byMember as $row)
                    <tr><td class="px-4 py-3">{{ $row->name }}</td><td class="px-4 py-3 text-right font-bold">৳{{ number_format($row->total, 0) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="mt-4 admin-table-wrap">
        <table class="w-full text-sm">
            <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Month</th><th class="px-4 py-3 text-right">Count</th><th class="px-4 py-3 text-right">Total</th></tr></thead>
            <tbody class="divide-y">
                @forelse($monthly as $row)
                    <tr><td class="px-4 py-3">{{ $row->month }}</td><td class="px-4 py-3 text-right">{{ $row->count }}</td><td class="px-4 py-3 text-right font-bold">৳{{ number_format($row->total, 0) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">No expense data yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
