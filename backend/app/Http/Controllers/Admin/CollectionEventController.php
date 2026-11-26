<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollectionEvent;
use App\Models\CollectionPhoto;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollectionEventController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    public function index(Request $request): View
    {
        $query = CollectionEvent::with([
            'vendor:id,vendor_code,name,tier,gps_lat,gps_lng',
            'collector:id,name',
            'container:id,qr_code,qualiplast_tier',
            'photos:id,collection_event_id,photo_type,sha256_hash',
            'supersededBy:id,supersedes_event_id',
        ]);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('client_event_uuid', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$search}%")->orWhere('vendor_code', 'like', "%{$search}%"))
                    ->orWhereHas('container', fn ($cq) => $cq->where('qr_code', 'like', "%{$search}%"));
            });
        }

        if ($vendorId = $request->input('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        if ($collectorId = $request->input('collector_id')) {
            $query->where('collector_id', $collectorId);
        }

        if ($device = $request->input('device')) {
            $query->where('field_device_type', $device);
        }

        if ($flag = $request->input('flag')) {
            match ($flag) {
                'gps_mismatch' => $query->where('gps_mismatch_flag', true),
                'manual_fallback' => $query->where('scan_method', 'manual_fallback'),
                'container_mismatch' => $query->where('container_mismatch_flag', true),
                'mock_gps' => $query->where('gps_is_mocked', true),
                default => null,
            };
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('collected_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('collected_at', '<=', $dateTo);
        }

        $events = $query->orderByDesc('collected_at')->paginate(20)->withQueryString();

        $vendors = Vendor::orderBy('name')->get(['id', 'vendor_code', 'name']);
        $collectors = User::where('role', User::ROLE_COLLECTOR)->orderBy('name')->get(['id', 'name']);

        return view('admin.events.index', compact('events', 'vendors', 'collectors'));
    }

    public function show(CollectionEvent $event): View
    {
        $event->load([
            'vendor',
            'collector',
            'container',
            'photos',
            'supersedesEvent',
            'supersededBy',
            'createdByAdmin',
        ]);

        return view('admin.events.show', compact('event'));
    }

    public function logCorrection(Request $request, CollectionEvent $event): RedirectResponse
    {
        if ($event->supersededBy()->exists()) {
            return back()->withErrors([
                'correction_reason' => 'This record has already been superseded by a newer correction.',
            ]);
        }

        $validated = $request->validate([
            'volume_liters' => ['required', 'numeric', 'gt:0', 'max:50000'],
            'field_ffa_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'field_miu_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_amount' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'payment_method' => ['required', Rule::in(CollectionEvent::PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'correction_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $newEvent = DB::transaction(function () use ($event, $validated, $request) {
            $now = now();

            $replacement = CollectionEvent::create([
                'client_event_uuid' => (string) Str::uuid(),
                'vendor_id' => $event->vendor_id,
                'collector_id' => $event->collector_id,
                'container_id' => $event->container_id,
                'scan_method' => $event->scan_method,
                'fallback_reason' => $event->fallback_reason,
                'container_mismatch_flag' => $event->container_mismatch_flag,
                'collected_at' => $event->collected_at,
                'synced_at' => $now,
                'gps_lat' => $event->gps_lat,
                'gps_lng' => $event->gps_lng,
                'gps_accuracy_meters' => $event->gps_accuracy_meters,
                'gps_is_mocked' => $event->gps_is_mocked,
                'gps_distance_meters' => $event->gps_distance_meters,
                'gps_mismatch_flag' => $event->gps_mismatch_flag,
                'volume_liters' => $validated['volume_liters'],
                'field_ffa_percent' => $validated['field_ffa_percent'] ?? null,
                'field_miu_percent' => $validated['field_miu_percent'] ?? null,
                'field_device_type' => $event->field_device_type,
                'payment_amount' => $validated['payment_amount'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'supersedes_event_id' => $event->id,
                'correction_reason' => $validated['correction_reason'],
                'created_by_admin_id' => $request->user()->id,
                'created_at' => $now,
            ]);

            $this->auditService->log(
                actorUserId: $request->user()->id,
                action: 'collection_corrected',
                entityType: 'collection_event',
                entityId: $replacement->id,
                metadata: [
                    'supersedes_event_id' => $event->id,
                    'reason' => $validated['correction_reason'],
                    'old_volume' => $event->volume_liters,
                    'new_volume' => $replacement->volume_liters,
                ],
                ipAddress: $request->ip()
            );

            return $replacement;
        });

        return redirect()->route('admin.events.show', $newEvent)
            ->with('success', "Append-only correction record #{$newEvent->id} created (original #{$event->id} preserved).");
    }

    public function showPhoto(CollectionPhoto $photo): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($photo->file_path), 404);

        return Storage::disk('local')->response($photo->file_path);
    }
}
