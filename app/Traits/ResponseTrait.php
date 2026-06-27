<?php

namespace App\Traits;

use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;

trait ResponseTrait
{
    private const DATE_FORMAT = 'd-m-Y H:i:s';

    protected function returnDataWithPagination(
        string $message,
        int $statusCode,
        mixed $data
    ): JsonResponse {
        return Response::json([
            'status' => 'success',
            'code' => $statusCode,
            'message' => $message,
            'data' => $this->formatDates(
                $data->resolve(request())
            ),
            'pagination' => $data->additional['pagination'] ?? null,
        ], $statusCode);
    }

    public function returnError(
        string $message,
        int $statusCode
    ): void {
        abort($statusCode, $message);
    }

    public function success(
        string $message,
        int $statusCode
    ): JsonResponse {
        return Response::json([
            'status' => 'success',
            'code' => $statusCode,
            'message' => $message,
        ], $statusCode);
    }

    public function returnErrorNotAbort(
        string $message,
        int $statusCode
    ): JsonResponse {
        return Response::json([
            'status' => 'error',
            'code' => $statusCode,
            'message' => $message,
        ], $statusCode);
    }

    public function returnData(
        string $message,
        int $statusCode,
        mixed $value
    ): JsonResponse {
        return Response::json([
            'status' => 'success',
            'code' => $statusCode,
            'message' => $message,
            'data' => $this->formatDates($value),
        ], $statusCode);
    }

    /**
     * Format every Carbon / DateTime object inside the response.
     */
    private function formatDates(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(self::DATE_FORMAT);
        }

        if ($value instanceof JsonResource) {
            return $this->formatDates(
                $value->resolve(request())
            );
        }

        if ($value instanceof Collection) {
            return $value
                ->map(
                    fn (mixed $item): mixed =>
                        $this->formatDates($item)
                )
                ->all();
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->formatDates($item);
            }

            return $value;
        }

        if ($value instanceof Arrayable) {
            return $this->formatDates(
                $value->toArray()
            );
        }

        return $value;
    }
}