<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StockController extends Controller
{
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
        ]);

        $q = trim($validated['q'] ?? '');
        $type = $validated['type'] ?? '';
        $start = $validated['start'] ?? null;
        $end = $validated['end'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = DB::table('stock_movements as sm')
            ->join('product_variants as v', 'v.id', '=', 'sm.variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('users as u', 'u.id', '=', 'sm.created_by')
            ->select([
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
                'ref' => $m->reference_type
                    ? $m->reference_type . ' #' . $m->reference_id
                    : '-',
                'ref_note' => $m->reference_type ?? '-',
                'officer' => $m->officer ?? '-',
            ];
        });

        return view('pages.stock.history', [
            'movements' => $movements,
            'rows' => $rows,
            'start' => $start,
            'end' => $end,
            'perPage' => $perPage,
            'type' => $type,
        ]);
    }

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

        /*
        |--------------------------------------------------------------------------
        | DATA OPNAME SEMENTARA
        |--------------------------------------------------------------------------
        | Data berikut hanya untuk menguji tampilan.
        | Belum tersimpan di database.
        */

        $opnameData = [
            [
                'id' => 1,
                'number' => 'OPN-2026-001',
                'date' => '2026-10-15',
                'warehouse' => 'Gudang Utama',
                'note' => 'Pemeriksaan stok mingguan',
                'officer' => 'Staff Gudang',
                'counted' => 7,
                'diff' => 2,
                'status' => 'draft',
                'finished_at' => null,
            ],
            [
                'id' => 2,
                'number' => 'OPN-2026-002',
                'date' => '2026-10-12',
                'warehouse' => 'Gudang Utama',
                'note' => 'Pemeriksaan stok produk las',
                'officer' => 'Staff Gudang',
                'counted' => 5,
                'diff' => 1,
                'status' => 'selesai',
                'finished_at' => '2026-10-12 14:30:00',
            ],
            [
                'id' => 3,
                'number' => 'OPN-2026-003',
                'date' => '2026-10-10',
                'warehouse' => 'Gudang Utama',
                'note' => 'Pemeriksaan stok berkala',
                'officer' => 'Staff Gudang',
                'counted' => 7,
                'diff' => 0,
                'status' => 'selesai',
                'finished_at' => '2026-10-10 10:15:00',
            ],
        ];

        $allOpnames = collect($opnameData)->map(function ($o) {
            $o['date'] = Carbon::parse($o['date']);
            $o['finished_at'] = $o['finished_at']
                ? Carbon::parse($o['finished_at'])
                : null;

            return $o;
        });

        // Filter periode dan pencarian.
        $filtered = $allOpnames->filter(function ($o) use (
            $q, $start, $end
        ) {
            $matchesSearch = $q === ''
                || str_contains(
                    strtolower($o['number']),
                    strtolower($q)
                )
                || str_contains(
                    strtolower($o['note']),
                    strtolower($q)
                );

            $matchesStart = !$start
                || $o['date']->toDateString() >= $start;

            $matchesEnd = !$end
                || $o['date']->toDateString() <= $end;

            return $matchesSearch && $matchesStart && $matchesEnd;
        })->values();

        // Ringkasan mengikuti pencarian dan periode,
        // tetapi tidak mengikuti filter tab status.
        $tabCounts = [
            'all' => $filtered->count(),
            'draft' => $filtered->where('status', 'draft')->count(),
            'selesai' => $filtered->where('status', 'selesai')->count(),
        ];

        // Filter status untuk tabel.
        if ($status !== '') {
            $filtered = $filtered
                ->where('status', $status)
                ->values();
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $items = $filtered
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $opnames = new LengthAwarePaginator(
            $items,
            $filtered->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('pages.stock.opname', compact(
            'opnames',
            'tabCounts',
            'status',
            'start',
            'end',
            'perPage'
        ));
    }

    public function opnameCreate()
    {
        $variants = DB::table('product_variants as pv')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->where('pv.status', 'active')
            ->whereNull('pv.merged_into_variant_id')
            ->orderBy('p.name')
            ->orderBy('pv.name')
            ->select([
                'pv.id',
                'p.name as name',
                'pv.name as variant',
                'pv.sku',
                'pv.unit',
                'pv.current_stock as stock',
            ])
            ->get()
            ->map(fn ($v) => [
                'id'      => (int) $v->id,
                'name'    => $v->name,
                'variant' => $v->variant,
                'sku'     => $v->sku,
                'unit'    => $v->unit,
                'stock'   => (int) $v->stock,
            ])
            ->values();

        $draft = session('stock_opname_draft');

        $initial = $draft['items'] ?? [];

        $opnameNumber = $draft['number']
            ?? 'OPN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        $warehouses = ['Gudang Utama'];

        $officer = Auth::user()->name ?? 'Staff Gudang';

        $snapshotTime = $draft['snapshot_time']
            ?? now()->format('d-m-Y H:i');

        return view('pages.stock.opname-create', compact(
            'variants',
            'initial',
            'opnameNumber',
            'warehouses',
            'officer',
            'snapshotTime'
        ));
    }

    public function opnameStore(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:draft,finish'],
            'opname_date' => ['required', 'date'],
            'warehouse' => ['required', 'in:Gudang Utama'],
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
        ]);

        $items = collect($validated['items'])->map(function ($item) {
            return [
                'id' => (int) $item['variant_id'],
                'counted' => $item['counted'] ?? '',
                'note' => trim($item['note'] ?? ''),
            ];
        })->values()->all();

        // Simpan draft sementara tanpa mengubah stok.
        if ($validated['action'] === 'draft') {
            $existingDraft = session('stock_opname_draft', []);

            session([
                'stock_opname_draft' => [
                    'number' => $existingDraft['number']
                        ?? 'OPN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                    'opname_date' => $validated['opname_date'],
                    'warehouse' => $validated['warehouse'],
                    'notes' => $validated['notes'] ?? '',
                    'snapshot_time' => $existingDraft['snapshot_time']
                        ?? now()->format('d-m-Y H:i'),
                    'items' => $items,
                ],
            ]);

            return redirect()
                ->route('stock.opname.create')
                ->with('success', 'Draft opname disimpan sementara.');
        }

        // Opname selesai: seluruh varian harus memiliki hitungan fisik.
        foreach ($items as $index => $item) {
            if ($item['counted'] === '' || $item['counted'] === null) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "items.$index.counted" =>
                            'Semua varian harus memiliki hasil hitung fisik sebelum opname diselesaikan.',
                    ]);
            }

            $variant = DB::table('product_variants')
                ->where('id', $item['id'])
                ->first(['current_stock']);

            if (!$variant) {
                return back()
                    ->withInput()
                    ->withErrors(['items' => 'Ada varian yang sudah tidak tersedia.']);
            }

            $difference = (int) $item['counted'] - (int) $variant->current_stock;

            if ($difference !== 0 && trim($item['note']) === '') {
                return back()
                    ->withInput()
                    ->withErrors([
                        "items.$index.note" =>
                            'Catatan wajib diisi untuk varian yang memiliki selisih.',
                    ]);
            }
        }

        $userId = Auth::id();

        if (!$userId) {
            return redirect()->route('login');
        }

        try {
            DB::transaction(function () use ($items, $userId) {
                foreach ($items as $item) {
                    $variant = DB::table('product_variants')
                        ->where('id', $item['id'])
                        ->lockForUpdate()
                        ->first(['id', 'current_stock']);

                    if (!$variant) {
                        throw new \RuntimeException(
                            'Varian tidak ditemukan. Silakan muat ulang halaman.'
                        );
                    }

                    $before = (int) $variant->current_stock;
                    $after = (int) $item['counted'];
                    $difference = $after - $before;

                    // Tidak perlu membuat pergerakan jika tidak ada selisih.
                    if ($difference === 0) {
                        continue;
                    }

                    DB::table('stock_movements')->insert([
                        'variant_id' => $variant->id,
                        'movement_type' => 'adjustment',
                        'qty_change' => $difference,
                        'stock_before' => $before,
                        'stock_after' => $after,
                        'reference_type' => 'stock_opname',
                        'reference_id' => null,
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);

                    DB::table('product_variants')
                        ->where('id', $variant->id)
                        ->update([
                            'current_stock' => $after,
                            'updated_at' => now(),
                        ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'items' => 'Opname gagal diselesaikan. Periksa kembali data dan konfigurasi database.',
                ]);
        }

        session()->forget('stock_opname_draft');

        return redirect()
            ->route('stock.opname')
            ->with('success', 'Opname selesai dan penyesuaian stok berhasil dicatat.');
    }

}
