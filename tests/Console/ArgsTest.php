<?php

use Lightpack\Console\Args;
use PHPUnit\Framework\TestCase;

class ArgsTest extends TestCase
{
    // ========================================
    // BASIC FUNCTIONALITY TESTS
    // ========================================

    public function testEmptyArguments()
    {
        $args = new Args([]);
        
        $this->assertNull($args->first());
        $this->assertEmpty($args->positional());
        $this->assertEmpty($args->options());
        $this->assertEmpty($args->all());
    }

    public function testSinglePositionalArgument()
    {
        $args = new Args(['User']);
        
        $this->assertEquals('User', $args->first());
        $this->assertEquals(['User'], $args->positional());
        $this->assertEmpty($args->options());
    }

    public function testMultiplePositionalArguments()
    {
        $args = new Args(['User', 'Admin/Role', 'Product']);
        
        $this->assertEquals('User', $args->first());
        $this->assertEquals(['User', 'Admin/Role', 'Product'], $args->positional());
        $this->assertEmpty($args->options());
    }

    // ========================================
    // OPTION PARSING TESTS
    // ========================================

    public function testOptionWithValue()
    {
        $args = new Args(['--table=users']);
        
        $this->assertEquals('users', $args->get('table'));
        $this->assertNull($args->first());
    }

    public function testMultipleOptionsWithValues()
    {
        $args = new Args(['--table=users', '--key=id', '--connection=mysql']);
        
        $this->assertEquals('users', $args->get('table'));
        $this->assertEquals('id', $args->get('key'));
        $this->assertEquals('mysql', $args->get('connection'));
    }

    public function testFlagWithoutValue()
    {
        $args = new Args(['--force', '--help']);
        
        $this->assertTrue($args->has('force'));
        $this->assertTrue($args->has('help'));
        $this->assertTrue($args->get('force'));
        $this->assertTrue($args->get('help'));
    }

    public function testMixedPositionalAndOptions()
    {
        $args = new Args(['User', '--table=users', 'Admin', '--force']);
        
        $this->assertEquals('User', $args->first());
        $this->assertEquals(['User', 'Admin'], $args->positional());
        $this->assertEquals('users', $args->get('table'));
        $this->assertTrue($args->has('force'));
    }

    // ========================================
    // DEFAULT VALUE TESTS
    // ========================================

    public function testGetWithDefaultValue()
    {
        $args = new Args(['--table=users']);
        
        $this->assertEquals('users', $args->get('table', 'default'));
        $this->assertEquals('id', $args->get('key', 'id'));
        $this->assertEquals('default', $args->get('missing', 'default'));
    }

    public function testGetWithNullDefault()
    {
        $args = new Args([]);
        
        $this->assertNull($args->get('table'));
        $this->assertNull($args->get('key', null));
    }

    // ========================================
    // EDGE CASE TESTS (Bug Prevention)
    // ========================================

    public function testOptionWithEmptyValue()
    {
        $args = new Args(['--table=']);
        
        // Empty string is a valid value
        $this->assertEquals('', $args->get('table'));
        $this->assertNotNull($args->get('table'));
    }

    public function testOptionWithSpecialCharacters()
    {
        $args = new Args([
            '--path=/var/www/html',
            '--url=https://example.com',
            '--name=John Doe',
            '--email=user@example.com'
        ]);
        
        $this->assertEquals('/var/www/html', $args->get('path'));
        $this->assertEquals('https://example.com', $args->get('url'));
        $this->assertEquals('John Doe', $args->get('name'));
        $this->assertEquals('user@example.com', $args->get('email'));
    }

    public function testOptionWithEqualsSignInValue()
    {
        // Value contains '=' character
        $args = new Args(['--query=SELECT * FROM users WHERE id=1']);
        
        // Should capture everything after first '='
        $this->assertEquals('SELECT * FROM users WHERE id=1', $args->get('query'));
    }

    public function testOptionWithMultipleEqualsSigns()
    {
        $args = new Args(['--config=key=value=extra']);
        
        $this->assertEquals('key=value=extra', $args->get('config'));
    }

    public function testPositionalArgumentStartingWithDash()
    {
        // Single dash should be treated as positional
        $args = new Args(['-', 'User']);
        
        $this->assertEquals('-', $args->first());
        $this->assertEquals(['-', 'User'], $args->positional());
    }

    public function testPositionalArgumentWithSingleDashPrefix()
    {
        // Single-dash options (like -v) are treated as positional
        // This is intentional - we only support --long-options
        $args = new Args(['-v', '-f']);
        
        $this->assertEquals('-v', $args->first());
        $this->assertEquals(['-v', '-f'], $args->positional());
        $this->assertFalse($args->has('v'));
        $this->assertFalse($args->has('f'));
    }

