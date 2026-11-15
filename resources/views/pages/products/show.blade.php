 @extends('layouts.app')

@section('content')
@php
    $totalVariants = $variants->count();
    $totalStock = $variants->sum('current_stock');
    $lowestPrice = $variants->min('selling_price');
@endphp

<div
    class="min-h-screen bg-gray-50/70"
    x-data="{ confirmDelete: false, deleteUrl: '', deleteName: '', deleteSku: '', confirmDeleteProduct: false,
            rename: false, renameUrl: '', renameOld: '', renameName: '' }"
>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
            <x-ui.breadcrumb :items="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Produk', 'url' => route('products.index')],
                ['label' => 'Detail Produk'],
            ]" />

        {{-- Header --}}
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
            <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-teal-100 text-teal-700">
                    <svg class="h-7 w-7" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/>
                        <path d="m4.5 7.8 7.5 4.4 7.5-4.4M12 12.2V21"/>
                    </svg>
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-medium text-teal-700">INFORMASI PRODUK</p>

                    <h1 class="mt-1 break-words text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                        {{ $product->name }}
                    </h1>

                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        Detail informasi, harga, dan ketersediaan varian produk.
                    </p>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold
                            {{ $product->status === 'active'
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200'
                                : 'bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-200' }}">
                            <span class="h-1.5 w-1.5 rounded-full
                                {{ $product->status === 'active' ? 'bg-emerald-500' : 'bg-gray-400' }}">
                            </span>
                            {{ $product->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                        </span>

                        @if ($product->category)
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
                                {{ $product->category }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tombol Header --}}
            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                @if (auth()->user()?->hasRole('staff_gudang'))
                    <button type="button"
                        @click="rename = true; renameUrl = '{{ route('products.rename', $product->id) }}'; renameOld = @js($product->name); renameName = @js($product->name)"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-teal-200 bg-white px-4 py-2.5 text-sm font-semibold text-teal-700 shadow-sm transition hover:bg-teal-50">
                        Ubah Nama
                    </button>
                @endif
                @if (auth()->user()?->hasRole('admin'))
                    <a href="{{ route('products.edit', $product->id) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m16 4 4 4"/>
                            <path d="M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16l-1 4Z"/>
                        </svg>
                        Edit Produk
                    </a>

                    @if ($variants->where('status', '!=', 'merged')->count() >= 2)
                        <a href="{{ route('products.variants.merge.create', $product->id) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-teal-200 bg-white px-4 py-2.5 text-sm font-semibold text-teal-700 shadow-sm transition hover:bg-teal-50">
                            <svg class="h-4 w-4" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M6 3v6a6 6 0 0 0 6 6h6"/>
                                <path d="m15 12 3 3-3 3"/>
                                <path d="M6 21v-3"/>
                            </svg>
                            Gabungkan Varian
                        </a>
                    @endif

                    <button
                        type="button"
                        @click="confirmDeleteProduct = true"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M3 6h18"/>
                            <path d="M8 6V4h8v2"/>
                            <path d="M19 6l-1 14H6L5 6"/>
                        </svg>
                        Hapus Produk
                    </button>
                @endif

                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                        <path d="M9 12h12"/>
                    </svg>
                    Kembali ke Daftar
                </a>
            </div>
        </div>

        {{-- Notifikasi Berhasil --}}
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        {{-- Notifikasi Gagal --}}
        @if (session('error'))
            <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v4M12 16h.01"/>
                </svg>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">

            {{-- Jumlah Varian --}}
            <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Varian</p>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-bold tracking-tight text-gray-900">
                                {{ number_format($totalVariants, 0, ',', '.') }}
                            </span>
                            <span class="text-sm text-gray-500">varian</span>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">
                            Varian yang terdaftar pada produk ini
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Stok --}}
            <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Stok</p>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-bold tracking-tight text-gray-900">
                                {{ number_format($totalStock, 0, ',', '.') }}
                            </span>
                            <span class="text-sm text-gray-500">unit</span>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">
                            Akumulasi stok seluruh varian
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/>
                            <path d="m3.5 7.8 8.5 4.4 8.5-4.4M12 12.2V21"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Harga Terendah --}}
            <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:col-span-2 xl:col-span-1">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Harga Jual Terendah</p>
                        <div class="mt-3">
                            <span class="text-2xl font-bold tracking-tight text-gray-900">
                                @if ($lowestPrice !== null)
                                    Rp {{ number_format($lowestPrice, 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">
                            {{ $lowestPrice !== null
                                ? 'Berdasarkan harga varian yang terdaftar'
                                : 'Belum ada harga varian' }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M16 8.5c-.8-.7-1.8-1-3-1-1.8 0-3 .9-3 2.2s1.2 2 3 2.3 3 1 3 2.3-1.2 2.2-3 2.2c-1.2 0-2.4-.4-3.2-1.2M12.5 5.5v13"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Informasi Produk --}}
        <section class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 11v5M12 8h.01"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-gray-900">Informasi Produk</h2>
                        <p class="mt-0.5 text-sm text-gray-500">
                            Identitas dan informasi dasar produk.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-x-10 sm:grid-cols-2">
                <div class="space-y-5 px-5 py-6 sm:px-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Nama Produk</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">
                            {{ $product->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Kategori</p>
                        <p class="mt-2 text-sm text-gray-700">
                            {{ $product->category ?: 'Belum ada kategori' }}
                        </p>
                    </div>
                </div>

                <div class="space-y-5 border-t border-gray-100 px-5 py-6 sm:border-l sm:border-t-0 sm:px-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Dibuat Oleh</p>
                        <p class="mt-2 text-sm font-medium text-gray-800">
                            {{ $product->creator_name ?? 'Tidak tersedia' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Tanggal Dibuat</p>
                        <p class="mt-2 text-sm text-gray-700">
                            @if ($product->created_at)
                                {{ \Carbon\Carbon::parse($product->created_at)->format('d M Y, H:i') }}
                            @else
                                Tidak tersedia
                            @endif
                        </p>
                    </div>
                </div>

                <div class="border-t border-gray-100 px-5 py-6 sm:col-span-2 sm:px-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Deskripsi Produk</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-gray-600">
                        {{ $product->description ?: 'Belum ada deskripsi untuk produk ini.' }}
                    </p>
                </div>
            </div>
        </section>

        {{-- Daftar Varian --}}
        <section class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">

            {{-- Header Daftar Varian --}}
            <div class="flex flex-col justify-between gap-4 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m12 3 9 4.5v9L12 21l-9-4.5v-9L12 3Z"/>
                            <path d="m3.5 7.8 8.5 4.4 8.5-4.4M12 12.2V21"/>
                        </svg>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-gray-900">Daftar Varian Produk</h2>
                            <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700">
                                {{ number_format($totalVariants, 0, ',', '.') }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Informasi SKU, harga jual, dan ketersediaan stok.
                        </p>
                    </div>
                </div>

                {{-- Tombol Tambah Varian hanya untuk Admin --}}
                @if (auth()->user()?->hasRole('admin'))
                    <a href="{{ route('products.variants.create', $product->id) }}"
                       class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Tambah Varian
                    </a>
                @endif
            </div>

            @if ($variants->isEmpty())
                {{-- Empty State --}}
                <div class="px-6 py-14 text-center sm:py-16">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                        <svg class="h-8 w-8" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m12 3 9 4.5v9L12 21l-9-4.5v-9L12 3Z"/>
                            <path d="m3.5 7.8 8.5 4.4 8.5-4.4M12 12.2V21"/>
                            <path d="M9 9.5h.01M15 9.5h.01"/>
                        </svg>
                    </div>

                    <h3 class="mt-5 text-base font-semibold text-gray-900">
                        Belum Ada Varian Produk
                    </h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                        Produk ini belum memiliki varian. Informasi SKU, harga jual,
                        dan stok akan ditampilkan di sini setelah varian ditambahkan.
                    </p>

                    <div class="mt-5 inline-flex items-center gap-2 rounded-xl bg-amber-50 px-4 py-3 text-left text-xs leading-5 text-amber-800 ring-1 ring-inset ring-amber-100">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 8v4M12 16h.01"/>
                        </svg>
                        Varian perlu terdaftar sebelum dapat dipilih dalam transaksi penjualan.
                    </div>
                </div>
            @else
                {{-- Tabel Varian --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 sm:px-6">
                                    SKU
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Nama Varian
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Satuan
                                </th>
                                <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Harga Jual
                                </th>
                                <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Stok
                                </th>
                                <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Reorder Point
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Status
                                </th>

                                @if (auth()->user()?->hasRole('staff_gudang'))
                                    <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Aksi
                                    </th>
                                @endif
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @foreach ($variants as $variant)
                                <tr class="transition hover:bg-teal-50/40">
                                    {{-- SKU --}}
                                    <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                        <span class="rounded-md bg-gray-100 px-2 py-1 font-mono text-xs font-medium text-gray-700">
                                            {{ $variant->sku }}
                                        </span>
                                    </td>

                                    {{-- Nama Varian --}}
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @php $variantImage = \App\Models\ProductVariant::imageUrl($variant->image_path); @endphp

                                        <div class="flex items-center gap-3">
                                            @if ($variantImage)
                                                <img src="{{ $variantImage }}" alt="{{ $variant->name }}" loading="lazy"
                                                    class="h-10 w-10 shrink-0 rounded-lg border border-gray-200 object-cover">
                                            @else
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                                        <path d="m21 15-5-5L5 21"/>
                                                    </svg>
                                                </div>
                                            @endif

                                            <p class="text-sm font-semibold text-gray-800">
                                                {{ $variant->name }}
                                            </p>
                                        </div>
                                    </td>

                                    {{-- Satuan --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                        {{ $variant->unit }}
                                    </td>

                                    {{-- Harga Jual --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold tabular-nums text-gray-800">
                                        Rp {{ number_format($variant->selling_price, 0, ',', '.') }}
                                    </td>

                                    {{-- Stok --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <span class="text-sm font-semibold tabular-nums
                                            {{ (int) $variant->current_stock <= (int) $variant->reorder_point
                                                ? 'text-amber-700'
                                                : 'text-gray-800' }}">
                                            {{ number_format($variant->current_stock, 0, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- Reorder Point --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm tabular-nums text-gray-600">
                                        {{ number_format($variant->reorder_point, 0, ',', '.') }}
                                    </td>

                                    {{-- Status --}}
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($variant->status === 'active')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                Aktif
                                            </span>
                                        @elseif ($variant->status === 'inactive')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                                Tidak Aktif
                                            </span>
                                        @elseif ($variant->status === 'merged')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700">
                                                Digabung
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                                {{ ucfirst($variant->status) }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Aksi Varian --}}
                                    @if (auth()->user()?->hasRole('staff_gudang'))
                                        <td class="whitespace-nowrap px-5 py-4 text-right">
                                            @if ($variant->status !== 'merged')
                                                <div class="flex items-center justify-end gap-2">

                                                    {{-- Ubah Nama: Admin & Staf Gudang --}}
                                                    <button type="button"
                                                        @click="rename = true; renameUrl = '{{ route('products.variants.rename', [$product->id, $variant->id]) }}'; renameOld = @js($variant->name); renameName = @js($variant->name)"
                                                        class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                        Ubah Nama
                                                    </button>

                                                    {{-- Edit & Hapus: khusus Admin --}}
                                                    @if (auth()->user()?->hasRole('admin'))
                                                    <a href="{{ route('products.variants.edit', [
                                                            'id' => $product->id,
                                                            'variantId' => $variant->id,
                                                        ]) }}"
                                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-teal-200 bg-white px-3 py-2 text-sm font-medium text-teal-700 transition hover:bg-teal-50"
                                                    aria-label="Edit varian {{ $variant->name }}">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path d="m16 4 4 4"/>
                                                            <path d="M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16l-1 4Z"/>
                                                        </svg>
                                                        Edit
                                                    </a>

                                                    <button
                                                        type="button"
                                                        @click="
                                                            deleteUrl = '{{ route('products.variants.destroy', [$product->id, $variant->id]) }}';
                                                            deleteName = @js($variant->name);
                                                            deleteSku = @js($variant->sku);
                                                            confirmDelete = true;
                                                        "
                                                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                                                        aria-label="Hapus varian {{ $variant->name }}">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path d="M3 6h18"/>
                                                            <path d="M8 6V4h8v2"/>
                                                            <path d="M19 6l-1 14H6L5 6"/>
                                                        </svg>
                                                        Hapus
                                                    </button>
                                                @endif

                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">
                                                    Tidak tersedia
                                                </span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Ringkasan Tabel --}}
                <div class="flex flex-col gap-1 border-t border-gray-100 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="text-xs text-gray-500">
                        Menampilkan {{ number_format($totalVariants, 0, ',', '.') }} varian produk.
                    </p>
                    <p class="text-xs text-gray-500">
                        Total stok:
                        <span class="font-semibold text-gray-700">
                            {{ number_format($totalStock, 0, ',', '.') }} unit
                        </span>
                    </p>
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <h2 class="font-semibold text-gray-900">Riwayat Perubahan Nama</h2>
        </div>
        @if ($renameLogs->isEmpty())
            <p class="px-6 py-8 text-center text-sm text-gray-500">Belum ada perubahan nama.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50/80 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Waktu</th>
                            <th class="px-5 py-3 text-left">Jenis</th>
                            <th class="px-5 py-3 text-left">Nama Lama</th>
                            <th class="px-5 py-3 text-left">Nama Baru</th>
                            <th class="px-5 py-3 text-left">Oleh</th>
                            <th class="px-5 py-3 text-left">Alasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($renameLogs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3">{{ \Carbon\Carbon::parse($log->changed_at)->format('d M Y, H:i') }}</td>
                                <td class="px-5 py-3">{{ $log->entity_type === 'product' ? 'Produk' : 'Varian' }}</td>
                                <td class="px-5 py-3">{{ $log->old_name }}</td>
                                <td class="px-5 py-3 font-semibold">{{ $log->new_name }}</td>
                                <td class="px-5 py-3">{{ $log->user_name }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $log->reason ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

        {{-- Footer --}}
        <div class="flex items-center justify-center gap-2 pb-2 text-xs text-gray-400">
            <svg class="h-4 w-4" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="1.7" stroke-linecap="round"
                 stroke-linejoin="round">
                <path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6l-8-3Z"/>
                <path d="m9 12 2 2 4-4"/>
            </svg>
            Informasi produk Askara Stock
        </div>

    </div>

    @if (auth()->user()?->hasRole('admin'))
    {{-- Modal konfirmasi hapus produk --}}
    <div
        x-show="confirmDeleteProduct"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="confirmDeleteProduct = false"
        @click.self="confirmDeleteProduct = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="delete-product-title"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500">
                    <svg class="h-5 w-5" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M3 6h18"/>
                        <path d="M8 6V4h8v2"/>
                        <path d="M19 6l-1 14H6L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                    </svg>
                </div>

                <div>
                    <h3 id="delete-product-title" class="text-base font-bold text-gray-900">
                        Hapus produk?
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-gray-600">
                        Produk
                        <span class="font-semibold text-gray-900">{{ $product->name }}</span>
                        @if ($totalVariants > 0)
                            beserta {{ $totalVariants }} variannya
                        @endif
                        akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('products.destroy', $product->id) }}"
                class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
            >
                @csrf
                @method('DELETE')

                <button
                    type="button"
                    @click="confirmDeleteProduct = false"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal Ubah Nama (Admin & Staf Gudang) --}}
    @if (auth()->user()?->hasRole('staff_gudang'))
    <div x-show="rename" x-cloak x-transition.opacity
         @keydown.escape.window="rename = false" @click.self="rename = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="text-base font-bold text-gray-900">Ubah Nama</h3>
            <p class="mt-1 text-sm text-gray-500">Nama saat ini: <span class="font-semibold text-gray-800" x-text="renameOld"></span></p>

            <form method="POST" :action="renameUrl" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nama baru <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="renameName" maxlength="255" required
                           class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Alasan (opsional)</label>
                    <input type="text" name="reason" maxlength="255" placeholder="Contoh: koreksi salah ketik"
                           class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="rename = false" class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal Konfirmasi Hapus Varian --}}
    <div
        x-show="confirmDelete"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="confirmDelete = false"
        @click.self="confirmDelete = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="delete-variant-title"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500">
                    <svg class="h-5 w-5" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M3 6h18"/>
                        <path d="M8 6V4h8v2"/>
                        <path d="M19 6l-1 14H6L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                    </svg>
                </div>

                <div>
                    <h3 id="delete-variant-title" class="text-base font-bold text-gray-900">
                        Hapus varian?
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-gray-600">
                        Varian
                        <span class="font-semibold text-gray-900" x-text="deleteName"></span>
                        (SKU:
                        <span class="font-mono text-xs font-medium text-gray-700" x-text="deleteSku"></span>)
                        akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
            </div>

            <form method="POST" :action="deleteUrl" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                @csrf
                @method('DELETE')

                <button
                    type="button"
                    @click="confirmDelete = false"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
