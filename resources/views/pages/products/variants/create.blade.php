
@extends('layouts.app')

@section('title', 'Tambah Varian Produk')

@section('content')
<div class="min-h-screen bg-[#EEF2FB] px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Header --}}
        <div>
            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <a href="{{ route('products.index') }}" class="hover:text-teal-700">
                    Produk
                </a>
                <span>/</span>
                <a href="{{ route('products.show', $product->id) }}" class="hover:text-teal-700">
                    {{ $product->name }}
                </a>
                <span>/</span>
                <span class="font-medium text-[#0A1E45]">Tambah Varian</span>
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-[#0A1E45]">
                Tambah Varian Produk
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Daftarkan SKU, harga jual, dan pengaturan stok untuk produk ini.
            </p>
        </div>

        {{-- Produk induk --}}
        <div class="flex items-start gap-4 rounded-xl border border-teal-100 bg-teal-50 p-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-teal-700 shadow-sm">
                <svg class="h-6 w-6" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 3 9 4.5v9L12 21l-9-4.5v-9L12 3Z"/>
                    <path d="m3.5 7.8 8.5 4.4 8.5-4.4M12 12.2V21"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">
                    Produk Induk
                </p>
                <p class="mt-1 text-base font-bold text-[#0A1E45]">
                    {{ $product->name }}
                </p>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $product->category ?: 'Belum ada kategori' }}
                </p>
            </div>
        </div>

        {{-- Error validasi --}}
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-700">
                    Periksa kembali data yang kamu masukkan.
                </p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form action="{{ route('products.variants.store', $product->id) }}"
              method="POST"
              class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @csrf

            <div class="border-b border-slate-100 px-5 py-5 sm:px-7">
                <h2 class="font-bold text-[#0A1E45]">Informasi Varian</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Field bertanda * wajib diisi.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 px-5 py-6 sm:grid-cols-2 sm:px-7">

                {{-- SKU --}}
                <div>
                    <label for="sku" class="mb-2 block text-sm font-semibold text-slate-700">
                        SKU <span class="text-red-500">*</span>
                    </label>
                    <input id="sku" name="sku" type="text"
                           value="{{ old('sku') }}"
                           maxlength="255" required
                           placeholder="Contoh: KPS-001"
                           class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15">
                    <p class="mt-1.5 text-xs text-slate-400">
                        SKU harus unik untuk setiap varian.
                    </p>
                </div>

                {{-- Nama varian --}}
                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">
                        Nama Varian <span class="text-red-500">*</span>
                    </label>
                    <input id="name" name="name" type="text"
                           value="{{ old('name') }}"
                           maxlength="255" required
                           placeholder="Contoh: Kipas 12cm Putih"
                           class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15">
                </div>

                {{-- Satuan --}}
                <div>
                    <label for="unit" class="mb-2 block text-sm font-semibold text-slate-700">
                        Satuan <span class="text-red-500">*</span>
                    </label>
                    <input id="unit" name="unit" type="text"
                           value="{{ old('unit', 'unit') }}"
                           maxlength="50" required
                           placeholder="Contoh: unit, pcs, box"
                           class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15">
                </div>

                {{-- Harga --}}
                <div>
                    <label for="selling_price" class="mb-2 block text-sm font-semibold text-slate-700">
                        Harga Jual (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input id="selling_price" name="selling_price" type="number"
                           value="{{ old('selling_price', 0) }}"
                           min="0" step="0.01" required
                           placeholder="150000"
                           class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15">
                </div>

                {{-- Reorder point --}}
                <div>
                    <label for="reorder_point" class="mb-2 block text-sm font-semibold text-slate-700">
                        Reorder Point <span class="text-red-500">*</span>
                    </label>
                    <input id="reorder_point" name="reorder_point" type="number"
                           value="{{ old('reorder_point', 0) }}"
                           min="0" step="1" required
                           class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15">
                    <p class="mt-1.5 text-xs text-slate-400">
                        Batas stok untuk memicu perhatian pengisian ulang.
                    </p>
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="mb-2 block text-sm font-semibold text-slate-700">
                        Status Varian <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status" required
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-sm outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>
                            Aktif
                        </option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>
                            Tidak Aktif
                        </option>
                    </select>
                </div>
            </div>

            {{-- Stok awal --}}
            <div class="mx-5 mb-5 rounded-lg border border-blue-100 bg-blue-50/70 p-4 sm:mx-7">
                <p class="text-sm font-semibold text-[#0A1E45]">
                    Informasi stok awal
                </p>
                <p class="mt-1 text-xs leading-5 text-slate-600">
                    Varian akan dibuat dengan stok awal 0. Gunakan fitur stok masuk
                    untuk mencatat persediaan agar pergerakan stok dapat ditelusuri.
                </p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
                <a href="{{ route('products.show', $product->id) }}"
                   class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0A1E45] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#142D5C]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Simpan Varian
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
