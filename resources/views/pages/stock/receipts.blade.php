@extends('layouts.app')

@section('title', 'Penerimaan Barang')

@section('content')
<div class="flex h-full flex-col">
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-[11px] text-slate-400">Dashboard / Pembelian / Penerimaan barang</p>
                <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">Purchase Order</h1>
                <p class="mt-1 text-xs text-slate-500">
                    Dokumen penerimaan barang yang sudah menambah stok.
                </p>
            </div>

            <a href="{{ route('stock.incoming.create') }}"
               class="inline-flex items-center gap-2 rounded-md bg-[#4C8DF6] px-4 py-2.5 text-xs font-semibold text-white hover:bg-blue-600">
                + Catat Stok Masuk
            </a>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto bg-[#EEF2FB] px-8 py-6">
        <div class="mb-5 flex gap-6 border-b border-slate-200 text-sm">
            <a href="{{ route('purchase-orders.index') }}"
               class="-mb-px border-b-2 border-transparent px-1 pb-3 text-slate-500 hover:text-[#0A1E45]">
                Purchase Order
            </a>
            <span aria-current="page" class="-mb-px border-b-2 border-[#4C8DF6] px-1 pb-3 font-medium text-[#4C8DF6]">
                Penerimaan barang
            </span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('goods-receipts.index') }}"
                  class="grid grid-cols-1 gap-4 p-5 md:grid-cols-[1fr_150px_150px_auto]">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Cari nomor penerimaan, PO, supplier, atau surat jalan..."
                       class="h-10 w-full rounded-md border border-slate-200 px-3 text-xs outline-none focus:border-[#4C8DF6]">
                <input type="date" name="start" value="{{ request('start') }}"
                       class="h-10 rounded-md border border-slate-200 px-3 text-xs">
                <input type="date" name="end" value="{{ request('end') }}"
                       class="h-10 rounded-md border border-slate-200 px-3 text-xs">

                <div class="flex gap-2">
                    <button class="h-10 rounded-md bg-[#4C8DF6] px-4 text-xs font-semibold text-white">Filter</button>
                    <a href="{{ route('goods-receipts.index') }}"
                       class="flex h-10 items-center rounded-md border border-slate-200 px-3 text-xs text-slate-600">Reset</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-xs">
                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50/70 text-[11px] font-semibold text-[#0A1E45]">
                            <th class="px-5 py-3">Nomor / Tanggal</th>
                            <th class="px-3 py-3">Supplier</th>
                            <th class="px-3 py-3">Referensi PO</th>
                            <th class="px-3 py-3">Surat jalan</th>
                            <th class="px-3 py-3">Varian / Total</th>
                            <th class="px-3 py-3">Petugas</th>
                            <th class="px-5 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($receipts as $r)
                            <tr class="border-b border-slate-100 hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-[#0A1E45]">{{ $r->receipt_no }}</p>
                                    <p class="mt-1 text-slate-500">
                                        {{ \Carbon\Carbon::parse($r->receipt_date)->locale('id')->translatedFormat('d M Y') }}
                                    </p>
                                </td>
                                <td class="px-3 py-4 text-slate-600">{{ $r->supplier ?: '-' }}</td>
                                <td class="px-3 py-4 text-slate-600">{{ $r->po_number ?: 'Tanpa PO' }}</td>
                                <td class="px-3 py-4 text-slate-600">{{ $r->delivery_ref ?: '-' }}</td>
                                <td class="px-3 py-4 text-slate-600">
                                    {{ $r->variants }} varian
                                    <p class="mt-1">{{ number_format($r->total_qty, 0, ',', '.') }} unit</p>
                                </td>
                                <td class="px-3 py-4 text-slate-600">{{ $r->officer }}</td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('stock.receipts.show', $r->id) }}"
                                       class="font-semibold text-[#4C8DF6] hover:underline">Lihat detail →</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-14 text-center text-slate-400">
                                    Belum ada dokumen penerimaan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 text-[11px] text-slate-500">
                <p>
                    Menampilkan {{ $receipts->firstItem() ?? 0 }}–{{ $receipts->lastItem() ?? 0 }}
                    dari {{ number_format($receipts->total(), 0, ',', '.') }} dokumen
                </p>

                <div class="flex items-center gap-3">
                    @if ($receipts->onFirstPage())
                        <span class="text-slate-300">‹ Sebelumnya</span>
                    @else
                        <a href="{{ $receipts->previousPageUrl() }}" class="hover:text-[#0A1E45]">‹ Sebelumnya</a>
                    @endif

                    <span class="font-semibold text-slate-700">{{ $receipts->currentPage() }} / {{ max($receipts->lastPage(), 1) }}</span>

                    @if ($receipts->hasMorePages())
                        <a href="{{ $receipts->nextPageUrl() }}" class="hover:text-[#0A1E45]">Berikutnya ›</a>
                    @else
                        <span class="text-slate-300">Berikutnya ›</span>
                    @endif
                </div>

                <form method="GET" action="{{ route('goods-receipts.index') }}">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input type="hidden" name="start" value="{{ request('start') }}">
                    <input type="hidden" name="end" value="{{ request('end') }}">
                    <select name="per_page" onchange="this.form.submit()"
                            class="h-9 rounded-md border border-slate-200 bg-white px-3 text-[11px] text-slate-600">
                        @foreach ([10, 25, 50] as $n)
                            <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} per halaman</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </main>
</div>
@endsection
