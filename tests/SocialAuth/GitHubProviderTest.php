<?php

use PHPUnit\Framework\TestCase;
use Lightpack\SocialAuth\Providers\GitHubProvider;
use Lightpack\Config\Config;

class GitHubProviderTest extends TestCase
{
    public function test_throws_exception_if_config_missing()
    {
        // Test missing config (error path)
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('get')->willReturn([]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GitHub OAuth credentials not configured');
        new GitHubProvider($mockConfig);
    }

    public function test_generates_auth_url_with_stateless()
    {
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('get')->willReturn([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost/github-callback',
            'scopes' => ['read:user', 'user:email']
        ]);
        $provider = new GitHubProvider($mockConfig);
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
            'redirect_uri' => 'http://localhost/github-web-callback',
            'scopes' => ['read:user']
        ]);
        $provider = new GitHubProvider($mockConfig);
        $url = $provider->getAuthUrl();
        $this->assertStringContainsString('client_id=web-client-id', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function test_get_user_throws_on_invalid_token()
    {
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('get')->willReturn([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost/github-callback',
            'scopes' => ['read:user', 'user:email']
        ]);
        $provider = new GitHubProvider($mockConfig);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid|expired|token/i');
        $provider->getUser('invalid-token');
    }
}
