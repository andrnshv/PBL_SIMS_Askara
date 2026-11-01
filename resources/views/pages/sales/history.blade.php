@extends('layouts.app')

@section('title', 'Riwayat Penjualan')
@section('user', 'Staff Penjualan')
@section('role', 'Sales Staff')

@section('content')
@php
    $rupiah = fn ($n) => 'Rp' . number_format($n, 0, ',', '.');

    $current = $transactions->currentPage();
    $last = max($transactions->lastPage(), 1);

    // Nomor halaman: pertama, sekitar halaman aktif, dan terakhir.
    $pages = collect([1, $current - 1, $current, $current + 1, $last])
        ->filter(fn ($p) => $p >= 1 && $p <= $last)
        ->unique()
        ->sort()
        ->values();
@endphp

<div class="flex h-full flex-col">

    {{-- Header --}}
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] text-slate-400">
                    Dashboard / Penjualan / Riwayat Penjualan
                </p>

                <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
                    Riwayat Penjualan
                </h1>

                <p class="mt-1 text-xs text-slate-500">
                    Lihat dan telusuri transaksi penjualan yang telah tercatat.
                </p>
            </div>

            <a
                href="{{ route('sales.index') }}"
                class="inline-flex shrink-0 items-center gap-2 rounded-md bg-[#0A1E45] px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-[#16346B]"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 19l-7-7 7-7M5 12h14"
                    />
                </svg>
                Transaksi Baru
            </a>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto bg-[#EEF2FB] px-8 py-6">

        {{-- Notifikasi sukses --}}
        @if (session('success'))
            <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Notifikasi validasi --}}
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

            {{-- Total transaksi --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-medium text-slate-500">
                        Total Transaksi
                    </p>

                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-[#4C8DF6]">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-7 8h6m-6 4h6"
                            />
                        </svg>
                    </span>
                </div>

                <p class="mt-3 text-2xl font-extrabold text-[#0A1E45]">
                    {{ number_format($stats['transactions'], 0, ',', '.') }}
                    <span class="text-sm font-medium text-slate-500">transaksi</span>
                </p>
            </div>

            {{-- Total penjualan --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-medium text-slate-500">
                        Total Penjualan
                    </p>

                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </span>
                </div>

                <p class="mt-3 text-2xl font-extrabold text-[#0A1E45]">
                    {{ $rupiah($stats['revenue']) }}
                </p>
            </div>

            {{-- Total item terjual --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-medium text-slate-500">
                        Total Item Terjual
                    </p>

                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 3h2l2.4 12.2a2 2 0 002 1.8h7.8a2 2 0 002-1.6L21 8H6m4 13a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"
                            />
                        </svg>
                    </span>
                </div>

                <p class="mt-3 text-2xl font-extrabold text-[#0A1E45]">
                    {{ number_format($stats['units'], 0, ',', '.') }}
                    <span class="text-sm font-medium text-slate-500">unit</span>
                </p>
            </div>

        </div>

        {{-- Tabel riwayat penjualan --}}
        <div class="mt-5 rounded-xl border border-slate-200 bg-white shadow-sm">

            {{-- Filter dan pencarian --}}
            <form
                method="GET"
                action="{{ route('sales.history') }}"
                class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_160px_160px_170px_auto]"
            >
                <input
                    type="hidden"
                    name="per_page"
                    value="{{ $perPage }}"
                >

                {{-- Pencarian --}}
                <div>
                    <label
                        for="q"
                        class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]"
                    >
                        Pencarian
                    </label>

                    <div class="relative">
                        <svg
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#4C8DF6]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>

                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Cari invoice atau nama kasir..."
                            class="h-10 w-full rounded-md border border-slate-200 bg-white pl-10 pr-3 text-xs outline-none placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                        >
                    </div>
                </div>

                {{-- Tanggal mulai --}}
                <div>
                    <label
                        for="start"
                        class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]"
                    >
                        Tanggal mulai
                    </label>

                    <input
                        type="date"
                        id="start"
                        name="start"
                        value="{{ request('start') }}"
                        max="{{ now()->toDateString() }}"
                        class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-600 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                    >
                </div>

                {{-- Tanggal akhir --}}
                <div>
                    <label
                        for="end"
                        class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]"
                    >
                        Tanggal akhir
                    </label>

                    <input
                        type="date"
                        id="end"
                        name="end"
                        value="{{ request('end') }}"
                        min="{{ request('start') }}"
                        max="{{ now()->toDateString() }}"
                        class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-600 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                    >
                </div>

                {{-- Pengurutan --}}
                <div>
                    <label
                        for="sort"
                        class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]"
                    >
                        Urutkan
                    </label>

                    <select
                        id="sort"
                        name="sort"
                        class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                    >
                        <option value="terbaru" @selected($sort === 'terbaru')>
                            Terbaru
                        </option>

                        <option value="terlama" @selected($sort === 'terlama')>
                            Terlama
                        </option>

                        <option value="tertinggi" @selected($sort === 'tertinggi')>
                            Total tertinggi
                        </option>

                        <option value="terendah" @selected($sort === 'terendah')>
                            Total terendah
                        </option>
                    </select>
                </div>

                {{-- Tombol filter --}}
                <div class="flex items-end gap-2">
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[#4C8DF6] px-4 text-xs font-semibold text-white transition hover:bg-blue-600"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 4h18M6 9h12m-9 5h6m-4 5h2"
                            />
                        </svg>
                        Filter
                    </button>

                    <a
                        href="{{ route('sales.history') }}"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-3 text-xs text-slate-600 transition hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>

            {{-- Informasi filter aktif --}}
            @if (request()->hasAny(['q', 'start', 'end']))
                <div class="border-t border-slate-100 bg-blue-50/50 px-5 py-3 text-[11px] text-slate-600">
                    Menampilkan hasil sesuai filter yang dipilih.
                    <a
                        href="{{ route('sales.history') }}"
                        class="ml-1 font-semibold text-[#4C8DF6] hover:underline"
                    >
                        Hapus filter
                    </a>
                </div>
            @endif

            {{-- Tabel transaksi --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50/70 text-[11px] font-semibold text-[#4C8DF6]">
                            <th class="w-16 px-5 py-3">No.</th>
                            <th class="px-3 py-3">No. Transaksi</th>
                            <th class="px-3 py-3">Tanggal &amp; Waktu</th>
                            <th class="px-3 py-3">Kasir</th>
                            <th class="px-3 py-3">Jumlah Item</th>
                            <th class="px-3 py-3 text-right">Total Penjualan</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($transactions as $t)
                            <tr class="border-b border-slate-100 transition hover:bg-slate-50/70">
                                {{-- Nomor urut --}}
                                <td class="px-5 py-4 text-[11px] text-slate-400">
                                    {{ $transactions->firstItem() + $loop->index }}
                                </td>

                                {{-- Nomor invoice --}}
                                <td class="px-3 py-4">
                                    <p class="text-xs font-bold text-[#0A1E45]">
                                        {{ $t['invoice'] }}
                                    </p>
                                </td>

                                {{-- Tanggal dan waktu --}}
                                <td class="px-3 py-4 text-[11px] text-slate-600">
                                    {{ $t['datetime']->format('d M Y, H.i') }}
                                </td>

                                {{-- Nama kasir --}}
                                <td class="px-3 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-[#4C8DF6]">
                                            {{ strtoupper(substr($t['cashier'], 0, 1)) }}
                                        </span>

                                        <span class="text-[11px] text-slate-600">
                                            {{ $t['cashier'] }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Jenis item dan total unit --}}
                                <td class="px-3 py-4">
                                    <p class="text-[11px] font-medium text-slate-700">
                                        {{ number_format($t['items'], 0, ',', '.') }} jenis
                                    </p>

                                    <p class="mt-1 text-[10px] text-[#4C8DF6]">
                                        {{ number_format($t['units'], 0, ',', '.') }} unit
                                    </p>
                                </td>

                                {{-- Total --}}
                                <td class="px-3 py-4 text-right text-xs font-bold text-[#0A1E45]">
                                    {{ $rupiah($t['total']) }}
                                </td>

                                {{-- Status transaksi --}}
                                <td class="px-5 py-4">
                                    @if ($t['status'] === 'completed')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-[10px] font-semibold text-green-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                            Selesai
                                        </span>
                                    @elseif ($t['status'] === 'cancelled')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-semibold text-red-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Dibatalkan
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600">
                                            {{ ucfirst($t['status']) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg
                                            class="h-6 w-6"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 12h6m-6 4h6"
                                            />
                                        </svg>
                                    </div>

                                    <p class="mt-3 text-sm font-semibold text-slate-700">
                                        Transaksi tidak ditemukan
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Belum ada transaksi yang sesuai dengan pencarian atau filter.
                                    </p>

                                    @if (request()->hasAny(['q', 'start', 'end']))
                                        <a
                                            href="{{ route('sales.history') }}"
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
                        {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }}
                    </span>
                    dari
                    <span class="font-semibold text-slate-700">
                        {{ number_format($transactions->total(), 0, ',', '.') }}
                    </span>
                    transaksi
                </p>

                {{-- Navigasi halaman --}}
                <nav
                    aria-label="Pagination riwayat penjualan"
                    class="flex flex-wrap items-center gap-1"
                >
                    @if ($transactions->onFirstPage())
                        <span class="px-2 py-1 text-slate-300">
                            ‹ Sebelumnya
                        </span>
                    @else
                        <a
                            href="{{ $transactions->previousPageUrl() }}"
                            class="rounded px-2 py-1 transition hover:bg-slate-100 hover:text-[#0A1E45]"
                        >
                            ‹ Sebelumnya
                        </a>
                    @endif

                    @foreach ($pages as $p)
                        @if (! $loop->first && $p - $pages[$loop->index - 1] > 1)
                            <span class="px-1 text-slate-300">…</span>
                        @endif

                        @if ($p === $current)
                            <span
                                aria-current="page"
                                class="flex h-7 min-w-7 items-center justify-center rounded bg-[#4C8DF6] px-2 text-[11px] font-bold text-white"
                            >
                                {{ $p }}
                            </span>
                        @else
                            <a
                                href="{{ $transactions->url($p) }}"
                                class="flex h-7 min-w-7 items-center justify-center rounded px-2 transition hover:bg-slate-100 hover:text-[#0A1E45]"
                            >
                                {{ $p }}
                            </a>
                        @endif
                    @endforeach

                    @if ($transactions->hasMorePages())
                        <a
                            href="{{ $transactions->nextPageUrl() }}"
                            class="rounded px-2 py-1 transition hover:bg-slate-100 hover:text-[#0A1E45]"
                        >
                            Berikutnya ›
                        </a>
                    @else
                        <span class="px-2 py-1 text-slate-300">
                            Berikutnya ›
                        </span>
                    @endif
                </nav>

                {{-- Jumlah data per halaman --}}
                <form method="GET" action="{{ route('sales.history') }}">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input type="hidden" name="start" value="{{ request('start') }}">
                    <input type="hidden" name="end" value="{{ request('end') }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">

                    <label for="per_page" class="sr-only">
                        Jumlah transaksi per halaman
                    </label>

                    <select
                        id="per_page"
                        name="per_page"
                        onchange="this.form.submit()"
                        class="h-9 cursor-pointer rounded-md border border-slate-200 bg-white px-3 text-[11px] text-slate-600 outline-none focus:border-[#4C8DF6]"
                    >
                        @foreach ([10, 25, 50] as $n)
                            <option
                                value="{{ $n }}"
                                @selected($perPage === $n)
                            >
                                {{ $n }} per halaman
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- Catatan informasi --}}
        <p class="mt-4 flex items-start gap-2 text-[10px] text-slate-400">
            <svg
                class="mt-0.5 h-3.5 w-3.5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="12" cy="12" r="9"/>
                <path
                    stroke-linecap="round"
                    d="M12 8h.01M11 12h1v4h1"
                />
            </svg>

            Jumlah jenis menunjukkan banyaknya varian produk berbeda dalam
            transaksi, sedangkan unit menunjukkan total kuantitas produk
            yang terjual. Ringkasan mengikuti filter pencarian dan tanggal.
        </p>

    </main>
</div>
@endsection
