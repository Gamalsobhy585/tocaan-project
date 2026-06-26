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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

class ProductController extends Controller
{
    public function __construct(
        private readonly IProductService $service
    ) {
    }

    public function index(
        Request $request
    ): AnonymousResourceCollection {
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

        return ProductResource::collection($products);
    }

    public function add(
        StoreProductRequest $request
    ): JsonResponse {
        $product = $this->service->add(
            $request->validated()
        );

        return response()->json([
            'message' => 'Product added successfully.',
            'data' => new ProductResource($product),
        ], 201);
    }

    public function delete(
        Product $product
    ): JsonResponse {
        $this->service->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
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

        return response()->json([
            'message' => 'Products import completed.',
            'processed_rows' => $import->processedRows(),
            'failed_rows_count' => $failures->count(),
            'failed_rows' => $failures,
        ]);
    }

    public function deleteBulk(
        BulkDeleteProductsRequest $request
    ): JsonResponse {
        $deletedCount = $this->service->deleteBulk(
            $request->validated('ids')
        );

        return response()->json([
            'message' => 'Selected products deleted successfully.',
            'deleted_count' => $deletedCount,
        ]);
    }
}