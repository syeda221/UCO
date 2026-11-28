<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocument extends Model
{
    use HasFactory;

    public const TYPES = [
        'mou',
        'loi',
        'iscc_self_declaration',
        'environmental_permit',
        'business_license',
        'other',
    ];

    public const TYPE_LABELS = [
        'mou' => 'MOU Agreement',
        'loi' => 'Letter of Intent (LOI)',
        'iscc_self_declaration' => 'ISCC Self-Declaration',
        'environmental_permit' => 'Environmental / EPA Permit',
        'business_license' => 'Business / FDA License',
        'other' => 'Other Document',
    ];

    public const STATUSES = ['active', 'expired', 'replaced'];

    protected $fillable = [
        'vendor_id',
        'doc_type',
        'reference_number',
        'issue_date',
        'expiry_date',
        'file_path',
        'file_name',
        'status',
        'replaced_by_document_id',
        'uploaded_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_document_id');
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->doc_type]
            ?? ucfirst(str_replace('_', ' ', $this->doc_type));
    }

    public function isExpired(): bool
    {
        if ($this->status === 'replaced') {
            return false;
        }

        if ($this->status === 'expired') {
            return true;
        }

        return $this->expiry_date !== null
            && $this->expiry_date->copy()->endOfDay()->isPast();
    }

    public function isExpiringSoon(int $days = 15): bool
    {
        if ($this->status !== 'active' || ! $this->expiry_date || $this->isExpired()) {
            return false;
        }

        $diff = now()->startOfDay()->diffInDays($this->expiry_date->copy()->startOfDay(), false);

        return $diff >= 0 && $diff <= $days;
    }
}
