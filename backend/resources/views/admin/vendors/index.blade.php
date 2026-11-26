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
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search vendor name, code, address..."
               class="flex-1 min-w-[220px] px-4 py-2 rounded-full bg-[#F5F5F3] text-sm focus:outline-none">

        <select name="tier" class="px-4 py-2 rounded-full bg-[#F5F5F3] text-sm focus:outline-none">
            <option value="">All Tiers</option>
            <option value="hotel" @selected(request('tier') === 'hotel')>Hotel</option>
            <option value="restaurant" @selected(request('tier') === 'restaurant')>Restaurant</option>
            <option value="street" @selected(request('tier') === 'street')>Street Vendor</option>
        </select>

        <select name="mou_status" class="px-4 py-2 rounded-full bg-[#F5F5F3] text-sm focus:outline-none">
            <option value="">All MOU/LOI Status</option>
            <option value="mou_active" @selected(request('mou_status') === 'mou_active')>MOU Active</option>
            <option value="loi" @selected(request('mou_status') === 'loi')>LOI</option>
            <option value="expired" @selected(request('mou_status') === 'expired')>Expired</option>
            <option value="none" @selected(request('mou_status') === 'none')>None</option>
        </select>

        <button type="submit" class="px-5 py-2 rounded-full bg-[#1A1C19] text-white text-xs font-semibold">Filter</button>
    </form>

    <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3]">
        @if($vendors->isEmpty())
            <div class="py-12 text-center text-sm text-[#7A7D78]">
                No vendors registered yet. Click "+ Add Vendor" to onboard your 8–12 pilot vendors.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-[#7A7D78] border-b border-[#E6E7E3]">
                        <tr>
                            <th class="py-3 pr-4">Code</th>
                            <th class="py-3 pr-4">Vendor Name</th>
                            <th class="py-3 pr-4">Tier</th>
                            <th class="py-3 pr-4">MOU / LOI</th>
                            <th class="py-3 pr-4">Qualiplast</th>
                            <th class="py-3 pr-4">Containers</th>
                            <th class="py-3 pr-4">GPS</th>
                            <th class="py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F1EE]">
                        @foreach($vendors as $vendor)
                            <tr>
                                <td class="py-3.5 pr-4 font-mono text-xs font-semibold">{{ $vendor->vendor_code }}</td>
                                <td class="py-3.5 pr-4">
                                    <div class="font-semibold text-[#141513]">{{ $vendor->name }}</div>
                                    <div class="text-xs text-[#7A7D78]">{{ $vendor->address ?: 'No address' }}</div>
                                </td>
                                <td class="py-3.5 pr-4">
                                    <span class="px-2.5 py-1 rounded-full bg-[#F5F5F3] text-xs font-semibold capitalize">{{ $vendor->tier }}</span>
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
                                <td class="py-3.5 pr-4 text-xs">{{ $vendor->qualiplast_tier ?: '—' }}</td>
                                <td class="py-3.5 pr-4 font-semibold">{{ $vendor->containers_count }}</td>
                                <td class="py-3.5 pr-4 font-mono text-xs text-[#5D5F5B]">
                                    {{ number_format($vendor->gps_lat, 5) }}, {{ number_format($vendor->gps_lng, 5) }}
                                </td>
                                <td class="py-3.5">
                                    <a href="{{ route('admin.vendors.edit', $vendor) }}" class="text-xs font-semibold underline">Edit</a>
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
