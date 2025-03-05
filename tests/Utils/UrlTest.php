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
        $this->assertEquals('', $this->url->asset(''));
        $this->assertEquals('/styles.css', $this->url->asset('styles.css'));
        $this->assertEquals('/styles.css', $this->url->asset('/styles.css'));
        $this->assertEquals('/styles.css', $this->url->asset(' styles.css '));
        $this->assertEquals('/css/styles.css', $this->url->asset('css/styles.css'));
        $this->assertEquals('/css/styles.css', $this->url->asset('/css/styles.css'));
    }

    public function testUrlRouteMethod()
    {
        // Add routes
        $container = Container::getInstance();
        $container->instance('request', new Request());
        $routeRegistery = new RouteRegistry($container);
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
        $container = Container::getInstance();
        $container->instance('request', new Request());
        $routeRegistery = new RouteRegistry($container);
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

    public function testValidate()
    {
        // Test valid URLs
        $this->assertTrue($this->url->validate('https://example.com'));
        $this->assertTrue($this->url->validate('http://localhost:8080'));
        
        // Test with custom schemes
        $this->assertTrue($this->url->validate('https://example.com', [
            'schemes' => ['https']
        ]));
        $this->assertFalse($this->url->validate('http://example.com', [
            'schemes' => ['https']
        ]));
        
        // Test with allowed hosts
        $this->assertTrue($this->url->validate('https://example.com', [
            'allowed_hosts' => ['example.com']
        ]));
        $this->assertFalse($this->url->validate('https://evil.com', [
            'allowed_hosts' => ['example.com']
        ]));
        
        // Test URL length
        $this->assertFalse($this->url->validate('https://example.com', [
            'max_length' => 10
        ]));
        
        // Test invalid URLs
        $this->assertFalse($this->url->validate('not-a-url'));
        $this->assertFalse($this->url->validate('http://'));
        $this->assertFalse($this->url->validate('https://example.com:99999')); // Invalid port
    }

    public function testJoin()
    {
        // Test basic joining
        $this->assertEquals(
            'https://api.com/v1/users',
            $this->url->join('https://api.com', 'v1', 'users')
        );
        
        // Test with slashes
        $this->assertEquals(
            'https://api.com/v1/users',
            $this->url->join('https://api.com/', '/v1/', '/users/')
        );
        
        // Test with query string
        $this->assertEquals(
            'https://api.com/v1/users?sort=desc',
            $this->url->join('https://api.com', 'v1', 'users', '?sort=desc')
        );
        
        // Test with fragment
        $this->assertEquals(
            'https://api.com/v1/users#section',
            $this->url->join('https://api.com', 'v1', 'users#section')
        );
        
        // Test with query and fragment
        $this->assertEquals(
            'https://api.com/v1/users?sort=desc#section',
            $this->url->join('https://api.com', 'v1', 'users?sort=desc#section')
        );
        
        // Test with empty segments
        $this->assertEquals(
            'https://api.com/users',
            $this->url->join('https://api.com', '', 'users')
        );
        
        // Test with no segments
        $this->assertEquals('', $this->url->join());
    }

    public function testWithoutQuery()
    {
        // Test removing single parameter
        $this->assertEquals(
            'https://example.com/posts?page=1',
            $this->url->withoutQuery('https://example.com/posts?page=1&sort=desc', 'sort')
        );
        
        // Test removing multiple parameters
        $this->assertEquals(
            'https://example.com/search?q=php',
            $this->url->withoutQuery(
                'https://example.com/search?q=php&utm_source=fb&utm_medium=social',
                ['utm_source', 'utm_medium']
            )
        );
        
        // Test removing non-existent parameters
        $this->assertEquals(
            'https://example.com/posts?page=1',
            $this->url->withoutQuery('https://example.com/posts?page=1', 'sort')
        );
        
        // Test removing all parameters
        $this->assertEquals(
            'https://example.com/posts',
            $this->url->withoutQuery('https://example.com/posts?page=1&sort=desc', ['page', 'sort'])
        );
        
        // Test with no query parameters
        $this->assertEquals(
            'https://example.com/posts',
            $this->url->withoutQuery('https://example.com/posts', 'sort')
        );

        // Test removing all query parameters with no keys specified
        $this->assertEquals(
            'https://example.com/posts',
            $this->url->withoutQuery('https://example.com/posts?page=1&sort=desc&utm_source=fb')
        );

        // Test removing all query parameters but preserve fragment
        $this->assertEquals(
            'https://example.com/posts#section',
            $this->url->withoutQuery('https://example.com/posts?page=1&sort=desc#section')
        );
    }

    public function testWithFragment()
    {
        // Test adding fragment
        $this->assertEquals(
            'https://example.com/posts#section1',
            $this->url->withFragment('https://example.com/posts', 'section1')
        );
        
        // Test updating existing fragment
        $this->assertEquals(
            'https://example.com/posts#new',
            $this->url->withFragment('https://example.com/posts#old', 'new')
        );
        
        // Test with query parameters
        $this->assertEquals(
            'https://example.com/posts?page=1#section',
            $this->url->withFragment('https://example.com/posts?page=1', 'section')
        );
        
        // Test with hash in fragment
        $this->assertEquals(
            'https://example.com/posts#section',
            $this->url->withFragment('https://example.com/posts', '#section')
        );
        
        // Test with empty fragment
        $this->assertEquals(
            'https://example.com/posts',
            $this->url->withFragment('https://example.com/posts#old', '')
        );
    }

    public function testWithoutFragment()
    {
        // Test removing fragment
        $this->assertEquals(
            'https://example.com/posts',
            $this->url->withoutFragment('https://example.com/posts#section')
        );
        
        // Test with query parameters
        $this->assertEquals(
            'https://example.com/posts?page=1',
            $this->url->withoutFragment('https://example.com/posts?page=1#section')
        );
        
        // Test without fragment
        $this->assertEquals(
            'https://example.com/posts',
            $this->url->withoutFragment('https://example.com/posts')
        );
        
        // Test with empty fragment
        $this->assertEquals(
            'https://example.com/posts',
            $this->url->withoutFragment('https://example.com/posts#')
        );
    }
}
