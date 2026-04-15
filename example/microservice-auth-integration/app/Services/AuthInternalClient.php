<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AuthInternalClient
{
    public function context(string $userToken): array
    {
        $userId = $this->resolveUserIdFromToken($userToken);

        return $this->client($userToken)
            ->get($this->baseUrl()."/api/internal/users/{$userId}/context")
            ->throw()
            ->json();
    }

    public function checkRole(array $roles, string $userToken): array
    {
        return $this->client($userToken)
            ->post($this->baseUrl().'/api/internal/roles/check', [
                'roles' => $roles,
            ])
            ->throw()
            ->json();
    }

    public function checkPermission(string|int $menuIdentifier, string $action, string $userToken): array
    {
        $payload = [
            'action' => $action,
        ];

        if (is_numeric($menuIdentifier)) {
            $payload['menu_id'] = $menuIdentifier;
        } else {
            $payload['menu_code'] = $menuIdentifier;
        }

        return $this->client($userToken)
            ->post($this->baseUrl().'/api/internal/permissions/check', $payload)
            ->throw()
            ->json();
    }

    protected function client(string $userToken): PendingRequest
    {
        return Http::withToken((string) config('services.auth_internal.token'))
            ->acceptJson()
            ->timeout((int) config('services.auth_internal.timeout', 10))
            ->withHeaders([
                'X-User-Token' => $userToken,
            ]);
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.auth_internal.url'), '/');
    }

    protected function resolveUserIdFromToken(string $userToken): string
    {
        $segments = explode('.', $userToken);
        $payloadSegment = $segments[1] ?? '';

        if ($payloadSegment === '') {
            throw new RuntimeException('User token payload tidak valid.');
        }

        $decodedPayload = $this->decodeBase64Url($payloadSegment);
        $payload = json_decode($decodedPayload, true);

        if (! is_array($payload)) {
            throw new RuntimeException('User token payload tidak dapat dibaca.');
        }

        $userId = (string) ($payload['user_id'] ?? $payload['sub'] ?? '');

        if ($userId === '') {
            throw new RuntimeException('user_id tidak ditemukan pada payload token user.');
        }

        return $userId;
    }

    protected function decodeBase64Url(string $value): string
    {
        $replaced = strtr($value, '-_', '+/');
        $padding = strlen($replaced) % 4;

        if ($padding > 0) {
            $replaced .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($replaced, true);

        if ($decoded === false) {
            throw new RuntimeException('Gagal decode payload token user.');
        }

        return $decoded;
    }
}
