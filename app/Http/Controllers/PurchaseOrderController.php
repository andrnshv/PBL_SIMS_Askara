<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * CRUD Purchase Order.
 * Mengirim PO TIDAK menambah stok; stok bertambah lewat dokumen penerimaan
 * (StockInController, ?po=ID) yang juga memperbarui qty_received & status PO.
 */
class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {

    $endRules = ['nullable', 'date'];

    if ($request->filled('start')) {
        $endRules[] = 'after_or_equal:start';
    }

        $validated = $request->validate([
            'q'        => ['nullable', 'string', 'max:100'],
            'supplier' => ['nullable', 'integer'],
            'status'   => ['nullable', 'in:draft,ordered,partial,received,cancelled'],
            'start'    => ['nullable', 'date'],
            'end'      => $endRules,
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
                'po.expected_date',
                's.name as supplier',
            ]);

        // Pencarian: nomor PO atau nama supplier.
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('po.po_number', 'ilike', "%{$search}%")
                  ->orWhere('s.name', 'ilike', "%{$search}%");
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
                'eta'      => $po->expected_date ? Carbon::parse($po->expected_date) : null,
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

    /* ------------------------------------------------------------------
     |  Buat / ubah draft
     ------------------------------------------------------------------ */

    public function create()
    {
        return view('pages.purchase-orders.form', $this->formData(null));
    }

    public function store(Request $request)
    {
        return $this->save($request, null);
    }

    public function edit(int $id)
    {
        $po = DB::table('purchase_orders')->where('id', $id)->first();
        abort_if(! $po, 404);

        if ($po->status !== 'draft') {
            return redirect()
                ->route('purchase-orders.show', $id)
                ->with('error', 'Hanya PO berstatus Draft yang dapat diubah.');
        }

        return view('pages.purchase-orders.form', $this->formData($po));
    }

    public function update(Request $request, int $id)
    {
        $po = DB::table('purchase_orders')->where('id', $id)->first();
        abort_if(! $po, 404);

        if ($po->status !== 'draft') {
            return redirect()
                ->route('purchase-orders.show', $id)
                ->with('error', 'Hanya PO berstatus Draft yang dapat diubah.');
        }

        return $this->save($request, $id);
    }

    /* ------------------------------------------------------------------
     |  Detail & perubahan status
     ------------------------------------------------------------------ */

