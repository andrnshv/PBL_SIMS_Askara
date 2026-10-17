<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $startThisMonth = $now->copy()->startOfMonth();
        $oneMonthAgo = $now->copy()->subMonthNoOverflow();   // hari yang sama di bulan lalu
        $startLastMonth = $oneMonthAgo->copy()->startOfMonth();

        // 1. Total produk aktif + pertumbuhan dibanding bulan lalu
        $totalProducts = DB::table('products')->where('status', 'active')->count();
        $productsLastMonth = DB::table('products')
            ->where('status', 'active')
            ->where('created_at', '<=', $oneMonthAgo)
            ->count();

        // 2. Total penjualan bulan ini (sampai hari ini) vs periode sama bulan lalu
        $salesThisMonth = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$startThisMonth, $now])
            ->count();
        $salesLastMonth = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$startLastMonth, $oneMonthAgo])
            ->count();

        // 3. Stok menipis: stok saat ini <= titik pemesanan ulang
        $lowStock = DB::table('product_variants')
            ->where('status', 'active')
            ->whereColumn('current_stock', '<=', 'reorder_point')
            ->count();

        // 4. Total PO
        $totalPo = DB::table('purchase_orders')->count();

        // 5. Grafik: jumlah penjualan per hari selama 7 hari terakhir
        $startChart = $now->copy()->subDays(6)->startOfDay();
        $rows = DB::table('sales')
            ->where('status', 'completed')
            ->where('sale_date', '>=', $startChart)
            ->selectRaw('DATE(sale_date) as day, COUNT(*) as total')
            ->groupByRaw('DATE(sale_date)')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startChart->copy()->addDays($i);
            $labels[] = $date->locale('id')->translatedFormat('d M');
            $values[] = (int) ($rows[$date->toDateString()] ?? 0);
        }

        return view('pages.dashboard.ecommerce', [
            'title' => 'Dashboard',
            'metrics' => [
                'total_products'  => $totalProducts,
                'products_growth' => $this->growth($totalProducts, $productsLastMonth),
                'sales_count'     => $salesThisMonth,
                'sales_growth'    => $this->growth($salesThisMonth, $salesLastMonth),
                'low_stock'       => $lowStock,
                'total_po'        => $totalPo,
            ],
            'chart' => ['labels' => $labels, 'values' => $values],
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