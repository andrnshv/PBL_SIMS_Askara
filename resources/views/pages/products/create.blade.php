@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')
<div class="min-h-screen bg-[#EEF2FB] px-4 py-6 sm:px-6 lg:px-8">

    {{-- Header halaman --}}
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ url('/dashboard') }}"
               class="transition hover:text-[#4C8DF6]">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('products.index') }}"
               class="transition hover:text-[#4C8DF6]">
                Produk
            </a>

            <span>/</span>

            <span class="font-medium text-[#0A1E45]">
                Tambah Produk
            </span>
        </div>

        <h1 class="text-2xl font-bold tracking-tight text-[#0A1E45]">
            Tambah Produk
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Tambahkan produk induk baru ke dalam sistem inventaris.
        </p>
    </div>

    {{-- Notifikasi validasi --}}
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="flex items-start gap-3">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="mt-0.5 h-5 w-5 shrink-0 text-red-500"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 8v4" />
                    <path d="M12 16h.01" />
                </svg>

                <div>
                    <p class="text-sm font-semibold text-red-700">
                        Produk belum dapat disimpan.
                    </p>

                    <ul class="mt-1 list-inside list-disc space-y-1 text-xs text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Form produk --}}
    <form
        action="{{ route('products.store') }}"
        method="POST"
        class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"
    >
        @csrf

        {{-- Judul form --}}
        <div class="border-b border-slate-100 px-5 py-5 sm:px-7">
            <h2 class="text-base font-bold text-[#0A1E45]">
                Informasi Produk
            </h2>

            <p class="mt-1 text-xs text-slate-500">
                Lengkapi informasi dasar produk yang akan didaftarkan.
            </p>
        </div>

        <div class="space-y-6 px-5 py-6 sm:px-7">

            {{-- Nama produk --}}
            <div>
                <label
                    for="name"
                    class="mb-2 block text-sm font-semibold text-slate-700"
                >
                    Nama Produk
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Contoh: Air Mineral"
                    maxlength="255"
                    required
                    autofocus
                    aria-describedby="name-help"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15 @error('name') border-red-400 @enderror"
                >

                <p id="name-help" class="mt-1.5 text-xs text-slate-400">
                    Masukkan nama produk induk, bukan nama SKU atau varian.
                </p>

                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Kategori --}}
            <div>
                <label for="category" class="mb-2 block text-sm font-semibold text-slate-700">
                    Kategori
                </label>

                <div
                x-data="{
                    open: false,
                    q: @js(old('category', '')),
                    options: @js($categories),
                    picked: @js(in_array(old('category'), $categories->all(), true)),
                    get filtered() {
                        const q = this.picked ? '' : this.q.toLowerCase();
                        return this.options.filter(o => o.toLowerCase().includes(q));
                    },
                    get isNew() {
                        return !this.picked && this.q.trim() !== '' &&
                            !this.options.some(o => o.toLowerCase() === this.q.trim().toLowerCase());
                    },
                    choose(o) { this.q = o; this.picked = true; this.open = false; },
                    reset(el) { if (this.picked) { this.q = ''; el.value = ''; this.picked = false; } }
                }"
                @click.outside="open = false"
                class="relative"
            >
                <input
                    type="text"
                    id="category"
                    name="category"
                    x-model="q"
                    @focus="open = true"
                    @input="open = true"
                    @keydown.escape="open = false"
                    @keydown="if ($event.key.length === 1 && !$event.ctrlKey && !$event.metaKey && !$event.altKey || ['Backspace','Delete'].includes($event.key)) reset($el)"
                    @paste="reset($el)"
                    autocomplete="off"
                    maxlength="255"
                    placeholder="Pilih kategori atau ketik kategori baru"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15 @error('category') border-red-400 @enderror"
                >

                <ul x-show="open" x-cloak
                    class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 text-sm shadow-lg">
                    <template x-for="o in filtered" :key="o">
                        <li @mousedown.prevent="choose(o)" x-text="o"
                            class="cursor-pointer px-3.5 py-2.5 text-slate-700 hover:bg-blue-50"></li>
                    </template>

                    <li x-show="isNew" @mousedown.prevent="open = false"
                        x-text="'+ Buat kategori baru: ' + q"
                        class="cursor-pointer border-t border-slate-100 px-3.5 py-2.5 font-medium text-[#4C8DF6] hover:bg-blue-50"></li>
                </ul>
            </div>

                <p class="mt-1.5 text-xs text-slate-400">
                    Pilih kategori yang sudah ada atau ketik untuk membuat kategori baru.
                </p>

                @error('category')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Deskripsi --}}
            <div>
                <label
                    for="description"
                    class="mb-2 block text-sm font-semibold text-slate-700"
                >
                    Deskripsi Produk
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Tuliskan informasi tambahan mengenai produk..."
                    class="w-full resize-y rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15 @error('description') border-red-400 @enderror"
                >{{ old('description') }}</textarea>

                <p class="mt-1.5 text-xs text-slate-400">
                    Deskripsi dapat digunakan untuk menjelaskan karakteristik produk.
                </p>

                @error('description')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div>
                <label
                    for="status"
                    class="mb-2 block text-sm font-semibold text-slate-700"
                >
                    Status Produk
                    <span class="text-red-500">*</span>
                </label>

                <select
                    id="status"
                    name="status"
                    required
                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-700 outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/15 @error('status') border-red-400 @enderror"
                >
                    <option
                        value="active"
                        {{ old('status', 'active') === 'active' ? 'selected' : '' }}
                    >
                        Aktif
                    </option>

                    <option
                        value="inactive"
                        {{ old('status') === 'inactive' ? 'selected' : '' }}
                    >
                        Tidak Aktif
                    </option>
                </select>

                <p class="mt-1.5 text-xs text-slate-400">
                    Produk tidak aktif dapat ditandai tanpa menghapus data produknya.
                </p>

                @error('status')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

        </div>

        {{-- Tombol aksi --}}
        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
            <a
                href="{{ route('products.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
            >
                Batal
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0A1E45] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#142D5C] focus:outline-none focus:ring-2 focus:ring-[#4C8DF6] focus:ring-offset-2"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                </svg>

                Simpan Produk
            </button>
        </div>
    </form>

    {{-- Informasi tambahan --}}
    <div class="mt-4 flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50/70 p-4">
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="mt-0.5 h-5 w-5 shrink-0 text-[#4C8DF6]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <circle cx="12" cy="12" r="10" />
            <path d="M12 16v-4" />
            <path d="M12 8h.01" />
        </svg>

        <div>
            <p class="text-sm font-semibold text-[#0A1E45]">
                Informasi varian produk
            </p>

            <p class="mt-1 text-xs leading-5 text-slate-600">
                Form ini membuat produk induk. SKU, satuan, harga jual, stok awal, dan reorder point akan dikelola pada fitur varian produk setelah fitur tersebut tersedia.
            </p>
        </div>
    </div>
</div>
@endsection
