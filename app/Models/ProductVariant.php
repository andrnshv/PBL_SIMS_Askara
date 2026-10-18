<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'unit',
        'selling_price',
        'current_stock',
        'reorder_point',
        'status',
        'merged_into_variant_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}
