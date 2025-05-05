<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Database\DB;
use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\Http\Request;
use Lightpack\Storage\LocalStorage;
use Lightpack\Uploads\UploadTrait;

// Minimal test model for uploads
class TestModel extends Model {
    use UploadTrait;
    protected $table = 'test_models';
    protected $primaryKey = 'id';
}

final class UploadTraitTest extends TestCase
{
    private ?DB $db;
    private Schema $schema;
    private string $uploadsDir;
    private string $fixturesDir;
    private TestModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['X_LIGHTPACK_TEST_UPLOAD'] = true;
        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $this->schema = new Schema($this->db);

        // Create uploads and test_models tables
        $this->schema->createTable('uploads', function(Table $table) {
            $table->id();
            $table->varchar('model_type');
            $table->column('model_id')->type('bigint')->attribute('unsigned');
            $table->varchar('collection')->default('default');
            $table->varchar('name');
            $table->varchar('file_name');
            $table->varchar('mime_type');
            $table->varchar('type', 25);
            $table->varchar('extension');
            $table->column('size')->type('bigint');
            $table->varchar('visibility', 25)->default('public');
            $table->column('meta')->type('json')->nullable();
            $table->timestamps();
        });
        $this->schema->createTable('test_models', function(Table $table) {
            $table->id();
            $table->varchar('name', 100)->nullable();
            $table->timestamps();
        });

        // Setup container
        $container = Container::getInstance();
        $container->register('db', fn() => $this->db);
        $container->register('logger', fn() => new class {
            public function error($message, $context = []) {}
            public function critical($message, $context = []) {}
        });
        $container->register('request', fn() => new Request());
        $container->alias(Request::class, 'request');

        // Setup uploads directory
        $this->uploadsDir = sys_get_temp_dir() . '/lightpack_uploads_test';
        if (!is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0777, true);
        }
        // Point storage to temp uploads dir
        $container->register('storage', fn() => new LocalStorage($this->uploadsDir));

        // Prepare fixtures dir
        $this->fixturesDir = __DIR__ . '/fixtures';
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0777, true);
        }
        // Create sample files if missing
        if (!file_exists($this->fixturesDir . '/test.txt')) {
            file_put_contents($this->fixturesDir . '/test.txt', 'sample text file');
        }
        if (!file_exists($this->fixturesDir . '/test.jpg')) {
            // Create a small blank jpeg
            $im = imagecreatetruecolor(10, 10);
            imagejpeg($im, $this->fixturesDir . '/test.jpg');
            imagedestroy($im);
        }
        if (!file_exists($this->fixturesDir . '/test.pdf')) {
            file_put_contents($this->fixturesDir . '/test.pdf', '%PDF-1.4\n%Test PDF');
        }

        // Create and save a reusable test model instance for uploads
        $this->model = $this->db->model(TestModel::class);
        $this->model->name = 'test';
        $this->model->save();
    }

    protected function tearDown(): void
    {
        $this->schema->dropTable('uploads');
        $this->schema->dropTable('test_models');
        $this->db = null;
        // Clean up uploads dir
        if (is_dir($this->uploadsDir)) {
            $this->rrmdir($this->uploadsDir);
        }
        // Reset $_FILES to avoid cross-test contamination
        $_FILES = [];
        unset($_SERVER['X_LIGHTPACK_TEST_UPLOAD']);
    }

    private function rrmdir($dir) {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = "$dir/$file";
            if (is_dir($path)) $this->rrmdir($path);
            else unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Helper to setup $_FILES for a test upload scenario
     */
    private function setTestFile(string $field, string $fixtureName, string $mimeType = 'text/plain')
    {
        // Copies a file from test fixtures directory to a real temporary file 
        // (simulating what PHP does for real uploads).
        $src = $this->fixturesDir . '/' . $fixtureName;
        $tmp = tempnam(sys_get_temp_dir(), 'upload_');
        copy($src, $tmp);

        $_FILES = [
            $field => [
                'name' => $fixtureName,
                'type' => $mimeType,
                'tmp_name' => $tmp,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($src),
            ]
        ];
    }

    public function test_single_file_upload_and_retrieval() {
        $this->setTestFile('file', 'test.txt', 'text/plain');
        $upload = $this->model->attach('file', [
            'collection' => 'test',
        ]);

        $this->assertNotNull($upload->id);
        $this->assertEquals('test.txt', $upload->file_name);
        $fullPath = $this->uploadsDir . '/' . $upload->getPath();
        $this->assertFileExists($fullPath, "Upload file not found at: $fullPath");
        $this->assertTrue($upload->exists());
        $this->assertEquals('public', $upload->visibility);
        $this->assertEquals('text/plain', $upload->mime_type);
        $this->assertEquals('txt', $upload->extension);
        $this->assertEquals('test', $upload->collection);
    }

    // Additional tests for multiple uploads, private uploads, singleton, detach, transformations, edge cases will follow here...
}