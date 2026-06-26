<?php

namespace App\Modules\PaymentMethod\Repositories\Interfaces;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;

interface IPaymentMethodRepository
{
    public function getAll(): Collection;

    public function create(array $data): PaymentMethod;

    public function toggleActive(
        PaymentMethod $paymentMethod
    ): PaymentMethod;
}