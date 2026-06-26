<?php

use App\Modules\Currency\Controllers\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')
    ->prefix('currencies')
    ->controller(CurrencyController::class)
    ->group(function () {
        Route::get('', 'index')
            ->name('currencies.index');

        Route::post('', 'add')
            ->name('currencies.add');

        Route::patch('{currency}/toggle-active', 'toggleActive')
            ->name('currencies.toggle-active');
    });