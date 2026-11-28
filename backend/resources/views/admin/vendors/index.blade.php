@extends('layouts.admin')

@section('title', 'Vendors Management')
@section('heading', 'Pilot Vendors')

@section('header_actions')
    <a href="{{ route('admin.vendors.create') }}"
       class="px-4 py-2 rounded-full bg-[#B6F500] text-[#141513] text-xs font-bold flex items-center gap-2 shadow-sm hover:brightness-95 transition">
        <span class="w-5 h-5 rounded-full bg-[#1A1C19] text-[#B6F500] flex items-center justify-center">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </span>
        <span>Add Vendor</span>
    </a>
@endsection

@section('content')
    <!-- Stitch Quick Summary Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-[20px] p-4 border border-[#E6E7E3] flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-[#7A7D78]">Total Vendors</div>
                <div class="font-display font-extrabold text-2xl text-[#141513] mt-1">{{ $summary['total'] ?? $vendors->total() }}</div>
            </div>
            <span class="w-10 h-10 rounded-2xl bg-[#1A1C19] text-[#B6F500] flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </span>
        </div>

        <div class="bg-[#B6F500] text-[#141513] rounded-[20px] p-4 flex items-center justify-between shadow-sm">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-[#141513]/75">Active in App</div>
                <div class="font-display font-extrabold text-2xl mt-1">{{ $summary['active'] ?? 0 }}</div>
            </div>
            <span class="w-10 h-10 rounded-2xl bg-[#1A1C19] text-[#B6F500] flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </span>
        </div>

        <div class="bg-white rounded-[20px] p-4 border border-[#E6E7E3] flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-[#7A7D78]">ISCC Certified</div>
                <div class="font-display font-extrabold text-2xl text-[#141513] mt-1">{{ $summary['iscc_active'] ?? 0 }}</div>
            </div>
            <span class="w-10 h-10 rounded-2xl bg-[#B6F500]/35 text-[#141513] flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>
                </svg>
            </span>
        </div>

        <a href="{{ route('admin.vendors.index', ['compliance_flag' => 'expiring_attention']) }}"
           class="bg-white rounded-[20px] p-4 border border-[#E6E7E3] hover:border-[#B6F500] transition flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-[#7A7D78]">Expiry Attention</div>
                <div class="font-display font-extrabold text-2xl {{ ($summary['attention'] ?? 0) > 0 ? 'text-amber-700' : 'text-[#141513]' }} mt-1">
                    {{ $summary['attention'] ?? 0 }}
                </div>
            </div>
            <span class="w-10 h-10 rounded-2xl {{ ($summary['attention'] ?? 0) > 0 ? 'bg-amber-100 text-amber-800' : 'bg-[#F5F5F3] text-[#7A7D78]' }} flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </span>
        </a>
    </div>

    <!-- Search & Filters -->
    <form method="GET" action="{{ route('admin.vendors.index') }}" class="bg-white rounded-[22px] p-4 border border-[#E6E7E3] mb-6 flex flex-wrap gap-3 items-center shadow-[0_2px_12px_rgba(26,28,25,0.03)]">
        <div class="relative flex-1 min-w-[230px]">
            <svg class="w-4 h-4 text-[#7A7D78] absolute left-3.5 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search vendor name, code, area, address..."
                   class="w-full pl-10 pr-4 py-2 rounded-full bg-[#F5F5F3] text-xs focus:outline-none focus:ring-2 focus:ring-[#B6F500]">
        </div>

        <select name="tier" class="px-3.5 py-2 rounded-full bg-[#F5F5F3] text-xs font-medium focus:outline-none">
            <option value="">All Tiers</option>
            <option value="hotel" @selected(request('tier') === 'hotel')>Hotel</option>
            <option value="restaurant" @selected(request('tier') === 'restaurant')>Restaurant</option>
            <option value="street" @selected(request('tier') === 'street')>Street Vendor</option>
        </select>

        @if($areaZones->isNotEmpty())
            <select name="area_zone" class="px-3.5 py-2 rounded-full bg-[#F5F5F3] text-xs font-medium focus:outline-none">
                <option value="">All Areas / Zones</option>
                @foreach($areaZones as $zone)
                    <option value="{{ $zone }}" @selected(request('area_zone') === $zone)>{{ $zone }}</option>
                @endforeach
            </select>
        @endif

        <select name="mou_status" class="px-3.5 py-2 rounded-full bg-[#F5F5F3] text-xs font-medium focus:outline-none">
            <option value="">All MOU/LOI</option>
            <option value="mou_active" @selected(request('mou_status') === 'mou_active')>MOU Active</option>
            <option value="loi" @selected(request('mou_status') === 'loi')>LOI</option>
            <option value="expired" @selected(request('mou_status') === 'expired')>Expired</option>
            <option value="none" @selected(request('mou_status') === 'none')>None</option>
        </select>

        <select name="iscc_status" class="px-3.5 py-2 rounded-full bg-[#F5F5F3] text-xs font-medium focus:outline-none">
            <option value="">All ISCC Status</option>
            <option value="active" @selected(request('iscc_status') === 'active')>ISCC Active</option>
            <option value="pending" @selected(request('iscc_status') === 'pending')>ISCC Pending</option>
            <option value="expired" @selected(request('iscc_status') === 'expired')>ISCC Expired</option>
            <option value="none" @selected(request('iscc_status') === 'none')>ISCC None</option>
        </select>

        <select name="compliance_flag" class="px-3.5 py-2 rounded-full bg-[#F5F5F3] text-xs font-medium focus:outline-none">
            <option value="">All Flags</option>
            <option value="expiring_attention" @selected(request('compliance_flag') === 'expiring_attention')>Expiring / Expired MOU or ISCC</option>
            <option value="animal_origin" @selected(request('compliance_flag') === 'animal_origin')>Animal Origin Only</option>
            <option value="10_plus_tonnes" @selected(request('compliance_flag') === '10_plus_tonnes')>10+ Tonnes/Mo Only</option>
        </select>

        <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 rounded-full bg-[#1A1C19] text-white text-xs font-bold hover:opacity-95">
            <span class="w-1.5 h-1.5 rounded-full bg-[#B6F500]"></span>
            <span>Filter</span>
        </button>
        @if(request()->hasAny(['search', 'tier', 'area_zone', 'mou_status', 'iscc_status', 'compliance_flag']))
            <a href="{{ route('admin.vendors.index') }}" class="text-xs font-bold text-[#5D5F5B] underline">Reset</a>
        @endif
    </form>

    <!-- Vendors Directory Card -->
    <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)]">
        @if($vendors->isEmpty())
            <div class="py-12 text-center text-sm text-[#7A7D78] bg-[#F5F5F3] rounded-2xl">
                No vendors match the current filter. Click "+ Add Vendor" to onboard pilot vendors.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wider text-[#7A7D78] bg-[#F5F5F3]">
                        <tr>
                            <th class="py-3 px-3 rounded-l-xl">Code</th>
                            <th class="py-3 px-3">Vendor &amp; Area</th>
                            <th class="py-3 px-3">Tier &amp; Flags</th>
                            <th class="py-3 px-3">MOU / LOI</th>
                            <th class="py-3 px-3">ISCC Self-Decl.</th>
                            <th class="py-3 px-3">Expected / Qualiplast</th>
                            <th class="py-3 px-3">Assets &amp; Docs</th>
                            <th class="py-3 px-3 rounded-r-xl text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F1EE]">
                        @foreach($vendors as $vendor)
                            <tr class="hover:bg-[#F5F5F3]/60 transition">
                                <td class="py-3.5 px-3 font-mono text-xs font-bold">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full {{ $vendor->is_active ? 'bg-[#B6F500]' : 'bg-red-400' }}"></span>
                                        <span>{{ $vendor->vendor_code }}</span>
                                    </div>
                                    @if(!$vendor->is_active)
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold">Inactive</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="flex items-start gap-2.5">
                                        <span class="w-8 h-8 rounded-xl bg-[#B6F500]/25 text-[#141513] flex items-center justify-center shrink-0 mt-0.5">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                            </svg>
                                        </span>
                                        <div>
                                            <div class="font-bold text-[#141513]">{{ $vendor->name }}</div>
                                            @if($vendor->area_zone)
                                                <span class="inline-flex items-center gap-1 mt-0.5 px-2 py-0.5 rounded-full bg-[#F5F5F3] text-[11px] font-semibold text-[#141513]">
                                                    <svg class="w-3 h-3 text-[#5D5F5B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                                    {{ $vendor->area_zone }}
                                                </span>
                                            @endif
                                            <div class="text-xs text-[#7A7D78] mt-0.5">{{ $vendor->address ?: 'No address' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="px-2.5 py-0.5 rounded-full bg-[#F5F5F3] text-xs font-bold capitalize">{{ $vendor->tier }}</span>
                                        @if($vendor->has_animal_origin)
                                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold">Animal Origin</span>
                                        @endif
                                        @if($vendor->produces_10_plus_tonnes_monthly)
                                            <span class="px-2 py-0.5 rounded-full bg-[#1A1C19] text-[#B6F500] text-[11px] font-bold">10+ t/mo</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-xs">
                                    @if($vendor->isMouExpired())
                                        <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-700 font-bold">Expired</span>
                                    @elseif($vendor->isMouExpiringSoon())
                                        <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-bold">
                                            Expiring ({{ $vendor->mou_expiry_date?->format('d M') }})
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-[#B6F500]/50 text-[#141513] font-bold uppercase">
                                            {{ str_replace('_', ' ', $vendor->mou_status) }}
                                        </span>
                                    @endif
                                    @if($vendor->mou_expiry_date)
                                        <div class="text-[11px] text-[#7A7D78] mt-1">Exp: {{ $vendor->mou_expiry_date->format('d M Y') }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3 text-xs">
                                    @if($vendor->isIsccExpired())
                                        <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-700 font-bold">Expired</span>
                                    @elseif($vendor->isIsccExpiringSoon())
                                        <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-bold">
                                            Expiring ({{ $vendor->iscc_expiry_date?->format('d M') }})
                                        </span>
                                    @elseif($vendor->iscc_declaration_status === 'active')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#B6F500] text-[#141513] font-bold uppercase">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#141513]"></span>
                                            Active
                                        </span>
                                    @elseif($vendor->iscc_declaration_status === 'pending')
                                        <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 font-bold uppercase">Pending</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-[#F5F5F3] text-[#7A7D78] font-bold uppercase">None</span>
                                    @endif
                                    @if($vendor->iscc_expiry_date)
                                        <div class="text-[11px] text-[#7A7D78] mt-1">Exp: {{ $vendor->iscc_expiry_date->format('d M Y') }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3 text-xs">
                                    <div class="font-bold text-[#141513]">
                                        @if($vendor->expected_monthly_volume !== null)
                                            {{ number_format($vendor->expected_monthly_volume, 0) }} {{ $vendor->expected_volume_unit === 'kg' ? 'kg/mo' : 'L/mo' }}
                                        @else
                                            &mdash;
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-[#7A7D78]">{{ $vendor->qualiplast_tier ?: 'No container tier' }}</div>
                                </td>
                                <td class="py-3.5 px-3 text-xs">
                                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#F5F5F3] font-bold text-[#141513]">
                                        {{ $vendor->containers_count }} QR
                                    </div>
                                    <div class="text-[11px] text-[#7A7D78] mt-0.5">{{ $vendor->documents_count }} docs</div>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-2 text-xs font-bold">
                                        <a href="{{ route('admin.vendors.edit', $vendor) }}"
                                           class="px-3 py-1.5 rounded-full bg-[#1A1C19] text-white hover:bg-[#B6F500] hover:text-[#141513] transition">
                                            Edit
                                        </a>
                                        <a href="{{ route('admin.events.index', ['vendor_id' => $vendor->id]) }}"
                                           class="px-3 py-1.5 rounded-full bg-[#F5F5F3] text-[#141513] hover:bg-[#E6E7E3] transition">
                                            History
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $vendors->links() }}</div>
        @endif
    </div>
@endsection
