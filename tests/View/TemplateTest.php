<?php

namespace Tests\View;

use Lightpack\Container\Container;
use Lightpack\View\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    private Template $template;
    private static string $fixturesDir;

    public static function setUpBeforeClass(): void
    {
        // Create fixtures directory once for all tests
        self::$fixturesDir = __DIR__ . '/fixtures';
        
        if (!is_dir(self::$fixturesDir)) {
            mkdir(self::$fixturesDir, 0777, true);
        }
    }

    protected function setUp(): void
    {
        // Create template with explicit views path - no global constant needed!
        $this->template = new Template(self::$fixturesDir);
        
        // Set up container with template instance for helper function
        $container = Container::getInstance();
        $container->register('template', function() {
            return $this->template;
        });
        
        // Clean fixtures directory before each test
        $this->cleanFixtures();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $this->cleanFixtures();
    }

    private function cleanFixtures(): void
    {
        if (!is_dir(self::$fixturesDir)) {
            return;
        }

        $files = array_diff(scandir(self::$fixturesDir), ['.', '..', '.gitkeep']);
        foreach ($files as $file) {
            $path = self::$fixturesDir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createView(string $name, string $content): void
    {
        $path = self::$fixturesDir . '/' . $name . '.php';
        $dir = dirname($path);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        file_put_contents($path, $content);
    }

    /** @test */
    public function test_render_basic_template_with_data()
    {
        $this->createView('simple', '<h1><?= $title ?></h1>');

        $this->template->setData(['title' => 'Hello World']);
        $result = $this->template->render('simple');

        $this->assertEquals('<h1>Hello World</h1>', $result);
    }

    /** @test */
    public function test_render_merges_instance_data_with_provided_data()
    {
        $this->createView('profile', '<h1><?= $name ?></h1><p><?= $role ?></p>');

        $this->template->setData(['name' => 'Alice']);
        $result = $this->template->render('profile', ['role' => 'Admin']);

        $this->assertStringContainsString('Alice', $result);
        $this->assertStringContainsString('Admin', $result);
    }

    /** @test */
    public function test_render_provided_data_overrides_instance_data()
    {
        $this->createView('override', '<p><?= $value ?></p>');

        $this->template->setData(['value' => 'original']);
        $result = $this->template->render('override', ['value' => 'overridden']);

        $this->assertEquals('<p>overridden</p>', $result);
    }

    /** @test */
    public function test_include_inherits_parent_data()
    {
        $this->createView('parent', '<?= template()->include("child") ?>');
        $this->createView('child', '<p><?= $name ?></p>');

        $this->template->setData(['name' => 'Bob']);
        $result = $this->template->render('parent');

        $this->assertEquals('<p>Bob</p>', $result);
    }

    /** @test */
    public function test_include_merges_parent_data_with_provided_data()
    {
        $this->createView('parent', '<?= template()->include("child", ["age" => 30]) ?>');
        $this->createView('child', '<p><?= $name ?> - <?= $age ?></p>');

        $this->template->setData(['name' => 'Charlie']);
        $result = $this->template->render('parent');

        $this->assertEquals('<p>Charlie - 30</p>', $result);
    }

    /** @test */
    public function test_include_provided_data_overrides_parent_data()
    {
        $this->createView('parent', '<?= template()->include("child", ["name" => "Override"]) ?>');
        $this->createView('child', '<p><?= $name ?></p>');

        $this->template->setData(['name' => 'Original']);
        $result = $this->template->render('parent');

        $this->assertEquals('<p>Override</p>', $result);
    }

    /** @test */
    public function test_multiple_includes_with_different_data()
    {
        $this->createView('parent', '<?= template()->include("childA", ["name" => "Alice"]) ?><?= template()->include("childB") ?>');
        $this->createView('childA', '<p><?= $name ?></p>');
        $this->createView('childB', '<p><?= $name ?></p>');

        $this->template->setData(['name' => 'Original']);
        $result = $this->template->render('parent');

        $this->assertEquals('<p>Alice</p><p>Original</p>', $result);
    }

    /** @test */
    public function test_include_does_not_pollute_parent_data()
    {
        $this->createView('parent', '<?= template()->include("child", ["extra" => "data"]) ?><p><?= isset($extra) ? "FAIL" : "PASS" ?></p>');
        $this->createView('child', '<span><?= $extra ?></span>');

        $this->template->setData(['name' => 'Test']);
        $result = $this->template->render('parent');

        $this->assertStringContainsString('<span>data</span>', $result);
        $this->assertStringContainsString('<p>PASS</p>', $result);
    }

    /** @test */
    public function test_includeIf_includes_when_true()
    {
        $this->createView('parent', '<?= template()->includeIf(true, "child") ?>');
        $this->createView('child', '<p>Included</p>');

        $result = $this->template->render('parent');

        $this->assertEquals('<p>Included</p>', $result);
    }

    /** @test */
    public function test_includeIf_returns_empty_when_false()
    {
        $this->createView('parent', '<?= template()->includeIf(false, "child") ?>');
        $this->createView('child', '<p>Should not appear</p>');

        $result = $this->template->render('parent');

        $this->assertEquals('', $result);
    }

    /** @test */
    public function test_component_only_uses_provided_data()
    {
        $this->createView('parent', '<?= template()->component("button", ["label" => "Click"]) ?>');
        $this->createView('button', '<button><?= $label ?></button>');

        $this->template->setData(['name' => 'Alice', 'role' => 'Admin']);
        $result = $this->template->render('parent');

        $this->assertEquals('<button>Click</button>', $result);
    }

    /** @test */
    public function test_component_does_not_have_access_to_parent_data()
    {
        $this->createView('parent', '<?= template()->component("widget", ["id" => 123]) ?>');
        $this->createView('widget', '<div><?= isset($name) ? "FAIL" : "PASS" ?></div>');

        $this->template->setData(['name' => 'Alice']);
        $result = $this->template->render('parent');

        $this->assertEquals('<div>PASS</div>', $result);
    }

    /** @test */
    public function test_embed_renders_child_template_in_layout()
    {
        $this->createView('layout', '<html><?= template()->embed() ?></html>');
        $this->createView('dashboard', '<h1><?= $title ?></h1>');

        $this->template->setData([
            '__embed' => 'dashboard',
            'title' => 'Dashboard'
        ]);
        $result = $this->template->render('layout');

        $this->assertEquals('<html><h1>Dashboard</h1></html>', $result);
    }

    /** @test */
    public function test_embed_child_has_access_to_layout_data()
    {
        $this->createView('layout', '<div><?= template()->embed() ?></div>');
        $this->createView('page', '<p><?= $user ?> - <?= $role ?></p>');

        $this->template->setData([
            '__embed' => 'page',
            'user' => 'Alice',
            'role' => 'Admin'
        ]);
        $result = $this->template->render('layout');

        $this->assertEquals('<div><p>Alice - Admin</p></div>', $result);
    }

    /** @test */
    public function test_embed_returns_empty_when_no_embed_set()
    {
        $this->createView('layout', '<div><?= template()->embed() ?></div>');

        $this->template->setData(['title' => 'Test']);
        $result = $this->template->render('layout');

        $this->assertEquals('<div></div>', $result);
    }

    /** @test */
    public function test_embed_key_is_not_exposed_to_templates()
    {
        $this->createView('layout', '<div><?= isset($__embed) ? "FAIL" : "PASS" ?></div>');

        $this->template->setData(['__embed' => 'child', 'title' => 'Test']);
        $result = $this->template->render('layout');

        $this->assertStringContainsString('PASS', $result);
    }

    /** @test */
    public function test_setData_replaces_existing_data()
    {
        $this->createView('test', '<p><?= isset($old) ? "FAIL" : "PASS" ?></p>');

        $this->template->setData(['old' => 'value']);
        $this->template->setData(['new' => 'value']);
        $result = $this->template->render('test');

        $this->assertEquals('<p>PASS</p>', $result);
    }

    /** @test */
    public function test_getData_returns_current_data()
    {
        $data = ['name' => 'Alice', 'role' => 'Admin'];
        $this->template->setData($data);

        $this->assertEquals($data, $this->template->getData());
    }

    /** @test */
    public function test_template_throws_exception_for_missing_file()
    {
        $this->expectException(\Lightpack\Exceptions\TemplateNotFoundException::class);
        
        $this->template->render('nonexistent');
    }

    /** @test */
    public function test_nested_includes_work_correctly()
    {
        $this->createView('parent', '<?= template()->include("child1") ?>');
        $this->createView('child1', '<div><?= template()->include("child2") ?></div>');
        $this->createView('child2', '<p><?= $name ?></p>');

        $this->template->setData(['name' => 'Nested']);
        $result = $this->template->render('parent');

        $this->assertEquals('<div><p>Nested</p></div>', $result);
    }

    /** @test */
    public function test_templates_have_isolated_scope()
    {
        // Templates should not have access to Template class properties
        $this->createView('test', '<p><?= isset($data) || isset($embeddedTemplate) ? "FAIL" : "PASS" ?></p>');

        $result = $this->template->render('test');

        $this->assertEquals('<p>PASS</p>', $result);
    }

    /** @test */
    public function test_output_buffer_cleanup_on_exception()
    {
        $this->createView('error', '<?php throw new Exception("Test error"); ?>');

        $obLevel = ob_get_level();

        try {
            $this->template->render('error');
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Exception was thrown as expected
        }

        // Output buffer should be cleaned up
        $this->assertEquals($obLevel, ob_get_level());
    }

    /** @test */
    public function test_complex_scenario_parent_with_multiple_includes_and_component()
    {
        $this->createView('page', 
            '<?= template()->include("header", ["title" => "Home"]) ?>' .
            '<?= template()->component("button", ["label" => "Save"]) ?>' .
            '<?= template()->include("footer") ?>'
        );
        $this->createView('header', '<h1><?= $title ?> - <?= $user ?></h1>');
        $this->createView('button', '<button><?= $label ?></button>');
        $this->createView('footer', '<footer><?= $user ?></footer>');

        $this->template->setData(['user' => 'Alice']);
        $result = $this->template->render('page');

        $expected = '<h1>Home - Alice</h1><button>Save</button><footer>Alice</footer>';
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function test_embed_with_includes_in_layout_and_child()
    {
        $this->createView('layout', 
            '<?= template()->include("header") ?>' .
            '<?= template()->embed() ?>' .
            '<?= template()->include("footer") ?>'
        );
        $this->createView('header', '<header><?= $site ?></header>');
        $this->createView('content', '<main><?= $page ?></main>');
        $this->createView('footer', '<footer><?= $site ?></footer>');

        $this->template->setData([
            '__embed' => 'content',
            'site' => 'Lightpack',
            'page' => 'Dashboard'
        ]);
        $result = $this->template->render('layout');

        $expected = '<header>Lightpack</header><main>Dashboard</main><footer>Lightpack</footer>';
        $this->assertEquals($expected, $result);
    }
}
