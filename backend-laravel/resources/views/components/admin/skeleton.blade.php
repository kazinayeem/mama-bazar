@props(['rows' => 4, 'height' => 'h-10'])

<div {{ $attributes->merge(['class' => 'space-y-2.5']) }} aria-busy="true" aria-label="Loading">
    @for($i = 0; $i < (int) $rows; $i++)
        <div class="admin-skeleton {{ $height }} w-full"></div>
    @endfor
</div>
