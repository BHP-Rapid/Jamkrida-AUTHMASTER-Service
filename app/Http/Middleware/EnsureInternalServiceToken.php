<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalServiceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('services.internal.token');
        $providedToken = (string) $request->bearerToken();

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return ApiResponse::error(
                message: 'Unauthorized internal service request.',
                status: 401,
                errors: [
                    'token' => ['Bearer token tidak valid.'],
                ],
            );
        }

        return $next($request);
    }
}
