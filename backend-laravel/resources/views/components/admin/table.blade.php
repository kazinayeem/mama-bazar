@props([])

<div {{ $attributes->merge(['class' => 'admin-table-wrap']) }}>
    {{-- Desktop table --}}
    <div class="hidden overflow-x-auto md:block">
        <table class="admin-table">
            @isset($head)
                <thead>
                    {{ $head }}
                </thead>
            @endisset
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    @isset($mobile)
        <div class="md:hidden">
            {{ $mobile }}
        </div>
    @endisset

    @isset($footer)
        {{ $footer }}
    @endisset
</div>
