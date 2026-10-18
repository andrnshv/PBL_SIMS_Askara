<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $search = trim((string) $request->input('q', ''));
        $category = $request->input('category');

        $query = Product::query()
            ->withCount([
                'variants as variants_count' => function ($query) {
                    $query->where('status', '!=', 'merged');
                },
            ]);

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhereHas('variants', function ($variants) use ($search) {
                        $variants->where('name', 'ilike', "%{$search}%")
                            ->orWhere('sku', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($category !== null && $category !== '') {
            $query->where('category', $category);
        }

        $products = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Product::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('pages.products.index', compact(
            'products',
            'categories',
            'perPage'
        ));
    }

    public function create()
    {
        $categories = Product::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('pages.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $category = trim((string) $request->input('category')) ?: null;

        if ($category) {
            // pakai penulisan kategori yang sudah ada jika hanya beda huruf besar/kecil
            $category = Product::query()
                ->whereRaw('lower(category) = ?', [mb_strtolower($category)])
                ->value('category') ?? $category;
        }

        $request->merge([
            'name'     => trim((string) $request->input('name')),
            'category' => $category,
        ]);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('products', 'name')],
            'category'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'name.required'   => 'Nama produk wajib diisi.',
            'name.max'        => 'Nama produk maksimal 255 karakter.',
            'name.unique'     => 'Nama produk sudah terdaftar.',
            'category.max'    => 'Kategori maksimal 255 karakter.',
            'status.required' => 'Status produk wajib dipilih.',
            'status.in'       => 'Status produk tidak valid.',
        ]);

        // Sementara: belum ada login, pakai user pertama di tabel users.
        // Saat auth sudah jadi, ganti dengan $request->user()->id
        $validated['created_by'] = $request->user()?->id
            ?? User::query()->value('id');

        if (! $validated['created_by']) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'Belum ada data user. Buat user terlebih dahulu.']);
        }

        $product = Product::create($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk "' . $product->name . '" berhasil ditambahkan.');
    }

    public function show($id)
    {
        $product = DB::table('products as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
            ->where('p.id', $id)
            ->select(
                'p.*',
                'u.name as creator_name'
            )
            ->first();

        abort_if(!$product, 404);

        $variants = DB::table('product_variants')
            ->where('product_id', $id)
            ->orderBy('name')
            ->get();

        return view('pages.products.show', compact(
            'product',
            'variants'
        ));
    }

        public function createVariant($id)
    {
        $product = Product::findOrFail($id);
        $units = $this->unitOptions();

        return view('pages.products.variants.create', compact('product', 'units'));
    }

    public function storeVariant(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->merge([
            'unit' => $this->normalizeUnit((string) $request->input('unit')),
        ]);

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_point' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'sku.required' => 'SKU wajib diisi.',
            'sku.unique' => 'SKU sudah digunakan oleh varian lain.',
            'name.required' => 'Nama varian wajib diisi.',
            'unit.required' => 'Satuan wajib diisi.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'selling_price.numeric' => 'Harga jual harus berupa angka.',
            'selling_price.min' => 'Harga jual tidak boleh negatif.',
            'reorder_point.required' => 'Reorder point wajib diisi.',
            'reorder_point.integer' => 'Reorder point harus berupa bilangan bulat.',
            'reorder_point.min' => 'Reorder point tidak boleh negatif.',
            'status.required' => 'Status varian wajib dipilih.',
            'status.in' => 'Status varian tidak valid.',
        ]);

        $now = now();

        DB::table('product_variants')->insert([
            'product_id' => $product->id,
            'sku' => trim($validated['sku']),
            'name' => trim($validated['name']),
            'unit' => trim($validated['unit']),
            'selling_price' => $validated['selling_price'],
            'current_stock' => 0,
            'reorder_point' => $validated['reorder_point'],
            'status' => $validated['status'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return redirect()
            ->route('products.show', $product->id)
            ->with('success', 'Varian "' . $validated['name'] . '" berhasil ditambahkan.');
    }

    public function editVariant($id, $variantId)
    {
        $product = DB::table('products')
            ->where('id', $id)
            ->first();

        abort_if(!$product, 404);

        $variant = DB::table('product_variants')
            ->where('id', $variantId)
            ->where('product_id', $id)
            ->first();

        abort_if(!$variant, 404);

        // Varian yang sudah digabung tidak dapat diedit.
        abort_if($variant->status === 'merged', 404);

        return view('pages.products.variants.edit', [
            'product' => $product,
            'variant' => $variant,
            'units'   => $this->unitOptions(),
        ]);
    }

    public function updateVariant(Request $request, $id, $variantId)
    {
        $product = DB::table('products')
            ->where('id', $id)
            ->first();

        abort_if(!$product, 404);

        $variant = DB::table('product_variants')
            ->where('id', $variantId)
            ->where('product_id', $id)
            ->first();

        abort_if(!$variant, 404);

        // Varian yang sudah digabung tidak dapat diedit.
        abort_if($variant->status === 'merged', 404);

        $request->merge([
            'unit' => $this->normalizeUnit((string) $request->input('unit')),
        ]);

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')->ignore($variantId, 'id'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_point' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        // Perbarui data tanpa mengubah current_stock.
        DB::table('product_variants')
            ->where('id', $variantId)
            ->where('product_id', $id)
            ->update([
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'selling_price' => $validated['selling_price'],
                'reorder_point' => $validated['reorder_point'],
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('products.show', $id)
            ->with('success', 'Varian produk berhasil diperbarui.');
    }

    private function unitOptions()
    {
        return ProductVariant::query()
            ->whereNotNull('unit')
            ->where('unit', '!=', '')
            ->distinct()
            ->orderBy('unit')
            ->pluck('unit')
            ->unique(fn ($u) => mb_strtolower($u))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    // Samakan penulisan dengan satuan yang sudah ada (beda huruf besar/kecil saja)
    private function normalizeUnit(string $unit): string
    {
        $unit = trim($unit);

        return ProductVariant::query()
            ->whereRaw('lower(unit) = ?', [mb_strtolower($unit)])
            ->value('unit') ?? $unit;
    }

}
