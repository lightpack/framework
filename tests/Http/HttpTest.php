<?php

namespace Lightpack\Tests\Http;

use Lightpack\Http\Http;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 * @group external
 * 
 * These tests require network access and depend on httpbin.org.
 * They are skipped by default. Run with: phpunit --group integration
 */
class HttpTest extends TestCase
{
    private string $baseUrl = 'https://httpbin.org';

    public function testCanMakeGetRequest()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/get');
        
        $this->assertEquals(200, $response->status());
        $data = $response->json();
        $this->assertIsArray($data);
    }

    public function testCanMakeGetRequestWithQueryParams()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/get', [
            'name' => 'john',
            'age' => 30
        ]);
        
        $this->assertEquals(200, $response->status());
        $data = $response->json();
        $this->assertEquals('john', $data['args']['name']);
        $this->assertEquals('30', $data['args']['age']);
    }

    public function testCanMakePostRequest()
    {
        $http = new Http();
        $response = $http->post($this->baseUrl . '/post', [
            'name' => 'john',
            'email' => 'john@example.com'
        ]);
        
        $this->assertFalse($response->failed());
        $data = $response->json();
        $this->assertEquals('application/json', $data['headers']['Content-Type']);
        $this->assertEquals('john', $data['json']['name']);
    }

    public function testCanMakePutRequest()
    {
        $http = new Http();
        $response = $http->put($this->baseUrl . '/put', [
            'name' => 'john',
            'email' => 'john@example.com'
        ]);
        
        $this->assertFalse($response->failed());
        $data = $response->json();
        $this->assertEquals('application/json', $data['headers']['Content-Type']);
        $this->assertEquals('john', $data['json']['name']);
    }

    public function testCanMakeDeleteRequest()
    {
        $http = new Http();
        $response = $http->delete($this->baseUrl . '/delete');
        
        $this->assertEquals(200, $response->status());
    }

    public function testCanSetCustomHeaders()
    {
        $http = new Http();
        $response = $http
            ->headers([
                'X-Custom' => 'test',
                'Accept' => 'application/json'
            ])
            ->get($this->baseUrl . '/headers');
        
        $data = $response->json();
        $this->assertEquals('test', $data['headers']['X-Custom']);
    }

    public function testCanSetBearerToken()
    {
        $http = new Http();
        $response = $http
            ->token('xyz123')
            ->get($this->baseUrl . '/headers');
        
        $data = $response->json();
        $this->assertEquals('Bearer xyz123', $data['headers']['Authorization']);
    }

    public function testCanUploadFile()
    {
        $http = new Http();
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        $response = $http
            ->files(['file' => $tempFile])
            ->post($this->baseUrl . '/post');

        unlink($tempFile);
        
        $this->assertEquals(200, $response->status());
        $data = $response->json();
        $this->assertArrayHasKey('files', $data);
    }

    public function testCanSetTimeout()
    {
        $http = new Http();
        $response = $http
            ->timeout(5)
            ->get($this->baseUrl . '/get');
        
        $this->assertEquals(200, $response->status());
    }

    public function testCanMakeInsecureRequest()
    {
        $http = new Http();
        $response = $http
            ->insecure()
            ->get($this->baseUrl . '/get');
        
        $this->assertEquals(200, $response->status());
    }

    public function testCanGetResponseAsText()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/get');
        
        $this->assertIsString($response->body());
        $this->assertJson($response->body());
    }

    public function testCanMakePatchRequest()
    {
        $http = new Http();
        $response = $http->patch($this->baseUrl . '/patch', [
            'name' => 'john',
            'active' => true
        ]);
        
        $this->assertFalse($response->failed());
        $data = $response->json();
        $this->assertEquals('application/json', $data['headers']['Content-Type']);
        $this->assertEquals('john', $data['json']['name']);
        $this->assertTrue($data['json']['active']);
    }

    public function testReturnsErrorOnConnectionFailure()
    {
        $http = new Http();
        $response = $http->get('http://non-existent-domain-123456.com');
        
        $this->assertTrue($response->failed());
        $this->assertEquals(0, $response->status());
    }

    public function testFailsOnServerError()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/status/404');
        
        $this->assertTrue($response->failed());
        $this->assertEquals(404, $response->status());
    }

    public function testOkForSuccessfulRequest()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/get');
        
        $this->assertTrue($response->ok());
        $this->assertFalse($response->clientError());
        $this->assertFalse($response->serverError());
    }

    public function testClientErrorFor404()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/status/404');
        
        $this->assertFalse($response->ok());
        $this->assertTrue($response->clientError());
        $this->assertFalse($response->serverError());
    }

    public function testServerErrorFor500()
    {
        $http = new Http();
        $response = $http->get($this->baseUrl . '/status/500');
        
        $this->assertFalse($response->ok());
        $this->assertFalse($response->clientError());
        $this->assertTrue($response->serverError());
    }

    public function testRedirectFor301()
    {
        $http = new Http();
        $response = $http->options([
            CURLOPT_FOLLOWLOCATION => false
        ])->get($this->baseUrl . '/status/301');
        
        $this->assertTrue($response->redirect());
        $this->assertFalse($response->ok());
        $this->assertFalse($response->clientError());
        $this->assertFalse($response->serverError());
    }

    public function testCanSendFormData()
    {
        $http = new Http();
        $response = $http
            ->form()
            ->post($this->baseUrl . '/post', [
                'username' => 'john',
                'password' => 'secret'
            ]);
        
        $this->assertFalse($response->failed());
        $data = $response->json();
        $this->assertEquals('application/x-www-form-urlencoded', $data['headers']['Content-Type']);
        $this->assertEquals('john', $data['form']['username']);
        $this->assertEquals('secret', $data['form']['password']);
    }

    public function testCanDownloadFile()
    {
        $http = new Http();
        $savePath = tempnam(sys_get_temp_dir(), 'download_');
        
        $success = $http->download($this->baseUrl . '/image/jpeg', $savePath);
        
        $this->assertTrue($success);
        $this->assertFileExists($savePath);
        $this->assertGreaterThan(0, filesize($savePath));
        
        unlink($savePath);
    }

    public function testCanStreamGetRequest()
    {
        $http = new Http();
        $chunks = [];
        $fullContent = '';
        
        $http->stream('GET', $this->baseUrl . '/stream/5', null, function($chunk) use (&$chunks, &$fullContent) {
            $chunks[] = $chunk;
            $fullContent .= $chunk;
        });
        
        $this->assertGreaterThan(1, count($chunks), 'Should receive multiple chunks');
        $this->assertNotEmpty($fullContent, 'Should receive content');
    }

    public function testCanStreamPostRequest()
    {
        $http = new Http();
        $chunks = [];
        $fullContent = '';
        
        $http->stream('POST', $this->baseUrl . '/post', [
            'name' => 'john',
            'email' => 'john@example.com'
        ], function($chunk) use (&$chunks, &$fullContent) {
            $chunks[] = $chunk;
            $fullContent .= $chunk;
        });
        
        $this->assertGreaterThan(0, count($chunks));
        $this->assertNotEmpty($fullContent);
        $this->assertStringContainsString('john', $fullContent);
    }

    public function testCanStreamWithCustomHeaders()
    {
        $http = new Http();
        $chunks = [];
        
        $http->headers(['X-Custom' => 'streaming-test'])
            ->stream('GET', $this->baseUrl . '/headers', null, function($chunk) use (&$chunks) {
                $chunks[] = $chunk;
            });
        
        $this->assertGreaterThan(0, count($chunks));
        $fullContent = implode('', $chunks);
        $this->assertStringContainsString('streaming-test', $fullContent);
    }

    public function testCanStreamWithTimeout()
    {
        $http = new Http();
        $chunks = [];
        
        $http->timeout(10)
            ->stream('GET', $this->baseUrl . '/delay/1', null, function($chunk) use (&$chunks) {
                $chunks[] = $chunk;
            });
        
        $this->assertGreaterThan(0, count($chunks));
    }

    public function testStreamThrowsExceptionOn404()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Streaming request failed');
        
        $http = new Http();
        $http->stream('GET', $this->baseUrl . '/status/404', null, function($chunk) {
            // Should not reach here
        });
    }

    public function testStreamThrowsExceptionOn500()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Streaming request failed');
        
        $http = new Http();
        $http->stream('GET', $this->baseUrl . '/status/500', null, function($chunk) {
            // Should not reach here
        });
    }

    public function testStreamReceivesChunksProgressively()
    {
        $http = new Http();
        $timestamps = [];
        $startTime = microtime(true);
        
        $http->stream('GET', $this->baseUrl . '/stream/3', null, function($chunk) use (&$timestamps, $startTime) {
            $timestamps[] = microtime(true) - $startTime;
        });
        
        $this->assertGreaterThan(1, count($timestamps), 'Should receive multiple chunks');
        
        // Verify chunks arrived over time (not all at once)
        if (count($timestamps) > 1) {
            $timeDiff = end($timestamps) - $timestamps[0];
            $this->assertGreaterThan(0, $timeDiff, 'Chunks should arrive progressively');
        }
    }

    public function testStreamWorksWithPutMethod()
    {
        $http = new Http();
        $chunks = [];
        
        $http->stream('PUT', $this->baseUrl . '/put', [
            'name' => 'john',
            'status' => 'active'
        ], function($chunk) use (&$chunks) {
            $chunks[] = $chunk;
        });
        
        $this->assertGreaterThan(0, count($chunks));
        $fullContent = implode('', $chunks);
        $this->assertStringContainsString('john', $fullContent);
    }

    public function testStreamWorksWithDeleteMethod()
    {
        $http = new Http();
        $chunks = [];
        
        $http->stream('DELETE', $this->baseUrl . '/delete', null, function($chunk) use (&$chunks) {
            $chunks[] = $chunk;
        });
        
        $this->assertGreaterThan(0, count($chunks));
    }
}
