<?php

namespace Lightpack\SocialAuth\Providers;

use Lightpack\Config\Config;
use RuntimeException;
use Lightpack\Http\Http;
use Lightpack\SocialAuth\SocialAuthInterface;

class LinkedInProvider implements SocialAuthInterface
{
    protected array $config;
    protected Http $http;
    protected bool $stateless = false;

    public function __construct(Config $config)
    {
        $this->config = $config->get('social.providers.linkedin');
        $this->http = new Http;
        
        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            throw new RuntimeException('LinkedIn OAuth credentials not configured');
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
                'provider' => 'linkedin'
            ]));
        }

        $queryParams = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(' ', $this->config['scopes']),
            'state' => $params['state'] ?? bin2hex(random_bytes(16)),
            'nonce' => bin2hex(random_bytes(16)),
        ];

        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query($queryParams);
    }

    public function getUser(string $code): array
    {
        // Exchange code for tokens
        $response = $this->http
            ->headers(['Content-Type' => 'application/x-www-form-urlencoded'])
            ->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'grant_type' => 'authorization_code',
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'code' => $code,
                'redirect_uri' => $this->config['redirect_uri'],
            ]);

        if ($response->failed() || empty($response->json()['access_token'])) {
            throw new RuntimeException('Failed to get access token from LinkedIn');
        }

        $tokens = $response->json();
        $idToken = $tokens['id_token'] ?? null;

        if (!$idToken) {
            throw new RuntimeException('No ID token received from LinkedIn');
        }

        // Decode the ID token (it's a JWT)
        $tokenParts = explode('.', $idToken);
        if (count($tokenParts) !== 3) {
            throw new RuntimeException('Invalid ID token format');
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])), true);
        
        if (!$payload) {
            throw new RuntimeException('Failed to decode ID token');
        }

        // Get user info from ID token
        return [
            'id' => $payload['sub'],
            'name' => $payload['name'] ?? ($payload['given_name'] . ' ' . $payload['family_name']),
            'email' => $payload['email'] ?? null,
            'avatar' => $payload['picture'] ?? null,
        ];
    }
}
