<?php

use Lightpack\Cache\Cache;
use Lightpack\Cache\Drivers\ArrayDriver;
use Lightpack\Config\Config;
use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\Logger\Drivers\NullLogger;
use Lightpack\Logger\Logger;
use Lightpack\Settings\Settings;
use Lightpack\Settings\SettingsTrait;

class SettingsIntegrationTest extends TestCase
{
    private $db;
    private $schema;
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);

        $this->container = Container::getInstance();
        $this->container->register('db', fn() => $this->db);
        $this->container->register('cache', fn() => new Cache(new ArrayDriver));
        $this->container->register('logger', fn() => new Logger(new NullLogger));

        $config = new Config();
        $config->set('settings', [
            'cache' => true,
            'ttl' => 3600,
        ]);
        $this->container->register('config', fn() => $config);

        $this->schema = new Schema($this->db);
        $this->schema->createTable('settings', function(Table $table) {
            $table->id();
            $table->varchar('`key`', 150);
            $table->varchar('key_type', 25)->nullable();
            $table->text('value');
            $table->varchar('`group`', 150)->default('global');
            $table->column('owner_id')->type('bigint')->attribute('unsigned')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        $this->schema->dropTable('settings');
        $this->db = null;
    }

    protected function getSettingsInstance()
    {
        return new Settings(
            Container::getInstance()->get('db'),
            Container::getInstance()->get('cache'),
            Container::getInstance()->get('config')
        );
    }

    public function testSetAndGetGlobalSetting()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('global')->owner(null)->set('site_name', 'Lightpack');
        $this->assertEquals('Lightpack', $settings->group('global')->owner(null)->get('site_name'));
    }

    public function testSetAndGetScopedSetting()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('users')->owner(42)->set('theme', 'dark');
        $this->assertEquals('dark', $settings->group('users')->owner(42)->get('theme'));
    }

    public function testTypeSafetyInt()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('users')->owner(1)->set('age', 25);
        $this->assertSame(25, $settings->group('users')->owner(1)->get('age'));
    }

    public function testTypeSafetyBool()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('users')->owner(2)->set('active', true);
        $this->assertSame(true, $settings->group('users')->owner(2)->get('active'));
    }

    public function testTypeSafetyFloat()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('products')->owner(5)->set('tax', 18.5);
        $this->assertSame(18.5, $settings->group('products')->owner(5)->get('tax'));
    }

    public function testTypeSafetyArray()
    {
        $settings = $this->getSettingsInstance();
        $arr = ['a' => 1, 'b' => 2];
        $settings->group('users')->owner(3)->set('prefs', $arr);
        $this->assertSame($arr, $settings->group('users')->owner(3)->get('prefs'));
    }

    public function testTypeSafetyNull()
    {
        // Setting a null value should now throw an exception (null is not allowed)
        $settings = $this->getSettingsInstance();
        $this->expectException(\InvalidArgumentException::class);
        $settings->group('users')->owner(4)->set('middle_name', null);
        // No need to assert get() here, as exception should be thrown above
    }

    public function testOverwriteSetting()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('users')->owner(5)->set('theme', 'light');
        $settings->group('users')->owner(5)->set('theme', 'dark');
        $this->assertEquals('dark', $settings->group('users')->owner(5)->get('theme'));
    }

    public function testForgetSetting()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('users')->owner(6)->set('theme', 'light');
        $settings->group('users')->owner(6)->forget('theme');
        $this->assertNull($settings->group('users')->owner(6)->get('theme'));
    }

    public function testAllSettingsForScope()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('users')->owner(7)->set('x', 1);
        $settings->group('users')->owner(7)->set('y', 2);
        $all = $settings->group('users')->owner(7)->all();
        $this->assertArrayHasKey('x', $all);
        $this->assertArrayHasKey('y', $all);
        $this->assertCount(2, $all);
    }

    public function testIsolationBetweenScopes()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('users')->owner(8)->set('theme', 'dark');
        $settings->group('users')->owner(9)->set('theme', 'light');
        $this->assertEquals('dark', $settings->group('users')->owner(8)->get('theme'));
        $this->assertEquals('light', $settings->group('users')->owner(9)->get('theme'));
    }

    public function testCacheHitMissAndInvalidation()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('users')->owner(10)->set('foo', 'bar');
        // Should be cached now
        $this->assertEquals('bar', $settings->group('users')->owner(10)->get('foo'));
        $settings->group('users')->owner(10)->set('foo', 'baz'); // Invalidate cache
        $this->assertEquals('baz', $settings->group('users')->owner(10)->get('foo'));
        $settings->group('users')->owner(10)->forget('foo'); // Invalidate cache
        $this->assertNull($settings->group('users')->owner(10)->get('foo'));
    }

    public function testGlobalVsScopedSettings()
    {
        $settings = $this->getSettingsInstance();
        $settings->group('global')->owner(null)->set('foo', 'bar');
        $settings->group('users')->owner(1)->set('foo', 'baz');
        $this->assertEquals('bar', $settings->group('global')->owner(null)->get('foo'));
        $this->assertEquals('baz', $settings->group('users')->owner(1)->get('foo'));
    }

    public function testEdgeCases()
    {
        $settings = $this->getSettingsInstance();
        // Getting non-existent key returns null
        $this->assertNull($settings->group('users')->owner(1000)->get('nonexistent'));
        // Setting empty string
        $settings->group('users')->owner(11)->set('empty', '');
        $this->assertSame('', $settings->group('users')->owner(11)->get('empty'));
        // Setting false
        $settings->group('users')->owner(11)->set('flag', false);
        $this->assertSame(false, $settings->group('users')->owner(11)->get('flag'));
    }

    public function testTraitOnModel()
    {
        $this->container->register('settings', fn() => $this->getSettingsInstance());
        $user = new class extends Model {
            use SettingsTrait;
            protected $table = 'users';
            protected $primaryKey = 'id';
        };

        $user->id = 123;
        $settings = $user->settings();
        $settings->set('foo', 'bar');
        $this->assertEquals('bar', $settings->get('foo'));
    }
}
