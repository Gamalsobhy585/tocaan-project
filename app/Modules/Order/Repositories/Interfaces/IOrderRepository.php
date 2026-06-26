<?php

namespace App\Modules\Order\Repositories\Interfaces;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IOrderRepository
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function create(array $data): Order;

    public function findForUpdate(int $orderId): Order;

    public function update(Order $order, array $data): Order;

    public function delete(Order $order): bool;

    public function loadDetails(Order $order): Order;
}