@extends('layouts.admin', ['headerTitle' => 'Inventory'])

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inventory</h1>
        <p class="text-sm text-slate-500">Stock levels and quick adjustments</p>
    </div>
</div>

<div class="mt-4 grid grid-cols-2 gap-4 xl:grid-cols-4">
    @foreach([
        ['Total Products', $stats['total'], 'bg-brand-green-50 text-brand-green-700'],
        ['In Stock', $stats['in_stock'], 'bg-emerald-50 text-emerald-700'],
        ['Low Stock', $stats['low'], 'bg-amber-50 text-amber-700'],
        ['Out of Stock', $stats['out'], 'bg-red-50 text-red-700'],
    ] as [$label, $val, $cls])
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-soft">
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="mt-1 text-2xl font-bold tracking-tight">{{ number_format($val) }}</p>
            <span class="mt-2 inline-block rounded-lg px-2 py-1 text-[10px] font-bold {{ $cls }}">Live</span>
        </div>
    @endforeach
</div>

<div class="mt-4 flex gap-2">
    @foreach(['all' => 'All', 'low' => 'Low (≤10)', 'out' => 'Out of stock'] as $key => $label)
        <a href="{{ route('admin.inventory.index', ['filter' => $key]) }}"
           class="rounded-full px-4 py-1.5 text-xs font-semibold {{ $filter === $key ? 'bg-brand-green-500 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft">
    <table class="w-full text-left text-sm">
        <thead class="border-b bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Product</th>
                <th class="px-4 py-3">SKU</th>
                <th class="px-4 py-3">Price</th>
                <th class="px-4 py-3">Stock</th>
                <th class="px-4 py-3 text-right">Adjust</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($products as $product)
                @php
                    $stock = (int) $product->stock;
                    $badge = $stock <= 0 ? 'bg-red-50 text-red-700' : ($stock <= 10 ? 'bg-amber-50 text-amber-700' : 'bg-brand-green-50 text-brand-green-700');
                    $pct = min(100, max(0, $stock <= 0 ? 0 : ($stock / max(50, $stock)) * 100));
                @endphp
                <tr class="hover:bg-slate-50/60">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-slate-900">{{ $product->title }}</div>
                        <div class="text-xs text-slate-400">{{ $product->stock_status }}</div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $product->sku ?: '—' }}</td>
                    <td class="px-4 py-3 font-semibold">৳{{ number_format($product->sale_price ?: $product->price, 0) }}</td>
                    <td class="px-4 py-3">
                        <div class="mb-1 h-2 w-28 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-brand-green-500" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badge }}">{{ $stock }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-1">
                            <form action="{{ route('admin.inventory.adjust', $product->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="delta" value="-1">
                                <button class="h-8 w-8 rounded-lg border border-slate-200 text-sm font-bold hover:bg-slate-50">−</button>
                            </form>
                            <form action="{{ route('admin.inventory.adjust', $product->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="delta" value="1">
                                <button class="h-8 w-8 rounded-lg border border-slate-200 text-sm font-bold hover:bg-slate-50">+</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">No products found</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($products->hasPages())
        <div class="border-t p-3">{{ $products->links() }}</div>
    @endif
</div>
@endsection
