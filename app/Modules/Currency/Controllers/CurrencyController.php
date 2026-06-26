<?php

namespace App\Modules\Currency\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Modules\Currency\Requests\StoreCurrencyRequest;
use App\Modules\Currency\Resources\CurrencyResource;
use App\Modules\Currency\Services\Interfaces\ICurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CurrencyController extends Controller
{
    public function __construct(
        private readonly ICurrencyService $service
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        return CurrencyResource::collection(
            $this->service->index()
        );
    }

    public function add(StoreCurrencyRequest $request): JsonResponse
    {
        $currency = $this->service->add(
            $request->validated()
        );

        return response()->json([
            'message' => 'Currency added successfully.',
            'data' => new CurrencyResource($currency),
        ], 201);
    }

    public function toggleActive(Currency $currency): JsonResponse
    {
        $currency = $this->service->toggleActive(
            $currency->id
        );

        return response()->json([
            'message' => $currency->is_active
                ? 'Currency activated successfully.'
                : 'Currency deactivated successfully.',

            'data' => new CurrencyResource($currency),
        ]);
    }
}