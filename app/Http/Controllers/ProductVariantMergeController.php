<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductVariantMergeController extends Controller
{
    public function create($id)
    {
        $product = DB::table('products')->where('id', $id)->first();

        abort_if(! $product, 404);

        $variants = DB::table('product_variants')
            ->where('product_id', $id)
            ->where('status', '!=', 'merged')
            ->whereNull('merged_into_variant_id')
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'unit', 'current_stock', 'status'])
            ->map(fn ($v) => [
                'id'     => (string) $v->id,
                'sku'    => $v->sku,
                'name'   => $v->name,
                'unit'   => $v->unit,
                'stock'  => (int) $v->current_stock,
                'status' => $v->status,
            ])
            ->values();

        if ($variants->count() < 2) {
            return redirect()
                ->route('products.show', $id)
                ->with('error', 'Penggabungan membutuhkan minimal dua varian yang belum digabung.');
        }

        return view('pages.products.variants.merge', compact('product', 'variants'));
    }

    public function store(Request $request, $id)
    {
        $product = DB::table('products')->where('id', $id)->first();

        abort_if(! $product, 404);

        $validated = $request->validate([
            'target_variant_id' => ['required', 'integer'],
            'source_ids'        => ['required', 'array', 'min:1'],
            'source_ids.*'      => ['integer', 'distinct'],
            'reason'            => ['nullable', 'string', 'max:500'],
        ], [
            'target_variant_id.required' => 'Pilih varian tujuan yang akan dipertahankan.',
            'source_ids.required'        => 'Pilih minimal satu varian yang akan digabungkan.',
            'source_ids.min'             => 'Pilih minimal satu varian yang akan digabungkan.',
            'reason.max'                 => 'Alasan maksimal 500 karakter.',
        ]);

        $targetId  = (int) $validated['target_variant_id'];
        $sourceIds = array_map('intval', $validated['source_ids']);

        if (in_array($targetId, $sourceIds, true)) {
            throw ValidationException::withMessages([
                'source_ids' => 'Varian tujuan tidak boleh ikut dipilih sebagai varian yang digabungkan.',
            ]);
        }

        $userId = $this->actor()->id;
        $now = now();

        $result = DB::transaction(function () use ($id, $product, $validated, $targetId, $sourceIds, $userId, $now) {
            $allIds = array_merge([$targetId], $sourceIds);

            // Kunci semua varian terkait agar stok tidak berubah selama proses.
            $rows = DB::table('product_variants')
                ->where('product_id', $id)
                ->whereIn('id', $allIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($rows->count() !== count($allIds)) {
                throw ValidationException::withMessages([
                    'source_ids' => 'Ada varian yang tidak ditemukan pada produk ini.',
                ]);
            }

            foreach ($rows as $row) {
                if ($row->status === 'merged' || $row->merged_into_variant_id !== null) {
                    throw ValidationException::withMessages([
                        'source_ids' => 'Varian "' . $row->name . '" sudah pernah digabung.',
                    ]);
                }
            }

            $target = $rows[$targetId];

            if ($target->status !== 'active') {
                throw ValidationException::withMessages([
                    'target_variant_id' => 'Varian tujuan harus berstatus Aktif.',
                ]);
            }

            // PO yang belum selesai masih memakai varian sumber.
            $openPo = DB::table('purchase_order_items as i')
                ->join('purchase_orders as po', 'po.id', '=', 'i.purchase_order_id')
                ->whereIn('i.variant_id', $sourceIds)
                ->whereIn('po.status', ['draft', 'ordered', 'partial'])
                ->pluck('i.variant_id')
                ->unique();

            if ($openPo->isNotEmpty()) {
                $names = $rows->only($openPo->all())->pluck('name')->implode(', ');

                throw ValidationException::withMessages([
                    'source_ids' => 'Varian ' . $names . ' masih ada di Purchase Order yang belum selesai. Selesaikan atau batalkan PO tersebut dulu.',
                ]);
            }

            // Dokumen opname yang belum selesai menyimpan stok sistem lama.
            $openOpname = DB::table('stock_opname_items as i')
                ->join('stock_opnames as o', 'o.id', '=', 'i.opname_id')
                ->whereIn('i.variant_id', $allIds)
                ->where('o.status', '!=', 'completed')
                ->pluck('i.variant_id')
                ->unique();

            if ($openOpname->isNotEmpty()) {
                $names = $rows->only($openOpname->all())->pluck('name')->implode(', ');

                throw ValidationException::withMessages([
                    'source_ids' => 'Varian ' . $names . ' masih ada di dokumen stock opname yang belum selesai. Selesaikan atau hapus draft opname tersebut dulu.',
                ]);
            }

            // Rencana pemindahan stok per varian sumber.
            $sources = [];
            $plan = [];

            foreach ($sourceIds as $sid) {
                $s = $rows[$sid];
                $qty = (int) $s->current_stock;

                $sources[] = [
                    'variant_id'        => (int) $s->id,
                    'name'              => $s->name,
                    'sku'               => $s->sku,
                    'stock_transferred' => $qty,
                ];

                $plan[] = ['id' => (int) $s->id, 'qty' => $qty];
            }

            $logId = DB::table('product_merge_logs')->insertGetId([
                'target_variant_id'    => $targetId,
                'target_name_snapshot' => $product->name . ' - ' . $target->name,
                'sources'              => json_encode($sources, JSON_UNESCAPED_UNICODE),
                'reason'               => $validated['reason'] ?? null,
                'merged_by'            => $userId,
                'merged_at'            => $now,
            ]);

            $targetStock = (int) $target->current_stock;
            $totalMoved = 0;

            foreach ($plan as $item) {
                if ($item['qty'] > 0) {
                    // Keluar dari varian sumber.
                    DB::table('stock_movements')->insert([
                        'variant_id'     => $item['id'],
                        'movement_type'  => 'merge_adjustment',
                        'qty_change'     => -$item['qty'],
                        'stock_before'   => $item['qty'],
                        'stock_after'    => 0,
                        'reference_type' => 'product_merge',
                        'reference_id'   => $logId,
                        'created_by'     => $userId,
                        'created_at'     => $now,
                    ]);

                    // Masuk ke varian tujuan.
                    DB::table('stock_movements')->insert([
                        'variant_id'     => $targetId,
                        'movement_type'  => 'merge_adjustment',
                        'qty_change'     => $item['qty'],
                        'stock_before'   => $targetStock,
                        'stock_after'    => $targetStock + $item['qty'],
                        'reference_type' => 'product_merge',
                        'reference_id'   => $logId,
                        'created_by'     => $userId,
                        'created_at'     => $now,
                    ]);

                    $targetStock += $item['qty'];
                    $totalMoved += $item['qty'];
                }

                // Varian sumber disembunyikan, bukan dihapus (riwayatnya tetap utuh).
                DB::table('product_variants')
                    ->where('id', $item['id'])
                    ->update([
                        'current_stock'          => 0,
                        'status'                 => 'merged',
                        'merged_into_variant_id' => $targetId,
                        'updated_at'             => $now,
                    ]);
            }

            DB::table('product_variants')
                ->where('id', $targetId)
                ->update(['current_stock' => $targetStock, 'updated_at' => $now]);

            DB::table('accurate_items')
                ->whereIn('mapped_variant_id', $sourceIds)
                ->update(['mapped_variant_id' => $targetId, 'updated_at' => $now]);
            
            return [
                'target' => $target->name,
                'count'  => count($plan),
                'moved'  => $totalMoved,
            ];
        });

        return redirect()
            ->route('products.show', $id)
            ->with(
                'success',
                $result['count'] . ' varian berhasil digabung ke "' . $result['target'] . '". '
                . number_format($result['moved'], 0, ',', '.') . ' unit stok dipindahkan.'
            );
    }
}