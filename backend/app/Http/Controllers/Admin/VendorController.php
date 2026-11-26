<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    public function index(Request $request): View
    {
        $query = Vendor::query()->withCount('containers');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('vendor_code', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($tier = $request->input('tier')) {
            $query->where('tier', $tier);
        }

        if ($mouStatus = $request->input('mou_status')) {
            $query->where('mou_status', $mouStatus);
        }

        $vendors = $query->orderBy('vendor_code')->paginate(15)->withQueryString();

        return view('admin.vendors.index', compact('vendors'));
    }

    public function create(): View
    {
        $nextNumber = (Vendor::max('id') ?? 0) + 1;
        $suggestedCode = 'VND-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

        return view('admin.vendors.form', [
            'vendor' => new Vendor([
                'vendor_code' => $suggestedCode,
                'mou_status' => 'loi',
                'is_active' => true,
            ]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateVendor($request);
        $validated['contact_chain_json'] = $this->extractContactChain($request);
        $validated['onboarding_docs_json'] = $this->storeUploadedDocs($request, []);
        $validated['is_active'] = $request->boolean('is_active', true);

        $vendor = Vendor::create($validated);

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'vendor_created',
            entityType: 'vendor',
            entityId: $vendor->id,
            metadata: ['vendor_code' => $vendor->vendor_code, 'tier' => $vendor->tier],
            ipAddress: $request->ip()
        );

        return redirect()->route('admin.vendors.index')
            ->with('success', "Vendor {$vendor->vendor_code} registered successfully.");
    }

    public function edit(Vendor $vendor): View
    {
        $vendor->load('containers');

        return view('admin.vendors.form', [
            'vendor' => $vendor,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $this->validateVendor($request, $vendor);
        $validated['contact_chain_json'] = $this->extractContactChain($request);
        $validated['onboarding_docs_json'] = $this->storeUploadedDocs(
            $request,
            $vendor->onboarding_docs_json ?? []
        );
        $validated['is_active'] = $request->boolean('is_active', true);

        $vendor->update($validated);

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'vendor_updated',
            entityType: 'vendor',
            entityId: $vendor->id,
            metadata: ['vendor_code' => $vendor->vendor_code],
            ipAddress: $request->ip()
        );

        return redirect()->route('admin.vendors.index')
            ->with('success', "Vendor {$vendor->vendor_code} updated.");
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
            'gps_lat' => ['required', 'numeric', 'between:-90,90'],
            'gps_lng' => ['required', 'numeric', 'between:-180,180'],
            'mou_status' => ['required', Rule::in(Vendor::MOU_STATUSES)],
            'mou_expiry_date' => ['nullable', 'date'],
            'qualiplast_tier' => ['nullable', 'string', 'max:60'],
            'onboarding_docs.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
    }

    private function extractContactChain(Request $request): array
    {
        $names = $request->input('contact_names', []);
        $roles = $request->input('contact_roles', []);
        $phones = $request->input('contact_phones', []);

        $chain = [];
        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $chain[] = [
                'name' => $name,
                'role' => trim((string) ($roles[$i] ?? '')),
                'phone' => trim((string) ($phones[$i] ?? '')),
            ];
        }

        return $chain;
    }

    private function storeUploadedDocs(Request $request, array $existing): array
    {
        if (! $request->hasFile('onboarding_docs')) {
            return $existing;
        }

        foreach ($request->file('onboarding_docs') as $file) {
            if (! $file) {
                continue;
            }
            $path = Storage::disk('local')->putFile('vendor_docs', $file);
            $existing[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'uploaded_at' => now()->toIso8601String(),
            ];
        }

        return $existing;
    }
}
