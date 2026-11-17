@extends('layouts.app')

@section('title', 'Batch Accurate #' . $batch->id)

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $badge = [
        'unmapped' => 'bg-slate-100 text-slate-600',
        'mapped'   => 'bg-amber-50 text-amber-700',
        'verified' => 'bg-green-50 text-green-700',
        'rejected' => 'bg-red-50 text-red-700',
    ];
    $itemsUrl = url('/accurate-migration/items');
@endphp

<div class="flex h-full flex-col"
     x-data="{
        variants: @js($variants),
        products: @js($products),
        suggestions: @js($suggestions),
        itemsUrl: @js($itemsUrl),
        modal: null,        // item yang sedang diproses
        mode: 'map',        // map | reject
        tab: 'existing',    // existing | new (new hanya Admin)
        search: '',
        variantId: '',
        newProductId: '',   // produk tujuan pada tab kedua ('' = produk baru)

        get filtered() {
            const q = this.search.toLowerCase();
            return this.variants.filter(v => v.label.toLowerCase().includes(q)).slice(0, 100);
        },
        openMap(item) {
            this.modal = item; this.mode = 'map'; this.tab = 'existing'; this.search = ''; this.newProductId = '';
            this.variantId = this.suggestions[item.code.toLowerCase()] ?? '';
        },
        openReject(item) { this.modal = item; this.mode = 'reject'; },
        close() { this.modal = null; }
     }"
     @keydown.escape.window="close()">

    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <x-ui.breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Migrasi Accurate', 'url' => route('accurate.index')],
            ['label' => 'Batch #' . $batch->id],
        ]" />

        <h1 class="mt-2 text-2xl font-extrabold text-[#0A1E45]">{{ $batch->file_name }}</h1>
        <p class="mt-1 text-xs text-slate-500">
            Diunggah {{ $batch->created_at->locale('id')->translatedFormat('d M Y H:i') }}
            oleh {{ $batch->uploader?->name ?? '-' }} ·
            {{ number_format($batch->imported_rows, 0, ',', '.') }} item diimpor,
            {{ number_format($batch->failed_rows, 0, ',', '.') }} baris ditolak
        </p>
    </header>

    <main class="flex-1 space-y-5 overflow-y-auto bg-[#EEF2FB] px-8 py-6">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3">
                <p class="text-sm font-medium text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                @foreach ($errors->all() as $message)
                    <p class="text-sm font-medium text-red-700">{{ $message }}</p>
                @endforeach
            </div>
        @endif

        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            Item baru dianggap <b>data final</b> hanya setelah berstatus <b>Terverifikasi</b>.
            Pemetaan saja belum cukup, dan modul ini tidak mengubah stok varian.
        </div>

        {{-- Laporan baris yang ditolak saat impor --}}
        @if (! empty($batch->error_report))
            <details class="rounded-xl border border-red-200 bg-white shadow-sm">
                <summary class="cursor-pointer px-5 py-3 text-xs font-semibold text-red-700">
                    {{ number_format($batch->failed_rows, 0, ',', '.') }} baris ditolak saat impor (lihat rincian)
                </summary>
                <div class="max-h-72 overflow-y-auto border-t border-red-100 px-5 py-3">
                    <ul class="space-y-1 text-xs text-slate-600">
                        @foreach ($batch->error_report as $err)
                            <li>
                                <span class="font-semibold text-slate-800">Baris {{ $err['row'] }}</span>
                                @if ($err['code'] !== '') ({{ $err['code'] }}) @endif
                                — {{ $err['message'] }}
                            </li>
                        @endforeach
                    </ul>
                    @if ($batch->failed_rows > count($batch->error_report))
                        <p class="mt-2 text-[11px] text-slate-400">
                            Hanya {{ count($batch->error_report) }} kesalahan pertama yang ditampilkan.
                        </p>
                    @endif
                </div>
            </details>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            {{-- Filter --}}
            <form method="GET" action="{{ route('accurate.batches.show', $batch->id) }}"
                  class="grid grid-cols-1 gap-4 p-5 md:grid-cols-[1fr_200px_auto]">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama barang Accurate..."
                       class="h-10 w-full rounded-md border border-slate-200 px-3 text-xs outline-none focus:border-[#4C8DF6]">
                <select name="status" class="h-10 rounded-md border border-slate-200 bg-white px-3 text-xs">
                    <option value="">Semua status ({{ number_format($statusCounts->sum(), 0, ',', '.') }})</option>
                    @foreach (\App\Models\AccurateItem::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>
                            {{ $label }} ({{ number_format($statusCounts[$key] ?? 0, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button class="h-10 rounded-md bg-[#4C8DF6] px-4 text-xs font-semibold text-white">Filter</button>
                    <a href="{{ route('accurate.batches.show', $batch->id) }}"
                       class="flex h-10 items-center rounded-md border border-slate-200 px-3 text-xs text-slate-600">Reset</a>
                </div>
            </form>

            {{-- Verifikasi massal: checkbox di tabel terhubung lewat atribut form --}}
            <form id="bulk-verify" method="POST" action="{{ route('accurate.batches.verify', $batch->id) }}"
                  class="flex items-center justify-between border-y border-slate-100 bg-slate-50/60 px-5 py-2">
                @csrf
                <p class="text-[11px] text-slate-500">Centang item berstatus “Menunggu verifikasi” untuk memverifikasi sekaligus.</p>
                <button class="h-8 rounded-md bg-green-600 px-3 text-[11px] font-semibold text-white hover:bg-green-700"
                        onclick="return confirm('Verifikasi semua item yang dicentang? Item terverifikasi dianggap data final.')">
                    Verifikasi yang dicentang
                </button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/70 text-[11px] font-semibold text-[#0A1E45]">
                            <th class="w-10 px-5 py-3"></th>
                            <th class="px-3 py-3">Baris</th>
                            <th class="px-3 py-3">Data Accurate</th>
                            <th class="px-3 py-3">Stok</th>
                            <th class="px-3 py-3">Dipetakan ke</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-5 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $it)
                            @php
                                $payload = [
                                    'id' => $it->id, 'code' => $it->accurate_code, 'name' => $it->accurate_name,
                                    'unit' => $it->accurate_unit, 'price' => $it->accurate_price,
                                ];
                            @endphp
                            <tr class="border-b border-slate-100 align-top hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    @if ($it->mapping_status === 'mapped')
                                        <input type="checkbox" name="ids[]" value="{{ $it->id }}" form="bulk-verify"
                                               class="rounded border-slate-300">
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-slate-400">{{ $it->row_number }}</td>
                                <td class="px-3 py-4">
                                    <p class="font-bold text-[#0A1E45]">{{ $it->accurate_code }}</p>
                                    <p class="mt-1 text-slate-600">{{ $it->accurate_name }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400">
                                        {{ $it->accurate_unit ?: 'tanpa satuan' }}
                                        @if ($it->accurate_price !== null)
                                            · Rp {{ number_format($it->accurate_price, 0, ',', '.') }}
                                        @endif
                                        @if ($it->accurate_category)
                                            · {{ $it->accurate_category }}
                                        @endif
                                    </p>
                                </td>
                                <td class="px-3 py-4 text-slate-600">{{ number_format($it->accurate_stock, 0, ',', '.') }}</td>
                                <td class="px-3 py-4 text-slate-600">
                                    @if ($it->variant)
                                        <a href="{{ route('products.show', $it->variant->product_id) }}" class="font-semibold text-[#4C8DF6] hover:underline">
                                            {{ $it->variant->product?->name }} - {{ $it->variant->name }}
                                        </a>
                                        <p class="mt-1 text-[11px] text-slate-400">{{ $it->variant->sku }} · {{ $it->variant->unit }}</p>
                                    @elseif ($it->mapping_status === 'unmapped' && isset($suggestions[mb_strtolower($it->accurate_code)]))
                                        <span class="text-[11px] text-slate-400">Ada varian dengan SKU sama (saran di dialog)</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    <span class="inline-block rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $badge[$it->mapping_status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $it->statusLabel() }}
                                    </span>
                                    @if ($it->mapping_status === 'rejected' && $it->rejected_reason)
                                        <p class="mt-1 max-w-[200px] text-[11px] text-slate-500">{{ $it->rejected_reason }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        @if (in_array($it->mapping_status, ['unmapped', 'mapped'], true))
                                            <button type="button" @click="openMap(@js($payload))"
                                                    class="font-semibold text-[#4C8DF6] hover:underline">
                                                {{ $it->mapping_status === 'mapped' ? 'Ubah' : 'Petakan' }}
                                            </button>
                                        @endif

                                        @if ($it->mapping_status === 'mapped')
                                            <form method="POST" action="{{ route('accurate.items.verify', $it->id) }}">
                                                @csrf
                                                <button class="font-semibold text-green-600 hover:underline">Verifikasi</button>
                                            </form>
                                            <form method="POST" action="{{ route('accurate.items.unmap', $it->id) }}">
                                                @csrf
                                                <button class="font-semibold text-slate-500 hover:underline">Batalkan</button>
                                            </form>
                                        @endif

                                        @if (in_array($it->mapping_status, ['unmapped', 'mapped'], true))
                                            <button type="button" @click="openReject(@js($payload))"
                                                    class="font-semibold text-red-600 hover:underline">Tolak</button>
                                        @endif

                                        @if ($isAdmin && in_array($it->mapping_status, ['verified', 'rejected'], true))
                                            <form method="POST" action="{{ route('accurate.items.reopen', $it->id) }}"
                                                  onsubmit="return confirm('Buka kembali item ini?')">
                                                @csrf
                                                <button class="font-semibold text-slate-500 hover:underline">Buka kembali</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-14 text-center text-slate-400">Tidak ada item yang cocok dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 text-[11px] text-slate-500">
                <p>
                    Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }}
                    dari {{ number_format($items->total(), 0, ',', '.') }} item
                </p>
                <div class="flex items-center gap-3">
                    @if ($items->onFirstPage())
                        <span class="text-slate-300">‹ Sebelumnya</span>
                    @else
                        <a href="{{ $items->previousPageUrl() }}" class="hover:text-[#0A1E45]">‹ Sebelumnya</a>
                    @endif
                    <span class="font-semibold text-slate-700">{{ $items->currentPage() }} / {{ max($items->lastPage(), 1) }}</span>
                    @if ($items->hasMorePages())
                        <a href="{{ $items->nextPageUrl() }}" class="hover:text-[#0A1E45]">Berikutnya ›</a>
                    @else
                        <span class="text-slate-300">Berikutnya ›</span>
                    @endif
                </div>
            </div>
        </div>
    </main>

    {{-- Dialog pemetaan / penolakan --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/40 p-4" @click.self="close()">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
            <template x-if="modal">
                <div>
                    <p class="text-xs text-slate-500">Item Accurate</p>
                    <h3 class="text-base font-bold text-[#0A1E45]" x-text="modal.code + ' · ' + modal.name"></h3>

                    {{-- Tolak --}}
                    <form x-show="mode === 'reject'" method="POST" :action="itemsUrl + '/' + modal.id + '/reject'" class="mt-4 space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold text-slate-700">Alasan penolakan</label>
                        <textarea name="reason" rows="3" maxlength="500" required
                                  class="w-full rounded-md border border-slate-200 p-3 text-xs outline-none focus:border-[#4C8DF6]"
                                  placeholder="Contoh: produk sudah tidak dijual"></textarea>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="close()" class="h-9 rounded-md border border-slate-200 px-4 text-xs text-slate-600">Batal</button>
                            <button class="h-9 rounded-md bg-red-600 px-4 text-xs font-semibold text-white">Tolak item</button>
                        </div>
                    </form>

                    {{-- Petakan --}}
                    <div x-show="mode === 'map'" class="mt-4">
                        @if ($isAdmin)
                            <div class="mb-4 flex gap-4 border-b border-slate-200 text-xs">
                                <button type="button" @click="tab = 'existing'"
                                        :class="tab === 'existing' ? 'border-[#4C8DF6] text-[#4C8DF6]' : 'border-transparent text-slate-500'"
                                        class="-mb-px border-b-2 px-1 pb-2 font-medium">Pilih varian yang ada</button>
                                <button type="button" @click="tab = 'new'"
                                        :class="tab === 'new' ? 'border-[#4C8DF6] text-[#4C8DF6]' : 'border-transparent text-slate-500'"
                                        class="-mb-px border-b-2 px-1 pb-2 font-medium">Buat varian baru</button>
                            </div>
                        @endif

                        <form x-show="tab === 'existing'" method="POST" :action="itemsUrl + '/' + modal.id + '/map'" class="space-y-3">
                            @csrf
                            <input type="text" x-model="search" placeholder="Cari produk, varian, atau SKU..."
                                   class="h-10 w-full rounded-md border border-slate-200 px-3 text-xs outline-none focus:border-[#4C8DF6]">
                            <select name="variant_id" x-model="variantId" required size="6"
                                    class="w-full rounded-md border border-slate-200 p-1 text-xs">
                                <template x-for="v in filtered" :key="v.id">
                                    <option :value="v.id" x-text="v.label" :selected="v.id === variantId"></option>
                                </template>
                            </select>
                            <p class="text-[11px] text-slate-400" x-show="suggestions[modal.code.toLowerCase()]">
                                Varian dengan SKU sama dengan kode Accurate sudah dipilih sebagai saran. Periksa sebelum menyimpan.
                            </p>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="close()" class="h-9 rounded-md border border-slate-200 px-4 text-xs text-slate-600">Batal</button>
                                <button class="h-9 rounded-md bg-[#4C8DF6] px-4 text-xs font-semibold text-white">Simpan pemetaan</button>
                            </div>
                        </form>

                        @if ($isAdmin)
                            <form x-show="tab === 'new'" method="POST" :action="itemsUrl + '/' + modal.id + '/create-variant'" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700">Produk</label>
                                    <select name="product_id" x-model="newProductId"
                                            class="mt-1 h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-xs">
                                        <option value="">— Buat produk baru —</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div x-show="newProductId === ''">
                                    <label class="block text-xs font-semibold text-slate-700">Nama produk baru</label>
                                    <input type="text" name="product_name" :value="modal.name" maxlength="255"
                                           class="mt-1 h-10 w-full rounded-md border border-slate-200 px-3 text-xs">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="col-span-2">
                                        <label class="block text-xs font-semibold text-slate-700">Nama varian</label>
                                        <input type="text" name="variant_name" :value="modal.name" required maxlength="255"
                                               class="mt-1 h-10 w-full rounded-md border border-slate-200 px-3 text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700">SKU</label>
                                        <input type="text" name="sku" :value="modal.code" required maxlength="100"
                                               class="mt-1 h-10 w-full rounded-md border border-slate-200 px-3 text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700">Satuan</label>
                                        <input type="text" name="unit" :value="modal.unit ?? ''" required maxlength="50"
                                               class="mt-1 h-10 w-full rounded-md border border-slate-200 px-3 text-xs">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-semibold text-slate-700">Harga jual (Rp)</label>
                                        <input type="number" name="selling_price" :value="modal.price ?? 0" min="0" step="0.01" required
                                               class="mt-1 h-10 w-full rounded-md border border-slate-200 px-3 text-xs">
                                    </div>
                                </div>
                                <p class="text-[11px] text-slate-400">Stok varian baru dimulai dari 0; stok Accurate tidak diterapkan di langkah ini.</p>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="close()" class="h-9 rounded-md border border-slate-200 px-4 text-xs text-slate-600">Batal</button>
                                    <button class="h-9 rounded-md bg-[#4C8DF6] px-4 text-xs font-semibold text-white">Buat & petakan</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection