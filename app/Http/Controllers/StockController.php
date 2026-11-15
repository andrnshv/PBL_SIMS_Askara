<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\StockAlertService;

class StockController extends Controller
{
    /** Gudang tunggal; belum ada tabel gudang di database. */
    public const WAREHOUSE = 'Gudang Utama';

    public function index(Request $request)
    {
        // Batasi jumlah baris per halaman.
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        // Sumber data utama: produk induk dan variannya.
        $baseQuery = DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.status', 'active')
            ->where('p.status', 'active')
            ->whereNull('v.merged_into_variant_id');

        // Ringkasan stok dihitung dari database, tanpa dipengaruhi filter.
        $summary = (clone $baseQuery)
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE
                    WHEN v.current_stock > COALESCE(v.reorder_point, 0)
                    THEN 1 ELSE 0
                END) AS tersedia,
                SUM(CASE
                    WHEN v.current_stock > 0
                     AND v.current_stock <= COALESCE(v.reorder_point, 0)
                    THEN 1 ELSE 0
                END) AS menipis,
                SUM(CASE
                    WHEN v.current_stock <= 0
                    THEN 1 ELSE 0
                END) AS habis
            ")
            ->first();

        $stats = [
            'total' => (int) ($summary->total ?? 0),
            'tersedia' => (int) ($summary->tersedia ?? 0),
            'menipis' => (int) ($summary->menipis ?? 0),
            'habis' => (int) ($summary->habis ?? 0),
        ];

        // Daftar kategori yang tersedia di database.
        $categories = (clone $baseQuery)
            ->whereNotNull('p.category')
            ->where('p.category', '<>', '')
            ->distinct()
            ->orderBy('p.category')
            ->pluck('p.category');

        // Query untuk tabel stok.
        $query = (clone $baseQuery)->select([
            'v.id',
            'p.name as product_name',
            'p.category',
            'v.name as variant_name',
            'v.sku',
            'v.unit',
            'v.current_stock',
            'v.reorder_point',
        ]);

