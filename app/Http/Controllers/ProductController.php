<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $categories = $this->categoryOptions();

        return view('pages.products.index', compact(
            'products',
            'categories',
            'perPage'
        ));
    }

    public function create()
    {
        $categories = $this->categoryOptions();

        return view('pages.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'name'     => trim((string) $request->input('name')),
            'category' => $this->normalizeCategory($request->input('category')),
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
        $validated['created_by'] = $request->user()->id;

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
            ->select('p.*', 'u.name as creator_name')
            ->first();

        abort_if(!$product, 404);

        $variants = DB::table('product_variants')
            ->where('product_id', $id)
            ->orderBy('name')
            ->get();

        return view('pages.products.show', compact('product', 'variants'));
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = $this->categoryOptions();

        return view('pages.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->merge([
            'name'     => trim((string) $request->input('name')),
            'category' => $this->normalizeCategory($request->input('category')),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')->ignore($product->id),
            ],
            'category'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'name.required'   => 'Nama produk wajib diisi.',
            'name.unique'     => 'Nama produk sudah digunakan.',
            'name.max'        => 'Nama produk maksimal 255 karakter.',
            'category.max'    => 'Kategori maksimal 255 karakter.',
            'status.required' => 'Status produk wajib dipilih.',
            'status.in'       => 'Status produk tidak valid.',
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.show', $product->id)
            ->with('success', 'Produk "' . $product->name . '" berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->hasTransactionHistory()) {
            return redirect()
                ->route('products.index')
                ->with(
                    'error',
                    'Produk "' . $product->name . '" tidak dapat dihapus karena variannya sudah memiliki stok atau riwayat transaksi. Ubah statusnya menjadi Tidak Aktif lewat menu Edit.'
                );
        }

        $name = $product->name;

        // Varian ikut terhapus otomatis (cascade di database).
        DB::transaction(function () use ($product) {
            $product->delete();
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk "' . $name . '" berhasil dihapus.');
    }

    // Daftar kategori yang sudah dipakai
    private function categoryOptions()
    {
        return Product::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    // Kosong jadi null; samakan huruf besar/kecil dengan kategori yang sudah ada
    private function normalizeCategory($category): ?string
    {
        $category = trim((string) $category) ?: null;

        if ($category === null) {
            return null;
        }

        return Product::query()
            ->whereRaw('lower(category) = ?', [mb_strtolower($category)])
            ->value('category') ?? $category;
    }
}