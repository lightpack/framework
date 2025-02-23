<?php

namespace Lightpack\Tests\Config;

use PHPUnit\Framework\TestCase;
use Lightpack\Config\Env;

class EnvTest extends TestCase
{
    private string $envFile;

    protected function setUp(): void
    {
        $this->envFile = __DIR__ . '/test.env';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->envFile)) {
            unlink($this->envFile);
        }
    }

    public function testLoadEmptyFile()
    {
        file_put_contents($this->envFile, '');
        Env::load($this->envFile);
        $this->assertFalse(Env::has('FOO'));
    }

    public function testLoadBasicVariables()
    {
        $env = <<<EOT
APP_NAME=Lightpack
APP_ENV=testing
APP_DEBUG=true
APP_PORT=8080
EOT;
        file_put_contents($this->envFile, $env);
        Env::load($this->envFile);

        $this->assertEquals('Lightpack', Env::get('APP_NAME'));
        $this->assertEquals('testing', Env::get('APP_ENV'));
        $this->assertTrue(Env::get('APP_DEBUG'));
        $this->assertEquals('8080', Env::get('APP_PORT'));
    }

    public function testSpecialValues()
    {
        $env = <<<EOT
NULL_VAL=null
TRUE_VAL=true
FALSE_VAL=false
QUOTED="hello world"
SINGLE='hello world'
EOT;
        file_put_contents($this->envFile, $env);
        Env::load($this->envFile);

        $this->assertNull(Env::get('NULL_VAL'));
        $this->assertTrue(Env::get('TRUE_VAL'));
        $this->assertFalse(Env::get('FALSE_VAL'));
        $this->assertEquals('hello world', Env::get('QUOTED'));
        $this->assertEquals('hello world', Env::get('SINGLE'));
    }

    public function testVariableInterpolation()
    {
        $env = <<<EOT
DB_HOST=localhost
DB_URL=mysql://\${DB_HOST}:3306
EOT;
        file_put_contents($this->envFile, $env);
        Env::load($this->envFile);

        $this->assertEquals('mysql://localhost:3306', Env::get('DB_URL'));
    }

    public function testComments()
    {
        $env = <<<EOT
# This is a comment
APP_NAME=Lightpack
# Another comment
APP_ENV=testing
EOT;
        file_put_contents($this->envFile, $env);
        Env::load($this->envFile);

        $this->assertEquals('Lightpack', Env::get('APP_NAME'));
        $this->assertEquals('testing', Env::get('APP_ENV'));
    }

    public function testDefaultValue()
    {
        $this->assertEquals('default', Env::get('NON_EXISTENT', 'default'));
    }

    public function testHasMethod()
    {
        file_put_contents($this->envFile, 'FOO=bar');
        Env::load($this->envFile);

        $this->assertTrue(Env::has('FOO'));
        $this->assertFalse(Env::has('BAR'));
    }
}
