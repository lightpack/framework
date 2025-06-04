<?php

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Collection;
use Lightpack\Database\Lucid\Model;
use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\Logger\Drivers\NullLogger;
use Lightpack\Logger\Logger;
use Lightpack\Taxonomies\Taxonomy;
use Lightpack\Taxonomies\TaxonomyTrait;

class TaxonomiesIntegrationTest extends TestCase
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
        $container->register('logger', fn() => new Logger(new NullLogger));

        // Create tables
        $this->schema = new Schema($this->db);
        $this->schema->createTable('posts', function (Table $table) {
            $table->id();
            $table->varchar('title');
            $table->timestamps();
        });
        $this->schema->createTable('taxonomies', function (Table $table) {
            $table->id();
            $table->varchar('name');
            $table->varchar('slug');
            $table->varchar('type');
            $table->column('parent_id')->type('bigint')->attribute('unsigned')->nullable();
            $table->column('sort_order')->type('integer')->default(0);
            $table->timestamps();
        });
        $this->schema->createTable('taxonomy_models', function (Table $table) {
            $table->column('taxonomy_id')->type('bigint')->attribute('unsigned');
            $table->column('model_id')->type('bigint')->attribute('unsigned');
            $table->varchar('model_type', 191);
            $table->primary(['taxonomy_id', 'model_id', 'model_type']);
        });
    }

    protected function tearDown(): void
    {
        $this->schema->dropTable('taxonomy_models');
        $this->schema->dropTable('taxonomies');
        $this->schema->dropTable('posts');
        $this->db = null;
    }

    protected function getPostModelInstance()
    {
        return new class extends Model {
            use TaxonomyTrait;
            protected $table = 'posts';
            protected $primaryKey = 'id';
            public $timestamps = true;
        };
    }

    protected function getTaxonomyModelInstance()
    {
        return new class extends Taxonomy {};
    }

    protected function seedTaxonomiesData()
    {
        // Insert taxonomies
        $this->db->table('taxonomies')->insert([
            ['id' => 1, 'name' => 'News', 'slug' => 'news', 'type' => 'category'],
            ['id' => 2, 'name' => 'Featured', 'slug' => 'featured', 'type' => 'category'],
            ['id' => 3, 'name' => 'Opinion', 'slug' => 'opinion', 'type' => 'category'],
        ]);
        // Insert posts
        $this->db->table('posts')->insert([
            ['id' => 101, 'title' => 'First Post'],
            ['id' => 102, 'title' => 'Second Post'],
            ['id' => 103, 'title' => 'Third Post'],
        ]);
        // Attach taxonomies to posts
        $this->db->table('taxonomy_models')->insert([
            ['taxonomy_id' => 1, 'model_id' => 101, 'model_type' => 'posts'],
            ['taxonomy_id' => 2, 'model_id' => 101, 'model_type' => 'posts'],
            ['taxonomy_id' => 2, 'model_id' => 102, 'model_type' => 'posts'],
            ['taxonomy_id' => 3, 'model_id' => 103, 'model_type' => 'posts'],
        ]);
    }



    protected function seedTaxonomyTree()
    {
        // Build forest: (1 -> 2,3) ; (2 -> 4,5) ; (3 -> 6) ; (10 -> 11) ; (20)
        $this->db->table('taxonomies')->insert([
            ['id' => 1, 'name' => 'Root 1', 'slug' => 'root-1', 'type' => 'category', 'parent_id' => null],
            ['id' => 2, 'name' => 'Child 1-1', 'slug' => 'child-1-1', 'type' => 'category', 'parent_id' => 1],
            ['id' => 3, 'name' => 'Child 1-2', 'slug' => 'child-1-2', 'type' => 'category', 'parent_id' => 1],
            ['id' => 4, 'name' => 'Grandchild 1-1-1', 'slug' => 'grandchild-1-1-1', 'type' => 'category', 'parent_id' => 2],
            ['id' => 5, 'name' => 'Grandchild 1-1-2', 'slug' => 'grandchild-1-1-2', 'type' => 'category', 'parent_id' => 2],
            ['id' => 6, 'name' => 'Grandchild 1-2-1', 'slug' => 'grandchild-1-2-1', 'type' => 'category', 'parent_id' => 3],
            ['id' => 10, 'name' => 'Root 2', 'slug' => 'root-2', 'type' => 'category', 'parent_id' => null],
            ['id' => 11, 'name' => 'Child 2-1', 'slug' => 'child-2-1', 'type' => 'category', 'parent_id' => 10],
            ['id' => 20, 'name' => 'Root 3', 'slug' => 'root-3', 'type' => 'category', 'parent_id' => null],
        ]);
    }

    public function testAttachAndDetachTaxonomy()
    {
        $this->seedTaxonomiesData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->detachTaxonomies([1]);
        $this->assertCount(1, $post->taxonomies()->all());
        $post->attachTaxonomies([1]);
        $this->assertCount(2, $post->taxonomies()->all());
    }

    public function testSyncTaxonomies()
    {
        $this->seedTaxonomiesData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->syncTaxonomies([3]);
        $taxonomyIds = array_column($post->taxonomies()->all()->toArray(), 'id');
        $this->assertEquals([3], $taxonomyIds);
    }

    public function testDetachNonExistentTaxonomy()
    {
        $this->seedTaxonomiesData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->detachTaxonomies([9999]); // Should not error
        $this->assertCount(2, $post->taxonomies()->all());
    }

    public function testAttachDuplicateTaxonomy()
    {
        $this->seedTaxonomiesData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->attachTaxonomies([1]);
        $this->assertCount(2, $post->taxonomies()->all());
    }

    public function testBulkAttachAndDetachTaxonomies()
    {
        $this->seedTaxonomiesData();
        $post = $this->getPostModelInstance();
        $post->find(102);
        $post->attachTaxonomies([1, 3]);
        $taxonomyIds = array_column($post->taxonomies()->all()->toArray(), 'id');
        $this->assertContains(1, $taxonomyIds);
        $this->assertContains(2, $taxonomyIds);
        $this->assertContains(3, $taxonomyIds);
        $post->detachTaxonomies([1, 2]);
        $taxonomyIds = array_column($post->taxonomies()->all()->toArray(), 'id');
        $this->assertNotContains(1, $taxonomyIds);
        $this->assertNotContains(2, $taxonomyIds);
        $this->assertContains(3, $taxonomyIds);
    }

    public function testBulkSyncTaxonomies()
    {
        $this->seedTaxonomiesData();
        $post = $this->getPostModelInstance();
        $post->find(102);
        $post->syncTaxonomies([1, 3]);
        $taxonomyIds = array_column($post->taxonomies()->all()->toArray(), 'id');
        $this->assertContains(1, $taxonomyIds);
        $this->assertContains(3, $taxonomyIds);
        $this->assertNotContains(2, $taxonomyIds);
    }

    public function testTaxonomyTypeIsolation()
    {
        $this->seedTaxonomiesData();
        // Add a different model_type
        $this->db->table('posts')->insert([
            'id' => 201,
            'title' => 'Fake Post for Isolation',
        ]);
        $this->db->table('taxonomy_models')->insert([
            ['taxonomy_id' => 1, 'model_id' => 201, 'model_type' => 'other_model'],
        ]);
        $post = $this->getPostModelInstance();
        $post->find(201);
        $this->assertCount(0, $post->taxonomies()->all());
    }

    public function testScopeTaxonomiesHierarchicalFiltering()
    {
        // Seed taxonomy tree: 1 -> 2 -> 3 -> 4
        $this->db->table('taxonomies')->insert([
            ['id' => 1, 'name' => 'Root', 'slug' => 'root', 'type' => 'category', 'parent_id' => null],
            ['id' => 2, 'name' => 'Level 2', 'slug' => 'level2', 'type' => 'category', 'parent_id' => 1],
            ['id' => 3, 'name' => 'Level 3', 'slug' => 'level3', 'type' => 'category', 'parent_id' => 2],
            ['id' => 4, 'name' => 'Level 4', 'slug' => 'level4', 'type' => 'category', 'parent_id' => 3],
        ]);
        $this->db->table('posts')->insert([
            ['id' => 301, 'title' => 'Deep Post'],
        ]);
        $this->db->table('taxonomy_models')->insert([
            ['taxonomy_id' => 4, 'model_id' => 301, 'model_type' => 'posts'], // Assigned at level 4
        ]);
        $postModel = $this->getPostModelInstance();
        $taxonomy = new Taxonomy(2); // Level 2
        // Get all descendant IDs (including itself)
        $idsToFilter = array_merge([$taxonomy->id], $taxonomy->descendants()->ids());
        $posts = $postModel::filters(['taxonomies' => $idsToFilter])->all();
        $postIds = array_column($posts->toArray(), 'id');
        $this->assertContains(301, $postIds);
    }

    public function testScopeTaxonomiesFiltersBySingleTaxonomy()
    {
        $this->seedTaxonomiesData();
        $postModel = $this->getPostModelInstance();
        $posts = $postModel::filters(['taxonomies' => [1]])->all();
        $postIds = array_column($posts->toArray(), 'id');
        $this->assertContains(101, $postIds);
        $this->assertNotContains(102, $postIds);
        $this->assertNotContains(103, $postIds);
    }

    public function testScopeTaxonomiesFiltersByMultipleTaxonomies()
    {
        $this->seedTaxonomiesData();
        $postModel = $this->getPostModelInstance();
        $posts = $postModel::filters(['taxonomies' => [2, 3]])->all();
        $postIds = array_column($posts->toArray(), 'id');
        $this->assertContains(101, $postIds);
        $this->assertContains(102, $postIds);
        $this->assertContains(103, $postIds);
    }

    public function testScopeTaxonomiesPolymorphicIsolation()
    {
        $this->seedTaxonomiesData();
        // Insert taxonomy for a different model_type
        $this->db->table('taxonomy_models')->insert([
            ['taxonomy_id' => 1, 'model_id' => 999, 'model_type' => 'other_model'],
        ]);
        $postModel = $this->getPostModelInstance();
        $posts = $postModel::filters(['taxonomies' => [1]])->all();
        $postIds = array_column($posts->toArray(), 'id');
        $this->assertNotContains(999, $postIds);
    }

    public function testTaxonomyDescendants()
    {
        // See seedTaxonomyTree()
        $this->seedTaxonomyTree();
        $root = new Taxonomy(1);
        $child1 = new Taxonomy(2);
        $child2 = new Taxonomy(3);
        $grandchild1 = new Taxonomy(4);
        $grandchild2 = new Taxonomy(5);
        $grandchild3 = new Taxonomy(6);

        $this->assertEquals([2, 4, 5, 3, 6], $root->descendants()->ids());
        $this->assertEquals([4, 5], $child1->descendants()->ids());
        $this->assertEquals([6], $child2->descendants()->ids());
        $this->assertEquals([], $grandchild1->descendants()->ids());
    }

    public function testTaxonomyAncestors()
    {
        $this->seedTaxonomyTree();
        $root = new Taxonomy(1);
        $grandchild1 = new Taxonomy(4);
        $grandchild3 = new Taxonomy(6);

        $this->assertEquals([], $root->ancestors()->ids());
        $this->assertEquals([1, 2], $grandchild1->ancestors()->ids());
        $this->assertEquals([1, 3], $grandchild3->ancestors()->ids());
    }

    public function testTaxonomyTree()
    {
        $this->seedTaxonomyTree();
        $root = new Taxonomy(1);
        $tree = $root->tree();
        $this->assertEquals(1, $tree['id']);
        $this->assertCount(2, $tree['children']);
        $this->assertEquals(2, $tree['children'][0]['id']);
        $this->assertEquals(3, $tree['children'][1]['id']);
        $this->assertCount(2, $tree['children'][0]['children']);
        $this->assertEquals(4, $tree['children'][0]['children'][0]['id']);
        $this->assertEquals(5, $tree['children'][0]['children'][1]['id']);
        $this->assertEquals(6, $tree['children'][1]['children'][0]['id']);
    }

    public function testTaxonomySiblings()
    {
        $this->seedTaxonomyTree();
        $root = new Taxonomy(1);
        $child1 = new Taxonomy(2);
        $child2 = new Taxonomy(3);
        $grandchild1 = new Taxonomy(4);
        $grandchild2 = new Taxonomy(5);
        $grandchild3 = new Taxonomy(6);

        // Root node: siblings are all other roots (10, 20)
        $this->assertEqualsCanonicalizing([10, 20], $root->siblings()->ids());

        // Siblings for child1 and child2 (should be each other)
        $this->assertEquals([3], $child1->siblings()->ids());
        $this->assertEquals([2], $child2->siblings()->ids());

        // Siblings for grandchild1 and grandchild2 (should be each other)
        $this->assertEquals([5], $grandchild1->siblings()->ids());
        $this->assertEquals([4], $grandchild2->siblings()->ids());

        // Grandchild3 is only child under child2
        $this->assertEquals([], $grandchild3->siblings()->ids());
    }

    public function testRootsReturnsAllRootNodes()
    {
        $this->seedTaxonomyTree();
        $roots = Taxonomy::roots();
        $rootIds = $roots->ids();
        // Expecting 3 roots: 1, 10, 20
        $this->assertEqualsCanonicalizing([1, 10, 20], $rootIds);
    }

    public function testForestReturnsAllTrees()
    {
        $this->seedTaxonomyTree();
        $forest = Taxonomy::forest();
        $this->assertCount(3, $forest); // Three roots: 1, 10, 20

        // Root 1
        $root1 = $forest[0];
        $this->assertEquals('Root 1', $root1['name']);
        $this->assertCount(2, $root1['children']);
        $this->assertEquals('Child 1-1', $root1['children'][0]['name']);
        $this->assertEquals('Child 1-2', $root1['children'][1]['name']);
        $this->assertEquals('Grandchild 1-1-1', $root1['children'][0]['children'][0]['name']);
        $this->assertEquals('Grandchild 1-1-2', $root1['children'][0]['children'][1]['name']);
        $this->assertEquals('Grandchild 1-2-1', $root1['children'][1]['children'][0]['name']);

        // Root 2
        $root2 = $forest[1];
        $this->assertEquals('Root 2', $root2['name']);
        $this->assertCount(1, $root2['children']);
        $this->assertEquals('Child 2-1', $root2['children'][0]['name']);
        $this->assertArrayNotHasKey('children', $root2['children'][0]);

        // Root 3
        $root3 = $forest[2];
        $this->assertEquals('Root 3', $root3['name']);
        $this->assertArrayNotHasKey('children', $root3);
    }

    public function testForestHandlesEmptyState()
    {
        $forest = Taxonomy::forest();
        $this->assertIsArray($forest);
        $this->assertCount(0, $forest);
    }

    public function testRootsHandlesEmptyState()
    {
        $roots = Taxonomy::roots();
        $this->assertInstanceOf(Collection::class, $roots);
        $this->assertCount(0, $roots);
    }

    public function testForestWithSingleNode()
    {
        $this->db->table('taxonomies')->insert([
            ['id' => 1, 'name' => 'Lonely Root', 'slug' => 'lonely', 'type' => 'category', 'parent_id' => null],
        ]);
        $forest = Taxonomy::forest();
        $this->assertCount(1, $forest);
        $this->assertEquals('Lonely Root', $forest[0]['name']);
        $this->assertArrayNotHasKey('children', $forest[0]);
    }

    public function testRootsAfterDeletingRoot()
    {
        $this->db->table('taxonomies')->insert([
            ['id' => 1, 'name' => 'Root', 'slug' => 'root', 'type' => 'category', 'parent_id' => null],
            ['id' => 2, 'name' => 'Child', 'slug' => 'child', 'type' => 'category', 'parent_id' => 1],
        ]);
        // Delete root
        $this->db->table('taxonomies')->where('id', 1)->delete();
        $roots = Taxonomy::roots();
        $this->assertCount(0, $roots);
    }

    public function testChildrenAndSiblingsAreOrderedBySortOrder()
    {
        $this->seedTaxonomyTree();
        // Reorder children of root 1: child-1-2 (id 3) before child-1-1 (id 2)
        Taxonomy::reorder([
            2 => 2, // child-1-1
            3 => 1, // child-1-2
        ]);
        $root = new Taxonomy(1);
        $children = $root->children->ids();
        $this->assertEquals([3, 2], $children); // child-1-2, child-1-1
        // Siblings of child-1-1 (id 2) should also be ordered
        $child1 = new Taxonomy(2);
        $siblings = $child1->siblings()->ids();
        $this->assertEquals([3], $siblings);
    }

    public function testMoveToChangesParent()
    {
        $this->seedTaxonomyTree();
        $child = new Taxonomy(2);
        $child->moveTo(10); // Move child-1-1 under root 2
        $this->assertEquals(10, (new Taxonomy(2))->parent_id);
        $root2 = new Taxonomy(10);
        $children = $root2->children->ids();
        $this->assertContains(2, $children);
    }

    public function testBulkUpdateOrder()
    {
        $this->seedTaxonomyTree();
        // Set custom order for roots
        Taxonomy::reorder([
            1 => 2,
            10 => 1,
            20 => 3,
        ]);
        $roots = Taxonomy::roots();
        $this->assertEqualsCanonicalizing([1, 10, 20], $roots->ids());
    }
}

