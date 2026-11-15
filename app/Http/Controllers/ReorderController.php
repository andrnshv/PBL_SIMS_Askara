<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReorderController extends Controller
{
    private const STATUS_FILTERS = ['alert', 'low', 'out', 'ok'];

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $search = trim((string) $request->input('q', ''));
        $category = $request->input('category');
        $status = $request->input('status');

        if (! in_array($status, self::STATUS_FILTERS, true)) {
            $status = null;
        }

        // PostgreSQL membedakan huruf besar/kecil pada LIKE.
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = $this->activeVariants()->select([
            'v.id',
            'v.sku',
            'v.name as variant',
            'v.unit',
            'v.current_stock',
            'v.reorder_point',
            'p.name as product',
            'p.category',
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search, $like) {
                $q->where('p.name', $like, "%{$search}%")
                    ->orWhere('v.name', $like, "%{$search}%")
                    ->orWhere('v.sku', $like, "%{$search}%");
            });
        }

        if ($category !== null && $category !== '') {
            $query->where('p.category', $category);
        }

        switch ($status) {
            case 'alert': // menipis + habis
                $query->whereColumn('v.current_stock', '<=', 'v.reorder_point');
                break;

            case 'out':
                $query->where('v.current_stock', '<=', 0);
                break;

            case 'low':
                $query->where('v.current_stock', '>', 0)
                    ->whereColumn('v.current_stock', '<=', 'v.reorder_point');
                break;

            case 'ok':
                $query->whereColumn('v.current_stock', '>', 'v.reorder_point');
                break;
        }

        $variants = $query
            // Varian yang perlu perhatian tampil paling atas.
            ->orderByRaw(
                'CASE
                    WHEN v.current_stock <= 0 THEN 0
                    WHEN v.current_stock <= v.reorder_point THEN 1
                    ELSE 2
                END'
            )
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->orderBy('v.id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($row) {
                $stock = (int) $row->current_stock;
                $reorderPoint = (int) $row->reorder_point;

                $state = $stock <= 0
                    ? 'out'
                    : ($stock <= $reorderPoint ? 'low' : 'ok');

                return [
                    'id' => (int) $row->id,
                    'product' => $row->product,
                    'variant' => $row->variant,
                    'sku' => $row->sku,
                    'unit' => $row->unit,
                    'stock' => $stock,
                    'reorder_point' => $reorderPoint,
                    'status' => $state,
                ];
            });

        $categories = DB::table('products')
            ->where('status', 'active')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        // Jumlah notifikasi aktif (tidak terpengaruh filter halaman).
        $alertCount = $this->activeVariants()
            ->whereColumn('v.current_stock', '<=', 'v.reorder_point')
            ->count();

        return view('pages.reorder.index', [
            'title' => 'Pengaturan Reorder Point',
            'variants' => $variants,
            'categories' => $categories,
            'alertCount' => $alertCount,
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'perPage' => $perPage,
        ]);
    }

    public function update(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.reorder_point' => ['required', 'integer', 'min:0', 'max:1000000'],
        ], [
            'items.required' => 'Tidak ada perubahan untuk disimpan.',
            'items.*.reorder_point.required' => 'Reorder point wajib diisi.',
            'items.*.reorder_point.integer' => 'Reorder point harus bilangan bulat.',
            'items.*.reorder_point.min' => 'Reorder point tidak boleh kurang dari 0.',
            'items.*.reorder_point.max' => 'Reorder point terlalu besar.',
        ])->validate();

        $updated = DB::transaction(function () use ($validated) {
            $ids = collect(array_keys($validated['items']))
                ->map(fn ($id) => (int) $id)
                ->all();

            $variants = DB::table('product_variants')
                ->whereIn('id', $ids)
                ->where('status', 'active')
                ->whereNull('merged_into_variant_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($variants->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'items' => 'Ada varian yang tidak ditemukan atau sudah tidak aktif. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            $now = now();
            $count = 0;

            foreach ($validated['items'] as $id => $item) {
                $variant = $variants->get((int) $id);
                $newValue = (int) $item['reorder_point'];

                if ((int) $variant->reorder_point === $newValue) {
                    continue;
                }

                DB::table('product_variants')
                    ->where('id', (int) $id)
                    ->update([
                        'reorder_point' => $newValue,
                        'updated_at' => $now,
                    ]);

                $count++;
            }

            return $count;
        });

        return redirect()
            ->back()
            ->with('success', $updated > 0
                ? "{$updated} reorder point berhasil disimpan."
                : 'Tidak ada perubahan yang disimpan.');
    }

    private function activeVariants()
    {
        return DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.status', 'active')
            ->where('p.status', 'active')
            ->whereNull('v.merged_into_variant_id');
    }
}
