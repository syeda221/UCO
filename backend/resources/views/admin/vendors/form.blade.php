@extends('layouts.admin')

@section('title', $isEdit ? 'Edit Vendor' : 'Register Vendor')
@section('heading', $isEdit ? 'Edit Vendor: ' . $vendor->vendor_code : 'Register New Pilot Vendor')

@section('content')
    <form method="POST"
          action="{{ $isEdit ? route('admin.vendors.update', $vendor) : route('admin.vendors.store') }}"
          enctype="multipart/form-data"
          class="max-w-4xl space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] space-y-4">
            <h2 class="font-display font-bold text-base">1. Vendor Identity &amp; GPS Location</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Vendor Code *</label>
                    <input type="text" name="vendor_code" value="{{ old('vendor_code', $vendor->vendor_code) }}" required
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm font-mono">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Trading / Facility Name *</label>
                    <input type="text" name="name" value="{{ old('name', $vendor->name) }}" required
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Legal Entity Name</label>
                    <input type="text" name="legal_name" value="{{ old('legal_name', $vendor->legal_name) }}"
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Vendor Tier *</label>
                    <select name="tier" required class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                        <option value="hotel" @selected(old('tier', $vendor->tier) === 'hotel')>Hotel</option>
                        <option value="restaurant" @selected(old('tier', $vendor->tier) === 'restaurant')>Restaurant</option>
                        <option value="street" @selected(old('tier', $vendor->tier) === 'street')>Street Vendor</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Address / Zone (Ghana)</label>
                <input type="text" name="address" value="{{ old('address', $vendor->address) }}"
                       class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">GPS Latitude *</label>
                    <input type="number" step="0.0000001" name="gps_lat" value="{{ old('gps_lat', $vendor->gps_lat) }}" required
                           placeholder="e.g. 5.6037000"
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">GPS Longitude *</label>
                    <input type="number" step="0.0000001" name="gps_lng" value="{{ old('gps_lng', $vendor->gps_lng) }}" required
                           placeholder="e.g. -0.1870000"
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm font-mono">
                </div>
            </div>
            <p class="text-xs text-[#7A7D78]">Note: Field pickups more than 200m from these coordinates will automatically trigger an Admin GPS Mismatch Flag (collector work is not blocked).</p>
        </div>

        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] space-y-4">
            <h2 class="font-display font-bold text-base">2. MOU / LOI &amp; Qualiplast Allotment</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">MOU / LOI Status *</label>
                    <select name="mou_status" required class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                        <option value="none" @selected(old('mou_status', $vendor->mou_status) === 'none')>None</option>
                        <option value="loi" @selected(old('mou_status', $vendor->mou_status) === 'loi')>Letter of Intent (LOI)</option>
                        <option value="mou_active" @selected(old('mou_status', $vendor->mou_status) === 'mou_active')>MOU Active</option>
                        <option value="expired" @selected(old('mou_status', $vendor->mou_status) === 'expired')>Expired</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">MOU / LOI Expiry Date</label>
                    <input type="date" name="mou_expiry_date" value="{{ old('mou_expiry_date', $vendor->mou_expiry_date?->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Qualiplast Container Tier</label>
                    <input type="text" name="qualiplast_tier" value="{{ old('qualiplast_tier', $vendor->qualiplast_tier) }}"
                           placeholder="e.g. 20L Jerrycan / 50L Drum"
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Onboarding Documents (MOU / LOI / Permit PDF or Image)</label>
                <input type="file" name="onboarding_docs[]" multiple accept=".pdf,.jpg,.jpeg,.png"
                       class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-xs">
                @if($isEdit && !empty($vendor->onboarding_docs_json))
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($vendor->onboarding_docs_json as $idx => $doc)
                            <a href="{{ route('admin.vendors.doc', [$vendor, $idx]) }}"
                               class="px-3 py-1.5 rounded-full bg-[#F5F5F3] text-xs font-semibold underline">
                                {{ $doc['name'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <label class="inline-flex items-center gap-2 text-xs font-semibold cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $vendor->is_active))>
                <span>Vendor Active (visible in Collector mobile app dropdown)</span>
            </label>
        </div>

        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display font-bold text-base">3. Contact Chain</h2>
                <button type="button" onclick="addContactRow()" class="px-3 py-1.5 rounded-full bg-[#F5F5F3] text-xs font-semibold">+ Add Contact</button>
            </div>

            @php
                $contacts = old('contact_names')
                    ? collect(old('contact_names'))->map(fn($n, $i) => [
                        'name' => $n,
                        'role' => old('contact_roles')[$i] ?? '',
                        'phone' => old('contact_phones')[$i] ?? '',
                    ])->all()
                    : ($vendor->contact_chain_json ?: [['name' => '', 'role' => '', 'phone' => '']]);
            @endphp

            <div id="contact-chain-list" class="space-y-3">
                @foreach($contacts as $contact)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <input type="text" name="contact_names[]" value="{{ $contact['name'] ?? '' }}" placeholder="Contact Name"
                               class="px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                        <input type="text" name="contact_roles[]" value="{{ $contact['role'] ?? '' }}" placeholder="Role (e.g. Chef / Owner)"
                               class="px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                        <input type="text" name="contact_phones[]" value="{{ $contact['phone'] ?? '' }}" placeholder="Phone (+233...)"
                               class="px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-6 py-3 rounded-full bg-[#1A1C19] text-white text-sm font-semibold hover:opacity-95">
                {{ $isEdit ? 'Save Vendor Changes' : 'Register Vendor' }}
            </button>
            <a href="{{ route('admin.vendors.index') }}" class="px-5 py-3 rounded-full bg-white border border-[#E6E7E3] text-sm font-semibold">
                Cancel
            </a>
        </div>
    </form>

    <script>
        function addContactRow() {
            const container = document.getElementById('contact-chain-list');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 md:grid-cols-3 gap-3';
            row.innerHTML = `
                <input type="text" name="contact_names[]" placeholder="Contact Name" class="px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                <input type="text" name="contact_roles[]" placeholder="Role" class="px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                <input type="text" name="contact_phones[]" placeholder="Phone (+233...)" class="px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
            `;
            container.appendChild(row);
        }
    </script>
@endsection
