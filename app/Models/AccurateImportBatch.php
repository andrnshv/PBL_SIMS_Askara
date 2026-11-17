<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccurateImportBatch extends Model
{
    protected $fillable = [
        'file_name',
        'file_hash',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'error_report',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['error_report' => 'array'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AccurateItem::class, 'batch_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}