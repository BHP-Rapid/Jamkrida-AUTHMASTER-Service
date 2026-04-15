<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CheckIdRequest;
use App\Services\PublicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function __construct(
        protected PublicService $publicService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->publicService->index($request->ip(), $request->userAgent());

        return $this->respond($result, [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
        ]);
    }

    public function checkId(CheckIdRequest $request): JsonResponse
    {
        $payload = array_filter([
            'user_id' => $request->query('user_id', $request->validated('user_id')),
            'email' => $request->query('email', $request->validated('email')),
        ], fn ($value) => $value !== null && $value !== '');

        $result = $this->publicService->checkId($payload, $request->ip(), $request->userAgent());

        return $this->respond($result, [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    protected function respond(array $result, array $headers = []): JsonResponse
    {
        $response = $result['success']
            ? ApiResponse::success(
                data: $result['data'] ?? null,
                message: $result['message'] ?? 'Request berhasil diproses.',
                status: $result['status'],
            )
            : ApiResponse::error(
                message: $result['message'],
                status: $result['status'],
                errors: $result['errors'] ?? [],
                data: $result['data'] ?? null,
            );

        return $response->withHeaders($headers);
    }
}
