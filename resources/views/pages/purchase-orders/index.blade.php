@extends('layouts.app')

@section('title', 'Purchase Order')

@section('content')
@php
    $rupiah = fn ($n) => 'Rp' . number_format($n, 0, ',', '.');

    // Pemetaan status database -> label & warna di tampilan.
    $statuses = [
        'draft'     => ['label' => 'Draft',             'dot' => 'bg-slate-500',  'text' => 'text-slate-600'],
        'ordered'   => ['label' => 'Dikirim',           'dot' => 'bg-blue-500',   'text' => 'text-blue-600'],
        'partial'   => ['label' => 'Diterima sebagian', 'dot' => 'bg-amber-500',  'text' => 'text-amber-600'],
        'received'  => ['label' => 'Selesai',           'dot' => 'bg-green-600',  'text' => 'text-green-700'],
        'cancelled' => ['label' => 'Dibatalkan',        'dot' => 'bg-red-500',    'text' => 'text-red-600'],
    ];

    $current = $orders->currentPage();
    $last = max($orders->lastPage(), 1);

    // Nomor halaman: pertama, sekitar halaman aktif, dan terakhir.
    $pages = collect([1, $current - 1, $current, $current + 1, $last])
        ->filter(fn ($p) => $p >= 1 && $p <= $last)
        ->unique()
        ->sort()
        ->values();

    $hasFilter = request()->hasAny(['q', 'supplier', 'status', 'start', 'end']);
@endphp

