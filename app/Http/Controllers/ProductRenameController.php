<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRenameLog;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductRenameController extends Controller
{
    // Ubah nama PRODUK INDUK
    public function product(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255',
                         Rule::unique('products', 'name')->ignore($product->id)],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama baru wajib diisi.',
            'name.unique'   => 'Nama produk sudah digunakan.',
            'name.max'      => 'Nama produk maksimal 255 karakter.',
            'reason.max'    => 'Alasan maksimal 255 karakter.',
        ]);

        $oldName = $product->name;

        if ($validated['name'] === $oldName) {
            return back()->with('error', 'Nama baru sama dengan nama saat ini.');
        }

        // Update nama + catat log harus berhasil bersamaan (atau batal bersamaan)
        DB::transaction(function () use ($product, $validated, $oldName, $request) {
            $product->update(['name' => $validated['name']]);

            ProductRenameLog::create([
                'entity_type' => 'product',
                'product_id'  => $product->id,
                'variant_id'  => null,
                'old_name'    => $oldName,
                'new_name'    => $validated['name'],
                'reason'      => $validated['reason'] ?? null,
                'changed_by'  => $request->user()->id,
                'changed_at'  => now(),
            ]);
        });

        return redirect()->route('products.show', $product->id)
            ->with('success', 'Nama produk diubah dari "' . $oldName . '" menjadi "' . $validated['name'] . '".');
    }

    // Ubah nama VARIAN
    public function variant(Request $request, $id, $variantId)
    {
        $variant = ProductVariant::where('id', $variantId)
            ->where('product_id', $id)
            ->firstOrFail();

        if ($variant->isMerged()) {
            return back()->with('error', 'Varian yang sudah digabung tidak dapat diubah namanya.');
        }

        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255',
                         // nama varian tidak boleh kembar dalam produk yang sama
                         Rule::unique('product_variants', 'name')
                             ->where('product_id', $id)
                             ->ignore($variant->id)],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama baru wajib diisi.',
            'name.unique'   => 'Nama varian sudah dipakai di produk ini.',
        ]);

        $oldName = $variant->name;

        if ($validated['name'] === $oldName) {
            return back()->with('error', 'Nama baru sama dengan nama saat ini.');
        }

        DB::transaction(function () use ($variant, $validated, $oldName, $request, $id) {
            $variant->update(['name' => $validated['name']]);

            ProductRenameLog::create([
                'entity_type' => 'variant',
                'product_id'  => $id,          // diisi juga supaya mudah ditampilkan di halaman produk
                'variant_id'  => $variant->id,
                'old_name'    => $oldName,
                'new_name'    => $validated['name'],
                'reason'      => $validated['reason'] ?? null,
                'changed_by'  => $request->user()->id,
                'changed_at'  => now(),
            ]);
        });

        return redirect()->route('products.show', $id)
            ->with('success', 'Nama varian diubah dari "' . $oldName . '" menjadi "' . $validated['name'] . '".');
    }
}