    public function show(int $id)
    {
        $po = DB::table('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->join('users as u', 'u.id', '=', 'po.created_by')
            ->where('po.id', $id)
            ->first(['po.*', 's.name as supplier', 's.phone as supplier_phone', 'u.name as creator']);

        abort_if(! $po, 404);

        $items = DB::table('purchase_order_items as i')
            ->join('product_variants as v', 'v.id', '=', 'i.variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('i.purchase_order_id', $id)
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->get([
                'p.name as product_name',
                'v.name as variant_name',
                'v.sku',
                'v.unit',
                'i.qty_ordered',
                'i.qty_received',
                'i.unit_cost',
            ]);

        $receipts = DB::table('stock_receipts')
            ->where('purchase_order_id', $id)
            ->orderByDesc('receipt_date')
            ->orderByDesc('id')
            ->get(['id', 'receipt_no', 'receipt_date', 'delivery_ref']);

        return view('pages.purchase-orders.show', [
            'po' => $po,
            'items' => $items,
            'receipts' => $receipts,
            'total' => $items->sum(fn ($i) => $i->qty_ordered * $i->unit_cost),
        ]);
    }

    public function exportPdf(int $id)
    {
        // Mengambil informasi utama Purchase Order.
        $po = DB::table('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->join('users as u', 'u.id', '=', 'po.created_by')
            ->where('po.id', $id)
            ->first([
                'po.*',
                's.name as supplier',
                's.phone as supplier_phone',
                'u.name as creator',
            ]);

        abort_if(! $po, 404);

        // Mengambil seluruh detail barang dalam Purchase Order.
        $items = DB::table('purchase_order_items as i')
            ->join('product_variants as v', 'v.id', '=', 'i.variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('i.purchase_order_id', $id)
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->get([
                'p.name as product_name',
                'v.name as variant_name',
                'v.sku',
                'v.unit',
                'i.qty_ordered',
                'i.qty_received',
                'i.unit_cost',
            ]);

        // Mengambil riwayat penerimaan barang dari PO ini.
        $receipts = DB::table('stock_receipts')
            ->where('purchase_order_id', $id)
            ->orderByDesc('receipt_date')
            ->orderByDesc('id')
            ->get([
                'id',
                'receipt_no',
                'receipt_date',
                'delivery_ref',
            ]);

        // Menghitung ringkasan Purchase Order.
        $total = $items->sum(
            fn ($item) => $item->qty_ordered * $item->unit_cost
        );

        $totalLines = $items->count();

        // Jumlah baris varian yang sudah menerima barang.
        $receivedLines = $items
            ->filter(fn ($item) => (int) $item->qty_received > 0)
            ->count();

        // Mengubah status database menjadi label yang mudah dibaca.
        $statusLabels = [
            'draft' => 'Draft',
            'ordered' => 'Dikirim',
            'partial' => 'Diterima sebagian',
            'received' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];

        $statusLabel = $statusLabels[$po->status]
            ?? ucfirst($po->status);

        // Membentuk dokumen PDF.
        $pdf = Pdf::loadView('reports.purchase-order-pdf', [
            'po' => $po,
            'items' => $items,
            'receipts' => $receipts,
            'total' => $total,
            'totalLines' => $totalLines,
            'receivedLines' => $receivedLines,
            'statusLabel' => $statusLabel,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            'purchase-order-' . $po->po_number . '.pdf'
        );
    }

    /** Draft -> ordered (dikirim ke supplier). Tidak menambah stok. */
    public function send(int $id)
    {
        $result = DB::transaction(function () use ($id) {
            $po = DB::table('purchase_orders')->where('id', $id)->lockForUpdate()->first();
            abort_if(! $po, 404);

            if ($po->status !== 'draft') {
                return 'PO ini sudah bukan draft.';
            }

            if (! DB::table('purchase_order_items')->where('purchase_order_id', $id)->exists()) {
                return 'PO belum memiliki varian.';
            }

            DB::table('purchase_orders')->where('id', $id)->update([
                'status' => 'ordered',
                'updated_at' => now(),
            ]);

            return null;
        });

        return redirect()
            ->route('purchase-orders.show', $id)
            ->with($result ? 'error' : 'success', $result ?? 'PO ditandai sebagai Dikirim.');
    }

    /** Batalkan PO yang belum ada barang diterima. */
    public function cancel(int $id)
    {
        $result = DB::transaction(function () use ($id) {
            $po = DB::table('purchase_orders')->where('id', $id)->lockForUpdate()->first();
            abort_if(! $po, 404);

            if (! in_array($po->status, ['draft', 'ordered'], true)) {
                return 'PO dengan status ini tidak dapat dibatalkan.';
            }

            $received = DB::table('purchase_order_items')
                ->where('purchase_order_id', $id)
                ->where('qty_received', '>', 0)
                ->exists();

            if ($received) {
                return 'PO sudah memiliki penerimaan barang dan tidak dapat dibatalkan.';
            }

            DB::table('purchase_orders')->where('id', $id)->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);

            return null;
        });

        return redirect()
            ->route('purchase-orders.show', $id)
            ->with($result ? 'error' : 'success', $result ?? 'PO dibatalkan. Stok tidak terpengaruh.');
    }

    /** Hapus permanen hanya untuk draft. */
    public function destroy(int $id)
    {
        $po = DB::table('purchase_orders')->where('id', $id)->first();
        abort_if(! $po, 404);

        if ($po->status !== 'draft') {
            return redirect()
                ->route('purchase-orders.show', $id)
                ->with('error', 'Hanya PO berstatus Draft yang boleh dihapus. Gunakan Batalkan untuk PO lain.');
        }

        // purchase_order_items ikut terhapus lewat cascadeOnDelete.
        DB::table('purchase_orders')->where('id', $id)->delete();

        return redirect()
            ->route('purchase-orders.index')
            ->with('success', "Draft {$po->po_number} dihapus.");
    }

    /* ------------------------------------------------------------------
     |  Internal
     ------------------------------------------------------------------ */

    private function save(Request $request, ?int $poId)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:draft,send'],
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('is_active', true),
            ],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
                        'items.*.variant_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('product_variants', 'id')->where(
                    fn ($q) => $q->where('status', 'active')
                        ->whereNull('merged_into_variant_id')
                        ->whereIn('product_id', function ($sub) {
                            $sub->select('id')->from('products')->where('status', 'active');
                        })
                ),
            ],
            'items.*.qty_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ], [
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'expected_date.after_or_equal' => 'Estimasi tiba tidak boleh sebelum tanggal PO.',
            'items.required' => 'Tambahkan minimal satu varian.',
            'items.min' => 'Tambahkan minimal satu varian.',
            'items.*.variant_id.distinct' => 'Varian yang sama tidak boleh dipilih dua kali.',
            'items.*.variant_id.exists' => 'Ada varian yang tidak aktif, produk nonaktif, atau sudah digabung.',
            'items.*.qty_ordered.required' => 'Jumlah pesanan wajib diisi.',
            'items.*.qty_ordered.min' => 'Jumlah pesanan minimal satu.',
            'items.*.unit_cost.required' => 'Harga beli wajib diisi.',
            'items.*.unit_cost.min' => 'Harga beli tidak boleh negatif.',
        ]);

        $userId = $this->actor()->id;
        $status = $validated['action'] === 'send' ? 'ordered' : 'draft';
        $savedId = null;

        DB::transaction(function () use ($validated, $userId, $status, $poId, &$savedId) {
            $now = now();

            $header = [
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $status,
                'updated_at' => $now,
            ];

            if ($poId === null) {
                $poId = DB::table('purchase_orders')->insertGetId($header + [
                    'po_number' => $this->nextNumber(),
                    'created_by' => $userId,
                    'created_at' => $now,
                ]);
            } else {
                $current = DB::table('purchase_orders')
                    ->where('id', $poId)
                    ->lockForUpdate()
                    ->first(['status']);

                if (! $current || $current->status !== 'draft') {
                    throw ValidationException::withMessages([
                        'items' => 'PO sudah tidak berstatus Draft dan tidak dapat diubah.',
                    ]);
                }

                DB::table('purchase_orders')->where('id', $poId)->update($header);
                DB::table('purchase_order_items')->where('purchase_order_id', $poId)->delete();
            }

            DB::table('purchase_order_items')->insert(
                collect($validated['items'])->map(fn ($i) => [
                    'purchase_order_id' => $poId,
                    'variant_id' => (int) $i['variant_id'],
                    'qty_ordered' => (int) $i['qty_ordered'],
                    'qty_received' => 0,
                    'unit_cost' => $i['unit_cost'],
                ])->all()
            );

            $savedId = $poId;
        });

        return redirect()
            ->route('purchase-orders.show', $savedId)
            ->with('success', $status === 'ordered'
                ? 'PO disimpan dan ditandai Dikirim.'
                : 'Draft PO disimpan.');
    }

    private function formData(?object $po): array
    {
        $items = [];

        if ($po) {
            $items = DB::table('purchase_order_items')
                ->where('purchase_order_id', $po->id)
                ->orderBy('id')
                ->get(['variant_id', 'qty_ordered', 'unit_cost'])
                ->map(fn ($i) => [
                    'id' => (int) $i->variant_id,
                    'qty' => (int) $i->qty_ordered,
                    'cost' => (float) $i->unit_cost,
                ])
                ->all();
        }

        // Harga beli terakhir tiap varian sebagai nilai awal.
        $lastCost = DB::table('purchase_order_items')
            ->select('variant_id')
            ->selectRaw('MAX(unit_cost) AS cost')
            ->groupBy('variant_id')
            ->pluck('cost', 'variant_id');

        $variants = DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.status', 'active')
            ->where('p.status', 'active')
            ->whereNull('v.merged_into_variant_id')
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->get(['v.id', 'p.name', 'v.name as variant', 'v.sku', 'v.unit', 'v.current_stock'])
            ->map(fn ($v) => [
                'id' => (int) $v->id,
                'name' => $v->name,
                'variant' => $v->variant,
                'sku' => $v->sku,
                'unit' => $v->unit,
                'stock' => (int) $v->current_stock,
                'cost' => (float) ($lastCost[$v->id] ?? 0),
            ])
            ->values();

        return [
            'po' => $po,
            'items' => $items,
            'variants' => $variants,
            'suppliers' => DB::table('suppliers')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'poNumber' => $po->po_number ?? $this->nextNumber(),
        ];
    }

    /** Nomor PO berurut per hari, mis. PO-20261018-001. */
    private function nextNumber(): string
    {
        $stamp = 'PO-' . now()->format('Ymd') . '-';

        $last = DB::table('purchase_orders')
            ->where('po_number', 'like', $stamp . '%')
            ->orderByDesc('po_number')
            ->value('po_number');

        $next = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $stamp . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
