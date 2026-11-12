<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ReorderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductVariantMergeController;

/*
| Hak akses (admin selalu lolos semua middleware 'role'):
| role                              -> semua user login
| role:staff_penjualan              -> penjualan
| role:staff_gudang                 -> opname
| role:staff_gudang,staff_pembelian -> stok masuk / penerimaan barang
| role:staff_pembelian              -> purchase order, ubah reorder point
| role:admin                        -> kelola produk & varian, tambah akun
*/

// ================= LOCALE (publik) =================
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch');

// ================= AUTENTIKASI =================
Route::middleware('guest')->group(function () {
    Route::get('/signin', function () {
        return view('pages.auth.signin', ['title' => 'Sign In']);
    })->name('signin');

    Route::post('/signin', [AuthController::class, 'login'])
        ->name('signin.store');
});

Route::get('/login', function () {
    return redirect()->route('signin');
})->name('login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Tambah akun: hanya admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/signup', function () {
        return view('pages.auth.signup', ['title' => 'Sign Up']);
    })->name('signup');

    Route::post('/signup', [AuthController::class, 'register'])
        ->name('signup.store');
});

// ================= ADMIN: kelola produk & varian =================
// (di atas group semua-role agar /products/create tidak tertangkap /products/{id})
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])
        ->name('products.create');

    Route::post('/products', [ProductController::class, 'store'])
        ->name('products.store');

    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])
        ->whereNumber('id')
        ->name('products.edit');

    Route::put('/products/{id}', [ProductController::class, 'update'])
        ->whereNumber('id')
        ->name('products.update');

    Route::delete('/products/{id}', [ProductController::class, 'destroy'])
        ->whereNumber('id')
        ->name('products.destroy');

    Route::get('/products/{id}/variants/create', [ProductVariantController::class, 'create'])
        ->whereNumber('id')
        ->name('products.variants.create');

    Route::post('/products/{id}/variants', [ProductVariantController::class, 'store'])
        ->whereNumber('id')
        ->name('products.variants.store');

    Route::get('/products/{id}/variants/{variantId}/edit', [ProductVariantController::class, 'edit'])
        ->whereNumber(['id', 'variantId'])
        ->name('products.variants.edit');

    Route::put('/products/{id}/variants/{variantId}', [ProductVariantController::class, 'update'])
        ->whereNumber(['id', 'variantId'])
        ->name('products.variants.update');

    Route::delete('/products/{id}/variants/{variantId}', [ProductVariantController::class, 'destroy'])
        ->whereNumber(['id', 'variantId'])
        ->name('products.variants.destroy');

        Route::get('/products/{id}/variants/merge', [ProductVariantMergeController::class, 'create'])
    ->whereNumber('id')
    ->name('products.variants.merge.create');

    Route::post('/products/{id}/variants/merge', [ProductVariantMergeController::class, 'store'])
        ->whereNumber('id')
        ->name('products.variants.merge.store');
});

// ================= SEMUA ROLE (harus login) =================
Route::middleware(['auth', 'role'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{id}', [ProductController::class, 'show'])
        ->whereNumber('id')
        ->name('products.show');

    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/history', [StockController::class, 'history'])->name('stock.history');

    Route::get('/reorder', [ReorderController::class, 'index'])->name('reorder.index');

    // Halaman template bawaan
    Route::get('/calendar', function () {
        return view('pages.calender', ['title' => 'Calendar']);
    })->name('calendar');

    Route::get('/form-elements', function () {
        return view('pages.form.form-elements', ['title' => 'Form Elements']);
    })->name('form-elements');

    Route::get('/basic-tables', function () {
        return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
    })->name('basic-tables');

    Route::get('/blank', function () {
        return view('pages.blank', ['title' => 'Blank']);
    })->name('blank');

    Route::get('/error-404', function () {
        return view('pages.errors.error-404', ['title' => 'Error 404']);
    })->name('error-404');

    Route::get('/line-chart', function () {
        return view('pages.chart.line-chart', ['title' => 'Line Chart']);
    })->name('line-chart');

    Route::get('/bar-chart', function () {
        return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
    })->name('bar-chart');

    Route::get('/alerts', function () {
        return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
    })->name('alerts');

    Route::get('/avatars', function () {
        return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
    })->name('avatars');

    Route::get('/badge', function () {
        return view('pages.ui-elements.badges', ['title' => 'Badges']);
    })->name('badges');

    Route::get('/buttons', function () {
        return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
    })->name('buttons');

    Route::get('/image', function () {
        return view('pages.ui-elements.images', ['title' => 'Images']);
    })->name('images');

    Route::get('/videos', function () {
        return view('pages.ui-elements.videos', ['title' => 'Videos']);
    })->name('videos');
});

