<?php

declare(strict_types=1);

use Lightpack\Utils\Url;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    private $response;

    public function setUp(): void
    {
        $this->response = new \Lightpack\Http\Response(new Url);
    }
    
    public function testResponseSetStatusMethod()
    {
        $this->assertSame($this->response,  $this->response->setStatus(200));
    }

    public function testResponseGetStatusMethod()
    {
        $this->assertEquals(200, $this->response->getStatus());
        $this->response->setStatus(302);
        $this->assertEquals(302, $this->response->getStatus());
    }
    
    public function testResponseSetMessageMethod()
    {
        $this->assertSame($this->response,  $this->response->setMessage('Found'));
    }

    public function testResponseGetMessageMethod()
    {
        $this->response->setMessage('Found');
        $this->assertEquals('Found', $this->response->getMessage());
    }
    
    public function testResponseSetTypeMethod()
    {
        $this->assertSame($this->response,  $this->response->setType('text/xml'));
    }

    public function testResponseGetTypeMethod()
    {
        $this->response->setType('text/xml');
        $this->assertEquals('text/xml', $this->response->getType());
    }

    public function testResponseSetHeaderMethod()
    {
        $this->assertSame($this->response,  $this->response->setHeader('Content-Type', 'text/html'));
    }

    public function testResponseSetHeadersMethod()
    {
        $this->assertSame($this->response,  $this->response->setHeaders(['Content-Type' => 'text/html']));
    }

    public function testResponseGetHeadersMethod()
    {
        $this->response->setHeader('Content-Type', 'text/html');
        $this->response->setHeaders([
            'Server' => 'Apache',
            'Connection' => 'Keep-Alive',
        ]);
        $this->assertEquals(
            [
                'Content-Type' => 'text/html',
                'Server' => 'Apache',
                'Connection' => 'Keep-Alive',
            ],
            $this->response->getHeaders()
        );
    }

    public function testResponseSetBodyMethod()
    {
        $this->assertSame($this->response,  $this->response->setBody('foo=23&bar=32'));
    }

    public function testResponseGetBodyMethod()
    {
        $this->response->setBody('foo=23&bar=32');
        $this->assertEquals('foo=23&bar=32', $this->response->getBody());
    }

    /**
     * @runInSeparateProcess
     * @todo This method throws fatal error.
     */
    public function _testResponseSend()
    {
        $this->response->setHeader('Server', 'Apache');
        $this->response->setBody('foo=23&bar=32');
        ob_start();
        $this->response->send();
        $result = ob_get_clean();
        $this->assertEquals('foo=23&bar=32', $result);
    }

    public function testResponseJsonMethod()
    {
        $data = ['message' => 'hello'];
        $this->response->json($data);
        $this->assertEquals('application/json', $this->response->getType());
        $this->assertEquals(json_encode($data), $this->response->getBody());
    }

    public function testResponseXmlMethod()
    {
        $data = 'xml-string';
        $this->response->xml($data);
        $this->assertEquals('text/xml', $this->response->getType());
        $this->assertEquals($data, $this->response->getBody());
    }

    public function testResponseTextMethod()
    {
        $data = 'text-string';
        $this->response->text($data);
        $this->assertEquals('text/plain', $this->response->getType());
        $this->assertEquals($data, $this->response->getBody());
    }
}