<?php

use Illuminate\Support\Facades\Route;



Route::middleware(['lang', 'cors'])->group(function () {
    require base_path('app/Modules/Authentication/Routes/authentication.php');
});

