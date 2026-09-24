{{-- Subscribers tab (matches React AdminHomepagePage subscribers) --}}
<div class="pt-4">
    <div x-show="subscribers.length === 0">
        <x-admin.empty-state
            title="No subscribers yet"
            description="Emails collected from the homepage newsletter form appear here."
        >
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </x-slot:icon>
        </x-admin.empty-state>
    </div>
    <div x-show="subscribers.length > 0" class="admin-table-wrap">
        <div class="divide-y divide-slate-100">
            <template x-for="sub in subscribers" :key="sub.id">
                <div class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900" x-text="sub.email"></p>
                        <p class="text-xs text-slate-500">
                            <span x-text="formatDate(sub.subscribedAt)"></span> · via <span x-text="sub.source || 'homepage'"></span>
                        </p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="sub.status === 'subscribed' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" x-text="sub.status"></span>
                </div>
            </template>
        </div>
    </div>
</div>
