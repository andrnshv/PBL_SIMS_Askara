<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const MAX_RANGE_DAYS = 90;

    public function index(Request $request)
    {
        $now = Carbon::now();

        // ===== FILTER TANGGAL =====
        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end'   => ['nullable', 'date', 'after_or_equal:start'],
        ], [
            'start.date'         => 'Tanggal mulai tidak valid.',
            'end.date'           => 'Tanggal akhir tidak valid.',
            'end.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
        ]);

        // Default: 7 hari terakhir
        $start = isset($validated['start'])
            ? Carbon::parse($validated['start'])->startOfDay()
            : $now->copy()->subDays(6)->startOfDay();
        $end = isset($validated['end'])
            ? Carbon::parse($validated['end'])->endOfDay()
            : $now->copy()->endOfDay();

        $days = iterator_count(CarbonPeriod::create($start->toDateString(), $end->toDateString()));

        if ($days > self::MAX_RANGE_DAYS) {
            return redirect()->route('dashboard')
                ->withErrors(['end' => 'Rentang maksimal ' . self::MAX_RANGE_DAYS . ' hari.']);
        }

        // Periode pembanding: rentang sebelumnya dengan panjang yang sama
        $prevEnd   = $start->copy()->subSecond();
        $prevStart = $start->copy()->subDays($days);
        // ==========================

        // 1. Total produk aktif + pertumbuhan dibanding bulan lalu
        $oneMonthAgo = $now->copy()->subMonthNoOverflow();
        $totalProducts = DB::table('products')->where('status', 'active')->count();
        $productsLastMonth = DB::table('products')
            ->where('status', 'active')
            ->where('created_at', '<=', $oneMonthAgo)
            ->count();

        // 2. Total penjualan pada rentang filter vs periode sebelumnya
        $salesInRange = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$start, $end])
            ->count();

        // 2b. Nilai penjualan (rupiah) pada rentang filter vs periode sebelumnya.
        //     Hanya transaksi 'completed'; sumber angka: sales.total_amount.
        $revenueInRange = (float) DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$start, $end])
            ->sum('total_amount');
        $revenuePrevRange = (float) DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$prevStart, $prevEnd])
            ->sum('total_amount');

        // 3. Stok menipis: stok saat ini <= titik pemesanan ulang
        $lowStock = DB::table('product_variants')
            ->where('status', 'active')
            ->whereColumn('current_stock', '<=', 'reorder_point')
            ->count();

        // 3b. Total stok: jumlah stok seluruh varian aktif
        //     (definisi sama dengan halaman Daftar Stok / StockController@index)
        $stockSummary = DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.status', 'active')
            ->where('p.status', 'active')
            ->whereNull('v.merged_into_variant_id')
            ->selectRaw('COALESCE(SUM(v.current_stock), 0) AS total_stock, COUNT(*) AS total_variants')
            ->first();

        // 3c. Item hasil penggabungan (kumulatif, tidak ikut filter tanggal)
        //     Varian sumber yang sudah digabung: status 'merged' atau merged_into_variant_id terisi
        //     (kriteria sama dengan ProductVariant::isMerged()).
        $mergedItems = DB::table('product_variants')
            ->where(function ($q) {
                $q->where('status', 'merged')
                  ->orWhereNotNull('merged_into_variant_id');
            })
            ->count();

        // Jumlah operasi penggabungan yang tercatat di audit trail (satu baris log = satu operasi)
        $mergeOperations = DB::table('product_merge_logs')->count();

        // 4. Total PO
        $totalPo = DB::table('purchase_orders')->count();

        // 5. Grafik: jumlah penjualan per hari pada rentang filter
        $rows = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$start, $end])
            ->selectRaw('DATE(sale_date) as day, COUNT(*) as total')
            ->groupByRaw('DATE(sale_date)')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];
        foreach (CarbonPeriod::create($start->toDateString(), $end->toDateString()) as $date) {
            $labels[] = $date->locale('id')->translatedFormat('d M');
            $values[] = (int) ($rows[$date->toDateString()] ?? 0);
        }

        // 6. Produk terlaris (5 teratas) pada rentang filter, digabung per produk induk.
        //    Varian yang sudah di-merge dipetakan ke varian tujuannya, sehingga penjualan
        //    produk yang sama tidak terpecah. Diurutkan berdasarkan nilai penjualan (rupiah)
        //    karena satuan antar produk berbeda (pcs, kg, pasang, dst).
        $topProducts = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->join('product_variants as v', 'v.id', '=', 'si.variant_id')
            ->leftJoin('product_variants as t', 't.id', '=', 'v.merged_into_variant_id')
            ->join('products as p', 'p.id', '=', DB::raw('COALESCE(t.product_id, v.product_id)'))
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$start, $end])
            ->selectRaw('p.id, p.name, SUM(si.quantity) AS qty, SUM(si.subtotal) AS revenue,
                         COUNT(DISTINCT COALESCE(t.id, v.id)) AS variant_count')
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('revenue')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        return view('pages.dashboard.ecommerce', [
            'title' => 'Dashboard',
            'metrics' => [
                'total_products'  => $totalProducts,
                'products_growth' => $this->growth($totalProducts, $productsLastMonth),
                'total_stock'     => (int) $stockSummary->total_stock,
                'total_variants'  => (int) $stockSummary->total_variants,
                'merged_items'    => $mergedItems,
                'merge_operations' => $mergeOperations,
                'sales_count'     => $salesInRange,
                'sales_revenue'   => $revenueInRange,
                'sales_growth'    => $this->growth($revenueInRange, $revenuePrevRange),
                'low_stock'       => $lowStock,
                'total_po'        => $totalPo,
            ],
            'chart'  => ['labels' => $labels, 'values' => $values],
            'topProducts' => $topProducts,
            'filter' => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
                'days'  => $days,
            ],
        ]);
    }

    // Hitung persentase pertumbuhan. Kalau data pembanding 0, hasilnya null (badge disembunyikan).
    private function growth(int|float $current, int|float $previous): ?int
    {
        if ($previous == 0) {
            return null;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}