<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Lang;
use App\Http\Middleware\Cors;


Route::middleware(['lang', 'cors'])->group(function () {
    require base_path('app/Modules/Authentication/Routes/authentication.php');
});

require app_path('Modules/Currency/Routes/api.php');

require app_path('Modules/Product/Routes/api.php');

require app_path('Modules/Order/Routes/api.php');

require app_path('Modules/PaymentMethod/Routes/api.php');

require app_path('Modules/Payment/Routes/api.php');
