<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CollectionPhoto extends Model
{
    use HasFactory;

    public const PHOTO_TYPES = ['pickup_evidence', 'ffa_reading'];

    public $timestamps = false;

    protected $fillable = [
        'collection_event_id',
        'photo_type',
        'file_path',
        'sha256_hash',
        'file_size_bytes',
        'captured_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'captured_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('CollectionPhoto records are append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new LogicException('CollectionPhoto records are append-only and cannot be deleted.');
        });
    }

    public function collectionEvent(): BelongsTo
    {
        return $this->belongsTo(CollectionEvent::class, 'collection_event_id');
    }
}
