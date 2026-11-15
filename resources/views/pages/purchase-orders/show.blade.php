@extends('layouts.app')

@section('title', 'Detail Purchase Order')

@section('content')
@php
    $rupiah = fn ($n) => 'Rp' . number_format($n, 0, ',', '.');

    $statuses = [
        'draft'     => ['label' => 'Draft',             'dot' => 'bg-slate-500', 'text' => 'text-slate-600'],
        'ordered'   => ['label' => 'Dikirim',           'dot' => 'bg-blue-500',  'text' => 'text-blue-600'],
        'partial'   => ['label' => 'Diterima sebagian', 'dot' => 'bg-amber-500', 'text' => 'text-amber-600'],
        'received'  => ['label' => 'Selesai',           'dot' => 'bg-green-600', 'text' => 'text-green-700'],
        'cancelled' => ['label' => 'Dibatalkan',        'dot' => 'bg-red-500',   'text' => 'text-red-600'],
    ];
    $st = $statuses[$po->status] ?? ['label' => ucfirst($po->status), 'dot' => 'bg-slate-400', 'text' => 'text-slate-600'];

    $anyReceived = $items->sum('qty_received') > 0;
    $fmt = fn ($d) => \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y');
@endphp

<div class="min-h-full bg-[#EEF2FB]">
    <header class="border-b border-slate-200 bg-white px-8 py-5">

        <x-ui.breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Pembelian', 'url' => route('purchase-orders.index')],
            ['label' => $po->po_number],
        ]" />

        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-4">
                <h1 class="text-3xl font-extrabold text-[#0A1E45]">{{ $po->po_number }}</h1>
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $st['text'] }}">
                    <span class="h-2 w-2 rounded-full {{ $st['dot'] }}"></span>{{ $st['label'] }}
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('purchase-orders.export.pdf', $po->id) }}"
                class="rounded-md bg-[#0A1E45] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#163663]">
                    Export PDF
                </a>

                <a href="{{ route('purchase-orders.index') }}"
                class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-[#0A1E45] hover:bg-slate-50">
                    Kembali
                </a>

                @if ($po->status === 'draft')
                    <a href="{{ route('purchase-orders.edit', $po->id) }}"
                       class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-[#4C8DF6] hover:bg-slate-50">
                        Ubah draft
                    </a>

                    <form method="POST" action="{{ route('purchase-orders.destroy', $po->id) }}"
                          onsubmit="return confirm('Hapus draft {{ $po->po_number }}?')">
                        @csrf @method('DELETE')
                        <button class="rounded-md border border-red-200 bg-white px-5 py-2.5 text-xs font-semibold text-red-500 hover:bg-red-50">
                            Hapus
                        </button>
                    </form>

                    <form method="POST" action="{{ route('purchase-orders.send', $po->id) }}">
                        @csrf
                        <button class="rounded-md bg-[#4C8DF6] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#3b7ae0]">
                            Kirim PO
                        </button>
                    </form>
                @endif

                @if (in_array($po->status, ['ordered', 'partial'], true))
                    <a href="{{ route('stock.incoming.create', ['po' => $po->id]) }}"
                       class="rounded-md bg-[#4C8DF6] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#3b7ae0]">
                        Terima barang
                    </a>
                @endif

                @if (in_array($po->status, ['draft', 'ordered'], true) && ! $anyReceived)
                    <form method="POST" action="{{ route('purchase-orders.cancel', $po->id) }}"
                          onsubmit="return confirm('Batalkan {{ $po->po_number }}?')">
                        @csrf
                        <button class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Batalkan PO
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    <main class="space-y-4 px-8 py-6">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-xs text-green-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">{{ session('error') }}</div>
        @endif

        <section class="grid grid-cols-2 gap-4 rounded-xl border border-slate-200 bg-white p-6 text-xs md:grid-cols-5">
            <div><p class="text-[11px] text-slate-500">Supplier</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $po->supplier }}</p></div>
            <div><p class="text-[11px] text-slate-500">Tanggal PO</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $fmt($po->order_date) }}</p></div>
            <div><p class="text-[11px] text-slate-500">Estimasi tiba</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $po->expected_date ? $fmt($po->expected_date) : '—' }}</p></div>
            <div><p class="text-[11px] text-slate-500">Dibuat oleh</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $po->creator }}</p></div>
            <div><p class="text-[11px] text-slate-500">Total nilai</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $rupiah($total) }}</p></div>
            <div class="col-span-full"><p class="text-[11px] text-slate-500">Catatan</p>
                <p class="mt-1 text-slate-700">{{ $po->notes ?: '-' }}</p></div>
        </section>

        <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full min-w-[820px] text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/70 text-[10px] font-semibold text-slate-600">
                        <th class="px-5 py-3">Produk</th>
                        <th class="px-3 py-3">SKU</th>
                        <th class="px-3 py-3">Dipesan</th>
                        <th class="px-3 py-3">Diterima</th>
                        <th class="px-3 py-3">Sisa</th>
                        <th class="px-3 py-3">Harga beli</th>
                        <th class="px-5 py-3 text-right">Subtotal</th>
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
                            <td class="px-3 py-3.5">{{ number_format($i->qty_ordered, 0, ',', '.') }} {{ $i->unit }}</td>
                            <td class="px-3 py-3.5">{{ number_format($i->qty_received, 0, ',', '.') }} {{ $i->unit }}</td>
                            <td class="px-3 py-3.5">{{ number_format(max($i->qty_ordered - $i->qty_received, 0), 0, ',', '.') }} {{ $i->unit }}</td>
                            <td class="px-3 py-3.5">{{ $rupiah($i->unit_cost) }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-[#0A1E45]">{{ $rupiah($i->qty_ordered * $i->unit_cost) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-bold text-[#0A1E45]">Penerimaan barang</h2>

            @forelse ($receipts as $r)
                <p class="mt-3 text-xs text-slate-600">
                    <a href="{{ route('stock.receipts.show', $r->id) }}"
                       class="font-semibold text-[#4C8DF6] hover:underline">{{ $r->receipt_no }}</a>
                    · {{ $fmt($r->receipt_date) }}
                    @if ($r->delivery_ref) · Surat jalan {{ $r->delivery_ref }} @endif
                </p>
            @empty
                <p class="mt-3 text-xs text-slate-400">Belum ada barang yang diterima.</p>
            @endforelse
        </section>
    </main>
</div>
@endsection
