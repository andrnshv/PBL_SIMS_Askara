<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CONTOH controller (hanya membaca data, tidak mengubah database).
 * Tujuannya supaya halaman purchase-orders/index.blade.php mendapat
 * variabel yang dibutuhkan. Silakan serahkan/sesuaikan dengan tim backend.
 */
class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q'        => ['nullable', 'string', 'max:100'],
            'supplier' => ['nullable', 'integer'],
            'status'   => ['nullable', 'in:draft,ordered,partial,received,cancelled'],
            'start'    => ['nullable', 'date'],
            'end'      => ['nullable', 'date', 'after_or_equal:start'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $search  = trim($validated['q'] ?? '');
        $perPage = (int) ($validated['per_page'] ?? 10);

        // Query utama: PO + nama supplier.
        $query = DB::table('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->select([
                'po.id',
                'po.po_number',
                'po.order_date',
                'po.status',
                's.name as supplier',
            ]);

        // Pencarian: nomor PO atau nama supplier.
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('po.po_number', 'like', "%{$search}%")
                  ->orWhere('s.name', 'like', "%{$search}%");
            });
        }

        if (! empty($validated['supplier'])) {
            $query->where('po.supplier_id', $validated['supplier']);
        }

        if (! empty($validated['status'])) {
            $query->where('po.status', $validated['status']);
        }

        if (! empty($validated['start'])) {
            $query->whereDate('po.order_date', '>=', $validated['start']);
        }

        if (! empty($validated['end'])) {
            $query->whereDate('po.order_date', '<=', $validated['end']);
        }

        $orders = $query
            ->orderByDesc('po.order_date')
            ->orderByDesc('po.id')
            ->paginate($perPage)
            ->withQueryString();

        // Hitung jumlah varian & total nilai untuk PO di halaman ini saja.
        $pageIds = $orders->getCollection()->pluck('id')->all();
        $itemStats = collect();

        if (! empty($pageIds)) {
            $itemStats = DB::table('purchase_order_items')
                ->select('purchase_order_id')
                ->selectRaw('COUNT(*) as variants')
                ->selectRaw('COALESCE(SUM(qty_ordered * unit_cost), 0) as total')
                ->whereIn('purchase_order_id', $pageIds)
                ->groupBy('purchase_order_id')
                ->get()
                ->keyBy('purchase_order_id');
        }

        // Ubah ke bentuk yang dipakai Blade.
        $orders->getCollection()->transform(function ($po) use ($itemStats) {
            $items = $itemStats->get($po->id);

            return [
                'id'       => $po->id,
                'number'   => $po->po_number,
                'date'     => Carbon::parse($po->order_date),
                'supplier' => $po->supplier,
                'variants' => (int) ($items->variants ?? 0),
                'total'    => (float) ($items->total ?? 0),
                // Kolom estimasi tiba belum ada di tabel purchase_orders.
                // Sementara null -> Blade menampilkan "—".
                'eta'      => null,
                'status'   => $po->status,
            ];
        });

        // Ringkasan untuk 3 kartu (dihitung dari semua PO, tanpa filter).
        $counts = DB::table('purchase_orders')
            ->select('status')
            ->selectRaw('COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $stats = [
            'draft'     => (int) ($counts['draft'] ?? 0),
            'ordered'   => (int) ($counts['ordered'] ?? 0),
            'partial'   => (int) ($counts['partial'] ?? 0),
            'received'  => (int) ($counts['received'] ?? 0),
            'cancelled' => (int) ($counts['cancelled'] ?? 0),
            'total'     => $orders->total(), // jumlah PO sesuai filter
        ];
        $stats['open'] = $stats['draft'] + $stats['ordered'] + $stats['partial'];

        $suppliers = DB::table('suppliers')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.purchase-orders.index', [
            'title'     => 'Purchase Order',
            'orders'    => $orders,
            'stats'     => $stats,
            'suppliers' => $suppliers,
            'perPage'   => $perPage,
        ]);
    }
}