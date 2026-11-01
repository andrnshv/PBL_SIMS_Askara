@extends('layouts.app')

@section('title', 'Stok')

@section('content')
@php
    $current = $items->currentPage();
    $last = max($items->lastPage(), 1);

    $pages = collect(
        $current <= 3
            ? [1, 2, 3, $last]
            : [1, $current - 1, $current, $current + 1, $last]
    )
        ->filter(fn ($p) => $p >= 1 && $p <= $last)
        ->unique()
        ->sort()
        ->values();

    $badge = [
        'tersedia' => [
            'label' => 'Tersedia',
            'class' => 'bg-blue-50 text-[#4C8DF6]',
        ],
        'menipis' => [
            'label' => 'Menipis',
            'class' => 'bg-yellow-50 text-yellow-700',
        ],
        'habis' => [
            'label' => 'Habis',
            'class' => 'bg-red-50 text-red-500',
        ],
    ];

    $iconCircles = 'M12 4a3 3 0 100 6 3 3 0 000-6zM6 14a3 3 0 100 6 3 3 0 000-6zm12 0a3 3 0 100 6 3 3 0 000-6z';
@endphp

<div class="flex h-full flex-col">

    {{-- Header --}}
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Stok'],
                ]" />

                <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
                    Stok
                </h1>

                <p class="mt-1 text-xs text-slate-500">
                    Pantau ketersediaan dan kelola stok setiap varian produk.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('stock.history') }}"
                   class="flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-[#0A1E45] transition hover:bg-slate-50">
                    <svg class="h-3.5 w-3.5 text-[#4C8DF6]"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M3 12a9 9 0 109-9M3 4v5h5M12 7v5l3 2"/>
                    </svg>
                    Riwayat Stok
                </a>

                <a href="{{ route('stock.opname') }}"
                   class="flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-[#0A1E45] transition hover:bg-slate-50">
                    <svg class="h-3.5 w-3.5 text-[#4C8DF6]"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 5h6M9 12h6M9 16h4"/>
                    </svg>
                    Stok Opname
                </a>

                <a href="{{ route('stock.incoming.create') }}"
                   class="flex items-center gap-2 rounded-md bg-[#4C8DF6] px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-[#3b7ae0]">
                    <span class="text-base leading-none">+</span>
                    Catat Stok Masuk
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto bg-[#EEF2FB] px-8 py-6">

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-xs text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Kartu Ringkasan --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- SKU Aktif --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] text-slate-500">SKU Aktif</p>

                    <svg class="h-4 w-4 text-[#4C8DF6]"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M21 8l-9-5-9 5v8l9 5 9-5V8zM3.3 7.7L12 12.5l8.7-4.8M12 12.5V21"/>
                    </svg>
                </div>

                <p class="mt-2 text-2xl font-extrabold text-[#0A1E45]">
                    {{ number_format($stats['total'], 0, ',', '.') }} varian
                </p>

                <p class="mt-1 text-[11px] text-[#4C8DF6]">
                    Total varian produk aktif
                </p>
            </div>

            {{-- Tersedia --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] text-slate-500">Tersedia</p>

                    <svg class="h-4 w-4 text-[#4C8DF6]"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="{{ $iconCircles }}"/>
                    </svg>
                </div>

                <p class="mt-2 text-2xl font-extrabold text-[#4C8DF6]">
                    {{ number_format($stats['tersedia'], 0, ',', '.') }} varian
                </p>

                <p class="mt-1 text-[11px] text-[#4C8DF6]">
                    Stok &gt; batas minimum
                </p>
            </div>

            {{-- Menipis --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] text-slate-500">Menipis</p>

                    <svg class="h-4 w-4 text-amber-500"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="{{ $iconCircles }}"/>
                    </svg>
                </div>

                <p class="mt-2 text-2xl font-extrabold text-amber-600">
                    {{ number_format($stats['menipis'], 0, ',', '.') }} varian
                </p>

                <p class="mt-1 text-[11px] text-[#4C8DF6]">
                    0 &lt; stok ≤ batas minimum
                </p>
            </div>

            {{-- Habis --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] text-slate-500">Habis</p>

                    <svg class="h-4 w-4 text-red-500"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="{{ $iconCircles }}"/>
                    </svg>
                </div>

                <p class="mt-2 text-2xl font-extrabold text-red-600">
                    {{ number_format($stats['habis'], 0, ',', '.') }} varian
                </p>

                <p class="mt-1 text-[11px] text-[#4C8DF6]">
                    Stok = 0
                </p>
            </div>

        </div>

        {{-- Tabel dan Filter --}}
        <div class="mt-4 rounded-xl border border-slate-200 bg-white shadow-sm">

            {{-- Filter --}}
            <form method="GET"
                  action="{{ route('stock.index') }}"
                  class="grid grid-cols-1 gap-4 p-5 md:grid-cols-[1fr_210px_160px]">

                <input type="hidden"
                       name="per_page"
                       value="{{ $perPage }}">

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
                               placeholder="Cari nama produk, varian, atau SKU..."
                               class="h-10 w-full rounded-md border border-slate-200 bg-white pl-10 pr-3 text-xs outline-none placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20">
                    </div>
                </div>

                {{-- Kategori --}}
                <div>
                    <label for="category"
                           class="mb-1.5 block text-[11px] text-[#4C8DF6]">
                        Kategori
                    </label>

                    <select id="category"
                            name="category"
                            onchange="this.form.submit()"
                            class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6]">
                        <option value="">Semua Kategori</option>

                        @foreach ($categories as $category)
                            <option value="{{ $category }}"
                                @selected(request('category') === $category)>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Stok --}}
                <div>
                    <label for="status"
                           class="mb-1.5 block text-[11px] text-[#4C8DF6]">
                        Status stok
                    </label>

                    <select id="status"
                            name="status"
                            onchange="this.form.submit()"
                            class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6]">
                        <option value="">Semua Status</option>

                        @foreach ($badge as $key => $itemBadge)
                            <option value="{{ $key }}"
                                @selected(request('status') === $key)>
                                {{ $itemBadge['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            {{-- Tabel Stok --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left">

                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50/70 text-[10px] font-semibold text-slate-600">
                            <th class="px-5 py-3">Produk</th>
                            <th class="px-3 py-3">Varian</th>
                            <th class="px-3 py-3">SKU</th>
                            <th class="px-3 py-3">Stok Saat Ini</th>
                            <th class="px-3 py-3">Batas Minimum</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-5 py-3">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($items as $item)
                            @php
                                $stockColor = match ($item['status']) {
                                    'habis' => 'text-red-500',
                                    'menipis' => 'text-amber-600',
                                    default => 'text-slate-700',
                                };
                            @endphp

                            <tr class="border-b border-slate-100 {{ $loop->even ? 'bg-slate-50/60' : 'bg-white' }}">

                                {{-- Nama Produk Induk --}}
                                <td class="px-5 py-3.5 text-[11px] font-semibold text-[#0A1E45]">
                                    {{ $item['name'] }}
                                </td>

                                {{-- Nama Varian --}}
                                <td class="px-3 py-3.5 text-[11px] text-slate-700">
                                    {{ $item['variant'] }}
                                </td>

                                {{-- SKU --}}
                                <td class="px-3 py-3.5 text-[11px] text-[#7C93BD]">
                                    {{ $item['sku'] }}
                                </td>

                                {{-- Stok Saat Ini --}}
                                <td class="px-3 py-3.5 text-[11px] {{ $stockColor }}">
                                    {{ number_format($item['stock'], 0, ',', '.') }}
                                    {{ $item['unit'] }}
                                </td>

                                {{-- Batas Minimum --}}
                                <td class="px-3 py-3.5 text-[11px] text-slate-700">
                                    {{ number_format($item['min'], 0, ',', '.') }}
                                    {{ $item['unit'] }}
                                </td>

                                {{-- Status --}}
                                <td class="px-3 py-3.5">
                                    <span class="inline-block w-[100px] rounded py-0.5 text-center text-[10px] font-medium {{ $badge[$item['status']]['class'] }}">
                                        {{ $badge[$item['status']]['label'] }}
                                    </span>
                                </td>

                                {{-- Aksi --}}
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('stock.history', ['variant' => $item['id']]) }}"
                                       class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#4C8DF6] hover:underline">
                                        Riwayat

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
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    class="px-5 py-12 text-center text-xs text-slate-400">
                                    Varian tidak ditemukan.
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
                    {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }}
                    dari
                    {{ number_format($items->total(), 0, ',', '.') }}
                    varian
                </p>

                <nav class="flex items-center gap-2"
                     aria-label="Pagination">

                    {{-- Sebelumnya --}}
                    @if ($items->onFirstPage())
                        <span class="px-2 text-slate-300">
                            ‹ Sebelumnya
                        </span>
                    @else
                        <a href="{{ $items->previousPageUrl() }}"
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
                            <a href="{{ $items->url($page) }}"
                               class="flex h-6 min-w-6 items-center justify-center px-2 hover:text-[#0A1E45]">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Berikutnya --}}
                    @if ($items->hasMorePages())
                        <a href="{{ $items->nextPageUrl() }}"
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
                <form method="GET"
                      action="{{ route('stock.index') }}">

                    <input type="hidden"
                           name="q"
                           value="{{ request('q') }}">

                    <input type="hidden"
                           name="category"
                           value="{{ request('category') }}">

                    <input type="hidden"
                           name="status"
                           value="{{ request('status') }}">

                    <select name="per_page"
                            onchange="this.form.submit()"
                            class="h-9 cursor-pointer rounded-md border border-slate-200 bg-white px-3 text-[11px] text-slate-600 outline-none">
                        @foreach ([10, 25, 50] as $number)
                            <option value="{{ $number }}"
                                @selected($perPage === $number)>
                                {{ $number }} per halaman
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- Informasi --}}
        <p class="mt-4 flex items-center gap-2 text-[10px] text-[#4C8DF6]">
            <svg class="h-3.5 w-3.5 shrink-0"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round"
                      d="M12 8h.01M11 12h1v4h1"/>
            </svg>

            Data ditampilkan berdasarkan inventaris produk aktif.
            Setiap baris mewakili satu varian; ringkasan menghitung
            jumlah varian, bukan kuantitas stok.
        </p>

    </main>
</div>
@endsection