        // Pencarian nama produk, nama varian, atau SKU.
        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));

            $query->where(function ($q) use ($keyword) {
                $q->where('p.name', 'ilike', "%{$keyword}%")
                    ->orWhere('v.name', 'ilike', "%{$keyword}%")
                    ->orWhere('v.sku', 'ilike', "%{$keyword}%");
            });
        }

        // Filter kategori.
        if ($request->filled('category')) {
            $query->where('p.category', $request->input('category'));
        }

        // Filter status berdasarkan jumlah stok dan batas minimum.
        switch ($request->input('status')) {
            case 'tersedia':
                $query->whereRaw(
                    'v.current_stock > COALESCE(v.reorder_point, 0)'
                );
                break;

            case 'menipis':
                $query->where('v.current_stock', '>', 0)
                    ->whereRaw(
                        'v.current_stock <= COALESCE(v.reorder_point, 0)'
                    );
                break;

            case 'habis':
                $query->where('v.current_stock', '<=', 0);
                break;
        }

        $items = $query
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($item) {
                $stock = (int) $item->current_stock;
                $minimum = (int) ($item->reorder_point ?? 0);

                $status = $stock <= 0
                    ? 'habis'
                    : ($stock <= $minimum ? 'menipis' : 'tersedia');

                return [
                    'id' => (int) $item->id,
                    'name' => $item->product_name,
                    'variant' => $item->variant_name,
                    'sku' => $item->sku,
                    'unit' => $item->unit,
                    'stock' => $stock,
                    'min' => $minimum,
                    'category' => $item->category ?: 'Tanpa Kategori',
                    'status' => $status,
                ];
            });

        return view('pages.stock.index', [
            'stats' => $stats,
            'items' => $items,
            'categories' => $categories,
            'perPage' => $perPage,
        ]);
    }


    public function history(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:masuk,keluar,penyesuaian'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
            'variant' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        $q = trim($validated['q'] ?? '');
        $variantId = $validated['variant'] ?? null;
        $type = $validated['type'] ?? '';
        $start = $validated['start'] ?? null;
        $end = $validated['end'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = DB::table('stock_movements as sm')
            ->join('product_variants as v', 'v.id', '=', 'sm.variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('users as u', 'u.id', '=', 'sm.created_by')
            ->leftJoin('stock_receipts as sr', function ($join) {
                $join->on('sr.id', '=', 'sm.reference_id')
                    ->where('sm.reference_type', '=', 'stock_receipt');
            })
            ->leftJoin('stock_opnames as so', function ($join) {
                $join->on('so.id', '=', 'sm.reference_id')
                    ->where('sm.reference_type', '=', 'stock_opname');
            })
            ->select([
                'sr.receipt_no',
                'so.opname_no',
                'sm.id',
                'sm.movement_type',
                'sm.qty_change',
                'sm.stock_before',
                'sm.stock_after',
                'sm.reference_type',
                'sm.reference_id',
                'sm.created_at',
                'p.name as product_name',
                'v.name as variant_name',
                'v.sku',
                'v.unit',
                'u.name as officer',
            ]);

        if ($variantId) {
            $query->where('sm.variant_id', $variantId);
        }

        if ($start) {
            $query->whereDate('sm.created_at', '>=', $start);
        }

        if ($end) {
            $query->whereDate('sm.created_at', '<=', $end);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('p.name', 'ilike', "%{$q}%")
                    ->orWhere('v.name', 'ilike', "%{$q}%")
                    ->orWhere('v.sku', 'ilike', "%{$q}%")
                    ->orWhere('sm.reference_type', 'ilike', "%{$q}%")
                    ->orWhereRaw(
                        'CAST(sm.reference_id AS TEXT) ILIKE ?',
                        ["%{$q}%"]
                    );
            });
        }

        // Klasifikasi memakai jenis pergerakan yang benar-benar tersimpan.
        $query->when($type !== '', function ($sub) use ($type) {
            if ($type === 'masuk') {
                $sub->where('sm.qty_change', '>', 0);
            } elseif ($type === 'keluar') {
                $sub->where('sm.qty_change', '<', 0);
            } else {
                $sub->where('sm.movement_type', 'ilike', '%adjustment%');
            }
        });

        $rows = $query->orderByDesc('sm.created_at')
            ->orderByDesc('sm.id')
            ->paginate($perPage)
            ->withQueryString();

        $movements = $rows->getCollection()->map(function ($m) {
            $isAdjustment = str_contains(
                strtolower($m->movement_type),
                'adjustment'
            );

            $typeLabel = $isAdjustment
                ? 'penyesuaian'
                : ($m->qty_change > 0 ? 'masuk' : 'keluar');

            return [
                'datetime' => \Carbon\Carbon::parse($m->created_at),
                'type' => $typeLabel,
                'movement_type' => $m->movement_type,
                'delta' => (int) $m->qty_change,
                'before' => (int) $m->stock_before,
                'after' => (int) $m->stock_after,
                'name' => $m->product_name,
                'variant' => $m->variant_name,
                'sku' => $m->sku,
                'unit' => $m->unit,
                'ref' => match (true) {
                    (bool) $m->receipt_no => $m->receipt_no,
                    (bool) $m->opname_no => $m->opname_no,
                    (bool) $m->reference_type => $m->reference_type . ' #' . $m->reference_id,
                    default => '-',
                },
                'ref_url' => match (true) {
                    (bool) $m->receipt_no => route('stock.receipts.show', $m->reference_id),
                    (bool) $m->opname_no => route('stock.opname.show', $m->reference_id),
                    default => null,
                },
                'ref_note' => $m->reference_type ?? '-',
                'officer' => $m->officer ?? '-',
            ];
        });

        $variantInfo = $variantId
            ? DB::table('product_variants as v')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->where('v.id', $variantId)
                ->selectRaw("p.name || ' - ' || v.name as label")
                ->value('label')
            : null;

        return view('pages.stock.history', [
            'variantId' => $variantId,
            'variantInfo' => $variantInfo,
            'movements' => $movements,
            'rows' => $rows,
            'start' => $start,
            'end' => $end,
            'perPage' => $perPage,
            'type' => $type,
        ]);
    }

    public function exportHistoryPdf(Request $request)
    {
        // Validasi filter yang sama dengan halaman Stock History.
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:masuk,keluar,penyesuaian'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
            'variant' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        $q = trim($validated['q'] ?? '');
        $variantId = $validated['variant'] ?? null;
        $type = $validated['type'] ?? '';
        $start = $validated['start'] ?? null;
        $end = $validated['end'] ?? null;

        // Query menggunakan sumber data yang sama dengan Stock History.
        $query = DB::table('stock_movements as sm')
            ->join('product_variants as v', 'v.id', '=', 'sm.variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('users as u', 'u.id', '=', 'sm.created_by')
            ->leftJoin('stock_receipts as sr', function ($join) {
                $join->on('sr.id', '=', 'sm.reference_id')
                    ->where('sm.reference_type', '=', 'stock_receipt');
            })
            ->leftJoin('stock_opnames as so', function ($join) {
                $join->on('so.id', '=', 'sm.reference_id')
                    ->where('sm.reference_type', '=', 'stock_opname');
            })
            ->select([
                'sr.receipt_no',
                'so.opname_no',
                'sm.id',
                'sm.movement_type',
                'sm.qty_change',
                'sm.stock_before',
                'sm.stock_after',
                'sm.reference_type',
                'sm.reference_id',
                'sm.created_at',
                'p.name as product_name',
                'v.name as variant_name',
                'v.sku',
                'v.unit',
                'u.name as officer',
            ]);

        // Filter varian.
        if ($variantId) {
            $query->where('sm.variant_id', $variantId);
        }

        // Filter periode.
        if ($start) {
            $query->whereDate('sm.created_at', '>=', $start);
        }

        if ($end) {
            $query->whereDate('sm.created_at', '<=', $end);
        }

        // Filter pencarian.
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('p.name', 'ilike', "%{$q}%")
                    ->orWhere('v.name', 'ilike', "%{$q}%")
                    ->orWhere('v.sku', 'ilike', "%{$q}%")
                    ->orWhere('sm.reference_type', 'ilike', "%{$q}%")
                    ->orWhereRaw(
                        'CAST(sm.reference_id AS TEXT) ILIKE ?',
                        ["%{$q}%"]
                    );
            });
        }

        // Filter jenis pergerakan.
        $query->when($type !== '', function ($sub) use ($type) {
            if ($type === 'masuk') {
                $sub->where('sm.qty_change', '>', 0);
            } elseif ($type === 'keluar') {
                $sub->where('sm.qty_change', '<', 0);
            } else {
                $sub->where('sm.movement_type', 'ilike', '%adjustment%');
            }
        });

        // Ringkasan dihitung dari seluruh data sesuai filter.
        $totalRows = (clone $query)->count('sm.id');

        $incomingCount = (clone $query)
            ->where('sm.qty_change', '>', 0)
            ->count('sm.id');

        $outgoingCount = (clone $query)
            ->where('sm.qty_change', '<', 0)
            ->count('sm.id');

        $adjustmentCount = (clone $query)
            ->where('sm.movement_type', 'ilike', '%adjustment%')
            ->count('sm.id');

        // Detail PDF dibatasi maksimal 50 movement terbaru.
        $movements = (clone $query)
            ->orderByDesc('sm.created_at')
            ->orderByDesc('sm.id')
            ->limit(50)
            ->get()
            ->map(function ($m) {
                $isAdjustment = str_contains(
                    strtolower($m->movement_type),
                    'adjustment'
                );

                $typeLabel = $isAdjustment
                    ? 'penyesuaian'
                    : ($m->qty_change > 0 ? 'masuk' : 'keluar');

                return [
                    'datetime' => Carbon::parse($m->created_at),
                    'type' => $typeLabel,
                    'delta' => (int) $m->qty_change,
                    'before' => (int) $m->stock_before,
                    'after' => (int) $m->stock_after,
                    'name' => $m->product_name,
                    'variant' => $m->variant_name,
                    'sku' => $m->sku,
                    'unit' => $m->unit,
                    'ref' => match (true) {
                        (bool) $m->receipt_no => $m->receipt_no,
                        (bool) $m->opname_no => $m->opname_no,
                        (bool) $m->reference_type =>
                            $m->reference_type . ' #' . $m->reference_id,
                        default => '-',
                    },
                    'officer' => $m->officer ?? '-',
                ];
            });

        // Informasi varian jika filter varian digunakan.
        $variantInfo = $variantId
            ? DB::table('product_variants as v')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->where('v.id', $variantId)
                ->selectRaw("p.name || ' - ' || v.name as label")
                ->value('label')
            : null;

        // Membentuk dokumen PDF.
        $pdf = Pdf::loadView('reports.stock-movement-pdf', [
            'movements' => $movements,
            'totalRows' => $totalRows,
            'incomingCount' => $incomingCount,
            'outgoingCount' => $outgoingCount,
            'generatedBy' => $request->user()?->name ?? '-',
            'adjustmentCount' => $adjustmentCount,
            'variantInfo' => $variantInfo,
            'start' => $start,
            'end' => $end,
            'q' => $q,
            'type' => $type,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-stock-movement.pdf');
    }

    /* =====================================================================
     |  STOK OPNAME
     |  Tabel: stock_opnames (header) + stock_opname_items (detail)
     |  Status DB: draft | completed  →  di tampilan: draft | selesai
     ===================================================================== */

    public function opnameIndex(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,selesai'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $q = trim($validated['q'] ?? '');
        $status = $validated['status'] ?? '';
        $start = $validated['start'] ?? null;
        $end = $validated['end'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 10);

        // Filter periode + pencarian.
        $user = $request->user();

        $canManageOpname = $user->hasRole('staff_gudang');

        $base = DB::table('stock_opnames as o')
            ->join('users as u', 'u.id', '=', 'o.performed_by');

        if ($canManageOpname) {
            // Admin dan Staff Gudang dapat melihat draft dan selesai.
            $base->whereIn('o.status', ['draft', 'completed']);
        } else {
            // Staff Pembelian dan Penjualan hanya melihat opname selesai.
            $base->where('o.status', 'completed');
        }

        if ($start) {
            $base->whereDate('o.opname_date', '>=', $start);
        }

        if ($end) {
            $base->whereDate('o.opname_date', '<=', $end);
        }

        if ($q !== '') {
            $base->where(function ($sub) use ($q) {
                $sub->where('o.opname_no', 'ilike', "%{$q}%")
                    ->orWhere('o.notes', 'ilike', "%{$q}%");
            });
        }

        $counts = (clone $base)
            ->select('o.status')
            ->selectRaw('COUNT(*) as c')
            ->groupBy('o.status')
            ->pluck('c', 'status');

        $tabCounts = [
            'draft' => (int) ($counts['draft'] ?? 0),
            'selesai' => (int) ($counts['completed'] ?? 0),
        ];
        $tabCounts['all'] = $tabCounts['draft'] + $tabCounts['selesai'];

        if ($status !== '') {
            $base->where('o.status', $status === 'selesai' ? 'completed' : 'draft');
        }

        $opnames = $base
            ->select([
                'o.id',
                'o.opname_no',
                'o.opname_date',
                'o.status',
                'o.notes',
                'o.completed_at',
                'u.name as officer',
            ])
            ->selectRaw('(SELECT COUNT(*) FROM stock_opname_items i
                          WHERE i.opname_id = o.id
                            AND i.physical_qty IS NOT NULL) AS counted')
            ->selectRaw('(SELECT COUNT(*) FROM stock_opname_items i
                          WHERE i.opname_id = o.id
                            AND i.difference IS NOT NULL
                            AND i.difference <> 0) AS diff')
            ->orderByDesc('o.opname_date')
            ->orderByDesc('o.id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($o) => [
                'id' => (int) $o->id,
                'number' => $o->opname_no,
                'date' => Carbon::parse($o->opname_date),
                'warehouse' => self::WAREHOUSE,
                'note' => $o->notes,
                'officer' => $o->officer,
                'counted' => (int) $o->counted,
                'diff' => (int) $o->diff,
                'status' => $o->status === 'completed' ? 'selesai' : 'draft',
                'finished_at' => $o->completed_at
                    ? Carbon::parse($o->completed_at)
                    : null,
            ]);

        return view('pages.stock.opname', compact(
            'opnames',
            'tabCounts',
            'status',
            'start',
            'end',
            'perPage',
            'canManageOpname'
        ));
    }

    public function opnameCreate()
    {
        return view('pages.stock.opname-create', $this->opnameFormData(null));
    }

    public function opnameEdit(int $id)
    {
        $opname = DB::table('stock_opnames')->where('id', $id)->first();
        abort_if(! $opname, 404);

        // Dokumen yang sudah selesai hanya bisa dilihat.
        if ($opname->status !== 'draft') {
            return redirect()->route('stock.opname.show', $id);
        }

        return view('pages.stock.opname-create', $this->opnameFormData($opname));
    }

    public function opnameShow(Request $request, int $id)
    {
        $opname = DB::table('stock_opnames as o')
            ->join('users as u', 'u.id', '=', 'o.performed_by')
            ->where('o.id', $id)
            ->first(['o.*', 'u.name as officer']);

        abort_if(! $opname, 404);

        if ($opname->status === 'draft') {
            if ($request->user()->hasRole('staff_gudang')) {
                return redirect()->route('stock.opname.edit', $id);
            }

            return redirect()
                ->route('stock.opname')
                ->with('error', 'Draft opname tidak dapat diakses.');
        }

        $items = DB::table('stock_opname_items as i')
            ->join('product_variants as v', 'v.id', '=', 'i.variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('i.opname_id', $id)
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->get([
                'p.name as product_name',
                'v.name as variant_name',
                'v.sku',
                'v.unit',
                'i.system_qty',
                'i.physical_qty',
                'i.difference',
                'i.reason',
            ]);

        return view('pages.stock.opname-show', [
            'opname' => $opname,
            'items' => $items,
            'warehouse' => self::WAREHOUSE,
        ]);
    }


    public function exportOpnamePdf(int $id)
    {
        // Mengambil informasi utama Stock Opname.
        $opname = DB::table('stock_opnames as o')
            ->join('users as u', 'u.id', '=', 'o.performed_by')
            ->where('o.id', $id)
            ->first(['o.*', 'u.name as officer']);

        abort_if(! $opname, 404);

        // PDF hanya boleh dibuat untuk opname yang sudah selesai.
        abort_unless(
            $opname->status === 'completed',
            403,
            'Laporan PDF hanya tersedia untuk Stock Opname yang sudah selesai.'
        );

        // Mengambil seluruh detail pemeriksaan.
        $items = DB::table('stock_opname_items as i')
            ->join('product_variants as v', 'v.id', '=', 'i.variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('i.opname_id', $id)
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->get([
                'p.name as product_name',
                'v.name as variant_name',
                'v.sku',
                'v.unit',
                'i.system_qty',
                'i.physical_qty',
                'i.difference',
                'i.reason',
            ]);

        // Menghitung ringkasan hasil Stock Opname.
        $totalVariants = $items->count();

        $matchedCount = $items
            ->filter(fn ($item) => (int) $item->difference === 0)
            ->count();

        $surplusCount = $items
            ->filter(fn ($item) => (int) $item->difference > 0)
            ->count();

        $shortageCount = $items
            ->filter(fn ($item) => (int) $item->difference < 0)
            ->count();

        // Membentuk dokumen PDF.
        $pdf = Pdf::loadView('reports.stock-opname-pdf', [
            'opname' => $opname,
            'items' => $items,
            'warehouse' => self::WAREHOUSE,
            'totalVariants' => $totalVariants,
            'matchedCount' => $matchedCount,
            'surplusCount' => $surplusCount,
            'shortageCount' => $shortageCount,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            'laporan-stock-opname-' . $id . '.pdf'
        );
    }

    public function opnameStore(Request $request)
    {
        return $this->opnameSave($request, null);
    }

    public function opnameUpdate(Request $request, int $id)
    {
        $opname = DB::table('stock_opnames')->where('id', $id)->first();
        abort_if(! $opname, 404);

        if ($opname->status !== 'draft') {
            return redirect()
                ->route('stock.opname.show', $id)
                ->with('error', 'Opname yang sudah selesai tidak dapat diubah.');
        }

        return $this->opnameSave($request, $id);
    }

    public function opnameDestroy(int $id)
    {
        $opname = DB::table('stock_opnames')->where('id', $id)->first();
        abort_if(! $opname, 404);

        if ($opname->status !== 'draft') {
            return redirect()
                ->route('stock.opname')
                ->with('error', 'Hanya draft opname yang boleh dihapus.');
        }

        // stock_opname_items ikut terhapus lewat cascadeOnDelete.
        DB::table('stock_opnames')->where('id', $id)->delete();

        return redirect()
            ->route('stock.opname')
            ->with('success', "Draft {$opname->opname_no} dihapus.");
    }

       /**
     * Simpan draft atau selesaikan opname (insert baru atau update draft).
     * Seluruh proses dalam satu transaksi; stok baru berubah saat "finish".
     */
    private function opnameSave(Request $request, ?int $opnameId)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:draft,finish'],
            'opname_date' => ['required', 'date'],
            'warehouse' => ['required', 'in:' . self::WAREHOUSE],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => [
                'required',
                'integer',
                'distinct',
                'exists:product_variants,id',
            ],
            'items.*.counted' => ['nullable', 'integer', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Pilih minimal satu varian.',
            'items.min' => 'Pilih minimal satu varian.',
        ]);

        $finish = $validated['action'] === 'finish';
        $userId = $this->actor()->id;

        $items = collect($validated['items'])->map(fn ($item) => [
            'variant_id' => (int) $item['variant_id'],
            'counted' => ($item['counted'] ?? '') === '' ? null : (int) $item['counted'],
            'note' => trim($item['note'] ?? ''),
        ])->values();

        $savedId = null;
        $changes = []; // perubahan stok, dipakai untuk email stok habis / reorder

        try {
            DB::transaction(function () use (
                $validated, $items, $finish, $userId, $opnameId, &$savedId, &$changes
            ) {
                $now = now();

                // Kunci semua varian (urut ID) agar stok konsisten.
                $stocks = DB::table('product_variants as pv')
                    ->join('products as p', 'p.id', '=', 'pv.product_id')
                    ->whereIn('pv.id', $items->pluck('variant_id'))
                    ->where('pv.status', 'active')
                    ->where('p.status', 'active')
                    ->whereNull('pv.merged_into_variant_id')
                    ->orderBy('pv.id')
                    ->lockForUpdate()
                    ->pluck('pv.current_stock', 'pv.id');

                if ($stocks->count() !== $items->count()) {
                    throw ValidationException::withMessages([
                        'items' => 'Terdapat varian yang sudah tidak aktif, sudah digabung, atau produknya tidak aktif. Silakan periksa kembali daftar varian.',
                    ]);
                }

                // Validasi penyelesaian memakai stok terkunci (terbaru).
                if ($finish) {
                    foreach ($items as $index => $item) {
                        if ($item['counted'] === null) {
                            throw ValidationException::withMessages([
                                "items.$index.counted" =>
                                    'Semua varian harus memiliki hasil hitung fisik sebelum opname diselesaikan.',
                            ]);
                        }

                        $diff = $item['counted'] - (int) $stocks[$item['variant_id']];

                        if ($diff !== 0 && $item['note'] === '') {
                            throw ValidationException::withMessages([
                                "items.$index.note" =>
                                    'Catatan wajib diisi untuk varian yang memiliki selisih.',
                            ]);
                        }
                    }
                }

                $header = [
                    'opname_date' => $validated['opname_date'],
                    'notes' => $validated['notes'] ?? null,
                    'updated_at' => $now,
                ];

                if ($opnameId === null) {
                    $opnameId = DB::table('stock_opnames')->insertGetId($header + [
                        'opname_no' => $this->nextNumber('stock_opnames', 'opname_no', 'OPN'),
                        'status' => 'draft',
                        'performed_by' => $userId,
                        'created_at' => $now,
                    ]);
                } else {
                    // Kunci dokumen & pastikan masih draft.
                    $current = DB::table('stock_opnames')
                        ->where('id', $opnameId)
                        ->lockForUpdate()
                        ->first(['status']);

                    if (! $current || $current->status !== 'draft') {
                        throw ValidationException::withMessages([
                            'items' => 'Dokumen opname sudah selesai dan tidak dapat diubah.',
                        ]);
                    }

                    DB::table('stock_opnames')->where('id', $opnameId)->update($header);
                    DB::table('stock_opname_items')->where('opname_id', $opnameId)->delete();
                }

                foreach ($items as $item) {
                    $system = (int) $stocks[$item['variant_id']];
                    $counted = $item['counted'];
                    $diff = $counted === null ? null : $counted - $system;

                    DB::table('stock_opname_items')->insert([
                        'opname_id' => $opnameId,
                        'variant_id' => $item['variant_id'],
                        'system_qty' => $system,
                        'physical_qty' => $counted,
                        'difference' => $diff,
                        'reason' => $item['note'] !== '' ? $item['note'] : null,
                    ]);

                    if (! $finish || $diff === 0) {
                        continue;
                    }

                    DB::table('stock_movements')->insert([
                        'variant_id' => $item['variant_id'],
                        'movement_type' => 'opname_adjustment',
                        'qty_change' => $diff,
                        'stock_before' => $system,
                        'stock_after' => $counted,
                        'reference_type' => 'stock_opname',
                        'reference_id' => $opnameId,
                        'created_by' => $userId,
                        'created_at' => $now,
                    ]);

                    DB::table('product_variants')
                        ->where('id', $item['variant_id'])
                        ->update(['current_stock' => $counted, 'updated_at' => $now]);

                    $changes[] = [
                        'variant_id' => $item['variant_id'],
                        'before' => $system,
                        'after' => $counted,
                    ];
                }

                if ($finish) {
                    DB::table('stock_opnames')->where('id', $opnameId)->update([
                        'status' => 'completed',
                        'completed_at' => $now,
                    ]);
                }

                $savedId = $opnameId;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['items' => 'Opname gagal disimpan. Silakan coba lagi.']);
        }

        if ($finish) {
            // Email stok habis / perlu reorder, dikirim setelah transaksi berhasil.
            StockAlertService::notify($changes);

            return redirect()
                ->route('stock.opname.show', $savedId)
                ->with('success', 'Opname selesai dan penyesuaian stok berhasil dicatat.');
        }

        return redirect()
            ->route('stock.opname.edit', $savedId)
            ->with('success', 'Draft opname disimpan.');
    }

    /** Data untuk form opname (baru atau lanjutkan draft). */
    private function opnameFormData(?object $opname): array
    {
        $variants = DB::table('product_variants as pv')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->where('pv.status', 'active')
            ->where('p.status', 'active')
            ->whereNull('pv.merged_into_variant_id')
            ->orderBy('p.name')
            ->orderBy('pv.name')
            ->get([
                'pv.id',
                'p.name as name',
                'pv.name as variant',
                'pv.sku',
                'pv.unit',
                'pv.current_stock as stock',
            ])
            ->map(fn ($v) => [
                'id' => (int) $v->id,
                'name' => $v->name,
                'variant' => $v->variant,
                'sku' => $v->sku,
                'unit' => $v->unit,
                'stock' => (int) $v->stock,
            ])
            ->values();

        $initial = [];
        $draft = [];
        $snapshotTime = now()->format('d-m-Y H:i');

        if ($opname) {
            $initial = DB::table('stock_opname_items')
                ->where('opname_id', $opname->id)
                ->orderBy('id')
                ->get(['variant_id', 'physical_qty', 'reason'])
                ->map(fn ($i) => [
                    'id' => (int) $i->variant_id,
                    'counted' => $i->physical_qty ?? '',
                    'note' => $i->reason ?? '',
                ])
                ->all();

            $draft = [
                'opname_date' => Carbon::parse($opname->opname_date)->toDateString(),
                'notes' => $opname->notes ?? '',
            ];
            $snapshotTime = Carbon::parse($opname->updated_at)->format('d-m-Y H:i');
        }

        return [
            'variants' => $variants,
            'initial' => $initial,
            'draft' => $draft,
            'opname' => $opname,
            'opnameNumber' => $opname->opname_no
                ?? $this->nextNumber('stock_opnames', 'opname_no', 'OPN'),
            'warehouses' => [self::WAREHOUSE],
            'officer' => $this->actor()->name,
            'snapshotTime' => $snapshotTime,
        ];
    }

    /** Nomor dokumen berurut per hari, mis. OPN-20261018-003. */
    private function nextNumber(string $table, string $column, string $prefix): string
    {
        $stamp = $prefix . '-' . now()->format('Ymd') . '-';

        $last = DB::table($table)
            ->where($column, 'like', $stamp . '%')
            ->orderByDesc($column)
            ->value($column);

        $next = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $stamp . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
