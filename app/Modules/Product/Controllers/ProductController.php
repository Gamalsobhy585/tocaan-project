<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Modules\Product\Imports\ProductsImport;
use App\Modules\Product\Requests\BulkDeleteProductsRequest;
use App\Modules\Product\Requests\ImportProductsRequest;
use App\Modules\Product\Requests\StoreProductRequest;
use App\Modules\Product\Resources\ProductResource;
use App\Modules\Product\Services\Interfaces\IProductService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

class ProductController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private readonly IProductService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $products = $this->service->index(
            perPage: (int) ($validated['per_page'] ?? 15),
            search: $validated['search'] ?? null
        );

        $productsResource = ProductResource::collection($products)
            ->additional([
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                    'has_more_pages' => $products->hasMorePages(),
                ],
            ]);

        return $this->returnDataWithPagination(
            'Products retrieved successfully.',
            200,
            $productsResource
        );
    }

    public function add(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->add(
            $request->validated()
        );

        return $this->returnData(
            'Product added successfully.',
            201,
            new ProductResource($product)
        );
    }

    public function delete(Product $product): JsonResponse
    {
        $this->service->delete($product);

        return $this->success(
            'Product deleted successfully.',
            200
        );
    }

    public function importBulk(
        ImportProductsRequest $request
    ): JsonResponse {
        $import = new ProductsImport();

        Excel::import(
            $import,
            $request->file('file')
        );

        $failures = collect($import->failures())
            ->map(function (Failure $failure): array {
                return [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ];
            })
            ->values();

        return $this->returnData(
            'Products import completed.',
            200,
            [
                'processed_rows' => $import->processedRows(),
                'failed_rows_count' => $failures->count(),
                'failed_rows' => $failures,
            ]
        );
    }

    public function deleteBulk(
        BulkDeleteProductsRequest $request
    ): JsonResponse {
        $deletedCount = $this->service->deleteBulk(
            $request->validated('ids')
        );

        return $this->returnData(
            'Selected products deleted successfully.',
            200,
            [
                'deleted_count' => $deletedCount,
            ]
        );
    }
}