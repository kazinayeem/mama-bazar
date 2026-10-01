@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-brand-green-100 shadow-soft text-center space-y-6">

        <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-2xl">
            {{ $subscribed ? '📬' : '🔕' }}
        </div>

        <div class="space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Email Preferences</h1>
            <p class="text-xs text-slate-500">Promotional emails for <strong class="text-slate-800">{{ $maskedEmail }}</strong></p>
        </div>

        @foreach(['success' => 'bg-brand-green-50 border-brand-green-200 text-brand-green-800', 'error' => 'bg-red-50 border-red-200 text-red-700'] as $flash => $classes)
            @if(session($flash))
                <div class="p-3 rounded-xl border text-xs {{ $classes }}" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach

        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-left text-xs text-slate-600">
            <p>Status: <strong class="{{ $subscribed ? 'text-brand-green-700' : 'text-slate-800' }}">{{ $subscribed ? 'Subscribed to offers & newsletters' : 'Not receiving promotional emails' }}</strong></p>
            <p class="mt-1 text-[11px] text-slate-400">Order confirmations, invoices, delivery updates and account security emails are always sent.</p>
        </div>

        <form action="{{ $actionUrl }}" method="POST">
            @csrf
            @if($subscribed)
                <input type="hidden" name="action" value="unsubscribe">
                <button type="submit" class="w-full py-3 px-4 rounded-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md transition">
                    Unsubscribe from promotional emails
                </button>
            @else
                <input type="hidden" name="action" value="resubscribe">
                <button type="submit" class="w-full py-3 px-4 rounded-full border border-brand-green-600 text-brand-green-700 hover:bg-brand-green-50 font-bold text-xs transition">
                    Subscribe again
                </button>
            @endif
        </form>

        <a href="{{ route('home') }}" class="inline-block text-xs text-slate-500 hover:text-slate-700">Return to Home &rarr;</a>
    </div>
</div>
@endsection
