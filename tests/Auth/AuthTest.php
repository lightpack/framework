<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Auth\Auth;
use Lightpack\Auth\Identity;
use Lightpack\Auth\Models\AccessToken;
use Lightpack\Session\Session;
use Lightpack\Http\Request;
use Lightpack\Container\Container;
use Lightpack\Http\Cookie;
use Lightpack\Http\Redirect;
use Lightpack\Utils\Url;
use Lightpack\Session\DriverInterface;
use Lightpack\Database\Lucid\Builder;
use Lightpack\Database\DB;
use PDO;
use PDOStatement;

class AuthTest extends TestCase
{
    private Auth $auth;
    private TestUser $user;
    private Session $session;
    private Request $request;
    private Cookie $cookie;
    private Redirect $redirect;
    private Url $url;
    private DriverInterface $sessionDriver;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = new TestUser();
        
        // Setup session driver mock
        $this->sessionDriver = $this->createMock(DriverInterface::class);
        Container::getInstance()->instance(DriverInterface::class, $this->sessionDriver);
        
        // Setup session mock
        $this->session = new Session($this->sessionDriver);
        Container::getInstance()->instance('session', $this->session);
        
        // Setup request mock
        $this->request = $this->createMock(Request::class);
        Container::getInstance()->instance('request', $this->request);
        
        // Setup cookie mock
        $this->cookie = $this->createMock(Cookie::class);
        Container::getInstance()->instance('cookie', $this->cookie);
        
        // Setup redirect mock
        $this->url = $this->createMock(Url::class);
        $this->redirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['to'])
            ->getMock();
        $this->redirect->method('to')
            ->willReturnSelf();
        Container::getInstance()->instance('redirect', $this->redirect);
        Container::getInstance()->instance('url', $this->url);

        // Setup database mocks
        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement->method('execute')->willReturn(true);
        $pdoStatement->method('fetch')->willReturn([
            'id' => 1,
            'token' => hash('sha256', 'test-token'),
            'user_id' => 1,
            'name' => 'Test Token',
            'abilities' => '["*"]',
            'last_used_at' => null,
            'expires_at' => null,
        ]);
        
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($pdoStatement);
        
        $db = $this->getMockBuilder(DB::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'query'])
            ->getMock();
        $db->method('getConnection')->willReturn($pdo);
        $db->method('query')->willReturn($pdoStatement);
        Container::getInstance()->instance('db', $db);
        
        // Setup auth config
        $config = [
            'default' => [
                'identifier' => TestIdentifier::class,
                'model' => TestUser::class,
                'fields.identity' => 'email',
                'fields.password' => 'password',
                'fields.api_token' => 'api_token',
                'fields.remember_token' => 'remember_token',
                'fields.last_login_at' => 'last_login_at',
                'login.url' => '/login',
                'logout.url' => '/logout',
                'home.url' => '/home',
                'login.redirect' => '/dashboard',
                'logout.redirect' => '/login',
            ],
        ];
        
        $this->auth = new Auth('default', $config);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Container::getInstance()->reset();
    }

    public function testAuthRecognizesGuestUser()
    {
        $this->sessionDriver->method('get')->with('_logged_in', false)->willReturn(false);        

        $this->assertTrue($this->auth->isGuest());
        $this->assertNull($this->auth->user());
    }

    public function testCanLoginAsUser()
    {
        $this->sessionDriver->expects($this->once())->method('regenerate');
            
        $this->sessionDriver->expects($this->exactly(2))->method('set')->withConsecutive(
            ['_logged_in', true],
            ['_auth_id', 1]
        );
            
        $this->auth->loginAs($this->user);
        
        $this->sessionDriver->method('get')
            ->willReturnMap([
                ['_logged_in', false, true],
            ]);
            
        $this->assertTrue($this->auth->isLoggedIn());
        $this->assertFalse($this->auth->isGuest());
        
        $user = $this->auth->user();
        $this->assertInstanceOf(Identity::class, $user);
        $this->assertEquals(1, $user->getId());
        $this->assertEquals('test@example.com', $user->getEmail());
    }

    public function testCanLogoutUser()
    {
        $values = [
            '_logged_in' => true,
            '_auth_id' => 1,
        ];
        
        // Setup session get expectations for login
        $this->sessionDriver->method('get')
            ->will($this->returnCallback(function($key = null, $default = null) use (&$values) {
                if ($key === null) {
                    return $values;
                }
                
                if (isset($values[$key])) {
                    return $values[$key];
                }
                
                return $default;
            }));
            
        // Setup session expectations for login
        $this->sessionDriver->expects($this->once())
            ->method('regenerate');
            
        $this->sessionDriver->expects($this->exactly(3))
            ->method('set')
            ->withConsecutive(
                ['_logged_in', true],
                ['_auth_id', 1],
                ['_intended_url', '']
            );
        
        // Login user
        $this->auth->loginAs($this->user);
        $this->assertTrue($this->auth->isLoggedIn());
        
        // Setup session expectations for logout
        $this->sessionDriver->expects($this->once())
            ->method('destroy')
            ->willReturnCallback(function() use (&$values) {
                $values['_logged_in'] = false;
                $values['_auth_id'] = null;
            });
        
        // Logout and verify
        $this->auth->logout();
        $this->assertFalse($this->auth->isLoggedIn());
        $this->assertTrue($this->auth->isGuest());
        $this->assertNull($this->auth->user());
    }

    public function testCanAuthenticateViaToken()
    {
        // Mock request to return bearer token
        $this->request->expects($this->once())
            ->method('bearerToken')
            ->willReturn('test-token');
            
        $user = $this->auth->viaToken();
        
        $this->assertInstanceOf(Identity::class, $user);
        $this->assertEquals(1, $user->getId());
    }

    public function testCannotAuthenticateWithInvalidToken()
    {
        // Mock request to return invalid token
        $this->request->expects($this->once())
            ->method('bearerToken')
            ->willReturn('invalid-token');
            
        $user = $this->auth->viaToken();
        
        $this->assertNull($user);
    }
}

// Test classes
class TestUser implements Identity 
{
    private $id = 1;
    private $email = 'test@example.com';
    private $password = 'hashed_password';
    private $authToken;
    private $rememberToken;
    
    public function getId(): mixed { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRememberToken(): ?string { return $this->rememberToken; }
    public function setRememberToken(string $token): void { $this->rememberToken = $token; }
    public function accessTokens() { return []; }
    public function createToken(string $name, array $abilities = ['*'], ?string $expiresAt = null): AccessToken 
    {
        return new AccessToken();
    }
    public function deleteTokens(?string $tokenId = ''): void {}
}

class TestIdentifier implements Lightpack\Auth\Identifier
{
    public function __construct(private TestUser $model) {}
    
    public function findById($id): ?Identity
    {
        return $this->model;
    }
    
    public function findByCredentials(array $credentials): ?Identity
    {
        return $this->model;
    }

    public function findByRememberToken($id, string $token): ?Identity
    {
        return $this->model;
    }
    
    public function updateLogin($id, array $fields): void {}
}
