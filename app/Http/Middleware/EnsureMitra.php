<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Models\UserMitra;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMitra
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $authType = (string) $request->attributes->get('auth_type', '');

        if (! $user) {
            return ApiResponse::error(
                message: 'Unauthorized: user not found.',
                status: 401,
            );
        }

        if ($authType !== 'mitra' || ! $user instanceof UserMitra) {
            return ApiResponse::error(
                message: 'Forbidden: mitra token required.',
                status: 403,
            );
        }

        return $next($request);
    }
}
