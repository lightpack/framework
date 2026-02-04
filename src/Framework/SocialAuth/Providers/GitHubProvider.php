<?php

namespace Lightpack\SocialAuth\Providers;

use Lightpack\Config\Config;
use RuntimeException;
use Lightpack\Http\Http;
use Lightpack\SocialAuth\SocialAuthInterface;

class GitHubProvider implements SocialAuthInterface
{
    protected array $config;
    protected Http $http;
    protected bool $stateless = false;

    public function __construct(Config $config)
    {
        $this->config = $config->get('social.providers.github');
        $this->http = new Http;
        
        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            throw new RuntimeException('GitHub OAuth credentials not configured');
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
                'provider' => 'github'
            ]));
        }

        $queryParams = [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(' ', $this->config['scopes']),
            'state' => $params['state'] ?? bin2hex(random_bytes(16)),
        ];

        return 'https://github.com/login/oauth/authorize?' . http_build_query($queryParams);
    }

    public function getUser(string $code): array
    {
        // Exchange code for access token
        $response = $this->http
            ->headers(['Accept' => 'application/json'])
            ->post('https://github.com/login/oauth/access_token', [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'code' => $code,
                'redirect_uri' => $this->config['redirect_uri'],
            ]);

        if ($response->failed() || empty($response->json()['access_token'])) {
            throw new RuntimeException('Failed to get access token from GitHub');
        }

        $token = $response->json()['access_token'];

        // Get user info
        $response = $this->http
            ->headers([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Lightpack-App',
            ])
            ->get('https://api.github.com/user');

        if ($response->failed()) {
            throw new RuntimeException('Failed to get user info from GitHub');
        }

        $userInfo = $response->json();

        // Get primary email if not public
        if (empty($userInfo['email'])) {
            $response = $this->http
                ->headers([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'Lightpack-App',
                ])
                ->get('https://api.github.com/user/emails');

            if ($response->ok()) {
                foreach ($response->json() as $email) {
                    if ($email['primary'] && $email['verified']) {
                        $userInfo['email'] = $email['email'];
                        break;
                    }
                }
            }
        }

        if (empty($userInfo['email'])) {
            throw new RuntimeException('Could not get verified email from GitHub');
        }

        return [
            'id' => (string) $userInfo['id'],
            'name' => $userInfo['name'] ?? $userInfo['login'],
            'email' => $userInfo['email'],
            'avatar' => $userInfo['avatar_url'],
        ];
    }
}
