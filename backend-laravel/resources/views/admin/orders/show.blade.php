@extends('layouts.admin', ['headerTitle' => 'Order #' . $order->order_id])

@section('content')
<div class="max-w-5xl mx-auto space-y-5" x-data="{ actionsOpen: false, customerOpen: false }">

    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200">
        <div>
            <span class="text-xs text-slate-400 font-semibold uppercase">Order Overview</span>
            <h2 class="text-xl font-black text-slate-900">{{ $order->order_id }}</h2>
            <p class="text-xs text-slate-500">{{ $order->invoice_number }} · {{ $order->created_at?->format('M d, Y h:i A') }} ·
                <span class="font-bold {{ $order->user_id ? 'text-brand-green-700' : 'text-amber-600' }}">{{ $order->user_id ? 'Registered' : 'Guest' }}</span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            {{-- Order Navigation Controls --}}
            <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white p-1 shadow-sm text-xs font-semibold text-slate-700">
                @if(!empty($navigation['previous']))
                    <a href="{{ $navigation['previous']['url'] }}"
                       title="Previous Order #{{ $navigation['previous']['order_id'] }} (Alt + ←)"
                       onclick="this.classList.add('opacity-50')"
                       class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 text-slate-700 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span class="hidden sm:inline">Previous</span>
                    </a>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-slate-300 cursor-not-allowed select-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span class="hidden sm:inline">Previous</span>
                    </span>
                @endif

                <div class="px-2.5 py-1 border-x border-slate-200 text-[11px] text-slate-600 font-bold whitespace-nowrap">
                    @if(!empty($navigation['has_active_filters']))
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-brand-green-500 mr-1 align-middle" title="Active Filter: {{ $navigation['filter_summary'] }}"></span>
                    @endif
                    Order {{ $navigation['position'] ?? 1 }} of {{ $navigation['total'] ?? 1 }}
                </div>

                @if(!empty($navigation['next']))
                    <a href="{{ $navigation['next']['url'] }}"
                       title="Next Order #{{ $navigation['next']['order_id'] }} (Alt + →)"
                       onclick="this.classList.add('opacity-50')"
                       class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 text-slate-700 transition">
                        <span class="hidden sm:inline">Next</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-slate-300 cursor-not-allowed select-none">
                        <span class="hidden sm:inline">Next</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </div>

            {{-- Invoice Actions Dropdown --}}
            <div class="relative">
                <button @click="actionsOpen = !actionsOpen" @click.away="actionsOpen = false"
                    class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <span>Invoice Actions</span>
                    <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="actionsOpen" x-cloak class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl py-1 text-xs">
                    <a href="{{ route('admin.orders.invoice', $order->id) }}" target="_blank" class="block px-4 py-2.5 font-semibold hover:bg-slate-50">View Invoice</a>
                    @adminCan('orders.export')
                        <a href="{{ route('admin.orders.invoice.download', $order->id) }}" class="block px-4 py-2.5 font-semibold hover:bg-slate-50">Download PDF</a>
                        <a href="{{ route('admin.orders.invoice', $order->id) }}?print=1" target="_blank" class="block px-4 py-2.5 font-semibold hover:bg-slate-50">Print Invoice</a>
                        <a href="{{ route('admin.orders.packing-slip', $order->id) }}" target="_blank" class="block px-4 py-2.5 font-semibold hover:bg-slate-50">Print Packing Slip</a>
                    @endadminCan
                    <button type="button" onclick="copyOrderInfo()" class="block w-full text-left px-4 py-2.5 font-semibold hover:bg-slate-50">Copy Order Info</button>
                    <button type="button" @click="customerOpen = true; actionsOpen = false" class="block w-full text-left px-4 py-2.5 font-semibold hover:bg-slate-50">View Customer Details</button>
                </div>
            </div>

            {{-- Back to Filtered List or Back to Orders --}}
            <a href="{{ $navigation['back_to_list_url'] ?? route('admin.orders.index') }}"
               class="inline-flex items-center gap-1 px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-bold text-slate-600 hover:text-slate-900 transition shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>{{ !empty($navigation['has_active_filters']) ? 'Back to Filtered (' . ($navigation['total'] ?? 0) . ')' : 'Back to Orders' }}</span>
            </a>
        </div>
    </div>

    @if(!empty($navigation['has_active_filters']))
        <div class="flex items-center justify-between gap-3 px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200/80 text-xs text-emerald-900">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                <span>Browsing filtered orders: <strong class="font-semibold">{{ $navigation['filter_summary'] ?? 'Active Filter' }}</strong></span>
                @if(empty($navigation['matches_filter']))
                    <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">
                        Current order outside filter
                    </span>
                @endif
            </div>
            <a href="{{ $navigation['back_to_list_url'] }}" class="font-bold underline hover:text-emerald-950">View all {{ $navigation['total'] }}</a>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            @adminCan('orders.update')
                <div class="admin-surface p-4">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Update Order Status</h3>
                    <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="w-52">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                            <select name="status" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 bg-white">
                                @foreach(['pending','payment_pending','payment_verification','confirmed','processing','packed','shipped','out_for_delivery','delivered','cancelled','returned','refunded'] as $st)
                                    <option value="{{ $st }}" {{ $order->status === $st ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Note (optional)</label>
                            <input type="text" name="note" placeholder="e.g. Courier tracking assigned" class="w-full text-xs rounded-xl border border-slate-200 p-2.5">
                        </div>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold transition">Update</button>
                    </form>
                    <form action="{{ route('admin.orders.payment', $order->id) }}" method="POST" class="flex flex-wrap items-end gap-3 mt-3 pt-3 border-t border-slate-100">
                        @csrf
                        <div class="w-52">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Payment status</label>
                            <select name="payment_status" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 bg-white">
                                @foreach(['pending','payment_pending','payment_verification','verified','success','failed','rejected','refunded'] as $ps)
                                    <option value="{{ $ps }}" {{ $order->payment_status === $ps ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$ps)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Note (optional)</label>
                            <input type="text" name="note" placeholder="e.g. bKash verified" class="w-full text-xs rounded-xl border border-slate-200 p-2.5">
                        </div>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">Save Payment</button>
                    </form>
                </div>
            @endadminCan

            <div class="admin-table-wrap">
                <div class="p-4 border-b border-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">Order Items ({{ $order->items->sum('quantity') }} pcs)</div>
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                        <tr><th class="p-4">Item / SKU / Variant</th><th class="p-4 text-center">Qty</th><th class="p-4 text-right">Unit</th><th class="p-4 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($order->items as $item)
                        <tr>
                            <td class="p-4">
                                <span class="font-bold text-slate-800">{{ $item->product_title ?: ($item->product?->title ?: 'Product') }}</span><br>
                                <span class="text-[11px] text-slate-400 font-mono">{{ $item->product_sku ?: 'MB-' . $item->product_id }}</span>
                                @if($item->variant_name || $item->size || $item->color)
                                <br><span class="text-[11px] text-slate-500">{{ $item->variant_name ?: trim(($item->size ?: '') . ' / ' . ($item->color ?: ''), ' /') }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-center font-bold">{{ $item->quantity }}</td>
                            <td class="p-4 text-right text-slate-600">৳{{ number_format($item->price, 0) }}</td>
                            <td class="p-4 font-extrabold text-slate-900 text-right">৳{{ number_format($item->price * $item->quantity, 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50/60 font-bold border-t border-slate-100">
                        <tr><td colspan="3" class="p-3 text-right text-slate-600">Subtotal</td><td class="p-3 text-right">৳{{ number_format($order->subtotal, 0) }}</td></tr>
                        @if((float)$order->discount > 0)
                        <tr><td colspan="3" class="p-3 text-right text-slate-600">Discount @if($order->coupon_code) ({{ $order->coupon_code }})@endif</td><td class="p-3 text-right text-red-600">− ৳{{ number_format($order->discount, 0) }}</td></tr>
                        @endif
                        <tr><td colspan="3" class="p-3 text-right text-slate-600">Delivery ({{ $order->shipping_method_name ?: 'Standard' }})</td><td class="p-3 text-right">৳{{ number_format($order->shipping_cost, 0) }}</td></tr>
                        @if((float)$order->tax > 0)
                        <tr><td colspan="3" class="p-3 text-right text-slate-600">VAT / Tax</td><td class="p-3 text-right">৳{{ number_format($order->tax, 0) }}</td></tr>
                        @endif
                        <tr class="text-sm"><td colspan="3" class="p-4 text-right text-brand-green-700 font-black">Grand Total</td><td class="p-4 text-right text-brand-orange-600 font-black">৳{{ number_format($order->total_price, 0) }}</td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="admin-surface p-4" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-700">
                    <span>Order Analytics &amp; Source (privacy-conscious)</span><span x-text="open ? '−' : '+'"></span>
                </button>
                <div x-show="open" x-cloak class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <div><span class="text-slate-400 font-semibold block">Device</span><span class="font-bold">{{ $order->device_type ?: '—' }}</span></div>
                    <div><span class="text-slate-400 font-semibold block">Browser</span><span class="font-bold">{{ $order->browser ?: '—' }}</span></div>
                    <div><span class="text-slate-400 font-semibold block">OS</span><span class="font-bold">{{ $order->os_platform ?: '—' }}</span></div>
                    <div><span class="text-slate-400 font-semibold block">Source</span><span class="font-bold">{{ $order->utm_source ?: ($order->order_source ?: 'Direct') }}</span></div>
                    <div><span class="text-slate-400 font-semibold block">Campaign</span><span class="font-bold">{{ $order->utm_campaign ?: '—' }}</span></div>
                    <div><span class="text-slate-400 font-semibold block">Medium</span><span class="font-bold">{{ $order->utm_medium ?: '—' }}</span></div>
                    <div class="col-span-2 sm:col-span-3"><span class="text-slate-400 font-semibold block">Landing page</span><span class="font-mono text-[11px] break-all">{{ $order->landing_page ?: '—' }}</span></div>
                    <div class="col-span-2 sm:col-span-3"><span class="text-slate-400 font-semibold block">Created</span>{{ $order->created_at?->format('M d, Y h:i A') }} · IP area {{ $order->ip_truncated ?: '—' }} (truncated for privacy)</div>
                </div>
            </div>

            <div class="admin-surface p-4 space-y-4" x-data="{ copied: false }">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">IP Address &amp; Location</h3>
                    </div>
                    <div>
                        @if(($ipGeolocation['status'] ?? '') === 'success')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ $ipGeolocation['status_label'] }}
                            </span>
                        @elseif(($ipGeolocation['status'] ?? '') === 'private')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Private Network
                            </span>
                        @elseif(($ipGeolocation['status'] ?? '') === 'incomplete')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Incomplete data
                            </span>
                        @elseif(($ipGeolocation['status'] ?? '') === 'not_recorded')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Not recorded
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> {{ $ipGeolocation['status_label'] ?? 'Unavailable' }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- IP & Location Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <div class="col-span-2 sm:col-span-3 flex flex-wrap items-center justify-between gap-2 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                        <div>
                            <span class="text-slate-400 font-semibold block text-[11px]">Customer IP Address</span>
                            <div class="flex items-center gap-2 mt-0.5">
                                @if($order->ip_address)
                                    <span class="font-mono font-bold text-slate-900 text-sm select-all">{{ $order->ip_address }}</span>
                                    @if(! empty($ipGeolocation['ip_version']))
                                        <span class="px-1.5 py-0.2 bg-slate-200 text-slate-700 text-[10px] font-bold rounded">{{ $ipGeolocation['ip_version'] }}</span>
                                    @endif
                                @else
                                    <span class="font-medium text-slate-500 italic">Not recorded</span>
                                    @if($order->ip_truncated)
                                        <span class="text-[11px] text-slate-400">(Truncated subnet: {{ $order->ip_truncated }})</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @if($order->ip_address)
                            <button type="button"
                                @click="navigator.clipboard.writeText('{{ $order->ip_address }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 transition shadow-xs"
                                title="Copy IP Address">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="copied ? 'Copied!' : 'Copy IP'">Copy IP</span>
                            </button>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold block">Approximate Country</span>
                        <span class="font-bold text-slate-800">{{ $ipGeolocation['country'] ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block">Region / Division</span>
                        <span class="font-bold text-slate-800">{{ $ipGeolocation['region'] ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block">City</span>
                        <span class="font-bold text-slate-800">{{ $ipGeolocation['city'] ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block">ISP / Provider</span>
                        <span class="font-bold text-slate-800 truncate block" title="{{ $ipGeolocation['isp'] ?: '—' }}">{{ $ipGeolocation['isp'] ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block">Time Zone</span>
                        <span class="font-bold text-slate-800">{{ $ipGeolocation['timezone'] ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block">Lookup Status</span>
                        <span class="font-bold text-slate-800">{{ $ipGeolocation['status_label'] }}</span>
                    </div>
                </div>

                <!-- Location Comparison vs Shipping Address -->
                <div class="mt-3 p-3 rounded-xl border {{ $locationComparison['badge_class'] ?? 'bg-slate-50 border-slate-200 text-slate-800' }}">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            @if(($locationComparison['status'] ?? '') === 'likely_match')
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @elseif(($locationComparison['status'] ?? '') === 'possible_mismatch')
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                            <span class="font-bold text-xs uppercase tracking-wide">{{ $locationComparison['label'] }}</span>
                        </div>
                        <span class="text-[11px] font-medium opacity-90">{{ $locationComparison['headline'] }}</span>
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed opacity-95">
                        {{ $locationComparison['description'] }}
                    </p>
                </div>

                <!-- Accuracy & Disclaimer Note -->
                <div class="text-[10px] text-slate-400 border-t border-slate-100 pt-2 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>IP geolocation is approximate. VPNs, cellular networks, and ISP routing can cause geographical variances; differences do not automatically indicate fraud.</span>
                </div>
            </div>

            <div class="admin-surface p-4">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Order History</h3>
                <div class="space-y-3">
                    @forelse($order->statusHistory->sortByDesc('created_at') as $h)
                    <div class="flex gap-3 text-xs">
                        <div class="w-2 h-2 rounded-full bg-brand-green-500 mt-1.5 shrink-0"></div>
                        <div>
                            <p class="font-bold">{{ ucfirst(str_replace('_',' ',$h->status)) }} <span class="font-normal text-slate-400">· {{ $h->created_at?->format('M d, h:i A') }}</span></p>
                            @if($h->note)<p class="text-slate-500">{{ $h->note }}</p>@endif
                            @if($h->user)<p class="text-slate-400">by {{ $h->user->name }}</p>@endif
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400">No history yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="admin-surface p-4 space-y-2 text-xs">
                <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs border-b border-slate-100 pb-2">Customer &amp; Shipping</h3>
                <p><span class="text-slate-400 font-semibold">Name:</span> <span class="font-bold text-slate-800">{{ $order->customer_name }}</span></p>
                <p><span class="text-slate-400 font-semibold">Phone:</span> <a href="tel:{{ $order->phone }}" class="font-semibold text-brand-green-700">{{ $order->phone }}</a></p>
                @if($order->display_alternative_phone)<p><span class="text-slate-400 font-semibold">Alt:</span> {{ $order->display_alternative_phone }}</p>@endif
                @if($order->email)<p><span class="text-slate-400 font-semibold">Email:</span> {{ $order->email }}</p>@endif
                <p><span class="text-slate-400 font-semibold">Address:</span> {{ $order->address }}@if($order->apartment), {{ $order->apartment }}@endif, {{ $order->district }}</p>
                <p><span class="text-slate-400 font-semibold">Method:</span> {{ $order->shipping_method_name ?: 'Standard Delivery' }}</p>
                @if($order->courier_tracking_number)<p><span class="text-slate-400 font-semibold">Courier:</span> <span class="font-mono">{{ $order->courier_tracking_number }}</span></p>@endif
                @if($order->order_note)<p><span class="text-slate-400 font-semibold">Note:</span> {{ $order->order_note }}</p>@endif
            </div>

            <div class="admin-surface p-4 space-y-2 text-xs">
                <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs border-b border-slate-100 pb-2">Payment Info</h3>
                <p><span class="text-slate-400 font-semibold">Method:</span> <span class="uppercase font-bold">{{ $order->payment_method }}</span></p>
                <p><span class="text-slate-400 font-semibold">Status:</span> <span class="font-bold {{ in_array($order->payment_status,['success','verified']) ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst(str_replace('_',' ',$order->payment_status)) }}</span></p>
                @if($order->transaction_id)<p><span class="text-slate-400 font-semibold">Trx ID:</span> <span class="font-mono">{{ $order->transaction_id }}</span></p>@endif
                @if($order->sender_number)<p><span class="text-slate-400 font-semibold">Sender:</span> {{ $order->sender_number }}</p>@endif
            </div>

            <div class="admin-surface p-4 space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs">Customer Emails</h3>
                    @if(\App\Http\Middleware\EnsureAdminPermission::allows(auth()->user(), ['email.logs.view']))
                        <a href="{{ route('admin.email.logs.index', ['order' => $order->order_id]) }}" class="text-[11px] font-semibold text-brand-green-700 hover:underline">All logs</a>
                    @endif
                </div>
                @if($order->email && $invoiceReady)
                    @adminCan('orders.update')
                        <form action="{{ route('admin.orders.email-invoice', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold">Email Invoice PDF to Customer</button>
                        </form>
                    @endadminCan
                @elseif(! $order->email)
                    <p class="text-slate-400">No email address on this order — emails cannot be sent.</p>
                @else
                    <p class="text-slate-400">Invoice email is available once payment is verified (or for Cash on Delivery orders).</p>
                @endif
                @forelse($emailLogs as $log)
                    <div class="border-b border-slate-100 pb-2.5 last:border-b-0 last:pb-0 space-y-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-slate-800">{{ $log->subject }}</p>
                                <p class="text-[11px] text-slate-400">
                                    {{ ($log->sent_at ?? $log->last_attempt_at ?? $log->created_at)?->format('M d, h:i A') }}
                                    @if(! empty($log->metadata['attachment_names'])) · PDF attached @endif
                                    @if($log->attempts > 1) · {{ $log->attempts }} attempts @endif
                                    @if(! empty($log->metadata['invoice_attachment_failed'])) · <span class="text-red-500 font-semibold">PDF failed</span> @endif
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                @include('admin.email.partials.status-badge', ['status' => $log->status])
                            </div>
                        </div>

                        @if($log->status === 'queued')
                            <p class="text-[11px] text-amber-700 bg-amber-50/60 rounded px-2 py-0.5">Queued — waiting for queue worker to deliver.</p>
                        @elseif($log->status === 'processing')
                            <p class="text-[11px] text-amber-700 bg-amber-50/60 rounded px-2 py-0.5">Processing — worker is sending via SMTP...</p>
                        @elseif($log->status === 'sent' && $log->message_id)
                            <p class="text-[10px] text-slate-400 truncate" title="{{ $log->message_id }}">SMTP ID: {{ $log->message_id }}</p>
                        @elseif($log->status === 'failed')
                            <div class="rounded bg-red-50 p-2 text-[11px] text-red-700 space-y-1.5">
                                <p class="break-words font-medium">{{ $log->error_message ?: 'Delivery failed without error details.' }}</p>
                                <form action="{{ route('admin.email.logs.retry', $log->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="font-bold text-red-800 underline hover:text-red-900">↻ Retry delivery</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-400">No emails sent for this order yet.</p>
                @endforelse
            </div>

            <div class="admin-surface p-4">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Internal Notes</h3>
                @if($order->admin_notes)<pre class="text-[11px] text-slate-600 whitespace-pre-wrap bg-slate-50 rounded-lg p-3 mb-3">{{ $order->admin_notes }}</pre>@endif
                @adminCan('orders.update')
                    <form action="{{ route('admin.orders.notes', $order->id) }}" method="POST" class="space-y-2">
                        @csrf
                        <textarea name="admin_notes" rows="2" required placeholder="Add internal note (not printed)..." class="w-full text-xs rounded-xl border border-slate-200 p-2.5"></textarea>
                        <button class="w-full px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold">Add Note</button>
                    </form>
                @endadminCan
            </div>
        </div>
    </div>

    <div x-show="customerOpen" x-cloak class="fixed inset-0 z-[300] flex items-center justify-center bg-black/40 p-4" @click.self="customerOpen = false">
        <div class="w-full max-w-md bg-white rounded-2xl p-6 space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-black">Customer Details</h3>
                <button @click="customerOpen = false" class="text-slate-400 hover:text-slate-800">✕</button>
            </div>
            <p><strong>{{ $order->customer_name }}</strong> <span class="text-xs px-2 py-0.5 rounded-full {{ $order->user_id ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $order->user_id ? 'Registered' : 'Guest' }}</span></p>
            <p class="text-xs">Phone: {{ $order->phone }}@if($order->display_alternative_phone) · Alt: {{ $order->display_alternative_phone }}@endif</p>
            @if($order->email)<p class="text-xs">Email: {{ $order->email }}</p>@endif
            <p class="text-xs">{{ $order->address }}, {{ $order->district }}</p>
            <button onclick="copyOrderInfo()" class="w-full py-2 rounded-xl bg-slate-800 text-white text-xs font-bold">Copy Order Information</button>
        </div>
    </div>

</div>

<script>
function copyOrderInfo() {
    const text = [
        'Order: {{ $order->order_id }} ({{ $order->invoice_number }})',
        'Customer: {{ addslashes($order->customer_name) }} — {{ $order->phone }}',
        'Address: {{ addslashes($order->address) }}, {{ addslashes($order->district ?? '') }}',
        'Total: ৳{{ number_format($order->total_price, 0) }} via {{ strtoupper($order->payment_method) }}',
    ].join('\n');
    navigator.clipboard?.writeText(text).then(() => alert('Order information copied.'));
}

// Alt + ArrowLeft (Previous Order), Alt + ArrowRight (Next Order)
document.addEventListener('keydown', function(e) {
    if (e.altKey && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
        const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
        if (['input', 'textarea', 'select'].includes(tag) || (e.target && e.target.isContentEditable)) {
            return;
        }

        @if(!empty($navigation['previous']['url']))
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            window.location.href = @json($navigation['previous']['url']);
        }
        @endif

        @if(!empty($navigation['next']['url']))
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            window.location.href = @json($navigation['next']['url']);
        }
        @endif
    }
});
</script>
@endsection
