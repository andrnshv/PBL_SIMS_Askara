@extends('layouts.app')

@section('title', 'Stok Opname')

@section('content')
@php
    $current = $opnames->currentPage();
    $last = max($opnames->lastPage(), 1);

    $pages = collect(
        $current <= 3
            ? [1, 2, 3, $last]
            : [1, $current - 1, $current, $current + 1, $last]
    )
        ->filter(fn ($p) => $p >= 1 && $p <= $last)
        ->unique()
        ->sort()
        ->values();

    $statusBadge = [
        'draft' => [
            'label' => 'Draft',
            'class' => 'bg-yellow-50 text-yellow-700',
        ],
        'selesai' => [
            'label' => 'Selesai',
            'class' => 'bg-blue-50 text-[#4C8DF6]',
        ],
    ];
@endphp

<div class="flex h-full flex-col">

    {{-- Header --}}
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Stok', 'url' => route('stock.index')],
                    ['label' => 'Opname Stok'],
                ]" />

                <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
                    Opname Stok
                </h1>

                <p class="mt-1 text-xs text-slate-500">
                    Kelola pemeriksaan fisik dan pencatatan selisih stok produk.
                </p>
            </div>

            @if ($canManageOpname)
                <a href="{{ route('stock.opname.create') }}"
                class="flex items-center gap-2 rounded-md bg-[#4C8DF6] px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-[#3b7ae0]">
                    <span class="text-base leading-none">+</span>
                    Buat Opname
                </a>
            @endif
        </div>
    </header>

    <main class="flex-1 overflow-y-auto bg-[#EEF2FB] px-8 py-6">

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-xs text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Ringkasan --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">

            {{-- Total Opname --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] text-slate-500">Total Opname</p>

                    <svg class="h-4 w-4 text-[#4C8DF6]"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 12h6M9 16h6"/>
                    </svg>
                </div>

                <p class="mt-2 text-2xl font-extrabold text-[#0A1E45]">
                    {{ number_format($tabCounts['all'], 0, ',', '.') }} dokumen
                </p>

                <p class="mt-1 text-[11px] text-[#4C8DF6]">
                    Total dokumen sesuai pencarian dan tanggal
                </p>
            </div>

            @if ($canManageOpname)
                {{-- Draft --}}
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] text-slate-500">Draft</p>

                        <svg class="h-4 w-4 text-amber-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <p class="mt-2 text-2xl font-extrabold text-amber-600">
                        {{ number_format($tabCounts['draft'], 0, ',', '.') }} dokumen
                    </p>

                    <p class="mt-1 text-[11px] text-[#4C8DF6]">
                        Menunggu pemeriksaan atau penyelesaian
                    </p>
                </div>
            @endif

            {{-- Selesai --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] text-slate-500">Selesai</p>

                    <svg class="h-4 w-4 text-[#4C8DF6]"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M5 12l4 4L19 6"/>
                    </svg>
                </div>

                <p class="mt-2 text-2xl font-extrabold text-[#4C8DF6]">
                    {{ number_format($tabCounts['selesai'], 0, ',', '.') }} dokumen
                </p>

                <p class="mt-1 text-[11px] text-[#4C8DF6]">
                    Pemeriksaan telah diselesaikan
                </p>
            </div>

        </div>

        {{-- Tabel dan Filter --}}
        <div class="mt-4 rounded-xl border border-slate-200 bg-white shadow-sm">

            {{-- Filter --}}
            <form method="GET"
                  action="{{ route('stock.opname') }}"
                  class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-[1fr_180px_180px_150px]">

                {{-- Pencarian --}}
                <div>
                    <label for="q"
                           class="mb-1.5 block text-[11px] text-[#4C8DF6]">
                        Pencarian
                    </label>

                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#4C8DF6]"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>

                        <input type="text"
                               id="q"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="Cari nomor atau catatan opname..."
                               class="h-10 w-full rounded-md border border-slate-200 bg-white pl-10 pr-3 text-xs outline-none placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20">
                    </div>
                </div>

                {{-- Tanggal Mulai --}}
                <div>
                    <label for="start"
                           class="mb-1.5 block text-[11px] text-[#4C8DF6]">
                        Dari tanggal
                    </label>

                    <input type="date"
                           id="start"
                           name="start"
                           value="{{ $start ?? '' }}"
                           class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20">
                </div>

                {{-- Tanggal Akhir --}}
                <div>
                    <label for="end"
                           class="mb-1.5 block text-[11px] text-[#4C8DF6]">
                        Sampai tanggal
                    </label>

                    <input type="date"
                           id="end"
                           name="end"
                           value="{{ $end ?? '' }}"
                           class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20">
                </div>

                {{-- Baris --}}
                <div>
                    <label for="per_page"
                           class="mb-1.5 block text-[11px] text-[#4C8DF6]">
                        Baris per halaman
                    </label>

                    <select id="per_page"
                            name="per_page"
                            class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6]">
                        @foreach ([10, 25, 50] as $number)
                            <option value="{{ $number }}"
                                @selected((int) $perPage === $number)>
                                {{ $number }} baris
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Pertahankan status tab saat filter digunakan --}}
                @if ($status !== '')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif

                <div class="flex flex-wrap items-center gap-2 md:col-span-2 xl:col-span-4">
                    <button type="submit"
                            class="rounded-md bg-[#4C8DF6] px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-[#3b7ae0]">
                        Terapkan Filter
                    </button>

                    <a href="{{ route('stock.opname') }}"
                       class="rounded-md border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-[#0A1E45] transition hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>

            {{-- Tab Status --}}
            <div class="flex flex-wrap items-center gap-5 border-t border-slate-200 px-5">
                <a href="{{ route('stock.opname', array_merge(request()->query(), ['status' => null, 'page' => 1])) }}"
                   class="border-b-2 py-3 text-xs font-semibold transition {{ empty($status) ? 'border-[#4C8DF6] text-[#4C8DF6]' : 'border-transparent text-slate-500 hover:text-[#0A1E45]' }}">
                    Semua
                    <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px]">
                        {{ $tabCounts['all'] }}
                    </span>
                </a>

                @if ($canManageOpname)
                    <a href="{{ route('stock.opname', array_merge(request()->query(), ['status' => 'draft', 'page' => 1])) }}"
                    class="border-b-2 py-3 text-xs font-semibold transition {{ $status === 'draft' ? 'border-[#4C8DF6] text-[#4C8DF6]' : 'border-transparent text-slate-500 hover:text-[#0A1E45]' }}">
                        Draft
                        <span class="ml-1 rounded bg-yellow-50 px-1.5 py-0.5 text-[10px] text-yellow-700">
                            {{ $tabCounts['draft'] }}
                        </span>
                    </a>
                @endif

                <a href="{{ route('stock.opname', array_merge(request()->query(), ['status' => 'selesai', 'page' => 1])) }}"
                   class="border-b-2 py-3 text-xs font-semibold transition {{ $status === 'selesai' ? 'border-[#4C8DF6] text-[#4C8DF6]' : 'border-transparent text-slate-500 hover:text-[#0A1E45]' }}">
                    Selesai
                    <span class="ml-1 rounded bg-blue-50 px-1.5 py-0.5 text-[10px] text-[#4C8DF6]">
                        {{ $tabCounts['selesai'] }}
                    </span>
                </a>
            </div>

            {{-- Tabel --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left">

                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50/70 text-[10px] font-semibold text-slate-600">
                            <th class="px-5 py-3">Nomor / Tanggal</th>
                            <th class="px-3 py-3">Gudang</th>
                            <th class="px-3 py-3">Catatan</th>
                            <th class="px-3 py-3">Petugas</th>
                            <th class="px-3 py-3 text-center">Varian Diperiksa</th>
                            <th class="px-3 py-3 text-center">Selisih</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-5 py-3">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($opnames as $o)
                            <tr class="border-b border-slate-100 {{ $loop->even ? 'bg-slate-50/60' : 'bg-white' }}">

                                {{-- Nomor / Tanggal --}}
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <p class="text-[11px] font-semibold text-[#0A1E45]">
                                        {{ $o['number'] }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ $o['date']->format('d M Y') }}
                                    </p>
                                </td>

                                {{-- Gudang --}}
                                <td class="whitespace-nowrap px-3 py-3.5 text-[11px] text-slate-700">
                                    {{ $o['warehouse'] }}
                                </td>

                                {{-- Catatan --}}
                                <td class="max-w-[240px] px-3 py-3.5 text-[11px] text-slate-700">
                                    {{ $o['note'] ?: '-' }}
                                </td>

                                {{-- Petugas --}}
                                <td class="whitespace-nowrap px-3 py-3.5 text-[11px] text-slate-700">
                                    {{ $o['officer'] }}
                                </td>

                                {{-- Varian Diperiksa --}}
                                <td class="px-3 py-3.5 text-center text-[11px] font-semibold text-[#0A1E45]">
                                    {{ number_format($o['counted'], 0, ',', '.') }}
                                </td>

                                {{-- Selisih --}}
                                <td class="px-3 py-3.5 text-center">
                                    @if ($o['diff'] > 0)
                                        <span class="inline-block min-w-[75px] rounded bg-yellow-50 px-2 py-0.5 text-[10px] font-medium text-yellow-700">
                                            {{ $o['diff'] }} varian
                                        </span>
                                    @else
                                        <span class="inline-block min-w-[75px] rounded bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-[#4C8DF6]">
                                            Tidak ada
                                        </span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="whitespace-nowrap px-3 py-3.5">
                                    <span class="inline-block w-[75px] rounded py-0.5 text-center text-[10px] font-medium {{ $statusBadge[$o['status']]['class'] }}">
                                        {{ $statusBadge[$o['status']]['label'] }}
                                    </span>

                                    @if ($canManageOpname && $o['status'] === 'draft')
                                        <p class="mt-1 text-[10px] text-slate-400">
                                            Belum diposting
                                        </p>
                                    @elseif ($o['finished_at'])
                                        <p class="mt-1 text-[10px] text-slate-400">
                                            {{ $o['finished_at']->format('d M Y, H.i') }}
                                        </p>
                                    @endif
                                </td>

                                {{-- Aksi --}}
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    @if ($canManageOpname && $o['status'] === 'draft')
                                        <a href="{{ route('stock.opname.edit', $o['id']) }}"
                                           class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#4C8DF6] hover:underline">
                                            Lanjutkan

                                            <svg class="h-3 w-3"
                                                 fill="none"
                                                 viewBox="0 0 24 24"
                                                 stroke="currentColor"
                                                 stroke-width="2.5">
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      d="M5 12h14m-6-6l6 6-6 6"/>
                                            </svg>
                                        </a>

                                        <form method="POST"
                                              action="{{ route('stock.opname.destroy', $o['id']) }}"
                                              class="ml-3 inline"
                                              onsubmit="return confirm('Hapus draft {{ $o['number'] }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-[11px] font-semibold text-red-500 hover:underline">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('stock.opname.show', $o['id']) }}"
                                           class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#4C8DF6] hover:underline">
                                            Lihat Hasil

                                            <svg class="h-3 w-3"
                                                 fill="none"
                                                 viewBox="0 0 24 24"
                                                 stroke="currentColor"
                                                 stroke-width="2.5">
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      d="M5 12h14m-6-6l6 6-6 6"/>
                                            </svg>
                                        </a>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    class="px-5 py-12 text-center text-xs text-slate-400">
                                    Tidak ada dokumen opname yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer dan Pagination --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-[11px] text-slate-500">

                <p class="text-[#4C8DF6]">
                    Menampilkan
                    {{ $opnames->firstItem() ?? 0 }}–{{ $opnames->lastItem() ?? 0 }}
                    dari
                    {{ number_format($opnames->total(), 0, ',', '.') }}
                    dokumen
                </p>

                <nav class="flex flex-wrap items-center gap-2"
                     aria-label="Pagination">

                    {{-- Sebelumnya --}}
                    @if ($opnames->onFirstPage())
                        <span class="px-2 text-slate-300">
                            ‹ Sebelumnya
                        </span>
                    @else
                        <a href="{{ $opnames->previousPageUrl() }}"
                           class="px-2 hover:text-[#0A1E45]">
                            ‹ Sebelumnya
                        </a>
                    @endif

                    {{-- Nomor Halaman --}}
                    @foreach ($pages as $index => $page)
                        @if ($index > 0 && $page - $pages[$index - 1] > 1)
                            <span class="px-1 text-slate-300">…</span>
                        @endif

                        @if ($page === $current)
                            <span class="flex h-6 min-w-6 items-center justify-center rounded bg-[#4C8DF6] px-2 text-[11px] font-bold text-white">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $opnames->url($page) }}"
                               class="flex h-6 min-w-6 items-center justify-center px-2 hover:text-[#0A1E45]">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Berikutnya --}}
                    @if ($opnames->hasMorePages())
                        <a href="{{ $opnames->nextPageUrl() }}"
                           class="px-2 hover:text-[#0A1E45]">
                            Berikutnya ›
                        </a>
                    @else
                        <span class="px-2 text-slate-300">
                            Berikutnya ›
                        </span>
                    @endif
                </nav>

                {{-- Jumlah Baris --}}
                <form method="GET" action="{{ route('stock.opname') }}">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input type="hidden" name="start" value="{{ $start ?? '' }}">
                    <input type="hidden" name="end" value="{{ $end ?? '' }}">

                    @if ($status !== '')
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif

                    <select name="per_page"
                            onchange="this.form.submit()"
                            class="h-9 cursor-pointer rounded-md border border-slate-200 bg-white px-3 text-[11px] text-slate-600 outline-none">
                        @foreach ([10, 25, 50] as $number)
                            <option value="{{ $number }}"
                                @selected((int) $perPage === $number)>
                                {{ $number }} per halaman
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- Informasi --}}
        <p class="mt-4 flex items-start gap-2 text-[10px] text-[#4C8DF6]">
            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round"
                      d="M12 8h.01M11 12h1v4h1"/>
            </svg>

            Data opname ditampilkan berdasarkan pencatatan aktual.
            Draft tidak mengubah stok. Stok dan riwayat pergerakan baru dicatat saat opname diselesaikan.
        </p>

    </main>
</div>
@endsection
