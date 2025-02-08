<?php

declare(strict_types=1);

use Lightpack\Exceptions\InvalidHttpMethodException;
use Lightpack\Http\Request;
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

        $this->assertIsArray($request->query());
        $this->assertEquals(['status' => 1, 'level' => 3], $request->query());
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
}