<div class="flex h-full flex-col">

    {{-- Header --}}
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div class="flex items-start justify-between gap-4">
            <div>

                <x-ui.breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Pembelian'],
                ]" />

                <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
                    Purchase Order
                </h1>

                <p class="mt-1 text-xs text-slate-500">
                    Kelola pesanan supplier dan pantau pemenuhan setiap varian.
                </p>
            </div>

            <a
                href="{{ route('purchase-orders.create') }}"
                class="inline-flex shrink-0 items-center gap-2 rounded-md bg-[#4C8DF6] px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-blue-600"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Buat PO
            </a>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto bg-[#EEF2FB] px-8 py-6">

        {{-- Tab --}}
        <div class="mb-5 flex gap-6 border-b border-slate-200 text-sm">
            <a
                href="{{ route('purchase-orders.index') }}"
                aria-current="page"
                class="-mb-px border-b-2 border-[#4C8DF6] px-1 pb-3 font-medium text-[#4C8DF6]"
            >
                Purchase Order
            </a>

            <a
                href="{{ route('goods-receipts.index') }}"
                class="-mb-px border-b-2 border-transparent px-1 pb-3 text-slate-500 transition hover:text-[#0A1E45]"
            >
                Penerimaan barang
            </a>
        </div>

        {{-- Notifikasi sukses --}}
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

            {{-- Pesanan terbuka --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-medium text-slate-500">Pesanan terbuka</p>

                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-[#4C8DF6]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-7 8h6m-6 4h6"/>
                        </svg>
                    </span>
                </div>

                <p class="mt-3 text-2xl font-extrabold text-[#4C8DF6]">
                    {{ number_format($stats['open'], 0, ',', '.') }}
                    <span class="text-sm font-medium">PO</span>
                </p>

                <p class="mt-1 text-[11px] text-slate-500">
                    {{ $stats['draft'] }} draft · {{ $stats['ordered'] }} dikirim · {{ $stats['partial'] }} parsial
                </p>
            </div>

            {{-- Selesai --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-medium text-slate-500">Selesai</p>

                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>

                <p class="mt-3 text-2xl font-extrabold text-green-700">
                    {{ number_format($stats['received'], 0, ',', '.') }}
                    <span class="text-sm font-medium">PO</span>
                </p>

                <p class="mt-1 text-[11px] text-slate-500">Semua varian telah diterima</p>
            </div>

            {{-- Dibatalkan --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-medium text-slate-500">Dibatalkan</p>

                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>

                <p class="mt-3 text-2xl font-extrabold text-slate-600">
                    {{ number_format($stats['cancelled'], 0, ',', '.') }}
                    <span class="text-sm font-medium">PO</span>
                </p>

                <p class="mt-1 text-[11px] text-slate-500">Tidak memengaruhi stok</p>
            </div>
        </div>

        {{-- Daftar pesanan --}}
        <div class="mt-5 rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="px-5 pt-5">
                <h2 class="text-lg font-extrabold text-[#0A1E45]">Daftar pesanan</h2>
                <p class="mt-1 text-[11px] text-slate-500">
                    {{ number_format($stats['total'], 0, ',', '.') }} PO
                    @if ($hasFilter)
                        · sesuai filter yang dipilih
                    @endif
                </p>
            </div>

            {{-- Filter --}}
            <form
                method="GET"
                action="{{ route('purchase-orders.index') }}"
                class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_180px_140px_140px_170px_auto]"
                x-data="{
                    start: null,
                    end: null,
                    init() {
                        const opts = {
                            dateFormat: 'Y-m-d',
                            altInput: true,
                            altFormat: 'd M Y',
                            altInputClass: 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-600 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20',
                            disableMobile: true,
                        };

                        this.start = flatpickr(this.$refs.start, {
                            ...opts,
                            onChange: (dates) => this.end.set('minDate', dates[0] || null),
                        });

                        this.end = flatpickr(this.$refs.end, {
                            ...opts,
                            onChange: (dates) => this.start.set('maxDate', dates[0] || null),
                        });

                        // Sinkronkan batas saat halaman dibuka dengan filter yang sudah terisi.
                        if (this.$refs.start.value) this.end.set('minDate', this.$refs.start.value);
                        if (this.$refs.end.value) this.start.set('maxDate', this.$refs.end.value);
                    }
                }"
            >
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                {{-- Pencarian --}}
                <div>
                    <label for="q" class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]">Pencarian</label>

                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#4C8DF6]"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>

                        <input
                            type="text" id="q" name="q" value="{{ request('q') }}"
                            placeholder="Cari nomor PO atau supplier..."
                            class="h-10 w-full rounded-md border border-slate-200 bg-white pl-10 pr-3 text-xs outline-none placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                        >
                    </div>
                </div>

                {{-- Supplier --}}
                <div>
                    <label for="supplier" class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]">Supplier</label>

                    <select
                        id="supplier" name="supplier"
                        class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                    >
                        <option value="">Semua supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected((string) request('supplier') === (string) $s->id)>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal mulai --}}
                    <div>
                        <label for="start" class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]">Dari tanggal</label>

                        <input
                            type="text" id="start" name="start" x-ref="start"
                            value="{{ request('start') }}"
                            placeholder="Pilih tanggal"
                            autocomplete="off"
                            class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-600 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                        >
                    </div>

                    {{-- Tanggal akhir --}}
                    <div>
                        <label for="end" class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]">Sampai tanggal</label>

                        <input
                            type="text" id="end" name="end" x-ref="end"
                            value="{{ request('end') }}"
                            placeholder="Pilih tanggal"
                            autocomplete="off"
                            class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-600 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                        >
                    </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="mb-1.5 block text-[11px] font-medium text-[#4C8DF6]">Status</label>

                    <select
                        id="status" name="status"
                        class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                    >
                        <option value="">Semua status</option>
                        @foreach ($statuses as $key => $st)
                            <option value="{{ $key }}" @selected(request('status') === $key)>
                                {{ $st['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tombol --}}
                <div class="flex items-end gap-2">
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center justify-center rounded-md bg-[#4C8DF6] px-4 text-xs font-semibold text-white transition hover:bg-blue-600"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('purchase-orders.index') }}"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-3 text-xs text-slate-600 transition hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>

            {{-- Tabel --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50/70 text-[11px] font-semibold text-[#0A1E45]">
                            <th class="px-5 py-3">Nomor PO / Tanggal</th>
                            <th class="px-3 py-3">Supplier</th>
                            <th class="px-3 py-3">Varian / Total</th>
                            <th class="px-3 py-3">Estimasi tiba</th>
                            <th class="px-3 py-3">Status PO</th>
                            <th class="px-5 py-3">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($orders as $po)
                            @php
                                $st = $statuses[$po['status']] ?? [
                                    'label' => ucfirst($po['status']),
                                    'dot'   => 'bg-slate-400',
                                    'text'  => 'text-slate-600',
                                ];

                                $detailUrl = route('purchase-orders.show', $po['id']);
                            @endphp

                            <tr class="border-b border-slate-100 transition hover:bg-slate-50/70">
                                {{-- Nomor PO & tanggal --}}
                                <td class="px-5 py-4">
                                    <p class="text-xs font-bold text-[#0A1E45]">{{ $po['number'] }}</p>
                                    <p class="mt-1 text-xs font-bold text-[#0A1E45]">
                                        {{ $po['date']->locale('id')->translatedFormat('d M Y') }}
                                    </p>
                                </td>

                                {{-- Supplier --}}
                                <td class="px-3 py-4 text-xs text-slate-600">
                                    {{ $po['supplier'] }}
                                </td>

                                {{-- Varian & total --}}
                                <td class="px-3 py-4">
                                    <p class="text-xs text-slate-600">
                                        {{ number_format($po['variants'], 0, ',', '.') }} varian
                                    </p>
                                    <p class="mt-1 text-xs text-slate-600">
                                        {{ $rupiah($po['total']) }}
                                    </p>
                                </td>

                                {{-- Estimasi tiba --}}
                                <td class="px-3 py-4 text-xs text-slate-600">
                                    {{ $po['eta'] ? $po['eta']->locale('id')->translatedFormat('d M Y') : '—' }}
                                </td>

                                {{-- Status --}}
                                <td class="px-3 py-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $st['text'] }}">
                                        <span class="h-2 w-2 rounded-full {{ $st['dot'] }}"></span>
                                        {{ $st['label'] }}
                                    </span>
                                </td>

                                {{-- Aksi --}}
                                <td class="px-5 py-4">
                                    <a
                                        href="{{ $detailUrl }}"
                                        class="text-xs font-semibold text-[#4C8DF6] hover:underline"
                                    >
                                        Lihat detail →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 12h6m-6 4h6"/>
                                        </svg>
                                    </div>

                                    <p class="mt-3 text-sm font-semibold text-slate-700">
                                        Purchase order tidak ditemukan
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Belum ada PO yang sesuai dengan pencarian atau filter.
                                    </p>

                                    @if ($hasFilter)
                                        <a
                                            href="{{ route('purchase-orders.index') }}"
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

            {{-- Footer & pagination --}}
            <div class="flex flex-col gap-4 border-t border-slate-100 px-5 py-4 text-[11px] text-slate-500 lg:flex-row lg:items-center lg:justify-between">

                <p>
                    Menampilkan
                    <span class="font-semibold text-slate-700">
                        {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }}
                    </span>
                    dari
                    <span class="font-semibold text-slate-700">
                        {{ number_format($orders->total(), 0, ',', '.') }}
                    </span>
                    PO
                </p>

                <nav aria-label="Pagination purchase order" class="flex flex-wrap items-center gap-1">
                    @if ($orders->onFirstPage())
                        <span class="px-2 py-1 text-slate-300">‹</span>
                    @else
                        <a href="{{ $orders->previousPageUrl() }}"
                           class="rounded px-2 py-1 transition hover:bg-slate-100 hover:text-[#0A1E45]">‹</a>
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
                            <a href="{{ $orders->url($p) }}"
                               class="flex h-7 min-w-7 items-center justify-center rounded px-2 transition hover:bg-slate-100 hover:text-[#0A1E45]">
                                {{ $p }}
                            </a>
                        @endif
                    @endforeach

                    @if ($orders->hasMorePages())
                        <a href="{{ $orders->nextPageUrl() }}"
                           class="rounded px-2 py-1 transition hover:bg-slate-100 hover:text-[#0A1E45]">›</a>
                    @else
                        <span class="px-2 py-1 text-slate-300">›</span>
                    @endif
                </nav>

                {{-- Jumlah data per halaman --}}
                <form method="GET" action="{{ route('purchase-orders.index') }}">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input type="hidden" name="supplier" value="{{ request('supplier') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="start" value="{{ request('start') }}">
                    <input type="hidden" name="end" value="{{ request('end') }}">

                    <label for="per_page" class="sr-only">Jumlah PO per halaman</label>

                    <select
                        id="per_page" name="per_page" onchange="this.form.submit()"
                        class="h-9 cursor-pointer rounded-md border border-slate-200 bg-white px-3 text-[11px] text-slate-600 outline-none focus:border-[#4C8DF6]"
                    >
                        @foreach ([10, 25, 50] as $n)
                            <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} per halaman</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- Catatan informasi --}}
        <div class="mt-5 flex items-start gap-3 rounded-lg bg-blue-50 px-4 py-3 text-xs text-slate-600">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#4C8DF6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
            </svg>

            <p>
                Mengirim PO tidak menambah stok. Status Diterima sebagian dan Selesai
                mengikuti dokumen penerimaan barang yang sudah tersimpan.
            </p>
        </div>

    </main>
</div>
@endsection