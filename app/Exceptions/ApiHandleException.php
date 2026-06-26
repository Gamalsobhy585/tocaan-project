<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ApiHandleException extends Exception
{
    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    protected function handleApiException(Throwable $exception)
    {
        $statusCode = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;

        return response()->json([
            'error' => [
                'message' => $exception->getMessage(),
                'status_code' => $statusCode,
            ],
        ], $statusCode);
    }

}
