<?php

use App\Http\Controllers\Fin\DepositController;
use App\Http\Controllers\Fin\FinDashboardController;
use App\Http\Controllers\Fin\LedgerController;
use App\Http\Controllers\Fin\MerchantServicesController;
use App\Http\Controllers\Fin\MerchantShopController;
use App\Http\Controllers\Fin\PlatformAccountController;
use App\Http\Controllers\Fin\ShopCheckoutController;
use App\Http\Controllers\Fin\ShopDirectoryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('pay')->name('pay.')->group(function () {
    Route::get('/', [FinDashboardController::class, 'index'])->name('dashboard');
    Route::get('/deposit', [DepositController::class, 'create'])->name('deposit.create');
    Route::post('/deposit', [DepositController::class, 'store'])->name('deposit.store');
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger');
    Route::get('/batches/{ref}', [LedgerController::class, 'showBatch'])->name('batches.show');
    Route::get('/platform-accounts', [PlatformAccountController::class, 'index'])->name('platform');
    Route::get('/shops', [ShopDirectoryController::class, 'index'])->name('shops');
    Route::get('/shop/{merchantPublicId}', [MerchantShopController::class, 'show'])->name('shop')->whereUuid('merchantPublicId');
    Route::post('/shop/pay', [ShopCheckoutController::class, 'payService'])->name('shop.pay');
    Route::get('/merchant/services', [MerchantServicesController::class, 'index'])->name('merchant.services');
    Route::post('/merchant/profile', [MerchantServicesController::class, 'storeProfile'])->name('merchant.profile.store');
    Route::post('/merchant/services', [MerchantServicesController::class, 'store'])->name('merchant.services.store');
    Route::patch('/merchant/services/{servicePublicId}', [MerchantServicesController::class, 'update'])->name('merchant.services.update');
    Route::delete('/merchant/services/{servicePublicId}', [MerchantServicesController::class, 'destroy'])->name('merchant.services.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
