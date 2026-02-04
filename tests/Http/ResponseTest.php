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

    /**
     * @runInSeparateProcess
     */
    public function testStreamCsvMethod()
    {
        $output = null;
        $this->response->streamCsv(function() use (&$output) {
            $output = "Name,Email\nJohn,john@example.com\n";
            echo $output;
        }, 'users.csv');

        // Verify headers
        $headers = $this->response->getHeaders();
        $this->assertEquals('text/csv', $headers['Content-Type']);
        $this->assertEquals('attachment; filename="users.csv"', $headers['Content-Disposition']);

        // Verify content
        ob_start();
        $this->response->send();
        $result = ob_get_clean();

        $this->assertEquals($output, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDownloadStreamMethod()
    {
        // Create a temporary test file
        $tempFile = sys_get_temp_dir() . '/test_download_' . uniqid() . '.txt';
        $testContent = str_repeat('Test content line ' . PHP_EOL, 100); // Create some content
        file_put_contents($tempFile, $testContent);
        
        try {
            // Test the downloadStream method
            $this->response->downloadStream($tempFile, 'test-download.txt');
            
            // Verify headers
            $headers = $this->response->getHeaders();
            $this->assertEquals('text/plain', $headers['Content-Type']);
            $this->assertEquals('attachment; filename="test-download.txt"', $headers['Content-Disposition']);
            $this->assertEquals('binary', $headers['Content-Transfer-Encoding']);
            $this->assertEquals(filesize($tempFile), $headers['Content-Length']);
            
            // Verify streaming callback was set (we can't easily test the actual streaming)
            $this->assertNotNull($this->response->getStreamCallback());
        } finally {
            // Clean up the temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testDownloadStreamMethodWithCustomChunkSize()
    {
        // Create a temporary test file
        $tempFile = sys_get_temp_dir() . '/test_download_' . uniqid() . '.txt';
        $testContent = str_repeat('Test content line ' . PHP_EOL, 100);
        file_put_contents($tempFile, $testContent);
        
        try {
            // Test with custom chunk size
            $this->response->downloadStream($tempFile, 'test-download.txt', [], 512);
            
            // We need to add a getter for the stream callback to properly test this
            // For now, we just verify the method doesn't throw exceptions
            $this->assertTrue(true);
        } finally {
            // Clean up
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testDownloadStreamMethodWithCustomHeaders()
    {
        // Create a temporary test file
        $tempFile = sys_get_temp_dir() . '/test_download_' . uniqid() . '.txt';
        file_put_contents($tempFile, 'Test content');
        
        try {
            // Test with custom headers
            $customHeaders = [
                'Cache-Control' => 'no-cache',
                'X-Custom-Header' => 'Custom Value'
            ];
            
            $this->response->downloadStream($tempFile, 'test-download.txt', $customHeaders);
            
            // Verify headers
            $headers = $this->response->getHeaders();
            $this->assertEquals('no-cache', $headers['Cache-Control']);
            $this->assertEquals('Custom Value', $headers['X-Custom-Header']);
        } finally {
            // Clean up
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
    
    /**
     * @runInSeparateProcess
     * @expectedException \RuntimeException
     */
    public function testDownloadStreamMethodWithNonExistentFile()
    {
        $this->expectException(\RuntimeException::class);
        $this->response->downloadStream('/path/to/non-existent-file.txt');
    }

    /**
     * @runInSeparateProcess
     */
    public function testFileStreamMethod()
    {
        // Create a temporary test file
        $tempFile = sys_get_temp_dir() . '/test_file_' . uniqid() . '.txt';
        $testContent = str_repeat('Test content line ' . PHP_EOL, 100);
        file_put_contents($tempFile, $testContent);
        
        try {
            // Test the fileStream method
            $this->response->fileStream($tempFile, 'test-view.txt');
            
            // Verify headers
            $headers = $this->response->getHeaders();
            $this->assertEquals('text/plain', $headers['Content-Type']);
            $this->assertEquals('inline; filename=test-view.txt', $headers['Content-Disposition']);
            $this->assertEquals('binary', $headers['Content-Transfer-Encoding']);
            $this->assertEquals(filesize($tempFile), $headers['Content-Length']);
            
            // Verify streaming callback was set
            $this->assertNotNull($this->response->getStreamCallback());
        } finally {
            // Clean up
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testFileStreamMethodWithCustomHeaders()
    {
        // Create a temporary test file
        $tempFile = sys_get_temp_dir() . '/test_file_' . uniqid() . '.txt';
        file_put_contents($tempFile, 'Test content');
        
        try {
            // Test with custom headers
            $customHeaders = [
                'X-Custom-Header' => 'Custom Value'
            ];
            
            $this->response->fileStream($tempFile, 'test-view.txt', $customHeaders);
            
            // Verify headers
            $headers = $this->response->getHeaders();
            $this->assertEquals('inline; filename=test-view.txt', $headers['Content-Disposition']);
            $this->assertEquals('Custom Value', $headers['X-Custom-Header']);
        } finally {
            // Clean up
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testSseMethod()
    {
        $this->response->sse(function($stream) {
            // Callback will be executed later
        });
        
        // Verify SSE headers are set correctly
        $headers = $this->response->getHeaders();
        
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('Connection', $headers);
        $this->assertArrayHasKey('X-Accel-Buffering', $headers);
        
        $this->assertEquals('text/event-stream', $headers['Content-Type']);
        $this->assertEquals('no-cache', $headers['Cache-Control']);
        $this->assertEquals('keep-alive', $headers['Connection']);
        $this->assertEquals('no', $headers['X-Accel-Buffering']);
        
        // Verify streaming callback was set
        $this->assertNotNull($this->response->getStreamCallback());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSseMethodStreamObjectHasPushMethod()
    {
        $streamObject = null;
        
        $this->response->sse(function($stream) use (&$streamObject) {
            $streamObject = $stream;
        });
        
        // Execute the stream callback to get the stream object
        ob_start();
        $this->response->send();
        ob_end_clean();
        
        // Verify stream object has push method
        $this->assertNotNull($streamObject);
        $this->assertTrue(method_exists($streamObject, 'push'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSseMethodCallbackExecution()
    {
        $callbackExecuted = false;
        $streamObjectReceived = null;
        
        $this->response->sse(function($stream) use (&$callbackExecuted, &$streamObjectReceived) {
            $callbackExecuted = true;
            $streamObjectReceived = $stream;
            
            // Test that push method exists and can be called
            $stream->push('test', ['message' => 'Hello']);
            $stream->push('done');
        });
        
        // Execute the stream by sending response
        ob_start();
        $this->response->send();
        ob_end_clean();
        
        // Verify callback was executed
        $this->assertTrue($callbackExecuted);
        $this->assertNotNull($streamObjectReceived);
        $this->assertTrue(method_exists($streamObjectReceived, 'push'));
    }
}