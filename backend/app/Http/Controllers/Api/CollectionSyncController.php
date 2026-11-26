<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectionEvent;
use App\Models\CollectionPhoto;
use App\Services\AuditService;
use App\Services\CollectionSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CollectionSyncController extends Controller
{
    public function __construct(
        private readonly CollectionSyncService $syncService,
        private readonly AuditService $auditService,
    ) {}

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_event_uuid' => ['required', 'uuid'],
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'container_id' => ['nullable', 'integer', 'required_without:container_qr_code'],
            'container_qr_code' => ['nullable', 'string', 'max:64', 'required_without:container_id'],
            'scan_method' => ['required', Rule::in(CollectionEvent::SCAN_METHODS)],
            'fallback_reason' => ['nullable', 'string', 'max:255', 'required_if:scan_method,manual_fallback'],
            'collected_at' => ['required', 'date'],
            'gps_lat' => ['required', 'numeric', 'between:-90,90'],
            'gps_lng' => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'gps_is_mocked' => ['nullable', 'boolean'],
            'volume_liters' => ['required', 'numeric', 'gt:0', 'max:50000'],
            'field_ffa_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'field_miu_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'field_device_type' => ['required', Rule::in(CollectionEvent::DEVICE_TYPES)],
            'payment_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'payment_method' => ['nullable', Rule::in(CollectionEvent::PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->syncService->syncEvent($request->user(), $validated, $request->ip());
        $event = $result['event'];

        return response()->json([
            'status' => $result['status'],
            'event' => [
                'id' => $event->id,
                'client_event_uuid' => $event->client_event_uuid,
                'synced_at' => $event->synced_at?->toIso8601String(),
                'gps_distance_meters' => $event->gps_distance_meters,
                'gps_mismatch_flag' => $event->gps_mismatch_flag,
                'container_mismatch_flag' => $event->container_mismatch_flag,
                'photos_count' => $event->photos->count(),
            ],
        ], $result['status'] === 'created' ? 201 : 200);
    }

    public function uploadPhoto(Request $request, string $clientEventUuid): JsonResponse
    {
        $event = CollectionEvent::where('client_event_uuid', $clientEventUuid)->firstOrFail();

        if ((int) $event->collector_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'You cannot upload photos for another collector\'s event.',
            ], 403);
        }

        $validated = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'photo_type' => ['required', Rule::in(CollectionPhoto::PHOTO_TYPES)],
            'sha256_hash' => ['nullable', 'string', 'size:64'],
            'captured_at' => ['nullable', 'date'],
        ]);

        $uploadedFile = $request->file('photo');
        $computedHash = hash_file('sha256', $uploadedFile->getRealPath());

        if (! empty($validated['sha256_hash']) && ! hash_equals(strtolower($validated['sha256_hash']), $computedHash)) {
            return response()->json([
                'message' => 'Photo integrity check failed (SHA-256 mismatch).',
            ], 422);
        }

        // Idempotent check so retries of the same photo do not duplicate records.
        $existingPhoto = CollectionPhoto::where('collection_event_id', $event->id)
            ->where('photo_type', $validated['photo_type'])
            ->where('sha256_hash', $computedHash)
            ->first();

        if ($existingPhoto) {
            return response()->json([
                'status' => 'already_uploaded',
                'photo' => [
                    'id' => $existingPhoto->id,
                    'photo_type' => $existingPhoto->photo_type,
                    'sha256_hash' => $existingPhoto->sha256_hash,
                ],
            ], 200);
        }

        $directory = 'collections/' . now()->format('Y/m');
        $storedPath = Storage::disk('local')->putFile($directory, $uploadedFile);

        $photo = CollectionPhoto::create([
            'collection_event_id' => $event->id,
            'photo_type' => $validated['photo_type'],
            'file_path' => $storedPath,
            'sha256_hash' => $computedHash,
            'file_size_bytes' => $uploadedFile->getSize(),
            'captured_at' => ! empty($validated['captured_at'])
                ? Carbon::parse($validated['captured_at'])->utc()
                : $event->collected_at,
            'created_at' => now(),
        ]);

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'collection_photo_uploaded',
            entityType: 'collection_photo',
            entityId: $photo->id,
            metadata: [
                'client_event_uuid' => $event->client_event_uuid,
                'photo_type' => $photo->photo_type,
                'sha256_hash' => $photo->sha256_hash,
            ],
            ipAddress: $request->ip()
        );

        return response()->json([
            'status' => 'uploaded',
            'photo' => [
                'id' => $photo->id,
                'photo_type' => $photo->photo_type,
                'sha256_hash' => $photo->sha256_hash,
            ],
        ], 201);
    }

    public function status(Request $request, string $clientEventUuid): JsonResponse
    {
        $event = CollectionEvent::with('photos')
            ->where('client_event_uuid', $clientEventUuid)
            ->where('collector_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'client_event_uuid' => $event->client_event_uuid,
            'id' => $event->id,
            'synced_at' => $event->synced_at?->toIso8601String(),
            'photos_count' => $event->photos->count(),
            'photo_hashes' => $event->photos->pluck('sha256_hash'),
        ]);
    }

    public function myHistory(Request $request): JsonResponse
    {
        $events = CollectionEvent::with([
            'vendor:id,vendor_code,name,tier',
            'container:id,qr_code,qualiplast_tier',
            'photos:id,collection_event_id,photo_type,sha256_hash',
        ])
            ->where('collector_id', $request->user()->id)
            ->orderByDesc('collected_at')
            ->limit(50)
            ->get()
            ->map(function (CollectionEvent $event) {
                return [
                    'id' => $event->id,
                    'client_event_uuid' => $event->client_event_uuid,
                    'vendor' => $event->vendor,
                    'container' => $event->container,
                    'scan_method' => $event->scan_method,
                    'fallback_reason' => $event->fallback_reason,
                    'collected_at' => $event->collected_at?->toIso8601String(),
                    'synced_at' => $event->synced_at?->toIso8601String(),
                    'gps_lat' => $event->gps_lat,
                    'gps_lng' => $event->gps_lng,
                    'gps_accuracy_meters' => $event->gps_accuracy_meters,
                    'volume_liters' => $event->volume_liters,
                    'field_ffa_percent' => $event->field_ffa_percent,
                    'field_miu_percent' => $event->field_miu_percent,
                    'field_device_type' => $event->field_device_type,
                    'payment_amount' => $event->payment_amount,
                    'payment_method' => $event->payment_method,
                    'payment_reference' => $event->payment_reference,
                    'photos_count' => $event->photos->count(),
                ];
            });

        return response()->json([
            'events' => $events,
        ]);
    }
}
