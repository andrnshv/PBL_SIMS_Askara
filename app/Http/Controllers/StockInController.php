<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\StockAlertService;

/**
 * Stok masuk = dokumen penerimaan barang (stock_receipts + stock_receipt_items).
 * Bisa dari PO (?po=ID) atau penerimaan langsung tanpa PO.
 */
class StockInController extends Controller
{
    /** Daftar dokumen penerimaan (tab "Penerimaan barang" di menu Pembelian). */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $q = trim($validated['q'] ?? '');
        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = DB::table('stock_receipts as r')
            ->leftJoin('suppliers as s', 's.id', '=', 'r.supplier_id')
            ->leftJoin('purchase_orders as po', 'po.id', '=', 'r.purchase_order_id')
            ->join('users as u', 'u.id', '=', 'r.received_by')
            ->select([
                'r.id',
                'r.receipt_no',
                'r.receipt_date',
                'r.delivery_ref',
                's.name as supplier',
                'po.po_number',
                'u.name as officer',
            ])
            ->selectRaw('(SELECT COUNT(*) FROM stock_receipt_items i WHERE i.receipt_id = r.id) AS variants')
            ->selectRaw('(SELECT COALESCE(SUM(i.qty_received), 0) FROM stock_receipt_items i WHERE i.receipt_id = r.id) AS total_qty');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('r.receipt_no', 'ilike', "%{$q}%")
                    ->orWhere('r.delivery_ref', 'ilike', "%{$q}%")
                    ->orWhere('s.name', 'ilike', "%{$q}%")
                    ->orWhere('po.po_number', 'ilike', "%{$q}%");
            });
        }

        if (! empty($validated['start'])) {
            $query->whereDate('r.receipt_date', '>=', $validated['start']);
        }

        if (! empty($validated['end'])) {
            $query->whereDate('r.receipt_date', '<=', $validated['end']);
        }

        $receipts = $query
            ->orderByDesc('r.receipt_date')
            ->orderByDesc('r.id')
            ->paginate($perPage)
            ->withQueryString();

        return view('pages.stock.receipts', [
            'receipts' => $receipts,
            'perPage' => $perPage,
        ]);
    }

    public function show(int $id)
    {
        $receipt = DB::table('stock_receipts as r')
            ->leftJoin('suppliers as s', 's.id', '=', 'r.supplier_id')
            ->leftJoin('purchase_orders as po', 'po.id', '=', 'r.purchase_order_id')
            ->join('users as u', 'u.id', '=', 'r.received_by')
            ->where('r.id', $id)
            ->first([
                'r.*',
                's.name as supplier',
                'po.po_number',
                'u.name as officer',
            ]);

        abort_if(! $receipt, 404);

        $items = DB::table('stock_receipt_items as i')
            ->join('product_variants as v', 'v.id', '=', 'i.variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('i.receipt_id', $id)
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->get([
                'p.name as product_name',
                'v.name as variant_name',
                'v.sku',
                'v.unit',
                'i.qty_received',
            ]);

        return view('pages.stock.receipt-show', [
            'receipt' => $receipt,
            'items' => $items,
            'warehouse' => StockController::WAREHOUSE,
        ]);
    }

        /** Form stok masuk. Dengan ?po=ID, varian & jumlah terisi dari sisa PO. */
    public function create(Request $request)
    {
        $po = null;
        $initial = [];
        $poItems = [];

        if ($request->filled('po')) {
            $po = DB::table('purchase_orders')
                ->where('id', (int) $request->input('po'))
                ->whereIn('status', ['ordered', 'partial'])
                ->first();

            if (! $po) {
                return redirect()
                    ->route('purchase-orders.index')
                    ->with('error', 'PO tidak ditemukan atau belum/sudah tidak bisa menerima barang.');
            }

            $poRows = DB::table('purchase_order_items')
                ->where('purchase_order_id', $po->id)
                ->whereRaw('qty_ordered > qty_received')
                ->get(['variant_id', 'qty_ordered', 'qty_received']);

            foreach ($poRows as $row) {
                $remaining = (int) $row->qty_ordered - (int) $row->qty_received;

                // Jumlah awal di form = sisa pesanan.
                $initial[$row->variant_id] = $remaining;

                // Info PO per varian: dipesan, sudah diterima, sisa.
                $poItems[$row->variant_id] = [
                    'ordered' => (int) $row->qty_ordered,
                    'received' => (int) $row->qty_received,
                    'remaining' => $remaining,
                ];
            }
        }

        // Pulihkan jumlah yang diisi apabila validasi gagal.
        $old = collect(old('items', []))
            ->filter(fn ($item) => isset($item['variant_id']))
            ->mapWithKeys(fn ($item) => [
                (string) $item['variant_id'] => $item['qty'] ?? '',
            ])
            ->all();

        if ($old) {
            $initial = $old;
        }

        $variants = DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.status', 'active')
            ->where('p.status', 'active')
            ->whereNull('v.merged_into_variant_id')
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->get([
                'v.id',
                'v.sku',
                'v.name as variant',
                'v.unit',
                'v.current_stock as stock',
                'p.name',
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

        $suppliers = DB::table('suppliers')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // PO yang masih bisa menerima barang (untuk dropdown "Dasar penerimaan").
        $openPos = DB::table('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->whereIn('po.status', ['ordered', 'partial'])
            ->orderByDesc('po.order_date')
            ->orderByDesc('po.id')
            ->get(['po.id', 'po.po_number', 'po.order_date', 's.name as supplier']);

        return view('pages.stock.incoming', [
            'variants' => $variants,
            'initial' => (object) $initial,
            'poItems' => (object) $poItems,
            'suppliers' => $suppliers,
            'po' => $po,
            'openPos' => $openPos,
            'warehouses' => [StockController::WAREHOUSE],
            'receiptNumber' => $this->nextReceiptNumber(),
            'officer' => $this->actor()->name,
        ]);
    }

    /** Simpan dokumen penerimaan, tambah stok, dan catat pergerakannya. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'received_at' => ['required', 'date'],
            'warehouse' => ['nullable', 'in:' . StockController::WAREHOUSE],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'delivery_ref' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'distinct', 'min:1'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Pilih minimal satu varian.',
            'items.min' => 'Pilih minimal satu varian.',
            'items.*.variant_id.required' => 'Varian tidak valid.',
            'items.*.variant_id.distinct' => 'Varian yang sama tidak boleh dipilih dua kali.',
            'items.*.qty.required' => 'Jumlah diterima wajib diisi.',
            'items.*.qty.integer' => 'Jumlah diterima harus berupa bilangan bulat.',
            'items.*.qty.min' => 'Jumlah diterima minimal satu.',
        ]);

        $userId = $this->actor()->id;
        $receiptId = null;

        DB::transaction(function () use ($validated, $userId, &$receiptId) {
            $now = now();
            $items = collect($validated['items'])->map(fn ($i) => [
                'variant_id' => (int) $i['variant_id'],
                'qty' => (int) $i['qty'],
            ]);

            // Kunci varian (urut ID) agar stok konsisten.
            $variants = DB::table('product_variants as v')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->whereIn('v.id', $items->pluck('variant_id')->sort()->values())
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

            if ($variants->count() !== $items->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Ada varian yang tidak ditemukan.',
                ]);
            }

            foreach ($items as $item) {
                $v = $variants->get($item['variant_id']);

                if (
                    $v->variant_status !== 'active' ||
                    $v->product_status !== 'active' ||
                    $v->merged_into_variant_id !== null
                ) {
                    throw ValidationException::withMessages([
                        'items' => "Varian {$v->product_name} - {$v->variant_name} tidak aktif atau sudah digabung.",
                    ]);
                }
            }

            // Jika berasal dari PO: cek sisa jumlah tiap varian.
            $poId = $validated['purchase_order_id'] ?? null;
            $poItems = collect();
            $supplierId = $validated['supplier_id'] ?? null;

            if ($poId) {
                $po = DB::table('purchase_orders')
                    ->where('id', $poId)
                    ->lockForUpdate()
                    ->first();

                if (! in_array($po->status, ['ordered', 'partial'], true)) {
                    throw ValidationException::withMessages([
                        'items' => 'PO ini tidak dapat menerima barang (status: ' . $po->status . ').',
                    ]);
                }

                $supplierId = $po->supplier_id;

                $poItems = DB::table('purchase_order_items')
                    ->where('purchase_order_id', $poId)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('variant_id');

                foreach ($items as $item) {
                    $poItem = $poItems->get($item['variant_id']);
                    $v = $variants->get($item['variant_id']);
                    $label = "{$v->product_name} - {$v->variant_name}";

                    if (! $poItem) {
                        throw ValidationException::withMessages([
                            'items' => "Varian {$label} tidak ada di PO {$po->po_number}.",
                        ]);
                    }

                    $remaining = $poItem->qty_ordered - $poItem->qty_received;

                    if ($item['qty'] > $remaining) {
                        throw ValidationException::withMessages([
                            'items' => "Jumlah {$label} melebihi sisa PO (sisa {$remaining}).",
                        ]);
                    }
                }
            }

            $receiptId = DB::table('stock_receipts')->insertGetId([
                'receipt_no' => $this->nextReceiptNumber(),
                'receipt_date' => $validated['received_at'],
                'supplier_id' => $supplierId,
                'purchase_order_id' => $poId,
                'delivery_ref' => $validated['delivery_ref'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'received_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($items as $item) {
                $v = $variants->get($item['variant_id']);
                $before = (int) $v->current_stock;
                $after = $before + $item['qty'];

                DB::table('stock_receipt_items')->insert([
                    'receipt_id' => $receiptId,
                    'variant_id' => $item['variant_id'],
                    'qty_received' => $item['qty'],
                ]);

                DB::table('product_variants')
                    ->where('id', $item['variant_id'])
                    ->update(['current_stock' => $after, 'updated_at' => $now]);

                DB::table('stock_movements')->insert([
                    'variant_id' => $item['variant_id'],
                    'movement_type' => 'receipt',
                    'qty_change' => $item['qty'],
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_type' => 'stock_receipt',
                    'reference_id' => $receiptId,
                    'created_by' => $userId,
                    'created_at' => $now,
                ]);

                if ($poId) {
                    DB::table('purchase_order_items')
                        ->where('purchase_order_id', $poId)
                        ->where('variant_id', $item['variant_id'])
                        ->increment('qty_received', $item['qty']);
                }
            }

            // Status PO mengikuti penerimaan yang sudah tercatat.
            if ($poId) {
                $open = DB::table('purchase_order_items')
                    ->where('purchase_order_id', $poId)
                    ->whereRaw('qty_received < qty_ordered')
                    ->exists();

                DB::table('purchase_orders')->where('id', $poId)->update([
                    'status' => $open ? 'partial' : 'received',
                    'updated_at' => $now,
                ]);
            }
       });

        // Email ringkasan stok masuk, dikirim setelah transaksi berhasil.
        StockAlertService::notifyReceipt($receiptId);

        return redirect()
            ->route('stock.receipts.show', $receiptId)
            ->with('success', 'Stok masuk berhasil disimpan.');
    }

    /** Nomor dokumen berurut per hari, mis. IN-20261018-001. */
    private function nextReceiptNumber(): string
    {
        $stamp = 'IN-' . Carbon::now()->format('Ymd') . '-';

        $last = DB::table('stock_receipts')
            ->where('receipt_no', 'like', $stamp . '%')
            ->orderByDesc('receipt_no')
            ->value('receipt_no');

        $next = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $stamp . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
