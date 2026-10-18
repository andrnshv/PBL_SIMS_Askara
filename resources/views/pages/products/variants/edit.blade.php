@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-teal-700">
                    Manajemen Produk
                </p>

                <h1 class="mt-1 text-2xl font-bold text-gray-900">
                    Edit Varian Produk
                </h1>

                <p class="mt-2 text-sm text-gray-500">
                    Perbarui informasi varian dari produk
                    {{ $product->name }}.
                </p>
            </div>

            <a
                href="{{ route('products.show', $product->id) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
            >
                Kembali
            </a>
        </div>

        {{-- Error Validasi --}}
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="font-semibold text-red-800">
                    Periksa kembali data yang kamu masukkan.
                </p>

                <ul class="mt-2 list-inside list-disc text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form Edit --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Informasi Varian
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    SKU: {{ $variant->sku }}
                </p>
            </div>

            <form
                action="{{ route('products.variants.update', [
                    'id' => $product->id,
                    'variantId' => $variant->id,
                ]) }}"
                method="POST"
                class="space-y-6 p-6"
            >
                @csrf
                @method('PUT')

                {{-- SKU --}}
                <div>
                    <label for="sku" class="mb-2 block text-sm font-medium text-gray-700">
                        SKU <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="sku"
                        name="sku"
                        value="{{ old('sku', $variant->sku) }}"
                        required
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
                    >
                </div>

                {{-- Nama Varian --}}
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-gray-700">
                        Nama Varian <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $variant->name) }}"
                        required
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
                    >
                </div>

                {{-- Satuan --}}
                <div>
                    <label for="unit" class="mb-2 block text-sm font-medium text-gray-700">
                        Satuan <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="unit"
                        name="unit"
                        value="{{ old('unit', $variant->unit) }}"
                        required
                        placeholder="Contoh: pcs, box, kg"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
                    >
                </div>

                {{-- Harga Jual --}}
                <div>
                    <label for="selling_price" class="mb-2 block text-sm font-medium text-gray-700">
                        Harga Jual <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="number"
                        id="selling_price"
                        name="selling_price"
                        value="{{ old('selling_price', $variant->selling_price) }}"
                        min="0"
                        step="0.01"
                        required
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
                    >
                </div>

                {{-- Reorder Point --}}
                <div>
                    <label for="reorder_point" class="mb-2 block text-sm font-medium text-gray-700">
                        Reorder Point <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="number"
                        id="reorder_point"
                        name="reorder_point"
                        value="{{ old('reorder_point', $variant->reorder_point) }}"
                        min="0"
                        step="1"
                        required
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
                    >

                    <p class="mt-1 text-xs text-gray-500">
                        Batas stok untuk menentukan kapan produk perlu diisi ulang.
                    </p>
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="mb-2 block text-sm font-medium text-gray-700">
                        Status <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="status"
                        name="status"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
                    >
                        <option
                            value="active"
                            {{ old('status', $variant->status) === 'active' ? 'selected' : '' }}
                        >
                            Aktif
                        </option>

                        <option
                            value="inactive"
                            {{ old('status', $variant->status) === 'inactive' ? 'selected' : '' }}
                        >
                            Tidak Aktif
                        </option>
                    </select>
                </div>

                {{-- Informasi Stok --}}
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                    <p class="text-sm font-semibold text-blue-900">
                        Informasi Stok
                    </p>

                    <p class="mt-1 text-sm text-blue-800">
                        Stok saat ini:
                        <strong>{{ $variant->current_stock }}</strong>
                        {{ $variant->unit }}.
                    </p>

                    <p class="mt-1 text-xs text-blue-700">
                        Untuk menjaga riwayat persediaan, perubahan stok dilakukan melalui fitur stok masuk atau penyesuaian stok.
                    </p>
                </div>

                {{-- Tombol --}}
                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a
                        href="{{ route('products.show', $product->id) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
