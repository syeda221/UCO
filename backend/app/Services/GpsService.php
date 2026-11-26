<?php

namespace App\Services;

use App\Models\Vendor;

class GpsService
{
    public const MISMATCH_THRESHOLD_METERS = 200.0;
    private const EARTH_RADIUS_METERS = 6371000.0;

    public function haversineDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * (sin($dLng / 2) ** 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_METERS * $c, 2);
    }

    /**
     * @return array{distance_meters: float, is_mismatch: bool}
     */
    public function evaluateVendorProximity(Vendor $vendor, float $pickupLat, float $pickupLng): array
    {
        $distance = $this->haversineDistanceMeters(
            (float) $vendor->gps_lat,
            (float) $vendor->gps_lng,
            $pickupLat,
            $pickupLng
        );

        return [
            'distance_meters' => $distance,
            'is_mismatch' => $distance > self::MISMATCH_THRESHOLD_METERS,
        ];
    }
}
