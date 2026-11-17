<?php

namespace App\Models;

use App\Support\SupabaseStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'image_path',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * URL publik foto dari path di bucket Supabase.
     *   \App\Models\ProductVariant::imageUrl($variant->image_path)
     */
    public static function imageUrl(?string $path): ?string
    {
        return SupabaseStorage::url($path);
    }

    public function getImageUrlAttribute(): ?string
    {
        return self::imageUrl($this->image_path);
    }

    // Varian ini sudah digabung ke varian lain.
    public function isMerged(): bool
    {
        return $this->status === 'merged'
            || $this->merged_into_variant_id !== null;
    }

    /**
     * Apakah varian ini sudah punya stok atau riwayat transaksi.
     * Jika ya, varian tidak boleh dihapus.
     */
    public function hasTransactionHistory(): bool
    {
        return $this->isMerged()
            || (int) $this->current_stock > 0
            || DB::table('stock_movements')->where('variant_id', $this->id)->exists()
            || DB::table('sale_items')->where('variant_id', $this->id)->exists()
            || DB::table('purchase_order_items')->where('variant_id', $this->id)->exists()
            || DB::table('stock_opname_items')->where('variant_id', $this->id)->exists()
            || DB::table('product_merge_logs')->where('target_variant_id', $this->id)->exists()
            || DB::table('product_variants')->where('merged_into_variant_id', $this->id)->exists()
            || DB::table('accurate_items')->where('mapped_variant_id', $this->id)->exists();
    }

    public function accurateItems(): HasMany
    {
        return $this->hasMany(AccurateItem::class, 'mapped_variant_id');
    }
}