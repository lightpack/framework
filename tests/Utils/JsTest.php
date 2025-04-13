<?php

namespace Lightpack\Tests\Utils;

use Lightpack\Utils\Js;
use PHPUnit\Framework\TestCase;

class JsTest extends TestCase
{
    private Js $js;

    protected function setUp(): void
    {
        $this->js = new Js();
    }

    public function test_toJs_converts_scalar_values()
    {
        $this->assertEquals('42', $this->js->encode(42));
        $this->assertEquals('"hello"', $this->js->encode('hello'));
        $this->assertEquals('true', $this->js->encode(true));
        $this->assertEquals('null', $this->js->encode(null));
    }

    public function test_toJs_converts_arrays()
    {
        $array = ['name' => 'John', 'age' => 30];
        $expected = '{"name":"John","age":30}';
        $this->assertEquals($expected, $this->js->encode($array));
    }

    public function test_toJs_escapes_html_and_quotes()
    {
        $data = ['html' => '<p>Hello "world" & \'quotes\'</p>'];
        $result = $this->js->encode($data);
        
        $this->assertStringContainsString('\\u003C', $result); // <
        $this->assertStringContainsString('\\u003E', $result); // >
        $this->assertStringContainsString('\\u0026', $result); // &
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function test_toJs_as_json_parse()
    {
        $data = ['foo' => 'bar'];
        $result = $this->js->encode($data, false);
        $this->assertStringStartsWith("JSON.parse('", $result);
        $this->assertStringEndsWith("')", $result);
    }

    public function test_var_creates_variable_declaration()
    {
        $result = $this->js->var('user', ['name' => 'John']);
        $this->assertEquals('var user = {"name":"John"};', $result);
    }

    public function test_let_creates_variable_declaration()
    {
        $result = $this->js->let('user', ['name' => 'John']);
        $this->assertEquals('let user = {"name":"John"};', $result);
    }

    public function test_const_creates_variable_declaration()
    {
        $result = $this->js->const('API_KEY', 'abc123');
        $this->assertEquals('const API_KEY = "abc123";', $result);
    }
}
