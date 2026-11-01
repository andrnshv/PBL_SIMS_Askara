<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SalesController extends Controller
{
    /**
     * Menampilkan halaman transaksi dengan data dari database.
     */
    public function index()
    {
        $products = DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.status', 'active')
            ->where('p.status', 'active')
            ->select([
                'v.id',
                'v.sku',
                'v.name as variant',
                'v.unit',
                'v.selling_price as price',
                'v.current_stock as stock',
                'v.reorder_point as min',
                'p.name',
                'p.category',
            ])
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->get()
            ->map(function ($product) {
                return [
                    // ID yang digunakan adalah ID VARIAN.
                    'id' => (int) $product->id,
                    'name' => $product->name,
                    'variant' => $product->variant,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'price' => (float) $product->price,
                    'stock' => (int) $product->stock,
                    'min' => (int) $product->min,
                    'category' => $product->category ?: 'Tanpa Kategori',
                    'image' => null,
                ];
            });

        return view('pages.sales.index', [
            'title' => 'Transaksi Penjualan',
            'products' => $products,
        ]);
    }

    /**
     * Menyimpan transaksi penjualan dan mengurangi stok.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => [
                'required',
                'integer',
                'distinct',
                'min:1',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ], [
            'items.required' => 'Keranjang penjualan masih kosong.',
            'items.min' => 'Tambahkan minimal satu produk.',
            'items.*.variant_id.required' => 'Varian produk tidak valid.',
            'items.*.variant_id.distinct' => 'Varian produk tidak boleh duplikat.',
            'items.*.quantity.required' => 'Kuantitas wajib diisi.',
            'items.*.quantity.min' => 'Kuantitas minimal satu.',
        ]);

        $userId = $request->user()->id;

        $saleId = DB::transaction(function () use ($validated, $userId) {
            $items = collect($validated['items']);

            $variantIds = $items
                ->pluck('variant_id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values();

            // Kunci baris varian agar stok diperiksa secara konsisten.
            $variants = DB::table('product_variants as v')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->whereIn('v.id', $variantIds)
                ->select([
                    'v.id',
                    'v.sku',
                    'v.name as variant_name',
                    'v.selling_price',
                    'v.current_stock',
                    'v.status as variant_status',
                    'p.name as product_name',
                    'p.status as product_status',
                ])
                ->orderBy('v.id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($variants->count() !== $variantIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Ada varian produk yang tidak ditemukan.',
                ]);
            }

            $totalAmount = 0;
            $preparedItems = [];

            // Validasi stok dan hitung harga di server.
            foreach ($items as $item) {
                $variantId = (int) $item['variant_id'];
                $quantity = (int) $item['quantity'];
                $variant = $variants->get($variantId);

                if (
                    $variant->variant_status !== 'active' ||
                    $variant->product_status !== 'active'
                ) {
                    throw ValidationException::withMessages([
                        'items' => "Produk {$variant->product_name} tidak aktif.",
                    ]);
                }

                if ($variant->current_stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$variant->product_name} - {$variant->variant_name} tidak mencukupi. Stok tersedia: {$variant->current_stock}.",
                    ]);
                }

                $unitPrice = (float) $variant->selling_price;
                $subtotal = round($unitPrice * $quantity, 2);

                $preparedItems[] = [
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'stock_before' => (int) $variant->current_stock,
                    'stock_after' => (int) $variant->current_stock - $quantity,
                ];

                $totalAmount += $subtotal;
            }

            // Nomor invoice unik; kolom invoice_no memiliki unique constraint.
            $invoiceNo = 'INV-' . now()->format('YmdHis')
                . '-' . Str::upper(Str::random(6));

            $now = now();

            $saleId = DB::table('sales')->insertGetId([
                'invoice_no' => $invoiceNo,
                'sale_date' => $now,
                'customer_name' => $validated['customer_name'] ?? null,
                'status' => 'completed',
                'total_amount' => $totalAmount,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($preparedItems as $item) {
                DB::table('sale_items')->insert([
                    'sale_id' => $saleId,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                DB::table('product_variants')
                    ->where('id', $item['variant_id'])
                    ->update([
                        'current_stock' => $item['stock_after'],
                        'updated_at' => $now,
                    ]);

                DB::table('stock_movements')->insert([
                    'variant_id' => $item['variant_id'],
                    'movement_type' => 'sale',
                    'qty_change' => -$item['quantity'],
                    'stock_before' => $item['stock_before'],
                    'stock_after' => $item['stock_after'],
                    'reference_type' => 'sale',
                    'reference_id' => $saleId,
                    'created_by' => $userId,
                    'created_at' => $now,
                ]);
            }

            return $saleId;
        });

        $invoiceNo = DB::table('sales')
            ->where('id', $saleId)
            ->value('invoice_no');

        return redirect()
            ->route('sales.index')
            ->with('success', "Transaksi {$invoiceNo} berhasil disimpan.");
    }


    public function history(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'sort' => ['nullable', 'in:terbaru,terlama,tertinggi,terendah'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $search = trim($validated['q'] ?? '');
        $start = $validated['start'] ?? null;
        $end = $validated['end'] ?? null;
        $sort = $validated['sort'] ?? 'terbaru';
        $perPage = (int) ($validated['per_page'] ?? 10);

        // Query utama: transaksi dan nama kasir.
        $baseQuery = DB::table('sales as s')
            ->leftJoin('users as u', 'u.id', '=', 's.created_by')
            ->select([
                's.id',
                's.invoice_no',
                's.sale_date',
                's.total_amount',
                's.status',
                'u.name as cashier',
            ]);

        // Pencarian berdasarkan nomor invoice atau nama kasir.
        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('s.invoice_no', 'like', "%{$search}%")
                    ->orWhere('u.name', 'like', "%{$search}%");
            });
        }

        // Filter rentang tanggal.
        if ($start) {
            $baseQuery->whereDate('s.sale_date', '>=', $start);
        }

        if ($end) {
            $baseQuery->whereDate('s.sale_date', '<=', $end);
        }

        // Urutan transaksi.
        switch ($sort) {
            case 'terlama':
                $baseQuery->orderBy('s.sale_date', 'asc');
                break;

            case 'tertinggi':
                $baseQuery->orderBy('s.total_amount', 'desc');
                break;

            case 'terendah':
                $baseQuery->orderBy('s.total_amount', 'asc');
                break;

            default:
                $baseQuery->orderBy('s.sale_date', 'desc');
                break;
        }

        $baseQuery->orderBy('s.id', 'desc');

           // Query ringkasan harus tanpa ORDER BY (PostgreSQL menolaknya pada count/sum).
            $statsQuery = (clone $baseQuery)->reorder();

            // ID transaksi setelah filter, digunakan untuk menghitung ringkasan.
            $filteredIds = (clone $statsQuery)->select('s.id');

            $stats = [
                'transactions' => (clone $statsQuery)->count('s.id'),
                'revenue' => (clone $statsQuery)->sum('s.total_amount') ?? 0,
                'units' => DB::table('sale_items as si')
                    ->join('sales as sx', 'sx.id', '=', 'si.sale_id')
                    ->whereIn('sx.id', $filteredIds)
                    ->sum('si.quantity'),
            ];

        // Pagination mempertahankan parameter filter pada URL.
        $transactions = $baseQuery
            ->paginate($perPage)
            ->withQueryString();

        // Hitung jenis produk dan total kuantitas untuk transaksi di halaman ini.
        $pageIds = $transactions->getCollection()
            ->pluck('id')
            ->all();

        $itemStats = collect();

        if (! empty($pageIds)) {
            $itemStats = DB::table('sale_items')
                ->select('sale_id')
                ->selectRaw('COUNT(DISTINCT variant_id) as items')
                ->selectRaw('COALESCE(SUM(quantity), 0) as units')
                ->whereIn('sale_id', $pageIds)
                ->groupBy('sale_id')
                ->get()
                ->keyBy('sale_id');
        }

        // Sesuaikan struktur data dengan Blade yang sudah kamu buat.
        $transactions->getCollection()->transform(function ($sale) use ($itemStats) {
            $items = $itemStats->get($sale->id);

            return [
                'id' => $sale->id,
                'invoice' => $sale->invoice_no,
                'datetime' => Carbon::parse($sale->sale_date),
                'cashier' => $sale->cashier ?? 'Pengguna tidak ditemukan',
                'items' => (int) ($items->items ?? 0),
                'units' => (int) ($items->units ?? 0),
                'total' => (float) $sale->total_amount,
                'status' => $sale->status,
            ];
        });

        return view('pages.sales.history', [
            'title' => 'Riwayat Penjualan',
            'transactions' => $transactions,
            'stats' => $stats,
            'sort' => $sort,
            'perPage' => $perPage,
        ]);
    }

}
