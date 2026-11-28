<?php

return [
    // Persentase pajak yang ditambahkan pada subtotal penjualan. Isi 0 untuk menonaktifkan.
    'tax_rate' => (float) env('SALES_TAX_RATE', 11),

    // Pembulatan total ke kelipatan tertentu (mis. 100 atau 500). Isi 0 untuk menonaktifkan.
    'rounding_step' => (int) env('SALES_ROUNDING_STEP', 100),

    // 'nearest' = ke kelipatan terdekat, 'up' = selalu ke atas.
    'rounding_mode' => env('SALES_ROUNDING_MODE', 'nearest'),
];