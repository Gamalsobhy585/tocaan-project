<?php

namespace App\Modules\PaymentMethod\Services;

use App\Models\PaymentMethod;
use App\Modules\Payment\Gateways\PaymentGatewayRegistry;
use App\Modules\PaymentMethod\Repositories\Interfaces\IPaymentMethodRepository;
use App\Modules\PaymentMethod\Services\Interfaces\IPaymentMethodService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PaymentMethodService implements IPaymentMethodService
{
    public function __construct(
        private readonly IPaymentMethodRepository $repository,
        private readonly PaymentGatewayRegistry $registry,
    ) {
    }

    public function index(): Collection
    {
        return $this->repository->getAll();
    }

    public function add(array $data): PaymentMethod
    {
        if (! $this->registry->has($data['strategy_key'])) {
            throw ValidationException::withMessages([
                'strategy_key' => sprintf(
                    'The strategy [%s] is not registered. Registered strategies: %s',
                    $data['strategy_key'],
                    implode(', ', $this->registry->registeredKeys())
                ),
            ]);
        }

        return $this->repository->create($data);
    }

    public function toggleActive(
        PaymentMethod $paymentMethod
    ): PaymentMethod {
        return $this->repository->toggleActive(
            $paymentMethod
        );
    }
}