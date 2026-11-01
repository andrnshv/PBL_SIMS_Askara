@extends('layouts.app')

@section('content')
@php
    $tanggal = \Carbon\Carbon::now()->locale('id')->translatedFormat('j F Y');
    $canEditReorder = auth()->user()?->hasRole('staff_pembelian');

    $statusLabels = [
        'out' => 'Habis',
        'low' => 'Menipis',
        'ok'  => 'Tersedia',
    ];

    $statusClasses = [
        'out' => 'bg-red-50 text-red-600',
        'low' => 'bg-amber-50 text-amber-700',
        'ok'  => 'bg-blue-50 text-[#4C8DF6]',
    ];

    $stockClasses = [
        'out' => 'text-red-600',
        'low' => 'text-amber-600',
        'ok'  => 'text-slate-700',
    ];

    $filtersActive = filled($search) || filled($category) || filled($status);

    // Nilai awal untuk Alpine. Jika validasi server gagal, nilai lama dipulihkan.
    $rowState = collect($variants->items())->map(function ($v) use ($errors) {
        $id = $v['id'];

        return [
            'id'      => $id,
            'rp'      => (string) old("items.$id.reorder_point", $v['reorder_point']),
            'orig_rp' => (string) $v['reorder_point'],
            'err'     => $errors->first("items.$id.reorder_point"),
        ];
    })->values();
@endphp

<div
    class="min-h-screen bg-[#EEF2FB] px-4 py-6 sm:px-6 lg:px-8"
    x-data="reorderSettings(@js($rowState))"
