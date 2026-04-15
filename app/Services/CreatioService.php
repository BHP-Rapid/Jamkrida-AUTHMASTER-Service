<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CreatioService
{
    protected string $baseUrl;

    protected ?string $username;

    protected ?string $password;

    protected ?string $bpmcsrf = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.creatio.url', ''), '/');
        $this->username = config('services.creatio.username');
        $this->password = config('services.creatio.password');
    }

    protected function login(): bool
    {
        $url = $this->baseUrl.'/ServiceModel/AuthService.svc/Login';

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($url, [
                'UserName' => $this->username,
                'UserPassword' => $this->password,
            ]);

        if (! $response->successful()) {
            return false;
        }

        $csrf = $response->header('BPMCSRF');

        $cookie1 = $response->cookies()->getCookieByName('.ASPXAUTH');
        $cookie2 = $response->cookies()->getCookieByName('BPMCSRF');
        $cookie3 = $response->cookies()->getCookieByName('BPMLOADER');
        $cookie4 = $response->cookies()->getCookieByName('UserType');

        $cookieParts = [];

        foreach ([$cookie1, $cookie2, $cookie3, $cookie4] as $cookie) {
            if ($cookie !== null) {
                $cookieParts[] = $cookie->getName().'='.$cookie->getValue();
            }
        }

        $this->bpmcsrf = $cookie2?->getValue() ?? (is_array($csrf) ? ($csrf[0] ?? null) : $csrf);
        $cookieString = implode('; ', $cookieParts);

        Cache::forget('creatio_cookie_arr');
        Cache::forget('creatio_cookie');
        Cache::forget('creatio_csrf');

        Cache::put('creatio_cookie_arr', $response->headers()['set-cookie'] ?? [], now()->addMinutes(20));
        Cache::put('creatio_cookie', $cookieString, now()->addMinutes(20));
        Cache::put('creatio_csrf', is_array($csrf) ? ($csrf[0] ?? null) : $csrf, now()->addMinutes(20));

        return true;
    }

    protected function getAuthHeaders(): array
    {
        $this->login();

        $cookies = (string) Cache::get('creatio_cookie', '');
        $csrf = Cache::get('creatio_csrf');

        if (is_array($csrf)) {
            $csrf = $csrf[0] ?? null;
        }

        return [
            'Content-Type' => 'application/json',
            'BPMCSRF' => $this->bpmcsrf ?? (string) $csrf,
            'Cookie' => $cookies,
        ];
    }

    public function request(
        string $method,
        string $endpoint,
        array $body = [],
        array $query = [],
        int $retry = 1,
        string $binaryData = '',
        string $binaryType = '',
    ): Response {
        $headers = $this->getAuthHeaders();
        $url = $this->baseUrl.'/'.ltrim($endpoint, '/');
        $request = Http::withHeaders($headers);
        $tempPath = storage_path('app/lampiran/tempfile.bin');

        $response = match (strtolower($method)) {
            'get' => $request->get($url, $query),
            'post' => $request->post($url, $body),
            'put' => $request->put($url, $body),
            'delete' => $request->delete($url),
            'binary' => $request->withBody($binaryData, $binaryType)->post($url),
            'download' => $request->withOptions([
                'verify' => false,
                'stream' => true,
                'read_timeout' => 120,
                'connect_timeout' => 30,
                'sink' => $tempPath,
            ])->get($url),
            default => throw new \RuntimeException('Unsupported HTTP method'),
        };

        if ($retry < 4 && in_array($response->status(), [401, 403], true)) {
            $this->login();

            return $this->request($method, $endpoint, $body, $query, $retry + 1, $binaryData, $binaryType);
        }

        return $response;
    }
}
