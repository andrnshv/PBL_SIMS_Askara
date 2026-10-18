
@extends('layouts.app')

@section('content')
    <div x-data="{ isProfileInfoModal: {{ $errors->any() ? 'true' : 'false' }} }">
        <x-common.page-breadcrumb pageTitle="User Profile" />

        <div class="space-y-6">

            {{-- Notifikasi berhasil --}}
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Notifikasi validasi --}}
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-700">
                    <p class="font-semibold">Profil belum berhasil diperbarui.</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Profile Information --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                            My Profile
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Informasi akun yang sedang kamu gunakan.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="isProfileInfoModal = true"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#0A1E45] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#163568]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg"
                            width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                        </svg>
                        Edit Profile
                    </button>
                </div>

                <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                    {{-- Avatar inisial --}}
                    <div class="flex size-20 shrink-0 items-center justify-center rounded-full bg-[#E8EEFC] text-2xl font-semibold text-[#0A1E45] dark:bg-white/10 dark:text-white">
                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <h4 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                            {{ $user->name }}
                        </h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->email }}
                        </p>
                        <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                            <span class="size-1.5 rounded-full bg-blue-500"></span>
                            Akun Pengguna
                        </div>
                    </div>
                </div>

                <div class="mt-7 border-t border-gray-200 pt-6 dark:border-gray-800">
                    <h4 class="mb-5 text-sm font-semibold text-gray-800 dark:text-white/90">
                        Personal Information
                    </h4>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                Nama Lengkap
                            </p>
                            <p class="break-words text-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $user->name }}
                            </p>
                        </div>

                        <div>
                            <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                Email Address
                            </p>
                            <p class="break-words text-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $user->email }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Account Information --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Account Information
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Informasi dasar akun yang digunakan untuk mengakses Askara Stock.
                </p>

                <div class="mt-5 flex items-start gap-3 rounded-xl bg-gray-50 p-4 dark:bg-white/[0.03]">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#E8EEFC] text-[#0A1E45] dark:bg-white/10 dark:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                            Informasi akun
                        </p>
                        <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                            Gunakan alamat email yang aktif agar informasi akun tetap akurat.
                            Jika nama atau email berubah, perbarui melalui tombol Edit Profile.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Edit Profile Modal --}}
        <div
            x-show="isProfileInfoModal"
            x-cloak
            x-transition.opacity
            @keydown.escape.window="isProfileInfoModal = false"
            class="fixed inset-0 z-[99999] flex items-center justify-center overflow-y-auto p-4 sm:p-6"
        >
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
                @click="isProfileInfoModal = false"
            ></div>

            {{-- Modal Content --}}
            <div
                class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-7 dark:bg-gray-900"
                @click.stop
            >
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                            Edit Profile
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Perbarui nama dan email akunmu.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="isProfileInfoModal = false"
                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 dark:hover:bg-white/10"
                        aria-label="Tutup modal"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg"
                            width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="m18 6-12 12M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-5">
                        <div>
                            <label for="name"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nama Lengkap
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                required
                                maxlength="255"
                                autocomplete="name"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            @error('name')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Email Address
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                required
                                maxlength="255"
                                autocomplete="email"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-[#4C8DF6] focus:ring-2 focus:ring-[#4C8DF6]/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            @error('email')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            @click="isProfileInfoModal = false"
                            class="h-10 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="h-10 rounded-lg bg-[#0A1E45] px-5 text-sm font-medium text-white transition hover:bg-[#163568]"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
