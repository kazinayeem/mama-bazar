@props(['rows' => 4])

<div {{ $attributes->merge(['class' => 'space-y-3']) }} aria-busy="true" aria-label="Loading">
    @for($i = 0; $i < (int) $rows; $i++)
        <div class="h-16 animate-pulse rounded-xl bg-slate-100"></div>
    @endfor
</div>
