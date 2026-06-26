<?php

namespace App\Modules\Order\Services\Interfaces;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IOrderService
{
    public function index(array $filters): LengthAwarePaginator;

    public function create(
        array $data,
        int $actorId
    ): Order;

    public function update(
        int $orderId,
        array $data,
        int $actorId
    ): Order;

    public function delete(
        int $orderId,
        int $actorId
    ): Order;
}