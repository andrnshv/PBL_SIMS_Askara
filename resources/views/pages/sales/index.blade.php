@extends('layouts.app')

@section('content')
<div x-data="salesPage(@js($products))" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                Transaksi Penjualan
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Pilih produk dan kelola keranjang penjualan.
            </p>
        </div>

        <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Total item
            </span>
            <span
                class="rounded-lg bg-blue-50 px-3 py-1 text-sm font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"
                x-text="totalQty"
            ></span>
        </div>
    </div>

    {{-- Notifikasi berhasil --}}
    @if (session('success'))
        <div
            role="status"
            class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-500/10 dark:text-green-300"
        >
            {{ session('success') }}
        </div>
    @endif

    {{-- Notifikasi validasi gagal --}}
    @if ($errors->any())
        <div
            role="alert"
            class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-500/10 dark:text-red-300"
        >
            <p class="font-semibold">
                Transaksi belum berhasil diproses.
            </p>

            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main layout --}}
    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-12">

        {{-- Daftar produk --}}
        <section class="min-w-0 space-y-5 xl:col-span-8">

            {{-- Pencarian dan filter kategori --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 sm:p-5">
                <label
                    for="product-search"
                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    Cari produk
                </label>

                <div class="relative">
                    <svg
                        class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true"
                    >
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-4-4"></path>
                    </svg>

                    <input
                        id="product-search"
                        type="text"
                        x-model="search"
                        placeholder="Cari nama produk, varian, atau SKU..."
                        class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-10 pr-4 text-sm text-gray-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <template x-for="item in categories" :key="item">
                        <button
                            type="button"
                            @click="category = item"
                            class="rounded-full border px-4 py-2 text-sm font-medium transition"
                            :class="category === item
                                ? 'border-blue-700 bg-blue-700 text-white'
                                : 'border-gray-200 bg-white text-gray-600 hover:border-blue-300 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
                            x-text="item"
                        ></button>
                    </template>
                </div>
            </div>

            {{-- Jumlah hasil --}}
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white">
                    Daftar Produk
                </h2>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <span x-text="filtered.length"></span> produk
                </p>
            </div>

            {{-- Kartu produk --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                <template x-for="product in filtered" :key="product.id">
                    <article class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white transition hover:border-blue-300 hover:shadow-sm dark:border-gray-800 dark:bg-gray-900">

                        {{-- Gambar atau placeholder --}}
                        <div class="relative flex h-40 items-center justify-center bg-gray-50 p-4 dark:bg-gray-800/60">
                            <template x-if="product.image">
                                <img
                                    :src="product.image"
                                    :alt="product.name"
                                    class="h-full w-full object-contain"
                                    x-on:error="$el.style.display = 'none'; $el.nextElementSibling.classList.remove('hidden')"
                                >
                            </template>

                            <div
                                class="text-center"
                                :class="product.image ? 'hidden' : ''"
                            >
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                    <svg
                                        class="h-7 w-7"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                        aria-hidden="true"
                                    >
                                        <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                                        <circle cx="8.5" cy="9" r="1.5"></circle>
                                        <path d="m21 15-5-5L5 20"></path>
                                    </svg>
                                </div>
                                <p class="mt-2 text-xs text-gray-400">
                                    Gambar produk
                                </p>
                            </div>

                            {{-- Status stok --}}
                            <span
                                class="absolute right-3 top-3 rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="{
                                    'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-300': status(product) === 'ok',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300': status(product) === 'low',
                                    'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300': status(product) === 'out'
                                }"
                                x-text="status(product) === 'out'
                                    ? 'Habis'
                                    : (status(product) === 'low' ? 'Stok rendah' : 'Tersedia')"
                            ></span>
                        </div>

                        {{-- Informasi produk --}}
                        <div class="flex flex-1 flex-col p-4">
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-400"
                                x-text="product.sku"
                            ></p>

                            <h3
                                class="mt-1 font-semibold text-gray-800 dark:text-white"
                                x-text="product.name"
                            ></h3>

                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                x-text="product.variant"
                            ></p>

                            <div class="mt-3 flex items-end justify-between gap-2">
                                <div>
                                    <p
                                        class="text-lg font-bold text-gray-900 dark:text-white"
                                        x-text="rupiah(product.price)"
                                    ></p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Stok:
                                        <span x-text="product.stock"></span>
                                        <span x-text="product.unit || ''"></span>
                                    </p>
                                </div>

                                <span
                                    class="rounded-lg bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                    x-text="product.category"
                                ></span>
                            </div>

                            {{-- Tambah ke keranjang --}}
                            <button
                                type="button"
                                @click="add(product)"
                                :disabled="product.stock <= 0 || !!inCart(product.id)"
                                class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 dark:disabled:bg-gray-800"
                                :class="product.stock > 0 && !inCart(product.id)
                                    ? 'bg-[#0A1E45] text-white hover:bg-blue-900'
                                    : ''"
                            >
                                <span
                                    x-text="product.stock <= 0
                                        ? 'Stok habis'
                                        : (inCart(product.id)
                                            ? 'Sudah di keranjang'
                                            : '+ Tambah ke keranjang')"
                                ></span>
                            </button>
                        </div>
                    </article>
                </template>
            </div>

            {{-- Tidak ada hasil pencarian --}}
            <div
                x-show="filtered.length === 0"
                x-cloak
                class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-900"
            >
                <p class="font-medium text-gray-700 dark:text-gray-200">
                    Produk tidak ditemukan
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Coba kata kunci atau kategori lainnya.
                </p>
            </div>
        </section>

        {{-- Keranjang --}}
        <aside class="min-w-0 xl:col-span-4">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 xl:sticky xl:top-6">

                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-white">
                            Keranjang Penjualan
                        </h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Periksa produk sebelum diproses
                        </p>
                    </div>

                    <button
                        type="button"
                        x-show="cart.length > 0"
                        @click="clearCart()"
                        class="text-xs font-medium text-red-600 hover:text-red-700"
                    >
                        Kosongkan
                    </button>
                </div>

                {{-- Keranjang kosong --}}
                <div
                    x-show="cart.length === 0"
                    class="px-5 py-12 text-center"
                >
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                        <svg
                            class="h-7 w-7"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            aria-hidden="true"
                        >
                            <path d="M3 3h2l2.4 11.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 8H6"></path>
                            <circle cx="10" cy="20" r="1"></circle>
                            <circle cx="18" cy="20" r="1"></circle>
                        </svg>
                    </div>

                    <p class="mt-4 font-medium text-gray-700 dark:text-gray-200">
                        Keranjang masih kosong
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Pilih produk untuk memulai transaksi.
                    </p>
                </div>

                {{-- Item keranjang --}}
                <div
                    x-show="cart.length > 0"
                    class="max-h-[420px] space-y-4 overflow-y-auto p-5"
                >
                    <template x-for="item in cart" :key="item.id">
                        <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0 dark:border-gray-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3
                                        class="text-sm font-semibold text-gray-800 dark:text-white"
                                        x-text="item.name"
                                    ></h3>

                                    <p
                                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                        x-text="item.variant"
                                    ></p>

                                    <p
                                        class="mt-1 text-xs text-gray-400"
                                        x-text="item.sku"
                                    ></p>
                                </div>

                                <button
                                    type="button"
                                    @click="remove(item.id)"
                                    class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                    aria-label="Hapus produk dari keranjang"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        aria-hidden="true"
                                    >
                                        <path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3">
                                {{-- Kontrol kuantitas --}}
                                <div class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700">
                                    <button
                                        type="button"
                                        @click="dec(item)"
                                        :disabled="item.qty <= 1"
                                        class="px-3 py-1.5 text-gray-600 disabled:opacity-40 dark:text-gray-300"
                                        aria-label="Kurangi kuantitas"
                                    >
                                        −
                                    </button>

                                    <span
                                        class="min-w-8 text-center text-sm font-semibold text-gray-800 dark:text-white"
                                        x-text="item.qty"
                                    ></span>

                                    <button
                                        type="button"
                                        @click="inc(item)"
                                        :disabled="item.qty >= item.stock"
                                        class="px-3 py-1.5 text-gray-600 disabled:opacity-40 dark:text-gray-300"
                                        aria-label="Tambah kuantitas"
                                    >
                                        +
                                    </button>
                                </div>

                                <p
                                    class="text-sm font-semibold text-gray-800 dark:text-white"
                                    x-text="rupiah(item.qty * item.price)"
                                ></p>
                            </div>

                            <p
                                x-show="item.stock <= item.min"
                                class="mt-2 text-xs text-amber-600 dark:text-amber-400"
                            >
                                Perhatian: stok produk rendah.
                            </p>
                        </div>
                    </template>
                </div>

                {{-- Ringkasan dan form transaksi --}}
                <div class="space-y-4 border-t border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-800/30">

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">
                            Total jenis produk
                        </span>
                        <span
                            class="font-medium text-gray-800 dark:text-white"
                            x-text="cart.length"
                        ></span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">
                            Total kuantitas
                        </span>
                        <span
                            class="font-medium text-gray-800 dark:text-white"
                            x-text="totalQty"
                        ></span>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                        <span class="font-semibold text-gray-800 dark:text-white">
                            Subtotal
                        </span>
                        <span
                            class="text-xl font-bold text-[#0A1E45] dark:text-white"
                            x-text="rupiah(subtotal)"
                        ></span>
                    </div>

                    {{-- Form untuk menyimpan transaksi ke database --}}
                    <form
                        method="POST"
                        action="{{ route('sales.store') }}"
                        @submit="if (cart.length === 0) $event.preventDefault()"
                    >
                        @csrf

                        <div class="mb-4">
                            <label
                                for="customer_name"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                Nama pelanggan (opsional)
                            </label>

                            <input
                                id="customer_name"
                                name="customer_name"
                                type="text"
                                maxlength="255"
                                value="{{ old('customer_name') }}"
                                placeholder="Masukkan nama pelanggan"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 outline-none focus:border-blue-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        {{-- Kirim ID varian dan kuantitas --}}
                        <template x-for="(item, index) in cart" :key="item.id">
                            <div>
                                <input
                                    type="hidden"
                                    :name="`items[${index}][variant_id]`"
                                    :value="item.id"
                                >

                                <input
                                    type="hidden"
                                    :name="`items[${index}][quantity]`"
                                    :value="item.qty"
                                >
                            </div>
                        </template>

                        <button
                            type="submit"
                            :disabled="cart.length === 0"
                            class="w-full rounded-xl bg-[#0A1E45] px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-900 disabled:cursor-not-allowed disabled:bg-gray-300 dark:disabled:bg-gray-700"
                        >
                            Proses Transaksi
                        </button>

                        <p class="mt-3 text-center text-xs text-gray-400">
                            Harga dan stok akan divalidasi kembali oleh server.
                        </p>
                    </form>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function salesPage(products) {
        return {
            products: products,
            search: '',
            category: 'Semua',
            cart: [],

            get categories() {
                return [
                    'Semua',
                    ...new Set(
                        this.products.map(product => product.category)
                    )
                ];
            },

            get filtered() {
                const keyword = this.search.trim().toLowerCase();

                return this.products.filter(product => {
                    const matchesCategory =
                        this.category === 'Semua' ||
                        product.category === this.category;

                    const searchable = [
                        product.name,
                        product.variant,
                        product.sku
                    ].join(' ').toLowerCase();

                    return matchesCategory &&
                        searchable.includes(keyword);
                });
            },

            get totalQty() {
                return this.cart.reduce(
                    (sum, item) => sum + item.qty,
                    0
                );
            },

            get subtotal() {
                return this.cart.reduce(
                    (sum, item) => sum + item.qty * item.price,
                    0
                );
            },

            status(product) {
                if (product.stock <= 0) return 'out';
                if (product.stock <= product.min) return 'low';

                return 'ok';
            },

            inCart(id) {
                return this.cart.find(item => item.id === id);
            },

            add(product) {
                if (product.stock > 0 && !this.inCart(product.id)) {
                    this.cart.push({
                        ...product,
                        qty: 1
                    });
                }
            },

            remove(id) {
                this.cart = this.cart.filter(
                    item => item.id !== id
                );
            },

            clearCart() {
                this.cart = [];
            },

            inc(item) {
                if (item.qty < item.stock) {
                    item.qty++;
                }
            },

            dec(item) {
                if (item.qty > 1) {
                    item.qty--;
                }
            },

            rupiah(value) {
                return 'Rp' + Number(value || 0).toLocaleString('id-ID');
            }
        };
    };
</script>
@endpush
