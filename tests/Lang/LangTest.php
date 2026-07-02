<?php

declare(strict_types=1);

use Lightpack\Lang\Lang;
use PHPUnit\Framework\TestCase;

class LangTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/lightpack-lang-test-' . uniqid();
        @mkdir($this->tempDir . '/en', 0777, true);
        @mkdir($this->tempDir . '/hi', 0777, true);
        @mkdir($this->tempDir . '/ar', 0777, true);
        @mkdir($this->tempDir . '/ru', 0777, true);

        file_put_contents($this->tempDir . '/en/messages.php', '<?php return [
            "hello" => "Hello",
            "welcome" => "Welcome, :name!",
            "items" => ":count item|:count items",
        ];');

        file_put_contents($this->tempDir . '/hi/messages.php', '<?php return [
            "hello" => "नमस्ते",
            "welcome" => "स्वागत है, :name!",
        ];');

        file_put_contents($this->tempDir . '/en/validation.php', '<?php return [
            "required" => "The :field field is required.",
        ];');

        file_put_contents($this->tempDir . '/en/forms.php', '<?php return [
            "signup" => [
                "title" => "Sign Up",
                "submit" => "Create Account",
            ],
        ];');

        file_put_contents($this->tempDir . '/ar/messages.php', '<?php return [
            "articles" => "{0} لا مقالات|{1} مقالة واحدة|{2} مقالتان|{3} :count مقالات|{4} :count مقالة|{5} :count مقالة",
        ];');

        file_put_contents($this->tempDir . '/ru/messages.php', '<?php return [
            "articles" => "{0} :count статей|{1} :count статья|{2} :count статьи",
        ];');
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->tempDir);
    }

    private function recursiveDelete(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    $path = $dir . '/' . $object;
                    if (is_dir($path)) {
                        $this->recursiveDelete($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public function testGetLocale()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('en', $lang->getLocale());
    }

    public function testSetLocale()
    {
        $lang = new Lang('en', $this->tempDir);
        $lang->setLocale('hi');
        $this->assertEquals('hi', $lang->getLocale());
    }

    public function testGetTranslation()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('Hello', $lang->get('messages.hello'));
    }

    public function testGetTranslationWithPlaceholders()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('Welcome, John!', $lang->get('messages.welcome', ['name' => 'John']));
    }

    public function testGetTranslationFallback()
    {
        $lang = new Lang('hi', $this->tempDir);
        // "hello" key exists in hi/messages.php
        $this->assertEquals('नमस्ते', $lang->get('messages.hello'));
        // "items" key does not exist in hi/messages.php, should fallback to en
        $this->assertEquals(':count item|:count items', $lang->get('messages.items'));
    }

    public function testGetTranslationReturnsKeyIfNotFound()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('messages.nonexistent', $lang->get('messages.nonexistent'));
    }

    public function testChoiceSingular()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('1 item', $lang->choice('messages.items', 1, ['count' => 1]));
    }

    public function testChoicePlural()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('5 items', $lang->choice('messages.items', 5, ['count' => 5]));
    }

    public function testChoiceFallback()
    {
        $lang = new Lang('hi', $this->tempDir);
        // "items" doesn't exist in hi, fallback to en
        $this->assertEquals('1 item', $lang->choice('messages.items', 1, ['count' => 1]));
    }

    public function testHasReturnsTrueForExistingKey()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertTrue($lang->has('messages.hello'));
    }

    public function testHasReturnsFalseForMissingKey()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertFalse($lang->has('messages.nonexistent'));
    }

    public function testHasChecksFallbackLocale()
    {
        $lang = new Lang('hi', $this->tempDir);
        // "items" exists in fallback (en) but not in hi
        $this->assertTrue($lang->has('messages.items'));
    }

    public function testDefaultFileWhenNoDotInKey()
    {
        $lang = new Lang('en', $this->tempDir);
        // Without dot notation, should default to 'messages' file
        $this->assertEquals('Hello', $lang->get('hello'));
    }

    public function testDifferentFile()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('The email field is required.', $lang->get('validation.required', ['field' => 'email']));
    }

    public function testLocaleOverride()
    {
        $lang = new Lang('en', $this->tempDir);
        // Override to hi locale
        $this->assertEquals('नमस्ते', $lang->get('messages.hello', [], 'hi'));
    }

    public function testNestedTranslation()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('Sign Up', $lang->get('forms.signup.title'));
        $this->assertEquals('Create Account', $lang->get('forms.signup.submit'));
    }

    public function testNestedHas()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertTrue($lang->has('forms.signup.title'));
        $this->assertFalse($lang->has('forms.signup.nonexistent'));
    }

    public function testNestedFallbackToMissingKey()
    {
        $lang = new Lang('en', $this->tempDir);
        // Missing nested key returns the full dot-notation key
        $this->assertEquals('forms.signup.nonexistent', $lang->get('forms.signup.nonexistent'));
    }

    public function testArabicIndexedPlural()
    {
        $lang = new Lang('ar', $this->tempDir);
        $this->assertEquals('لا مقالات', $lang->choice('messages.articles', 0, ['count' => 0]));
        $this->assertEquals('مقالة واحدة', $lang->choice('messages.articles', 1, ['count' => 1]));
        $this->assertEquals('مقالتان', $lang->choice('messages.articles', 2, ['count' => 2]));
        $this->assertEquals('5 مقالات', $lang->choice('messages.articles', 5, ['count' => 5]));
        $this->assertEquals('15 مقالة', $lang->choice('messages.articles', 15, ['count' => 15]));
        $this->assertEquals('100 مقالة', $lang->choice('messages.articles', 100, ['count' => 100]));
    }

    public function testRussianIndexedPlural()
    {
        $lang = new Lang('ru', $this->tempDir);
        $this->assertEquals('0 статей', $lang->choice('messages.articles', 0, ['count' => 0]));
        $this->assertEquals('1 статья', $lang->choice('messages.articles', 1, ['count' => 1]));
        $this->assertEquals('2 статьи', $lang->choice('messages.articles', 2, ['count' => 2]));
        $this->assertEquals('5 статей', $lang->choice('messages.articles', 5, ['count' => 5]));
        $this->assertEquals('21 статья', $lang->choice('messages.articles', 21, ['count' => 21]));
        $this->assertEquals('25 статей', $lang->choice('messages.articles', 25, ['count' => 25]));
    }

    public function testSimplePluralStillWorks()
    {
        // Non-indexed format should still use simple singular/plural logic
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('1 item', $lang->choice('messages.items', 1, ['count' => 1]));
        $this->assertEquals('5 items', $lang->choice('messages.items', 5, ['count' => 5]));
    }

    public function testGetReturnsKeyWhenValueIsArray()
    {
        // lang('forms.signup') resolves to an array (not a leaf) — should return the key, not blow up
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('forms.signup', $lang->get('forms.signup'));
    }

    public function testChoiceReturnsKeyWhenValueIsArray()
    {
        // choice() on a non-leaf key should also return the key safely
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('forms.signup', $lang->choice('forms.signup', 3));
    }

    public function testChoiceWithLocaleOverride()
    {
        // choice() should respect the locale override parameter
        $lang = new Lang('en', $this->tempDir);
        // Russian locale override: 1 → form 1 (статья)
        $this->assertEquals('1 статья', $lang->choice('messages.articles', 1, ['count' => 1], 'ru'));
        // Russian locale override: 5 → form 0 (статей)
        $this->assertEquals('5 статей', $lang->choice('messages.articles', 5, ['count' => 5], 'ru'));
    }

    public function testSetLocaleRule()
    {
        // Custom rule: always singular (form 1)
        $lang = new Lang('en', $this->tempDir);
        $lang->setLocaleRule('xx', fn (int $n) => 1);
        // Simple pipe — form index from custom rule will pick form[1] = "items" (plural side)
        // But our simple format doesn't use indexed forms, so this tests the delegation works
        $lang->setLocale('xx');
        // With simple format, custom rules don't affect outcome (no {n} prefix)
        // Verify setLocaleRule doesn't throw and the lang instance works normally
        $this->assertEquals('5 items', $lang->choice('messages.items', 5, ['count' => 5]));
    }

    public function testSetLocaleRuleIndexed()
    {
        // Custom rule + indexed format
        $lang = new Lang('ru', $this->tempDir);
        // Override Russian to always return form 0 (many)
        $lang->setLocaleRule('ru', fn (int $n) => 0);
        $this->assertEquals('1 статей', $lang->choice('messages.articles', 1, ['count' => 1]));
    }

    public function testRomanianIndexedPlural()
    {
        // ro: 1 → one (form 1); 0 and 2-19 → few (form 2); 20+ → other (form 0)
        @mkdir($this->tempDir . '/ro', 0777, true);
        file_put_contents($this->tempDir . '/ro/messages.php', '<?php return [
            "articles" => "{0} :count articole|{1} :count articol|{2} :count articole",
        ];');

        $lang = new Lang('ro', $this->tempDir);
        $this->assertEquals('1 articol', $lang->choice('messages.articles', 1, ['count' => 1]));
        $this->assertEquals('2 articole', $lang->choice('messages.articles', 2, ['count' => 2]));
        $this->assertEquals('19 articole', $lang->choice('messages.articles', 19, ['count' => 19]));
        $this->assertEquals('20 articole', $lang->choice('messages.articles', 20, ['count' => 20]));
    }
}
