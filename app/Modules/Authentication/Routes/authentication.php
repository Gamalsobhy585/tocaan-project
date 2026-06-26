<?php
use App\Modules\Authentication\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {    // ← jwt guard
        Route::post('logout',          [AuthController::class, 'logout']);
        Route::post('renew-password',  [AuthController::class, 'renewPassword']);
        Route::get('me',               [AuthController::class, 'getUserInfo']);
    });
});