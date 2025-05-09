<?php

use PHPUnit\Framework\TestCase;
use Lightpack\SocialAuth\Providers\LinkedInProvider;
use Lightpack\Config\Config;

class LinkedInProviderTest extends TestCase
{
    public function test_throws_exception_if_config_missing()
    {
        // Test missing config (error path)
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('get')->willReturn([]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LinkedIn OAuth credentials not configured');
        new LinkedInProvider($mockConfig);
    }

    public function test_generates_auth_url_with_stateless()
    {
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('get')->willReturn([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost/linkedin-callback',
            'scopes' => ['r_liteprofile', 'r_emailaddress']
        ]);
        $provider = new LinkedInProvider($mockConfig);
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
            'redirect_uri' => 'http://localhost/linkedin-web-callback',
            'scopes' => ['r_liteprofile']
        ]);
        $provider = new LinkedInProvider($mockConfig);
        $url = $provider->getAuthUrl();
        $this->assertStringContainsString('client_id=web-client-id', $url);
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('nonce=', $url);
    }

    public function test_get_user_throws_on_invalid_token()
    {
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('get')->willReturn([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost/linkedin-callback',
            'scopes' => ['r_liteprofile', 'r_emailaddress']
        ]);
        $provider = new LinkedInProvider($mockConfig);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid|expired|token/i');
        $provider->getUser('invalid-token');
    }
}