// ================= PENJUALAN =================
Route::middleware(['auth', 'role:staff_penjualan'])->group(function () {
    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::post('/sales', [SalesController::class, 'store'])->name('sales.store');
    Route::get('/sales/history', [SalesController::class, 'history'])->name('sales.history');

    Route::get('/sales/history/{id}', [SalesController::class, 'detail'])
    ->whereNumber('id')
    ->name('sales.detail');
});

// ================= UBAH REORDER POINT =================
Route::middleware(['auth', 'role:staff_pembelian'])->group(function () {
    Route::put('/reorder', [ReorderController::class, 'update'])->name('reorder.update');
});

// ================= STOK MASUK & PENERIMAAN BARANG =================
Route::middleware(['auth', 'role:staff_gudang,staff_pembelian'])->group(function () {
    Route::get('/stock/incoming', [StockInController::class, 'create'])
        ->name('stock.incoming.create');

    Route::post('/stock/incoming', [StockInController::class, 'store'])
        ->name('stock.store');

    Route::get('/stock/receipts/{id}', [StockInController::class, 'show'])
        ->whereNumber('id')
        ->name('stock.receipts.show');

    Route::get('/goods-receipts', [StockInController::class, 'index'])
        ->name('goods-receipts.index');
});


// ================= STOCK OPNAME =================

// Daftar dan detail opname: semua role operasional dapat melihat.
Route::middleware([
    'auth',
    'role:staff_gudang,staff_pembelian,staff_penjualan'
])->group(function () {
    Route::get('/stock/opname', [StockController::class, 'opnameIndex'])
        ->name('stock.opname');

    Route::get('/stock/opname/{id}', [StockController::class, 'opnameShow'])
        ->whereNumber('id')
        ->name('stock.opname.show');
});

// Pengelolaan opname: khusus Admin dan Staff Gudang.
// Admin otomatis mendapat akses melalui RoleMiddleware.
Route::middleware(['auth', 'role:staff_gudang'])->group(function () {
    Route::get('/stock/opname/create', [StockController::class, 'opnameCreate'])
        ->name('stock.opname.create');

    Route::post('/stock/opname', [StockController::class, 'opnameStore'])
        ->name('stock.opname.store');

    Route::get('/stock/opname/{id}/edit', [StockController::class, 'opnameEdit'])
        ->whereNumber('id')
        ->name('stock.opname.edit');

    Route::put('/stock/opname/{id}', [StockController::class, 'opnameUpdate'])
        ->whereNumber('id')
        ->name('stock.opname.update');

    Route::delete('/stock/opname/{id}', [StockController::class, 'opnameDestroy'])
        ->whereNumber('id')
        ->name('stock.opname.destroy');
});

// ================= PURCHASE ORDER =================
Route::middleware(['auth', 'role:staff_pembelian'])->group(function () {
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])
        ->name('purchase-orders.index');

    Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])
        ->name('purchase-orders.create');

    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])
        ->name('purchase-orders.store');

    Route::get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show'])
        ->whereNumber('id')
        ->name('purchase-orders.show');

    Route::get('/purchase-orders/{id}/edit', [PurchaseOrderController::class, 'edit'])
        ->whereNumber('id')
        ->name('purchase-orders.edit');

    Route::put('/purchase-orders/{id}', [PurchaseOrderController::class, 'update'])
        ->whereNumber('id')
        ->name('purchase-orders.update');

    Route::delete('/purchase-orders/{id}', [PurchaseOrderController::class, 'destroy'])
        ->whereNumber('id')
        ->name('purchase-orders.destroy');

    Route::post('/purchase-orders/{id}/send', [PurchaseOrderController::class, 'send'])
        ->whereNumber('id')
        ->name('purchase-orders.send');

    Route::post('/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])
        ->whereNumber('id')
        ->name('purchase-orders.cancel');
});

 // ================= MANAJEMEN SUPPLIER =================
Route::middleware(['auth', 'role:staff_pembelian'])->group(function () {
    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->name('suppliers.index');

    Route::get('/suppliers/create', [SupplierController::class, 'create'])
        ->name('suppliers.create');

    Route::post('/suppliers', [SupplierController::class, 'store'])
        ->name('suppliers.store');

    Route::get('/suppliers/{id}/edit', [SupplierController::class, 'edit'])
        ->whereNumber('id')
        ->name('suppliers.edit');

    Route::put('/suppliers/{id}', [SupplierController::class, 'update'])
        ->whereNumber('id')
        ->name('suppliers.update');

    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy'])
        ->whereNumber('id')
        ->name('suppliers.destroy');
});
