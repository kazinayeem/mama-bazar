@php $items = $items ?? []; @endphp
@if(count($items) > 0)
<section class="border-y border-brand-green-100 bg-brand-green-50/60 overflow-hidden">
    <div class="store-container">
        <div class="grid grid-cols-2 gap-2 py-2.5 sm:gap-3 lg:grid-cols-4 lg:py-3.5">
            @foreach($items as $item)
                @php
                    $title = $item['title'] ?? '';
                    $text = $item['text'] ?? $item['subtitle'] ?? '';
                @endphp
                <div class="flex items-center gap-2 sm:gap-3 rounded-[10px] border border-brand-green-100/80 bg-white p-2.5 sm:px-3.5 sm:py-3 min-w-0">
                    <span class="flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-[8px] bg-brand-green-500 text-xs sm:text-sm font-bold text-white shadow-xs" aria-hidden="true">
                        {{ mb_substr($title, 0, 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold text-slate-900 sm:text-[13px] leading-tight">{{ $title }}</p>
                        @if($text)
                            <p class="mt-0.5 truncate text-[10px] sm:text-[11px] text-slate-500 leading-tight">{{ $text }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
