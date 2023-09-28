<?php

declare(strict_types=1);

use Lightpack\Container\Container;
use Lightpack\Http\Request;
use Lightpack\Routing\RouteRegistry;
use Lightpack\Utils\Crypto;
use Lightpack\Utils\Url;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\once;

final class UrlTest extends TestCase
{
    /** @var Url */
    private $url;

    public function setUp(): void
    {
        $this->url = new Url();
    }

    public function tearDown(): void
    {
        unset($this->url);
    }

    public function testUrlToMethod()
    {
        $this->assertEquals('/', $this->url->to('/'));
        $this->assertEquals('/', $this->url->to('//'));
        $this->assertEquals('/users', $this->url->to('users'));
        $this->assertEquals('/users/profile', $this->url->to('users/profile'));
        $this->assertEquals('/users/profile', $this->url->to('users', 'profile'));
        $this->assertEquals('/users/profile', $this->url->to('users', 'profile'));
        $this->assertEquals('/users/profile', $this->url->to('/users', ' profile '));
        $this->assertEquals('/users/profile', $this->url->to('users', 'profile', '', null));
        $this->assertEquals('/?sort=asc&status=active', $this->url->to(['sort' => 'asc', 'status' => 'active']));
        $this->assertEquals('/users?sort=asc&status=active', $this->url->to('users', ['sort' => 'asc', 'status' => 'active']));
        $this->assertEquals('/users', $this->url->to('users', ['sort' => '', 'status' => null]));
        $this->assertEquals('/users?member=gold', $this->url->to('users', ['sort' => '', 'status' => null, 'member' => 'gold']));
    }

    public function testUrlAssetMethod()
    {
        $this->assertEquals('/assets', $this->url->asset(''));
        $this->assertEquals('/assets/styles.css', $this->url->asset('styles.css'));
        $this->assertEquals('/assets/styles.css', $this->url->asset('/styles.css'));
        $this->assertEquals('/assets/styles.css', $this->url->asset(' styles.css '));
        $this->assertEquals('/assets/css/styles.css', $this->url->asset('css/styles.css'));
        $this->assertEquals('/assets/css/styles.css', $this->url->asset('/css/styles.css'));
    }

    public function testUrlRouteMethod()
    {
        $testRoutes = [
            ['name' => 'foo', 'uri' => '/foo', 'params' => [], 'expected' => '/foo'],
            ['name' => 'foo.num', 'uri' => '/foo/:num', 'params' => [23], 'expected' => '/foo/23'],
            ['name' => 'foo.num.bar', 'uri' => '/foo/:num/bar', 'params' => [23], 'expected' => '/foo/23/bar'],
            ['name' => 'foo.num.bar.baz', 'uri' => '/foo/:num/bar/:slug', 'params' => [23, 'baz'], 'expected' => '/foo/23/bar/baz'],
            ['name' => 'foo.num.bar.baz', 'uri' => '/foo/:num/bar/:slug', 'params' => [23, 'baz', ['p' => 1, 'r' => 2]], 'expected' => '/foo/23/bar/baz?p=1&r=2'],
        ];

        // Add routes
        $routeRegistery = new RouteRegistry(new Request());
        $routeRegistery->get('/foo', 'DummyController')->name('foo');
        $routeRegistery->get('/foo/:num', 'DummyController')->name('foo.num');
        $routeRegistery->get('/foo/:num/bar', 'DummyController')->name('foo.num.bar');
        $routeRegistery->get('/foo/:num/bar/:slug', 'DummyController')->name('foo.num.bar.baz');
        $routeRegistery->bootRouteNames();

        // Set container
        Container::getInstance()->instance('route', $routeRegistery);

        // Test routes
        $this->assertEquals('/foo', $this->url->route('foo'));
        $this->assertEquals('/foo/23', $this->url->route('foo.num', ['num' => 23]));
        $this->assertEquals('/foo/23/bar', $this->url->route('foo.num.bar', ['num' => 23]));
        $this->assertEquals('/foo/23/bar/baz', $this->url->route('foo.num.bar.baz', ['num' => 23, 'slug' => 'baz']));
        $this->assertEquals('/foo/23/bar/baz?p=1&r=2', $this->url->route('foo.num.bar.baz', ['num' => 23, 'slug' => 'baz', 'p' => 1, 'r' => 2]));
    }

    public function testUrlSignMethod()
    {
        $url = 'https://example.com';

        // Add routes
        $routeRegistery = new RouteRegistry(new Request());
        $routeRegistery->get('/users', 'DummyController')->name('users');
        $routeRegistery->bootRouteNames();
        Container::getInstance()->instance('route', $routeRegistery);

        // Set up the Crypto class mock
        $cryptoMock = $this->getMockBuilder(Crypto::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cryptoMock->expects($this->once())
            ->method('hash')
            ->willReturn('encryptedSignature');

        // Set the Container 
        Container::getInstance()->instance('crypto', $cryptoMock);

        // Generate the signed URL
        $signedUrl = $this->url->sign('users', ['sort' => 'asc', 'status' => 'active'], 3600);

        // Verify the generated URL
        $this->assertStringContainsString('/users?sort=asc&status=active', $signedUrl);
        $this->assertStringContainsString('&signature=encryptedSignature', $signedUrl);
        $this->assertStringMatchesFormat('%s&expires=%s', $signedUrl);
    }

    /**
     * Verify the integrity and expiration of a signed URL.
     *
     * @param string $url The signed URL to verify.
     * @param array $ignoredParameters The optional array of query parameters to ignore during verification.
     * @return bool True if the URL is valid and has not been tampered with or expired, false otherwise.
     */
    public function testUrlVerifyMethod()
    {
        $expires = time() + 3600;
        $url = 'https://example.com/users?sort=asc&status=active&signature=encryptedSignature&expires=' . $expires;
        $invalidUrl = 'https://example.com/users?sort=asc&status=active&signature=encryptedSignature&expires=' . time() - 1;
        $plainUrl = 'https://example.com/users';

        // Set up the Crypto class mock
        $cryptoMock = $this->getMockBuilder(Crypto::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cryptoMock
            ->method('hash')
            ->willReturn('encryptedSignature');

        // Set the Container 
        Container::getInstance()->instance('crypto', $cryptoMock);

        // Assertions
        $this->assertTrue($this->url->verify($url));
        $this->assertTrue($this->url->verify($url, ['sort']));
        $this->assertFalse($this->url->verify($invalidUrl));
        $this->assertFalse($this->url->verify($plainUrl));
    }
}
