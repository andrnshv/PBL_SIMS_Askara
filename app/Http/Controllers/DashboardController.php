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
        $salesPrevRange = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$prevStart, $prevEnd])
            ->count();

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
                'sales_growth'    => $this->growth($salesInRange, $salesPrevRange),
                'low_stock'       => $lowStock,
                'total_po'        => $totalPo,
            ],
            'chart'  => ['labels' => $labels, 'values' => $values],
            'filter' => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
                'days'  => $days,
            ],
        ]);
    }

    // Hitung persentase pertumbuhan. Kalau data pembanding 0, hasilnya null (badge disembunyikan).
    private function growth(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return null;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}