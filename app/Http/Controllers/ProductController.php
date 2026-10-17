<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $search = trim($request->input('q', ''));
        $category = $request->input('category');

        $query = Product::query()
            ->withCount([
                'variants as variants_count' => function ($query) {
                    $query->where('status', '!=', 'merged');
                },
            ]);

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('variants', function ($variants) use ($search) {
                        $variants->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
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
        return view('pages.products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'name.max' => 'Nama produk maksimal 255 karakter.',
            'category.max' => 'Kategori maksimal 255 karakter.',
            'status.required' => 'Status produk wajib dipilih.',
            'status.in' => 'Status produk tidak valid.',
        ]);

        $validated['created_by'] = $request->user()->id;

        $product = Product::create($validated);

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Produk "' . $product->name . '" berhasil ditambahkan.'
            );
    }
}
