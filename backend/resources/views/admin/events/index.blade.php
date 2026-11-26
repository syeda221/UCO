@extends('layouts.admin')

@section('title', 'Collection Events Ledger')
@section('heading', 'Append-Only Collection Events')

@section('content')
    <form method="GET" action="{{ route('admin.events.index') }}" class="bg-white rounded-[22px] p-4 border border-[#E6E7E3] mb-6 grid grid-cols-1 md:grid-cols-6 gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="UUID, vendor, QR..."
               class="md:col-span-2 px-4 py-2 rounded-full bg-[#F5F5F3] text-xs focus:outline-none">

        <select name="vendor_id" class="px-3 py-2 rounded-full bg-[#F5F5F3] text-xs">
            <option value="">All Vendors</option>
            @foreach($vendors as $v)
                <option value="{{ $v->id }}" @selected((string) request('vendor_id') === (string) $v->id)>{{ $v->vendor_code }} — {{ $v->name }}</option>
            @endforeach
        </select>

        <select name="collector_id" class="px-3 py-2 rounded-full bg-[#F5F5F3] text-xs">
            <option value="">All Collectors</option>
            @foreach($collectors as $c)
                <option value="{{ $c->id }}" @selected((string) request('collector_id') === (string) $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>

        <select name="flag" class="px-3 py-2 rounded-full bg-[#F5F5F3] text-xs">
            <option value="">All Flags</option>
            <option value="gps_mismatch" @selected(request('flag') === 'gps_mismatch')>GPS Mismatch (200m+)</option>
            <option value="manual_fallback" @selected(request('flag') === 'manual_fallback')>Manual QR Fallback</option>
            <option value="container_mismatch" @selected(request('flag') === 'container_mismatch')>Container Mismatch</option>
            <option value="mock_gps" @selected(request('flag') === 'mock_gps')>Mock GPS Detected</option>
        </select>

        <button type="submit" class="px-4 py-2 rounded-full bg-[#1A1C19] text-white text-xs font-semibold">Apply Filters</button>
    </form>

    <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3]">
        @if($events->isEmpty())
            <div class="py-12 text-center text-sm text-[#7A7D78]">
                No collection events recorded yet. Events synced from the Flutter Collector App will appear here.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-[#7A7D78] border-b border-[#E6E7E3]">
                        <tr>
                            <th class="py-3 pr-4">ID / UUID</th>
                            <th class="py-3 pr-4">Collected At</th>
                            <th class="py-3 pr-4">Collector</th>
                            <th class="py-3 pr-4">Vendor</th>
                            <th class="py-3 pr-4">Container QR</th>
                            <th class="py-3 pr-4">Volume</th>
                            <th class="py-3 pr-4">FFA / Device</th>
                            <th class="py-3 pr-4">Payment</th>
                            <th class="py-3 pr-4">Flags &amp; Status</th>
                            <th class="py-3">Inspect</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F1EE]">
                        @foreach($events as $evt)
                            <tr class="{{ $evt->supersededBy ? 'opacity-60 bg-[#F9F9F7]' : '' }}">
                                <td class="py-3.5 pr-4">
                                    <div class="font-mono text-xs font-bold">#{{ $evt->id }}</div>
                                    <div class="font-mono text-[10px] text-[#7A7D78]">{{ \Illuminate\Support\Str::limit($evt->client_event_uuid, 12) }}</div>
                                </td>
                                <td class="py-3.5 pr-4 text-xs">{{ $evt->collected_at?->format('d M Y, H:i') }}</td>
                                <td class="py-3.5 pr-4 text-xs">{{ $evt->collector?->name }}</td>
                                <td class="py-3.5 pr-4">
                                    <div class="font-medium text-xs">{{ $evt->vendor?->name }}</div>
                                    <div class="text-[11px] text-[#7A7D78]">{{ $evt->vendor?->vendor_code }}</div>
                                </td>
                                <td class="py-3.5 pr-4 font-mono text-xs">{{ $evt->container?->qr_code }}</td>
                                <td class="py-3.5 pr-4 font-bold">{{ number_format($evt->volume_liters, 1) }} L</td>
                                <td class="py-3.5 pr-4 text-xs">
                                    {{ $evt->field_ffa_percent !== null ? $evt->field_ffa_percent . '%' : 'N/A' }}
                                    <div class="text-[11px] text-[#7A7D78]">{{ str_replace('_', ' ', $evt->field_device_type) }}</div>
                                </td>
                                <td class="py-3.5 pr-4 text-xs">
                                    GHS {{ number_format($evt->payment_amount, 2) }}
                                    <div class="text-[11px] text-[#7A7D78] uppercase">{{ $evt->payment_method }}</div>
                                </td>
                                <td class="py-3.5 pr-4">
                                    <div class="flex flex-wrap gap-1">
                                        @if($evt->supersededBy)
                                            <span class="px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 text-[10px] font-semibold">Superseded</span>
                                        @elseif($evt->supersedes_event_id)
                                            <span class="px-2 py-0.5 rounded-full bg-[#1A1C19] text-[#B6F500] text-[10px] font-semibold">Correction</span>
                                        @endif
                                        @if($evt->gps_mismatch_flag)
                                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-semibold">
                                                GPS {{ number_format($evt->gps_distance_meters ?? 0, 0) }}m
                                            </span>
                                        @endif
                                        @if($evt->scan_method === 'manual_fallback')
                                            <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 text-[10px] font-semibold">QR Fallback</span>
                                        @endif
                                        @if($evt->container_mismatch_flag)
                                            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-semibold">Container Mismatch</span>
                                        @endif
                                        @if($evt->gps_is_mocked)
                                            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-semibold">Mock GPS</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5">
                                    <a href="{{ route('admin.events.show', $evt) }}"
                                       class="px-3 py-1.5 rounded-full bg-[#F5F5F3] text-xs font-semibold hover:bg-[#E7E8E3]">
                                        Inspect ({{ $evt->photos->count() }})
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $events->links() }}</div>
        @endif
    </div>
@endsection
