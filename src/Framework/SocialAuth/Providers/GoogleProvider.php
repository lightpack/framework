<?php

namespace Lightpack\SocialAuth\Providers;

use Google\Client as GoogleClient;
use Google\Service\Oauth2;
use Google\Service\Oauth2\Userinfo;
use Lightpack\Config\Config;
use Lightpack\SocialAuth\SocialAuthInterface;
use RuntimeException;

class GoogleProvider implements SocialAuthInterface
{
    protected array $config;
    protected GoogleClient $client;
    protected bool $stateless = false;

    public function __construct(Config $config)
    {
        $this->config = $config->get('social.providers.google');
        
        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            throw new RuntimeException('Google OAuth credentials not configured');
        }

        $this->client = new GoogleClient([
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
        ]);

        $this->client->setScopes($this->config['scopes']);
    }

    public function stateless(): self
    {
        $this->stateless = true;
        return $this;
    }

    public function getAuthUrl(array $params = []): string
    {
        if ($this->stateless) {
            // For API, use state parameter
            $params['state'] = base64_encode(json_encode([
                'is_api' => true,
                'state' => bin2hex(random_bytes(16)),
                'provider' => 'google'
            ]));
        }

        if (isset($params['state'])) {
            $this->client->setState($params['state']);
        }
        
        return $this->client->createAuthUrl();
    }

    public function getUser(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (!isset($token['access_token'])) {
            throw new RuntimeException('Failed to get access token from Google');
        }

        $this->client->setAccessToken($token);
        
        $oauth2 = new Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();

        if (!($userInfo instanceof Userinfo)) {
            throw new RuntimeException('Failed to get user info from Google');
        }

        return [
            'id' => $userInfo->getId(),
            'name' => $userInfo->getName(),
            'email' => $userInfo->getEmail(),
            'avatar' => $userInfo->getPicture(),
        ];
    }
}
