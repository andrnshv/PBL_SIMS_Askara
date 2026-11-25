<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full bg-gray-50 dark:bg-gray-900">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $title ?? 'Dashboard')</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Theme Store -->
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <!-- Theme Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                theme: 'light',
                resolvedTheme: 'light',

                init() {
                    this.updateTheme();
                },

                set() {
                    this.theme = 'light';
                    this.updateTheme();

                    window.dispatchEvent(
                        new CustomEvent('theme-changed', { detail: 'light' })
                    );
                },

                toggle() {
                    this.set();
                },

                updateTheme() {
                    const html = document.documentElement;

                    html.classList.remove('dark');
                    html.setAttribute('data-color-scheme', 'light');
                    html.dataset.theme = 'light';
                    html.style.colorScheme = 'light';

                    this.theme = 'light';
                    this.resolvedTheme = 'light';

                    if (document.body) {
                        document.body.classList.remove('dark');
                        document.body.dataset.theme = 'light';
                        document.body.style.colorScheme = 'light';
                    }
                }
            });

            Alpine.store('sidebar', {
                isExpanded: false,
                isMobileOpen: false,
                isHovered: false,

                init() {
                    const savedState = localStorage.getItem('sidebarExpanded');
                    if (window.innerWidth >= 1280) {
                        this.isExpanded = savedState === null ? true : savedState === 'true';
                    } else {
                        this.isExpanded = false;
                    }
                    this.isMobileOpen = false;

                    window.addEventListener('resize', () => {
                        this.handleResize();
                    });
                },

                handleResize() {
                    if (window.innerWidth < 1280) {
                        if (this.isMobileOpen) {
                             this.isMobileOpen = false;
                        }
                    } else {
                        this.isMobileOpen = false;
                        const savedState = localStorage.getItem('sidebarExpanded');
                        this.isExpanded = savedState === null ? true : savedState === 'true';
                    }
                },

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    this.isMobileOpen = false;

                    if (window.innerWidth >= 1280) {
                        localStorage.setItem('sidebarExpanded', this.isExpanded);
                    }
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <!-- Apply RTL and dark mode immediately to prevent flash -->
    <script>
        (function() {
            const savedDir = localStorage.getItem('dir');
            const savedLocale = localStorage.getItem('locale');
            if (savedDir) {
                document.documentElement.setAttribute('dir', savedDir);
            } else if (savedLocale === 'ar') {
                document.documentElement.setAttribute('dir', 'rtl');
            }
            if (savedLocale) {
                document.documentElement.setAttribute('lang', savedLocale);
            }

            // Force Light Mode globally.
            localStorage.setItem('theme', 'light');

            const html = document.documentElement;

            html.classList.remove('dark');
            html.setAttribute('data-color-scheme', 'light');
            html.dataset.theme = 'light';
            html.style.colorScheme = 'light';
        })();
    </script>


</head>

<body>

    <div class="min-h-screen xl:flex sidebar-expanded" x-data :class="{ 'sidebar-expanded': $store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen }">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        {{-- transition-all duration-300 ease-in-out --}}
        <div class="min-w-0 flex-1 ml-0 ltr:xl:ml-[90px] rtl:xl:ml-0 rtl:xl:mr-[90px] [.sidebar-expanded_&]:ltr:xl:ml-[290px] [.sidebar-expanded_&]:rtl:xl:ml-0 [.sidebar-expanded_&]:rtl:xl:mr-[290px] transition-all duration-300 ease-in-out">
            <!-- app header start -->
            @include('layouts.app-header')
            <!-- app header end -->
            <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
                @yield('content')
            </div>
        </div>

    </div>

</body>

@stack('scripts')

</html>
