<?php

use Illuminate\Support\Facades\Route;



Route::middleware(['lang', 'cors'])->group(function () {
    require base_path('app/Modules/Authentication/Routes/authentication.php');
});

require app_path('Modules/Currency/Routes/api.php');

require app_path('Modules/Product/Routes/api.php');