>

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <x-ui.breadcrumb :items="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Reorder', 'url' => route('reorder.index')],
                ['label' => 'Pengaturan'],
            ]" class="mb-2" />

            <h1 class="text-2xl font-bold tracking-tight text-[#0A1E45]">
                Pengaturan reorder point
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Atur batas pemantauan stok untuk setiap varian produk.
            </p>
        </div>

        <a
            href="{{ route('reorder.index', ['status' => 'alert']) }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#4C8DF6] shadow-sm transition hover:bg-slate-50"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            Notifikasi reorder
            @if ($alertCount > 0)
                <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-bold leading-5 text-white">
                    {{ $alertCount }}
                </span>
            @endif
        </a>
    </div>

    {{-- Notifikasi sukses / gagal --}}
    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3">
            <p class="text-sm font-medium text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-medium text-red-700">
                Perubahan belum tersimpan. Periksa baris yang ditandai di bawah.
            </p>
            @if ($errors->has('items'))
                <p class="mt-1 text-xs text-red-600">{{ $errors->first('items') }}</p>
            @endif
        </div>
    @endif

    {{-- Info --}}
    <div class="mb-5 flex items-start gap-3 rounded-lg bg-blue-50/80 px-4 py-3 text-sm text-slate-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-[#4C8DF6]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
        </svg>
        <p>
            Data per {{ $tanggal }}. Stok ≤ reorder point memicu notifikasi aktif; stok 0 ditandai habis.
        </p>
    </div>

    {{-- Kartu utama --}}
    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">

        <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div>
                <h2 class="text-lg font-bold text-[#0A1E45]">Pemantauan per varian</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $variants->total() }} varian ditampilkan · Nilai berlaku dalam satuan bawaan SKU.
                </p>
            </div>

            <span
                x-show="dirtyRows.length > 0"
                x-cloak
                x-text="dirtyRows.length + ' perubahan belum disimpan'"
                class="inline-flex self-start rounded-md bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700"
            ></span>
        </div>

        {{-- Filter (GET) --}}
        <form
            method="GET"
            action="{{ route('reorder.index') }}"
            class="grid gap-4 border-b border-slate-100 px-5 pb-6 sm:px-6 lg:grid-cols-[1fr_220px_220px]"
        >
            <input type="hidden" name="per_page" value="{{ $perPage }}">

            <div>
                <label class="mb-1.5 block text-sm text-slate-500">Pencarian</label>
                <div class="relative">
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari nama produk, varian, atau SKU..."
                        class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-3 pr-10 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15"
                    >
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#4C8DF6]" aria-label="Cari">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />
                        </svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm text-slate-500">Kategori</label>
                <select
                    name="category"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15"
                >
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm text-slate-500">Status stok</label>
                <select
                    name="status"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15"
                >
                    <option value="">Semua status</option>
                    <option value="alert" @selected($status === 'alert')>Perlu reorder</option>
                    <option value="low" @selected($status === 'low')>Menipis</option>
                    <option value="out" @selected($status === 'out')>Habis</option>
                    <option value="ok" @selected($status === 'ok')>Tersedia</option>
                </select>
            </div>
        </form>

        @if ($filtersActive)
            <div class="border-b border-slate-100 px-5 py-3 sm:px-6">
                <a href="{{ route('reorder.index') }}" class="text-xs font-semibold text-[#4C8DF6] hover:text-blue-700">
                    Hapus semua filter
                </a>
            </div>
        @endif

        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80 text-sm text-slate-500">
                        <th class="px-6 py-4 font-medium">Produk / Varian / SKU</th>
                        <th class="px-3 py-4 font-medium">Stok saat ini</th>
                        <th class="px-3 py-4 font-medium">Satuan</th>
                        <th class="px-3 py-4 font-medium">Reorder point *</th>
                        <th class="px-6 py-4 font-medium">Status stok</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($variants as $v)
                        @php $i = $loop->index; @endphp
                        <tr class="border-b border-slate-100 align-top transition last:border-b-0 {{ $loop->even ? 'bg-slate-50/40' : 'bg-white' }}">

                            {{-- Produk / varian / SKU --}}
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-[#0A1E45]">{{ $v['product'] }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $v['variant'] }} · {{ $v['sku'] }}</div>
                            </td>

                            {{-- Stok saat ini --}}
                            <td class="px-3 py-4 pt-[1.35rem]">
                                <span class="text-sm font-medium {{ $stockClasses[$v['status']] }}">
                                    {{ number_format($v['stock'], 0, ',', '.') }} {{ $v['unit'] }}
                                </span>
                            </td>

                            {{-- Satuan --}}
                            <td class="px-3 py-4 pt-[1.35rem] text-sm text-slate-500">{{ $v['unit'] }}</td>

                            {{-- Reorder point --}}
                            <td class="px-3 py-3">
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    x-model="rows[{{ $i }}].rp"
                                    @input="rows[{{ $i }}].err = ''"
                                    :class="fieldClass(rows[{{ $i }}])"
                                    @disabled(! $canEditReorder)
                                    aria-label="Reorder point {{ $v['product'] }} {{ $v['variant'] }}"
                                    class="w-full max-w-[140px] rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#4C8DF6] disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
                                >
                                <p
                                    x-show="rowMessage(rows[{{ $i }}])"
                                    x-cloak
                                    x-text="rowMessage(rows[{{ $i }}])"
                                    class="mt-1 max-w-[200px] text-[11px] leading-4 text-red-600"
                                ></p>
                            </td>

                            {{-- Status stok --}}
                            <td class="px-6 py-4 pt-[1.2rem]">
                                <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$v['status']] }}">
                                    {{ $statusLabels[$v['status']] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <p class="text-sm font-semibold text-[#0A1E45]">Varian tidak ditemukan</p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Tidak ada varian aktif yang sesuai dengan pencarian atau filter.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer tabel: info + paginasi --}}
        <div class="flex flex-col gap-4 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="text-sm text-slate-500">
                Menampilkan {{ $variants->firstItem() ?? 0 }}–{{ $variants->lastItem() ?? 0 }}
                dari {{ $variants->total() }} varian
            </p>

            <div class="flex flex-wrap items-center gap-4">
                @if ($variants->hasPages())
                    <div class="text-sm">{{ $variants->links() }}</div>
                @endif

                <form method="GET" action="{{ route('reorder.index') }}" class="flex items-center gap-2 text-sm text-slate-500">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="category" value="{{ $category }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <select
                        name="per_page"
                        onchange="this.form.submit()"
                        class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 outline-none focus:border-[#4C8DF6]"
                    >
                        @foreach ([10, 25, 50] as $n)
                            <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                    per halaman
                </form>
            </div>
        </div>
    </div>

    {{-- Peringatan perubahan belum disimpan --}}
    <div
        x-show="dirtyRows.length > 0"
        x-cloak
        :class="invalidCount > 0 ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-800'"
        class="mt-5 flex items-start gap-3 rounded-lg px-4 py-3 text-sm"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" /><path d="M12 8v4" /><path d="M12 16h.01" />
        </svg>
        <p x-text="warningText"></p>
    </div>

    {{-- Aksi simpan (PUT) --}}
    @if ($canEditReorder)
    <form
        method="POST"
        action="{{ route('reorder.update') }}"
        class="mt-5"
        @submit="if (!canSave) { $event.preventDefault(); return; } saving = true"
    >
        @csrf
        @method('PUT')

        {{-- Hanya baris yang berubah yang dikirim ke server --}}
        <template x-for="r in dirtyRows" :key="r.id">
            <div class="hidden">
                <input type="hidden" :name="'items[' + r.id + '][reorder_point]'" :value="r.rp">
            </div>
        </template>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <button
                type="button"
                @click="reset()"
                :disabled="dirtyRows.length === 0"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#4C8DF6] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Batalkan perubahan
            </button>

            <button
                type="submit"
                :disabled="!canSave || saving"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#4C8DF6] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
                Simpan perubahan
            </button>
        </div>
    </form>
    @else
        <p class="mt-5 text-xs text-slate-400">
            Hanya Staff Pembelian yang dapat mengubah reorder point.
        </p>
    @endif

    <p class="mt-6 text-xs text-slate-400">Kuantitas mengikuti satuan setiap varian.</p>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reorderSettings', (initialRows) => ({
            rows: initialRows,
            saving: false,

            init() {
                // Peringatkan jika pengguna meninggalkan halaman dengan perubahan belum disimpan.
                window.addEventListener('beforeunload', (e) => {
                    if (this.dirtyRows.length > 0 && !this.saving) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });
            },

            isNum(v) {
                const s = String(v).trim();
                return /^\d+$/.test(s) && Number(s) <= 1000000;
            },

            isDirty(r) {
                return String(r.rp).trim() !== r.orig_rp;
            },

            validRow(r) {
                return this.isNum(r.rp);
            },

            fieldClass(r) {
                if (!this.isDirty(r)) return 'border-slate-200';
                return this.validRow(r)
                    ? 'border-[#4C8DF6] ring-2 ring-[#4C8DF6]/10'
                    : 'border-red-400 ring-2 ring-red-200';
            },

            rowMessage(r) {
                if (!this.isDirty(r)) return '';
                if (!this.validRow(r)) {
                    return 'Isi dengan bilangan bulat 0–1.000.000.';
                }
                return r.err || '';
            },

            get dirtyRows() {
                return this.rows.filter((r) => this.isDirty(r));
            },

            get invalidCount() {
                return this.dirtyRows.filter((r) => !this.validRow(r)).length;
            },

            get canSave() {
                return this.dirtyRows.length > 0 && this.invalidCount === 0;
            },

            get warningText() {
                const rule = 'Nilai harus bilangan bulat ≥ 0.';
                if (this.invalidCount > 0) {
                    return this.invalidCount + ' varian belum valid · Perubahan belum bisa disimpan. ' + rule;
                }
                return this.dirtyRows.length + ' varian diubah · Perubahan belum berlaku. ' + rule + ' Semua isian saat ini valid.';
            },

            reset() {
                this.rows.forEach((r) => {
                    r.rp = r.orig_rp;
                    r.err = '';
                });
            },
        }));
    });
</script>
@endpush