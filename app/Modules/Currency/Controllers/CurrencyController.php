<?php

namespace App\Modules\Currency\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Modules\Currency\Requests\StoreCurrencyRequest;
use App\Modules\Currency\Resources\CurrencyResource;
use App\Modules\Currency\Services\Interfaces\ICurrencyService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private readonly ICurrencyService $service
    ) {
    }

    public function index(): JsonResponse
    {
        $currencies = CurrencyResource::collection(
            $this->service->index()
        );

        return $this->returnData(
            'Currencies retrieved successfully.',
            200,
            $currencies
        );
    }

    public function add(StoreCurrencyRequest $request): JsonResponse
    {
        $currency = $this->service->add(
            $request->validated()
        );

        return $this->returnData(
            'Currency added successfully.',
            201,
            new CurrencyResource($currency)
        );
    }

    public function toggleActive(Currency $currency): JsonResponse
    {
        $currency = $this->service->toggleActive(
            $currency->id
        );

        $message = $currency->is_active
            ? 'Currency activated successfully.'
            : 'Currency deactivated successfully.';

        return $this->returnData(
            $message,
            200,
            new CurrencyResource($currency)
        );
    }
}