@if ($visible)
<div class="relative" x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false">

    {{-- Tombol lonceng --}}
    <button
        type="button"
        @click="dropdownOpen = !dropdownOpen"
        aria-label="Notifikasi stok"
        class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-gray-700 h-11 w-11 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
    >
        {{-- Angka merah: hanya muncul kalau ada stok yang perlu reorder --}}
        @if ($total > 0)
            <span class="absolute -right-1 -top-1 z-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[11px] font-bold leading-none text-white">
                {{ $total > 99 ? '99+' : $total }}
            </span>
        @endif

        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
                fill="" />
        </svg>
    </button>

    {{-- Dropdown --}}
    <div
        x-show="dropdownOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute -right-[240px] mt-[17px] flex max-h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0"
        style="display: none;"
    >
        {{-- Judul --}}
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">Notifikasi Stok</h5>
                @if ($total > 0)
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $total }} varian perlu reorder
                        @if ($outCount > 0)
                            · {{ $outCount }} habis
                        @endif
                    </p>
                @endif
            </div>

            <button type="button" @click="dropdownOpen = false" class="text-gray-500 dark:text-gray-400" aria-label="Tutup">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                        fill="" />
                </svg>
            </button>
        </div>

        @if ($total === 0)
            {{-- Kosong: semua stok aman --}}
            <div class="px-4 py-10 text-center">
                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Semua stok aman</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Tidak ada varian yang mencapai batas reorder point.
                </p>
            </div>
        @else
            {{-- Daftar varian yang perlu reorder --}}
            <ul class="flex flex-col h-auto overflow-y-auto custom-scrollbar">
                @foreach ($items as $item)
                    <li>
                        <a
                            href="{{ route('reorder.index', ['status' => 'alert', 'q' => $item['sku']]) }}"
                            class="flex items-start justify-between gap-3 rounded-lg border-b border-gray-100 px-4 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-gray-800 dark:text-white/90">
                                    {{ $item['name'] }}
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item['variant'] }} · {{ $item['sku'] }}
                                </span>
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                    Stok
                                    <span class="font-semibold {{ $item['out'] ? 'text-red-600' : 'text-amber-600' }}">
                                        {{ number_format($item['stock'], 0, ',', '.') }} {{ $item['unit'] }}
                                    </span>
                                    / batas {{ number_format($item['min'], 0, ',', '.') }}
                                </span>
                            </span>

                            <span class="mt-0.5 shrink-0 rounded-md px-2 py-1 text-[11px] font-semibold {{ $item['out'] ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700' }}">
                                {{ $item['out'] ? 'Habis' : 'Menipis' }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Tombol lihat semua --}}
            <a
                href="{{ route('reorder.index', ['status' => 'alert']) }}"
                class="mt-3 flex justify-center rounded-lg border border-gray-300 bg-white p-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
            >
                Lihat semua ({{ $total }})
            </a>
        @endif
    </div>
</div>
@endif