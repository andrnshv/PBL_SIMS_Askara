<?php

namespace App\Http\Controllers;

use App\Models\AccurateImportBatch;
use App\Models\AccurateItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\AccurateImporter;
use App\Support\AccurateImportException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Migrasi data produk lama dari Accurate: impor CSV -> pemetaan ke varian -> verifikasi.
 * Akses: Admin dan Staf Gudang (lihat routes/web.php). Aksi buat-varian & buka-ulang: Admin.
 *
 * Data Accurate hanya berstatus final setelah "verified". Modul ini tidak mengubah
 * stok varian; penerapan stok awal adalah langkah terpisah.
 */
class AccurateMigrationController extends Controller
{
    public function index(Request $request)
    {
        $batches = AccurateImportBatch::query()
            ->with('uploader:id,name')
            ->withCount([
                'items as unmapped_count' => fn ($q) => $q->where('mapping_status', AccurateItem::STATUS_UNMAPPED),
                'items as mapped_count'   => fn ($q) => $q->where('mapping_status', AccurateItem::STATUS_MAPPED),
                'items as verified_count' => fn ($q) => $q->where('mapping_status', AccurateItem::STATUS_VERIFIED),
                'items as rejected_count' => fn ($q) => $q->where('mapping_status', AccurateItem::STATUS_REJECTED),
            ])
            ->latest('id')
            ->paginate(10);

        $counts = DB::table('accurate_items')
            ->selectRaw('mapping_status, COUNT(*) AS total')
            ->groupBy('mapping_status')
            ->pluck('total', 'mapping_status');

        $summary = [
            'total'    => (int) $counts->sum(),
            'unmapped' => (int) ($counts[AccurateItem::STATUS_UNMAPPED] ?? 0),
            'mapped'   => (int) ($counts[AccurateItem::STATUS_MAPPED] ?? 0),
            'verified' => (int) ($counts[AccurateItem::STATUS_VERIFIED] ?? 0),
            'rejected' => (int) ($counts[AccurateItem::STATUS_REJECTED] ?? 0),
        ];

        return view('pages.accurate.index', compact('batches', 'summary'));
    }

    public function template()
    {
        return response(AccurateImporter::templateCsv(), 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-impor-accurate.csv"',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'file.required' => 'Pilih file CSV hasil ekspor Accurate.',
            'file.file'     => 'File gagal diunggah. Coba lagi.',
            'file.mimes'    => 'File harus berformat CSV.',
            'file.max'      => 'Ukuran file maksimal 2 MB.',
        ]);

        $file = $request->file('file');

        try {
            $result = AccurateImporter::import(
                $file->getRealPath(),
                $file->getClientOriginalName(),
                $this->actor()->id
            );
        } catch (AccurateImportException $e) {
            return redirect()
                ->route('accurate.index')
                ->with('error', $e->getMessage())
                ->with('import_errors', array_slice($e->errors, 0, 20));
        }

        $message = number_format($result['imported'], 0, ',', '.') . ' item Accurate berhasil diimpor sebagai batch #' . $result['batch']->id . '.';

        if ($result['failed'] > 0) {
            $message .= ' ' . number_format($result['failed'], 0, ',', '.') . ' baris ditolak, lihat laporan kesalahan.';
        }

