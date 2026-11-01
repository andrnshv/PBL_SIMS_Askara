<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'description',
        'status',
        'created_by',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Produk punya stok atau riwayat transaksi? Jika ya, produk tidak boleh dihapus.
    public function hasTransactionHistory(): bool
    {
        $variantIds = $this->variants()->pluck('id');

        if ($variantIds->isEmpty()) {
            return false;
        }

        return DB::table('stock_movements')->whereIn('variant_id', $variantIds)->exists()
            || DB::table('sale_items')->whereIn('variant_id', $variantIds)->exists()
            || DB::table('purchase_order_items')->whereIn('variant_id', $variantIds)->exists()
            || DB::table('stock_opname_items')->whereIn('variant_id', $variantIds)->exists()
            || DB::table('product_merge_logs')->whereIn('target_variant_id', $variantIds)->exists()
            || DB::table('product_variants')->whereIn('merged_into_variant_id', $variantIds)->exists()
            || $this->variants()->where('current_stock', '>', 0)->exists();
    }
}