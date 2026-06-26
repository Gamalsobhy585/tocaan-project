<?php

namespace App\Modules\Product\Services;

use App\Models\Product;
use App\Modules\Product\Repositories\Interfaces\IProductRepository;
use App\Modules\Product\Services\Interfaces\IProductService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService implements IProductService
{
    public function __construct(
        private readonly IProductRepository $repository
    ) {
    }

    public function index(
        int $perPage = 15,
        ?string $search = null
    ): LengthAwarePaginator {
        $perPage = min(max($perPage, 1), 100);

        return $this->repository->paginate(
            perPage: $perPage,
            search: $search
        );
    }

    public function add(array $data): Product
    {
        return $this->repository->create($data);
    }

    public function delete(Product $product): bool
    {
        return $this->repository->delete($product);
    }

    public function deleteBulk(array $ids): int
    {
        return $this->repository->deleteBulk($ids);
    }
}