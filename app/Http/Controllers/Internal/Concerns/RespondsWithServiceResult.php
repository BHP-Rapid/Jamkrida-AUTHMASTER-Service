<?php

namespace App\Http\Controllers\Internal\Concerns;

use App\Helpers\ApiResponse;

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
        return ApiResponse::success(
            data: $data,
            message: $message,
            status: $status,
            meta: $extra['meta'] ?? [],
            extra: array_diff_key($extra, ['meta' => true]),
        );
    }

    protected function errorResponse(string $message, int $status = 400, array $errors = [], mixed $data = null, array $extra = [])
    {
        return ApiResponse::error(
            message: $message,
            status: $status,
            errors: $errors,
            data: $data,
            extra: $extra,
        );
    }
}
