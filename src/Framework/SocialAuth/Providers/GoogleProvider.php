<?php

namespace Lightpack\SocialAuth\Providers;

use Lightpack\Config\Config;
use Lightpack\Http\Http;
use Lightpack\SocialAuth\SocialAuthInterface;
use RuntimeException;

class GoogleProvider implements SocialAuthInterface
{
    protected array $config;
    protected Http $http;
    protected bool $stateless = false;

    public function __construct(Config $config)
    {
        $this->config = $config->get('social.providers.google');
        $this->http = new Http;

        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            throw new RuntimeException('Google OAuth credentials not configured');
        }
    }

    public function stateless(): self
    {
        $this->stateless = true;
        return $this;
    }

    public function getAuthUrl(array $params = []): string
    {
        if ($this->stateless) {
            $params['state'] = base64_encode(json_encode([
                'is_api' => true,
                'state' => bin2hex(random_bytes(16)),
                'provider' => 'google'
            ]));
        }

        $queryParams = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(' ', $this->config['scopes']),
            'state' => $params['state'] ?? bin2hex(random_bytes(16)),
            'access_type' => 'online',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($queryParams);
    }

    public function getUser(string $code): array
    {
        $response = $this->http
            ->headers(['Content-Type' => 'application/x-www-form-urlencoded'])
            ->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'redirect_uri' => $this->config['redirect_uri'],
                'grant_type' => 'authorization_code',
            ]);

        if ($response->failed() || empty($response->json()['access_token'])) {
            throw new RuntimeException('Failed to get access token from Google');
        }

        $token = $response->json()['access_token'];

        $response = $this->http
            ->headers([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if ($response->failed()) {
            throw new RuntimeException('Failed to get user info from Google');
        }

        $userInfo = $response->json();

        return [
            'id' => $userInfo['sub'],
            'name' => $userInfo['name'] ?? null,
            'email' => $userInfo['email'] ?? null,
            'avatar' => $userInfo['picture'] ?? null,
        ];
    }
}
