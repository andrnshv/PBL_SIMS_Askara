
@extends('layouts.app')

@section('title', 'Form Stok Opname')

@section('content')
@php
    $input = 'h-10 w-full rounded-md border bg-white px-3 text-xs text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20';
    $readonly = 'h-10 w-full rounded-md border border-slate-200 bg-slate-100 px-3 text-xs text-slate-500';
    $label = 'mb-1.5 block text-[11px] text-slate-500';

    $draft = $draft ?? [];
@endphp

<form
    method="POST"
    action="{{ isset($opname) && $opname ? route('stock.opname.update', $opname->id) : route('stock.opname.store') }}"
    x-data="opnameForm(@js($variants), @js($initial))"
    class="flex h-full flex-col"
>
    @csrf
    @if (isset($opname) && $opname)
        @method('PUT')
    @endif

    {{-- HEADER --}}
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Stok', 'url' => route('stock.index')],
                    ['label' => 'Stok Opname', 'url' => route('stock.opname')],
                    ['label' => 'Tambah Opname'],
                ]" />

                <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
                    Form Stok Opname
                </h1>

                <p class="mt-1 text-xs text-slate-500">
                    Catat stok fisik dan bandingkan dengan stok yang tercatat di sistem.
                </p>
            </div>

            <span class="rounded bg-amber-100 px-2.5 py-1 text-[10px] font-semibold text-amber-700">
                Draft
            </span>
        </div>
    </header>

    <main class="flex-1 space-y-4 overflow-y-auto bg-[#EEF2FB] px-8 py-6">

        {{-- NOTIFIKASI --}}
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-[11px] text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- ERROR VALIDASI --}}
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[11px] text-red-600">
                <p class="mb-1 font-semibold">Periksa kembali data berikut:</p>

                <ul class="list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- INFORMASI OPNAME --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-[#0A1E45]">
                        Informasi Opname
                    </h2>

                    <p class="mt-1 text-[11px] text-slate-500">
                        Informasi dasar pemeriksaan persediaan.
                    </p>
                </div>

                <span class="text-[11px] text-slate-400">
                    {{ $opnameNumber }}
                </span>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-4">

                {{-- TANGGAL --}}
                <div>
                    <label for="opname_date" class="{{ $label }}">
                        Tanggal Opname *
                    </label>

                    <input
                        type="date"
                        id="opname_date"
                        name="opname_date"
                        required
                        value="{{ old('opname_date', $draft['opname_date'] ?? now()->toDateString()) }}"
                        class="{{ $input }} {{ $errors->has('opname_date') ? 'border-red-400' : 'border-slate-200' }}"
                    >
                </div>

                {{-- GUDANG --}}
                <div>
                    <label for="warehouse" class="{{ $label }}">
                        Gudang *
                    </label>

                    <select
                        id="warehouse"
                        name="warehouse"
                        required
                        class="{{ $input }} {{ $errors->has('warehouse') ? 'border-red-400' : 'border-slate-200' }}"
                    >
                        <option value="">Pilih gudang</option>

                        @foreach ($warehouses as $warehouse)
                            <option
                                value="{{ $warehouse }}"
                                @selected(old('warehouse', $draft['warehouse'] ?? 'Gudang Utama') === $warehouse)
                            >
                                {{ $warehouse }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- PETUGAS --}}
                <div>
                    <label class="{{ $label }}">Petugas</label>

                    <input
                        type="text"
                        value="{{ $officer }}"
                        readonly
                        class="{{ $readonly }}"
                    >
                </div>

                {{-- CATATAN --}}
                <div>
                    <label for="notes" class="{{ $label }}">
                        Catatan Opname
                    </label>

                    <input
                        type="text"
                        id="notes"
                        name="notes"
                        maxlength="255"
                        value="{{ old('notes', $draft['notes'] ?? 'Pemeriksaan rutin') }}"
                        placeholder="Catatan pemeriksaan..."
                        class="{{ $input }} border-slate-200"
                    >
                </div>
            </div>
        </section>

        {{-- INFORMASI --}}
        <div class="flex items-start gap-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-[11px] text-[#4C8DF6]">
            <svg
                class="mt-0.5 h-3.5 w-3.5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
            </svg>

            <p>
                Selisih dihitung dari stok fisik dikurangi stok sistem.
                Simpan draft tidak mengubah stok. Stok sistem akan diperbarui
                setelah opname diselesaikan.
            </p>
        </div>

        {{-- HASIL HITUNG FISIK --}}
        <section class="rounded-xl border border-slate-200 bg-white">

            <div class="flex flex-wrap items-start justify-between gap-3 p-6 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-[#0A1E45]">
                        Hasil Hitung Fisik
                    </h2>

                    <p class="mt-1 text-[11px] text-slate-500">
                        <span x-text="`${filledCount} dari ${rows.length} varian telah diisi`"></span>
                        · Stok sistem diambil saat halaman dibuka:
                        {{ $snapshotTime }}.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openPicker()"
                    class="flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-[#4C8DF6] transition hover:bg-slate-50"
                >
                    <span class="text-base leading-none">+</span>
                    Tambah Varian
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left">
                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50/70 text-[10px] font-semibold text-slate-600">
                            <th class="px-6 py-3">Produk / Varian / SKU</th>
                            <th class="px-3 py-3">Stok Sistem</th>
                            <th class="px-3 py-3">Stok Fisik *</th>
                            <th class="px-3 py-3">Selisih</th>
                            <th class="px-3 py-3">Catatan Perbedaan</th>
                            <th class="px-3 py-3">Hasil</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="(r, idx) in rows" :key="r.id">
                            <tr
                                class="border-b border-slate-100"
                                :class="idx % 2 === 1 ? 'bg-slate-50/60' : 'bg-white'"
                            >
                                {{-- PRODUK --}}
                                <td class="px-6 py-4 text-[11px] leading-5 text-slate-800">
                                    <span
                                        class="block font-semibold text-[#0A1E45]"
                                        x-text="r.name"
                                    ></span>

                                    <span
                                        class="block text-slate-500"
                                        x-text="r.variant"
                                    ></span>

                                    <span
                                        class="block text-[10px] text-slate-400"
                                        x-text="r.sku"
                                    ></span>

                                    <input
                                        type="hidden"
                                        :name="`items[${idx}][variant_id]`"
                                        :value="r.id"
                                    >
                                </td>

                                {{-- STOK SISTEM --}}
                                <td
                                    class="px-3 py-4 text-[11px] text-slate-500"
                                    x-text="`${fmt(r.stock)} ${r.unit}`"
                                ></td>

                                {{-- STOK FISIK --}}
                                <td class="px-3 py-4">
                                    <div class="flex w-[130px] items-center">
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            x-model="r.counted"
                                            :name="`items[${idx}][counted]`"
                                            placeholder="0"
                                            class="h-9 w-full min-w-0 rounded-l-md border bg-white px-2.5 text-xs font-semibold text-[#4C8DF6] outline-none focus:border-[#4C8DF6]"
                                            :class="invalidCount(r) ? 'border-red-400' : 'border-slate-200'"
                                        >

                                        <span
                                            class="flex h-9 min-w-[44px] items-center justify-center rounded-r-md border border-l-0 border-slate-200 bg-slate-50 px-2 text-[10px] text-slate-500"
                                            x-text="r.unit"
                                        ></span>
                                    </div>
                                </td>

                                {{-- SELISIH --}}
                                <td
                                    class="px-3 py-4 text-[11px] font-bold"
                                    :class="!isFilled(r) ? 'text-slate-400' : (diff(r) > 0 ? 'text-emerald-700' : (diff(r) < 0 ? 'text-red-600' : 'text-slate-500'))"
                                    x-text="isFilled(r) ? signed(diff(r), r.unit) : '—'"
                                ></td>

                                {{-- CATATAN --}}
                                <td class="px-3 py-4">
                                    <input
                                        type="text"
                                        x-model="r.note"
                                        maxlength="255"
                                        :name="`items[${idx}][note]`"
                                        :placeholder="diff(r) !== 0 && isFilled(r) ? 'Wajib diisi...' : 'Catatan opsional'"
                                        class="h-9 w-[190px] rounded-md border bg-white px-2.5 text-[11px] text-slate-600 outline-none placeholder:text-slate-400 focus:border-[#4C8DF6]"
                                        :class="needsNote(r) ? 'border-red-400' : 'border-slate-200'"
                                    >
                                </td>

                                {{-- STATUS HASIL --}}
                                <td class="px-3 py-4">
                                    <span
                                        x-show="!isFilled(r)"
                                        class="text-[11px] text-slate-400"
                                    >Belum dihitung</span>

                                    <span
                                        x-show="isFilled(r) && diff(r) > 0"
                                        class="inline-block rounded bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700"
                                    >Lebih</span>

                                    <span
                                        x-show="isFilled(r) && diff(r) < 0"
                                        class="inline-block rounded bg-amber-50 px-2 py-1 text-[10px] font-bold text-amber-700"
                                    >Kurang</span>

                                    <span
                                        x-show="isFilled(r) && diff(r) === 0"
                                        class="inline-block rounded bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600"
                                    >Sesuai</span>
                                </td>

                                {{-- HAPUS VARIAN --}}
                                <td class="px-4 py-4 text-center">
                                    <button
                                        type="button"
                                        @click="removeRow(r.id)"
                                        class="text-slate-400 transition hover:text-red-500"
                                        aria-label="Hapus varian"
                                        title="Hapus varian"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M4 7h16M10 11v6m4-6v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"
                                            />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        {{-- TABEL KOSONG --}}
                        <tr x-show="rows.length === 0" x-cloak>
                            <td
                                colspan="7"
                                class="px-6 py-12 text-center text-xs text-slate-400"
                            >
                                Belum ada varian. Klik "Tambah Varian" untuk memilih produk.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-3 text-[10px]">
                <p class="text-slate-500">
                    Catatan wajib diisi untuk varian berselisih.
                    Satuan mengikuti data varian.
                </p>

                <p
                    class="text-[#4C8DF6]"
                    x-text="`${filledCount} / ${rows.length} varian terisi`"
                ></p>
            </div>
        </section>

        {{-- RINGKASAN --}}
        <section class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-6">
            <div>
                <h2 class="text-lg font-bold text-[#0A1E45]">
                    Ringkasan Pemeriksaan
                </h2>

                <p
                    class="mt-1 text-[11px] text-slate-500"
                    x-text="`${summary.counted} varian dihitung · ${summary.same} sesuai · ${summary.more.count + summary.less.count} berselisih`"
                ></p>
            </div>

            <div class="flex flex-wrap gap-8">
                <div>
                    <p
                        class="text-[11px] text-slate-500"
                        x-text="`Lebih · ${summary.more.count} varian`"
                    ></p>

                    <p
                        class="mt-1 text-xl font-extrabold text-emerald-700"
                        x-text="totalsText(summary.more.units)"
                    ></p>
                </div>

                <div>
                    <p
                        class="text-[11px] text-slate-500"
                        x-text="`Kurang · ${summary.less.count} varian`"
                    ></p>

                    <p
                        class="mt-1 text-xl font-extrabold text-red-600"
                        x-text="totalsText(summary.less.units)"
                    ></p>
                </div>
            </div>
        </section>

        {{-- FOOTER AKSI --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-[#0A1E45]">
                    Periksa kembali hasil hitung sebelum menyelesaikan opname.
                </p>

                <p
                    class="mt-1 max-w-2xl text-[11px] text-slate-500"
                    x-text="`Selesaikan opname akan menyesuaikan stok sistem untuk ${summary.more.count + summary.less.count} varian yang berselisih. Simpan draft tidak mengubah stok.`"
                ></p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('stock.opname') }}"
                    class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-[#0A1E45] transition hover:bg-slate-50"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    name="action"
                    value="draft"
                    :disabled="rows.length === 0"
                    class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-[#4C8DF6] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Simpan Draft
                </button>

                <button
                    type="submit"
                    name="action"
                    value="finish"
                    :disabled="!canFinish"
                    class="flex items-center gap-2 rounded-md bg-[#4C8DF6] px-5 py-2.5 text-xs font-semibold text-white transition hover:bg-[#3b7ae0] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <svg
                        class="h-3.5 w-3.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>

                    Selesaikan Opname
                </button>
            </div>
        </div>

        {{-- CATATAN SISTEM --}}
        <p class="flex items-start gap-2 text-[10px] text-[#4C8DF6]">
            <svg
                class="mt-0.5 h-3.5 w-3.5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
            </svg>

            <span>
                Draft tersimpan di database dan dapat dilanjutkan dari daftar opname.
                Stok baru berubah setelah opname diselesaikan.
            </span>
        </p>
    </main>

    {{-- MODAL TAMBAH VARIAN --}}
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
                <div>
                    <h3 class="text-sm font-bold text-[#0A1E45]">
                        Pilih Varian Produk
                    </h3>

                    <p class="mt-1 text-[10px] text-slate-500">
                        Pilih varian yang akan dihitung.
                    </p>
                </div>

                <button
                    type="button"
                    @click="pickerOpen = false"
                    class="text-lg leading-none text-slate-400 hover:text-slate-600"
                    aria-label="Tutup"
                >&times;</button>
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
                                class="flex w-full items-center justify-between gap-3 px-2 py-3 text-left transition hover:bg-slate-50"
                            >
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-xs font-semibold text-[#0A1E45]"
                                        x-text="v.name"
                                    ></span>

                                    <span
                                        class="mt-0.5 block text-[10px] text-[#7C93BD]"
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

            <div class="flex justify-end border-t border-slate-100 px-5 py-3">
                <button
                    type="button"
                    @click="pickerOpen = false"
                    class="rounded-md border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                >
                    Tutup
                </button>
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
    function opnameForm(variants, initial) {
        const round = n => Math.round(n * 1000) / 1000;

        return {
            variants: variants.map(v => ({
                ...v,
                id: Number(v.id),
                stock: Number(v.stock),
            })),

            rows: initial.map(item => {
                const variant = variants.find(
                    v => Number(v.id) === Number(item.id)
                );

                if (!variant) return null;

                return {
                    ...variant,
                    id: Number(variant.id),
                    stock: Number(variant.stock),
                    counted: item.counted ?? '',
                    note: item.note ?? '',
                };
            }).filter(Boolean),

            pickerOpen: false,
            pickerSearch: '',

            // PERHITUNGAN
            isFilled(r) {
                return r.counted !== ''
                    && r.counted !== null
                    && r.counted !== undefined;
            },

            diff(r) {
                if (!this.isFilled(r)) return 0;

                return round(Number(r.counted) - Number(r.stock));
            },

            invalidCount(r) {
                return this.isFilled(r)
                    && (
                        !Number.isInteger(Number(r.counted))
                        || Number(r.counted) < 0
                    );
            },

            needsNote(r) {
                return this.isFilled(r)
                    && this.diff(r) !== 0
                    && !String(r.note || '').trim();
            },

            get filledCount() {
                return this.rows.filter(r =>
                    this.isFilled(r) && !this.invalidCount(r)
                ).length;
            },

            get summary() {
                const result = {
                    counted: 0,
                    same: 0,
                    more: { count: 0, units: {} },
                    less: { count: 0, units: {} },
                };

                this.rows.forEach(r => {
                    if (!this.isFilled(r) || this.invalidCount(r)) return;

                    result.counted++;

                    const difference = this.diff(r);

                    if (difference > 0) {
                        result.more.count++;

                        result.more.units[r.unit] = round(
                            (result.more.units[r.unit] || 0) + difference
                        );
                    } else if (difference < 0) {
                        result.less.count++;

                        result.less.units[r.unit] = round(
                            (result.less.units[r.unit] || 0) + difference
                        );
                    } else {
                        result.same++;
                    }
                });

                return result;
            },

            get canFinish() {
                return this.rows.length > 0
                    && this.rows.every(r =>
                        this.isFilled(r)
                        && !this.invalidCount(r)
                        && !this.needsNote(r)
                    );
            },

            // FORMAT ANGKA
            fmt(number) {
                return Number(number).toLocaleString('id-ID');
            },

            signed(difference, unit) {
                const sign = difference > 0
                    ? '+'
                    : difference < 0 ? '−' : '';

                return `${sign}${this.fmt(Math.abs(difference))} ${unit}`;
            },

            totalsText(units) {
                const parts = Object.entries(units).map(([unit, value]) =>
                    this.signed(value, unit)
                );

                return parts.length ? parts.join(' · ') : '0';
            },

            // PILIH VARIAN
            get pickerResults() {
                const query = this.pickerSearch.toLowerCase().trim();
                const usedIds = this.rows.map(r => Number(r.id));

                return this.variants
                    .filter(v => !usedIds.includes(Number(v.id)))
                    .filter(v =>
                        [
                            v.name,
                            v.variant,
                            v.sku,
                        ].join(' ').toLowerCase().includes(query)
                    )
                    .slice(0, 30);
            },

            openPicker() {
                this.pickerSearch = '';
                this.pickerOpen = true;

                this.$nextTick(() => {
                    this.$refs.pickerInput?.focus();
                });
            },

            addRow(variant) {
                this.rows.push({
                    ...variant,
                    counted: '',
                    note: '',
                });

                this.pickerOpen = false;
            },

            removeRow(id) {
                this.rows = this.rows.filter(
                    r => Number(r.id) !== Number(id)
                );
            },
        };
    }
</script>
@endsection
