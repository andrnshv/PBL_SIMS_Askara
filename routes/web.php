<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ReorderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\PurchaseOrderController;

// Locale Switch Route
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
use App\Http\Controllers\DashboardController;

// dashboard pages
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// calender pages
Route::get('/calendar', function () {
    return view('pages.calender', ['title' => 'Calendar']);
})->name('calendar');

// profile pages
Route::get('/profile', function () {
    return view('pages.profile', ['title' => 'Profile']);
})->name('profile');

// form pages
Route::get('/form-elements', function () {
    return view('pages.form.form-elements', ['title' => 'Form Elements']);
})->name('form-elements');

// tables pages
Route::get('/basic-tables', function () {
    return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
})->name('basic-tables');

// pages

Route::get('/blank', function () {
    return view('pages.blank', ['title' => 'Blank']);
})->name('blank');

// error pages
Route::get('/error-404', function () {
    return view('pages.errors.error-404', ['title' => 'Error 404']);
})->name('error-404');

// chart pages
Route::get('/line-chart', function () {
    return view('pages.chart.line-chart', ['title' => 'Line Chart']);
})->name('line-chart');

Route::get('/bar-chart', function () {
    return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
})->name('bar-chart');


// authentication pages
Route::get('/signin', function () {
    return view('pages.auth.signin', ['title' => 'Sign In']);
})->name('signin');

Route::get('/signup', function () {
    return view('pages.auth.signup', ['title' => 'Sign Up']);
})->name('signup');

// ui elements pages
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

// Sales transaction
Route::get('/sales', [SalesController::class, 'index'])
    ->name('sales.index');

Route::post('/sales', [SalesController::class, 'store'])
    ->name('sales.store');

// Produk induk
Route::get('/products', [ProductController::class, 'index'])
    ->name('products.index');

Route::get('/products/create', [ProductController::class, 'create'])
    ->name('products.create');

Route::post('/products', [ProductController::class, 'store'])
    ->name('products.store');

Route::get('/products/{id}', [ProductController::class, 'show'])
    ->name('products.show');

Route::get('/products/{id}/edit', [ProductController::class, 'edit'])
    ->name('products.edit');

Route::put('/products/{id}', [ProductController::class, 'update'])
    ->name('products.update');

Route::delete('/products/{id}', [ProductController::class, 'destroy'])
    ->name('products.destroy');

// Varian produk
Route::get('/products/{id}/variants/create', [ProductVariantController::class, 'create'])
    ->name('products.variants.create');

Route::post('/products/{id}/variants', [ProductVariantController::class, 'store'])
    ->name('products.variants.store');

Route::get('/products/{id}/variants/{variantId}/edit', [ProductVariantController::class, 'edit'])
    ->name('products.variants.edit');

Route::put('/products/{id}/variants/{variantId}', [ProductVariantController::class, 'update'])
    ->name('products.variants.update');

Route::delete('/products/{id}/variants/{variantId}', [ProductVariantController::class, 'destroy'])
    ->name('products.variants.destroy');

Route::get('/sales/history', [SalesController::class, 'history'])
    ->name('sales.history');

Route::get('/reorder', [ReorderController::class, 'index'])
    ->name('reorder.index');

Route::put('/reorder', [ReorderController::class, 'update'])
    ->name('reorder.update');

// ===== Stok =====
// Riwayat: tambahkan ?variant=ID untuk riwayat satu varian.
Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
Route::get('/stock/history', [StockController::class, 'history'])->name('stock.history');

// Stok masuk (dokumen penerimaan). Dari PO: /stock/incoming?po=ID
Route::get('/stock/incoming', [StockInController::class, 'create'])->name('stock.incoming.create');
Route::post('/stock/incoming', [StockInController::class, 'store'])->name('stock.store');
Route::get('/stock/receipts/{id}', [StockInController::class, 'show'])->whereNumber('id')->name('stock.receipts.show');

// Opname (rute statis /create harus sebelum /{id})
Route::get('/stock/opname', [StockController::class, 'opnameIndex'])->name('stock.opname');
Route::get('/stock/opname/create', [StockController::class, 'opnameCreate'])->name('stock.opname.create');
Route::post('/stock/opname', [StockController::class, 'opnameStore'])->name('stock.opname.store');
Route::get('/stock/opname/{id}', [StockController::class, 'opnameShow'])->whereNumber('id')->name('stock.opname.show');
Route::get('/stock/opname/{id}/edit', [StockController::class, 'opnameEdit'])->whereNumber('id')->name('stock.opname.edit');
Route::put('/stock/opname/{id}', [StockController::class, 'opnameUpdate'])->whereNumber('id')->name('stock.opname.update');
Route::delete('/stock/opname/{id}', [StockController::class, 'opnameDestroy'])->whereNumber('id')->name('stock.opname.destroy');

// ===== Pembelian =====
Route::get('/goods-receipts', [StockInController::class, 'index'])->name('goods-receipts.index');

Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
Route::get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show'])->whereNumber('id')->name('purchase-orders.show');
Route::get('/purchase-orders/{id}/edit', [PurchaseOrderController::class, 'edit'])->whereNumber('id')->name('purchase-orders.edit');
Route::put('/purchase-orders/{id}', [PurchaseOrderController::class, 'update'])->whereNumber('id')->name('purchase-orders.update');
Route::delete('/purchase-orders/{id}', [PurchaseOrderController::class, 'destroy'])->whereNumber('id')->name('purchase-orders.destroy');
Route::post('/purchase-orders/{id}/send', [PurchaseOrderController::class, 'send'])->whereNumber('id')->name('purchase-orders.send');
Route::post('/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->whereNumber('id')->name('purchase-orders.cancel');