@extends('layouts.app')

@section('title', 'Migrasi Data Accurate')

@section('content')
<div class="flex h-full flex-col">
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <x-ui.breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Migrasi Accurate'],
        ]" />

        <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">Migrasi Data Accurate</h1>
        <p class="mt-1 text-xs text-slate-500">
            Impor produk lama dari Accurate, petakan ke produk/varian sistem baru, lalu verifikasi.
            Hanya item yang sudah diverifikasi dianggap data final.
        </p>
    </header>

    <main class="flex-1 space-y-5 overflow-y-auto bg-[#EEF2FB] px-8 py-6">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3">
                <p class="text-sm font-medium text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>

                @if (session('import_errors'))
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-red-700">
                        @foreach (session('import_errors') as $err)
                            <li>Baris {{ $err['row'] }}{{ $err['code'] !== '' ? ' (' . $err['code'] . ')' : '' }}: {{ $err['message'] }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                @foreach ($errors->all() as $message)
                    <p class="text-sm font-medium text-red-700">{{ $message }}</p>
                @endforeach
            </div>
        @endif

        {{-- Ringkasan status seluruh item --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            @foreach ([
                ['Total item', $summary['total'], 'text-[#0A1E45]'],
                ['Belum dipetakan', $summary['unmapped'], 'text-slate-600'],
                ['Menunggu verifikasi', $summary['mapped'], 'text-amber-600'],
                ['Terverifikasi', $summary['verified'], 'text-green-600'],
                ['Ditolak', $summary['rejected'], 'text-red-600'],
            ] as [$label, $value, $color])
                <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-extrabold {{ $color }}">{{ number_format($value, 0, ',', '.') }}</p>
                </div>
            @endforeach
        </div>

        {{-- Form impor --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-[#0A1E45]">Impor file Accurate</h2>

            <form method="POST" action="{{ route('accurate.import') }}" enctype="multipart/form-data"
                  class="mt-4 flex flex-wrap items-center gap-3">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" required
                       class="block w-full max-w-md text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700">
                <button class="h-10 rounded-md bg-[#4C8DF6] px-4 text-xs font-semibold text-white hover:bg-blue-600">
                    Impor
                </button>
                <a href="{{ route('accurate.template') }}" class="text-xs font-semibold text-[#4C8DF6] hover:underline">
                    Unduh template CSV
                </a>
            </form>

            <ul class="mt-4 list-disc space-y-1 pl-5 text-[11px] text-slate-500">
                <li>Format CSV (pemisah koma, titik koma, atau tab), maksimal 2 MB dan 5.000 baris per file.</li>
                <li>Kolom wajib: <b>kode_barang</b>, <b>nama_barang</b>, <b>stok</b>. Opsional: satuan, harga_jual, kategori.</li>
                <li>Baris kosong, format salah, atau kode duplikat ditolak dan dilaporkan; baris valid lainnya tetap diimpor.</li>
                <li>File yang identik dengan batch sebelumnya tidak dapat diimpor ulang.</li>
            </ul>
        </div>

        {{-- Daftar batch --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/70 text-[11px] font-semibold text-[#0A1E45]">
                            <th class="px-5 py-3">Batch</th>
                            <th class="px-3 py-3">Diunggah</th>
                            <th class="px-3 py-3">Diimpor / Ditolak</th>
                            <th class="px-3 py-3">Belum dipetakan</th>
                            <th class="px-3 py-3">Menunggu</th>
                            <th class="px-3 py-3">Terverifikasi</th>
                            <th class="px-5 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $b)
                            <tr class="border-b border-slate-100 hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-[#0A1E45]">#{{ $b->id }} · {{ $b->file_name }}</p>
                                </td>
                                <td class="px-3 py-4 text-slate-600">
                                    {{ $b->created_at->locale('id')->translatedFormat('d M Y H:i') }}
                                    <p class="mt-1 text-slate-400">{{ $b->uploader?->name }}</p>
                                </td>
                                <td class="px-3 py-4 text-slate-600">
                                    {{ number_format($b->imported_rows, 0, ',', '.') }}
                                    / <span class="{{ $b->failed_rows > 0 ? 'font-semibold text-red-600' : '' }}">{{ number_format($b->failed_rows, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-3 py-4 text-slate-600">{{ number_format($b->unmapped_count, 0, ',', '.') }}</td>
                                <td class="px-3 py-4 text-amber-600">{{ number_format($b->mapped_count, 0, ',', '.') }}</td>
                                <td class="px-3 py-4 text-green-600">{{ number_format($b->verified_count, 0, ',', '.') }}</td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('accurate.batches.show', $b->id) }}"
                                       class="font-semibold text-[#4C8DF6] hover:underline">Buka →</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-14 text-center text-slate-400">
                                    Belum ada batch impor. Unggah file CSV Accurate untuk memulai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($batches->hasPages())
                <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 text-[11px] text-slate-500">
                    <p>{{ $batches->firstItem() }}–{{ $batches->lastItem() }} dari {{ $batches->total() }} batch</p>
                    <div class="flex items-center gap-3">
                        @if ($batches->onFirstPage())
                            <span class="text-slate-300">‹ Sebelumnya</span>
                        @else
                            <a href="{{ $batches->previousPageUrl() }}" class="hover:text-[#0A1E45]">‹ Sebelumnya</a>
                        @endif
                        @if ($batches->hasMorePages())
                            <a href="{{ $batches->nextPageUrl() }}" class="hover:text-[#0A1E45]">Berikutnya ›</a>
                        @else
                            <span class="text-slate-300">Berikutnya ›</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </main>
</div>
@endsection