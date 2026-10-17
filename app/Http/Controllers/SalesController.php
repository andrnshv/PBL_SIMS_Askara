<?php

namespace App\Http\Controllers;

class SalesController extends Controller
{
    public function index()
    {
        // Data contoh sementara, sebelum terhubung ke database.
        $products = [
            [
                'id' => 1,
                'name' => 'Batu Gerinda',
                'variant' => 'Diameter 4 inci',
                'sku' => 'GRD-004',
                'price' => 25000,
                'stock' => 35,
                'min' => 10,
                'category' => 'Abrasif',
                'image' => asset('images/products/batu-gerinda-4.jpg'),
            ],
            [
                'id' => 2,
                'name' => 'Batu Gerinda',
                'variant' => 'Diameter 7 inci',
                'sku' => 'GRD-007',
                'price' => 45000,
                'stock' => 5,
                'min' => 10,
                'category' => 'Abrasif',
                'image' => asset('images/products/batu-gerinda-7.jpg'),
            ],
            [
                'id' => 3,
                'name' => 'APAR',
                'variant' => 'Kapasitas 6 kg',
                'sku' => 'APAR-006',
                'price' => 650000,
                'stock' => 12,
                'min' => 5,
                'category' => 'Keselamatan',
                'image' => asset('images/products/apar-6kg.jpg'),
            ],
            [
                'id' => 4,
                'name' => 'Sarung Tangan Las',
                'variant' => 'Ukuran L',
                'sku' => 'WGL-L',
                'price' => 35000,
                'stock' => 20,
                'min' => 5,
                'category' => 'Pengelasan',
                'image' => asset('images/products/sarung-tangan-las.jpg'),
            ],
            [
                'id' => 5,
                'name' => 'Elektroda Las',
                'variant' => 'Diameter 2.6 mm',
                'sku' => 'ELD-026',
                'price' => 55000,
                'stock' => 8,
                'min' => 10,
                'category' => 'Pengelasan',
                'image' => asset('images/products/elektroda-26.jpg'),
            ],
            [
                'id' => 6,
                'name' => 'Kacamata Safety',
                'variant' => 'Clear Lens',
                'sku' => 'SAF-CLR',
                'price' => 18000,
                'stock' => 0,
                'min' => 5,
                'category' => 'Keselamatan',
                'image' => asset('images/products/kacamata-safety.jpg'),
            ],
        ];

        return view('pages.sales.index', [
            'title' => 'Transaksi Penjualan',
            'products' => $products,
        ]);
    }
}
