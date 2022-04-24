<?php

require_once 'Option.php';
require_once 'Product.php';
require_once 'Role.php';
require_once 'User.php';
require_once 'Project.php';
require_once 'Task.php';
require_once 'Comment.php';

use Lightpack\Database\Lucid\Collection;
use PHPUnit\Framework\TestCase;
use \Lightpack\Database\Lucid\Model;
use Lightpack\Exceptions\RecordNotFoundException;

final class ModelTest extends TestCase
{
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
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments";
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
        
        if(!isset($owner->id)) {
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
        
        if(!isset($owner->id)) {
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
}

