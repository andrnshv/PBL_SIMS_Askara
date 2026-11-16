@extends('layouts.app')

@section('content')

  @php
    $filterStart = \Carbon\Carbon::parse($filter['start']);
    $filterEnd   = \Carbon\Carbon::parse($filter['end']);
  @endphp

  {{-- Sapaan --}}
  <p class="text-xl text-gray-500 dark:text-gray-400">Selamat Datang,</p>
  <h1 class="mt-1 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ auth()->user()->name }} 👋</h1>
  <p class="mt-2 text-gray-500 dark:text-gray-400">
    Berikut ringkasan aktivitas sistem periode
    {{ $filterStart->translatedFormat('d M Y') }} – {{ $filterEnd->translatedFormat('d M Y') }}.
  </p>

  {{-- Filter tanggal --}}
  <form method="GET" action="{{ route('dashboard') }}"
    class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]"
    x-data="{
      start: null,
      end: null,
      init() {
        const opts = {
          dateFormat: 'Y-m-d',
          altInput: true,
          altFormat: 'd M Y',
          altInputClass: 'h-10 w-44 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300',
          maxDate: 'today',
          disableMobile: true,
        };

        this.start = flatpickr(this.$refs.start, {
          ...opts,
          onChange: (dates) => this.end.set('minDate', dates[0] || null),
        });

        this.end = flatpickr(this.$refs.end, {
          ...opts,
          onChange: (dates) => this.start.set('maxDate', dates[0] || 'today'),
        });

        // Sinkronkan batas saat halaman dibuka dengan filter yang sudah terisi.
        if (this.$refs.start.value) this.end.set('minDate', this.$refs.start.value);
        if (this.$refs.end.value) this.start.set('maxDate', this.$refs.end.value);
      }
    }">

    <div>
      <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Tanggal mulai</label>
      <input type="text" name="start" x-ref="start" value="{{ $filter['start'] }}" autocomplete="off">
    </div>

    <div>
      <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Tanggal akhir</label>
      <input type="text" name="end" x-ref="end" value="{{ $filter['end'] }}" autocomplete="off">
    </div>

    <button type="submit"
      class="h-10 rounded-lg bg-brand-500 px-5 text-sm font-medium text-white hover:bg-brand-600">
      Terapkan
    </button>

    <a href="{{ route('dashboard') }}"
      class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
      Reset
    </a>

    @if ($errors->has('start') || $errors->has('end'))
      <p class="w-full text-sm text-error-600">{{ $errors->first('start') ?: $errors->first('end') }}</p>
    @endif
  </form>

  @php
    // Kelas warna badge
    $up   = 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500';
    $down = 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500';
    $warn = 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500';

    $pg = $metrics['products_growth'];   // pertumbuhan produk (persen) atau null
    $sg = $metrics['sales_growth'];      // pertumbuhan penjualan (persen) atau null

    $stats = [
      [
        'title' => 'Produk Aktif',
        'value' => number_format($metrics['total_products'], 0, ',', '.'),
        'border' => 'border-[#3B6EF5]',
        'badge' => $pg === null ? null : ($pg >= 0 ? '+' : '') . $pg . '%',
        'badgeClass' => ($pg ?? 0) >= 0 ? $up : $down,
        'note' => number_format($metrics['total_variants'], 0, ',', '.') . ' item (varian) aktif'
                  . ($pg === null ? '' : ' · dari bulan lalu'),
        'icon' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
      ],
      [
        'title' => 'Item Hasil Penggabungan',
        'value' => number_format($metrics['merged_items'], 0, ',', '.'),
        'border' => 'border-[#8B5CF6]',
        'badge' => null,
        'badgeClass' => '',
        'note' => 'dari ' . number_format($metrics['merge_operations'], 0, ',', '.') . ' operasi penggabungan · kumulatif',
        'icon' => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
      ],
      [
        'title' => 'Total Stok',
        'value' => number_format($metrics['total_stock'], 0, ',', '.'),
        'border' => 'border-[#10B981]',
        'badge' => null,
        'badgeClass' => '',
        'note' => 'jumlah unit semua varian aktif · per hari ini',
        'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
      ],
      [
        'title' => 'Total Penjualan',
        'value' => number_format($metrics['sales_count'], 0, ',', '.'),
        'border' => 'border-[#E6EAFB]',
        'badge' => $sg === null ? null : ($sg >= 0 ? '+' : '') . $sg . '%',
        'badgeClass' => ($sg ?? 0) >= 0 ? $up : $down,
        'note' => $sg === null ? null : 'dari periode sebelumnya',
        'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z',
      ],
      [
        'title' => 'Stok Menipis',
        'value' => number_format($metrics['low_stock'], 0, ',', '.'),
        'border' => 'border-[#FBC9B5]',
        'badge' => $metrics['low_stock'] > 0 ? 'Perlu Perhatian' : null,
        'badgeClass' => $warn,
        'note' => null,
        'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
      ],
      [
        'title' => 'Total PO',
        'value' => number_format($metrics['total_po'], 0, ',', '.'),
        'border' => 'border-[#6B7280]',
        'badge' => null,
        'badgeClass' => '',
        'note' => null,
        'icon' => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
      ],
    ];
  @endphp

  {{-- Kartu statistik --}}
  <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 md:gap-6">
    @foreach ($stats as $s)
      <div class="rounded-2xl border-[3px] {{ $s['border'] }} bg-white p-5 dark:bg-white/[0.03] md:p-6">
        <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-xl dark:bg-gray-800">
          <svg class="h-6 w-6 text-gray-800 dark:text-white/90" fill="none" stroke="currentColor"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="{{ $s['icon'] }}" />
          </svg>
        </div>

        <div class="flex items-end justify-between mt-5">
          <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $s['title'] }}</span>
            <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $s['value'] }}</h4>
          </div>

          @if ($s['badge'])
            <span class="rounded-full px-2.5 py-0.5 text-sm font-medium {{ $s['badgeClass'] }}">{{ $s['badge'] }}</span>
          @endif
        </div>

        @if ($s['note'])
          <p class="mt-3 text-theme-xs text-gray-400">{{ $s['note'] }}</p>
        @endif
      </div>
    @endforeach
  </div>

  {{-- Grafik penjualan --}}
  <div class="grid grid-cols-12 gap-4 mt-6 md:gap-6">
    <div class="col-span-12 xl:col-span-7">
      <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Grafik Penjualan</h3>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Tren penjualan {{ $filter['days'] }} hari</p>
          </div>
          <span class="rounded-lg border border-gray-200 px-3 py-1.5 text-theme-sm text-gray-700 dark:border-gray-800 dark:text-gray-400">
            {{ $filterStart->translatedFormat('d M') }} – {{ $filterEnd->translatedFormat('d M Y') }}
          </span>
        </div>

        <div class="max-w-full overflow-x-auto custom-scrollbar">
          <div id="chartSales"
            data-labels="{{ json_encode($chart['labels']) }}"
            data-values="{{ json_encode($chart['values']) }}"
            class="h-full min-w-[500px] xl:min-w-full"></div>
        </div>
      </div>
    </div>
  </div>

@endsection