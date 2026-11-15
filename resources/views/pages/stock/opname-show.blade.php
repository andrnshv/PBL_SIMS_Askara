@extends('layouts.app')

@section('title', 'Hasil Stok Opname')

@section('content')
@php
    $withDiff = $items->where('difference', '!=', 0)->count();
@endphp

<div class="min-h-full bg-[#EEF2FB]">
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <x-ui.breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Stok', 'url' => route('stock.index')],
            ['label' => 'Stok Opname', 'url' => route('stock.opname')],
            ['label' => $opname->opname_no],
        ]" />

        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-3xl font-extrabold text-[#0A1E45]">
        Hasil Opname {{ $opname->opname_no }}
    </h1>

    <div class="flex flex-wrap items-center gap-2">
            @if ($opname->status === 'completed')
                <a href="{{ route('stock.opname.export.pdf', $opname->id) }}"
                class="rounded-md bg-[#0A1E45] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#163663]">
                    Export PDF
                </a>
            @endif

            <a href="{{ route('stock.opname') }}"
            class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-[#0A1E45] hover:bg-slate-50">
                Kembali
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

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <section class="grid grid-cols-2 gap-4 rounded-xl border border-slate-200 bg-white p-6 text-xs md:grid-cols-5">
            <div>
                <p class="text-[11px] text-slate-500">Tanggal</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">
                    {{ \Carbon\Carbon::parse($opname->opname_date)->format('d M Y') }}
                </p>
            </div>
            <div>
                <p class="text-[11px] text-slate-500">Gudang</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $warehouse }}</p>
            </div>
            <div>
                <p class="text-[11px] text-slate-500">Petugas</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $opname->officer }}</p>
            </div>
            <div>
                <p class="text-[11px] text-slate-500">Selesai pada</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">
                    {{ $opname->completed_at ? \Carbon\Carbon::parse($opname->completed_at)->format('d M Y, H.i') : '-' }}
                </p>
            </div>
            <div>
                <p class="text-[11px] text-slate-500">Varian berselisih</p>
                <p class="mt-1 font-semibold text-[#0A1E45]">{{ $withDiff }} dari {{ $items->count() }}</p>
            </div>
            <div class="col-span-full">
                <p class="text-[11px] text-slate-500">Catatan</p>
                <p class="mt-1 text-slate-700">{{ $opname->notes ?: '-' }}</p>
            </div>
        </section>

        <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full min-w-[820px] text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/70 text-[10px] font-semibold text-slate-600">
                        <th class="px-5 py-3">Produk</th>
                        <th class="px-3 py-3">SKU</th>
                        <th class="px-3 py-3">Stok sistem</th>
                        <th class="px-3 py-3">Hitung fisik</th>
                        <th class="px-3 py-3">Selisih</th>
                        <th class="px-5 py-3">Catatan</th>
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
                            <td class="px-3 py-3.5">{{ $i->system_qty }} {{ $i->unit }}</td>
                            <td class="px-3 py-3.5">{{ $i->physical_qty }} {{ $i->unit }}</td>
                            <td class="px-3 py-3.5 font-semibold
                                {{ $i->difference > 0 ? 'text-emerald-600' : ($i->difference < 0 ? 'text-red-600' : 'text-slate-500') }}">
                                {{ $i->difference > 0 ? '+' : '' }}{{ $i->difference }}
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $i->reason ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <a href="{{ route('stock.history', ['q' => $opname->opname_no]) }}"
           class="inline-block text-[11px] font-semibold text-[#4C8DF6] hover:underline">
            Lihat pergerakan stok dari opname ini →
        </a>
    </main>
</div>
@endsection
