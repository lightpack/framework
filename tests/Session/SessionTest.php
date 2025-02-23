<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Session\Session;
use Lightpack\Session\DriverInterface;

class SessionTest extends TestCase
{
    private $driver;
    private $session;

    protected function setUp(): void
    {
        $this->driver = $this->createMock(DriverInterface::class);
        $this->session = new Session($this->driver);
    }

    public function testSetMethodCallsDriverSet()
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->driver->expects($this->once())
            ->method('set')
            ->with($key, $value);

        $this->session->set($key, $value);
    }

    public function testGetMethodCallsDriverGet()
    {
        $key = 'test_key';
        $value = 'test_value';
        $default = 'default_value';

        $this->driver->expects($this->once())
            ->method('get')
            ->with($key, $default)
            ->willReturn($value);

        $result = $this->session->get($key, $default);
        $this->assertEquals($value, $result);
    }

    public function testDeleteMethodCallsDriverDelete()
    {
        $key = 'test_key';

        $this->driver->expects($this->once())
            ->method('delete')
            ->with($key);

        $this->session->delete($key);
    }

    public function testHasMethodReturnsTrueWhenKeyExists()
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->driver->method('get')
            ->with($key)
            ->willReturn($value);

        $this->assertTrue($this->session->has($key));
    }

    public function testHasMethodReturnsFalseWhenKeyDoesNotExist()
    {
        $key = 'test_key';

        $this->driver->method('get')
            ->with($key)
            ->willReturn(null);

        $this->assertFalse($this->session->has($key));
    }

    public function testTokenGenerationAndStorage()
    {
        $this->driver->expects($this->once())
            ->method('set')
            ->with('_token', $this->callback(function($value) {
                return strlen($value) === 16 && ctype_xdigit($value);
            }));

        $token = $this->session->token();
        $this->assertEquals(16, strlen($token));
        $this->assertTrue(ctype_xdigit($token));
    }

    public function testVerifyTokenReturnsFalseWhenSessionNotStarted()
    {
        $this->driver->method('started')
            ->willReturn(false);

        $this->assertFalse($this->session->verifyToken());
    }

    public function testVerifyTokenReturnsFalseWhenTokenMismatch()
    {
        $_POST['_token'] = 'wrong_token';

        $this->driver->method('started')
            ->willReturn(true);

        $this->driver->method('get')
            ->with('_token')
            ->willReturn('correct_token');

        $this->assertFalse($this->session->verifyToken());
    }

    public function testVerifyTokenReturnsTrueWhenTokenMatches()
    {
        $token = 'matching_token';
        $_POST['_token'] = $token;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->driver->method('started')
            ->willReturn(true);

        $this->driver->method('get')
            ->with('_token')
            ->willReturn($token);

        $this->assertTrue($this->session->verifyToken());
    }

    public function testFlashSetValue()
    {
        $key = 'flash_key';
        $value = 'flash_value';

        $this->driver->expects($this->once())
            ->method('set')
            ->with($key, $value);

        $this->session->flash($key, $value);
    }

    public function testFlashGetValue()
    {
        $key = 'flash_key';
        $value = 'flash_value';

        $this->driver->method('get')
            ->with($key)
            ->willReturn($value);

        $this->driver->expects($this->once())
            ->method('delete')
            ->with($key);

        $result = $this->session->flash($key);
        $this->assertEquals($value, $result);
    }

    public function testRegenerateCallsDriverRegenerate()
    {
        $this->driver->expects($this->once())
            ->method('regenerate')
            ->willReturn(true);

        $result = $this->session->regenerate();
        $this->assertTrue($result);
    }

    public function testVerifyAgentCallsDriverVerifyAgent()
    {
        $this->driver->expects($this->once())
            ->method('verifyAgent')
            ->willReturn(true);

        $result = $this->session->verifyAgent();
        $this->assertTrue($result);
    }

    public function testHasInvalidTokenReturnsOppositeOfVerifyToken()
    {
        $token = 'valid_token';
        $_POST['_token'] = $token;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->driver->method('started')
            ->willReturn(true);

        $this->driver->method('get')
            ->with('_token')
            ->willReturn($token);

        $this->assertFalse($this->session->hasInvalidToken());
    }

    public function testHasInvalidAgentReturnsOppositeOfVerifyAgent()
    {
        $this->driver->method('verifyAgent')
            ->willReturn(true);

        $this->assertFalse($this->session->hasInvalidAgent());
    }

    public function testDestroyCallsDriverDestroy()
    {
        $this->driver->expects($this->once())
            ->method('destroy');

        $this->session->destroy();
    }

    public function testSetWithDotNotationStoresNestedValueInSession()
    {
        $key = 'user.profile.name';
        $value = 'John Doe';

        $this->driver->method('get')
            ->willReturn([]);

        $this->driver->expects($this->once())
            ->method('set')
            ->with('user', ['profile' => ['name' => $value]]);

        $this->session->set($key, $value);
    }

    public function testGetWithDotNotationRetrievesNestedValue()
    {
        $this->driver->method('get')
            ->willReturnCallback(function($key) {
                if($key === 'user') {
                    return ['profile' => ['name' => 'John Doe']];
                }
                return null;
            });

        $value = $this->session->get('user.profile.name');
        $this->assertEquals('John Doe', $value);
    }

    public function testSetWithDotNotationPreservesExistingValues()
    {
        $this->driver->method('get')
            ->willReturnCallback(function($key) {
                if($key === 'user') {
                    return [
                        'profile' => [
                            'name' => 'John Doe',
                            'age' => 30,
                        ],
                    ];
                }
                return null;
            });

        $this->driver->expects($this->once())
            ->method('set')
            ->with('user', [
                'profile' => [
                    'name' => 'John Doe',
                    'age' => 30,
                    'email' => 'john@example.com',
                ],
            ]);

        $this->session->set('user.profile.email', 'john@example.com');
    }

    public function testGetWithDotNotationReturnsDefaultForMissingKey()
    {
        $this->driver->method('get')
            ->willReturnCallback(function($key) {
                if($key === 'user') {
                    return ['profile' => ['name' => 'John Doe']];
                }
                return null;
            });

        $value = $this->session->get('user.profile.email', 'default@example.com');
        $this->assertEquals('default@example.com', $value);
    }

    public function testSetWithDotNotationOverwritesScalarWithArray()
    {
        $this->driver->method('get')
            ->willReturnCallback(function($key) {
                if($key === 'user') {
                    return 'scalar value';
                }
                return null;
            });

        $this->driver->expects($this->once())
            ->method('set')
            ->with('user', [
                'profile' => [
                    'name' => 'John Doe',
                ],
            ]);

        $this->session->set('user.profile.name', 'John Doe');
    }

    public function testDeleteWithDotNotation()
    {
        $data = [
            'user' => [
                'profile' => [
                    'email' => 'john@example.com',
                    'name' => 'John Doe'
                ]
            ]
        ];

        $this->driver->expects($this->once())
            ->method('get')
            ->with('user')
            ->willReturn($data['user']);

        $this->driver->expects($this->once())
            ->method('set')
            ->with('user', [
                'profile' => [
                    'name' => 'John Doe'
                ]
            ]);

        $this->session->delete('user.profile.email');
    }

    public function testDeleteWithNonExistentDotNotationKey()
    {
        $data = [
            'user' => [
                'profile' => [
                    'name' => 'John Doe'
                ]
            ]
        ];

        $this->driver->expects($this->once())
            ->method('get')
            ->with('user')
            ->willReturn($data['user']);

        $this->driver->expects($this->once())
            ->method('set')
            ->with('user', [
                'profile' => [
                    'name' => 'John Doe'
                ]
            ]);

        $this->session->delete('user.profile.email');
    }

    public function testDeleteWithScalarValue()
    {
        $this->driver->expects($this->never())
            ->method('get');

        $this->driver->expects($this->once())
            ->method('delete')
            ->with('name');

        $this->session->delete('name');
    }
}
