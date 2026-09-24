{{-- Session flash toasts — include once in admin layout --}}
<div
    x-data="{
        toasts: [],
        push(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4200);
        }
    }"
    x-init="
        @if(session('success')) push(@js(session('success')), 'success'); @endif
        @if(session('error')) push(@js(session('error')), 'error'); @endif
        window.addEventListener('admin-toast', (e) => push(e.detail.message, e.detail.type || 'success'));
    "
    class="pointer-events-none fixed bottom-4 right-4 z-[400] flex w-[min(22rem,calc(100vw-2rem))] flex-col gap-2"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="pointer-events-auto rounded-[8px] border px-3.5 py-2.5 text-sm font-medium shadow-panel"
            :class="toast.type === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-brand-green-200 bg-brand-green-50 text-brand-green-800'"
            x-transition
            x-text="toast.message"
        ></div>
    </template>
</div>
