<?php

namespace App\Modules\Payment\Repositories\Interfaces;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IPaymentRepository
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function paginateForOrder(
        int $orderId,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function create(array $data): Payment;

    public function lockById(int $paymentId): Payment;

    public function update(
        Payment $payment,
        array $data
    ): Payment;

    public function findWithDetails(int $paymentId): Payment;
}