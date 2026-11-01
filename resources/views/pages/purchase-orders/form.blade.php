@extends('layouts.app')

@section('title', $po ? 'Ubah Draft PO' : 'Buat Purchase Order')

@section('content')
@php
    $input = 'h-10 w-full rounded-md border bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20';
    $readonly = 'h-10 w-full rounded-md border border-slate-200 bg-slate-100 px-3 text-xs text-slate-500';
    $label = 'mb-1.5 block text-[11px] text-slate-500';

    // Prioritas: input lama (validasi gagal) -> data draft -> kosong.
    $initial = old('items')
        ? collect(old('items'))->map(fn ($i) => [
            'id' => (int) ($i['variant_id'] ?? 0),
            'qty' => $i['qty_ordered'] ?? '',
            'cost' => $i['unit_cost'] ?? '',
        ])->values()->all()
        : $items;
@endphp

<form
    method="POST"
    action="{{ $po ? route('purchase-orders.update', $po->id) : route('purchase-orders.store') }}"
    x-data="poForm(@js($variants), @js($initial))"
    class="flex h-full flex-col"
>
    @csrf
    @if ($po)
        @method('PUT')
    @endif

    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-[11px] text-slate-400">
                    Dashboard / Pembelian / {{ $po ? 'Ubah Draft' : 'Buat PO' }}
                </p>
                <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
                    {{ $po ? 'Ubah Draft PO' : 'Buat Purchase Order' }}
                </h1>
                <p class="mt-1 text-xs text-slate-500">
                    Menyimpan atau mengirim PO tidak menambah stok.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ $po ? route('purchase-orders.show', $po->id) : route('purchase-orders.index') }}"
                   class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-[#0A1E45] hover:bg-slate-50">
                    Batal
                </a>

                <button type="submit" name="action" value="draft" :disabled="rows.length === 0"
                        class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-[#4C8DF6] hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                    Simpan Draft
                </button>

                <button type="submit" name="action" value="send" :disabled="!canSend"
                        class="rounded-md bg-[#4C8DF6] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#3b7ae0] disabled:cursor-not-allowed disabled:opacity-50">
                    Simpan & Kirim
                </button>
            </div>
        </div>
    </header>

    <main class="flex-1 space-y-4 overflow-y-auto bg-[#EEF2FB] px-8 py-6">

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                <p class="font-semibold">Periksa kembali isian berikut.</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold text-[#0A1E45]">Informasi PO</h2>

            <div class="mt-5 grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-4">
                <div>
                    <label class="{{ $label }}">Nomor PO</label>
                    <input type="text" value="{{ $poNumber }}" readonly class="{{ $readonly }}">
                </div>

                <div>
                    <label for="supplier_id" class="{{ $label }}">Supplier *</label>
                    <select id="supplier_id" name="supplier_id" required
                            class="{{ $input }} {{ $errors->has('supplier_id') ? 'border-red-400' : 'border-slate-200' }}">
                        <option value="">Pilih supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}"
                                @selected((string) old('supplier_id', $po->supplier_id ?? '') === (string) $s->id)>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="order_date" class="{{ $label }}">Tanggal PO *</label>

                    <div class="relative">
                        <input
                            type="text"
                            id="order_date"
                            name="order_date"
                            required
                            readonly
                            data-datepicker
                            value="{{ old('order_date', $po->order_date ?? now()->toDateString()) }}"
                            placeholder="Pilih tanggal PO"
                            class="{{ $input }} cursor-pointer pr-10 {{ $errors->has('order_date') ? 'border-red-400' : 'border-slate-200' }}"
                        >

                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div>
                    <label for="expected_date" class="{{ $label }}">Estimasi tiba</label>

                    <div class="relative">
                        <input
                            type="text"
                            id="expected_date"
                            name="expected_date"
                            readonly
                            data-datepicker
                            value="{{ old('expected_date', $po->expected_date ?? '') }}"
                            placeholder="Pilih estimasi tiba"
                            class="{{ $input }} cursor-pointer pr-10 {{ $errors->has('expected_date') ? 'border-red-400' : 'border-slate-200' }}"
                        >

                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
                            </svg>
                        </span>
                    </div>
                </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-[#0A1E45]">Varian dipesan</h2>

                <div class="flex items-center gap-2">
                    <select x-model="pick"
                            class="h-10 max-w-[360px] rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700">
                        <option value="">Pilih varian...</option>
                        <template x-for="v in available" :key="v.id">
                            <option :value="v.id" x-text="`${v.name} - ${v.variant} (${v.sku})`"></option>
                        </template>
                    </select>

                    <button type="button" @click="add()" :disabled="!pick"
                            class="h-10 rounded-md bg-[#4C8DF6] px-4 text-xs font-semibold text-white disabled:opacity-50">
                        + Tambah
                    </button>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50/70 text-[10px] font-semibold text-slate-600">
                            <th class="px-4 py-3">Produk / Varian</th>
                            <th class="px-3 py-3">Stok saat ini</th>
                            <th class="w-32 px-3 py-3">Jumlah</th>
                            <th class="w-44 px-3 py-3">Harga beli (Rp)</th>
                            <th class="px-3 py-3 text-right">Subtotal</th>
                            <th class="w-12 px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, idx) in rows" :key="r.id">
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-3">
                                    <input type="hidden" :name="`items[${idx}][variant_id]`" :value="r.id">
                                    <p class="font-semibold text-slate-800" x-text="r.name"></p>
                                    <p class="mt-0.5 text-slate-500" x-text="`${r.variant} · ${r.sku}`"></p>
                                </td>
                                <td class="px-3 py-3 text-slate-600" x-text="`${r.stock} ${r.unit}`"></td>
                                <td class="px-3 py-3">
                                    <input type="number" min="1" step="1" required x-model.number="r.qty"
                                           :name="`items[${idx}][qty_ordered]`"
                                           class="h-9 w-full rounded-md border border-slate-200 px-2 text-xs">
                                </td>
                                <td class="px-3 py-3">
                                    <input type="number" min="0" step="any" required x-model.number="r.cost"
                                           :name="`items[${idx}][unit_cost]`"
                                           class="h-9 w-full rounded-md border border-slate-200 px-2 text-xs">
                                </td>
                                <td class="px-3 py-3 text-right font-semibold text-[#0A1E45]"
                                    x-text="rupiah((Number(r.qty) || 0) * (Number(r.cost) || 0))"></td>
                                <td class="px-3 py-3 text-right">
                                    <button type="button" @click="remove(r.id)"
                                            class="text-red-500 hover:underline" aria-label="Hapus baris">Hapus</button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="rows.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                                Belum ada varian. Pilih varian lalu klik "Tambah".
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end text-sm">
                <p class="text-slate-500">Total:
                    <span class="ml-2 text-lg font-extrabold text-[#0A1E45]" x-text="rupiah(total)"></span>
                </p>
            </div>
        </section>
    </main>
</form>

<script>
    function poForm(variants, initial) {
        return {
            variants: variants,
            pick: '',
            rows: (initial || []).map(i => {
                const v = variants.find(x => Number(x.id) === Number(i.id));
                return v ? { ...v, qty: i.qty, cost: i.cost } : null;
            }).filter(Boolean),

            get available() {
                const used = this.rows.map(r => Number(r.id));
                return this.variants.filter(v => !used.includes(Number(v.id)));
            },

            get total() {
                return this.rows.reduce((s, r) => s + (Number(r.qty) || 0) * (Number(r.cost) || 0), 0);
            },

            get canSend() {
                return this.rows.length > 0
                    && this.rows.every(r => Number(r.qty) >= 1 && r.cost !== '' && Number(r.cost) >= 0);
            },

            add() {
                const v = this.variants.find(x => Number(x.id) === Number(this.pick));
                if (!v) return;
                this.rows.push({ ...v, qty: 1, cost: v.cost || 0 });
                this.pick = '';
            },

            remove(id) {
                this.rows = this.rows.filter(r => Number(r.id) !== Number(id));
            },

            rupiah(n) {
                return 'Rp' + Math.round(n).toLocaleString('id-ID');
            },
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
    flatpickr('[data-datepicker]', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd M Y',
        altInputClass: '{{ $input }} cursor-pointer',
        allowInput: false,
        disableMobile: true,
        monthSelectorType: 'static',
    });
});
</script>
@endsection
