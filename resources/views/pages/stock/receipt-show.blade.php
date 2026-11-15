@extends('layouts.app')

@section('title', 'Detail Penerimaan Barang')

@section('content')
<div class="min-h-full bg-[#EEF2FB]">
    <header class="border-b border-slate-200 bg-white px-8 py-5">

        <x-ui.breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Pembelian', 'url' => route('purchase-orders.index')],
            ['label' => 'Penerimaan Barang', 'url' => route('goods-receipts.index')],
            ['label' => $receipt->receipt_no],
        ]" />

        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl font-extrabold text-[#0A1E45]">
                {{ $receipt->receipt_no }}
            </h1>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('stock.receipts.export.pdf', $receipt->id) }}"
                class="rounded-md bg-[#0A1E45] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#163663]">
                    Export PDF
                </a>

                @if ($receipt->purchase_order_id)
                    <a href="{{ route('purchase-orders.show', $receipt->purchase_order_id) }}"
                    class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-[#0A1E45] hover:bg-slate-50">
                        Lihat PO {{ $receipt->po_number }}
                    </a>
                @endif

                <a href="{{ route('stock.index') }}"
                class="rounded-md bg-[#4C8DF6] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#3b7ae0]">
                    Ke Daftar Stok
                </a>
            </div>
        </div>
    </header>

    <main class="space-y-4 px-8 py-6">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-xs text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <section class="grid grid-cols-2 gap-4 rounded-xl border border-slate-200 bg-white p-6 text-xs md:grid-cols-5">
            <div>
                <p class="text-[11px] text-slate-500">Tanggal terima</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">
                    {{ \Carbon\Carbon::parse($receipt->receipt_date)->format('d M Y') }}
                </p>
            </div>
            <div>
                <p class="text-[11px] text-slate-500">Gudang</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $warehouse }}</p>
            </div>
            <div>
                <p class="text-[11px] text-slate-500">Supplier</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $receipt->supplier ?: '-' }}</p>
            </div>
            <div>
                <p class="text-[11px] text-slate-500">Surat jalan</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $receipt->delivery_ref ?: '-' }}</p>
            </div>
            <div>
                <p class="text-[11px] text-slate-500">Petugas</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $receipt->officer }}</p>
            </div>
            <div class="col-span-full">
                <p class="text-[11px] text-slate-500">Catatan</p>
                <p class="mt-1 text-slate-700">{{ $receipt->notes ?: '-' }}</p>
            </div>
        </section>

        <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full min-w-[600px] text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/70 text-[10px] font-semibold text-slate-600">
                        <th class="px-5 py-3">Produk</th>
                        <th class="px-3 py-3">SKU</th>
                        <th class="px-5 py-3">Jumlah diterima</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $i)
                        <tr class="border-b border-slate-100">
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-slate-800">{{ $i->product_name }}</p>
                                <p class="mt-0.5 text-slate-500">{{ $i->variant_name }}</p>
                            </td>
                            <td class="px-3 py-3.5 text-[#7C93BD]">{{ $i->sku }}</td>
                            <td class="px-5 py-3.5 font-semibold text-emerald-600">
                                +{{ number_format($i->qty_received, 0, ',', '.') }} {{ $i->unit }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </main>
</div>
@endsection
