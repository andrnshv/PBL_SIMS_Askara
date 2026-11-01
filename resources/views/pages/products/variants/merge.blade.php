@extends('layouts.app')

@section('title', 'Gabungkan Varian')

@section('content')
<div class="min-h-screen bg-[#EEF2FB] px-4 py-6 sm:px-6 lg:px-8">
    <div
        class="mx-auto max-w-4xl space-y-6"
        x-data="{
            variants: @js($variants),
            target: @js((string) old('target_variant_id', '')),
            rows: @js(count((array) old('source_ids', [])) ? array_map('strval', (array) old('source_ids')) : ['']),
            agree: false,

            init() {
                // Jika tujuan diganti ke varian yang sedang dipilih sebagai sumber, kosongkan baris itu.
                this.$watch('target', (t) => {
                    this.rows = this.rows.map(r => (r === t ? '' : r));
                });
            },

            label(v) {
                return v.name + ' (' + v.sku + ') · stok ' + this.fmt(v.stock) + ' ' + v.unit;
            },
            fmt(n) { return Number(n).toLocaleString('id-ID'); },

            get chosen() { return this.rows.filter(r => r !== ''); },
            get targetVariant() { return this.variants.find(v => v.id === this.target) || null; },
            get selectedSources() { return this.variants.filter(v => this.chosen.includes(v.id)); },
            get transfer() { return this.selectedSources.reduce((s, v) => s + v.stock, 0); },

            // Pilihan untuk dropdown sumber ke-i: bukan tujuan, dan belum dipilih di baris lain.
            sourceOptions(i) {
                return this.variants.filter(v =>
                    v.id !== this.target &&
                    !this.rows.some((r, j) => j !== i && r === v.id)
                );
            },
            // Pilihan untuk dropdown tujuan: harus Aktif dan belum dipilih sebagai sumber.
            get targetOptions() {
                return this.variants.filter(v => v.status === 'active' && !this.chosen.includes(v.id));
            },

            get canAddRow() {
                const last = this.rows[this.rows.length - 1];
                const free = this.variants.filter(v => v.id !== this.target && !this.chosen.includes(v.id));
                return last !== '' && free.length > 0;
            },
            addRow() { if (this.canAddRow) this.rows.push(''); },
            removeRow(i) { this.rows.splice(i, 1); if (this.rows.length === 0) this.rows = ['']; },

            get canSubmit() {
                return this.targetVariant !== null && this.chosen.length > 0 && this.agree;
            }
        }"
    >

        {{-- Header --}}
        <div>
            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <a href="{{ route('products.index') }}" class="hover:text-teal-700">Produk</a>
                <span>/</span>
                <a href="{{ route('products.show', $product->id) }}" class="hover:text-teal-700">{{ $product->name }}</a>
                <span>/</span>
                <span class="font-medium text-[#0A1E45]">Gabungkan Varian</span>
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-[#0A1E45]">Gabungkan Varian</h1>
            <p class="mt-1 text-sm text-slate-500">
                Gabungkan varian ganda dari produk <span class="font-semibold">{{ $product->name }}</span>.
                Stok varian yang digabungkan dipindahkan ke varian tujuan.
            </p>
        </div>

        {{-- Error validasi --}}
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-700">Penggabungan belum berhasil.</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('products.variants.merge.store', $product->id) }}"
            method="POST"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            @csrf

            <div class="border-b border-slate-100 px-5 py-5 sm:px-7">
                <h2 class="font-bold text-[#0A1E45]">Pilih varian</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Pilih varian yang ingin digabungkan, lalu pilih varian tujuan yang dipertahankan.
                </p>
            </div>

            <div class="grid grid-cols-1 items-start gap-4 px-5 py-6 sm:px-7 md:grid-cols-[1fr_auto_1fr]">

                {{-- Varian yang digabungkan --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Varian yang digabungkan <span class="text-red-500">*</span>
                    </label>

                    <div class="space-y-2">
                        <template x-for="(row, i) in rows" :key="i">
                            <div class="flex items-center gap-2">
                                <select
                                    x-model="rows[i]"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15"
                                >
                                    <option value="">Pilih varian...</option>
                                    <template x-for="v in sourceOptions(i)" :key="v.id">
                                        <option :value="v.id" :selected="rows[i] === v.id" x-text="label(v)"></option>
                                    </template>
                                </select>

                                <button
                                    type="button"
                                    x-show="rows.length > 1"
                                    @click="removeRow(i)"
                                    class="shrink-0 rounded-lg border border-slate-200 px-3 py-3 text-sm text-red-500 transition hover:bg-red-50"
                                    aria-label="Hapus pilihan ini"
                                >
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                    <button
                        type="button"
                        @click="addRow()"
                        :disabled="!canAddRow"
                        class="mt-3 text-xs font-semibold text-[#4C8DF6] transition hover:text-blue-700 disabled:cursor-not-allowed disabled:text-slate-300"
                    >
                        + Tambah varian lain
                    </button>

                    {{-- Hanya varian yang sudah dipilih yang dikirim --}}
                    <template x-for="id in chosen" :key="'src-' + id">
                        <input type="hidden" name="source_ids[]" :value="id">
                    </template>
                </div>

                {{-- Panah --}}
                <div class="flex items-center justify-center text-slate-400 md:pt-9">
                    <svg class="h-6 w-6 rotate-90 md:rotate-0" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/>
                        <path d="m13 6 6 6-6 6"/>
                    </svg>
                </div>

                {{-- Varian tujuan --}}
                <div>
                    <label for="target_variant_id" class="mb-2 block text-sm font-semibold text-slate-700">
                        Digabung ke (tujuan) <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="target_variant_id"
                        name="target_variant_id"
                        x-model="target"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-700 outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15"
                    >
                        <option value="">Pilih varian tujuan...</option>
                        <template x-for="v in targetOptions" :key="v.id">
                            <option :value="v.id" :selected="target === v.id" x-text="label(v)"></option>
                        </template>
                    </select>

                    <p class="mt-1.5 text-xs text-slate-400">
                        Varian ini dipertahankan dan hanya varian Aktif yang bisa dipilih.
                    </p>
                </div>
            </div>

            {{-- Ringkasan --}}
            <div
                class="mx-5 mb-5 rounded-lg border border-blue-100 bg-blue-50/70 p-4 sm:mx-7"
                x-show="targetVariant && chosen.length > 0"
                x-cloak
            >
                <p class="text-sm font-semibold text-[#0A1E45]">Ringkasan</p>
                <p class="mt-1 text-sm text-slate-600">
                    <span x-text="chosen.length"></span> varian akan digabung ke
                    <span class="font-semibold" x-text="targetVariant ? targetVariant.name : ''"></span>.
                    Sebanyak <span class="font-semibold" x-text="fmt(transfer)"></span> unit stok dipindahkan.
                </p>
                <p class="mt-1 text-sm text-slate-600">
                    Stok varian tujuan menjadi
                    <span class="font-semibold" x-text="targetVariant ? fmt(targetVariant.stock + transfer) : ''"></span>
                    <span x-text="targetVariant ? targetVariant.unit : ''"></span>.
                </p>
            </div>

            {{-- Alasan --}}
            <div class="px-5 pb-5 sm:px-7">
                <label for="reason" class="mb-2 block text-sm font-semibold text-slate-700">
                    Alasan penggabungan <span class="text-xs font-normal text-slate-400">(opsional)</span>
                </label>

                <textarea
                    id="reason"
                    name="reason"
                    rows="3"
                    maxlength="500"
                    placeholder="Contoh: Varian ganda karena SKU salah input."
                    class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15"
                >{{ old('reason') }}</textarea>
            </div>

            {{-- Persetujuan --}}
            <div class="px-5 pb-5 sm:px-7">
                <label class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <input
                        type="checkbox"
                        x-model="agree"
                        class="mt-0.5 h-4 w-4 rounded border-amber-300 text-teal-700 focus:ring-teal-500"
                    >
                    <span>
                        Saya mengerti bahwa penggabungan tidak dapat dibatalkan. Varian yang digabungkan
                        akan disembunyikan dan tidak bisa dipakai lagi untuk transaksi baru.
                    </span>
                </label>
            </div>

            {{-- Tombol --}}
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
                <a
                    href="{{ route('products.show', $product->id) }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    :disabled="!canSubmit"
                    class="inline-flex items-center justify-center rounded-lg bg-[#0A1E45] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#142D5C] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Gabungkan Varian
                </button>
            </div>
        </form>
    </div>
</div>
@endsection