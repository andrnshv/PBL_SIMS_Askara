@extends('layouts.app')

@section('title', 'Detail Riwayat Penjualan')
@section('user', auth()->user()->name ?? 'User')
@section('role', auth()->user()->role ?? 'User')

@section('content')
@php
    $rupiah = fn ($n) => 'Rp' . number_format($n, 0, ',', '.');

    $saleDate = \Carbon\Carbon::parse($sale->sale_date);

    $isCompleted = $sale->status === 'completed';

    // Modal terbuka otomatis kalau validasi alasan pembatalan gagal.
    $openCancelModal = $errors->has('cancel_reason');
@endphp

<div class="flex h-full flex-col">

    {{-- Header --}}
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2 text-xs text-slate-500">
                    <a href="{{ route('dashboard') }}"
                    class="transition hover:text-[#4C8DF6]">
                        Dashboard
                    </a>

                    <span>/</span>

                    <a href="{{ route('sales.index') }}"
                    class="transition hover:text-[#4C8DF6]">
                        Penjualan
                    </a>

                    <span>/</span>

                    <a href="{{ route('sales.history') }}"
                    class="transition hover:text-[#4C8DF6]">
                        Riwayat Penjualan
                    </a>

                    <span>/</span>

                    <span class="font-medium text-[#0A1E45]">
                        Detail
                    </span>
                </div>

                <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
                    Detail Penjualan
                </h1>

                <p class="mt-1 text-xs text-slate-500">
                    Informasi lengkap transaksi penjualan.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">

                {{-- Tombol batalkan transaksi (hanya untuk transaksi selesai) --}}
                @if ($isCompleted)
                    <div x-data="{ open: {{ $openCancelModal ? 'true' : 'false' }} }">
                        <button
                            type="button"
                            @click="open = true"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md border border-rose-200 px-4 py-2.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                            Batalkan Transaksi
                        </button>

                        {{-- Modal alasan pembatalan --}}
                        <div
                            x-show="open"
                            x-cloak
                            x-transition.opacity
                            @keydown.escape.window="open = false"
                            class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/40 p-4"
                        >
                            <form
                                method="POST"
                                action="{{ route('sales.cancel', $sale->id) }}"
                                @click.outside="open = false"
                                class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
                            >
                                @csrf

                                <h3 class="text-base font-bold text-[#0A1E45]">
                                    Batalkan {{ $sale->invoice_no }}?
                                </h3>

                                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                                    Stok semua produk pada transaksi ini akan dikembalikan
                                    dan transaksi tidak dihitung sebagai pendapatan.
                                    Tindakan ini tidak dapat dibatalkan.
                                </p>

                                <label for="cancel_reason"
                                       class="mt-4 block text-[11px] font-semibold text-slate-600">
                                    Alasan pembatalan
                                </label>

                                <textarea
                                    id="cancel_reason"
                                    name="cancel_reason"
                                    rows="3"
                                    required
                                    minlength="5"
                                    maxlength="255"
                                    placeholder="Contoh: Salah input jumlah barang"
                                    class="mt-1 w-full rounded-md border border-slate-200 p-3 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                >{{ old('cancel_reason') }}</textarea>

                                @error('cancel_reason')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror

                                <div class="mt-5 flex justify-end gap-2">
                                    <button
                                        type="button"
                                        @click="open = false"
                                        class="rounded-md border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                                    >
                                        Tutup
                                    </button>

                                    <button
                                        type="submit"
                                        class="rounded-md bg-rose-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-rose-700"
                                    >
                                        Ya, Batalkan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                <a
                    href="{{ route('sales.history') }}"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
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
                    Kembali ke Riwayat
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 space-y-5 overflow-y-auto bg-slate-50/70 p-6 md:p-8">

        {{-- Pesan sukses / error --}}
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-xs font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-xs font-medium text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Informasi pembatalan --}}
        @if (! $isCompleted)
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-5">
                <div class="flex items-start gap-3">
                    <svg
                        class="mt-0.5 h-4 w-4 shrink-0 text-rose-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="12" cy="12" r="9"/>
                        <path stroke-linecap="round" d="M12 8v5M12 16h.01"/>
                    </svg>

                    <div>
                        <p class="text-sm font-bold text-rose-700">
                            Transaksi ini telah dibatalkan
                        </p>

                        <dl class="mt-3 grid grid-cols-1 gap-3 text-xs sm:grid-cols-3">
                            <div>
                                <dt class="text-[11px] text-rose-400">Dibatalkan oleh</dt>
                                <dd class="mt-1 font-semibold text-rose-700">
                                    {{ $sale->cancelled_by_name ?? '-' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-[11px] text-rose-400">Waktu pembatalan</dt>
                                <dd class="mt-1 font-semibold text-rose-700">
                                    @if ($sale->cancelled_at)
                                        {{ \Carbon\Carbon::parse($sale->cancelled_at)->translatedFormat('d F Y, H:i') }} WIB
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-[11px] text-rose-400">Alasan</dt>
                                <dd class="mt-1 font-semibold text-rose-700">
                                    {{ $sale->cancel_reason ?: '-' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        @endif

        {{-- Informasi utama transaksi --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-medium text-slate-400">
                        NOMOR INVOICE
                    </p>

                    <h2 class="mt-2 text-xl font-extrabold text-[#0A1E45]">
                        {{ $sale->invoice_no }}
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        ID Transaksi: #{{ $sale->id }}
                    </p>
                </div>

                <span class="inline-flex w-fit items-center rounded-full px-3 py-1.5 text-[11px] font-semibold
                    {{ $isCompleted
                        ? 'bg-emerald-50 text-emerald-700'
                        : 'bg-rose-50 text-rose-700' }}">
                    <span class="mr-1.5 h-1.5 w-1.5 rounded-full
                        {{ $isCompleted ? 'bg-emerald-500' : 'bg-rose-500' }}">
                    </span>

                    {{ $isCompleted ? 'Selesai' : 'Dibatalkan' }}
                </span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-5 border-t border-slate-100 pt-5 sm:grid-cols-3">

                <div>
                    <p class="text-[11px] text-slate-400">Tanggal Transaksi</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">
                        {{ $saleDate->translatedFormat('d F Y') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ $saleDate->format('H:i') }} WIB
                    </p>
                </div>

                <div>
                    <p class="text-[11px] text-slate-400">Nama Kasir</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">
                        {{ $sale->cashier }}
                    </p>
                </div>

                <div>
                    <p class="text-[11px] text-slate-400">Nama Pelanggan</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">
                        {{ $sale->customer_name ?: 'Pelanggan Umum' }}
                    </p>
                </div>

            </div>
        </section>

        {{-- Rincian produk --}}
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-[#0A1E45]">
                    Rincian Produk
                </h3>

                <p class="mt-1 text-[11px] text-slate-400">
                    Daftar produk yang tercatat dalam transaksi ini.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold text-[#4C8DF6]">
                            <th class="px-5 py-3">No.</th>
                            <th class="px-3 py-3">Produk</th>
                            <th class="px-3 py-3">SKU</th>
                            <th class="px-3 py-3 text-right">Harga Satuan</th>
                            <th class="px-3 py-3 text-center">Jumlah</th>
                            <th class="px-5 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($items as $item)
                            <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/70">

                                <td class="px-5 py-4 text-xs text-slate-400">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="px-3 py-4">
                                    <p class="text-xs font-semibold text-slate-700">
                                        {{ $item->product_name }}
                                    </p>

                                    <p class="mt-1 text-[11px] text-slate-400">
                                        {{ $item->variant_name }}
                                    </p>
                                </td>

                                <td class="px-3 py-4">
                                    <span class="rounded bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-600">
                                        {{ $item->sku }}
                                    </span>
                                </td>

                                <td class="px-3 py-4 text-right text-xs text-slate-600">
                                    {{ $rupiah($item->unit_price) }}
                                </td>

                                <td class="px-3 py-4 text-center text-xs font-medium text-slate-700">
                                    {{ number_format($item->quantity, 0, ',', '.') }}
                                    <span class="text-slate-400">
                                        {{ $item->unit }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-right text-xs font-semibold text-slate-700">
                                    {{ $rupiah($item->subtotal) }}
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center">
                                    <p class="text-xs text-slate-400">
                                        Tidak ada rincian produk pada transaksi ini.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Total transaksi --}}
            <div class="border-t border-slate-200 bg-slate-50/70 p-5">
                <div class="ml-auto flex max-w-sm items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-medium text-slate-500">
                            Total Pembayaran
                        </p>

                        <p class="mt-1 text-[10px] text-slate-400">
                            {{ $items->count() }} jenis varian produk
                        </p>
                    </div>

                    <p class="text-xl font-extrabold {{ $isCompleted ? 'text-[#0A1E45]' : 'text-slate-400 line-through' }}">
                        {{ $rupiah($sale->total_amount) }}
                    </p>
                </div>
            </div>

        </section>

        {{-- Catatan --}}
        <div class="flex items-start gap-2 rounded-lg border border-blue-100 bg-blue-50/60 p-4">
            <svg
                class="mt-0.5 h-4 w-4 shrink-0 text-blue-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
            </svg>

            <p class="text-[11px] leading-relaxed text-blue-700">
                Informasi pada halaman ini merupakan rincian transaksi
                yang tersimpan di sistem. Harga satuan dan subtotal
                mengikuti data saat transaksi dilakukan.
            </p>
        </div>

    </main>
</div>
@endsection