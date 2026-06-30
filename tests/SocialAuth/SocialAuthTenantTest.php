<?php

declare(strict_types=1);

use Lightpack\Container\Container;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\Lucid\TenantContext;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\SocialAuth\Models\SocialAccountModel;
use PHPUnit\Framework\TestCase;

final class SocialAuthTenantTest extends TestCase
{
    private ?Mysql $db;
    private Schema $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new Mysql($config);
        $this->schema = new Schema($this->db);

        $container = Container::getInstance();
        $container->register('db', fn () => $this->db);
        $container->register('logger', fn () => new class {
            public function error($message, $context = [])
            {
            }
        });

        $this->schema->createTable('social_accounts', function (Table $table) {
            $table->id();
            $table->column('user_id')->type('BIGINT')->attribute('UNSIGNED');
            $table->varchar('provider', 20);
            $table->varchar('provider_id', 255);
            $table->column('tenant_id')->type('BIGINT')->attribute('UNSIGNED')->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'provider', 'provider_id']);
            $table->index('user_id');
            $table->index('tenant_id');
        });

        $this->schema->createTable('users', function (Table $table) {
            $table->id();
            $table->varchar('name', 100)->nullable();
            $table->varchar('email', 255)->nullable();
            $table->varchar('password', 255)->nullable();
            $table->column('tenant_id')->type('BIGINT')->attribute('UNSIGNED')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        $this->schema->dropTable('social_accounts');
        $this->schema->dropTable('users');
        $this->db = null;
        TenantContext::clear();
    }

    public function test_social_account_lookup_is_scoped_by_tenant()
    {
        // Insert social accounts for two tenants with same provider+provider_id
        $this->db->table('users')->insert([
            ['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'secret', 'tenant_id' => 5],
            ['name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'secret', 'tenant_id' => 7],
        ]);

        $this->db->table('social_accounts')->insert([
            ['user_id' => 1, 'provider' => 'google', 'provider_id' => 'google-123', 'tenant_id' => 5],
            ['user_id' => 2, 'provider' => 'google', 'provider_id' => 'google-123', 'tenant_id' => 7],
        ]);

        // Lookup scoped to tenant 5
        $account = SocialAccountModel::query()
            ->where('provider', 'google')
            ->where('provider_id', 'google-123')
            ->where('tenant_id', '=', 5)
            ->one();

        $this->assertNotNull($account);
        $this->assertEquals(1, $account->user_id);

        // Lookup scoped to tenant 7
        $account = SocialAccountModel::query()
            ->where('provider', 'google')
            ->where('provider_id', 'google-123')
            ->where('tenant_id', '=', 7)
            ->one();

        $this->assertNotNull($account);
        $this->assertEquals(2, $account->user_id);
    }

    public function test_same_provider_id_can_exist_across_tenants()
    {
        $this->db->table('users')->insert([
            ['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'secret', 'tenant_id' => 5],
            ['name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'secret', 'tenant_id' => 7],
        ]);

        // Both tenants can have the same Google account linked
        $this->db->table('social_accounts')->insert([
            ['user_id' => 1, 'provider' => 'google', 'provider_id' => 'google-123', 'tenant_id' => 5],
            ['user_id' => 2, 'provider' => 'google', 'provider_id' => 'google-123', 'tenant_id' => 7],
        ]);

        $count = $this->db->table('social_accounts')->count();
        $this->assertEquals(2, $count);
    }

    public function test_social_account_creation_includes_tenant_id()
    {
        TenantContext::set(5);

        $account = new SocialAccountModel;
        $account->user_id = 1;
        $account->provider = 'google';
        $account->provider_id = 'google-abc';
        $account->tenant_id = TenantContext::get() ?? 0;
        $account->save();

        $this->assertEquals(5, $account->tenant_id);

        TenantContext::clear();
    }
}
