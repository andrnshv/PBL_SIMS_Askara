<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockInController extends Controller
{
    /**
     * Menampilkan form stok masuk.
     */
    public function create()
    {
        $variants = DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.status', 'active')
            ->where('p.status', 'active')
            ->whereNull('v.merged_into_variant_id')
            ->select([
                'v.id',
                'v.sku',
                'v.name as variant',
                'v.unit',
                'v.current_stock as stock',
                'p.name',
            ])
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->get()
            ->map(fn ($v) => [
                'id' => (int) $v->id,
                'name' => $v->name,
                'variant' => $v->variant,
                'sku' => $v->sku,
                'unit' => $v->unit,
                'stock' => (int) $v->stock,
            ])
            ->values();

        // Pulihkan jumlah yang diisi apabila validasi gagal.
        $initial = collect(old('items', []))
            ->filter(fn ($item) => isset($item['variant_id']))
            ->mapWithKeys(fn ($item) => [
                (string) $item['variant_id'] => $item['qty'] ?? '',
            ])
            ->all();

        return view('pages.stock.incoming', [
            'variants' => $variants,
            'initial' => (object) $initial,
            'receiptNumber' => 'IN-' . now()->format('Ymd-His'),
            'officer' => auth()->user()->name ?? 'Staff Gudang',
        ]);
    }

    /**
     * Menambahkan stok dan mencatat pergerakannya.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => [
                'required',
                'integer',
                'distinct',
                'min:1',
            ],
            'items.*.qty' => [
                'required',
                'integer',
                'min:1',
            ],
        ], [
            'items.required' => 'Pilih minimal satu varian.',
            'items.min' => 'Pilih minimal satu varian.',
            'items.*.variant_id.required' => 'Varian tidak valid.',
            'items.*.variant_id.distinct' => 'Varian yang sama tidak boleh dipilih dua kali.',
            'items.*.qty.required' => 'Jumlah diterima wajib diisi.',
            'items.*.qty.integer' => 'Jumlah diterima harus berupa bilangan bulat.',
            'items.*.qty.min' => 'Jumlah diterima minimal satu.',
        ]);

        $userId = $request->user()->id;

        DB::transaction(function () use ($validated, $userId) {
            $items = collect($validated['items']);

            // Urutkan ID agar penguncian stok konsisten.
            $variantIds = $items
                ->pluck('variant_id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values();

            $variants = DB::table('product_variants as v')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->whereIn('v.id', $variantIds)
                ->select([
                    'v.id',
                    'v.current_stock',
                    'v.status as variant_status',
                    'v.merged_into_variant_id',
                    'p.name as product_name',
                    'v.name as variant_name',
                    'p.status as product_status',
                ])
                ->orderBy('v.id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($variants->count() !== $variantIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Ada varian yang tidak ditemukan.',
                ]);
            }

            $now = now();

            foreach ($items as $item) {
                $id = (int) $item['variant_id'];
                $qty = (int) $item['qty'];
                $variant = $variants->get($id);

                if (
                    $variant->variant_status !== 'active' ||
                    $variant->product_status !== 'active' ||
                    $variant->merged_into_variant_id !== null
                ) {
                    throw ValidationException::withMessages([
                        'items' => "Varian {$variant->product_name} - {$variant->variant_name} tidak aktif atau sudah digabung.",
                    ]);
                }

                $before = (int) $variant->current_stock;
                $after = $before + $qty;

                DB::table('product_variants')
                    ->where('id', $id)
                    ->update([
                        'current_stock' => $after,
                        'updated_at' => $now,
                    ]);

                DB::table('stock_movements')->insert([
                    'variant_id' => $id,
                    'movement_type' => 'stock_in',
                    'qty_change' => $qty,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_type' => 'stock_in',
                    'reference_id' => null,
                    'created_by' => $userId,
                    'created_at' => $now,
                ]);
            }
        });

        return redirect()
            ->route('stock.index')
            ->with('success', 'Stok masuk berhasil disimpan.');
    }
}
