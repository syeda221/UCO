<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Container extends Model
{
    use HasFactory;

    public const STATUSES = ['active', 'damaged', 'retired'];

    protected $fillable = [
        'qr_code',
        'qualiplast_tier',
        'capacity_liters',
        'assigned_vendor_id',
        'status',
        'last_scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'capacity_liters' => 'float',
            'last_scanned_at' => 'datetime',
        ];
    }

    public function assignedVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'assigned_vendor_id');
    }

    public function collectionEvents(): HasMany
    {
        return $this->hasMany(CollectionEvent::class, 'container_id');
    }
}
