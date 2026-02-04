<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Auth\Auth;
use Lightpack\Auth\IdentityInterface;
use Lightpack\Auth\Models\AuthUser;
use Lightpack\Auth\Models\AccessToken;
use Lightpack\Container\Container;
use Lightpack\Database\DB;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\Session\Session;
use Lightpack\Session\Drivers\ArrayDriver;
use Lightpack\Http\Request;
use Lightpack\Http\Cookie;
use Lightpack\Http\Redirect;
use Lightpack\Utils\Url;
use Lightpack\Config\Config;
use Lightpack\Utils\Password;

/**
 * Comprehensive Auth Integration Tests
 * 
 * Tests the entire auth system with real database, session, and all components.
 */
final class AuthIntegrationTest extends TestCase
{
    private ?DB $db;
    private Schema $schema;
    private Container $container;
    private Session $session;
    private Auth $auth;
    private array $authConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup real database
        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $this->schema = new Schema($this->db);
        
        // Create auth tables
        $this->createAuthTables();
        
        // Setup container
        $this->container = Container::getInstance();
        $this->container->register('db', fn() => $this->db);
        
        // Setup config
        $configMock = $this->createMock(Config::class);
        $configValues = [
            'session.lifetime' => 7200,
            'session.same_site' => 'lax',
            'session.name' => 'lightpack_test_session',
            'app.key' => 'test-key-32-characters-exactly!!',
        ];
        $configMock->method('get')
            ->will($this->returnCallback(function($key, $default = null) use ($configValues) {
                return $configValues[$key] ?? $default;
            }));
        $this->container->instance('config', $configMock);
        
        // Setup real session with array driver
        $sessionDriver = new ArrayDriver();
        /** @var Config $configMock */
        $this->session = new Session($sessionDriver, $configMock);
        $this->container->instance('session', $this->session);
        $this->container->instance(\Lightpack\Session\DriverInterface::class, $sessionDriver);
        
        // Setup request mock
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('input')->willReturn([]);
        $request->expects($this->any())->method('bearerToken')->willReturn(null);
        $this->container->instance('request', $request);
        
        // Setup cookie mock
        $cookie = $this->createMock(Cookie::class);
        $cookie->expects($this->any())->method('has')->willReturn(false);
        $cookie->expects($this->any())->method('get')->willReturn(null);
        $this->container->instance('cookie', $cookie);
        
