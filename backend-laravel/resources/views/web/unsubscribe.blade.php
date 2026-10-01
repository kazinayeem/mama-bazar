@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-brand-green-100 shadow-soft text-center space-y-6">

        <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-2xl">
            🔕
        </div>

        <div class="space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Unsubscribe from Marketing</h1>
            <p class="text-xs text-slate-500">We respect your privacy. You can opt out of promotional newsletters and campaigns anytime.</p>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-xs">
                {{ session('success') }}
            </div>
            <a href="{{ route('home') }}" class="inline-block mt-4 px-6 py-2.5 rounded-full bg-brand-green-600 text-white font-bold text-xs">Return to Home</a>
        @else
            <form action="{{ route('email.unsubscribe.submit') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-left text-xs text-slate-600">
                    <p>Unsubscribing email: <strong class="text-slate-800">{{ $email }}</strong></p>
                    <p class="mt-1 text-[11px] text-slate-400">Note: You will still receive essential transactional emails regarding your orders and account security.</p>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md transition">
                    Confirm Unsubscribe &rarr;
                </button>
            </form>
        @endif

    </div>
</div>
@endsection
