@extends('layouts.admin')

@section('title', 'Inspect Collection Event #' . $event->id)
@section('heading', 'Collection Event #' . $event->id)

@section('header_actions')
    <a href="{{ route('admin.events.index') }}" class="px-4 py-2 rounded-full bg-white border border-[#E6E7E3] text-xs font-semibold">
        &larr; Back to Ledger
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Columns: Custody Data & Photos -->
        <div class="lg:col-span-2 space-y-6">
            @if($event->supersedes_event_id)
                <div class="px-5 py-4 rounded-2xl bg-[#1A1C19] text-white text-sm flex items-center justify-between">
                    <div>
                        <span class="font-semibold text-[#B6F500]">Correction Record:</span>
                        Supersedes original event <a href="{{ route('admin.events.show', $event->supersedes_event_id) }}" class="underline font-mono">#{{ $event->supersedes_event_id }}</a>.
                        Reason: {{ $event->correction_reason }}
                    </div>
                </div>
            @endif

            @if($event->supersededBy)
                <div class="px-5 py-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 text-sm">
                    This original record is preserved for audit history and was superseded by
                    <a href="{{ route('admin.events.show', $event->supersededBy) }}" class="underline font-bold">#{{ $event->supersededBy->id }}</a>.
                </div>
            @endif

            <!-- Core Custody Summary -->
            <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3]">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                    <div>
                        <div class="font-mono text-xs text-[#7A7D78]">UUID: {{ $event->client_event_uuid }}</div>
                        <h2 class="font-display font-bold text-lg mt-0.5">{{ $event->vendor?->name }} ({{ $event->vendor?->vendor_code }})</h2>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-[#F5F5F3] text-xs font-semibold">
                        Immutable • Edits &amp; Deletes Disabled
                    </span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="p-4 rounded-2xl bg-[#F5F5F3]">
                        <div class="text-xs text-[#7A7D78]">Volume</div>
                        <div class="font-display font-bold text-xl mt-1">{{ number_format($event->volume_liters, 2) }} L</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#F5F5F3]">
                        <div class="text-xs text-[#7A7D78]">Field FFA / MIU</div>
                        <div class="font-display font-bold text-xl mt-1">
                            {{ $event->field_ffa_percent !== null ? $event->field_ffa_percent . '%' : 'N/A' }}
                            <span class="text-xs font-normal text-[#7A7D78]">
                                / {{ $event->field_miu_percent !== null ? $event->field_miu_percent . '%' : 'N/A' }}
                            </span>
                        </div>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#F5F5F3]">
                        <div class="text-xs text-[#7A7D78]">Testing Device</div>
                        <div class="font-semibold text-sm mt-1 capitalize">{{ str_replace('_', ' ', $event->field_device_type) }}</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#F5F5F3]">
                        <div class="text-xs text-[#7A7D78]">Payment</div>
                        <div class="font-semibold text-sm mt-1">GHS {{ number_format($event->payment_amount, 2) }}</div>
                        <div class="text-[11px] text-[#7A7D78] uppercase">{{ $event->payment_method }} {{ $event->payment_reference ? '• ' . $event->payment_reference : '' }}</div>
                    </div>
                </div>

                <div class="mt-5 pt-5 border-t border-[#E6E7E3] grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-[#7A7D78]">Collector:</span>
                        <span class="font-semibold">{{ $event->collector?->name }}</span>
                    </div>
                    <div>
                        <span class="text-[#7A7D78]">Container QR:</span>
                        <span class="font-mono font-semibold">{{ $event->container?->qr_code }}</span>
                        ({{ $event->scan_method === 'manual_fallback' ? 'Manual Fallback: ' . $event->fallback_reason : 'QR Scanned' }})
                    </div>
                    <div>
                        <span class="text-[#7A7D78]">Collected At (Device):</span>
                        <span class="font-semibold">{{ $event->collected_at?->format('d M Y, H:i:s') }}</span>
                    </div>
                    <div>
                        <span class="text-[#7A7D78]">Synced At (Server):</span>
                        <span class="font-semibold">{{ $event->synced_at?->format('d M Y, H:i:s') }}</span>
                    </div>
                </div>
            </div>

            <!-- GPS Comparison Card -->
            <div class="bg-[#1A1C19] text-white rounded-[24px] p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display font-bold text-base">GPS Verification (200m Threshold)</h3>
                    @if($event->gps_mismatch_flag)
                        <span class="px-3 py-1 rounded-full bg-amber-500 text-[#141513] text-xs font-bold">
                            Mismatch Flag: {{ number_format($event->gps_distance_meters ?? 0, 1) }} m
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full bg-[#B6F500] text-[#141513] text-xs font-bold">
                            Within Range ({{ number_format($event->gps_distance_meters ?? 0, 1) }} m)
                        </span>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 rounded-2xl bg-[#272A25]">
                        <div class="text-[#A3A6A0]">Registered Vendor GPS</div>
                        <div class="font-mono text-sm mt-1">{{ $event->vendor?->gps_lat }}, {{ $event->vendor?->gps_lng }}</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#272A25]">
                        <div class="text-[#A3A6A0]">Captured Pickup GPS (Accuracy ±{{ $event->gps_accuracy_meters ?? '?' }}m)</div>
                        <div class="font-mono text-sm mt-1">{{ $event->gps_lat }}, {{ $event->gps_lng }}</div>
                        <a href="https://maps.google.com/?q={{ $event->gps_lat }},{{ $event->gps_lng }}" target="_blank"
                           class="inline-block mt-2 text-[#B6F500] underline">Open in Google Maps &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Photo Evidence -->
            <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3]">
                <h3 class="font-display font-bold text-base mb-4">Photo Evidence ({{ $event->photos->count() }})</h3>
                @if($event->photos->isEmpty())
                    <p class="text-xs text-[#7A7D78]">No photos uploaded for this event yet.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($event->photos as $photo)
                            <div class="rounded-2xl overflow-hidden border border-[#E6E7E3] bg-[#F5F5F3]">
                                <img src="{{ route('admin.photos.show', $photo) }}" alt="{{ $photo->photo_type }}"
                                     class="w-full h-56 object-cover">
                                <div class="p-3 text-xs space-y-1">
                                    <div class="font-semibold capitalize">{{ str_replace('_', ' ', $photo->photo_type) }}</div>
                                    <div class="font-mono text-[10px] text-[#7A7D78] break-all">SHA-256: {{ $photo->sha256_hash }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Append-Only Correction Form -->
        <div>
            <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] sticky top-6">
                <h3 class="font-display font-bold text-base">Log Append-Only Correction</h3>
                <p class="text-xs text-[#7A7D78] mt-1 mb-4">
                    Original record #{{ $event->id }} cannot be edited or deleted. Submitting creates a new linked correction record.
                </p>

                @if($event->supersededBy)
                    <div class="p-4 rounded-2xl bg-[#F5F5F3] text-xs text-[#5D5F5B]">
                        Already superseded by #{{ $event->supersededBy->id }}. Open the latest record to log another correction.
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.events.correction', $event) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Corrected Volume (Liters) *</label>
                            <input type="number" step="0.01" name="volume_liters" value="{{ old('volume_liters', $event->volume_liters) }}" required
                                   class="w-full px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Field FFA %</label>
                                <input type="number" step="0.01" name="field_ffa_percent" value="{{ old('field_ffa_percent', $event->field_ffa_percent) }}"
                                       class="w-full px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Field MIU %</label>
                                <input type="number" step="0.01" name="field_miu_percent" value="{{ old('field_miu_percent', $event->field_miu_percent) }}"
                                       class="w-full px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Payment (GHS) *</label>
                                <input type="number" step="0.01" name="payment_amount" value="{{ old('payment_amount', $event->payment_amount) }}" required
                                       class="w-full px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Method *</label>
                                <select name="payment_method" class="w-full px-3 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                                    @foreach(\App\Models\CollectionEvent::PAYMENT_METHODS as $pm)
                                        <option value="{{ $pm }}" @selected($event->payment_method === $pm)>{{ strtoupper($pm) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Payment Reference</label>
                            <input type="text" name="payment_reference" value="{{ old('payment_reference', $event->payment_reference) }}"
                                   class="w-full px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Mandatory Correction Reason *</label>
                            <textarea name="correction_reason" rows="3" required minlength="10"
                                      placeholder="Explain why this correction is required (min 10 chars)..."
                                      class="w-full px-4 py-2 rounded-2xl bg-[#F5F5F3] text-sm">{{ old('correction_reason') }}</textarea>
                        </div>
                        <button type="submit" class="w-full py-2.5 rounded-full bg-[#1A1C19] text-white text-xs font-semibold">
                            Append Correction Record
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
