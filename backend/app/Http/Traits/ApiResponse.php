<?php

namespace App\Http\Traits;

trait ApiResponse
{
    protected function successResponse($data, string $message = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse(string $message, $errors = null, int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    protected function notFoundResponse(string $message = 'Resource not found')
    {
        return $this->errorResponse($message, null, 404);
    }

    protected function unauthorizedResponse(string $message = 'Unauthorized')
    {
        return $this->errorResponse($message, null, 401);
    }

    protected function validationErrorResponse($errors, string $message = 'Validation failed')
    {
        return $this->errorResponse($message, $errors, 422);
    }
}