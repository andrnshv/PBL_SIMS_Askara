<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccurateItem extends Model
{
    public const STATUS_UNMAPPED = 'unmapped';   // belum dipetakan
    public const STATUS_MAPPED   = 'mapped';     // sudah dipetakan, menunggu verifikasi
    public const STATUS_VERIFIED = 'verified';   // diverifikasi = data final
    public const STATUS_REJECTED = 'rejected';   // ditolak (tidak dimigrasikan)

    public const STATUSES = [
        self::STATUS_UNMAPPED => 'Belum dipetakan',
        self::STATUS_MAPPED   => 'Menunggu verifikasi',
        self::STATUS_VERIFIED => 'Terverifikasi',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    protected $fillable = [
        'batch_id',
        'row_number',
        'accurate_code',
        'accurate_name',
        'accurate_unit',
        'accurate_stock',
        'accurate_price',
        'accurate_category',
        'mapping_status',
        'mapped_variant_id',
        'mapped_by',
        'mapped_at',
        'verified_by',
        'verified_at',
        'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'accurate_stock' => 'integer',
            'mapped_at'      => 'datetime',
            'verified_at'    => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(AccurateImportBatch::class, 'batch_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'mapped_variant_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->mapping_status] ?? (string) $this->mapping_status;
    }

    /**
     * Hanya item terverifikasi yang boleh dianggap data final.
     * Kode lain yang memakai data Accurate wajib lewat scope ini.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('mapping_status', self::STATUS_VERIFIED);
    }
}