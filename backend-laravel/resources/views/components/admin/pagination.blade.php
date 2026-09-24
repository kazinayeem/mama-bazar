@props(['paginator'])

@if($paginator && method_exists($paginator, 'hasPages') && $paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'flex flex-col gap-3 border-t border-[var(--admin-border)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between']) }}>
        <p class="text-xs text-slate-500">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            · {{ number_format($paginator->total()) }} items
        </p>
        <div class="overflow-x-auto">
            {{ $paginator->onEachSide(1)->links() }}
        </div>
    </div>
@endif
