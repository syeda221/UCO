<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    public function index(Request $request): View
    {
        $query = Vendor::query()->withCount(['containers', 'documents']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('vendor_code', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('area_zone', 'like', "%{$search}%");
            });
        }

        if ($tier = $request->input('tier')) {
            $query->where('tier', $tier);
        }

        if ($areaZone = trim((string) $request->input('area_zone'))) {
            $query->where('area_zone', $areaZone);
        }

        if ($mouStatus = $request->input('mou_status')) {
            $query->where('mou_status', $mouStatus);
        }

        if ($isccStatus = $request->input('iscc_status')) {
            $query->where('iscc_declaration_status', $isccStatus);
        }

        if ($flag = $request->input('compliance_flag')) {
            if ($flag === 'animal_origin') {
                $query->where('has_animal_origin', true);
            } elseif ($flag === '10_plus_tonnes') {
                $query->where('produces_10_plus_tonnes_monthly', true);
            } elseif ($flag === 'expiring_attention') {
                $cutoff = now()->addDays(15)->toDateString();
                $query->where(function ($q) use ($cutoff) {
                    $q->where('mou_status', 'expired')
                        ->orWhere('iscc_declaration_status', 'expired')
                        ->orWhere(function ($q2) use ($cutoff) {
                            $q2->whereNotNull('mou_expiry_date')
                                ->whereDate('mou_expiry_date', '<=', $cutoff);
                        })
                        ->orWhere(function ($q3) use ($cutoff) {
                            $q3->whereNotNull('iscc_expiry_date')
                                ->whereDate('iscc_expiry_date', '<=', $cutoff);
                        });
                });
            }
        }

        $vendors = $query->orderBy('vendor_code')->paginate(15)->withQueryString();
        $areaZones = Vendor::query()
            ->whereNotNull('area_zone')
            ->where('area_zone', '!=', '')
            ->distinct()
            ->orderBy('area_zone')
            ->pluck('area_zone');

        return view('admin.vendors.index', compact('vendors', 'areaZones'));
    }

    public function create(): View
    {
        $nextNumber = (Vendor::max('id') ?? 0) + 1;
        $suggestedCode = 'VND-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

        return view('admin.vendors.form', [
            'vendor' => new Vendor([
                'vendor_code' => $suggestedCode,
                'mou_status' => 'loi',
                'expected_volume_unit' => 'liters',
                'iscc_declaration_status' => 'none',
                'produces_10_plus_tonnes_monthly' => false,
                'has_animal_origin' => false,
                'is_active' => true,
            ]),
            'isEdit' => false,
            'profileStats' => null,
            'recentEvents' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateVendor($request);
        $attributes = $this->prepareVendorAttributes($request, $validated);

        $vendor = DB::transaction(function () use ($request, $validated, $attributes) {
            if ($request->hasFile('iscc_doc')) {
                $isccFile = $request->file('iscc_doc');
                $isccPath = Storage::disk('local')->putFile('vendor_docs/iscc', $isccFile);
                $attributes['iscc_doc_path'] = $isccPath;
                $attributes['iscc_doc_name'] = $isccFile->getClientOriginalName();
            }

            $vendor = Vendor::create($attributes);

            if (! empty($attributes['iscc_doc_path'])) {
                $this->recordIsccHistoryDocument(
                    vendor: $vendor,
                    filePath: $attributes['iscc_doc_path'],
                    fileName: $attributes['iscc_doc_name'],
                    actorId: $request->user()?->id
                );
            }

            $this->storeUploadedDocuments($request, $vendor, $validated);

            return $vendor;
        });

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'vendor_created',
            entityType: 'vendor',
            entityId: $vendor->id,
            metadata: [
                'vendor_code' => $vendor->vendor_code,
                'tier' => $vendor->tier,
                'area_zone' => $vendor->area_zone,
                'has_animal_origin' => $vendor->has_animal_origin,
                'produces_10_plus_tonnes_monthly' => $vendor->produces_10_plus_tonnes_monthly,
                'iscc_declaration_status' => $vendor->iscc_declaration_status,
            ],
            ipAddress: $request->ip()
        );

        return redirect()->route('admin.vendors.index')
            ->with('success', "Vendor {$vendor->vendor_code} registered successfully.");
    }

    public function edit(Vendor $vendor): View
    {
        $vendor->load(['containers', 'documents.uploadedBy', 'documents.replacedBy']);

        $effectiveEventsQuery = $vendor->collectionEvents()->whereDoesntHave('supersededBy');
        $totalLiters = (float) (clone $effectiveEventsQuery)->sum('volume_liters');
        $monthLiters = (float) (clone $effectiveEventsQuery)
            ->whereBetween('collected_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('volume_liters');
        $pickupsCount = (clone $effectiveEventsQuery)->count();
        $avgFfa = (clone $effectiveEventsQuery)->whereNotNull('field_ffa_percent')->avg('field_ffa_percent');
        $avgMiu = (clone $effectiveEventsQuery)->whereNotNull('field_miu_percent')->avg('field_miu_percent');

        $expectedLiters = $vendor->estimatedExpectedMonthlyLiters();
        $exceedsExpected = $expectedLiters !== null && $monthLiters > ($expectedLiters * 1.1);

        $recentEvents = (clone $effectiveEventsQuery)
            ->with(['collector:id,name', 'container:id,qr_code'])
            ->orderByDesc('collected_at')
            ->limit(5)
            ->get();

        return view('admin.vendors.form', [
            'vendor' => $vendor,
            'isEdit' => true,
            'profileStats' => [
                'total_liters' => $totalLiters,
                'month_liters' => $monthLiters,
                'expected_liters' => $expectedLiters,
                'exceeds_expected' => $exceedsExpected,
                'pickups_count' => $pickupsCount,
                'avg_ffa' => $avgFfa !== null ? round((float) $avgFfa, 2) : null,
                'avg_miu' => $avgMiu !== null ? round((float) $avgMiu, 2) : null,
                'active_docs_count' => $vendor->documents->where('status', 'active')->count(),
            ],
            'recentEvents' => $recentEvents,
        ]);
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $this->validateVendor($request, $vendor);
        $attributes = $this->prepareVendorAttributes($request, $validated);

        DB::transaction(function () use ($request, $vendor, $validated, $attributes) {
            if ($request->hasFile('iscc_doc')) {
                $isccFile = $request->file('iscc_doc');
                $isccPath = Storage::disk('local')->putFile('vendor_docs/iscc', $isccFile);
                $attributes['iscc_doc_path'] = $isccPath;
                $attributes['iscc_doc_name'] = $isccFile->getClientOriginalName();
            }

            $vendor->update($attributes);

            if (! empty($attributes['iscc_doc_path'])) {
                $this->recordIsccHistoryDocument(
                    vendor: $vendor,
                    filePath: $attributes['iscc_doc_path'],
                    fileName: $attributes['iscc_doc_name'],
                    actorId: $request->user()?->id
                );
            }

            $replaceDocId = $validated['replace_document_id'] ?? null;
            $this->storeUploadedDocuments($request, $vendor, $validated, $replaceDocId);
        });

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'vendor_updated',
            entityType: 'vendor',
            entityId: $vendor->id,
            metadata: [
                'vendor_code' => $vendor->vendor_code,
                'area_zone' => $vendor->area_zone,
                'has_animal_origin' => $vendor->has_animal_origin,
                'produces_10_plus_tonnes_monthly' => $vendor->produces_10_plus_tonnes_monthly,
                'iscc_declaration_status' => $vendor->iscc_declaration_status,
            ],
            ipAddress: $request->ip()
        );

        return redirect()->route('admin.vendors.index')
            ->with('success', "Vendor {$vendor->vendor_code} updated.");
    }

    public function uploadDocument(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'doc_type' => ['required', Rule::in(VendorDocument::TYPES)],
            'doc_reference_number' => ['nullable', 'string', 'max:80'],
            'doc_issue_date' => ['nullable', 'date'],
            'doc_expiry_date' => [
                'nullable',
                'date',
                Rule::when($request->filled('doc_issue_date'), ['after_or_equal:doc_issue_date']),
            ],
            'doc_notes' => ['nullable', 'string', 'max:500'],
            'replace_document_id' => [
                'nullable',
                Rule::exists('vendor_documents', 'id')->where('vendor_id', $vendor->id),
            ],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $doc = DB::transaction(function () use ($request, $vendor, $validated) {
            return $this->createVendorDocumentRecord(
                vendor: $vendor,
                file: $request->file('document_file'),
                docType: $validated['doc_type'],
                referenceNumber: $validated['doc_reference_number'] ?? null,
                issueDate: $validated['doc_issue_date'] ?? null,
                expiryDate: $validated['doc_expiry_date'] ?? null,
                notes: $validated['doc_notes'] ?? null,
                actorId: $request->user()?->id,
                replaceDocId: isset($validated['replace_document_id']) ? (int) $validated['replace_document_id'] : null
            );
        });

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'vendor_document_uploaded',
            entityType: 'vendor_document',
            entityId: $doc->id,
            metadata: [
                'vendor_id' => $vendor->id,
                'doc_type' => $doc->doc_type,
                'reference_number' => $doc->reference_number,
                'replaced_doc_id' => $validated['replace_document_id'] ?? null,
            ],
            ipAddress: $request->ip()
        );

        return redirect()->route('admin.vendors.edit', $vendor)
            ->with('success', 'Vendor document uploaded and added to compliance history.');
    }

    public function downloadDocument(Vendor $vendor, VendorDocument $document): StreamedResponse
    {
        abort_unless($document->vendor_id === $vendor->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    public function markDocumentExpired(Request $request, Vendor $vendor, VendorDocument $document): RedirectResponse
    {
        abort_unless($document->vendor_id === $vendor->id, 404);

        $document->update(['status' => 'expired']);

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'vendor_document_marked_expired',
            entityType: 'vendor_document',
            entityId: $document->id,
            metadata: ['vendor_id' => $vendor->id, 'doc_type' => $document->doc_type],
            ipAddress: $request->ip()
        );

        return redirect()->route('admin.vendors.edit', $vendor)
            ->with('success', "{$document->typeLabel()} marked as expired.");
    }

    public function downloadIsccDoc(Vendor $vendor): StreamedResponse
    {
        abort_unless(
            $vendor->iscc_doc_path && Storage::disk('local')->exists($vendor->iscc_doc_path),
            404
        );

        return Storage::disk('local')->download(
            $vendor->iscc_doc_path,
            $vendor->iscc_doc_name ?: "{$vendor->vendor_code}_ISCC_Declaration.pdf"
        );
    }

    public function downloadDoc(Vendor $vendor, int $index): StreamedResponse
    {
        $docs = $vendor->onboarding_docs_json ?? [];
        abort_unless(isset($docs[$index]), 404);

        $doc = $docs[$index];
        abort_unless(Storage::disk('local')->exists($doc['path']), 404);

        return Storage::disk('local')->download($doc['path'], $doc['name']);
    }

    private function validateVendor(Request $request, ?Vendor $vendor = null): array
    {
        return $request->validate([
            'vendor_code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('vendors', 'vendor_code')->ignore($vendor?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:180'],
            'tier' => ['required', Rule::in(Vendor::TIERS)],
            'address' => ['nullable', 'string', 'max:255'],
            'area_zone' => ['nullable', 'string', 'max:120'],
            'gps_lat' => ['required', 'numeric', 'between:-90,90'],
            'gps_lng' => ['required', 'numeric', 'between:-180,180'],
            'mou_status' => ['required', Rule::in(Vendor::MOU_STATUSES)],
            'mou_expiry_date' => ['nullable', 'date'],
            'qualiplast_tier' => ['nullable', 'string', 'max:60'],
            'expected_monthly_volume' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'expected_volume_unit' => ['nullable', Rule::in(Vendor::VOLUME_UNITS)],
            'produces_10_plus_tonnes_monthly' => ['nullable', 'boolean'],
            'has_animal_origin' => ['nullable', 'boolean'],
            'iscc_declaration_status' => ['nullable', Rule::in(Vendor::ISCC_STATUSES)],
            'iscc_signed_date' => ['nullable', 'date'],
            'iscc_expiry_date' => [
                'nullable',
                'date',
                Rule::when($request->filled('iscc_signed_date'), ['after_or_equal:iscc_signed_date']),
            ],
            'iscc_doc' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'doc_type' => ['nullable', Rule::in(VendorDocument::TYPES)],
            'doc_reference_number' => ['nullable', 'string', 'max:80'],
            'doc_issue_date' => ['nullable', 'date'],
            'doc_expiry_date' => [
                'nullable',
                'date',
                Rule::when($request->filled('doc_issue_date'), ['after_or_equal:doc_issue_date']),
            ],
            'replace_document_id' => [
                'nullable',
                Rule::when(
                    $vendor !== null,
                    [Rule::exists('vendor_documents', 'id')->where('vendor_id', $vendor?->id)]
                ),
            ],
            'onboarding_docs.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'contact_emails.*' => ['nullable', 'email', 'max:150'],
        ]);
    }

    private function prepareVendorAttributes(Request $request, array $validated): array
    {
        $attributes = collect($validated)->except([
            'iscc_doc',
            'onboarding_docs',
            'doc_type',
            'doc_reference_number',
            'doc_issue_date',
            'doc_expiry_date',
            'replace_document_id',
            'contact_emails',
        ])->toArray();

        $attributes['area_zone'] = isset($attributes['area_zone']) ? trim((string) $attributes['area_zone']) ?: null : null;
        $attributes['expected_volume_unit'] = $attributes['expected_volume_unit'] ?? 'liters';
        $attributes['iscc_declaration_status'] = $attributes['iscc_declaration_status'] ?? 'none';
        $attributes['produces_10_plus_tonnes_monthly'] = $request->boolean('produces_10_plus_tonnes_monthly');
        $attributes['has_animal_origin'] = $request->boolean('has_animal_origin');
        $attributes['is_active'] = $request->boolean('is_active', true);
        $attributes['contact_chain_json'] = $this->extractContactChain($request);

        return $attributes;
    }

    private function extractContactChain(Request $request): array
    {
        $names = $request->input('contact_names', []);
        $roles = $request->input('contact_roles', []);
        $phones = $request->input('contact_phones', []);
        $emails = $request->input('contact_emails', []);

        $chain = [];
        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $email = trim((string) ($emails[$i] ?? ''));
            $chain[] = [
                'name' => $name,
                'role' => trim((string) ($roles[$i] ?? '')),
                'phone' => trim((string) ($phones[$i] ?? '')),
                'email' => $email !== '' ? $email : null,
            ];
        }

        return $chain;
    }

    private function storeUploadedDocuments(
        Request $request,
        Vendor $vendor,
        array $validated,
        ?int $replaceDocId = null
    ): void {
        if (! $request->hasFile('onboarding_docs')) {
            return;
        }

        $docType = $validated['doc_type'] ?? 'mou';
        $first = true;

        foreach ($request->file('onboarding_docs') as $file) {
            if (! $file) {
                continue;
            }

            $this->createVendorDocumentRecord(
                vendor: $vendor,
                file: $file,
                docType: $docType,
                referenceNumber: $validated['doc_reference_number'] ?? null,
                issueDate: $validated['doc_issue_date'] ?? null,
                expiryDate: $validated['doc_expiry_date'] ?? null,
                notes: null,
                actorId: $request->user()?->id,
                replaceDocId: $first ? $replaceDocId : null
            );

            $first = false;
        }
    }

    private function recordIsccHistoryDocument(
        Vendor $vendor,
        string $filePath,
        string $fileName,
        ?int $actorId
    ): void {
        $previousActiveIds = VendorDocument::query()
            ->where('vendor_id', $vendor->id)
            ->where('doc_type', 'iscc_self_declaration')
            ->where('status', 'active')
            ->pluck('id');

        $newDoc = VendorDocument::create([
            'vendor_id' => $vendor->id,
            'doc_type' => 'iscc_self_declaration',
            'reference_number' => $vendor->vendor_code . '-ISCC',
            'issue_date' => $vendor->iscc_signed_date,
            'expiry_date' => $vendor->iscc_expiry_date,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'status' => $vendor->iscc_declaration_status === 'expired' ? 'expired' : 'active',
            'uploaded_by_user_id' => $actorId,
        ]);

        if ($previousActiveIds->isNotEmpty()) {
            VendorDocument::whereIn('id', $previousActiveIds)->update([
                'status' => 'replaced',
                'replaced_by_document_id' => $newDoc->id,
            ]);
        }
    }

    private function createVendorDocumentRecord(
        Vendor $vendor,
        UploadedFile $file,
        string $docType,
        ?string $referenceNumber,
        ?string $issueDate,
        ?string $expiryDate,
        ?string $notes,
        ?int $actorId,
        ?int $replaceDocId = null
    ): VendorDocument {
        $path = Storage::disk('local')->putFile('vendor_docs', $file);

        $doc = VendorDocument::create([
            'vendor_id' => $vendor->id,
            'doc_type' => $docType,
            'reference_number' => $referenceNumber ? trim($referenceNumber) : null,
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'status' => 'active',
            'uploaded_by_user_id' => $actorId,
            'notes' => $notes ? trim($notes) : null,
        ]);

        if ($replaceDocId !== null) {
            VendorDocument::where('vendor_id', $vendor->id)
                ->where('id', $replaceDocId)
                ->update([
                    'status' => 'replaced',
                    'replaced_by_document_id' => $doc->id,
                ]);
        }

        return $doc;
    }
}
