<?php

use App\Modules\Order\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')
    ->prefix('orders')
    ->controller(OrderController::class)
    ->group(function () {
        Route::get('', 'index')
            ->name('orders.index');

        Route::post('', 'store')
            ->name('orders.store');

        Route::put('{order}', 'update')
            ->name('orders.update');

        Route::delete('{order}', 'destroy')
            ->name('orders.destroy');
    });