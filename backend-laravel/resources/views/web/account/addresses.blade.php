@extends('web.account.layout')

@section('account-title', 'Saved Addresses')

@section('account-content')
<div class="space-y-6" x-data="{
    showAddModal: false,
    editingAddress: null,
    openEdit(addr) {
        this.editingAddress = addr;
    }
}">

    {{-- Addresses Page Header --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Saved Shipping Addresses</h2>
            <p class="text-xs text-slate-500">Manage multiple delivery locations for quick, one-click checkout.</p>
        </div>

        <button type="button" @click="showAddModal = true"
                class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition shrink-0">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add New Address</span>
        </button>
    </div>

    {{-- Addresses Cards Grid --}}
    @if($addresses->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center space-y-3">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-green-50 text-brand-green-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900">No saved addresses yet</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Add your home, office, or family delivery addresses to speed up future checkouts.
                </p>
            </div>
            <div class="pt-2">
                <button type="button" @click="showAddModal = true"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition">
                    <span>+ Add Your First Address</span>
                </button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($addresses as $address)
                <div class="rounded-2xl border {{ $address->is_default ? 'border-brand-green-400 bg-brand-green-50/20 shadow-sm ring-1 ring-brand-green-400/30' : 'border-slate-200/90 bg-white shadow-xs' }} p-5 flex flex-col justify-between gap-4 transition hover:border-slate-300">
                    <div class="space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $address->is_default ? 'bg-brand-green-600 text-white' : 'bg-slate-100 text-slate-600' }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                </span>
                                <h3 class="font-extrabold text-sm text-slate-900">{{ $address->recipient_name }}</h3>
                            </div>
                            @if($address->is_default)
                                <span class="rounded-full bg-brand-green-100 px-2.5 py-0.5 text-[10px] font-bold text-brand-green-800 border border-brand-green-200">
                                    Default Delivery
                                </span>
                            @endif
                        </div>

                        <div class="text-xs text-slate-600 space-y-1 pt-1">
                            <p class="font-medium text-slate-900 leading-relaxed">{{ $address->address }}</p>
                            @if($address->apartment)
                                <p class="text-[11px] text-slate-500">Apartment / Flat: {{ $address->apartment }}</p>
                            @endif
                            <p class="text-[11px] text-slate-500">
                                {{ implode(', ', array_filter([$address->area, $address->upazila, $address->district, $address->division, $address->postal_code])) }}
                            </p>
                            <p class="pt-2 text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $address->phone }}
                                @if($address->alternative_phone)
                                    <span class="text-slate-400 font-normal">({{ $address->alternative_phone }})</span>
                                @endif
                            </p>
                            <span class="inline-block mt-1 text-[10px] font-medium text-slate-400 uppercase tracking-wider">
                                Zone: {{ $address->shipping_area === 'outside_dhaka' ? 'Outside Dhaka' : 'Inside Dhaka' }}
                            </span>
                        </div>
                    </div>

                    {{-- Card Action Footer --}}
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <div>
                            @if(! $address->is_default)
                                <form action="{{ route('account.addresses.default', $address->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-brand-green-700 hover:underline">
                                        Set as Default
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="openEdit({{ json_encode($address) }})"
                                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                                Edit
                            </button>

                            <form action="{{ route('account.addresses.destroy', $address->id) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to remove this saved address?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-red-200 bg-white px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Address Modal --}}
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showAddModal = false"></div>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl space-y-4" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-extrabold text-slate-900" id="modal-title">Add New Address</h3>
                    <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('account.addresses.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Recipient Name *</label>
                            <input type="text" name="recipient_name" required value="{{ old('recipient_name', $user->name) }}"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number *</label>
                            <input type="tel" name="phone" required value="{{ old('phone', $user->phone) }}"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Alternative Phone</label>
                            <input type="tel" name="alternative_phone" value="{{ old('alternative_phone') }}"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Delivery Zone *</label>
                            <select name="shipping_area" class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                                <option value="inside_dhaka">Inside Dhaka (৳60)</option>
                                <option value="outside_dhaka">Outside Dhaka (৳120)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Division</label>
                            <input type="text" name="division" placeholder="e.g. Dhaka" value="{{ old('division') }}"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">District</label>
                            <input type="text" name="district" placeholder="e.g. Dhaka" value="{{ old('district') }}"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Area / Thana</label>
                            <input type="text" name="area" placeholder="e.g. Mirpur" value="{{ old('area') }}"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Street Address *</label>
                        <textarea name="address" required rows="2" placeholder="House, Road, Block, Landmark..."
                                  class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">{{ old('address') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Apartment / Suite</label>
                            <input type="text" name="apartment" placeholder="Flat 4B, 3rd Floor" value="{{ old('apartment') }}"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Postal Code</label>
                            <input type="text" name="postal_code" placeholder="1216" value="{{ old('postal_code') }}"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="is_default" name="is_default" value="1" class="rounded text-brand-green-600 focus:ring-brand-green-500">
                        <label for="is_default" class="text-xs font-semibold text-slate-700">Set as my default shipping address</label>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="showAddModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-brand-green-600 hover:bg-brand-green-700 px-5 py-2 text-xs font-bold text-white shadow-sm transition">
                            Save Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Address Modal --}}
    <div x-show="editingAddress" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="editingAddress = null"></div>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl space-y-4" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-extrabold text-slate-900">Edit Address</h3>
                    <button type="button" @click="editingAddress = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="'{{ url('/account/addresses') }}/' + editingAddress?.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Recipient Name *</label>
                            <input type="text" name="recipient_name" required :value="editingAddress?.recipient_name"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number *</label>
                            <input type="tel" name="phone" required :value="editingAddress?.phone"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Alternative Phone</label>
                            <input type="tel" name="alternative_phone" :value="editingAddress?.alternative_phone"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Delivery Zone *</label>
                            <select name="shipping_area" class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                                <option value="inside_dhaka" :selected="editingAddress?.shipping_area === 'inside_dhaka'">Inside Dhaka</option>
                                <option value="outside_dhaka" :selected="editingAddress?.shipping_area === 'outside_dhaka'">Outside Dhaka</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Division</label>
                            <input type="text" name="division" :value="editingAddress?.division"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">District</label>
                            <input type="text" name="district" :value="editingAddress?.district"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Area / Thana</label>
                            <input type="text" name="area" :value="editingAddress?.area"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Street Address *</label>
                        <textarea name="address" required rows="2" x-model="editingAddress.address"
                                  class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Apartment / Suite</label>
                            <input type="text" name="apartment" :value="editingAddress?.apartment"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Postal Code</label>
                            <input type="text" name="postal_code" :value="editingAddress?.postal_code"
                                   class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="edit_is_default" name="is_default" value="1" :checked="editingAddress?.is_default" class="rounded text-brand-green-600 focus:ring-brand-green-500">
                        <label for="edit_is_default" class="text-xs font-semibold text-slate-700">Set as default shipping address</label>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="editingAddress = null" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-brand-green-600 hover:bg-brand-green-700 px-5 py-2 text-xs font-bold text-white shadow-sm transition">
                            Update Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
