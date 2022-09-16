<?php

declare(strict_types=1);

use Lightpack\Utils\Url;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\once;

final class UrlTest extends TestCase
{
    /** @var Url */
    private $url;

    public function setUp(): void
    {
        $request = $this->createMock('Lightpack\Http\Request');
        $this->url = new Url($request);
    }

    public function tearDown(): void
    {
        unset($this->url);
    }

    public function testUrlToMethod()
    {
        $this->assertEquals('/users', $this->url->to('users'));
    }

    public function testToMethodReturnsUrlWithQueryString(): void
    {
        $this->assertEquals(
            '/?sort=asc&status=active',
            $this->url->to(['sort' => 'asc', 'status' => 'active'])
        );
    }
}
