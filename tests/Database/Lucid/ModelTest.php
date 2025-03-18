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
require_once 'CastModel.php';

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Collection;
use PHPUnit\Framework\TestCase;
use \Lightpack\Database\Lucid\Model;
use Lightpack\Database\Query\Query;
use Lightpack\Exceptions\RecordNotFoundException;
use PhpParser\Node\Expr\Cast;

final class ModelTest extends TestCase
{
    /** @var \Lightpack\Database\DB */
    private $db;

    /** @var \Lightpack\Database\Lucid\Model */
    private $product;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();
        $this->product = $this->db->model(Product::class);

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
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments, articles, managers, cast_models, cast_model_relations";
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

        $this->product->query()->insert([
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
        $this->db->table('users')->insert([
            ['name' => 'Bob'],
            ['name' => 'John'],
            ['name' => 'Jane'],
        ]);
        $this->db->table('roles')->insert([
            ['name' => 'admin'],
            ['name' => 'user'],
            ['name' => 'guest'],
        ]);
        $this->db->table('role_user')->insert([
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

    public function testPivotAttachMethod()
    {
        $this->db->table('users')->insert([
            ['name' => 'Bob'],
            ['name' => 'John'],
            ['name' => 'Jane'],
        ]);

        $this->db->table('roles')->insert([
            ['name' => 'admin'],
            ['name' => 'user'],
            ['name' => 'guest'],
        ]);

        $this->db->table('role_user')->insert([
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 1, 'role_id' => 2],
            ['user_id' => 2, 'role_id' => 2],
        ]);

        /** @var User */
        $user = $this->db->model(User::class);
        $user->find(3);
        $userRolesCountBeforeAttach = $user->roles->count();
        $rolesBefore = array_column($user->roles->toArray(), 'name');
        $user->roles()->attach([1, 3]);
        $user->load('roles');
        $userRolesCountAfterAttach = $user->roles->count();
        $rolesAfter = array_column($user->roles->toArray(), 'name');

        // Assertions
        $this->assertEquals(0, $userRolesCountBeforeAttach);
        $this->assertEquals(2, $userRolesCountAfterAttach);
        $this->assertEquals([], $rolesBefore);
        $this->assertEquals(['admin', 'guest'], $rolesAfter);
    }

    public function testPivotDetachMethod()
    {
        $this->db->table('users')->insert([
            ['name' => 'Bob'],
            ['name' => 'John'],
            ['name' => 'Jane'],
        ]);

        $this->db->table('roles')->insert([
            ['name' => 'admin'],
            ['name' => 'user'],
            ['name' => 'guest'],
        ]);

        $this->db->table('role_user')->insert([
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 1, 'role_id' => 2],
            ['user_id' => 2, 'role_id' => 2],
        ]);
        
        /** @var User */
        $user = $this->db->model(User::class);
        $user->find(1);
        $userRolesCountBeforeDetach = $user->roles->count();
        $rolesBefore = array_column($user->roles->toArray(), 'name');
        $user->roles()->detach(1);
        $user->load('roles');
        $userRolesCountAfterDetach = $user->roles->count();
        $rolesAfter = array_column($user->roles->toArray(), 'name');

        // Assertions
        $this->assertEquals(2, $userRolesCountBeforeDetach);
        $this->assertEquals(1, $userRolesCountAfterDetach);
        $this->assertEquals(['admin', 'user'], $rolesBefore);
        $this->assertEquals(['user'], $rolesAfter);
    }

    public function testPivotSyncMethod()
    {
        $this->db->table('users')->insert([
            ['name' => 'Bob'],
            ['name' => 'John'],
            ['name' => 'Jane'],
        ]);
        
        $this->db->table('roles')->insert([
            ['name' => 'admin'],
            ['name' => 'user'],
            ['name' => 'guest'],
        ]);
        
        $this->db->table('role_user')->insert([
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 1, 'role_id' => 2],
            ['user_id' => 2, 'role_id' => 2],
        ]);
        
        /** @var User */
        $user = $this->db->model(User::class);
        $user->find(1);
        $userRolesCountBeforeSync = $user->roles->count();
        $rolesBefore = array_column($user->roles->toArray(), 'name');
        $user->roles()->sync([1, 3]);
        $user->load('roles');
        $userRolesCountAfterSync = $user->roles->count();
        $rolesAfter = array_column($user->roles->toArray(), 'name');
        
        // Assertions
        $this->assertEquals(2, $userRolesCountBeforeSync);
        $this->assertEquals(2, $userRolesCountAfterSync);
        $this->assertEquals(['admin', 'user'], $rolesBefore);
        $this->assertEquals(['admin', 'guest'], $rolesAfter);
    }

    public function testHasManyThrough()
    {
        // projects, tasks, and comments table will be used for tests of hasmanyThrough relation
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);
        $this->db->table('comments')->insert([
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
        $this->db->table('users')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $article->save();

        $this->assertNotNull($article->created_at);
        $this->assertNull($article->updated_at);

        // update the article
        $article->title = 'My Article 2';
        $article->save();

        $this->assertNotNull($article->created_at);
        $this->assertNotNull($article->updated_at);
    }

    public function testItDoesNotSetTimestampAttributesWheninsert()
    {
        // bulk insert articles
        $this->db->table('articles')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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

        // Test with() for empty parent records.
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with('tasks')->where('id', '=', 999)->all();
        
        $this->assertTrue($projects->isEmpty());
    }

    public function testWithCountMethodForEagerLoading()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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

        // Test withCount() for empty parent records.
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->withCount('tasks')->where('id', '=', 999)->all();

        $this->assertTrue($projects->isEmpty());
    }

    public function testWithMethodForNestedEagerLoading()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->insert([
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
        $this->assertEquals('Task 2', $project->tasks[0]->name);
        $this->assertNotEmpty($project->tasks[0]->comments);
        $this->assertEquals(2, $project->tasks[0]->comments->count());
    }

    public function testWithMethodForEagerLoadingAll()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $this->assertEquals('Project 2', $projects[1]->name);
        $this->assertNotEmpty($projects[1]->tasks);
        $this->assertEquals(2, $projects[1]->tasks->count());
        $this->assertEquals('Task 3', $projects[1]->tasks[1]->name);
    }

    public function testWithCountMethodForEagerLoadingAll()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $this->assertEquals(1, $projects[0]->tasks_count);
        $this->assertEquals(2, $projects[1]->tasks_count);
    }

