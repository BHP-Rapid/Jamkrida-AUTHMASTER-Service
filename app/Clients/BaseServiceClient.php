<?php

namespace App\Clients;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseServiceClient
{
    public function __construct(
        protected readonly ?string $baseUrl = null,
        protected readonly ?string $token = null,
        protected readonly array $headers = [],
        protected readonly int $timeout = 10,
    ) {
    }

    protected function request(): PendingRequest
    {
        $request = Http::acceptJson()
            ->contentType('application/json')
            ->timeout($this->timeout);

        if ($this->baseUrl) {
            $request = $request->baseUrl($this->baseUrl);
        }

        if ($this->token) {
            $request = $request->withToken($this->token);
        }

        if ($this->headers !== []) {
            $request = $request->withHeaders($this->headers);
        }

        return $request;
    }

    protected function get(string $uri, array $query = []): Response
    {
        return $this->request()->get($uri, $query);
    }

    protected function post(string $uri, array $payload = []): Response
    {
        return $this->request()->post($uri, $payload);
    }

    protected function put(string $uri, array $payload = []): Response
    {
        return $this->request()->put($uri, $payload);
    }

    protected function patch(string $uri, array $payload = []): Response
    {
        return $this->request()->patch($uri, $payload);
    }

    protected function delete(string $uri, array $payload = []): Response
    {
        return $this->request()->delete($uri, $payload);
    }
}