    public function testOptionNameWithHyphens()
    {
        $args = new Args(['--dry-run', '--no-cache=true']);
        
        $this->assertTrue($args->has('dry-run'));
        $this->assertEquals('true', $args->get('no-cache'));
    }

    public function testNumericPositionalArguments()
    {
        $args = new Args(['123', '456', '789']);
        
        $this->assertEquals('123', $args->first());
        $this->assertEquals(['123', '456', '789'], $args->positional());
    }

    public function testNumericOptionValues()
    {
        $args = new Args(['--port=8080', '--timeout=30']);
        
        // Values are returned as strings (command should cast if needed)
        $this->assertIsString($args->get('port'));
        $this->assertEquals('8080', $args->get('port'));
        $this->assertEquals('30', $args->get('timeout'));
    }

    public function testBooleanStringOptionValues()
    {
        $args = new Args(['--debug=true', '--verbose=false']);
        
        // String 'true'/'false' are returned as strings, not booleans
        $this->assertIsString($args->get('debug'));
        $this->assertEquals('true', $args->get('debug'));
        $this->assertEquals('false', $args->get('verbose'));
    }

    // ========================================
    // WHITESPACE AND EMPTY STRING TESTS
    // ========================================

    public function testOptionWithWhitespaceValue()
    {
        $args = new Args(['--message=Hello World', '--spaces=   ']);
        
        $this->assertEquals('Hello World', $args->get('message'));
        $this->assertEquals('   ', $args->get('spaces'));
    }

    public function testPositionalWithWhitespace()
    {
        $args = new Args(['Hello World', 'Foo Bar']);
        
        $this->assertEquals('Hello World', $args->first());
        $this->assertEquals(['Hello World', 'Foo Bar'], $args->positional());
    }

    // ========================================
    // DUPLICATE HANDLING TESTS
    // ========================================

    public function testDuplicateOptionLastWins()
    {
        // Last value should win
        $args = new Args(['--table=users', '--table=posts']);
        
        $this->assertEquals('posts', $args->get('table'));
    }

    public function testDuplicateFlagRemainsTrue()
    {
        $args = new Args(['--force', '--force']);
        
        $this->assertTrue($args->has('force'));
    }

    public function testFlagThenOptionWithSameName()
    {
        // Option value should override flag
        $args = new Args(['--debug', '--debug=verbose']);
        
        $this->assertEquals('verbose', $args->get('debug'));
        $this->assertNotTrue($args->get('debug')); // No longer boolean true
    }

    // ========================================
    // ORDER PRESERVATION TESTS
    // ========================================

    public function testPositionalOrderPreserved()
    {
        $args = new Args(['Third', 'First', 'Second']);
        
        $this->assertEquals('Third', $args->first());
        $this->assertEquals(['Third', 'First', 'Second'], $args->positional());
    }

    public function testMixedArgumentsPositionalOrderPreserved()
    {
        $args = new Args(['First', '--opt=val', 'Second', '--flag', 'Third']);
        
        $this->assertEquals(['First', 'Second', 'Third'], $args->positional());
    }

    // ========================================
    // all() METHOD TESTS
    // ========================================

    public function testAllReturnsOriginalArray()
    {
        $original = ['User', '--table=users', '--force'];
        $args = new Args($original);
        
        $this->assertEquals($original, $args->all());
    }

    public function testAllReturnsExactCopy()
    {
        $original = ['--a=1', '--b=2', 'pos'];
        $args = new Args($original);
        
        $this->assertSame($original, $args->all());
    }

    // ========================================
    // has() METHOD EDGE CASES
    // ========================================

    public function testHasReturnsFalseForMissingFlag()
    {
        $args = new Args(['--force']);
        
        $this->assertTrue($args->has('force'));
        $this->assertFalse($args->has('help'));
        $this->assertFalse($args->has('missing'));
    }

    public function testHasReturnsFalseForOptionWithValue()
    {
        $args = new Args(['--table=users']);
        
        // has() checks for boolean flags, not options with values
        $this->assertFalse($args->has('table'));
        
        // But get() still works
        $this->assertEquals('users', $args->get('table'));
    }

    // ========================================
    // REAL-WORLD SCENARIO TESTS
    // ========================================

    public function testCreateModelCommandScenario()
    {
        // php console create:model User --table=users --key=id
        $args = new Args(['User', '--table=users', '--key=id']);
        
        $this->assertEquals('User', $args->first());
        $this->assertEquals('users', $args->get('table'));
        $this->assertEquals('id', $args->get('key'));
    }

