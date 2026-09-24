@php $items = $items ?? []; @endphp
@if(count($items) > 0)
<section class="border-y border-brand-green-100 bg-brand-green-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-2 py-3 sm:gap-3 lg:grid-cols-4">
            @foreach($items as $item)
                @php
                    $title = $item['title'] ?? '';
                    $text = $item['text'] ?? $item['subtitle'] ?? '';
                @endphp
                <div class="flex items-center gap-3 rounded-2xl border border-brand-green-100 bg-white px-4 py-3.5 transition hover:border-brand-green-300 hover:bg-brand-green-50">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-green-500 to-brand-green-700 text-sm font-bold text-white shadow-sm">
                        {{ mb_substr($title, 0, 1) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold text-slate-900 sm:text-[13px]">{{ $title }}</p>
                        @if($text)
                            <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $text }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
