<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Container;
use App\Models\Vendor;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ContainerController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    public function index(Request $request): View
    {
        $query = Container::with('assignedVendor:id,vendor_code,name');

        if ($search = trim((string) $request->input('search'))) {
            $query->where('qr_code', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($vendorId = $request->input('vendor_id')) {
            if ($vendorId === 'unassigned') {
                $query->whereNull('assigned_vendor_id');
            } else {
                $query->where('assigned_vendor_id', $vendorId);
            }
        }

        $containers = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get(['id', 'vendor_code', 'name']);

        $stats = [
            'total' => Container::count(),
            'active' => Container::where('status', 'active')->count(),
            'assigned' => Container::where('status', 'active')->whereNotNull('assigned_vendor_id')->count(),
            'damaged' => Container::where('status', 'damaged')->count(),
        ];

        return view('admin.containers.index', compact('containers', 'vendors', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:64', 'unique:containers,qr_code'],
            'qualiplast_tier' => ['required', 'string', 'max:60'],
            'capacity_liters' => ['required', 'numeric', 'gt:0', 'max:5000'],
            'assigned_vendor_id' => ['nullable', 'exists:vendors,id'],
        ]);

        $container = Container::create([
            'qr_code' => strtoupper(trim($validated['qr_code'])),
            'qualiplast_tier' => $validated['qualiplast_tier'],
            'capacity_liters' => $validated['capacity_liters'],
            'assigned_vendor_id' => $validated['assigned_vendor_id'] ?? null,
            'status' => 'active',
        ]);

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'container_created',
            entityType: 'container',
            entityId: $container->id,
            metadata: ['qr_code' => $container->qr_code],
            ipAddress: $request->ip()
        );

        return redirect()->route('admin.containers.index')
            ->with('success', "Container {$container->qr_code} added.");
    }

    public function generateBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:20'],
            'qualiplast_tier' => ['required', 'string', 'max:60'],
            'capacity_liters' => ['required', 'numeric', 'gt:0', 'max:5000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'assigned_vendor_id' => ['nullable', 'exists:vendors,id'],
        ]);

        $prefix = strtoupper(trim($validated['prefix']));
        $startNum = (Container::where('qr_code', 'like', "{$prefix}-%")->count()) + 1;
        $createdCodes = [];

        for ($i = 0; $i < $validated['quantity']; $i++) {
            $num = $startNum + $i;
            do {
                $code = $prefix . '-' . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
                $num++;
            } while (Container::where('qr_code', $code)->exists());

            Container::create([
                'qr_code' => $code,
                'qualiplast_tier' => $validated['qualiplast_tier'],
                'capacity_liters' => $validated['capacity_liters'],
                'assigned_vendor_id' => $validated['assigned_vendor_id'] ?? null,
                'status' => 'active',
            ]);
            $createdCodes[] = $code;
        }

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'containers_batch_generated',
            entityType: 'container',
            metadata: ['count' => count($createdCodes), 'first' => $createdCodes[0] ?? null],
            ipAddress: $request->ip()
        );

        return redirect()->route('admin.containers.index')
            ->with('success', count($createdCodes) . ' QR container codes generated.');
    }

    public function update(Request $request, Container $container): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_vendor_id' => ['nullable', 'exists:vendors,id'],
            'status' => ['required', Rule::in(Container::STATUSES)],
        ]);

        $container->update($validated);

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'container_updated',
            entityType: 'container',
            entityId: $container->id,
            metadata: $validated,
            ipAddress: $request->ip()
        );

        return back()->with('success', "Container {$container->qr_code} updated.");
    }

    public function printQrPdf(Request $request): Response
    {
        $query = Container::with('assignedVendor:id,vendor_code,name')
            ->where('status', 'active')
            ->orderBy('qr_code');

        if ($ids = $request->input('ids')) {
            $idArray = array_filter(explode(',', (string) $ids));
            if ($idArray !== []) {
                $query->whereIn('id', $idArray);
            }
        } elseif ($vendorId = $request->input('vendor_id')) {
            $query->where('assigned_vendor_id', $vendorId);
        }

        $containers = $query->limit(60)->get()->map(function (Container $c) {
            $svg = QrCode::format('svg')->size(140)->margin(1)->generate($c->qr_code);
            return [
                'container' => $c,
                'qr_data_uri' => 'data:image/svg+xml;base64,' . base64_encode($svg),
            ];
        });

        $pdf = Pdf::loadView('admin.containers.qr-pdf', [
            'items' => $containers,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('uco-container-qr-stickers.pdf');
    }
}
