<?php

namespace App\Modules\Product\Services\Interfaces;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IProductService
{
    public function index(
        int $perPage = 15,
        ?string $search = null
    ): LengthAwarePaginator;

    public function add(array $data): Product;

    public function delete(Product $product): bool;

    public function deleteBulk(array $ids): int;
}