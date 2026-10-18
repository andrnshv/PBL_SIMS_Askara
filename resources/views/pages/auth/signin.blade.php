@extends('layouts.fullscreen-layout')

@section('content')
<div class="relative z-1 bg-white p-6 sm:p-0 dark:bg-gray-900">
    <div class="relative flex min-h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-gray-900">

        {{-- Left: Login Form --}}
        <div class="flex w-full flex-1 flex-col lg:w-1/2">

            <div class="mx-auto w-full max-w-md pt-10">
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="stroke-current rtl:rotate-180"
                        xmlns="http://www.w3.org/2000/svg"
                        width="20" height="20"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    Back to dashboard
                </a>
            </div>

            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
                <div>

                    {{-- Heading --}}
                    <div class="mb-5 sm:mb-8">
                        <h1 class="text-title-sm sm:text-title-md mb-2 font-semibold text-gray-800 dark:text-white/90">
                            Sign In
                        </h1>

                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Enter your email and password to sign in!
                        </p>
                    </div>

                    {{-- Login Error Messages --}}
                    @if ($errors->any())
                        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-400"
                            role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-900 dark:bg-green-950/30 dark:text-green-400"
                            role="status">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Login Form --}}
                    <form action="{{ route('signin.store') }}" method="POST">
                        @csrf

                        <div class="space-y-5">

                            {{-- Email --}}
                            <div>
                                <label for="email"
                                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Email <span class="text-error-500">*</span>
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="info@gmail.com"
                                    autocomplete="email"
                                    required
                                    autofocus
                                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>

                            {{-- Password --}}
                            <div>
                                <label for="password"
                                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Password <span class="text-error-500">*</span>
                                </label>

                                <div x-data="{ showPassword: false }" class="relative">
                                    <input
                                        :type="showPassword ? 'text' : 'password'"
                                        id="password"
                                        name="password"
                                        placeholder="Enter your password"
                                        autocomplete="current-password"
                                        required
                                        class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                    />

                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                        class="absolute top-1/2 right-4 z-10 -translate-y-1/2 cursor-pointer text-gray-500 dark:text-gray-400">

                                        {{-- Show password icon --}}
                                        <svg x-show="!showPassword"
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="20" height="20"
                                            viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M10 4.043c-3.518 0-6.505 2.266-7.585 5.416a1.5 1.5 0 0 0 0 1.002c1.08 3.15 4.067 5.416 7.585 5.416s6.505-2.266 7.585-5.416a1.5 1.5 0 0 0 0-1.002C16.505 6.309 13.518 4.043 10 4.043Zm0 9.819a4.16 4.16 0 1 1 0-8.32 4.16 4.16 0 0 1 0 8.32Zm0-1.5a2.66 2.66 0 1 0 0-5.32 2.66 2.66 0 0 0 0 5.32Z"/>
                                        </svg>

                                        {{-- Hide password icon --}}
                                        <svg x-show="showPassword"
                                            x-cloak
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="20" height="20"
                                            viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M3.577 3.577a.75.75 0 0 1 1.061 0l11.785 11.785a.75.75 0 1 1-1.061 1.061l-1.863-1.863A10.3 10.3 0 0 1 10 15.362c-3.518 0-6.505-2.266-7.585-5.416a1.5 1.5 0 0 1 0-1.002 10.26 10.26 0 0 1 2.438-3.546L3.577 4.638a.75.75 0 0 1 0-1.061ZM5.92 6.979l6.441 6.442A8.8 8.8 0 0 1 10 13.862a7.15 7.15 0 0 1-6.077-4.16 8.7 8.7 0 0 1 1.997-2.723ZM10 4.043c3.518 0 6.505 2.266 7.585 5.416a1.5 1.5 0 0 1 0 1.002 10.2 10.2 0 0 1-1.704 2.846l-1.073-1.073a8.7 8.7 0 0 0 1.269-2.275A7.15 7.15 0 0 0 10 5.543c-.427 0-.845.041-1.248.12L7.522 4.433A10.2 10.2 0 0 1 10 4.043Z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Remember Me --}}
                            <div class="flex items-center justify-between">
                                <label for="remember"
                                    class="flex cursor-pointer items-center text-sm font-normal text-gray-700 select-none dark:text-gray-400">
                                    <input
                                        type="checkbox"
                                        id="remember"
                                        name="remember"
                                        value="1"
                                        @checked(old('remember'))
                                        class="mr-3 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900"
                                    />
                                    Keep me logged in
                                </label>

                                <span class="text-sm text-gray-400"
                                    title="Fitur reset password belum tersedia">
                                    Forgot password?
                                </span>
                            </div>

                            {{-- Submit --}}
                            <div>
                                <button
                                    type="submit"
                                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex w-full items-center justify-center rounded-lg px-4 py-3 text-sm font-medium text-white transition">
                                    Sign In
                                </button>
                            </div>

                        </div>
                    </form>

                    {{-- Sign Up Link --}}
                    <div class="mt-5">
                        <p class="text-center text-sm font-normal text-gray-700 sm:text-start dark:text-gray-400">
                            Don't have an account?
                            <a href="{{ route('signup') }}"
                                class="text-brand-500 hover:text-brand-600 dark:text-brand-400">
                                Sign Up
                            </a>
                        </p>
                    </div>

                </div>
            </div>
        </div>

        {{-- Right: Branding Panel --}}
        <div class="relative hidden min-h-screen w-full overflow-hidden bg-brand-950 lg:flex lg:w-1/2 lg:items-center lg:justify-center">

            {{-- Background decoration --}}
            <div class="pointer-events-none absolute -right-28 -top-28 h-96 w-96 rounded-full border border-white/[0.08]"></div>
            <div class="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full border border-white/[0.06]"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-brand-500/[0.08] blur-3xl"></div>

            {{-- Dot pattern --}}
            <div class="pointer-events-none absolute inset-0 opacity-[0.06]"
                style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 26px 26px;">
            </div>

            {{-- Center glow --}}
            <div class="pointer-events-none absolute left-1/2 top-1/2 h-80 w-80 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/[0.025] blur-3xl"></div>

            {{-- Branding content --}}
            <div class="relative z-10 flex w-full max-w-lg flex-col items-center px-10 py-12 text-center">

                {{-- Logo --}}
                <div class="mb-7 flex h-40 w-40 items-center justify-center rounded-[2rem] border border-white/20 bg-white p-5 shadow-2xl shadow-black/20 ring-1 ring-white/10">
                    <img
                        src="{{ asset('images/assets/logo-askara.png') }}"
                        alt="Logo PT Askara Mulia Sejahtera"
                        class="h-full w-full object-contain"
                    />
                </div>

                {{-- Brand label --}}
                <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[0.07] px-4 py-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                    <span class="text-[10px] font-semibold uppercase tracking-[0.22em] text-white/75">
                        Integrated Business System
                    </span>
                </div>

                {{-- Company name --}}
                <h2 class="text-2xl font-bold leading-relaxed tracking-wide text-white xl:text-3xl">
                    PT Askara Mulia Sejahtera
                </h2>

                {{-- Divider --}}
                <div class="my-5 flex items-center gap-2">
                    <span class="h-px w-8 bg-white/20"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                    <span class="h-px w-8 bg-white/20"></span>
                </div>

                {{-- System description --}}
                <p class="max-w-sm text-sm leading-7 text-white/70">
                    Sistem Informasi Manajemen Stok dan Penjualan
                </p>

                <p class="mt-5 max-w-xs text-xs leading-6 text-white/45">
                    Manage stock, products, and sales in one place.
                </p>

                {{-- Bottom accent --}}
                <div class="mt-9 flex items-center gap-3">
                    <span class="h-1 w-8 rounded-full bg-brand-500"></span>
                    <span class="h-1 w-2 rounded-full bg-white/25"></span>
                    <span class="h-1 w-2 rounded-full bg-white/15"></span>
                </div>

                {{-- Footer --}}
                <p class="mt-8 text-[10px] tracking-[0.18em] text-white/35">
                    &copy; {{ date('Y') }} PT Askara Mulia Sejahtera
                </p>
            </div>
        </div>

        {{-- Theme Toggle --}}
        <div class="fixed right-6 bottom-6 z-50">
            <button
                type="button"
                aria-label="Toggle theme"
                class="bg-brand-500 hover:bg-brand-600 inline-flex size-14 items-center justify-center rounded-full text-white transition-colors"
                @click.prevent="$store.theme.toggle()">

                {{-- Sun icon --}}
                <svg class="hidden fill-current dark:block"
                    width="20" height="20"
                    viewBox="0 0 20 20"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <circle cx="10" cy="10" r="3.5" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M10 1.75V3.25M10 16.75V18.25M18.25 10H16.75M3.25 10H1.75M15.83 4.17L14.77 5.23M5.23 14.77L4.17 15.83M15.83 15.83L14.77 14.77M5.23 5.23L4.17 4.17"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>

                {{-- Moon icon --}}
                <svg class="fill-current dark:hidden"
                    width="20" height="20"
                    viewBox="0 0 20 20"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.45 11.97A7.46 7.46 0 0 1 8.03 2.55 8.46 8.46 0 1 0 17.45 11.97Z"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

    </div>
</div>
@endsection
