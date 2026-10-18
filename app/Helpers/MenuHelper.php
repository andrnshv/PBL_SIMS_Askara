<?php

namespace App\Helpers;

class MenuHelper
{

    public static function getMainNavItems()
    {
        return [
            [
                'icon' => 'dashboard',
                'name' => 'Dashboard',
                'subItems' => [
                    ['name' => 'Ecommerce', 'path' => '/'],
                ],
            ],
            [
                'icon' => 'sales',
                'name' => 'Transaksi Penjualan',
                'path' => '/sales',
            ],
            [
                'icon' => 'history',
                'name' => 'Riwayat Penjualan',
                'path' => '/sales/history',
            ],
            [
                'icon' => 'package',
                'name' => 'Daftar Produk',
                'path' => '/products',
            ],
            [
                'icon' => 'warehouse',
                'name' => 'Stok',
                'subItems' => [
                    ['name' => 'Daftar Stok', 'path' => '/stock'],
                    ['name' => 'Stok Masuk', 'path' => '/stock/incoming'],
                ],
            ],
            [
                'icon' => 'bell',
                'name' => 'Reorder Point',
                'path' => '/reorder',
            ],
            [
                'icon' => 'ecommerce',
                'name' => 'Pembelian',
                'path' => '/purchase-orders',
            ],
            [
                'icon' => 'user-profile',
                'name' => 'User Profile',
                'path' => '/profile',
            ],
        ];
    }

    public static function getOthersItems()
    {
        return [
            [
                'icon' => 'authentication',
                'name' => 'Authentication',
                'subItems' => [
                    ['name' => 'Sign In', 'path' => '/signin', 'pro' => false],
                    ['name' => 'Sign Up', 'path' => '/signup', 'pro' => false],
                ],
            ],
        ];
    }

    public static function getMenuGroups()
    {
        return [
            [
                'title' => 'Menu',
                'items' => self::getMainNavItems(),
            ],
            [
                'title' => 'Others',
                'items' => self::getOthersItems(),
            ],
        ];
    }

    public static function isActive($path)
    {
        $path = trim($path, '/');

        // Mendukung halaman root "/".
        if ($path === '') {
            return request()->is('/');
        }

        return request()->is($path);
    }

    public static function getIconSvg($iconName)
    {
        $icons = [
            'dashboard' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.5 3.5H10V10H3.5V3.5ZM14 3.5H20.5V10H14V3.5ZM3.5 14H10V20.5H3.5V14ZM14 14H20.5V20.5H14V14Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',

            'ecommerce' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2.5 4H4L6 15H18L21 7H5M8 19.5H8.01M17 19.5H17.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',

            'sales' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 3V19C3 20.1 3.9 21 5 21H21M7 14L11 10L14 13L21 6M16 6H21V11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',

            'history' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 12A9 9 0 1 0 6 5.3L3 8M3 3V8H8M12 7V12L16 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',

            'package' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3L21 8L12 13L3 8L12 3ZM3 8V17L12 22L21 17V8M12 13V22M7.5 5.5L16.5 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',

            'warehouse' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 10L12 4L21 10V20H3V10ZM9 20V14H15V20M7 10H7.01M12 10H12.01M17 10H17.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',

            'calendar' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3.25" y="5" width="17.5" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7.5 2.75V6.5M16.5 2.75V6.5M3.5 9.5H20.5M7.5 13H7.51M12 13H12.01M16.5 13H16.51M7.5 17H7.51M12 17H12.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',

            'user-profile' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M4 21C4 16.58 7.58 13 12 13C16.42 13 20 16.58 20 21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',

            'bell' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 8A6 6 0 0 0 6 8C6 15 3 17 3 17H21S18 15 18 8ZM10 21H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',

            'forms' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M8 8H16M8 12H16M8 16H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',

            'tables' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="16" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M3 9H21M3 14H21M9 9V20M15 9V20" stroke="currentColor" stroke-width="1.5"/></svg>',

            'pages' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 3H14L19 8V20C19 20.55 18.55 21 18 21H6C5.45 21 5 20.55 5 20V4C5 3.45 5.45 3 6 3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 3V8H19M8 13H16M8 17H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',

            'charts' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V12M10 20V4M16 20V9M22 20H2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',

            'ui-elements' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3L21 8V16L12 21L3 16V8L12 3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M3 8L12 13L21 8M12 13V21" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',

            'authentication' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="10" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M8 10V7A4 4 0 0 1 16 7V10M12 14V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',

            'chat' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 5H20V17H13L7 21V17H4V5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M8 9H16M8 13H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',

            'support-ticket' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 7A2 2 0 0 1 6 5H18A2 2 0 0 1 20 7V10A2 2 0 0 0 20 14V17A2 2 0 0 1 18 19H6A2 2 0 0 1 4 17V14A2 2 0 0 0 4 10V7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 8V16M12 11H12.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',

            'email' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M4 7L12 13L20 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ];

        return $icons[$iconName] ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L15 9L22 9.5L16.5 14.5L18 22L12 18L6 22L7.5 14.5L2 9.5L9 9L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>';
    }
}
