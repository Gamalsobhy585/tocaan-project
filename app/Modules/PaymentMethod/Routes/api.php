<?php

use App\Modules\PaymentMethod\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')
    ->prefix('payment-methods')
    ->controller(PaymentMethodController::class)
    ->group(function () {
        Route::get('', 'index')
            ->name('payment-methods.index');

        Route::post('', 'add')
            ->name('payment-methods.add');

        Route::patch('{paymentMethod}/toggle-active', 'toggleActive')
            ->name('payment-methods.toggle-active');
    });