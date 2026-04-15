<?php

namespace App\Repositories;

use App\Models\RefreshToken;

class RefreshTokenRepository
{
    public function create(array $payload): RefreshToken
    {
        return RefreshToken::query()->create($payload);
    }

    public function findActiveByPlainTextToken(string $plainTextToken): ?RefreshToken
    {
        return RefreshToken::query()
            ->where('token_hash', $this->hashToken($plainTextToken))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function rotate(RefreshToken $currentToken, RefreshToken $replacementToken): void
    {
        $currentToken->forceFill([
            'last_used_at' => now(),
            'revoked_at' => now(),
            'replaced_by_id' => $replacementToken->getKey(),
        ])->save();
    }

    public function revoke(RefreshToken $refreshToken): void
    {
        $refreshToken->forceFill([
            'revoked_at' => now(),
        ])->save();
    }

    public function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
