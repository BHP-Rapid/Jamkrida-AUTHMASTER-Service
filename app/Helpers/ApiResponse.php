<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Request berhasil diproses.',
        int $status = 200,
        array $meta = [],
        array $extra = [],
    ): JsonResponse {
        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $extra), $status);
    }

    public static function error(
        string $message = 'Terjadi kesalahan pada server.',
        int $status = 500,
        array $errors = [],
        mixed $data = null,
        array $extra = [],
    ): JsonResponse {
        return response()->json(array_merge([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => $data,
        ], $extra), $status);
    }
}
