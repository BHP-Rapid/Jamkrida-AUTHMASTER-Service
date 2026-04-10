<?php

namespace App\Clients;

use Illuminate\Http\Client\Response;

class UserServiceClient extends BaseServiceClient
{
    public function __construct()
    {
        parent::__construct(
            baseUrl: config('services.user_service.base_url'),
            token: config('services.user_service.token'),
            headers: [
                'X-Service-Name' => config('app.name', 'authmaster-service'),
            ],
            timeout: (int) config('services.user_service.timeout', 10),
        );
    }

    public function findUserByEmail(string $email): Response
    {
        return $this->get('/users', ['email' => $email]);
    }
}
