@extends('layouts.app')

@section('title', 'Manajemen Supplier')

@section('content')
@php
    $current = $suppliers->currentPage();
    $last = max($suppliers->lastPage(), 1);

    $pages = collect([1, $current - 1, $current, $current + 1, $last])
        ->filter(fn ($p) => $p >= 1 && $p <= $last)
        ->unique()
        ->sort()
        ->values();

    $hasFilter = request()->hasAny(['q', 'status']);
@endphp

<div class="flex h-full flex-col">

    {{-- Header --}}
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <x-ui.breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Pembelian', 'url' => route('purchase-orders.index')],
                    ['label' => 'Manajemen Supplier'],
                ]" />

                <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
                    Manajemen Supplier
                </h1>

                <p class="mt-1 text-xs text-slate-500">
                    Kelola informasi pemasok dan status kerja sama supplier.
                </p>
            </div>

            <a
                href="{{ route('suppliers.create') }}"
                class="inline-flex shrink-0 items-center gap-2 rounded-md bg-[#4C8DF6] px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-blue-600"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 5v14M5 12h14"/>
                </svg>
                Tambah Supplier
            </a>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto bg-[#EEF2FB] px-8 py-6">

        {{-- Notifikasi --}}
        @if (session('success'))
            <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold">Terjadi kesalahan.</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Kartu ringkasan --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            {{-- Total --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-medium text-slate-500">
                        Total Supplier
                    </p>

                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-[#4C8DF6]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01M14 9v.01M14 12v.01M14 15v.01M14 18v.01"/>
                        </svg>
                    </span>
                </div>

                <p class="mt-3 text-2xl font-extrabold text-[#4C8DF6]">
                    {{ number_format($stats['total'], 0, ',', '.') }}
                    <span class="text-sm font-medium">supplier</span>
                </p>

                <p class="mt-1 text-[11px] text-slate-500">
                    Seluruh data supplier terdaftar
                </p>
            </div>

            {{-- Aktif --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-medium text-slate-500">
                        Supplier Aktif
                    </p>

                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>

                <p class="mt-3 text-2xl font-extrabold text-green-700">
                    {{ number_format($stats['active'], 0, ',', '.') }}
                    <span class="text-sm font-medium">supplier</span>
                </p>

                <p class="mt-1 text-[11px] text-slate-500">
                    Dapat dipilih pada pembuatan PO
                </p>
            </div>

            {{-- Nonaktif --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-medium text-slate-500">
                        Supplier Nonaktif
                    </p>

                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>

                <p class="mt-3 text-2xl font-extrabold text-slate-600">
                    {{ number_format($stats['inactive'], 0, ',', '.') }}
                    <span class="text-sm font-medium">supplier</span>
                </p>

                <p class="mt-1 text-[11px] text-slate-500">
                    Tidak tersedia untuk PO baru
                </p>
            </div>
        </div>

        {{-- Daftar supplier --}}
        <div class="mt-5 rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="px-5 pt-5">
                <h2 class="text-lg font-extrabold text-[#0A1E45]">
                    Daftar Supplier
                </h2>

                <p class="mt-1 text-[11px] text-slate-500">
                    {{ number_format($suppliers->total(), 0, ',', '.') }} supplier
                    @if ($hasFilter)
                        · sesuai filter yang dipilih
                    @endif
                </p>
            </div>

            {{-- Pencarian dan filter --}}
            <form
                method="GET"
                action="{{ route('suppliers.index') }}"
                class="grid grid-cols-1 gap-4 p-5 md:grid-cols-[minmax(220px,1fr)_180px_auto]"
            >
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <div>
                    <label for="q"
                           class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]">
                        Pencarian
                    </label>

                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#4C8DF6]"
                             fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>

                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Cari nama, kontak, telepon..."
                            class="h-10 w-full rounded-md border border-slate-200 bg-white pl-10 pr-3 text-xs outline-none placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                        >
                    </div>
                </div>

                <div>
                    <label for="status"
                           class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]">
                        Status Supplier
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                    >
                        <option value="all" @selected($status === 'all')>
                            Semua status
                        </option>
                        <option value="active" @selected($status === 'active')>
                            Aktif
                        </option>
                        <option value="inactive" @selected($status === 'inactive')>
                            Nonaktif
                        </option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center justify-center rounded-md bg-[#4C8DF6] px-4 text-xs font-semibold text-white transition hover:bg-blue-600"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('suppliers.index') }}"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-3 text-xs text-slate-600 transition hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>

            {{-- Tabel supplier --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[950px] text-left">
                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50/70 text-[11px] font-semibold text-[#0A1E45]">
                            <th class="px-5 py-3">Nama Supplier</th>
                            <th class="px-3 py-3">Kontak</th>
                            <th class="px-3 py-3">Telepon</th>
                            <th class="px-3 py-3">Email</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-5 py-3">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr class="border-b border-slate-100 transition hover:bg-slate-50/70">

                                <td class="px-5 py-4">
                                    <p class="text-xs font-bold text-[#0A1E45]">
                                        {{ $supplier->name }}
                                    </p>

                                    <p class="mt-1 max-w-xs truncate text-[11px] text-slate-400"
                                       title="{{ $supplier->address }}">
                                        {{ $supplier->address ?: 'Alamat belum diisi' }}
                                    </p>
                                </td>

                                <td class="px-3 py-4 text-xs text-slate-600">
                                    {{ $supplier->contact_person ?: '—' }}
                                </td>

                                <td class="px-3 py-4 text-xs text-slate-600">
                                    {{ $supplier->phone ?: '—' }}
                                </td>

                                <td class="px-3 py-4 text-xs text-slate-600">
                                    {{ $supplier->email ?: '—' }}
                                </td>

                                <td class="px-3 py-4">
                                    @if ($supplier->is_active)
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-green-700">
                                            <span class="h-2 w-2 rounded-full bg-green-600"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                                            <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <a
                                            href="{{ route('suppliers.edit', $supplier->id) }}"
                                            class="text-xs font-semibold text-[#4C8DF6] hover:underline"
                                        >
                                            Ubah
                                        </a>

                                        @if ($supplier->is_active)
                                            <form
                                                method="POST"
                                                action="{{ route('suppliers.destroy', $supplier->id) }}"
                                                onsubmit="return confirm('Nonaktifkan supplier ini? Riwayat transaksi tidak akan dihapus.')"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="text-xs font-semibold text-red-500 hover:underline"
                                                >
                                                    Nonaktifkan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                             stroke="currentColor" stroke-width="1.6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/>
                                        </svg>
                                    </div>

                                    <p class="mt-3 text-sm font-semibold text-slate-700">
                                        Supplier tidak ditemukan
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Belum ada supplier yang sesuai dengan pencarian atau filter.
                                    </p>

                                    @if ($hasFilter)
                                        <a
                                            href="{{ route('suppliers.index') }}"
                                            class="mt-3 inline-block text-xs font-semibold text-[#4C8DF6] hover:underline"
                                        >
                                            Reset filter
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer dan pagination --}}
            <div class="flex flex-col gap-4 border-t border-slate-100 px-5 py-4 text-[11px] text-slate-500 lg:flex-row lg:items-center lg:justify-between">

                <p>
                    Menampilkan
                    <span class="font-semibold text-slate-700">
                        {{ $suppliers->firstItem() ?? 0 }}–{{ $suppliers->lastItem() ?? 0 }}
                    </span>
                    dari
                    <span class="font-semibold text-slate-700">
                        {{ number_format($suppliers->total(), 0, ',', '.') }}
                    </span>
                    supplier
                </p>

                <nav aria-label="Pagination supplier"
                     class="flex flex-wrap items-center gap-1">

                    @if ($suppliers->onFirstPage())
                        <span class="px-2 py-1 text-slate-300">‹</span>
                    @else
                        <a href="{{ $suppliers->previousPageUrl() }}"
                           class="rounded px-2 py-1 transition hover:bg-slate-100 hover:text-[#0A1E45]">
                            ‹
                        </a>
                    @endif

                    @foreach ($pages as $p)
                        @if (! $loop->first && $p - $pages[$loop->index - 1] > 1)
                            <span class="px-1 text-slate-300">…</span>
                        @endif

                        @if ($p === $current)
                            <span aria-current="page"
                                  class="flex h-7 min-w-7 items-center justify-center rounded bg-[#4C8DF6] px-2 text-[11px] font-bold text-white">
                                {{ $p }}
                            </span>
                        @else
                            <a href="{{ $suppliers->url($p) }}"
                               class="flex h-7 min-w-7 items-center justify-center rounded px-2 transition hover:bg-slate-100 hover:text-[#0A1E45]">
                                {{ $p }}
                            </a>
                        @endif
                    @endforeach

                    @if ($suppliers->hasMorePages())
                        <a href="{{ $suppliers->nextPageUrl() }}"
                           class="rounded px-2 py-1 transition hover:bg-slate-100 hover:text-[#0A1E45]">
                            ›
                        </a>
                    @else
                        <span class="px-2 py-1 text-slate-300">›</span>
                    @endif
                </nav>

                {{-- Jumlah data per halaman --}}
                <form method="GET" action="{{ route('suppliers.index') }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="status" value="{{ $status }}">

                    <label for="per_page" class="sr-only">
                        Jumlah supplier per halaman
                    </label>

                    <select
                        id="per_page"
                        name="per_page"
                        onchange="this.form.submit()"
                        class="h-9 cursor-pointer rounded-md border border-slate-200 bg-white px-3 text-[11px] text-slate-600 outline-none focus:border-[#4C8DF6]"
                    >
                        @foreach ([10, 25, 50] as $n)
                            <option value="{{ $n }}" @selected($perPage === $n)>
                                {{ $n }} per halaman
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- Catatan --}}
        <div class="mt-5 flex items-start gap-3 rounded-lg bg-blue-50 px-4 py-3 text-xs text-slate-600">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#4C8DF6]"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
            </svg>

            <p>
                Supplier nonaktif tetap tersimpan agar riwayat Purchase Order terjaga.
                Supplier nonaktif tidak tersedia untuk pembuatan PO baru.
            </p>
        </div>

    </main>
</div>
@endsection
