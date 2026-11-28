<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Container;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $collector = $request->user();
        $collector->update(['last_synced_at' => now()]);

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'vendor_code',
                'name',
                'tier',
                'address',
                'area_zone',
                'gps_lat',
                'gps_lng',
                'mou_status',
                'mou_expiry_date',
                'qualiplast_tier',
                'expected_monthly_volume',
                'expected_volume_unit',
                'produces_10_plus_tonnes_monthly',
                'has_animal_origin',
                'iscc_declaration_status',
            ]);

        $containers = Container::query()
            ->where('status', 'active')
            ->orderBy('qr_code')
            ->get([
                'id',
                'qr_code',
                'qualiplast_tier',
                'capacity_liters',
                'assigned_vendor_id',
                'status',
            ]);

        return response()->json([
            'server_time' => now()->utc()->toIso8601String(),
            'vendors' => $vendors,
            'containers' => $containers,
        ]);
    }
}
