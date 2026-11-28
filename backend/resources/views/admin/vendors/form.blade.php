@extends('layouts.admin')

@section('title', $isEdit ? 'Edit Vendor' : 'Register Vendor')
@section('heading', $isEdit ? 'Edit Vendor: ' . $vendor->vendor_code : 'Register New Pilot Vendor')

@section('header_actions')
    @if($isEdit)
        <a href="{{ route('admin.events.index', ['vendor_id' => $vendor->id]) }}"
           class="px-4 py-2 rounded-full bg-[#B6F500] text-[#141513] text-xs font-bold inline-flex items-center gap-2 shadow-sm hover:brightness-95 transition">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <span>Collection History &rarr;</span>
        </a>
    @endif
@endsection

@section('content')
    @if($isEdit && $profileStats)
        @php
            $targetPct = ($profileStats['expected_liters'] ?? 0) > 0
                ? min(100, round(($profileStats['month_liters'] / $profileStats['expected_liters']) * 100))
                : 0;
        @endphp
        <div class="max-w-5xl bg-[#1A1C19] text-white rounded-[24px] p-6 mb-6 shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-[#B6F500] text-[#141513] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-[#B6F500] uppercase tracking-wider">Vendor Performance &amp; Compliance Snapshot</div>
                        <div class="font-display font-bold text-lg text-white flex flex-wrap items-center gap-2 mt-0.5">
                            <span>{{ $vendor->name }}</span>
                            @if($vendor->area_zone)
                                <span class="px-2.5 py-0.5 rounded-full bg-white/10 text-[#E6E7E3] text-xs font-semibold">{{ $vendor->area_zone }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-bold">
                    @if($vendor->has_animal_origin)
                        <span class="px-3 py-1 rounded-full bg-amber-400 text-[#141513]">Animal Origin Declared</span>
                    @endif
                    @if($vendor->produces_10_plus_tonnes_monthly)
                        <span class="px-3 py-1 rounded-full bg-[#B6F500] text-[#141513]">10+ Tonnes/Mo Producer</span>
                    @endif
                    @if($profileStats['exceeds_expected'])
                        <span class="px-3 py-1 rounded-full bg-amber-300 text-[#141513]">Above Expected Monthly Volume</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-[#B6F500] text-[#141513] p-4">
                    <div class="text-xs font-bold text-[#141513]/75">Total Effective Collected</div>
                    <div class="font-display font-extrabold text-xl mt-1">{{ number_format($profileStats['total_liters'], 1) }} L</div>
                    <div class="text-[11px] font-semibold mt-0.5">{{ $profileStats['pickups_count'] }} verified pickups</div>
                </div>
                <div class="rounded-2xl bg-white/10 p-4">
                    <div class="text-xs text-[#A3A6A0]">This Month vs Expected</div>
                    <div class="font-display font-bold text-xl text-white mt-1">{{ number_format($profileStats['month_liters'], 1) }} L</div>
                    <div class="text-[11px] text-[#D6D8D2] mt-0.5">
                        @if($vendor->expected_monthly_volume !== null)
                            Target: {{ number_format($vendor->expected_monthly_volume, 0) }} {{ $vendor->expected_volume_unit === 'kg' ? 'kg/mo (~' . number_format($profileStats['expected_liters'], 0) . ' L)' : 'L/mo' }}
                        @else
                            Expected monthly volume not set
                        @endif
                    </div>
                    @if(($profileStats['expected_liters'] ?? 0) > 0)
                        <div class="w-full h-1.5 rounded-full bg-white/15 mt-2 overflow-hidden">
                            <div class="h-full rounded-full bg-[#B6F500]" style="width: {{ $targetPct }}%"></div>
                        </div>
                    @endif
                </div>
                <div class="rounded-2xl bg-white/10 p-4">
                    <div class="text-xs text-[#A3A6A0]">Average Field Quality</div>
                    <div class="font-display font-bold text-xl text-white mt-1">
                        {{ $profileStats['avg_ffa'] !== null ? $profileStats['avg_ffa'] . '% FFA' : 'N/A' }}
                    </div>
                    <div class="text-[11px] text-[#D6D8D2] mt-0.5">
                        MIU Avg: {{ $profileStats['avg_miu'] !== null ? $profileStats['avg_miu'] . '%' : 'N/A' }}
                    </div>
                </div>
                <div class="rounded-2xl bg-white/10 p-4">
                    <div class="text-xs text-[#A3A6A0]">Containers &amp; Permits</div>
                    <div class="font-display font-bold text-xl text-[#B6F500] mt-1">{{ $vendor->containers->count() }} QR</div>
                    <div class="text-[11px] text-[#D6D8D2] mt-0.5">{{ $profileStats['active_docs_count'] }} active compliance docs</div>
                </div>
            </div>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('admin.vendors.update', $vendor) : route('admin.vendors.store') }}"
          enctype="multipart/form-data"
          class="max-w-5xl space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] space-y-4 shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)]">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-[#B6F500] text-[#141513] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                </span>
                <div>
                    <h2 class="font-display font-bold text-base">1. Vendor Identity, Volume Profile &amp; GPS Location</h2>
                    <div class="text-xs text-[#7A7D78]">Official registry details, collector zone, monthly capacity, and geofence pin</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Vendor Code *</label>
                    <input type="text" name="vendor_code" value="{{ old('vendor_code', $vendor->vendor_code) }}" required
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm font-mono font-semibold focus:outline-none focus:ring-2 focus:ring-[#B6F500]">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Trading / Facility Name *</label>
                    <input type="text" name="name" value="{{ old('name', $vendor->name) }}" required
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm focus:outline-none focus:ring-2 focus:ring-[#B6F500]">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Legal Entity Name</label>
                    <input type="text" name="legal_name" value="{{ old('legal_name', $vendor->legal_name) }}"
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm focus:outline-none focus:ring-2 focus:ring-[#B6F500]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Vendor Tier *</label>
                    <select name="tier" required class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm focus:outline-none focus:ring-2 focus:ring-[#B6F500]">
                        <option value="hotel" @selected(old('tier', $vendor->tier) === 'hotel')>Hotel</option>
                        <option value="restaurant" @selected(old('tier', $vendor->tier) === 'restaurant')>Restaurant</option>
                        <option value="street" @selected(old('tier', $vendor->tier) === 'street')>Street Vendor</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Street Address / Landmark (Ghana)</label>
                    <input type="text" name="address" id="vendor_address" value="{{ old('address', $vendor->address) }}"
                           placeholder="e.g. Oxford Street, Plot 14"
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm focus:outline-none focus:ring-2 focus:ring-[#B6F500]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Area / Zone (for Collector Scoping)</label>
                    <input type="text" name="area_zone" value="{{ old('area_zone', $vendor->area_zone) }}"
                           placeholder="e.g. Osu, Airport Residential, Tema"
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm focus:outline-none focus:ring-2 focus:ring-[#B6F500]">
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-[#F5F5F3] space-y-3 border border-[#E6E7E3]">
                <div class="flex items-center gap-2 text-xs font-bold text-[#141513]">
                    <span class="w-6 h-6 rounded-lg bg-[#B6F500] text-[#141513] flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M12 20V10M18 20V4M6 20v-4"/></svg>
                    </span>
                    <span>Expected Output &amp; ISCC Feedstock Declarations</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Expected Monthly Volume</label>
                        <input type="number" step="0.01" min="0" name="expected_monthly_volume" id="expected_monthly_volume"
                               value="{{ old('expected_monthly_volume', $vendor->expected_monthly_volume) }}"
                               placeholder="e.g. 450"
                               class="w-full px-4 py-2.5 rounded-2xl bg-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Volume Unit</label>
                        <select name="expected_volume_unit" id="expected_volume_unit" class="w-full px-4 py-2.5 rounded-2xl bg-white text-sm">
                            <option value="liters" @selected(old('expected_volume_unit', $vendor->expected_volume_unit ?? 'liters') === 'liters')>Liters / Month</option>
                            <option value="kg" @selected(old('expected_volume_unit', $vendor->expected_volume_unit) === 'kg')>kg / Month</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2.5 p-3 rounded-2xl bg-white border border-[#E6E7E3] has-[:checked]:bg-[#B6F500]/25 has-[:checked]:border-[#B6F500] cursor-pointer transition">
                        <input type="hidden" name="produces_10_plus_tonnes_monthly" value="0">
                        <input type="checkbox" name="produces_10_plus_tonnes_monthly" value="1" class="accent-[#1A1C19] w-4 h-4"
                               @checked(old('produces_10_plus_tonnes_monthly', $vendor->produces_10_plus_tonnes_monthly))>
                        <span class="text-xs font-bold text-[#141513]">10+ tonnes/month</span>
                    </label>
                    <label class="flex items-center gap-2.5 p-3 rounded-2xl bg-white border border-[#E6E7E3] has-[:checked]:bg-amber-100 has-[:checked]:border-amber-400 cursor-pointer transition">
                        <input type="hidden" name="has_animal_origin" value="0">
                        <input type="checkbox" name="has_animal_origin" value="1" class="accent-[#1A1C19] w-4 h-4"
                               @checked(old('has_animal_origin', $vendor->has_animal_origin))>
                        <span class="text-xs font-bold text-[#141513]">Animal origin declared</span>
                    </label>
                </div>
            </div>

            <!-- Interactive Map Picker + Search + Google Maps Link Parser -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
            <div class="p-4 rounded-2xl bg-[#F5F5F3] space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-xs font-bold text-[#141513]">Pick Vendor Location (No Manual Typing Needed)</div>
                    <button type="button" onclick="useBrowserGps()"
                            class="px-3.5 py-1.5 rounded-full bg-[#1A1C19] text-[#B6F500] text-xs font-bold inline-flex items-center gap-1.5 hover:opacity-90">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
                        <span>Use My Current Location</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="flex gap-2">
                        <input type="text" id="place_search_input" placeholder="Search place (e.g. Kempinski Accra, Osu)..."
                               onkeydown="if(event.key==='Enter'){event.preventDefault(); searchPlace();}"
                               class="flex-1 px-3.5 py-2 rounded-full bg-white text-xs focus:outline-none">
                        <button type="button" onclick="searchPlace()"
                                class="px-4 py-2 rounded-full bg-[#B6F500] text-[#141513] text-xs font-bold hover:brightness-95">
                            Search
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" id="gmaps_link_input" placeholder="Paste Google Maps link or lat, lng..."
                               oninput="parseMapsInput(this.value)"
                               class="flex-1 px-3.5 py-2 rounded-full bg-white text-xs focus:outline-none">
                    </div>
                </div>

                <div id="map_status" class="text-[11px] text-[#5D5F5B] hidden"></div>
                <div id="vendor_map" class="w-full rounded-2xl border border-[#E6E7E3] z-0" style="height: 280px; min-height: 280px;"></div>
                <p class="text-[11px] text-[#7A7D78]">Click anywhere on the map or drag the pin to set exact coordinates.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">GPS Latitude *</label>
                    <input type="number" step="0.0000001" name="gps_lat" id="gps_lat" value="{{ old('gps_lat', $vendor->gps_lat) }}" required
                           placeholder="e.g. 5.6037000" onchange="syncMapFromInputs()"
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">GPS Longitude *</label>
                    <input type="number" step="0.0000001" name="gps_lng" id="gps_lng" value="{{ old('gps_lng', $vendor->gps_lng) }}" required
                           placeholder="e.g. -0.1870000" onchange="syncMapFromInputs()"
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm font-mono">
                </div>
            </div>
            <p class="text-xs text-[#7A7D78]">Note: Field pickups more than 200m from these coordinates will automatically trigger an Admin GPS Mismatch Flag (collector work is not blocked).</p>
        </div>

        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] space-y-5 shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)]">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-[#1A1C19] text-[#B6F500] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>
                    </svg>
                </span>
                <div>
                    <h2 class="font-display font-bold text-base">2. MOU / LOI, ISCC Self-Declaration &amp; Onboarding Documents</h2>
                    <div class="text-xs text-[#7A7D78]">Commercial agreements, ISCC Point-of-Origin certification, and permits</div>
                </div>
            </div>

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

            <!-- ISCC Self-Declaration Block -->
            <div class="p-4 rounded-2xl bg-[#B6F500]/12 border border-[#B6F500]/50 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#141513]">
                        <span class="w-6 h-6 rounded-lg bg-[#1A1C19] text-[#B6F500] flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <span>ISCC Point-of-Origin Self-Declaration</span>
                    </div>
                    @if($isEdit)
                        @if($vendor->isIsccExpired())
                            <span class="px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 text-[11px] font-bold">ISCC Expired</span>
                        @elseif($vendor->isIsccExpiringSoon())
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold">Expiring Soon</span>
                        @elseif($vendor->iscc_declaration_status === 'active')
                            <span class="px-2.5 py-0.5 rounded-full bg-[#B6F500] text-[#141513] text-[11px] font-bold">ISCC Active</span>
                        @endif
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">ISCC Declaration Status</label>
                        <select name="iscc_declaration_status" class="w-full px-4 py-2.5 rounded-2xl bg-white text-sm">
                            <option value="none" @selected(old('iscc_declaration_status', $vendor->iscc_declaration_status ?? 'none') === 'none')>None</option>
                            <option value="pending" @selected(old('iscc_declaration_status', $vendor->iscc_declaration_status) === 'pending')>Pending Signature</option>
                            <option value="active" @selected(old('iscc_declaration_status', $vendor->iscc_declaration_status) === 'active')>Signed &amp; Active</option>
                            <option value="expired" @selected(old('iscc_declaration_status', $vendor->iscc_declaration_status) === 'expired')>Expired</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">ISCC Signed Date</label>
                        <input type="date" name="iscc_signed_date" value="{{ old('iscc_signed_date', $vendor->iscc_signed_date?->format('Y-m-d')) }}"
                               class="w-full px-4 py-2.5 rounded-2xl bg-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">ISCC Renewal / Expiry Date</label>
                        <input type="date" name="iscc_expiry_date" value="{{ old('iscc_expiry_date', $vendor->iscc_expiry_date?->format('Y-m-d')) }}"
                               class="w-full px-4 py-2.5 rounded-2xl bg-white text-sm">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex-1 min-w-[240px]">
                        <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Upload Signed ISCC Self-Declaration (PDF/JPG/PNG)</label>
                        <input type="file" name="iscc_doc" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full px-4 py-2 rounded-2xl bg-white text-xs">
                    </div>
                    @if($isEdit && $vendor->iscc_doc_path)
                        <div class="pt-4">
                            <a href="{{ route('admin.vendors.iscc-doc', $vendor) }}"
                               class="px-4 py-2 rounded-full bg-[#1A1C19] text-[#B6F500] text-xs font-bold inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <span>Download: {{ $vendor->iscc_doc_name ?: 'ISCC Declaration' }}</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Structured Onboarding / Permit Upload -->
            <div class="p-4 rounded-2xl border border-[#E6E7E3] space-y-3 bg-[#F9F9F7]">
                <div class="text-xs font-bold text-[#141513]">Attach Onboarding Document / Permit (Optional)</div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5D5F5B] mb-1">Document Type</label>
                        <select name="doc_type" class="w-full px-3 py-2 rounded-2xl bg-white text-xs">
                            @foreach(\App\Models\VendorDocument::TYPE_LABELS as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" @selected(old('doc_type', 'mou') === $typeKey)>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5D5F5B] mb-1">Reference No.</label>
                        <input type="text" name="doc_reference_number" value="{{ old('doc_reference_number') }}"
                               placeholder="e.g. EPA-GH-2026-01"
                               class="w-full px-3 py-2 rounded-2xl bg-white text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5D5F5B] mb-1">Issue Date</label>
                        <input type="date" name="doc_issue_date" value="{{ old('doc_issue_date') }}"
                               class="w-full px-3 py-2 rounded-2xl bg-white text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5D5F5B] mb-1">Expiry Date</label>
                        <input type="date" name="doc_expiry_date" value="{{ old('doc_expiry_date') }}"
                               class="w-full px-3 py-2 rounded-2xl bg-white text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5D5F5B] mb-1">Document Files (PDF / JPG / PNG)</label>
                        <input type="file" name="onboarding_docs[]" multiple accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full px-4 py-2 rounded-2xl bg-white text-xs">
                    </div>
                    @if($isEdit && $vendor->documents->where('status', 'active')->isNotEmpty())
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5D5F5B] mb-1">Renew / Replace Existing Document (Optional)</label>
                            <select name="replace_document_id" id="replace_document_id" class="w-full px-3 py-2 rounded-2xl bg-white text-xs">
                                <option value="">Keep existing &mdash; add as new document</option>
                                @foreach($vendor->documents->where('status', 'active') as $activeDoc)
                                    <option value="{{ $activeDoc->id }}">
                                        Replace: {{ $activeDoc->typeLabel() }} {{ $activeDoc->reference_number ? '(' . $activeDoc->reference_number . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <label class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-2xl bg-[#F5F5F3] border border-[#E6E7E3] has-[:checked]:bg-[#B6F500]/25 has-[:checked]:border-[#B6F500] text-xs font-bold cursor-pointer transition">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="accent-[#1A1C19] w-4 h-4" @checked(old('is_active', $vendor->is_active))>
                <span>Vendor Active (visible in Collector mobile app dropdown)</span>
            </label>
        </div>

        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] space-y-4 shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)]">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-[#B6F500]/35 text-[#141513] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="font-display font-bold text-base">3. Contact Chain</h2>
                        <p class="text-xs text-[#7A7D78]">Authorized facility contacts (name, role, phone, and optional email).</p>
                    </div>
                </div>
                <button type="button" onclick="addContactRow()" class="px-4 py-2 rounded-full bg-[#1A1C19] text-[#B6F500] text-xs font-bold hover:opacity-90">+ Add Contact</button>
            </div>

            <div id="contact-chain-list" class="space-y-3">
                @php
                    $oldNames = old('contact_names');
                    $contacts = is_array($oldNames)
                        ? collect($oldNames)->map(fn ($name, $idx) => [
                            'name' => $name,
                            'role' => old("contact_roles.$idx"),
                            'phone' => old("contact_phones.$idx"),
                            'email' => old("contact_emails.$idx"),
                        ])->all()
                        : ($vendor->contact_chain_json ?: [['name' => '', 'role' => '', 'phone' => '', 'email' => '']]);
                @endphp

                @foreach($contacts as $contact)
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-center contact-row">
                        <input type="text" name="contact_names[]" value="{{ $contact['name'] ?? '' }}" placeholder="Contact Name"
                               class="px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                        <input type="text" name="contact_roles[]" value="{{ $contact['role'] ?? '' }}" placeholder="Role (e.g. Chef / Owner)"
                               class="px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                        <input type="text" name="contact_phones[]" value="{{ $contact['phone'] ?? '' }}" placeholder="Phone (+233...)"
                               class="px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                        <div class="flex gap-2 items-center">
                            <input type="email" name="contact_emails[]" value="{{ $contact['email'] ?? '' }}" placeholder="Email (optional)"
                                   class="flex-1 min-w-0 px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                            <button type="button" onclick="removeContactRow(this)" title="Remove row"
                                    class="px-2.5 py-2 rounded-full bg-[#F5F5F3] text-xs font-semibold text-[#7A7D78] hover:text-red-700">&times;</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-6 py-3 rounded-full bg-[#B6F500] text-[#141513] text-sm font-bold shadow-sm hover:brightness-95 transition inline-flex items-center gap-2">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <span>{{ $isEdit ? 'Save Vendor Changes' : 'Register Vendor' }}</span>
            </button>
            <a href="{{ route('admin.vendors.index') }}" class="px-5 py-3 rounded-full bg-white border border-[#E6E7E3] text-sm font-semibold hover:bg-[#F5F5F3]">
                Cancel
            </a>
        </div>
    </form>

    @if($isEdit)
        <!-- 4. Standalone Document & Permit History + Direct Upload/Renew -->
        <div class="max-w-5xl bg-white rounded-[24px] p-6 border border-[#E6E7E3] mt-6 space-y-5 shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)]">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-[#B6F500] text-[#141513] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    </svg>
                </span>
                <div>
                    <h2 class="font-display font-bold text-base">4. Vendor Documents &amp; Permits History</h2>
                    <p class="text-xs text-[#7A7D78]">Track MOU, LOI, ISCC declarations, environmental permits, renewals, and expiry reminders.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.vendors.documents.store', $vendor) }}" enctype="multipart/form-data"
                  class="p-4 rounded-2xl bg-[#F5F5F3] space-y-3 border border-[#E6E7E3]">
                @csrf
                <div class="text-xs font-bold text-[#141513]">Quick Upload / Renew Permit Document</div>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <select name="doc_type" required class="px-3 py-2 rounded-2xl bg-white text-xs">
                        @foreach(\App\Models\VendorDocument::TYPE_LABELS as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="doc_reference_number" placeholder="Reference No."
                           class="px-3 py-2 rounded-2xl bg-white text-xs">
                    <input type="date" name="doc_issue_date" title="Issue Date"
                           class="px-3 py-2 rounded-2xl bg-white text-xs">
                    <input type="date" name="doc_expiry_date" title="Expiry Date"
                           class="px-3 py-2 rounded-2xl bg-white text-xs">
                    <select name="replace_document_id" id="quick_replace_doc_id" class="px-3 py-2 rounded-2xl bg-white text-xs">
                        <option value="">New document</option>
                        @foreach($vendor->documents->where('status', 'active') as $activeDoc)
                            <option value="{{ $activeDoc->id }}">Replace: {{ $activeDoc->typeLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png"
                           class="flex-1 min-w-[220px] px-4 py-2 rounded-2xl bg-white text-xs">
                    <input type="text" name="doc_notes" placeholder="Optional note (e.g. Annual renewal)"
                           class="flex-1 min-w-[200px] px-3 py-2 rounded-2xl bg-white text-xs">
                    <button type="submit" class="px-5 py-2 rounded-full bg-[#B6F500] text-[#141513] text-xs font-bold hover:brightness-95 transition">
                        Upload Document
                    </button>
                </div>
            </form>

            @if($vendor->documents->isEmpty() && empty($vendor->onboarding_docs_json))
                <div class="py-6 text-center text-xs text-[#7A7D78] bg-[#F5F5F3] rounded-2xl">
                    No permits or compliance documents recorded yet.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-[#7A7D78] bg-[#F5F5F3] uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5 px-3 rounded-l-xl">Document Type</th>
                                <th class="py-2.5 px-3">Ref No.</th>
                                <th class="py-2.5 px-3">Issue Date</th>
                                <th class="py-2.5 px-3">Expiry Date</th>
                                <th class="py-2.5 px-3">Status</th>
                                <th class="py-2.5 px-3">File</th>
                                <th class="py-2.5 px-3 rounded-r-xl text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F0F1EE]">
                            @foreach($vendor->documents as $doc)
                                <tr class="hover:bg-[#F5F5F3]/60 transition">
                                    <td class="py-3 px-3 font-bold text-[#141513]">
                                        {{ $doc->typeLabel() }}
                                        @if($doc->notes)
                                            <div class="text-[11px] font-normal text-[#7A7D78]">{{ $doc->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 font-mono">{{ $doc->reference_number ?: '—' }}</td>
                                    <td class="py-3 px-3">{{ $doc->issue_date?->format('d M Y') ?: '—' }}</td>
                                    <td class="py-3 px-3">{{ $doc->expiry_date?->format('d M Y') ?: '—' }}</td>
                                    <td class="py-3 px-3">
                                        @if($doc->status === 'replaced')
                                            <span class="px-2.5 py-0.5 rounded-full bg-[#F5F5F3] text-[#7A7D78] font-bold">Replaced</span>
                                        @elseif($doc->isExpired())
                                            <span class="px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 font-bold">Expired</span>
                                        @elseif($doc->isExpiringSoon())
                                            <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold">Expiring Soon</span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full bg-[#B6F500] text-[#141513] font-bold">Active</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        <a href="{{ route('admin.vendors.documents.download', [$vendor, $doc]) }}" class="underline font-bold">
                                            {{ $doc->file_name }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-3 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-2">
                                            @if($doc->status === 'active')
                                                <button type="button"
                                                        onclick="selectForReplace({{ $doc->id }})"
                                                        class="px-2.5 py-1 rounded-full bg-[#1A1C19] text-white font-bold hover:bg-[#B6F500] hover:text-[#141513] transition">
                                                    Renew
                                                </button>
                                                <form method="POST" action="{{ route('admin.vendors.documents.expire', [$vendor, $doc]) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 rounded-full bg-red-50 text-red-700 font-bold hover:bg-red-100 transition">Mark Expired</button>
                                                </form>
                                            @else
                                                <span class="text-[#7A7D78]">&mdash;</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            @foreach(($vendor->onboarding_docs_json ?? []) as $idx => $legacyDoc)
                                <tr>
                                    <td class="py-3 px-3 font-bold text-[#141513]">Legacy Onboarding File</td>
                                    <td class="py-3 px-3">&mdash;</td>
                                    <td class="py-3 px-3">&mdash;</td>
                                    <td class="py-3 px-3">&mdash;</td>
                                    <td class="py-3 px-3">
                                        <span class="px-2.5 py-0.5 rounded-full bg-[#F5F5F3] text-[#5D5F5B] font-bold">Archived</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <a href="{{ route('admin.vendors.doc', [$vendor, $idx]) }}" class="underline font-bold">
                                            {{ $legacyDoc['name'] }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-3 text-right text-[#7A7D78]">&mdash;</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- 5. Assigned Containers & Recent Collection History -->
        <div class="max-w-5xl grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)]">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-[#1A1C19] text-[#B6F500] flex items-center justify-center">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zm4 4h3v3h-3z"/></svg>
                        </span>
                        <h2 class="font-display font-bold text-base">Assigned QR Containers ({{ $vendor->containers->count() }})</h2>
                    </div>
                    <a href="{{ route('admin.containers.index') }}" class="text-xs font-bold underline">Manage Containers</a>
                </div>
                @if($vendor->containers->isEmpty())
                    <div class="py-6 text-center text-xs text-[#7A7D78] bg-[#F5F5F3] rounded-2xl">No QR containers assigned yet.</div>
                @else
                    <div class="space-y-2.5">
                        @foreach($vendor->containers as $c)
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-[#F5F5F3] text-xs">
                                <div>
                                    <div class="font-mono font-bold text-[#141513]">{{ $c->qr_code }}</div>
                                    <div class="text-[#7A7D78]">{{ $c->qualiplast_tier }} &bull; {{ number_format($c->capacity_liters, 0) }}L</div>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full {{ $c->status === 'active' ? 'bg-[#B6F500] text-[#141513]' : 'bg-red-100 text-red-700' }} font-bold uppercase">
                                    {{ $c->status }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)]">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-[#B6F500]/35 text-[#141513] flex items-center justify-center">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </span>
                        <h2 class="font-display font-bold text-base">Recent Collections</h2>
                    </div>
                    <a href="{{ route('admin.events.index', ['vendor_id' => $vendor->id]) }}" class="text-xs font-bold underline">View All History &rarr;</a>
                </div>
                @if($recentEvents->isEmpty())
                    <div class="py-6 text-center text-xs text-[#7A7D78] bg-[#F5F5F3] rounded-2xl">No pickups synced for this vendor yet.</div>
                @else
                    <div class="space-y-2.5">
                        @foreach($recentEvents as $evt)
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-[#F5F5F3] text-xs">
                                <div>
                                    <div class="font-bold text-[#141513]">
                                        {{ number_format($evt->volume_liters, 1) }} L
                                        @if($evt->field_ffa_percent !== null)
                                            <span class="text-[#7A7D78] font-normal">&bull; FFA {{ $evt->field_ffa_percent }}%</span>
                                        @endif
                                    </div>
                                    <div class="text-[#7A7D78]">
                                        {{ $evt->collected_at?->format('d M Y, H:i') }} &bull; {{ $evt->collector?->name ?? 'Collector' }}
                                    </div>
                                </div>
                                <a href="{{ route('admin.events.show', $evt) }}" class="px-3 py-1 rounded-full bg-[#1A1C19] text-white font-bold hover:bg-[#B6F500] hover:text-[#141513] transition">Inspect</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const latInput = document.getElementById('gps_lat');
        const lngInput = document.getElementById('gps_lng');
        const statusBox = document.getElementById('map_status');

        const defaultLat = parseFloat(latInput.value) || 5.6037;
        const defaultLng = parseFloat(lngInput.value) || -0.1870;

        const map = L.map('vendor_map').setView([defaultLat, defaultLng], latInput.value ? 16 : 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
        setTimeout(() => map.invalidateSize(), 250);

        function setCoords(lat, lng, zoom = null, msg = null) {
            const fixedLat = Number(lat).toFixed(7);
            const fixedLng = Number(lng).toFixed(7);
            latInput.value = fixedLat;
            lngInput.value = fixedLng;
            marker.setLatLng([fixedLat, fixedLng]);
            if (zoom) {
                map.setView([fixedLat, fixedLng], zoom);
            } else {
                map.panTo([fixedLat, fixedLng]);
            }
            if (msg) {
                statusBox.textContent = msg;
                statusBox.classList.remove('hidden');
            }
        }

        map.on('click', (e) => setCoords(e.latlng.lat, e.latlng.lng, null, 'Pin updated from map click.'));
        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            setCoords(pos.lat, pos.lng, null, 'Pin updated from drag.');
        });

        function syncMapFromInputs() {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            if (!isNaN(lat) && !isNaN(lng)) {
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], 16);
            }
        }

        function useBrowserGps() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported in this browser.');
                return;
            }
            statusBox.textContent = 'Detecting current GPS...';
            statusBox.classList.remove('hidden');
            navigator.geolocation.getCurrentPosition(
                (pos) => setCoords(pos.coords.latitude, pos.coords.longitude, 17, `Current location locked (±${Math.round(pos.coords.accuracy)}m).`),
                () => {
                    statusBox.textContent = 'Could not get browser location. Please search or click on the map.';
                },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        }

        async function searchPlace() {
            const query = document.getElementById('place_search_input').value.trim();
            if (!query) return;
            statusBox.textContent = 'Searching place...';
            statusBox.classList.remove('hidden');
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`);
                const items = await res.json();
                if (!items.length) {
                    statusBox.textContent = 'No match found. Try adding city name (e.g. Accra) or click on the map.';
                    return;
                }
                const first = items[0];
                setCoords(first.lat, first.lon, 16, `Found: ${first.display_name}`);
                const addrInput = document.getElementById('vendor_address');
                if (addrInput && !addrInput.value.trim()) {
                    addrInput.value = first.display_name.split(',').slice(0, 3).join(',').trim();
                }
            } catch {
                statusBox.textContent = 'Place search failed. Check internet or click directly on the map.';
            }
        }

        function parseMapsInput(raw) {
            if (!raw) return;
            const patterns = [
                /@(-?\d+\.\d+),\s*(-?\d+\.\d+)/,
                /[?&](?:q|ll|center)=(-?\d+\.\d+),\s*(-?\d+\.\d+)/,
                /!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/,
                /^\s*(-?\d+\.\d+)\s*[,\s]\s*(-?\d+\.\d+)\s*$/
            ];
            for (const regex of patterns) {
                const m = raw.match(regex);
                if (m) {
                    const lat = parseFloat(m[1]);
                    const lng = parseFloat(m[2]);
                    if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                        setCoords(lat, lng, 17, `Extracted coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
                        return;
                    }
                }
            }
        }

        function addContactRow() {
            const container = document.getElementById('contact-chain-list');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 md:grid-cols-4 gap-3 items-center contact-row';

            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.name = 'contact_names[]';
            nameInput.placeholder = 'Contact Name';
            nameInput.className = 'px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm';

            const roleInput = document.createElement('input');
            roleInput.type = 'text';
            roleInput.name = 'contact_roles[]';
            roleInput.placeholder = 'Role';
            roleInput.className = 'px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm';

            const phoneInput = document.createElement('input');
            phoneInput.type = 'text';
            phoneInput.name = 'contact_phones[]';
            phoneInput.placeholder = 'Phone (+233...)';
            phoneInput.className = 'px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm';

            const emailWrap = document.createElement('div');
            emailWrap.className = 'flex gap-2 items-center';

            const emailInput = document.createElement('input');
            emailInput.type = 'email';
            emailInput.name = 'contact_emails[]';
            emailInput.placeholder = 'Email (optional)';
            emailInput.className = 'flex-1 min-w-0 px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm';

            const rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.title = 'Remove row';
            rmBtn.className = 'px-2.5 py-2 rounded-full bg-[#F5F5F3] text-xs font-semibold text-[#7A7D78] hover:text-red-700';
            rmBtn.textContent = '\u00D7';
            rmBtn.onclick = function () { removeContactRow(this); };

            emailWrap.appendChild(emailInput);
            emailWrap.appendChild(rmBtn);

            row.appendChild(nameInput);
            row.appendChild(roleInput);
            row.appendChild(phoneInput);
            row.appendChild(emailWrap);
            container.appendChild(row);
        }

        function removeContactRow(btn) {
            const container = document.getElementById('contact-chain-list');
            const rows = container.querySelectorAll('.contact-row');
            const targetRow = btn.closest('.contact-row');
            if (!targetRow) return;
            if (rows.length <= 1) {
                targetRow.querySelectorAll('input').forEach((inp) => { inp.value = ''; });
                return;
            }
            targetRow.remove();
        }

        function selectForReplace(docId) {
            const selectEl = document.getElementById('quick_replace_doc_id');
            if (!selectEl) return;
            selectEl.value = String(docId);
            selectEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            selectEl.focus();
        }
    </script>
@endsection
