@php $items = $items ?? []; @endphp
@if(count($items) > 0)
<section class="border-y border-brand-green-100 bg-brand-green-50/60">
    <div class="store-container">
        <div class="grid grid-cols-2 gap-2 py-3 sm:gap-3 lg:grid-cols-4 lg:py-3.5">
            @foreach($items as $item)
                @php
                    $title = $item['title'] ?? '';
                    $text = $item['text'] ?? $item['subtitle'] ?? '';
                @endphp
                <div class="flex items-center gap-3 rounded-[10px] border border-brand-green-100/80 bg-white px-3.5 py-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[8px] bg-brand-green-500 text-xs font-bold text-white" aria-hidden="true">
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
