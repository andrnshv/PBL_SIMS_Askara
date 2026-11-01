@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <x-ui.breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Produk', 'url' => route('products.index')],
                    ['label' => $product->name, 'url' => route('products.show', $product->id)],
                    ['label' => 'Edit Varian'],
                ]" />
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
                enctype="multipart/form-data"
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

                    <div
                        x-data="{
                            open: false,
                            q: @js(old('unit', $variant->unit)),
                            options: @js($units),
                            picked: @js(in_array(old('unit', $variant->unit), $units->all(), true)),
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
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
                        >

                        <ul x-show="open" x-cloak
                            class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-gray-200 bg-white py-1 text-sm shadow-lg">
                            <template x-for="o in filtered" :key="o">
                                <li @mousedown.prevent="choose(o)" x-text="o"
                                    class="cursor-pointer px-4 py-2.5 text-gray-700 hover:bg-teal-50"></li>
                            </template>

                            <li x-show="isNew" @mousedown.prevent="open = false"
                                x-text="'+ Buat satuan baru: ' + q"
                                class="cursor-pointer border-t border-gray-100 px-4 py-2.5 font-medium text-teal-700 hover:bg-teal-50"></li>
                        </ul>
                    </div>

                    <p class="mt-1.5 text-xs text-gray-500">
                        Pilih satuan yang ada atau ketik untuk membuat satuan baru.
                    </p>
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

                {{-- Foto varian --}}
                <div
                    x-data="{
                        current: @js(\App\Models\ProductVariant::imageUrl($variant->image_path)),
                        preview: null,
                        remove: false,
                        get src() { return this.preview ?? (this.remove ? null : this.current); }
                    }"
                >
                    <label for="image" class="mb-2 block text-sm font-medium text-gray-700">
                        Foto Varian <span class="text-xs font-normal text-gray-400">(opsional)</span>
                    </label>

                    <div class="flex items-center gap-4">
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-gray-300 bg-gray-50">
                            <img x-show="src" x-cloak :src="src" alt="Foto varian"
                                class="h-full w-full object-cover">
                            <svg x-show="!src" class="h-8 w-8 text-gray-300" viewBox="0 0 24 24"
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
                                @change="
                                    const f = $event.target.files[0];
                                    preview = f ? URL.createObjectURL(f) : null;
                                    if (f) remove = false;
                                "
                                class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200">

                            <p class="mt-1.5 text-xs text-gray-500">
                                Format JPG, PNG, atau WEBP. Maksimal 2 MB. Pilih file baru untuk mengganti foto.
                            </p>

                            <label x-show="current && !preview" x-cloak
                                class="mt-2 inline-flex items-center gap-2 text-sm text-red-600">
                                <input type="checkbox" name="remove_image" value="1" x-model="remove"
                                    class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                Hapus foto saat ini
                            </label>
                        </div>
                    </div>
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
