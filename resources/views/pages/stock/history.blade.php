
@extends('layouts.app')

@section('title', 'Riwayat Pergerakan Stok')

@section('content')
<div class="min-h-full bg-[#EEF2FB]">
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <x-ui.breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Stok', 'url' => route('stock.index')],
            ['label' => 'Riwayat'],
        ]" />

        <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
            Riwayat Pergerakan Stok
        </h1>

        <p class="mt-1 text-xs text-slate-500">
            Telusuri perubahan stok produk berdasarkan transaksi yang tercatat.
        </p>
    </header>

    <main class="space-y-4 px-8 py-6">
        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <form method="GET"
                  action="{{ route('stock.history') }}"
                  class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 lg:grid-cols-5">

                <div>
                    <label class="mb-1 block text-xs text-slate-600">
                        Pencarian
                    </label>
                    <input name="q"
                           value="{{ request('q') }}"
                           placeholder="Produk, SKU, referensi..."
                           class="h-10 w-full rounded-md border border-slate-200 px-3 text-xs">
                </div>

                <div>
                    <label class="mb-1 block text-xs text-slate-600">
                        Jenis pergerakan
                    </label>
                    <select name="type"
                            class="h-10 w-full rounded-md border border-slate-200 px-3 text-xs">
                        <option value="">Semua jenis</option>
                        <option value="masuk" @selected($type === 'masuk')>
                            Stok masuk
                        </option>
                        <option value="keluar" @selected($type === 'keluar')>
                            Stok keluar
                        </option>
                        <option value="penyesuaian" @selected($type === 'penyesuaian')>
                            Penyesuaian
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs text-slate-600">
                        Dari tanggal
                    </label>
                    <input type="date" name="start" value="{{ $start }}"
                           class="h-10 w-full rounded-md border border-slate-200 px-3 text-xs">
                </div>

                <div>
                    <label class="mb-1 block text-xs text-slate-600">
                        Sampai tanggal
                    </label>
                    <input type="date" name="end" value="{{ $end }}"
                           class="h-10 w-full rounded-md border border-slate-200 px-3 text-xs">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="h-10 rounded-md bg-[#4C8DF6] px-4 text-xs font-semibold text-white">
                        Terapkan
                    </button>

                    <a href="{{ route('stock.history') }}"
                       class="flex h-10 items-center rounded-md border border-slate-200 px-4 text-xs">
                        Reset
                    </a>
                </div>

                <input type="hidden" name="per_page" value="{{ $perPage }}">

                @if ($variantId)
                    <input type="hidden" name="variant" value="{{ $variantId }}">
                @endif
            </form>

            @if ($variantInfo)
                <p class="mt-4 inline-flex items-center gap-2 rounded bg-blue-50 px-3 py-1.5 text-[11px] text-[#4C8DF6]">
                    Varian: <span class="font-semibold">{{ $variantInfo }}</span>
                    <a href="{{ route('stock.history', request()->except(['variant', 'page'])) }}"
                       class="font-bold hover:underline" aria-label="Hapus filter varian">×</a>
                </p>
            @endif
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 p-5">
                <h2 class="font-bold text-[#0A1E45]">
                    Daftar pergerakan stok
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    {{ number_format($rows->total(), 0, ',', '.') }} catatan ditemukan
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left">
                    <thead class="bg-slate-50 text-[10px] text-slate-600">
                        <tr>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-3 py-3">Produk / SKU</th>
                            <th class="px-3 py-3">Jenis</th>
                            <th class="px-3 py-3">Perubahan</th>
                            <th class="px-3 py-3">Stok sebelum</th>
                            <th class="px-3 py-3">Stok sesudah</th>
                            <th class="px-3 py-3">Referensi</th>
                            <th class="px-5 py-3">Petugas</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($movements as $m)
                            <tr class="border-t border-slate-100 text-[11px]">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    {{ $m['datetime']->format('d/m/Y H:i') }}
                                </td>

                                <td class="px-3 py-4">
                                    <p class="font-semibold text-slate-800">
                                        {{ $m['name'] }}
                                    </p>
                                    <p class="mt-1 text-slate-500">
                                        {{ $m['variant'] }} · {{ $m['sku'] }}
                                    </p>
                                </td>

                                <td class="px-3 py-4">
                                    {{ ucfirst($m['type']) }}
                                </td>

                                <td class="px-3 py-4 font-semibold
                                    {{ $m['delta'] > 0 ? 'text-emerald-600' : ($m['delta'] < 0 ? 'text-red-600' : 'text-slate-600') }}">
                                    {{ $m['delta'] > 0 ? '+' : '' }}{{ $m['delta'] }}
                                    {{ $m['unit'] }}
                                </td>

                                <td class="px-3 py-4">
                                    {{ $m['before'] }} {{ $m['unit'] }}
                                </td>

                                <td class="px-3 py-4">
                                    {{ $m['after'] }} {{ $m['unit'] }}
                                </td>

                                <td class="px-3 py-4">
                                    @if ($m['ref_url'])
                                        <a href="{{ $m['ref_url'] }}"
                                           class="font-semibold text-[#4C8DF6] hover:underline">{{ $m['ref'] }}</a>
                                    @else
                                        {{ $m['ref'] }}
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    {{ $m['officer'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    class="px-5 py-12 text-center text-xs text-slate-400">
                                    Belum ada riwayat pergerakan stok.
                                    Riwayat akan muncul setelah transaksi stok tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-xs text-slate-500">
                <p>
                    Menampilkan {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }}
                    dari {{ $rows->total() }} catatan
                </p>

                {{ $rows->links() }}
            </div>
        </section>
    </main>
</div>
@endsection
