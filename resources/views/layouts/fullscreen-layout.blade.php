<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $title ?? 'Dashboard')</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    {{-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

    <!-- Theme Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                theme: 'light',

                init() {
                    this.updateTheme();
                },

                toggle() {
                    this.updateTheme();
                },

                updateTheme() {
                    const html = document.documentElement;
                    const body = document.body;

                    html.classList.remove('dark');
                    html.setAttribute('data-color-scheme', 'light');
                    html.dataset.theme = 'light';
                    html.style.colorScheme = 'light';

                    if (body) {
                        body.classList.remove('dark', 'bg-gray-900');
                        body.dataset.theme = 'light';
                        body.style.colorScheme = 'light';
                    }

                    this.theme = 'light';
                }
            });

            Alpine.store('sidebar', {
                // Initialize based on screen size
                isExpanded: window.innerWidth >= 1280, // true for desktop, false for mobile
                isMobileOpen: false,
                isHovered: false,

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    // When toggling desktop sidebar, ensure mobile menu is closed
                    this.isMobileOpen = false;
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                    // Don't modify isExpanded when toggling mobile menu
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    // Only allow hover effects on desktop when sidebar is collapsed
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <!-- Force Light Mode immediately -->
    <script>
        (function () {
            localStorage.setItem('theme', 'light');

            document.documentElement.classList.remove('dark');
            document.documentElement.setAttribute('data-color-scheme', 'light');
            document.documentElement.dataset.theme = 'light';
            document.documentElement.style.colorScheme = 'light';

            if (document.body) {
                document.body.classList.remove('dark', 'bg-gray-900');
            }
        })();
    </script>
    <!-- Apply RTL mode immediately to prevent flash -->
    <script>
        (function() {
            const savedDir = localStorage.getItem("dir");
            if (savedDir === "rtl") {
                document.documentElement.setAttribute("dir", "rtl");
            } else {
                document.documentElement.setAttribute("dir", "ltr");
            }
        })();
    </script>
</head>

<body x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
const checkMobile = () => {
    if (window.innerWidth < 1280) {
        $store.sidebar.setMobileOpen(false);
        $store.sidebar.isExpanded = false;
    } else {
        $store.sidebar.isMobileOpen = false;
        $store.sidebar.isExpanded = true;
    }
};
window.addEventListener('resize', checkMobile);">

    @yield('content')

</body>

@stack('scripts')

</html>
