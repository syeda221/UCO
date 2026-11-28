@extends('layouts.admin')

@section('title', 'Overview Dashboard')
@section('heading', 'Field Capture Overview')

@section('header_actions')
    <a href="{{ route('admin.vendors.create') }}"
       class="px-4 py-2 rounded-full bg-[#B6F500] text-[#141513] text-xs font-bold flex items-center gap-2 shadow-sm hover:brightness-95 transition">
        <span class="w-5 h-5 rounded-full bg-[#1A1C19] text-[#B6F500] flex items-center justify-center">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </span>
        <span>Register Vendor</span>
    </a>
@endsection

@section('content')
    @php
        $totalComplianceAlerts = $expiringMous->count() + $expiringIsccs->count() + $flaggedCount;
        $weeklyTotal = collect($dailyChart)->sum('liters');
        $dailyAvg = count($dailyChart) > 0 ? round($weeklyTotal / count($dailyChart), 1) : 0;
        $maxLiters = max(1, collect($dailyChart)->max('liters'));
    @endphp

    <!-- Row 1: Vibrant Stitch KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <!-- Card 1: Signature Electric Volt Hero Card -->
        <div class="bg-[#B6F500] text-[#141513] rounded-[24px] p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-[#141513]/75">Total Liters Collected</div>
                    <div class="font-display font-extrabold text-3xl mt-2 tracking-tight">
                        {{ number_format($totalLiters, 1) }} <span class="text-base font-bold">L</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-[#1A1C19] text-[#B6F500] flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#1A1C19] text-[#B6F500] text-[11px] font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#B6F500]"></span>
                    Effective non-superseded volume
                </span>
                <span class="text-xs font-bold text-[#141513]/80">~{{ number_format($totalLiters * 0.92, 0) }} kg</span>
            </div>
        </div>

        <!-- Card 2: Active Pilot Vendors -->
        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-[#7A7D78]">Active Pilot Vendors</div>
                    <div class="font-display font-extrabold text-3xl mt-2 text-[#141513]">
                        {{ $activeVendorsCount }} <span class="text-base font-semibold text-[#7A7D78]">/ {{ $totalVendorsCount }}</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-[#B6F500] text-[#141513] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-1.5 text-[11px] font-bold">
                <span class="px-2.5 py-1 rounded-full bg-[#F5F5F3] text-[#141513]">H: {{ $tierCounts['hotel'] ?? 0 }}</span>
                <span class="px-2.5 py-1 rounded-full bg-[#F5F5F3] text-[#141513]">R: {{ $tierCounts['restaurant'] ?? 0 }}</span>
                <span class="px-2.5 py-1 rounded-full bg-[#F5F5F3] text-[#141513]">S: {{ $tierCounts['street'] ?? 0 }}</span>
            </div>
        </div>

        <!-- Card 3: Pickups Today & Active QR -->
        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-[#7A7D78]">Pickups Today</div>
                    <div class="font-display font-extrabold text-3xl mt-2 text-[#141513]">{{ $todayPickupsCount }}</div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-[#1A1C19] text-[#B6F500] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zm4 4h3v3h-3z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#B6F500]/25 text-[#141513] text-[11px] font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#1A1C19]"></span>
                    {{ $activeContainersCount }} active QR containers
                </span>
            </div>
        </div>

        <!-- Card 4: Compliance & Field Alerts -->
        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-[#7A7D78]">Compliance &amp; Field Alerts</div>
                    <div class="font-display font-extrabold text-3xl mt-2 {{ $totalComplianceAlerts > 0 ? 'text-amber-700' : 'text-[#141513]' }}">
                        {{ $totalComplianceAlerts }}
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl {{ $totalComplianceAlerts > 0 ? 'bg-amber-100 text-amber-800' : 'bg-[#B6F500] text-[#141513]' }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-xs font-medium text-[#5D5F5B]">
                {{ $expiringMous->count() }} MOUs &bull; {{ $expiringIsccs->count() }} ISCC &bull; {{ $expiringDocsCount }} docs &bull; {{ $flaggedCount }} pickups
            </div>
        </div>
    </div>

    @if(($expiringMous->count() + $expiringIsccs->count()) > 0)
        <div class="mb-6 px-5 py-4 rounded-[22px] bg-[#1A1C19] text-white flex flex-wrap items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3 text-xs">
                <span class="w-9 h-9 rounded-xl bg-amber-400 text-[#141513] flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </span>
                <div>
                    <span class="font-bold text-[#B6F500] uppercase tracking-wider">Expiry Attention:</span>
                    <span class="text-[#E6E7E3] ml-1">
                        {{ $expiringMous->count() }} MOU/LOI and {{ $expiringIsccs->count() }} ISCC Self-Declaration records are expired or expiring within 15 days.
                    </span>
                </div>
            </div>
            <a href="{{ route('admin.vendors.index', ['compliance_flag' => 'expiring_attention']) }}"
               class="px-4 py-2 rounded-full bg-[#B6F500] text-[#141513] text-xs font-bold hover:brightness-95 transition">
                Review Vendors &rarr;
            </a>
        </div>
    @endif

    <!-- Row 2: Charcoal Chart Card + Collector Sync Health -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-[#1A1C19] text-white rounded-[24px] p-6 shadow-md flex flex-col justify-between">
            <div>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-[#B6F500] text-[#141513] flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-[#A3A6A0] font-medium">Last 7 Days Telemetry</div>
                            <h2 class="font-display font-bold text-lg text-white">Daily Collection Volume (Liters)</h2>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-[#272A25] text-[#B6F500] text-xs font-bold">
                        <span class="w-2 h-2 rounded-full bg-[#B6F500] shadow-[0_0_8px_#B6F500]"></span>
                        Live DB
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-3 p-3.5 rounded-2xl bg-white/5 mb-6">
                    <div>
                        <div class="text-[11px] text-[#A3A6A0] uppercase tracking-wider">7-Day Total</div>
                        <div class="font-display font-bold text-base text-white mt-0.5">{{ number_format($weeklyTotal, 1) }} L</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-[#A3A6A0] uppercase tracking-wider">Daily Average</div>
                        <div class="font-display font-bold text-base text-white mt-0.5">{{ number_format($dailyAvg, 1) }} L/day</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-[#A3A6A0] uppercase tracking-wider">Active Containers</div>
                        <div class="font-display font-bold text-base text-[#B6F500] mt-0.5">{{ $activeContainersCount }} QR</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-7 gap-3 items-end h-44 pt-2 border-b border-white/10 pb-3">
                @foreach($dailyChart as $day)
                    @php
                        $pct = $day['liters'] > 0 ? max(10, round(($day['liters'] / $maxLiters) * 100)) : 5;
                        $isPeak = $day['liters'] > 0 && $day['liters'] >= $maxLiters;
                    @endphp
                    <div class="flex flex-col items-center gap-2 h-full justify-end">
                        <div class="text-[11px] font-semibold {{ $isPeak ? 'text-[#B6F500]' : 'text-[#A3A6A0]' }}">
                            {{ $day['liters'] > 0 ? $day['liters'] . 'L' : '0' }}
                        </div>
                        <div class="w-full max-w-[38px] rounded-xl transition-all {{ $day['liters'] > 0 ? 'bg-[#B6F500] shadow-[0_0_16px_rgba(182,245,0,0.3)]' : 'bg-[#2C2F2A]' }}"
                             style="height: {{ $pct }}%"></div>
                        <div class="text-xs font-semibold {{ $isPeak ? 'text-[#B6F500]' : 'text-[#D6D8D2]' }}">{{ $day['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-[#B6F500]/30 text-[#141513] flex items-center justify-center">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </span>
                        <h2 class="font-display font-bold text-base">Collector Sync Health</h2>
                    </div>
                    <a href="{{ route('admin.collectors.index') }}" class="px-3 py-1 rounded-full bg-[#F5F5F3] text-xs font-bold text-[#141513] hover:bg-[#B6F500] transition">
                        Manage &rarr;
                    </a>
                </div>

                @if($collectorsHealth->isEmpty())
                    <div class="py-12 text-center text-xs text-[#7A7D78] bg-[#F5F5F3] rounded-2xl p-6">
                        No collectors created yet. Add a collector to start field capture.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($collectorsHealth as $col)
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-[#F5F5F3] hover:bg-[#EEEEEC] transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-[#1A1C19] text-[#B6F500] font-display font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($col->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-[#141513] truncate">{{ $col->name }}</div>
                                        <div class="text-xs text-[#7A7D78] truncate">
                                            {{ $col->collection_events_count }} pickups &bull;
                                            {{ $col->last_synced_at ? 'Synced ' . $col->last_synced_at->diffForHumans() : 'Never synced' }}
                                        </div>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0 {{ $col->is_active ? 'bg-[#B6F500] text-[#141513]' : 'bg-red-100 text-red-700' }}">
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
    <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] shadow-[0_4px_20px_-2px_rgba(26,28,25,0.04)]">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-[#1A1C19] text-[#B6F500] flex items-center justify-center">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                </span>
                <div>
                    <h2 class="font-display font-bold text-base">Latest Synced Collection Events</h2>
                    <div class="text-xs text-[#7A7D78]">Tamper-evident custody entries synced from field collectors</div>
                </div>
            </div>
            <a href="{{ route('admin.events.index') }}" class="px-4 py-1.5 rounded-full bg-[#F5F5F3] text-xs font-bold text-[#141513] hover:bg-[#B6F500] transition">
                View Full Ledger &rarr;
            </a>
        </div>

        @if($latestEvents->isEmpty())
            <div class="py-10 text-center text-sm text-[#7A7D78] bg-[#F5F5F3] rounded-2xl">
                No collection events synced yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wider text-[#7A7D78] bg-[#F5F5F3]">
                        <tr>
                            <th class="py-3 px-3 rounded-l-xl">Collected At</th>
                            <th class="py-3 px-3">Vendor</th>
                            <th class="py-3 px-3">Collector</th>
                            <th class="py-3 px-3">Container QR</th>
                            <th class="py-3 px-3">Volume</th>
                            <th class="py-3 px-3">Field FFA / Device</th>
                            <th class="py-3 px-3">Flags</th>
                            <th class="py-3 px-3 rounded-r-xl text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F1EE]">
                        @foreach($latestEvents as $evt)
                            <tr class="hover:bg-[#F5F5F3]/60 transition">
                                <td class="py-3.5 px-3 text-xs text-[#5D5F5B] whitespace-nowrap">{{ $evt->collected_at?->format('d M Y, H:i') }}</td>
                                <td class="py-3.5 px-3 font-semibold text-[#141513]">{{ $evt->vendor?->name }}</td>
                                <td class="py-3.5 px-3 text-xs text-[#5D5F5B]">{{ $evt->collector?->name }}</td>
                                <td class="py-3.5 px-3 font-mono text-xs">
                                    <span class="px-2 py-1 rounded-lg bg-[#F5F5F3] font-semibold">{{ $evt->container?->qr_code }}</span>
                                </td>
                                <td class="py-3.5 px-3 font-bold text-[#141513]">{{ number_format($evt->volume_liters, 1) }} L</td>
                                <td class="py-3.5 px-3 text-xs">
                                    {{ $evt->field_ffa_percent !== null ? $evt->field_ffa_percent . '%' : 'N/A' }}
                                    <span class="text-[#7A7D78]">({{ str_replace('_', ' ', $evt->field_device_type) }})</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if($evt->gps_mismatch_flag)
                                            <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold">GPS 200m+</span>
                                        @endif
                                        @if($evt->scan_method === 'manual_fallback')
                                            <span class="px-2.5 py-0.5 rounded-full bg-orange-100 text-orange-800 text-[11px] font-bold">QR Fallback</span>
                                        @endif
                                        @if(!$evt->gps_mismatch_flag && $evt->scan_method === 'qr_scan')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#B6F500]/45 text-[#141513] text-[11px] font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#141513]"></span>
                                                OK
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-right">
                                    <a href="{{ route('admin.events.show', $evt) }}"
                                       class="inline-flex items-center px-3 py-1 rounded-full bg-[#1A1C19] text-white text-xs font-semibold hover:bg-[#B6F500] hover:text-[#141513] transition">
                                        Inspect
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
