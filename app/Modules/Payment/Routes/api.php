<?php

use App\Modules\Payment\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')
    ->group(function () {
        Route::get('payments', [
            PaymentController::class,
            'index',
        ])->name('payments.index');

        Route::get('payments/{payment}', [
            PaymentController::class,
            'show',
        ])->name('payments.show');

        Route::get('orders/{order}/payments', [
            PaymentController::class,
            'forOrder',
        ])->name('orders.payments.index');

        Route::post('orders/{order}/payments/process', [
            PaymentController::class,
            'process',
        ])->name('orders.payments.process');
    });