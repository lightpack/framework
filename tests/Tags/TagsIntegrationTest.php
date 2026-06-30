<?php

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Lucid\TenantContext;
use Lightpack\Database\Lucid\TenantModel;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\Logger\Drivers\NullLogger;
use Lightpack\Logger\Logger;
use Lightpack\Tags\Tag;
use Lightpack\Tags\TagsTrait;
use Lightpack\Tags\TenantTag;
use PHPUnit\Framework\TestCase;

class TagsIntegrationTest extends TestCase
{
    private $db;
    private $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);

        // configure container
        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });
        $container->register('logger', fn () => new Logger(new NullLogger));

        // Create tables
        $this->schema = new Schema($this->db);
        $this->schema->createTable('posts', function (Table $table) {
            $table->id();
            $table->column('tenant_id')->type('bigint')->attribute('unsigned')->default(0);
            $table->varchar('title');
            $table->timestamps();
        });
        $this->schema->createTable('tags', function (Table $table) {
            $table->id();
            $table->column('tenant_id')->type('bigint')->attribute('unsigned')->default(0);
            $table->varchar('name');
            $table->varchar('slug');
            $table->timestamps();
        });
        $this->schema->createTable('tag_morphs', function (Table $table) {
            $table->column('tag_id')->type('bigint')->attribute('unsigned');
            $table->column('morph_id')->type('bigint')->attribute('unsigned');
            $table->varchar('morph_type', 191);
            $table->primary(['tag_id', 'morph_id', 'morph_type']);
        });
    }

    protected function tearDown(): void
    {
        $this->schema->dropTable('tag_morphs');
        $this->schema->dropTable('tags');
        $this->schema->dropTable('posts');
        $this->db = null;
    }

    protected function getPostModelInstance()
    {
        return new class extends Model {
            use TagsTrait;
            protected $table = 'posts';
            protected $primaryKey = 'id';
            protected $timestamps = true;
        };
    }

    protected function getTagModelInstance()
    {
        return new class extends Tag {};
    }

    protected function seedTagsData()
    {
        // Insert tags
        $this->db->table('tags')->insert([
            ['id' => 1, 'name' => 'news', 'slug' => 'news'],
            ['id' => 2, 'name' => 'featured', 'slug' => 'featured'],
            ['id' => 3, 'name' => 'opinion', 'slug' => 'opinion'],
        ]);
        // Insert posts
        $this->db->table('posts')->insert([
            ['id' => 101, 'title' => 'First Post'],
            ['id' => 102, 'title' => 'Second Post'],
            ['id' => 103, 'title' => 'Third Post'],
        ]);
        // Tag posts
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => 1, 'morph_id' => 101, 'morph_type' => 'posts'],
            ['tag_id' => 2, 'morph_id' => 101, 'morph_type' => 'posts'],
            ['tag_id' => 2, 'morph_id' => 102, 'morph_type' => 'posts'],
            ['tag_id' => 3, 'morph_id' => 103, 'morph_type' => 'posts'],
        ]);
    }

    public function testAttachAndDetachTag()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->tags()->detach([1]);
        $post = $this->getPostModelInstance()->find(101); // Re-fetch to avoid cache
        $this->assertCount(1, $post->tags);
        $post->tags()->attach([1]);
        $post = $this->getPostModelInstance()->find(101); // Re-fetch to avoid cache
        $this->assertCount(2, $post->tags);
    }

    public function testSyncTags()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->tags()->sync([3]);
        $post = $this->getPostModelInstance()->find(101);
        $tagIds = array_column($post->tags->toArray(), 'id');
        $this->assertEquals([3], $tagIds);
    }

    public function testHasTagByIdAndSlug()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $tagIds = array_column($post->tags->toArray(), 'id');
        $this->assertContains(1, $tagIds);
        $this->assertContains(2, $tagIds);
    }

    public function testDetachNonExistentTag()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->tags()->detach([9999]); // Should not error
        $post = $this->getPostModelInstance()->find(101);
        $this->assertCount(2, $post->tags);
    }

    public function testAttachDuplicateTag()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->tags()->attach([1]);
        $post = $this->getPostModelInstance()->find(101);
        $this->assertCount(2, $post->tags);
    }

    public function testBulkAttachAndDetachTags()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(102);
        $post->tags()->attach([1, 3]);
        $post = $this->getPostModelInstance()->find(102);
        $tagIds = array_column($post->tags->toArray(), 'id');
        $this->assertContains(1, $tagIds);
        $this->assertContains(2, $tagIds);
        $this->assertContains(3, $tagIds);
        $post->tags()->detach([1, 2]);
        $post = $this->getPostModelInstance()->find(102);
        $tagIds = array_column($post->tags->toArray(), 'id');
        $this->assertNotContains(1, $tagIds);
        $this->assertNotContains(2, $tagIds);
        $this->assertContains(3, $tagIds);
    }

    public function testFilterPostsByTagId()
    {
        $this->seedTagsData();
        $posts = $this->getPostModelInstance()::filters(['tags' => [2]])->all();
        $postIds = $posts->column('id');
        $this->assertContains(101, $postIds);
        $this->assertContains(102, $postIds);
        $this->assertNotContains(103, $postIds);
    }

    public function testFilterPostsByMultipleTags()
    {
        $this->seedTagsData();
        $posts = $this->getPostModelInstance()::filters(['tags' => [1,2]])->all();
        $postIds = $posts->column('id');
        $this->assertContains(101, $postIds);
        $this->assertContains(102, $postIds);
        $this->assertNotContains(103, $postIds);
    }

    public function testFilterPostsByNonExistentTag()
    {
        $this->seedTagsData();
        $posts = $this->getPostModelInstance()::filters(['tags' => [9999]])->all();
        $this->assertEmpty($posts);
    }

    public function testBulkSyncTags()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(102);
        $post->tags()->sync([1, 3]);
        $post = $this->getPostModelInstance()->find(102);
        $tagIds = array_column($post->tags->toArray(), 'id');
        $this->assertContains(1, $tagIds);
        $this->assertContains(3, $tagIds);
        $this->assertNotContains(2, $tagIds);
    }

    public function testTaggableTypeIsolation()
    {
        $this->seedTagsData();
        // Add a different taggable type
        $this->db->table('posts')->insert([
            'id' => 201,
            'title' => 'Fake Post for Isolation',
        ]);
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => 1, 'morph_id' => 201, 'morph_type' => 'other_model'],
        ]);
        $post = $this->getPostModelInstance();
        $post->find(201);
        $this->assertCount(0, $post->tags);
    }

    public function testFilterPostsByMultipleTagsReturnsNoDuplicates()
    {
        $this->seedTagsData();

        // Post 101 has both tags 1 and 2
        // Without deduplication, it would appear twice in results (once per tag)
        $posts = $this->getPostModelInstance()::filters(['tags' => [1,2]])->all();

        // Count how many times post 101 appears in the result set
        $post101Count = 0;
        foreach ($posts as $post) {
            if ($post->id == 101) {
                $post101Count++;
            }
        }

        // Should appear exactly once, not twice
        $this->assertEquals(1, $post101Count, 'Post 101 should appear exactly once, not duplicated');

        // Total should be 2 unique posts (101 and 102), not more due to duplicates
        $this->assertCount(2, $posts, 'Should return 2 unique posts, not duplicates');
    }

    public function testFilterPostsWithCustomColumnSelection()
    {
        $this->seedTagsData();

        // User specifies custom columns with tag filter
        // This tests that GROUP BY works with custom column selection
        $builder = $this->getPostModelInstance()::filters(['tags' => [1,2]]);
        $builder->select('posts.id', 'posts.title');
        $posts = $builder->all();

        // Should still return unique posts without duplicates
        $this->assertCount(2, $posts, 'Should return 2 unique posts with custom columns');

        // Verify the selected columns are accessible
        $firstPost = $posts->first();
        $this->assertNotNull($firstPost->id);
        $this->assertNotNull($firstPost->title);

        // Verify no duplicates - post 101 should appear only once
        $postIds = $posts->column('id');
        $this->assertEquals(2, count($postIds), 'Should have 2 posts');
        $this->assertEquals(2, count(array_unique($postIds)), 'All post IDs should be unique');
    }

    public function testTagModelHookReturnsCorrectModel()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $pivot = $post->tags();
        $this->assertInstanceOf(Tag::class, $pivot->getModel());
    }

    public function testTagAutoDetectUsesTenantTagForTenantModel()
    {
        $this->seedTagsData();

        // TenantModel using TagsTrait WITHOUT overriding getTagModel()
        $post = new class extends TenantModel {
            use TagsTrait;
            protected $table = 'posts';
            protected $tenantColumn = 'tenant_id';
            protected $primaryKey = 'id';
            protected $timestamps = true;
        };
        $post->find(101);

        // Should auto-detect and use TenantTag
        $pivot = $post->tags();
        $this->assertInstanceOf(TenantTag::class, $pivot->getModel());
    }

    public function testTagTenantIsolation()
    {
        // Seed tags for two different tenants
        $this->db->table('tags')->insert([
            ['id' => 1, 'tenant_id' => 1, 'name' => 'News', 'slug' => 'news'],
            ['id' => 2, 'tenant_id' => 1, 'name' => 'Featured', 'slug' => 'featured'],
            ['id' => 3, 'tenant_id' => 2, 'name' => 'Opinion', 'slug' => 'opinion'],
        ]);

        // Create a tenant-scoped tag model
        $appTag = new class extends TenantModel {
            protected $table = 'tags';
            protected $tenantColumn = 'tenant_id';
            protected $primaryKey = 'id';
        };

        // Tenant 1 should see only 2 tags
        TenantContext::set(1);
        $tags = $appTag::query()->all();
        $this->assertCount(2, $tags);
        $tagIds = array_column($tags->toArray(), 'id');
        $this->assertContains(1, $tagIds);
        $this->assertContains(2, $tagIds);
        $this->assertNotContains(3, $tagIds);

        // Tenant 2 should see only 1 tag
        TenantContext::set(2);
        $tags = $appTag::query()->all();
        $this->assertCount(1, $tags);
        $this->assertEquals(3, $tags->first()->id);

        // No tenant context should see all 3 tags (null tenant_id scope = no filter)
        TenantContext::clear();
        $tags = $appTag::query()->all();
        $this->assertCount(3, $tags);
    }

    public function testTagFilterWithTenantIsolation()
    {
        // Seed tenant-scoped tags
        $this->db->table('tags')->insert([
            ['id' => 1, 'tenant_id' => 1, 'name' => 'News', 'slug' => 'news'],
            ['id' => 2, 'tenant_id' => 1, 'name' => 'Featured', 'slug' => 'featured'],
            ['id' => 3, 'tenant_id' => 2, 'name' => 'News', 'slug' => 'news'],
        ]);

        // Seed posts with tenant_id
        $this->db->table('posts')->insert([
            ['id' => 101, 'tenant_id' => 1, 'title' => 'Post 1'],
            ['id' => 102, 'tenant_id' => 2, 'title' => 'Post 2'],
        ]);

        // Tag posts: tenant 1 post has tags 1,2; tenant 2 post has tag 3
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => 1, 'morph_id' => 101, 'morph_type' => 'posts'],
            ['tag_id' => 2, 'morph_id' => 101, 'morph_type' => 'posts'],
            ['tag_id' => 3, 'morph_id' => 102, 'morph_type' => 'posts'],
        ]);

        // Create a tenant-scoped tag model and capture its class name
        $appTag = new class extends TenantModel {
            protected $table = 'tags';
            protected $tenantColumn = 'tenant_id';
            protected $primaryKey = 'id';
        };
        $tagClassName = get_class($appTag);

        // Create tenant-scoped post model using the tag class name
        $postModel = new class ($tagClassName) extends TenantModel {
            use TagsTrait;
            protected $table = 'posts';
            protected $tenantColumn = 'tenant_id';
            protected $primaryKey = 'id';
            protected $timestamps = true;
            protected static $tagClassName;

            public function __construct($tagClassName = null)
            {
                if ($tagClassName !== null) {
                    self::$tagClassName = $tagClassName;
                }
                parent::__construct();
            }

            protected function getTagModel(): string
            {
                return self::$tagClassName;
            }
        };

        // Tenant 1 filters by tags: should only see post 101
        TenantContext::set(1);
        $posts = $postModel::filters(['tags' => [1, 2]])->all();
        $this->assertCount(1, $posts);
        $this->assertEquals(101, $posts->first()->id);

        // Tenant 2 filters by tag 3: should only see post 102
        TenantContext::set(2);
        $posts = $postModel::filters(['tags' => [3]])->all();
        $this->assertCount(1, $posts);
        $this->assertEquals(102, $posts->first()->id);

        // Reset
        TenantContext::clear();
    }

    public function testCrossTenantTagPivotDataDoesNotLeak()
    {
        // Seed tenant-scoped tags
        $this->db->table('tags')->insert([
            ['id' => 1, 'tenant_id' => 1, 'name' => 'Tenant1Tag', 'slug' => 't1-tag'],
            ['id' => 2, 'tenant_id' => 2, 'name' => 'Tenant2Tag', 'slug' => 't2-tag'],
        ]);

        // Seed posts with tenant_id
        $this->db->table('posts')->insert([
            ['id' => 101, 'tenant_id' => 1, 'title' => 'Post 1'],
        ]);

        // Normal: tenant 1 post tagged with tenant 1 tag
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => 1, 'morph_id' => 101, 'morph_type' => 'posts'],
        ]);

        // CORRUPT: manually link tenant 2's tag to tenant 1's post
        // This simulates a bug or bypass in application logic
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => 2, 'morph_id' => 101, 'morph_type' => 'posts'],
        ]);

        // Create tenant-scoped tag model and capture class name
        $appTag = new class extends TenantModel {
            protected $table = 'tags';
            protected $tenantColumn = 'tenant_id';
            protected $primaryKey = 'id';
            protected $timestamps = true;
        };
        $tagClassName = get_class($appTag);

        // Load post 101 as tenant 1 using tenant-scoped tag model
        TenantContext::set(1);
        $post = new class ($tagClassName) extends TenantModel {
            use TagsTrait;
            protected $table = 'posts';
            protected $tenantColumn = 'tenant_id';
            protected $primaryKey = 'id';
            protected $timestamps = true;
            protected static $tagClassName;

            public function __construct($tagClassName = null)
            {
                if ($tagClassName !== null) {
                    self::$tagClassName = $tagClassName;
                }
                parent::__construct();
            }

            protected function getTagModel(): string
            {
                return self::$tagClassName;
            }
        };
        $post->find(101);
        $tags = $post->tags;

        // Even though the pivot table has a cross-tenant link,
        // we should NOT see tenant 2's tag data
        $tagNames = array_column($tags->toArray(), 'name');
        $this->assertContains('Tenant1Tag', $tagNames);
        $this->assertNotContains(
            'Tenant2Tag',
            $tagNames,
            'Cross-tenant tag data leaked through relationship'
        );

        TenantContext::clear();
    }
}
