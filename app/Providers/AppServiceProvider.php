<?php

namespace App\Providers;

use App\Modules\Authentication\Listeners\LogAuthenticationActivity;
use App\Modules\Authentication\Repositories\Implementation\UserRepository;
use App\Modules\Authentication\Repositories\Interface\IUser;
use App\Modules\Currency\Repositories\Implementation\CurrencyRepository;
use App\Modules\Currency\Repositories\Interfaces\ICurrencyRepository;
use App\Modules\Currency\Services\CurrencyService;
use App\Modules\Currency\Services\Interfaces\ICurrencyService;
use App\Modules\Order\Repositories\Implementation\OrderRepository;
use App\Modules\Order\Repositories\Interfaces\IOrderRepository;
use App\Modules\Order\Services\Interfaces\IOrderService;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Gateways\Strategies\CreditCardGatewayStrategy;
use App\Modules\Payment\Gateways\Strategies\PayPalGatewayStrategy;
use App\Modules\PaymentMethod\Repositories\Implementation\PaymentMethodRepository;
use App\Modules\PaymentMethod\Repositories\Interfaces\IPaymentMethodRepository;
use App\Modules\PaymentMethod\Services\Interfaces\IPaymentMethodService;
use App\Modules\PaymentMethod\Services\PaymentMethodService;
use App\Modules\Product\Repositories\Implementation\ProductRepository;
use App\Modules\Product\Repositories\Interfaces\IProductRepository;
use App\Modules\Product\Services\Interfaces\IProductService;
use App\Modules\Product\Services\ProductService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
         $this->app->bind(IUser::class,UserRepository::class);
         $this->app->bind(ICurrencyRepository::class,CurrencyRepository::class);
         $this->app->bind(ICurrencyService::class,CurrencyService::class);
         $this->app->bind(IProductRepository::class,ProductRepository::class);
         $this->app->bind(IProductService::class,ProductService::class);
         $this->app->bind(IPaymentMethodRepository::class,PaymentMethodRepository::class);
         $this->app->bind(IPaymentMethodService::class,PaymentMethodService::class);
         $this->app->bind(IOrderRepository::class,OrderRepository::class);
         $this->app->bind(IOrderService::class,OrderService::class);

         $this->app->singleton(CreditCardGatewayStrategy::class);
         $this->app->singleton(PayPalGatewayStrategy::class);
        $this->app->tag([CreditCardGatewayStrategy::class,PayPalGatewayStrategy::class,], 'payment.gateway.strategies');


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
