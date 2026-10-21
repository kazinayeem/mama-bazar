@php $items = $items ?? []; @endphp
@if(count($items) > 0)
<section class="border-y border-pink-100 bg-white overflow-hidden py-3">
    <div class="store-container">
        <div class="grid grid-cols-2 gap-2.5 sm:gap-4 lg:grid-cols-4">
            @foreach($items as $item)
                @php
                    $title = $item['title'] ?? '';
                    $text = $item['text'] ?? $item['subtitle'] ?? '';
                @endphp
                <div class="flex items-center gap-3 rounded-xl border border-pink-100 bg-[#fffbfa] p-3 shadow-xs min-w-0 transition hover:shadow-sm">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pink-50 text-[#e2006a] font-bold text-sm" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold text-slate-900 leading-tight">{{ $title }}</p>
                        @if($text)
                            <p class="mt-0.5 truncate text-[11px] text-slate-500 leading-tight">{{ $text }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

