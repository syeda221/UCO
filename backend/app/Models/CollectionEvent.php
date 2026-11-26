<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class CollectionEvent extends Model
{
    use HasFactory;

    public const SCAN_METHODS = ['qr_scan', 'manual_fallback'];
    public const DEVICE_TYPES = ['colorimetric_kit', 'testo', 'ebro'];
    public const PAYMENT_METHODS = ['cash', 'momo', 'bank', 'credit'];

    public $timestamps = false;

    protected $fillable = [
        'client_event_uuid',
        'vendor_id',
        'collector_id',
        'container_id',
        'scan_method',
        'fallback_reason',
        'container_mismatch_flag',
        'collected_at',
        'synced_at',
        'gps_lat',
        'gps_lng',
        'gps_accuracy_meters',
        'gps_is_mocked',
        'gps_distance_meters',
        'gps_mismatch_flag',
        'volume_liters',
        'field_ffa_percent',
        'field_miu_percent',
        'field_device_type',
        'payment_amount',
        'payment_method',
        'payment_reference',
        'supersedes_event_id',
        'correction_reason',
        'created_by_admin_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'container_mismatch_flag' => 'boolean',
            'collected_at' => 'datetime',
            'synced_at' => 'datetime',
            'gps_lat' => 'float',
            'gps_lng' => 'float',
            'gps_accuracy_meters' => 'float',
            'gps_is_mocked' => 'boolean',
            'gps_distance_meters' => 'float',
            'gps_mismatch_flag' => 'boolean',
            'volume_liters' => 'float',
            'field_ffa_percent' => 'float',
            'field_miu_percent' => 'float',
            'payment_amount' => 'float',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('CollectionEvent records are append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new LogicException('CollectionEvent records are append-only and cannot be deleted.');
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class, 'container_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CollectionPhoto::class, 'collection_event_id');
    }

    public function supersedesEvent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_event_id');
    }

    public function supersededBy(): HasOne
    {
        return $this->hasOne(self::class, 'supersedes_event_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }
}
