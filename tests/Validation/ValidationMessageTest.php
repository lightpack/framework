<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation;

use Lightpack\Container\Container;
use Lightpack\Lang\Lang;
use Lightpack\Utils\Arr;
use Lightpack\Validation\Rules\AfterRule;
use Lightpack\Validation\Rules\AlphaRule;
use Lightpack\Validation\Rules\ArrayRule;
use Lightpack\Validation\Rules\BeforeRule;
use Lightpack\Validation\Rules\BetweenRule;
use Lightpack\Validation\Rules\DateRule;
use Lightpack\Validation\Rules\DbUniqueRule;
use Lightpack\Validation\Rules\EmailRule;
use Lightpack\Validation\Rules\File\ImageRule;
use Lightpack\Validation\Rules\File\MultipleFileRule;
use Lightpack\Validation\Rules\InRule;
use Lightpack\Validation\Rules\IpRule;
use Lightpack\Validation\Rules\LengthRule;
use Lightpack\Validation\Rules\MaxRule;
use Lightpack\Validation\Rules\MinRule;
use Lightpack\Validation\Rules\NotInRule;
use Lightpack\Validation\Rules\RegexRule;
use Lightpack\Validation\Rules\RequiredIfRule;
use Lightpack\Validation\Rules\RequiredRule;
use Lightpack\Validation\Rules\RequiredUnlessRule;
use Lightpack\Validation\Rules\RequiredWithoutRule;
use Lightpack\Validation\Rules\RequiredWithRule;
use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationMessageTest extends TestCase
{
    private static string $langPath = __DIR__ . '/fixtures/lang';

    public static function setUpBeforeClass(): void
    {
        $lang = new Lang('en', self::$langPath);
        Container::getInstance()->instance('lang', $lang);
    }

    public static function tearDownAfterClass(): void
    {
        // Reset to a path with no files so subsequent tests get raw English
        // fallback messages (lang() returns the key → triggers $this->message).
        Container::getInstance()->instance('lang', new Lang('en', '/tmp/__lp_no_lang__'));
    }

    // =========================================================
    // Fallback: raw English message when no translation file
    // =========================================================

    public function testFallbackMessageWhenNoLangKey(): void
    {
        $rule = new RequiredRule;
        $rule->setMessage('Custom override');

        $this->assertSame('Custom override', $rule->getMessage());
    }

    public function testSetMessageClearsLangKey(): void
    {
        $rule = new RequiredRule;
        $rule->setMessage('Explicit override');

        // Even though lang is bootstrapped, setMessage must win
        $this->assertSame('Explicit override', $rule->getMessage());
    }

    // =========================================================
    // Lang resolution — simple rules (no params)
    // =========================================================

    public function testRequiredRuleUsesLang(): void
    {
        $this->assertSame('This field cannot be blank', (new RequiredRule)->getMessage());
    }

    public function testEmailRuleUsesLang(): void
    {
        $this->assertSame('Enter a valid email', (new EmailRule)->getMessage());
    }

    public function testAlphaRuleUsesLang(): void
    {
        $this->assertSame('Letters only', (new AlphaRule)->getMessage());
    }

    // =========================================================
    // Lang resolution — parameterised rules
    // =========================================================

    public function testMinRuleUsesLangWithParam(): void
    {
        $this->assertSame('Minimum value is 5', (new MinRule(5))->getMessage());
    }

    public function testMaxRuleUsesLangWithParam(): void
    {
        $this->assertSame('Maximum value is 100', (new MaxRule(100))->getMessage());
    }

    public function testBetweenRuleUsesLangWithParams(): void
    {
        $this->assertSame('Must be between 1 and 10', (new BetweenRule(1, 10))->getMessage());
    }

    public function testLengthRuleUsesLangWithParam(): void
    {
        $this->assertSame('Must be exactly 8 characters', (new LengthRule(8))->getMessage());
    }

    public function testInRuleUsesLangWithParam(): void
    {
        $this->assertSame('Must be one of: a, b, c', (new InRule(['a', 'b', 'c']))->getMessage());
    }

    public function testNotInRuleUsesLangWithParam(): void
    {
        $this->assertSame('Must not be: x, y', (new NotInRule(['x', 'y']))->getMessage());
    }

    public function testRegexRuleUsesLangWithParam(): void
    {
        $this->assertSame('Invalid format', (new RegexRule('/^[a-z]+$/'))->getMessage());
    }

    // =========================================================
    // Lang resolution — conditional rules
    // =========================================================

    public function testIpRuleUsesLangNoVersion(): void
    {
        $this->assertSame('Enter a valid IP address', (new IpRule)->getMessage());
    }

    public function testIpRuleUsesLangV4(): void
    {
        $this->assertSame('Enter a valid IPv4 address', (new IpRule('v4'))->getMessage());
    }

    public function testIpRuleUsesLangV6(): void
    {
        $this->assertSame('Enter a valid IPv6 address', (new IpRule('v6'))->getMessage());
    }

    public function testDateRuleUsesLangNoFormat(): void
    {
        $this->assertSame('Enter a valid date', (new DateRule)->getMessage());
    }

    public function testDateRuleUsesLangWithFormat(): void
    {
        $this->assertSame('Date must be in Y-m-d format', (new DateRule('Y-m-d'))->getMessage());
    }

    public function testAfterRuleUsesLangNoFormat(): void
    {
        $this->assertSame('Date must be after 2024-01-01', (new AfterRule('2024-01-01'))->getMessage());
    }

    public function testAfterRuleUsesLangWithFormat(): void
    {
        $this->assertSame('Date must be after 2024-01-01 (Y-m-d)', (new AfterRule('2024-01-01', 'Y-m-d'))->getMessage());
    }

    public function testBeforeRuleUsesLangNoFormat(): void
    {
        $this->assertSame('Date must be before 2099-12-31', (new BeforeRule('2099-12-31'))->getMessage());
    }

    public function testArrayRuleUsesLangNoConstraints(): void
    {
        $this->assertSame('Must be a list', (new ArrayRule)->getMessage());
    }

    public function testArrayRuleUsesLangMinOnly(): void
    {
        $this->assertSame('Provide at least 2 items', (new ArrayRule(2))->getMessage());
    }

    public function testArrayRuleUsesLangMaxOnly(): void
    {
        $this->assertSame('Provide at most 5 items', (new ArrayRule(null, 5))->getMessage());
    }

    public function testArrayRuleUsesLangMinAndMax(): void
    {
        $this->assertSame('Provide between 2 and 5 items', (new ArrayRule(2, 5))->getMessage());
    }

    // =========================================================
    // Lang resolution — required_* rules with params
    // =========================================================

    public function testRequiredIfRuleUsesLang(): void
    {
        $rule = new RequiredIfRule('role', 'admin', new Arr);
        $this->assertSame('Required when role is admin', $rule->getMessage());
    }

    public function testRequiredUnlessRuleUsesLang(): void
    {
        $rule = new RequiredUnlessRule('type', 'guest', new Arr);
        $this->assertSame('Required unless type is guest', $rule->getMessage());
    }

    public function testRequiredWithRuleUsesLang(): void
    {
        $rule = new RequiredWithRule('email', new Arr);
        $this->assertSame('Required when email is provided', $rule->getMessage());
    }

    public function testRequiredWithoutRuleUsesLang(): void
    {
        $rule = new RequiredWithoutRule('phone', new Arr);
        $this->assertSame('Required when phone is absent', $rule->getMessage());
    }

    // =========================================================
    // Locale switch — same validator, different locale
    // =========================================================

    public function testLocaleSwitch(): void
    {
        $lang = new Lang('fr', self::$langPath . '/extra');
        Container::getInstance()->instance('lang', $lang);

        // No French file exists → falls back to raw English message
        $this->assertSame('This field is required', (new RequiredRule)->getMessage());

        // Restore
        $lang = new Lang('en', self::$langPath);
        Container::getInstance()->instance('lang', $lang);
    }

    // =========================================================
    // End-to-end: validator errors use translated messages
    // =========================================================

    public function testValidatorErrorsUseTranslatedMessages(): void
    {
        $validator = new Validator;
        $validator->field('name')->required()->min(3);
        $result = $validator->setInput(['name' => ''])->validate();

        $this->assertTrue($result->fails());
        $this->assertSame('This field cannot be blank', $result->getErrors()['name']);
    }

    public function testValidatorMinErrorUsesTranslatedMessageWithParam(): void
    {
        $validator = new Validator;
        $validator->field('age')->required()->min(18);
        $result = $validator->setInput(['age' => '5'])->validate();

        $this->assertTrue($result->fails());
        $this->assertSame('Minimum value is 18', $result->getErrors()['age']);
    }

    // =========================================================
    // DbUniqueRule
    // =========================================================

    public function testDbUniqueRuleSingleColumnUsesLang(): void
    {
        $rule = new DbUniqueRule('users', 'email');
        $this->assertSame('email is already taken', $rule->getMessage());
    }

    public function testDbUniqueRuleCompositeColumnsUsesLang(): void
    {
        $rule = new DbUniqueRule('users', ['email', 'username']);
        $this->assertSame('The combination of email, username is already taken', $rule->getMessage());
    }

    // =========================================================
    // MultipleFileRule
    // =========================================================

    public function testMultipleFileRuleNoConstraintUsesLang(): void
    {
        $this->assertSame('Invalid number of files', (new MultipleFileRule)->getMessage());
    }

    public function testMultipleFileRuleMinOnlyConstructorUsesLang(): void
    {
        $this->assertSame('Upload at least 2 files', (new MultipleFileRule(2))->getMessage());
    }

    public function testMultipleFileRuleMaxOnlyConstructorUsesLang(): void
    {
        $this->assertSame('Upload no more than 5 files', (new MultipleFileRule(null, 5))->getMessage());
    }

    public function testMultipleFileRuleBetweenConstructorUsesLang(): void
    {
        $this->assertSame('Upload between 1 and 3 files', (new MultipleFileRule(1, 3))->getMessage());
    }

    public function testMultipleFileRuleMinViolationUsesLangAtRuntime(): void
    {
        $rule = new MultipleFileRule(3);
        $rule(['name' => ['a.jpg', 'b.jpg'], 'error' => [0, 0]]);
        $this->assertSame('Upload at least 3 files', $rule->getMessage());
    }

    public function testMultipleFileRuleMaxViolationUsesLangAtRuntime(): void
    {
        $rule = new MultipleFileRule(null, 2);
        $rule(['name' => ['a.jpg', 'b.jpg', 'c.jpg'], 'error' => [0, 0, 0]]);
        $this->assertSame('Upload no more than 2 files', $rule->getMessage());
    }

    // =========================================================
    // ImageRule — uses a test double to avoid filesystem calls
    // =========================================================

    public function testImageRuleDefaultMessageUsesLang(): void
    {
        $rule = new ImageRule(['min_width' => 100]);
        $this->assertSame('Invalid image dimensions', $rule->getMessage());
    }

    public function testImageRuleInvalidFileUsesLang(): void
    {
        $rule = new class (['min_width' => 100]) extends ImageRule {
            protected function isImage(string $path): bool
            {
                return false;
            }
        };
        $rule(['tmp_name' => '/fake/path.jpg']);
        $this->assertSame('Not a valid image', $rule->getMessage());
    }

    public function testImageRuleMinWidthViolationUsesLang(): void
    {
        $rule = new class (['min_width' => 800]) extends ImageRule {
            protected function isImage(string $path): bool
            {
                return true;
            }

            protected function getDimensions(string $path): array
            {
                return ['width' => 400, 'height' => 600];
            }
        };
        $rule(['tmp_name' => '/fake/path.jpg']);
        $this->assertSame('Image width must be at least 800 px', $rule->getMessage());
    }

    public function testImageRuleMaxWidthViolationUsesLang(): void
    {
        $rule = new class (['max_width' => 200]) extends ImageRule {
            protected function isImage(string $path): bool
            {
                return true;
            }

            protected function getDimensions(string $path): array
            {
                return ['width' => 400, 'height' => 300];
            }
        };
        $rule(['tmp_name' => '/fake/path.jpg']);
        $this->assertSame('Image width must not exceed 200 px', $rule->getMessage());
    }

    public function testImageRuleMinHeightViolationUsesLang(): void
    {
        $rule = new class (['min_height' => 600]) extends ImageRule {
            protected function isImage(string $path): bool
            {
                return true;
            }

            protected function getDimensions(string $path): array
            {
                return ['width' => 800, 'height' => 300];
            }
        };
        $rule(['tmp_name' => '/fake/path.jpg']);
        $this->assertSame('Image height must be at least 600 px', $rule->getMessage());
    }

    public function testImageRuleMaxHeightViolationUsesLang(): void
    {
        $rule = new class (['max_height' => 100]) extends ImageRule {
            protected function isImage(string $path): bool
            {
                return true;
            }

            protected function getDimensions(string $path): array
            {
                return ['width' => 800, 'height' => 300];
            }
        };
        $rule(['tmp_name' => '/fake/path.jpg']);
        $this->assertSame('Image height must not exceed 100 px', $rule->getMessage());
    }
}
