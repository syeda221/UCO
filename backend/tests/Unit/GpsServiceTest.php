<?php

namespace Tests\Unit;

use App\Models\Vendor;
use App\Services\GpsService;
use Tests\TestCase;

class GpsServiceTest extends TestCase
{
    public function test_it_flags_distances_over_200_meters_and_accepts_close_pickups(): void
    {
        $service = new GpsService();

        $vendor = new Vendor([
            'gps_lat' => 5.6037000,
            'gps_lng' => -0.1870000,
        ]);

        // ~35 meters away
        $near = $service->evaluateVendorProximity($vendor, 5.6040000, -0.1870000);
        $this->assertFalse($near['is_mismatch']);
        $this->assertLessThan(200.0, $near['distance_meters']);

        // ~350 meters away
        $far = $service->evaluateVendorProximity($vendor, 5.6068000, -0.1870000);
        $this->assertTrue($far['is_mismatch']);
        $this->assertGreaterThan(200.0, $far['distance_meters']);
    }
}
