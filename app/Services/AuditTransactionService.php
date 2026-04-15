<?php

namespace App\Services;

use App\Models\AuditTransactions;

class AuditTransactionService
{
    public function logAuditTrail(
        string $method,
        string $endpoint,
        ?string $targetTable,
        string $userEmail,
        string $userRole,
        ?string $requestPayload = null,
        string|int|null $id = null,
        ?string $name = null,
        bool $isSuccess = false,
    ): bool {
        $result = AuditTransactions::query()->create([
            'http_method' => $method,
            'api_endpoint' => $endpoint,
            'target_table' => $targetTable,
            'is_success' => $isSuccess,
            'user_email' => $userEmail,
            'user_role' => $userRole,
            'request_payload' => $requestPayload,
            'created_by_id' => $id,
            'created_by_name' => $name,
            'created_at' => now(),
        ]);

        return (bool) $result;
    }
}
