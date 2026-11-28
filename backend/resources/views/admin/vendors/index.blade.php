@extends('layouts.admin')

@section('title', 'Vendors Management')
@section('heading', 'Pilot Vendors')

@section('header_actions')
    <a href="{{ route('admin.vendors.create') }}"
       class="px-4 py-2 rounded-full bg-[#1A1C19] text-white text-xs font-semibold flex items-center gap-2 hover:opacity-90">
        <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
        + Add Vendor
    </a>
@endsection

@section('content')
    <form method="GET" action="{{ route('admin.vendors.index') }}" class="bg-white rounded-[22px] p-4 border border-[#E6E7E3] mb-6 flex flex-wrap gap-3 items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search vendor name, code, area, address..."
               class="flex-1 min-w-[210px] px-4 py-2 rounded-full bg-[#F5F5F3] text-xs focus:outline-none">

        <select name="tier" class="px-3.5 py-2 rounded-full bg-[#F5F5F3] text-xs focus:outline-none">
            <option value="">All Tiers</option>
            <option value="hotel" @selected(request('tier') === 'hotel')>Hotel</option>
            <option value="restaurant" @selected(request('tier') === 'restaurant')>Restaurant</option>
            <option value="street" @selected(request('tier') === 'street')>Street Vendor</option>
        </select>

        @if($areaZones->isNotEmpty())
            <select name="area_zone" class="px-3.5 py-2 rounded-full bg-[#F5F5F3] text-xs focus:outline-none">
                <option value="">All Areas / Zones</option>
                @foreach($areaZones as $zone)
                    <option value="{{ $zone }}" @selected(request('area_zone') === $zone)>{{ $zone }}</option>
                @endforeach
            </select>
        @endif

        <select name="mou_status" class="px-3.5 py-2 rounded-full bg-[#F5F5F3] text-xs focus:outline-none">
            <option value="">All MOU/LOI</option>
            <option value="mou_active" @selected(request('mou_status') === 'mou_active')>MOU Active</option>
            <option value="loi" @selected(request('mou_status') === 'loi')>LOI</option>
            <option value="expired" @selected(request('mou_status') === 'expired')>Expired</option>
            <option value="none" @selected(request('mou_status') === 'none')>None</option>
        </select>

        <select name="iscc_status" class="px-3.5 py-2 rounded-full bg-[#F5F5F3] text-xs focus:outline-none">
            <option value="">All ISCC Status</option>
            <option value="active" @selected(request('iscc_status') === 'active')>ISCC Active</option>
            <option value="pending" @selected(request('iscc_status') === 'pending')>ISCC Pending</option>
            <option value="expired" @selected(request('iscc_status') === 'expired')>ISCC Expired</option>
            <option value="none" @selected(request('iscc_status') === 'none')>ISCC None</option>
        </select>

        <select name="compliance_flag" class="px-3.5 py-2 rounded-full bg-[#F5F5F3] text-xs focus:outline-none">
            <option value="">All Flags</option>
            <option value="expiring_attention" @selected(request('compliance_flag') === 'expiring_attention')>Expiring / Expired MOU or ISCC</option>
            <option value="animal_origin" @selected(request('compliance_flag') === 'animal_origin')>Animal Origin Only</option>
            <option value="10_plus_tonnes" @selected(request('compliance_flag') === '10_plus_tonnes')>10+ Tonnes/Mo Only</option>
        </select>

        <button type="submit" class="px-5 py-2 rounded-full bg-[#1A1C19] text-white text-xs font-semibold">Filter</button>
        @if(request()->hasAny(['search', 'tier', 'area_zone', 'mou_status', 'iscc_status', 'compliance_flag']))
            <a href="{{ route('admin.vendors.index') }}" class="text-xs font-semibold text-[#5D5F5B] underline">Reset</a>
        @endif
    </form>

    <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3]">
        @if($vendors->isEmpty())
            <div class="py-12 text-center text-sm text-[#7A7D78]">
                No vendors match the current filter. Click "+ Add Vendor" to onboard pilot vendors.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-[#7A7D78] border-b border-[#E6E7E3]">
                        <tr>
                            <th class="py-3 pr-4">Code</th>
                            <th class="py-3 pr-4">Vendor &amp; Area</th>
                            <th class="py-3 pr-4">Tier &amp; Flags</th>
                            <th class="py-3 pr-4">MOU / LOI</th>
                            <th class="py-3 pr-4">ISCC Self-Decl.</th>
                            <th class="py-3 pr-4">Expected / Qualiplast</th>
                            <th class="py-3 pr-4">Assets &amp; Docs</th>
                            <th class="py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F1EE]">
                        @foreach($vendors as $vendor)
                            <tr>
                                <td class="py-3.5 pr-4 font-mono text-xs font-semibold">
                                    <div>{{ $vendor->vendor_code }}</div>
                                    @if(!$vendor->is_active)
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-semibold">Inactive</span>
                                    @endif
                                </td>
                                <td class="py-3.5 pr-4">
                                    <div class="font-semibold text-[#141513]">{{ $vendor->name }}</div>
                                    @if($vendor->area_zone)
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full bg-[#F5F5F3] text-[11px] font-medium text-[#141513]">{{ $vendor->area_zone }}</span>
                                    @endif
                                    <div class="text-xs text-[#7A7D78] mt-0.5">{{ $vendor->address ?: 'No address' }}</div>
                                </td>
                                <td class="py-3.5 pr-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="px-2.5 py-0.5 rounded-full bg-[#F5F5F3] text-xs font-semibold capitalize">{{ $vendor->tier }}</span>
                                        @if($vendor->has_animal_origin)
                                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-semibold">Animal Origin</span>
                                        @endif
                                        @if($vendor->produces_10_plus_tonnes_monthly)
                                            <span class="px-2 py-0.5 rounded-full bg-[#1A1C19] text-[#B6F500] text-[11px] font-semibold">10+ t/mo</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 pr-4 text-xs">
                                    @if($vendor->isMouExpired())
                                        <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-700 font-semibold">Expired</span>
                                    @elseif($vendor->isMouExpiringSoon())
                                        <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-semibold">
                                            Expiring ({{ $vendor->mou_expiry_date?->format('d M') }})
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-[#B6F500]/40 text-[#141513] font-semibold uppercase">
                                            {{ str_replace('_', ' ', $vendor->mou_status) }}
                                        </span>
                                    @endif
                                    @if($vendor->mou_expiry_date)
                                        <div class="text-[11px] text-[#7A7D78] mt-1">Exp: {{ $vendor->mou_expiry_date->format('d M Y') }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 pr-4 text-xs">
                                    @if($vendor->isIsccExpired())
                                        <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-700 font-semibold">Expired</span>
                                    @elseif($vendor->isIsccExpiringSoon())
                                        <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-semibold">
                                            Expiring ({{ $vendor->iscc_expiry_date?->format('d M') }})
                                        </span>
                                    @elseif($vendor->iscc_declaration_status === 'active')
                                        <span class="px-2.5 py-1 rounded-full bg-[#B6F500]/50 text-[#141513] font-semibold uppercase">Active</span>
                                    @elseif($vendor->iscc_declaration_status === 'pending')
                                        <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 font-semibold uppercase">Pending</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-[#F5F5F3] text-[#7A7D78] font-semibold uppercase">None</span>
                                    @endif
                                    @if($vendor->iscc_expiry_date)
                                        <div class="text-[11px] text-[#7A7D78] mt-1">Exp: {{ $vendor->iscc_expiry_date->format('d M Y') }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 pr-4 text-xs">
                                    <div class="font-semibold text-[#141513]">
                                        @if($vendor->expected_monthly_volume !== null)
                                            {{ number_format($vendor->expected_monthly_volume, 0) }} {{ $vendor->expected_volume_unit === 'kg' ? 'kg/mo' : 'L/mo' }}
                                        @else
                                            &mdash;
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-[#7A7D78]">{{ $vendor->qualiplast_tier ?: 'No container tier' }}</div>
                                </td>
                                <td class="py-3.5 pr-4 text-xs">
                                    <div class="font-semibold">{{ $vendor->containers_count }} QR</div>
                                    <div class="text-[11px] text-[#7A7D78]">{{ $vendor->documents_count }} docs</div>
                                </td>
                                <td class="py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-3 text-xs font-semibold">
                                        <a href="{{ route('admin.vendors.edit', $vendor) }}" class="underline text-[#141513]">Edit</a>
                                        <a href="{{ route('admin.events.index', ['vendor_id' => $vendor->id]) }}" class="underline text-[#5D5F5B]">History</a>
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
