<?php

use Lightpack\Config\Config;
use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\Logger\Drivers\NullLogger;
use Lightpack\Logger\Logger;
use Lightpack\Secrets\Secrets;
use Lightpack\Secrets\SecretsTrait;
use Lightpack\Utils\Crypto;

class SecretsIntegrationTest extends TestCase
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
        $this->container->register('logger', fn() => new Logger(new NullLogger));
        if (!defined('DIR_CONFIG')) {
            define('DIR_CONFIG', __DIR__ . '/tmp');
        }
        $config = new Config();
        $config->set('app', ['secrets_key' => str_repeat('a', 32)]);
        $this->container->register('config', fn() => $config);
        $this->container->register('secrets', function () {
            $crypto = new Crypto(str_repeat('a', 32));
            return new Secrets(
                $this->container->get('db'),
                $this->container->get('config'),
                $crypto
            );
        });
        $this->schema = new Schema($this->db);
        $this->schema->createTable('secrets', function (Table $table) {
            $table->id();
            $table->varchar('`key`', 150);
            $table->text('value');
            $table->varchar('`group`', 150)->default('global');
            $table->column('owner_id')->type('bigint')->attribute('unsigned')->nullable();
            $table->timestamps();
            $table->unique(['key', 'group', 'owner_id']);
        });
    }

    protected function tearDown(): void
    {
        $this->schema->dropTable('secrets');
        $this->db = null;
    }

    protected function getSecretsInstance(): Secrets
    {
        return $this->container->get('secrets');
    }

    public function testSetAndGetGlobalSecret()
    {
        $secrets = $this->getSecretsInstance();
        $secrets->group('global')->owner(null)->set('api_key', 'secret123');
        $this->assertEquals('secret123', $secrets->group('global')->owner(null)->get('api_key'));
    }

    public function testSetAndGetScopedSecret()
    {
        $secrets = $this->getSecretsInstance();
        $secrets->group('users')->owner(42)->set('token', 'tok42');
        $this->assertEquals('tok42', $secrets->group('users')->owner(42)->get('token'));
    }

    public function testOverwriteSecret()
    {
        $secrets = $this->getSecretsInstance();
        $secrets->group('users')->owner(5)->set('api', 'v1');
        $secrets->group('users')->owner(5)->set('api', 'v2');
        $this->assertEquals('v2', $secrets->group('users')->owner(5)->get('api'));
    }

    public function testDeleteSecret()
    {
        $secrets = $this->getSecretsInstance();
        $secrets->group('users')->owner(6)->set('token', 'abc');
        $secrets->group('users')->owner(6)->delete('token');
        $this->assertNull($secrets->group('users')->owner(6)->get('token'));
    }

    public function testIsolationBetweenScopes()
    {
        $secrets = $this->getSecretsInstance();
        $secrets->group('users')->owner(8)->set('token', 'dark');
        $secrets->group('users')->owner(9)->set('token', 'light');
        $this->assertEquals('dark', $secrets->group('users')->owner(8)->get('token'));
        $this->assertEquals('light', $secrets->group('users')->owner(9)->get('token'));
    }

    public function testGlobalVsScopedSecrets()
    {
        $secrets = $this->getSecretsInstance();
        $secrets->group('global')->owner(null)->set('foo', 'bar');
        $secrets->group('users')->owner(1)->set('foo', 'baz');
        $this->assertEquals('bar', $secrets->group('global')->owner(null)->get('foo'));
        $this->assertEquals('baz', $secrets->group('users')->owner(1)->get('foo'));
    }

    public function testEdgeCases()
    {
        $secrets = $this->getSecretsInstance();
        $this->assertNull($secrets->group('users')->owner(1000)->get('nonexistent'));
        $secrets->group('users')->owner(11)->set('empty', '');
        $this->assertSame('', $secrets->group('users')->owner(11)->get('empty'));
        $secrets->group('users')->owner(11)->set('flag', false);
        $this->assertSame(false, $secrets->group('users')->owner(11)->get('flag'));
    }

    public function testTraitOnModel()
    {
        // $this->container->factory('secrets', fn() => $this->getSecretsInstance());
        $user = new class extends Model {
            use SecretsTrait;
            protected $table = 'users';
            protected $primaryKey = 'id';
            protected $timestamps = true;
        };
        $user->id = 123;
        $secrets = $user->secrets();
        $secrets->set('foo', 'bar');
        $this->assertEquals('bar', $secrets->get('foo'));
    }

    public function testEncryptionAndDecryption()
    {
        $secrets = $this->getSecretsInstance();
        $secrets->group('global')->owner(null)->set('enc', 'encryptme');

        // Direct DB check: should not be stored as plain text
        $row = $this->db->table('secrets')->where('key', 'enc')->where('group', 'global')->one();

        $this->assertNotEquals('encryptme', $row->value);
        $this->assertEquals('encryptme', $secrets->group('global')->owner(null)->get('enc'));
    }

    public function testRotateKeySuccessfully()
    {
        $secrets = $this->getSecretsInstance();
        $secrets->group('global')->owner(null)->set('foo', 'bar');
        $secrets->group('users')->owner(1)->set('token', 'tok1');
        $secrets->group('users')->owner(2)->set('token', 'tok2');

        $oldKey = str_repeat('a', 32);
        $newKey = str_repeat('b', 32);
        $result = $secrets->rotateKey($oldKey, $newKey);
        $this->assertEquals(['success' => 3, 'fail' => 0], $result);

        // Now secrets instance is still using old key, so get will fail
        $this->assertNull($secrets->group('global')->owner(null)->get('foo'));

        // Swap crypto to new key and verify
        $crypto = new Crypto($newKey);
        $secretsNew = new Secrets($this->db, $this->container->get('config'), $crypto);
        $this->assertEquals('bar', $secretsNew->group('global')->owner(null)->get('foo'));
        $this->assertEquals('tok1', $secretsNew->group('users')->owner(1)->get('token'));
        $this->assertEquals('tok2', $secretsNew->group('users')->owner(2)->get('token'));
    }

    public function testRotateKeyWithInvalidOldKey()
    {
        $secrets = $this->getSecretsInstance();
        $secrets->group('global')->owner(null)->set('foo', 'bar');
        $secrets->group('users')->owner(1)->set('token', 'tok1');

        $oldKey = str_repeat('x', 32); // wrong key
        $newKey = str_repeat('b', 32);
        $result = $secrets->rotateKey($oldKey, $newKey);
        // Should fail to decrypt both
        $this->assertEquals(['success' => 0, 'fail' => 2], $result);

        // Values should not be updated, still decryptable with original key
        $crypto = new Crypto(str_repeat('a', 32));
        $secretsOrig = new Secrets($this->db, $this->container->get('config'), $crypto);
        $this->assertEquals('bar', $secretsOrig->group('global')->owner(null)->get('foo'));
        $this->assertEquals('tok1', $secretsOrig->group('users')->owner(1)->get('token'));
    }

    public function testRotateKeyWithEmptyKeysThrows()
    {
        $secrets = $this->getSecretsInstance();
        $this->expectException(InvalidArgumentException::class);
        $secrets->rotateKey('', str_repeat('b', 32));
        $this->expectException(InvalidArgumentException::class);
        $secrets->rotateKey(str_repeat('a', 32), '');
    }

    public function testRotateKeySummaryCounts()
    {
        $secrets = $this->getSecretsInstance();
        // One good, one bad
        $secrets->group('global')->owner(null)->set('foo', 'bar');
        // Insert a fake/bad record that can't be decrypted
        $this->db->table('secrets')->insert([
            'key' => 'bad',
            'value' => 'not-encrypted',
            'group' => 'global',
            'owner_id' => null,
        ]);
        $oldKey = str_repeat('a', 32);
        $newKey = str_repeat('b', 32);
        $result = $secrets->rotateKey($oldKey, $newKey);
        $this->assertEquals(['success' => 1, 'fail' => 1], $result);
    }
}
