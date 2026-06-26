<?php

namespace App\Modules\PaymentMethod\Services\Interfaces;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;

interface IPaymentMethodService
{
    public function index(): Collection;

    public function add(array $data): PaymentMethod;

    public function toggleActive(
        PaymentMethod $paymentMethod
    ): PaymentMethod;
}