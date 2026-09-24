@extends('layouts.admin', ['headerTitle' => 'Inventory'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Inventory" subtitle="Stock levels and quick adjustments" />

    <div class="admin-metric-grid">
        <x-admin.metric-card label="Total Products" :value="number_format($stats['total'])" />
        <x-admin.metric-card label="In Stock" :value="number_format($stats['in_stock'])" tone="success" />
        <x-admin.metric-card label="Low Stock" :value="number_format($stats['low'])" tone="warning" />
        <x-admin.metric-card label="Out of Stock" :value="number_format($stats['out'])" tone="danger" />
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach(['all' => 'All', 'low' => 'Low (≤10)', 'out' => 'Out of stock'] as $key => $label)
            <a href="{{ route('admin.inventory.index', ['filter' => $key]) }}"
               class="inline-flex h-8 items-center rounded-[6px] px-3 text-xs font-semibold {{ $filter === $key ? 'bg-brand-green-500 text-white' : 'border border-[var(--admin-border)] bg-white text-slate-600 hover:bg-[var(--admin-muted)]' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="admin-table-wrap">
        <div class="md:hidden">
            @forelse($products as $product)
                @php
                    $stock = (int) $product->stock;
                    $badge = $stock <= 0 ? 'destructive' : ($stock <= 10 ? 'warning' : 'default');
                    $pct = min(100, max(0, $stock <= 0 ? 0 : min(100, ($stock / 50) * 100)));
                @endphp
                <div class="admin-mobile-card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="line-clamp-2 font-semibold text-slate-900">{{ $product->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $product->stock_status }}</p>
                        </div>
                        <x-admin.badge :variant="$badge">{{ $stock }}</x-admin.badge>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-slate-400">SKU</dt>
                            <dd class="font-mono text-slate-600">{{ $product->sku ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-slate-400">Price</dt>
                            <dd class="font-bold text-slate-900">৳{{ number_format($product->sale_price ?: $product->price, 0) }}</dd>
                        </div>
                    </dl>
                    <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-green-500" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="mt-3 flex items-center justify-end gap-2 border-t border-[var(--admin-border)] pt-3">
                        <span class="mr-auto text-[10px] font-semibold uppercase tracking-wide text-slate-400">Adjust</span>
                        <form action="{{ route('admin.inventory.adjust', $product->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="delta" value="-1">
                            <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-[6px] border border-[var(--admin-border)] text-sm font-bold hover:bg-slate-50">−</button>
                        </form>
                        <form action="{{ route('admin.inventory.adjust', $product->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="delta" value="1">
                            <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-[6px] border border-[var(--admin-border)] text-sm font-bold hover:border-brand-green-200 hover:bg-brand-green-50">+</button>
                        </form>
                    </div>
                </div>
            @empty
                <x-admin.empty-state title="No products found" />
            @endforelse
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th class="text-right">Adjust</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $stock = (int) $product->stock;
                            $badge = $stock <= 0 ? 'destructive' : ($stock <= 10 ? 'warning' : 'default');
                        @endphp
                        <tr>
                            <td class="max-w-[240px] truncate font-semibold text-slate-900">{{ $product->title }}</td>
                            <td class="font-mono text-xs text-slate-500">{{ $product->sku ?: '—' }}</td>
                            <td><x-admin.badge :variant="$badge">{{ $stock }}</x-admin.badge></td>
                            <td class="text-slate-500">{{ $product->stock_status }}</td>
                            <td class="font-bold">৳{{ number_format($product->sale_price ?: $product->price, 0) }}</td>
                            <td class="text-right">
                                <div class="inline-flex items-center gap-1">
                                    <form action="{{ route('admin.inventory.adjust', $product->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="delta" value="-1">
                                        <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-[6px] border border-[var(--admin-border)] text-sm font-bold hover:bg-slate-50">−</button>
                                    </form>
                                    <form action="{{ route('admin.inventory.adjust', $product->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="delta" value="1">
                                        <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-[6px] border border-[var(--admin-border)] text-sm font-bold hover:border-brand-green-200 hover:bg-brand-green-50">+</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="!h-auto py-8"><x-admin.empty-state title="No products found" class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($products, 'hasPages'))
            <x-admin.pagination :paginator="$products" />
        @endif
    </div>
</div>
@endsection
