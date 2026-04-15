<?php

namespace App\Http\Controllers\Internal\Concerns;

trait RespondsWithServiceResult
{
    protected function respond(array $result)
    {
        if (! $result['success']) {
            return $this->errorResponse(
                message: $result['message'],
                status: $result['status'],
                errors: $result['errors'] ?? [],
                data: $result['data'] ?? null,
            );
        }

        return $this->successResponse(
            data: $result['data'] ?? null,
            message: $result['message'] ?? 'Request berhasil diproses.',
            status: $result['status'],
        );
    }

    protected function successResponse(mixed $data = null, string $message = 'Request berhasil diproses.', int $status = 200, array $extra = [])
    {
        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $extra), $status);
    }

    protected function errorResponse(string $message, int $status = 400, array $errors = [], mixed $data = null, array $extra = [])
    {
        return response()->json(array_merge([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => $data,
        ], $extra), $status);
    }
}
