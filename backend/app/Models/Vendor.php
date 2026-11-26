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

    protected $fillable = [
        'vendor_code',
        'name',
        'legal_name',
        'tier',
        'address',
        'gps_lat',
        'gps_lng',
        'mou_status',
        'mou_expiry_date',
        'qualiplast_tier',
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

    public function isMouExpiringSoon(int $days = 15): bool
    {
        if (! $this->mou_expiry_date) {
            return false;
        }

        return $this->mou_expiry_date->isFuture()
            && now()->diffInDays($this->mou_expiry_date, false) <= $days;
    }

    public function isMouExpired(): bool
    {
        if ($this->mou_status === 'expired') {
            return true;
        }

        return $this->mou_expiry_date !== null && $this->mou_expiry_date->isPast();
    }
}
