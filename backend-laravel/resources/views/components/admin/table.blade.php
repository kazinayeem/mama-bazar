@props([])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft']) }}>
    {{-- Desktop table --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left text-sm">
            @isset($head)
                <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    {{ $head }}
                </thead>
            @endisset
            <tbody class="divide-y divide-slate-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    @isset($mobile)
        <div class="divide-y divide-slate-100 md:hidden">
            {{ $mobile }}
        </div>
    @endisset
</div>
