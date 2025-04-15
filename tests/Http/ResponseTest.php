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
        $this->response->setTestMode(true);
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

    public function testCacheMethodWithDefaultOptions()
    {
        $this->response->cache(3600);
        $headers = $this->response->getHeaders();

        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('Expires', $headers);
        $this->assertArrayHasKey('Pragma', $headers);

        $this->assertStringContainsString('max-age=3600', $headers['Cache-Control']);
        $this->assertStringContainsString('public', $headers['Cache-Control']);
    }

    public function testCacheMethodWithPrivateOption()
    {
        $this->response->cache(3600, ['public' => false]);
        $headers = $this->response->getHeaders();

        $this->assertStringContainsString('private', $headers['Cache-Control']);
        $this->assertStringNotContainsString('public', $headers['Cache-Control']);
    }

    public function testCacheMethodWithImmutableOption()
    {
        $this->response->cache(3600, ['immutable' => true]);
        $headers = $this->response->getHeaders();

        $this->assertStringContainsString('immutable', $headers['Cache-Control']);
    }

    public function testNoCacheMethod()
    {
        $this->response->noCache();
        $headers = $this->response->getHeaders();

        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('Expires', $headers);
        $this->assertArrayHasKey('Pragma', $headers);

        $this->assertStringContainsString('no-store', $headers['Cache-Control']);
        $this->assertStringContainsString('no-cache', $headers['Cache-Control']);
        $this->assertEquals('no-cache', $headers['Pragma']);
    }

    public function testSetLastModifiedMethod()
    {
        // Test with timestamp
        $time = time();
        $this->response->setLastModified($time);
        $this->assertEquals(gmdate('D, d M Y H:i:s', $time) . ' GMT', $this->response->getHeader('Last-Modified'));

        // Test with DateTime
        $date = new DateTime();
        $this->response->setLastModified($date);
        $this->assertEquals(gmdate('D, d M Y H:i:s', $date->getTimestamp()) . ' GMT', $this->response->getHeader('Last-Modified'));

        // Test with date string
        $dateStr = '2024-02-13 12:00:00';
        $this->response->setLastModified($dateStr);
        $this->assertEquals(gmdate('D, d M Y H:i:s', strtotime($dateStr)) . ' GMT', $this->response->getHeader('Last-Modified'));
    }

    public function testSecureMethod()
    {
        $this->response->secure();
        $headers = $this->response->getHeaders();

        $this->assertArrayHasKey('X-Content-Type-Options', $headers);
        $this->assertArrayHasKey('X-Frame-Options', $headers);
        $this->assertArrayHasKey('X-XSS-Protection', $headers);
        $this->assertArrayHasKey('Referrer-Policy', $headers);

        $this->assertEquals('nosniff', $headers['X-Content-Type-Options']);
        $this->assertEquals('SAMEORIGIN', $headers['X-Frame-Options']);
        $this->assertEquals('1; mode=block', $headers['X-XSS-Protection']);
        $this->assertEquals('strict-origin-when-cross-origin', $headers['Referrer-Policy']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStreamMethod()
    {
        $output = null;
        $this->response->stream(function() use (&$output) {
            $output = "Hello Stream";
            echo $output;
        });

        // Test output by sending response
        ob_start();
        $this->response->send();
        $result = ob_get_clean();
        
        $this->assertEquals($output, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStreamMethodWithHeaders()
    {
        $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->stream(function() {
                echo "data,more data";
            });

        // Verify headers are set correctly
        $headers = $this->response->getHeaders();
        $this->assertEquals('text/csv', $headers['Content-Type']);
        
        // Test output by sending response
        ob_start();
        $this->response->send();
        $result = ob_get_clean();
        
        $this->assertEquals("data,more data", $result);
    }
}