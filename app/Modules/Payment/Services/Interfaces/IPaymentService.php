<?php

namespace App\Modules\Payment\Services\Interfaces;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IPaymentService
{
    public function index(array $filters): LengthAwarePaginator;

    public function forOrder(
        int $orderId,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function show(int $paymentId): Payment;

    public function process(
        int $orderId,
        array $data,
        int $actorId
    ): Payment;
}