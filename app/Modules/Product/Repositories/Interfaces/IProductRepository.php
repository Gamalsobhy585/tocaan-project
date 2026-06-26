<?php

namespace App\Modules\Product\Repositories\Interfaces;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IProductRepository
{
    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ): LengthAwarePaginator;

    public function create(array $data): Product;

    public function delete(Product $product): bool;

    public function deleteBulk(array $ids): int;
}