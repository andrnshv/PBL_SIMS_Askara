
@extends('layouts.app')

@section('title', 'Daftar Produk')

@section('content')
<div class="min-h-screen bg-[#EEF2FB] px-4 py-6 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-slate-500">
                <a href="{{ url('/dashboard') }}" class="transition hover:text-[#4C8DF6]">
                    Dashboard
                </a>
                <span>/</span>
                <span class="font-medium text-[#0A1E45]">Produk</span>
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-[#0A1E45]">
                Daftar Produk
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Kelola data produk dan varian produk perusahaan.
            </p>
        </div>

        {{-- Tombol tambah produk --}}
        <a
            href="{{ route('products.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0A1E45] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#142D5C]"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M12 5v14" />
                <path d="M5 12h14" />
            </svg>
            Tambah Produk
        </a>
    </div>

    {{-- Notifikasi sukses --}}
    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3">
            <p class="text-sm font-medium text-green-700">
                {{ session('success') }}
            </p>
        </div>
    @endif

    {{-- Informasi jumlah produk --}}
    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500">
                Total Produk
            </p>
            <p class="mt-2 text-2xl font-bold text-[#0A1E45]">
                {{ $products->total() }}
            </p>
            <p class="mt-1 text-xs text-slate-400">
                Produk induk terdaftar
            </p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500">
                Kategori Produk
            </p>
            <p class="mt-2 text-2xl font-bold text-[#4C8DF6]">
                {{ $categories->count() }}
            </p>
            <p class="mt-1 text-xs text-slate-400">
                Kategori yang tersedia
            </p>
        </div>
    </div>

    {{-- Konten utama --}}
    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">

        {{-- Filter dan pencarian --}}
        <form
            action="{{ route('products.index') }}"
            method="GET"
            class="border-b border-slate-100 p-4 sm:p-5"
        >
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">

                {{-- Pencarian --}}
                <div class="relative flex-1">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>

                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari nama produk, varian, atau SKU..."
                        class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15"
                    >
                </div>

                {{-- Filter kategori --}}
                <div class="w-full lg:w-52">
                    <select
                        name="category"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15"
                    >
                        <option value="">Semua Kategori</option>

                        @foreach ($categories as $category)
                            <option
                                value="{{ $category }}"
                                {{ request('category') === $category ? 'selected' : '' }}
                            >
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Jumlah data per halaman --}}
                <div class="w-full lg:w-36">
                    <select
                        name="per_page"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15"
                    >
                        <option value="10" {{ (int) $perPage === 10 ? 'selected' : '' }}>
                            10 data
                        </option>
                        <option value="25" {{ (int) $perPage === 25 ? 'selected' : '' }}>
                            25 data
                        </option>
                        <option value="50" {{ (int) $perPage === 50 ? 'selected' : '' }}>
                            50 data
                        </option>
                    </select>
                </div>

                {{-- Tombol terapkan --}}
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-[#4C8DF6] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-600"
                >
                    Terapkan
                </button>

                {{-- Reset filter --}}
                @if (request()->filled('q') || request()->filled('category') || (int) request('per_page', 10) !== 10)
                    <a
                        href="{{ route('products.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                    >
                        Reset
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabel produk --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[650px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80">
                        <th class="w-16 px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            No.
                        </th>
                        <th class="px-3 py-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Nama Produk
                        </th>
                        <th class="px-3 py-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Kategori
                        </th>
                        <th class="px-3 py-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Jumlah Varian
                        </th>
                        <th class="px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($products as $p)
                        <tr class="border-b border-slate-100 transition last:border-b-0 hover:bg-blue-50/30 {{ $loop->even ? 'bg-slate-50/40' : 'bg-white' }}">

                            {{-- Nomor urut --}}
                            <td class="px-5 py-4 text-xs text-slate-400">
                                {{ $products->firstItem() + $loop->index }}
                            </td>

                            {{-- Nama produk --}}
                            <td class="px-3 py-4">
                                <div class="text-sm font-semibold text-[#0A1E45]">
                                    {{ $p->name }}
                                </div>

                                @if ($p->description)
                                    <p class="mt-1 max-w-xs truncate text-xs text-slate-400">
                                        {{ $p->description }}
                                    </p>
                                @endif
                            </td>

                            {{-- Kategori --}}
                            <td class="px-3 py-4">
                                @if ($p->category)
                                    <span class="inline-flex rounded-md bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-[#4C8DF6]">
                                        {{ $p->category }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">
                                        Tanpa Kategori
                                    </span>
                                @endif
                            </td>

                            {{-- Jumlah varian --}}
                            <td class="px-3 py-4">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    {{ $p->variants_count }} varian
                                </span>
                            </td>

                            {{-- Aksi --}}
                            <td class="px-5 py-4">
                                <a
                                    href="{{ route('products.show', $p->id) }}"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#4C8DF6] transition hover:text-blue-700"
                                >
                                    Lihat Detail
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-3.5 w-3.5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M7 17 17 7" />
                                        <path d="M7 7h10v10" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-6 w-6 text-slate-400"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                            <path d="m3.3 7 8.7 5 8.7-5" />
                                            <path d="M12 22V12" />
                                        </svg>
                                    </div>

                                    <p class="text-sm font-semibold text-[#0A1E45]">
                                        Produk tidak ditemukan
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-slate-400">
                                        Belum ada produk yang sesuai dengan pencarian atau kategori yang dipilih.
                                    </p>

                                    @if (request()->filled('q') || request()->filled('category'))
                                        <a
                                            href="{{ route('products.index') }}"
                                            class="mt-4 text-xs font-semibold text-[#4C8DF6] hover:text-blue-700"
                                        >
                                            Hapus filter pencarian
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($products->hasPages())
            <div class="flex flex-col gap-4 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    Menampilkan
                    <span class="font-semibold text-[#0A1E45]">
                        {{ $products->firstItem() ?? 0 }}
                    </span>
                    sampai
                    <span class="font-semibold text-[#0A1E45]">
                        {{ $products->lastItem() ?? 0 }}
                    </span>
                    dari
                    <span class="font-semibold text-[#0A1E45]">
                        {{ $products->total() }}
                    </span>
                    produk
                </p>

                <div class="text-sm">
                    {{ $products->links() }}
                </div>
            </div>
        @elseif ($products->total() > 0)
            <div class="border-t border-slate-100 px-5 py-4">
                <p class="text-xs text-slate-500">
                    Menampilkan {{ $products->total() }} produk
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
