<?php

use Lightpack\Config\Config;
use Lightpack\SocialAuth\Providers\GoogleProvider;
use PHPUnit\Framework\TestCase;

class GoogleProviderTest extends TestCase
{
    public function test_throws_exception_if_config_missing()
    {
        // Test missing config (error path)
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('get')->willReturn([]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Google OAuth credentials not configured');
        new GoogleProvider($mockConfig);
    }

    public function test_generates_auth_url_with_stateless()
    {
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('get')->willReturn([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost/callback',
            'scopes' => ['email'],
        ]);
        $provider = new GoogleProvider($mockConfig);
        $url = $provider->stateless()->getAuthUrl();
        $this->assertStringContainsString('client_id=test-client-id', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function test_generates_auth_url_for_web_flow()
    {
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('get')->willReturn([
            'client_id' => 'web-client-id',
            'client_secret' => 'web-client-secret',
            'redirect_uri' => 'http://localhost/web-callback',
            'scopes' => ['profile'],
        ]);
        $provider = new GoogleProvider($mockConfig);
        $url = $provider->getAuthUrl();
        $this->assertStringContainsString('client_id=web-client-id', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function test_get_user_throws_on_invalid_token()
    {
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('get')->willReturn([
            'client_id' => 'id',
            'client_secret' => 'secret',
            'redirect_uri' => 'http://localhost/callback',
            'scopes' => ['email'],
        ]);
        $provider = new GoogleProvider($mockConfig);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to get access token from Google');
        $provider->getUser('bad-code');
    }
}
