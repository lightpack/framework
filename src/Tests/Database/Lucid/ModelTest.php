<?php

require_once 'Option.php';
require_once 'Product.php';
require_once 'Role.php';
require_once 'User.php';
require_once 'Project.php';
require_once 'Task.php';
require_once 'Comment.php';
require_once 'Article.php';
require_once 'Manager.php';

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Collection;
use PHPUnit\Framework\TestCase;
use \Lightpack\Database\Lucid\Model;
use Lightpack\Database\Query\Query;
use Lightpack\Exceptions\RecordNotFoundException;
use Lightpack\Moment\Moment;

// Initalize container
$container = new Container();

final class ModelTest extends TestCase
{
    /** @var \Lightpack\Database\Pdo */
    private $db;

    /** @var \Lightpack\Database\Lucid\Model */
    private $product;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();
        $this->product = $this->db->model(Product::class);

        // Configure container
        global $container;
        $container->register('db', function () {
            return $this->db;
        });
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments, articles, managers";
        $this->db->query($sql);
        $this->db = null;
    }

    public function testModelInstance()
    {
        $this->assertInstanceOf(Model::class, $this->product);
    }

    public function testModelSaveInsertMethod()
    {
        $products = $this->db->table('products')->all();
        $productsCountBeforeSave = count($products);

        $this->product->name = 'Dummy Product';
        $this->product->color = '#CCC';
        $this->product->save();

        $products = $this->db->table('products')->all();
        $productsCountAfterSave = count($products);

        $this->assertEquals($productsCountBeforeSave + 1, $productsCountAfterSave);
    }

    public function testModelSaveUpdateMethod()
    {
        $product = $this->db->table('products')->one();
        $products = $this->db->table('products')->all();
        $productsCountBeforeSave = count($products);

        $this->product->find($product->id);
        $this->product->name = 'ACME Product';
        $this->product->save();

        $products = $this->db->table('products')->all();
        $productsCountAfterSave = count($products);

        $this->assertEquals($productsCountBeforeSave, $productsCountAfterSave);
    }

    public function testModelBulkInsertMethod()
    {
        $products = $this->db->table('products')->all();
        $productsCountBeforeSave = count($products);

        $this->product->query()->bulkInsert([
            ['name' => 'Dummy Product 1', 'color' => '#CCC'],
            ['name' => 'Dummy Product 2', 'color' => '#CCC'],
            ['name' => 'Dummy Product 3', 'color' => '#CCC'],
            ['name' => 'Dummy Product 4', 'color' => '#CCC'],
            ['name' => 'Dummy Product 5', 'color' => '#CCC'],
        ]);

        $products = $this->db->table('products')->all();
        $productsCountAfterSave = count($products);

        $this->assertEquals($productsCountBeforeSave + 5, $productsCountAfterSave);
    }

    public function testModelDeleteMethod()
    {
        $product = $this->db->table('products')->one();
        $products = $this->db->table('products')->all();
        $productsCountBeforeDelete = count($products);

        $this->product->find($product->id);
        $this->product->delete();

        $products = $this->db->table('products')->all();
        $productsCountAfterDelete = count($products);

        $this->assertEquals($productsCountBeforeDelete - 1, $productsCountAfterDelete);
    }

    public function testModelDeleteWithIdMethod()
    {
        $productsCountBeforeDelete = Product::query()->count();
        $product = Product::query()->one();
        (new Product)->delete($product->id);
        $productsCountAfterDelete = Product::query()->count();

        $this->assertEquals($productsCountBeforeDelete - 1, $productsCountAfterDelete);
    }

    public function testModelDeleteWhenIdNotSet()
    {
        $this->assertNull($this->product->delete());
    }

    public function testModelHasOneRelation()
    {
        $this->db->table('products')->insert(['name' => 'Dummy Product', 'color' => '#CCC']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $owner = $this->db->table('owners')->where('product_id', '=', $product->id)->one();

        if (!isset($owner->id)) {
            $this->db->table('owners')->insert(['product_id' => $product->id, 'name' => 'Bob']);
        }

        $this->product->find($product->id);
        $productOwner = $this->product->owner;

        // Assertions
        $this->assertNotNull($productOwner->id);
        $this->assertEquals($this->product->getRelationType(), 'hasOne');
        $this->assertEquals($this->product->getRelatingKey(), 'product_id');
        $this->assertEquals($this->product->getRelatingForeignKey(), 'product_id');
        $this->assertEquals($this->product->getRelatingModel(), Owner::class);
        $this->assertNull($this->product->getPivotTable());
    }

    public function testModelHasManyRelation()
    {
        $this->db->table('products')->insert(['name' => 'Dummy Product', 'color' => '#CCC']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $this->db->table('options')->insert(['product_id' => $product->id, 'name' => 'Size', 'value' => 'XL']);
        $this->db->table('options')->insert(['product_id' => $product->id, 'name' => 'Color', 'value' => '#000']);

        $this->product->find($product->id);
        $productOptions = $this->product->options;

        // Assertions
        $this->assertEquals(2, count($productOptions));
        $this->assertEquals($this->product->getRelationType(), 'hasMany');
        $this->assertEquals($this->product->getRelatingKey(), 'product_id');
        $this->assertEquals($this->product->getRelatingForeignKey(), 'id');
        $this->assertEquals($this->product->getRelatingModel(), Option::class);
        $this->assertNull($this->product->getPivotTable());
    }

    public function testModelBelongsToRelation()
    {
        $this->db->table('products')->insert(['name' => 'Dummy Product', 'color' => '#CCC']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $owner = $this->db->table('owners')->where('product_id', '=', $product->id)->one();

        if (!isset($owner->id)) {
            $this->db->table('owners')->insert(['product_id' => $product->id, 'name' => 'Bob']);
            $owner = $this->db->table('owners')->where('product_id', '=', $product->id)->one();
        }

        $ownerModel = $this->db->model(Owner::class);
        $ownerModel->find($owner->id);
        $ownerProduct = $ownerModel->product;

        $this->assertNotNull($ownerProduct->id);
    }

    public function testPivotMethod() // aka many-to-many relation
    {
        $this->db->table('users')->bulkInsert([
            ['name' => 'Bob'],
            ['name' => 'John'],
            ['name' => 'Jane'],
        ]);
        $this->db->table('roles')->bulkInsert([
            ['name' => 'admin'],
            ['name' => 'user'],
            ['name' => 'guest'],
        ]);
        $this->db->table('role_user')->bulkInsert([
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 1, 'role_id' => 2],
            ['user_id' => 2, 'role_id' => 2],
            ['user_id' => 3, 'role_id' => 3],
        ]);

        $user = $this->db->model(User::class);
        $user->find(1);
        $userRoles = $user->roles;

        $this->assertEquals(2, count($userRoles));
        $this->assertEquals($user->getRelationType(), 'pivot');
        $this->assertEquals($user->getRelatingKey(), 'user_id');
        $this->assertEquals($user->getRelatingForeignKey(), 'user_id');
        $this->assertEquals($user->getRelatingModel(), Role::class);
        $this->assertEquals($user->getPivotTable(), 'role_user');
    }

    public function testHasManyThrough()
    {
        // projects, tasks, and comments table will be used for tests of hasmanyThrough relation
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);
        $this->db->table('comments')->bulkInsert([
            ['task_id' => 1, 'content' => 'Comment 1'],
            ['task_id' => 2, 'content' => 'Comment 2'],
            ['task_id' => 2, 'content' => 'Comment 3'],
        ]);

        // projects have many tasks, and tasks have many comments
        // so we will test projects have many comments through tasks
        $project = $this->db->model(Project::class);
        $project->find(2);
        $projectComments = $project->comments;

        $this->assertEquals(2, count($projectComments));
        $this->assertEquals($project->getRelationType(), 'hasManyThrough');
        $this->assertEquals($project->getRelatingKey(), 'project_id');
        $this->assertEquals($project->getRelatingForeignKey(), 'project_id');
        $this->assertEquals($project->getRelatingModel(), Comment::class);
        $this->assertNull($project->getPivotTable());
    }

    public function testModelCollectionToArray()
    {
        $this->db->table('users')->bulkInsert([
            ['name' => 'Bob'],
            ['name' => 'John'],
            ['name' => 'Jane'],
        ]);

        $users = $this->db->model(User::class)->query()->all();

        $this->assertEquals(3, count($users));
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertIsArray($users->toArray());
    }

    public function testLoadMethod()
    {
        // test eager load relationship after parent model has been created
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        $project = $this->db->model(Project::class);
        $project->find(2);
        $project->load('tasks');

        $this->assertEquals(2, count($project->tasks));
        $this->assertInstanceOf(Collection::class, $project->tasks);
        $this->assertIsArray($project->tasks->toArray());
    }

    public function testLoadCountMethod()
    {
        // test eager load relationship after parent model has been created
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        $project = $this->db->model(Project::class);
        $project->find(2);
        $project->loadCount('tasks');

        $this->assertEquals(2, $project->tasks_count);
    }

    public function testLoadMethodWithConstraint()
    {
        // test eager load relationship after parent model has been created
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        $project = $this->db->model(Project::class);
        $project->find(2);
        $project->load(['tasks' => function($q) {
            $q->where('name', 'LIKE', '%Task%');
        }]);

       // Assertions
        $this->assertEquals(2, count($project->tasks));
        $this->assertInstanceOf(Collection::class, $project->tasks);
        $this->assertIsArray($project->tasks->toArray());
    }

    public function testLoadCountMethodWithConstraint()
    {
        // test eager load relationship after parent model has been created
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        $project = $this->db->model(Project::class);
        $project->find(2);
        $project->loadCount(['tasks' => function($q) {
            $q->where('name', 'LIKE', '%Task%');
        }]);

        // Assertions
        $this->assertEquals(2, $project->tasks_count);
    }

    public function testLastInsertId()
    {
        $product = new Product();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->save();
        $lastInsertId = $product->lastInsertId();

        // Fetch the latest product
        $product = Product::query()->orderBy('id', 'DESC')->one();
        $this->assertTrue($product->id == $lastInsertId);
    }

    public function testModelToArrayMethod()
    {
        $product = product::query()->one();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->save();
        $productArray = $product->toArray();

        $this->assertTrue(is_array($productArray));
        $this->assertTrue(isset($productArray['id']));
        $this->assertTrue(isset($productArray['name']));
        $this->assertTrue(isset($productArray['color']));
        $this->assertTrue($productArray['id'] == $product->id);
        $this->assertTrue($productArray['name'] == $product->name);
        $this->assertTrue($productArray['color'] == $product->color);
    }

    public function testModelSetAttribute()
    {
        $product = new Product();
        $product->setAttribute('name', 'Dummy Product');
        $product->setAttribute('color', '#CCC');
        $product->save();

        $product = Product::query()->orderBy('id', 'DESC')->one();

        $this->assertEquals('Dummy Product', $product->name);
        $this->assertEquals('#CCC', $product->color);
    }

    public function testModelGetAttributeMethod()
    {
        $product = product::query()->one();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->save();

        $this->assertEquals($product->name, $product->getAttribute('name'));
        $this->assertEquals($product->color, $product->getAttribute('color'));
        $this->assertEquals($product->name, $product->getAttribute('name', 'default'));
        $this->assertNull($product->getAttribute('non_existing_attribute'));
    }

    public function testHasAttributeMethod()
    {
        $product = product::query()->one();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->save();

        $this->assertTrue($product->hasAttribute('name'));
        $this->assertTrue($product->hasAttribute('color'));
        $this->assertFalse($product->hasAttribute('non_existing_attribute'));
    }

    public function testModelGetAttributesMethod()
    {
        $product = product::query()->one();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->save();
        $productAttributes = $product->getAttributes();

        $this->assertTrue(is_object($productAttributes));
        $this->assertTrue(isset($productAttributes->id));
        $this->assertTrue(isset($productAttributes->name));
        $this->assertTrue(isset($productAttributes->color));
        $this->assertTrue($productAttributes->id == $product->id);
        $this->assertTrue($productAttributes->name == $product->name);
        $this->assertTrue($productAttributes->color == $product->color);
    }

    public function testSaveAndRefresh()
    {
        $product = new Product();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->saveAndRefresh();
        $latestProduct = Product::query()->orderBy('id', 'DESC')->one();

        $this->assertTrue($product->id == $latestProduct->id);
        $this->assertTrue($product->name == $latestProduct->name);
        $this->assertTrue($product->color == $latestProduct->color);
    }

    public function testContructThrowsRecordNotFoundException()
    {
        $this->expectException(RecordNotFoundException::class);
        new Product('non_existing_id');
    }

    public function testRecordNotFoundException()
    {
        $this->expectException(RecordNotFoundException::class);
        $product = new Product();
        $product->find(1);
    }

    public function testModelsAreCached()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch project 2
        $project = $this->db->model(Project::class);
        $project->find(2);

        // Because we have not yet accessed any relation on project 2,
        $this->assertEmpty($project->getCachedModels());

        // fetch tasks for project 2
        $project->tasks;

        // Now we have accessed tasks relation on project 2,
        // so we should have cached the tasks for project 2
        $this->assertNotEmpty($project->getCachedModels());
        $this->assertArrayHasKey('tasks', $project->getCachedModels());

        // fetch tasks for project 2 again
        $project->tasks;

        // Now we have already accessed tasks relation on project 2 again,
        // so we should have cached the tasks for project 2
        $this->assertNotEmpty($project->getCachedModels());
        $this->assertArrayHasKey('tasks', $project->getCachedModels());
    }

    public function testItSetsTimestampAttributes()
    {
        $article = new Article();
        $article->title = 'My Article';
        $article->saveAndRefresh();

        $this->assertNotNull($article->created_at);
        $this->assertNull($article->updated_at);

        // update the article
        $article->title = 'My Article 2';
        $article->saveAndRefresh();

        $this->assertNotNull($article->created_at);
        $this->assertNotNull($article->updated_at);
    }

    public function testItDoesNotSetTimestampAttributesWhenBulkInsert()
    {
        // bulk insert articles
        $this->db->table('articles')->bulkInsert([
            ['title' => 'Article 1'],
            ['title' => 'Article 2'],
            ['title' => 'Article 3'],
        ]);

        // fetch article 1
        $article = $this->db->model(Article::class);
        $article->find(1);

        // It should have created_at and updated_at attributes set null
        // because we inserted the article without setting these attributes
        // using bulk insert.
        $this->assertNull($article->created_at);
        $this->assertNull($article->updated_at);
    }

    public function testWithMethodForEagerLoading()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch project 2 with all its tasks
        $projectModel = $this->db->model(Project::class);
        $project = $projectModel::query()->with('tasks')->where('id', '=', 2)->one();

        // Assertions
        $this->assertNotEmpty($project->tasks);
        $this->assertEquals(2, $project->tasks->count());
        // $this->assertEquals('Task 2', $project->tasks[2]->name);
    }

    public function testWithCountMethodForEagerLoading()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch project 2 with all its tasks
        $projectModel = $this->db->model(Project::class);
        $project2 = $projectModel::query()->withCount('tasks')->where('id', '=', 2)->one();

        // fetch project 3 with all its tasks
        $projectModel = $this->db->model(Project::class);
        $project3 = $projectModel::query()->withCount('tasks')->where('id', '=', 3)->one();

        // Assertions
        $this->assertEquals(2, $project2->tasks_count);
        $this->assertEquals(0, $project3->tasks_count);
    }

    public function testWithMethodForNestedEagerLoading()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->bulkInsert([
            ['content' => 'Comment 1', 'task_id' => 1],
            ['content' => 'Comment 2', 'task_id' => 2],
            ['content' => 'Comment 3', 'task_id' => 2],
        ]);

        // fetch project 2 with all its tasks with comments
        $projectModel = $this->db->model(Project::class);
        $project = $projectModel::query()->with('tasks.comments')->where('id', '=', 2)->one();

        // Assertions
        $this->assertNotEmpty($project->tasks);
        $this->assertEquals(2, $project->tasks->count());
        // $this->assertEquals('Task 2', $project->tasks[2]->name);
        // $this->assertNotEmpty($project->tasks[2]->comments);
        // $this->assertEquals(2, $project->tasks[2]->comments->count());
    }

    public function testWithMethodForEagerLoadingAll()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all projects with all its tasks
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with('tasks')->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        // $this->assertEquals('Project 1', $projects[1]->name);
        // $this->assertNotEmpty($projects[1]->tasks);
        // $this->assertEquals(1, $projects[1]->tasks->count());
        // $this->assertEquals('Task 1', $projects[1]->tasks[1]->name);
    }

    public function testWithCountMethodForEagerLoadingAll()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all projects with all its tasks
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->withCount('tasks')->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        // $this->assertEquals(1, $projects[1]->tasks_count);
        // $this->assertEquals(2, $projects[2]->tasks_count);
    }

    public function testWithAndWithCountMethodBoth()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all projects with all its tasks
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with('tasks')->withCount('tasks')->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        // $this->assertEquals('Project 1', $projects[1]->name);
        // $this->assertNotEmpty($projects[1]->tasks);
        // $this->assertEquals(1, $projects[1]->tasks->count());
        // $this->assertEquals('Task 1', $projects[1]->tasks[1]->name);
        // $this->assertEquals(1, $projects[1]->tasks_count);
    }

    public function testWithAndWithCountMethodForHasManyThroughRelations()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->bulkInsert([
            ['content' => 'Comment 1', 'task_id' => 1],
            ['content' => 'Comment 2', 'task_id' => 2],
            ['content' => 'Comment 3', 'task_id' => 2],
        ]);

        // fetch all projects with all its comments
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with('comments')->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        // $this->assertEquals('Project 1', $projects[1]->name);
        // $this->assertNotEmpty($projects[1]->comments);
        // $this->assertEquals(1, $projects[1]->comments->count());
        // $this->assertEquals('Comment 1', $projects[1]->comments[1]->content);
    }

    public function testThrowsExceptionWhenEagerLoadingNonExistingRelation()
    {
        $projectModel = $this->db->model(Project::class);
        $this->expectException(\Exception::class);
        $projectModel::query()->with('managers')->all();
    }

    public function testEagerLoadHasOneRelation()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert managers
        $this->db->table('managers')->bulkInsert([
            ['name' => 'Manager 1', 'project_id' => 1],
            ['name' => 'Manager 2', 'project_id' => 2],
        ]);

        // fetch all projects with all its managers
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with('manager')->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        // $this->assertEquals('Project 1', $projects[1]->name);
        // $this->assertNotEmpty($projects[1]->manager);
        // $this->assertEquals('Manager 1', $projects[1]->manager->name);
    }

    public function testEagerLoadForEmptyRelation()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // fetch all projects with all its managers
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with('manager')->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        // $this->assertEquals('Project 1', $projects[1]->name);
        // $this->assertNull($projects[1]->manager);
    }

    public function testEagerLoadForManyToManyRelation()
    {
        // use pivot tables users, roles, role_user
        $this->db->table('users')->bulkInsert([
            ['name' => 'User 1'],
            ['name' => 'User 2'],
        ]);

        $this->db->table('roles')->bulkInsert([
            ['name' => 'Role 1'],
            ['name' => 'Role 2'],
        ]);

        $this->db->table('role_user')->bulkInsert([
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 1, 'role_id' => 2],
            ['user_id' => 2, 'role_id' => 1],
        ]);

        // fetch all users with all its roles
        $userModel = $this->db->model(User::class);
        $users = $userModel::query()->with('roles')->all();
        $firstUser = $users->getByKey(1);
        $nonExistingUser = $users->getByKey('non-existing');

        // Assertions
        $this->assertNotEmpty($users);
        $this->assertEquals(2, $users->count());
        $this->assertEquals('User 1', $firstUser->name);
        $this->assertNotEmpty($firstUser->roles);
        $this->assertEquals(2, $firstUser->roles->count());
        $this->assertNull($nonExistingUser);
    }

    public function testEagerLoadingNestedRelations()
    {
        set_env('APP_DEBUG', true);

        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->bulkInsert([
            ['content' => 'Comment 1', 'task_id' => 1],
            ['content' => 'Comment 2', 'task_id' => 1],
            ['content' => 'Comment 3', 'task_id' => 1],
            ['content' => 'Comment 4', 'task_id' => 2],
        ]);

        // bulk insert managers
        $this->db->table('managers')->bulkInsert([
            ['name' => 'Manager 1', 'project_id' => 1],
            ['name' => 'Manager 2', 'project_id' => 2],
        ]);

        // fetch all projects with all its tasks and comments
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with('manager', 'comments', 'tasks', 'tasks.comments')->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(3, $projects->count());
        $this->assertEquals('Project 1', $projects[0]->name);
        $this->assertNotEmpty($projects[0]->tasks);
        $this->assertEquals(2, $projects[0]->tasks->count());
        $this->assertEquals('Task 1', $projects[0]->tasks[0]->name);
        $this->assertNotEmpty($projects[0]->tasks[0]->comments);
        $this->assertEquals(3, $projects[0]->tasks[0]->comments->count());
        $this->assertEquals('Comment 1', $projects[0]->tasks[0]->comments[0]->content);
        $this->assertEquals('Comment 2', $projects[0]->tasks[0]->comments[1]->content);
        $this->assertEquals(0, $projects[2]->tasks->count());
        $this->assertEmpty($projects[2]->tasks);
        $this->assertEquals(4, $projects[0]->comments->count());
        $this->assertEquals(0, $projects[1]->comments->count());
        $this->assertTrue($projects[0]->hasAttribute('manager'));

        set_env('APP_DEBUG', false);
    }

    public function testEagerLoadingBelongsToRelation()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all tasks with all its projects
        $taskModel = $this->db->model(Task::class);
        $tasks = $taskModel::query()->with('project')->all();

        // Assertions
        $this->assertNotEmpty($tasks);
        $this->assertEquals(3, $tasks->count());
        $this->assertEquals('Task 1', $tasks[0]->name);
        $this->assertNotEmpty($tasks[0]->project);
        $this->assertEquals('Project 1', $tasks[0]->project->name);
        $this->assertEquals('Task 2', $tasks[1]->name);
        $this->assertNotEmpty($tasks[1]->project);
        $this->assertEquals('Project 1', $tasks[1]->project->name);
        $this->assertEquals('Task 3', $tasks[2]->name);
        $this->assertNotEmpty($tasks[2]->project);
        $this->assertEquals('Project 2', $tasks[2]->project->name);
    }

    public function testRelationsMethodsReturnQuery()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->bulkInsert([
            ['content' => 'Comment 1', 'task_id' => 1],
            ['content' => 'Comment 2', 'task_id' => 1],
            ['content' => 'Comment 3', 'task_id' => 1],
            ['content' => 'Comment 4', 'task_id' => 2],
        ]);

        // fetch all projects
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->all();

        // Fetch tasks of the first project as a method
        $tasksQuery1 = $projects[0]->tasks();
        $tasks = $tasksQuery1->all();

        // Fetch tasks of the first project as a method
        $tasksQuery2 = $projects[0]->tasks();
        $tasksWithComments = $tasksQuery2->with('comments')->all();

        // Assertions
        $this->assertInstanceOf(Query::class, $tasksQuery1);
        $this->assertNotEmpty($tasks);
        $this->assertEquals(2, $tasks->count());
        $this->assertEquals('Task 1', $tasks[0]->name);
        $this->assertEquals('Task 2', $tasks[1]->name);
        $this->assertNotEmpty($tasksWithComments);
        $this->assertEquals(2, $tasksWithComments->count());
        $this->assertEquals('Task 1', $tasksWithComments[0]->name);
        $this->assertEquals('Task 2', $tasksWithComments[1]->name);
        $this->assertNotEmpty($tasksWithComments[0]->comments);
        $this->assertEquals(3, $tasksWithComments[0]->comments->count());
        $this->assertEquals('Comment 1', $tasksWithComments[0]->comments[0]->content);
    }

    public function testCollectionAccessAsArray()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // fetch all projects
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        $this->assertEquals(2, count($projects));
        $this->assertArrayHasKey(0, $projects);
        $this->assertArrayHasKey(1, $projects);

        // unset the first project
        $project1 = $projects[0];
        unset($projects[0]);

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(1, $projects->count());
        $this->assertEquals(1, count($projects));
        $this->assertArrayNotHasKey(0, $projects);
        $this->assertArrayHasKey(1, $projects);

        // unset the second project
        $secondProject = $projects[1];
        unset($projects[1]);

        // Assertions
        $this->assertEmpty($projects);
        $this->assertTrue($projects->isEmpty());
        $this->assertEquals(0, $projects->count());
        $this->assertEquals(0, count($projects));
        $this->assertArrayNotHasKey(0, $projects);
        $this->assertArrayNotHasKey(1, $projects);

        // Re-add the second project
        $projects[] = $secondProject; // this should calls offsetSet() method with null as first parameter

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(1, $projects->count());
        $this->assertEquals(1, count($projects));
        $this->assertArrayHasKey(2, $projects);
        $this->assertArrayNotHasKey(0, $projects);
        $this->assertArrayNotHasKey(1, $projects);
        $this->assertEquals('Project 2', $projects[2]->name);

        // Re-add the first project
        $projects[0] = $project1; // this should calls offsetSet() method with 0 as first parameter

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        $this->assertEquals(2, count($projects));
        $this->assertArrayHasKey(0, $projects);
        $this->assertArrayNotHasKey(1, $projects);
        $this->assertArrayHasKey(2, $projects);
        $this->assertEquals('Project 1', $projects[0]->name);
        $this->assertEquals('Project 2', $projects[2]->name);
    }

    public function testEagerLoadingEmptyRelations()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all projects
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->all();
        $projects->load(); // calling load() without any relation should not throw any exception
        $projects->loadCount(); // calling loadCount() without any relation should not throw any exception

        // Assertions
        $this->assertNotEmpty($projects);
    }

    public function testHasMethodForRelationshipExistence()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all projects that have atleast one task
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->has('tasks')->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        $this->assertEquals('Project 1', $projects[0]->name);
        $this->assertEquals('Project 2', $projects[1]->name);

        // fetch all projects that have no tasks
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->has('tasks', '=', 0)->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(1, $projects->count());
        $this->assertEquals('Project 3', $projects[0]->name);

        // fetch all projects that have atleast one task
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->has('tasks', '>', 0)->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        $this->assertEquals('Project 1', $projects[0]->name);
        $this->assertEquals('Project 2', $projects[1]->name);

        // Expect exception when passing non-existing relation
        $this->expectException(Exception::class);
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->has('categories')->all();
    }

    public function testHasMethodWithConstraints()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all projects that have atleast one task with name 'Task 1'
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->has('tasks', '>', 0, function($q) {
            $q->where('name', '=', 'Task 1');
        })->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(1, $projects->count());
        $this->assertEquals('Project 1', $projects[0]->name);
    }

    public function testWhereHas()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all projects that have atleast one task
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->whereHas('tasks', function ($q) {
            $q->where('name', '=', 'Task 1');
        })->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(1, $projects->count());
        $this->assertEquals('Project 1', $projects[0]->name);

        // fetch all projects that have no tasks
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->whereHas('tasks', function ($q) {
            $q->where('name', '=', 'Task 4');
        })->all();

        // Assertions
        $this->assertEmpty($projects);
        $this->assertEquals(0, $projects->count());

        // fetch all projects that have atleast 2 tasks with name like 'Task'
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->whereHas('tasks', function ($q) {
            $q->where('name', 'like', 'Task%');
        }, '>=', 2)->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(1, $projects->count());
        $this->assertEquals('Project 1', $projects[0]->name);
    }

    public function testEagerLoadingWithArrayOfRelations()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->bulkInsert([
            ['content' => 'Comment 1', 'task_id' => 1],
            ['content' => 'Comment 2', 'task_id' => 1],
            ['content' => 'Comment 3', 'task_id' => 2],
        ]);

        // fetch all projects with tasks and task comments
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with(['tasks.comments'])->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(3, $projects->count());
        $this->assertEquals('Project 1', $projects[0]->name);
        $this->assertEquals('Project 2', $projects[1]->name);
        $this->assertEquals('Project 3', $projects[2]->name);
        $this->assertEquals(2, $projects[0]->tasks->count());
        $this->assertEquals(1, $projects[1]->tasks->count());
        $this->assertEquals(0, $projects[2]->tasks->count());
        $this->assertEquals(2, $projects[0]->tasks[0]->comments->count());
        $this->assertEquals(1, $projects[0]->tasks[1]->comments->count());
        $this->assertEquals(0, $projects[1]->tasks[0]->comments->count());
    }

    public function testEagerLoadingWithArrayOfRelationConstraints()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->bulkInsert([
            ['content' => 'Comment 1', 'task_id' => 1],
            ['content' => 'Comment 2', 'task_id' => 1],
            ['content' => 'Comment 3', 'task_id' => 2],
        ]);

        // fetch all projects that with tasks
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with(['tasks' => function ($q) {
            $q->where('name', '=', 'Task 1');
        }])->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(3, $projects->count());
        $this->assertEquals(1, $projects[0]->tasks->count());
        $this->assertEquals(0, $projects[1]->tasks->count());
        $this->assertEquals(0, $projects[2]->tasks->count());

        // fetch all projects that with task and task comments
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with(['tasks' => function ($q) {
            $q->with(['comments' => function ($q) {
                $q->where('content', '=', 'Comment 1');
            }]);
        }])->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(3, $projects->count());
        $this->assertEquals(2, $projects[0]->tasks->count());
        $this->assertEquals(1, $projects[1]->tasks->count());
        $this->assertEquals(0, $projects[2]->tasks->count());
        $this->assertEquals(1, $projects[0]->tasks[0]->comments->count());
        $this->assertEquals(0, $projects[0]->tasks[1]->comments->count());
    }

    public function testEagerLoadCountWithArrayOfRelationConstraints()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->bulkInsert([
            ['content' => 'Comment 1', 'task_id' => 1],
            ['content' => 'Comment 2', 'task_id' => 1],
            ['content' => 'Comment 3', 'task_id' => 2],
        ]);

        // fetch all projects with tasks count
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->withCount(['tasks' => function ($q) {
            $q->where('name', '=', 'Task 1');
        }])->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(3, $projects->count());
        $this->assertEquals(1, $projects[0]->tasks_count);
        $this->assertEquals(0, $projects[1]->tasks_count);
        $this->assertEquals(0, $projects[2]->tasks_count);

        // fetch all projects with task and task comments count
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with(['tasks' => function ($q) {
            $q->withCount('comments');
        }])->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(3, $projects->count());
        $this->assertEquals(2, $projects[0]->tasks[0]->comments_count);
        $this->assertEquals(1, $projects[0]->tasks[1]->comments_count);
        $this->assertEquals(0, $projects[1]->tasks[0]->comments_count);
        $this->assertObjectNotHasAttribute('tasks', $projects[2]);
    }

    public function testEagerLoadWithThrowsException()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all projects with tasks count
        $projectModel = $this->db->model(Project::class);

        // Expect exception
        $this->expectException(Exception::class);

        // try eager loading without specifying relation key
        $projectModel::query()->with(function ($q) {
            $q->with(['comments' => function ($q) {
                $q->where('content', '=', 'Comment 1');
            }]);
        })->all();
    }

    public function testEagerLoadWithCountThrowsException()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all projects with tasks count
        $projectModel = $this->db->model(Project::class);

        // Expect exception
        $this->expectException(Exception::class);

        // try eager loading without specifying relation key
        $projectModel::query()->withCount(function ($q) {
            $q->with(['comments' => function ($q) {
                $q->where('content', '=', 'Comment 1');
            }]);
        })->all();
    }

    public function testModelCastIntoArray()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch first projects with tasks
        $projectModel = $this->db->model(Project::class);
        $project = $projectModel::query()->with('tasks')->one();
        $projectArray = $project->toArray();

        // Assertions
        $this->assertNotEmpty($project);
        $this->assertEquals(1, $project->id);
        $this->assertEquals('Project 1', $project->name);
        $this->assertCount(2, $project->tasks);
        $this->assertIsArray($projectArray);
        $this->assertEquals('Project 1', $projectArray['name']);
        $this->assertCount(2, $projectArray['tasks']);
    }

    public function testCollectCastIntoArray()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch all projects with tasks
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with('tasks')->all();
        $projectsArray = $projects->toArray();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(3, $projects->count());
        $this->assertIsArray($projectsArray);
        $this->assertCount(2, $projectsArray[0]['tasks']);
        $this->assertCount(1, $projectsArray[1]['tasks']);
        $this->assertCount(0, $projectsArray[2]['tasks']);
    }

    public function testModelJsonSerializeMethod()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch first projects with tasks
        $projectModel = $this->db->model(Project::class);
        $project = $projectModel::query()->with('tasks')->one();
        $projectJson = json_encode($project);

        // Assertions
        $this->assertNotEmpty($project);
        $this->assertEquals(1, $project->id);
        $this->assertEquals('Project 1', $project->name);
        $this->assertCount(2, $project->tasks);
        $this->assertIsString($projectJson);
        $this->assertEquals('{"id":"1","name":"Project 1","tasks":[{"id":"1","name":"Task 1","project_id":"1"},{"id":"2","name":"Task 2","project_id":"1"}]}', $projectJson);
    }
}
