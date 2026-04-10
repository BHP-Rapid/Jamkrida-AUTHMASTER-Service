<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Repositories\UserMitraRepository;
use App\Repositories\UserRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsureForwardedUserToken
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserMitraRepository $userMitraRepository,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->header('X-User-Token', '');

        if ($token === '') {
            return ApiResponse::error(
                message: 'Unauthorized: X-User-Token missing.',
                status: 401,
            );
        }

        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $authType = (string) $payload->get('auth_type', 'admin');
            $subject = $payload->get('sub');

            $user = match ($authType) {
                'mitra' => $this->userMitraRepository->findById($subject),
                default => $this->userRepository->findById($subject),
            };

            if (! $user) {
                return ApiResponse::error(
                    message: 'Unauthorized: forwarded user not found.',
                    status: 401,
                );
            }

            $request->attributes->set('forwarded_user', $user);
            $request->attributes->set('forwarded_auth_type', $authType);
            $request->attributes->set('forwarded_user_id', (string) ($payload->get('user_id') ?? $user->user_id ?? $user->getKey()));
            $request->attributes->set('forwarded_user_token_payload', $payload->toArray());

            return $next($request);
        } catch (TokenExpiredException) {
            return ApiResponse::error(
                message: 'Unauthorized: forwarded user token expired.',
                status: 401,
            );
        } catch (TokenInvalidException) {
            return ApiResponse::error(
                message: 'Unauthorized: forwarded user token invalid.',
                status: 401,
            );
        } catch (JWTException) {
            return ApiResponse::error(
                message: 'Unauthorized: forwarded user token missing.',
                status: 401,
            );
        }
    }
}
