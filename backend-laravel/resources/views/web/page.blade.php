@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">

    <div class="pb-4 border-b border-brand-green-100">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $page->title }}</h1>
        @php
            // last_updated is stored as an integer UNIX timestamp (see PolicyPageSeeder).
            $pageUpdatedAt = $page->last_updated
                ? (is_numeric($page->last_updated)
                    ? \Carbon\Carbon::createFromTimestamp((int) $page->last_updated)
                    : \Carbon\Carbon::parse($page->last_updated))
                : $page->created_at;
        @endphp
        <p class="text-xs text-slate-400 mt-1">Last updated: {{ $pageUpdatedAt ? $pageUpdatedAt->format('F d, Y') : '—' }}</p>
    </div>

    <div class="bg-white p-6 sm:p-10 rounded-3xl border border-brand-green-100 shadow-soft text-slate-700 text-sm leading-relaxed space-y-4">
        {!! $page->content !!}
    </div>

</div>
@endsection
