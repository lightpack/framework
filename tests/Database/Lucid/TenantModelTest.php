<?php

namespace Lightpack\Tests\Database\Lucid;

use Lightpack\Database\Lucid\TenantModel;
use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Database\DB;
use Lightpack\Exceptions\RecordNotFoundException;

class Post extends TenantModel
{
    protected $table = 'posts';
}

class Article extends TenantModel
{
    protected $table = 'articles';
    protected $tenantColumn = 'site_id';  // Custom column name
}

class TenantModelTest extends TestCase
{
    private ?DB $db;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        
        // Create tables
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `posts` (
                `id` int NOT NULL AUTO_INCREMENT,
                `tenant_id` int NOT NULL,
                `title` varchar(255) NOT NULL,
                `content` text,
                PRIMARY KEY (id),
                INDEX idx_tenant_id (tenant_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `articles` (
                `id` int NOT NULL AUTO_INCREMENT,
                `site_id` int NOT NULL,
                `title` varchar(255) NOT NULL,
                `body` text,
                PRIMARY KEY (id),
                INDEX idx_site_id (site_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Insert test data
        $this->db->table('posts')->insert([
            ['tenant_id' => 1, 'title' => 'Tenant 1 Post 1', 'content' => 'Content 1'],
            ['tenant_id' => 1, 'title' => 'Tenant 1 Post 2', 'content' => 'Content 2'],
            ['tenant_id' => 2, 'title' => 'Tenant 2 Post 1', 'content' => 'Content 3'],
            ['tenant_id' => 2, 'title' => 'Tenant 2 Post 2', 'content' => 'Content 4'],
        ]);

        $this->db->table('articles')->insert([
            ['site_id' => 1, 'title' => 'Site 1 Article 1', 'body' => 'Body 1'],
            ['site_id' => 1, 'title' => 'Site 1 Article 2', 'body' => 'Body 2'],
            ['site_id' => 2, 'title' => 'Site 2 Article 1', 'body' => 'Body 3'],
        ]);

        // Configure container
        $container = Container::getInstance();

        $container->register('db', function () {
            return $this->db;
        });

        $container->register('logger', function () {
            return new class {
                public function error($message, $context = []) {}
                public function critical($message, $context = []) {}
            };
        });

        // Mock session
        $mockSession = new class {
            private $data = [];
            public function get($key) {
                return $this->data[$key] ?? null;
            }
            public function set($key, $value) {
                $this->data[$key] = $value;
            }
            public function forget($key) {
                unset($this->data[$key]);
            }
        };
        
        $container->register('session', function () use ($mockSession) {
            return $mockSession;
        });
    }

    public function tearDown(): void
    {
        $this->db->query("DROP TABLE IF EXISTS posts, articles");
        $this->db = null;
    }

    // ==================== READ ISOLATION TESTS ====================

    public function testQueryAllFiltersByTenant()
    {
        session()->set('tenant.id', 1);
        
        $posts = Post::query()->all();
        
        $this->assertCount(2, $posts);
        foreach ($posts as $post) {
            $this->assertEquals(1, $post->tenant_id);
        }
    }

    public function testQueryCountFiltersByTenant()
    {
        session()->set('tenant.id', 1);
        
        $count = Post::query()->count();
        
        $this->assertEquals(2, $count);
    }

    public function testQueryWithWhereFiltersByTenant()
    {
        session()->set('tenant.id', 1);
        
        $posts = Post::query()
            ->where('title', 'LIKE', '%Post%')
            ->all();
        
        $this->assertCount(2, $posts);
        foreach ($posts as $post) {
            $this->assertEquals(1, $post->tenant_id);
        }
    }

    public function testFindFiltersByTenant()
    {
        session()->set('tenant.id', 1);
        
        // Get first post from tenant 1
        $post = Post::query()->one();
        $postId = $post->id;
        
        // Should be able to find it
        $found = (new Post)->find($postId);
        $this->assertEquals($postId, $found->id);
        $this->assertEquals(1, $found->tenant_id);
    }

    public function testFindThrowsExceptionForOtherTenant()
    {
        session()->set('tenant.id', 1);
        
        // Get a post from tenant 2
        session()->set('tenant.id', 2);
        $post = Post::query()->one();
        $tenant2PostId = $post->id;
        
        // Switch back to tenant 1
        session()->set('tenant.id', 1);
        
        // Should not be able to find tenant 2's post
        $this->expectException(RecordNotFoundException::class);
        (new Post)->find($tenant2PostId);
    }

    public function testCustomTenantColumn()
    {
        session()->set('tenant.id', 1);
        
        $articles = Article::query()->all();
        
        $this->assertCount(2, $articles);
        foreach ($articles as $article) {
            $this->assertEquals(1, $article->site_id);
        }
    }

    // ==================== CREATE ISOLATION TESTS ====================

    public function testSaveAutoAssignsTenant()
    {
        session()->set('tenant.id', 1);
        
        $post = new Post();
        $post->title = 'New Post';
        $post->content = 'New Content';
        $post->save();
        
        $this->assertEquals(1, $post->tenant_id);
        
        // Verify in database
        $found = $this->db->table('posts')
            ->where('id', $post->id)
            ->one();
        $this->assertEquals(1, $found->tenant_id);
    }

    public function testInsertAutoAssignsTenant()
    {
        session()->set('tenant.id', 1);
        
        $post = new Post();
        $post->title = 'New Post';
        $post->content = 'New Content';
        $post->insert();
        
        $this->assertEquals(1, $post->tenant_id);
    }

    public function testSaveDoesNotOverrideExplicitTenant()
    {
        session()->set('tenant.id', 1);
        
        $post = new Post();
        $post->title = 'New Post';
        $post->content = 'New Content';
        $post->tenant_id = 2;  // Explicitly set to different tenant
        $post->save();
        
        $this->assertEquals(2, $post->tenant_id);
    }

    public function testCustomTenantColumnAutoAssigned()
    {
        session()->set('tenant.id', 1);
        
        $article = new Article();
        $article->title = 'New Article';
        $article->body = 'New Body';
        $article->save();
        
        $this->assertEquals(1, $article->site_id);
    }

    // ==================== UPDATE ISOLATION TESTS ====================

    public function testUpdateOnlyAffectsTenantRecords()
    {
        session()->set('tenant.id', 1);
        
        // Update all posts (should only affect tenant 1)
        Post::query()->update(['content' => 'Updated']);
        
        // Check tenant 1 posts are updated
        $tenant1Posts = $this->db->table('posts')
            ->where('tenant_id', 1)
            ->all();
        foreach ($tenant1Posts as $post) {
            $this->assertEquals('Updated', $post->content);
        }
        
        // Check tenant 2 posts are unchanged
        $tenant2Posts = $this->db->table('posts')
            ->where('tenant_id', 2)
            ->all();
        foreach ($tenant2Posts as $post) {
            $this->assertNotEquals('Updated', $post->content);
        }
    }

    public function testModelUpdateFiltersByTenant()
    {
        session()->set('tenant.id', 1);
        
        $post = Post::query()->one();
        $post->title = 'Updated Title';
        $post->save();
        
        // Verify tenant_id didn't change
        $this->assertEquals(1, $post->tenant_id);
        
        // Verify in database
        $found = $this->db->table('posts')
            ->where('id', $post->id)
            ->one();
        $this->assertEquals(1, $found->tenant_id);
        $this->assertEquals('Updated Title', $found->title);
    }

    public function testDirectUpdateMethodFiltersByTenant()
    {
        session()->set('tenant.id', 1);
        
        $post = Post::query()->one();
        $post->title = 'Updated via update()';
        $post->update();
        
        $this->assertEquals(1, $post->tenant_id);
    }

    public function testUpdateAllowsExplicitTenantChange()
    {
        session()->set('tenant.id', 1);
        
        $post = Post::query()->one();
        $originalId = $post->id;
        
        // Explicitly change tenant
        $post->tenant_id = 2;
        $post->update();  // Use update() not save()
        
        // Verify tenant changed
        $this->assertEquals(2, $post->tenant_id);
        
        // Verify in database
        $found = $this->db->table('posts')
            ->where('id', $originalId)
            ->one();
        $this->assertEquals(2, $found->tenant_id);
    }

    // ==================== DELETE ISOLATION TESTS ====================

    public function testDeleteOnlyAffectsTenantRecords()
    {
        session()->set('tenant.id', 1);
        
        // Delete all posts (should only affect tenant 1)
        Post::query()->delete();
        
        // Check tenant 1 posts are deleted
        $tenant1Count = $this->db->table('posts')
            ->where('tenant_id', 1)
            ->count();
        $this->assertEquals(0, $tenant1Count);
        
        // Check tenant 2 posts still exist
        $tenant2Count = $this->db->table('posts')
            ->where('tenant_id', 2)
            ->count();
        $this->assertEquals(2, $tenant2Count);
    }

    public function testModelDeleteFiltersByTenant()
    {
        session()->set('tenant.id', 1);
        
        $post = Post::query()->one();
        $postId = $post->id;
        
        $post->delete();
        
        // Verify deleted
        $found = $this->db->table('posts')
            ->where('id', $postId)
            ->one();
        $this->assertNull($found);
        
        // Verify tenant 2 posts still exist
        $tenant2Count = $this->db->table('posts')
            ->where('tenant_id', 2)
            ->count();
        $this->assertEquals(2, $tenant2Count);
    }

    // ==================== NO TENANT CONTEXT TESTS ====================

    public function testNoTenantContextReturnsAllRecords()
    {
        // No tenant set
        session()->forget('tenant.id');
        
        $posts = Post::query()->all();
        
        // Should return all posts from all tenants
        $this->assertCount(4, $posts);
    }

    public function testNoTenantContextDoesNotSetTenantOnSave()
    {
        session()->forget('tenant.id');
        
        $post = new Post();
        $post->title = 'No Tenant Post';
        $post->content = 'Content';
        $post->tenant_id = 99;  // Must set manually
        $post->save();
        
        $this->assertEquals(99, $post->tenant_id);
    }

    // ==================== BYPASS SCOPE TESTS ====================

    public function testQueryWithoutScopesBypassesTenantFilter()
    {
        session()->set('tenant.id', 1);
        
        $allPosts = Post::queryWithoutScopes()->all();
        
        // Should return all posts from all tenants
        $this->assertCount(4, $allPosts);
    }

    public function testQueryWithoutScopesAllowsCrossTenantOperations()
    {
        session()->set('tenant.id', 1);
        
        // Get a post from tenant 2 using queryWithoutScopes
        $tenant2Post = Post::queryWithoutScopes()
            ->where('tenant_id', 2)
            ->one();
        
        $this->assertEquals(2, $tenant2Post->tenant_id);
        
        // To move between tenants, use raw query or table() method
        // because Model's update() applies globalScope which would prevent the update
        $this->db->table('posts')
            ->where('id', $tenant2Post->id)
            ->update(['tenant_id' => 1]);
        
        // Verify it moved
        $found = $this->db->table('posts')
            ->where('id', $tenant2Post->id)
            ->one();
        $this->assertEquals(1, $found->tenant_id);
    }
}
