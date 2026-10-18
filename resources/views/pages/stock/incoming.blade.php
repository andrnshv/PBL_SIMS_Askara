
@extends('layouts.app')

@section('title', 'Catat Stok Masuk')

@section('content')
@php
    $itemsError = $errors->first('items') ?: $errors->first('items.*');

    $input = 'h-10 w-full rounded-md border bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20';

    $readonly = 'h-10 w-full rounded-md border border-slate-200 bg-slate-100 px-3 text-xs text-slate-500';
@endphp

<form
    method="POST"
    action="{{ route('stock.store') }}"
    x-data="receiptForm(
        @js($variants),
        @js($initial)
    )"
    class="flex h-full flex-col"
>
    @csrf
    @if ($po)
        <input type="hidden" name="purchase_order_id" value="{{ $po->id }}">
    @endif

    {{-- HEADER --}}
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-[11px] text-slate-400">
                    Dashboard / Stok / Stok masuk
                </p>

                <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
                    Catat stok masuk
                </h1>

                <p class="mt-1 text-xs text-slate-500">
                    Catat penerimaan barang dan tambahkan stok pada varian yang sesuai.
                </p>
            </div>

            <a
                href="{{ route('stock.index') }}"
                class="flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-[#4C8DF6] transition hover:bg-slate-50"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 12H5m6-6l-6 6 6 6"/>
                </svg>
                Kembali ke stok
            </a>
        </div>
    </header>

    <main class="flex-1 space-y-4 overflow-y-auto bg-[#EEF2FB] px-8 py-6">

        {{-- NOTIFIKASI --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-xs text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any() && !$itemsError)
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                <p class="font-semibold">Periksa kembali input berikut:</p>

                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- BANNER INFORMASI --}}
        <div class="flex items-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-[11px] text-[#4C8DF6]">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
            </svg>

            Stok akan bertambah setelah penerimaan berhasil disimpan.
            Periksa varian, satuan, dan jumlah barang sebelum melanjutkan.
        </div>

        {{-- INFORMASI PENERIMAAN --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-[#0A1E45]">
                        Informasi penerimaan
                    </h2>

                    <p class="mt-1 text-[11px] text-slate-500">
                        Lengkapi informasi dokumen penerimaan barang.
                    </p>
                </div>

                <span class="rounded bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-500">
                    {{ $po ? 'Dari ' . $po->po_number : 'Tanpa PO' }}
                </span>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-3">

                {{-- NOMOR PENERIMAAN --}}
                <div>
                    <label class="mb-1.5 block text-[11px] text-slate-500">
                        Nomor penerimaan
                    </label>

                    <input
                        type="text"
                        value="{{ $receiptNumber }}"
                        readonly
                        class="{{ $readonly }}"
                    >

                    <p class="mt-1 text-[10px] text-slate-400">
                        Nomor final ditetapkan otomatis saat dokumen disimpan.
                    </p>
                </div>

                {{-- TANGGAL --}}
                <div>
                    <label for="received_at" class="mb-1.5 block text-[11px] text-slate-500">
                        Tanggal penerimaan
                    </label>

                    <input
                        type="date"
                        id="received_at"
                        name="received_at"
                        value="{{ old('received_at', now()->toDateString()) }}"
                        class="{{ $input }} {{ $errors->has('received_at') ? 'border-red-400' : 'border-slate-200' }}"
                    >

                    @error('received_at')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- GUDANG --}}
                <div>
                    <label for="warehouse" class="mb-1.5 block text-[11px] text-slate-500">
                        Gudang tujuan
                    </label>

                    <select
                        id="warehouse"
                        name="warehouse"
                        class="{{ $input }} {{ $errors->has('warehouse') ? 'border-red-400' : 'border-slate-200' }}"
                    >
                        <option value="">Pilih gudang</option>

                        @foreach (($warehouses ?? ['Gudang Utama']) as $w)
                            <option
                                value="{{ $w }}"
                                @selected(old('warehouse', 'Gudang Utama') === $w)
                            >
                                {{ $w }}
                            </option>
                        @endforeach
                    </select>

                    @error('warehouse')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- PEMASOK --}}
                <div>
                    <label for="supplier" class="mb-1.5 block text-[11px] text-slate-500">
                        Pemasok
                    </label>

                    <select
                        id="supplier"
                        name="supplier_id"
                        @disabled((bool) $po)
                        class="{{ $input }} {{ $errors->has('supplier_id') ? 'border-red-400' : 'border-slate-200' }}"
                    >
                        <option value="">Pilih pemasok</option>

                        @foreach ($suppliers as $s)
                            <option
                                value="{{ $s->id }}"
                                @selected((string) old('supplier_id', $po->supplier_id ?? '') === (string) $s->id)
                            >
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('supplier_id')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- SURAT JALAN --}}
                <div>
                    <label for="delivery_ref" class="mb-1.5 block text-[11px] text-slate-500">
                        Referensi surat jalan
                    </label>

                    <input
                        type="text"
                        id="delivery_ref"
                        name="delivery_ref"
                        value="{{ old('delivery_ref') }}"
                        placeholder="Masukkan nomor surat jalan"
                        maxlength="100"
                        class="{{ $input }} border-slate-200"
                    >

                    @error('delivery_ref')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- PETUGAS --}}
                <div>
                    <label class="mb-1.5 block text-[11px] text-slate-500">
                        Petugas penerima
                    </label>

                    <input
                        type="text"
                        value="{{ $officer }}"
                        readonly
                        class="{{ $readonly }}"
                    >
                </div>
            </div>
        </section>

        {{-- VARIAN YANG DITERIMA --}}
        <section class="rounded-xl border border-slate-200 bg-white">

            <div class="flex flex-wrap items-start justify-between gap-3 p-6 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-[#0A1E45]">
                        Varian yang diterima
                    </h2>

                    <p class="mt-1 text-[11px] text-slate-500">
                        Masukkan jumlah sesuai satuan bawaan setiap SKU.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openPicker()"
                    class="flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-[#4C8DF6] transition hover:bg-slate-50"
                >
                    <span class="text-base leading-none">+</span>
                    Tambah varian
                </button>
            </div>

            @if ($itemsError)
                <p class="mx-6 mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-600">
                    {{ $itemsError }}
                </p>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50/70 text-[10px] font-semibold text-slate-600">
                            <th class="px-6 py-3">Produk / Varian / SKU</th>
                            <th class="px-3 py-3">Stok saat ini</th>
                            <th class="px-3 py-3">Jumlah diterima *</th>
                            <th class="px-3 py-3">Stok setelah simpan</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="(r, idx) in rows" :key="r.id">
                            <tr
                                class="border-b border-slate-100"
                                :class="idx % 2 === 1 ? 'bg-slate-50/60' : 'bg-white'"
                            >
                                <td class="px-6 py-4">
                                    <p
                                        class="text-xs font-semibold text-[#0A1E45]"
                                        x-text="r.name"
                                    ></p>

                                    <p
                                        class="mt-0.5 text-[10px] text-[#7C93BD]"
                                        x-text="`${r.variant} · ${r.sku}`"
                                    ></p>

                                    <input
                                        type="hidden"
                                        :name="`items[${idx}][variant_id]`"
                                        :value="r.id"
                                    >
                                </td>

                                <td
                                    class="px-3 py-4 text-[11px] font-semibold text-slate-700"
                                    x-text="`${fmt(r.stock)} ${r.unit}`"
                                ></td>

                                <td class="px-3 py-4">
                                    <div class="flex w-[150px]">
                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            required
                                            x-model="r.qty"
                                            :name="`items[${idx}][qty]`"
                                            placeholder="0"
                                            class="h-10 w-full min-w-0 rounded-l-md border bg-white px-3 text-xs outline-none focus:border-[#4C8DF6]"
                                            :class="r.qty !== '' && Number(r.qty) < 1 ? 'border-red-400' : 'border-slate-200'"
                                        >

                                        <span
                                            class="flex h-10 w-[56px] shrink-0 items-center justify-center rounded-r-md border border-l-0 border-slate-200 bg-slate-50 text-[11px] text-slate-500"
                                            x-text="r.unit"
                                        ></span>
                                    </div>
                                </td>

                                <td
                                    class="px-3 py-4 text-[11px] font-semibold text-[#4C8DF6]"
                                    x-text="`${fmt(Number(r.stock) + (Number(r.qty) || 0))} ${r.unit}`"
                                ></td>

                                <td class="px-6 py-4 text-center">
                                    <button
                                        type="button"
                                        @click="removeRow(r.id)"
                                        class="inline-flex items-center justify-center text-slate-400 transition hover:text-red-500"
                                        aria-label="Hapus varian"
                                    >
                                        <svg class="h-4 w-4" fill="none"
                                             viewBox="0 0 24 24" stroke="currentColor"
                                             stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M4 7h16M10 11v6m4-6v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="rows.length === 0" x-cloak>
                            <td colspan="5" class="px-6 py-12 text-center text-xs text-slate-400">
                                Belum ada varian. Klik “Tambah varian” untuk memilih.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- CATATAN DAN RINGKASAN --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_250px]">

            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <label for="notes" class="text-lg font-bold text-[#0A1E45]">
                    Catatan penerimaan
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                    maxlength="1000"
                    placeholder="Tambahkan catatan jika perlu..."
                    class="mt-3 w-full resize-none rounded-md border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                >{{ old('notes') }}</textarea>

                @error('notes')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror

                <p class="mt-2 text-[10px] text-slate-400">
                    Catatan disimpan pada dokumen penerimaan.
                </p>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-bold text-[#0A1E45]">
                    Ringkasan penerimaan
                </h2>

                <p
                    class="mt-3 text-[11px] text-slate-700"
                    x-text="`${rows.length} varian dipilih`"
                ></p>

                <div class="mt-2 flex flex-wrap gap-2">
                    <template x-for="(qty, unit) in totalsByUnit" :key="unit">
                        <span
                            class="rounded bg-blue-50 px-2 py-1 text-[10px] font-semibold text-[#4C8DF6]"
                            x-text="`${fmt(qty)} ${unit}`"
                        ></span>
                    </template>
                </div>

                <p class="mt-3 text-[10px] text-slate-500">
                    Satuan berbeda tidak dijumlahkan.
                </p>
            </section>
        </div>

        {{-- FOOTER --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-[11px] text-slate-500">
                * Wajib diisi. Stok diperbarui setelah penyimpanan berhasil.
            </p>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('stock.index') }}"
                    class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-[#0A1E45] transition hover:bg-slate-50"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    :disabled="!canSubmit"
                    class="flex items-center gap-2 rounded-md bg-[#4C8DF6] px-5 py-2.5 text-xs font-semibold text-white transition hover:bg-[#3b7ae0] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <svg class="h-3.5 w-3.5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M5 13l4 4L19 7"/>
                    </svg>

                    Simpan penerimaan
                </button>
            </div>
        </div>

        <p class="flex items-center gap-2 text-[10px] text-[#4C8DF6]">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor"
                 stroke-width="2">
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
            </svg>

            Data varian diambil dari database. Pastikan jumlah sesuai satuan setiap SKU.
        </p>
    </main>

    {{-- MODAL PILIH VARIAN --}}
    <div
        x-show="pickerOpen"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="pickerOpen = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    >
        <div
            @click.outside="pickerOpen = false"
            class="w-full max-w-lg rounded-xl bg-white shadow-xl"
        >
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-bold text-[#0A1E45]">
                    Pilih varian
                </h3>

                <button
                    type="button"
                    @click="pickerOpen = false"
                    class="text-lg leading-none text-slate-400 hover:text-slate-600"
                    aria-label="Tutup"
                >
                    &times;
                </button>
            </div>

            <div class="p-5">
                <input
                    type="text"
                    x-model="pickerSearch"
                    x-ref="pickerInput"
                    placeholder="Cari nama produk, varian, atau SKU..."
                    class="h-10 w-full rounded-md border border-slate-200 px-3 text-xs outline-none placeholder:text-slate-400 focus:border-[#4C8DF6]"
                >

                <ul class="mt-3 max-h-72 divide-y divide-slate-100 overflow-y-auto">
                    <template x-for="v in pickerResults" :key="v.id">
                        <li>
                            <button
                                type="button"
                                @click="addRow(v)"
                                class="flex w-full items-center justify-between gap-3 px-2 py-2.5 text-left transition hover:bg-slate-50"
                            >
                                <span class="min-w-0">
                                    <span
                                        class="block text-xs font-semibold text-[#0A1E45]"
                                        x-text="v.name"
                                    ></span>

                                    <span
                                        class="block text-[10px] text-[#7C93BD]"
                                        x-text="`${v.variant} · ${v.sku}`"
                                    ></span>
                                </span>

                                <span
                                    class="shrink-0 text-[10px] text-slate-500"
                                    x-text="`Stok: ${fmt(v.stock)} ${v.unit}`"
                                ></span>
                            </button>
                        </li>
                    </template>
                </ul>

                <p
                    x-show="pickerResults.length === 0"
                    x-cloak
                    class="py-8 text-center text-xs text-slate-400"
                >
                    Varian tidak ditemukan atau sudah ditambahkan semua.
                </p>
            </div>
        </div>
    </div>
</form>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<script>
    function receiptForm(variants, initial) {
        return {
            variants: Array.isArray(variants) ? variants : [],

            rows: Object.entries(initial || {})
                .map(([id, qty]) => {
                    const variant = (variants || []).find(
                        item => Number(item.id) === Number(id)
                    );

                    return variant
                        ? { ...variant, qty: qty ?? '' }
                        : null;
                })
                .filter(Boolean),

            pickerOpen: false,
            pickerSearch: '',

            get pickerResults() {
                const query = this.pickerSearch.toLowerCase().trim();
                const usedIds = this.rows.map(row => Number(row.id));

                return this.variants
                    .filter(variant => !usedIds.includes(Number(variant.id)))
                    .filter(variant => [
                        variant.name,
                        variant.variant,
                        variant.sku
                    ].join(' ').toLowerCase().includes(query))
                    .slice(0, 30);
            },

            get totalsByUnit() {
                return this.rows.reduce((totals, row) => {
                    const qty = Number(row.qty) || 0;

                    if (qty > 0) {
                        totals[row.unit] = (totals[row.unit] || 0) + qty;
                    }

                    return totals;
                }, {});
            },

            get canSubmit() {
                return this.rows.length > 0 &&
                    this.rows.every(row => {
                        const qty = Number(row.qty);

                        return row.qty !== '' &&
                            Number.isInteger(qty) &&
                            qty > 0;
                    });
            },

            openPicker() {
                this.pickerSearch = '';
                this.pickerOpen = true;

                this.$nextTick(() => {
                    this.$refs.pickerInput?.focus();
                });
            },

            addRow(variant) {
                if (this.rows.some(row => Number(row.id) === Number(variant.id))) {
                    return;
                }

                this.rows.push({
                    ...variant,
                    qty: ''
                });

                this.pickerOpen = false;
            },

            removeRow(id) {
                this.rows = this.rows.filter(
                    row => Number(row.id) !== Number(id)
                );
            },

            fmt(value) {
                return Number(value || 0).toLocaleString('id-ID');
            }
        };
    }
</script>
@endsection
