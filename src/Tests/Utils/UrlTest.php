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
        $this->url = new Url();
    }

    public function tearDown(): void
    {
        unset($this->url);
    }

    public function testUrlToMethod()
    {
        $this->assertEquals('/', $this->url->to());
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
}
