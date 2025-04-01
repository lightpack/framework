<?php

namespace Lightpack\Tests\Http;

use Lightpack\Http\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testCanMakeGetRequest()
    {
        $client = new Client();
        $response = $client->get('https://jsonplaceholder.typicode.com/posts/1');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getJson());
    }

    public function testCanMakePostRequest()
    {
        $client = new Client();
        $response = $client
            ->json()
            ->post('https://jsonplaceholder.typicode.com/posts', [
                'title' => 'foo',
                'body' => 'bar',
                'userId' => 1,
            ]);
        
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($response->getJson());
    }

    public function testCanUploadFile()
    {
        $client = new Client();
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        $response = $client
            ->files(['file' => $tempFile])
            ->post('https://httpbin.org/post');

        unlink($tempFile);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getJson();
        $this->assertArrayHasKey('files', $data);
    }
}
