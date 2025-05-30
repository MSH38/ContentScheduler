<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait CustomResponseTrait
{
    /**
     * Return a standardized JSON response.
     *
     * @param mixed $result The response data or error message.
     * @param int $status HTTP status code. Defaults to 200.
     * @param array $additional Any additional key-value pairs to include in the response.
     * @return JsonResponse
     */
    public function customResponse(mixed $result = null, int $status = 200, array $additional = []): JsonResponse
    {
        $isSuccess = $status === 200;
        $key = $isSuccess ? 'result' : 'error';

        if (is_string($result)) {
            $result = ['message' => $result];
        }

        $response = [
            'status'  => $status,
            'success' => $isSuccess,
            $key      => $result,
        ];

        return response()->json(array_merge($response, $additional), $status);
    }
}
