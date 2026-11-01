<?php

namespace App\View\Components\header;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;

class NotificationDropdown extends Component
{
    /** Apakah lonceng ditampilkan untuk pengguna ini. */
    public bool $visible = false;

    /** Jumlah varian yang perlu reorder (menipis + habis). */
    public int $total = 0;

    /** Jumlah varian yang stoknya habis. */
    public int $outCount = 0;

    /** Maksimal 5 varian paling mendesak, untuk isi dropdown. */
    public Collection $items;

    public function __construct()
    {
        $this->items = collect();

        $user = auth()->user();

        // Hanya admin, staff pembelian, dan staff gudang yang melihat notifikasi stok.
        if (! $user || ! $user->hasRole('admin', 'staff_pembelian', 'staff_gudang')) {
            return;
        }

        $this->visible = true;

        // Aturan: stok <= reorder point, hanya varian & produk aktif, bukan varian yang sudah digabung.
        $query = DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.status', 'active')
            ->where('p.status', 'active')
            ->whereNull('v.merged_into_variant_id')
            ->whereRaw('v.current_stock <= COALESCE(v.reorder_point, 0)');

        $this->total = (clone $query)->count();
        $this->outCount = (clone $query)->where('v.current_stock', '<=', 0)->count();

        $this->items = $query
            // Stok habis tampil paling atas.
            ->orderByRaw('CASE WHEN v.current_stock <= 0 THEN 0 ELSE 1 END')
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->limit(5)
            ->get([
                'v.sku',
                'p.name as product',
                'v.name as variant',
                'v.unit',
                'v.current_stock',
                'v.reorder_point',
            ])
            ->map(fn ($v) => [
                'sku' => $v->sku,
                'name' => $v->product,
                'variant' => $v->variant,
                'unit' => $v->unit,
                'stock' => (int) $v->current_stock,
                'min' => (int) ($v->reorder_point ?? 0),
                'out' => (int) $v->current_stock <= 0,
            ]);
    }

    public function render(): View|Closure|string
    {
        return view('components.header.notification-dropdown');
    }
}