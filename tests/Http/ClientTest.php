<?php

namespace Lightpack\Tests\Http;

use Lightpack\Http\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private string $jsonApi = 'https://jsonplaceholder.typicode.com';
    private string $httpBin = 'https://httpbin.org';

    public function testCanMakeGetRequest()
    {
        $client = new Client();
        $response = $client->get($this->jsonApi . '/posts/1');
        
        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($response->json());
    }

    public function testCanMakeGetRequestWithQueryParams()
    {
        $client = new Client();
        $response = $client->get($this->jsonApi . '/posts', [
            'userId' => 1,
            'id' => 5
        ]);
        
        $this->assertEquals(200, $response->status());
        $data = $response->json();
        $this->assertIsArray($data);
    }

    public function testCanMakePostRequest()
    {
        $client = new Client();
        $response = $client
            ->post($this->jsonApi . '/posts', [
                'title' => 'foo',
                'body' => 'bar',
                'userId' => 1,
            ]);
        
        $this->assertFalse($response->failed());
        $data = $response->json();
        $this->assertEquals('foo', $data['title']);
    }

    public function testCanMakePutRequest()
    {
        $client = new Client();
        $response = $client->put($this->httpBin . '/put', [
            'name' => 'john',
            'email' => 'john@example.com'
        ]);
        
        $this->assertFalse($response->failed());
        $data = $response->json();
        $this->assertEquals('application/json', $data['headers']['Content-Type']);
        $this->assertEquals('john', $data['json']['name']);
        $this->assertEquals('john@example.com', $data['json']['email']);
    }

    public function testCanMakeDeleteRequest()
    {
        $client = new Client();
        $response = $client->delete($this->jsonApi . '/posts/1');
        
        $this->assertEquals(200, $response->status());
    }

    public function testCanSetCustomHeaders()
    {
        $client = new Client();
        $response = $client
            ->headers([
                'X-Custom' => 'test',
                'Accept' => 'application/json'
            ])
            ->get($this->httpBin . '/headers');
        
        $data = $response->json();
        $this->assertEquals('test', $data['headers']['X-Custom']);
    }

    public function testCanSetBearerToken()
    {
        $client = new Client();
        $response = $client
            ->token('xyz123')
            ->get($this->httpBin . '/headers');
        
        $data = $response->json();
        $this->assertEquals('Bearer xyz123', $data['headers']['Authorization']);
    }

    public function testCanUploadFile()
    {
        $client = new Client();
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        $response = $client
            ->files(['file' => $tempFile])
            ->post($this->httpBin . '/post');

        unlink($tempFile);
        
        $this->assertEquals(200, $response->status());
        $data = $response->json();
        $this->assertArrayHasKey('files', $data);
    }

    public function testCanSetTimeout()
    {
        $client = new Client();
        $response = $client
            ->timeout(5)
            ->get($this->httpBin . '/get');
        
        $this->assertEquals(200, $response->status());
    }

    public function testCanMakeInsecureRequest()
    {
        $client = new Client();
        $response = $client
            ->insecure()
            ->get($this->httpBin . '/get');
        
        $this->assertEquals(200, $response->status());
    }

    public function testCanGetResponseAsText()
    {
        $client = new Client();
        $response = $client->get($this->httpBin . '/get');
        
        $this->assertIsString($response->getText());
        $this->assertJson($response->getText());
    }

    public function testCanMakePatchRequest()
    {
        $client = new Client();
        $response = $client->patch($this->httpBin . '/patch', [
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
        $client = new Client();
        $response = $client->get('http://non-existent-domain-123456.com');
        
        $this->assertTrue($response->failed());
        $this->assertEquals(0, $response->status());
        $this->assertNotEmpty($response->error());
    }

    public function testFailsOnServerError()
    {
        $client = new Client();
        $response = $client->get($this->httpBin . '/status/404');
        
        $this->assertTrue($response->failed());
        $this->assertEquals(404, $response->status());
        $this->assertEmpty($response->error());
    }

    public function testOkForSuccessfulRequest()
    {
        $client = new Client();
        $response = $client->get($this->jsonApi . '/posts/1');
        
        $this->assertTrue($response->ok());
        $this->assertFalse($response->clientError());
        $this->assertFalse($response->serverError());
    }

    public function testClientErrorFor404()
    {
        $client = new Client();
        $response = $client->get($this->httpBin . '/status/404');
        
        $this->assertFalse($response->ok());
        $this->assertTrue($response->clientError());
        $this->assertFalse($response->serverError());
    }

    public function testServerErrorFor500()
    {
        $client = new Client();
        $response = $client->get($this->httpBin . '/status/500');
        
        $this->assertFalse($response->ok());
        $this->assertFalse($response->clientError());
        $this->assertTrue($response->serverError());
    }

    public function testRedirectFor301()
    {
        $client = new Client();
        $response = $client->options([
            CURLOPT_FOLLOWLOCATION => false
        ])->get($this->httpBin . '/status/301');
        
        $this->assertFalse($response->ok());
        $this->assertTrue($response->redirect());
        $this->assertFalse($response->clientError());
        $this->assertFalse($response->serverError());
    }

    public function testCanSendFormData()
    {
        $client = new Client();
        $response = $client
            ->form()
            ->post($this->httpBin . '/post', [
                'username' => 'john',
                'password' => 'secret'
            ]);
        
        $this->assertFalse($response->failed());
        $data = $response->json();
        $this->assertEquals('application/x-www-form-urlencoded', $data['headers']['Content-Type']);
        $this->assertEquals('john', $data['form']['username']);
        $this->assertEquals('secret', $data['form']['password']);
    }
}
