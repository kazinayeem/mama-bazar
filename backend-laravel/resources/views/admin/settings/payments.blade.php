@extends('layouts.admin', ['headerTitle' => 'Payment Methods'])

@section('content')
<div class="space-y-6">

    <div class="pb-4 border-b border-slate-200">
        <h2 class="text-lg font-bold text-slate-900">Supported Checkout Payment Methods</h2>
        <p class="text-xs text-slate-500">Enable or disable payment options visible on the storefront checkout.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($methods as $method)
            <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-soft flex flex-col justify-between space-y-4">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-900 text-sm">{{ $method->name }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $method->enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ $method->enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <p class="text-[11px] font-mono text-slate-400 uppercase">Code: {{ $method->code }}</p>
                </div>

                <form action="{{ route('admin.payment-methods.toggle', $method->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2 px-3 rounded-xl border text-xs font-semibold transition {{ $method->enabled ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                        {{ $method->enabled ? 'Disable Payment Method' : 'Enable Payment Method' }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>

</div>
@endsection