        return redirect()
            ->route('accurate.batches.show', $result['batch']->id)
            ->with('success', $message);
    }

    public function show(Request $request, $id)
    {
        $batch = AccurateImportBatch::query()->with('uploader:id,name')->findOrFail($id);

        $validated = $request->validate([
            'status'   => ['nullable', Rule::in(array_keys(AccurateItem::STATUSES))],
            'q'        => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
        ]);

        $q = trim($validated['q'] ?? '');
        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = AccurateItem::query()
            ->with(['variant:id,product_id,sku,name,unit,status', 'variant.product:id,name'])
            ->where('batch_id', $batch->id);

        if (! empty($validated['status'])) {
            $query->where('mapping_status', $validated['status']);
        }

        if ($q !== '') {
            $like = '%' . mb_strtolower(addcslashes($q, '%_\\')) . '%';
            $query->where(function ($sub) use ($like) {
                $sub->whereRaw('lower(accurate_code) like ?', [$like])
                    ->orWhereRaw('lower(accurate_name) like ?', [$like]);
            });
        }

        $items = $query->orderBy('row_number')->paginate($perPage)->withQueryString();

        $statusCounts = AccurateItem::query()
            ->where('batch_id', $batch->id)
            ->selectRaw('mapping_status, COUNT(*) AS total')
            ->groupBy('mapping_status')
            ->pluck('total', 'mapping_status');

        $variants = DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.status', 'active')
            ->whereNull('v.merged_into_variant_id')
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->get(['v.id', 'v.sku', 'v.name', 'v.unit', 'p.name as product_name'])
            ->map(fn ($v) => [
                'id'    => (string) $v->id,
                'label' => $v->product_name . ' - ' . $v->name . ' (' . $v->sku . ') · ' . $v->unit,
            ])
            ->values();

        // Saran pemetaan: varian dengan SKU sama persis dengan kode Accurate (tidak otomatis dipetakan).
        $codes = $items->getCollection()
            ->where('mapping_status', AccurateItem::STATUS_UNMAPPED)
            ->map(fn ($i) => mb_strtolower($i->accurate_code))
            ->unique()
            ->values()
            ->all();

        $suggestions = $codes === []
            ? []
            : DB::table('product_variants')
                ->selectRaw('lower(sku) AS sku_key, MIN(id) AS variant_id')
                ->whereIn(DB::raw('lower(sku)'), $codes)
                ->where('status', 'active')
                ->whereNull('merged_into_variant_id')
                ->groupBy(DB::raw('lower(sku)'))
                ->pluck('variant_id', 'sku_key')
                ->map(fn ($id) => (string) $id)
                ->all();

        $products = $request->user()->isAdmin()
            ? DB::table('products')->orderBy('name')->get(['id', 'name'])
                ->map(fn ($p) => ['id' => (string) $p->id, 'name' => $p->name])->values()
            : collect();

        return view('pages.accurate.show', compact(
            'batch', 'items', 'statusCounts', 'variants', 'suggestions', 'products', 'perPage'
        ));
    }

    /** Petakan item ke varian yang sudah ada. */
    public function map(Request $request, $id)
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
        ], [
            'variant_id.required' => 'Pilih varian tujuan pemetaan.',
            'variant_id.integer'  => 'Varian tidak valid.',
        ]);

        $userId = $this->actor()->id;

        $item = DB::transaction(function () use ($id, $validated, $userId) {
            $item = $this->lockItem($id, [AccurateItem::STATUS_UNMAPPED, AccurateItem::STATUS_MAPPED], 'dipetakan');

            $variant = ProductVariant::query()->find($validated['variant_id']);

            if (! $variant || $variant->status !== 'active' || $variant->merged_into_variant_id !== null) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Varian tujuan tidak ditemukan atau tidak aktif.',
                ]);
            }

            $this->markMapped($item, $variant->id, $userId);

            return $item;
        });

        return back()->with('success', 'Item "' . $item->accurate_code . '" dipetakan dan menunggu verifikasi.');
    }

    /** Buat produk/varian baru dari data Accurate, lalu petakan (khusus Admin). */
    public function createVariant(Request $request, $id)
    {
        $request->merge([
            'sku'  => trim((string) $request->input('sku')),
            'unit' => trim((string) $request->input('unit')),
        ]);

        $validated = $request->validate([
            'product_id'    => ['nullable', 'integer', 'exists:products,id'],
            'product_name'  => ['required_without:product_id', 'nullable', 'string', 'max:255'],
            'variant_name'  => ['required', 'string', 'max:255'],
            'sku'           => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')],
            'unit'          => ['required', 'string', 'max:50'],
            'selling_price' => ['required', 'numeric', 'min:0'],
        ], [
            'product_id.exists'             => 'Produk yang dipilih tidak ditemukan.',
            'product_name.required_without' => 'Isi nama produk baru atau pilih produk yang sudah ada.',
            'variant_name.required'         => 'Nama varian wajib diisi.',
            'sku.required'                  => 'SKU wajib diisi.',
            'sku.unique'                    => 'SKU sudah digunakan oleh varian lain.',
            'unit.required'                 => 'Satuan wajib diisi.',
            'selling_price.required'        => 'Harga jual wajib diisi.',
            'selling_price.numeric'         => 'Harga jual harus berupa angka.',
            'selling_price.min'             => 'Harga jual tidak boleh negatif.',
        ]);

        $userId = $this->actor()->id;

        $variant = DB::transaction(function () use ($id, $validated, $userId) {
            $item = $this->lockItem($id, [AccurateItem::STATUS_UNMAPPED, AccurateItem::STATUS_MAPPED], 'dipetakan');

            $productId = $validated['product_id'] ?? null;

            if (! $productId) {
                $productId = Product::create([
                    'name'       => trim($validated['product_name']),
                    'category'   => $item->accurate_category,
                    'status'     => 'active',
                    'created_by' => $userId,
                ])->id;
            }

            // Stok sengaja 0: stok awal baru diterapkan setelah verifikasi, bukan saat membuat varian.
            $variant = ProductVariant::create([
                'product_id'    => $productId,
                'sku'           => $validated['sku'],
                'name'          => trim($validated['variant_name']),
                'unit'          => $validated['unit'],
                'selling_price' => $validated['selling_price'],
                'current_stock' => 0,
                'reorder_point' => 0,
                'status'        => 'active',
            ]);

            $this->markMapped($item, $variant->id, $userId);

            return $variant;
        });

        return back()->with('success', 'Varian "' . $variant->name . '" dibuat dan dipetakan. Menunggu verifikasi.');
    }

    public function unmap($id)
    {
        $item = DB::transaction(function () use ($id) {
            $item = $this->lockItem($id, [AccurateItem::STATUS_MAPPED], 'dibatalkan pemetaannya');
            $this->resetToUnmapped($item);

            return $item;
        });

        return back()->with('success', 'Pemetaan item "' . $item->accurate_code . '" dibatalkan.');
    }

    public function verify($id)
    {
        $userId = $this->actor()->id;

        $item = DB::transaction(function () use ($id, $userId) {
            $item = $this->lockItem($id, [AccurateItem::STATUS_MAPPED], 'diverifikasi');
            $this->assertVariantUsable($item);

            $item->update([
                'mapping_status' => AccurateItem::STATUS_VERIFIED,
                'verified_by'    => $userId,
                'verified_at'    => now(),
            ]);

            return $item;
        });

        return back()->with('success', 'Item "' . $item->accurate_code . '" terverifikasi.');
    }

    public function verifyBulk(Request $request, $batchId)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'distinct'],
        ], [
            'ids.required' => 'Pilih minimal satu item yang akan diverifikasi.',
            'ids.min'      => 'Pilih minimal satu item yang akan diverifikasi.',
            'ids.max'      => 'Maksimal 500 item per proses.',
        ]);

        $batch = AccurateImportBatch::query()->findOrFail($batchId);
        $userId = $this->actor()->id;

        [$verified, $skipped] = DB::transaction(function () use ($batch, $validated, $userId) {
            $items = AccurateItem::query()
                ->where('batch_id', $batch->id)
                ->whereIn('id', $validated['ids'])
                ->lockForUpdate()
                ->get();

            $verified = 0;
            $skipped = 0;

            foreach ($items as $item) {
                try {
                    if ($item->mapping_status !== AccurateItem::STATUS_MAPPED) {
                        throw new \DomainException('bukan berstatus menunggu verifikasi');
                    }

                    $this->assertVariantUsable($item);
                } catch (\DomainException|ValidationException) {
                    $skipped++;
                    continue;
                }

                $item->update([
                    'mapping_status' => AccurateItem::STATUS_VERIFIED,
                    'verified_by'    => $userId,
                    'verified_at'    => now(),
                ]);
                $verified++;
            }

            // ID yang bukan milik batch ini juga dihitung dilewati.
            $skipped += count($validated['ids']) - $items->count();

            return [$verified, $skipped];
        });

        $message = $verified . ' item diverifikasi.';
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' item dilewati (belum dipetakan, sudah final, atau varian tidak aktif).';
        }

        return back()->with($verified > 0 ? 'success' : 'error', $message);
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
            'reason.max'      => 'Alasan maksimal 500 karakter.',
        ]);

        $item = DB::transaction(function () use ($id, $validated) {
            $item = $this->lockItem($id, [AccurateItem::STATUS_UNMAPPED, AccurateItem::STATUS_MAPPED], 'ditolak');

            $item->update([
                'mapping_status'    => AccurateItem::STATUS_REJECTED,
                'mapped_variant_id' => null,
                'mapped_by'         => null,
                'mapped_at'         => null,
                'rejected_reason'   => trim($validated['reason']),
            ]);

            return $item;
        });

        return back()->with('success', 'Item "' . $item->accurate_code . '" ditolak dan tidak ikut dimigrasikan.');
    }

    /** Buka kembali item final/ditolak (khusus Admin). Terverifikasi -> menunggu verifikasi; ditolak -> belum dipetakan. */
    public function reopen($id)
    {
        $item = DB::transaction(function () use ($id) {
            $item = $this->lockItem($id, [AccurateItem::STATUS_VERIFIED, AccurateItem::STATUS_REJECTED], 'dibuka kembali');

            if ($item->mapping_status === AccurateItem::STATUS_VERIFIED) {
                $item->update([
                    'mapping_status' => AccurateItem::STATUS_MAPPED,
                    'verified_by'    => null,
                    'verified_at'    => null,
                ]);
            } else {
                $this->resetToUnmapped($item);
            }

            return $item;
        });

        return back()->with('success', 'Item "' . $item->accurate_code . '" dibuka kembali.');
    }

    // ------------------------------------------------------------------

    /** Ambil item dengan kunci baris, pastikan statusnya sesuai untuk aksi yang diminta. */
    private function lockItem($id, array $allowed, string $action): AccurateItem
    {
        $item = AccurateItem::query()->lockForUpdate()->findOrFail($id);

        if (! in_array($item->mapping_status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'Item "' . $item->accurate_code . '" berstatus "' . $item->statusLabel()
                    . '" sehingga tidak dapat ' . $action . '.',
            ]);
        }

        return $item;
    }

    private function markMapped(AccurateItem $item, int $variantId, int $userId): void
    {
        $item->update([
            'mapping_status'    => AccurateItem::STATUS_MAPPED,
            'mapped_variant_id' => $variantId,
            'mapped_by'         => $userId,
            'mapped_at'         => now(),
            'verified_by'       => null,
            'verified_at'       => null,
            'rejected_reason'   => null,
        ]);
    }

    private function resetToUnmapped(AccurateItem $item): void
    {
        $item->update([
            'mapping_status'    => AccurateItem::STATUS_UNMAPPED,
            'mapped_variant_id' => null,
            'mapped_by'         => null,
            'mapped_at'         => null,
            'verified_by'       => null,
            'verified_at'       => null,
            'rejected_reason'   => null,
        ]);
    }

    /** Varian hasil pemetaan harus masih aktif dan belum digabung saat diverifikasi. */
    private function assertVariantUsable(AccurateItem $item): void
    {
        $variant = $item->mapped_variant_id ? ProductVariant::query()->find($item->mapped_variant_id) : null;

        if (! $variant || $variant->status !== 'active' || $variant->merged_into_variant_id !== null) {
            throw ValidationException::withMessages([
                'status' => 'Varian hasil pemetaan item "' . $item->accurate_code
                    . '" tidak aktif atau sudah digabung. Petakan ulang sebelum verifikasi.',
            ]);
        }
    }
}