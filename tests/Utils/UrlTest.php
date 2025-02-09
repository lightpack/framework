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
        // Add routes
        $routeRegistery = new RouteRegistry(new Request());
        $routeRegistery->get('/foo', 'DummyController')->name('foo');
        $routeRegistery->get('/foo/:num', 'DummyController')->name('foo.num');
        $routeRegistery->get('/foo/:num/bar/:slug?', 'DummyController')->name('foo.num.bar');
        $routeRegistery->bootRouteNames();

        // Set container
        Container::getInstance()->instance('route', $routeRegistery);

        // Test routes
        $this->assertEquals('/foo', $this->url->route('foo'));
        $this->assertEquals('/foo/23', $this->url->route('foo.num', ['num' => 23]));
        $this->assertEquals('/foo/23/bar', $this->url->route('foo.num.bar', ['num' => 23]));
        $this->assertEquals('/foo/23/bar/baz', $this->url->route('foo.num.bar', ['num' => 23, 'slug' => 'baz']));
        $this->assertEquals('/foo/23/bar/baz?p=1&r=2', $this->url->route('foo.num.bar', ['num' => 23, 'slug' => 'baz', 'p' => 1, 'r' => 2]));
        $this->assertEquals('/foo/23/bar?p=1&r=2', $this->url->route('foo.num.bar', ['num' => 23, 'slug' => null, 'p' => 1, 'r' => 2]));
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

    public function testParse()
    {
        $url = 'https://john:doe@example.com:8080/blog/post?sort=desc&page=2#comments';
        $parts = $this->url->parse($url);

        $this->assertEquals('https', $parts['scheme']);
        $this->assertEquals('example.com', $parts['host']);
        $this->assertEquals(8080, $parts['port']);
        $this->assertEquals('john', $parts['user']);
        $this->assertEquals('doe', $parts['pass']);
        $this->assertEquals('/blog/post', $parts['path']);
        $this->assertEquals(['sort' => 'desc', 'page' => '2'], $parts['query']);
        $this->assertEquals('comments', $parts['fragment']);

        // Test with minimal URL
        $parts = $this->url->parse('https://example.com');
        $this->assertEquals('https', $parts['scheme']);
        $this->assertEquals('example.com', $parts['host']);
        $this->assertNull($parts['port']);
        $this->assertNull($parts['user']);
        $this->assertNull($parts['pass']);
        $this->assertNull($parts['path']);
        $this->assertEquals([], $parts['query']);
        $this->assertNull($parts['fragment']);

        // Test with invalid URL
        $this->expectException(\InvalidArgumentException::class);
        $this->url->parse('not a url');
    }

    public function testWithQuery()
    {
        // Test adding single parameter as array
        $this->assertEquals(
            'https://example.com/search?q=php',
            $this->url->withQuery('https://example.com/search', ['q' => 'php'])
        );

        // Test adding multiple parameters (preserves order)
        $this->assertEquals(
            'https://example.com/users?sort=name&order=desc',
            $this->url->withQuery('https://example.com/users', ['sort' => 'name', 'order' => 'desc'])
        );

        // Test merging with existing parameters (preserves order)
        $this->assertEquals(
            'https://example.com/posts?category=tech&author=john',
            $this->url->withQuery('https://example.com/posts?category=tech', ['author' => 'john'])
        );

        // Test array parameters
        $this->assertEquals(
            'https://example.com/posts?tags%5B0%5D=php&tags%5B1%5D=mysql',
            $this->url->withQuery('https://example.com/posts', ['tags' => ['php', 'mysql']])
        );

        // Test nested array parameters
        $this->assertEquals(
            'https://example.com/search?filter%5Btags%5D%5B0%5D=php&filter%5Byear%5D=2024',
            $this->url->withQuery('https://example.com/search', [
                'filter' => [
                    'tags' => ['php'],
                    'year' => '2024'
                ]
            ])
        );

        // Test with empty/null values
        $this->assertEquals(
            'https://example.com/posts?valid=1',
            $this->url->withQuery('https://example.com/posts', ['empty' => '', 'null' => null, 'valid' => '1'])
        );

        // Test with special characters
        $this->assertEquals(
            'https://example.com/search?q=php%20%26%20mysql',
            $this->url->withQuery('https://example.com/search', ['q' => 'php & mysql'])
        );

        // Test parameter order preservation
        $this->assertEquals(
            'https://example.com/posts?c=3&a=1&b=2',
            $this->url->withQuery('https://example.com/posts', ['c' => 3, 'a' => 1, 'b' => 2])
        );
    }

    public function testNormalize()
    {
        // Test removing duplicate slashes
        $this->assertEquals(
            'https://example.com/api/users',
            $this->url->normalize('https://example.com//api//users')
        );

        // Test resolving directory traversal
        $this->assertEquals(
            'https://example.com/api/users',
            $this->url->normalize('https://example.com/blog/../api/./users')
        );

        // Test with query parameters
        $this->assertEquals(
            'https://example.com/api/users?sort=name&page=1',
            $this->url->normalize('https://example.com//api/users/?sort=name&page=1')
        );

        // Test with fragment
        $this->assertEquals(
            'https://example.com/api/users#section',
            $this->url->normalize('https://example.com//api/users/#section')
        );

        // Test with complex path
        $this->assertEquals(
            'https://example.com/final',
            $this->url->normalize('https://example.com/one/../two/./three/../../final')
        );

        // Test with port and authentication
        $this->assertEquals(
            'https://user:pass@example.com:8080/api/users',
            $this->url->normalize('https://user:pass@example.com:8080//api//users//')
        );
    }
}
