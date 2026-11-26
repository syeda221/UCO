<?php

namespace App\Services;

use App\Models\CollectionEvent;
use App\Models\Container;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollectionSyncService
{
    public function __construct(
        private readonly GpsService $gpsService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Idempotently store an offline-captured collection event.
     *
     * @return array{event: CollectionEvent, status: string}
     */
    public function syncEvent(User $collector, array $data, ?string $ipAddress = null): array
    {
        $existing = CollectionEvent::with('photos')
            ->where('client_event_uuid', $data['client_event_uuid'])
            ->first();

        if ($existing) {
            return [
                'event' => $existing,
                'status' => 'already_synced',
            ];
        }

        $vendor = Vendor::where('id', $data['vendor_id'])
            ->where('is_active', true)
            ->first();

        if (! $vendor) {
            throw ValidationException::withMessages([
                'vendor_id' => ['Selected vendor does not exist or is inactive.'],
            ]);
        }

        $container = null;
        if (! empty($data['container_id'])) {
            $container = Container::find($data['container_id']);
        } elseif (! empty($data['container_qr_code'])) {
            $container = Container::where('qr_code', trim($data['container_qr_code']))->first();
        }

        if (! $container) {
            throw ValidationException::withMessages([
                'container_id' => ['Container QR record could not be found.'],
            ]);
        }

        $scanMethod = $data['scan_method'] ?? 'qr_scan';
        $fallbackReason = isset($data['fallback_reason']) ? trim((string) $data['fallback_reason']) : null;

        if ($scanMethod === 'manual_fallback' && empty($fallbackReason)) {
            throw ValidationException::withMessages([
                'fallback_reason' => ['A reason is required when using manual container fallback.'],
            ]);
        }

        $containerMismatch = $container->assigned_vendor_id !== null
            && (int) $container->assigned_vendor_id !== (int) $vendor->id;

        $gpsEval = $this->gpsService->evaluateVendorProximity(
            $vendor,
            (float) $data['gps_lat'],
            (float) $data['gps_lng']
        );

        return DB::transaction(function () use (
            $collector,
            $vendor,
            $container,
            $data,
            $scanMethod,
            $fallbackReason,
            $containerMismatch,
            $gpsEval,
            $ipAddress
        ) {
            $now = now();
            $collectedAt = Carbon::parse($data['collected_at'])->utc();

            $event = CollectionEvent::create([
                'client_event_uuid' => $data['client_event_uuid'],
                'vendor_id' => $vendor->id,
                'collector_id' => $collector->id,
                'container_id' => $container->id,
                'scan_method' => $scanMethod,
                'fallback_reason' => $fallbackReason,
                'container_mismatch_flag' => $containerMismatch,
                'collected_at' => $collectedAt,
                'synced_at' => $now,
                'gps_lat' => $data['gps_lat'],
                'gps_lng' => $data['gps_lng'],
                'gps_accuracy_meters' => $data['gps_accuracy_meters'] ?? null,
                'gps_is_mocked' => (bool) ($data['gps_is_mocked'] ?? false),
                'gps_distance_meters' => $gpsEval['distance_meters'],
                'gps_mismatch_flag' => $gpsEval['is_mismatch'],
                'volume_liters' => $data['volume_liters'],
                'field_ffa_percent' => $data['field_ffa_percent'] ?? null,
                'field_miu_percent' => $data['field_miu_percent'] ?? null,
                'field_device_type' => $data['field_device_type'],
                'payment_amount' => $data['payment_amount'] ?? 0,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_reference' => $data['payment_reference'] ?? null,
                'created_at' => $now,
            ]);

            $container->update(['last_scanned_at' => $collectedAt]);
            $collector->update(['last_synced_at' => $now]);

            $this->auditService->log(
                actorUserId: $collector->id,
                action: 'collection_synced',
                entityType: 'collection_event',
                entityId: $event->id,
                metadata: [
                    'client_event_uuid' => $event->client_event_uuid,
                    'vendor_code' => $vendor->vendor_code,
                    'container_qr' => $container->qr_code,
                    'scan_method' => $scanMethod,
                    'gps_distance_meters' => $gpsEval['distance_meters'],
                    'gps_mismatch_flag' => $gpsEval['is_mismatch'],
                    'container_mismatch_flag' => $containerMismatch,
                ],
                ipAddress: $ipAddress
            );

            return [
                'event' => $event->load('photos'),
                'status' => 'created',
            ];
        });
    }
}
