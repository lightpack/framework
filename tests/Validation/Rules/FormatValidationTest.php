<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class FormatValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // ========================================
    // EmailRule Tests
    // ========================================

    public function testValidEmail(): void
    {
        $data = ['email' => 'user@example.com'];

        $this->validator
            ->field('email')
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidEmailWithSubdomain(): void
    {
        $data = ['email' => 'user@mail.example.com'];

        $this->validator
            ->field('email')
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidEmailWithPlus(): void
    {
        $data = ['email' => 'user+tag@example.com'];

        $this->validator
            ->field('email')
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testInvalidEmailMissingAt(): void
    {
        $data = ['email' => 'userexample.com'];

        $this->validator
            ->field('email')
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidEmailMissingDomain(): void
    {
        $data = ['email' => 'user@'];

        $this->validator
            ->field('email')
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidEmailSpaces(): void
    {
        $data = ['email' => 'user @example.com'];

        $this->validator
            ->field('email')
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // UrlRule Tests
    // ========================================

    public function testValidHttpUrl(): void
    {
        $data = ['website' => 'http://example.com'];

        $this->validator
            ->field('website')
            ->url();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidHttpsUrl(): void
    {
        $data = ['website' => 'https://example.com'];

        $this->validator
            ->field('website')
            ->url();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidUrlWithPath(): void
    {
        $data = ['website' => 'https://example.com/path/to/page'];

        $this->validator
            ->field('website')
            ->url();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidUrlWithQueryString(): void
    {
        $data = ['website' => 'https://example.com?param=value'];

        $this->validator
            ->field('website')
            ->url();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testInvalidUrlMissingScheme(): void
    {
        $data = ['website' => 'example.com'];

        $this->validator
            ->field('website')
            ->url();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidUrlSpaces(): void
    {
        $data = ['website' => 'http://example .com'];

        $this->validator
            ->field('website')
            ->url();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // IpRule Tests
    // ========================================

    public function testValidIpv4(): void
    {
        $data = ['ip' => '192.168.1.1'];

        $this->validator
            ->field('ip')
            ->ip();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidIpv4WithVersion(): void
    {
        $data = ['ip' => '192.168.1.1'];

        $this->validator
            ->field('ip')
            ->ip('v4');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidIpv6(): void
    {
        $data = ['ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'];

        $this->validator
            ->field('ip')
            ->ip();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidIpv6WithVersion(): void
    {
        $data = ['ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'];

        $this->validator
            ->field('ip')
            ->ip('v6');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testIpv6FailsV4Validation(): void
    {
        $data = ['ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'];

        $this->validator
            ->field('ip')
            ->ip('v4');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testIpv4FailsV6Validation(): void
    {
        $data = ['ip' => '192.168.1.1'];

        $this->validator
            ->field('ip')
            ->ip('v6');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidIp(): void
    {
        $data = ['ip' => '999.999.999.999'];

        $this->validator
            ->field('ip')
            ->ip();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // SlugRule Tests
    // ========================================

    public function testValidSlug(): void
    {
        $data = ['slug' => 'my-blog-post'];

        $this->validator
            ->field('slug')
            ->slug();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidSlugSingleWord(): void
    {
        $data = ['slug' => 'blog'];

        $this->validator
            ->field('slug')
            ->slug();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidSlugWithNumbers(): void
    {
        $data = ['slug' => 'post-123'];

        $this->validator
            ->field('slug')
            ->slug();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testInvalidSlugUppercase(): void
    {
        $data = ['slug' => 'My-Blog-Post'];

        $this->validator
            ->field('slug')
            ->slug();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidSlugSpaces(): void
    {
        $data = ['slug' => 'my blog post'];

        $this->validator
            ->field('slug')
            ->slug();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidSlugUnderscores(): void
    {
        $data = ['slug' => 'my_blog_post'];

        $this->validator
            ->field('slug')
            ->slug();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidSlugStartsWithHyphen(): void
    {
        $data = ['slug' => '-my-blog'];

        $this->validator
            ->field('slug')
            ->slug();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidSlugEndsWithHyphen(): void
    {
        $data = ['slug' => 'my-blog-'];

        $this->validator
            ->field('slug')
            ->slug();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // AlphaRule Tests
    // ========================================

    public function testValidAlpha(): void
    {
        $data = ['name' => 'JohnDoe'];

        $this->validator
            ->field('name')
            ->alpha();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidAlphaWithUnicode(): void
    {
        $data = ['name' => 'José'];

        $this->validator
            ->field('name')
            ->alpha();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testInvalidAlphaWithNumbers(): void
    {
        $data = ['name' => 'John123'];

        $this->validator
            ->field('name')
            ->alpha();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidAlphaWithSpaces(): void
    {
        $data = ['name' => 'John Doe'];

        $this->validator
            ->field('name')
            ->alpha();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidAlphaWithSpecialChars(): void
    {
        $data = ['name' => 'John-Doe'];

        $this->validator
            ->field('name')
            ->alpha();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // AlphaNumRule Tests
    // ========================================

    public function testValidAlphaNum(): void
    {
        $data = ['username' => 'user123'];

        $this->validator
            ->field('username')
            ->alphaNum();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidAlphaNumOnlyLetters(): void
    {
        $data = ['username' => 'username'];

        $this->validator
            ->field('username')
            ->alphaNum();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidAlphaNumOnlyNumbers(): void
    {
        $data = ['username' => '123456'];

        $this->validator
            ->field('username')
            ->alphaNum();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidAlphaNumWithUnicode(): void
    {
        $data = ['username' => 'José123'];

        $this->validator
            ->field('username')
            ->alphaNum();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testInvalidAlphaNumWithSpaces(): void
    {
        $data = ['username' => 'user 123'];

        $this->validator
            ->field('username')
            ->alphaNum();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidAlphaNumWithSpecialChars(): void
    {
        $data = ['username' => 'user-123'];

        $this->validator
            ->field('username')
            ->alphaNum();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // DateRule Tests
    // ========================================

    public function testValidDate(): void
    {
        $data = ['date' => '2024-01-15'];

        $this->validator
            ->field('date')
            ->date();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidDateWithFormat(): void
    {
        $data = ['date' => '15/01/2024'];

        $this->validator
            ->field('date')
            ->date('d/m/Y');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidDateUSFormat(): void
    {
        $data = ['date' => '01/15/2024'];

        $this->validator
            ->field('date')
            ->date('m/d/Y');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testValidDateWithTime(): void
    {
        $data = ['datetime' => '2024-01-15 14:30:00'];

        $this->validator
            ->field('datetime')
            ->date('Y-m-d H:i:s');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testInvalidDate(): void
    {
        $data = ['date' => '2024-13-45'];

        $this->validator
            ->field('date')
            ->date();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidDateFormat(): void
    {
        $data = ['date' => '15/01/2024'];

        $this->validator
            ->field('date')
            ->date('Y-m-d');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidDateString(): void
    {
        $data = ['date' => 'not a date'];

        $this->validator
            ->field('date')
            ->date();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }
}
