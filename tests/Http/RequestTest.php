<?php

declare(strict_types=1);

use Lightpack\Exceptions\InvalidHttpMethodException;
use Lightpack\Http\Request;
use Lightpack\Utils\Arr;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    private $uri;
    private $basepath;
    private $fullpath;
    private $path;
    private $query;

    public function setUp(): void
    {
        $basepath = '/lightpack';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $basepath . '/users/23?status=1&level=3';

        $this->uri = $_SERVER['REQUEST_URI'];
        $this->basepath = $basepath;
        $this->fullpath = explode('?', $this->uri)[0];
        $this->path = substr($this->fullpath, strlen($this->basepath));
        $this->query = explode('?', $this->uri)[1] ?? '';
    }

    public function testRequestPath()
    {
        $request = new Request($this->basepath);

        $this->assertSame(
            $this->path,
            $request->path(),
            "Request path should be {$this->path}"
        );
    }

    public function testRequestBasePath()
    {
        $request = new Request($this->basepath);

        $this->assertSame(
            $this->basepath,
            $request->basepath(),
            "Basepath should be {$this->basepath}"
        );
    }

    public function testRequestFullPath()
    {
        $request = new Request($this->basepath);
        
        $this->assertSame(
            $this->fullpath,
            $request->fullpath(),
            "Full request path should be {$this->fullpath}"
        );
    }

    public function testRequestUrl()
    {
        $request = new Request($this->basepath);

        $this->assertSame(
            $this->uri,
            $request->uri(),
            "Request URI should be {$this->uri}"
        );
    }

    public function testRequestQueryString()
    {
        $_GET = ['status' => 1, 'level' => 3];
        $request = new Request($this->basepath);

        $this->assertIsArray($request->input());
        $this->assertEquals(['status' => 1, 'level' => 3], $request->input());
    }

    public function testRequestGetParams()
    {
        $_GET = ['name' => 'Pradeep'];

        $this->assertSame(
            $_GET['name'],
            (new Request)->input('name'),
            "GET[name] should be {$_GET['name']}"
        );

        $this->assertSame(
            'Mumbai',
            (new Request)->input('city', 'Mumbai'),
            'GET[city] should be Mumbai'
        );

        $this->assertSame(
            null,
            (new Request)->input('foo'),
            'GET[foo] should be null'
        );

        $this->assertEquals($_GET, (new Request)->input());
    }

    public function testRequestPostParams()
    {
        $_POST = ['name' => 'Pradeep'];

        $this->assertSame(
            $_POST['name'],
            (new Request)->input('name'),
            "POST[name] should be {$_POST['name']}"
        );

        $this->assertSame(
            'Mumbai',
            (new Request)->input('city', 'Mumbai'),
            'POST[city] should be Mumbai'
        );

        $this->assertSame(
            null,
            (new Request)->input('foo'),
            'POST[foo] should be null'
        );

        $this->assertEquals($_POST, (new Request)->input());
    }

    public function testRequestMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertSame(
            'GET',
            (new Request)->method(),
            'Request method should be GET'
        );
    }

    public function testRequestMethodIsGet()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertTrue(
            (new Request)->isGet(),
            'Request method should be GET'
        );
    }

    public function testRequestMethodIsPost()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertTrue(
            (new Request)->isPost(),
            'Request method should be POST'
        );
    }

    public function testRequestMethodIsPut()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $this->assertTrue(
            (new Request)->isPut(),
            'Request method should be PUT'
        );
    }

    public function testRequestMethodIsPatch()
    {
        $_SERVER['REQUEST_METHOD'] = 'PATCH';

        $this->assertTrue(
            (new Request)->isPatch(),
            'Request method should be PATCH'
        );
    }

    public function testRequestMethodIsDelete()
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';

        $this->assertTrue(
            (new Request)->isDelete(),
            'Request method should be DELETE'
        );
    }

    public function testRequestIsValid()
    {
        $this->expectException(InvalidHttpMethodException::class);
        
        $_SERVER['REQUEST_METHOD'] = 'GETPOST';
        $request = new Request();
    }

    public function testRequestIsAjax()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH']= 'XMLHttpRequest';
        $this->assertTrue((new Request)->isAjax());
    }

    public function testRequestIsJson()
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $this->assertTrue((new Request)->isJson());

        $_SERVER['CONTENT_TYPE'] = 'application/xml';
        $this->assertFalse((new Request)->isJson());
    }

    public function testRequestIsSecure()
    {
        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue((new Request)->isSecure());
    }

    public function testRequestScheme()
    {
        $_SERVER['HTTPS'] = 'off';
        $this->assertEquals('http', (new Request)->scheme());
    }

    public function testRequestHost()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $this->assertEquals('example.com', (new Request)->host());
    }

    public function testRequestProtocol()
    {
        $this->assertEquals('HTTP/1.1', (new Request)->protocol());
    }

    public function testRequestSegments()
    {
        $request = new Request($this->basepath);

        $this->assertEquals(['users', 23], $request->segments());
        $this->assertEquals('users', $request->segments(0));
        $this->assertEquals(23, $request->segments(1));
    }

    public function testRequestInputWithDotNotation()
    {
        // Test with GET parameters
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [
            'user' => [
                'profile' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ],
                'settings' => [
                    'theme' => 'dark'
                ]
            ]
        ];

        $request = new Request();
        
        // Test nested access
        $this->assertEquals('John Doe', $request->input('user.profile.name'));
        $this->assertEquals('dark', $request->input('user.settings.theme'));
        
        // Test default value with non-existent key
        $this->assertEquals('light', $request->input('user.settings.color', 'light'));
        
        // Test null for non-existent nested key
        $this->assertNull($request->input('user.profile.age'));

        // Test with POST parameters
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'data' => [
                'items' => [
                    ['id' => 1, 'name' => 'Item 1'],
                    ['id' => 2, 'name' => 'Item 2']
                ]
            ]
        ];

        $request = new Request(); // Create new instance for POST test
        
        // Test array access with dot notation
        $this->assertEquals('Item 1', $request->input('data.items.0.name'));
        $this->assertEquals(2, $request->input('data.items.1.id'));
    }

    public function testRequestInputWithDotNotationAndJson()
    {
        // Create a mock for the Request class
        $request = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([null])
            ->onlyMethods(['getRawBody'])
            ->getMock();

        // Set up the environment for JSON request
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $jsonData = json_encode([
            'user' => [
                'profile' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com'
                ]
            ]
        ]);

        // Configure the mock
        $request->expects($this->any())
            ->method('getRawBody')
            ->willReturn($jsonData);

        // Test JSON data access with dot notation
        $this->assertEquals('Jane Doe', $request->input('user.profile.name'));
        $this->assertEquals('jane@example.com', $request->input('user.profile.email'));
        $this->assertNull($request->input('user.profile.phone'));
        $this->assertEquals('default', $request->input('user.settings.theme', 'default'));
    }

    public function testRequestInputWithWildcardAccess()
    {
        // Test with POST parameters
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'users' => [
                ['id' => 1, 'name' => 'John', 'role' => 'admin'],
                ['id' => 2, 'name' => 'Jane', 'role' => 'user'],
                ['id' => 3, 'name' => 'Bob', 'role' => 'user']
            ],
            'settings' => [
                'notifications' => [
                    ['type' => 'email', 'enabled' => true],
                    ['type' => 'sms', 'enabled' => false],
                    ['type' => 'push', 'enabled' => true]
                ]
            ],
            'departments' => [
                'tech' => [
                    'teams' => [
                        ['name' => 'Frontend', 'members' => [['name' => 'Alice'], ['name' => ['Bob', 'Meghan']]]],
                        ['name' => 'Backend', 'members' => [['name' => 'Charlie'], ['name' => 'Dave']]]
                    ]
                ],
                'design' => [
                    'teams' => [
                        ['name' => 'UI', 'members' => [['name' => 'Eve'], ['name' => 'Frank']]],
                        ['name' => 'UX', 'members' => [['name' => 'Grace'], ['name' => 'Henry']]]
                    ]
                ]
            ]
        ];

        $request = new Request();
        
        // Test wildcard access to get all user names
        $this->assertEquals(
            ['John', 'Jane', 'Bob'],
            $request->input('users.*.name')
        );

        // Test wildcard access to get all user roles
        $this->assertEquals(
            ['admin', 'user', 'user'],
            $request->input('users.*.role')
        );

        // Test wildcard with nested arrays
        $this->assertEquals(
            [true, false, true],
            $request->input('settings.notifications.*.enabled')
        );

        // Test wildcard with non-existent path
        $this->assertNull($request->input('users.*.address'));

        // Test wildcard with default value
        $this->assertEquals(
            'N/A',
            $request->input('users.*.phone', 'N/A')
        );
    }
}
