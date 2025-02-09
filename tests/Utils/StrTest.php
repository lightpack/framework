<?php

declare(strict_types=1);

use Lightpack\Utils\Str;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public function testSingularize()
    {
        $this->assertEquals('quiz', (new Str)->singularize('quizzes'));
    }

    public function testPluralize()
    {
        $this->assertEquals('quizzes', (new Str)->pluralize('quiz'));
    }

    public function testPluralizeIf()
    {
        $this->assertEquals('role', (new Str)->pluralizeIf(1, 'role'));
    }

    public function testCamelize()
    {
        $this->assertEquals('ParentClass', (new Str)->camelize('parent_class'));
        $this->assertEquals('ParentClass', (new Str)->camelize('parent class'));
        $this->assertEquals('LazyBrownFox', (new Str)->camelize('lazy Brown fox'));
        $this->assertEquals('LazyBrownFox', (new Str)->camelize('lazy-brown-fox'));
        $this->assertEquals('LazyBrownFox', (new Str)->camelize('lazy_brown_fox'));
    }

    public function testVariable()
    {
        $this->assertEquals('parentClass', (new Str)->variable('ParentClass'));
        $this->assertEquals('lazyBrownFox', (new Str)->variable('Lazy Brown Fox'));
        $this->assertEquals('lazyBrownFox', (new Str)->variable('lazy-brown-fox'));
        $this->assertEquals('lazyBrownFox', (new Str)->variable('lazy_brown_fox'));
    }

    public function testUnderscore()
    {
        $this->assertEquals('parent_class', (new Str)->underscore('ParentClass'));
        $this->assertEquals('parent_class', (new Str)->underscore('Parent Class'));
        $this->assertEquals('parent_class', (new Str)->underscore('Parent-Class'));
        $this->assertEquals('parent_class', (new Str)->underscore('Parent-Class'));
        $this->assertEquals('parent_class', (new Str)->underscore('Parent_Class'));
    }

    public function testDasherize()
    {
        $this->assertEquals('parent-class', (new Str)->dasherize('parent_class'));
        $this->assertEquals('parent-class', (new Str)->dasherize('parent class'));
        $this->assertEquals('parent-class', (new Str)->dasherize('Parent Class'));
        $this->assertEquals('lazy-brown-fox', (new Str)->dasherize('lazy Brown fox'));
        $this->assertEquals('lazy-brown-fox', (new Str)->dasherize('lazy-brown-fox'));
    }

    public function testHumanize()
    {
        $str = new Str();
        $this->assertEquals('Lazy brown fox', $str->humanize('lazy_brown_fox'));
        $this->assertEquals('Lazy brown fox', $str->humanize('lazy-brown-fox'));
        $this->assertEquals('Lazy brown fox', $str->humanize('lazyBrownFox'));
        $this->assertEquals('Lazy brown fox', $str->humanize('lazy brown fox'));
        $this->assertEquals('', $str->humanize(''));
    }

    public function testHeadline()
    {
        $str = new Str();
        $this->assertEquals('Lazy Brown Fox', $str->headline('lazy_brown_fox'));
        $this->assertEquals('Lazy Brown Fox', $str->headline('lazy-brown-fox'));
        $this->assertEquals('Lazy Brown Fox', $str->headline('lazyBrownFox'));
        $this->assertEquals('Lazy Brown Fox', $str->headline('lazy brown fox'));
        $this->assertEquals('', $str->headline(''));
    }

    public function testTableize()
    {
        $this->assertEquals('users', (new Str)->tableize('User'));
        $this->assertEquals('users', (new Str)->tableize('User'));
        $this->assertEquals('users', (new Str)->tableize('user'));
        $this->assertEquals('users', (new Str)->tableize('users'));
        $this->assertEquals('user_groups', (new Str)->tableize('UserGroup'));
        $this->assertEquals('user_groups', (new Str)->tableize('User Group'));
        $this->assertEquals('user_groups', (new Str)->tableize('user_group'));
        $this->assertEquals('user_groups', (new Str)->tableize('user_groups'));
    }

    public function testClassify()
    {
        $this->assertEquals('User', (new Str)->classify('user'));
        $this->assertEquals('User', (new Str)->classify('users'));
        $this->assertEquals('User', (new Str)->classify('user'));
        $this->assertEquals('UserGroup', (new Str)->classify('user_group'));
        $this->assertEquals('UserGroup', (new Str)->classify('user_groups'));
        $this->assertEquals('UserGroup', (new Str)->classify('user groups'));
    }

    public function testForeignKey()
    {
        $this->assertEquals('user_id', (new Str)->foreignKey('user'));
        $this->assertEquals('user_id', (new Str)->foreignKey('users'));
        $this->assertEquals('user_id', (new Str)->foreignKey('user'));
        $this->assertEquals('user_group_id', (new Str)->foreignKey('user_group'));
        $this->assertEquals('user_group_id', (new Str)->foreignKey('user_groups'));
    }

    public function testOrdinalize()
    {
        $this->assertEquals('1st', (new Str)->ordinalize(1));
        $this->assertEquals('2nd', (new Str)->ordinalize(2));
        $this->assertEquals('3rd', (new Str)->ordinalize(3));
        $this->assertEquals('4th', (new Str)->ordinalize(4));
        $this->assertEquals('5th', (new Str)->ordinalize(5));
        $this->assertEquals('11th', (new Str)->ordinalize(11));
        $this->assertEquals('12th', (new Str)->ordinalize(12));
        $this->assertEquals('13th', (new Str)->ordinalize(13));
        $this->assertEquals('21st', (new Str)->ordinalize(21));
        $this->assertEquals('22nd', (new Str)->ordinalize(22));
        $this->assertEquals('23rd', (new Str)->ordinalize(23));
        $this->assertEquals('24th', (new Str)->ordinalize(24));
    }

    public function testSlugify()
    {
        $this->assertEquals('simple-blog', (new Str)->slugify('simple blog'));
        $this->assertEquals('this-is-blog-id-123', (new Str)->slugify('This is blog_id 123'));
        $this->assertEquals('what-is-seo', (new Str)->slugify('What is SEO?'));
        $this->assertEquals('learn-c-programming', (new Str)->slugify('Learn C++ Programming'));
    }

    public function testSlugifyWithUTF8()
    {
        $str = new Str();
        $this->assertEquals('uber-grunen', $str->slugify('über grünen'));
        $this->assertEquals('cafe-francais', $str->slugify('café français'));
        $this->assertEquals('hello-world', $str->slugify('hello world!@#$%^&*()'));
        $this->assertEquals('ni-hao', $str->slugify('你好')); // Chinese characters
        $this->assertEquals('kon-nichiha', $str->slugify('こんにちは')); // Japanese characters
    }

    public function testStartsWith()
    {
        $this->assertTrue((new Str)->startsWith('Hello World', 'Hello'));
        $this->assertTrue((new Str)->startsWith('/admin/products/123', '/admin'));
    }

    public function testEndsWith()
    {
        $this->assertTrue((new Str)->endsWith('Hello World', 'World'));
        $this->assertTrue((new Str)->endsWith('/admin/products/123', '123'));
    }

    public function testContains()
    {
        $this->assertTrue((new Str)->contains('Hello World', 'World'));
        $this->assertTrue((new Str)->contains('/admin/products/123', '/products/'));
    }

    public function testRandom()
    {
        $str = new Str();
        $this->assertEquals(16, strlen($str->random(16)));
        $this->assertEquals(1, strlen($str->random(1)));
    }

    public function testRandomWithInvalidLength()
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Str)->random(0);
    }

    public function testRandomWithValidLength()
    {
        $str = new Str();
        $this->assertEquals(1, strlen($str->random(1)));
        $this->assertEquals(2, strlen($str->random(2)));
        $this->assertEquals(16, strlen($str->random(16)));
    }

    public function testMask()
    {
        $this->assertEquals('******', (new Str)->mask('secret'));
        $this->assertEquals('******', (new Str)->mask('secret', '*'));
        $this->assertEquals('se****', (new Str)->mask('secret', '*', 2));
    }

    public function testTruncate()
    {
        $str = new Str();
        $this->assertEquals('Hello...', $str->truncate('Hello World', 5));
        $this->assertEquals('Hello World', $str->truncate('Hello World', 11));
        $this->assertEquals('Hello***', $str->truncate('Hello World', 5, '***'));
        $this->assertEquals('Hel...', $str->truncate('Hello', 3));
    }

    public function testTruncateWithUTF8()
    {
        $str = new Str();
        $this->assertEquals('über...', $str->truncate('über grünen', 4));
        $this->assertEquals('café...', $str->truncate('café français', 4));
    }

    public function testLimit()
    {
        $str = new Str();
        $this->assertEquals('one two...', $str->limit('one two three four', 2));
        $this->assertEquals('one two three', $str->limit('one two three', 3));
        $this->assertEquals('one***', $str->limit('one two three', 1, '***'));
    }

    public function testPad()
    {
        $str = new Str();
        $this->assertEquals('Hello     ', $str->pad('Hello', 10));
        $this->assertEquals('     Hello', $str->pad('Hello', 10, ' ', STR_PAD_LEFT));
        $this->assertEquals('**Hello***', $str->pad('Hello', 10, '*', STR_PAD_BOTH));
    }

    public function testTitle()
    {
        $str = new Str();
        $this->assertEquals('Hello World', $str->title('hello world'));
        $this->assertEquals('Über Grünen', $str->title('über grünen'));
    }

    public function testUpper()
    {
        $str = new Str();
        $this->assertEquals('HELLO WORLD', $str->upper('Hello World'));
        $this->assertEquals('ÜBER GRÜNEN', $str->upper('über grünen'));
    }

    public function testLower()
    {
        $str = new Str();
        $this->assertEquals('hello world', $str->lower('Hello World'));
        $this->assertEquals('über grünen', $str->lower('ÜBER GRÜNEN'));
    }

    public function testEscape()
    {
        $str = new Str();
        $this->assertEquals('&lt;h1&gt;Hello&lt;/h1&gt;', $str->escape('<h1>Hello</h1>'));
        $this->assertEquals('&quot;quoted&quot;', $str->escape('"quoted"'));
        $this->assertEquals('Tom &amp; Jerry', $str->escape('Tom & Jerry'));
    }

    public function testIsEmail()
    {
        $str = new Str();
        $this->assertTrue($str->isEmail('test@example.com'));
        $this->assertTrue($str->isEmail('test.name@sub.example.com'));
        $this->assertFalse($str->isEmail('invalid.email'));
        $this->assertFalse($str->isEmail('@example.com'));
    }

    public function testIsUrl()
    {
        $str = new Str();
        $this->assertTrue($str->isUrl('http://example.com'));
        $this->assertTrue($str->isUrl('https://sub.example.com/path?query=1'));
        $this->assertFalse($str->isUrl('not-a-url'));
        $this->assertFalse($str->isUrl('http://'));
    }

    public function testIsIp()
    {
        $str = new Str();
        // IPv4
        $this->assertTrue($str->isIp('192.168.1.1'));
        $this->assertTrue($str->isIp('127.0.0.1'));
        // IPv6
        $this->assertTrue($str->isIp('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertTrue($str->isIp('::1'));
        // Invalid
        $this->assertFalse($str->isIp('256.256.256.256'));
        $this->assertFalse($str->isIp('not.an.ip.address'));
    }

    public function testIsHex()
    {
        $str = new Str();
        $this->assertTrue($str->isHex('#fff'));
        $this->assertTrue($str->isHex('#000000'));
        $this->assertTrue($str->isHex('#FF0000'));
        $this->assertFalse($str->isHex('fff'));
        $this->assertFalse($str->isHex('#ffff'));
        $this->assertFalse($str->isHex('#xyz'));
    }

    public function testIsUuid()
    {
        $str = new Str();
        $this->assertTrue($str->isUuid('123e4567-e89b-42d3-a456-556642440000'));
        $this->assertFalse($str->isUuid('123e4567-e89b-12d3-a456-556642440000')); // Not v4
        $this->assertFalse($str->isUuid('not-a-uuid'));
    }

    public function testIsDomain()
    {
        $str = new Str();
        $this->assertTrue($str->isDomain('example.com'));
        $this->assertTrue($str->isDomain('sub.example.com'));
        $this->assertTrue($str->isDomain('sub-domain.example.com'));
        $this->assertFalse($str->isDomain('not_a_domain'));
        $this->assertFalse($str->isDomain('example'));
        $this->assertFalse($str->isDomain('-example.com'));
    }

    public function testIsBase64()
    {
        $str = new Str();
        $this->assertTrue($str->isBase64(base64_encode('Hello World')));
        $this->assertTrue($str->isBase64('SGVsbG8gV29ybGQ=')); // "Hello World"
        $this->assertFalse($str->isBase64('Not Base64!'));
        $this->assertFalse($str->isBase64(''));
    }

    public function testIsMimeType()
    {
        $str = new Str();
        $this->assertTrue($str->isMimeType('text/plain'));
        $this->assertTrue($str->isMimeType('application/json'));
        $this->assertTrue($str->isMimeType('image/jpeg'));
        $this->assertTrue($str->isMimeType('application/vnd.ms-excel'));
        $this->assertFalse($str->isMimeType('not/a/mime'));
        $this->assertFalse($str->isMimeType('text'));
    }

    public function testIsPath()
    {
        $str = new Str();
        $this->assertTrue($str->isPath('/path/to/file.txt'));
        $this->assertTrue($str->isPath('file.txt'));
        $this->assertTrue($str->isPath('./file.txt'));
        $this->assertFalse($str->isPath('../file.txt')); // Directory traversal
        $this->assertFalse($str->isPath('file*.txt')); // Invalid character
    }

    public function testIsJson()
    {
        $str = new Str();
        $this->assertTrue($str->isJson('{"name":"John","age":30}'));
        $this->assertTrue($str->isJson('[1,2,3]'));
        $this->assertTrue($str->isJson('{"nested":{"key":"value"}}'));
        $this->assertFalse($str->isJson('{invalid json}'));
        $this->assertFalse($str->isJson(''));
    }

    public function testFilename()
    {
        $str = new Str();
        $this->assertEquals('file.txt', $str->filename('/path/to/file.txt'));
        $this->assertEquals('file.txt', $str->filename('file.txt'));
        $this->assertEquals('image.jpg', $str->filename('/var/www/html/uploads/image.jpg'));
        $this->assertEquals('.htaccess', $str->filename('/var/www/.htaccess'));
    }

    public function testStem()
    {
        $str = new Str();
        $this->assertEquals('file', $str->stem('/path/to/file.txt'));
        $this->assertEquals('image', $str->stem('/var/www/html/uploads/image.jpg'));
        $this->assertEquals('script', $str->stem('script.min.js'));
        $this->assertEquals('', $str->stem('.htaccess'));
    }

    public function testExt()
    {
        $str = new Str();
        $this->assertEquals('txt', $str->ext('/path/to/file.txt'));
        $this->assertEquals('jpg', $str->ext('/var/www/html/uploads/image.jpg'));
        $this->assertEquals('js', $str->ext('script.min.js'));
        $this->assertEquals('htaccess', $str->ext('.htaccess'));
        $this->assertEquals('', $str->ext('README'));
    }

    public function testDir()
    {
        $str = new Str();
        $this->assertEquals('/path/to', $str->dir('/path/to/file.txt'));
        $this->assertEquals('/var/www/html/uploads', $str->dir('/var/www/html/uploads/image.jpg'));
        $this->assertEquals('.', $str->dir('file.txt'));
        $this->assertEquals('/var/www', $str->dir('/var/www/.htaccess'));
    }

    public function testStrip()
    {
        $str = new Str();
        $this->assertEquals('Hello World', $str->strip('<p>Hello World</p>'));
        $this->assertEquals('Hello World', $str->strip('<script>alert("xss");</script>Hello World'));
        $this->assertEquals('', $str->strip(''));
    }

    public function testAlphanumeric()
    {
        $str = new Str();
        $this->assertEquals('HelloWorld123', $str->alphanumeric('Hello, World! 123'));
        $this->assertEquals('Test123', $str->alphanumeric('Test@123!'));
        $this->assertEquals('', $str->alphanumeric('!@#$%^&*()'));
    }

    public function testAlpha()
    {
        $str = new Str();
        $this->assertEquals('HelloWorld', $str->alpha('Hello, World! 123'));
        $this->assertEquals('Test', $str->alpha('Test@123!'));
        $this->assertEquals('', $str->alpha('123'));
    }

    public function testNumber()
    {
        $str = new Str();
        $this->assertEquals('123', $str->number('Hello123World'));
        $this->assertEquals('12345', $str->number('Price: $123.45'));
        $this->assertEquals('', $str->number('No numbers here'));
    }

    public function testCollapse()
    {
        $str = new Str();
        $this->assertEquals('Hello World', $str->collapse('Hello   World'));
        $this->assertEquals('Hello World', $str->collapse("Hello\n\tWorld"));
        $this->assertEquals('', $str->collapse(''));
    }

    public function testInitials()
    {
        $str = new Str();
        $this->assertEquals('JD', $str->initials('John Doe'));
        $this->assertEquals('ABC', $str->initials('Alice Bob Charlie'));
        $this->assertEquals('J', $str->initials('john'));
        $this->assertEquals('JD', $str->initials('john   doe'));
        $this->assertEquals('', $str->initials(''));
    }

    public function testExcerpt()
    {
        $str = new Str();
        $text = 'This is a very long text that needs to be shortened';
        
        // Test with default length and end
        $this->assertEquals($text, $str->excerpt($text));
        
        // Test with custom length
        $this->assertEquals('This is a very long...', $str->excerpt($text, 20));
        
        // Test with custom end
        $this->assertEquals('This is a very long[...]', $str->excerpt($text, 20, '[...]'));
        
        // Test with short text
        $this->assertEquals('Hello', $str->excerpt('Hello', 20));
        
        // Test with empty text
        $this->assertEquals('', $str->excerpt(''));
    }
}
