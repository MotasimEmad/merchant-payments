<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\LedgerController;
use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\MerchantServiceController;
use App\Http\Controllers\Api\V1\PaymentIntentController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthTokenController::class, 'register']);
    Route::post('auth/login', [AuthTokenController::class, 'login']);

    Route::get('merchants/{merchantPublicId}/services', [MerchantServiceController::class, 'publicIndex'])
        ->whereUuid('merchantPublicId');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthTokenController::class, 'me']);
        Route::post('auth/logout', [AuthTokenController::class, 'logout']);

        Route::get('wallets', [WalletController::class, 'index']);
        Route::get('wallets/{uuid}', [WalletController::class, 'show']);
        Route::post('wallets/{uuid}/deposits', [WalletController::class, 'deposit']);
        Route::post('wallets/{uuid}/withdrawals', [WalletController::class, 'withdraw']);
        Route::post('wallets/transfer', [WalletController::class, 'transfer']);

        Route::get('ledger', [LedgerController::class, 'index']);

        Route::post('merchants', [MerchantController::class, 'store']);
        Route::get('merchants/mine', [MerchantController::class, 'mine']);
        Route::get('merchants/mine/services', [MerchantServiceController::class, 'indexMine']);
        Route::post('merchants/mine/services', [MerchantServiceController::class, 'store']);
        Route::patch('merchants/mine/services/{servicePublicId}', [MerchantServiceController::class, 'update']);
        Route::delete('merchants/mine/services/{servicePublicId}', [MerchantServiceController::class, 'destroy']);

        Route::post('payment_intents', [PaymentIntentController::class, 'store']);
        Route::post('payment_intents/checkout', [PaymentIntentController::class, 'storeAsPayer']);
        Route::get('payment_intents/{publicId}', [PaymentIntentController::class, 'show']);
        Route::post('payment_intents/{publicId}/confirm', [PaymentIntentController::class, 'confirm']);
        Route::post('payment_intents/{publicId}/refund', [PaymentIntentController::class, 'refund']);
    });
});
