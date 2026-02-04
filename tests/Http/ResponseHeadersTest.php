<?php

namespace Lightpack\Tests\Http;

use Lightpack\Http\Http;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 * @group external
 */
class ResponseHeadersTest extends TestCase
{
    private string $baseUrl = 'https://httpbin.org';

    public function testCanGetAllResponseHeaders()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/get');
        
        $headers = $response->responseHeaders();
        
        $this->assertIsArray($headers);
        $this->assertNotEmpty($headers);
        $this->assertArrayHasKey('content-type', $headers);
    }

    public function testCanGetSpecificResponseHeader()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/get');
        
        $contentType = $response->responseHeader('Content-Type');
        
        $this->assertNotNull($contentType);
        $this->assertStringContainsString('application/json', $contentType);
    }

    public function testHeaderNamesAreCaseInsensitive()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/get');
        
        $header1 = $response->responseHeader('Content-Type');
        $header2 = $response->responseHeader('content-type');
        $header3 = $response->responseHeader('CONTENT-TYPE');
        
        $this->assertSame($header1, $header2);
        $this->assertSame($header2, $header3);
    }

    public function testReturnsNullForNonExistentHeader()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/get');
        
        $header = $response->responseHeader('X-Non-Existent-Header');
        
        $this->assertNull($header);
    }

    public function testCanAccessServerHeader()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/get');
        
        $server = $response->responseHeader('Server');
        
        $this->assertNotNull($server);
        $this->assertIsString($server);
    }
}
