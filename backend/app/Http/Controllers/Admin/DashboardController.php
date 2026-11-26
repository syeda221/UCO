<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollectionEvent;
use App\Models\Container;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Exclude superseded original events so corrected volumes are not double-counted.
        $effectiveEventsQuery = CollectionEvent::query()->whereDoesntHave('supersededBy');

        $totalLiters = (float) (clone $effectiveEventsQuery)->sum('volume_liters');
        $todayPickupsCount = (clone $effectiveEventsQuery)
            ->whereDate('collected_at', Carbon::today())
            ->count();

        $activeVendorsCount = Vendor::where('is_active', true)->count();
        $totalVendorsCount = Vendor::count();
        $tierCounts = Vendor::where('is_active', true)
            ->selectRaw('tier, COUNT(*) as total')
            ->groupBy('tier')
            ->pluck('total', 'tier')
            ->toArray();

        $expiringMous = Vendor::where('is_active', true)
            ->whereNotNull('mou_expiry_date')
            ->whereDate('mou_expiry_date', '<=', now()->addDays(15))
            ->orderBy('mou_expiry_date')
            ->get();

        $flaggedCount = (clone $effectiveEventsQuery)
            ->where(function ($q) {
                $q->where('gps_mismatch_flag', true)
                    ->orWhere('scan_method', 'manual_fallback')
                    ->orWhere('container_mismatch_flag', true)
                    ->orWhere('gps_is_mocked', true);
            })
            ->count();

        $dailyChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayVolume = (float) (clone $effectiveEventsQuery)
                ->whereDate('collected_at', $day)
                ->sum('volume_liters');

            $dailyChart[] = [
                'label' => $day->format('D'),
                'date' => $day->format('d M'),
                'liters' => round($dayVolume, 1),
            ];
        }

        $collectorsHealth = User::where('role', User::ROLE_COLLECTOR)
            ->withCount('collectionEvents')
            ->orderByDesc('last_synced_at')
            ->get();

        $latestEvents = CollectionEvent::with([
            'vendor:id,vendor_code,name,tier',
            'collector:id,name',
            'container:id,qr_code',
            'photos:id,collection_event_id',
        ])
            ->orderByDesc('synced_at')
            ->limit(8)
            ->get();

        $activeContainersCount = Container::where('status', 'active')->count();

        return view('admin.dashboard', compact(
            'totalLiters',
            'todayPickupsCount',
            'activeVendorsCount',
            'totalVendorsCount',
            'tierCounts',
            'expiringMous',
            'flaggedCount',
            'dailyChart',
            'collectorsHealth',
            'latestEvents',
            'activeContainersCount'
        ));
    }
}
