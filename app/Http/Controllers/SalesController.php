<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Models\ProductVariant;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\StockAlertService;

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
                'v.image_path',
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
                    'image' => ProductVariant::imageUrl($product->image_path),
                ];
            });

        return view('pages.sales.index', [
            'title' => 'Transaksi Penjualan',
            'products' => $products,
            'taxRate' => (float) config('sales.tax_rate', 0),
            'rounding' => [
                'step' => (int) config('sales.rounding_step', 0),
                'mode' => config('sales.rounding_mode', 'nearest'),
            ],
        ]);
    }

    /**
     * Menyimpan transaksi penjualan dan mengurangi stok.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'amount_paid' => ['required', 'numeric', 'min:0', 'max:999999999999'],
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
            'amount_paid.required' => 'Uang diterima wajib diisi.',
            'amount_paid.numeric' => 'Uang diterima harus berupa angka.',
            'amount_paid.min' => 'Uang diterima tidak boleh negatif.',
            'items.required' => 'Keranjang penjualan masih kosong.',
            'items.min' => 'Tambahkan minimal satu produk.',
            'items.*.variant_id.required' => 'Varian produk tidak valid.',
            'items.*.variant_id.distinct' => 'Varian produk tidak boleh duplikat.',
            'items.*.quantity.required' => 'Kuantitas wajib diisi.',
            'items.*.quantity.min' => 'Kuantitas minimal satu.',
        ]);

        $userId = $request->user()->id;
        $changes = []; // perubahan stok, dipakai untuk email stok habis / reorder

        $saleId = DB::transaction(function () use ($validated, $userId, &$changes) {
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

            $subtotalAmount = 0;
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

                $subtotalAmount += $subtotal;
            }

            // Pajak, pembulatan, total, dan kembalian dihitung di server.
            $subtotalAmount = round($subtotalAmount, 2);
            $taxRate = (float) config('sales.tax_rate', 0);
            $taxAmount = round($subtotalAmount * $taxRate / 100, 0);
            $beforeRounding = round($subtotalAmount + $taxAmount, 2);

            $step = (int) config('sales.rounding_step', 0);

            if ($step > 0) {
                $units = round($beforeRounding / $step, 6);
                $units = config('sales.rounding_mode') === 'up' ? ceil($units) : round($units);
                $totalAmount = round($units * $step, 2);
            } else {
                $totalAmount = $beforeRounding;
            }

            $roundingAmount = round($totalAmount - $beforeRounding, 2);

            $amountPaid = round((float) $validated['amount_paid'], 2);

            if ($amountPaid < $totalAmount) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Uang diterima kurang dari total (Rp'
                        . number_format($totalAmount, 0, ',', '.') . ').',
                ]);
            }

            $changeAmount = round($amountPaid - $totalAmount, 2);

            // Nomor invoice unik; kolom invoice_no memiliki unique constraint.
            $invoiceNo = 'INV-' . now()->format('YmdHis')
                . '-' . Str::upper(Str::random(6));

            $now = now();

            $saleId = DB::table('sales')->insertGetId([
                'invoice_no' => $invoiceNo,
                'sale_date' => $now,
                'customer_name' => $validated['customer_name'] ?? null,
                'status' => 'completed',
                'subtotal_amount' => $subtotalAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'rounding_amount' => $roundingAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
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

                $changes[] = [
                    'variant_id' => $item['variant_id'],
                    'before' => $item['stock_before'],
                    'after' => $item['stock_after'],
                ];
            }

            return $saleId;
        });

        // Email stok habis / perlu reorder, dikirim setelah transaksi berhasil.
        StockAlertService::notify($changes);

        $invoiceNo = DB::table('sales')
            ->where('id', $saleId)
            ->value('invoice_no');

        return redirect()
            ->route('sales.index')
            ->with('success', "Transaksi {$invoiceNo} berhasil disimpan.");
    }

    /**
     * Membatalkan transaksi penjualan (void) dan mengembalikan stok.
     * Data penjualan tidak dihapus; status berubah menjadi 'cancelled'.
     */
    public function cancel(Request $request, $id)
    {
        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'cancel_reason.required' => 'Alasan pembatalan wajib diisi.',
            'cancel_reason.min' => 'Alasan pembatalan minimal 5 karakter.',
            'cancel_reason.max' => 'Alasan pembatalan maksimal 255 karakter.',
        ]);

        $userId = $request->user()->id;
        $changes = [];

        DB::transaction(function () use ($id, $validated, $userId, &$changes) {
            // Kunci baris penjualan agar tidak bisa dibatalkan dua kali secara bersamaan.
            $sale = DB::table('sales')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            abort_if(!$sale, 404);

            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages([
                    'cancel_reason' => 'Transaksi ini sudah dibatalkan.',
                ]);
            }

            $items = DB::table('sale_items')
                ->where('sale_id', $sale->id)
                ->orderBy('variant_id')
                ->get();

            // Kunci varian dengan urutan id yang sama seperti store() untuk mencegah deadlock.
            $variants = DB::table('product_variants')
                ->whereIn('id', $items->pluck('variant_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $now = now();

            foreach ($items as $item) {
                $variant = $variants->get($item->variant_id);

                if (!$variant) {
                    throw ValidationException::withMessages([
                        'cancel_reason' => 'Ada varian produk pada transaksi ini yang tidak ditemukan.',
                    ]);
                }

                $quantity = (int) $item->quantity;
                $before = (int) $variant->current_stock;
                $after = $before + $quantity;

                DB::table('product_variants')
                    ->where('id', $item->variant_id)
                    ->update([
                        'current_stock' => $after,
                        'updated_at' => $now,
                    ]);

                DB::table('stock_movements')->insert([
                    'variant_id' => $item->variant_id,
                    'movement_type' => 'sale_cancel',
                    'qty_change' => $quantity, // positif: stok kembali
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'created_by' => $userId,
                    'created_at' => $now,
                ]);

                $changes[] = [
                    'variant_id' => $item->variant_id,
                    'before' => $before,
                    'after' => $after,
                ];
            }

            DB::table('sales')
                ->where('id', $sale->id)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => $now,
                    'cancelled_by' => $userId,
                    'cancel_reason' => $validated['cancel_reason'],
                    'updated_at' => $now,
                ]);
        });

        return redirect()
            ->route('sales.detail', $id)
            ->with('success', 'Transaksi berhasil dibatalkan dan stok dikembalikan.');
    }

    public function history(Request $request)
    {
        // Tanggal akhir hanya dibandingkan dengan tanggal mulai jika tanggal mulai diisi.
        $endRules = ['nullable', 'date'];

        if ($request->filled('start')) {
            $endRules[] = 'after_or_equal:start';
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'start' => ['nullable', 'date'],
            'end' => $endRules,
            'sort' => ['nullable', 'in:terbaru,terlama,tertinggi,terendah'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ], [
            'start.date' => 'Tanggal mulai tidak valid.',
            'end.date' => 'Tanggal akhir tidak valid.',
            'end.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
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
                $query->where('s.invoice_no', 'ilike', "%{$search}%")
                    ->orWhere('u.name', 'ilike', "%{$search}%");
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
        // Hanya transaksi 'completed' yang dihitung; transaksi dibatalkan tidak masuk pendapatan.
        $statsQuery = (clone $baseQuery)
            ->reorder()
            ->where('s.status', 'completed');

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

    public function exportPdf(Request $request)
    {
        $endRules = ['nullable', 'date'];

        if ($request->filled('start')) {
            $endRules[] = 'after_or_equal:start';
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'start' => ['nullable', 'date'],
            'end' => $endRules,
            'sort' => ['nullable', 'in:terbaru,terlama,tertinggi,terendah'],
        ], [
            'start.date' => 'Tanggal mulai tidak valid.',
            'end.date' => 'Tanggal akhir tidak valid.',
            'end.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
        ]);

        $search = trim($validated['q'] ?? '');
        $start = $validated['start'] ?? null;
        $end = $validated['end'] ?? null;
        $sort = $validated['sort'] ?? 'terbaru';

        // Query utama transaksi.
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

        // Filter pencarian.
        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('s.invoice_no', 'ilike', "%{$search}%")
                    ->orWhere('u.name', 'ilike', "%{$search}%");
            });
        }

        // Filter tanggal.
        if ($start) {
            $baseQuery->whereDate('s.sale_date', '>=', $start);
        }

        if ($end) {
            $baseQuery->whereDate('s.sale_date', '<=', $end);
        }

        // Pengurutan.
        switch ($sort) {
            case 'terlama':
                $baseQuery->orderBy('s.sale_date', 'asc');
                $sortLabel = 'Terlama';
                break;

            case 'tertinggi':
                $baseQuery->orderBy('s.total_amount', 'desc');
                $sortLabel = 'Total tertinggi';
                break;

            case 'terendah':
                $baseQuery->orderBy('s.total_amount', 'asc');
                $sortLabel = 'Total terendah';
                break;

            default:
                $baseQuery->orderBy('s.sale_date', 'desc');
                $sortLabel = 'Terbaru';
                break;
        }

        $baseQuery->orderBy('s.id', 'desc');

        // Query ringkasan tanpa ORDER BY, hanya transaksi 'completed'.
        $statsQuery = (clone $baseQuery)
            ->reorder()
            ->where('s.status', 'completed');

        $filteredIds = (clone $statsQuery)->select('s.id');

        $stats = [
            'transactions' => (clone $statsQuery)->count('s.id'),
            'revenue' => (clone $statsQuery)->sum('s.total_amount') ?? 0,
            'units' => DB::table('sale_items as si')
                ->join('sales as sx', 'sx.id', '=', 'si.sale_id')
                ->whereIn('sx.id', $filteredIds)
                ->sum('si.quantity'),
        ];

        // Total baris di tabel PDF mencakup semua status (termasuk dibatalkan),
        // karena daftar detail di bawah juga menampilkan semuanya.
        $totalRows = (clone $baseQuery)->reorder()->count('s.id');

        // Detail PDF dibatasi maksimal 50 transaksi.
        $sales = $baseQuery->limit(50)->get();

        // Hitung jenis item dan total unit setiap transaksi.
        $pageIds = $sales->pluck('id')->all();

        $itemStats = collect();

        if (!empty($pageIds)) {
            $itemStats = DB::table('sale_items')
                ->select('sale_id')
                ->selectRaw('COUNT(DISTINCT variant_id) as items')
                ->selectRaw('COALESCE(SUM(quantity), 0) as units')
                ->whereIn('sale_id', $pageIds)
                ->groupBy('sale_id')
                ->get()
                ->keyBy('sale_id');
        }

        // Sesuaikan struktur data dengan template PDF.
        $transactions = $sales->map(function ($sale) use ($itemStats) {
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

        $generatedAt = Carbon::now()->format('d/m/Y H:i');
        $generatedBy = $request->user()?->name ?? '-';

        $pdf = Pdf::loadView('reports.sales-report-pdf', [
            'stats' => $stats,
            'transactions' => $transactions,
            'totalRows' => $totalRows,
            'start' => $start,
            'end' => $end,
            'search' => $search,
            'sortLabel' => $sortLabel,
            'generatedAt' => $generatedAt,
            'generatedBy' => $generatedBy,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            'laporan-penjualan-' . Carbon::now()->format('Ymd-His') . '.pdf'
        );
    }

    public function detail($id)
    {
        // Ambil informasi utama transaksi beserta nama kasir dan data pembatalan.
        $sale = DB::table('sales as s')
            ->join('users as u', 'u.id', '=', 's.created_by')
            ->leftJoin('users as cu', 'cu.id', '=', 's.cancelled_by')
            ->where('s.id', $id)
            ->select([
                's.id',
                's.invoice_no',
                's.sale_date',
                's.customer_name',
                's.status',
                's.subtotal_amount',
                's.tax_rate',
                's.tax_amount',
                's.rounding_amount',
                's.total_amount',
                's.amount_paid',
                's.change_amount',
                's.cancelled_at',
                's.cancel_reason',
                'u.name as cashier',
                'cu.name as cancelled_by_name',
            ])
            ->first();

        // Jika transaksi tidak ditemukan, tampilkan halaman 404.
        abort_if(!$sale, 404);

        // Ambil seluruh produk yang tercatat dalam transaksi.
        $items = DB::table('sale_items as si')
            ->join('product_variants as pv', 'pv.id', '=', 'si.variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->where('si.sale_id', $sale->id)
            ->select([
                'p.name as product_name',
                'pv.name as variant_name',
                'pv.sku',
                'pv.unit',
                'si.quantity',
                'si.unit_price',
                'si.subtotal',
            ])
            ->get();

        return view('pages.sales.detail', compact('sale', 'items'));
    }

}