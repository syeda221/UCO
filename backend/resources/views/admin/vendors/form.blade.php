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
                <input type="text" name="address" id="vendor_address" value="{{ old('address', $vendor->address) }}"
                       class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
            </div>

            <!-- Interactive Map Picker + Search + Google Maps Link Parser -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
            <div class="p-4 rounded-2xl bg-[#F5F5F3] space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-xs font-semibold text-[#141513]">Pick Vendor Location (No Manual Typing Needed)</div>
                    <button type="button" onclick="useBrowserGps()"
                            class="px-3.5 py-1.5 rounded-full bg-[#1A1C19] text-[#B6F500] text-xs font-semibold hover:opacity-90">
                        Use My Current Location
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="flex gap-2">
                        <input type="text" id="place_search_input" placeholder="Search place (e.g. Kempinski Accra, Osu)..."
                               onkeydown="if(event.key==='Enter'){event.preventDefault(); searchPlace();}"
                               class="flex-1 px-3.5 py-2 rounded-full bg-white text-xs focus:outline-none">
                        <button type="button" onclick="searchPlace()"
                                class="px-4 py-2 rounded-full bg-[#1A1C19] text-white text-xs font-semibold">
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
                <div id="vendor_map" class="w-full h-64 rounded-2xl border border-[#E6E7E3] z-0"></div>
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
