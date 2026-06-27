<?php

use App\Modules\Product\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')
    ->prefix('products')
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('', 'index')
            ->name('products.index');

        Route::post('', 'add')
            ->name('products.add');

        Route::post('import-bulk', 'importBulk')
            ->name('products.import-bulk');

        Route::delete('delete-bulk', 'deleteBulk')
            ->name('products.delete-bulk');

        Route::delete('{product}', 'delete')
            ->name('products.delete');
    });