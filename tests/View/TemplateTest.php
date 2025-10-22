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
        $result = $this->template->include('simple');

        $this->assertEquals('<h1>Hello World</h1>', $result);
    }

    /** @test */
    public function test_render_merges_instance_data_with_provided_data()
    {
        $this->createView('profile', '<h1><?= $name ?></h1><p><?= $role ?></p>');

        $this->template->setData(['name' => 'Alice']);
        $result = $this->template->include('profile', ['role' => 'Admin']);

        $this->assertStringContainsString('Alice', $result);
        $this->assertStringContainsString('Admin', $result);
    }

    /** @test */
    public function test_render_provided_data_overrides_instance_data()
    {
        $this->createView('override', '<p><?= $value ?></p>');

        $this->template->setData(['value' => 'original']);
        $result = $this->template->include('override', ['value' => 'overridden']);

        $this->assertEquals('<p>overridden</p>', $result);
    }

    /** @test */
    public function test_include_inherits_parent_data()
    {
        $this->createView('parent', '<?= template()->include("child") ?>');
        $this->createView('child', '<p><?= $name ?></p>');

        $this->template->setData(['name' => 'Bob']);
        $result = $this->template->include('parent');

        $this->assertEquals('<p>Bob</p>', $result);
    }

    /** @test */
    public function test_include_merges_parent_data_with_provided_data()
    {
        $this->createView('parent', '<?= template()->include("child", ["age" => 30]) ?>');
        $this->createView('child', '<p><?= $name ?> - <?= $age ?></p>');

        $this->template->setData(['name' => 'Charlie']);
        $result = $this->template->include('parent');

        $this->assertEquals('<p>Charlie - 30</p>', $result);
    }

    /** @test */
    public function test_include_provided_data_overrides_parent_data()
    {
        $this->createView('parent', '<?= template()->include("child", ["name" => "Override"]) ?>');
        $this->createView('child', '<p><?= $name ?></p>');

        $this->template->setData(['name' => 'Original']);
        $result = $this->template->include('parent');

        $this->assertEquals('<p>Override</p>', $result);
    }

    /** @test */
    public function test_multiple_includes_with_different_data()
    {
        $this->createView('parent', '<?= template()->include("childA", ["name" => "Alice"]) ?><?= template()->include("childB") ?>');
        $this->createView('childA', '<p><?= $name ?></p>');
        $this->createView('childB', '<p><?= $name ?></p>');

        $this->template->setData(['name' => 'Original']);
        $result = $this->template->include('parent');

        $this->assertEquals('<p>Alice</p><p>Original</p>', $result);
    }

    /** @test */
    public function test_include_does_not_pollute_parent_data()
    {
        $this->createView('parent', '<?= template()->include("child", ["extra" => "data"]) ?><p><?= isset($extra) ? "FAIL" : "PASS" ?></p>');
        $this->createView('child', '<span><?= $extra ?></span>');

        $this->template->setData(['name' => 'Test']);
        $result = $this->template->include('parent');

        $this->assertStringContainsString('<span>data</span>', $result);
        $this->assertStringContainsString('<p>PASS</p>', $result);
    }

    /** @test */
    public function test_component_only_uses_provided_data()
    {
        $this->createView('parent', '<?= template()->component("button", ["label" => "Click"]) ?>');
        $this->createView('button', '<button><?= $label ?></button>');

        $this->template->setData(['name' => 'Alice', 'role' => 'Admin']);
        $result = $this->template->include('parent');

        $this->assertEquals('<button>Click</button>', $result);
    }

    /** @test */
    public function test_component_does_not_have_access_to_parent_data()
    {
        $this->createView('parent', '<?= template()->component("widget", ["id" => 123]) ?>');
        $this->createView('widget', '<div><?= isset($name) ? "FAIL" : "PASS" ?></div>');

        $this->template->setData(['name' => 'Alice']);
        $result = $this->template->include('parent');

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
        $result = $this->template->include('layout');

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
        $result = $this->template->include('layout');

        $this->assertEquals('<div><p>Alice - Admin</p></div>', $result);
    }

    /** @test */
    public function test_embed_returns_empty_when_no_embed_set()
    {
        $this->createView('layout', '<div><?= template()->embed() ?></div>');

        $this->template->setData(['title' => 'Test']);
        $result = $this->template->include('layout');

        $this->assertEquals('<div></div>', $result);
    }

    /** @test */
    public function test_embed_key_is_not_exposed_to_templates()
    {
        $this->createView('layout', '<div><?= isset($__embed) ? "FAIL" : "PASS" ?></div>');

        $this->template->setData(['__embed' => 'child', 'title' => 'Test']);
        $result = $this->template->include('layout');

        $this->assertStringContainsString('PASS', $result);
    }

    /** @test */
    public function test_setData_replaces_existing_data()
    {
        $this->createView('test', '<p><?= isset($old) ? "FAIL" : "PASS" ?></p>');

        $this->template->setData(['old' => 'value']);
        $this->template->setData(['new' => 'value']);
        $result = $this->template->include('test');

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
        
        $this->template->include('nonexistent');
    }

    /** @test */
    public function test_nested_includes_work_correctly()
    {
        $this->createView('parent', '<?= template()->include("child1") ?>');
        $this->createView('child1', '<div><?= template()->include("child2") ?></div>');
        $this->createView('child2', '<p><?= $name ?></p>');

        $this->template->setData(['name' => 'Nested']);
        $result = $this->template->include('parent');

        $this->assertEquals('<div><p>Nested</p></div>', $result);
    }

    /** @test */
    public function test_templates_have_isolated_scope()
    {
        // Templates should not have access to Template class properties
        $this->createView('test', '<p><?= isset($data) || isset($embeddedTemplate) ? "FAIL" : "PASS" ?></p>');

        $result = $this->template->include('test');

        $this->assertEquals('<p>PASS</p>', $result);
    }

    /** @test */
    public function test_output_buffer_cleanup_on_exception()
    {
        $this->createView('error', '<?php throw new Exception("Test error"); ?>');

        $obLevel = ob_get_level();

        try {
            $this->template->include('error');
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
        $result = $this->template->include('page');

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
        $result = $this->template->include('layout');

        $expected = '<header>Lightpack</header><main>Dashboard</main><footer>Lightpack</footer>';
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function test_layout_renders_child_content_in_layout()
    {
        $this->createView('layouts/app', '<html><body><?= $this->content() ?></body></html>');
        $this->createView('dashboard', '<?php $this->layout("layouts/app") ?><h1>Dashboard</h1>');

        $result = $this->template->include('dashboard');

        $this->assertEquals('<html><body><h1>Dashboard</h1></body></html>', $result);
    }

    /** @test */
    public function test_layout_child_has_access_to_data()
    {
        $this->createView('layouts/app', '<html><?= $this->content() ?></html>');
        $this->createView('page', '<?php $this->layout("layouts/app") ?><h1><?= $title ?></h1>');

        $this->template->setData(['title' => 'Welcome']);
        $result = $this->template->include('page');

        $this->assertEquals('<html><h1>Welcome</h1></html>', $result);
    }

    /** @test */
    public function test_layout_has_access_to_same_data()
    {
        $this->createView('layouts/app', '<html><title><?= $site ?></title><?= $this->content() ?></html>');
        $this->createView('page', '<?php $this->layout("layouts/app") ?><p><?= $page ?></p>');

        $this->template->setData(['site' => 'Lightpack', 'page' => 'Home']);
        $result = $this->template->include('page');

        $this->assertEquals('<html><title>Lightpack</title><p>Home</p></html>', $result);
    }

    /** @test */
    public function test_layout_with_includes_in_child()
    {
        $this->createView('layouts/app', '<html><?= $this->content() ?></html>');
        $this->createView('page', '<?php $this->layout("layouts/app"); ?><?= template()->include("header") ?><main>Content</main>');
        $this->createView('header', '<h1><?= $title ?></h1>');

        $this->template->setData(['title' => 'Dashboard']);
        $result = $this->template->include('page');

        $this->assertEquals('<html><h1>Dashboard</h1><main>Content</main></html>', $result);
    }

    /** @test */
    public function test_layout_with_includes_in_layout()
    {
        $this->createView('layouts/app', '<?= template()->include("header") ?><?= $this->content() ?><?= template()->include("footer") ?>');
        $this->createView('header', '<header><?= $site ?></header>');
        $this->createView('footer', '<footer><?= $site ?></footer>');
        $this->createView('page', '<?php $this->layout("layouts/app") ?><main><?= $page ?></main>');

        $this->template->setData(['site' => 'Lightpack', 'page' => 'Dashboard']);
        $result = $this->template->include('page');

        $this->assertEquals('<header>Lightpack</header><main>Dashboard</main><footer>Lightpack</footer>', $result);
    }

    /** @test */
    public function test_template_without_layout_renders_normally()
    {
        $this->createView('simple', '<h1><?= $title ?></h1>');

        $this->template->setData(['title' => 'No Layout']);
        $result = $this->template->include('simple');

        $this->assertEquals('<h1>No Layout</h1>', $result);
    }

    /** @test */
    public function test_content_returns_empty_when_no_layout_used()
    {
        $this->createView('test', '<div><?= $this->content() ?></div>');

        $result = $this->template->include('test');

        $this->assertEquals('<div></div>', $result);
    }

    /** @test */
    public function test_layout_with_components()
    {
        $this->createView('layouts/app', '<html><?= $this->content() ?></html>');
        $this->createView('page', '<?php $this->layout("layouts/app") ?><?= template()->component("button", ["label" => "Click"]) ?>');
        $this->createView('button', '<button><?= $label ?></button>');

        $result = $this->template->include('page');

        $this->assertEquals('<html><button>Click</button></html>', $result);
    }

    /** @test */
    public function test_nested_layout_inheritance_two_levels()
    {
        // Base layout
        $this->createView('layouts/base', '<!DOCTYPE html><html><body><?= $this->content() ?></body></html>');
        
        // Primary layout extends base
        $this->createView('layouts/primary', '<?php $this->layout("layouts/base") ?><div class="primary"><?= $this->content() ?></div>');
        
        // Page uses primary layout
        $this->createView('users', '<?php $this->layout("layouts/primary") ?><h1>Users</h1>');

        $result = $this->template->include('users');

        $this->assertEquals('<!DOCTYPE html><html><body><div class="primary"><h1>Users</h1></div></body></html>', $result);
    }

    /** @test */
    public function test_nested_layout_inheritance_three_levels()
    {
        // Base layout
        $this->createView('layouts/base', '<html><?= $this->content() ?></html>');
        
        // App layout extends base
        $this->createView('layouts/app', '<?php $this->layout("layouts/base") ?><body><?= $this->content() ?></body>');
        
        // Admin layout extends app
        $this->createView('layouts/admin', '<?php $this->layout("layouts/app") ?><div class="admin-panel"><?= $this->content() ?></div>');
        
        // Page uses admin layout
        $this->createView('dashboard', '<?php $this->layout("layouts/admin") ?><h1>Admin Dashboard</h1>');

        $result = $this->template->include('dashboard');

        $this->assertEquals('<html><body><div class="admin-panel"><h1>Admin Dashboard</h1></div></body></html>', $result);
    }

    /** @test */
    public function test_multiple_pages_with_different_nested_layouts()
    {
        // Base layout
        $this->createView('layouts/base', '<!DOCTYPE html><html><?= $this->content() ?></html>');
        
        // Primary layout
        $this->createView('layouts/primary', '<?php $this->layout("layouts/base") ?><body class="primary"><?= $this->content() ?></body>');
        
        // Secondary layout
        $this->createView('layouts/secondary', '<?php $this->layout("layouts/base") ?><body class="secondary"><?= $this->content() ?></body>');
        
        // Users page with primary layout
        $this->createView('users', '<?php $this->layout("layouts/primary") ?><h1>Users</h1>');
        
        // Books page with secondary layout
        $this->createView('books', '<?php $this->layout("layouts/secondary") ?><h1>Books</h1>');

        $usersResult = $this->template->include('users');
        $booksResult = $this->template->include('books');

        $this->assertEquals('<!DOCTYPE html><html><body class="primary"><h1>Users</h1></body></html>', $usersResult);
        $this->assertEquals('<!DOCTYPE html><html><body class="secondary"><h1>Books</h1></body></html>', $booksResult);
    }

    /** @test */
    public function test_nested_layouts_with_data_access()
    {
        // Base layout uses data
        $this->createView('layouts/base', '<html><title><?= $site ?></title><?= $this->content() ?></html>');
        
        // App layout uses data and extends base
        $this->createView('layouts/app', '<?php $this->layout("layouts/base") ?><body><nav><?= $site ?></nav><?= $this->content() ?></body>');
        
        // Page uses data and app layout
        $this->createView('home', '<?php $this->layout("layouts/app") ?><h1><?= $page ?></h1>');

        $this->template->setData(['site' => 'Lightpack', 'page' => 'Home']);
        $result = $this->template->include('home');

        $this->assertEquals('<html><title>Lightpack</title><body><nav>Lightpack</nav><h1>Home</h1></body></html>', $result);
    }

    /** @test */
    public function test_nested_layouts_with_includes_at_each_level()
    {
        // Base layout with include
        $this->createView('layouts/base', '<html><?= template()->include("meta") ?><?= $this->content() ?></html>');
        $this->createView('meta', '<head><title><?= $title ?></title></head>');
        
        // App layout with include
        $this->createView('layouts/app', '<?php $this->layout("layouts/base") ?><body><?= template()->include("nav") ?><?= $this->content() ?></body>');
        $this->createView('nav', '<nav>Navigation</nav>');
        
        // Page with include
        $this->createView('page', '<?php $this->layout("layouts/app") ?><?= template()->include("content") ?>');
        $this->createView('content', '<main>Content</main>');

        $this->template->setData(['title' => 'Test']);
        $result = $this->template->include('page');

        $this->assertEquals('<html><head><title>Test</title></head><body><nav>Navigation</nav><main>Content</main></body></html>', $result);
    }

    /** @test */
    public function test_nested_layouts_with_components()
    {
        // Base layout
        $this->createView('layouts/base', '<html><?= $this->content() ?></html>');
        
        // App layout with component
        $this->createView('layouts/app', '<?php $this->layout("layouts/base") ?><body><?= template()->component("header", ["title" => "App"]) ?><?= $this->content() ?></body>');
        $this->createView('header', '<header><?= $title ?></header>');
        
        // Page with component
        $this->createView('page', '<?php $this->layout("layouts/app") ?><?= template()->component("card", ["text" => "Hello"]) ?>');
        $this->createView('card', '<div><?= $text ?></div>');

        $result = $this->template->include('page');

        $this->assertEquals('<html><body><header>App</header><div>Hello</div></body></html>', $result);
    }

    /** @test */
    public function test_deeply_nested_layouts_four_levels()
    {
        // Level 1: Base
        $this->createView('layouts/base', '<html><?= $this->content() ?></html>');
        
        // Level 2: App
        $this->createView('layouts/app', '<?php $this->layout("layouts/base") ?><body><?= $this->content() ?></body>');
        
        // Level 3: Section
        $this->createView('layouts/section', '<?php $this->layout("layouts/app") ?><main><?= $this->content() ?></main>');
        
        // Level 4: Subsection
        $this->createView('layouts/subsection', '<?php $this->layout("layouts/section") ?><article><?= $this->content() ?></article>');
        
        // Page
        $this->createView('page', '<?php $this->layout("layouts/subsection") ?><p>Deep nesting works!</p>');

        $result = $this->template->include('page');

        $this->assertEquals('<html><body><main><article><p>Deep nesting works!</p></article></main></body></html>', $result);
    }

    // ========================================
    // Stack Tests
    // ========================================

    /** @test */
    public function test_stack_basic_push_and_render()
    {
        $this->createView('page', '<?php $this->push("scripts") ?><script>alert("test")</script><?php $this->endPush() ?><?= $this->stack("scripts") ?>');

        $result = $this->template->include('page');

        $this->assertEquals('<script>alert("test")</script>', $result);
    }

    /** @test */
    public function test_stack_multiple_pushes()
    {
        $this->createView('page', '<?php $this->push("scripts") ?><script>one.js</script><?php $this->endPush() ?><?php $this->push("scripts") ?><script>two.js</script><?php $this->endPush() ?><?= $this->stack("scripts") ?>');

        $result = $this->template->include('page');

        $this->assertStringContainsString('<script>one.js</script>', $result);
        $this->assertStringContainsString('<script>two.js</script>', $result);
    }

    /** @test */
    public function test_stack_with_layout()
    {
        $this->createView('layouts/app', '<html><head><?= $this->stack("styles") ?></head><body><?= $this->content() ?><?= $this->stack("scripts") ?></body></html>');
        $this->createView('page', '<?php $this->layout("layouts/app") ?><?php $this->push("styles") ?><link rel="stylesheet" href="app.css"><?php $this->endPush() ?><h1>Page</h1><?php $this->push("scripts") ?><script>app.js</script><?php $this->endPush() ?>');

        $result = $this->template->include('page');

        $this->assertStringContainsString('<link rel="stylesheet" href="app.css">', $result);
        $this->assertStringContainsString('<script>app.js</script>', $result);
        $this->assertStringContainsString('<h1>Page</h1>', $result);
    }

    /** @test */
    public function test_stack_empty_returns_empty_string()
    {
        $this->createView('page', '<?= $this->stack("nonexistent") ?>');

        $result = $this->template->include('page');

        $this->assertEquals('', $result);
    }

    /** @test */
    public function test_stack_with_includes()
    {
        $this->createView('partial', '<?php $this->push("scripts") ?><script>partial.js</script><?php $this->endPush() ?>');
        $this->createView('page', '<?= template()->include("partial") ?><?php $this->push("scripts") ?><script>page.js</script><?php $this->endPush() ?><?= $this->stack("scripts") ?>');

        $result = $this->template->include('page');

        $this->assertStringContainsString('<script>partial.js</script>', $result);
        $this->assertStringContainsString('<script>page.js</script>', $result);
    }

    /** @test */
    public function test_stack_with_components()
    {
        $this->createView('button', '<?php $this->push("scripts") ?><script>button.js</script><?php $this->endPush() ?><button>Click</button>');
        $this->createView('page', '<?= template()->component("button") ?><?= $this->stack("scripts") ?>');

        $result = $this->template->include('page');

        $this->assertStringContainsString('<button>Click</button>', $result);
        $this->assertStringContainsString('<script>button.js</script>', $result);
    }

    /** @test */
    public function test_stack_multiple_stacks()
    {
        $this->createView('page', '<?php $this->push("styles") ?><link rel="stylesheet" href="app.css"><?php $this->endPush() ?><?php $this->push("scripts") ?><script>app.js</script><?php $this->endPush() ?>STYLES:<?= $this->stack("styles") ?>SCRIPTS:<?= $this->stack("scripts") ?>');

        $result = $this->template->include('page');

        $this->assertStringContainsString('STYLES:<link rel="stylesheet" href="app.css">', $result);
        $this->assertStringContainsString('SCRIPTS:<script>app.js</script>', $result);
    }

    /** @test */
    public function test_stack_complex_layout_scenario()
    {
        // Base layout with stacks
        $this->createView('layouts/base', '<!DOCTYPE html><html><head><?= $this->stack("meta") ?><?= $this->stack("styles") ?></head><body><?= $this->content() ?><?= $this->stack("scripts") ?></body></html>');
        
        // App layout extends base and adds its own assets
        $this->createView('layouts/app', '<?php $this->layout("layouts/base") ?><?php $this->push("styles") ?><link rel="stylesheet" href="app.css"><?php $this->endPush() ?><div class="app"><?= $this->content() ?></div><?php $this->push("scripts") ?><script>app.js</script><?php $this->endPush() ?>');
        
        // Page uses app layout and adds page-specific assets
        $this->createView('page', '<?php $this->layout("layouts/app") ?><?php $this->push("meta") ?><meta name="description" content="Page"><?php $this->endPush() ?><?php $this->push("styles") ?><link rel="stylesheet" href="page.css"><?php $this->endPush() ?><h1>Page Content</h1><?php $this->push("scripts") ?><script>page.js</script><?php $this->endPush() ?>');

        $result = $this->template->include('page');

        // Verify structure
        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<div class="app">', $result);
        $this->assertStringContainsString('<h1>Page Content</h1>', $result);
        
        // Verify all assets are included
        $this->assertStringContainsString('<meta name="description" content="Page">', $result);
        $this->assertStringContainsString('app.css', $result);
        $this->assertStringContainsString('page.css', $result);
        $this->assertStringContainsString('app.js', $result);
        $this->assertStringContainsString('page.js', $result);
    }

}
