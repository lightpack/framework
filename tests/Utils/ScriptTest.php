<?php

namespace Lightpack\Tests\Utils;

use Lightpack\Utils\Script;
use PHPUnit\Framework\TestCase;

class ScriptTest extends TestCase
{
    private Script $script;

    protected function setUp(): void
    {
        $this->script = new Script();
    }

    public function test_toJs_converts_scalar_values()
    {
        $this->assertEquals('42', $this->script->toJs(42));
        $this->assertEquals('"hello"', $this->script->toJs('hello'));
        $this->assertEquals('true', $this->script->toJs(true));
        $this->assertEquals('null', $this->script->toJs(null));
    }

    public function test_toJs_converts_arrays()
    {
        $array = ['name' => 'John', 'age' => 30];
        $expected = '{"name":"John","age":30}';
        $this->assertEquals($expected, $this->script->toJs($array));
    }

    public function test_toJs_escapes_html_and_quotes()
    {
        $data = ['html' => '<p>Hello "world" & \'quotes\'</p>'];
        $result = $this->script->toJs($data);
        
        $this->assertStringContainsString('\\u003C', $result); // <
        $this->assertStringContainsString('\\u003E', $result); // >
        $this->assertStringContainsString('\\u0026', $result); // &
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function test_toJs_as_json_parse()
    {
        $data = ['foo' => 'bar'];
        $result = $this->script->toJs($data, false);
        $this->assertStringStartsWith("JSON.parse('", $result);
        $this->assertStringEndsWith("')", $result);
    }

    public function test_var_creates_variable_declaration()
    {
        $result = $this->script->var('user', ['name' => 'John']);
        $this->assertEquals('var user = {"name":"John"};', $result);
    }

    public function test_let_creates_variable_declaration()
    {
        $result = $this->script->let('user', ['name' => 'John']);
        $this->assertEquals('let user = {"name":"John"};', $result);
    }

    public function test_const_creates_variable_declaration()
    {
        $result = $this->script->const('API_KEY', 'abc123');
        $this->assertEquals('const API_KEY = "abc123";', $result);
    }
}
