<?php

namespace App\Modules\Product\Repositories\Implementation;

use App\Models\Product;
use App\Modules\Product\Repositories\Interfaces\IProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository implements IProductRepository
{
    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ): LengthAwarePaginator {
        return Product::query()
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    public function deleteBulk(array $ids): int
    {
        return Product::query()
            ->whereKey($ids)
            ->delete();
    }
}