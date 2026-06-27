<?php

namespace App\Modules\Payment\Providers;

use App\Modules\Payment\Gateways\PaymentGatewayRegistry;
use App\Modules\Payment\Gateways\Strategies\CreditCardGatewayStrategy;
use App\Modules\Payment\Gateways\Strategies\PayPalGatewayStrategy;
use App\Modules\Payment\Repositories\Implementation\PaymentRepository;
use App\Modules\Payment\Repositories\Interfaces\IPaymentRepository;
use App\Modules\Payment\Services\Interfaces\IPaymentService;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\PaymentMethod\Repositories\Implementation\PaymentMethodRepository;
use App\Modules\PaymentMethod\Repositories\Interfaces\IPaymentMethodRepository;
use App\Modules\PaymentMethod\Services\Interfaces\IPaymentMethodService;
use App\Modules\PaymentMethod\Services\PaymentMethodService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            IPaymentMethodRepository::class,
            PaymentMethodRepository::class
        );

        $this->app->bind(
            IPaymentMethodService::class,
            PaymentMethodService::class
        );

        $this->app->bind(
            IPaymentRepository::class,
            PaymentRepository::class
        );

        $this->app->bind(
            IPaymentService::class,
            PaymentService::class
        );

        $this->app->tag([
            CreditCardGatewayStrategy::class,
            PayPalGatewayStrategy::class,
        ], 'payment.gateway.strategies');

        $this->app->singleton(
            PaymentGatewayRegistry::class,
            fn ($app) => new PaymentGatewayRegistry(
                $app->tagged('payment.gateway.strategies')
            )
        );
    }

    public function boot(): void
    {
        //
    }
}