    public function testMigrateCommandScenario()
    {
        // php console migrate:up --force
        $args = new Args(['--force']);
        
        $this->assertTrue($args->has('force'));
        $this->assertNull($args->first());
    }

    public function testWatchCommandScenario()
    {
        // php console watch --path=app,config --ext=php,json --run="vendor/bin/phpunit"
        $args = new Args([
            '--path=app,config',
            '--ext=php,json',
            '--run=vendor/bin/phpunit'
        ]);
        
        $this->assertEquals('app,config', $args->get('path'));
        $this->assertEquals('php,json', $args->get('ext'));
        $this->assertEquals('vendor/bin/phpunit', $args->get('run'));
    }

    public function testProcessJobsCommandScenario()
    {
        // php console process:jobs --queue=emails,notifications --sleep=5 --cooldown=60
        $args = new Args([
            '--queue=emails,notifications',
            '--sleep=5',
            '--cooldown=60'
        ]);
        
        $this->assertEquals('emails,notifications', $args->get('queue'));
        $this->assertEquals('5', $args->get('sleep'));
        $this->assertEquals('60', $args->get('cooldown'));
    }

    public function testServeCommandScenario()
    {
        // php console app:serve 8080
        $args = new Args(['8080']);
        
        $this->assertEquals('8080', $args->first());
        $this->assertEquals('8000', $args->get('port', '8000'));
    }

    // ========================================
    // MALFORMED INPUT TESTS
    // ========================================

    public function testOptionWithoutName()
    {
        // Edge case: --=value (no option name)
        $args = new Args(['--=value']);
        
        // Regex requires at least one char for option name
        // [^=]+ means "one or more non-= chars"
        // So --=value doesn't match and falls through to positional
        $this->assertEquals('--=value', $args->first());
        $this->assertEmpty($args->options());
    }

    public function testTripleDashPrefix()
    {
        $args = new Args(['---option']);
        
        // ---option matches ^--(.+)$ regex with capture group = '-option'
        // This is actually valid behavior - it becomes a flag named '-option'
        // If we want to reject this, we'd need more complex validation
        $this->assertTrue($args->has('-option'));
        $this->assertEmpty($args->positional());
    }

    public function testOptionWithSpaceBeforeEquals()
    {
        // This would come as separate array elements from shell
        $args = new Args(['--table', '=', 'users']);
        
        // --table is a valid flag (no = attached)
        // = and users are positional arguments
        $this->assertTrue($args->has('table'));
        $this->assertEquals('=', $args->first());
        $this->assertEquals(['=', 'users'], $args->positional());
    }

    // ========================================
    // UNICODE AND SPECIAL CHARACTERS
    // ========================================

    public function testUnicodeInOptionValues()
    {
        $args = new Args(['--name=José', '--city=São Paulo', '--emoji=🚀']);
        
        $this->assertEquals('José', $args->get('name'));
        $this->assertEquals('São Paulo', $args->get('city'));
        $this->assertEquals('🚀', $args->get('emoji'));
    }

    public function testUnicodeInPositionalArguments()
    {
        $args = new Args(['Müller', '北京', '🎉']);
        
        $this->assertEquals('Müller', $args->first());
        $this->assertContains('北京', $args->positional());
        $this->assertContains('🎉', $args->positional());
    }

    // ========================================
    // TYPE SAFETY TESTS
    // ========================================

    public function testGetReturnsNullNotFalse()
    {
        $args = new Args([]);
        
        $this->assertNull($args->get('missing'));
        $this->assertNotFalse($args->get('missing'));
    }

    public function testHasReturnsBoolean()
    {
        $args = new Args(['--force']);
        
        $this->assertIsBool($args->has('force'));
        $this->assertIsBool($args->has('missing'));
    }

    public function testFirstReturnsStringOrNull()
    {
        $args1 = new Args(['User']);
        $args2 = new Args([]);
        
        $this->assertIsString($args1->first());
        $this->assertNull($args2->first());
    }

    public function testPositionalAlwaysReturnsArray()
    {
        $args1 = new Args(['User']);
        $args2 = new Args([]);
        
        $this->assertIsArray($args1->positional());
        $this->assertIsArray($args2->positional());
    }

    public function testOptionsAlwaysReturnsArray()
    {
        $args1 = new Args(['--force']);
        $args2 = new Args([]);
        
        $this->assertIsArray($args1->options());
        $this->assertIsArray($args2->options());
    }

    public function testAllAlwaysReturnsArray()
    {
        $args1 = new Args(['User']);
        $args2 = new Args([]);
        
        $this->assertIsArray($args1->all());
        $this->assertIsArray($args2->all());
    }
}