    public function testWithAndWithCountMethodBoth()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $this->assertEquals('Project 2', $projects[1]->name);
        $this->assertNotEmpty($projects[1]->tasks);
        $this->assertEquals(1, $projects[0]->tasks->count());
        $this->assertEquals('Task 3', $projects[1]->tasks[1]->name);
        $this->assertEquals(2, $projects[1]->tasks_count);
    }

    public function testWithAndWithCountMethodForHasManyThroughRelations()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->insert([
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
        $this->assertEquals('Project 1', $projects[0]->name);
        $this->assertNotEmpty($projects[0]->comments);
        $this->assertEquals(1, $projects[0]->comments->count());
        $this->assertEquals('Comment 1', $projects[0]->comments[0]->content);
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert managers
        $this->db->table('managers')->insert([
            ['name' => 'Manager 1', 'project_id' => 1],
            ['name' => 'Manager 2', 'project_id' => 2],
        ]);

        // fetch all projects with all its managers
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with('manager')->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        $this->assertEquals('Project 2', $projects[1]->name);
        $this->assertNotEmpty($projects[1]->manager);
        $this->assertEquals('Manager 2', $projects[1]->manager->name);
    }

    public function testEagerLoadForEmptyRelation()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // fetch all projects with all its managers
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->with('manager')->all();

        // Assertions
        $this->assertNotEmpty($projects);
        $this->assertEquals(2, $projects->count());
        $this->assertEquals('Project 1', $projects[0]->name);
        $this->assertNull($projects[0]->manager);
    }

    public function testEagerLoadForManyToManyRelation()
    {
        // use pivot tables users, roles, role_user
        $this->db->table('users')->insert([
            ['name' => 'User 1'],
            ['name' => 'User 2'],
        ]);

        $this->db->table('roles')->insert([
            ['name' => 'Role 1'],
            ['name' => 'Role 2'],
        ]);

        $this->db->table('role_user')->insert([
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 1, 'role_id' => 2],
            ['user_id' => 2, 'role_id' => 1],
        ]);

        // fetch all users with all its roles
        $userModel = $this->db->model(User::class);
        $users = $userModel::query()->with('roles')->all();
        $firstUser = $users->find(1);
        $nonExistingUser = $users->find('non-existing');

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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->insert([
            ['content' => 'Comment 1', 'task_id' => 1],
            ['content' => 'Comment 2', 'task_id' => 1],
            ['content' => 'Comment 3', 'task_id' => 1],
            ['content' => 'Comment 4', 'task_id' => 2],
        ]);

        // bulk insert managers
        $this->db->table('managers')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->insert([
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
        $this->db->table('projects')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // bulk insert comments
        $this->db->table('comments')->insert([
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
        $this->assertObjectNotHasProperty('tasks', $projects[2]);
    }

    public function testEagerLoadWithThrowsException()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
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
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        // fetch first projects with tasks
        $projectModel = $this->db->model(Project::class);
        $project = $projectModel::query()->with('tasks')->one();
        $projectJson = json_encode($project);
        $projectArray = $project->toArray();

        // Assertions
        $this->assertNotEmpty($project);
        $this->assertEquals(1, $project->id);
        $this->assertEquals('Project 1', $project->name);
        $this->assertCount(2, $project->tasks);
        $this->assertIsString($projectJson);
        $this->assertArrayHasKey('name', $projectArray);
        $this->assertArrayHasKey('tasks', $projectArray);   
        $this->assertEquals('Project 1', $projectArray['name']);
        $this->assertCount(2, $projectArray['tasks']);
    }

    public function testModelCollectionExcludeMethod()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
            ['name' => 'Project 4'],
        ]);

        // fetch first all projects
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->all();

        $this->assertCount(4, $projects);
        $this->assertEquals([1,2,3,4], $projects->ids());

        // lets exclude product ID: 2
        $projects = $projects->exclude(2);
        $this->assertCount(3, $projects);
        $this->assertEquals([1,3,4], $projects->ids());

        // lets exclude product IDs: 1,4
        $projects = $projects->exclude([1,4]);
        $this->assertCount(1, $projects);
        $this->assertEquals([3], $projects->ids());
    }

    public function testModelCollectionFilterMethod()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // fetch first all projects
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->all();   

        // filter out projects having name 'Project 2'
        $filteredProjects = $projects->filter(fn($project) => $project->name !== 'Project 2');

        // Assertions
        $this->assertCount(3, $projects);
        $this->assertCount(2, $filteredProjects);
        $this->assertEquals([1,2,3], $projects->ids());
        $this->assertEquals([1,3], $filteredProjects->ids());
    }

    public function testModelCloneMethod()
    {
        // bulk insert articles
        $this->db->table('articles')->insert([
            ['title' => 'Article 1', 'created_at' => date("Y-m-d", time()), 'updated_at' => date("Y-m-d", time())],
            ['title' => 'Article 2', 'created_at' => date("Y-m-d", time()), 'updated_at' => date("Y-m-d", time())],
            ['title' => 'Article 3', 'created_at' => date("Y-m-d", time()), 'updated_at' => date("Y-m-d", time())],
        ]);

        // fetch article 3
        $article = $this->db->model(Article::class);
        $article->find(3);

        // Test 1: Clone without exclude argument
        $clone = $article->clone();

        // Assertions
        $this->assertNull($clone->id);
        $this->assertNull($clone->created_at);
        $this->assertNull($clone->updated_at);
        $this->assertNotNull($clone->title);
        $this->assertNotNull($clone->status);
        $this->assertEquals('Article 3', $clone->title);

        // Test 2: Clone with exclude argument
        $clone = $article->clone(['status']);

        // Assertions
        $this->assertNull($clone->id);
        $this->assertNull($clone->status);
        $this->assertNotNull($clone->title);
        $this->assertEquals('Article 3', $clone->title);
        $this->assertNull($clone->status);

        // Test 3: It successfully inserts the clone
        $clone = $article->clone();
        $clone->status = 'published';
        $clone->save();

        // Assertions
        $this->assertEquals(4, $clone->id);
        $this->assertEquals($article->title, $clone->title);
        $this->assertEquals('published', $clone->status);
        $this->assertNull($clone->updated_at);
        $this->assertNotNull($clone->created_at);

        // Test 5: It throws exception if cloned from a non-existing model
        try {
            $clone = (new Article)->clone();
        } catch(\Exception $e) {
            $this->assertEquals('You cannot clone a non-existing model instance.', $e->getMessage());
        }
    }

    public function testQueryChunkMethodOnModel()
    {
        // Make sure we have no records
        (new Product)->query()->delete();

        foreach(range(1, 25) as $item) {
            $records[] = ['name' => 'Product name', 'color' => '#CCC'];
        }

        (new Product)->query()->insert($records);

        // Process chunk query
        $chunkedRecords = [];

        (new Product)->query()->chunk(5, function($records) use (&$chunkedRecords) {
            if(count($chunkedRecords) == 4) {
                return false;
            }

            $chunkedRecords[] = $records;
        });

        // Assertions
        $this->assertCount(4, $chunkedRecords);

        foreach($chunkedRecords as $records) {
            $this->assertCount(5, $records);
        }
    }

    public function testAggregateMethodsOnModel()
    {
        // Make sure we have no records
        Product::query()->delete();

        foreach(range(1, 10) as $item) {
            $records[] = ['name' => 'Product name', 'color' => '#CCC', 'price' => 100];
        }

        Product::query()->insert($records);

        // Assertions
        $this->assertEquals(10, Product::query()->count());
        $this->assertEquals(100, Product::query()->min('price'));
        $this->assertEquals(100, Product::query()->max('price'));
        $this->assertEquals(100, Product::query()->avg('price'));
        $this->assertEquals(1000, Product::query()->sum('price'));
    }

    public function testModelCollectionEachMethod()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        
        // get all projects 
        $project = $this->db->model(Project::class);
        $projects = $project::query()->all();
        $projects->each(function($project) {
            $project->toUppercase();
        });

        // Assertions
        $this->assertCount(3, $projects);
        $this->assertEquals('PROJECT 1', $projects[0]->name);
        $this->assertEquals('PROJECT 2', $projects[1]->name);
        $this->assertEquals('PROJECT 3', $projects[2]->name);
    }

    public function testModelCollectionFilterAndEachMethod()
    {
        // bulk insert projects
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        
        // get all projects 
        $project = $this->db->model(Project::class);
        $projects = $project::query()->all();
        $filteredProjects = $projects
            ->filter(fn($project) => $project->name !== 'Project 1')
            ->each(fn($project) => $project->toUppercase());

        // Assertions
        $this->assertCount(3, $projects);
        $this->assertCount(2, $filteredProjects);
        $this->assertEquals('PROJECT 2', $filteredProjects[0]->name);
        $this->assertEquals('PROJECT 3', $filteredProjects[1]->name);
        $this->assertEquals('Project 1', $projects[0]->name);
    }

    public function testCollectionMethodsAfterFilter()
    {
        // Insert test data
        $this->db->table('projects')->insert([
            ['name' => 'Project 1', 'status' => 'active'],
            ['name' => 'Project 2', 'status' => 'inactive'],
            ['name' => 'Project 3', 'status' => 'active'],
            ['name' => 'Project 4', 'status' => 'active'],
            ['name' => 'Project 5', 'status' => 'inactive'],
        ]);

        // Get all projects and filter active ones
        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->all();
        $activeProjects = $projects->filter(fn($project) => $project->status === 'active');

        // Test ids() method
        $this->assertEquals([1, 3, 4], $activeProjects->ids());

        // Test find() method
        $this->assertNotNull($activeProjects->find(1));
        $this->assertNull($activeProjects->find(2)); // inactive project
        $this->assertEquals('Project 3', $activeProjects->find(3)->name);

        // Test first() method with no conditions
        $this->assertEquals('Project 1', $activeProjects->first()->name);

        // Test first() method with conditions
        $this->assertEquals('Project 3', $activeProjects->first(['name' => 'Project 3'])->name);
        $this->assertNull($activeProjects->first(['name' => 'Project 2'])); // inactive project

        // Test column() method
        $this->assertEquals(['Project 1', 'Project 3', 'Project 4'], $activeProjects->column('name'));
        $this->assertEquals(['active', 'active', 'active'], $activeProjects->column('status'));

        // Test any() method
        $this->assertTrue($activeProjects->any('status'));
        $this->assertTrue($activeProjects->any('name'));
        $this->assertFalse($activeProjects->any('non_existent_column'));

        // Test that original collection remains unchanged
        $this->assertCount(5, $projects);
        $this->assertCount(3, $activeProjects);
        $this->assertEquals([1, 2, 3, 4, 5], $projects->ids());
    }

    public function testCollectionColumnMethod()
    {
        // Insert test data with null values and different cases
        $this->db->table('projects')->insert([
            ['name' => 'Project 1', 'status' => 'active'],
            ['name' => 'Project 2', 'status' => null],
            ['name' => 'Project 3', 'status' => ''],
            ['name' => 'Project 4', 'status' => 'active'],
            ['name' => 'Project 5', 'status' => 'inactive'],
        ]);

        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->all();

        // Test getting a column with all values present
        $this->assertEquals([1, 2, 3, 4, 5], $projects->column('id'));

        // Test getting a column with null and empty values
        $names = $projects->column('name');
        $this->assertEquals('Project 1', $names[0]);
        $this->assertEquals('Project 2', $names[1]);
        $this->assertEquals('Project 3', $names[2]);
        $this->assertEquals('Project 4', $names[3]);
        $this->assertEquals('Project 5', $names[4]);

        // Test getting a column with null and empty string
        $statuses = $projects->column('status');
        $this->assertEquals(['active', 'active', 'inactive'], $statuses);
    }

    public function testCollectionFirstMethod()
    {
        // Insert test data
        $this->db->table('projects')->insert([
            ['name' => 'Project 1', 'status' => 'active'],
            ['name' => 'Project 2', 'status' => 'inactive'],
            ['name' => 'Project 3', 'status' => 'active'],
            ['name' => 'Project 4', 'status' => 'active'],
            ['name' => 'Project 5', 'status' => 'inactive'],
        ]);

        $projectModel = $this->db->model(Project::class);
        $projects = $projectModel::query()->all();

        // Test first() with no conditions
        $first = $projects->first();
        $this->assertNotNull($first);
        $this->assertEquals('Project 1', $first->name);

        // Test first() with single condition
        $firstActive = $projects->first(['status' => 'active']);
        $this->assertEquals('Project 1', $firstActive->name);

        $firstInactive = $projects->first(['status' => 'inactive']);
        $this->assertEquals('Project 2', $firstInactive->name);

        // Test first() with multiple conditions
        $firstActiveProject3 = $projects->first([
            'status' => 'active',
            'name' => 'Project 3'
        ]);
        $this->assertNotNull($firstActiveProject3);
        $this->assertEquals('Project 3', $firstActiveProject3->name);

        // Test first() with non-matching conditions
        $nonExistent = $projects->first(['status' => 'pending']);
        $this->assertNull($nonExistent);

        // Test first() on empty collection
        $emptyProjects = $projectModel::query()->where('id', 999)->all();
        $this->assertNull($emptyProjects->first());
        $this->assertNull($emptyProjects->first(['status' => 'active']));
    }

    public function testModelAttributeCasting()
    {
        // Test all types of casting
        $now = new \DateTime();
        
        $data = [
            'string_col' => 123,
            'integer_col' => '456',
            'float_col' => '123.45',
            'boolean_col' => 1,
            'json_col' => json_encode(['key' => 'value', 'nested' => ['foo' => 'bar']]),
            'date_col' => $now->format('Y-m-d'),
            'datetime_col' => $now->format('Y-m-d H:i:s'),
            'timestamp_col' => $now->format('Y-m-d H:i:s')
        ];

        $this->db->table('cast_models')->insert($data);
        $model = $this->db->model(CastModel::class);
        $record = $model->query()->one();

        // Verify types after create
        $this->assertIsString($record->string_col);
        $this->assertEquals('123', $record->string_col);

        $this->assertIsInt($record->integer_col);
        $this->assertEquals(456, $record->integer_col);

        $this->assertIsFloat($record->float_col);
        $this->assertEquals(123.45, $record->float_col);

        $this->assertIsBool($record->boolean_col);
        $this->assertTrue($record->boolean_col);

        $this->assertIsArray($record->json_col);
        $this->assertEquals(
            ['key' => 'value', 'nested' => ['foo' => 'bar']], 
            $record->json_col,
            'JSON in database does not match expected array structure'
        );
        
        $this->assertEquals($now->format('Y-m-d'), $record->date_col);
        
        $this->assertInstanceOf(\DateTimeInterface::class, $record->datetime_col);
        $this->assertEquals(
            $now->format('Y-m-d H:i:s'), 
            $record->datetime_col->format('Y-m-d H:i:s')
        );
        
        $this->assertIsInt($record->timestamp_col);
        $this->assertEquals($now->getTimestamp(), $record->timestamp_col);
    }

    public function testModelAttributeCastingWithNullValues()
    {
        $model = $this->db->model(CastModel::class);
        
        $data = [
            'string_col' => null,
            'integer_col' => null,
            'float_col' => null,
            'boolean_col' => null,
            'json_col' => null,
            'date_col' => null,
            'datetime_col' => null,
            'timestamp_col' => null,
        ];

        // Create with null values
        $this->db->table('cast_models')->insert($data);
        $model = $this->db->model(CastModel::class);
        $record = $model->query()->one();

        // Verify all values remain null
        $this->assertNull($record->string_col);
        $this->assertNull($record->integer_col);
        $this->assertNull($record->float_col);
        $this->assertNull($record->boolean_col);
        $this->assertNull($record->json_col);
        $this->assertNull($record->date_col);
        $this->assertNull($record->datetime_col);
        $this->assertNull($record->timestamp_col);

        // Fetch and verify nulls persist
        $fetched = $model->find($record->id);
        $this->assertNull($fetched->string_col);
        $this->assertNull($fetched->integer_col);
        $this->assertNull($fetched->float_col);
        $this->assertNull($fetched->boolean_col);
        $this->assertNull($fetched->json_col);
        $this->assertNull($fetched->date_col);
        $this->assertNull($fetched->datetime_col);
        $this->assertNull($fetched->timestamp_col);
    }

    public function testModelAttributeCastingWithSave()
    {
        $model = $this->db->model(CastModel::class);
        $now = new \DateTime();

        // Test creating through model
        $model->string_col = 123;
        $model->integer_col = '456';
        $model->float_col = '123.45';
        $model->boolean_col = 1;
        $model->json_col = ['key' => 'value'];
        $model->date_col = $now->format('Y-m-d');
        $model->datetime_col = $now->format('Y-m-d H:i:s');
        $model->timestamp_col = $now->format('Y-m-d H:i:s');
        
        $model->save();
        
        // Verify types after save
        $this->assertIsString($model->string_col);
        $this->assertEquals('123', $model->string_col);
        
        $this->assertIsInt($model->integer_col);
        $this->assertEquals(456, $model->integer_col);
        
        $this->assertIsFloat($model->float_col);
        $this->assertEquals(123.45, $model->float_col);
        
        $this->assertIsBool($model->boolean_col);
        $this->assertTrue($model->boolean_col);
        
        $this->assertIsArray($model->json_col);
        $this->assertEquals(['key' => 'value'], $model->json_col);
        
        $this->assertEquals($now->format('Y-m-d'), $model->date_col);
        
        $this->assertInstanceOf(\DateTimeInterface::class, $model->datetime_col);
        $this->assertEquals(
            $now->format('Y-m-d H:i:s'),
            $model->datetime_col->format('Y-m-d H:i:s')
        );
        
        $this->assertIsInt($model->timestamp_col);
        $this->assertEquals($now->getTimestamp(), $model->timestamp_col);

        // Verify database received correct format
        $raw = $this->db->table('cast_models')->where('id', $model->id)->one();
        $this->assertEquals('123', $raw->string_col);
        $this->assertEquals('456', $raw->integer_col);
        $this->assertEquals('123.45', $raw->float_col);
        $this->assertEquals('1', $raw->boolean_col);
        $this->assertEquals(['key' => 'value'], json_decode($raw->json_col, true));
        $this->assertEquals($now->format('Y-m-d'), $raw->date_col);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $raw->datetime_col);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $raw->timestamp_col);

        // Test updating through model
        $model->json_col = ['updated' => true];
        $model->save();

        // Verify update was cast correctly
        $raw = $this->db->table('cast_models')->where('id', $model->id)->one();
        $this->assertEquals(['updated' => true], json_decode($raw->json_col, true));

        // Verify we can read it back
        $fetched = $model->find($model->id);
        $this->assertIsArray($fetched->json_col);
        $this->assertEquals(['updated' => true], $fetched->json_col);
    }

    public function testModelAttributeCastingWithRelations()
    {
        // Create a product with cast attributes
        $product = $this->db->model(CastModel::class);
        $product->string_col = 'Product 1';
        $product->json_col = ['tags' => ['electronics', 'gadgets']];
        $product->save();

        // Create options with cast attributes
        $option1 = $this->db->model(CastModel::class);
        $option1->string_col = 'Option 1';
        $option1->json_col = ['color' => 'red', 'size' => 'small'];
        $option1->save();

        $option2 = $this->db->model(CastModel::class);
        $option2->string_col = 'Option 2';
        $option2->json_col = ['color' => 'blue', 'size' => 'large'];
        $option2->save();

        // Link options to product
        $this->db->table('cast_model_relations')->insert([
            ['parent_id' => $product->id, 'child_id' => $option1->id],
            ['parent_id' => $product->id, 'child_id' => $option2->id],
        ]);

        // Test collection with relations
        $products = $product->query()
            ->with('options')
            ->all();

        $this->assertCount(3, $products);
        $product = $products->first();

        // Verify main model casting
        $this->assertIsString($product->string_col);
        $this->assertEquals('Product 1', $product->string_col);
        $this->assertIsArray($product->json_col);
        $this->assertEquals(['tags' => ['electronics', 'gadgets']], $product->json_col);

        // Verify relation casting
        $this->assertCount(2, $product->options);
        
        $option = $product->options->first();
        $this->assertIsString($option->string_col);
        $this->assertEquals('Option 1', $option->string_col);
        $this->assertIsArray($option->json_col);
        $this->assertEquals(['color' => 'red', 'size' => 'small'], $option->json_col);

        $option = $product->options[1];
        $this->assertIsString($option->string_col);
        $this->assertEquals('Option 2', $option->string_col);
        $this->assertIsArray($option->json_col);
        $this->assertEquals(['color' => 'blue', 'size' => 'large'], $option->json_col);
    }
}
