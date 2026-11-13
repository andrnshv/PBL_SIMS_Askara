<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRenameLog extends Model
{
    // Tabel ini hanya punya changed_at, tidak punya created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'entity_type', 'product_id', 'variant_id',
        'old_name', 'new_name', 'reason',
        'changed_by', 'changed_at',
    ];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }
}