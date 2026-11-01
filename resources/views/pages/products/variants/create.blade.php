
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
            enctype="multipart/form-data"
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

                    <div
                        x-data="{
                            open: false,
                            q: @js(old('unit', 'unit')),
                            options: @js($units),
                            picked: @js(in_array(old('unit', 'unit'), $units->all(), true)),
                            get filtered() {
                                const q = this.picked ? '' : this.q.toLowerCase();
                                return this.options.filter(o => o.toLowerCase().includes(q));
                            },
                            get isNew() {
                                return !this.picked && this.q.trim() !== '' &&
                                    !this.options.some(o => o.toLowerCase() === this.q.trim().toLowerCase());
                            },
                            choose(o) { this.q = o; this.picked = true; this.open = false; },
                            reset(el) { if (this.picked) { this.q = ''; el.value = ''; this.picked = false; } }
                        }"
                        @click.outside="open = false"
                        class="relative"
                    >
                        <input
                            type="text"
                            id="unit"
                            name="unit"
                            x-model="q"
                            @focus="open = true"
                            @input="open = true"
                            @keydown.escape="open = false"
                            @keydown="if ($event.key.length === 1 && !$event.ctrlKey && !$event.metaKey && !$event.altKey || ['Backspace','Delete'].includes($event.key)) reset($el)"
                            @paste="reset($el)"
                            autocomplete="off"
                            maxlength="50"
                            required
                            placeholder="Pilih satuan atau ketik satuan baru"
                            class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15"
                        >

                        <ul x-show="open" x-cloak
                            class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 text-sm shadow-lg">
                            <template x-for="o in filtered" :key="o">
                                <li @mousedown.prevent="choose(o)" x-text="o"
                                    class="cursor-pointer px-3.5 py-2.5 text-slate-700 hover:bg-blue-50"></li>
                            </template>

                            <li x-show="isNew" @mousedown.prevent="open = false"
                                x-text="'+ Buat satuan baru: ' + q"
                                class="cursor-pointer border-t border-slate-100 px-3.5 py-2.5 font-medium text-[#4C8DF6] hover:bg-blue-50"></li>
                        </ul>
                    </div>

                    <p class="mt-1.5 text-xs text-slate-400">
                        Pilih satuan yang ada atau ketik untuk membuat satuan baru.
                    </p>
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
                
                {{-- Foto varian --}}
                <div class="sm:col-span-2" x-data="{ preview: null }">
                    <label for="image" class="mb-2 block text-sm font-semibold text-slate-700">
                        Foto Varian <span class="text-xs font-normal text-slate-400">(opsional)</span>
                    </label>

                    <div class="flex items-center gap-4">
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50">
                            <img x-show="preview" x-cloak :src="preview" alt="Pratinjau foto"
                                class="h-full w-full object-cover">
                            <svg x-show="!preview" class="h-8 w-8 text-slate-300" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <path d="m21 15-5-5L5 21"/>
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <input id="image" name="image" type="file"
                                accept="image/png,image/jpeg,image/webp"
                                @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : null"
                                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                            <p class="mt-1.5 text-xs text-slate-400">
                                Format JPG, PNG, atau WEBP. Maksimal 2 MB.
                            </p>
                        </div>
                    </div>
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
