 @extends('layouts.app')

@section('title', $title)

@section('content')
<div class="flex h-full flex-col">

    {{-- Header --}}
    <header class="border-b border-slate-200 bg-white px-8 py-5">
        <div>
            <x-ui.breadcrumb :items="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Pembelian', 'url' => route('purchase-orders.index')],
                ['label' => 'Manajemen Supplier', 'url' => route('suppliers.index')],
                ['label' => $title],
            ]" />

            <h1 class="mt-2 text-3xl font-extrabold text-[#0A1E45]">
                {{ $title }}
            </h1>

            <p class="mt-1 text-xs text-slate-500">
                Lengkapi informasi supplier untuk mendukung proses pembelian.
            </p>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto bg-[#EEF2FB] px-8 py-6">

        {{-- Notifikasi validasi --}}
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold">Terjadi kesalahan.</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="max-w-4xl rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-lg font-extrabold text-[#0A1E45]">
                    Informasi Supplier
                </h2>

                <p class="mt-1 text-[11px] text-slate-500">
                    Kolom bertanda <span class="font-semibold text-red-500">*</span>
                    wajib diisi.
                </p>
            </div>

            <form
                method="POST"
                action="{{ $supplier
                    ? route('suppliers.update', $supplier->id)
                    : route('suppliers.store') }}"
                class="p-6"
            >
                @csrf

                @if ($supplier)
                    @method('PUT')
                @endif

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- Nama Supplier --}}
                    <div class="md:col-span-2">
                        <label for="name"
                               class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Nama Supplier <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $supplier->name ?? '') }}"
                            required
                            maxlength="255"
                            placeholder="Masukkan nama supplier"
                            class="h-11 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                        >
                    </div>

                    {{-- Contact Person --}}
                    <div>
                        <label for="contact_person"
                               class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Contact Person
                        </label>

                        <input
                            type="text"
                            id="contact_person"
                            name="contact_person"
                            value="{{ old('contact_person', $supplier->contact_person ?? '') }}"
                            maxlength="255"
                            placeholder="Nama kontak supplier"
                            class="h-11 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                        >
                    </div>

                    {{-- Telepon --}}
                    <div>
                        <label for="phone"
                               class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Nomor Telepon
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="{{ old('phone', $supplier->phone ?? '') }}"
                            maxlength="255"
                            placeholder="Contoh: 081234567890"
                            class="h-11 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                        >
                    </div>

                    {{-- Email --}}
                    <div class="md:col-span-2">
                        <label for="email"
                               class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email', $supplier->email ?? '') }}"
                            maxlength="255"
                            placeholder="supplier@example.com"
                            class="h-11 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                        >
                    </div>

                    {{-- Alamat --}}
                    <div class="md:col-span-2">
                        <label for="address"
                               class="mb-1.5 block text-xs font-semibold text-slate-700">
                            Alamat
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            rows="4"
                            placeholder="Masukkan alamat supplier"
                            class="w-full rounded-md border border-slate-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20"
                        >{{ old('address', $supplier->address ?? '') }}</textarea>
                    </div>

                </div>

                {{-- Status saat edit --}}
                @if ($supplier)
                    <div class="mt-5 rounded-lg bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold text-slate-700">
                            Status Supplier
                        </p>

                        <p class="mt-1 text-xs {{ $supplier->is_active ? 'text-green-700' : 'text-slate-500' }}">
                            {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                        </p>

                        <p class="mt-1 text-[11px] text-slate-500">
                            Status dikelola melalui aksi pada halaman daftar supplier.
                        </p>
                    </div>
                @endif

                {{-- Tombol --}}
                <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">

                    <a
                        href="{{ route('suppliers.index') }}"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[#4C8DF6] px-5 text-xs font-semibold text-white transition hover:bg-blue-600"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M5 13l4 4L19 7"/>
                        </svg>

                        {{ $supplier ? 'Simpan Perubahan' : 'Simpan Supplier' }}
                    </button>

                </div>
            </form>
        </div>

        <div class="mt-5 max-w-4xl rounded-lg bg-blue-50 px-4 py-3 text-xs text-slate-600">
            Pastikan informasi supplier benar agar memudahkan komunikasi dan pencatatan Purchase Order.
        </div>

    </main>
</div>
@endsection