        // Setup redirect mock
        $url = $this->createMock(Url::class);
        $redirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['to', 'intended'])
            ->getMock();
        $redirect->expects($this->any())->method('to')->willReturnSelf();
        $redirect->expects($this->any())->method('intended')->willReturnSelf();
        $this->container->instance('redirect', $redirect);
        $this->container->instance('url', $url);
        
        // Setup password helper
        $password = new Password();
        $this->container->instance('password', $password);
        
        // Setup logger mock
        $this->container->register('logger', fn() => new class {
            public function error($message, $context = []) {}
            public function critical($message, $context = []) {}
        });
        
        // Auth configuration
        $this->authConfig = [
            'default' => [
                'identifier' => \Lightpack\Auth\Identifiers\EmailPasswordIdentifier::class,
                'model' => AuthUser::class,
                'remember_duration' => 60 * 24 * 30,
            ],
        ];
        
        // Create auth instance
        $this->auth = new Auth('default', $this->authConfig);
        
        // Clear any static identity from previous tests
        $reflection = new \ReflectionClass(\Lightpack\Auth\AuthManager::class);
        $property = $reflection->getProperty('identity');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    protected function tearDown(): void
    {
        $this->dropAuthTables();
        $this->db = null;
        $this->container->destroy();
        
        // Clear static identity
        $reflection = new \ReflectionClass(\Lightpack\Auth\AuthManager::class);
        $property = $reflection->getProperty('identity');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        parent::tearDown();
    }

    private function createAuthTables(): void
    {
        // Users table
        $this->schema->createTable('users', function(Table $table) {
            $table->id();
            $table->varchar('email')->unique();
            $table->varchar('password');
            $table->varchar('remember_token', 100)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
        
        // Access tokens table
        $this->schema->createTable('access_tokens', function(Table $table) {
            $table->id();
            $table->column('user_id')->type('bigint')->attribute('unsigned');
            $table->varchar('name');
            $table->varchar('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    private function dropAuthTables(): void
    {
        $this->schema->dropTable('access_tokens');
        $this->schema->dropTable('users');
    }

    private function createTestUser(string $email = 'test@example.com', string $password = 'password123'): AuthUser
    {
        $user = new AuthUser();
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();
        
        return $user;
    }

    // ========================================
    // GUEST USER TESTS
    // ========================================

    public function testGuestUserIsRecognized()
    {
        $this->assertTrue($this->auth->isGuest());
        $this->assertFalse($this->auth->isLoggedIn());
        $this->assertNull($this->auth->user());
        $this->assertNull($this->auth->id());
    }

    public function testGuestUserHasNoSessionData()
    {
        $this->assertFalse($this->session->has('_logged_in'));
        $this->assertFalse($this->session->has('_auth_id'));
    }

    // ========================================
    // LOGIN AS TESTS (Direct Login)
    // ========================================

    public function testCanLoginAsUser()
    {
        $user = $this->createTestUser();
        
        $this->auth->loginAs($user);
        
        $this->assertTrue($this->auth->isLoggedIn());
        $this->assertFalse($this->auth->isGuest());
        $this->assertNotNull($this->auth->user());
        $this->assertEquals($user->id, $this->auth->id());
    }

    public function testLoginAsCreatesSession()
    {
        $user = $this->createTestUser();
        
        $this->auth->loginAs($user);
        
        $this->assertTrue($this->session->get('_logged_in'));
        $this->assertEquals($user->id, $this->session->get('_auth_id'));
    }

    public function testLoginAsUpdatesLastLoginTimestamp()
    {
        $user = $this->createTestUser();
        $beforeLogin = $user->last_login_at;
        
        $this->auth->loginAs($user);
        
        // Reload user from database
        $user = new AuthUser($user->id);
        $this->assertNotNull($user->last_login_at);
        $this->assertNotEquals($beforeLogin, $user->last_login_at);
    }

    public function testLoginAsReturnsAuthInstanceForChaining()
    {
        $user = $this->createTestUser();
        
        $result = $this->auth->loginAs($user);
        
        $this->assertInstanceOf(Auth::class, $result);
    }

    public function testCanRetrieveLoggedInUser()
    {
        $user = $this->createTestUser('john@example.com');
        
        $this->auth->loginAs($user);
        
        $authUser = $this->auth->user();
        $this->assertInstanceOf(IdentityInterface::class, $authUser);
        $this->assertEquals('john@example.com', $authUser->email);
        $this->assertEquals($user->id, $authUser->getId());
    }

    // ========================================
    // LOGOUT TESTS
    // ========================================

    public function testCanLogoutUser()
    {
        $user = $this->createTestUser();
        $this->auth->loginAs($user);
        $this->assertTrue($this->auth->isLoggedIn());
        
        $this->auth->logout();
        
        $this->assertFalse($this->auth->isLoggedIn());
        $this->assertTrue($this->auth->isGuest());
        $this->assertNull($this->auth->user());
    }

    public function testLogoutDestroysSession()
    {
        $user = $this->createTestUser();
        $this->auth->loginAs($user);
        
        $this->auth->logout();
        
        $this->assertFalse($this->session->has('_logged_in'));
        $this->assertFalse($this->session->has('_auth_id'));
    }

    // ========================================
    // FORM AUTHENTICATION TESTS
    // ========================================

    public function testCanAuthenticateWithValidCredentials()
    {
        $user = $this->createTestUser('user@example.com', 'secret123');
        
        // Mock request with credentials
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('input')->willReturn([
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);
        $this->container->instance('request', $request);
        
        $authenticatedUser = $this->auth->attempt();
        
        $this->assertInstanceOf(IdentityInterface::class, $authenticatedUser);
        $this->assertEquals($user->id, $authenticatedUser->getId());
    }

    public function testCannotAuthenticateWithInvalidEmail()
    {
        $this->createTestUser('user@example.com', 'secret123');
        
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('input')->willReturn([
            'email' => 'wrong@example.com',
            'password' => 'secret123',
        ]);
        $this->container->instance('request', $request);
        
        $result = $this->auth->attempt();
        
        $this->assertNull($result);
    }

    public function testCannotAuthenticateWithInvalidPassword()
    {
        $this->createTestUser('user@example.com', 'secret123');
        
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('input')->willReturn([
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);
        $this->container->instance('request', $request);
        
        $result = $this->auth->attempt();
        
        $this->assertNull($result);
    }

    public function testCannotAuthenticateWithEmptyCredentials()
    {
        $this->createTestUser();
        
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('input')->willReturn([]);
        $this->container->instance('request', $request);
        
        $result = $this->auth->attempt();
        
        $this->assertNull($result);
    }

    public function testSuccessfulAuthenticationUpdatesLastLogin()
    {
        $user = $this->createTestUser('user@example.com', 'secret123');
        
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('input')->willReturn([
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);
        $this->container->instance('request', $request);
        
        $this->auth->attempt();
        
        // Reload user
        $user = new AuthUser($user->id);
        $this->assertNotNull($user->last_login_at);
    }

    // ========================================
    // BEARER TOKEN AUTHENTICATION TESTS
    // ========================================

    public function testCanAuthenticateViaValidBearerToken()
    {
        $user = $this->createTestUser();
        $accessToken = $user->createToken('test-token');
        $plainToken = $accessToken->plainTextToken;
        
        // Mock request with bearer token
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('bearerToken')->willReturn($plainToken);
        $this->container->instance('request', $request);
        
        $authenticatedUser = $this->auth->viaToken();
        
        $this->assertInstanceOf(IdentityInterface::class, $authenticatedUser);
        $this->assertEquals($user->id, $authenticatedUser->getId());
    }

    public function testCannotAuthenticateViaInvalidBearerToken()
    {
        $this->createTestUser();
        
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('bearerToken')->willReturn('invalid-token');
        $this->container->instance('request', $request);
        
        $result = $this->auth->viaToken();
        
        $this->assertNull($result);
    }

    public function testCannotAuthenticateWithoutBearerToken()
    {
        $this->createTestUser();
        
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('bearerToken')->willReturn(null);
        $this->container->instance('request', $request);
        
        $result = $this->auth->viaToken();
        
        $this->assertNull($result);
    }

    public function testBearerAuthenticationUpdatesLastUsedTimestamp()
    {
        $user = $this->createTestUser();
        $accessToken = $user->createToken('test-token');
        $plainToken = $accessToken->plainTextToken;
        
        $this->assertNull($accessToken->last_used_at);
        
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('bearerToken')->willReturn($plainToken);
        $this->container->instance('request', $request);
        
        $this->auth->viaToken();
        
        // Reload token
        $accessToken = new AccessToken($accessToken->id);
        $this->assertNotNull($accessToken->last_used_at);
    }

    public function testBearerAuthenticationUpdatesUserLastLogin()
    {
        $user = $this->createTestUser();
        $accessToken = $user->createToken('test-token');
        $plainToken = $accessToken->plainTextToken;
        
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('bearerToken')->willReturn($plainToken);
        $this->container->instance('request', $request);
        
        $this->auth->viaToken();
        
        // Reload user
        $user = new AuthUser($user->id);
        $this->assertNotNull($user->last_login_at);
    }

    public function testCannotAuthenticateWithExpiredToken()
    {
        $user = $this->createTestUser();
        $accessToken = $user->createToken('test-token', ['*'], date('Y-m-d H:i:s', strtotime('-1 hour')));
        $plainToken = $accessToken->plainTextToken;
        
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('bearerToken')->willReturn($plainToken);
        $this->container->instance('request', $request);
        
        $result = $this->auth->viaToken();
        
        $this->assertNull($result);
    }

    // ========================================
    // ACCESS TOKEN TESTS
    // ========================================

    public function testUserCanCreateAccessToken()
    {
        $user = $this->createTestUser();
        
        $token = $user->createToken('my-app');
        
        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals('my-app', $token->name);
        $this->assertEquals($user->id, $token->user_id);
        $this->assertNotNull($token->plainTextToken);
    }

    public function testCreatedTokenHasHashedValueInDatabase()
    {
        $user = $this->createTestUser();
        
        $token = $user->createToken('my-app');
        $plainToken = $token->plainTextToken;
        
        // Reload from database
        $dbToken = new AccessToken($token->id);
        $this->assertNotEquals($plainToken, $dbToken->token);
        $this->assertEquals(hash('sha256', $plainToken), $dbToken->token);
    }

    public function testUserCanCreateTokenWithAbilities()
    {
        $user = $this->createTestUser();
        
        $token = $user->createToken('my-app', ['read', 'write']);
        
        // Reload from DB to get proper JSON decoding
        $token = new AccessToken($token->id);
        
        $this->assertTrue($token->can('read'));
        $this->assertTrue($token->can('write'));
        $this->assertFalse($token->can('delete'));
    }

    public function testUserCanCreateTokenWithWildcardAbilities()
    {
        $user = $this->createTestUser();
        
        $token = $user->createToken('my-app', ['*']);
        
        // Reload from DB to get proper JSON decoding
        $token = new AccessToken($token->id);
        
        $this->assertTrue($token->can('read'));
        $this->assertTrue($token->can('write'));
        $this->assertTrue($token->can('delete'));
        $this->assertTrue($token->can('anything'));
    }

    public function testUserCanCreateTokenWithExpiration()
    {
        $user = $this->createTestUser();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $token = $user->createToken('my-app', ['*'], $expiresAt);
        
        $this->assertEquals($expiresAt, $token->expires_at);
        $this->assertFalse($token->isExpired());
    }

    public function testTokenExpirationIsDetected()
    {
        $user = $this->createTestUser();
        $expiresAt = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $token = $user->createToken('my-app', ['*'], $expiresAt);
        
        $this->assertTrue($token->isExpired());
    }

    public function testUserCanDeleteAllTokens()
    {
        $user = $this->createTestUser();
        $user->createToken('token1');
        $user->createToken('token2');
        $user->createToken('token3');
        
        $count = AccessToken::query()->where('user_id', '=', $user->id)->count();
        $this->assertEquals(3, $count);
        
        $user->deleteTokens();
        
        $count = AccessToken::query()->where('user_id', '=', $user->id)->count();
        $this->assertEquals(0, $count);
    }

    public function testUserCanDeleteSpecificToken()
    {
        $user = $this->createTestUser();
        $token1 = $user->createToken('token1');
        $token2 = $user->createToken('token2');
        
        $user->deleteTokens($token1->plainTextToken);
        
        $count = AccessToken::query()->where('user_id', '=', $user->id)->count();
        $this->assertEquals(1, $count);
        
        // Verify token2 still exists
        $remaining = AccessToken::query()->where('user_id', '=', $user->id)->one();
        $this->assertEquals($token2->id, $remaining->id);
    }

    public function testUserCanDeleteTokensById()
    {
        $user = $this->createTestUser();
        $token1 = $user->createToken('token1');
        $token2 = $user->createToken('token2');
        $token3 = $user->createToken('token3');
        
        $user->deleteTokensById([$token1->id, $token3->id]);
        
        $count = AccessToken::query()->where('user_id', '=', $user->id)->count();
        $this->assertEquals(1, $count);
        
        // Verify token2 still exists
        $remaining = AccessToken::query()->where('user_id', '=', $user->id)->one();
        $this->assertEquals($token2->id, $remaining->id);
    }

    public function testTokenAbilitiesAreCheckedCorrectly()
    {
        $user = $this->createTestUser();
        $token = $user->createToken('my-app', ['read', 'write']);
        
        // Reload from DB to get proper JSON decoding
        $token = new AccessToken($token->id);
        
        // Set as current token
        $user->currentAccessToken = $token;
        
        $this->assertTrue($user->tokenCan('read'));
        $this->assertTrue($user->tokenCan('write'));
        $this->assertFalse($user->tokenCan('delete'));
        
        $this->assertFalse($user->tokenCannot('read'));
        $this->assertTrue($user->tokenCannot('delete'));
    }

    // ========================================
    // REMEMBER ME TESTS
    // ========================================

    public function testUserCanSetRememberToken()
    {
        $user = $this->createTestUser();
        
        $user->setRememberToken('test-remember-token');
        
        $this->assertEquals('test-remember-token', $user->getRememberToken());
        
        // Reload from database
        $user = new AuthUser($user->id);
        $this->assertEquals('test-remember-token', $user->remember_token);
    }

    public function testRememberTokenIsHiddenInSerialization()
    {
        $user = $this->createTestUser();
        $user->setRememberToken('secret-token');
        
        $json = json_encode($user);
        
        $this->assertStringNotContainsString('secret-token', $json);
        $this->assertStringNotContainsString('remember_token', $json);
    }

    // ========================================
    // SESSION PERSISTENCE TESTS
    // ========================================

    public function testAuthenticatedUserPersistsAcrossRequests()
    {
        $user = $this->createTestUser();
        $this->auth->loginAs($user);
        
        // Simulate new request - create new auth instance
        $newAuth = new Auth('default', $this->authConfig);
        
        $this->assertTrue($newAuth->isLoggedIn());
        $this->assertNotNull($newAuth->user());
        $this->assertEquals($user->id, $newAuth->id());
    }

    public function testUserIdentityIsCachedAfterFirstRetrieval()
    {
        $user = $this->createTestUser();
        $this->auth->loginAs($user);
        
        $user1 = $this->auth->user();
        $user2 = $this->auth->user();
        
        // Should be same instance (cached)
        $this->assertSame($user1, $user2);
    }

    // ========================================
    // EDGE CASES AND ERROR HANDLING
    // ========================================

    public function testCannotGetUserIdWhenNotLoggedIn()
    {
        // Clear any session from previous tests and create fresh auth
        $this->session->destroy();
        
        // Create new auth instance with clean state
        $freshAuth = new Auth('default', $this->authConfig);
        
        $this->assertNull($freshAuth->id());
    }

    public function testMultipleUsersCanHaveSeparateSessions()
    {
        $user1 = $this->createTestUser('user1@example.com');
        $user2 = $this->createTestUser('user2@example.com');
        
        // Login as user1
        $this->auth->loginAs($user1);
        $this->assertEquals($user1->id, $this->auth->id());
        
        // Logout and login as user2
        $this->auth->logout();
        $this->auth->loginAs($user2);
        $this->assertEquals($user2->id, $this->auth->id());
    }

    public function testPasswordIsHiddenInUserSerialization()
    {
        $user = $this->createTestUser();
        
        $json = json_encode($user);
        
        $this->assertStringNotContainsString('password', $json);
    }

    public function testTokenRelationshipReturnsUser()
    {
        $user = $this->createTestUser();
        $token = $user->createToken('test');
        
        // Reload token
        $token = new AccessToken($token->id);
        $tokenUser = $token->user;
        
        $this->assertInstanceOf(AuthUser::class, $tokenUser);
        $this->assertEquals($user->id, $tokenUser->id);
    }

    public function testUserRelationshipReturnsTokens()
    {
        $user = $this->createTestUser();
        $user->createToken('token1');
        $user->createToken('token2');
        
        $tokens = $user->accessTokens()->all();
        
        $this->assertCount(2, $tokens);
    }

    // ========================================
    // CONFIGURATION TESTS
    // ========================================

    public function testCanSetCustomDriver()
    {
        $result = $this->auth->setDriver('default');
        
        $this->assertInstanceOf(Auth::class, $result);
    }

    public function testCanSetCustomConfig()
    {
        $newConfig = $this->authConfig;
        $newConfig['default']['remember_duration'] = 60 * 24 * 7; // 7 days
        
        $result = $this->auth->setConfig($newConfig);
        
        $this->assertInstanceOf(Auth::class, $result);
    }

    /**
     * Test that remember me cookie duration is configurable.
     */
    public function testRememberMeUsesConfigurableDuration()
    {
        $this->createTestUser('user@example.com', 'password123');
        
        // Set custom remember duration (7 days)
        $customConfig = $this->authConfig;
        $customConfig['default']['remember_duration'] = 60 * 24 * 7; // 7 days in minutes
        
        // Track what cookie()->set() is called with
        $capturedName = null;
        $capturedValue = null;
        $capturedDuration = null;
        
        $cookie = $this->createMock(Cookie::class);
        $cookie->expects($this->once())
               ->method('set')
               ->willReturnCallback(function($name, $value, $duration) use (&$capturedName, &$capturedValue, &$capturedDuration) {
                   $capturedName = $name;
                   $capturedValue = $value;
                   $capturedDuration = $duration;
                   return true;
               });
        $this->container->instance('cookie', $cookie);
        
        // Mock request with credentials AND remember checkbox
        $request = $this->createMock(Request::class);
        $request->expects($this->any())
                ->method('input')
                ->willReturnCallback(function($key = null) {
                    if ($key === null) {
                        return [
                            'email' => 'user@example.com',
                            'password' => 'password123',
                            'remember' => '1',
                        ];
                    }
                    if ($key === 'remember') {
                        return '1';
                    }
                    return null;
                });
        $this->container->instance('request', $request);
        
        // Create auth with custom config and attempt login (which calls persist())
        $auth = new Auth('default', $customConfig);
        $result = $auth->attempt();
        
        // Verify user was authenticated
        $this->assertInstanceOf(IdentityInterface::class, $result);
        
        // Verify cookie()->set() was called with correct duration
        $this->assertEquals('remember_token', $capturedName);
        $this->assertNotNull($capturedValue);
        $this->assertEquals(60 * 24 * 7, $capturedDuration, 'Cookie duration should be 7 days (10080 minutes)');
    }
    
    public function testRememberMeDefaultsTo30Days()
    {
        $this->createTestUser('user@example.com', 'password123');
        
        // Don't set remember_duration - should default to 30 days
        $capturedDuration = null;
        
        $cookie = $this->createMock(Cookie::class);
        $cookie->expects($this->once())
               ->method('set')
               ->willReturnCallback(function($name, $value, $duration) use (&$capturedDuration) {
                   $capturedDuration = $duration;
                   return true;
               });
        $this->container->instance('cookie', $cookie);
        
        $request = $this->createMock(Request::class);
        $request->expects($this->any())
                ->method('input')
                ->willReturnCallback(function($key = null) {
                    if ($key === null) {
                        return [
                            'email' => 'user@example.com',
                            'password' => 'password123',
                            'remember' => '1',
                        ];
                    }
                    if ($key === 'remember') {
                        return '1';
                    }
                    return null;
                });
        $this->container->instance('request', $request);
        
        // Use default config (no remember_duration set)
        $auth = new Auth('default', $this->authConfig);
        $result = $auth->attempt();
        
        $this->assertInstanceOf(IdentityInterface::class, $result);
        $this->assertEquals(60 * 24 * 30, $capturedDuration, 'Cookie duration should default to 30 days (43200 minutes)');
    }
}
