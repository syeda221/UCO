@extends('layouts.admin')

@section('title', 'Overview Dashboard')
@section('heading', 'Field Capture Overview')

@section('header_actions')
    <a href="{{ route('admin.vendors.create') }}"
       class="px-4 py-2 rounded-full bg-[#1A1C19] text-white text-xs font-semibold flex items-center gap-2 hover:opacity-90">
        <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
        + Register Vendor
    </a>
@endsection

@section('content')
    <!-- Row 1: Real KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-[22px] p-6 border border-[#E6E7E3]">
            <div class="flex items-center justify-between text-xs font-medium text-[#7A7D78]">
                <span>Total Liters Collected</span>
                <span class="w-2.5 h-2.5 rounded-full bg-[#B6F500]"></span>
            </div>
            <div class="font-display font-bold text-3xl mt-3 text-[#141513]">
                {{ number_format($totalLiters, 1) }} <span class="text-base font-medium text-[#7A7D78]">L</span>
            </div>
            <div class="text-xs text-[#7A7D78] mt-2">Effective non-superseded volume</div>
        </div>

        <div class="bg-white rounded-[22px] p-6 border border-[#E6E7E3]">
            <div class="flex items-center justify-between text-xs font-medium text-[#7A7D78]">
                <span>Active Pilot Vendors</span>
                <span class="w-2.5 h-2.5 rounded-full bg-[#B6F500]"></span>
            </div>
            <div class="font-display font-bold text-3xl mt-3 text-[#141513]">
                {{ $activeVendorsCount }} <span class="text-base font-medium text-[#7A7D78]">/ {{ $totalVendorsCount }}</span>
            </div>
            <div class="text-xs text-[#7A7D78] mt-2">
                H: {{ $tierCounts['hotel'] ?? 0 }} &bull; R: {{ $tierCounts['restaurant'] ?? 0 }} &bull; S: {{ $tierCounts['street'] ?? 0 }}
            </div>
        </div>

        <div class="bg-white rounded-[22px] p-6 border border-[#E6E7E3]">
            <div class="flex items-center justify-between text-xs font-medium text-[#7A7D78]">
                <span>Pickups Today</span>
                <span class="w-2.5 h-2.5 rounded-full bg-[#B6F500]"></span>
            </div>
            <div class="font-display font-bold text-3xl mt-3 text-[#141513]">{{ $todayPickupsCount }}</div>
            <div class="text-xs text-[#7A7D78] mt-2">{{ $activeContainersCount }} active QR containers</div>
        </div>

        <div class="bg-white rounded-[22px] p-6 border border-[#E6E7E3]">
            @php
                $totalComplianceAlerts = $expiringMous->count() + $expiringIsccs->count() + $flaggedCount;
            @endphp
            <div class="flex items-center justify-between text-xs font-medium text-[#7A7D78]">
                <span>Compliance &amp; Field Alerts</span>
                <span class="w-2.5 h-2.5 rounded-full {{ $totalComplianceAlerts > 0 ? 'bg-amber-500' : 'bg-[#B6F500]' }}"></span>
            </div>
            <div class="font-display font-bold text-3xl mt-3 text-[#141513]">
                {{ $totalComplianceAlerts }}
            </div>
            <div class="text-xs text-[#7A7D78] mt-2">
                {{ $expiringMous->count() }} MOUs &bull; {{ $expiringIsccs->count() }} ISCC &bull; {{ $expiringDocsCount }} docs &bull; {{ $flaggedCount }} pickups
            </div>
        </div>
    </div>

    @if(($expiringMous->count() + $expiringIsccs->count()) > 0)
        <div class="mb-6 px-5 py-3.5 rounded-[20px] bg-amber-50 border border-amber-200 flex flex-wrap items-center justify-between gap-3 text-xs text-amber-900">
            <div>
                <span class="font-bold">Expiry Attention:</span>
                {{ $expiringMous->count() }} MOU/LOI and {{ $expiringIsccs->count() }} ISCC Self-Declaration records are expired or expiring within 15 days.
            </div>
            <a href="{{ route('admin.vendors.index', ['compliance_flag' => 'expiring_attention']) }}"
               class="px-3.5 py-1.5 rounded-full bg-[#1A1C19] text-white font-semibold hover:opacity-90">
                Review Vendors &rarr;
            </a>
        </div>
    @endif

    <!-- Row 2: 7-Day Volume Chart + Collector Sync Health -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-[#1A1C19] text-white rounded-[24px] p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="text-xs text-[#A3A6A0]">Last 7 Days</div>
                    <h2 class="font-display font-bold text-lg">Daily Collection Volume (Liters)</h2>
                </div>
                <span class="px-3 py-1 rounded-full bg-[#272A25] text-[#B6F500] text-xs font-semibold">Live DB</span>
            </div>

            @php
                $maxLiters = max(1, collect($dailyChart)->max('liters'));
            @endphp
            <div class="grid grid-cols-7 gap-3 items-end h-44 pt-4">
                @foreach($dailyChart as $day)
                    @php
                        $pct = $day['liters'] > 0 ? max(8, round(($day['liters'] / $maxLiters) * 100)) : 4;
                    @endphp
                    <div class="flex flex-col items-center gap-2 h-full justify-end">
                        <div class="text-[11px] text-[#A3A6A0]">{{ $day['liters'] > 0 ? $day['liters'] . 'L' : '0' }}</div>
                        <div class="w-full max-w-[36px] rounded-xl transition-all {{ $day['liters'] > 0 ? 'bg-[#B6F500]' : 'bg-[#2C2F2A]' }}"
                             style="height: {{ $pct }}%"></div>
                        <div class="text-xs font-medium text-[#D6D8D2]">{{ $day['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-display font-bold text-base">Collector Sync Health</h2>
                    <a href="{{ route('admin.collectors.index') }}" class="text-xs font-semibold text-[#141513] underline">Manage</a>
                </div>

                @if($collectorsHealth->isEmpty())
                    <div class="py-10 text-center text-xs text-[#7A7D78]">
                        No collectors created yet. Add a collector to start field capture.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($collectorsHealth as $col)
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-[#F5F5F3]">
                                <div>
                                    <div class="text-sm font-semibold text-[#141513]">{{ $col->name }}</div>
                                    <div class="text-xs text-[#7A7D78]">
                                        {{ $col->collection_events_count }} pickups &bull;
                                        {{ $col->last_synced_at ? 'Synced ' . $col->last_synced_at->diffForHumans() : 'Never synced' }}
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $col->is_active ? 'bg-[#B6F500] text-[#141513]' : 'bg-red-100 text-red-700' }}">
                                    {{ $col->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Row 3: Latest Synced Collection Events -->
    <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3]">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display font-bold text-base">Latest Synced Collection Events</h2>
            <a href="{{ route('admin.events.index') }}" class="text-xs font-semibold text-[#141513] underline">View Full Ledger</a>
        </div>

        @if($latestEvents->isEmpty())
            <div class="py-10 text-center text-sm text-[#7A7D78]">
                No collection events synced yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-[#7A7D78] border-b border-[#E6E7E3]">
                        <tr>
                            <th class="py-3 pr-4">Collected At</th>
                            <th class="py-3 pr-4">Vendor</th>
                            <th class="py-3 pr-4">Collector</th>
                            <th class="py-3 pr-4">Container QR</th>
                            <th class="py-3 pr-4">Volume</th>
                            <th class="py-3 pr-4">Field FFA / Device</th>
                            <th class="py-3 pr-4">Flags</th>
                            <th class="py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F1EE]">
                        @foreach($latestEvents as $evt)
                            <tr>
                                <td class="py-3.5 pr-4 text-xs text-[#5D5F5B]">{{ $evt->collected_at?->format('d M Y, H:i') }}</td>
                                <td class="py-3.5 pr-4 font-medium">{{ $evt->vendor?->name }}</td>
                                <td class="py-3.5 pr-4 text-xs">{{ $evt->collector?->name }}</td>
                                <td class="py-3.5 pr-4 font-mono text-xs">{{ $evt->container?->qr_code }}</td>
                                <td class="py-3.5 pr-4 font-semibold">{{ number_format($evt->volume_liters, 1) }} L</td>
                                <td class="py-3.5 pr-4 text-xs">
                                    {{ $evt->field_ffa_percent !== null ? $evt->field_ffa_percent . '%' : 'N/A' }}
                                    <span class="text-[#7A7D78]">({{ str_replace('_', ' ', $evt->field_device_type) }})</span>
                                </td>
                                <td class="py-3.5 pr-4">
                                    <div class="flex flex-wrap gap-1">
                                        @if($evt->gps_mismatch_flag)
                                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-semibold">GPS 200m+</span>
                                        @endif
                                        @if($evt->scan_method === 'manual_fallback')
                                            <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 text-[11px] font-semibold">QR Fallback</span>
                                        @endif
                                        @if(!$evt->gps_mismatch_flag && $evt->scan_method === 'qr_scan')
                                            <span class="px-2 py-0.5 rounded-full bg-[#B6F500]/40 text-[#141513] text-[11px] font-semibold">OK</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5">
                                    <a href="{{ route('admin.events.show', $evt) }}" class="text-xs font-semibold underline">Inspect</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
