@php $items = $items ?? []; @endphp
@if(count($items) > 0)
<x-homepage.section-shell :section="$section">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($items as $item)
            @php
                $title = $item['title'] ?? '';
                $text = $item['text'] ?? $item['description'] ?? '';
            @endphp
            <div class="rounded-[10px] border border-brand-green-100 bg-white p-4 transition duration-200 hover:border-brand-green-300">
                <span class="flex h-10 w-10 items-center justify-center rounded-[8px] bg-brand-green-50 text-sm font-bold text-brand-green-700" aria-hidden="true">
                    {{ mb_substr($title, 0, 1) }}
                </span>
                <h3 class="mt-3 text-sm font-extrabold text-slate-900">{{ $title }}</h3>
                @if($text)
                    <p class="mt-1.5 text-xs leading-5 text-slate-500">{{ $text }}</p>
                @endif
            </div>
        @endforeach
    </div>
</x-homepage.section-shell>
@endif
