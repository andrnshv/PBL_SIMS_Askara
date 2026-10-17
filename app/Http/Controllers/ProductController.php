<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
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
}