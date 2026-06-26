<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use App\Modules\Authentication\Listeners\LogAuthenticationActivity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $listener = new LogAuthenticationActivity();

        Event::listen(Login::class,   fn($e) => $listener->handleLogin($e));
        Event::listen(Logout::class,  fn($e) => $listener->handleLogout($e));
        Event::listen(Failed::class,  fn($e) => $listener->handleFailed($e));
    }
}
