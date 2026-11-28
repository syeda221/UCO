<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    public const TIERS = ['hotel', 'restaurant', 'street'];
    public const MOU_STATUSES = ['none', 'loi', 'mou_active', 'expired'];
    public const VOLUME_UNITS = ['liters', 'kg'];
    public const ISCC_STATUSES = ['none', 'pending', 'active', 'expired'];

    protected $fillable = [
        'vendor_code',
        'name',
        'legal_name',
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
        'iscc_signed_date',
        'iscc_expiry_date',
        'iscc_doc_path',
        'iscc_doc_name',
        'contact_chain_json',
        'onboarding_docs_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'gps_lat' => 'float',
            'gps_lng' => 'float',
            'mou_expiry_date' => 'date',
            'expected_monthly_volume' => 'float',
            'produces_10_plus_tonnes_monthly' => 'boolean',
            'has_animal_origin' => 'boolean',
            'iscc_signed_date' => 'date',
            'iscc_expiry_date' => 'date',
            'contact_chain_json' => 'array',
            'onboarding_docs_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function containers(): HasMany
    {
        return $this->hasMany(Container::class, 'assigned_vendor_id');
    }

    public function collectionEvents(): HasMany
    {
        return $this->hasMany(CollectionEvent::class, 'vendor_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class, 'vendor_id')
            ->orderByDesc('created_at');
    }

    public function scopeWithExpiringMou($query, int $days = 15)
    {
        $cutoff = now()->addDays($days)->toDateString();

        return $query->where(function ($q) use ($cutoff) {
            $q->where('mou_status', 'expired')
                ->orWhere(function ($q2) use ($cutoff) {
                    $q2->whereNotNull('mou_expiry_date')
                        ->whereDate('mou_expiry_date', '<=', $cutoff);
                });
        });
    }

    public function scopeWithExpiringIscc($query, int $days = 15)
    {
        $cutoff = now()->addDays($days)->toDateString();

        return $query->where(function ($q) use ($cutoff) {
            $q->where('iscc_declaration_status', 'expired')
                ->orWhere(function ($q2) use ($cutoff) {
                    $q2->whereNotNull('iscc_expiry_date')
                        ->whereDate('iscc_expiry_date', '<=', $cutoff);
                });
        });
    }

    public function scopeNeedingComplianceAttention($query, int $days = 15)
    {
        $cutoff = now()->addDays($days)->toDateString();

        return $query->where(function ($q) use ($cutoff) {
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

    public function isMouExpiringSoon(int $days = 15): bool
    {
        if (! $this->mou_expiry_date || $this->isMouExpired()) {
            return false;
        }

        $diff = now()->startOfDay()->diffInDays($this->mou_expiry_date->copy()->startOfDay(), false);

        return $diff >= 0 && $diff <= $days;
    }

    public function isMouExpired(): bool
    {
        if ($this->mou_status === 'expired') {
            return true;
        }

        return $this->mou_expiry_date !== null
            && $this->mou_expiry_date->copy()->endOfDay()->isPast();
    }

    public function isIsccExpiringSoon(int $days = 15): bool
    {
        if (! $this->iscc_expiry_date || $this->isIsccExpired()) {
            return false;
        }

        $diff = now()->startOfDay()->diffInDays($this->iscc_expiry_date->copy()->startOfDay(), false);

        return $diff >= 0 && $diff <= $days;
    }

    public function isIsccExpired(): bool
    {
        if ($this->iscc_declaration_status === 'expired') {
            return true;
        }

        return $this->iscc_expiry_date !== null
            && $this->iscc_expiry_date->copy()->endOfDay()->isPast();
    }

    /**
     * Converts expected monthly volume into equivalent liters (~0.92 kg/L UCO density)
     * so field volume_liters can be compared uniformly on profile summaries.
     */
    public function estimatedExpectedMonthlyLiters(): ?float
    {
        if ($this->expected_monthly_volume === null || $this->expected_monthly_volume <= 0) {
            return null;
        }

        if ($this->expected_volume_unit === 'kg') {
            return round($this->expected_monthly_volume / 0.92, 2);
        }

        return (float) $this->expected_monthly_volume;
    }
}
