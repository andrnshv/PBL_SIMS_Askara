<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function create($id)
    {
        $product = Product::findOrFail($id);
        $units = $this->unitOptions();

        return view('pages.products.variants.create', compact('product', 'units'));
    }

    public function store(Request $request, $id)
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

    public function edit($id, $variantId)
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

    public function update(Request $request, $id, $variantId)
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

    // Daftar satuan yang sudah dipakai di database
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